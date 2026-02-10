<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'dueño') {
    http_response_code(403);
    echo 'Acceso no autorizado';
    exit;
}

$dueño_id = $_SESSION['user_id'];

// Parámetros del mes/año
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date('m');
$año = isset($_GET['año']) ? (int)$_GET['año'] : (int)date('Y');

if ($mes < 1) { $mes = 12; $año--; }
if ($mes > 12) { $mes = 1; $año++; }

$primer_dia = sprintf('%04d-%02d-01', $año, $mes);
$ultimo_dia = date('Y-m-d', strtotime($primer_dia . ' +1 month -1 day'));

// ¿Exportación por empleado?
$empleado_id = isset($_GET['empleado_id']) ? (int)$_GET['empleado_id'] : null;

// Forzar descarga CSV (UTF-8 BOM para Excel en Windows)
$filename = $empleado_id ? "reporte_empleado_{$empleado_id}_{$año}_{$mes}.csv" : "reporte_mensual_{$año}_{$mes}.csv";
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename=' . $filename);
echo "\xEF\xBB\xBF"; // BOM

$out = fopen('php://output', 'w');

// Validar que el empleado (si se solicita) pertenece al dueño
if ($empleado_id) {
    $stmt_check = $pdo->prepare("SELECT id, username, nombre FROM usuarios WHERE id = ? AND rol = 'empleado' AND propietario_id = ?");
    $stmt_check->execute([$empleado_id, $dueño_id]);
    $empleado = $stmt_check->fetch(PDO::FETCH_ASSOC);
    if (!$empleado) {
        fputcsv($out, ['Error', 'Empleado no pertenece al dueño actual']);
        exit;
    }

    // Encabezado per-empleado
    fputcsv($out, ['Empleado', ($empleado['nombre'] ?: $empleado['username'])]);
    fputcsv($out, ['Mes', $mes, 'Año', $año]);
    fputcsv($out, []);
    fputcsv($out, ['Fecha', 'Entrada', 'Salida', 'Nueva Entrada', 'Nueva Salida', 'Ajustado', 'Horas Día']);

    // Detalle diario con ajustes
    $stmt = $pdo->prepare("\n        SELECT DATE(m.entrada) as fecha, m.entrada, m.salida,\n               sc.nueva_hora_entrada, sc.nueva_hora_salida\n        FROM marcaciones m\n        LEFT JOIN solicitudes_cambio sc ON m.id = sc.marcacion_id AND sc.estado = 'aprobado'\n        WHERE m.empleado_id = ? AND DATE(m.entrada) BETWEEN ? AND ?\n        ORDER BY m.entrada ASC, m.id ASC\n    ");
    $stmt->execute([$empleado_id, $primer_dia, $ultimo_dia]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $r) {
        $entrada_base_dt = $r['entrada'] ? new DateTime($r['entrada']) : null;
        $salida_base_dt = $r['salida'] ? new DateTime($r['salida']) : null;
        $fecha_base = $r['fecha'];

        $entrada_usar_dt = $r['nueva_hora_entrada'] ? new DateTime($fecha_base . ' ' . $r['nueva_hora_entrada']) : $entrada_base_dt;
        $salida_usar_dt  = $r['nueva_hora_salida'] ? new DateTime($fecha_base . ' ' . $r['nueva_hora_salida']) : $salida_base_dt;
        $ajustado = ($r['nueva_hora_entrada'] || $r['nueva_hora_salida']) ? 'Sí' : 'No';

        $horas_dia = '';
        if ($entrada_usar_dt && $salida_usar_dt) {
            try {
                if ($salida_usar_dt < $entrada_usar_dt) {
                    $salida_usar_dt->modify('+1 day');
                }
                $inicio = $entrada_usar_dt;
                $fin    = $salida_usar_dt;
                $intervalo = $inicio->diff($fin);
                $horas_dia = $intervalo->format('%H:%I');
            } catch (Exception $e) {
                $horas_dia = '';
            }
        }

        fputcsv($out, [
            $r['fecha'],
            $r['entrada'] ? date('H:i', strtotime($r['entrada'])) : '',
            $r['salida'] ? date('H:i', strtotime($r['salida'])) : '',
            $r['nueva_hora_entrada'] ?: '',
            $r['nueva_hora_salida'] ?: '',
            $ajustado,
            $horas_dia,
        ]);
    }

    fclose($out);
    exit;
}

// Export general por dueño: resumen por empleado

// Lista empleados del dueño
$stmt_empleados = $pdo->prepare("\n    SELECT id, username, nombre\n    FROM usuarios\n    WHERE rol = 'empleado' AND propietario_id = ?\n    ORDER BY username\n");
$stmt_empleados->execute([$dueño_id]);
$empleados = $stmt_empleados->fetchAll(PDO::FETCH_ASSOC);

