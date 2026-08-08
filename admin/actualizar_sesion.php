<?php
require_once "seguridad_admin.php";
require_once "../conexion.php";

$id_sesion = filter_input(
INPUT_POST,
"id_sesion",
FILTER_VALIDATE_INT
);
if (!$id_sesion) {
header("Location: sesiones.php?error=no_encontrada");
exit;
}

$sql_original = "
SELECT fecha, hora_inicio, hora_fin
FROM sesiones
WHERE id_sesion = ?
";
$stmt_original = $conexion->prepare($sql_original);
$stmt_original->bind_param("i", $id_sesion);
$stmt_original->execute();
$sesion_original = $stmt_original->get_result()->fetch_assoc();
$stmt_original->close();

if (!$sesion_original) {
header("Location: sesiones.php?error=no_encontrada");
exit;
}

$errores = [];
$id_actividad = filter_input(
INPUT_POST,
"id_actividad",
FILTER_VALIDATE_INT
);
$id_espacio = filter_input(
INPUT_POST,
"id_espacio",
FILTER_VALIDATE_INT
);
$id_monitor = filter_input(
INPUT_POST,
"id_monitor",
FILTER_VALIDATE_INT
);
$fecha = trim($_POST["fecha"] ?? "");
$hora_inicio = trim($_POST["hora_inicio"] ?? "");
$duracion = filter_input(
INPUT_POST,
"duracion",
FILTER_VALIDATE_INT
);
$aforo = filter_input(
INPUT_POST,
"aforo",
FILTER_VALIDATE_INT
);
$observaciones = trim($_POST["observaciones"] ?? "");
/*
|--------------------------------------------------------------------------
| 1. Validaciones básicas
|--------------------------------------------------------------------------
*/


if (!$id_actividad) {
$errores[] = "Debes seleccionar una actividad válida.";
}
if (!$id_espacio) {
$errores[] = "Debes seleccionar un espacio válido.";
}
if (!$id_monitor) {
$errores[] = "Debes seleccionar un monitor válido.";
}
if ($fecha === "") {
    $errores[] = "Debes indicar una fecha.";
}
if ($hora_inicio === "") {
$errores[] = "Debes indicar una hora de inicio.";
}
if ($duracion === false || $duracion === null) {
$errores[] = "La duración no es válida.";
} elseif ($duracion < 15 || $duracion > 480) {
$errores[] =
"La duración debe estar entre 15 y 480 minutos.";
}
if ($aforo === false || $aforo === null || $aforo <= 0) {
$errores[] = "El aforo debe ser superior a cero.";
}
/*
|--------------------------------------------------------------------------
| 2. Validar y calcular el horario
|--------------------------------------------------------------------------
*/

$inicio = false;
$fin = false;
$hora_fin = null;
if (
$fecha !== "" &&
$hora_inicio !== "" &&
$duracion !== false &&
$duracion !== null &&
$duracion >= 15 &&
$duracion <= 480
) {
$inicio = DateTime::createFromFormat(
"Y-m-d H:i",
$fecha . " " . $hora_inicio
);
$errores_fecha = DateTime::getLastErrors();
if (
!$inicio ||
(
$errores_fecha !== false &&
(
$errores_fecha["warning_count"] > 0 ||
$errores_fecha["error_count"] > 0
)
)
) {
$errores[] = "La fecha o la hora no son válidas.";
} else {
$fin = clone $inicio;
$fin->modify("+{$duracion} minutes");

// Margen de 15 minutos para comprobaciones
$inicio_comprobacion = clone $inicio;
$inicio_comprobacion->modify("-15 minutes");

$fin_comprobacion = clone $fin;
$fin_comprobacion->modify("+15 minutes");
if (
$inicio->format("Y-m-d") !==
$fin->format("Y-m-d")
) {
$errores[] =
"La sesión debe comenzar y terminar el mismo día.";
}
$hora_inicio = $inicio->format("H:i:s");
$hora_fin = $fin->format("H:i:s");
$fecha = $inicio->format("Y-m-d");

$cambia_horario = (
$fecha !== $sesion_original["fecha"] ||
$hora_inicio !== $sesion_original["hora_inicio"] ||
$hora_fin !== $sesion_original["hora_fin"]
);
if ($cambia_horario) {
$ahora = new DateTime();
if ($inicio <= $ahora) {
$errores[] =
"La fecha y la hora de inicio deben estar en el futuro.";
}
}
}
}
/*
|--------------------------------------------------------------------------
| 3. Comprobar la actividad
|--------------------------------------------------------------------------

*/

