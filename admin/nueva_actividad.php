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
    Nueva actividad | Reservar Llocs
</title>
<link rel="stylesheet" href="../estilos.css">
</head>
<body>
<?php require_once __DIR__ . '/menu_admin.php'; ?>
<main class="contenedor seccion">
<a class="enlace-volver" href="actividades.php">
← Volver a actividades
</a>
<div class="encabezado-pagina">
<p class="etiqueta">
Actividades
</p>
<h1>Nueva actividad</h1>
</div>
<?php if (isset($_GET['error'])): ?>
<div class="mensaje mensaje-error">
No se ha podido guardar la actividad.
Revisa los datos del formulario.
</div>
<?php endif; ?>
<form
class="formulario-admin"
action="guardar_actividad.php"
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
<div class="campo campo-completo">
<label for="descripcion">
Descripción
</label>
<textarea
id="descripcion"
name="descripcion"
rows="6"
required
></textarea>
</div>
<div class="campo">
<label for="categoria">
Categoría
</label>
<select
id="categoria"
name="categoria"
required
>
<option value="">
Selecciona una categoría
</option>
<option value="Deporte">
Deporte
</option>
<option value="Bienestar">
Bienestar
</option>
<option value="Cultura">
Cultura
</option>
<option value="Formación">
Formación
</option>
<option value="Ocio">
Ocio
</option>
</select>
</div>
<div class="campo">
<label for="nivel">
Nivel
</label>
<select id="nivel" name="nivel" required>
<option value="">
Selecciona un nivel
</option>
<option value="inicial">
Inicial
</option>
<option value="intermedio">
Intermedio
</option>
<option value="avanzado">
Avanzado
</option>
<option value="todos">
Todos los niveles
</option>
</select>
</div>
<div class="campo">
<label for="duracion_minutos">
Duración habitual
</label>
<input
type="number"
id="duracion_minutos"
name="duracion_minutos"
min="15"
max="480"
step="5"
value="60"
required
>
</div>
<div class="campo">
<label for="imagen">
Nombre de la imagen
</label>
<input
type="text"
id="imagen"
name="imagen"
maxlength="255"
placeholder="yoga.jpg"
>
</div>
<div class="campo-checkbox campo-completo">
<label>
<input
type="checkbox"
name="activa"
value="1"
checked
>
Mostrar la actividad en el catálogo
</label>
</div>
<div class="campo-completo">
<button class="boton" type="submit">
    Guardar actividad
</button>
</div>
</form>
</main>
</body>
</html>