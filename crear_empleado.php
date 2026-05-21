<?php
session_start();
require_once 'config.php';

// Solo el dueño puede crear empleados
require_dueno();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dueño.php');
    exit;
}

$username_raw = trim($_POST['username'] ?? '');
$nombre = trim($_POST['nombre'] ?? '');
$password = $_POST['password'] ?? '';
$confirmar_password = $_POST['confirmar_password'] ?? '';
$fecha_inicio = $_POST['fecha_inicio'] ?? date('Y-m-d');
$es_gerente = isset($_POST['es_gerente']) ? 1 : 0;

// Procesar username: convertir a minÃºsculas y quitar espacios
$username = strtolower(str_replace(' ', '', $username_raw));

// Validaciones
$errores = [];

// Validar que no estÃ©n vacÃ­os
if (empty($nombre)) {
    $errores[] = "El nombre del empleado es obligatorio";
}

if (empty($username)) {
    $errores[] = "El nombre de usuario es obligatorio";
}

if (empty($password)) {
    $errores[] = "La contraseÃ±a es obligatoria";
}

// Validar longitud del username
if (strlen($username) < 3) {
    $errores[] = "El nombre de usuario debe tener al menos 3 caracteres";
}

if (strlen($username) > 50) {
    $errores[] = "El nombre de usuario no puede exceder 50 caracteres";
}

// Validar formato del username (solo letras y números despuÃ©s de procesar)
if (!preg_match('/^[a-z0-9]+$/', $username)) {
    $errores[] = "El nombre de usuario solo puede contener letras y números";
}

// Validar longitud de contraseÃ±a
if (strlen($password) < 6) {
    $errores[] = "La contraseÃ±a debe tener al menos 6 caracteres";
}

// Validar que la contraseÃ±a NO tenga espacios
if (strpos($password, ' ') !== false) {
    $errores[] = "La contraseÃ±a no puede contener espacios";
}

// Validar que las contraseÃ±as coincidan
if ($password !== $confirmar_password) {
    $errores[] = "Las contraseÃ±as no coinciden";
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
        header('Location: dueño.php?error=' . urlencode("El nombre de usuario '$username' ya estÃ¡ en uso"));
        exit;
    }

    // Crear el nuevo empleado
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $dueno_actual_id = (int)$_SESSION['user_id'];
    $stmt = $pdo->prepare("
        INSERT INTO usuarios (username, password, rol, nombre, requiere_cambio_password, propietario_id, created_at) 
        VALUES (?, ?, 'empleado', ?, 1, ?, ?)
    ");
    $stmt->execute([$username, $password_hash, $nombre, $dueno_actual_id, $fecha_inicio]);

    if ($es_gerente === 1) {
        $empleado_id = (int)$pdo->lastInsertId();
        $stmt_gerente = $pdo->prepare("UPDATE usuarios SET es_gerente = 1 WHERE id = ? AND propietario_id = ? AND rol = 'empleado'");
        $stmt_gerente->execute([$empleado_id, $dueno_actual_id]);
    }

    // Redirigir con Ã©xito
    header('Location: dueño.php?mensaje=empleado_creado&username=' . urlencode($username));
    exit;

} catch (Exception $e) {
    error_log("Error al crear empleado: " . $e->getMessage());
    header('Location: dueño.php?error=' . urlencode("Error al crear el empleado. Por favor, intenta de nuevo."));
    exit;
}
?>
