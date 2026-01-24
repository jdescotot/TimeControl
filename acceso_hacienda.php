<?php
session_start();
require_once 'config.php';

// Verificar si ya existe un usuario hacienda
$stmt = $pdo->prepare("SELECT id, username FROM usuarios WHERE rol = 'hacienda' LIMIT 1");
$stmt->execute();
$usuario_hacienda = $stmt->fetch();

if (!$usuario_hacienda) {
    // Crear usuario hacienda si no existe
    $password_hash = password_hash('hacienda123', PASSWORD_DEFAULT);
    try {
        $stmt = $pdo->prepare("
            INSERT INTO usuarios (username, password, rol, requiere_cambio_password, propietario_id) 
            VALUES ('hacienda', ?, 'hacienda', 0, NULL)
        ");
        $stmt->execute([$password_hash]);
        $usuario_hacienda = ['id' => $pdo->lastInsertId(), 'username' => 'hacienda'];
    } catch (PDOException $e) {
        die("Error al crear usuario hacienda. Verifica que la columna 'rol' acepte el valor 'hacienda'.");
    }
}

// Establecer sesión automáticamente
$_SESSION['user_id'] = $usuario_hacienda['id'];
$_SESSION['username'] = $usuario_hacienda['username'];
$_SESSION['rol'] = 'hacienda';

// Redirigir al panel de hacienda
header('Location: hacienda.php');
exit;
?>
