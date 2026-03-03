<?php
// resetear_fallidos.php - Cambia el estado de correos fallidos a queued
// SOLO resetea los errores transitorios/SMTP, NO los errores permanentes (destinatario inválido, no existe, etc)
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

// Cambiar correos fallidos (NO los permanentes) de vuelta a queued
// Los permanent_error se quedan como están
$stmt = $pdo->prepare("
    UPDATE email_queue 
    SET status = 'queued'
    WHERE status = 'failed'
");

try {
    $stmt->execute();
    $count = $stmt->rowCount();
    
    // Contar cuántos permanent_error hay
    $perm_count = $pdo->query("SELECT COUNT(*) FROM email_queue WHERE status = 'permanent_error'")->fetchColumn();
    
    $message = "$count correo(s) fallido(s) movido(s) de vuelta a la cola";
    if ($perm_count > 0) {
        $message .= " (⚠ $perm_count correo(s) con error permanente NO reseteado)";
    }
    
    header("Location: estado_envios.php?success=" . urlencode($message));
} catch (Exception $e) {
    header("Location: estado_envios.php?error=" . urlencode("Error: " . $e->getMessage()));
}
exit;
?>
