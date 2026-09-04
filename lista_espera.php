<?php
require_once "seguridad.php";
require_once "conexion.php";
require_once "funciones.php";

$id_usuario = idUsuarioActual();

$sql = "
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
            anterior.fecha_solicitud < le.fecha_solicitud
            OR (
                anterior.fecha_solicitud = le.fecha_solicitud
                AND anterior.id_espera <= le.id_espera
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
ORDER BY s.fecha ASC, s.hora_inicio ASC
";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$esperas = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de espera</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>

<?php require "menu.php"; ?>

<main class="contenedor">

<h1>Mi lista de espera</h1>

<?php if ($esperas->num_rows == 0): ?>

<p>No estás en lista de espera de ninguna actividad.</p>

<?php else: ?>

<div class="rejilla-reservas">

<?php while($fila = $esperas->fetch_assoc()): ?>

<article class="tarjeta-reserva espera">

<h3><?= escapar($fila["actividad"]) ?></h3>

<p>
<strong>Fecha:</strong>
<?= date("d/m/Y", strtotime($fila["fecha"])) ?>
</p>

<p>
<strong>Horario:</strong>
<?= substr($fila["hora_inicio"],0,5) ?>
 -
<?= substr($fila["hora_fin"],0,5) ?>
</p>

<p>
<strong>Espacio:</strong>
<?= escapar($fila["espacio"]) ?>
</p>

<p>
<strong>Estado:</strong>
<?= ucfirst($fila["estado"]) ?>
</p>

<p class="codigo-reserva">
<strong>Posición:</strong>
<?= $fila["posicion"] ?>
</p>

</article>

<?php endwhile; ?>

</div>

<?php endif; ?>

</main>

</body>
</html>

<?php
$stmt->close();
$conexion->close();
?>