<?php
require_once __DIR__ . "/funciones.php";
if (!usuarioAutenticado()) {
$ruta_actual = $_SERVER["REQUEST_URI"] ?? "/";
header(
"Location: login.php?error=acceso&volver=" .
urlencode($ruta_actual)
);
exit;
}