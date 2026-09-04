<?php
require_once "seguridad.php";
require_once "conexion.php";
require_once "funciones.php";
$id_reserva = filter_input(
INPUT_GET,
"id",
FILTER_VALIDATE_INT
);
$id_usuario = idUsuarioActual();
if (!$id_reserva) {
http_response_code(400);
exit("Identificador de reserva no válido.");
}
$sql = "
SELECT
r.id_reserva,
r.estado,
s.id_sesion,
s.fecha,
s.hora_inicio,
s.hora_fin,
a.nombre AS actividad,
a.precio AS precio,
e.nombre AS espacio
FROM reservas r
INNER JOIN sesiones s
ON r.id_sesion = s.id_sesion
INNER JOIN actividades a
ON s.id_actividad = a.id_actividad
INNER JOIN espacios e
ON s.id_espacio = e.id_espacio
WHERE r.id_reserva = ?
AND r.id_usuario = ?
";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("ii", $id_reserva, $id_usuario);
$stmt->execute();
$reserva = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conexion->close();
if (!$reserva) {
http_response_code(404);
exit("La reserva no existe.");
}
if ($reserva["estado"] !== "pendiente_pago") {
header("Location: mis_reservas.php");
exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta
name="viewport"
content="width=device-width, initial-scale=1.0"
>
<title>Confirmar y pagar</title>
<link rel="stylesheet" href="estilos.css">
</head>
<body>
<?php require "menu.php"; ?>
<main class="contenedor">
<h1>Plaza disponible</h1>
<div class="mensaje mensaje-aviso">
Has obtenido una plaza desde la lista de espera.
Confirma cómo quieres pagarla.
</div>
<section class="resumen-reserva">
<h2>
<?= escapar($reserva["actividad"]) ?>
</h2>
<p>
<strong>Fecha:</strong>
<?= date(
"d/m/Y",
strtotime($reserva["fecha"])
) ?>
</p>
<p>
<strong>Horario:</strong>
<?= substr($reserva["hora_inicio"], 0, 5) ?>
–
<?= substr($reserva["hora_fin"], 0, 5) ?>
</p>
<p>
<strong>Espacio:</strong>
<?= escapar($reserva["espacio"]) ?>
</p>
<form action="procesar_confirmacion_pago.php" method="post">
<input
type="hidden"
name="id_reserva"
value="<?= $reserva["id_reserva"] ?>"
>
<p>Precio:
<?= number_format($reserva["precio"], 2) ?> €
</p>
<label>
<input
type="radio"
name="metodo_pago"
value="pago"
required
>
Pagar actividad
</label>
<br>
<label>
<input
type="radio"
name="metodo_pago"
value="bono"
>
Utilizar bono
</label>
<button type="submit" class="boton">
Confirmar y pagar
</button>
</form>
</section>
</main>
</body>
</html>
