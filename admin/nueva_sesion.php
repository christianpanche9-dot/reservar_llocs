<?php
require_once "seguridad_admin.php";
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../funciones.php';
$sql_actividades = "
SELECT
id_actividad,
nombre,
duracion_minutos
FROM actividades
WHERE activa = 1
ORDER BY nombre
";
$sql_espacios = "
SELECT
id_espacio,
nombre,
ubicacion,
aforo_maximo
FROM espacios
WHERE activo = 1
ORDER BY nombre
";
$sql_monitores = "
SELECT
id_monitor,
nombre,
apellidos,
especialidad
FROM monitores
WHERE activo = 1
ORDER BY apellidos, nombre
";
$actividades =
$conexion->query($sql_actividades);
$espacios =
$conexion->query($sql_espacios);
$monitores =
$conexion->query($sql_monitores);
$puede_crearse =
$actividades->num_rows > 0
&& $espacios->num_rows > 0
&& $monitores->num_rows > 0;
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
</title>
Programar sesión | Reservar Llocs
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
<h1>Programar una sesión</h1>
<?php if (!$puede_crearse): ?>
<div class="mensaje mensaje-aviso">
Para crear una sesión debe existir, como
mínimo, una actividad activa, un espacio
disponible y un monitor activo.
</div>
<?php else: ?>
<?php if (isset($_GET['error'])): ?>
<div class="mensaje mensaje-error">
No se ha podido programar la sesión.
Revisa la fecha, el horario y el aforo.
</div>
<?php endif; ?>
<form
class="formulario-admin"
action="guardar_sesion.php"
method="post"
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
<option value="">
Selecciona una actividad
</option>
<?php while (
$actividad =
$actividades->fetch_assoc()
): ?>
<option
value="<?= (int)
$actividad['id_actividad'] ?>"
data-duracion="<?= (int)
$actividad['duracion_minutos'] ?>"
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
<option value="">
Selecciona un espacio
</option>
<?php while (
$espacio =
$espacios->fetch_assoc()
): ?>
<option
value="<?= (int)
$espacio['id_espacio'] ?>"
data-aforo="<?= (int)
$espacio['aforo_maximo'] ?>"
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
<option value="">
Selecciona un monitor
</option>
<?php while (
$monitor =
$monitores->fetch_assoc()
): ?>
<option
value="<?= (int)
$monitor['id_monitor'] ?>"
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
min="<?= date('Y-m-d') ?>"
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
value="60"
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
></textarea>
</div>
<div class="campo-completo">
<button class="boton" type="submit">
Programar sesión
</button>
</div>
</form>
<?php endif; ?>
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
</script>
</main>