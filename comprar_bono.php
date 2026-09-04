<?php
require_once "seguridad.php";
require_once "conexion.php";
require_once "funciones.php";
$sql_tipos = "
SELECT id_tipo_bono, nombre, numero_usos, precio, dias_validez
FROM tipos_bono
WHERE activo = 1
ORDER BY numero_usos ASC
";
$resultado = $conexion->query($sql_tipos);
$error = $_GET["error"] ?? "";
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta
name="viewport"
content="width=device-width, initial-scale=1.0"
>
<title>Comprar bono</title>
<link rel="stylesheet" href="estilos.css">
</head>
<body>
<?php require "menu.php"; ?>
<main class="contenedor">
<h1>Comprar un bono</h1>
<?php if ($error === "datos"): ?>
<div class="mensaje mensaje-error">
El bono seleccionado no está disponible.
</div>
<?php endif; ?>
<?php if ($resultado->num_rows === 0): ?>
<p>No hay bonos disponibles en este momento.</p>
<?php else: ?>
<div class="rejilla-reservas">
<?php while ($tipo = $resultado->fetch_assoc()): ?>
<article class="tarjeta-reserva">
<h3>
<?= escapar($tipo["nombre"]) ?>
</h3>
<p>
<strong>Usos:</strong>
<?= (int) $tipo["numero_usos"] ?>
</p>
<p>
<strong>Precio:</strong>
<?= number_format($tipo["precio"], 2) ?> €
</p>
<?php if ($tipo["dias_validez"]): ?>
<p>
<strong>Validez:</strong>
<?= (int) $tipo["dias_validez"] ?> días
</p>
<?php endif; ?>
<form action="procesar_compra_bono.php" method="post">
<input
type="hidden"
name="id_tipo_bono"
value="<?= $tipo["id_tipo_bono"] ?>"
>
<button type="submit" class="boton">
Comprar
</button>
</form>
</article>
<?php endwhile; ?>
</div>
<?php endif; ?>
</main>
</body>
</html>
<?php
$conexion->close();
