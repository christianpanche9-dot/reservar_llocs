<?php
require_once "conexion.php";
require_once "funciones.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
header("Location: login.php");
exit;
}
$email = strtolower(trim($_POST["email"] ?? ""));
$password = $_POST["password"] ?? "";
$volver = trim($_POST["volver"] ?? "");
if (
!filter_var($email, FILTER_VALIDATE_EMAIL) ||
$password === ""
) {
header("Location: login.php?error=credenciales");
exit;
}
$sql = "
SELECT
id_usuario,
nombre,
apellidos,
email,
password,
rol,
activo
FROM usuarios
WHERE email = ?
LIMIT 1
";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$resultado = $stmt->get_result();
$usuario = $resultado->fetch_assoc();
if (
!$usuario ||
!password_verify($password, $usuario["password"])
) {
$stmt->close();
$conexion->close();
header("Location: login.php?error=credenciales");
exit;
}
if ((int) $usuario["activo"] !== 1) {
$stmt->close();
$conexion->close();
header("Location: login.php?error=inactivo");
exit;
}
session_regenerate_id(true);
$_SESSION["usuario"] = [
"id_usuario" => (int) $usuario["id_usuario"],
"nombre" => $usuario["nombre"],
"apellidos" => $usuario["apellidos"],
"email" => $usuario["email"],
"rol" => $usuario["rol"]
];
$stmt->close();
$conexion->close();
if ($usuario["rol"] === "admin") {
header("Location: admin/index.php");
exit;
}
if ($volver !== "" && str_starts_with($volver, "/")) {
header("Location: " . $volver);
exit;
}
header("Location: mi_cuenta.php");
exit;