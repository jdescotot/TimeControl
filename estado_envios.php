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
$permanent_error = $stats['permanent_error'] ?? 0;

// Filtro de estado
$filter = $_GET['filter'] ?? 'all';
$where = '';
$params = [];
if ($filter !== 'all') {
    $where = "WHERE status = ?";
    $params[] = $filter;
}

// Paginación
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 50;
$offset = ($page - 1) * $per_page;

// Obtener correos (ordenados por created_at descendente para mostrar los más recientes primero)
$sql = "SELECT * FROM email_queue $where ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?";
$stmt = $pdo->prepare($sql);
$bindParams = array_merge($params, [$per_page, $offset]);
foreach ($bindParams as $i => $param) {
    $stmt->bindValue($i + 1, $param, is_int($param) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->execute();
$emails = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Contar total filtrado
$countSql = "SELECT COUNT(*) FROM email_queue $where";
$countStmt = $pdo->prepare($countSql);
if (!empty($params)) {
    foreach ($params as $i => $param) {
        $countStmt->bindValue($i + 1, $param, PDO::PARAM_STR);
    }
}
$countStmt->execute();
$total_filtered = $countStmt->fetchColumn();
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
    <meta http-equiv="refresh" content="30">
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
                    <div class="stat-label">Fallidos (SMTP)</div>
                </div>
            </div>

            <div class="stat-card" style="background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);">
                <div class="stat-icon">🚫</div>
                <div class="stat-info">
                    <div class="stat-value"><?= number_format($permanent_error) ?></div>
                    <div class="stat-label">Error Permanente</div>
                </div>
            </div>
        </div>

        <?php 
        // Verificar si hay correos recientes (últimas 24 horas)
        $recent_check = $pdo->query("SELECT COUNT(*) FROM email_queue WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();
        if ($recent_check == 0 && $total > 0):
        ?>
        <div class="worker-notice" style="background: #fff3cd; border-color: #ffc107; color: #856404;">
            <strong>⚠️ Advertencia:</strong> No se han encolado correos nuevos en las últimas 24 horas. 
            Todos los correos mostrados son antiguos. Si esperabas correos nuevos, verifica el proceso de encolado.
        </div>
        <?php endif; ?>

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
            <a href="?filter=failed" class="filter-btn <?= $filter === 'failed' ? 'active' : '' ?>">Fallidos (SMTP)</a>
            <a href="?filter=permanent_error" class="filter-btn <?= $filter === 'permanent_error' ? 'active' : '' ?>">Error Permanente</a>
        </div>

        <?php if ($queued > 0): ?>
        <div class="action-panel">
            <div class="action-info">
                <strong>⏳ Hay <?= $queued ?> correo(s) en cola</strong> esperando ser enviados.
                <p style="margin: 10px 0 0 0; font-size: 13px; color: #666;">Procesa los correos en lotes seguros para evitar problemas con los servidores SMTP.</p>
            </div>
            <div class="action-buttons">
                <a href="procesar_cola.php?batch_size=1" class="btn btn-process">▶ 1 Correo</a>
                <a href="procesar_cola.php?batch_size=29" class="btn btn-process btn-process-multi">▶ 29 Correos</a>
                <a href="procesar_cola.php?lotes=1" class="btn btn-process">▶ 1 Lote (<?= $mail_config['batch_size'] ?? 50 ?> correos)</a>
                <a href="procesar_cola.php?lotes=3" class="btn btn-process btn-process-multi">▶ 3 Lotes</a>
                <a href="procesar_cola.php?lotes=5" class="btn btn-process btn-process-multi">▶ 5 Lotes</a>
            </div>
        </div>
        <?php endif; ?>

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
                                'failed' => '❌ Fallido (SMTP)',
                                'permanent_error' => '🚫 Error Permanente'
                            ][$email['status']] ?? $email['status'];
                            ?>
                            <span class="status-badge <?= $status_class ?>"><?= $status_label ?></span>
                        </td>
                        <td class="attempts"><?= $email['attempts'] ?></td>
                        <td class="date"><?= date('d/m/Y H:i:s', strtotime($email['created_at'])) ?></td>
                        <td class="date">
                            <?= $email['sent_at'] ? date('d/m/Y H:i:s', strtotime($email['sent_at'])) : '-' ?>
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

        <?php if ($failed > 0): ?>
        <div class="worker-notice" style="background: #fff3cd; border-color: #ffc107; color: #856404;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <strong>⚠️ Hay <?= $failed ?> correo(s) fallidos por SMTP.</strong> 
                    <a href="test_smtp.php" style="color: #856404; font-weight: bold; text-decoration: underline;">🔧 Probar conexión SMTP</a> para diagnosticar el problema.
                </div>
                <a href="resetear_fallidos.php" class="btn btn-retry" style="background: #ffc107; color: #856404; border: none; margin-left: 10px;">🔄 Mover a cola</a>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($permanent_error > 0): ?>
        <div class="worker-notice" style="background: #f8d7da; border-color: #f5c6cb; color: #721c24;">
            <strong>🚫 Hay <?= $permanent_error ?> correo(s) con error permanente:</strong> 
            Destinatarios inválidos, no existen o han sido rechazados permanentemente. 
            <a href="?filter=permanent_error" style="color: #721c24; font-weight: bold; text-decoration: underline;">Ver lista</a> - NO se reintentan automáticamente.
        </div>
        <?php endif; ?>

        <div class="refresh-notice">
            🔄 Esta página se actualiza automáticamente cada 30 segundos (última actualización: <?= date('H:i:s') ?>)
        </div>
    </div>
</body>
</html>
