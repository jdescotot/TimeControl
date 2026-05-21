<?php
session_start();
require_once 'config.php';

echo "<h1>Diagnóstico de dueño.php</h1>";

// 1. Verificar autenticación
echo "<h2>1. Sesión</h2>";
echo "<p>Rol: " . htmlspecialchars($_SESSION['rol'] ?? 'NO DEFINIDO') . "</p>";
echo "<p>User ID: " . htmlspecialchars($_SESSION['user_id'] ?? 'NO DEFINIDO') . "</p>";
echo "<p>Username: " . htmlspecialchars($_SESSION['username'] ?? 'NO DEFINIDO') . "</p>";

// 2. Verificar guarda
try {
    $dueno_id = require_dueno_o_gerente($pdo);
    echo "<h2>2. Guard</h2>";
    echo "<p style='color: green;'><b>✅ Guard OK - Dueño ID: $dueno_id</b></p>";
} catch (Exception $e) {
    echo "<h2>2. Guard</h2>";
    echo "<p style='color: red;'><b>❌ Error en guard: " . htmlspecialchars($e->getMessage()) . "</b></p>";
    die();
}

// 3. Probar queries
echo "<h2>3. Queries</h2>";

try {
    // Query 1: Solicitudes
    $stmt_pendientes = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM solicitudes_cambio sc
        INNER JOIN usuarios u ON sc.empleado_id = u.id
        WHERE sc.estado IN ('pendiente', 'rechazado_empleado') AND u.propietario_id = ?
    ");
    $stmt_pendientes->execute([$dueno_id]);
    $resultado = $stmt_pendientes->fetch(PDO::FETCH_ASSOC);
    echo "<p><b>✅ Query 1 (solicitudes):</b> OK - Total: {$resultado['total']}</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'><b>❌ Query 1 ERROR:</b> " . htmlspecialchars($e->getMessage()) . "</p>";
}

try {
    // Query 2: Empleados
    $stmt_empleados = $pdo->prepare(" 
        SELECT id, username, nombre, es_gerente
        FROM usuarios 
        WHERE rol = 'empleado' 
        AND propietario_id = ? 
        ORDER BY nombre IS NULL OR nombre = '', nombre, username
    ");
    $stmt_empleados->execute([$dueno_id]);
    $empleados = $stmt_empleados->fetchAll(PDO::FETCH_ASSOC);
    echo "<p><b>✅ Query 2 (empleados):</b> OK - Total: " . count($empleados) . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'><b>❌ Query 2 ERROR:</b> " . htmlspecialchars($e->getMessage()) . "</p>";
}

try {
    // Query 3: Descansos
    $hoy = date('Y-m-d');
    $stmt_descansos = $pdo->prepare("SELECT empleado_id FROM horarios_semanales WHERE fecha_descanso = ?");
    $stmt_descansos->execute([$hoy]);
    $descansos = $stmt_descansos->fetchAll(PDO::FETCH_ASSOC);
    echo "<p><b>✅ Query 3 (descansos):</b> OK - Total: " . count($descansos) . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'><b>❌ Query 3 ERROR:</b> " . htmlspecialchars($e->getMessage()) . "</p>";
}

try {
    // Query 4: Ausencias
    $stmt_ausencias = $pdo->prepare("
        SELECT ae.empleado_id, ae.tipo_ausencia
        FROM ausencias_empleados ae
        INNER JOIN usuarios u ON ae.empleado_id = u.id
        WHERE u.propietario_id = ? AND ae.fecha = ?
    ");
    $stmt_ausencias->execute([$dueno_id, $hoy]);
    $ausencias = $stmt_ausencias->fetchAll(PDO::FETCH_ASSOC);
    echo "<p><b>✅ Query 4 (ausencias):</b> OK - Total: " . count($ausencias) . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'><b>❌ Query 4 ERROR:</b> " . htmlspecialchars($e->getMessage()) . "</p>";
}

// 5. Probar queries de marcaciones
if (!empty($empleados)) {
    try {
        $empleado_ids = array_column($empleados, 'id');
        $placeholders = implode(',', array_fill(0, count($empleado_ids), '?'));
        
        $stmt_marcaciones = $pdo->prepare("
            SELECT m.empleado_id, m.entrada, m.salida,
                   sc.nueva_hora_entrada, sc.nueva_hora_salida,
                   ROW_NUMBER() OVER (PARTITION BY m.empleado_id ORDER BY m.entrada DESC) as rn
            FROM marcaciones m
            LEFT JOIN solicitudes_cambio sc ON m.id = sc.marcacion_id AND sc.estado = 'aprobado'
            WHERE m.empleado_id IN ($placeholders) AND DATE(m.entrada) = ?
        ");
        
        $stmt_marcaciones->execute([...$empleado_ids, $hoy]);
        $marcaciones = $stmt_marcaciones->fetchAll(PDO::FETCH_ASSOC);
        echo "<p><b>✅ Query 5 (marcaciones):</b> OK - Total: " . count($marcaciones) . "</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'><b>❌ Query 5 ERROR:</b> " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

echo "<p><a href='dueño.php'>← Volver a dueño.php</a></p>";
?>
