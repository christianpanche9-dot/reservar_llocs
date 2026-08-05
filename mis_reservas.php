<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once "seguridad.php";
require_once "conexion.php";
require_once "funciones.php";
$id_usuario = idUsuarioActual();
$sql_reservas = "
SELECT
r.id_reserva,
r.fecha_reserva,
r.estado,
r.asistencia,
r.codigo_reserva,
s.id_sesion,
s.fecha,
s.hora_inicio,
s.hora_fin,
s.estado AS estado_sesion,
a.nombre AS actividad,
e.nombre AS espacio
FROM reservas r
INNER JOIN sesiones s
ON r.id_sesion = s.id_sesion
INNER JOIN actividades a
ON s.id_actividad = a.id_actividad
INNER JOIN espacios e
ON s.id_espacio = e.id_espacio
WHERE r.id_usuario = ?
ORDER BY
s.fecha DESC,
s.hora_inicio DESC
";
$stmt_reservas = $conexion->prepare($sql_reservas);
$stmt_reservas->bind_param("i", $id_usuario);
$stmt_reservas->execute();
$reservas = $stmt_reservas->get_result();
$sql_espera = "
SELECT
le.id_espera,
le.fecha_solicitud,
le.estado,
s.id_sesion,
s.fecha,
s.hora_inicio,
s.hora_fin,
a.nombre AS actividad,
e.nombre AS espacio,
(
SELECT COUNT(*)
FROM lista_espera anterior
WHERE anterior.id_sesion = le.id_sesion
AND anterior.estado = 'esperando'
AND (
anterior.fecha_solicitud
< le.fecha_solicitud
OR (
anterior.fecha_solicitud
= le.fecha_solicitud
AND anterior.id_espera
<= le.id_espera
)
)
) AS posicion
FROM lista_espera le
INNER JOIN sesiones s
ON le.id_sesion = s.id_sesion
INNER JOIN actividades a
ON s.id_actividad = a.id_actividad
INNER JOIN espacios e
ON s.id_espacio = e.id_espacio
WHERE le.id_usuario = ?
AND le.estado = 'esperando'
ORDER BY
s.fecha ASC,
s.hora_inicio ASC
";
$stmt_espera = $conexion->prepare($sql_espera);
$stmt_espera->bind_param("i", $id_usuario);
$stmt_espera->execute();
$lista_espera = $stmt_espera->get_result();
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
<title>Mis reservas</title>
<link rel="stylesheet" href="estilos.css">
</head>
<body>
<?php require "menu.php"; ?>
<main class="contenedor">
<h1>Mis reservas</h1>
<?php if ($mensaje === "confirmada"): ?>
<div class="mensaje exito">
La reserva se ha confirmado correctamente.
</div>
<?php elseif ($mensaje === "espera"): ?>
<div class="mensaje aviso">
La sesión está completa.
Te hemos añadido a la lista de espera.
</div>
<?php elseif ($mensaje === "cancelada"): ?>
<div class="mensaje exito">
La reserva se ha cancelado.
</div>
<?php endif; ?>
<section>
<h2>Reservas</h2>
<?php if ($reservas->num_rows === 0): ?>
    <p>Todavía no tienes reservas.</p>
<?php else: ?>
<div class="rejilla-reservas">
<?php while (
$reserva = $reservas->fetch_assoc()
): ?>
<?php
$inicio = new DateTime(
$reserva["fecha"] .
" " .
$reserva["hora_inicio"]
);
$puede_cancelar =
$reserva["estado"] === "confirmada" &&
$reserva["estado_sesion"] !== "cancelada" &&
$inicio > new DateTime();
?>
<article class="tarjeta-reserva">
<h3>
    <?= escapar(
        $reserva["actividad"]
) ?>
</h3>


<p>
<strong>Fecha:</strong>
<?= date(
    "d/m/Y",
strtotime($reserva["fecha"])
) ?>
</p>
<p>
<strong>Horario:</strong>
<?= substr(
$reserva["hora_inicio"],
0,
5
) ?>
–
<?= substr(
$reserva["hora_fin"],
0,
5
) ?>
</p>
<p>
<strong>Espacio:</strong>
<?= escapar(
$reserva["espacio"]
) ?>
</p>
<p>
<strong>Estado:</strong>
<?= escapar(
ucfirst($reserva["estado"])
) ?>
</p>
<?php if (
    $reserva["estado"] === "confirmada"
): ?>
<p class="codigo-reserva">
Código:
<?= escapar(
$reserva["codigo_reserva"]
) ?>
</p>
<?php endif; ?>
<?php if ($puede_cancelar): ?>
<form
action="cancelar_reserva.php"
method="post"
>
<input
type="hidden"
name="id_reserva"
value="<?=
$reserva["id_reserva"]
?>"
>
<button
type="submit"
class="boton peligro"
>
Cancelar reserva
</button>
</form>
<?php endif; ?>
</article>
<?php endwhile; ?>
</div>
<?php endif; ?>
</section>

<section>
<h2>Lista de espera</h2>
<?php if ($lista_espera->num_rows === 0): ?>
<p>
</p>
No estás esperando plaza en ninguna sesión.
<?php else: ?>
<div class="rejilla-reservas">
<?php while (
$espera = $lista_espera->fetch_assoc()
): ?>
<article class="tarjeta-reserva espera">
<h3>
    <?= escapar(
$espera["actividad"]
) ?>
</h3>

<p>
<?= date(
    "d/m/Y",
strtotime($espera["fecha"])
) ?>
·
<?= substr(
$espera["hora_inicio"],
0,
5
) ?>
</p>
<p>
</p>
<strong>Posición:</strong>
<?= (int) $espera["posicion"] ?>
</article>
<?php endwhile; ?>
</div>
<?php endif; ?>
</section>
</main>
</body>
</html>
<?php
$stmt_reservas->close();
$stmt_espera->close();
$conexion->close();