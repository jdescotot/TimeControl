<?php
session_start();
require_once 'config.php';

// Headers de seguridad
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");

$token = $_GET['token'] ?? '';
$mensaje_error = '';
$mensaje_exito = '';
$token_valido = false;
$user = null;
$ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

if (empty($token)) {
    $mensaje_error = 'Token de recuperación inválido. Por favor solicita un nuevo enlace.';
} else {
    // Verificar que el token existe y no ha expirado
    $stmt = $pdo->prepare("
        SELECT id, username, email, correo, nombre, reset_token_expira 
        FROM usuarios 
        WHERE reset_token = ? AND reset_token_expira > NOW()
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $mensaje_error = 'El enlace de recuperación ha expirado o es inválido. Los enlaces expiran después de 1 hora por seguridad.';
    } else {
        $token_valido = true;
    }
}

// Procesar el cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valido) {
    $password_nueva = $_POST['password_nueva'] ?? '';
    $password_confirmar = $_POST['password_confirmar'] ?? '';

    if (empty($password_nueva) || empty($password_confirmar)) {
        $mensaje_error = 'Todos los campos son obligatorios';
    } elseif (strlen($password_nueva) < 6) {
        $mensaje_error = 'La contraseña debe tener al menos 6 caracteres';
    } elseif (strpos($password_nueva, ' ') !== false) {
        $mensaje_error = 'La contraseña no puede contener espacios';
    } elseif ($password_nueva !== $password_confirmar) {
        $mensaje_error = 'Las contraseñas no coinciden';
    } else {
        try {
            $password_hash = password_hash($password_nueva, PASSWORD_DEFAULT);
            
            // Actualizar contraseña y limpiar token
            $stmt = $pdo->prepare("
                UPDATE usuarios 
                SET password = ?, 
                    reset_token = NULL, 
                    reset_token_expira = NULL,
                    requiere_cambio_password = 0
                WHERE reset_token = ?
            ");
            $stmt->execute([$password_hash, $token]);

            // ===============================================
            // REGISTRAR EN AUDITORÍA
            // ===============================================
            $stmt = $pdo->prepare("INSERT INTO password_reset_log 
                (user_id, username, token_used_at, ip_address, user_agent, success, action) 
                VALUES (?, ?, NOW(), ?, ?, 1, 'password_changed')");
            $stmt->execute([
                $user['id'], 
                $user['username'], 
                $ip_address, 
                $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            ]);

            // ===============================================
            // ENVIAR EMAIL DE NOTIFICACIÓN DE CAMBIO
            // ===============================================
            $email_destino = $user['email'] ?? $user['correo'] ?? null;
            
            if ($email_destino) {
                $asunto_notif = "🔐 Tu contraseña ha sido cambiada - Control Horario";
                
                $mensaje_notif = "
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
                            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%); 
                            color: white; 
                            padding: 40px 30px; 
                            text-align: center; 
                        }
                        .header h1 {
                            margin: 0;
                            font-size: 28px;
                            font-weight: 600;
                        }
                        .content { 
                            padding: 40px 30px; 
                        }
                        .content p {
                            margin: 0 0 16px 0;
                            color: #4a5568;
                        }
                        .username {
                            color: #38a169;
                            font-weight: 600;
                        }
                        .info {
                            background: #f0fff4;
                            border-left: 4px solid #48bb78;
                            padding: 15px;
                            border-radius: 8px;
                            margin: 20px 0;
                            font-size: 14px;
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
                        }
                        .footer { 
                            text-align: center; 
                            padding: 30px; 
                            background: #f7fafc;
                            color: #718096; 
                            font-size: 13px; 
                            border-top: 1px solid #e2e8f0;
                        }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1>✅ Contraseña Actualizada</h1>
                        </div>
                        <div class='content'>
                            <p>Hola <span class='username'>" . htmlspecialchars($user['username']) . "</span>,</p>
                            
                            <p>Te confirmamos que la contraseña de tu cuenta en el sistema de Control Horario <strong>ha sido cambiada exitosamente</strong>.</p>
                            
                            <div class='info'>
                                <strong>📋 Detalles del cambio:</strong><br>
                                • Usuario: " . htmlspecialchars($user['username']) . "<br>
                                • Fecha y hora: " . date('d/m/Y H:i:s') . "<br>
                                • Dirección IP: " . htmlspecialchars($ip_address) . "
                            </div>
                            
                            <p>Ya puedes iniciar sesión con tu nueva contraseña:</p>
                            
                            <div class='btn-container'>
                                <a href='https://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/index.php' class='btn'>Ir al inicio de sesión</a>
                            </div>
                            
                            <div class='warning'>
                                <strong>⚠️ ¿No fuiste tú?</strong><br>
                                Si no realizaste este cambio, tu cuenta puede estar comprometida. Contacta inmediatamente con el administrador del sistema y solicita un nuevo cambio de contraseña.
                            </div>
                            
                            <p style='margin-top: 30px; color: #718096; font-size: 14px;'>
                                Este es un correo automático de seguridad. Por favor no respondas a este mensaje.
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

                // Encolar email de notificación
                $recipient_name = !empty($user['nombre']) ? $user['nombre'] : $user['username'];
                
                $stmt = $pdo->prepare("INSERT INTO email_queue 
                    (recipient_email, recipient_name, subject, body, status, attempts, created_at) 
                    VALUES (?, ?, ?, ?, 'queued', 0, NOW())");
                
                $stmt->execute([
                    $email_destino,
                    $recipient_name,
                    $asunto_notif,
                    $mensaje_notif
                ]);
            }

            $mensaje_exito = 'Contraseña actualizada exitosamente. Redirigiendo al inicio de sesión...';
            
            // Regenerar ID de sesión por seguridad
            session_regenerate_id(true);
            
            header("refresh:3;url=index.php");
        } catch (Exception $e) {
            error_log("Error al restablecer contraseña: " . $e->getMessage());
            $mensaje_error = 'Error al actualizar la contraseña. Intenta de nuevo.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - Control Horario</title>
    <link rel="stylesheet" href="empleado.css">
    <style>
        .password-container {
            max-width: 500px;
            margin: 0 auto;
        }
        
        .password-requirements {
            background: #f7fafc;
            border-left: 4px solid #667eea;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .password-requirements h4 {
            margin: 0 0 10px 0;
            color: #2d3748;
            font-size: 16px;
        }
        
        .password-requirements ul {
            margin: 0;
            padding-left: 20px;
            color: #4a5568;
            font-size: 14px;
        }
        
        .password-requirements li {
            margin: 5px 0;
        }

        .password-wrapper {
            position: relative;
        }

        .password-wrapper input {
            padding-right: 45px;
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 5px;
            color: #718096;
        }
    </style>
</head>
<body>
    <div class="container password-container">
        <header class="header">
            <div class="header-content">
                <div class="logo">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                    <span>Restablecer Contraseña</span>
                </div>
            </div>
        </header>

        <main class="main-content">
            <div class="card">
                <div class="card-header">
                    <h2>Nueva Contraseña</h2>
                </div>
                <div class="card-body">
                    <?php if ($mensaje_error): ?>
                    <div class="status-message" style="background-color: #fed7d7; color: #c53030; border-left-color: #e53e3e;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <span><?php echo htmlspecialchars($mensaje_error); ?></span>
                    </div>
                    <div style="text-align: center; margin-top: 16px;">
                        <a href="recuperar_password.php" style="color: #667eea; text-decoration: none;">
                            ← Solicitar nuevo enlace
                        </a>
                    </div>
                    <?php endif; ?>

                    <?php if ($mensaje_exito): ?>
                    <div class="status-message success">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                        <span><?php echo htmlspecialchars($mensaje_exito); ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if ($token_valido && !$mensaje_exito): ?>
                    <div class="password-requirements">
                        <h4>📋 Requisitos de la contraseña:</h4>
                        <ul>
                            <li>Mínimo 6 caracteres</li>
                            <li>No debe contener espacios</li>
                            <li>Recomendado: combinar letras, números y símbolos</li>
                        </ul>
                    </div>

                    <form method="POST" action="">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                        <div class="form-group">
                            <label for="password_nueva">Nueva Contraseña:</label>
                            <div class="password-wrapper">
                                <input type="password" name="password_nueva" id="password_nueva" required 
                                       placeholder="Mínimo 6 caracteres"
                                       minlength="6"
                                       autocomplete="new-password">
                                <button type="button" class="toggle-password" onclick="togglePassword('password_nueva', this)" 
                                    aria-label="Mostrar contraseña">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password_confirmar">Confirmar Nueva Contraseña:</label>
                            <div class="password-wrapper">
                                <input type="password" name="password_confirmar" id="password_confirmar" required 
                                       placeholder="Repite la nueva contraseña"
                                       minlength="6"
                                       autocomplete="new-password">
                                <button type="button" class="toggle-password" onclick="togglePassword('password_confirmar', this)" 
                                    aria-label="Mostrar contraseña">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div style="display: flex; gap: 12px; margin-top: 24px;">
                            <button type="submit" class="btn btn-primary" style="flex: 1;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                    <polyline points="7 3 7 8 15 8"></polyline>
                                </svg>
                                Actualizar Contraseña
                            </button>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </main>

        <footer class="footer">
            <a href="index.php" class="logout-link">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                Volver al inicio de sesión
            </a>
        </footer>
    </div>

    <script>
    function togglePassword(inputId, button) {
        const input = document.getElementById(inputId);
        const svg = button.querySelector('svg');
        
        if (input.type === 'password') {
            input.type = 'text';
            svg.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
        } else {
            input.type = 'password';
            svg.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
        }
    }
    </script>
</body>
</html>
