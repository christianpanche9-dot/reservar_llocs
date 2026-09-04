<?php
require_once "seguridad.php";
require_once "conexion.php";
require_once "funciones.php";
require_once "funciones_reservas.php";
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
header("Location: mis_reservas.php");
exit;
}
$id_reserva = filter_input(
INPUT_POST,
"id_reserva",
FILTER_VALIDATE_INT
);
$id_usuario = idUsuarioActual();
$metodo_pago = $_POST["metodo_pago"] ?? "";
if (
!$id_reserva ||
!$id_usuario ||
!in_array($metodo_pago, ["pago", "bono"], true)
) {
header("Location: mis_reservas.php?error=datos");
exit;
}
try {
$conexion->begin_transaction();
$sql_reserva = "
SELECT
r.id_reserva,
r.estado,
a.precio
FROM reservas r
INNER JOIN sesiones s
ON r.id_sesion = s.id_sesion
INNER JOIN actividades a
ON s.id_actividad = a.id_actividad
WHERE r.id_reserva = ?
AND r.id_usuario = ?
FOR UPDATE
";
$stmt_reserva = $conexion->prepare($sql_reserva);
$stmt_reserva->bind_param("ii", $id_reserva, $id_usuario);
$stmt_reserva->execute();
$reserva = $stmt_reserva->get_result()->fetch_assoc();
$stmt_reserva->close();
if (!$reserva) {
throw new Exception("La reserva no existe.");
}
if ($reserva["estado"] !== "pendiente_pago") {
throw new Exception(
"Esta reserva no está pendiente de pago."
);
}
[$importe, $id_bono_usado] = resolverMetodoPago(
$conexion,
$id_usuario,
$metodo_pago,
(float) $reserva["precio"]
);
$sql_confirmar = "
UPDATE reservas
SET
estado = 'confirmada',
metodo_pago = ?,
importe = ?,
id_bono_cliente = ?
WHERE id_reserva = ?
";
$stmt_confirmar = $conexion->prepare($sql_confirmar);
$stmt_confirmar->bind_param(
"sdii",
$metodo_pago,
$importe,
$id_bono_usado,
$id_reserva
);
$stmt_confirmar->execute();
$stmt_confirmar->close();
$concepto = $metodo_pago === "bono"
? "Reserva pagada con bono"
: "Reserva de sesión";
$sql_pago = "
INSERT INTO pagos (
id_usuario,
id_reserva,
id_bono_cliente,
concepto,
importe,
estado
)
VALUES (?, ?, ?, ?, ?, 'pagado')
";
$stmt_pago = $conexion->prepare($sql_pago);
$stmt_pago->bind_param(
"iiisd",
$id_usuario,
$id_reserva,
$id_bono_usado,
$concepto,
$importe
);
$stmt_pago->execute();
$stmt_pago->close();
$conexion->commit();
$conexion->close();
header(
"Location: mis_reservas.php?mensaje=confirmada"
);
exit;
} catch (Throwable $error) {
$conexion->rollback();
$conexion->close();
header(
"Location: mis_reservas.php?error=" .
urlencode($error->getMessage())
);
exit;
}
