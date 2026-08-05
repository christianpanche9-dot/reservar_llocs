<?php
require_once "funciones.php";
if (usuarioAutenticado()) {
header("Location: mi_cuenta.php");
exit;
}
$error = $_GET["error"] ?? "";
$datos = $_SESSION["datos_registro"] ?? [];
$nombre = $datos["nombre"] ?? "";
$apellidos = $datos["apellidos"] ?? "";
$email = $datos["email"] ?? "";
$telefono = $datos["telefono"] ?? "";
unset($_SESSION["datos_registro"]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta
name="viewport"
content="width=device-width, initial-scale=1.0"
>
<title>Crear una cuenta</title>
<link rel="stylesheet" href="estilos.css">
</head>
<body>
<?php require "menu.php"; ?>
<main class="contenedor">
<h1>Crear una cuenta</h1>
<p>
</p>
Regístrate para reservar plazas en las actividades.
<?php if ($error === "datos"): ?>
<div class="mensaje error">
Revisa los datos introducidos.
</div>
<?php elseif ($error === "email"): ?>
<div class="mensaje error">
Ya existe una cuenta con ese correo electrónico.
</div>
<?php elseif ($error === "password"): ?>
<div class="mensaje error">
Las contraseñas no coinciden.
</div>
<?php elseif ($error === "guardar"): ?>
<div class="mensaje error">
No se ha podido crear la cuenta.
</div>
<?php endif; ?>
<form
action="guardar_registro.php"
method="post"
class="formulario"
>
<div class="campo">
    <label for="nombre">Nombre</label>
<input
type="text"
id="nombre"
name="nombre"
maxlength="100"
value="<?= escapar($nombre) ?>"
required
>
</div>
<div class="campo">
<label for="apellidos">Apellidos</label>
<input
type="text"
id="apellidos"
name="apellidos"
maxlength="150"
value="<?= escapar($apellidos) ?>"
required
>
</div>
<div class="campo">
<label for="email">Correo electrónico</label>
<input
type="email"
id="email"
name="email"
maxlength="150"
value="<?= escapar($email) ?>"
required
>
</div>
<div class="campo">
<label for="telefono">Teléfono</label>
<input
type="tel"
id="telefono"
name="telefono"
maxlength="20"
value="<?= escapar($telefono) ?>"
>
</div>
<div class="campo">
<label for="password">Contraseña</label>
<input
type="password"
id="password"
name="password"
minlength="8"
required
>
<p class="ayuda">
Debe contener al menos ocho caracteres.
</p>
</div>
<div class="campo">
<label for="repetir_password">
Repetir contraseña
</label>
<input
type="password"
id="repetir_password"
name="repetir_password"
minlength="8"
required
>
</div>
<button type="submit" class="boton">
Crear mi cuenta
</button>
</form>
<p>
</p>
¿Ya tienes una cuenta?
<a href="login.php">Inicia sesión</a>.
</main>
</body>
</html>