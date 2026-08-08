<?php
require_once "seguridad_admin.php";
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../funciones.php';

$id_sesion = filter_input(
INPUT_GET,
'id_sesion',
FILTER_VALIDATE_INT
);
if (!$id_sesion) {
header('Location: sesiones.php');
exit;
}

$sql_sesion = "
SELECT
id_sesion,
id_actividad,
id_espacio,
id_monitor,
fecha,
hora_inicio,
hora_fin,
aforo,
observaciones
FROM sesiones
WHERE id_sesion = ?
";
$stmt_sesion = $conexion->prepare($sql_sesion);
$stmt_sesion->bind_param('i', $id_sesion);
$stmt_sesion->execute();
$sesion = $stmt_sesion->get_result()->fetch_assoc();
$stmt_sesion->close();

if (!$sesion) {
header('Location: sesiones.php?error=no_encontrada');
exit;
}

$duracion_actual = (int) round(
(strtotime($sesion['hora_fin']) - strtotime($sesion['hora_inicio'])) / 60
);

$sql_actividades = "
SELECT
id_actividad,
nombre,
duracion_minutos
FROM actividades
WHERE activa = 1
OR id_actividad = ?
ORDER BY nombre
";
$stmt_actividades = $conexion->prepare($sql_actividades);
$stmt_actividades->bind_param('i', $sesion['id_actividad']);
$stmt_actividades->execute();
$actividades = $stmt_actividades->get_result();

$sql_espacios = "
SELECT
id_espacio,
nombre,
ubicacion,
aforo_maximo
FROM espacios
WHERE activo = 1
OR id_espacio = ?
ORDER BY nombre
";
$stmt_espacios = $conexion->prepare($sql_espacios);
$stmt_espacios->bind_param('i', $sesion['id_espacio']);
$stmt_espacios->execute();
$espacios = $stmt_espacios->get_result();

