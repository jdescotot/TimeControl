<?php
// test_password_encoding.php - Verificador de codificación de contraseña
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>🔍 Verificador de Codificación de Contraseña SMTP</h2>";

$config = include 'mail_config.php';
$smtp = $config['smtp'];

echo "<div style='font-family: monospace; background: #f0f0f0; padding: 20px; border-radius: 8px;'>";
echo "<h3>Configuración Actual:</h3>";
echo "<strong>Usuario:</strong> " . htmlspecialchars($smtp['user']) . "<br>";
echo "<strong>Contraseña:</strong> " . htmlspecialchars($smtp['pass']) . "<br><br>";

echo "<h3>Codificación Base64 (como se envía al servidor):</h3>";
$user_base64 = base64_encode($smtp['user']);
$pass_base64 = base64_encode($smtp['pass']);

echo "<strong>Usuario (Base64):</strong> " . $user_base64 . "<br>";
echo "<strong>Contraseña (Base64):</strong> " . $pass_base64 . "<br><br>";

echo "<h3>Verificación de Caracteres:</h3>";
echo "<strong>Longitud de contraseña:</strong> " . strlen($smtp['pass']) . " caracteres<br>";
echo "<strong>Caracteres especiales detectados:</strong><br>";

$special_chars = ['$', '@', '#', '%', '&', '/', '(', ')', '=', '?', '¡', '!', '¿', "'", '"', '\\', '.', ',', ';', ':'];
$found = [];
foreach ($special_chars as $char) {
    if (strpos($smtp['pass'], $char) !== false) {
        $found[] = $char;
    }
}

if (empty($found)) {
    echo "Ninguno<br>";
} else {
    echo implode(', ', $found) . "<br>";
}

echo "<br><h3>✅ Recomendaciones:</h3>";
echo "<ul>";
echo "<li>Verifica que la contraseña sea exactamente la misma que usas en el webmail</li>";
echo "<li>Si tienes 2FA activado, necesitas una <strong>Contraseña de Aplicación</strong></li>";
echo "<li>Prueba generar una contraseña de aplicación en el panel de IONOS</li>";
echo "<li>Contacta al soporte de IONOS: 900 102 413</li>";
echo "</ul>";

echo "</div>";

echo "<br><a href='test_smtp.php' style='display: inline-block; padding: 10px 20px; background: #2196F3; color: white; text-decoration: none; border-radius: 5px;'>← Volver al test SMTP</a>";
?>
