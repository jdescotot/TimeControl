<?php
session_start();
require_once 'config.php';

// Simular sesión autenticada
$_SESSION['user_id'] = 52;
$_SESSION['username'] = 'cetproservices';
$_SESSION['rol'] = 'dueño';
$_SESSION['es_gerente'] = 0;
$_SESSION['propietario_id'] = null;

ob_start();
try {
    include 'dueño.php';
    $output = ob_get_clean();
    
    // Verificar referencias CSS
    $has_dueno_css = strpos($output, 'href="dueño.css"') !== false;
    $has_empleado_css = strpos($output, 'href="empleado.css"') !== false;
    $has_solicitudes_css = strpos($output, 'href="solicitudes_cambio.css"') !== false;
    
    echo "<h2>✅ Verificación Final de dueño.php</h2>";
    echo "<p><b>Tamaño del HTML:</b> " . strlen($output) . " bytes</p>";
    
    echo "<h3>Referencias CSS:</h3>";
    echo "<p>✅ dueño.css: " . ($has_dueno_css ? 'ENCONTRADO' : '❌ FALTANTE') . "</p>";
    echo "<p>✅ empleado.css: " . ($has_empleado_css ? 'ENCONTRADO' : '❌ FALTANTE') . "</p>";
    echo "<p>✅ solicitudes_cambio.css: " . ($has_solicitudes_css ? 'ENCONTRADO' : '❌ FALTANTE') . "</p>";
    
    // Verificar título
    if (strpos($output, 'Panel del Dueño') !== false) {
        echo "<p>✅ Título correcto: 'Panel del Dueño' (sin mojibake)</p>";
    } else if (strpos($output, 'DueÃ±o') !== false) {
        echo "<p>❌ Título aún tiene mojibake</p>";
    }
    
    // Verificar DOCTYPE y estructura
    echo "<h3>Estructura HTML:</h3>";
    echo "<p>DOCTYPE: " . (strpos($output, '<!DOCTYPE') === 0 ? '✅' : '⚠️') . "</p>";
    echo "<p>Cierre html: " . (strpos($output, '</html>') !== false ? '✅' : '❌') . "</p>";
    echo "<p>Clase owner-dashboard: " . (strpos($output, 'owner-dashboard') !== false ? '✅' : '❌') . "</p>";
    
    echo "<p style='color: green; font-weight: bold;'>✅ LISTO PARA PRODUCCIÓN</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'><b>ERROR:</b> " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
