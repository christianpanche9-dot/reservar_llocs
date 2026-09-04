<?php
require_once "seguridad_admin.php";
require_once "../conexion.php";
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta
name="viewport"
content="width=device-width, initial-scale=1.0"
>
<title>Nuevo monitor | Reservar Llocs</title>
<link rel="stylesheet" href="../estilos.css">
</head>
<body>
<?php require_once __DIR__ . '/menu_admin.php'; ?>
<main class="contenedor seccion">
<a class="enlace-volver" href="monitores.php">
← Volver a monitores
</a>
<h1>Nuevo monitor</h1>
<?php if (
    ($_GET['error'] ?? '') === 'datos'
): ?>

<div class="mensaje mensaje-error">
Revisa los datos del formulario.
</div>
<?php endif; ?>
<?php if (
    ($_GET['error'] ?? '') === 'email'
): ?>

<div class="mensaje mensaje-error">
Ya existe un monitor con ese correo.
</div>
<?php endif; ?>
<form
class="formulario-admin"
action="guardar_monitor.php"
method="post"
>
<div class="campo">
<label for="nombre">Nombre</label>
<input
type="text"
id="nombre"
name="nombre"
maxlength="80"
required
>
</div>
<div class="campo">
<label for="apellidos">Apellidos</label>
<input
type="text"
id="apellidos"
name="apellidos"
maxlength="120"
required
>
</div>
<div class="campo">
<label for="email">Correo electrónico</label>
<input
type="email"
id="email"
name="email"
maxlength="180"
required
>
</div>
<div class="campo">
<label for="telefono">Teléfono</label>
<input
type="tel"
id="telefono"
name="telefono"
maxlength="25"
>
</div>
<div class="campo campo-completo">
<label for="especialidad">
Especialidad
</label>
<input
type="text"
id="especialidad"
name="especialidad"
maxlength="180"
required
>
</div>
<div class="campo-checkbox campo-completo">
<label>
<input
type="checkbox"
name="activo"
value="1"
checked
>
Monitor activo
</label>
</div>
<div class="campo-completo">
<button class="boton" type="submit">
Guardar monitor
</button>
</div>
</form>
</main>
</body>
</html>