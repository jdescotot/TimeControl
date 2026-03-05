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
                    $mensaje_exito = 'Usuario verificado. Ingresa tu nueva contraseña.';
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
                $mensaje_error = 'Minimo 6 caracteres.';
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
                    $mensaje_exito = 'Contraseña actualizada. Inicia sesión ahora.';
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
    <title>Resetear Contraseña</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 450px;
            padding: 40px;
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }
        .alert {
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
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
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: bold;
            font-size: 14px;
        }
        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            font-family: Arial, sans-serif;
        }
        input:focus {
            outline: none;
            border-color: #667eea;
            background: #f8f9ff;
        }
        button {
            width: 100%;
            padding: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
        }
        button:hover {
            opacity: 0.9;
        }
        .form-step {
            display: none;
        }
        .form-step.active {
            display: block;
        }
        .usuario-info {
            background: #f0f0f0;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            text-align: center;
            color: #666;
        }
        .back-link {
            text-align: center;
            margin-top: 15px;
        }
        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Resetear Contraseña</h1>

        <?php if ($mensaje_exito): ?>
        <div class="alert alert-success">✓ <?= htmlspecialchars($mensaje_exito) ?></div>
        <?php endif; ?>

        <?php if ($mensaje_error): ?>
        <div class="alert alert-error">✕ <?= htmlspecialchars($mensaje_error) ?></div>
        <?php endif; ?>

        <!-- PASO 1: Verificar usuario -->
        <form method="POST" class="form-step <?= $paso_actual == 1 ? 'active' : '' ?>">
            <input type="hidden" name="paso" value="1">
            <div class="form-group">
                <label>Usuario</label>
                <input type="text" name="usuario" placeholder="Tu usuario" required>
            </div>
            <button type="submit">Verificar →</button>
            <div class="back-link">
                <a href="index.php">← Volver al inicio</a>
            </div>
        </form>

        <!-- PASO 2: Nueva contraseña -->
        <form method="POST" class="form-step <?= $paso_actual == 2 ? 'active' : '' ?>">
            <input type="hidden" name="paso" value="2">
            <?php if (isset($_SESSION['reset_usuario'])): ?>
            <div class="usuario-info">
                Cambiar contraseña para: <strong><?= htmlspecialchars($_SESSION['reset_usuario']) ?></strong>
            </div>
            <?php endif; ?>
            <div class="form-group">
                <label>Nueva Contraseña</label>
                <input type="password" name="password_nueva" placeholder="Mínimo 6 caracteres" required minlength="6">
            </div>
            <div class="form-group">
                <label>Repetir Contraseña</label>
                <input type="password" name="password_repetir" placeholder="Repite la contraseña" required minlength="6">
            </div>
            <button type="submit">Guardar Contraseña</button>
            <div class="back-link">
                <a href="resetear_contrasena_simple.php">← Empezar de nuevo</a>
            </div>
        </form>
    </div>
</body>
</html>
