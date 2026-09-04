<?php
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
$borrador = $_SESSION['borrador_serie'] ?? null;
if (!$tokenGuardado ||
!hash_equals($tokenGuardado, $tokenRecibido) ||
!$borrador) {
exit('La confirmación no es válida o ha caducado.');
}
if (time() - $borrador['creado_en'] > 900) {
unset($_SESSION['borrador_serie']);
exit('La vista previa ha caducado. Vuelve a generarla.');
}
$idActividad = (int) $borrador['id_actividad'];
$idEspacio = (int) $borrador['id_espacio'];
$idMonitor = (int) $borrador['id_monitor'];
$aforo = (int) $borrador['aforo'];
$sesiones = $borrador['sesiones'];
if (!$sesiones || count($sesiones) > 120) {
exit('La serie no contiene sesiones válidas.');
}
try {
$conexion->begin_transaction();
$sqlDatos = "SELECT a.id_actividad, e.aforo_maximo, m.id_monitor
FROM actividades a
JOIN espacios e ON e.id_espacio = ? AND e.activo = 1
JOIN monitores m ON m.id_monitor = ? AND m.activo = 1
WHERE a.id_actividad = ? AND a.activa = 1
FOR UPDATE";
$stmtDatos = $conexion->prepare($sqlDatos);
$stmtDatos->bind_param('iii', $idEspacio, $idMonitor, $idActividad);
$stmtDatos->execute();
$datos = $stmtDatos->get_result()->fetch_assoc();
$stmtDatos->close();
if (!$datos || $aforo < 1 || $aforo > (int) $datos['aforo_maximo']) {
throw new RuntimeException('Datos principales no válidos.');
}
$sqlInsertar = "INSERT INTO sesiones
(id_actividad, id_espacio, id_monitor, fecha,
hora_inicio, hora_fin, aforo, estado)
VALUES (?, ?, ?, ?, ?, ?, ?, 'programada')";
$stmtInsertar = $conexion->prepare($sqlInsertar);
foreach ($sesiones as $sesion) {
$fecha = $sesion['fecha'] ?? '';
$horaInicio = $sesion['hora_inicio'] ?? '';
$horaFin = $sesion['hora_fin'] ?? '';
if (!fecha_valida($fecha) ||
!preg_match('/^\d{2}:\d{2}:\d{2}$/', $horaInicio) ||
!preg_match('/^\d{2}:\d{2}:\d{2}$/', $horaFin)) {
throw new RuntimeException('Sesión del borrador no válida.');
}
$conflicto = buscar_conflicto(
$conexion,
$idEspacio,
$idMonitor,
$fecha,
$horaInicio,
$horaFin,
true
);
if ($conflicto) {
throw new RuntimeException(
'Ha aparecido un conflicto en la fecha ' . $fecha
);
}
$stmtInsertar->bind_param(
'iiisssi',
$idActividad,
$idEspacio,
$idMonitor,
$fecha,
$horaInicio,
$horaFin,
$aforo
);
$stmtInsertar->execute();
}
$stmtInsertar->close();
$conexion->commit();
$cantidadCreada = count($sesiones);
unset($_SESSION['borrador_serie']);
// El token usado deja de ser válido.
$_SESSION['csrf_serie'] = bin2hex(random_bytes(32));
header(
'Location: sesiones.php?mensaje=serie_creada&cantidad=' .
$cantidadCreada
);
exit;
} catch (Throwable $error) {
$conexion->rollback();
// En producción se registra el detalle y se muestra un mensaje neutro.
error_log($error->getMessage());
header('Location: nueva_serie_sesiones.php?error=serie');
exit;
}