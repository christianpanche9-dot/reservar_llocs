<?php
require_once "seguridad_admin.php";
require_once __DIR__ . '/../conexion.php';

$id_monitor = filter_input(
INPUT_GET,
'id_monitor',
FILTER_VALIDATE_INT
);
if (!$id_monitor) {
header('Location: monitores.php');
exit;
}

$sql = "
SELECT
id_monitor,
nombre,
apellidos,
email,
telefono,
especialidad,
activo
FROM monitores
WHERE id_monitor = ?
";
$stmt = $conexion->prepare($sql);
$stmt->bind_param('i', $id_monitor);
$stmt->execute();
$monitor = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$monitor) {
header('Location: monitores.php?error=no_encontrado');
exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta
name="viewport"
content="width=device-width, initial-scale=1.0"
>
<title>Editar monitor | Reservar Llocs</title>
<link rel="stylesheet" href="../estilos.css">
</head>
<body>
<?php require_once __DIR__ . '/menu_admin.php'; ?>
<main class="contenedor seccion">
<a class="enlace-volver" href="monitores.php">
← Volver a monitores
</a>
<h1>Editar monitor</h1>
<?php if (
    ($_GET['error'] ?? '') === 'datos'
): ?>

<div class="mensaje mensaje-error">
Revisa los datos del formulario.
</div>
<?php endif; ?>
<?php if (
    ($_GET['error'] ?? '') === 'email'
): ?>

<div class="mensaje mensaje-error">
Ya existe otro monitor con ese correo.
</div>
<?php endif; ?>
<form
class="formulario-admin"
action="actualizar_monitor.php"
method="post"
>
<input
type="hidden"
name="id_monitor"
value="<?= (int) $monitor['id_monitor'] ?>"
>
<div class="campo">
<label for="nombre">Nombre</label>
<input
type="text"
id="nombre"
name="nombre"
maxlength="80"
value="<?= escapar($monitor['nombre']) ?>"
required
>
</div>
<div class="campo">
<label for="apellidos">Apellidos</label>
<input
type="text"
id="apellidos"
name="apellidos"
maxlength="120"
value="<?= escapar($monitor['apellidos']) ?>"
required
>
</div>
<div class="campo">
<label for="email">Correo electrónico</label>
<input
type="email"
id="email"
name="email"
maxlength="180"
value="<?= escapar($monitor['email']) ?>"
required
>
</div>
<div class="campo">
<label for="telefono">Teléfono</label>
<input
type="tel"
id="telefono"
name="telefono"
maxlength="25"
value="<?= escapar($monitor['telefono']) ?>"
>
</div>
<div class="campo campo-completo">
<label for="especialidad">
Especialidad
</label>
<input
type="text"
id="especialidad"
name="especialidad"
maxlength="180"
value="<?= escapar($monitor['especialidad']) ?>"
required
>
</div>
<div class="campo-checkbox campo-completo">
<label>
<input
type="checkbox"
name="activo"
value="1"
<?= (int) $monitor['activo'] === 1 ? 'checked' : '' ?>
>
Monitor activo
</label>
</div>
<div class="campo-completo">
<button class="boton" type="submit">
Guardar cambios
</button>
</div>
</form>
</main>
</body>
</html>
