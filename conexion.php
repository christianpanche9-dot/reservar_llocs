<?php
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
$conexion = new mysqli(
$servidor,
$usuario,
$password,
$base_datos
);
if ($conexion->connect_error) {
die(
'No se ha podido conectar con la base de datos.'
);
}
