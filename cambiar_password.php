<?php
session_start();
require_once 'config.php';

// MODIFICACIÓN: Permitir acceso a dueños y empleados
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['rol'], ['empleado', 'dueño'])) {
    header('Location: index.php');
    exit;
}

$mensaje_error = '';
$mensaje_exito = '';

// Procesar el cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password_actual = $_POST['password_actual'] ?? '';
    $password_nueva = $_POST['password_nueva'] ?? '';
    $password_confirmar = $_POST['password_confirmar'] ?? '';

    // Validaciones
    if (empty($password_actual) || empty($password_nueva) || empty($password_confirmar)) {
        $mensaje_error = 'Todos los campos son obligatorios';
    } elseif (strlen($password_nueva) < 6) {
        $mensaje_error = 'La nueva contraseña debe tener al menos 6 caracteres';
    } elseif ($password_nueva !== $password_confirmar) {
        $mensaje_error = 'Las contraseñas nuevas no coinciden';
    } elseif ($password_actual === $password_nueva) {
        $mensaje_error = 'La nueva contraseña debe ser diferente a la actual';
    } else {
        try {
            // Verificar la contraseña actual
            $stmt = $pdo->prepare("SELECT password FROM usuarios WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();

            if ($user && password_verify($password_actual, $user['password'])) {
                // Actualizar la contraseña y quitar el flag de cambio obligatorio
                // Esta consulta ya actualiza el valor a 0, tal como solicitaste
                $password_hash = password_hash($password_nueva, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    UPDATE usuarios 
                    SET password = ?, requiere_cambio_password = 0 
                    WHERE id = ?
                ");
                $stmt->execute([$password_hash, $_SESSION['user_id']]);

                $mensaje_exito = 'Contraseña actualizada exitosamente';
                
                // MODIFICACIÓN: Redirección dinámica según el rol
                //$redirect_page = ($_SESSION['rol'] === 'dueño') ? 'dueño.php' : 'empleado.php';
                header("refresh:2;url=politica_datos.php");
            } else {
                $mensaje_error = 'La contraseña actual es incorrecta';
            }
        } catch (Exception $e) {
            error_log("Error al cambiar contraseña: " . $e->getMessage());
            $mensaje_error = 'Error al cambiar la contraseña. Por favor, intenta de nuevo.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Contraseña - Control Horario</title>
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
                    <span>Cambiar Contraseña</span>
                </div>
                <div class="user-info">
                    <span class="welcome-text">Usuario</span>
                    <span class="username"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </div>
            </div>
        </header>

        <main class="main-content">
            <div class="card">
                <div class="card-header">
                    <h2>Actualizar Contraseña</h2>
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
                    <?php endif; ?>

                    <?php if ($mensaje_exito): ?>
                    <div class="status-message success">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                        <span><?php echo htmlspecialchars($mensaje_exito); ?>. Redirigiendo...</span>
                    </div>
                    <?php endif; ?>

                    <?php if (!$mensaje_exito): ?>
                    <div class="password-requirements">
                        <h4>📋 Requisitos de la contraseña:</h4>
                        <ul>
                            <li>Mínimo 6 caracteres</li>
                            <li>Debe ser diferente a la contraseña actual</li>
                            <li>Recomendado: combinar letras, números y símbolos</li>
                        </ul>
                    </div>

                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="password_actual">Contraseña Actual:</label>
                            <div style="position: relative;">
                                <input type="password" name="password_actual" id="password_actual" required 
                                       placeholder="Tu contraseña temporal actual"
                                       autocomplete="current-password"
                                       style="padding-right: 45px;">
                                <button type="button" onclick="togglePassword('password_actual', this)" 
                                    style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; padding: 5px; color: #718096;"
                                    aria-label="Mostrar contraseña">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password_nueva">Nueva Contraseña:</label>
                            <div style="position: relative;">
                                <input type="password" name="password_nueva" id="password_nueva" required 
                                       placeholder="Mínimo 6 caracteres"
                                       minlength="6"
                                       autocomplete="new-password"
                                       style="padding-right: 45px;">
                                <button type="button" onclick="togglePassword('password_nueva', this)" 
                                    style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; padding: 5px; color: #718096;"
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
                            <div style="position: relative;">
                                <input type="password" name="password_confirmar" id="password_confirmar" required 
                                       placeholder="Repite la nueva contraseña"
                                       minlength="6"
                                       autocomplete="new-password"
                                       style="padding-right: 45px;">
                                <button type="button" onclick="togglePassword('password_confirmar', this)" 
                                    style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; padding: 5px; color: #718096;"
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
            <a href="logout.php" class="logout-link">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                Cerrar Sesión
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