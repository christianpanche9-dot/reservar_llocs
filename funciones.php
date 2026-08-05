<?php
if (session_status() === PHP_SESSION_NONE) {
session_start();
}

function escapar(?string $texto): string
{
return htmlspecialchars(
$texto ?? "",
ENT_QUOTES,
"UTF-8"
);
}
function usuarioAutenticado(): bool
{
return isset($_SESSION["usuario"]);
}
function usuarioEsAdmin(): bool
{
return isset($_SESSION["usuario"]) &&
$_SESSION["usuario"]["rol"] === "admin";
}
function idUsuarioActual(): ?int
{
    return $_SESSION["usuario"]["id_usuario"] ?? null;
}
function nombreUsuarioActual(): string
{
return $_SESSION["usuario"]["nombre"] ?? "";
}
function formatear_fecha(string $fecha): string
{
$objeto_fecha = DateTime::createFromFormat(
'Y-m-d',
$fecha
);
if (!$objeto_fecha) {
return $fecha;
}
return $objeto_fecha->format('d/m/Y');
}

function formatear_hora(string $hora): string
{
$objeto_hora = DateTime::createFromFormat(
'H:i:s',
$hora
);
if (!$objeto_hora) {
return substr($hora, 0, 5);
}
return $objeto_hora->format('H:i');
}

function texto_nivel(string $nivel): string
{
$niveles = [
'todos' => 'Todos los niveles',
'inicial' => 'Nivel inicial',
'intermedio' => 'Nivel intermedio',
'avanzado' => 'Nivel avanzado'
];
return $niveles[$nivel] ?? ucfirst($nivel);
}

function texto_estado_sesion(string $estado): string
{
$estados = [
'programada' => 'Programada',
'completa' => 'Completa',
'cancelada' => 'Cancelada',
'finalizada' => 'Finalizada'
];
return $estados[$estado] ?? ucfirst($estado);
}

function clase_estado_sesion(string $estado): string
{
$estados_permitidos = [
'programada',
'completa',
'cancelada',
'finalizada'
];
if (!in_array($estado, $estados_permitidos, true)) {
return 'desconocido';
}
return $estado;

}
function calcular_porcentaje_ocupacion(
int $reservas,
int $aforo
): float {
if ($aforo <= 0) {
return 0;
}
$porcentaje = ($reservas / $aforo) * 100;
return min(100, round($porcentaje, 1));
}

function fecha_valida(string $fecha): bool
{
$objeto_fecha = DateTime::createFromFormat(
'Y-m-d',
$fecha
);
return $objeto_fecha !== false
&& $objeto_fecha->format('Y-m-d') === $fecha;
}

function hora_valida(string $hora): bool
{
$objeto_hora = DateTime::createFromFormat(
'H:i',
$hora
);
return $objeto_hora !== false
&& $objeto_hora->format('H:i') === $hora;
}
function entero_positivo(
mixed $valor,
int $minimo = 1
): bool {
return filter_var(
$valor,
FILTER_VALIDATE_INT,
[
'options' => [
'min_range' => $minimo
]
]
) !== false;
}
