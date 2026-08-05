<?php
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/funciones.php';
$fecha = trim($_GET['fecha'] ?? '');
if ($fecha !== '' && !fecha_valida($fecha)) {
$fecha = '';
}
$condiciones = [
'a.activa = 1',
"s.estado IN ('programada', 'completa')",
'TIMESTAMP(s.fecha, s.hora_fin) >= NOW()'
];
$tipos = '';
$valores = [];
if ($fecha !== '') {
$condiciones[] = 's.fecha = ?';
$tipos .= 's';
$valores[] = $fecha;
}
$where = implode(' AND ', $condiciones);
$sql = "
SELECT
s.id_sesion,
s.fecha,
s.hora_inicio,
s.hora_fin,
s.aforo,
s.estado,
a.id_actividad,
a.nombre AS actividad,
a.categoria,
a.nivel,
e.nombre AS espacio,
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
INNER JOIN actividades AS a
ON s.id_actividad = a.id_actividad
INNER JOIN espacios AS e
ON s.id_espacio = e.id_espacio
INNER JOIN monitores AS m
ON s.id_monitor = m.id_monitor
LEFT JOIN reservas AS r
ON r.id_sesion = s.id_sesion
AND r.estado = 'confirmada'
WHERE $where
GROUP BY
s.id_sesion,
s.fecha,
s.hora_inicio,
s.hora_fin,
s.aforo,
s.estado,
a.id_actividad,
a.nombre,
a.categoria,
a.nivel,
e.nombre,
m.nombre,
m.apellidos
ORDER BY
s.fecha,
s.hora_inicio
";
$stmt = $conexion->prepare($sql);
if ($tipos !== '') {
$stmt->bind_param(
$tipos,
...$valores
);
}
$stmt->execute();
$resultado = $stmt->get_result();
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
    Próximas sesiones | Reservar Llocs
</title>
<link rel="stylesheet" href="estilos.css">
</head>

<body>
<?php require_once __DIR__ . '/menu.php'; ?>
<main class="contenedor seccion">
<div class="encabezado-pagina">
<div>
<p class="etiqueta">
Calendario
</p>
<h1>Próximas sesiones</h1>
<p>
</p>
</div>
</div>
Consulta las actividades que se celebrarán
próximamente.
<form
class="formulario-filtros"
action="sesiones.php"
method="get"    
>
<div class="campo">
<label for="fecha">
Buscar por fecha
</label>
<input
type="date"
id="fecha"
name="fecha"
value="<?= escapar($fecha) ?>"
>
</div>
<div class="acciones-filtro">
<button class="boton" type="submit">
Buscar
</button>
<a
class="boton boton-secundario"
href="sesiones.php"
>
Mostrar todas
</a>
</div>
</form>
<?php if ($resultado->num_rows === 0): ?>
<div class="mensaje mensaje-aviso">
No hay sesiones disponibles
para la fecha seleccionada.
</div>
<?php else: ?>
<div class="lista-sesiones">
<?php while (
$sesion = $resultado->fetch_assoc()
): ?>
<?php
$plazas = (int)
$sesion['plazas_disponibles'];
$reservas = (int)
$sesion['reservas_confirmadas'];
$aforo = (int)
$sesion['aforo'];
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
<p class="insignia">
<?= escapar(
$sesion['categoria']
) ?>
</p>

<h2>
<?= escapar(
$sesion['actividad']
) ?>
</h2>

<p>
<strong>Espacio:</strong>
<?= escapar(
$sesion['espacio']
) ?>
</p>
<p>
<strong>Monitor:</strong>
<?= escapar(
$sesion['monitor']
) ?>
</p>
<p>
<?= $reservas ?>
de
<?= $aforo ?>
plazas ocupadas
</p>
</div>
<div class="acciones-sesion">
<?php if ($plazas > 0): ?>
<span class="plazas-disponibles">
<?= $plazas ?>
plazas disponibles
</span>
<?php else: ?>
<span class="sesion-completa">
Lista de espera disponible
</span>
<?php endif; ?>
<a
class="boton"
href="detalle_sesion.php?id=<?= (int)
$sesion['id_sesion'] ?>"
>
Ver detalles
</a>
</div>
</article>
<?php endwhile; ?>
</div>
<?php endif; ?>
</main>
</body>
</html>