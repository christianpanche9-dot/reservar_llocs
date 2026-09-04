<?php
session_start();
require_once "seguridad_admin.php";
require_once "../conexion.php";
$sql = "
SELECT
id_notificacion,
tipo,
destinatario,
estado,
intentos,
creada_en,
enviada_en,
ultimo_error
FROM notificaciones
ORDER BY creada_en DESC
LIMIT 100
";
$resultado = $conexion->query($sql);
if (empty($_SESSION['csrf_notificacion'])) {
$_SESSION['csrf_notificacion'] = bin2hex(random_bytes(32));
}
$etiquetas_estado = [
"pendiente" => "Pendiente",
"procesando" => "Procesando",
"enviada" => "Enviada",
"fallida" => "Fallida"
];
$clases_estado = [
"pendiente" => "estado-completa",
"procesando" => "estado-finalizada",
"enviada" => "estado-programada",
"fallida" => "estado-cancelada"
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta
name="viewport"
content="width=device-width, initial-scale=1.0"
>
<title>Notificaciones | Reservar Llocs</title>
<link rel="stylesheet" href="../estilos.css">
</head>
<body>
<?php require_once __DIR__ . '/menu_admin.php'; ?>
<main class="contenedor seccion">
<h1>Notificaciones</h1>
<?php if (($_GET["mensaje"] ?? "") === "reactivada"): ?>
<div class="mensaje mensaje-exito">
La notificación se ha vuelto a poner en cola.
</div>
<?php endif; ?>
<?php if (($_GET["error"] ?? "") === "datos"): ?>
<div class="mensaje mensaje-error">
No se ha podido reactivar la notificación.
</div>
<?php endif; ?>
<?php if ($resultado->num_rows === 0): ?>
<p>Todavía no se ha generado ninguna notificación.</p>
<?php else: ?>
<div class="tabla-responsive">
<table class="tabla-admin">
<thead>
<tr>
<th>Tipo</th>
<th>Destinatario</th>
<th>Estado</th>
<th>Intentos</th>
<th>Creada</th>
<th>Enviada</th>
<th>Último error</th>
<th>Acciones</th>
</tr>
</thead>
<tbody>
<?php while ($fila = $resultado->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($fila["tipo"]) ?></td>
<td><?= htmlspecialchars($fila["destinatario"]) ?></td>
<td>
<span class="estado <?= $clases_estado[$fila["estado"]] ?? "" ?>">
<?= $etiquetas_estado[$fila["estado"]] ?? htmlspecialchars($fila["estado"]) ?>
</span>
</td>
<td><?= (int) $fila["intentos"] ?></td>
<td>
<?= date(
"d/m/Y H:i",
strtotime($fila["creada_en"])
) ?>
</td>
<td>
<?= $fila["enviada_en"]
? date("d/m/Y H:i", strtotime($fila["enviada_en"]))
: "—" ?>
</td>
<td>
<?= $fila["ultimo_error"]
? htmlspecialchars($fila["ultimo_error"])
: "—" ?>
</td>
<td>
<?php if ($fila["estado"] === "fallida"): ?>
<form action="reactivar_notificacion.php" method="post">
<input
type="hidden"
name="csrf"
value="<?= htmlspecialchars($_SESSION['csrf_notificacion']) ?>"
>
<input
type="hidden"
name="id_notificacion"
value="<?= (int) $fila['id_notificacion'] ?>"
>
<button class="boton boton-pequeno" type="submit">
Reactivar
</button>
</form>
<?php else: ?>
—
<?php endif; ?>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
<?php endif; ?>
</main>
</body>
</html>
