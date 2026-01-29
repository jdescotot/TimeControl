<?php
// eliminar_correo.php - Elimina un correo de la cola
session_start();

$id = (int)($_GET['id'] ?? 0);
if ($id === 0) {
    header('Location: estado_envios.php?error=' . urlencode('ID inválido'));
    exit;
}

// Cargar configuración
if (!file_exists(__DIR__ . '/mail_config.php')) {
    die('Falta mail_config.php');
}
$mail_config = include __DIR__ . '/mail_config.php';
$db = $mail_config['db'] ?? null;

try {
    $pdo = new PDO("mysql:host={$db['host']};dbname={$db['name']};charset=utf8", $db['user'], $db['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Error de conexión: ' . $e->getMessage());
}

// Eliminar el correo
$stmt = $pdo->prepare("DELETE FROM email_queue WHERE id = ?");
$stmt->execute([$id]);

header('Location: estado_envios.php?success=' . urlencode('Correo eliminado correctamente'));
exit;
