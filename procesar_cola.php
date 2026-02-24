<?php
// procesar_cola.php - Procesa la cola de correos en lotes desde el navegador
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Cargar configuración
if (!file_exists(__DIR__ . '/mail_config.php')) {
    die('Falta mail_config.php. Copia mail_config.php.example a mail_config.php y completa las credenciales.');
}
$mail_config = include __DIR__ . '/mail_config.php';
$db = $mail_config['db'] ?? null;
$smtp = $mail_config['smtp'] ?? null;
$from = $mail_config['from'] ?? ['email'=>'noreply@localhost','name'=>'No Reply'];
$batch_size = $mail_config['batch_size'] ?? 50;

if (!$db || !$smtp) {
    die('Configuración de BD o SMTP inválida en mail_config.php');
}

try {
    $pdo = new PDO("mysql:host={$db['host']};dbname={$db['name']};charset=utf8", $db['user'], $db['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('No se pudo conectar a la base de datos: ' . $e->getMessage());
}

// Verificar PHPMailer - intentar múltiples ubicaciones
$phpmailer_loaded = false;

// Intento 1: Composer estándar
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require 'vendor/autoload.php';
    $phpmailer_loaded = true;
}
// Intento 2: Instalación manual
elseif (file_exists(__DIR__ . '/vendor/phpmailer/phpmailer/src/PHPMailer.php')) {
    require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
    require 'vendor/phpmailer/phpmailer/src/SMTP.php';
    require 'vendor/phpmailer/phpmailer/src/Exception.php';
    $phpmailer_loaded = true;
}

if (!$phpmailer_loaded) {
    echo '<div style="padding: 20px; background: #f8d7da; color: #721c24; border-radius: 5px; margin: 20px;">';
    echo '<h2>❌ ERROR: PHPMailer no está instalado</h2>';
    echo '<p>Por favor, instala PHPMailer antes de procesar correos.</p>';
    echo '<p><strong>Lee:</strong> <a href="INSTALAR_PHPMAILER.md">INSTALAR_PHPMAILER.md</a></p>';
    echo '<p><strong>O copia:</strong> <a href="instalador_phpmailer.php">instalador_phpmailer.php</a> a tu servidor</p>';
    echo '</div>';
    exit;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Obtener parámetro para saber cuántos lotes procesar
$lotes = isset($_GET['lotes']) ? (int)$_GET['lotes'] : 1;
if ($lotes < 1) $lotes = 1;
if ($lotes > 5) $lotes = 5; // Máximo 5 lotes para evitar timeout

$total_enviados = 0;
$total_errores = 0;
$resultados = [];

for ($lote = 0; $lote < $lotes; $lote++) {
    // Obtener batch de correos en cola
    $pdo->beginTransaction();
    $sel = $pdo->prepare("SELECT * FROM email_queue WHERE status = 'queued' ORDER BY id ASC LIMIT ? FOR UPDATE");
    $sel->bindValue(1, $batch_size, PDO::PARAM_INT);
    $sel->execute();
    $rows = $sel->fetchAll(PDO::FETCH_ASSOC);

    if (count($rows) === 0) {
        $pdo->commit();
        $resultados[] = "Lote " . ($lote + 1) . ": No hay correos en cola.";
        break;
    }

    // Marcar como sending para evitar duplicados
    $ids = array_column($rows, 'id');
    $in = implode(',', array_map('intval', $ids));
    $pdo->exec("UPDATE email_queue SET status='sending' WHERE id IN ($in)");
    $pdo->commit();

    $lote_enviados = 0;
    $lote_errores = 0;

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
            $mail->Timeout = 15;
            
            // Capturar debug SMTP para mostrar en errores
            $mail->SMTPDebug = 2;
            $mail->Debugoutput = function($str, $level) use (&$debug_log) {
                $debug_log[] = $str;
            };

            $mail->setFrom($from['email'], $from['name']);
            $mail->addAddress($row['recipient_email'], $row['recipient_name']);
            $mail->isHTML(true);
            $mail->Subject = $row['subject'];
            $mail->Body = $row['body'];

            // Adjuntos
            $attachments = json_decode($row['attachments'] ?? '[]', true);
            foreach ($attachments as $att) {
                $path = __DIR__ . '/mail_uploads/' . basename($att);
                if (is_file($path)) $mail->addAttachment($path);
            }

            $mail->send();

            $stmt = $pdo->prepare("UPDATE email_queue SET status='sent', sent_at = NOW() WHERE id = ?");
            $stmt->execute([$row['id']]);
            $lote_enviados++;
            $total_enviados++;
        } catch (Exception $e) {
            // Crear mensaje de error detallado con el log SMTP
            $error_msg = $e->getMessage();
            if (!empty($debug_log)) {
                $error_msg .= "\n\n=== DEBUG SMTP ===\n" . implode("\n", $debug_log);
            }
            
            $stmt = $pdo->prepare("UPDATE email_queue SET status='failed', attempts = attempts + 1, last_error = ? WHERE id = ?");
            $stmt->execute([substr($error_msg, 0, 2500), $row['id']]);
            $lote_errores++;
            $total_errores++;
        }
    }

    $resultados[] = "Lote " . ($lote + 1) . ": $lote_enviados enviados, $lote_errores errores";
}

