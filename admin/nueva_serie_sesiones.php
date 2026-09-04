<?php
session_start();
require_once '../conexion.php';
require_once 'seguridad_admin.php';

$actividades = $conexion->query(
"SELECT id_actividad, nombre
FROM actividades
WHERE activa = 1
ORDER BY nombre"
);
$espacios = $conexion->query(
"SELECT id_espacio, nombre, aforo_maximo
FROM espacios
WHERE activo = 1
ORDER BY nombre"
);
$monitores = $conexion->query(
"SELECT id_monitor, nombre
FROM monitores
WHERE activo = 1
ORDER BY nombre"
);
if (empty($_SESSION['csrf_serie'])) {
$_SESSION['csrf_serie'] = bin2hex(random_bytes(32));
}
$diasSemana = [
1 => 'Lunes',
2 => 'Martes',
3 => 'Miércoles',
4 => 'Jueves',
5 => 'Viernes',
6 => 'Sábado',
7 => 'Domingo'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta
name="viewport"
content="width=device-width, initial-scale=1.0"
>
<title>Sesiones recurrentes | Reservar Llocs</title>
<link rel="stylesheet" href="../estilos.css">
</head>
<body>
<?php require_once __DIR__ . '/menu_admin.php'; ?>
<main class="contenedor seccion">
<a class="enlace-volver" href="sesiones.php">
← Volver a sesiones
</a>
<h1>Programar sesiones recurrentes</h1>
<?php if ($_GET['error'] ?? ''): ?>
<div class="mensaje mensaje-error">
La serie no se ha creado. Revisa los conflictos e inténtalo
de nuevo.
</div>
<?php endif; ?>
<form
class="formulario-admin"
action="previsualizar_serie.php"
method="post"
>
<input type="hidden" name="csrf"
value="<?= htmlspecialchars($_SESSION['csrf_serie']) ?>">
<div class="campo">
<label for="id_actividad">Actividad</label>
<select id="id_actividad" name="id_actividad" required>
<option value="">Selecciona una actividad</option>
<?php while ($actividad = $actividades->fetch_assoc()): ?>
<option value="<?= (int) $actividad['id_actividad'] ?>">
<?= htmlspecialchars($actividad['nombre']) ?>
</option>
<?php endwhile; ?>
</select>
</div>
<div class="campo">
<label for="id_espacio">Espacio</label>
<select id="id_espacio" name="id_espacio" required>
<option value="">Selecciona un espacio</option>
<?php while ($espacio = $espacios->fetch_assoc()): ?>
<option value="<?= (int) $espacio['id_espacio'] ?>">
<?= htmlspecialchars($espacio['nombre']) ?> (capacidad <?= (int) $espacio['aforo_maximo'] ?>)
</option>
<?php endwhile; ?>
</select>
</div>
<div class="campo">
<label for="id_monitor">Monitor</label>
<select id="id_monitor" name="id_monitor" required>
<option value="">Selecciona un monitor</option>
<?php while ($monitor = $monitores->fetch_assoc()): ?>
<option value="<?= (int) $monitor['id_monitor'] ?>">
<?= htmlspecialchars($monitor['nombre']) ?>
</option>
<?php endwhile; ?>
</select>
</div>
<div class="campo">
<label for="fecha_inicio">Fecha inicial</label>
<input
type="date"
id="fecha_inicio"
name="fecha_inicio"
required
>
</div>
<div class="campo">
<label for="fecha_fin">Fecha final</label>
<input
type="date"
id="fecha_fin"
name="fecha_fin"
required
>
</div>
<fieldset class="campo-completo dias-semana">
<legend>Días de la semana</legend>
<?php foreach ($diasSemana as $numero => $nombre): ?>
<label>
<input
type="checkbox"
name="dias[]"
value="<?= $numero ?>"
>
<?= $nombre ?>
</label>
<?php endforeach; ?>
</fieldset>
<div class="campo">
<label for="hora_inicio">Hora de inicio</label>
<input
type="time"
id="hora_inicio"
name="hora_inicio"
required
>
</div>
<div class="campo">
<label for="duracion">Duración en minutos</label>
<input
type="number"
id="duracion"
name="duracion"
min="15"
max="480"
required
>
</div>
<div class="campo">
<label for="aforo">Aforo</label>
<input
type="number"
id="aforo"
name="aforo"
min="1"
required
>
</div>
<div class="campo-completo">
<button class="boton" type="submit">
Generar vista previa
</button>
</div>
</form>
</main>
</body>
</html>
