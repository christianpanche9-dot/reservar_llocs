<?php
require_once "seguridad.php";
require_once "conexion.php";
$id_usuario = idUsuarioActual();
$sql_proximas = "
SELECT COUNT(*) AS total
FROM reservas r
INNER JOIN sesiones s
ON r.id_sesion = s.id_sesion
WHERE r.id_usuario = ?
AND r.estado = 'confirmada'
AND TIMESTAMP(s.fecha, s.hora_inicio) > NOW()
";
$stmt_proximas = $conexion->prepare($sql_proximas);
$stmt_proximas->bind_param("i", $id_usuario);
$stmt_proximas->execute();
$total_proximas = (int) $stmt_proximas
->get_result()
->fetch_assoc()["total"];
$stmt_proximas->close();
$sql_bonos_activos = "
SELECT COUNT(*) AS total
FROM bonos_clientes
WHERE id_usuario = ?
AND estado = 'activo'
AND usos_disponibles > 0
";
$stmt_bonos_activos = $conexion->prepare($sql_bonos_activos);
$stmt_bonos_activos->bind_param("i", $id_usuario);
$stmt_bonos_activos->execute();
$total_bonos_activos = (int) $stmt_bonos_activos
->get_result()
->fetch_assoc()["total"];
$stmt_bonos_activos->close();
$sql_usos = "
SELECT COALESCE(SUM(usos_disponibles), 0) AS total
FROM bonos_clientes
WHERE id_usuario = ?
AND estado = 'activo'
";
$stmt_usos = $conexion->prepare($sql_usos);
$stmt_usos->bind_param("i", $id_usuario);
$stmt_usos->execute();
$total_usos = (int) $stmt_usos
->get_result()
->fetch_assoc()["total"];
$stmt_usos->close();
$sql_pagos = "
SELECT COUNT(*) AS total
FROM pagos
WHERE id_usuario = ?
AND estado = 'pagado'
";
$stmt_pagos = $conexion->prepare($sql_pagos);
$stmt_pagos->bind_param("i", $id_usuario);
$stmt_pagos->execute();
$total_pagos = (int) $stmt_pagos
->get_result()
->fetch_assoc()["total"];
$stmt_pagos->close();
$sql_pendientes = "
SELECT COUNT(*) AS total
FROM reservas
WHERE id_usuario = ?
AND estado = 'pendiente_pago'
";
$stmt_pendientes = $conexion->prepare($sql_pendientes);
$stmt_pendientes->bind_param("i", $id_usuario);
$stmt_pendientes->execute();
$total_pendientes = (int) $stmt_pendientes
->get_result()
->fetch_assoc()["total"];
$stmt_pendientes->close();
$sql_proxima_actividad = "
SELECT
r.id_reserva,
a.nombre AS actividad,
s.fecha,
s.hora_inicio
FROM reservas r
INNER JOIN sesiones s
ON r.id_sesion = s.id_sesion
INNER JOIN actividades a
ON s.id_actividad = a.id_actividad
WHERE r.id_usuario = ?
AND r.estado = 'confirmada'
AND TIMESTAMP(s.fecha, s.hora_inicio) > NOW()
ORDER BY s.fecha ASC, s.hora_inicio ASC
LIMIT 1
";
$stmt_proxima_actividad =
$conexion->prepare($sql_proxima_actividad);
$stmt_proxima_actividad->bind_param("i", $id_usuario);
$stmt_proxima_actividad->execute();
$proxima_actividad = $stmt_proxima_actividad
->get_result()
->fetch_assoc();
$stmt_proxima_actividad->close();
$conexion->close();
if ($proxima_actividad) {
$hoy = new DateTime("today");
$fecha_actividad = new DateTime(
$proxima_actividad["fecha"]
);
$dias_restantes = (int) $hoy
->diff($fecha_actividad)
->format("%a");
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
<title>Mi cuenta</title>
<link rel="stylesheet" href="estilos.css">
</head>
<body>
<?php require "menu.php"; ?>
<main class="contenedor">
<h1>
Hola,
<?= escapar($_SESSION["usuario"]["nombre"]) ?>
</h1>
<?php if ($total_pendientes > 0): ?>
<div class="mensaje mensaje-aviso">
Tienes
<?= $total_pendientes ?>
<?= $total_pendientes === 1 ? "plaza pendiente" : "plazas pendientes" ?> de pago desde la lista de espera.
<a href="mis_reservas.php">Revisar</a>
</div>
<?php endif; ?>
<?php if ($proxima_actividad): ?>
<section class="tarjeta-resumen">
<span>Tu próxima actividad</span>
<strong>
<?= escapar($proxima_actividad["actividad"]) ?>
</strong>
<p>
<?= date(
"d/m/Y",
strtotime($proxima_actividad["fecha"])
) ?> ·
<?= substr(
$proxima_actividad["hora_inicio"],
0,
5
) ?>
</p>
<p>
<?php if ($dias_restantes === 0): ?>
Es hoy
<?php elseif ($dias_restantes === 1): ?>
Falta 1 día
<?php else: ?>
Faltan
<?= $dias_restantes ?> días
<?php endif; ?>
</p>
</section>
<?php endif; ?>
<section class="panel-cuenta">
<h2>Mis datos</h2>
<dl>
<dt>Nombre</dt>
<dd>
<?= escapar(
$_SESSION["usuario"]["nombre"] .
" " .
$_SESSION["usuario"]["apellidos"]
) ?>
</dd>
<dt>Correo electrónico</dt>
<dd>
    <?= escapar(
$_SESSION["usuario"]["email"]
) ?>
</dd>
</dl>
</section>
<section class="rejilla-resumen">
<div class="tarjeta-resumen">
<span>Próximas reservas</span>
<strong><?= $total_proximas ?></strong>
</div>
<div class="tarjeta-resumen">
<span>Bonos activos</span>
<strong><?= $total_bonos_activos ?></strong>
</div>
<div class="tarjeta-resumen">
<span>Usos disponibles</span>
<strong><?= $total_usos ?></strong>
</div>
<div class="tarjeta-resumen">
<span>Pagos realizados</span>
<strong><?= $total_pagos ?></strong>
</div>
</section>
<section class="opciones-cuenta">
<article class="tarjeta">
<h2>Mis reservas</h2>
<p>
    Consulta tus próximas actividades y tu historial.
</p>
<a class="boton" href="mis_reservas.php">
Ver mis reservas
</a>
</article>
<article class="tarjeta">
<h2>Mis bonos</h2>
<p>
Consulta tus bonos y los usos disponibles.
</p>
<a class="boton" href="mis_bonos.php">
Ver mis bonos
</a>
</article>
<article class="tarjeta">
<h2>Mis pagos</h2>
<p>
Consulta el historial de tus pagos.
</p>
<a class="boton" href="mis_pagos.php">
Ver mis pagos
</a>
</article>
</section>
</main>
</body>
</html>
