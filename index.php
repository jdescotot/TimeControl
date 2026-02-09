<?php
session_start();

$rawUsername = '';

// Solo procesamos si es POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config.php';
    
    $rawUsername = trim($_POST['username'] ?? '');
    // Normalizamos: sin espacios y en minúsculas para el login
    $username = strtolower(preg_replace('/\s+/', '', $rawUsername));
    // mostramos la versión normalizada en el formulario
    $rawUsername = $username;
    $password = $_POST['password'] ?? ''; 

    // Validación: contraseña no debe tener espacios
    if (strpos($password, ' ') !== false) {
        $error = 'La contraseña no puede contener espacios';
    } else {
        // Búsqueda case-insensitive de usuario
        $stmt = $pdo->prepare("SELECT id, username, password, rol, requiere_cambio_password FROM usuarios WHERE LOWER(username) = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = strtolower($user['username']);
            $_SESSION['rol'] = $user['rol'];

            // Si requiere cambio de contraseña, redirigir a cambiar_password.php
            if ($user['requiere_cambio_password'] == 1) {
                header('Location: cambiar_password.php');
                exit;
            }

            // Si no requiere cambio, ir a su panel normal según el rol
            if ($user['rol'] === 'dueño') {
                header('Location: dueño.php');
                exit;
            } elseif ($user['rol'] === 'hacienda') {
                header('Location: hacienda.php');
                exit;
            } else {
                header('Location: empleado.php');
                exit;
            }
        } else {
            $error = 'Usuario o contraseña incorrectos';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso - Control Horario</title>
    <link rel="stylesheet" href="index.css">
    <style>
        .password-wrapper { position: relative; display: block; }
        .password-wrapper input { width: 100%; padding-right: 48px; box-sizing: border-box; }
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px;
            display: flex;
            align-items: center;
            color: #718096;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Control Horario</h1>
            <p>Acceso al sistema de gestión</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <div class="back-link" style="text-align: center; margin: 10px 0;">
                <a href="index.php">← Volver al inicio</a>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="login-form">
            <div class="form-group">
                <label for="username">Usuario</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    required 
                    autocomplete="username"
                    placeholder="Ingresa tu usuario"
                    oninput="this.value = this.value.toLowerCase().replace(/\s+/g, '')"
                    style="text-transform:lowercase"
                    value="<?php echo htmlspecialchars($rawUsername ?? ''); ?>"
                >
            </div>

            <div class="form-group">
                <label for="password">Contraseña</label>
                <div class="password-wrapper">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required 
                        autocomplete="current-password"
                        placeholder="Ingresa tu contraseña"
                    >
                    <button type="button" class="toggle-password" onclick="togglePassword('password', this)" aria-label="Mostrar contraseña">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-login">Iniciar Sesión</button>
        </form>

        <div style="text-align: center; margin-top: 16px;">
            <a href="recuperar_password.php" style="color: #667eea; text-decoration: none; font-size: 14px; transition: color 0.2s;">
                ¿Olvidaste tu contraseña?
            </a>
        </div>

        <div class="login-footer">
            <p>Sistema de Control Horario</p>
        </div>
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