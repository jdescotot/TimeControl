<?php
// Activar display de errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Verificar sintaxis de dueño.php sin ejecutarlo
$syntax_check = shell_exec("php -l " . escapeshellarg(__DIR__ . '/dueño.php') . " 2>&1");

echo "<h1>Verificación de Sintaxis - dueño.php</h1>";
echo "<pre>";
echo htmlspecialchars($syntax_check);
echo "</pre>";

// Si hay error de sintaxis, mostrarlo
if (strpos($syntax_check, 'Parse error') !== false || strpos($syntax_check, 'Syntax error') !== false) {
    echo "<p style='color: red;'><b>❌ HAY ERROR DE SINTAXIS EN dueño.php</b></p>";
} else {
    echo "<p style='color: green;'><b>✅ Sintaxis correcta</b></p>";
}

// Verificar también el archivo empleado.css
echo "<h2>Verificar CSS</h2>";
echo "<p><b>dueño.css</b>: " . (file_exists('dueño.css') ? '✅ EXISTS' : '❌ NOT FOUND') . "</p>";
echo "<p><b>empleado.css</b>: " . (file_exists('empleado.css') ? '✅ EXISTS' : '❌ NOT FOUND') . "</p>";
echo "<p><b>solicitudes_cambio.css</b>: " . (file_exists('solicitudes_cambio.css') ? '✅ EXISTS' : '❌ NOT FOUND') . "</p>";

// Intentar ver qué línea causa problema
echo "<h2>Líneas clave del archivo</h2>";
$lines = file(__DIR__ . '/dueño.php');
echo "<p>Total de líneas: " . count($lines) . "</p>";

// Verificar línea de <?php al inicio
echo "<p>Línea 1: " . htmlspecialchars(trim($lines[0])) . "</p>";
echo "<p>Última línea: " . htmlspecialchars(trim(end($lines))) . "</p>";

?>
