<?php
require_once "seguridad.php";
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta
name="viewport"
content="width=device-width, initial-scale=1.0"
>
<title>Mi cuenta</title>
<link rel="stylesheet" href="estilos.css">
</head>
<body>
<?php require "menu.php"; ?>
<main class="contenedor">
<h1>
Hola,
<?= escapar($_SESSION["usuario"]["nombre"]) ?>
</h1>
<section class="panel-cuenta">
<h2>Mis datos</h2>
<dl>
<dt>Nombre</dt>
<dd>
<?= escapar(
$_SESSION["usuario"]["nombre"] .
" " .
$_SESSION["usuario"]["apellidos"]
) ?>
</dd>
<dt>Correo electrónico</dt>
<dd>
    <?= escapar(
$_SESSION["usuario"]["email"]
) ?>
</dd>
</dl>
</section>
<section class="opciones-cuenta">
<article class="tarjeta">
<h2>Mis reservas</h2>
<p>
    Consulta tus próximas actividades y tu historial.
</p>
<a class="boton" href="mis_reservas.php">
Ver mis reservas
</a>
</article>
<article class="tarjeta">
<h2>Mis bonos</h2>
<p>
</p>
Consulta tus bonos y los usos disponibles.
<span class="boton desactivado">
Disponible próximamente
</span>
</article>
</section>
</main>
</body>
</html>