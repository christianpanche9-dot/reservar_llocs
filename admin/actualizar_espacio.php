<?php
require_once "seguridad_admin.php";
require_once __DIR__ . '/../conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
header('Location: espacios.php');
exit;
}

$id_espacio = filter_input(
INPUT_POST,
'id_espacio',
FILTER_VALIDATE_INT
);
if (!$id_espacio) {
header('Location: espacios.php?error=no_encontrado');
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
header("Location: editar_espacio.php?id_espacio=$id_espacio&error=1");
exit;
}

$sql_comprobar = "
SELECT id_espacio
FROM espacios
WHERE nombre = ?
AND id_espacio <> ?
";
$stmt_comprobar = $conexion->prepare($sql_comprobar);
$stmt_comprobar->bind_param('si', $nombre, $id_espacio);
$stmt_comprobar->execute();
$resultado_comprobar = $stmt_comprobar->get_result();
if ($resultado_comprobar->num_rows > 0) {
header("Location: editar_espacio.php?id_espacio=$id_espacio&error=nombre");
exit;
}

$sql = "
UPDATE espacios SET
nombre = ?,
ubicacion = ?,
descripcion = ?,
aforo_maximo = ?,
activo = ?
WHERE id_espacio = ?
";
$stmt = $conexion->prepare($sql);
$stmt->bind_param(
'sssiii',
$nombre,
$ubicacion,
$descripcion,
$aforo,
$activo,
$id_espacio
);
$stmt->execute();
header('Location: espacios.php?mensaje=actualizado');
exit;
