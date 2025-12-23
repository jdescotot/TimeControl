<?php
session_start();

// Solo procesamos si es POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config.php';
    
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // AQUÍ VA LA CONSULTA ACTUALIZADA - con el campo requiere_cambio_password
    $stmt = $pdo->prepare("SELECT id, username, password, rol, requiere_cambio_password FROM usuarios WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['rol'] = $user['rol'];

        // Si requiere cambio de contraseña, redirigir a cambiar_password.php
        if ($user['requiere_cambio_password'] == 1) {
            header('Location: cambiar_password.php');
            exit;
        }

        // Si no requiere cambio, ir a su panel normal
        if ($user['rol'] === 'dueño') {
            header('Location: dueño.php');
            exit;
        } else {
            header('Location: empleado.php');
            exit;
        }
    } else {
        $error = 'Usuario o contraseña incorrectos';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso - Control Horario</title>
    <link rel="stylesheet" href="login.css">
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
                    value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                >
            </div>

            <div class="form-group">
                <label for="password">Contraseña</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required 
                    autocomplete="current-password"
                    placeholder="Ingresa tu contraseña"
                >
            </div>

            <button type="submit" class="btn-login">Iniciar Sesión</button>
        </form>

        <div class="login-footer">
            <p>Sistema de Control Horario para Hostelería</p>
        </div>
    </div>
</body>
</html>