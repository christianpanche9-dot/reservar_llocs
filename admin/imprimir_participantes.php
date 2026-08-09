<?php
require_once "seguridad_admin.php";
require_once "../conexion.php";
require_once "../funciones.php";
$id_sesion = filter_input(
INPUT_GET,
"id",
FILTER_VALIDATE_INT
);
if (!$id_sesion) {
exit("Sesión no válida.");
}
$sql_sesion = "
SELECT
s.fecha,
s.hora_inicio,
s.hora_fin,
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
WHERE s.id_sesion = ?
";
$stmt_sesion =
$conexion->prepare($sql_sesion);
$stmt_sesion->bind_param(
"i",
$id_sesion
);
$stmt_sesion->execute();
$sesion = $stmt_sesion
->get_result()
->fetch_assoc();
$stmt_sesion->close();
if (!$sesion) {
exit("La sesión no existe.");
}
$sql = "
SELECT
u.nombre,
u.apellidos,
u.telefono,
r.codigo_reserva,
r.asistencia
FROM reservas r
INNER JOIN usuarios u
ON r.id_usuario = u.id_usuario
WHERE r.id_sesion = ?
AND r.estado = 'confirmada'
ORDER BY
u.apellidos ASC,
u.nombre ASC
";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_sesion);
$stmt->execute();
$participantes = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="es">
    <head>
<meta charset="UTF-8">
<title>Listado de participantes</title>
<style>
body {
font-family: Arial, sans-serif;
color: #172b3a;
}
header {
margin-bottom: 2rem;
border-bottom: 2px solid #1769aa;
}
table {
width: 100%;
border-collapse: collapse;
}
th,
td {
padding: 0.7rem;
border: 1px solid #777;
text-align: left;
}
th {
background: #eaf4ff;
}
.acciones {
margin-bottom: 1.5rem;
}
@media print {
.acciones {
display: none;
}
}
</style>
</head>
<body>
<div class="acciones">
<button onclick="window.print()">
Imprimir
</button>
</div>
<header>
<h1>
<?= escapar($sesion["actividad"]) ?>
</h1>
<p>
<?= date(
"d/m/Y",
strtotime($sesion["fecha"])
) ?>
·
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
</p>
<p>
<?= escapar($sesion["espacio"]) ?>
·
<?= escapar(
$sesion["monitor_nombre"] .
" " .
$sesion["monitor_apellidos"]
) ?>
</p>
</header>
<table>
<thead>
<tr>
<th>N.º</th>
<th>Participante</th>
<th>Teléfono</th>
<th>Código</th>
<th>Firma</th>
</tr>
</thead>
<tbody>
<?php
$numero = 1;
while (
$participante =
$participantes->fetch_assoc()
):
?>
<tr>
<td><?= $numero ?></td>
<td>
<?= escapar(
$participante["apellidos"] .
", " .
$participante["nombre"]
) ?>
</td>
<td>
    <?= escapar(
$participante["telefono"]
) ?>
</td>

<td>
    <?= escapar(
$participante["codigo_reserva"]
) ?>
</td>

<td></td>
</tr>
<?php
$numero++;
endwhile;
?>
</tbody>
</table>
</body>
</html>
<?php
$stmt->close();
$conexion->close();