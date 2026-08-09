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
http_response_code(400);
exit("Identificador no válido.");
}
$sql_sesion = "
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
http_response_code(404);
exit("La sesión no existe.");
}
$sql_participantes = "
SELECT
r.id_reserva,
r.codigo_reserva,
r.estado,
r.asistencia,
r.fecha_reserva,
u.nombre,
u.apellidos,
u.email,
u.telefono
FROM reservas r
INNER JOIN usuarios u
ON r.id_usuario = u.id_usuario
WHERE r.id_sesion = ?
ORDER BY
r.estado ASC,
u.apellidos ASC,
u.nombre ASC
";
$stmt_participantes =
$conexion->prepare($sql_participantes);
$stmt_participantes->bind_param(
"i",
$id_sesion
);
$stmt_participantes->execute();
$participantes =
$stmt_participantes->get_result();
$sql_espera = "
SELECT
le.id_espera,
le.fecha_solicitud,
le.estado,
u.nombre,
u.apellidos,
u.email
FROM lista_espera le
INNER JOIN usuarios u
ON le.id_usuario = u.id_usuario
WHERE le.id_sesion = ?
ORDER BY
FIELD(
le.estado,
'esperando',
'promocionada',
'cancelada'
),
le.fecha_solicitud ASC,
le.id_espera ASC
";
$stmt_espera =
$conexion->prepare($sql_espera);
$stmt_espera->bind_param(
"i",
$id_sesion
);
$stmt_espera->execute();
$lista_espera =
$stmt_espera->get_result();
$sql_ocupacion = "
SELECT COUNT(*) AS total
FROM reservas
WHERE id_sesion = ?
AND estado = 'confirmada'
";
$stmt_ocupacion =
$conexion->prepare($sql_ocupacion);
$stmt_ocupacion->bind_param(
"i",
$id_sesion
);
$stmt_ocupacion->execute();
$total_confirmadas = (int) $stmt_ocupacion
->get_result()
->fetch_assoc()["total"];
$stmt_ocupacion->close();
$porcentaje = $sesion["aforo"] > 0
? round(
$total_confirmadas /
$sesion["aforo"] *
100,
1
)
: 0;
$mensaje = $_GET["mensaje"] ?? "";
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta
name="viewport"
content="width=device-width, initial-scale=1.0"
>
<title>Participantes de la sesión</title>
<link rel="stylesheet" href="../estilos.css">
</head>
<body>
<?php require "menu_admin.php"; ?>
<main class="contenedor">
<h1>
<?= escapar($sesion["actividad"]) ?>
</h1>
<?php if ($mensaje === "asistencia"): ?>
<div class="mensaje mensaje-exito">
La asistencia se ha actualizado.
</div>
<?php elseif ($mensaje === "cancelada"): ?>
<div class="mensaje mensaje-exito">
La reserva se ha cancelado y se ha
revisado la lista de espera.
</div>
<?php endif; ?>
<section class="resumen-sesion-admin">
<p>
<strong>Fecha:</strong>
<?= date(
"d/m/Y",
strtotime($sesion["fecha"])
) ?>
</p>
<p>
<strong>Horario:</strong>
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
<strong>Espacio:</strong>
<?= escapar($sesion["espacio"]) ?>
</p>
<p>
<strong>Monitor:</strong>
<?= escapar(
$sesion["monitor_nombre"] .
" " .
$sesion["monitor_apellidos"]
) ?>
</p>
<p>
<strong>Ocupación:</strong>
<?= $total_confirmadas ?>
/
<?= (int) $sesion["aforo"] ?>
plazas
(<?= $porcentaje ?> %)
</p>
<div class="barra-ocupacion">
<span
style="width: <?= min(
100,
$porcentaje
) ?>%"
></span>
</div>
</section>
<div class="grupo-botones">
<a
class="boton"
href="imprimir_participantes.php?id=<?=
$id_sesion
?>"
target="_blank"
>
Imprimir participantes
</a>
<a
class="boton boton-secundario"
href="exportar_participantes.php?id=<?=
$id_sesion
?>"
>
Exportar CSV
</a>
</div>
<section>
<h2>Participantes</h2>
<?php if (
    $participantes->num_rows === 0
): ?>

