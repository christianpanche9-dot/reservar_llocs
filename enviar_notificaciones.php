<?php
if (PHP_SAPI !== 'cli') {
http_response_code(404);
exit;
}
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/config_correo.php';

use PHPMailer\PHPMailer\PHPMailer;

function enviarEmail(array $aviso, array $smtp): void
{
$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = $smtp['host'];
$mail->Port = $smtp['port'];
$mail->SMTPAuth = true;
$mail->Username = $smtp['user'];
$mail->Password = $smtp['pass'];
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->CharSet = 'UTF-8';
$mail->setFrom($smtp['from'], $smtp['name']);
$mail->addAddress($aviso['destinatario']);
$mail->Subject = $aviso['asunto'];
$mail->Body = $aviso['cuerpo'];
$mail->isHTML(false);
$mail->send();
}

function registrarFallo(
mysqli $conexion,
array $aviso,
Throwable $error
): void {
$intento = (int) $aviso['intentos'] + 1;
$estado = $intento >= 5 ? 'fallida' : 'pendiente';
$minutos = min(60, 2 ** $intento);
$mensaje = mb_substr($error->getMessage(), 0, 500);
$stmt = $conexion->prepare(
"UPDATE notificaciones
SET estado = ?, procesando_en = NULL, ultimo_error = ?,
procesar_despues = DATE_ADD(NOW(), INTERVAL ? MINUTE)
WHERE id_notificacion = ?"
);
$stmt->bind_param(
'ssii',
$estado,
$mensaje,
$minutos,
$aviso['id_notificacion']
);
$stmt->execute();
$stmt->close();
}

function procesarUnaNotificacion(
mysqli $conexion,
array $smtp,
bool $modoPruebas
): bool {
$conexion->begin_transaction();
$consulta = $conexion->query(
"SELECT id_notificacion, destinatario, asunto, cuerpo, intentos
FROM notificaciones
WHERE estado = 'pendiente'
AND procesar_despues <= NOW()
ORDER BY creada_en
LIMIT 1
FOR UPDATE"
);
$aviso = $consulta->fetch_assoc();
if (!$aviso) {
$conexion->commit();
return false;
}
$marcar = $conexion->prepare(
"UPDATE notificaciones
SET estado = 'procesando', intentos = intentos + 1,
procesando_en = NOW()
WHERE id_notificacion = ?"
);
$marcar->bind_param('i', $aviso['id_notificacion']);
$marcar->execute();
$marcar->close();
$conexion->commit();

$envio = $aviso;
if (!filter_var($envio['destinatario'], FILTER_VALIDATE_EMAIL)) {
registrarFallo(
$conexion,
$aviso,
new RuntimeException('Destinatario no válido.')
);
return true;
}
if ($modoPruebas) {
$envio['asunto'] =
'[PRUEBA para ' . $envio['destinatario'] . '] ' .
$envio['asunto'];
$envio['destinatario'] = getenv('EMAIL_PRUEBAS');
}
try {
enviarEmail($envio, $smtp);
$stmt = $conexion->prepare(
"UPDATE notificaciones
SET estado = 'enviada', enviada_en = NOW(),
procesando_en = NULL, ultimo_error = NULL
WHERE id_notificacion = ?"
);
$stmt->bind_param('i', $aviso['id_notificacion']);
$stmt->execute();
$stmt->close();
} catch (Throwable $error) {
registrarFallo($conexion, $aviso, $error);
}
return true;
}

$conexion->query(
"UPDATE notificaciones
SET estado = 'pendiente', procesando_en = NULL,
procesar_despues = NOW(),
ultimo_error = 'Worker interrumpido; se reintentará.'
WHERE estado = 'procesando'
AND procesando_en < DATE_SUB(NOW(), INTERVAL 15 MINUTE)"
);

$modoPruebas = getenv('APP_ENV') !== 'production';
$maximo = 20;
for ($i = 0; $i < $maximo; $i++) {
$procesada = procesarUnaNotificacion(
$conexion,
$smtp,
$modoPruebas
);
if (!$procesada) {
break;
}
}
