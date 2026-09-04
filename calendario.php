<?php
require_once 'conexion.php';
$actividades = $conexion->query(
"SELECT id_actividad, nombre
FROM actividades
WHERE activa = 1
ORDER BY nombre"
);
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Calendario de actividades</title>
<link rel="stylesheet" href="estilos.css">
<link rel="stylesheet" href="calendario.css">
<script src="calendario.js" defer></script>
</head>
<body>
<?php require "menu.php"; ?>
<main class="contenedor seccion calendario-app">
<h1>Calendario de actividades</h1>
<div class="barra-calendario">
<button class="boton boton-secundario" type="button" id="anterior">
← Anterior
</button>
<h2 id="titulo-mes"></h2>
<button class="boton boton-secundario" type="button" id="siguiente">
Siguiente →
</button>
</div>
<div class="campo campo-filtro">
<label for="filtro-actividad">Actividad</label>
<select id="filtro-actividad">
    <option value="">Todas las actividades</option>
<?php while ($actividad = $actividades->fetch_assoc()): ?>
<option value="<?= (int) $actividad['id_actividad'] ?>">
<?= htmlspecialchars($actividad['nombre']) ?>
</option>
<?php endwhile; ?>
</select>
</div>
<p id="estado" role="status" aria-live="polite"></p>
<div class="dias-semana" aria-hidden="true">
<span>Lun</span><span>Mar</span><span>Mié</span>
<span>Jue</span><span>Vie</span><span>Sáb</span><span>Dom</span>
</div>
<section id="calendario" class="cuadricula"></section>
<aside id="detalle" class="detalle" hidden></aside>
</main>
</body>
</html>