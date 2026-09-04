<?php
function generarCodigoReserva(): string
{
return strtoupper(
    bin2hex(random_bytes(8))
);
}
/**
 * Resuelve cómo se paga una reserva dentro de una transacción ya abierta.
 * Si es con bono, bloquea, comprueba y descuenta un uso.
 * Devuelve [importe, id_bono_cliente_usado].
 */
function resolverMetodoPago(
mysqli $conexion,
int $id_usuario,
string $metodo_pago,
float $precio
): array {
if ($metodo_pago !== "bono") {
return [$precio, null];
}
$sql_bono = "
SELECT id_bono_cliente, usos_disponibles
FROM bonos_clientes
WHERE id_usuario = ?
AND estado = 'activo'
AND usos_disponibles > 0
ORDER BY fecha_compra ASC
LIMIT 1
FOR UPDATE
";
$stmt_bono = $conexion->prepare($sql_bono);
$stmt_bono->bind_param("i", $id_usuario);
$stmt_bono->execute();
$bono = $stmt_bono->get_result()->fetch_assoc();
$stmt_bono->close();
if (!$bono) {
throw new Exception(
"No tienes bonos disponibles."
);
}
$id_bono_usado = (int) $bono["id_bono_cliente"];
$sql_descontar = "
UPDATE bonos_clientes
SET usos_disponibles = usos_disponibles - 1
WHERE id_bono_cliente = ?
AND usos_disponibles > 0
";
$stmt_descontar = $conexion->prepare($sql_descontar);
$stmt_descontar->bind_param("i", $id_bono_usado);
$stmt_descontar->execute();
$consumido = $stmt_descontar->affected_rows === 1;
$stmt_descontar->close();
if (!$consumido) {
throw new Exception(
"No se ha podido consumir el bono."
);
}
$sql_agotado = "
UPDATE bonos_clientes
SET estado = 'agotado'
WHERE id_bono_cliente = ?
AND usos_disponibles = 0
";
$stmt_agotado = $conexion->prepare($sql_agotado);
$stmt_agotado->bind_param("i", $id_bono_usado);
$stmt_agotado->execute();
$stmt_agotado->close();
return [0.00, $id_bono_usado];
}
function cancelarReservaYPromocionar(
mysqli $conexion,
int $id_reserva,
?int $id_usuario = null
): int {
$conexion->begin_transaction();
try {
/*
|--------------------------------------------------------------------------
| 1. Consultar la reserva
|--------------------------------------------------------------------------
*/

$sql_reserva = "
SELECT
r.id_reserva,
r.id_sesion,
r.id_usuario,
r.estado,
r.metodo_pago,
r.id_bono_cliente,
s.fecha,
s.hora_inicio
FROM reservas r
INNER JOIN sesiones s
ON r.id_sesion = s.id_sesion
WHERE r.id_reserva = ?
";
if ($id_usuario !== null) {
$sql_reserva .= "
AND r.id_usuario = ?
";
}
$sql_reserva .= "
FOR UPDATE
";
$stmt_reserva =
$conexion->prepare($sql_reserva);
if ($id_usuario !== null) {
$stmt_reserva->bind_param(
"ii",
$id_reserva,
$id_usuario
);
} else {
$stmt_reserva->bind_param(
"i",
$id_reserva
);
}
$stmt_reserva->execute();
$reserva = $stmt_reserva
->get_result()
->fetch_assoc();
$stmt_reserva->close();
if (!$reserva) {
throw new Exception(
"La reserva no existe."
);
}
if ($reserva["estado"] !== "confirmada") {
throw new Exception(
"La reserva no está confirmada."
);
}
$id_sesion =
(int) $reserva["id_sesion"];
/*
|--------------------------------------------------------------------------
| 2. Bloquear la sesión
|--------------------------------------------------------------------------
*/
$sql_sesion = "
SELECT
s.id_sesion,
s.aforo,
s.estado,
s.fecha,
TIME_FORMAT(s.hora_inicio, '%H:%i') AS inicio,
a.nombre AS actividad,
e.nombre AS espacio
FROM sesiones s
JOIN actividades a ON a.id_actividad = s.id_actividad
JOIN espacios e ON e.id_espacio = s.id_espacio
WHERE s.id_sesion = ?
FOR UPDATE
";
$stmt_sesion =
$conexion->prepare($sql_sesion);
$stmt_sesion->bind_param(
"i",
$id_sesion
);
$stmt_sesion->execute();
$sesion = $stmt_sesion
->get_result()
->fetch_assoc();
$stmt_sesion->close();
if (!$sesion) {
throw new Exception(
"La sesión no existe."
);
}
/*
|--------------------------------------------------------------------------
| 3. Cancelar la reserva
|--------------------------------------------------------------------------
*/

$sql_cancelar = "
UPDATE reservas
SET
estado = 'cancelada',
asistencia = 'pendiente'
WHERE id_reserva = ?
";
$stmt_cancelar =
$conexion->prepare($sql_cancelar);
$stmt_cancelar->bind_param(
"i",
$id_reserva
);
$stmt_cancelar->execute();
$stmt_cancelar->close();
/*
|--------------------------------------------------------------------------
| 3.1 Devolver el uso si se pagó con bono
|--------------------------------------------------------------------------
*/

if (
$reserva["metodo_pago"] === "bono" &&
$reserva["id_bono_cliente"] !== null
) {
$sql_devolver = "
UPDATE bonos_clientes
SET
usos_disponibles = usos_disponibles + 1,
estado = 'activo'
WHERE id_bono_cliente = ?
";
$stmt_devolver =
$conexion->prepare($sql_devolver);
$stmt_devolver->bind_param(
"i",
$reserva["id_bono_cliente"]
);
$stmt_devolver->execute();
$stmt_devolver->close();
} elseif ($reserva["metodo_pago"] === "pago") {
$sql_reembolsar = "
UPDATE pagos
SET estado = 'reembolsado'
WHERE id_reserva = ?
AND estado = 'pagado'
";
$stmt_reembolsar =
$conexion->prepare($sql_reembolsar);
$stmt_reembolsar->bind_param(
"i",
$id_reserva
);
$stmt_reembolsar->execute();
$stmt_reembolsar->close();
}
/*
|--------------------------------------------------------------------------
| 4. Buscar la primera espera
|--------------------------------------------------------------------------
*/

$sql_espera = "
SELECT
le.id_espera,
le.id_usuario,
u.nombre,
u.email
FROM lista_espera le
JOIN usuarios u ON u.id_usuario = le.id_usuario
WHERE le.id_sesion = ?
AND le.estado = 'esperando'
AND u.activo = 1
ORDER BY
le.fecha_solicitud ASC,
le.id_espera ASC
LIMIT 1
FOR UPDATE
";
$stmt_espera =
$conexion->prepare($sql_espera);
$stmt_espera->bind_param(
"i",
$id_sesion
);
$stmt_espera->execute();
$primera_espera = $stmt_espera
->get_result()
->fetch_assoc();
$stmt_espera->close();
/*
|--------------------------------------------------------------------------
| 5. Promocionar
|--------------------------------------------------------------------------
*/
if ($primera_espera) {
$id_promocionado = (int)
$primera_espera["id_usuario"];
$sql_anterior = "
SELECT id_reserva
FROM reservas
WHERE id_sesion = ?
AND id_usuario = ?
FOR UPDATE
";
$stmt_anterior =
$conexion->prepare($sql_anterior);
$stmt_anterior->bind_param(
"ii",
$id_sesion,
$id_promocionado
);
$stmt_anterior->execute();
$anterior = $stmt_anterior
->get_result()
->fetch_assoc();
$stmt_anterior->close();
$codigo = generarCodigoReserva();
if ($anterior) {
$sql_promocionar = "
UPDATE reservas
SET
estado = 'pendiente_pago',
asistencia = 'pendiente',
fecha_reserva = NOW(),
codigo_reserva = ?,
metodo_pago = NULL,
importe = NULL,
id_bono_cliente = NULL
WHERE id_reserva = ?
";
$stmt_promocionar =
$conexion->prepare(
$sql_promocionar
);
$stmt_promocionar->bind_param(
"si",
$codigo,
$anterior["id_reserva"]
);
} else {
$sql_promocionar = "
INSERT INTO reservas (
id_sesion,
id_usuario,
estado,
asistencia,
codigo_reserva
)
VALUES (
?,
?,
'pendiente_pago',
'pendiente',
?
)
";
$stmt_promocionar =
$conexion->prepare(
$sql_promocionar
);
$stmt_promocionar->bind_param(
"iis",
$id_sesion,
$id_promocionado,
$codigo
);
}
$stmt_promocionar->execute();
$stmt_promocionar->close();
$sql_promocionada = "
UPDATE lista_espera
SET
estado = 'promocionada'
WHERE id_espera = ?
";
$stmt_promocionada =
$conexion->prepare(
$sql_promocionada
);
$stmt_promocionada->bind_param(
"i",
$primera_espera["id_espera"]
);
$stmt_promocionada->execute();
$stmt_promocionada->close();
/*
|--------------------------------------------------------------------------
| 5.1 Encolar el aviso de plaza pendiente de pago
|--------------------------------------------------------------------------
*/

$asunto =
"Tienes una plaza disponible: " .
$sesion["actividad"];
$cuerpo =
"Hola " . $primera_espera["nombre"] . ",\n\n" .
"Se ha liberado una plaza para ti en la sesión " .
"por la que estabas en lista de espera.\n\n" .
"Actividad: " . $sesion["actividad"] . "\n" .
"Fecha: " . $sesion["fecha"] . "\n" .
"Hora: " . $sesion["inicio"] . "\n" .
"Espacio: " . $sesion["espacio"] . "\n\n" .
"La plaza queda reservada, pero todavía debes " .
"confirmarla y elegir cómo pagarla desde " .
"Mi cuenta > Mis reservas.\n\n" .
"Reservar Llocs";
$clave_evento =
"plaza_pendiente_pago:" .
$primera_espera["id_espera"];
$sql_notificacion = "
INSERT INTO notificaciones (
id_usuario,
tipo,
destinatario,
asunto,
cuerpo,
clave_evento
)
VALUES (
?,
'plaza_pendiente_pago',
?,
?,
?,
?
)
";
$stmt_notificacion =
$conexion->prepare($sql_notificacion);
$stmt_notificacion->bind_param(
"issss",
$id_promocionado,
$primera_espera["email"],
$asunto,
$cuerpo,
$clave_evento
);
$stmt_notificacion->execute();
$stmt_notificacion->close();
}
/*
|--------------------------------------------------------------------------
| 6. Recalcular el estado
|--------------------------------------------------------------------------
*/

$sql_total = "
SELECT COUNT(*) AS total
FROM reservas
WHERE id_sesion = ?
AND estado IN ('confirmada', 'pendiente_pago')
";
$stmt_total =
$conexion->prepare($sql_total);
$stmt_total->bind_param(
"i",
$id_sesion
);
$stmt_total->execute();
$total = (int) $stmt_total
->get_result()
->fetch_assoc()["total"];
$stmt_total->close();
$nuevo_estado =
$total >= (int) $sesion["aforo"]
? "completa"
: "programada";
$sql_actualizar = "
UPDATE sesiones
SET estado = ?
WHERE id_sesion = ?
AND estado NOT IN (
'cancelada',
'finalizada'
)
";
$stmt_actualizar =
$conexion->prepare(
$sql_actualizar
);
$stmt_actualizar->bind_param(
"si",
$nuevo_estado,
$id_sesion
);
$stmt_actualizar->execute();
$stmt_actualizar->close();
$conexion->commit();
return $id_sesion;
} catch (Throwable $error) {
$conexion->rollback();
throw $error;
}
}