// Marcaciones del mes (días trabajados)
$stmt_marc = $pdo->prepare("\n    SELECT empleado_id, COUNT(DISTINCT DATE(entrada)) AS dias_trabajados\n    FROM marcaciones\n    WHERE DATE(entrada) BETWEEN ? AND ?\n      AND empleado_id IN (SELECT id FROM usuarios WHERE rol='empleado' AND propietario_id = ?)\n    GROUP BY empleado_id\n");
$stmt_marc->execute([$primer_dia, $ultimo_dia, $dueño_id]);
$marcaciones = [];
foreach ($stmt_marc->fetchAll(PDO::FETCH_ASSOC) as $m) {
    $marcaciones[$m['empleado_id']] = (int)$m['dias_trabajados'];
}

// Total horas (originales)
$stmt_horas = $pdo->prepare("\n    SELECT empleado_id, SEC_TO_TIME(SUM(TIMESTAMPDIFF(SECOND, entrada, salida))) AS total_horas\n    FROM marcaciones\n    WHERE DATE(entrada) BETWEEN ? AND ?\n      AND entrada IS NOT NULL AND salida IS NOT NULL\n      AND empleado_id IN (SELECT id FROM usuarios WHERE rol='empleado' AND propietario_id = ?)\n    GROUP BY empleado_id\n");
$stmt_horas->execute([$primer_dia, $ultimo_dia, $dueño_id]);
$horas = [];
foreach ($stmt_horas->fetchAll(PDO::FETCH_ASSOC) as $h) {
    $horas[$h['empleado_id']] = $h['total_horas'];
}

// Días de descanso
$stmt_desc = $pdo->prepare("\n    SELECT empleado_id, COUNT(*) AS dias_descanso\n    FROM horarios_semanales\n    WHERE fecha_descanso BETWEEN ? AND ?\n      AND empleado_id IN (SELECT id FROM usuarios WHERE rol='empleado' AND propietario_id = ?)\n    GROUP BY empleado_id\n");
$stmt_desc->execute([$primer_dia, $ultimo_dia, $dueño_id]);
$descanso = [];
foreach ($stmt_desc->fetchAll(PDO::FETCH_ASSOC) as $d) {
    $descanso[$d['empleado_id']] = (int)$d['dias_descanso'];
}

// Ausencias por tipo
$stmt_aus = $pdo->prepare("\n    SELECT empleado_id, tipo_ausencia, COUNT(*) AS cantidad\n    FROM ausencias_empleados\n    WHERE fecha BETWEEN ? AND ?\n      AND empleado_id IN (SELECT id FROM usuarios WHERE rol='empleado' AND propietario_id = ?)\n    GROUP BY empleado_id, tipo_ausencia\n");
$stmt_aus->execute([$primer_dia, $ultimo_dia, $dueño_id]);
$aus = [];
foreach ($stmt_aus->fetchAll(PDO::FETCH_ASSOC) as $a) {
    $eid = $a['empleado_id'];
    if (!isset($aus[$eid])) $aus[$eid] = [];
    $aus[$eid][$a['tipo_ausencia']] = (int)$a['cantidad'];
}

// Ajustes aprobados en el mes
$stmt_adj = $pdo->prepare("\n    SELECT sc.empleado_id, COUNT(*) AS ajustes\n    FROM solicitudes_cambio sc\n    INNER JOIN marcaciones m ON m.id = sc.marcacion_id\n    WHERE sc.estado = 'aprobado'\n      AND DATE(m.entrada) BETWEEN ? AND ?\n      AND sc.empleado_id IN (SELECT id FROM usuarios WHERE rol='empleado' AND propietario_id = ?)\n    GROUP BY sc.empleado_id\n");
$stmt_adj->execute([$primer_dia, $ultimo_dia, $dueño_id]);
$ajustes = [];
foreach ($stmt_adj->fetchAll(PDO::FETCH_ASSOC) as $aj) {
    $ajustes[$aj['empleado_id']] = (int)$aj['ajustes'];
}

// Encabezado general
fputcsv($out, ['Reporte Mensual', $mes, $año]);
fputcsv($out, []);
fputcsv($out, ['Empleado', 'Usuario', 'Días Trabajados', 'Horas Totales', 'Vacaciones Ley', 'Enfermedad', 'Faltas Justificadas', 'Días Descanso', 'Ajustes Aprobados']);

foreach ($empleados as $emp) {
    $eid = (int)$emp['id'];
    $dias_trab = $marcaciones[$eid] ?? 0;
    $horas_tot = $horas[$eid] ?? '00:00:00';
    $vac = $aus[$eid]['vacaciones_ley'] ?? 0;
    $enf = $aus[$eid]['enfermedad'] ?? 0;
    $fj  = ($aus[$eid]['emergencia_familiar'] ?? 0) + ($aus[$eid]['fuerza_mayor'] ?? 0);
    $desc = $descanso[$eid] ?? 0;
    $adj = $ajustes[$eid] ?? 0;

    fputcsv($out, [
        ($emp['nombre'] ?: $emp['username']),
        $emp['username'],
        $dias_trab,
        substr($horas_tot, 0, 5),
        $vac,
        $enf,
        $fj,
        $desc,
        $adj,
    ]);
}

fclose($out);
exit;
?>
