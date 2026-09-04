<?php
function calcular_hora_fin(
string $fecha,
string $horaInicio,
int $duracion
): ?string {
$inicio = DateTime::createFromFormat(
'Y-m-d H:i',
$fecha . ' ' . $horaInicio
);
if ($inicio === false) {
return null;
}
$fin = clone $inicio;
$fin->modify('+' . $duracion . ' minutes');
if ($fin->format('Y-m-d') !== $fecha) {
return null;
}
return $fin->format('H:i:s');
}

function generar_fechas(
string $fechaInicio,
string $fechaFin,
array $diasSemana
): array {
$inicio = new DateTime($fechaInicio);
$fin = new DateTime($fechaFin);
// DatePeriod no incluye el límite final.
$finInclusivo = (clone $fin)->modify('+1 day');
$periodo = new DatePeriod(
$inicio,
new DateInterval('P1D'),
$finInclusivo
);
$fechas = [];
foreach ($periodo as $fecha) {
$numeroDia = (int) $fecha->format('N');
if (in_array($numeroDia, $diasSemana, true)) {
$fechas[] = $fecha->format('Y-m-d');
}
}
return $fechas;
}

function buscar_conflicto(
mysqli $conexion,
int $idEspacio,
int $idMonitor,
string $fecha,
string $horaInicio,
string $horaFin,
bool $bloquear = false
): ?array {
$sql = "SELECT id_sesion, id_espacio, id_monitor,
hora_inicio, hora_fin
FROM sesiones
WHERE fecha = ?
AND estado <> 'cancelada'
AND (id_espacio = ? OR id_monitor = ?)
AND hora_inicio < ?
AND hora_fin > ?
LIMIT 1";
if ($bloquear) {
$sql .= " FOR UPDATE";
}
$stmt = $conexion->prepare($sql);
$stmt->bind_param(
'siiss',
$fecha,
$idEspacio,
$idMonitor,
$horaFin,
$horaInicio
);
$stmt->execute();
$resultado = $stmt->get_result();
$fila = $resultado->fetch_assoc();
$stmt->close();
return $fila ?: null;
}

function describir_conflicto(
array $conflicto,
int $idEspacio,
int $idMonitor
): string {
$motivos = [];
if ((int) $conflicto['id_espacio'] === $idEspacio) {
$motivos[] = 'espacio ocupado';
}
if ((int) $conflicto['id_monitor'] === $idMonitor) {
$motivos[] = 'monitor no disponible';
}
return implode(' y ', $motivos);
}

