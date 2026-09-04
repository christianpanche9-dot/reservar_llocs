<?php
require_once "funciones.php";
if (usuarioAutenticado()) {
header("Location: mi_cuenta.php");
exit;
}
$error = $_GET["error"] ?? "";
$mensaje = $_GET["mensaje"] ?? "";
$volver = $_GET["volver"] ?? "";
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta
name="viewport"
content="width=device-width, initial-scale=1.0"
$usuario_autenticado = usuarioAutenticado();
>
<title>Iniciar sesión</title>
<link rel="stylesheet" href="estilos.css">
</head>
<body>
<?php require "menu.php"; ?>
<main class="contenedor">
<h1>Iniciar sesión</h1>
<?php if ($mensaje === "registro"): ?>
<div class="mensaje exito">
La cuenta se ha creado correctamente.
Ya puedes iniciar sesión.
</div>
<?php endif; ?>
<?php if ($error === "credenciales"): ?>
<div class="mensaje error">
El correo o la contraseña no son correctos.
</div>
<?php elseif ($error === "inactivo"): ?>
    <div class="mensaje error">
Esta cuenta está desactivada.
</div>
<?php elseif ($error === "acceso"): ?>
<div class="mensaje aviso">
Debes iniciar sesión para acceder a esa página.
</div>
<?php endif; ?>
<form
action="validar_login.php"
method="post"
class="formulario"
>
<input
type="hidden"
name="volver"
value="<?= escapar($volver) ?>"
>
<div class="campo">
<label for="email">Correo electrónico</label>
<input
type="email"
id="email"
name="email"
required
>
</div>
<div class="campo">
<label for="password">Contraseña</label>
<input
type="password"
id="password"
name="password"
required
>
</div>
<button type="submit" class="boton">
Entrar
</button>
</form>
<p>
</p>
¿Todavía no tienes cuenta?
<a href="registro.php">Regístrate</a>.
</main>
</body>
</html>