if ($id_actividad) {
$sql_actividad = "
SELECT id_actividad, nombre
FROM actividades
WHERE id_actividad = ?
";
$stmt_actividad =
$conexion->prepare($sql_actividad);
$stmt_actividad->bind_param(
"i",
$id_actividad
);
$stmt_actividad->execute();
$resultado_actividad =
$stmt_actividad->get_result();
$actividad =
$resultado_actividad->fetch_assoc();
if (!$actividad) {
$errores[] =
"La actividad seleccionada no existe.";
}
$stmt_actividad->close();
}
/*
|--------------------------------------------------------------------------
| 4. Comprobar el espacio y su capacidad
|--------------------------------------------------------------------------

*/

$espacio = null;
if ($id_espacio) {
$sql_espacio = "
SELECT id_espacio, nombre, aforo_maximo
FROM espacios
WHERE id_espacio = ?
";
$stmt_espacio =
$conexion->prepare($sql_espacio);
$stmt_espacio->bind_param(
"i",
$id_espacio
);
$stmt_espacio->execute();
$resultado_espacio =
$stmt_espacio->get_result();
$espacio =
$resultado_espacio->fetch_assoc();
if (!$espacio) {
$errores[] =
"El espacio seleccionado no existe.";
} elseif (
$aforo !== false &&
$aforo !== null &&
$aforo > $espacio["aforo_maximo"]
) {
$errores[] =
"El aforo solicitado supera la capacidad de " .
$espacio["nombre"] .
", que admite un máximo de " .
$espacio["aforo_maximo"] .
" personas.";
}
$stmt_espacio->close();
}
/*
|--------------------------------------------------------------------------
| 5. Comprobar el monitor
|--------------------------------------------------------------------------
*/

