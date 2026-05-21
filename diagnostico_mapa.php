<?php
/**
 * SCRIPT DE DIAGNÃ“STICO: Verifica la configuraciÃ³n del mapa de marcaciones
 * 
 * Muestra:
 * - Datos GPS guardados en BD
 * - Empleados sin GPS
 * - Zonas permitidas
 * - EstadÃ­sticas de marcaciones
 */
session_start();
require_once 'config.php';
require_once 'jaen_geocoder.php';

if (!es_dueno_o_gerente()) {
    die('Acceso denegado.');
}

$dueno_id = owner_scope_id($pdo);
$hoy = date('Y-m-d');
$hace_3_dias = date('Y-m-d', strtotime('-2 days'));

echo "<h1>ðŸ” DiagnÃ³stico - Mapa de Marcaciones</h1>";
echo "<hr>";

// â”€â”€ 1. VERIFICAR ZONAS PERMITIDAS â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
echo "<h2>ðŸ“ Zonas Permitidas de MarcaciÃ³n</h2>";
$allowed = getAllowedMarkingLocations();
if (empty($allowed)) {
    echo "<p style='color: red;'>âš ï¸ <b>NO hay zonas permitidas configuradas</b></p>";
} else {
    echo "<ul>";
    foreach ($allowed as $zone) {
        echo "<li><b>{$zone['name']}</b> - Lat: {$zone['lat']}, Lng: {$zone['lng']}, Radio: {$zone['radius_m']}m</li>";
    }
    echo "</ul>";
}

// â”€â”€ 2. ESTADÃSTICAS DE MARCACIONES â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
echo "<h2>ðŸ“Š EstadÃ­sticas de Marcaciones (Ãºltimos 3 dÃ­as)</h2>";

