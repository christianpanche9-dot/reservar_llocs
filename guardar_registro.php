<?php
require_once "conexion.php";
require_once "funciones.php";
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
header("Location: registro.php");
exit;
}
if (usuarioAutenticado()) {
header("Location: mi_cuenta.php");
exit;
}
$nombre = trim($_POST["nombre"] ?? "");
$apellidos = trim($_POST["apellidos"] ?? "");
$email = strtolower(trim($_POST["email"] ?? ""));
$telefono = trim($_POST["telefono"] ?? "");
$password = $_POST["password"] ?? "";
$repetir_password = $_POST["repetir_password"] ?? "";
$_SESSION["datos_registro"] = [
    "nombre" => $nombre,
    "apellidos" => $apellidos,
    "email" => $email,
    "telefono" => $telefono
];
if (
$nombre === "" ||
$apellidos === "" ||
$email === "" ||
$password === "" ||
$repetir_password === ""
) {
    header("Location: registro.php?error=datos");
    exit;
}
if (
mb_strlen($nombre) > 100 ||
mb_strlen($apellidos) > 150 ||
mb_strlen($email) > 150 ||
mb_strlen($telefono) > 20
) {
header("Location: registro.php?error=datos");
exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
header("Location: registro.php?error=datos");
exit;
}
if (strlen($password) < 8) {
header("Location: registro.php?error=datos");
exit;
}
if ($password !== $repetir_password) {
header("Location: registro.php?error=password");
exit;
}
$sql_comprobar = "
SELECT id_usuario
FROM usuarios
WHERE email = ?
LIMIT 1
";
$stmt_comprobar = $conexion->prepare($sql_comprobar);
$stmt_comprobar->bind_param("s", $email);
$stmt_comprobar->execute();
$resultado = $stmt_comprobar->get_result();
if ($resultado->num_rows > 0) {
$stmt_comprobar->close();
$conexion->close();
header("Location: registro.php?error=email");
exit;
}
$stmt_comprobar->close();
$password_hash = password_hash(
$password,
PASSWORD_DEFAULT
);
$sql_insertar = "
INSERT INTO usuarios (
nombre,
apellidos,
email,
password,
telefono,
rol
)
VALUES (?, ?, ?, ?, ?, 'cliente')
";
$stmt_insertar = $conexion->prepare($sql_insertar);
$stmt_insertar->bind_param(
"sssss",
$nombre,
$apellidos,
$email,
$password_hash,
$telefono
);
if ($stmt_insertar->execute()) {
unset($_SESSION["datos_registro"]);
$stmt_insertar->close();
$conexion->close();
header("Location: login.php?mensaje=registro");
exit;
}
$stmt_insertar->close();
$conexion->close();
header("Location: registro.php?error=guardar");
exit;