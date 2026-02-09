<?php
// No hay sesión ni verificación - página standalone
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config.php';
    
    $rawUsername = trim($_POST['username'] ?? '');
    $username = strtolower(preg_replace('/\s+/', '', $rawUsername));
    $password = $_POST['password'] ?? '';
    $confirmar_password = $_POST['confirmar_password'] ?? '';
    $nombre_completo = trim($_POST['nombre_completo'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $numero_fiscal = trim($_POST['numero_fiscal'] ?? '');
    
    // Validaciones
    if (empty($username) || empty($password) || empty($nombre_completo) || empty($email) || empty($numero_fiscal)) {
        $mensaje = 'Todos los campos son obligatorios';
        $tipo_mensaje = 'error';
    } elseif (strpos($password, ' ') !== false) {
        $mensaje = 'La contraseña no puede contener espacios';
        $tipo_mensaje = 'error';
    } elseif (strlen($password) < 6) {
        $mensaje = 'La contraseña debe tener al menos 6 caracteres';
        $tipo_mensaje = 'error';
    } elseif ($password !== $confirmar_password) {
        $mensaje = 'Las contraseñas no coinciden';
        $tipo_mensaje = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje = 'El email no es válido';
        $tipo_mensaje = 'error';
    } else {
        try {
            // Verificar si el usuario ya existe
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE LOWER(username) = ?");
            $stmt->execute([$username]);
            
            if ($stmt->fetch()) {
                $mensaje = 'El nombre de usuario ya está en uso';
                $tipo_mensaje = 'error';
            } else {
                // Verificar si el email ya existe
                $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
                $stmt->execute([$email]);
                
                if ($stmt->fetch()) {
                    $mensaje = 'El email ya está registrado';
                    $tipo_mensaje = 'error';
                } else {
                    // Crear el nuevo usuario dueño
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $fecha_actual = date('Y-m-d H:i:s');
                    
                    // username = nombre de la empresa (obligatorio)
                    // rol = 'dueño' (siempre)
                    // nombre = nombre_completo (obligatorio)
                    // email = email (obligatorio)
                    // numero_fiscal = número fiscal (obligatorio)
                    // requiere_cambio_password = 1 (siempre)
                    $sql = "INSERT INTO usuarios (username, password, rol, nombre, email, numero_fiscal, requiere_cambio_password, created_at) VALUES (?, ?, 'dueño', ?, ?, ?, 1, ?)";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$username, $password_hash, $nombre_completo, $email, $numero_fiscal, $fecha_actual]);
                    
                    $mensaje = 'Cuenta de dueño creada exitosamente. Ya puedes iniciar sesión.';
                    $tipo_mensaje = 'success';
                    
                    // Limpiar campos después del éxito
                    $rawUsername = '';
                    $nombre_completo = '';
                    $email = '';
                    $numero_fiscal = '';
                }
            }
        } catch (PDOException $e) {
            $mensaje = 'Error al crear la cuenta: ' . $e->getMessage();
            $tipo_mensaje = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Dueño - Control Horario</title>
    <link rel="stylesheet" href="registro_dueño.css">
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
    <div class="registro-container">
        <div class="registro-header">
            <div class="logo-circle">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </div>
            <h1>Registro de Dueño</h1>
            <p>Crear nueva cuenta de administrador</p>
        </div>

        <?php if ($mensaje): ?>
            <div class="message <?php echo $tipo_mensaje; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="registro-form">
            <div class="form-group">
                <label for="nombre_completo">Nombre Completo</label>
                <input 
                    type="text" 
                    id="nombre_completo" 
                    name="nombre_completo" 
                    required
                    value="<?php echo htmlspecialchars($nombre_completo ?? ''); ?>"
                    placeholder="Juan Pérez García"
                >
            </div>

            <div class="form-group">
                <label for="email">Correo Electrónico</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    required
                    value="<?php echo htmlspecialchars($email ?? ''); ?>"
                    placeholder="correo@ejemplo.com"
                >
            </div>

            <div class="form-group">
                <label for="numero_fiscal">Número Fiscal</label>
                <input 
                    type="text" 
                    id="numero_fiscal" 
                    name="numero_fiscal" 
                    required
                    value="<?php echo htmlspecialchars($numero_fiscal ?? ''); ?>"
                    placeholder="RUC o equivalente"
                >
            </div>

            <div class="form-group">
                <label for="username">Nombre de Usuario</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    required
                    value="<?php echo htmlspecialchars($rawUsername ?? ''); ?>"
                    placeholder="usuario123"
                >
                <small class="form-hint">Sin espacios, se convertirá a minúsculas</small>
            </div>

            <div class="form-group">
                <label for="password">Contraseña</label>
                <div class="password-wrapper">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required
                        placeholder="Mínimo 6 caracteres"
                    >
                    <button type="button" class="toggle-password" onclick="togglePassword('password', this)">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
                <small class="form-hint">Sin espacios, mínimo 6 caracteres</small>
            </div>

            <div class="form-group">
                <label for="confirmar_password">Confirmar Contraseña</label>
                <div class="password-wrapper">
                    <input 
                        type="password" 
                        id="confirmar_password" 
                        name="confirmar_password" 
                        required
                        placeholder="Repite la contraseña"
                    >
                    <button type="button" class="toggle-password" onclick="togglePassword('confirmar_password', this)">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                Crear Cuenta de Dueño
            </button>
        </form>

        <div class="registro-footer">
            <p>¿Ya tienes una cuenta? <a href="index.php">Iniciar sesión</a></p>
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

        // Validación en tiempo real
        const password = document.getElementById('password');
        const confirmar = document.getElementById('confirmar_password');

        function validatePasswords() {
            if (confirmar.value && password.value !== confirmar.value) {
                confirmar.setCustomValidity('Las contraseñas no coinciden');
            } else {
                confirmar.setCustomValidity('');
            }
        }

        password.addEventListener('input', validatePasswords);
        confirmar.addEventListener('input', validatePasswords);
    </script>
</body>
</html>
