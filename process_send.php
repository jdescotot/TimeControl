<?php
// process_send.php - procesa el formulario y encola los correos en la tabla `email_queue`
session_start();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: enviar_correo.php');
    exit;
}

// Cargar configuración
if (!file_exists(__DIR__ . '/mail_config.php')) {
    die('Falta mail_config.php. Copia mail_config.php.example a mail_config.php y completa las credenciales.');
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

$subject = trim($_POST['subject'] ?? '');
$body = trim($_POST['body'] ?? '');
$recipients_source = $_POST['recipients_source'] ?? 'all_employees';

if ($subject === '' || $body === '') {
    die('Asunto y cuerpo son obligatorios.');
}

// Procesar archivos y mover a mail_uploads/
$upload_dir = __DIR__ . '/mail_uploads';
if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }
$attachments_saved = [];
if (!empty($_FILES['attachments'])) {
    for ($i=0; $i<count($_FILES['attachments']['name']); $i++) {
        if ($_FILES['attachments']['error'][$i] !== UPLOAD_ERR_OK) continue;
        $tmp = $_FILES['attachments']['tmp_name'][$i];
        $orig = basename($_FILES['attachments']['name'][$i]);
        $ext = pathinfo($orig, PATHINFO_EXTENSION);
        $newname = bin2hex(random_bytes(12)) . '.' . $ext;
        if (move_uploaded_file($tmp, $upload_dir . '/' . $newname)) {
            $attachments_saved[] = $newname;
        }
    }
}

$insert_stmt = $pdo->prepare("INSERT INTO email_queue (recipient_email, recipient_name, subject, body, attachments, status, attempts, created_at) VALUES (?, ?, ?, ?, ?, 'queued', 0, NOW())");

$enqueued = 0;
$attachments_json = json_encode($attachments_saved);

if ($recipients_source === 'csv' && isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
    $csv = fopen($_FILES['csv_file']['tmp_name'], 'r');
    while (($row = fgetcsv($csv)) !== false) {
        $email = trim($row[0] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) continue;
        $insert_stmt->execute([$email, null, $subject, $body, $attachments_json]);
        $enqueued++;
    }
    fclose($csv);
} elseif ($recipients_source === 'manual') {
    $manual_raw = trim($_POST['manual_emails'] ?? '');
    if ($manual_raw === '') {
        die('Debes ingresar al menos un correo para la opción manual.');
    }

    $candidates = preg_split('/[\s,;]+/', $manual_raw);
    foreach ($candidates as $candidate) {
        $email = trim($candidate);
        if ($email === '') continue;
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) continue;
        $insert_stmt->execute([$email, null, $subject, $body, $attachments_json]);
        $enqueued++;
    }

    if ($enqueued === 0) {
        header('Location: enviar_correo.php?error=' . urlencode('No se encontraron correos válidos en la entrada manual.'));
        exit;
    }
} else {
    // Tomar de la tabla tb_empleados
    $stmt = $pdo->query("SELECT email, nombre, apellidos FROM tb_empleados WHERE email IS NOT NULL AND email <> ''");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $email = trim($row['email']);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) continue;
        $name = trim(($row['nombre'] ?? '') . ' ' . ($row['apellidos'] ?? ''));
        $insert_stmt->execute([$email, $name, $subject, $body, $attachments_json]);
        $enqueued++;
    }
}

if ($enqueued === 0) {
    header('Location: enviar_correo.php?error=' . urlencode('No se encontraron destinatarios válidos.'));
    exit;
}

header('Location: enviar_correo.php?enqueued=' . $enqueued);
exit;