if ($id_monitor) {
    $sql_monitor = "
SELECT id_monitor, nombre, apellidos
FROM monitores
WHERE id_monitor = ?
";
$stmt_monitor =
$conexion->prepare($sql_monitor);
$stmt_monitor->bind_param(
"i",
$id_monitor
);
$stmt_monitor->execute();
$resultado_monitor =
$stmt_monitor->get_result();
$monitor =
$resultado_monitor->fetch_assoc();
if (!$monitor) {
$errores[] =
"El monitor seleccionado no existe.";
}
$stmt_monitor->close();
}
/*
|--------------------------------------------------------------------------
| 6. Comprobar solapamientos, excluyendo esta misma sesión
|--------------------------------------------------------------------------
*/
if (empty($errores)) {
/*
|--------------------------------------------------------------------------
| 6.1 Conflicto del espacio
|--------------------------------------------------------------------------
*/

$sql_conflicto_espacio = "
SELECT
s.id_sesion,
s.hora_inicio,
s.hora_fin,
a.nombre AS actividad
FROM sesiones s
INNER JOIN actividades a
ON s.id_actividad = a.id_actividad
WHERE s.fecha = ?
AND s.id_espacio = ?
AND s.id_sesion <> ?
AND s.estado <> 'cancelada'
AND s.hora_inicio < ?
AND s.hora_fin > ?
LIMIT 1
";
$stmt_conflicto_espacio =
$conexion->prepare($sql_conflicto_espacio);
$stmt_conflicto_espacio->bind_param(
"siiss",
$fecha,
$id_espacio,
$id_sesion,
$fin_comprobacion->format("H:i:s"),
$inicio_comprobacion->format("H:i:s")
);
$stmt_conflicto_espacio->execute();
$resultado_conflicto_espacio =
$stmt_conflicto_espacio->get_result();
if ($resultado_conflicto_espacio->num_rows > 0) {
$conflicto =
$resultado_conflicto_espacio->fetch_assoc();
$errores[] =
"El espacio ya está ocupado por la actividad \"" .
htmlspecialchars($conflicto["actividad"]) .
"\", de " .
substr($conflicto["hora_inicio"], 0, 5) .
" a " .
substr($conflicto["hora_fin"], 0, 5) .
".";
}
$stmt_conflicto_espacio->close();
/*
|--------------------------------------------------------------------------
| 6.2 Conflicto del monitor
|--------------------------------------------------------------------------
*/

$sql_conflicto_monitor = "
SELECT
s.id_sesion,
s.hora_inicio,
s.hora_fin,
a.nombre AS actividad,
e.nombre AS espacio
FROM sesiones s
INNER JOIN actividades a
ON s.id_actividad = a.id_actividad
INNER JOIN espacios e
ON s.id_espacio = e.id_espacio
WHERE s.fecha = ?
AND s.id_monitor = ?
AND s.id_sesion <> ?
AND s.estado <> 'cancelada'
AND s.hora_inicio < ?
AND s.hora_fin > ?
LIMIT 1
";
$stmt_conflicto_monitor =
$conexion->prepare($sql_conflicto_monitor);
$stmt_conflicto_monitor->bind_param(
"siiss",
$fecha,
$id_monitor,
$id_sesion,
$fin_comprobacion->format("H:i:s"),
$inicio_comprobacion->format("H:i:s")
);
$stmt_conflicto_monitor->execute();
$resultado_conflicto_monitor =
$stmt_conflicto_monitor->get_result();
if ($resultado_conflicto_monitor->num_rows > 0) {
$conflicto =
$resultado_conflicto_monitor->fetch_assoc();
$errores[] =
"El monitor ya tiene asignada la actividad \"" .
htmlspecialchars($conflicto["actividad"]) .
"\" en " .
htmlspecialchars($conflicto["espacio"]) .
", de " .
substr($conflicto["hora_inicio"], 0, 5) .
" a " .
substr($conflicto["hora_fin"], 0, 5) .
".";
}
$stmt_conflicto_monitor->close();
}
/*
|--------------------------------------------------------------------------
| 7. Mostrar los errores
|--------------------------------------------------------------------------
*/

if (!empty($errores)) {
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta
name="viewport"
content="width=device-width, initial-scale=1.0"
>
<title>No se ha podido actualizar la sesión</title>
<link rel="stylesheet" href="../estilos.css">
</head>
<body>
    <main class="contenedor">
<h1>No se ha podido actualizar la sesión</h1>
<div class="mensaje mensaje-error">
<ul>
<?php foreach ($errores as $error): ?>
<li><?= $error ?></li>
<?php endforeach; ?>
</ul>
</div>
<a
class="boton"
href="editar_sesion.php?id_sesion=<?= (int) $id_sesion ?>"
>
Volver al formulario
</a>
</main>
</body>
</html>
<?php
exit;
}
/*
|--------------------------------------------------------------------------
| 8. Actualizar la sesión
|--------------------------------------------------------------------------
*/
$sql_actualizar = "
UPDATE sesiones SET
id_actividad = ?,
id_espacio = ?,
id_monitor = ?,
fecha = ?,
hora_inicio = ?,
hora_fin = ?,
aforo = ?,
observaciones = ?
WHERE id_sesion = ?
";
$stmt_actualizar =
$conexion->prepare($sql_actualizar);
$stmt_actualizar->bind_param(
"iiisssisi",
$id_actividad,
$id_espacio,
$id_monitor,
$fecha,
$hora_inicio,
$hora_fin,
$aforo,
$observaciones,
$id_sesion
);
if ($stmt_actualizar->execute()) {
$stmt_actualizar->close();
$conexion->close();
header(
    "Location: sesiones.php?mensaje=sesion_actualizada"
);
exit;
}
$stmt_actualizar->close();
$conexion->close();
echo "No se ha podido actualizar la sesión.";
