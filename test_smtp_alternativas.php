<?php
// test_smtp_alternativas.php - Probar diferentes combinaciones de configuración SMTP
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    die('PHPMailer no instalado');
}
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$config = include 'mail_config.php';
$smtp = $config['smtp'];
$from = $config['from'];

// Definir alternativas a probar
$alternativas = [
    [
        'nombre' => 'Configuración Actual (Email completo)',
        'user' => 'rcalatayud@hosturjaen.es',
        'port' => 587,
        'secure' => 'tls'
    ],
    [
        'nombre' => 'Solo nombre de usuario (sin @dominio)',
        'user' => 'rcalatayud',
        'port' => 587,
        'secure' => 'tls'
    ],
    [
        'nombre' => 'Puerto 465 con SSL',
        'user' => 'rcalatayud@hosturjaen.es',
        'port' => 465,
        'secure' => 'ssl'
    ],
    [
        'nombre' => 'Servidor smtp.ionos.com',
        'user' => 'rcalatayud@hosturjaen.es',
        'port' => 587,
        'secure' => 'tls',
        'host' => 'smtp.ionos.com'
    ],
];

$test_email = $_POST['test_email'] ?? '';
$resultados = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $test_email) {
    foreach ($alternativas as $idx => $alt) {
        $mail = new PHPMailer(true);
        $debug_output = [];
        
        try {
            $mail->CharSet = 'UTF-8';
            $mail->isSMTP();
            $mail->Host = $alt['host'] ?? $smtp['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $alt['user'];
            $mail->Password = $smtp['pass'];
            $mail->SMTPSecure = $alt['secure'];
            $mail->Port = $alt['port'];
            $mail->Timeout = 10;
            
            $mail->SMTPDebug = 0; // Sin debug para ir más rápido
            
            $mail->setFrom($from['email'], $from['name']);
            $mail->addAddress($test_email);
            $mail->isHTML(true);
            $mail->Subject = 'Test ' . ($idx + 1) . ' - ' . $alt['nombre'];
            $mail->Body = '<p>Esta configuración funcionó: <strong>' . htmlspecialchars($alt['nombre']) . '</strong></p>';
            
            $mail->send();
            
            $resultados[] = [
                'config' => $alt,
                'success' => true,
                'message' => '✅ ÉXITO'
            ];
        } catch (Exception $e) {
            $resultados[] = [
                'config' => $alt,
                'success' => false,
                'message' => '❌ ' . $e->getMessage()
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Probar Alternativas SMTP</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        h1 { color: #333; margin-bottom: 10px; }
        .subtitle { color: #666; margin-bottom: 30px; }
        .form-group {
            margin-bottom: 25px;
        }
        label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }
        input[type="email"] {
            width: 100%;
            padding: 14px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 16px;
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
            width: 100%;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        .results {
            margin-top: 30px;
        }
        .result-item {
            background: #f8f9fa;
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 8px;
            border-left: 5px solid #ddd;
        }
        .result-item.success {
            border-left-color: #4CAF50;
            background: #e8f5e9;
        }
        .result-item.error {
            border-left-color: #f44336;
            background: #ffebee;
        }
        .config-name {
            font-weight: bold;
            font-size: 18px;
            margin-bottom: 10px;
        }
        .config-details {
            font-family: 'Courier New', monospace;
            font-size: 13px;
            color: #555;
            margin-top: 8px;
        }
        .message {
            margin-top: 10px;
            padding: 10px;
            background: white;
            border-radius: 4px;
            font-size: 14px;
        }
        .warning {
            background: #fff3cd;
            border: 2px solid #ffc107;
            color: #856404;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 25px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔬 Probar Configuraciones SMTP Alternativas</h1>
        <p class="subtitle">Esta herramienta probará diferentes combinaciones de configuración SMTP para encontrar la correcta</p>

        <div class="warning">
            <strong>⚠️ Importante:</strong> Esta herramienta probará 4 configuraciones diferentes. 
            Cada una intentará enviar un correo de prueba. Solo las configuraciones exitosas enviarán correo.
        </div>

        <form method="POST">
            <div class="form-group">
                <label for="test_email">📧 Tu email para recibir las pruebas</label>
                <input 
                    type="email" 
                    id="test_email" 
                    name="test_email" 
                    placeholder="tu-email@ejemplo.com" 
                    value="<?= htmlspecialchars($test_email) ?>"
                    required
                >
            </div>
            <button type="submit" class="btn">🚀 Probar Todas las Configuraciones</button>
        </form>

        <?php if (!empty($resultados)): ?>
        <div class="results">
            <h2 style="margin-bottom: 20px;">📊 Resultados de las Pruebas</h2>
            
            <?php foreach ($resultados as $idx => $resultado): ?>
            <div class="result-item <?= $resultado['success'] ? 'success' : 'error' ?>">
                <div class="config-name">
                    <?= ($idx + 1) ?>. <?= htmlspecialchars($resultado['config']['nombre']) ?>
                </div>
                <div class="config-details">
                    Usuario: <?= htmlspecialchars($resultado['config']['user']) ?><br>
                    Servidor: <?= htmlspecialchars($resultado['config']['host'] ?? $smtp['host']) ?><br>
                    Puerto: <?= $resultado['config']['port'] ?> / <?= strtoupper($resultado['config']['secure']) ?>
                </div>
                <div class="message">
                    <?= htmlspecialchars($resultado['message']) ?>
                </div>
                
                <?php if ($resultado['success']): ?>
                <div style="margin-top: 15px; padding: 15px; background: #4CAF50; color: white; border-radius: 5px;">
                    <strong>✅ ¡ESTA CONFIGURACIÓN FUNCIONA!</strong><br>
                    Copia esta configuración a tus archivos mail_config.php
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            
            <?php 
            $exitosas = array_filter($resultados, fn($r) => $r['success']);
            if (empty($exitosas)):
            ?>
            <div style="background: #fff3cd; padding: 20px; border-radius: 8px; border: 2px solid #ffc107; margin-top: 20px;">
                <strong>⚠️ Ninguna configuración funcionó</strong><br><br>
                Posibles causas:
                <ul style="margin: 10px 0 0 20px;">
                    <li>La contraseña es incorrecta</li>
                    <li>Necesitas una Contraseña de Aplicación de IONOS</li>
                    <li>Tu cuenta está bloqueada o suspendida</li>
                    <li>Has excedido los límites de envío</li>
                </ul>
                <br>
                <strong>Siguiente paso:</strong> Contacta al soporte de IONOS (900 102 413)
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div style="margin-top: 30px;">
            <a href="test_smtp.php" style="color: #667eea; text-decoration: none; font-weight: 600;">← Volver al test simple</a>
        </div>
    </div>
</body>
</html>