try {
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN lat_entrada IS NOT NULL AND lng_entrada IS NOT NULL THEN 1 ELSE 0 END) as entrada_gps,
            SUM(CASE WHEN lat_salida IS NOT NULL AND lng_salida IS NOT NULL THEN 1 ELSE 0 END) as salida_gps,
            SUM(CASE WHEN (lat_entrada IS NOT NULL AND lng_entrada IS NOT NULL) OR (lat_salida IS NOT NULL AND lng_salida IS NOT NULL) THEN 1 ELSE 0 END) as con_ubicacion
        FROM marcaciones m
        INNER JOIN usuarios u ON m.empleado_id = u.id
        WHERE u.propietario_id = ? AND DATE(m.entrada) BETWEEN ? AND ?
    ");
    $stmt->execute([$dueno_id, $hace_3_dias, $hoy]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><td><b>Total Marcaciones</b></td><td>{$stats['total']}</td></tr>";
    echo "<tr><td><b>Con GPS Entrada</b></td><td>{$stats['entrada_gps']}</td></tr>";
    echo "<tr><td><b>Con GPS Salida</b></td><td>{$stats['salida_gps']}</td></tr>";
    echo "<tr><td><b>Con Alguna UbicaciÃ³n</b></td><td style='background-color: #d4edda;'><b>{$stats['con_ubicacion']}</b></td></tr>";
    echo "</table>";
    
    if ($stats['con_ubicacion'] == 0) {
        echo "<p style='color: red; font-weight: bold;'>âš ï¸ PROBLEMA: Ninguna marcaciÃ³n tiene coordenadas GPS</p>";
        echo "<p>Posibles causas:</p>";
        echo "<ul>";
        echo "<li>Los empleados no tienen GPS habilitado en el navegador</li>";
        echo "<li>Los empleados estÃ¡n fuera de las zonas permitidas</li>";
        echo "<li>El formulario de marcaciÃ³n no estÃ¡ enviando las coordenadas</li>";
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>âŒ Error: " . $e->getMessage() . "</p>";
}

// â”€â”€ 3. EMPLEADOS Y SUS MARCACIONES CON GPS â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
echo "<h2>ðŸ‘¥ Detalle por Empleado</h2>";

try {
    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.nombre,
            u.apellido,
            u.username,
            COUNT(m.id) as total_marcaciones,
            SUM(CASE WHEN m.lat_entrada IS NOT NULL THEN 1 ELSE 0 END) as entrada_gps,
            SUM(CASE WHEN m.lat_salida IS NOT NULL THEN 1 ELSE 0 END) as salida_gps
        FROM usuarios u
        LEFT JOIN marcaciones m ON u.id = m.empleado_id AND DATE(m.entrada) BETWEEN ? AND ?
        WHERE u.propietario_id = ?
        GROUP BY u.id
        ORDER BY u.nombre, u.apellido
    ");
    $stmt->execute([$hace_3_dias, $hoy, $dueno_id]);
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='8'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>Empleado</th><th>Marcaciones</th><th>Entrada GPS</th><th>Salida GPS</th><th>Estado</th>";
    echo "</tr>";
    
    foreach ($empleados as $emp) {
        $status = $emp['entrada_gps'] > 0 || $emp['salida_gps'] > 0 ? 
            "âœ… OK" : 
            ($emp['total_marcaciones'] > 0 ? "âš ï¸ Sin GPS" : "â¸ï¸ Sin marcar");
        $status_color = strpos($status, 'âœ…') !== false ? 'lightgreen' : 
                       (strpos($status, 'âš ï¸') !== false ? 'lightyellow' : 'lightgray');
        
        echo "<tr>";
        echo "<td>{$emp['nombre']} {$emp['apellido']} ({$emp['username']})</td>";
        echo "<td>{$emp['total_marcaciones']}</td>";
        echo "<td>{$emp['entrada_gps']}</td>";
        echo "<td>{$emp['salida_gps']}</td>";
        echo "<td style='background-color: {$status_color};'>{$status}</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color: red;'>âŒ Error: " . $e->getMessage() . "</p>";
}

// â”€â”€ 4. MUESTRA DE DATOS RAW â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
echo "<h2>ðŸ”¬ Muestra de Datos (Ãšltimas 5 Marcaciones)</h2>";

try {
    $stmt = $pdo->prepare("
        SELECT 
            m.id,
            u.nombre,
            u.apellido,
            m.entrada,
            m.salida,
            m.lat_entrada,
            m.lng_entrada,
            m.lat_salida,
            m.lng_salida
        FROM marcaciones m
        INNER JOIN usuarios u ON m.empleado_id = u.id
        WHERE u.propietario_id = ? AND DATE(m.entrada) BETWEEN ? AND ?
        ORDER BY m.entrada DESC
        LIMIT 5
    ");
    $stmt->execute([$dueno_id, $hace_3_dias, $hoy]);
    $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($samples)) {
        echo "<p>No hay marcaciones en los Ãºltimos 3 dÃ­as</p>";
    } else {
        echo "<table border='1' cellpadding='8' style='font-size: 12px;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th>ID</th><th>Empleado</th><th>Entrada</th><th>Lat Entrada</th><th>Lng Entrada</th><th>Salida</th><th>Lat Salida</th><th>Lng Salida</th>";
        echo "</tr>";
        
        foreach ($samples as $row) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['nombre']} {$row['apellido']}</td>";
            echo "<td>{$row['entrada']}</td>";
            echo "<td>" . ($row['lat_entrada'] ? number_format($row['lat_entrada'], 6) : 'â€”') . "</td>";
            echo "<td>" . ($row['lng_entrada'] ? number_format($row['lng_entrada'], 6) : 'â€”') . "</td>";
            echo "<td>{$row['salida']}</td>";
            echo "<td>" . ($row['lat_salida'] ? number_format($row['lat_salida'], 6) : 'â€”') . "</td>";
            echo "<td>" . ($row['lng_salida'] ? number_format($row['lng_salida'], 6) : 'â€”') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>âŒ Error: " . $e->getMessage() . "</p>";
}

// â”€â”€ 5. RECOMENDACIONES â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
echo "<h2>ðŸ“‹ Recomendaciones</h2>";
echo "<ol>";
echo "<li><a href='mapa_marcaciones.php?rango=3dias'>Ver Mapa - Ãšltimos 3 dÃ­as</a></li>";
echo "<li><a href='mapa_marcaciones.php?rango=7dias'>Ver Mapa - Ãšltimos 7 dÃ­as</a></li>";
echo "<li>Si no hay datos con GPS:
    <ul>
    <li>Verifica que los empleados marquen entrada/salida en <b>empleado.php</b> (debe solicitar ubicaciÃ³n)</li>
    <li>Revisa que los empleados tengan GPS habilitado en el navegador</li>
    <li>Comprueba que estÃ©n dentro de una zona permitida</li>
    </ul>
</li>";
echo "</ol>";

echo "<hr>";
echo "<p style='color: gray; font-size: 12px;'>Generado: " . date('Y-m-d H:i:s') . "</p>";
?>

<style>
body {
    font-family: Arial, sans-serif;
    margin: 20px;
    background-color: #f5f5f5;
}
h1, h2 {
    color: #333;
}
table {
    border-collapse: collapse;
    background-color: white;
    margin: 15px 0;
}
td, th {
    text-align: left;
}
</style>

