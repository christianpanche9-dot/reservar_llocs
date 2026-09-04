<?php
require_once "seguridad_admin.php";
require_once __DIR__ . '/../conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
header('Location: espacios.php');
exit;
}

$id_espacio = filter_input(
INPUT_POST,
'id_espacio',
FILTER_VALIDATE_INT
);
if (!$id_espacio) {
header('Location: espacios.php');
exit;
}

$sql_uso = "
SELECT COUNT(*) AS total
FROM sesiones
WHERE id_espacio = ?
";
$stmt_uso = $conexion->prepare($sql_uso);
$stmt_uso->bind_param('i', $id_espacio);
$stmt_uso->execute();
$fila_uso = $stmt_uso->get_result()->fetch_assoc();
if ((int) $fila_uso['total'] > 0) {
header('Location: espacios.php?error=en_uso');
exit;
}

$sql = "DELETE FROM espacios WHERE id_espacio = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param('i', $id_espacio);
$stmt->execute();
header('Location: espacios.php?mensaje=eliminado');
exit;
