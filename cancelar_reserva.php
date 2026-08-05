<?php
require_once "seguridad.php";
require_once "conexion.php";
require_once "funciones.php";
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
header("Location: mis_reservas.php");
exit;
}
$id_reserva = filter_input(
INPUT_POST,
"id_reserva",
FILTER_VALIDATE_INT
);
$id_usuario = idUsuarioActual();
if (!$id_reserva || !$id_usuario) {
header("Location: mis_reservas.php?error=datos");
exit;
}
try {
$conexion->begin_transaction();

/*
|--------------------------------------------------------------------------
| 1. Consultar la reserva
|--------------------------------------------------------------------------
*/

$sql_reserva = "
SELECT
r.id_reserva,
r.id_sesion,
r.estado,
s.fecha,
s.hora_inicio,
s.estado AS estado_sesion
FROM reservas r
INNER JOIN sesiones s
ON r.id_sesion = s.id_sesion
WHERE r.id_reserva = ?
AND r.id_usuario = ?
FOR UPDATE
";
$stmt_reserva =
$conexion->prepare($sql_reserva);
$stmt_reserva->bind_param(
"ii",
$id_reserva,
$id_usuario
);
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
"La reserva ya está cancelada."
);
}
$inicio = new DateTime(
$reserva["fecha"] .
" " .
$reserva["hora_inicio"]
);
if ($inicio <= new DateTime()) {
throw new Exception(
"La sesión ya ha comenzado."
);
}
$id_sesion = (int) $reserva["id_sesion"];

/*
|--------------------------------------------------------------------------
| 2. Bloquear la sesión
|--------------------------------------------------------------------------
*/

$sql_bloquear = "
SELECT id_sesion, aforo, estado
FROM sesiones
WHERE id_sesion = ?
FOR UPDATE
";
$stmt_bloquear =
$conexion->prepare($sql_bloquear);
$stmt_bloquear->bind_param(
"i",
$id_sesion
);
$stmt_bloquear->execute();
$sesion = $stmt_bloquear
->get_result()
->fetch_assoc();
$stmt_bloquear->close();
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
SET estado = 'cancelada'
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
| 4. Buscar la primera persona en espera
|--------------------------------------------------------------------------
*/

$sql_primera = "
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
$stmt_primera =
$conexion->prepare($sql_primera);
$stmt_primera->bind_param(
"i",
$id_sesion
);
$stmt_primera->execute();
$primera_persona = $stmt_primera
->get_result()
->fetch_assoc();
$stmt_primera->close();

/*
|--------------------------------------------------------------------------
| 5. Promocionar al primer usuario
|--------------------------------------------------------------------------
*/

if ($primera_persona) {
$id_promocionado =
(int) $primera_persona["id_usuario"];
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
$reserva_promocionada = $stmt_anterior
->get_result()
->fetch_assoc();
$stmt_anterior->close();
$codigo = strtoupper(
bin2hex(random_bytes(8))
);
if ($reserva_promocionada) {
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
$conexion->prepare($sql_promocionar);
$stmt_promocionar->bind_param(
"si",
$codigo,
$reserva_promocionada["id_reserva"]
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
$conexion->prepare($sql_promocionar);
$stmt_promocionar->bind_param(
"iis",
$id_sesion,
$id_promocionado,
$codigo
);
}
$stmt_promocionar->execute();
$stmt_promocionar->close();
$sql_lista = "
UPDATE lista_espera
SET
estado = 'promocionada'
WHERE id_espera = ?
";
$stmt_lista =
$conexion->prepare($sql_lista);
$stmt_lista->bind_param(
"i",
$primera_persona["id_espera"]
);
$stmt_lista->execute();
$stmt_lista->close();
}

/*
|--------------------------------------------------------------------------
| 6. Actualizar el estado de la sesión
|--------------------------------------------------------------------------
*/

$sql_contar = "
SELECT COUNT(*) AS total
FROM reservas
WHERE id_sesion = ?
AND estado = 'confirmada'
";
$stmt_contar =
$conexion->prepare($sql_contar);
$stmt_contar->bind_param(
"i",
$id_sesion
);
$stmt_contar->execute();
$total = (int) $stmt_contar
->get_result()
->fetch_assoc()["total"];
$stmt_contar->close();
$nuevo_estado =
$total >= (int) $sesion["aforo"]
? "completa"
: "programada";
$sql_estado = "
UPDATE sesiones
SET estado = ?
WHERE id_sesion = ?
AND estado <> 'cancelada'
AND estado <> 'finalizada'
";
$stmt_estado =
$conexion->prepare($sql_estado);
$stmt_estado->bind_param(
"si",
$nuevo_estado,
$id_sesion
);
$stmt_estado->execute();
$stmt_estado->close();
$conexion->commit();
header(
"Location: mis_reservas.php" .
"?mensaje=cancelada"
);
exit;
} catch (Throwable $error) {
$conexion->rollback();
header(
"Location: mis_reservas.php" .
"?error=" .
urlencode($error->getMessage())
);
exit;
}