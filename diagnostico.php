<?php
header('Content-Type: text/plain; charset=utf-8');
$archivo_env = __DIR__ . '/.env';
$variables_entorno = [];
if (is_readable($archivo_env)) {
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
}
$servidor = $variables_entorno['DB_HOST'] ?? 'localhost';
$usuario = $variables_entorno['DB_USER'] ?? 'root';
$password = $variables_entorno['DB_PASS'] ?? '';
$base_datos = $variables_entorno['DB_NAME'] ?? 'reservar_llocs';
echo "DB_HOST: '$servidor'\n";
echo "DB_USER: '$usuario'\n";
echo "DB_NAME: '$base_datos'\n";
echo "DB_PASS longitud: " . strlen($password) . "\n\n";
echo "Intentando conectar...\n";
try {
$conexion = @new mysqli($servidor, $usuario, $password, $base_datos);
if ($conexion->connect_error) {
echo "FALLO (connect_error): " . $conexion->connect_error . "\n";
echo "Código: " . $conexion->connect_errno . "\n";
} else {
echo "CONEXION EXITOSA\n";
$resultado = $conexion->query("SHOW TABLES");
if ($resultado) {
echo "Tablas encontradas: " . $resultado->num_rows . "\n";
} else {
echo "Conectó pero SHOW TABLES falló: " . $conexion->error . "\n";
}
}
} catch (mysqli_sql_exception $e) {
echo "EXCEPCION: " . $e->getMessage() . "\n";
echo "Código: " . $e->getCode() . "\n";
}
