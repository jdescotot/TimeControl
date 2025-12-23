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
}