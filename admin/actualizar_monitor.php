<?php
require_once "seguridad_admin.php";
require_once __DIR__ . '/../conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
header('Location: monitores.php');
exit;
}

$id_monitor = filter_input(
INPUT_POST,
'id_monitor',
FILTER_VALIDATE_INT
);
if (!$id_monitor) {
header('Location: monitores.php?error=no_encontrado');
exit;
}

$nombre = trim($_POST['nombre'] ?? '');
$apellidos = trim($_POST['apellidos'] ?? '');
$email = trim($_POST['email'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$especialidad = trim($_POST['especialidad'] ?? '');
$activo = isset($_POST['activo']) ? 1 : 0;
$errores = [];
if ($nombre === '') {
$errores[] = 'El nombre es obligatorio.';
}
if ($apellidos === '') {
$errores[] = 'Los apellidos son obligatorios.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
$errores[] = 'El correo no es válido.';
}
if ($especialidad === '') {
$errores[] = 'La especialidad es obligatoria.';
}
if ($errores) {
header("Location: editar_monitor.php?id_monitor=$id_monitor&error=datos");
exit;
}
$sql_comprobar = "
SELECT id_monitor
FROM monitores
WHERE email = ?
AND id_monitor <> ?
";
$stmt_comprobar =
$conexion->prepare($sql_comprobar);
$stmt_comprobar->bind_param(
'si',
$email,
$id_monitor
);
$stmt_comprobar->execute();
$resultado_comprobar =
$stmt_comprobar->get_result();
if ($resultado_comprobar->num_rows > 0) {
header("Location: editar_monitor.php?id_monitor=$id_monitor&error=email");
exit;
}
$sql = "
UPDATE monitores SET
nombre = ?,
apellidos = ?,
email = ?,
telefono = ?,
especialidad = ?,
activo = ?
WHERE id_monitor = ?
";
$stmt =
$conexion->prepare($sql);
$stmt->bind_param(
'sssssii',
$nombre,
$apellidos,
$email,
$telefono,
$especialidad,
$activo,
$id_monitor
);
$stmt->execute();
header('Location: monitores.php?mensaje=actualizado');
exit;
