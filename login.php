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

        <?php
        session_start();
        require_once 'config.php';
        $showError = false;
        $errorMessage = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';

            // MODIFICACIÓN: Añadimos requiere_cambio_password a la consulta
            $stmt = $pdo->prepare("SELECT id, username, password, rol, requiere_cambio_password FROM usuarios WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['rol'] = $user['rol'];

                // MODIFICACIÓN: Verificamos si requiere cambio de contraseña antes de redirigir al panel
                if ($user['requiere_cambio_password'] == 1) {
                    header('Location: cambiar_password.php');
                    exit;
                }

                if ($user['rol'] === 'dueño') {
                    header('Location: dueño.php');
                } else {
                    header('Location: empleado.php');
                }
                exit;
            } else {
                $showError = true;
                $errorMessage = 'Usuario o contraseña incorrectos';
            }
        }

        if ($showError):
        ?>
            <div class="error-message">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
            <div class="back-link">
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
            <p>Sistema de Control Horario</p>
        </div>
    </div>
</body>
</html>