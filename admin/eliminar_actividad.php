<?php
require_once "seguridad_admin.php";
require_once __DIR__ . '/../conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
header('Location: actividades.php');
exit;
}

$id_actividad = filter_input(
INPUT_POST,
'id_actividad',
FILTER_VALIDATE_INT
);
if (!$id_actividad) {
header('Location: actividades.php');
exit;
}

$sql_uso = "
SELECT COUNT(*) AS total
FROM sesiones
WHERE id_actividad = ?
";
$stmt_uso = $conexion->prepare($sql_uso);
$stmt_uso->bind_param('i', $id_actividad);
$stmt_uso->execute();
$fila_uso = $stmt_uso->get_result()->fetch_assoc();
if ((int) $fila_uso['total'] > 0) {
header('Location: actividades.php?error=en_uso');
exit;
}

$sql = "DELETE FROM actividades WHERE id_actividad = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param('i', $id_actividad);
$stmt->execute();
header('Location: actividades.php?mensaje=eliminada');
exit;
