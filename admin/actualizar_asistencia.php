<?php
require_once "seguridad_admin.php";
require_once "../conexion.php";
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
header("Location: reservas.php");
exit;
}
$id_reserva = filter_input(
INPUT_POST,
"id_reserva",
FILTER_VALIDATE_INT
);
$id_sesion = filter_input(
INPUT_POST,
"id_sesion",
FILTER_VALIDATE_INT
);
$asistencia =
trim($_POST["asistencia"] ?? "");
$valores_permitidos = [
"pendiente",
"asistio",
"no_asistio"
];
if (
!$id_reserva ||
!$id_sesion ||
!in_array(
$asistencia,
$valores_permitidos,
true
)
) {
header("Location: reservas.php?error=datos");
exit;
}
$sql = "
UPDATE reservas
SET asistencia = ?
WHERE id_reserva = ?
AND id_sesion = ?
AND estado = 'confirmada'
";
$stmt = $conexion->prepare($sql);
$stmt->bind_param(
"sii",
$asistencia,
$id_reserva,
$id_sesion
);
$stmt->execute();
if ($stmt->affected_rows === 0) {
$stmt->close();
$conexion->close();
header(
"Location: detalles_sesion.php?id=" .
$id_sesion .
"&error=actualizar"
);
exit;
}
$stmt->close();
$conexion->close();
header(
"Location: detalles_sesion.php?id=" .
$id_sesion .
"&mensaje=asistencia"
);
exit;
