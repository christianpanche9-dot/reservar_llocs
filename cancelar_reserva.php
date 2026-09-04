<?php
require_once "seguridad.php";
require_once "conexion.php";
require_once "funciones.php";
require_once "funciones_reservas.php";
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
header("Location: mis_reservas.php");
exit;
}
$id_reserva = filter_input(
INPUT_POST,
"id_reserva",
FILTER_VALIDATE_INT
);
if (!$id_reserva) {
header("Location: mis_reservas.php?error=datos");
exit;
}
try {
cancelarReservaYPromocionar(
$conexion,
$id_reserva,
idUsuarioActual()
);
$conexion->close();
header(
"Location: mis_reservas.php" .
"?mensaje=cancelada"
);
exit;
} catch (Throwable $error) {
$conexion->close();
header(
"Location: mis_reservas.php?error=" .
urlencode($error->getMessage())
);
exit;
}