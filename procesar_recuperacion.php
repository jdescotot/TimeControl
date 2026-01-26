<?php
session_start();
require_once 'config.php';
require_once 'key.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: recuperar_password.php');
    exit;
}

$email = trim($_POST['email'] ?? '');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: recuperar_password.php?error=' . urlencode('Ingresa un correo electrónico válido'));
    exit;
}

try {
    // Buscar usuario por email o correo
    $stmt = $pdo->prepare("SELECT id, username, email, correo FROM usuarios WHERE email = ? OR correo = ?");
    $stmt->execute([$email, $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // Por seguridad, no revelamos si el email existe o no
        header('Location: recuperar_password.php?mensaje=enviado');
        exit;
    }

    // Generar token único
    $token = bin2hex(random_bytes(32));
    $expiracion = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Guardar token en la base de datos
    $stmt = $pdo->prepare("UPDATE usuarios SET reset_token = ?, reset_token_expira = ? WHERE id = ?");
    $stmt->execute([$token, $expiracion, $user['id']]);

    // Enviar correo
    $enlace = "https://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/restablecer_password.php?token=" . $token;
    
    $asunto = "Recuperación de contraseña - Control Horario";
    $mensaje_correo = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { background: #f7fafc; padding: 30px; border-radius: 0 0 8px 8px; }
            .btn { display: inline-block; background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; margin: 20px 0; }
            .footer { text-align: center; margin-top: 20px; color: #718096; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Recuperación de Contraseña</h1>
            </div>
            <div class='content'>
                <p>Hola <strong>" . htmlspecialchars($user['username']) . "</strong>,</p>
                <p>Recibimos una solicitud para restablecer tu contraseña en el sistema de Control Horario.</p>
                <p>Haz clic en el siguiente botón para crear una nueva contraseña:</p>
                <p style='text-align: center;'>
                    <a href='" . $enlace . "' class='btn'>Restablecer Contraseña</a>
                </p>
                <p>O copia y pega este enlace en tu navegador:</p>
                <p style='word-break: break-all; background: white; padding: 10px; border-radius: 4px; font-size: 12px;'>" . $enlace . "</p>
                <p><strong>Este enlace expirará en 1 hora.</strong></p>
                <p>Si no solicitaste este cambio, puedes ignorar este correo de forma segura.</p>
            </div>
            <div class='footer'>
                <p>Sistema de Control Horario</p>
            </div>
        </div>
    </body>
    </html>
    ";

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Control Horario <noreply@" . $_SERVER['HTTP_HOST'] . ">" . "\r\n";

    // Intentar enviar con PHPMailer si está disponible, sino usar mail()
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        require 'vendor/autoload.php';
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            // Configuración SMTP (ajustar según tu servidor)
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; // Cambiar por tu servidor SMTP
            $mail->SMTPAuth = true;
            $mail->Username = EMAIL_USERNAME; // Definir en key.php
            $mail->Password = EMAIL_PASSWORD; // Definir en key.php
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';
            
            $mail->setFrom('noreply@' . $_SERVER['HTTP_HOST'], 'Control Horario');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body = $mensaje_correo;
            
            $mail->send();
        } catch (Exception $e) {
            error_log("Error enviando email con PHPMailer: " . $e->getMessage());
            // Fallback a mail() nativo
            mail($email, $asunto, $mensaje_correo, $headers);
        }
    } else {
        // Usar función mail() nativa de PHP
        mail($email, $asunto, $mensaje_correo, $headers);
    }

    header('Location: recuperar_password.php?mensaje=enviado');
    exit;

} catch (Exception $e) {
    error_log("Error en recuperación de contraseña: " . $e->getMessage());
    header('Location: recuperar_password.php?error=' . urlencode('Error al procesar la solicitud. Intenta de nuevo.'));
    exit;
}
