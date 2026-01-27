<?php
// serve_file.php - Sirve archivos subidos de forma segura desde mail_uploads/
$filename = $_GET['f'] ?? '';
if ($filename === '' || !preg_match('/^[a-f0-9]{24}\.[a-zA-Z0-9]{1,6}$/', $filename)) {
    http_response_code(400);
    echo 'Archivo no válido';
    exit;
}
$path = __DIR__ . '/mail_uploads/' . $filename;
if (!is_file($path)) {
    http_response_code(404);
    echo 'No encontrado';
    exit;
}
$mime = mime_content_type($path);
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
readfile($path);
