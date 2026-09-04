<?php
require_once "seguridad.php";
require_once "conexion.php";
require_once "funciones.php";
$id_usuario = idUsuarioActual();
$sql_bonos = "
SELECT
bc.id_bono_cliente,
bc.fecha_compra,
bc.fecha_caducidad,
bc.usos_iniciales,
bc.usos_disponibles,
bc.estado,
tb.nombre AS nombre_bono
FROM bonos_clientes bc
INNER JOIN tipos_bono tb
ON bc.id_tipo_bono = tb.id_tipo_bono
WHERE bc.id_usuario = ?
ORDER BY bc.fecha_compra DESC
";
$stmt_bonos = $conexion->prepare($sql_bonos);
$stmt_bonos->bind_param("i", $id_usuario);
$stmt_bonos->execute();
$resultado_bonos = $stmt_bonos->get_result();
$bonos_activos = [];
$bonos_historial = [];
while ($bono = $resultado_bonos->fetch_assoc()) {
if ($bono["estado"] === "activo") {
$bonos_activos[] = $bono;
} else {
$bonos_historial[] = $bono;
}
}
$stmt_bonos->close();
$conexion->close();
$mensaje = $_GET["mensaje"] ?? "";
function renderizarTarjetaBono(array $bono): void
{
$iniciales = (int) $bono["usos_iniciales"];
$restantes = (int) $bono["usos_disponibles"];
$porcentaje = $iniciales > 0
? round(($restantes / $iniciales) * 100)
: 0;
?>
<article class="tarjeta-reserva">
<h3>
<?= escapar($bono["nombre_bono"]) ?>
</h3>
<p>
<strong>Comprado:</strong>
<?= date(
"d/m/Y",
strtotime($bono["fecha_compra"])
) ?>
</p>
<?php if ($bono["fecha_caducidad"]): ?>
<p>
<strong>Caduca:</strong>
<?= date(
"d/m/Y",
strtotime($bono["fecha_caducidad"])
) ?>
</p>
<?php endif; ?>
<p>
<strong>Usos iniciales:</strong>
<?= $iniciales ?>
</p>
<p>
<strong>Usos restantes:</strong>
<?= $restantes ?>
</p>
<div class="barra-progreso">
<div
class="barra-progreso-relleno"
style="width: <?= $porcentaje ?>%"
></div>
</div>
<p>
<?= $restantes ?> / <?= $iniciales ?>
</p>
<?php if ($bono["estado"] === "agotado"): ?>
<p><strong>Bono agotado</strong></p>
<?php else: ?>
<p>
<strong>Estado:</strong>
<?= escapar(ucfirst($bono["estado"])) ?>
</p>
<?php endif; ?>
</article>
<?php
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
<title>Mis bonos</title>
<link rel="stylesheet" href="estilos.css">
</head>
<body>
<?php require "menu.php"; ?>
<main class="contenedor">
<h1>Mis bonos</h1>
<?php if ($mensaje === "comprado"): ?>
<div class="mensaje mensaje-exito">
El bono se ha comprado correctamente.
</div>
<?php endif; ?>
<p>
<a class="boton" href="comprar_bono.php">
Comprar un bono
</a>
</p>
<h2>Bonos activos</h2>
<?php if (count($bonos_activos) === 0): ?>
<p>No tienes ningún bono activo.</p>
<?php else: ?>
<div class="rejilla-reservas">
<?php foreach ($bonos_activos as $bono): ?>
<?php renderizarTarjetaBono($bono); ?>
<?php endforeach; ?>
</div>
<?php endif; ?>
<h2>Historial</h2>
<?php if (count($bonos_historial) === 0): ?>
<p>Todavía no tienes bonos en el historial.</p>
<?php else: ?>
<div class="rejilla-reservas">
<?php foreach ($bonos_historial as $bono): ?>
<?php renderizarTarjetaBono($bono); ?>
<?php endforeach; ?>
</div>
<?php endif; ?>
</main>
</body>
</html>
