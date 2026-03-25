<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'dueño') {
    http_response_code(403);
    echo 'Acceso no autorizado';
    exit;
}

function h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$dueño_id = (int)$_SESSION['user_id'];
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date('m');
$año = isset($_GET['año']) ? (int)$_GET['año'] : (isset($_GET['anio']) ? (int)$_GET['anio'] : (int)date('Y'));

if ($mes < 1) {
    $mes = 12;
    $año--;
}
if ($mes > 12) {
    $mes = 1;
    $año++;
}

$primer_dia = sprintf('%04d-%02d-01', $año, $mes);
$ultimo_dia = date('Y-m-d', strtotime($primer_dia . ' +1 month -1 day'));
$empleado_id = isset($_GET['empleado_id']) ? (int)$_GET['empleado_id'] : null;

$filename = $empleado_id ? "reporte_empleado_{$empleado_id}_{$año}_{$mes}.xls" : "reporte_mensual_{$año}_{$mes}.xls";
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
echo "\xEF\xBB\xBF";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Mensual</title>
    <style>
        body {
            font-family: Calibri, Arial, sans-serif;
            color: #1f2937;
            margin: 20px;
        }

        .sheet-title {
            font-size: 24px;
            font-weight: 700;
            color: #1e3a8a;
            margin-bottom: 4px;
        }

        .sheet-subtitle {
            font-size: 14px;
            color: #475569;
            margin-bottom: 16px;
        }

        .summary {
            margin-bottom: 16px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 10px 12px;
            background: #f8fafc;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border: 1px solid #cbd5e1;
            font-size: 13px;
        }

        th {
            background: #1d4ed8;
            color: #ffffff;
            text-align: left;
            padding: 9px 10px;
            border: 1px solid #cbd5e1;
            font-weight: 700;
        }

        td {
            padding: 8px 10px;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            vertical-align: top;
        }

        tr:nth-child(even) td {
            background: #f8fafc;
        }

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        .empty {
            text-align: center;
            color: #64748b;
            padding: 18px;
            font-style: italic;
        }

        .totals-row td {
            background: #dbeafe;
            font-weight: 700;
        }
    </style>
</head>
<body>
<?php
if ($empleado_id) {
    $stmt_check = $pdo->prepare("SELECT id, username, nombre FROM usuarios WHERE id = ? AND rol = 'empleado' AND propietario_id = ?");
    $stmt_check->execute([$empleado_id, $dueño_id]);
    $empleado = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$empleado) {
        ?>
        <div class="sheet-title">Exportación no permitida</div>
        <div class="summary">El empleado solicitado no pertenece al dueño autenticado.</div>
        <?php
        exit;
    }

    $stmt = $pdo->prepare("\n        SELECT DATE(m.entrada) as fecha, m.entrada, m.salida,\n               sc.nueva_hora_entrada, sc.nueva_hora_salida\n        FROM marcaciones m\n        LEFT JOIN solicitudes_cambio sc ON m.id = sc.marcacion_id AND sc.estado = 'aprobado'\n        WHERE m.empleado_id = ? AND DATE(m.entrada) BETWEEN ? AND ?\n        ORDER BY m.entrada ASC, m.id ASC\n    ");
    $stmt->execute([$empleado_id, $primer_dia, $ultimo_dia]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $nombre_empleado = $empleado['nombre'] ?: $empleado['username'];
    ?>
    <div class="sheet-title">Reporte Individual de Empleado</div>
    <div class="sheet-subtitle">Periodo: <?php echo h($mes); ?>/<?php echo h($año); ?> | Empleado: <?php echo h($nombre_empleado); ?></div>

    <div class="summary">
        <strong>Usuario:</strong> <?php echo h($empleado['username']); ?>
        <br>
        <strong>Rango:</strong> <?php echo h($primer_dia); ?> a <?php echo h($ultimo_dia); ?>
        <br>
        <strong>Registros:</strong> <?php echo count($rows); ?>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 13%;">Fecha</th>
                <th style="width: 12%;">Entrada</th>
                <th style="width: 12%;">Salida</th>
                <th style="width: 14%;">Nueva Entrada</th>
                <th style="width: 14%;">Nueva Salida</th>
                <th style="width: 12%;">Ajustado</th>
                <th style="width: 14%;">Horas Día</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="7" class="empty">No hay marcaciones para este periodo.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $r): ?>
                    <?php
                    $entrada_base_dt = $r['entrada'] ? new DateTime($r['entrada']) : null;
                    $salida_base_dt = $r['salida'] ? new DateTime($r['salida']) : null;
                    $fecha_base = $r['fecha'];

                    $entrada_usar_dt = $r['nueva_hora_entrada'] ? new DateTime($fecha_base . ' ' . $r['nueva_hora_entrada']) : $entrada_base_dt;
                    $salida_usar_dt = $r['nueva_hora_salida'] ? new DateTime($fecha_base . ' ' . $r['nueva_hora_salida']) : $salida_base_dt;
                    $ajustado = ($r['nueva_hora_entrada'] || $r['nueva_hora_salida']) ? 'Si' : 'No';

                    $horas_dia = '';
                    if ($entrada_usar_dt && $salida_usar_dt) {
                        try {
                            if ($salida_usar_dt < $entrada_usar_dt) {
                                $salida_usar_dt->modify('+1 day');
                            }
                            $intervalo = $entrada_usar_dt->diff($salida_usar_dt);
                            $horas_dia = $intervalo->format('%H:%I');
                        } catch (Exception $e) {
                            $horas_dia = '';
                        }
                    }
                    ?>
                    <tr>
                        <td><?php echo h($r['fecha']); ?></td>
                        <td class="center"><?php echo h($r['entrada'] ? date('H:i', strtotime($r['entrada'])) : ''); ?></td>
                        <td class="center"><?php echo h($r['salida'] ? date('H:i', strtotime($r['salida'])) : ''); ?></td>
                        <td class="center"><?php echo h($r['nueva_hora_entrada'] ?: ''); ?></td>
                        <td class="center"><?php echo h($r['nueva_hora_salida'] ?: ''); ?></td>
                        <td class="center"><?php echo h($ajustado); ?></td>
                        <td class="center"><?php echo h($horas_dia); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <?php
    exit;
}

