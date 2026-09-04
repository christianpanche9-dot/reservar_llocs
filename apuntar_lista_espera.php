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

/* Comprobar si ya existe una fila (en cualquier estado) para este usuario y sesión */
$sql = "
SELECT id_espera, estado
FROM lista_espera
WHERE id_usuario = ?
AND id_sesion = ?
";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("ii", $id_usuario, $id_sesion);
$stmt->execute();
$existente = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($existente && $existente["estado"] === "esperando") {
    header("Location: lista_espera.php");
    exit;
}

if ($existente) {
    /* Reactivar la fila existente (venía cancelada o promocionada) */
    $sql = "
    UPDATE lista_espera
    SET
        estado = 'esperando',
        fecha_solicitud = NOW()
    WHERE id_espera = ?
    ";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $existente["id_espera"]);
    $stmt->execute();
    $stmt->close();
} else {
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
    $stmt->close();
}

header("Location: lista_espera.php");
exit;