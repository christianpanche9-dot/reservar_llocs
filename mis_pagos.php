<?php
require_once "seguridad.php";
require_once "conexion.php";
require_once "funciones.php";
$id_usuario = idUsuarioActual();
$sql_pagos = "
SELECT
p.id_pago,
p.concepto,
p.importe,
p.fecha_pago,
p.estado,
r.id_reserva,
a.nombre AS actividad,
s.fecha AS fecha_sesion
FROM pagos p
LEFT JOIN reservas r
ON p.id_reserva = r.id_reserva
LEFT JOIN sesiones s
ON r.id_sesion = s.id_sesion
LEFT JOIN actividades a
ON s.id_actividad = a.id_actividad
WHERE p.id_usuario = ?
ORDER BY p.fecha_pago DESC
";
$stmt_pagos = $conexion->prepare($sql_pagos);
$stmt_pagos->bind_param("i", $id_usuario);
$stmt_pagos->execute();
$pagos = $stmt_pagos->get_result();
$sql_total = "
SELECT COALESCE(SUM(importe), 0) AS total
FROM pagos
WHERE id_usuario = ?
AND estado = 'pagado'
";
$stmt_total = $conexion->prepare($sql_total);
$stmt_total->bind_param("i", $id_usuario);
$stmt_total->execute();
$total_pagado = (float) $stmt_total
->get_result()
->fetch_assoc()["total"];
$stmt_total->close();
$etiquetas_estado = [
"pendiente" => "Pendiente",
"pagado" => "Pagado",
"reembolsado" => "Reembolsado",
"cancelado" => "Cancelado"
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
<title>Mis pagos</title>
<link rel="stylesheet" href="estilos.css">
</head>
<body>
<?php require "menu.php"; ?>
<main class="contenedor">
<h1>Mis pagos</h1>
<div class="tarjeta-resumen">
<span>Total pagado</span>
<strong>
<?= number_format($total_pagado, 2, ",", ".") ?> €
</strong>
</div>
<?php if ($pagos->num_rows === 0): ?>
<p>Todavía no tienes pagos registrados.</p>
<?php else: ?>
<div class="rejilla-reservas">
<?php while ($pago = $pagos->fetch_assoc()): ?>
<article class="tarjeta-reserva">
<h3>
<?= escapar($pago["concepto"]) ?>
</h3>
<?php if ($pago["actividad"]): ?>
<p>
<strong>Actividad:</strong>
<?= escapar($pago["actividad"]) ?>
<?php if ($pago["fecha_sesion"]): ?>
·
<?= date(
"d/m/Y",
strtotime($pago["fecha_sesion"])
) ?>
<?php endif; ?>
</p>
<?php endif; ?>
<p>
<strong>Fecha de pago:</strong>
<?= date(
"d/m/Y",
strtotime($pago["fecha_pago"])
) ?>
</p>
<p>
<strong>Importe:</strong>
<?= number_format(
$pago["importe"],
2,
",",
"."
) ?> €
</p>
<p>
<strong>Estado:</strong>
<?= escapar(
$etiquetas_estado[$pago["estado"]]
?? ucfirst($pago["estado"])
) ?>
</p>
</article>
<?php endwhile; ?>
</div>
<?php endif; ?>
</main>
</body>
</html>
<?php
$stmt_pagos->close();
$conexion->close();
