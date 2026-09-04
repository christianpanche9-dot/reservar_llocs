<?php
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/funciones.php';
$id_sesion = filter_input(
INPUT_GET,
'id',
FILTER_VALIDATE_INT
);
if (!$id_sesion || $id_sesion < 1) {
http_response_code(400);
die('El identificador de la sesión no es válido.');
}
$sql = "
SELECT
s.id_sesion,
s.fecha,
s.hora_inicio,
s.hora_fin,
s.aforo,
s.estado,
s.observaciones,
TIMESTAMPDIFF(
MINUTE,
s.hora_inicio,
s.hora_fin
) AS duracion_real,
a.id_actividad,
a.nombre AS actividad,
a.descripcion,
a.categoria,
a.nivel,
a.duracion_minutos,
a.imagen,
e.nombre AS espacio,
e.ubicacion,
e.descripcion AS descripcion_espacio,
e.aforo_maximo,
CONCAT(
m.nombre,
' ',
m.apellidos
) AS monitor,
m.especialidad,
COUNT(r.id_reserva)
AS reservas_confirmadas,
GREATEST(
s.aforo - COUNT(r.id_reserva),
0
) AS plazas_disponibles
FROM sesiones AS s
INNER JOIN actividades AS a
ON s.id_actividad = a.id_actividad
INNER JOIN espacios AS e
ON s.id_espacio = e.id_espacio
INNER JOIN monitores AS m
ON s.id_monitor = m.id_monitor
LEFT JOIN reservas AS r
ON r.id_sesion = s.id_sesion
AND r.estado = 'confirmada'
WHERE s.id_sesion = ?
AND a.activa = 1
GROUP BY
s.id_sesion,
s.fecha,
s.hora_inicio,
s.hora_fin,
s.aforo,
s.estado,
s.observaciones,
a.id_actividad,
a.nombre,
a.descripcion,
a.categoria,
a.nivel,
a.duracion_minutos,
a.imagen,
e.nombre,
e.ubicacion,
e.descripcion,
e.aforo_maximo,
m.nombre,
m.apellidos,
m.especialidad
";
$stmt = $conexion->prepare($sql);
$stmt->bind_param(
'i',
$id_sesion
);
$stmt->execute();
$resultado = $stmt->get_result();
$sesion = $resultado->fetch_assoc();
if (!$sesion) {
http_response_code(404);
die('La sesión solicitada no existe.');
}
$plazas =
(int) $sesion['plazas_disponibles'];
$reservas =
(int) $sesion['reservas_confirmadas'];
$aforo =
(int) $sesion['aforo'];
$porcentaje =
calcular_porcentaje_ocupacion(
$reservas,
$aforo
);
$fecha_final = new DateTime(
$sesion['fecha'] . ' ' .
$sesion['hora_fin']
);
$sesion_terminada =
$fecha_final < new DateTime();
$puede_reservarse =
!$sesion_terminada
&& $sesion['estado'] === 'programada'
&& $plazas > 0;
$usuario_autenticado = usuarioAutenticado();

$lista_espera =
    !$sesion_terminada &&
    $sesion['estado'] === 'completa' &&
    $plazas === 0;
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
<?= escapar($sesion['actividad']) ?>
| Reservar Llocs
</title>
<link rel="stylesheet" href="estilos.css">
</head>
<body>
<?php require_once __DIR__ . '/menu.php'; ?>
<main class="contenedor seccion">
<a
class="enlace-volver"
href="detalle_actividad.php?id=<?= (int)
$sesion['id_actividad'] ?>"
>
← Volver a la actividad
</a>
<div class="ficha-sesion">
<section class="informacion-sesion">
<div class="metadatos">
<span class="insignia">
<?= escapar(
$sesion['categoria']
) ?>
</span>
<span class="insignia insignia-clara">
<?= escapar(
texto_nivel(
$sesion['nivel']
)
) ?>
</span>
</div>
<h1>
    <?= escapar($sesion['actividad']) ?>
</h1>

<p class="descripcion-destacada">
<?= escapar(
$sesion['descripcion']
) ?>
</p>
<div class="rejilla-datos">
    <div class="dato">
<span>Fecha</span>
<strong>
<?= escapar(
formatear_fecha(
$sesion['fecha']
)
) ?>
</strong>
</div>
<div class="dato">
<span>Horario</span>
<strong>
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
</strong>
</div>
<div class="dato">
<span>Duración</span>
<strong>
<?= (int)
$sesion['duracion_real'] ?>
minutos
</strong>
</div>
<div class="dato">
<span>Espacio</span>
<strong>
<?= escapar(
$sesion['espacio']
) ?>
</strong>
</div>
<div class="dato">
<span>Ubicación</span>
<strong>
<?= escapar(
$sesion['ubicacion']
) ?>
</strong>
</div>
<div class="dato">
<span>Monitor</span>
<strong>
<?= escapar(
$sesion['monitor']
) ?>
</strong>
</div>
</div>
<?php if (
    !empty($sesion['especialidad'])
): ?>

<p>
<strong>Especialidad del monitor:</strong>
<?= escapar(
$sesion['especialidad']
) ?>
</p>
<?php endif; ?>
<?php if (
    !empty($sesion['observaciones'])
): ?>

<div class="mensaje mensaje-aviso">
<strong>Observaciones:</strong>
<?= escapar(
$sesion['observaciones']
) ?>
</div>
<?php endif; ?>
</section>
<aside class="panel-reserva">
<span
class="estado estado-<?= escapar(
clase_estado_sesion(
$sesion['estado']
)
) ?>"
>
<?= escapar(
texto_estado_sesion(
    $sesion['estado']
)
) ?>
</span>
<h2>Disponibilidad</h2>
<p class="numero-plazas">
<?= $plazas ?>
</p>
<p>
    plazas disponibles de <?= $aforo ?>
</p>

<div class="barra-ocupacion">
<div
class="barra-ocupacion-interior"
style="width:
<?= $porcentaje ?>%"
></div>
</div>
<p>
    <?= $reservas ?>
 reservas confirmadas
</p>
<?php if ($sesion_terminada): ?>

    <div class="mensaje mensaje-aviso">
        Esta sesión ya ha finalizado.
    </div>

<?php elseif ($sesion['estado'] === 'cancelada'): ?>

    <div class="mensaje mensaje-error">
        Esta sesión ha sido cancelada.
    </div>

<?php elseif ($plazas > 0): ?>

    <?php if ($usuario_autenticado): ?>

        <a
            class="boton boton-bloque"
            href="reservar.php?id=<?= (int)$sesion['id_sesion'] ?>"
        >
            Reservar una plaza
        </a>

    <?php else: ?>

        <a
            class="boton boton-espera boton-bloque"
            href="login.php?error=acceso&volver=<?= urlencode('/detalle_sesion.php?id=' . $sesion['id_sesion']) ?>"
        >
            Inicia sesión para reservar
        </a>

    <?php endif; ?>

<?php elseif ($lista_espera): ?>

    <a
        class="boton secundario"
        href="apuntar_lista_espera.php?id=<?= (int)$sesion['id_sesion'] ?>"
    >
        Apuntarme a la lista de espera
    </a>

<?php endif; ?>
</aside>
</div>
</main>
</body>
</html>