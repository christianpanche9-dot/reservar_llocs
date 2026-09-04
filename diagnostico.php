<?php
header('Content-Type: text/plain; charset=utf-8');
$archivo_env = __DIR__ . '/.env';
echo "Ruta buscada: $archivo_env\n";
echo "Existe: " . (file_exists($archivo_env) ? 'si' : 'NO') . "\n";
echo "Es legible: " . (is_readable($archivo_env) ? 'si' : 'NO') . "\n";
if (is_readable($archivo_env)) {
$contenido = file_get_contents($archivo_env);
echo "Tamaño en bytes: " . strlen($contenido) . "\n";
echo "Primeros 3 bytes (hex): " . bin2hex(substr($contenido, 0, 3)) . "\n";
$variables_entorno = [];
foreach (file($archivo_env) as $linea) {
$linea = trim($linea);
if ($linea === '' || str_starts_with($linea, '#')) {
continue;
}
[$clave, $valor] = array_pad(
explode('=', $linea, 2),
2,
''
);
$variables_entorno[trim($clave)] = trim($valor);
}
echo "Claves detectadas: " . implode(', ', array_keys($variables_entorno)) . "\n";
echo "DB_HOST leído: '" . ($variables_entorno['DB_HOST'] ?? '(no detectado, usaría localhost por defecto)') . "'\n";
echo "DB_USER leído: '" . ($variables_entorno['DB_USER'] ?? '(no detectado)') . "'\n";
echo "DB_NAME leído: '" . ($variables_entorno['DB_NAME'] ?? '(no detectado)') . "'\n";
echo "DB_PASS longitud: " . strlen($variables_entorno['DB_PASS'] ?? '') . " caracteres\n";
$servidor = $variables_entorno['DB_HOST'] ?? 'localhost';
$usuario = $variables_entorno['DB_USER'] ?? 'root';
$password = $variables_entorno['DB_PASS'] ?? '';
$base_datos = $variables_entorno['DB_NAME'] ?? 'reservar_llocs';
echo "\nIntentando conectar...\n";
try {
$conexion = @new mysqli($servidor, $usuario, $password, $base_datos);
if ($conexion->connect_error) {
echo "FALLO: " . $conexion->connect_error . "\n";
} else {
echo "CONEXION EXITOSA\n";
}
} catch (mysqli_sql_exception $e) {
echo "EXCEPCION: " . $e->getMessage() . "\n";
}
}
