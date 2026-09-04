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
<title>
</title>
Nuevo espacio | Reservar Llocs
<link rel="stylesheet" href="../estilos.css">
</head>
<body>
<?php require_once __DIR__ . '/menu_admin.php'; ?>
<main class="contenedor seccion">
<a class="enlace-volver" href="espacios.php">
← Volver a espacios
</a>
<h1>Nuevo espacio</h1>
<?php if (isset($_GET['error'])): ?>
    <div class="mensaje mensaje-error">
Revisa los datos del espacio.
</div>
<?php endif; ?>
<form
class="formulario-admin"
action="guardar_espacio.php"
method="post"
>
<div class="campo">
<label for="nombre">
Nombre
</label>
<input
type="text"
id="nombre"
name="nombre"
maxlength="120"
required
>
</div>
<div class="campo">
<label for="ubicacion">
Ubicación
</label>
<input
type="text"
id="ubicacion"
name="ubicacion"
maxlength="180"
placeholder="Primera planta"
required
>
</div>
<div class="campo">
<label for="aforo_maximo">
Aforo máximo
</label>
<input
type="number"
id="aforo_maximo"
name="aforo_maximo"
min="1"
max="5000"
required
>
</div>
<div class="campo campo-completo">
<label for="descripcion">
Descripción
</label>
<textarea
id="descripcion"
name="descripcion"
rows="5"
></textarea>
</div>
<div class="campo-checkbox campo-completo">
<label>
    <input
type="checkbox"
name="activo"
value="1"
checked
>
Espacio disponible
</label>
</div>
<div class="campo-completo">
<button class="boton" type="submit">
Guardar espacio
</button>
</div>
</form>
</main>
</body>
</html>