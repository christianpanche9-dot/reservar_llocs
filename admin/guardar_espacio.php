<?php
require_once "seguridad_admin.php";
require_once __DIR__ . '/../conexion.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
header('Location: nuevo_espacio.php');
exit;
}
$nombre = trim($_POST['nombre'] ?? '');
$ubicacion = trim($_POST['ubicacion'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$aforo = $_POST['aforo_maximo'] ?? '';
$activo = isset($_POST['activo']) ? 1 : 0;
$errores = [];
if ($nombre === '') {
$errores[] = 'El nombre es obligatorio.';
}
if ($ubicacion === '') {
$errores[] = 'La ubicación es obligatoria.';
}
$aforo = filter_var(
$aforo,
FILTER_VALIDATE_INT,
[
'options' => [
'min_range' => 1,
'max_range' => 5000
]
]
);
if ($aforo === false) {
$errores[] = 'El aforo no es válido.';
}
if ($errores) {
header('Location: nuevo_espacio.php?error=1');
exit;
}
$sql = "
INSERT INTO espacios (
nombre,
ubicacion,
descripcion,
aforo_maximo,
activo
)
VALUES (?, ?, ?, ?, ?)
";
$stmt = $conexion->prepare($sql);
$stmt->bind_param(
'sssii',
$nombre,
$ubicacion,
$descripcion,
$aforo,
$activo
);
$stmt->execute();
header('Location: espacios.php?mensaje=creado');
exit;