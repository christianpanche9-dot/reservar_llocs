<?php
require_once "seguridad_admin.php";
require_once __DIR__ . '/../conexion.php';

$id_espacio = filter_input(
INPUT_GET,
'id_espacio',
FILTER_VALIDATE_INT
);
if (!$id_espacio) {
header('Location: espacios.php');
exit;
}

$sql = "
SELECT
id_espacio,
nombre,
ubicacion,
descripcion,
aforo_maximo,
activo
FROM espacios
WHERE id_espacio = ?
";
$stmt = $conexion->prepare($sql);
$stmt->bind_param('i', $id_espacio);
$stmt->execute();
$espacio = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$espacio) {
header('Location: espacios.php?error=no_encontrado');
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
<title>
Editar espacio | Reservar Llocs
</title>
<link rel="stylesheet" href="../estilos.css">
</head>
<body>
<?php require_once __DIR__ . '/menu_admin.php'; ?>
<main class="contenedor seccion">
<a class="enlace-volver" href="espacios.php">
← Volver a espacios
</a>
<h1>Editar espacio</h1>
<?php if (isset($_GET['error'])): ?>
<div class="mensaje mensaje-error">
<?php if ($_GET['error'] === 'nombre'): ?>
Ya existe otro espacio con ese nombre.
<?php else: ?>
Revisa los datos del espacio.
<?php endif; ?>
</div>
<?php endif; ?>
<form
class="formulario-admin"
action="actualizar_espacio.php"
method="post"
>
<input
type="hidden"
name="id_espacio"
value="<?= (int) $espacio['id_espacio'] ?>"
>
<div class="campo">
<label for="nombre">
Nombre
</label>
<input
type="text"
id="nombre"
name="nombre"
maxlength="120"
value="<?= escapar($espacio['nombre']) ?>"
required
>
</div>
<div class="campo">
<label for="ubicacion">
Ubicación
</label>
<input
type="text"
id="ubicacion"
name="ubicacion"
maxlength="180"
placeholder="Primera planta"
value="<?= escapar($espacio['ubicacion']) ?>"
required
>
</div>
<div class="campo">
<label for="aforo_maximo">
Aforo máximo
</label>
<input
type="number"
id="aforo_maximo"
name="aforo_maximo"
min="1"
max="5000"
value="<?= (int) $espacio['aforo_maximo'] ?>"
required
>
</div>
<div class="campo campo-completo">
<label for="descripcion">
Descripción
</label>
<textarea
id="descripcion"
name="descripcion"
rows="5"
><?= escapar($espacio['descripcion']) ?></textarea>
</div>
<div class="campo-checkbox campo-completo">
<label>
    <input
type="checkbox"
name="activo"
value="1"
<?= (int) $espacio['activo'] === 1 ? 'checked' : '' ?>
>
Espacio disponible
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
