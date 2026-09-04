<?php
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/funciones.php';
$categoria = trim($_GET['categoria'] ?? '');
$nivel = trim($_GET['nivel'] ?? '');
$niveles_permitidos = [
'todos',
'inicial',
'intermedio',
'avanzado'
];
if (
$nivel !== '' &&
!in_array($nivel, $niveles_permitidos, true)
) {
$nivel = '';
}
$sql_categorias = "
SELECT DISTINCT categoria
FROM actividades
WHERE activa = 1
ORDER BY categoria
";
$resultado_categorias =
$conexion->query($sql_categorias);
$condiciones = [
'a.activa = 1'
];
$tipos = '';
$valores = [];
if ($categoria !== '') {
$condiciones[] = 'a.categoria = ?';
$tipos .= 's';
$valores[] = $categoria;
}
if ($nivel !== '') {
$condiciones[] = 'a.nivel = ?';
$tipos .= 's';
$valores[] = $nivel;
}
$where = implode(' AND ', $condiciones);
$sql = "
SELECT
a.id_actividad,
a.nombre,
a.descripcion,
a.categoria,
a.nivel,
a.duracion_minutos,
a.imagen,
COUNT(s.id_sesion) AS proximas_sesiones,
MIN(
TIMESTAMP(
s.fecha,
s.hora_inicio
)
) AS proxima_fecha
FROM actividades AS a
LEFT JOIN sesiones AS s
ON s.id_actividad = a.id_actividad
AND s.estado IN (
'programada',
'completa'
)
AND TIMESTAMP(
s.fecha,
s.hora_fin
) >= NOW()
WHERE $where
GROUP BY
a.id_actividad,
a.nombre,
a.descripcion,
a.categoria,
a.nivel,
a.duracion_minutos,
a.imagen
ORDER BY a.nombre
";
$stmt = $conexion->prepare($sql);
if (!$stmt) {
die('No se ha podido preparar la consulta.');
}
if ($tipos !== '') {
$stmt->bind_param(
$tipos,
...$valores
);
}
$stmt->execute();
$resultado = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta
name="viewport"
content="width=device-width, initial-scale=1.0"
>
<title>Actividades | Reservar Llocs</title>
<link rel="stylesheet" href="estilos.css">
</head>
<body>
<?php require_once __DIR__ . '/menu.php'; ?>
<main class="contenedor seccion">
<div class="encabezado-pagina">
<div>
<p class="etiqueta">
Catálogo
</p>
<h1>Actividades</h1>
<p>
Consulta las actividades del centro y descubre
sus próximas sesiones.
</p>
</div>
</div>
<form
class="formulario-filtros"
action="actividades.php"
method="get"
>
<div class="campo">
<label for="categoria">
Categoría
</label>
<select
id="categoria"
name="categoria"
>
<option value="">
Todas las categorías
</option>
<?php while (
$fila_categoria =
$resultado_categorias->fetch_assoc()
): ?>
<option
value="<?= escapar(
$fila_categoria['categoria']
) ?>"
<?= $categoria ===
$fila_categoria['categoria']
? 'selected'
: '' ?>
>
<?= escapar(
$fila_categoria['categoria']
) ?>
</option>
<?php endwhile; ?>
</select>
</div>
<div class="campo">
<label for="nivel">
Nivel
</label>
<select id="nivel" name="nivel">
<option value="">
Cualquier nivel
</option>
<?php foreach (
$niveles_permitidos as $valor_nivel
): ?>
<option
value="<?= escapar($valor_nivel) ?>"
<?= $nivel === $valor_nivel
? 'selected'
: '' ?>
>
<?= escapar(
texto_nivel($valor_nivel)
) ?>
</option>
<?php endforeach; ?>
</select>
</div>
<div class="acciones-filtro">
<button class="boton" type="submit">
Filtrar
</button>
<a
class="boton boton-secundario"
href="actividades.php"
>
Limpiar
</a>
</div>
</form>
<?php if ($resultado->num_rows === 0): ?>
<div class="mensaje mensaje-aviso">
No se han encontrado actividades
con los filtros seleccionados.
</div>
<?php else: ?>
<div class="rejilla-actividades">
<?php while (
$actividad = $resultado->fetch_assoc()
): ?>
<article class="tarjeta-actividad">
<?php if (!empty($actividad['imagen'])): ?>
<img
class="imagen-actividad"
src="imagenes/actividades/<?= escapar(
$actividad['imagen']
) ?>"
alt="<?= escapar(
$actividad['nombre']
) ?>"
>
<?php else: ?>
<div class="imagen-sin-contenido">
Sin imagen
</div>
<?php endif; ?>
<div class="contenido-tarjeta">
<div class="metadatos">
<span class="insignia">
<?= escapar(
$actividad['categoria']
) ?>
</span>
<span class="insignia insignia-clara">
<?= escapar(
texto_nivel(
$actividad['nivel']
)
) ?>
</span>
</div>
<h2>
<?= escapar(
$actividad['nombre']
) ?>
<p>
<?= escapar(
$actividad['descripcion']
) ?>
</h2>
</p>
<p class="dato-destacado">
Duración habitual:
<?= (int)
$actividad['duracion_minutos'] ?>
minutos
</p>
<?php if (
    (int) $actividad['proximas_sesiones'] > 0
): ?>
<p class="proxima-sesion">
<?= (int)
$actividad['proximas_sesiones'] ?>
próximas sesiones
</p>
<p>
Próxima:
<?= escapar(
    date(
'd/m/Y H:i',
strtotime(
$actividad['proxima_fecha']
)
)
) ?>
</p>
<?php else: ?>
<p class="sin-sesiones">
Próximamente anunciaremos nuevas fechas.
</p>
<?php endif; ?>
<a
class="boton"
href="detalle_actividad.php?id=<?= (int)
$actividad['id_actividad'] ?>"
>
Ver actividad
</a>
</div>
</article>
<?php endwhile; ?>
</div>
<?php endif; ?>
</main>
</body>
</html>