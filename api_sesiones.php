<?php
require_once 'conexion.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
function responder(array $datos, int $codigo = 200): never
{
http_response_code($codigo);
echo json_encode($datos, JSON_UNESCAPED_UNICODE);
exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
responder([
'ok' => false,
'error' => 'Método no permitido.'
], 405);
}
$inicioTexto = trim($_GET['inicio'] ?? '');
$finTexto = trim($_GET['fin'] ?? '');
$idActividad = filter_input(
INPUT_GET,
'id_actividad',
FILTER_VALIDATE_INT
);
if ($idActividad === false) {
responder([
'ok' => false,
'error' => 'La actividad no es válida.'
], 400);
}
function convertir_fecha(string $texto): ?DateTimeImmutable
{
$fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $texto);
if ($fecha === false || $fecha->format('Y-m-d') !== $texto) {
return null;
}
return $fecha;
}
$inicio = convertir_fecha($inicioTexto);
$fin = convertir_fecha($finTexto);
if (!$inicio || !$fin || $fin < $inicio) {
responder([
'ok' => false,
'error' => 'El intervalo de fechas no es válido.'
], 400);
}
if ($inicio->diff($fin)->days > 41) {
responder([
'ok' => false,
'error' => 'El intervalo no puede contener más de 42 días.'
], 400);
}
$sql = "SELECT
s.id_sesion,
s.fecha,
TIME_FORMAT(s.hora_inicio, '%H:%i') AS inicio,
TIME_FORMAT(s.hora_fin, '%H:%i') AS fin,
s.aforo,
a.id_actividad,
a.nombre AS actividad,
a.categoria,
a.nivel,
e.nombre AS espacio,
m.nombre AS monitor,
COUNT(CASE WHEN r.estado = 'confirmada' THEN 1 END)
AS confirmadas
FROM sesiones s
JOIN actividades a
ON a.id_actividad = s.id_actividad AND a.activa = 1
JOIN espacios e ON e.id_espacio = s.id_espacio
JOIN monitores m ON m.id_monitor = s.id_monitor
LEFT JOIN reservas r ON r.id_sesion = s.id_sesion
WHERE s.estado = 'programada'
AND s.fecha BETWEEN ? AND ?";
$tipos = 'ss';
$parametros = [$inicioTexto, $finTexto];
if ($idActividad) {
$sql .= " AND s.id_actividad = ?";
$tipos .= 'i';
$parametros[] = $idActividad;
}
$sql .= " GROUP BY
s.id_sesion, s.fecha, s.hora_inicio, s.hora_fin,
s.aforo, a.id_actividad, a.nombre, a.categoria,
a.nivel, e.nombre, m.nombre
ORDER BY s.fecha, s.hora_inicio";
$stmt = $conexion->prepare($sql);
$stmt->bind_param($tipos, ...$parametros);
$stmt->execute();
$resultado = $stmt->get_result();
$sesiones = [];
while ($fila = $resultado->fetch_assoc()) {
$aforo = (int) $fila['aforo'];
$confirmadas = (int) $fila['confirmadas'];
$plazas = max(0, $aforo - $confirmadas);
$sesiones[] = [
'id' => (int) $fila['id_sesion'],
'id_actividad' => (int) $fila['id_actividad'],
'actividad' => $fila['actividad'],
'categoria' => $fila['categoria'],
'nivel' => $fila['nivel'],
'fecha' => $fila['fecha'],
'inicio' => $fila['inicio'],
'fin' => $fila['fin'],
'espacio' => $fila['espacio'],
'monitor' => $fila['monitor'],
'aforo' => $aforo,
'confirmadas' => $confirmadas,
'plazas' => $plazas,
'completa' => $plazas === 0
];
}
$stmt->close();
responder([
'ok' => true,
'inicio' => $inicioTexto,
'fin' => $finTexto,
'total' => count($sesiones),
'sesiones' => $sesiones
]);

try {
// Validación, consulta y construcción de la respuesta.
} catch (Throwable $error) {
error_log($error->getMessage());
responder([
'ok' => false,
'error' => 'No se han podido obtener las sesiones.'
], 500);
}