<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config.php';

$mensaje_exito = null;
$mensaje_error = null;
$paso_actual = 1;

if (isset($_SESSION['reset_user_id'])) {
    $paso_actual = 2;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['paso']) && $_POST['paso'] == '1') {
        $usuario = trim($_POST['usuario'] ?? '');
        
        if (empty($usuario)) {
            $mensaje_error = 'Por favor completa el usuario';
            $paso_actual = 1;
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id, username FROM usuarios WHERE LOWER(username) = LOWER(?) LIMIT 1");
                $stmt->execute([$usuario]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    $_SESSION['reset_user_id'] = $user['id'];
                    $_SESSION['reset_usuario'] = $user['username'];
                    $paso_actual = 2;
                    $mensaje_exito = '✓ Usuario verificado. Ingresa tu nueva contraseña.';
                } else {
                    $mensaje_error = 'No se encontró ese usuario.';
                    $paso_actual = 1;
                }
            } catch (PDOException $e) {
                $mensaje_error = 'Error: ' . $e->getMessage();
                $paso_actual = 1;
            }
        }
    }
    
    if (isset($_POST['paso']) && $_POST['paso'] == '2') {
        if (!isset($_SESSION['reset_user_id'])) {
            $mensaje_error = 'Sesión expirada.';
            unset($_SESSION['reset_user_id'], $_SESSION['reset_usuario']);
            $paso_actual = 1;
        } else {
            $password_nueva = $_POST['password_nueva'] ?? '';
            $password_repetir = $_POST['password_repetir'] ?? '';
            $usuario_id = $_SESSION['reset_user_id'];
            
            if (empty($password_nueva) || empty($password_repetir)) {
                $mensaje_error = 'Por favor completa ambos campos.';
                $paso_actual = 2;
            } elseif (strlen($password_nueva) < 6) {
                $mensaje_error = 'Mínimo 6 caracteres.';
                $paso_actual = 2;
            } elseif ($password_nueva !== $password_repetir) {
                $mensaje_error = 'Las contraseñas no coinciden.';
                $paso_actual = 2;
            } else {
                try {
                    $password_hash = password_hash($password_nueva, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
                    $stmt->execute([$password_hash, $usuario_id]);
                    
                    unset($_SESSION['reset_user_id'], $_SESSION['reset_usuario']);
                    $mensaje_exito = '✓ Contraseña actualizada. Inicia sesión ahora.';
                    $paso_actual = 1;
                } catch (PDOException $e) {
                    $mensaje_error = 'Error: ' . $e->getMessage();
                    $paso_actual = 2;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resetear Contraseña - TimeControl</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 450px;
            padding: 40px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 28px;
            color: #333;
            margin-bottom: 10px;
        }

        .header p {
            color: #666;
            font-size: 14px;
        }

        .icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        /* Alertas */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-icon {
            font-size: 18px;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .alert-content {
            flex: 1;
        }

        .alert strong {
            display: block;
            margin-bottom: 3px;
        }

        /* Pasos */
        .steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
        }

        .steps::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 2px;
            background: #e0e0e0;
            z-index: 0;
        }

        .step {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
        }

        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e0e0e0;
            color: #999;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-bottom: 8px;
            transition: all 0.3s ease;
        }

        .step.active .step-number {
            background: #667eea;
            color: white;
        }

        .step.completed .step-number {
            background: #28a745;
            color: white;
        }

        .step-label {
            font-size: 12px;
            color: #999;
            text-align: center;
        }

        .step.active .step-label {
            color: #667eea;
            font-weight: bold;
        }

        .step.completed .step-label {
            color: #28a745;
        }

        /* Formulario */
        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
            background: #f8f9ff;
        }

        input::placeholder {
            color: #aaa;
        }

        /* Botones */
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }

        button, .btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            width: 100%;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-secondary {
            background: #f0f0f0;
            color: #333;
            border: 2px solid #e0e0e0;
        }

        .btn-secondary:hover {
            background: #e8e8e8;
        }

        /* Password strength indicator */
        .password-strength {
            margin-top: 8px;
            font-size: 12px;
            color: #666;
        }

        .strength-bar {
            height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
            margin-top: 4px;
            overflow: hidden;
        }

        .strength-fill {
            height: 100%;
            width: 0%;
            background: #dc3545;
            transition: all 0.3s ease;
        }

        .strength-fill.medium {
            width: 50%;
            background: #ffc107;
        }

        .strength-fill.strong {
            width: 100%;
            background: #28a745;
        }

        /* Link de volver */
        .back-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }

        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        /* Tooltip de requisitos */
        .requirements {
            background: #f8f9ff;
            padding: 12px;
            border-radius: 6px;
            margin-top: 15px;
            font-size: 13px;
            color: #666;
            border-left: 4px solid #667eea;
        }

        .requirements ul {
            margin: 0;
            padding-left: 20px;
            margin-top: 8px;
        }

        .requirements li {
            margin-bottom: 4px;
        }

        .form-step {
            display: none;
        }

        .form-step.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .success-icon {
            font-size: 60px;
            text-align: center;
            margin-bottom: 20px;
            animation: bounce 0.6s ease;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        /* Responsive */
        @media (max-width: 480px) {
            .container {
                padding: 30px 20px;
            }

            .header h1 {
                font-size: 24px;
            }

            .steps {
                margin-bottom: 20px;
            }

            .button-group {
                flex-direction: column;
            }

            .button-group button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="icon">🔐</div>
            <h1>Resetear Contraseña</h1>
            <p>Actualiza tu contraseña de acceso a TimeControl</p>
        </div>

        <!-- Pasos -->
        <div class="steps">
            <div class="step <?= $paso_actual >= 1 ? 'active' : '' ?> <?= $paso_actual > 1 ? 'completed' : '' ?>">
                <div class="step-number">✓</div>
                <div class="step-label">Verificar datos</div>
            </div>
            <div class="step <?= $paso_actual >= 2 ? 'active' : '' ?> <?= $paso_actual > 2 ? 'completed' : '' ?>">
                <div class="step-number">✓</div>
                <div class="step-label">Nueva contraseña</div>
            </div>
        </div>

        <!-- Alertas -->
        <?php if ($mensaje_exito): ?>
        <div class="alert alert-success">
            <div class="alert-icon">✓</div>
            <div class="alert-content">
                <strong>¡Éxito!</strong>
                <div><?= htmlspecialchars($mensaje_exito) ?></div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($mensaje_error): ?>
        <div class="alert alert-error">
            <div class="alert-icon">✕</div>
            <div class="alert-content">
                <strong>Error</strong>
                <div><?= htmlspecialchars($mensaje_error) ?></div>
            </div>
        </div>
        <?php endif; ?>

        <!-- PASO 1: Verificar Usuario -->
        <form method="POST" class="form-step <?= $paso_actual == 1 ? 'active' : '' ?>" id="form-paso-1">
            <input type="hidden" name="paso" value="1">

            <div class="form-group">
                <label for="usuario">👤 Usuario</label>
                <input 
                    type="text" 
                    id="usuario" 
                    name="usuario" 
                    placeholder="Ingresa tu nombre de usuario"
                    required
                    autocomplete="username"
                >
            </div>

            <div class="button-group">
                <button type="submit" class="btn-primary">Verificar usuario →</button>
            </div>

            <div class="back-link">
                <a href="index.php">← Volver al inicio</a>
            </div>
        </form>

        <!-- PASO 2: Cambiar Contraseña -->
        <form method="POST" class="form-step <?= $paso_actual == 2 ? 'active' : '' ?>" id="form-paso-2">
            <input type="hidden" name="paso" value="2">

            <div class="form-group">
                <p style="color: #666; font-size: 14px; margin-bottom: 15px;">
                    👤 <strong><?= isset($_SESSION['reset_usuario']) ? htmlspecialchars($_SESSION['reset_usuario']) : 'Usuario' ?></strong>
                </p>
            </div>

            <div class="form-group">
                <label for="password_nueva">🔑 Nueva Contraseña</label>
                <input 
                    type="password" 
                    id="password_nueva" 
                    name="password_nueva" 
                    placeholder="Ingresa tu nueva contraseña"
                    required
                    minlength="6"
                    onchange="verificarContraseñas()"
                    onkeyup="mostrarFortaleza(this.value)"
                >
                <div class="password-strength">
                    Fortaleza:
                    <div class="strength-bar">
                        <div class="strength-fill" id="strength-fill"></div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="password_repetir">🔑 Repetir Contraseña</label>
                <input 
                    type="password" 
                    id="password_repetir" 
                    name="password_repetir" 
                    placeholder="Repite tu nueva contraseña"
                    required
                    minlength="6"
                    onchange="verificarContraseñas()"
                >
                <div id="match-feedback" style="margin-top: 8px; font-size: 12px; display: none;"></div>
            </div>

            <div class="requirements">
                <strong>Requisitos de contraseña:</strong>
                <ul>
                    <li>✓ Al menos 6 caracteres</li>
                    <li>✓ Las dos contraseñas deben coincidir</li>
                    <li>✓ Se recomienda usar mayúsculas, números y símbolos</li>
                </ul>
            </div>

            <div class="button-group">
                <button type="button" class="btn-secondary" onclick="volverAlPaso1()">← Atrás</button>
                <button type="submit" class="btn-primary" id="btn-cambiar">Guardar Contraseña</button>
            </div>
        </form>

        <!-- Mensaje de éxito final -->
        <?php if ($mensaje_exito && $paso_actual == 1 && !isset($_POST['paso'])): ?>
        <div style="text-align: center;">
            <div class="success-icon">✅</div>
            <p style="color: #666; margin-bottom: 20px;">Tu contraseña ha sido actualizada exitosamente.</p>
            <a href="index.php" class="btn btn-primary" style="max-width: 300px; margin: 0 auto; display: inline-block;">Ir a Iniciar Sesión</a>
        </div>
        <?php endif; ?>
    </div>

    <script>
        function mostrarFortaleza(password) {
            const strengthFill = document.getElementById('strength-fill');
            let strength = 'weak';

            if (password.length >= 6) {
                if (/[A-Z]/.test(password) && /[0-9]/.test(password) && /[!@#$%^&*]/.test(password)) {
                    strength = 'strong';
                } else if (/[A-Z]/.test(password) || /[0-9]/.test(password)) {
                    strength = 'medium';
                }
            }

            strengthFill.className = 'strength-fill';
            if (strength !== 'weak') {
                strengthFill.classList.add(strength);
            }
        }

        function verificarContraseñas() {
            const password1 = document.getElementById('password_nueva').value;
            const password2 = document.getElementById('password_repetir').value;
            const feedback = document.getElementById('match-feedback');
            const btnCambiar = document.getElementById('btn-cambiar');

            if (password1 === '' || password2 === '') {
                feedback.style.display = 'none';
                btnCambiar.disabled = false;
                return;
            }

            if (password1 === password2) {
                feedback.style.display = 'block';
                feedback.innerHTML = '✓ Las contraseñas coinciden';
                feedback.style.color = '#28a745';
                btnCambiar.disabled = false;
            } else {
                feedback.style.display = 'block';
                feedback.innerHTML = '✕ Las contraseñas no coinciden';
                feedback.style.color = '#dc3545';
                btnCambiar.disabled = true;
            }
        }

        function volverAlPaso1() {
            document.getElementById('form-paso-2').classList.remove('active');
            document.getElementById('form-paso-1').classList.add('active');
            
            // Hacer una petición para limpiar la sesión
            fetch('<?= $_SERVER['PHP_SELF'] ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'paso=0'
            }).then(() => {
                location.reload();
            });
        }

        // Inicializar contraseñas al cargar
        document.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById('password_nueva')) {
                verificarContraseñas();
            }
        });
    </script>
</body>
</html>
