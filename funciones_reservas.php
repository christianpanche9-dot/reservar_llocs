<?php
function generarCodigoReserva(): string
{
return strtoupper(
    bin2hex(random_bytes(8))
);
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
SELECT id_sesion, aforo, estado
FROM sesiones
WHERE id_sesion = ?
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
| 4. Buscar la primera espera
|--------------------------------------------------------------------------
*/

$sql_espera = "
SELECT id_espera, id_usuario
FROM lista_espera
WHERE id_sesion = ?
AND estado = 'esperando'
ORDER BY
fecha_solicitud ASC,
id_espera ASC
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
estado = 'confirmada',
asistencia = 'pendiente',
fecha_reserva = NOW(),
codigo_reserva = ?
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
'confirmada',
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
AND estado = 'confirmada'
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