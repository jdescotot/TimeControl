<?php
// worker_send.php - Procesa la cola `email_queue` y envía correos en lotes (ejecución CLI)
// Uso: php worker_send.php
if (php_sapi_name() !== 'cli') { die('Este script debe ejecutarse desde la línea de comandos.'); }

if (!file_exists(__DIR__ . '/mail_config.php')) {
    die('Falta mail_config.php. Copia mail_config.php.example y completa las credenciales.');
}
$mail_config = include __DIR__ . '/mail_config.php';
$db = $mail_config['db'] ?? null;
$smtp = $mail_config['smtp'] ?? null;
$from = $mail_config['from'] ?? ['email'=>'noreply@localhost','name'=>'No Reply'];
$batch = $mail_config['batch_size'] ?? 50;
$pause = $mail_config['pause_seconds'] ?? 30;

try {
    $pdo = new PDO("mysql:host={$db['host']};dbname={$db['name']};charset=utf8", $db['user'], $db['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('No se pudo conectar a la base de datos: ' . $e->getMessage());
}

require 'vendor/autoload.php'; // PHPMailer si está instalado via Composer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

while (true) {
    // Obtener batch
    $pdo->beginTransaction();
    $sel = $pdo->prepare("SELECT * FROM email_queue WHERE status = 'queued' ORDER BY id ASC LIMIT ? FOR UPDATE");
    $sel->bindValue(1, (int)$batch, PDO::PARAM_INT);
    $sel->execute();
    $rows = $sel->fetchAll(PDO::FETCH_ASSOC);

    if (count($rows) === 0) {
        $pdo->commit();
        echo date('c') . " - No hay mensajes en cola. Saliendo.\n";
        break;
    }

    // Marcar como sending para evitar duplicados
    $ids = array_column($rows, 'id');
    $in = implode(',', array_map('intval', $ids));
    $pdo->exec("UPDATE email_queue SET status='sending' WHERE id IN ($in)");
    $pdo->commit();

    foreach ($rows as $row) {
        $mail = new PHPMailer(true);
        $debug_log = [];
        try {
            // Configurar SMTP
            $mail->CharSet = 'UTF-8';
            $mail->isSMTP();
            $mail->Host = $smtp['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $smtp['user'];
            $mail->Password = $smtp['pass'];
            $mail->SMTPSecure = $smtp['secure'] ?? 'tls';
            $mail->Port = $smtp['port'] ?? 587;
            
            // Debug SMTP para terminal
            $mail->SMTPDebug = 2;
            $mail->Debugoutput = function($str, $level) use (&$debug_log) {
                $debug_log[] = $str;
                echo $str; // También mostrar en consola
            };

            $mail->setFrom($from['email'], $from['name']);
            $mail->addAddress($row['recipient_email'], $row['recipient_name']);
            $mail->isHTML(true);
            $mail->Subject = $row['subject'];
            $mail->Body = $row['body'];

            // Adjuntos e imágenes embebidas
            $attachments = json_decode($row['attachments'] ?? '[]', true);
            if (!is_array($attachments)) { $attachments = []; }
            foreach ($attachments as $att) {
                if (is_string($att)) {
                    $file = $att;
                    $type = 'attachment';
                    $name = basename($att);
                    $cid = null;
                } else {
                    $file = $att['file'] ?? '';
                    $type = $att['type'] ?? 'attachment';
                    $name = $att['name'] ?? basename($file);
                    $cid = $att['cid'] ?? null;
                }

                if ($file === '') continue;
                $path = __DIR__ . '/mail_uploads/' . basename($file);
                if (!is_file($path)) continue;

                if ($type === 'inline' && $cid) {
                    $mail->addEmbeddedImage($path, $cid, $name);
                } else {
                    $mail->addAttachment($path, $name);
                }
            }

            $mail->send();

            $stmt = $pdo->prepare("UPDATE email_queue SET status='sent', sent_at = NOW() WHERE id = ?");
            $stmt->execute([$row['id']]);
            echo "Enviado a {$row['recipient_email']}\n";
        } catch (Exception $e) {
            // Crear mensaje de error detallado con el log SMTP
            $error_msg = $e->getMessage();
            if (!empty($debug_log)) {
                $error_msg .= "\n\n=== DEBUG SMTP ===\n" . implode("\n", $debug_log);
            }
            
            $stmt = $pdo->prepare("UPDATE email_queue SET status='failed', attempts = attempts + 1, last_error = ? WHERE id = ?");
            $stmt->execute([substr($error_msg, 0, 2500), $row['id']]);
            echo "Error enviando a {$row['recipient_email']}: " . $e->getMessage() . "\n";
            if (!empty($debug_log)) {
                echo "=== DEBUG SMTP ===\n" . implode("\n", $debug_log) . "\n";
            }
        }
    }

    // Pausa entre lotes
    echo "Pausa de {$pause} segundos antes del siguiente lote...\n";
    sleep((int)$pause);
}

echo "Worker terminado.\n";
