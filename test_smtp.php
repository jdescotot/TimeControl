<?php
// test_smtp.php - Herramienta de diagnóstico SMTP
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Cargar configuración
if (!file_exists(__DIR__ . '/mail_config.php')) {
    die('Falta mail_config.php. Copia mail_config.php.example a mail_config.php y completa las credenciales.');
}
$mail_config = include __DIR__ . '/mail_config.php';
$smtp = $mail_config['smtp'] ?? null;
$from = $mail_config['from'] ?? ['email'=>'noreply@localhost','name'=>'No Reply'];

if (!$smtp) {
    die('Configuración SMTP inválida en mail_config.php');
}

// Verificar PHPMailer
$phpmailer_loaded = false;
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require 'vendor/autoload.php';
    $phpmailer_loaded = true;
} elseif (file_exists(__DIR__ . '/vendor/phpmailer/phpmailer/src/PHPMailer.php')) {
    require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
    require 'vendor/phpmailer/phpmailer/src/SMTP.php';
    require 'vendor/phpmailer/phpmailer/src/Exception.php';
    $phpmailer_loaded = true;
}

if (!$phpmailer_loaded) {
    die('PHPMailer no está instalado. Por favor, instálalo primero.');
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$test_result = null;
$debug_output = [];
$test_email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $test_email = trim($_POST['test_email'] ?? '');
    
    if (!filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
        $test_result = ['success' => false, 'message' => 'Email inválido'];
    } else {
        $mail = new PHPMailer(true);
        
        try {
            // Configurar SMTP con debugging
            $mail->CharSet = 'UTF-8';
            $mail->isSMTP();
            $mail->Host = $smtp['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $smtp['user'];
            $mail->Password = $smtp['pass'];
            $mail->SMTPSecure = $smtp['secure'] ?? 'tls';
            $mail->Port = $smtp['port'] ?? 587;
            $mail->Timeout = 30;
            
            // Enable MAXIMUM debug output
            $mail->SMTPDebug = 4; // Show all messages including low-level details
            $mail->Debugoutput = function($str, $level) use (&$debug_output) {
                $debug_output[] = htmlspecialchars($str);
            };
            
            $mail->setFrom($from['email'], $from['name']);
            $mail->addAddress($test_email);
            $mail->isHTML(true);
            $mail->Subject = '✅ Test SMTP - ' . date('Y-m-d H:i:s');
            $mail->Body = '<h2>✅ Conexión SMTP exitosa</h2><p>Este correo confirma que la configuración SMTP funciona correctamente.</p><p>Enviado: ' . date('Y-m-d H:i:s') . '</p>';
            
            $mail->send();
            
            $test_result = [
                'success' => true,
                'message' => '✅ Correo enviado exitosamente a ' . htmlspecialchars($test_email)
            ];
        } catch (Exception $e) {
            $test_result = [
                'success' => false,
                'message' => '❌ Error: ' . htmlspecialchars($e->getMessage())
            ];
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>🔧 Test SMTP - Diagnóstico</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 32px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 15px;
        }
        .config-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 5px solid #667eea;
        }
        .config-title {
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
            font-size: 18px;
        }
        .config-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .config-item:last-child { border-bottom: none; }
        .config-label {
            font-weight: 600;
            color: #555;
        }
        .config-value {
            color: #667eea;
            font-family: 'Courier New', monospace;
            font-weight: bold;
        }
        .config-value.masked {
            color: #999;
        }
        .test-form {
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            border: 2px solid #e0e0e0;
            margin-bottom: 25px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
            font-size: 15px;
        }
        input[type="email"] {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        input[type="email"]:focus {
            outline: none;
            border-color: #667eea;
        }
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 16px 32px;
            border: none;
            border-radius: 6px;
            font-size: 17px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            width: 100%;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        .result {
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-weight: 500;
            font-size: 16px;
        }
        .result.success {
            background: #d4edda;
            color: #155724;
            border: 2px solid #c3e6cb;
        }
        .result.error {
            background: #f8d7da;
            color: #721c24;
            border: 2px solid #f5c6cb;
        }
        .debug-section {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 25px;
            border-radius: 8px;
            margin-top: 25px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            overflow-x: auto;
            max-height: 600px;
            overflow-y: auto;
        }
        .debug-title {
            color: #4ec9b0;
            font-weight: bold;
            margin-bottom: 15px;
            font-size: 16px;
        }
        .debug-line {
            padding: 5px 0;
            line-height: 1.7;
            word-wrap: break-word;
        }
        .back-link {
            display: inline-block;
            margin-top: 25px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .warning-box {
            background: #fff3cd;
            border: 2px solid #ffc107;
            color: #856404;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 25px;
        }
        .warning-box strong {
            display: block;
            margin-bottom: 8px;
            font-size: 16px;
        }
        .tips-section {
            background: #e7f3ff;
            border: 2px solid #2196F3;
            color: #0d47a1;
            padding: 20px;
            border-radius: 6px;
            margin-top: 25px;
        }
        .tips-section h3 {
            margin-bottom: 15px;
            color: #0d47a1;
        }
        .tips-section ul {
            margin-left: 20px;
        }
        .tips-section li {
            margin: 8px 0;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Test de Conexión SMTP</h1>
        <p class="subtitle">Herramienta de diagnóstico para verificar y solucionar problemas de configuración SMTP</p>

        <div class="config-section">
            <div class="config-title">📋 Configuración Actual (mail_config.php)</div>
            <div class="config-item">
                <span class="config-label">Servidor SMTP:</span>
                <span class="config-value"><?= htmlspecialchars($smtp['host']) ?></span>
            </div>
            <div class="config-item">
                <span class="config-label">Puerto:</span>
                <span class="config-value"><?= htmlspecialchars($smtp['port'] ?? 587) ?></span>
            </div>
            <div class="config-item">
                <span class="config-label">Seguridad:</span>
                <span class="config-value"><?= strtoupper(htmlspecialchars($smtp['secure'] ?? 'tls')) ?></span>
            </div>
            <div class="config-item">
                <span class="config-label">Usuario SMTP:</span>
                <span class="config-value"><?= htmlspecialchars($smtp['user']) ?></span>
            </div>
            <div class="config-item">
                <span class="config-label">Contraseña:</span>
                <span class="config-value masked">••••••••••••••••</span>
            </div>
            <div class="config-item">
                <span class="config-label">Email Remitente (From):</span>
                <span class="config-value"><?= htmlspecialchars($from['email']) ?></span>
            </div>
            <div class="config-item">
                <span class="config-label">Nombre Remitente:</span>
                <span class="config-value"><?= htmlspecialchars($from['name']) ?></span>
            </div>
        </div>

        <div class="warning-box">
            <strong>⚠️ Advertencia</strong>
            Esta herramienta enviará un correo real al destinatario que especifiques. 
            Asegúrate de usar una dirección de correo válida que puedas verificar.
        </div>

        <form method="POST" class="test-form">
            <div class="form-group">
                <label for="test_email">📧 Email de destino para la prueba</label>
                <input 
                    type="email" 
                    id="test_email" 
                    name="test_email" 
                    placeholder="tu-email@ejemplo.com" 
                    value="<?= htmlspecialchars($test_email) ?>"
                    required
                    autofocus
                >
            </div>
            <button type="submit" class="btn">🚀 Enviar Correo de Prueba</button>
        </form>

        <?php if ($test_result): ?>
        <div class="result <?= $test_result['success'] ? 'success' : 'error' ?>">
            <?= $test_result['message'] ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($debug_output)): ?>
        <div class="debug-section">
            <div class="debug-title">🔍 SMTP Debug Log (Comunicación detallada con el servidor)</div>
            <?php foreach ($debug_output as $line): ?>
            <div class="debug-line"><?= $line ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="tips-section">
            <h3>💡 Soluciones Comunes para Error de Autenticación SMTP</h3>
            <ul>
                <li><strong>Credenciales incorrectas:</strong> Verifica que el usuario y contraseña en mail_config.php sean correctos</li>
                <li><strong>Contraseña de aplicación requerida:</strong> IONOS puede requerir una "contraseña de aplicación" en lugar de tu contraseña normal</li>
                <li><strong>Autenticación de dos factores (2FA):</strong> Si tienes 2FA activo, necesitas generar una contraseña específica para aplicaciones</li>
                <li><strong>Puerto bloqueado:</strong> Verifica que tu hosting permita conexiones salientes por el puerto 587</li>
                <li><strong>IP no autorizada:</strong> Algunos proveedores requieren autorizar IPs específicas para envío SMTP</li>
                <li><strong>Límites de envío:</strong> Verifica que no hayas excedido los límites de envío de tu plan</li>
            </ul>
        </div>

        <a href="estado_envios.php" class="back-link">← Volver al estado de envíos</a>
    </div>
</body>
</html>
