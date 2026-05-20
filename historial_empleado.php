<?php
session_start();
require_once 'config.php';

$dueno_id = require_dueno_o_gerente($pdo);

$empleado_id = $_GET['id'] ?? 0;

// Validar que el empleado exista y pertenezca al dueÃ±o autenticado
$stmt = $pdo->prepare("SELECT username, nombre, created_at FROM usuarios WHERE id = ? AND rol = 'empleado' AND propietario_id = ?");
$stmt->execute([$empleado_id, $dueno_id]);
$empleado = $stmt->fetch();

if (!$empleado) {
    die('Empleado no encontrado.');
}

$username = $empleado['username'];
$nombre_mostrar = !empty($empleado['nombre']) ? $empleado['nombre'] : $empleado['username'];

// Calcular antigÃ¼edad
$antiguedad_texto = '';
if (!empty($empleado['created_at'])) {
    $fecha_inicio = new DateTime($empleado['created_at']);
    $fecha_actual = new DateTime();
    $diferencia = $fecha_inicio->diff($fecha_actual);
    
    $aÃ±os = $diferencia->y;
    $meses = $diferencia->m;
    
    if ($aÃ±os > 0 && $meses > 0) {
        $antiguedad_texto = $aÃ±os . ' aÃ±o' . ($aÃ±os > 1 ? 's' : '') . ' y ' . $meses . ' mes' . ($meses > 1 ? 'es' : '');
    } elseif ($aÃ±os > 0) {
        $antiguedad_texto = $aÃ±os . ' aÃ±o' . ($aÃ±os > 1 ? 's' : '');
    } else {
        $antiguedad_texto = $meses . ' mes' . ($meses > 1 ? 'es' : '');
    }
}

