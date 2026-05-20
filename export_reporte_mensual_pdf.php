<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'dueÃ±o') {
    http_response_code(403);
    echo 'Acceso no autorizado';
    exit;
}

$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    http_response_code(500);
    echo 'No se encontro el autoload de Composer para generar PDF.';
    exit;
}

require_once $autoloadPath;

use Dompdf\Dompdf;
use Dompdf\Options;

function esc($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function calc_horas_dia($entradaBase, $salidaBase, $nuevaEntrada, $nuevaSalida, $fechaBase)
{
    $entradaBaseDt = $entradaBase ? new DateTime($entradaBase) : null;
    $salidaBaseDt = $salidaBase ? new DateTime($salidaBase) : null;

    $entradaUsarDt = $nuevaEntrada ? new DateTime($fechaBase . ' ' . $nuevaEntrada) : $entradaBaseDt;
    $salidaUsarDt = $nuevaSalida ? new DateTime($fechaBase . ' ' . $nuevaSalida) : $salidaBaseDt;

    if (!$entradaUsarDt || !$salidaUsarDt) {
        return '';
    }

    try {
        if ($salidaUsarDt < $entradaUsarDt) {
            $salidaUsarDt->modify('+1 day');
        }
        $intervalo = $entradaUsarDt->diff($salidaUsarDt);
        return $intervalo->format('%H:%I');
    } catch (Exception $e) {
        return '';
    }
}

$dueÃ±o_id = (int)$_SESSION['user_id'];
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date('m');
$aÃ±o = isset($_GET['aÃ±o']) ? (int)$_GET['aÃ±o'] : (isset($_GET['anio']) ? (int)$_GET['anio'] : (int)date('Y'));

if ($mes < 1) {
    $mes = 12;
    $aÃ±o--;
}
if ($mes > 12) {
    $mes = 1;
    $aÃ±o++;
}

$primer_dia = sprintf('%04d-%02d-01', $aÃ±o, $mes);
$ultimo_dia = date('Y-m-d', strtotime($primer_dia . ' +1 month -1 day'));
$empleado_id = isset($_GET['empleado_id']) ? (int)$_GET['empleado_id'] : null;
$filename = $empleado_id ? "reporte_empleado_{$empleado_id}_{$aÃ±o}_{$mes}.pdf" : "reporte_mensual_{$aÃ±o}_{$mes}.pdf";

ob_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 24px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #1f2937;
            font-size: 12px;
        }

        .sheet-title {
            font-size: 20px;
            font-weight: bold;
            color: #1e3a8a;
            margin-bottom: 4px;
        }

        .sheet-subtitle {
            font-size: 11px;
            color: #475569;
            margin-bottom: 12px;
        }

        .summary {
            margin-bottom: 14px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 8px 10px;
            background: #f8fafc;
            line-height: 1.5;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border: 1px solid #cbd5e1;
            font-size: 11px;
        }

        th {
            background: #1d4ed8;
            color: #ffffff;
            text-align: left;
            padding: 7px 8px;
            border: 1px solid #cbd5e1;
            font-weight: bold;
        }

        td {
            padding: 6px 8px;
            border: 1px solid #cbd5e1;
            vertical-align: top;
            word-wrap: break-word;
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
            padding: 12px;
            font-style: italic;
        }

        .totals-row td {
            background: #dbeafe;
            font-weight: bold;
        }
    </style>
