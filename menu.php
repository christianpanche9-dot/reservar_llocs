<?php
require_once __DIR__ . "/funciones.php";
?>
<header class="cabecera">
<div class="contenedor cabecera-interior">
<nav class="menu">
<a class="logo" href="index.php">
Reservar Llocs
</a>
<div class="menu-principal">
    <a href="actividades.php">
Actividades
</a>
<a href="sesiones.php">
Sesiones
</a>
<?php if (!usuarioAutenticado()): ?>
<a href="login.php">
Iniciar sesión
</a>
<a href="registro.php">
Registrarse
</a>
<?php else: ?>
<?php if (usuarioEsAdmin()): ?>
<a href="admin/index.php">
Administración
</a>
<?php else: ?>
<a href="mi_cuenta.php">
Mi cuenta
</a>
<a href="mis_reservas.php">
Mis reservas
</a>
<?php endif; ?>
<span class="usuario-menu">
<?= escapar(
    nombreUsuarioActual()
) ?>
</span>
<a href="logout.php">
Salir
</a>
<?php endif; ?>
</div>
</div>
</nav>
</header>