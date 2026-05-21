<?php
session_start();
require_once 'config.php';

if (!es_dueno_o_gerente()) {
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

function esc_historial_pdf($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$dueno_id = owner_scope_id($pdo);
$empleado_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT username, nombre, created_at FROM usuarios WHERE id = ? AND rol = 'empleado' AND propietario_id = ?");
$stmt->execute([$empleado_id, $dueno_id]);
$empleado = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$empleado) {
    http_response_code(404);
    echo 'Empleado no encontrado.';
    exit;
}

$nombre_mostrar = !empty($empleado['nombre']) ? $empleado['nombre'] : $empleado['username'];
$antiguedad_texto = '';

if (!empty($empleado['created_at'])) {
    $fecha_inicio = new DateTime($empleado['created_at']);
    $fecha_actual = new DateTime();
    $diferencia = $fecha_inicio->diff($fecha_actual);
    $años = $diferencia->y;
    $meses = $diferencia->m;

    if ($años > 0 && $meses > 0) {
        $antiguedad_texto = $años . ' año' . ($años > 1 ? 's' : '') . ' y ' . $meses . ' mes' . ($meses > 1 ? 'es' : '');
    } elseif ($años > 0) {
        $antiguedad_texto = $años . ' año' . ($años > 1 ? 's' : '');
    } else {
        $antiguedad_texto = $meses . ' mes' . ($meses > 1 ? 'es' : '');
    }
}

$stmt = $pdo->prepare("\n    SELECT DATE(m.entrada) as fecha, m.entrada, m.salida,\n           sc.nueva_hora_entrada, sc.nueva_hora_salida, sc.motivo\n    FROM marcaciones m\n    LEFT JOIN solicitudes_cambio sc ON m.id = sc.marcacion_id AND sc.estado = 'aprobado'\n    WHERE m.empleado_id = ? \n    ORDER BY m.entrada DESC\n");
$stmt->execute([$empleado_id]);
$marcaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

        .title {
            font-size: 20px;
            font-weight: bold;
            color: #1e3a8a;
            margin-bottom: 4px;
        }

        .subtitle {
            font-size: 11px;
            color: #475569;
            margin-bottom: 12px;
        }

        .summary {
            margin-bottom: 14px;
            border: 1px solid #cbd5e1;
            padding: 8px 10px;
            background: #f8fafc;
            line-height: 1.5;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
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

        .empty {
            text-align: center;
            color: #64748b;
            padding: 12px;
            font-style: italic;
        }

        .adjusted {
            color: #4338ca;
            font-weight: bold;
        }

        .original {
            text-decoration: line-through;
            color: #64748b;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="title">Historial de <?php echo esc_historial_pdf($nombre_mostrar); ?></div>
    <div class="subtitle">Empleado: <?php echo esc_historial_pdf($empleado['username']); ?></div>
    <div class="summary">
        <strong>Registros:</strong> <?php echo count($marcaciones); ?><br>
        <?php if ($antiguedad_texto !== ''): ?>
            <strong>Antiguedad:</strong> <?php echo esc_historial_pdf($antiguedad_texto); ?><br>
        <?php endif; ?>
        <?php if (!empty($empleado['created_at'])): ?>
            <strong>Fecha de alta:</strong> <?php echo esc_historial_pdf((new DateTime($empleado['created_at']))->format('d/m/Y')); ?>
        <?php endif; ?>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 18%;">Fecha</th>
                <th style="width: 22%;">Entrada</th>
                <th style="width: 22%;">Salida</th>
                <th style="width: 20%;">Horas Trabajadas</th>
                <th style="width: 18%;">Motivo Ajuste</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($marcaciones)): ?>
            <tr>
                <td colspan="5" class="empty">No hay marcaciones registradas para este empleado.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($marcaciones as $fila): ?>
                <?php
                $entrada_dt = $fila['entrada'] ? new DateTime($fila['entrada']) : null;
                $salida_dt = $fila['salida'] ? new DateTime($fila['salida']) : null;
                $entrada = $entrada_dt ? $entrada_dt->format('H:i') : null;
                $salida = $salida_dt ? $salida_dt->format('H:i') : null;
                $entrada_ajustada = $fila['nueva_hora_entrada'];
                $salida_ajustada = $fila['nueva_hora_salida'];
                $fecha_base = $fila['fecha'];
                $entrada_calcular_dt = $entrada_ajustada ? new DateTime($fecha_base . ' ' . $entrada_ajustada) : $entrada_dt;
                $salida_calcular_dt = $salida_ajustada ? new DateTime($fecha_base . ' ' . $salida_ajustada) : $salida_dt;
                $horas = 'â€”';

                if ($entrada_calcular_dt && $salida_calcular_dt) {
                    if ($salida_calcular_dt < $entrada_calcular_dt) {
                        $salida_calcular_dt->modify('+1 day');
                    }
                    $intervalo = $entrada_calcular_dt->diff($salida_calcular_dt);
                    $horas = $intervalo->format('%h horas %i minutos');
                }
                ?>
                <tr>
                    <td><?php echo esc_historial_pdf($fila['fecha']); ?></td>
                    <td>
                        <?php if (!empty($entrada_ajustada)): ?>
                            <div class="original"><?php echo esc_historial_pdf($entrada ?: 'â€”'); ?></div>
                            <div class="adjusted"><?php echo esc_historial_pdf(substr($entrada_ajustada, 0, 5)); ?></div>
                        <?php else: ?>
                            <?php echo esc_historial_pdf($entrada ?: 'â€”'); ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($salida_ajustada)): ?>
                            <div class="original"><?php echo esc_historial_pdf($salida ?: 'â€”'); ?></div>
                            <div class="adjusted"><?php echo esc_historial_pdf(substr($salida_ajustada, 0, 5)); ?></div>
                        <?php else: ?>
                            <?php echo esc_historial_pdf($salida ?: 'â€”'); ?>
                        <?php endif; ?>
                    </td>
                    <td><?php echo esc_historial_pdf($horas); ?></td>
                    <td><?php echo esc_historial_pdf($fila['motivo'] ?: 'â€”'); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
<?php
$html = ob_get_clean();

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->setPaper('A4', 'portrait');
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->render();
$dompdf->stream('historial_empleado_' . $empleado_id . '.pdf', ['Attachment' => true]);
exit;