// Obtener marcaciones del empleado con ajustes aprobados
$stmt = $pdo->prepare("
    SELECT DATE(m.entrada) as fecha, m.entrada, m.salida,
           sc.nueva_hora_entrada, sc.nueva_hora_salida, sc.motivo
    FROM marcaciones m
    LEFT JOIN solicitudes_cambio sc ON m.id = sc.marcacion_id AND sc.estado = 'aprobado'
    WHERE m.empleado_id = ? 
    ORDER BY m.entrada DESC
");
$stmt->execute([$empleado_id]);
$marcaciones = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial - <?php echo htmlspecialchars($nombre_mostrar); ?></title>
    <link rel="stylesheet" href="empleado.css">
    <link rel="stylesheet" href="historial_emplelado.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header">
            <div class="header-content">
                <div class="logo">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    <span>Control Horario</span>
                </div>
                <div class="user-info">
                    <?php 
                    $mes_param = $_GET['mes'] ?? null;
                    $aÃ±o_param = $_GET['aÃ±o'] ?? null;
                    $back_url = ($mes_param && $aÃ±o_param) ? "reporte_mensual.php?mes=$mes_param&aÃ±o=$aÃ±o_param" : "dueÃ±o.php";
                    $back_text = ($mes_param && $aÃ±o_param) ? "Volver al Reporte" : "Volver al Panel";
                    $pdf_query = http_build_query(['id' => (int)$empleado_id]);
                    ?>
                    <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap; justify-content:flex-end;">
                        <span class="welcome-text">
                            Historial de <?php echo htmlspecialchars($nombre_mostrar); ?>
                            <?php if ($antiguedad_texto): ?>
                                <span style="color: #667eea; font-weight: 600; margin-left: 12px; font-size: 14px;">
                                    â€¢ Trabaja aquÃ­: <?php echo $antiguedad_texto; ?>
                                </span>
                            <?php endif; ?>
                        </span>
                        <a href="historial_empleado_pdf.php?<?php echo htmlspecialchars($pdf_query, ENT_QUOTES, 'UTF-8'); ?>" class="btn-back" style="background: linear-gradient(135deg, #c53030 0%, #9b2c2c 100%); color: white; border: none;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M7 2h8l5 5v15a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2z"></path>
                                <path d="M14 2v6h6"></path>
                            </svg>
                            <span>Descargar PDF</span>
                        </a>
                        <a href="<?php echo $back_url; ?>" class="btn-back">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M19 12H5"></path>
                                <polyline points="12 19 5 12 12 5"></polyline>
                            </svg>
                            <span><?php echo $back_text; ?></span>
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Card de historial -->
            <div class="card historial-card">
                <div class="card-header">
                    <div class="header-title">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <h2>Historial de <?php echo htmlspecialchars($nombre_mostrar); ?></h2>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Entrada</th>
                                    <th>Salida</th>
                                    <th>Horas Trabajadas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($marcaciones) > 0): ?>
                                    <?php foreach ($marcaciones as $fila):
                                        $entrada_dt = $fila['entrada'] ? new DateTime($fila['entrada']) : null;
                                        $salida_dt = $fila['salida'] ? new DateTime($fila['salida']) : null;
                                        $entrada = $entrada_dt ? $entrada_dt->format('H:i') : null;
                                        $salida = $salida_dt ? $salida_dt->format('H:i') : null;
                                        $entrada_ajustada = $fila['nueva_hora_entrada'];
                                        $salida_ajustada = $fila['nueva_hora_salida'];
                                        $tiene_ajuste_entrada = !empty($entrada_ajustada);
                                        $tiene_ajuste_salida = !empty($salida_ajustada);
                                        
                                        // Usar horas ajustadas para cÃ¡lculos si existen (dÃ­a de inicio)
                                        $fecha_base = $fila['fecha'];
                                        $entrada_calcular_dt = $entrada_ajustada ? new DateTime($fecha_base . ' ' . $entrada_ajustada) : $entrada_dt;
                                        if ($salida_ajustada) {
                                            $salida_calcular_dt = new DateTime($fecha_base . ' ' . $salida_ajustada);
                                        } else {
                                            $salida_calcular_dt = $salida_dt;
                                        }
                                        
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
                                            <td data-label="Fecha"><?= htmlspecialchars($fila['fecha']) ?></td>
                                            <td data-label="Entrada">
                                                <?php if ($tiene_ajuste_entrada): ?>
                                                    <div style="display: flex; flex-direction: column; gap: 4px;">
                                                        <span style="text-decoration: line-through; opacity: 0.5; font-size: 12px;">
                                                            <?= $entrada ? substr($entrada, 0, 5) : 'â€”' ?>
                                                        </span>
                                                        <div>
                                                            <strong style="color: #667eea; font-size: 15px;"><?= substr($entrada_ajustada, 0, 5) ?></strong>
                                                            <span style="background: #667eea; color: white; padding: 2px 5px; border-radius: 3px; font-size: 10px; margin-left: 5px;" title="Motivo: <?= htmlspecialchars($fila['motivo']) ?>">Ajustado</span>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <?= $entrada ? substr($entrada, 0, 5) : 'â€”' ?>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="Salida">
                                                <?php if ($tiene_ajuste_salida): ?>
                                                    <div style="display: flex; flex-direction: column; gap: 4px;">
                                                        <span style="text-decoration: line-through; opacity: 0.5; font-size: 12px;">
                                                            <?= $salida ? substr($salida, 0, 5) : 'â€”' ?>
                                                        </span>
                                                        <div>
                                                            <strong style="color: #667eea; font-size: 15px;"><?= substr($salida_ajustada, 0, 5) ?></strong>
                                                            <span style="background: #667eea; color: white; padding: 2px 5px; border-radius: 3px; font-size: 10px; margin-left: 5px;" title="Motivo: <?= htmlspecialchars($fila['motivo']) ?>">Ajustado</span>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <?= $salida ? substr($salida, 0, 5) : 'â€”' ?>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="Horas"><?= $horas ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="empty-state">
                                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                            </svg>
                                            <p>No hay marcaciones registradas para este empleado</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
