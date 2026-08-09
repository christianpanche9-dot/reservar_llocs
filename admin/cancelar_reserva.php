<?php
require_once "seguridad_admin.php";
require_once "../conexion.php";
require_once "../funciones_reservas.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
header("Location: reservas.php");
exit;
}
$id_reserva = filter_input(
INPUT_POST,
"id_reserva",
FILTER_VALIDATE_INT
);
if (!$id_reserva) {
header("Location: reservas.php?error=datos");
exit;
}
try {
$id_sesion =
cancelarReservaYPromocionar(
$conexion,
$id_reserva
);
$conexion->close();
header(
"Location: detalles_sesion.php?id=" .
$id_sesion .
"&mensaje=cancelada"
);
exit;
} catch (Throwable $error) {
$conexion->close();
header(
"Location: reservas.php?error=" .
urlencode($error->getMessage())
);
exit;
}