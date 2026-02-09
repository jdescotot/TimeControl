<?php
// ver_cola.php - Ver el estado actual de la cola de correos
session_start();
header('Content-Type: text/html; charset=utf-8');

// Cargar configuración
if (!file_exists(__DIR__ . '/mail_config.php')) {
    die('Falta mail_config.php');
}
$mail_config = include __DIR__ . '/mail_config.php';
$db = $mail_config['db'] ?? null;

if (!$db) {
    die('Configuración BD inválida');
}

try {
    $pdo = new PDO("mysql:host={$db['host']};dbname={$db['name']};charset=utf8", $db['user'], $db['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('No se pudo conectar: ' . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ver Cola de Correos</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #333; margin-bottom: 20px; }
        h2 { color: #666; margin-top: 30px; margin-bottom: 15px; font-size: 1.2em; }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .stat-box {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
            border-left: 4px solid #2196F3;
        }
        .stat-number {
            font-size: 28px;
            font-weight: bold;
            color: #2196F3;
        }
        .stat-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th {
            background: #2196F3;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }
        tr:hover {
            background: #f5f5f5;
        }
        
        .status-queued { background: #fff3cd; color: #856404; padding: 5px 10px; border-radius: 3px; font-size: 12px; }
        .status-sending { background: #cfe2ff; color: #084298; padding: 5px 10px; border-radius: 3px; font-size: 12px; }
        .status-sent { background: #d1e7dd; color: #0f5132; padding: 5px 10px; border-radius: 3px; font-size: 12px; }
        .status-failed { background: #f8d7da; color: #842029; padding: 5px 10px; border-radius: 3px; font-size: 12px; }
        
        .actions {
            text-align: right;
            margin-top: 20px;
        }
        .btn {
            padding: 10px 20px;
            background: #2196F3;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-left: 10px;
        }
        .btn:hover {
            background: #1976D2;
        }
        .btn-danger {
            background: #f44336;
        }
        .btn-danger:hover {
            background: #d32f2f;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .email-cell {
            max-width: 300px;
            word-break: break-all;
        }
        .subject-cell {
            max-width: 250px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 Estado de la Cola de Correos</h1>
        
        <?php
        // Obtener estadísticas
        $stats = $pdo->query("
            SELECT status, COUNT(*) as count
            FROM email_queue
            GROUP BY status
        ")->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $total = array_sum($stats);
        $queued = $stats['queued'] ?? 0;
        $sending = $stats['sending'] ?? 0;
        $sent = $stats['sent'] ?? 0;
        $failed = $stats['failed'] ?? 0;
        ?>
        
        <div class="stats">
            <div class="stat-box">
                <div class="stat-number"><?= $total ?></div>
                <div class="stat-label">Total</div>
            </div>
            <div class="stat-box" style="border-left-color: #ffc107;">
                <div class="stat-number" style="color: #ffc107;"><?= $queued ?></div>
                <div class="stat-label">En Cola</div>
            </div>
            <div class="stat-box" style="border-left-color: #17a2b8;">
                <div class="stat-number" style="color: #17a2b8;"><?= $sending ?></div>
                <div class="stat-label">Enviando</div>
            </div>
            <div class="stat-box" style="border-left-color: #28a745;">
                <div class="stat-number" style="color: #28a745;"><?= $sent ?></div>
                <div class="stat-label">Enviados</div>
            </div>
            <div class="stat-box" style="border-left-color: #dc3545;">
                <div class="stat-number" style="color: #dc3545;"><?= $failed ?></div>
                <div class="stat-label">Fallidos</div>
            </div>
        </div>
        
        <?php if ($queued > 0): ?>
        <div class="alert alert-info">
            ⏳ Hay <strong><?= $queued ?></strong> correo(s) en cola. 
            <a href="procesar_cola.php?lotes=1" style="color: #0c5460; text-decoration: underline;">Procesar ahora</a>
        </div>
        <?php endif; ?>
        
        <!-- En Cola -->
        <?php if ($queued > 0): ?>
        <h2>⏳ En Cola (<?= $queued ?>)</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Email</th>
                    <th>Nombre</th>
                    <th>Asunto</th>
                    <th>Creado</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $rows = $pdo->query("SELECT id, recipient_email, recipient_name, subject, created_at FROM email_queue WHERE status='queued' ORDER BY id DESC LIMIT 10")->fetchAll();
                foreach ($rows as $row): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td class="email-cell"><?= htmlspecialchars($row['recipient_email']) ?></td>
                    <td><?= htmlspecialchars($row['recipient_name'] ?? '-') ?></td>
                    <td class="subject-cell"><?= htmlspecialchars($row['subject']) ?></td>
                    <td><?= $row['created_at'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        
        <!-- Enviados -->
        <?php if ($sent > 0): ?>
        <h2>✅ Enviados (<?= $sent ?>)</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Email</th>
                    <th>Asunto</th>
                    <th>Enviado</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $rows = $pdo->query("SELECT id, recipient_email, subject, sent_at FROM email_queue WHERE status='sent' ORDER BY sent_at DESC LIMIT 10")->fetchAll();
                foreach ($rows as $row): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td class="email-cell"><?= htmlspecialchars($row['recipient_email']) ?></td>
                    <td class="subject-cell"><?= htmlspecialchars($row['subject']) ?></td>
                    <td><?= $row['sent_at'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        
        <!-- Fallidos -->
        <?php if ($failed > 0): ?>
        <h2>❌ Fallidos (<?= $failed ?>)</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Email</th>
                    <th>Asunto</th>
                    <th>Error</th>
                    <th>Intentos</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $rows = $pdo->query("SELECT id, recipient_email, subject, last_error, attempts FROM email_queue WHERE status='failed' ORDER BY id DESC LIMIT 10")->fetchAll();
                foreach ($rows as $row): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td class="email-cell"><?= htmlspecialchars($row['recipient_email']) ?></td>
                    <td class="subject-cell"><?= htmlspecialchars($row['subject']) ?></td>
                    <td style="color: red; font-size: 12px;"><?= htmlspecialchars(substr($row['last_error'], 0, 100)) ?></td>
                    <td><?= $row['attempts'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        
        <div class="actions">
            <a href="estado_envios.php" class="btn">← Volver a Estado</a>
            <?php if ($queued > 0): ?>
            <a href="procesar_cola.php?lotes=1" class="btn">▶ Procesar 1 Lote</a>
            <a href="procesar_cola.php?lotes=3" class="btn">▶ Procesar 3 Lotes</a>
            <?php endif; ?>
            <?php if ($failed > 0): ?>
            <form method="POST" style="display: inline;">
                <button type="submit" name="retry_failed" class="btn btn-danger" onclick="return confirm('¿Reintentar correos fallidos?')">🔄 Reintentar Fallidos</button>
            </form>
            <?php endif; ?>
        </div>
        
        <?php
        // Procesar reintentos
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['retry_failed'])) {
            $updated = $pdo->exec("UPDATE email_queue SET status='queued', attempts=0 WHERE status='failed'");
            echo '<div class="alert alert-info" style="margin-top: 20px;">✓ Se reiniciaron ' . $updated . ' correo(s) fallidos</div>';
        }
        ?>
    </div>
</body>
</html>