$sql_monitores = "
SELECT
id_monitor,
nombre,
apellidos,
especialidad
FROM monitores
WHERE activo = 1
OR id_monitor = ?
ORDER BY apellidos, nombre
";
$stmt_monitores = $conexion->prepare($sql_monitores);
$stmt_monitores->bind_param('i', $sesion['id_monitor']);
$stmt_monitores->execute();
$monitores = $stmt_monitores->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta
name="viewport"
content="width=device-width, initial-scale=1.0"
>
<title>
Editar sesión | Reservar Llocs
</title>
<link rel="stylesheet" href="../estilos.css">
</head>
<body>
<?php require_once __DIR__ . '/menu_admin.php'; ?>
<main class="contenedor seccion">
<a class="enlace-volver" href="sesiones.php">
← Volver a sesiones
</a>
<p class="etiqueta">
Horarios
</p>
<h1>Editar sesión</h1>
<?php if (isset($_GET['error'])): ?>
<div class="mensaje mensaje-error">
No se ha podido guardar la sesión.
Revisa la fecha, el horario y el aforo.
</div>
<?php endif; ?>
<form
class="formulario-admin"
action="actualizar_sesion.php"
method="post"
>
<input
type="hidden"
name="id_sesion"
value="<?= (int) $sesion['id_sesion'] ?>"
>
<div class="campo">
<label for="id_actividad">
Actividad
</label>
<select
id="id_actividad"
name="id_actividad"
required
>
<?php while (
$actividad =
$actividades->fetch_assoc()
): ?>
<option
value="<?= (int)
$actividad['id_actividad'] ?>"
data-duracion="<?= (int)
$actividad['duracion_minutos'] ?>"
<?= (int) $actividad['id_actividad'] === (int) $sesion['id_actividad']
? 'selected'
: '' ?>
>
<?= escapar(
$actividad['nombre']
) ?>
—
<?= (int)
$actividad['duracion_minutos'] ?>
min
</option>
<?php endwhile; ?>
</select>
</div>
<div class="campo">
<label for="id_espacio">
Espacio
</label>
<select
id="id_espacio"
name="id_espacio"
required
>
<?php while (
$espacio =
$espacios->fetch_assoc()
): ?>
<option
value="<?= (int)
$espacio['id_espacio'] ?>"
data-aforo="<?= (int)
$espacio['aforo_maximo'] ?>"
<?= (int) $espacio['id_espacio'] === (int) $sesion['id_espacio']
? 'selected'
: '' ?>
>
<?= escapar(
$espacio['nombre']
) ?>
—
máximo
<?= (int)
$espacio['aforo_maximo'] ?>
</option>
<?php endwhile; ?>
</select>
</div>
<div class="campo">
<label for="id_monitor">
Monitor
</label>
<select
id="id_monitor"
name="id_monitor"
required
>
<?php while (
$monitor =
$monitores->fetch_assoc()
): ?>
<option
value="<?= (int)
$monitor['id_monitor'] ?>"
<?= (int) $monitor['id_monitor'] === (int) $sesion['id_monitor']
? 'selected'
: '' ?>
>
<?= escapar(
$monitor['nombre'] . ' ' .
$monitor['apellidos']
) ?>
<?php if (
!empty(
$monitor['especialidad']
)
): ?>
—
<?= escapar(
$monitor['especialidad']
) ?>
<?php endif; ?>
</option>
<?php endwhile; ?>
</select>
</div>
<div class="campo">
<label for="fecha">
Fecha
</label>
<input
type="date"
id="fecha"
name="fecha"
value="<?= escapar($sesion['fecha']) ?>"
required
>
</div>
<div class="campo">
<label for="hora_inicio">
Hora de inicio
</label>
<input
type="time"
id="hora_inicio"
name="hora_inicio"
value="<?= escapar(substr($sesion['hora_inicio'], 0, 5)) ?>"
required
>
<p class="ayuda">
El sistema calculará automáticamente la hora final y comprobará que el espacio y el monitor estén disponibles.
</p>
<p>
Hora final prevista:
<strong id="hora-final">--:--</strong>
</p>

</div>
<div class="campo">
<label for="duracion">
Duración en minutos
</label>
<input
type="number"
id="duracion"
name="duracion"
min="15"
max="480"
step="15"
value="<?= $duracion_actual ?>"
required
>
</div>
<div class="campo">
<label for="aforo">
Aforo de la sesión
</label>
<input
type="number"
id="aforo"
name="aforo"
min="1"
value="<?= (int) $sesion['aforo'] ?>"
required
>
<small id="ayuda-aforo">
Selecciona un espacio para conocer
su aforo máximo.
</small>
</div>
<div class="campo campo-completo">
<label for="observaciones">
Observaciones
</label>
<textarea
id="observaciones"
name="observaciones"
rows="5"
placeholder="Material necesario, indicaciones de acceso..."
><?= escapar($sesion['observaciones']) ?></textarea>
</div>
<div class="campo-completo">
<button class="boton" type="submit">
Guardar cambios
</button>
</div>
</form>
<script>
const campoHora = document.querySelector("#hora_inicio");
const campoDuracion = document.querySelector("#duracion");
const salida = document.querySelector("#hora-final");

function calcularHoraFinal() {
    if (!campoHora.value || !campoDuracion.value) {
        salida.textContent = "--:--";
        return;
    }

    const [horas, minutos] =
        campoHora.value.split(":").map(Number);

    const fecha = new Date();
    fecha.setHours(horas, minutos, 0, 0);

    fecha.setMinutes(
        fecha.getMinutes() + Number(campoDuracion.value)
    );

    salida.textContent = fecha.toLocaleTimeString("es-ES", {
        hour: "2-digit",
        minute: "2-digit"
    });
}

campoHora.addEventListener("input", calcularHoraFinal);
campoDuracion.addEventListener("input", calcularHoraFinal);
calcularHoraFinal();
</script>
</main>
</body>
</html>
