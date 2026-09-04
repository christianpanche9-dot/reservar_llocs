<?php
require_once "seguridad_admin.php";
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../funciones.php';
$sql = "
SELECT
id_espacio,
nombre,
ubicacion,
aforo_maximo,
activo
FROM espacios
ORDER BY nombre
";
$resultado = $conexion->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta
name="viewport"
content="width=device-width, initial-scale=1.0"
>
<title>Espacios | Administración</title>
<link rel="stylesheet" href="../estilos.css">
</head>
<body>
<?php require_once __DIR__ . '/menu_admin.php'; ?>
<main class="contenedor seccion">
<div class="encabezado-con-accion">
<h1>Espacios</h1>
<a class="boton" href="nuevo_espacio.php">
Nuevo espacio
</a>
</div>
<?php if (
($_GET['mensaje'] ?? '') === 'creado'
): ?>
<div class="mensaje mensaje-exito">
El espacio se ha creado correctamente.
</div>
<?php endif; ?>
<?php if (
($_GET['mensaje'] ?? '') === 'actualizado'
): ?>
<div class="mensaje mensaje-exito">
El espacio se ha actualizado correctamente.
</div>
<?php endif; ?>
<?php if (
($_GET['mensaje'] ?? '') === 'eliminado'
): ?>
<div class="mensaje mensaje-exito">
El espacio se ha eliminado correctamente.
</div>
<?php endif; ?>
<?php if (
($_GET['error'] ?? '') === 'en_uso'
): ?>
<div class="mensaje mensaje-error">
No se puede eliminar el espacio porque tiene sesiones asociadas.
Desactívalo si no quieres que se siga utilizando.
</div>
<?php endif; ?>
<div class="tabla-responsive">
<table class="tabla-admin">
<thead>
<tr>
<th>Espacio</th>
<th>Ubicación</th>
<th>Aforo máximo</th>
<th>Estado</th>
<th>Acciones</th>
</tr>
</thead>
<tbody>
<?php while (
$espacio = $resultado->fetch_assoc()
): ?>
<tr>
<td>
    <?= escapar($espacio['nombre']) ?>
</td>

<td>
    <?= escapar($espacio['ubicacion']) ?>
</td>

<td>
<?= (int)
$espacio['aforo_maximo'] ?>
</td>
<td>
<?= (int) $espacio['activo'] === 1
? 'Disponible'
: 'Inactivo' ?>
</td>
<td class="acciones-tabla">
<a
class="boton boton-secundario boton-pequeno"
href="editar_espacio.php?id_espacio=<?= (int) $espacio['id_espacio'] ?>"
>
Editar
</a>
<form
action="eliminar_espacio.php"
method="post"
onsubmit="return confirm('¿Seguro que quieres eliminar este espacio?');"
>
<input
type="hidden"
name="id_espacio"
value="<?= (int) $espacio['id_espacio'] ?>"
>
<button class="boton peligro boton-pequeno" type="submit">
Eliminar
</button>
</form>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</main>
</body>
</html>