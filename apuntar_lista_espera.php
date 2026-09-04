<?php
require_once "seguridad.php";
require_once "conexion.php";
require_once "funciones.php";

$id_usuario = idUsuarioActual();

$id_sesion = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$id_sesion) {
    die("Sesión no válida.");
}

/* Comprobar que no esté ya apuntado */
$sql = "
SELECT id_espera
FROM lista_espera
WHERE id_usuario = ?
AND id_sesion = ?
AND estado = 'esperando'
";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("ii", $id_usuario, $id_sesion);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    header("Location: lista_espera.php");
    exit;
}

$stmt->close();

/* Insertar */
$sql = "
INSERT INTO lista_espera
(
    id_usuario,
    id_sesion,
    fecha_solicitud,
    estado
)
VALUES
(
    ?, ?, NOW(), 'esperando'
)
";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("ii", $id_usuario, $id_sesion);
$stmt->execute();

header("Location: lista_espera.php");
exit;