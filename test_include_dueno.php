<?php
session_start();
require_once 'config.php';

// Simular sesión autenticada como dueño
$_SESSION['user_id'] = 52;
$_SESSION['username'] = 'cetproservices';
$_SESSION['rol'] = 'dueño';
$_SESSION['es_gerente'] = 0;
$_SESSION['propietario_id'] = null;

echo "<h1>Intento de incluir dueño.php</h1>";
echo "<p>Sesión configurada como dueño</p>";

// Capturar cualquier error
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    echo "<p style='color: red;'><b>ERROR DETECTADO:</b><br>";
    echo "Tipo: " . $errstr . "<br>";
    echo "Archivo: " . $errfile . "<br>";
    echo "Línea: " . $errline . "</p>";
    return true; // No mostrar error predeterminado
});

set_exception_handler(function(Throwable $e) {
    echo "<p style='color: red;'><b>EXCEPCIÓN DETECTADA:</b><br>";
    echo "Mensaje: " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "Archivo: " . $e->getFile() . "<br>";
    echo "Línea: " . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
});

try {
    // Capturar salida
    ob_start();
    
    // Incluir el archivo
    include 'dueño.php';
    
    // Si llegamos aquí sin die/exit, capturar la salida
    $output = ob_get_clean();
    
    echo "<p style='color: green;'><b>✅ Archivo procesado correctamente</b></p>";
    echo "<p>Salida generada: " . strlen($output) . " bytes</p>";
    
    // Mostrar primeros 500 caracteres
    echo "<p><b>Primeros 500 caracteres:</b></p>";
    echo "<pre>" . htmlspecialchars(substr($output, 0, 500)) . "</pre>";
    
    // Mostrar últimos 500 caracteres
    echo "<p><b>Últimos 500 caracteres:</b></p>";
    echo "<pre>" . htmlspecialchars(substr($output, -500)) . "</pre>";
    
    // Verificar si es HTML válido
    if (strpos($output, '<!DOCTYPE') === 0) {
        echo "<p style='color: green;'><b>✅ Comienza con DOCTYPE</b></p>";
    } else {
        echo "<p style='color: red;'><b>❌ NO comienza con DOCTYPE</b></p>";
    }
    
    // Verificar cierre
    if (strpos($output, '</html>') !== false) {
        echo "<p style='color: green;'><b>✅ Tiene cierre &lt;/html&gt;</b></p>";
    } else {
        echo "<p style='color: red;'><b>❌ Falta &lt;/html&gt;</b></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><b>ERROR:</b> " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Restaurar handlers
restore_error_handler();
restore_exception_handler();
?>
