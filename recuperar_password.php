<?php
session_start();

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
            <h1>Recuperar Contraseña</h1>
            <p>Ingresa tu correo electrónico registrado</p>
        </div>

        <?php if ($mensaje === 'enviado'): ?>
            <div class="error-message" style="background-color: #c6f6d5; color: #22543d; border-left-color: #38a169;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 8px;">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                Se ha enviado un enlace de recuperación a tu correo electrónico.
            </div>
            <div class="back-link" style="text-align: center; margin: 10px 0;">
                <a href="index.php">← Volver al inicio de sesión</a>
            </div>
        <?php elseif ($error): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($mensaje !== 'enviado'): ?>
        <form method="POST" action="procesar_recuperacion.php" class="login-form">
            <div class="form-group">
                <label for="email">Correo Electrónico</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    required 
                    autocomplete="email"
                    placeholder="usuario@ejemplo.com"
                >
            </div>

            <button type="submit" class="btn-login">Enviar enlace de recuperación</button>
        </form>

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
