<?php
require_once "seguridad_admin.php";
require_once "../conexion.php";
require_once "../funciones.php";
$sql = "
SELECT
s.id_sesion,
s.fecha,
s.hora_inicio,
s.aforo,
s.estado,
a.nombre AS actividad,
(
SELECT COUNT(*)
FROM reservas r
WHERE r.id_sesion = s.id_sesion
AND r.estado = 'confirmada'
) AS confirmadas,
(
SELECT COUNT(*)
FROM reservas r
WHERE r.id_sesion = s.id_sesion
AND r.estado = 'cancelada'
) AS canceladas,
(
SELECT COUNT(*)
FROM reservas r
WHERE r.id_sesion = s.id_sesion
AND r.estado = 'confirmada'
AND r.asistencia = 'asistio'
) AS asistieron,
(
SELECT COUNT(*)
FROM reservas r
WHERE r.id_sesion = s.id_sesion
AND r.estado = 'confirmada'
AND r.asistencia = 'no_asistio'
) AS ausentes,
(
SELECT COUNT(*)
FROM lista_espera le
WHERE le.id_sesion = s.id_sesion
AND le.estado = 'esperando'
) AS esperando
FROM sesiones s
INNER JOIN actividades a
ON s.id_actividad = a.id_actividad
ORDER BY
s.fecha DESC,
s.hora_inicio DESC
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
<title>Estadísticas de ocupación</title>
<link rel="stylesheet" href="../estilos.css">
</head>
<body>
<?php require "menu_admin.php"; ?>
<main class="contenedor">
<h1>Estadísticas de ocupación</h1>
<div class="tabla-responsive">
<table>
<thead>
<tr>
<th>Sesión</th>
<th>Aforo</th>
<th>Confirmadas</th>
<th>Ocupación</th>
<th>Canceladas</th>
<th>En espera</th>
<th>Asistieron</th>
<th>Ausentes</th>
</tr>
</thead>
<tbody>
<?php while (
$sesion =
$resultado->fetch_assoc()
): ?>
<?php
$ocupacion =
$sesion["aforo"] > 0
? round(
$sesion["confirmadas"] /
$sesion["aforo"] *
100,
1
)
: 0;
?>
<tr>
<td>
<a
href="detalles_sesion.php?id=<?=
(int) $sesion["id_sesion"]
?>"
>
<?= escapar(
$sesion["actividad"]
) ?>
</a>
<br>
<small>
<?= date(
"d/m/Y",
strtotime(
$sesion["fecha"]
)
) ?>
·
<?= substr(
$sesion["hora_inicio"],
0,
5
) ?>
</small>
</td>
<td>
    <?= (int) $sesion["aforo"] ?>
</td>

<td>
<?= (int)
$sesion["confirmadas"] ?>
</td>
<td>
<?= $ocupacion ?> %
<div class="barra-mini">
<span
style="width: <?=
min(
100,
$ocupacion
)
?>%"
></span>
</div>
</td>
<td>
<?= (int)
$sesion["canceladas"] ?>
</td>
<td>
<?= (int)
$sesion["esperando"] ?>
</td>
<td>
<?= (int)
$sesion["asistieron"] ?>
</td>
<td>
<?= (int)
$sesion["ausentes"] ?>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</main>
</body>
</html>
<?php
$conexion->close();