$stmt_empleados = $pdo->prepare("\n    SELECT id, username, nombre\n    FROM usuarios\n    WHERE rol = 'empleado' AND propietario_id = ?\n    ORDER BY username\n");
$stmt_empleados->execute([$dueño_id]);
$empleados = $stmt_empleados->fetchAll(PDO::FETCH_ASSOC);

$stmt_marc = $pdo->prepare("\n    SELECT empleado_id, COUNT(DISTINCT DATE(entrada)) AS dias_trabajados\n    FROM marcaciones\n    WHERE DATE(entrada) BETWEEN ? AND ?\n      AND empleado_id IN (SELECT id FROM usuarios WHERE rol='empleado' AND propietario_id = ?)\n    GROUP BY empleado_id\n");
$stmt_marc->execute([$primer_dia, $ultimo_dia, $dueño_id]);
$marcaciones = [];
foreach ($stmt_marc->fetchAll(PDO::FETCH_ASSOC) as $m) {
    $marcaciones[$m['empleado_id']] = (int)$m['dias_trabajados'];
}

$stmt_horas = $pdo->prepare("\n    SELECT empleado_id, SEC_TO_TIME(SUM(TIMESTAMPDIFF(SECOND, entrada, salida))) AS total_horas\n    FROM marcaciones\n    WHERE DATE(entrada) BETWEEN ? AND ?\n      AND entrada IS NOT NULL AND salida IS NOT NULL\n      AND empleado_id IN (SELECT id FROM usuarios WHERE rol='empleado' AND propietario_id = ?)\n    GROUP BY empleado_id\n");
$stmt_horas->execute([$primer_dia, $ultimo_dia, $dueño_id]);
$horas = [];
foreach ($stmt_horas->fetchAll(PDO::FETCH_ASSOC) as $h) {
    $horas[$h['empleado_id']] = $h['total_horas'];
}

$stmt_desc = $pdo->prepare("\n    SELECT empleado_id, COUNT(*) AS dias_descanso\n    FROM horarios_semanales\n    WHERE fecha_descanso BETWEEN ? AND ?\n      AND empleado_id IN (SELECT id FROM usuarios WHERE rol='empleado' AND propietario_id = ?)\n    GROUP BY empleado_id\n");
$stmt_desc->execute([$primer_dia, $ultimo_dia, $dueño_id]);
$descanso = [];
foreach ($stmt_desc->fetchAll(PDO::FETCH_ASSOC) as $d) {
    $descanso[$d['empleado_id']] = (int)$d['dias_descanso'];
}

