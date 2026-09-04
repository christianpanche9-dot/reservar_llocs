<?php
require_once "seguridad.php";
require_once "conexion.php";
require_once "funciones.php";
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
header("Location: comprar_bono.php");
exit;
}
$id_tipo_bono = filter_input(
INPUT_POST,
"id_tipo_bono",
FILTER_VALIDATE_INT
);
$id_usuario = idUsuarioActual();
if (!$id_tipo_bono || !$id_usuario) {
header("Location: comprar_bono.php?error=datos");
exit;
}
try {
$conexion->begin_transaction();
$sql_tipo = "
SELECT id_tipo_bono, nombre, numero_usos, precio, dias_validez
FROM tipos_bono
WHERE id_tipo_bono = ?
AND activo = 1
FOR UPDATE
";
$stmt_tipo = $conexion->prepare($sql_tipo);
$stmt_tipo->bind_param("i", $id_tipo_bono);
$stmt_tipo->execute();
$tipo = $stmt_tipo->get_result()->fetch_assoc();
$stmt_tipo->close();
if (!$tipo) {
throw new Exception(
"El bono seleccionado no está disponible."
);
}
$usos = (int) $tipo["numero_usos"];
$precio = (float) $tipo["precio"];
$sql_bono = "
INSERT INTO bonos_clientes (
id_usuario,
id_tipo_bono,
fecha_compra,
fecha_caducidad,
usos_iniciales,
usos_disponibles,
estado
)
VALUES (
?,
?,
NOW(),
? ,
?,
?,
'activo'
)
";
$fecha_caducidad = $tipo["dias_validez"]
? date(
"Y-m-d",
strtotime(
"+" . (int) $tipo["dias_validez"] . " days"
)
)
: null;
$stmt_bono = $conexion->prepare($sql_bono);
$stmt_bono->bind_param(
"iisii",
$id_usuario,
$id_tipo_bono,
$fecha_caducidad,
$usos,
$usos
);
$stmt_bono->execute();
$id_bono_cliente = $conexion->insert_id;
$stmt_bono->close();
$sql_pago = "
INSERT INTO pagos (
id_usuario,
id_bono_cliente,
concepto,
importe,
estado
)
VALUES (?, ?, ?, ?, 'pagado')
";
$stmt_pago = $conexion->prepare($sql_pago);
$stmt_pago->bind_param(
"iisd",
$id_usuario,
$id_bono_cliente,
$tipo["nombre"],
$precio
);
$stmt_pago->execute();
$stmt_pago->close();
$conexion->commit();
$conexion->close();
header("Location: mis_bonos.php?mensaje=comprado");
exit;
} catch (Throwable $error) {
$conexion->rollback();
$conexion->close();
header(
"Location: comprar_bono.php?error=datos"
);
exit;
}