// Obtener estado actual de la cola
$cola = $pdo->query("SELECT COUNT(*) as queued FROM email_queue WHERE status='queued'")->fetch(PDO::FETCH_ASSOC);
$enviados = $pdo->query("SELECT COUNT(*) as sent FROM email_queue WHERE status='sent'")->fetch(PDO::FETCH_ASSOC);
$errores = $pdo->query("SELECT COUNT(*) as failed FROM email_queue WHERE status='failed'")->fetch(PDO::FETCH_ASSOC);

?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Procesar Cola de Correos</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-top: 0;
        }
        .status {
            background: #f0f8ff;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #2196F3;
        }
        .status-item {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
            font-size: 16px;
        }
        .status-label {
            font-weight: bold;
            color: #555;
        }
        .status-value {
            color: #2196F3;
            font-weight: bold;
        }
        .resultados {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border: 1px solid #ddd;
        }
        .resultado-item {
            padding: 8px;
            margin: 5px 0;
            background: white;
            border-left: 3px solid #4CAF50;
            border-radius: 3px;
        }
        .acciones {
            display: flex;
            gap: 10px;
            margin: 20px 0;
        }
        .btn {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: background 0.3s;
        }
        .btn-primary {
            background: #2196F3;
            color: white;
        }
        .btn-primary:hover {
            background: #1976D2;
        }
        .btn-secondary {
            background: #666;
            color: white;
        }
        .btn-secondary:hover {
            background: #555;
        }
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .stats {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px;
            margin: 20px 0;
        }
        .stat-box {
            background: #f0f0f0;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
        }
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #2196F3;
        }
        .stat-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 Procesar Cola de Correos</h1>

        <div class="alert alert-success">
            <strong>✓ Lotes procesados:</strong> Se ejecutaron <?= count($resultados) ?> lote(s)
        </div>

        <div class="stats">
            <div class="stat-box">
                <div class="stat-number"><?= $total_enviados ?></div>
                <div class="stat-label">Enviados</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?= $cola['queued'] ?></div>
                <div class="stat-label">En Cola</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?= $total_errores ?></div>
                <div class="stat-label">Con Error</div>
            </div>
        </div>

        <div class="resultados">
            <h3>Resultados:</h3>
            <?php foreach ($resultados as $resultado): ?>
            <div class="resultado-item"><?= htmlspecialchars($resultado) ?></div>
            <?php endforeach; ?>
        </div>

        <div class="status">
            <div class="status-item">
                <span class="status-label">📤 Correos enviados:</span>
                <span class="status-value"><?= $enviados['sent'] ?></span>
            </div>
            <div class="status-item">
                <span class="status-label">⏳ En cola esperando:</span>
                <span class="status-value"><?= $cola['queued'] ?></span>
            </div>
            <div class="status-item">
                <span class="status-label">❌ Con errores:</span>
                <span class="status-value"><?= $errores['failed'] ?></span>
            </div>
        </div>

        <?php if ($cola['queued'] > 0): ?>
        <div class="alert alert-warning">
            <strong>⚠ Hay <?= $cola['queued'] ?> correo(s) aún en cola.</strong> Puedes seguir procesando lotes.
        </div>
        <?php else: ?>
        <div class="alert alert-success">
            <strong>✓ La cola está vacía.</strong> Todos los correos han sido procesados.
        </div>
        <?php endif; ?>

        <div class="acciones">
            <?php if ($cola['queued'] > 0): ?>
            <a href="procesar_cola.php?lotes=1" class="btn btn-primary">▶ Procesar 1 Lote</a>
            <a href="procesar_cola.php?lotes=3" class="btn btn-primary">▶ Procesar 3 Lotes</a>
            <a href="procesar_cola.php?lotes=5" class="btn btn-primary">▶ Procesar 5 Lotes</a>
            <?php endif; ?>
            <a href="estado_envios.php" class="btn btn-secondary">← Volver</a>
        </div>
    </div>
</body>
</html>
