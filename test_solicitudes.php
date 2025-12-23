<?php
session_start();
require_once 'config.php';

echo "<h1>Test de Solicitudes - Debug</h1>";
echo "<style>body { font-family: Arial; padding: 20px; } table { border-collapse: collapse; width: 100%; margin: 20px 0; } th, td { border: 1px solid #ddd; padding: 8px; text-align: left; } th { background-color: #667eea; color: white; } .info { background: #e3f2fd; padding: 15px; margin: 10px 0; border-left: 4px solid #2196F3; } .success { background: #c8e6c9; border-left-color: #4caf50; } .error { background: #ffcdd2; border-left-color: #f44336; }</style>";

// Test 1: Verificar conexión
echo "<div class='info'><strong>Test 1:</strong> Conexión a base de datos</div>";
try {
    $test = $pdo->query("SELECT 1");
    echo "<div class='success'>✓ Conexión exitosa</div>";
} catch (Exception $e) {
    echo "<div class='error'>✗ Error de conexión: " . $e->getMessage() . "</div>";
    die();
}

// Test 2: Verificar si existe la tabla
echo "<div class='info'><strong>Test 2:</strong> Verificar tabla solicitudes_cambio</div>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'solicitudes_cambio'");
    if ($stmt->rowCount() > 0) {
        echo "<div class='success'>✓ Tabla 'solicitudes_cambio' existe</div>";
    } else {
        echo "<div class='error'>✗ Tabla 'solicitudes_cambio' NO existe</div>";
        die();
    }
} catch (Exception $e) {
    echo "<div class='error'>✗ Error: " . $e->getMessage() . "</div>";
    die();
}

// Test 3: Ver estructura de la tabla
echo "<div class='info'><strong>Test 3:</strong> Estructura de la tabla</div>";
try {
    $stmt = $pdo->query("DESCRIBE solicitudes_cambio");
    $columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table><tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columnas as $col) {
        echo "<tr>";
        echo "<td>" . $col['Field'] . "</td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . $col['Key'] . "</td>";
        echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<div class='error'>✗ Error: " . $e->getMessage() . "</div>";
}

// Test 4: Contar todas las solicitudes
echo "<div class='info'><strong>Test 4:</strong> Total de solicitudes en la tabla</div>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM solicitudes_cambio");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<div class='success'>✓ Total de solicitudes: <strong>" . $result['total'] . "</strong></div>";
} catch (Exception $e) {
    echo "<div class='error'>✗ Error: " . $e->getMessage() . "</div>";
}

// Test 5: Ver todos los registros
echo "<div class='info'><strong>Test 5:</strong> Todos los registros</div>";
try {
    $stmt = $pdo->query("SELECT * FROM solicitudes_cambio ORDER BY id DESC");
    $solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($solicitudes) > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Marcación ID</th><th>Empleado ID</th><th>Nueva Entrada</th><th>Nueva Salida</th><th>Estado</th><th>Motivo</th></tr>";
        foreach ($solicitudes as $sol) {
            echo "<tr>";
            echo "<td>" . $sol['id'] . "</td>";
            echo "<td>" . $sol['marcacion_id'] . "</td>";
            echo "<td>" . $sol['empleado_id'] . "</td>";
            echo "<td>" . $sol['nueva_hora_entrada'] . "</td>";
            echo "<td>" . $sol['nueva_hora_salida'] . "</td>";
            echo "<td><strong>" . ($sol['estado'] ?? 'NULL') . "</strong></td>";
            echo "<td>" . substr($sol['motivo'], 0, 50) . "...</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='error'>✗ No hay registros en la tabla</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>✗ Error: " . $e->getMessage() . "</div>";
}

// Test 6: Contar solicitudes pendientes
echo "<div class='info'><strong>Test 6:</strong> Solicitudes con estado 'pendiente'</div>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM solicitudes_cambio WHERE estado = 'pendiente'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<div class='success'>✓ Solicitudes pendientes: <strong>" . $result['total'] . "</strong></div>";
    
    if ($result['total'] == 0) {
        echo "<div class='error'>⚠ Hay 0 solicitudes pendientes. Verifica que el campo 'estado' tenga exactamente el valor 'pendiente' (en minúsculas)</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>✗ Error: " . $e->getMessage() . "</div>";
}

// Test 7: Ver valores únicos del campo estado
echo "<div class='info'><strong>Test 7:</strong> Valores únicos en campo 'estado'</div>";
try {
    $stmt = $pdo->query("SELECT DISTINCT estado, COUNT(*) as cantidad FROM solicitudes_cambio GROUP BY estado");
    $estados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($estados) > 0) {
        echo "<table><tr><th>Estado</th><th>Cantidad</th></tr>";
        foreach ($estados as $est) {
            echo "<tr>";
            echo "<td>" . ($est['estado'] ?? 'NULL') . "</td>";
            echo "<td>" . $est['cantidad'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<div class='error'>✗ Error: " . $e->getMessage() . "</div>";
}

echo "<hr><p><a href='dueño.php'>← Volver al panel del dueño</a></p>";
?>