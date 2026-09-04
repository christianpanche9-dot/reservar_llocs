<?php
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/funciones.php';
$id_actividad = filter_input(
INPUT_GET,
'id',
FILTER_VALIDATE_INT
);
if (!$id_actividad || $id_actividad < 1) {
http_response_code(400);
die('El identificador no es válido.');
}
$sql_actividad = "
SELECT
id_actividad,
nombre,
descripcion,
categoria,
nivel,
duracion_minutos,
imagen
FROM actividades
WHERE id_actividad = ?
AND activa = 1
";
$stmt_actividad =
$conexion->prepare($sql_actividad);
$stmt_actividad->bind_param(
'i',
$id_actividad
);
$stmt_actividad->execute();
$resultado_actividad =
$stmt_actividad->get_result();
$actividad =
$resultado_actividad->fetch_assoc();
if (!$actividad) {
http_response_code(404);
die('La actividad no existe o no está disponible.');
}
$sql_sesiones = "
SELECT
s.id_sesion,
s.fecha,
s.hora_inicio,
s.hora_fin,
s.aforo,
s.estado,
s.observaciones,
e.nombre AS espacio,
e.ubicacion,
CONCAT(
m.nombre,
' ',
m.apellidos
) AS monitor,
COUNT(r.id_reserva)
AS reservas_confirmadas,
GREATEST(
s.aforo - COUNT(r.id_reserva),
0
) AS plazas_disponibles
FROM sesiones AS s
INNER JOIN espacios AS e
ON s.id_espacio = e.id_espacio
INNER JOIN monitores AS m
ON s.id_monitor = m.id_monitor
LEFT JOIN reservas AS r
ON r.id_sesion = s.id_sesion
AND r.estado = 'confirmada'
WHERE s.id_actividad = ?
AND s.estado IN (
'programada',
'completa'
)
AND TIMESTAMP(
s.fecha,
s.hora_fin
) >= NOW()
GROUP BY
s.id_sesion,
s.fecha,
s.hora_inicio,
s.hora_fin,
s.aforo,
s.estado,
s.observaciones,
e.nombre,
e.ubicacion,
m.nombre,
m.apellidos
ORDER BY
s.fecha,
s.hora_inicio
";
$stmt_sesiones =
$conexion->prepare($sql_sesiones);
$stmt_sesiones->bind_param(
'i',
$id_actividad
);
$stmt_sesiones->execute();
$resultado_sesiones =
$stmt_sesiones->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta
name="viewport"
content="width=device-width, initial-scale=1.0"
>
<title>
<?= escapar($actividad['nombre']) ?>
| Reservar Llocs
</title>
<link rel="stylesheet" href="estilos.css">
</head>
<body>
<?php require_once __DIR__ . '/menu.php'; ?>
<main>
<section class="detalle-actividad">
<div class="contenedor detalle-cabecera">
<div>
<?php if (!empty($actividad['imagen'])): ?>
<img
class="imagen-detalle"
src="imagenes/actividades/<?= escapar(
$actividad['imagen']
) ?>"
alt="<?= escapar(
$actividad['nombre']
) ?>"
>
<?php endif; ?>
</div>
<div>
<div class="metadatos">
<span class="insignia">
    <?= escapar(
$actividad['categoria']
) ?>
</span>
<span class="insignia insignia-clara">
<?= escapar(
texto_nivel(
$actividad['nivel']
)
) ?>
</span>
</div>
<h1>
</h1>
<?= escapar($actividad['nombre']) ?>
<p class="descripcion-destacada">
<?= escapar(
$actividad['descripcion']
) ?>
</p>
<p>
<strong>Duración habitual:</strong>
<?= (int)
$actividad['duracion_minutos'] ?>
minutos
</p>
<a
class="enlace-volver"
href="actividades.php"
>
← Volver a las actividades
</a>
</div>
</div>
</section>
<section class="contenedor seccion">
<h2>Próximas sesiones</h2>
<?php if (
    $resultado_sesiones->num_rows === 0
): ?>

<div class="mensaje mensaje-aviso">
Esta actividad todavía no tiene
próximas sesiones disponibles.
</div>
<?php else: ?>
<div class="lista-sesiones">
<?php while (
$sesion =
$resultado_sesiones->fetch_assoc()
): ?>
<?php
$plazas = (int)
$sesion['plazas_disponibles'];
$reservas = (int)
$sesion['reservas_confirmadas'];
$aforo = (int)
$sesion['aforo'];
$porcentaje =
calcular_porcentaje_ocupacion(
$reservas,
$aforo
);
?>
<article class="tarjeta-sesion">
<div class="fecha-sesion">
<span class="fecha-principal">
<?= escapar(
formatear_fecha(
$sesion['fecha']
)
) ?>
</span>
<span>
<?= escapar(
formatear_hora(
$sesion['hora_inicio']
)
) ?>
–
<?= escapar(
formatear_hora(
$sesion['hora_fin']
)
) ?>
</span>
</div>
<div class="datos-sesion">
<p>
<strong>Espacio:</strong>
<?= escapar(
$sesion['espacio']
) ?>
</p>
<?php if (
    !empty($sesion['ubicacion'])
): ?>

<p>
<strong>Ubicación:</strong>
<?= escapar(
$sesion['ubicacion']
) ?>
</p>
<?php endif; ?>
<p>
<strong>Monitor:</strong>
<?= escapar(
$sesion['monitor']
) ?>
</p>
<p>
</p>
<strong>Aforo:</strong>
<?= $aforo ?>
persona
<div class="barra-ocupacion">
<div
class="barra-ocupacion-interior"
style="width:
<?= $porcentaje ?>%"
></div>
</div>
<small>
<?= $reservas ?>
de
<?= $aforo ?>
plazas ocupadas
</small>
</div>
<div class="acciones-sesion">
<?php if ($plazas > 0): ?>
<span class="plazas-disponibles">
<?= $plazas ?>
plazas disponibles
</span>
<?php else: ?>
<span class="sesion-completa">
Sesión completa
</span>
<?php endif; ?>
<a
class="boton"
href="detalle_sesion.php?id=<?= (int)
$sesion['id_sesion'] ?>"
>
Ver sesión
</a>
</div>
</article>
<?php endwhile; ?>
</div>
<?php endif; ?>
</section>
</main>
</body>
</html>