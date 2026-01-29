<?php
// detalle_correo.php - Muestra el detalle completo de un correo específico
session_start();

$id = (int)($_GET['id'] ?? 0);
if ($id === 0) {
    header('Location: estado_envios.php');
    exit;
}

// Cargar configuración
if (!file_exists(__DIR__ . '/mail_config.php')) {
    die('Falta mail_config.php. Copia mail_config.php.example y completa las credenciales.');
}
$mail_config = include __DIR__ . '/mail_config.php';
$db = $mail_config['db'] ?? null;
if (!$db) { die('Configuración de BD inválida en mail_config.php'); }

try {
    $pdo = new PDO("mysql:host={$db['host']};dbname={$db['name']};charset=utf8", $db['user'], $db['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('No se pudo conectar a la base de datos: ' . $e->getMessage());
}

$stmt = $pdo->prepare("SELECT * FROM email_queue WHERE id = ?");
$stmt->execute([$id]);
$email = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$email) {
    die('Correo no encontrado');
}

$attachments = json_decode($email['attachments'] ?? '[]', true);

// Capturar mensajes de éxito
$success = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : null;
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Detalle de correo #<?= $id ?></title>
    <link rel="stylesheet" href="detalle_correo.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📧 Detalle de correo #<?= $id ?></h1>
            <a href="estado_envios.php" class="btn-back">← Volver al listado</a>
        </div>

        <?php if ($success): ?>
        <div class="alert alert-success">
            <strong>✓ Éxito:</strong> <?= $success ?>
        </div>
        <?php endif; ?>

        <div class="status-section">
            <?php
            $status_info = [
                'queued' => ['icon' => '⏳', 'label' => 'En cola', 'class' => 'queued'],
                'sending' => ['icon' => '🔄', 'label' => 'Enviando', 'class' => 'sending'],
                'sent' => ['icon' => '✅', 'label' => 'Enviado correctamente', 'class' => 'sent'],
                'failed' => ['icon' => '❌', 'label' => 'Falló el envío', 'class' => 'failed']
            ];
            $current_status = $status_info[$email['status']] ?? ['icon' => '❓', 'label' => 'Desconocido', 'class' => 'unknown'];
            ?>
            <div class="status-badge-large <?= $current_status['class'] ?>">
                <span class="status-icon"><?= $current_status['icon'] ?></span>
                <span class="status-label"><?= $current_status['label'] ?></span>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-card">
                <div class="info-label">Destinatario</div>
                <div class="info-value">
                    <strong><?= htmlspecialchars($email['recipient_email']) ?></strong>
                    <?php if ($email['recipient_name']): ?>
                        <div class="info-secondary"><?= htmlspecialchars($email['recipient_name']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="info-card">
                <div class="info-label">Asunto</div>
                <div class="info-value"><?= htmlspecialchars($email['subject']) ?></div>
            </div>

            <div class="info-card">
                <div class="info-label">Fecha de creación</div>
                <div class="info-value"><?= date('d/m/Y H:i:s', strtotime($email['created_at'])) ?></div>
            </div>

            <div class="info-card">
                <div class="info-label">Fecha de envío</div>
                <div class="info-value">
                    <?= $email['sent_at'] ? date('d/m/Y H:i:s', strtotime($email['sent_at'])) : '⏳ Pendiente' ?>
                </div>
            </div>

            <div class="info-card">
                <div class="info-label">Intentos de envío</div>
                <div class="info-value attempts-badge"><?= $email['attempts'] ?></div>
            </div>

            <?php if (count($attachments) > 0): ?>
            <div class="info-card">
                <div class="info-label">Archivos adjuntos</div>
                <div class="info-value"><?= count($attachments) ?> archivo(s)</div>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($email['status'] === 'failed' && $email['last_error']): ?>
        <div class="error-section">
            <h3>❌ Error de envío</h3>
            <div class="error-message">
                <?= htmlspecialchars($email['last_error']) ?>
            </div>
            <div class="error-hint">
                <strong>💡 Posibles soluciones:</strong>
                <ul>
                    <li>Verifica las credenciales SMTP en mail_config.php</li>
                    <li>Revisa que el servidor SMTP esté disponible</li>
                    <li>Confirma que el correo del destinatario es válido</li>
                    <li>Revisa los logs del servidor para más detalles</li>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <div class="body-section">
            <h3>📄 Contenido del correo</h3>
            <div class="body-preview">
                <?= $email['body'] ?>
            </div>
        </div>

        <?php if (count($attachments) > 0): ?>
        <div class="attachments-section">
            <h3>📎 Archivos adjuntos</h3>
            <div class="attachments-list">
                <?php foreach ($attachments as $att): ?>
                    <?php
                    $path = __DIR__ . '/mail_uploads/' . basename($att);
                    $exists = is_file($path);
                    $size = $exists ? filesize($path) : 0;
                    $ext = pathinfo($att, PATHINFO_EXTENSION);
                    ?>
                    <div class="attachment-item <?= $exists ? '' : 'missing' ?>">
                        <div class="attachment-icon">
                            <?php
                            $icon = match(strtolower($ext)) {
                                'pdf' => '📕',
                                'jpg', 'jpeg', 'png', 'gif' => '🖼️',
                                'doc', 'docx' => '📘',
                                'xls', 'xlsx' => '📊',
                                'zip', 'rar' => '📦',
                                default => '📄'
                            };
                            echo $icon;
                            ?>
                        </div>
                        <div class="attachment-info">
                            <div class="attachment-name"><?= htmlspecialchars($att) ?></div>
                            <div class="attachment-size">
                                <?= $exists ? round($size / 1024, 2) . ' KB' : '⚠️ Archivo no encontrado' ?>
                            </div>
                        </div>
                        <?php if ($exists): ?>
                        <a href="serve_file.php?file=<?= urlencode($att) ?>" class="btn-download" target="_blank">
                            ⬇️ Descargar
                        </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="actions-section">
            <h3>⚙️ Acciones</h3>
            <div class="actions-buttons">
                <?php if ($email['status'] === 'failed'): ?>
                <a href="reenviar_correo.php?id=<?= $id ?>" class="btn-action retry">
                    🔄 Reintentar envío
                </a>
                <?php endif; ?>
                
                <a href="eliminar_correo.php?id=<?= $id ?>" class="btn-action delete" 
                   onclick="return confirm('¿Estás seguro de eliminar este correo de la cola?')">
                    🗑️ Eliminar de la cola
                </a>
            </div>
        </div>
    </div>
</body>
</html>