</head>
<body>
<?php
if ($empleado_id) {
    $stmt_check = $pdo->prepare("SELECT id, username, nombre FROM usuarios WHERE id = ? AND rol = 'empleado' AND propietario_id = ?");
    $stmt_check->execute([$empleado_id, $dueÃ±o_id]);
    $empleado = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$empleado) {
        ?>
        <div class="sheet-title">Exportacion no permitida</div>
        <div class="summary">El empleado solicitado no pertenece al dueÃ±o autenticado.</div>
        <?php
    } else {
        $stmt = $pdo->prepare("\n            SELECT DATE(m.entrada) AS fecha, m.entrada, m.salida,\n                   sc.nueva_hora_entrada, sc.nueva_hora_salida\n            FROM marcaciones m\n            LEFT JOIN solicitudes_cambio sc ON m.id = sc.marcacion_id AND sc.estado = 'aprobado'\n            WHERE m.empleado_id = ? AND DATE(m.entrada) BETWEEN ? AND ?\n            ORDER BY m.entrada ASC, m.id ASC\n        ");
        $stmt->execute([$empleado_id, $primer_dia, $ultimo_dia]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $nombre_empleado = $empleado['nombre'] ?: $empleado['username'];
        ?>
        <div class="sheet-title">Reporte Individual de Empleado</div>
        <div class="sheet-subtitle">Periodo: <?php echo esc($mes); ?>/<?php echo esc($aÃ±o); ?> | Empleado: <?php echo esc($nombre_empleado); ?></div>

        <div class="summary">
            <strong>Usuario:</strong> <?php echo esc($empleado['username']); ?><br>
            <strong>Rango:</strong> <?php echo esc($primer_dia); ?> a <?php echo esc($ultimo_dia); ?><br>
            <strong>Registros:</strong> <?php echo count($rows); ?>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 14%;">Fecha</th>
                    <th style="width: 12%;">Entrada</th>
                    <th style="width: 12%;">Salida</th>
                    <th style="width: 14%;">Nueva Entrada</th>
                    <th style="width: 14%;">Nueva Salida</th>
                    <th style="width: 12%;">Ajustado</th>
                    <th style="width: 16%;">Horas Dia</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="7" class="empty">No hay marcaciones para este periodo.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $r): ?>
                    <?php $horas_dia = calc_horas_dia($r['entrada'], $r['salida'], $r['nueva_hora_entrada'], $r['nueva_hora_salida'], $r['fecha']); ?>
                    <tr>
                        <td><?php echo esc($r['fecha']); ?></td>
                        <td class="center"><?php echo esc($r['entrada'] ? date('H:i', strtotime($r['entrada'])) : ''); ?></td>
                        <td class="center"><?php echo esc($r['salida'] ? date('H:i', strtotime($r['salida'])) : ''); ?></td>
                        <td class="center"><?php echo esc($r['nueva_hora_entrada'] ?: ''); ?></td>
                        <td class="center"><?php echo esc($r['nueva_hora_salida'] ?: ''); ?></td>
                        <td class="center"><?php echo ($r['nueva_hora_entrada'] || $r['nueva_hora_salida']) ? 'Si' : 'No'; ?></td>
                        <td class="center"><?php echo esc($horas_dia); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
        <?php
    }
} else {
    $stmt_empleados = $pdo->prepare("\n        SELECT id, username, nombre\n        FROM usuarios\n        WHERE rol = 'empleado' AND propietario_id = ?\n        ORDER BY username\n    ");
    $stmt_empleados->execute([$dueÃ±o_id]);
    $empleados = $stmt_empleados->fetchAll(PDO::FETCH_ASSOC);

    $stmt_marc = $pdo->prepare("\n        SELECT empleado_id, COUNT(DISTINCT DATE(entrada)) AS dias_trabajados\n        FROM marcaciones\n        WHERE DATE(entrada) BETWEEN ? AND ?\n          AND empleado_id IN (SELECT id FROM usuarios WHERE rol='empleado' AND propietario_id = ?)\n        GROUP BY empleado_id\n    ");
    $stmt_marc->execute([$primer_dia, $ultimo_dia, $dueÃ±o_id]);
    $marcaciones = [];
    foreach ($stmt_marc->fetchAll(PDO::FETCH_ASSOC) as $m) {
        $marcaciones[$m['empleado_id']] = (int)$m['dias_trabajados'];
    }

    $stmt_horas = $pdo->prepare("\n        SELECT empleado_id, SEC_TO_TIME(SUM(TIMESTAMPDIFF(SECOND, entrada, salida))) AS total_horas\n        FROM marcaciones\n        WHERE DATE(entrada) BETWEEN ? AND ?\n          AND entrada IS NOT NULL AND salida IS NOT NULL\n          AND empleado_id IN (SELECT id FROM usuarios WHERE rol='empleado' AND propietario_id = ?)\n        GROUP BY empleado_id\n    ");
    $stmt_horas->execute([$primer_dia, $ultimo_dia, $dueÃ±o_id]);
    $horas = [];
    foreach ($stmt_horas->fetchAll(PDO::FETCH_ASSOC) as $h) {
        $horas[$h['empleado_id']] = $h['total_horas'];
    }

    $stmt_desc = $pdo->prepare("\n        SELECT empleado_id, COUNT(*) AS dias_descanso\n        FROM horarios_semanales\n        WHERE fecha_descanso BETWEEN ? AND ?\n          AND empleado_id IN (SELECT id FROM usuarios WHERE rol='empleado' AND propietario_id = ?)\n        GROUP BY empleado_id\n    ");
    $stmt_desc->execute([$primer_dia, $ultimo_dia, $dueÃ±o_id]);
    $descanso = [];
    foreach ($stmt_desc->fetchAll(PDO::FETCH_ASSOC) as $d) {
        $descanso[$d['empleado_id']] = (int)$d['dias_descanso'];
    }

    $stmt_aus = $pdo->prepare("\n        SELECT empleado_id, tipo_ausencia, COUNT(*) AS cantidad\n        FROM ausencias_empleados\n        WHERE fecha BETWEEN ? AND ?\n          AND empleado_id IN (SELECT id FROM usuarios WHERE rol='empleado' AND propietario_id = ?)\n        GROUP BY empleado_id, tipo_ausencia\n    ");
    $stmt_aus->execute([$primer_dia, $ultimo_dia, $dueÃ±o_id]);
    $aus = [];
    foreach ($stmt_aus->fetchAll(PDO::FETCH_ASSOC) as $a) {
        $eid = $a['empleado_id'];
        if (!isset($aus[$eid])) {
            $aus[$eid] = [];
        }
        $aus[$eid][$a['tipo_ausencia']] = (int)$a['cantidad'];
    }

    $stmt_adj = $pdo->prepare("\n        SELECT sc.empleado_id, COUNT(*) AS ajustes\n        FROM solicitudes_cambio sc\n        INNER JOIN marcaciones m ON m.id = sc.marcacion_id\n        WHERE sc.estado = 'aprobado'\n          AND DATE(m.entrada) BETWEEN ? AND ?\n          AND sc.empleado_id IN (SELECT id FROM usuarios WHERE rol='empleado' AND propietario_id = ?)\n        GROUP BY sc.empleado_id\n    ");
    $stmt_adj->execute([$primer_dia, $ultimo_dia, $dueÃ±o_id]);
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
    <div class="sheet-subtitle">Periodo: <?php echo esc($mes); ?>/<?php echo esc($aÃ±o); ?> | DueÃ±o ID: <?php echo esc($dueÃ±o_id); ?></div>

    <div class="summary">
        <strong>Rango:</strong> <?php echo esc($primer_dia); ?> a <?php echo esc($ultimo_dia); ?><br>
        <strong>Total empleados:</strong> <?php echo count($empleados); ?>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 23%;">Empleado</th>
                <th style="width: 15%;">Usuario</th>
                <th style="width: 10%;">Dias</th>
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
            <tr><td colspan="9" class="empty">No hay empleados registrados para este dueÃ±o.</td></tr>
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
                    <td><?php echo esc($emp['nombre'] ?: $emp['username']); ?></td>
                    <td><?php echo esc($emp['username']); ?></td>
                    <td class="right"><?php echo $dias_trab; ?></td>
                    <td class="center"><?php echo esc(substr((string)$horas_tot, 0, 5)); ?></td>
                    <td class="right"><?php echo $vac; ?></td>
                    <td class="right"><?php echo $enf; ?></td>
                    <td class="right"><?php echo $fj; ?></td>
                    <td class="right"><?php echo $desc; ?></td>
                    <td class="right"><?php echo $adj; ?></td>
                </tr>
            <?php endforeach; ?>
            <tr class="totals-row">
                <td colspan="2">Totales</td>
                <td class="right"><?php echo $total_dias; ?></td>
                <td></td>
                <td class="right"><?php echo $total_vac; ?></td>
                <td class="right"><?php echo $total_enf; ?></td>
                <td class="right"><?php echo $total_fj; ?></td>
                <td class="right"><?php echo $total_desc; ?></td>
                <td class="right"><?php echo $total_adj; ?></td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
    <?php
}
?>
</body>
</html>
<?php
$html = ob_get_clean();

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->setPaper('A4', 'landscape');
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->render();
$dompdf->stream($filename, ['Attachment' => true]);
exit;

