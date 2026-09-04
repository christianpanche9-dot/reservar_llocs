<?php
require_once __DIR__ . "/../funciones.php";

if (!usuarioAutenticado()) {
header("Location: ../login.php?error=acceso");
exit;
}

if (!usuarioEsAdmin()) {
http_response_code(403);

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Acceso denegado</title>
<link
rel="stylesheet"
href="../estilos.css"
>
</head>
<body>
<main class="contenedor">
<h1>Acceso denegado</h1>
<div class="mensaje error">
No tienes permisos para acceder
a la administración.
</div>
<a class="boton" href="../index.php">
Volver al inicio
</a>
</main>
</body>
</html>
<?php
exit;
}