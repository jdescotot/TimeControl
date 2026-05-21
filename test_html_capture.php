<?php
// Capturar la salida de dueño.php sin enviarla al navegador
session_start();

// Simular que estamos autenticados como dueño
$_SESSION['user_id'] = 52;
$_SESSION['username'] = 'cetproservices';
$_SESSION['rol'] = 'dueño';
$_SESSION['es_gerente'] = 0;
$_SESSION['propietario_id'] = null;

// Incluir el archivo sin que salga el HTML directamente
ob_start();

try {
    // Incluir dueño.php pero capturar su salida
    include 'dueño.php';
    $html = ob_get_clean();
    
    // Mostrar el resultado como plain text con análisis
    echo "<pre>";
    echo "LONGITUD DEL HTML: " . strlen($html) . " bytes\n\n";
    
    // Contar etiquetas
    $open_tags = substr_count($html, '<');
    $close_tags = substr_count($html, '>');
    echo "Etiquetas abiertas (<): $open_tags\n";
    echo "Etiquetas cerradas (>): $close_tags\n\n";
    
    // Verificar primeras líneas
    echo "PRIMERAS 500 CARACTERES:\n";
    echo htmlspecialchars(substr($html, 0, 500)) . "\n\n";
    
    // Verificar últimas líneas
    echo "ÚLTIMAS 500 CARACTERES:\n";
    echo htmlspecialchars(substr($html, -500)) . "\n\n";
    
    // Buscar errores de sintaxis HTML
    echo "ANÁLISIS DE BALANCEO:\n";
    if ($open_tags === $close_tags) {
        echo "✅ Etiquetas balanceadas\n";
    } else {
        echo "❌ DESEQUILIBRIO: Diferencia de " . abs($open_tags - $close_tags) . " etiquetas\n";
    }
    
    // Verificar si tiene <!DOCTYPE
    if (strpos($html, '<!DOCTYPE') === 0) {
        echo "✅ Tiene DOCTYPE\n";
    } else {
        echo "❌ Falta DOCTYPE\n";
    }
    
    // Verificar si tiene </html>
    if (strpos($html, '</html>') !== false) {
        echo "✅ Tiene cierre </html>\n";
    } else {
        echo "❌ Falta cierre </html>\n";
    }
    
    // Buscar etiquetas de script vacías o incompletas
    echo "\nANÁLISIS DE SCRIPTS:\n";
    preg_match_all('/<script[^>]*>/i', $html, $script_opens);
    preg_match_all('/<\/script>/i', $html, $script_closes);
    echo "Scripts abiertos: " . count($script_opens[0]) . "\n";
    echo "Scripts cerrados: " . count($script_closes[0]) . "\n";
    
    // Mostrar el HTML completo para inspección
    echo "\n\n=== HTML COMPLETO ===\n";
    echo htmlspecialchars($html);
    
} catch (Throwable $e) {
    echo "<p style='color: red;'><b>❌ ERROR:</b> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    ob_end_clean();
}
?>
