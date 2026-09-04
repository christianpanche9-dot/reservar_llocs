<?php
require_once "seguridad_admin.php";
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../funciones.php';
$sql = "
SELECT
id_actividad,
nombre,
categoria,
nivel,
duracion_minutos,
activa
FROM actividades
ORDER BY nombre
";
$resultado = $conexion->query($sql);
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
Actividades | Administración
<link rel="stylesheet" href="../estilos.css">
</head>
<body>
    <?php require_once __DIR__ . '/menu_admin.php'; ?>
<main class="contenedor seccion">
<div class="encabezado-con-accion">
<div>
<p class="etiqueta">
Administración
</p>
<h1>Actividades</h1>
</div>
<a class="boton" href="nueva_actividad.php">
Nueva actividad
</a>
</div>
<?php if (
    ($_GET['mensaje'] ?? '') === 'creada'
): ?>


<div class="mensaje mensaje-exito">
La actividad se ha creado correctamente.
</div>
<?php endif; ?>
<?php if (
    ($_GET['mensaje'] ?? '') === 'actualizada'
): ?>

<div class="mensaje mensaje-exito">
La actividad se ha actualizado correctamente.
</div>
<?php endif; ?>
<?php if (
    ($_GET['mensaje'] ?? '') === 'eliminada'
): ?>

<div class="mensaje mensaje-exito">
La actividad se ha eliminado correctamente.
</div>
<?php endif; ?>
<?php if (
    ($_GET['error'] ?? '') === 'en_uso'
): ?>

<div class="mensaje mensaje-error">
No se puede eliminar la actividad porque tiene sesiones asociadas.
Desactívala si no quieres que se siga ofreciendo.
</div>
<?php endif; ?>
<div class="tabla-responsive">
<table class="tabla-admin">
<thead>
<tr>
<th>Actividad</th>
<th>Categoría</th>
<th>Nivel</th>
<th>Duración</th>
<th>Estado</th>
<th>Acciones</th>
</tr>
</thead>
<tbody>
<?php while (
$actividad = $resultado->fetch_assoc()
): ?>
<tr>
<td>
    <?= escapar($actividad['nombre']) ?>
</td>

<td>
    <?= escapar($actividad['categoria']) ?>
</td>

<td>

<td>
<?= escapar(
texto_nivel($actividad['nivel'])
) ?>
</td>

<td>
<?= (int)
$actividad['duracion_minutos'] ?>
minutos
</td>

<td>
<?php if (
    (int) $actividad['activa'] === 1
): ?>
<span class="estado estado-programada">
Activa
</span>
<?php else: ?>
<span class="estado estado-finalizada">
Inactiva
</span>
<?php endif; ?>
</td>
<td class="acciones-tabla">
<a
class="boton boton-secundario boton-pequeno"
href="editar_actividad.php?id_actividad=<?= (int) $actividad['id_actividad'] ?>"
>
Editar
</a>
<form
action="eliminar_actividad.php"
method="post"
onsubmit="return confirm('¿Seguro que quieres eliminar esta actividad?');"
>
<input
type="hidden"
name="id_actividad"
value="<?= (int) $actividad['id_actividad'] ?>"
>
<button class="boton peligro boton-pequeno" type="submit">
Eliminar
</button>
</form>
</td>
</tr>

<?php endwhile; ?>
</tbody>
</table>
</div>
</main>
</body>
</html>