<p>No existen reservas.</p>
<?php else: ?>
<div class="tabla-responsive">
<table class="tabla-admin">
<thead>
<tr>
<th>Participante</th>
<th>Contacto</th>
<th>Estado</th>
<th>Asistencia</th>
<th>Acciones</th>
</tr>
</thead>
<tbody>
<?php while (
$participante =
$participantes->fetch_assoc()
): ?>
<tr>
    <td>
<?= escapar(
$participante["apellidos"] .
", " .
$participante["nombre"]
) ?>
<br>
<small>
<?= escapar(
$participante[
"codigo_reserva"
]
) ?>
</small>
</td>
<td>
    <?= escapar(
$participante["email"]
) ?>

<br>
<small>
<?= escapar(
$participante["telefono"]
) ?>
</small>
</td>

<td>
<?= escapar(
ucfirst(
    $participante["estado"]
)
) ?>
</td>
<td>
<?php if (
$participante["estado"]
=== "confirmada"
): ?>
<form
action="actualizar_asistencia.php"
method="post"
class="formulario-inline"
>
<input
type="hidden"
name="id_reserva"
value="<?=
$participante[
"id_reserva"
]
?>"
>
<input
type="hidden"
name="id_sesion"
value="<?=
$id_sesion
?>"
>
<select
name="asistencia"
>
<option
value="pendiente"
<?=
$participante[
"asistencia"
] === "pendiente"
? "selected"
: ""
?>
>
Pendiente
</option>
<option
value="asistio"
<?=
$participante[
"asistencia"
] === "asistio"
? "selected"
: ""
?>
>
Asistió
</option>
<option
value="no_asistio"
<?=
$participante[
"asistencia"
] === "no_asistio"
? "selected"
: ""
?>
>
No asistió
</option>
</select>
<button
type="submit"
class="boton boton-pequeno"
>
Guardar
</button>
</form>
<?php else: ?>
—
<?php endif; ?>
</td>
<td>
<?php if (
$participante["estado"]
=== "confirmada"
): ?>
<form
action="cancelar_reserva.php"
method="post"
>
<input
type="hidden"
name="id_reserva"
value="<?=
$participante[
"id_reserva"
]
?>"
>
<input
type="hidden"
name="id_sesion"
value="<?=
$id_sesion
?>"
>
<button
type="submit"
class="boton peligro boton-pequeno"
>
Cancelar
</button>
</form>
<?php endif; ?>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
<?php endif; ?>
</section>
<section>
<h2>Lista de espera</h2>
<?php if (
    $lista_espera->num_rows === 0
): ?>
<p>No existen solicitudes de espera.</p>
<?php else: ?>
<div class="tabla-responsive">
<table class="tabla-admin">
<thead>
<tr>
<th>Usuario</th>
<th>Solicitud</th>
<th>Estado</th>
</tr>
</thead>
<tbody>
<?php
$posicion = 0;
while (
$espera =
$lista_espera->fetch_assoc()
):
if (
$espera["estado"] ===
"esperando"
) {
$posicion++;
}
?>
<tr>
<td>
<?= escapar(
$espera["apellidos"] .
", " .
$espera["nombre"]
) ?>
<br>
<small>
<?= escapar(
$espera["email"]
) ?>
</small>
</td>
<td>
<?= date(
"d/m/Y H:i",
strtotime(
$espera[
"fecha_solicitud"
]
)
) ?>
<?php if (
$espera["estado"] ===
"esperando"
): ?>
<br>
<strong>
Posición:
<?= $posicion ?>
</strong>
<?php endif; ?>
</td>
<td>
<?= escapar(
ucfirst(
$espera["estado"]
)
) ?>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
<?php endif; ?>
</section>
</main>
</body>
</html>
<?php
$stmt_participantes->close();
$stmt_espera->close();
$conexion->close();