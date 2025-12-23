<?php
session_start();
require_once 'config.php';

// Solo el dueño puede crear empleados
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'dueño' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dueño.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$confirmar_password = $_POST['confirmar_password'] ?? '';

// Validaciones
$errores = [];

// Validar que no estén vacíos
if (empty($username)) {
    $errores[] = "El nombre de usuario es obligatorio";
}

if (empty($password)) {
    $errores[] = "La contraseña es obligatoria";
}

// Validar longitud del username
if (strlen($username) < 3) {
    $errores[] = "El nombre de usuario debe tener al menos 3 caracteres";
}

if (strlen($username) > 50) {
    $errores[] = "El nombre de usuario no puede exceder 50 caracteres";
}

// Validar formato del username (solo letras, números, puntos, guiones y guiones bajos)
if (!preg_match('/^[a-zA-Z0-9._-]+$/', $username)) {
    $errores[] = "El nombre de usuario solo puede contener letras, números, puntos, guiones y guiones bajos";
}

// Validar longitud de contraseña
if (strlen($password) < 6) {
    $errores[] = "La contraseña debe tener al menos 6 caracteres";
}

// Validar que las contraseñas coincidan
if ($password !== $confirmar_password) {
    $errores[] = "Las contraseñas no coinciden";
}

// Si hay errores, redirigir con mensaje
if (!empty($errores)) {
    $mensaje_error = implode(". ", $errores);
    header('Location: dueño.php?error=' . urlencode($mensaje_error));
    exit;
}

try {
    // Verificar si el usuario ya existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE username = ?");
    $stmt->execute([$username]);
    
    if ($stmt->fetch()) {
        header('Location: dueño.php?error=' . urlencode("El nombre de usuario '$username' ya está en uso"));
        exit;
    }

    // Crear el nuevo empleado
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("
        INSERT INTO usuarios (username, password, rol, requiere_cambio_password) 
        VALUES (?, ?, 'empleado', 1)
    ");
    $stmt->execute([$username, $password_hash]);

    // Redirigir con éxito
    header('Location: dueño.php?mensaje=empleado_creado&username=' . urlencode($username));
    exit;

} catch (Exception $e) {
    error_log("Error al crear empleado: " . $e->getMessage());
    header('Location: dueño.php?error=' . urlencode("Error al crear el empleado. Por favor, intenta de nuevo."));
    exit;
}
?>