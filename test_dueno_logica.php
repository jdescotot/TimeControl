<?php
session_start();
require_once 'config.php';

echo "<h1>Simulación de lógica de dueño.php</h1>";

$dueno_id = 52; // Mismo que en la sesión
$hoy = date('Y-m-d');

// Obtener empleados
$stmt_empleados = $pdo->prepare(" 
    SELECT id, username, nombre, es_gerente
    FROM usuarios 
    WHERE rol = 'empleado' 
    AND propietario_id = ? 
    ORDER BY nombre IS NULL OR nombre = '', nombre, username
");
$stmt_empleados->execute([$dueno_id]);
$empleados = $stmt_empleados->fetchAll(PDO::FETCH_ASSOC);
echo "<p><b>Empleados obtenidos:</b> " . count($empleados) . "</p>";

// Obtener descansos
$stmt_descansos = $pdo->prepare("SELECT empleado_id FROM horarios_semanales WHERE fecha_descanso = ?");
$stmt_descansos->execute([$hoy]);
$empleados_con_descanso = [];
foreach ($stmt_descansos->fetchAll(PDO::FETCH_ASSOC) as $d) {
    $empleados_con_descanso[] = $d['empleado_id'];
}
echo "<p><b>Descansos:</b> " . count($empleados_con_descanso) . "</p>";

// Obtener ausencias
$stmt_ausencias_hoy = $pdo->prepare("
    SELECT ae.empleado_id, ae.tipo_ausencia
    FROM ausencias_empleados ae
    INNER JOIN usuarios u ON ae.empleado_id = u.id
    WHERE u.propietario_id = ? AND ae.fecha = ?
");
$stmt_ausencias_hoy->execute([$dueno_id, $hoy]);
$ausencias_hoy = [];
foreach ($stmt_ausencias_hoy->fetchAll(PDO::FETCH_ASSOC) as $a) {
    $ausencias_hoy[$a['empleado_id']] = $a['tipo_ausencia'];
}
echo "<p><b>Ausencias:</b> " . count($ausencias_hoy) . "</p>";

// Obtener marcaciones
echo "<h2>Procesando marcaciones...</h2>";
$empleado_ids = array_column($empleados, 'id');
$marcaciones_por_emp = [];

if (!empty($empleado_ids)) {
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
    
    foreach ($stmt_marcaciones->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if ($row['rn'] == 1 && !isset($marcaciones_por_emp[$row['empleado_id']])) {
            $marcaciones_por_emp[$row['empleado_id']] = $row;
        }
    }
}
echo "<p><b>Marcaciones por empleado:</b> " . count($marcaciones_por_emp) . "</p>";

// Procesar empleados
echo "<h2>Procesando datos de empleados...</h2>";
$total_empleados = count($empleados);
$entraron_hoy = 0;
$en_jornada = 0;

try {
    if (!empty($empleados)) {
        foreach ($empleados as $key => $emp) {
            echo "<p>Procesando empleado: {$emp['username']}...</p>";
            
            $empleados[$key]['tiene_descanso'] = in_array($emp['id'], $empleados_con_descanso);
            $empleados[$key]['ausencia_hoy'] = $ausencias_hoy[$emp['id']] ?? null;
            
            $registro = $marcaciones_por_emp[$emp['id']] ?? null;
            
            $empleados[$key]['entrada'] = $registro['entrada'] ?? null;
            $empleados[$key]['salida'] = $registro['salida'] ?? null;
            
            // Línea crítica: procesamiento de horas
            echo "  - Entrada: " . ($empleados[$key]['entrada'] ? "SÍ" : "NO") . "<br>";
            
            if (!empty($registro['entrada'])) {
                try {
                    $empleados[$key]['entrada_hora'] = date('H:i', strtotime($registro['entrada']));
                    echo "  - Hora entrada: {$empleados[$key]['entrada_hora']}<br>";
                } catch (Exception $e) {
                    echo "  - ERROR al procesar entrada_hora: " . htmlspecialchars($e->getMessage()) . "<br>";
                    $empleados[$key]['entrada_hora'] = null;
                }
            } else {
                $empleados[$key]['entrada_hora'] = null;
            }
            
            if (!empty($registro['salida'])) {
                try {
                    $empleados[$key]['salida_hora'] = date('H:i', strtotime($registro['salida']));
                    echo "  - Hora salida: {$empleados[$key]['salida_hora']}<br>";
                } catch (Exception $e) {
                    echo "  - ERROR al procesar salida_hora: " . htmlspecialchars($e->getMessage()) . "<br>";
                    $empleados[$key]['salida_hora'] = null;
                }
            } else {
                $empleados[$key]['salida_hora'] = null;
            }
            
            $empleados[$key]['hora_entrada_ajustada'] = $registro['nueva_hora_entrada'] ?? null;
            $empleados[$key]['hora_salida_ajustada'] = $registro['nueva_hora_salida'] ?? null;
            $empleados[$key]['tiene_ajuste'] = !empty($registro['nueva_hora_entrada']) || !empty($registro['nueva_hora_salida']);

            if ($empleados[$key]['entrada']) {
                $entraron_hoy++;
                if (!$empleados[$key]['salida']) {
                    $en_jornada++;
                }
            }
            
            echo "  ✅ OK<br>";
        }
    }
    echo "<p style='color: green;'><b>✅ Todas las iteraciones completadas</b></p>";
} catch (Exception $e) {
    echo "<p style='color: red;'><b>❌ ERROR en procesamiento:</b> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

$pendientes = max(0, $total_empleados - $entraron_hoy - count($empleados_con_descanso));

echo "<h2>Resumen final</h2>";
echo "<p>Total empleados: $total_empleados</p>";
echo "<p>Entraron hoy: $entraron_hoy</p>";
echo "<p>En jornada: $en_jornada</p>";
echo "<p>Pendientes: $pendientes</p>";

echo "<p><a href='test_dueno_debug.php'>← Volver a test_dueno_debug.php</a></p>";
?>
