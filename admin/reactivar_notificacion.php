<?php
session_start();
require_once "seguridad_admin.php";
require_once "../conexion.php";
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
header("Location: notificaciones.php");
exit;
}
$token_recibido = $_POST["csrf"] ?? "";
$token_guardado = $_SESSION["csrf_notificacion"] ?? "";
$id_notificacion = filter_input(
INPUT_POST,
"id_notificacion",
FILTER_VALIDATE_INT
);
if (
!$token_guardado ||
!hash_equals($token_guardado, $token_recibido) ||
!$id_notificacion
) {
header("Location: notificaciones.php?error=datos");
exit;
}
$sql = "
UPDATE notificaciones
SET
estado = 'pendiente',
intentos = 0,
procesar_despues = NOW(),
procesando_en = NULL,
ultimo_error = NULL
WHERE id_notificacion = ?
AND estado = 'fallida'
";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_notificacion);
$stmt->execute();
$reactivada = $stmt->affected_rows === 1;
$stmt->close();
$conexion->close();
header(
"Location: notificaciones.php?" .
($reactivada ? "mensaje=reactivada" : "error=datos")
);
exit;
