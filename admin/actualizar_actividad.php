<?php
require_once "seguridad_admin.php";
require_once __DIR__ . '/../conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
header('Location: actividades.php');
exit;
}

$id_actividad = filter_input(
INPUT_POST,
'id_actividad',
FILTER_VALIDATE_INT
);
if (!$id_actividad) {
header('Location: actividades.php?error=no_encontrada');
exit;
}

$nombre = trim($_POST['nombre'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$categoria = trim($_POST['categoria'] ?? '');
$nivel = trim($_POST['nivel'] ?? '');
$duracion = $_POST['duracion_minutos'] ?? '';
$imagen = trim($_POST['imagen'] ?? '');
$activa = isset($_POST['activa']) ? 1 : 0;
$categorias_validas = [
'Deporte',
'Bienestar',
'Cultura',
'Formación',
'Ocio'
];
$niveles_validos = [
'inicial',
'intermedio',
'avanzado',
'todos'
];
$errores = [];
if ($nombre === '') {
$errores[] = 'El nombre es obligatorio.';
}
if (mb_strlen($nombre) > 120) {
$errores[] = 'El nombre es demasiado largo.';
}
if ($descripcion === '') {
$errores[] = 'La descripción es obligatoria.';
}
if (!in_array(
$categoria,
$categorias_validas,
true
)) {
$errores[] = 'La categoría no es válida.';
}
if (!in_array(
$nivel,
$niveles_validos,
true
)) {
$errores[] = 'El nivel no es válido.';
}
$duracion = filter_var(
    $duracion,
FILTER_VALIDATE_INT,
[
'options' => [
'min_range' => 15,
'max_range' => 480
]
]
);
if ($duracion === false) {
$errores[] =
'La duración debe estar entre 15 y 480 minutos.';
}
if (mb_strlen($imagen) > 255) {
$errores[] =
'El nombre de la imagen es demasiado largo.';
}
if ($errores) {
header("Location: editar_actividad.php?id_actividad=$id_actividad&error=1");
exit;
}
$sql = "
UPDATE actividades SET
nombre = ?,
descripcion = ?,
categoria = ?,
nivel = ?,
duracion_minutos = ?,
imagen = ?,
activa = ?
WHERE id_actividad = ?
";
$stmt = $conexion->prepare($sql);
$stmt->bind_param(
'ssssisii',
$nombre,
$descripcion,
$categoria,
$nivel,
$duracion,
$imagen,
$activa,
$id_actividad
);
$stmt->execute();
header('Location: actividades.php?mensaje=actualizada');
exit;
