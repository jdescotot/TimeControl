<?php
session_start();
require_once 'config.php';

// Headers de seguridad
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: recuperar_password.php');
    exit;
}

$email = trim($_POST['email'] ?? '');
$username = trim($_POST['username'] ?? '');
$ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

// Validación: al menos uno debe completarse
if (empty($email) && empty($username)) {
    header('Location: recuperar_password.php?error=' . urlencode('Debes ingresar usuario O correo electrónico'));
    exit;
}

// Validar email si se proporcionó
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: recuperar_password.php?error=' . urlencode('El correo electrónico no es válido'));
    exit;
}

try {
    // ===============================================
    // 1. RATE LIMITING - Prevenir spam
    // ===============================================
    
    // Limpiar intentos antiguos (más de 1 hora)
    $pdo->exec("DELETE FROM password_reset_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    
    // Verificar intentos por email
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM password_reset_attempts 
                           WHERE email = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $stmt->execute([$email]);
    $email_attempts = $stmt->fetchColumn();
    
    if ($email_attempts >= 3) {
        // Siempre redirigir con mensaje de éxito (no revelar que hay límite)
        usleep(rand(100000, 500000)); // Random delay para prevenir timing attacks
        header('Location: recuperar_password.php?mensaje=enviado');
        exit;
    }
    
    // Verificar intentos por IP
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM password_reset_attempts 
                           WHERE ip_address = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $stmt->execute([$ip_address]);
    $ip_attempts = $stmt->fetchColumn();
    
    if ($ip_attempts >= 10) {
        // Siempre redirigir con mensaje de éxito (no revelar que hay límite)
        usleep(rand(100000, 500000)); // Random delay para prevenir timing attacks
        header('Location: recuperar_password.php?mensaje=enviado');
        exit;
    }
    
    // Registrar intento
    $stmt = $pdo->prepare("INSERT INTO password_reset_attempts (email, ip_address) VALUES (?, ?)");
    $stmt->execute([$email, $ip_address]);
    
    // ===============================================
    // 2. VALIDAR EMAIL (buscar usuario por email)
    // ===============================================
    
    // Si proporciona usuario, busca por usuario
    if (!empty($username)) {
        $stmt = $pdo->prepare("SELECT id, username, email, correo, nombre 
                               FROM usuarios 
                               WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Obtener el email del usuario
            $email = $user['email'] ?? $user['correo'];
        }
    } 
    // Si no proporciona usuario pero sí email, busca por email
    else if (!empty($email)) {
        $stmt = $pdo->prepare("SELECT id, username, email, correo, nombre 
                               FROM usuarios 
                               WHERE email = ? OR correo = ?");
        $stmt->execute([$email, $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Si no encuentra usuario, mostrar éxito igual por seguridad
    if (!$user || empty($email)) {
        usleep(rand(100000, 500000));
        header('Location: recuperar_password.php?mensaje=enviado');
        exit;
    }

    // ===============================================
    // 3. INVALIDAR TOKENS PREVIOS
    // ===============================================
    
    $stmt = $pdo->prepare("UPDATE usuarios 
                           SET reset_token = NULL, reset_token_expira = NULL 
                           WHERE id = ?");
    $stmt->execute([$user['id']]);

    // ===============================================
    // 4. GENERAR NUEVO TOKEN SEGURO
    // ===============================================
    
    try {
        $token = bin2hex(random_bytes(32));
    } catch (Exception $e) {
        error_log("CSPRNG failure: " . $e->getMessage());
        header('Location: recuperar_password.php?error=' . urlencode('Error del sistema. Intenta de nuevo.'));
        exit;
    }
    
    $expiracion = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Guardar token en la base de datos
    $stmt = $pdo->prepare("UPDATE usuarios SET reset_token = ?, reset_token_expira = ? WHERE id = ?");
    $stmt->execute([$token, $expiracion, $user['id']]);
    
    // Registrar generación de token en auditoría
    $stmt = $pdo->prepare("INSERT INTO password_reset_log 
                           (user_id, username, token_generated_at, ip_address, user_agent, action) 
                           VALUES (?, ?, NOW(), ?, ?, 'token_generated')");
    $stmt->execute([
        $user['id'], 
        $user['username'], 
        $ip_address, 
        $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
    ]);

    // Registrar generación de token en auditoría
    $stmt = $pdo->prepare("INSERT INTO password_reset_log 
                           (user_id, username, token_generated_at, ip_address, user_agent, action) 
                           VALUES (?, ?, NOW(), ?, ?, 'token_generated')");
    $stmt->execute([
        $user['id'], 
        $user['username'], 
        $ip_address, 
        $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
    ]);

    // ===============================================
    // 5. PREPARAR Y ENVIAR EMAIL INMEDIATAMENTE
    // ===============================================
    
    // Cargar configuración de email
    if (!file_exists(__DIR__ . '/mail_config.php')) {
        error_log("Falta mail_config.php para envío de emails");
        header('Location: recuperar_password.php?error=' . urlencode('Error de configuración del sistema'));
        exit;
    }
    
    $mail_config = include __DIR__ . '/mail_config.php';
    $smtp = $mail_config['smtp'] ?? null;
    
    if (!$smtp) {
        error_log("Configuración SMTP no encontrada en mail_config.php");
        header('Location: recuperar_password.php?error=' . urlencode('Error de configuración SMTP'));
        exit;
    }
    
    $enlace = "https://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/restablecer_password.php?token=" . $token;
    
    $asunto = "🔐 Recuperación de contraseña - Control Horario";
    
    // Template HTML profesional
    $mensaje_correo = "
    <!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <style>
            body { 
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
                line-height: 1.6; 
                color: #2d3748; 
                background-color: #f7fafc;
                margin: 0;
                padding: 0;
            }
            .container { 
                max-width: 600px; 
                margin: 40px auto; 
                background: white;
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            }
            .header { 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                color: white; 
                padding: 40px 30px; 
                text-align: center; 
            }
            .header h1 {
                margin: 0;
                font-size: 28px;
                font-weight: 600;
            }
            .header p {
                margin: 10px 0 0 0;
                opacity: 0.9;
                font-size: 14px;
            }
            .content { 
                padding: 40px 30px; 
            }
            .content p {
                margin: 0 0 16px 0;
                color: #4a5568;
            }
            .username {
                color: #667eea;
                font-weight: 600;
            }
            .btn-container {
                text-align: center;
                margin: 30px 0;
            }
            .btn { 
                display: inline-block; 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white !important; 
                padding: 14px 40px; 
                text-decoration: none; 
                border-radius: 8px; 
                font-weight: 600;
                font-size: 16px;
                box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
                transition: transform 0.2s;
            }
            .btn:hover {
                transform: translateY(-2px);
            }
            .link-box {
                background: #f7fafc;
                padding: 15px;
                border-radius: 8px;
                border-left: 4px solid #667eea;
                word-break: break-all;
                font-size: 12px;
                color: #718096;
                margin: 20px 0;
            }
            .warning {
                background: #fff5f5;
                border-left: 4px solid #f56565;
                padding: 15px;
                border-radius: 8px;
                margin: 20px 0;
            }
            .warning strong {
                color: #c53030;
            }
            .info {
                background: #ebf8ff;
                border-left: 4px solid #4299e1;
                padding: 15px;
                border-radius: 8px;
                margin: 20px 0;
                font-size: 14px;
            }
            .footer { 
                text-align: center; 
                padding: 30px; 
                background: #f7fafc;
                color: #718096; 
                font-size: 13px; 
                border-top: 1px solid #e2e8f0;
            }
            .footer p {
                margin: 5px 0;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🔐 Recuperación de Contraseña</h1>
                <p>Control Horario - Sistema Seguro</p>
            </div>
            <div class='content'>
                <p>Hola <span class='username'>" . htmlspecialchars($user['username']) . "</span>,</p>
                
                <p>Recibimos una solicitud para restablecer la contraseña de tu cuenta en el sistema de Control Horario.</p>
                
                <div class='info'>
                    <strong>📋 Detalles de la solicitud:</strong><br>
                    • Usuario: " . htmlspecialchars($user['username']) . "<br>
                    • Email: " . htmlspecialchars($email) . "<br>
                    • Fecha: " . date('d/m/Y H:i:s') . "<br>
                    • IP: " . htmlspecialchars($ip_address) . "
                </div>
                
                <p>Haz clic en el siguiente botón para crear una nueva contraseña de forma segura:</p>
                
                <div class='btn-container'>
                    <a href='" . htmlspecialchars($enlace) . "' class='btn'>Restablecer mi contraseña</a>
                </div>
                
                <p>Si el botón no funciona, copia y pega este enlace en tu navegador:</p>
                <div class='link-box'>" . htmlspecialchars($enlace) . "</div>
                
                <div class='warning'>
                    <strong>⏰ Importante:</strong> Este enlace expirará en <strong>1 hora</strong> por seguridad.
                </div>
                
                <p><strong>🔒 Nota de seguridad:</strong> Si no solicitaste este cambio, tu cuenta puede estar en riesgo. Te recomendamos cambiar tu contraseña inmediatamente desde tu panel de usuario.</p>
                
                <p style='margin-top: 30px; color: #718096; font-size: 14px;'>
                    Este es un correo automático, por favor no respondas a este mensaje.
                </p>
            </div>
            <div class='footer'>
                <p><strong>Sistema de Control Horario</strong></p>
                <p>© " . date('Y') . " - Todos los derechos reservados</p>
            </div>
        </div>
    </body>
    </html>
    ";

    // ===============================================
    // 6. ENVIAR EMAIL INMEDIATAMENTE (NO ENCOLAR)
    // ===============================================
    
    require_once __DIR__ . '/vendor/autoload.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Configuración SMTP
        $mail->isSMTP();
        $mail->Host = $smtp['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $smtp['user'];
        $mail->Password = $smtp['pass'];
        $mail->SMTPSecure = $smtp['secure'];
        $mail->Port = $smtp['port'];
        $mail->CharSet = 'UTF-8';
        
        // Remitente y destinatario
        $mail->setFrom($smtp['user'], 'Control Horario - Recuperación');
        $mail->addAddress($email);
        
        // Contenido
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body = $mensaje_correo;
        
        // Enviar
        $mail->send();
        
        // Log de éxito
        error_log("Email de recuperación enviado exitosamente a: " . $email);
        
    } catch (Exception $e) {
        error_log("Error al enviar email de recuperación: " . $mail->ErrorInfo);
        // Aún así redirigimos con éxito para no revelar info
    }

    // Siempre mostrar mensaje de éxito (no revelar si el usuario existe)
    header('Location: recuperar_password.php?mensaje=enviado');
    exit;

} catch (Exception $e) {
    error_log("Error en recuperación de contraseña: " . $e->getMessage());
    // Por seguridad, siempre mostrar el mismo mensaje
    header('Location: recuperar_password.php?mensaje=enviado');
    exit;
}
