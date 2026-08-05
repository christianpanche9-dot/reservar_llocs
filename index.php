<?php
require_once __DIR__ . '/funciones.php';
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
Reservar Llocs | Reserva actividades y espacios
<link rel="stylesheet" href="estilos.css">
</head>
<body>
<?php require_once __DIR__ . '/menu.php'; ?>
<main>
<section class="hero">
<div class="contenedor hero-interior">
<div>
<p class="etiqueta">
Actividades, talleres y espacios
</p>
<h1>
</h1>
Cada reserva tiene su lugar
<p class="hero-texto">
Consulta las próximas actividades del centro,
comprueba sus horarios y encuentra una plaza.
</p>
<div class="grupo-botones">
<a
class="boton"
href="actividades.php"
>
Ver actividades
</a>
<a
class="boton boton-secundario"
href="sesiones.php"
>
Próximas sesiones
</a>
</div>
</div>
<div class="hero-resumen">
<h2>¿Cómo funciona?</h2>
<ol>
<li>Elige una actividad.</li>
<li>Consulta sus próximas sesiones.</li>
<li>Comprueba fecha, horario y plazas.</li>
<li>Reserva la sesión que te interese.</li>
</ol>
</div>
</div>
</section>
<section class="contenedor seccion">
<h2>Un proyecto basado en la disponibilidad</h2>
<div class="rejilla-ventajas">
<article class="tarjeta-informativa">
<h3>Actividades</h3>
<p>
Descubre qué puedes hacer, su categoría,
nivel y duración habitual.
</p>
</article>
<article class="tarjeta-informativa">
<h3>Sesiones</h3>
<p>
Consulta cuándo se celebra cada actividad,
en qué espacio y con qué monitor.
</p>
</article>
<article class="tarjeta-informativa">
<h3>Plazas</h3>
<p>
Comprueba el aforo y las plazas disponibles
antes de realizar la reserva.
</p>
</article>
</div>
</section>
</main>
</body>
</html>