$stmt_aus = $pdo->prepare("\n    SELECT empleado_id, tipo_ausencia, COUNT(*) AS cantidad\n    FROM ausencias_empleados\n    WHERE fecha BETWEEN ? AND ?\n      AND empleado_id IN (SELECT id FROM usuarios WHERE rol='empleado' AND propietario_id = ?)\n    GROUP BY empleado_id, tipo_ausencia\n");
$stmt_aus->execute([$primer_dia, $ultimo_dia, $dueño_id]);
$aus = [];
foreach ($stmt_aus->fetchAll(PDO::FETCH_ASSOC) as $a) {
    $eid = $a['empleado_id'];
    if (!isset($aus[$eid])) {
        $aus[$eid] = [];
    }
    $aus[$eid][$a['tipo_ausencia']] = (int)$a['cantidad'];
}

$stmt_adj = $pdo->prepare("\n    SELECT sc.empleado_id, COUNT(*) AS ajustes\n    FROM solicitudes_cambio sc\n    INNER JOIN marcaciones m ON m.id = sc.marcacion_id\n    WHERE sc.estado = 'aprobado'\n      AND DATE(m.entrada) BETWEEN ? AND ?\n      AND sc.empleado_id IN (SELECT id FROM usuarios WHERE rol='empleado' AND propietario_id = ?)\n    GROUP BY sc.empleado_id\n");
$stmt_adj->execute([$primer_dia, $ultimo_dia, $dueño_id]);
$ajustes = [];
foreach ($stmt_adj->fetchAll(PDO::FETCH_ASSOC) as $aj) {
    $ajustes[$aj['empleado_id']] = (int)$aj['ajustes'];
}

$total_dias = 0;
$total_vac = 0;
$total_enf = 0;
$total_fj = 0;
$total_desc = 0;
$total_adj = 0;
?>
<div class="sheet-title">Reporte Mensual por Empleado</div>
<div class="sheet-subtitle">Periodo: <?php echo h($mes); ?>/<?php echo h($año); ?> | Dueño ID: <?php echo h($dueño_id); ?></div>

<div class="summary">
    <strong>Rango:</strong> <?php echo h($primer_dia); ?> a <?php echo h($ultimo_dia); ?>
    <br>
    <strong>Total empleados:</strong> <?php echo count($empleados); ?>
</div>

<table>
    <thead>
        <tr>
            <th style="width: 23%;">Empleado</th>
            <th style="width: 15%;">Usuario</th>
            <th style="width: 10%;">Días</th>
            <th style="width: 11%;">Horas</th>
            <th style="width: 10%;">Vacaciones</th>
            <th style="width: 10%;">Enfermedad</th>
            <th style="width: 10%;">F. Justificadas</th>
            <th style="width: 10%;">Descanso</th>
            <th style="width: 11%;">Ajustes</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($empleados)): ?>
            <tr>
                <td colspan="9" class="empty">No hay empleados registrados para este dueño.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($empleados as $emp): ?>
                <?php
                $eid = (int)$emp['id'];
                $dias_trab = $marcaciones[$eid] ?? 0;
                $horas_tot = $horas[$eid] ?? '00:00:00';
                $vac = $aus[$eid]['vacaciones_ley'] ?? 0;
                $enf = $aus[$eid]['enfermedad'] ?? 0;
                $fj = ($aus[$eid]['emergencia_familiar'] ?? 0) + ($aus[$eid]['fuerza_mayor'] ?? 0);
                $desc = $descanso[$eid] ?? 0;
                $adj = $ajustes[$eid] ?? 0;

                $total_dias += $dias_trab;
                $total_vac += $vac;
                $total_enf += $enf;
                $total_fj += $fj;
                $total_desc += $desc;
                $total_adj += $adj;
                ?>
                <tr>
                    <td><?php echo h($emp['nombre'] ?: $emp['username']); ?></td>
                    <td><?php echo h($emp['username']); ?></td>
                    <td class="right"><?php echo h($dias_trab); ?></td>
                    <td class="center"><?php echo h(substr((string)$horas_tot, 0, 5)); ?></td>
                    <td class="right"><?php echo h($vac); ?></td>
                    <td class="right"><?php echo h($enf); ?></td>
                    <td class="right"><?php echo h($fj); ?></td>
                    <td class="right"><?php echo h($desc); ?></td>
                    <td class="right"><?php echo h($adj); ?></td>
                </tr>
            <?php endforeach; ?>
            <tr class="totals-row">
                <td colspan="2">Totales</td>
                <td class="right"><?php echo h($total_dias); ?></td>
                <td></td>
                <td class="right"><?php echo h($total_vac); ?></td>
                <td class="right"><?php echo h($total_enf); ?></td>
                <td class="right"><?php echo h($total_fj); ?></td>
                <td class="right"><?php echo h($total_desc); ?></td>
                <td class="right"><?php echo h($total_adj); ?></td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
</body>
</html>
