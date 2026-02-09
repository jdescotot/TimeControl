<?php
// diagnostico.php - Verifica por qué no funcionan los correos
header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔍 Diagnóstico del Sistema de Correos</h1>";
echo "<style>
body { font-family: Arial; padding: 20px; background: #f5f5f5; }
.ok { background: #d4edda; padding: 10px; margin: 10px 0; border-radius: 5px; color: #155724; }
.error { background: #f8d7da; padding: 10px; margin: 10px 0; border-radius: 5px; color: #721c24; }
.warning { background: #fff3cd; padding: 10px; margin: 10px 0; border-radius: 5px; color: #856404; }
code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
</style>";

// 1. Verificar mail_config.php
echo "<h2>1️⃣ Archivo mail_config.php</h2>";
if (file_exists(__DIR__ . '/mail_config.php')) {
    echo '<div class="ok">✓ mail_config.php EXISTE</div>';
    $config = include __DIR__ . '/mail_config.php';
    
    if (isset($config['smtp']['host'])) {
        echo '<div class="ok">✓ SMTP configurado: ' . $config['smtp']['host'] . '</div>';
    } else {
        echo '<div class="error">✗ SMTP no configurado en mail_config.php</div>';
    }
} else {
    echo '<div class="error">✗ mail_config.php NO EXISTE</div>';
    echo '<p>Copia mail_config.php.example a mail_config.php</p>';
}

// 2. Verificar PHPMailer
echo "<h2>2️⃣ PHPMailer (Composer)</h2>";
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo '<div class="ok">✓ vendor/autoload.php EXISTE (PHPMailer instalado)</div>';
} else {
    echo '<div class="error">✗ vendor/autoload.php NO EXISTE</div>';
    echo '<p>Ejecuta en terminal: <code>composer require phpmailer/phpmailer</code></p>';
}

// 3. Verificar conexión a BD
echo "<h2>3️⃣ Conexión a Base de Datos</h2>";
if (file_exists(__DIR__ . '/mail_config.php')) {
    $config = include __DIR__ . '/mail_config.php';
    $db = $config['db'];
    
    try {
        $pdo = new PDO(
            "mysql:host={$db['host']};dbname={$db['name']};charset=utf8",
            $db['user'],
            $db['pass']
        );
        echo '<div class="ok">✓ Conexión a BD EXITOSA</div>';
        echo '<p>Host: ' . $db['host'] . '<br>Base de datos: ' . $db['name'] . '</p>';
        
        // 4. Verificar tabla email_queue
        echo "<h2>4️⃣ Tabla email_queue</h2>";
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM email_queue");
            $count = $stmt->fetchColumn();
            echo '<div class="ok">✓ Tabla email_queue EXISTE</div>';
            echo '<p>Correos en cola: ' . $count . '</p>';
            
            // Ver correos en cola
            $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM email_queue GROUP BY status");
            $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo '<table style="width:100%; border-collapse: collapse; margin-top: 10px;">';
            echo '<tr style="background: #ddd;"><th style="border: 1px solid #ccc; padding: 8px;">Status</th><th style="border: 1px solid #ccc; padding: 8px;">Cantidad</th></tr>';
            foreach ($stats as $row) {
                echo '<tr>';
                echo '<td style="border: 1px solid #ccc; padding: 8px;">' . $row['status'] . '</td>';
                echo '<td style="border: 1px solid #ccc; padding: 8px;">' . $row['count'] . '</td>';
                echo '</tr>';
            }
            echo '</table>';
            
        } catch (PDOException $e) {
            echo '<div class="error">✗ Tabla email_queue NO EXISTE</div>';
            echo '<p>Error: ' . $e->getMessage() . '</p>';
            echo '<p>Ejecuta este SQL:</p>';
            echo '<pre style="background: #f4f4f4; padding: 10px; overflow-x: auto;">
CREATE TABLE email_queue (
    id INT PRIMARY KEY AUTO_INCREMENT,
    recipient_email VARCHAR(255) NOT NULL,
    recipient_name VARCHAR(255),
    subject VARCHAR(255) NOT NULL,
    body LONGTEXT NOT NULL,
    attachments JSON,
    status ENUM(\'queued\', \'sending\', \'sent\', \'failed\') DEFAULT \'queued\',
    attempts INT DEFAULT 0,
    last_error TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    sent_at DATETIME,
    INDEX idx_status (status)
) CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            </pre>';
        }
        
    } catch (PDOException $e) {
        echo '<div class="error">✗ NO se puede conectar a BD</div>';
        echo '<p>Error: ' . $e->getMessage() . '</p>';
        echo '<p>Verifica credenciales en mail_config.php:</p>';
        echo '<ul>';
        echo '<li>Host: ' . $db['host'] . '</li>';
        echo '<li>Usuario: ' . $db['user'] . '</li>';
        echo '<li>Base de datos: ' . $db['name'] . '</li>';
        echo '</ul>';
    }
}

// 5. Verificar SMTP IONOS
echo "<h2>5️⃣ Servidor SMTP (IONOS)</h2>";
if (file_exists(__DIR__ . '/mail_config.php')) {
    $config = include __DIR__ . '/mail_config.php';
    $smtp = $config['smtp'];
    
    echo '<div class="warning">⚠ Configuración IONOS detectada:</div>';
    echo '<p>';
    echo 'Host: <code>' . $smtp['host'] . '</code><br>';
    echo 'Puerto: <code>' . $smtp['port'] . '</code><br>';
    echo 'Seguridad: <code>' . $smtp['secure'] . '</code><br>';
    echo 'Usuario: <code>' . $smtp['user'] . '</code>';
    echo '</p>';
    
    // Probar conexión SMTP
    echo "<h3>Prueba de conexión SMTP:</h3>";
    $socket = @fsockopen($smtp['host'], $smtp['port'], $errno, $errstr, 5);
    if ($socket) {
        echo '<div class="ok">✓ Conexión SMTP posible en ' . $smtp['host'] . ':' . $smtp['port'] . '</div>';
        fclose($socket);
    } else {
        echo '<div class="error">✗ NO se puede conectar a ' . $smtp['host'] . ':' . $smtp['port'] . '</div>';
        echo '<p>Error: ' . $errstr . ' (código ' . $errno . ')</p>';
        echo '<p>Posibles causas:</p>';
        echo '<ul>';
        echo '<li>El servidor está caído</li>';
        echo '<li>El puerto está bloqueado por firewall</li>';
        echo '<li>Host incorrecto</li>';
        echo '</ul>';
    }
}

// 6. Verificar permisos de directorios
echo "<h2>6️⃣ Permisos de Directorios</h2>";
$dirs = [
    'mail_uploads' => __DIR__ . '/mail_uploads',
];
foreach ($dirs as $name => $dir) {
    if (is_dir($dir)) {
        if (is_writable($dir)) {
            echo '<div class="ok">✓ Directorio ' . $name . ' es escribible</div>';
        } else {
            echo '<div class="error">✗ Directorio ' . $name . ' NO es escribible</div>';
            echo '<p>Ejecuta: <code>chmod 755 ' . $dir . '</code></p>';
        }
    } else {
        echo '<div class="warning">⚠ Directorio ' . $name . ' no existe (se crea automáticamente)</div>';
    }
}

// 7. Verificar extensiones PHP
echo "<h2>7️⃣ Extensiones PHP Requeridas</h2>";
$extensions = ['pdo', 'pdo_mysql', 'json'];
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo '<div class="ok">✓ Extensión ' . $ext . ' está habilitada</div>';
    } else {
        echo '<div class="error">✗ Extensión ' . $ext . ' NO está habilitada</div>';
    }
}

// 8. Ver logs de error
echo "<h2>8️⃣ Últimos Errores PHP</h2>";
$log_file = ini_get('error_log');
if ($log_file && file_exists($log_file)) {
    echo '<p>Archivo de log: <code>' . $log_file . '</code></p>';
    $lines = file($log_file);
    $recent = array_slice($lines, -20);
    echo '<pre style="background: #f4f4f4; padding: 10px; max-height: 300px; overflow-y: auto; border-radius: 5px;">';
    foreach ($recent as $line) {
        echo htmlspecialchars($line);
    }
    echo '</pre>';
} else {
    echo '<div class="warning">⚠ No se encontró archivo de log de PHP</div>';
    echo '<p>Mira la consola del navegador (F12) para ver errores</p>';
}

echo "<hr>";
echo "<p>💡 Si todo está en verde (✓), el error 500 está en <code>procesar_cola.php</code></p>";
echo "<p>📞 Vuelve aquí después de verificar todo</p>";
?>
