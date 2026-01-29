<?php
// estado_envios.php - Muestra el estado de todos los correos encolados y enviados
session_start();

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

// Obtener estadísticas
$stats = $pdo->query("
    SELECT 
        status,
        COUNT(*) as count
    FROM email_queue
    GROUP BY status
")->fetchAll(PDO::FETCH_KEY_PAIR);

$total = array_sum($stats);
$queued = $stats['queued'] ?? 0;
$sending = $stats['sending'] ?? 0;
$sent = $stats['sent'] ?? 0;
$failed = $stats['failed'] ?? 0;

// Filtro de estado
$filter = $_GET['filter'] ?? 'all';
$where = '';
if ($filter !== 'all') {
    $where = "WHERE status = " . $pdo->quote($filter);
}

// Paginación
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 50;
$offset = ($page - 1) * $per_page;

// Obtener correos
$stmt = $pdo->query("
    SELECT * FROM email_queue
    $where
    ORDER BY id DESC
    LIMIT $per_page OFFSET $offset
");
$emails = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_filtered = $pdo->query("SELECT COUNT(*) FROM email_queue $where")->fetchColumn();
$total_pages = ceil($total_filtered / $per_page);

// Capturar mensajes de éxito o error
$success = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : null;
$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : null;
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Estado de envíos - Sistema de correo masivo</title>
    <link rel="stylesheet" href="estado_envios.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Estado de envíos</h1>
            <a href="enviar_correo.php" class="btn-back">← Volver a enviar correo</a>
        </div>

        <?php if ($success): ?>
        <div class="alert alert-success">
            <strong>✓ Éxito:</strong> <?= $success ?>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-error">
            <strong>✕ Error:</strong> <?= $error ?>
        </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-icon">📧</div>
                <div class="stat-info">
                    <div class="stat-value"><?= number_format($total) ?></div>
                    <div class="stat-label">Total</div>
                </div>
            </div>

            <div class="stat-card queued">
                <div class="stat-icon">⏳</div>
                <div class="stat-info">
                    <div class="stat-value"><?= number_format($queued) ?></div>
                    <div class="stat-label">En cola</div>
                </div>
            </div>

            <div class="stat-card sending">
                <div class="stat-icon">🔄</div>
                <div class="stat-info">
                    <div class="stat-value"><?= number_format($sending) ?></div>
                    <div class="stat-label">Enviando</div>
                </div>
            </div>

            <div class="stat-card sent">
                <div class="stat-icon">✅</div>
                <div class="stat-info">
                    <div class="stat-value"><?= number_format($sent) ?></div>
                    <div class="stat-label">Enviados</div>
                </div>
            </div>

            <div class="stat-card failed">
                <div class="stat-icon">❌</div>
                <div class="stat-info">
                    <div class="stat-value"><?= number_format($failed) ?></div>
                    <div class="stat-label">Fallidos</div>
                </div>
            </div>
        </div>

        <?php if ($sent > 0): ?>
        <div class="progress-bar-container">
            <div class="progress-label">Progreso de envío</div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?= $total > 0 ? round($sent / $total * 100, 2) : 0 ?>%">
                    <?= $total > 0 ? round($sent / $total * 100, 1) : 0 ?>%
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="filters">
            <a href="?filter=all" class="filter-btn <?= $filter === 'all' ? 'active' : '' ?>">Todos</a>
            <a href="?filter=queued" class="filter-btn <?= $filter === 'queued' ? 'active' : '' ?>">En cola</a>
            <a href="?filter=sending" class="filter-btn <?= $filter === 'sending' ? 'active' : '' ?>">Enviando</a>
            <a href="?filter=sent" class="filter-btn <?= $filter === 'sent' ? 'active' : '' ?>">Enviados</a>
            <a href="?filter=failed" class="filter-btn <?= $filter === 'failed' ? 'active' : '' ?>">Fallidos</a>
        </div>

        <?php if (count($emails) === 0): ?>
        <div class="empty-state">
            <div class="empty-icon">📭</div>
            <p>No hay correos en el sistema</p>
        </div>
        <?php else: ?>
        <div class="table-container">
            <table class="emails-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Destinatario</th>
                        <th>Asunto</th>
                        <th>Estado</th>
                        <th>Intentos</th>
                        <th>Fecha creación</th>
                        <th>Fecha envío</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($emails as $email): ?>
                    <tr>
                        <td><?= $email['id'] ?></td>
                        <td>
                            <div class="recipient">
                                <div class="recipient-email"><?= htmlspecialchars($email['recipient_email']) ?></div>
                                <?php if ($email['recipient_name']): ?>
                                <div class="recipient-name"><?= htmlspecialchars($email['recipient_name']) ?></div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="subject"><?= htmlspecialchars($email['subject']) ?></td>
                        <td>
                            <?php
                            $status_class = $email['status'];
                            $status_label = [
                                'queued' => '⏳ En cola',
                                'sending' => '🔄 Enviando',
                                'sent' => '✅ Enviado',
                                'failed' => '❌ Fallido'
                            ][$email['status']] ?? $email['status'];
                            ?>
                            <span class="status-badge <?= $status_class ?>"><?= $status_label ?></span>
                        </td>
                        <td class="attempts"><?= $email['attempts'] ?></td>
                        <td class="date"><?= date('d/m/Y H:i', strtotime($email['created_at'])) ?></td>
                        <td class="date">
                            <?= $email['sent_at'] ? date('d/m/Y H:i', strtotime($email['sent_at'])) : '-' ?>
                        </td>
                        <td>
                            <a href="detalle_correo.php?id=<?= $email['id'] ?>" class="btn-detail">Ver detalle</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
            <a href="?filter=<?= $filter ?>&page=<?= $page - 1 ?>" class="page-btn">← Anterior</a>
            <?php endif; ?>

            <span class="page-info">Página <?= $page ?> de <?= $total_pages ?></span>

            <?php if ($page < $total_pages): ?>
            <a href="?filter=<?= $filter ?>&page=<?= $page + 1 ?>" class="page-btn">Siguiente →</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <?php if ($queued > 0): ?>
        <div class="worker-notice">
            <strong>ℹ️ Nota:</strong> Hay <?= $queued ?> correo(s) en cola. 
            Ejecuta <code>php worker_send.php</code> en terminal para procesarlos.
        </div>
        <?php endif; ?>

        <div class="refresh-notice">
            🔄 Esta página se actualiza automáticamente cada 30 segundos
        </div>
    </div>

    <script>
        // Auto-refresh cada 30 segundos
        setTimeout(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
