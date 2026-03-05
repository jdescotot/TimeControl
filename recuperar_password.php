<?php
session_start();

// Headers de seguridad
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");

// Si ya está logueado, redirigir a su panel
if (isset($_SESSION['user_id'])) {
    $rol = $_SESSION['rol'] ?? 'empleado';
    if ($rol === 'dueño') {
        header('Location: dueño.php');
    } elseif ($rol === 'hacienda') {
        header('Location: hacienda.php');
    } else {
        header('Location: empleado.php');
    }
    exit;
}

$mensaje = $_GET['mensaje'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - Control Horario</title>
    <link rel="stylesheet" href="index.css">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🔐 Recuperar Contraseña</h1>
            <p>Ingresa tu correo electrónico registrado</p>
        </div>

        <?php if ($mensaje === 'enviado'): ?>
            <div class="error-message" style="background-color: #c6f6d5; color: #22543d; border-left-color: #38a169;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 8px;">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <div>
                    <strong>✓ Solicitud recibida</strong><br>
                    Si el correo existe, recibirás un enlace de recuperación en los próximos minutos.
                    <br><br>
                    <strong>📧 Revisa tu bandeja de entrada</strong> (y también la carpeta de spam).
                </div>
            </div>
            <div class="back-link" style="text-align: center; margin: 10px 0;">
                <a href="index.php">← Volver al inicio de sesión</a>
            </div>
        <?php elseif ($error): ?>
            <div class="error-message">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 8px;">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($mensaje !== 'enviado'): ?>
        <form method="POST" action="procesar_recuperacion.php" class="login-form">
            <div class="form-group">
                <label for="username">Usuario (Opcional)</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    autocomplete="username"
                    placeholder="Tu nombre de usuario"
                >
                <small style="color: #718096; font-size: 13px; display: block; margin-top: 6px;">
                    Si ingresas el usuario, se enviará al email registrado
                </small>
            </div>

            <div style="text-align: center; margin: 15px 0; color: #a0aec0; font-weight: 600;">— O —</div>

            <div class="form-group">
                <label for="email">Correo Electrónico (Opcional)</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    autocomplete="email"
                    placeholder="usuario@ejemplo.com"
                >
                <small style="color: #718096; font-size: 13px; display: block; margin-top: 6px;">
                    O ingresa tu correo registrado
                </small>
            </div>

            <button type="submit" class="btn-login">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 8px;">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                    <polyline points="22,6 12,13 2,6"></polyline>
                </svg>
                Enviar enlace de recuperación
            </button>
        </form>

        <div style="background: #fffaf0; border-left: 4px solid #ed8936; padding: 15px; border-radius: 8px; margin-top: 20px; font-size: 13px;">
            <strong>⚠️ Nota de seguridad:</strong><br>
            • El enlace expirará en <strong>1 hora</strong><br>
            • Solo puedes solicitar <strong>3 enlaces por hora</strong><br>
            • Ingresa al menos uno: usuario O correo electrónico
        </div>

        <div style="text-align: center; margin-top: 16px;">
            <a href="index.php" style="color: #667eea; text-decoration: none; font-size: 14px;">
                ← Volver al inicio de sesión
            </a>
        </div>
        <?php endif; ?>

        <div class="login-footer">
            <p>Sistema de Control Horario</p>
        </div>
    </div>
</body>
</html>
