<?php
$servidor = 'localhost';
$usuario = 'root';
$password = '';
$base_datos = 'reservar_llocs';
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