<?php
require_once "seguridad_admin.php";
require_once "../conexion.php";
$sql = "
SELECT
s.id_sesion,
s.fecha,
s.hora_inicio,
s.hora_fin,
s.aforo,
s.estado,
a.nombre AS actividad,
e.nombre AS espacio,
m.nombre AS monitor_nombre,
m.apellidos AS monitor_apellidos
FROM sesiones s
INNER JOIN actividades a
ON s.id_actividad = a.id_actividad
INNER JOIN espacios e
ON s.id_espacio = e.id_espacio
INNER JOIN monitores m
ON s.id_monitor = m.id_monitor
ORDER BY
s.fecha ASC,
s.hora_inicio ASC
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
<title>Sesiones programadas</title>
<link rel="stylesheet" href="../estilos.css">
</head>

<body>
<?php require_once "menu_admin.php"; ?>
<main class="contenedor">
<h1>Sesiones programadas</h1>
<?php if (

($_GET["mensaje"] ?? "") === "sesion_creada"
): ?>
<div class="mensaje exito">
La sesión se ha creado correctamente.
</div>
<?php endif; ?>
<a class="boton" href="nueva_sesion.php">
Programar una sesión
</a>
<?php if ($resultado->num_rows === 0): ?>
<p>No existen sesiones programadas.</p>
<?php else: ?>
<div class="tabla-responsive">
<table>
    <thead>
<tr>
<th>Fecha</th>
<th>Horario</th>
<th>Actividad</th>
<th>Espacio</th>
<th>Monitor</th>
<th>Aforo</th>
<th>Estado</th>
</tr>
</thead>
<tbody>
<?php while (
$sesion = $resultado->fetch_assoc()
): ?>
<tr>
<td>
<?= date(
"d/m/Y",
strtotime($sesion["fecha"])
) ?>
</td>
<td>
<?= substr(
$sesion["hora_inicio"],
0,
5
) ?>
–
<?= substr(
$sesion["hora_fin"],
0,
5
) ?>
</td>
<td>
    <?= htmlspecialchars(
$sesion["actividad"]
) ?>
</td>

<td>
    <?= htmlspecialchars(
$sesion["espacio"]
) ?>
</td>

<td>
<?= htmlspecialchars(
$sesion["monitor_nombre"] .
" " .
$sesion["monitor_apellidos"]
) ?>
</td>
<td>
    <?= $sesion["aforo"] ?>
</td>

<td>
    <?= htmlspecialchars(
ucfirst($sesion["estado"])
) ?>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
<?php endif; ?>
</main>
</body>
</html>
