<?php
header('Content-Type: text/plain; charset=utf-8');
echo "Carpeta: " . __DIR__ . "\n\n";
echo "Archivos que PHP ve realmente en esta carpeta:\n";
$archivos = scandir(__DIR__);
foreach ($archivos as $archivo) {
if ($archivo === '.' || $archivo === '..') {
continue;
}
$ruta = __DIR__ . '/' . $archivo;
$tipo = is_dir($ruta) ? 'carpeta' : 'archivo';
$tamano = is_file($ruta) ? filesize($ruta) . ' bytes' : '';
echo "- [$tipo] '$archivo' $tamano\n";
}
echo "\n";
$archivo_env = __DIR__ . '/.env';
echo "¿Existe exactamente '.env'? " . (file_exists($archivo_env) ? 'SI' : 'NO') . "\n";
