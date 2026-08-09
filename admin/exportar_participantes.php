<?php
require_once "seguridad_admin.php";
require_once "../conexion.php";
$id_sesion = filter_input(
INPUT_GET,
"id",
FILTER_VALIDATE_INT
);
if (!$id_sesion) {
exit("Sesión no válida.");
}
$sql = "
SELECT
u.nombre,
u.apellidos,
u.email,
u.telefono,
r.codigo_reserva,
r.asistencia,
s.fecha,
s.hora_inicio,
a.nombre AS actividad
FROM reservas r
INNER JOIN usuarios u
ON r.id_usuario = u.id_usuario
INNER JOIN sesiones s
ON r.id_sesion = s.id_sesion
INNER JOIN actividades a
ON s.id_actividad = a.id_actividad
WHERE r.id_sesion = ?
AND r.estado = 'confirmada'
ORDER BY
u.apellidos ASC,
u.nombre ASC
";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_sesion);
$stmt->execute();
$resultado = $stmt->get_result();
header("Content-Type: text/csv; charset=UTF-8");
header(
'Content-Disposition: attachment; ' .
'filename="participantes-sesion-' .
$id_sesion .
'.csv"'
);
echo "\xEF\xBB\xBF";
$salida = fopen("php://output", "w");
fputcsv(
$salida,
[
"Nombre",
"Apellidos",
"Correo",
"Teléfono",
"Código",
"Asistencia",
"Fecha",
"Hora",
"Actividad"
],
";"
);
while ($fila = $resultado->fetch_assoc()) {
fputcsv(
$salida,
[
$fila["nombre"],
$fila["apellidos"],
$fila["email"],
$fila["telefono"],
$fila["codigo_reserva"],
$fila["asistencia"],
$fila["fecha"],
substr(
$fila["hora_inicio"],
0,
5
),
$fila["actividad"]
],
";"
);
}
fclose($salida);
$stmt->close();
$conexion->close();
exit;