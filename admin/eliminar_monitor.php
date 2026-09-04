<?php
require_once "seguridad_admin.php";
require_once __DIR__ . '/../conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
header('Location: monitores.php');
exit;
}

$id_monitor = filter_input(
INPUT_POST,
'id_monitor',
FILTER_VALIDATE_INT
);
if (!$id_monitor) {
header('Location: monitores.php');
exit;
}

$sql_uso = "
SELECT COUNT(*) AS total
FROM sesiones
WHERE id_monitor = ?
";
$stmt_uso = $conexion->prepare($sql_uso);
$stmt_uso->bind_param('i', $id_monitor);
$stmt_uso->execute();
$fila_uso = $stmt_uso->get_result()->fetch_assoc();
if ((int) $fila_uso['total'] > 0) {
header('Location: monitores.php?error=en_uso');
exit;
}

$sql = "DELETE FROM monitores WHERE id_monitor = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param('i', $id_monitor);
$stmt->execute();
header('Location: monitores.php?mensaje=eliminado');
exit;
