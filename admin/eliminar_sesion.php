<?php
require_once "seguridad_admin.php";
require_once "../conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
header("Location: sesiones.php");
exit;
}

$id_sesion = filter_input(
INPUT_POST,
"id_sesion",
FILTER_VALIDATE_INT
);
if (!$id_sesion) {
header("Location: sesiones.php");
exit;
}

$sql_uso = "
SELECT
(SELECT COUNT(*) FROM reservas WHERE id_sesion = ?) AS total_reservas,
(SELECT COUNT(*) FROM lista_espera WHERE id_sesion = ?) AS total_espera
";
$stmt_uso = $conexion->prepare($sql_uso);
$stmt_uso->bind_param("ii", $id_sesion, $id_sesion);
$stmt_uso->execute();
$fila_uso = $stmt_uso->get_result()->fetch_assoc();
if (
(int) $fila_uso["total_reservas"] > 0 ||
(int) $fila_uso["total_espera"] > 0
) {
header("Location: sesiones.php?error=en_uso");
exit;
}

$sql = "DELETE FROM sesiones WHERE id_sesion = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_sesion);
$stmt->execute();
header("Location: sesiones.php?mensaje=sesion_eliminada");
exit;
