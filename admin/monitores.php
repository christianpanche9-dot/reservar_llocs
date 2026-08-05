<?php
require_once "seguridad_admin.php";
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../funciones.php';
$sql = "
SELECT
id_monitor,
nombre,
apellidos,
email,
especialidad,
activo
FROM monitores
ORDER BY apellidos, nombre
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
<title>Monitores | Administración</title>
<link rel="stylesheet" href="../estilos.css">
</head>
<body>
<?php require_once __DIR__ . '/menu_admin.php'; ?>

<main class="contenedor seccion">
<div class="encabezado-con-accion">
<h1>Monitores</h1>
<a class="boton" href="nuevo_monitor.php">
Nuevo monitor
</a>
</div>

<?php if (
    ($_GET['mensaje'] ?? '') === 'creado'
): ?>

<div class="mensaje mensaje-exito">
El monitor se ha creado correctamente.
</div>
<?php endif; ?>
<div class="tabla-responsive">
<table class="tabla-admin">
<thead>
<tr>
<th>Monitor</th>
<th>Correo</th>
<th>Especialidad</th>
<th>Estado</th>
</tr>
</thead>
<tbody>
<?php while (
$monitor = $resultado->fetch_assoc()
): ?>
<tr>
<td>
<?= escapar(
$monitor['nombre'] . ' ' .
$monitor['apellidos']
) ?>
</td>

<td>
    <?= escapar($monitor['email']) ?>
</td>

<td>
    <?= escapar(
$monitor['especialidad']
) ?>
</td>

<td>
<?= (int) $monitor['activo'] === 1
? 'Activo'
: 'Inactivo' ?>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</main>
</body>
</html>