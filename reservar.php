<?php
require_once "seguridad.php";
require_once "conexion.php";
require_once "funciones.php";
$id_sesion = filter_input(
INPUT_GET,
"id",
FILTER_VALIDATE_INT
);
if (!$id_sesion) {
http_response_code(400);
exit("Identificador de sesión no válido.");
}
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
m.apellidos AS monitor_apellidos,
(
SELECT COUNT(*)
FROM reservas r
WHERE r.id_sesion = s.id_sesion
AND r.estado = 'confirmada'
) AS plazas_ocupadas
FROM sesiones s
INNER JOIN actividades a
ON s.id_actividad = a.id_actividad
INNER JOIN espacios e
ON s.id_espacio = e.id_espacio
INNER JOIN monitores m
ON s.id_monitor = m.id_monitor
WHERE s.id_sesion = ?
";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_sesion);
$stmt->execute();
$resultado = $stmt->get_result();
$sesion = $resultado->fetch_assoc();
$stmt->close();
$conexion->close();
if (!$sesion) {
http_response_code(404);
exit("La sesión no existe.");
}
$inicio = new DateTime(
$sesion["fecha"] . " " . $sesion["hora_inicio"]
);
if (
!in_array(
$sesion["estado"],
["programada", "completa"],
true
) ||
$inicio <= new DateTime()
) {
exit("Esta sesión ya no admite reservas.");
}
$plazas_disponibles = max(
0,
(int) $sesion["aforo"] -
(int) $sesion["plazas_ocupadas"]
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta
name="viewport"
content="width=device-width, initial-scale=1.0"
>
<title>Confirmar reserva</title>
<link rel="stylesheet" href="estilos.css">
</head>
<body>
    <?php require "menu.php"; ?>
<main class="contenedor">
<h1>Confirmar solicitud</h1>
<section class="resumen-reserva">
<h2>
<?= escapar($sesion["actividad"]) ?>
</h2>
<p>
<strong>Fecha:</strong>
<?= date(
"d/m/Y",
strtotime($sesion["fecha"])
) ?>
</p>
<p>
<strong>Horario:</strong>
<?= substr($sesion["hora_inicio"], 0, 5) ?>
–
<?= substr($sesion["hora_fin"], 0, 5) ?>
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
<?php if ($plazas_disponibles > 0): ?>
<div class="mensaje exito">
Quedan
<?= $plazas_disponibles ?>
plazas disponibles.
</div>
<p>
Al confirmar se creará una reserva.
</p>
<?php else: ?>
<div class="mensaje aviso">
La sesión está completa.
</div>
<p>
Al confirmar entrarás en la lista de espera.
</p>
<?php endif; ?>
<form
action="procesar_reserva.php"
method="post"
>
<input
type="hidden"
name="id_sesion"
value="<?= $sesion["id_sesion"] ?>"
>
<button type="submit" class="boton">
Confirmar solicitud
</button>
</form>
</section>
</main>
</body>
</html>