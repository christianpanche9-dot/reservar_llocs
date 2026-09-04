<?php
require_once "seguridad_admin.php";
require_once __DIR__ . '/../conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
header('Location: nuevo_monitor.php');
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
header('Location: nuevo_monitor.php?error=datos');
exit;
}
$sql_comprobar = "
SELECT id_monitor
FROM monitores
WHERE email = ?
";
$stmt_comprobar =
$conexion->prepare($sql_comprobar);
$stmt_comprobar->bind_param(
's',
$email
);
$stmt_comprobar->execute();
$resultado_comprobar =
$stmt_comprobar->get_result();
if ($resultado_comprobar->num_rows > 0) {
header('Location: nuevo_monitor.php?error=email');
exit;
}
$sql_insertar = "
INSERT INTO monitores (
nombre,
apellidos,
email,
telefono,
especialidad,
activo
)
VALUES (?, ?, ?, ?, ?, ?)
";
$stmt_insertar =
$conexion->prepare($sql_insertar);
$stmt_insertar->bind_param(
'sssssi',
$nombre,
$apellidos,
$email,
$telefono,
$especialidad,
$activo
);
$stmt_insertar->execute();
header('Location: monitores.php?mensaje=creado');
exit;