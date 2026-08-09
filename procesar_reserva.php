<?php
require_once "seguridad.php";
require_once "conexion.php";
require_once "funciones.php";
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
header("Location: sesiones.php");
exit;
}
$id_sesion = filter_input(
INPUT_POST,
"id_sesion",
FILTER_VALIDATE_INT
);
$id_usuario = idUsuarioActual();
if (!$id_sesion || !$id_usuario) {
header("Location: sesiones.php?error=datos");
exit;
}
try {
$conexion->begin_transaction();
/*
|--------------------------------------------------------------------------
| 1. Bloquear y consultar la sesión
|--------------------------------------------------------------------------
*/

$sql_sesion = "
SELECT
id_sesion,
fecha,
hora_inicio,
aforo,
estado
FROM sesiones
WHERE id_sesion = ?
FOR UPDATE
";
$stmt_sesion = $conexion->prepare($sql_sesion);
$stmt_sesion->bind_param("i", $id_sesion);
$stmt_sesion->execute();
$resultado_sesion = $stmt_sesion->get_result();
$sesion = $resultado_sesion->fetch_assoc();
$stmt_sesion->close();
if (!$sesion) {
throw new Exception("La sesión no existe.");
}
if (
!in_array(
$sesion["estado"],
["programada", "completa"],
true
)
) {
throw new Exception(
"La sesión no admite reservas."
);
}
$inicio = new DateTime(
$sesion["fecha"] .
" " .
$sesion["hora_inicio"]
);
if ($inicio <= new DateTime()) {
throw new Exception(
"La sesión ya ha comenzado."
);
}
/*
|--------------------------------------------------------------------------
| 2. Comprobar si ya tiene una reserva
|--------------------------------------------------------------------------
*/

$sql_reserva = "
SELECT id_reserva, estado
FROM reservas
WHERE id_sesion = ?
AND id_usuario = ?
FOR UPDATE
";
$stmt_reserva = $conexion->prepare($sql_reserva);
$stmt_reserva->bind_param(
"ii",
$id_sesion,
$id_usuario
);
$stmt_reserva->execute();
$reserva_anterior =
$stmt_reserva->get_result()->fetch_assoc();
$stmt_reserva->close();
if (
$reserva_anterior &&
$reserva_anterior["estado"] === "confirmada"
) {
throw new Exception(
"Ya tienes una reserva confirmada."
);
}
/*
|--------------------------------------------------------------------------
| 3. Comprobar si ya está esperando
|--------------------------------------------------------------------------
*/

$sql_lista = "
SELECT id_espera, estado
FROM lista_espera
WHERE id_sesion = ?
AND id_usuario = ?
FOR UPDATE
";
$stmt_lista = $conexion->prepare($sql_lista);
$stmt_lista->bind_param(
"ii",
$id_sesion,
$id_usuario
);
$stmt_lista->execute();
$lista_anterior =
$stmt_lista->get_result()->fetch_assoc();
$stmt_lista->close();
if (
$lista_anterior &&
$lista_anterior["estado"] === "esperando"
) {
throw new Exception(
"Ya estás en la lista de espera."
);
}
/*
|--------------------------------------------------------------------------
| 4. Contar las plazas ocupadas
|--------------------------------------------------------------------------
*/

$sql_contar = "
SELECT COUNT(*) AS total
FROM reservas
WHERE id_sesion = ?
AND estado = 'confirmada'
";
$stmt_contar = $conexion->prepare($sql_contar);
$stmt_contar->bind_param("i", $id_sesion);
$stmt_contar->execute();
$plazas_ocupadas = (int) $stmt_contar
->get_result()
->fetch_assoc()["total"];
$stmt_contar->close();
/*
|--------------------------------------------------------------------------
| 5. Crear una reserva si queda aforo
|--------------------------------------------------------------------------
*/

if ($plazas_ocupadas < (int) $sesion["aforo"]) {
$codigo_reserva = strtoupper(
bin2hex(random_bytes(8))
);
if ($reserva_anterior) {
$sql_guardar = "
UPDATE reservas
SET
estado = 'confirmada',
asistencia = 'pendiente',
fecha_reserva = NOW(),
codigo_reserva = ?
WHERE id_reserva = ?
";
$stmt_guardar =
$conexion->prepare($sql_guardar);
$stmt_guardar->bind_param(
"si",
$codigo_reserva,
$reserva_anterior["id_reserva"]
);
} else {
$sql_guardar = "
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
$stmt_guardar =
$conexion->prepare($sql_guardar);
$stmt_guardar->bind_param(
"iis",
$id_sesion,
$id_usuario,
$codigo_reserva
);
}
$stmt_guardar->execute();
$stmt_guardar->close();
/*
| Si existía una antigua solicitud de espera,
| la marcamos como promocionada.
*/

if ($lista_anterior) {
    $sql_actualizar_lista = "
UPDATE lista_espera
SET
estado = 'promocionada'
WHERE id_espera = ?
";
$stmt_actualizar_lista =
$conexion->prepare(
$sql_actualizar_lista
);
$stmt_actualizar_lista->bind_param(
"i",
$lista_anterior["id_espera"]
);
$stmt_actualizar_lista->execute();
$stmt_actualizar_lista->close();
}
$nuevo_total = $plazas_ocupadas + 1;
$nuevo_estado =
$nuevo_total >= (int) $sesion["aforo"]
? "completa"
: "programada";
$sql_estado = "
UPDATE sesiones
SET estado = ?
WHERE id_sesion = ?
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
"?mensaje=confirmada"
);
exit;
}
/*
|--------------------------------------------------------------------------
| 6. Incorporar a la lista de espera
|--------------------------------------------------------------------------
*/

if ($lista_anterior) {
$sql_espera = "
UPDATE lista_espera
SET
estado = 'esperando',
fecha_solicitud = NOW()
WHERE id_espera = ?
";
$stmt_espera =
$conexion->prepare($sql_espera);
$stmt_espera->bind_param(
"i",
$lista_anterior["id_espera"]
);
} else {
$sql_espera = "
INSERT INTO lista_espera (
id_sesion,
id_usuario,
estado
)
VALUES (?, ?, 'esperando')
";
$stmt_espera =
$conexion->prepare($sql_espera);
$stmt_espera->bind_param(
"ii",
$id_sesion,
$id_usuario
);
}
$stmt_espera->execute();
$stmt_espera->close();
$sql_completa = "
UPDATE sesiones
SET estado = 'completa'
WHERE id_sesion = ?
";
$stmt_completa =
$conexion->prepare($sql_completa);
$stmt_completa->bind_param(
"i",
$id_sesion
);
$stmt_completa->execute();
$stmt_completa->close();
$conexion->commit();
header(
"Location: mis_reservas.php" .
"?mensaje=espera"
);
exit;
} catch (Throwable $error) {
$conexion->rollback();
header(
"Location: detalle_sesion.php?id=" .
$id_sesion .
"&error=" .
urlencode($error->getMessage())
);
exit;
}