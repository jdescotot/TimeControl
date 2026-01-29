<?php
// reenviar_correo.php - Marca un correo fallido para reintentarlo
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

// Verificar que el correo existe y está en estado fallido
$stmt = $pdo->prepare("SELECT status FROM email_queue WHERE id = ?");
$stmt->execute([$id]);
$email = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$email) {
    header('Location: estado_envios.php?error=' . urlencode('Correo no encontrado'));
    exit;
}

// Restablecer estado a 'queued' para que el worker lo procese nuevamente
$stmt = $pdo->prepare("UPDATE email_queue SET status = 'queued', last_error = NULL WHERE id = ?");
$stmt->execute([$id]);

header('Location: detalle_correo.php?id=' . $id . '&success=' . urlencode('Correo marcado para reenvío'));
exit;
