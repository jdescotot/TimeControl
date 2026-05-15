<?php
/**
 * SCRIPT DE DIAGNÓSTICO: Verifica la configuración del mapa de marcaciones
 * 
 * Muestra:
 * - Datos GPS guardados en BD
 * - Empleados sin GPS
 * - Zonas permitidas
 * - Estadísticas de marcaciones
 */
session_start();
require_once 'config.php';
require_once 'jaen_geocoder.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'dueño') {
    die('❌ Acceso denegado. Solo para dueños.');
}

$dueño_id = (int)$_SESSION['user_id'];
$hoy = date('Y-m-d');
$hace_3_dias = date('Y-m-d', strtotime('-2 days'));

echo "<h1>🔍 Diagnóstico - Mapa de Marcaciones</h1>";
echo "<hr>";

// ── 1. VERIFICAR ZONAS PERMITIDAS ────────────────────────────────────
echo "<h2>📍 Zonas Permitidas de Marcación</h2>";
$allowed = getAllowedMarkingLocations();
if (empty($allowed)) {
    echo "<p style='color: red;'>⚠️ <b>NO hay zonas permitidas configuradas</b></p>";
} else {
    echo "<ul>";
    foreach ($allowed as $zone) {
        echo "<li><b>{$zone['name']}</b> - Lat: {$zone['lat']}, Lng: {$zone['lng']}, Radio: {$zone['radius_m']}m</li>";
    }
    echo "</ul>";
}

// ── 2. ESTADÍSTICAS DE MARCACIONES ────────────────────────────────────
echo "<h2>📊 Estadísticas de Marcaciones (últimos 3 días)</h2>";

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
    $stmt->execute([$dueño_id, $hace_3_dias, $hoy]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><td><b>Total Marcaciones</b></td><td>{$stats['total']}</td></tr>";
    echo "<tr><td><b>Con GPS Entrada</b></td><td>{$stats['entrada_gps']}</td></tr>";
    echo "<tr><td><b>Con GPS Salida</b></td><td>{$stats['salida_gps']}</td></tr>";
    echo "<tr><td><b>Con Alguna Ubicación</b></td><td style='background-color: #d4edda;'><b>{$stats['con_ubicacion']}</b></td></tr>";
    echo "</table>";
    
    if ($stats['con_ubicacion'] == 0) {
        echo "<p style='color: red; font-weight: bold;'>⚠️ PROBLEMA: Ninguna marcación tiene coordenadas GPS</p>";
        echo "<p>Posibles causas:</p>";
        echo "<ul>";
        echo "<li>Los empleados no tienen GPS habilitado en el navegador</li>";
        echo "<li>Los empleados están fuera de las zonas permitidas</li>";
        echo "<li>El formulario de marcación no está enviando las coordenadas</li>";
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

// ── 3. EMPLEADOS Y SUS MARCACIONES CON GPS ────────────────────────────
echo "<h2>👥 Detalle por Empleado</h2>";

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
    $stmt->execute([$hace_3_dias, $hoy, $dueño_id]);
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='8'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>Empleado</th><th>Marcaciones</th><th>Entrada GPS</th><th>Salida GPS</th><th>Estado</th>";
    echo "</tr>";
    
    foreach ($empleados as $emp) {
        $status = $emp['entrada_gps'] > 0 || $emp['salida_gps'] > 0 ? 
            "✅ OK" : 
            ($emp['total_marcaciones'] > 0 ? "⚠️ Sin GPS" : "⏸️ Sin marcar");
        $status_color = strpos($status, '✅') !== false ? 'lightgreen' : 
                       (strpos($status, '⚠️') !== false ? 'lightyellow' : 'lightgray');
        
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
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

// ── 4. MUESTRA DE DATOS RAW ────────────────────────────────────
echo "<h2>🔬 Muestra de Datos (Últimas 5 Marcaciones)</h2>";

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
    $stmt->execute([$dueño_id, $hace_3_dias, $hoy]);
    $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($samples)) {
        echo "<p>No hay marcaciones en los últimos 3 días</p>";
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
            echo "<td>" . ($row['lat_entrada'] ? number_format($row['lat_entrada'], 6) : '—') . "</td>";
            echo "<td>" . ($row['lng_entrada'] ? number_format($row['lng_entrada'], 6) : '—') . "</td>";
            echo "<td>{$row['salida']}</td>";
            echo "<td>" . ($row['lat_salida'] ? number_format($row['lat_salida'], 6) : '—') . "</td>";
            echo "<td>" . ($row['lng_salida'] ? number_format($row['lng_salida'], 6) : '—') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

// ── 5. RECOMENDACIONES ────────────────────────────────────────────────
echo "<h2>📋 Recomendaciones</h2>";
echo "<ol>";
echo "<li><a href='mapa_marcaciones.php?rango=3dias'>Ver Mapa - Últimos 3 días</a></li>";
echo "<li><a href='mapa_marcaciones.php?rango=7dias'>Ver Mapa - Últimos 7 días</a></li>";
echo "<li>Si no hay datos con GPS:
    <ul>
    <li>Verifica que los empleados marquen entrada/salida en <b>empleado.php</b> (debe solicitar ubicación)</li>
    <li>Revisa que los empleados tengan GPS habilitado en el navegador</li>
    <li>Comprueba que estén dentro de una zona permitida</li>
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
