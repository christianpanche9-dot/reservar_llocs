<?php
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
header("Location: registro.php");
exit;
}
require_once "conexion.php";
$nombre = trim($_POST["nombre"] ?? "");
$apellidos = trim($_POST["apellidos"] ?? "");
$email = trim($_POST["email"] ?? "");
$telefono = trim($_POST["telefono"] ?? "");
$direccion = trim($_POST["direccion"] ?? "");
$password = $_POST["password"] ?? "";
$repetir_password = $_POST["repetir_password"] ?? "";
$errores = [];
if ($nombre === "") {
$errores[] = "El nombre es obligatorio.";
}
if ($apellidos === "") {
$errores[] = "Los apellidos son obligatorios.";
}
if ($email === "") {
$errores[] = "El correo electrónico es obligatorio.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
$errores[] = "El correo electrónico no es válido.";
}
if (strlen($password) < 8) {
$errores[] = "La contraseña debe tener al menos 8 caracteres.";
}
if ($password !== $repetir_password) {
$errores[] = "Las contraseñas no coinciden.";
}
if (!empty($errores)) {
echo "<h1>No se ha podido crear la cuenta</h1>";
echo "<ul>";
foreach ($errores as $error) {
echo "<li>" . htmlspecialchars($error) . "</li>";
}
echo "</ul>";
echo '<a href="registro.php">Volver</a>';
exit;
}
$sql = "SELECT id_usuario
FROM usuarios
WHERE email = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$resultado = $stmt->get_result();
if ($resultado->num_rows > 0) {
echo "<h1>El correo ya está registrado</h1>";
echo '<a href="login.php">Iniciar sesión</a>';
exit;
}
$password_hash = password_hash(
$password,
PASSWORD_DEFAULT
);
$sql = "INSERT INTO usuarios
(nombre, apellidos, email, password,
telefono, direccion)
VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conexion->prepare($sql);
$stmt->bind_param(
"ssssss",
$nombre,
$apellidos,
$email,
$password_hash,
$telefono,
$direccion
);
$stmt->execute();
if ($stmt->affected_rows === 1) {
echo "<h1>Cuenta creada correctamente</h1>";
echo "<p>Ya puedes iniciar sesión.</p>";
echo '<a href="login.php">Iniciar sesión</a>';
} else {
echo "<h1>No se ha podido crear la cuenta</h1>";
}