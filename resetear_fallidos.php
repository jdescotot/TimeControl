<?php
// resetear_fallidos.php - Cambia el estado de correos fallidos a queued
session_start();

// Cargar configuración
if (!file_exists(__DIR__ . '/mail_config.php')) {
    die('Falta mail_config.php');
}
$mail_config = include __DIR__ . '/mail_config.php';
$db = $mail_config['db'] ?? null;
if (!$db) { die('Configuración de BD inválida'); }

try {
    $pdo = new PDO("mysql:host={$db['host']};dbname={$db['name']};charset=utf8", $db['user'], $db['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Error de conexión: ' . $e->getMessage());
}

// Cambiar todos los correos fallidos a queued
$stmt = $pdo->prepare("
    UPDATE email_queue 
    SET status = 'queued', attempts = 0
    WHERE status = 'failed'
");

try {
    $stmt->execute();
    $count = $stmt->rowCount();
    header("Location: estado_envios.php?success=" . urlencode("$count correo(s) fallido(s) movido(s) de vuelta a la cola"));
} catch (Exception $e) {
    header("Location: estado_envios.php?error=" . urlencode("Error: " . $e->getMessage()));
}
exit;
?>
