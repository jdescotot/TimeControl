<?php
/**
 * VERIFICADOR: Comprueba si las columnas GPS estÃ¡n en la tabla marcaciones
 * Si NO existen, redirecciona al script de migraciÃ³n
 */
session_start();
require_once 'config.php';

if (!es_dueno_o_gerente()) {
    die('Acceso denegado.');
}

echo "<h1>ðŸ” VerificaciÃ³n de Estructura GPS en BD</h1>";
echo "<hr>";

$columnas_requeridas = ['lat_entrada', 'lng_entrada', 'lat_salida', 'lng_salida'];
$columnas_faltantes = [];
$columnas_ok = [];

foreach ($columnas_requeridas as $col) {
    $check = $pdo->prepare("
        SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'marcaciones'
          AND COLUMN_NAME  = ?
    ");
    $check->execute([$col]);
    
    if ((int)$check->fetchColumn() > 0) {
        $columnas_ok[] = $col;
        echo "<p style='color: green;'>âœ… Columna <b>$col</b> existe</p>";
    } else {
        $columnas_faltantes[] = $col;
        echo "<p style='color: red;'>âŒ Columna <b>$col</b> NO existe</p>";
    }
}

echo "<hr>";

if (empty($columnas_faltantes)) {
    echo "<p style='color: green; font-size: 1.2rem; font-weight: bold;'>âœ… Todas las columnas de GPS estÃ¡n creadas</p>";
    echo "<p>El sistema estÃ¡ listo para guardar y mostrar ubicaciones.</p>";
    echo "<p><a href='diagnostico_mapa.php'>â†’ Ver diagnÃ³stico del mapa</a></p>";
} else {
    echo "<p style='color: red; font-size: 1.2rem; font-weight: bold;'>âŒ Faltan " . count($columnas_faltantes) . " columna(s)</p>";
    echo "<p>Columnas faltantes: <b>" . implode(', ', $columnas_faltantes) . "</b></p>";
    echo "<p><strong>Necesitas ejecutar la migraciÃ³n:</strong></p>";
    echo "<p><a href='migrar_gps.php' style='background: #667eea; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; display: inline-block;'>â†’ Ejecutar MigraciÃ³n GPS</a></p>";
}

echo "<hr>";
echo "<p style='color: gray; font-size: 12px;'>Generado: " . date('Y-m-d H:i:s') . "</p>";
?>

<style>
body {
    font-family: Arial, sans-serif;
    margin: 40px;
    background-color: #f5f5f5;
}
h1 {
    color: #333;
    font-size: 1.6rem;
}
a {
    color: #667eea;
}
</style>

