<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once '../conexion.php';
require_once 'seguridad_admin.php';
require_once 'funciones_serie.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
header('Location: nueva_serie_sesiones.php');
exit;
}
$tokenRecibido = $_POST['csrf'] ?? '';
$tokenGuardado = $_SESSION['csrf_serie'] ?? '';
if (!$tokenGuardado ||
!hash_equals($tokenGuardado, $tokenRecibido)) {
    exit('Petición no válida.');
}
$idActividad = filter_input(
INPUT_POST,
'id_actividad',
FILTER_VALIDATE_INT
);
$idEspacio = filter_input(
INPUT_POST,
'id_espacio',
FILTER_VALIDATE_INT
);
$idMonitor = filter_input(
INPUT_POST,
'id_monitor',
FILTER_VALIDATE_INT
);
$fechaInicio = trim($_POST['fecha_inicio'] ?? '');
$fechaFin = trim($_POST['fecha_fin'] ?? '');
$horaInicio = trim($_POST['hora_inicio'] ?? '');
$duracion = filter_input(INPUT_POST, 'duracion', FILTER_VALIDATE_INT);
$aforo = filter_input(INPUT_POST, 'aforo', FILTER_VALIDATE_INT);
$dias = $_POST['dias'] ?? [];
$dias = array_values(array_unique(array_map('intval', $dias)));
$errores = [];
if (!$idActividad || !$idEspacio || !$idMonitor) {
$errores[] = 'Debes seleccionar actividad, espacio y monitor.';
}
if (!$dias || array_diff($dias, range(1, 7))) {
$errores[] = 'Selecciona días de la semana válidos.';
}
if (!fecha_valida($fechaInicio) || !fecha_valida($fechaFin)) {
$errores[] = 'Las fechas no son válidas.';
} else {
$inicio = new DateTime($fechaInicio);
$fin = new DateTime($fechaFin);
$hoy = new DateTime('today');
if ($inicio < $hoy) {
$errores[] = 'La fecha inicial no puede estar en el pasado.';
}
if ($fin < $inicio) {
$errores[] = 'La fecha final debe ser igual o posterior a la inicial.';
}
if ($inicio->diff($fin)->days > 366) {
$errores[] = 'La serie no puede superar un año.';
}
}
if (!hora_valida($horaInicio)) {
$errores[] = 'La hora de inicio no es válida.';
}
if (!$duracion || $duracion < 15 || $duracion > 480) {
$errores[] = 'La duración debe estar entre 15 y 480 minutos.';
}
if (!$aforo || $aforo < 1) {
$errores[] = 'El aforo debe ser como mínimo 1.';
}
if (!$errores) {
$sql = "SELECT
a.nombre AS actividad,
e.nombre AS espacio,
e.aforo_maximo,
m.nombre AS monitor
FROM actividades a
JOIN espacios e ON e.id_espacio = ? AND e.activo = 1
JOIN monitores m ON m.id_monitor = ? AND m.activo = 1
WHERE a.id_actividad = ? AND a.activa = 1";
$stmt = $conexion->prepare($sql);
$stmt->bind_param('iii', $idEspacio, $idMonitor, $idActividad);
$stmt->execute();
$datosRelacionados = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$datosRelacionados) {
$errores[] = 'Actividad, espacio o monitor no disponibles.';
} elseif ($aforo > (int) $datosRelacionados['aforo_maximo']) {
$errores[] = 'El aforo supera la capacidad del espacio.';
}
}
$sesiones = [];
if (!$errores) {
$fechas = generar_fechas($fechaInicio, $fechaFin, $dias);
if (!$fechas) {
$errores[] = 'La regla no genera ninguna sesión.';
} elseif (count($fechas) > 120) {
$errores[] = 'La serie no puede contener más de 120 sesiones.';
}
}
if (!$errores) {
foreach ($fechas as $fecha) {
$horaFin = calcular_hora_fin($fecha, $horaInicio, $duracion);
if ($horaFin === null) {
$errores[] = 'Una sesión no puede terminar al día siguiente.';
break;
}
$conflicto = buscar_conflicto(
$conexion,
$idEspacio,
$idMonitor,
$fecha,
$horaInicio . ':00',
$horaFin
);
$sesiones[] = [
'fecha' => $fecha,
'hora_inicio' => $horaInicio . ':00',
'hora_fin' => $horaFin,
'conflicto' => $conflicto
? describir_conflicto($conflicto, $idEspacio, $idMonitor)
: null
];
}
}
if (!$errores) {
$_SESSION['borrador_serie'] = [
'id_actividad' => (int) $idActividad,
'id_espacio' => (int) $idEspacio,
'id_monitor' => (int) $idMonitor,
'duracion' => (int) $duracion,
'aforo' => (int) $aforo,
'sesiones' => $sesiones,
'creado_en' => time()
];
}
$hayConflictos = !$errores && array_filter(
$sesiones,
fn ($sesion) => $sesion['conflicto'] !== null
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
<title>Vista previa de la serie | Reservar Llocs</title>
<link rel="stylesheet" href="../estilos.css">
</head>
<body>
<?php require_once __DIR__ . '/menu_admin.php'; ?>
<main class="contenedor seccion">
<a class="enlace-volver" href="nueva_serie_sesiones.php">
← Volver al formulario
</a>
<?php if ($errores): ?>
<h1>No se puede generar la serie</h1>
<div class="mensaje mensaje-error">
<ul>
<?php foreach ($errores as $error): ?>
<li><?= htmlspecialchars($error) ?></li>
<?php endforeach; ?>
</ul>
</div>
<a class="boton" href="nueva_serie_sesiones.php">
Volver al formulario
</a>
<?php else: ?>
<h1>Vista previa de la serie</h1>
<p>
<strong>Actividad:</strong>
<?= htmlspecialchars($datosRelacionados['actividad']) ?><br>
<strong>Espacio:</strong>
<?= htmlspecialchars($datosRelacionados['espacio']) ?><br>
<strong>Monitor:</strong>
<?= htmlspecialchars($datosRelacionados['monitor']) ?><br>
<strong>Sesiones generadas:</strong> <?= count($sesiones) ?>
</p>
<div class="tabla-responsive">
<table class="tabla-admin">
<thead>
<tr>
<th>Fecha</th>
<th>Inicio</th>
<th>Final</th>
<th>Resultado</th>
</tr>
</thead>
<tbody>
<?php foreach ($sesiones as $sesion): ?>
<tr>
<td><?= htmlspecialchars($sesion['fecha']) ?></td>
<td><?= substr($sesion['hora_inicio'], 0, 5) ?></td>
<td><?= substr($sesion['hora_fin'], 0, 5) ?></td>
<td>
<?php if ($sesion['conflicto']): ?>
<span class="estado estado-cancelada">
<?= htmlspecialchars($sesion['conflicto']) ?>
</span>
<?php else: ?>
<span class="estado estado-programada">
Disponible
</span>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php if (!$hayConflictos): ?>
<form action="confirmar_serie.php" method="post">
<input type="hidden" name="csrf"
value="<?= htmlspecialchars($_SESSION['csrf_serie']) ?>">
<button class="boton" type="submit">
Crear todas las sesiones
</button>
</form>
<?php else: ?>
<div class="mensaje mensaje-aviso">
La serie no puede guardarse mientras existan conflictos.
</div>
<?php endif; ?>
<a class="enlace-volver" href="nueva_serie_sesiones.php">
Cancelar y volver
</a>
<?php endif; ?>
</main>
</body>
</html>