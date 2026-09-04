<?php
$archivo_env = __DIR__ . '/.env';
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
$clave = trim($clave);
$valor = trim($valor);
if ($clave !== '' && getenv($clave) === false) {
putenv("$clave=$valor");
}
}
}
$smtp = [
'host' => getenv('SMTP_HOST'),
'port' => (int) (getenv('SMTP_PORT') ?: 587),
'user' => getenv('SMTP_USER'),
'pass' => getenv('SMTP_PASS'),
'from' => getenv('SMTP_FROM'),
'name' => getenv('SMTP_FROM_NAME') ?: 'Reservar Llocs'
];
foreach (['host', 'user', 'pass', 'from'] as $campo) {
if (!$smtp[$campo]) {
throw new RuntimeException("Falta SMTP_$campo");
}
}
