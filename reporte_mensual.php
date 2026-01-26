<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'dueño') {
    header('Location: index.php');
    exit;
}

// Obtener mes y año actual
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date('m');
$año = isset($_GET['año']) ? (int)$_GET['año'] : (int)date('Y');

// Validar mes
if ($mes < 1) {
    $mes = 12;
    $año--;
} elseif ($mes > 12) {
    $mes = 1;
    $año++;
}

// Obtener el ID del dueño
$dueño_id = $_SESSION['user_id'];

// Obtener todos los empleados del dueño
$stmt_empleados = $pdo->prepare("
    SELECT id, username 
    FROM usuarios 
    WHERE rol = 'empleado' 
    AND propietario_id = ? 
    ORDER BY username
");
$stmt_empleados->execute([$dueño_id]);
$empleados = $stmt_empleados->fetchAll(PDO::FETCH_ASSOC);

// Calcular primer y último día del mes
$primer_dia = "$año-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-01";
$ultimo_dia = date('Y-m-d', strtotime("$año-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-01 +1 month -1 day"));

// Obtener datos de marcaciones del mes
$stmt_marcaciones = $pdo->prepare("
    SELECT 
        empleado_id,
        COUNT(*) as dias_trabajados,
        SUM(CASE WHEN hora_entrada IS NOT NULL THEN 1 ELSE 0 END) as marcaciones_entrada,
        SUM(CASE WHEN hora_salida IS NOT NULL THEN 1 ELSE 0 END) as marcaciones_salida
    FROM marcaciones 
    WHERE fecha BETWEEN ? AND ? AND empleado_id IN (
        SELECT id FROM usuarios WHERE rol = 'empleado' AND propietario_id = ?
    )
    GROUP BY empleado_id
");
$stmt_marcaciones->execute([$primer_dia, $ultimo_dia, $dueño_id]);
$marcaciones_data = [];
foreach ($stmt_marcaciones->fetchAll(PDO::FETCH_ASSOC) as $m) {
    $marcaciones_data[$m['empleado_id']] = $m;
}

// Obtener ausencias del mes
$stmt_ausencias = $pdo->prepare("
    SELECT 
        empleado_id,
        tipo_ausencia,
        COUNT(*) as cantidad
    FROM ausencias_empleados 
    WHERE fecha BETWEEN ? AND ? AND empleado_id IN (
        SELECT id FROM usuarios WHERE rol = 'empleado' AND propietario_id = ?
    )
    GROUP BY empleado_id, tipo_ausencia
");
$stmt_ausencias->execute([$primer_dia, $ultimo_dia, $dueño_id]);
$ausencias_data = [];
foreach ($stmt_ausencias->fetchAll(PDO::FETCH_ASSOC) as $a) {
    if (!isset($ausencias_data[$a['empleado_id']])) {
        $ausencias_data[$a['empleado_id']] = [];
    }
    $ausencias_data[$a['empleado_id']][$a['tipo_ausencia']] = $a['cantidad'];
}

// Obtener días de descanso del mes (para excluirlos del cálculo de asistencia)
$stmt_descansos = $pdo->prepare("
    SELECT 
        empleado_id,
        COUNT(*) as dias_descanso
    FROM horarios_semanales 
    WHERE fecha_descanso BETWEEN ? AND ? AND empleado_id IN (
        SELECT id FROM usuarios WHERE rol = 'empleado' AND propietario_id = ?
    )
    GROUP BY empleado_id
");
$stmt_descansos->execute([$primer_dia, $ultimo_dia, $dueño_id]);
$descansos_data = [];
foreach ($stmt_descansos->fetchAll(PDO::FETCH_ASSOC) as $d) {
    $descansos_data[$d['empleado_id']] = $d['dias_descanso'];
}

// Obtener total de horas trabajadas por empleado
$stmt_horas = $pdo->prepare("
    SELECT 
        empleado_id,
        SEC_TO_TIME(SUM(TIME_TO_SEC(TIMEDIFF(hora_salida, hora_entrada)))) as total_horas
    FROM marcaciones 
    WHERE fecha BETWEEN ? AND ? 
    AND hora_entrada IS NOT NULL 
    AND hora_salida IS NOT NULL
    AND empleado_id IN (
        SELECT id FROM usuarios WHERE rol = 'empleado' AND propietario_id = ?
    )
    GROUP BY empleado_id
");
$stmt_horas->execute([$primer_dia, $ultimo_dia, $dueño_id]);
$horas_data = [];
foreach ($stmt_horas->fetchAll(PDO::FETCH_ASSOC) as $h) {
    $horas_data[$h['empleado_id']] = $h['total_horas'];
}

// Nombres de meses en español
$meses = [
    1 => 'Enero',
    2 => 'Febrero',
    3 => 'Marzo',
    4 => 'Abril',
    5 => 'Mayo',
    6 => 'Junio',
    7 => 'Julio',
    8 => 'Agosto',
    9 => 'Septiembre',
    10 => 'Octubre',
    11 => 'Noviembre',
    12 => 'Diciembre'
];

// Calcular mes anterior y siguiente
$mes_anterior = $mes - 1;
$año_anterior = $año;
if ($mes_anterior < 1) {
    $mes_anterior = 12;
    $año_anterior--;
}

$mes_siguiente = $mes + 1;
$año_siguiente = $año;
if ($mes_siguiente > 12) {
    $mes_siguiente = 1;
    $año_siguiente++;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Mensual - Control Horario</title>
    <link rel="stylesheet" href="empleado.css">
    <link rel="stylesheet" href="reporte_mensual.css">
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
                    <span class="welcome-text">Reporte Mensual</span>
                    <a href="dueño.php" class="btn-back">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 12H5"></path>
                            <polyline points="12 19 5 12 12 5"></polyline>
                        </svg>
                        <span>Volver al Panel</span>
                    </a>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Selector de Mes -->
            <div class="card">
                <div class="card-body">
                    <div class="month-selector">
                        <a href="?mes=<?php echo $mes_anterior; ?>&año=<?php echo $año_anterior; ?>" class="btn-nav-month">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="15 18 9 12 15 6"></polyline>
                            </svg>
                        </a>
                        <div class="month-display">
                            <h2><?php echo $meses[$mes]; ?> <?php echo $año; ?></h2>
                        </div>
                        <a href="?mes=<?php echo $mes_siguiente; ?>&año=<?php echo $año_siguiente; ?>" class="btn-nav-month">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Resumen General del Mes -->
            <div class="card">
                <div class="card-header">
                    <h3>Resumen General</h3>
                </div>
                <div class="card-body">
                    <div class="summary-grid">
                        <div class="summary-item">
                            <div class="summary-icon" style="background-color: #bee3f8;">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                </svg>
                            </div>
                            <div class="summary-text">
                                <span class="summary-label">Total Empleados</span>
                                <span class="summary-value"><?php echo count($empleados); ?></span>
                            </div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-icon" style="background-color: #c6f6d5;">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                                    <circle cx="12" cy="13" r="4"></circle>
                                </svg>
                            </div>
                            <div class="summary-text">
                                <span class="summary-label">Días Laborables</span>
                                <span class="summary-value"><?php echo (int)((strtotime($ultimo_dia) - strtotime($primer_dia)) / 86400) + 1; ?></span>
                            </div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-icon" style="background-color: #feebc8;">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                                </svg>
                            </div>
                            <div class="summary-text">
                                <span class="summary-label">Promedio Asistencia</span>
                                <span class="summary-value">
                                    <?php
                                    if (count($empleados) > 0) {
                                        $total_marcaciones = 0;
                                        foreach ($marcaciones_data as $m) {
                                            $total_marcaciones += $m['dias_trabajados'];
                                        }
                                        $promedio = ($total_marcaciones / count($empleados)) / (((strtotime($ultimo_dia) - strtotime($primer_dia)) / 86400) + 1) * 100;
                                        echo number_format($promedio, 1) . '%';
                                    } else {
                                        echo '—';
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de Empleados -->
            <div class="card">
                <div class="card-header">
                    <h3>Detalle por Empleado</h3>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table class="reporte-table">
                            <thead>
                                <tr>
                                    <th>Empleado</th>
                                    <th>Días Trabajados</th>
                                    <th>Horas Totales</th>
                                    <th>Vacaciones Ley</th>
                                    <th>Enfermedad</th>
                                    <th>Faltas Justificadas</th>
                                    <th>Asistencia %</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($empleados)): ?>
                                    <tr>
                                        <td colspan="8" class="empty-state">
                                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                            </svg>
                                            <p>No hay empleados registrados</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php 
                                    $dias_mes = (int)((strtotime($ultimo_dia) - strtotime($primer_dia)) / 86400) + 1;
                                    foreach ($empleados as $emp): 
                                        $marcaciones = $marcaciones_data[$emp['id']] ?? ['dias_trabajados' => 0];
                                        $ausencias = $ausencias_data[$emp['id']] ?? [];
                                        $horas_totales = $horas_data[$emp['id']] ?? '00:00:00';
                                        $dias_descanso = $descansos_data[$emp['id']] ?? 0;
                                        
                                        // Calcular porcentaje de asistencia
                                        $dias_registrados = $marcaciones['dias_trabajados'];
                                        $vacaciones = $ausencias['vacaciones_ley'] ?? 0;
                                        $enfermedad = $ausencias['enfermedad'] ?? 0;
                                        $emergencia = $ausencias['emergencia_familiar'] ?? 0;
                                        $fuerza_mayor = $ausencias['fuerza_mayor'] ?? 0;
                                        $faltas_justificadas = $emergencia + $fuerza_mayor;
                                        
                                        // Restar días de descanso y vacaciones del total esperado
                                        $dias_esperados = $dias_mes - $vacaciones - $dias_descanso;
                                        $asistencia = $dias_esperados > 0 ? ($dias_registrados / $dias_esperados * 100) : 0;
                                    ?>
                                    <tr>
                                        <td data-label="Empleado">
                                            <a href="historial_empleado.php?id=<?php echo $emp['id']; ?>&mes=<?php echo $mes; ?>&año=<?php echo $año; ?>" 
                                               class="empleado-name"
                                               style="color: #667eea; text-decoration: none; font-weight: 500; cursor: pointer; transition: all 0.2s;"
                                               onmouseover="this.style.color='#764ba2'; this.style.textDecoration='underline';"
                                               onmouseout="this.style.color='#667eea'; this.style.textDecoration='none';">
                                                <?php echo htmlspecialchars($emp['username']); ?>
                                            </a>
                                        </td>
                                        <td data-label="Días Trabajados">
                                            <a href="calendario_empleado.php?id=<?php echo $emp['id']; ?>&mes=<?php echo $mes; ?>&año=<?php echo $año; ?>" 
                                               class="badge badge-blue" 
                                               style="text-decoration: none; cursor: pointer; transition: transform 0.2s;"
                                               onmouseover="this.style.transform='scale(1.1)'"
                                               onmouseout="this.style.transform='scale(1)'">
                                                <?php echo $dias_registrados; ?>
                                            </a>
                                        </td>
                                        <td data-label="Horas Totales">
                                            <a href="calendario_empleado.php?id=<?php echo $emp['id']; ?>&mes=<?php echo $mes; ?>&año=<?php echo $año; ?>" 
                                               class="badge badge-green" 
                                               style="text-decoration: none; cursor: pointer; transition: transform 0.2s;"
                                               onmouseover="this.style.transform='scale(1.1)'"
                                               onmouseout="this.style.transform='scale(1)'">
                                                <?php echo substr($horas_totales, 0, 5); ?>
                                            </a>
                                        </td>
                                        <td data-label="Vacaciones Ley">
                                            <a href="calendario_empleado.php?id=<?php echo $emp['id']; ?>&mes=<?php echo $mes; ?>&año=<?php echo $año; ?>" 
                                               class="badge badge-yellow" 
                                               style="text-decoration: none; cursor: pointer; transition: transform 0.2s;"
                                               onmouseover="this.style.transform='scale(1.1)'"
                                               onmouseout="this.style.transform='scale(1)'">
                                                <?php echo $vacaciones; ?>
                                            </a>
                                        </td>
                                        <td data-label="Enfermedad">
                                            <a href="calendario_empleado.php?id=<?php echo $emp['id']; ?>&mes=<?php echo $mes; ?>&año=<?php echo $año; ?>" 
                                               class="badge badge-orange" 
                                               style="text-decoration: none; cursor: pointer; transition: transform 0.2s;"
                                               onmouseover="this.style.transform='scale(1.1)'"
                                               onmouseout="this.style.transform='scale(1)'">
                                                <?php echo $enfermedad; ?>
                                            </a>
                                        </td>
                                        <td data-label="Faltas Justificadas">
                                            <a href="calendario_empleado.php?id=<?php echo $emp['id']; ?>&mes=<?php echo $mes; ?>&año=<?php echo $año; ?>" 
                                               class="badge badge-purple" 
                                               style="text-decoration: none; cursor: pointer; transition: transform 0.2s;"
                                               onmouseover="this.style.transform='scale(1.1)'"
                                               onmouseout="this.style.transform='scale(1)'">
                                                <?php echo $faltas_justificadas; ?>
                                            </a>
                                        </td>
                                        <td data-label="Asistencia %">
                                            <div class="progress-bar">
                                                <div class="progress-fill" style="width: <?php echo $asistencia; ?>%; background: <?php echo $asistencia >= 80 ? '#48bb78' : ($asistencia >= 60 ? '#ed8936' : '#e53e3e'); ?>;"></div>
                                            </div>
                                            <span class="progress-text"><?php echo number_format($asistencia, 1); ?>%</span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Gráfico de Ausencias por Tipo -->
            <div class="card">
                <div class="card-header">
                    <h3>Resumen de Ausencias por Tipo</h3>
                </div>
                <div class="card-body">
                    <div class="absence-summary">
                        <?php
                        $total_vacaciones = 0;
                        $total_enfermedad = 0;
                        $total_emergencia = 0;
                        $total_fuerza_mayor = 0;
                        $total_faltas_justificadas = 0;
                        
                        foreach ($ausencias_data as $ausencias) {
                            $total_vacaciones += $ausencias['vacaciones_ley'] ?? 0;
                            $total_enfermedad += $ausencias['enfermedad'] ?? 0;
                            $total_emergencia += $ausencias['emergencia_familiar'] ?? 0;
                            $total_fuerza_mayor += $ausencias['fuerza_mayor'] ?? 0;
                            $total_faltas_justificadas += ($ausencias['emergencia_familiar'] ?? 0) + ($ausencias['fuerza_mayor'] ?? 0);
                        }
                        
                        $total_ausencias = $total_vacaciones + $total_enfermedad + $total_emergencia + $total_fuerza_mayor;
                        ?>
                        <div class="absence-item" 
                           style="text-decoration: none; color: inherit; cursor: default;">
                            <div class="absence-badge" style="background-color: #e9d8fd;">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="9 11 12 14 22 4"></polyline>
                                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                                </svg>
                            </div>
                            <div class="absence-content">
                                <span class="absence-label">Faltas Justificadas</span>
                                <span class="absence-count"><?php echo $total_faltas_justificadas; ?> días</span>
                                <small style="color: #718096; display: block;">Incluye emergencia familiar y fuerza mayor</small>
                            </div>
                        </div>
                        <a href="detalle_ausencias.php?tipo=vacaciones_ley&mes=<?php echo $mes; ?>&año=<?php echo $año; ?>" 
                           class="absence-item" 
                           style="text-decoration: none; color: inherit; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;"
                           onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.12)';"
                           onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.08)';">
                            <div class="absence-badge" style="background-color: #feebc8;">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                                </svg>
                            </div>
                            <div class="absence-content">
                                <span class="absence-label">Vacaciones Ley</span>
                                <span class="absence-count"><?php echo $total_vacaciones; ?> días</span>
                            </div>
                        </a>
                        <a href="detalle_ausencias.php?tipo=enfermedad&mes=<?php echo $mes; ?>&año=<?php echo $año; ?>" 
                           class="absence-item" 
                           style="text-decoration: none; color: inherit; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;"
                           onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.12)';"
                           onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.08)';">
                            <div class="absence-badge" style="background-color: #fed7d7;">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
                                </svg>
                            </div>
                            <div class="absence-content">
                                <span class="absence-label">Enfermedad</span>
                                <span class="absence-count"><?php echo $total_enfermedad; ?> días</span>
                            </div>
                        </a>
                        <a href="detalle_ausencias.php?tipo=emergencia_familiar&mes=<?php echo $mes; ?>&año=<?php echo $año; ?>" 
                           class="absence-item" 
                           style="text-decoration: none; color: inherit; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;"
                           onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.12)';"
                           onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.08)';">
                            <div class="absence-badge" style="background-color: #bee3f8;">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="9 11 12 14 22 4"></polyline>
                                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                                </svg>
                            </div>
                            <div class="absence-content">
                                <span class="absence-label">Emergencia Familiar</span>
                                <span class="absence-count"><?php echo $total_emergencia; ?> días</span>
                            </div>
                        </a>
                        <a href="detalle_ausencias.php?tipo=fuerza_mayor&mes=<?php echo $mes; ?>&año=<?php echo $año; ?>" 
                           class="absence-item" 
                           style="text-decoration: none; color: inherit; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;"
                           onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.12)';"
                           onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.08)';">
                            <div class="absence-badge" style="background-color: #fbb6ce;">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="15" y1="9" x2="9" y2="15"></line>
                                    <line x1="9" y1="9" x2="15" y2="15"></line>
                                </svg>
                            </div>
                            <div class="absence-content">
                                <span class="absence-label">Fuerza Mayor</span>
                                <span class="absence-count"><?php echo $total_fuerza_mayor; ?> días</span>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
