<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'dueño') {
    header('Location: index.php');
    exit;
}

$empleado_id = $_GET['id'] ?? 0;
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date('m');
$año = isset($_GET['año']) ? (int)$_GET['año'] : (int)date('Y');

// Validar que el empleado exista
$stmt = $pdo->prepare("SELECT username FROM usuarios WHERE id = ? AND rol = 'empleado'");
$stmt->execute([$empleado_id]);
$empleado = $stmt->fetch();

if (!$empleado) {
    die('Empleado no encontrado.');
}

$username = $empleado['username'];

// Calcular primer y último día del mes
$primer_dia = "$año-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-01";
$ultimo_dia = date('Y-m-d', strtotime("$año-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-01 +1 month -1 day"));

// Obtener todas las marcaciones del mes con ajustes aprobados
$stmt_marcaciones = $pdo->prepare("
    SELECT m.fecha, m.hora_entrada, m.hora_salida,
           sc.nueva_hora_entrada, sc.nueva_hora_salida,
           CASE 
               WHEN sc.nueva_hora_entrada IS NOT NULL THEN 
                   TIME_TO_SEC(TIMEDIFF(sc.nueva_hora_salida, sc.nueva_hora_entrada)) / 3600
               WHEN m.hora_entrada IS NOT NULL AND m.hora_salida IS NOT NULL THEN
                   TIME_TO_SEC(TIMEDIFF(m.hora_salida, m.hora_entrada)) / 3600
               ELSE 0
           END as horas_trabajadas
    FROM marcaciones m
    LEFT JOIN solicitudes_cambio sc ON m.id = sc.marcacion_id AND sc.estado = 'aprobado'
    WHERE m.empleado_id = ? AND m.fecha BETWEEN ? AND ?
");
$stmt_marcaciones->execute([$empleado_id, $primer_dia, $ultimo_dia]);
$marcaciones = [];
foreach ($stmt_marcaciones->fetchAll() as $m) {
    $marcaciones[$m['fecha']] = $m;
}

// Obtener todas las ausencias del mes
$stmt_ausencias = $pdo->prepare("
    SELECT fecha, tipo_ausencia
    FROM ausencias_empleados
    WHERE empleado_id = ? AND fecha BETWEEN ? AND ?
");
$stmt_ausencias->execute([$empleado_id, $primer_dia, $ultimo_dia]);
$ausencias = [];
foreach ($stmt_ausencias->fetchAll() as $a) {
    $ausencias[$a['fecha']] = $a['tipo_ausencia'];
}

// Nombres de meses en español
$meses = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];

// Calcular información del calendario
$primer_dia_mes = new DateTime($primer_dia);
$dia_semana_inicio = (int)$primer_dia_mes->format('N'); // 1=Lunes, 7=Domingo
$dias_en_mes = (int)$primer_dia_mes->format('t');

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
    <title>Calendario - <?php echo htmlspecialchars($username); ?></title>
    <link rel="stylesheet" href="empleado.css">
    <style>
        :root {
            --color-trabajado: linear-gradient(135deg, #4299e1, #3182ce);
            --color-vacacion: linear-gradient(135deg, #f6ad55, #ed8936);
            --color-enfermedad: linear-gradient(135deg, #68d391, #38b2ac);
            --color-falta-just: linear-gradient(135deg, #fc8181, #f56565);
            --color-falta-injust: linear-gradient(135deg, #ef4444, #dc2626);
            --color-incompleto: linear-gradient(135deg, #cbd5e0, #a0aec0);
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.08);
            --shadow-md: 0 4px 16px rgba(0,0,0,0.12);
            --shadow-lg: 0 8px 24px rgba(0,0,0,0.15);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .calendar-container {
            background: white;
            border-radius: 16px;
            box-shadow: var(--shadow-md);
            padding: 32px;
            margin: 20px 0;
            animation: slideUp 0.4s ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            padding-bottom: 20px;
            border-bottom: 3px solid transparent;
            background: linear-gradient(white, white) padding-box,
                        linear-gradient(90deg, #667eea, #764ba2) border-box;
            border-radius: 0;
        }

        .calendar-title {
            font-size: 28px;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .calendar-nav {
            display: flex;
            gap: 12px;
        }

        .calendar-nav-btn {
            padding: 10px 18px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
            font-weight: 500;
            transition: var(--transition);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .calendar-nav-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .calendar-nav-btn:active {
            transform: translateY(0);
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 12px;
        }

        .calendar-day-header {
            text-align: center;
            font-weight: 700;
            color: #667eea;
            padding: 14px;
            background: linear-gradient(135deg, #f7fafc, #edf2f7);
            border-radius: 10px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .calendar-day {
            aspect-ratio: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border: 2px solid #e2e8f0;
            border-radius: 14px;
            position: relative;
            background: linear-gradient(135deg, #ffffff 0%, #f7fafc 100%);
            transition: var(--transition);
            min-height: 110px;
            cursor: pointer;
        }

        .calendar-day:hover {
            border-color: #667eea;
            box-shadow: var(--shadow-md);
            transform: translateY(-4px) scale(1.02);
            background: white;
        }

        .calendar-day.empty {
            border-color: transparent;
            background: #fafafa;
            cursor: default;
        }

        .calendar-day.empty:hover {
            transform: none;
            box-shadow: none;
        }

        .day-number {
            font-size: 15px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 10px;
            background: #f7fafc;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .day-indicator {
            width: 54px;
            height: 54px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: white;
            font-size: 11px;
            text-align: center;
            line-height: 1.3;
            transition: var(--transition);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .calendar-day:hover .day-indicator {
            transform: scale(1.15) rotate(5deg);
            box-shadow: 0 6px 16px rgba(0,0,0,0.2);
        }

        .indicator-trabajado { background: var(--color-trabajado); }
        .indicator-vacacion { background: var(--color-vacacion); }
        .indicator-enfermedad { background: var(--color-enfermedad); }
        .indicator-falta-justificada { background: var(--color-falta-just); }
        .indicator-falta-injustificada { background: var(--color-falta-injust); }
        .indicator-incompleto { background: var(--color-incompleto); }

        .legend {
            display: flex;
            flex-wrap: wrap;
            gap: 24px;
            margin-top: 32px;
            padding: 24px;
            background: linear-gradient(135deg, #f7fafc, #edf2f7);
            border-radius: 14px;
            border: 2px solid #e2e8f0;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            background: white;
            border-radius: 8px;
            transition: var(--transition);
        }

        .legend-item:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }

        .legend-circle {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }

        .legend-label {
            font-size: 13px;
            color: #4a5568;
            font-weight: 500;
        }

        .stats-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: linear-gradient(135deg, #ffffff 0%, #f7fafc 100%);
            padding: 20px;
            border-radius: 14px;
            border-left: 5px solid;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255,255,255,0.8), transparent);
            opacity: 0;
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
        }

        .stat-card:hover::before {
            opacity: 1;
            animation: shimmer 1.5s ease-in-out;
        }

        @keyframes shimmer {
            0% { transform: translate(-100%, -100%); }
            100% { transform: translate(100%, 100%); }
        }

        .stat-label {
            font-size: 11px;
            color: #718096;
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 800;
            color: #2d3748;
            line-height: 1;
        }

        @media (max-width: 768px) {
            .calendar-container { padding: 20px; }
            .calendar-grid { gap: 6px; }
            .calendar-day { min-height: 85px; }
            .day-indicator { width: 44px; height: 44px; font-size: 10px; }
            .legend { gap: 14px; padding: 16px; }
            .stats-summary { gap: 12px; }
            .calendar-title { font-size: 22px; }
        }
    </style>
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
                    <span class="welcome-text">Calendario de <?php echo htmlspecialchars($username); ?></span>
                    <a href="reporte_mensual.php?mes=<?php echo $mes; ?>&año=<?php echo $año; ?>" class="btn-back">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 12H5"></path>
                            <polyline points="12 19 5 12 12 5"></polyline>
                        </svg>
                        <span>Volver al Reporte</span>
                    </a>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Estadísticas Resumidas -->
            <div class="stats-summary">
                <?php
                $total_trabajados = 0;
                $total_vacaciones = 0;
                $total_enfermedad = 0;
                $total_falta_just = 0;
                $total_falta_injust = 0;
                $total_horas = 0;

                foreach ($ausencias as $tipo) {
                    if ($tipo === 'Vacación') $total_vacaciones++;
                    elseif ($tipo === 'Enfermedad') $total_enfermedad++;
                    elseif ($tipo === 'Falta Justificada') $total_falta_just++;
                    elseif ($tipo === 'Falta Injustificada') $total_falta_injust++;
                }

                foreach ($marcaciones as $m) {
                    if ($m['hora_entrada'] && $m['hora_salida']) {
                        $total_trabajados++;
                        $total_horas += $m['horas_trabajadas'];
                    }
                }
                
                // Array de estadísticas para DRY
                $stats = [
                    ['label' => 'Días Trabajados', 'value' => $total_trabajados, 'color' => '#4299e1', 'icon' => '<path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>'],
                    ['label' => 'Vacaciones', 'value' => $total_vacaciones, 'color' => '#f6ad55', 'icon' => '<path d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"></path>'],
                    ['label' => 'Enfermedad', 'value' => $total_enfermedad, 'color' => '#68d391', 'icon' => '<path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>'],
                    ['label' => 'Faltas Justificadas', 'value' => $total_falta_just, 'color' => '#fc8181', 'icon' => '<path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>'],
                    ['label' => 'Faltas Injustificadas', 'value' => $total_falta_injust, 'color' => '#ef4444', 'icon' => '<path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>'],
                    ['label' => 'Total Horas', 'value' => number_format($total_horas, 1), 'color' => '#667eea', 'icon' => '<path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>']
                ];

                foreach ($stats as $stat): ?>
                    <div class="stat-card" style="border-left-color: <?php echo $stat['color']; ?>;">
                        <div class="stat-label">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline; margin-right: 4px; vertical-align: middle;">
                                <?php echo $stat['icon']; ?>
                            </svg>
                            <?php echo $stat['label']; ?>
                        </div>
                        <div class="stat-value"><?php echo $stat['value']; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Calendario -->
            <div class="calendar-container">
                <div class="calendar-header">
                    <div class="calendar-title"><?php echo $meses[$mes] . ' ' . $año; ?></div>
                    <div class="calendar-nav">
                        <a href="?id=<?php echo $empleado_id; ?>&mes=<?php echo $mes_anterior; ?>&año=<?php echo $año_anterior; ?>" 
                           class="calendar-nav-btn">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="15 18 9 12 15 6"></polyline>
                            </svg>
                            Anterior
                        </a>
                        <a href="?id=<?php echo $empleado_id; ?>&mes=<?php echo $mes_siguiente; ?>&año=<?php echo $año_siguiente; ?>" 
                           class="calendar-nav-btn">
                            Siguiente
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </a>
                    </div>
                </div>

                <div class="calendar-grid">
                    <!-- Encabezados de días -->
                    <div class="calendar-day-header">Lun</div>
                    <div class="calendar-day-header">Mar</div>
                    <div class="calendar-day-header">Mié</div>
                    <div class="calendar-day-header">Jue</div>
                    <div class="calendar-day-header">Vie</div>
                    <div class="calendar-day-header">Sáb</div>
                    <div class="calendar-day-header">Dom</div>

                    <?php
                    // Días vacíos antes del primer día
                    for ($i = 1; $i < $dia_semana_inicio; $i++) {
                        echo '<div class="calendar-day empty"></div>';
                    }

                    // Días del mes
                    for ($dia = 1; $dia <= $dias_en_mes; $dia++) {
                        $fecha = sprintf('%04d-%02d-%02d', $año, $mes, $dia);
                        $tiene_marcacion = isset($marcaciones[$fecha]);
                        $tiene_ausencia = isset($ausencias[$fecha]);

                        echo '<div class="calendar-day">';
                        echo '<div class="day-number">' . $dia . '</div>';

                        if ($tiene_ausencia) {
                            $tipo = $ausencias[$fecha];
                            // Mapeo DRY de tipos de ausencia
                            $tipos_ausencia = [
                                'Vacación' => ['clase' => 'indicator-vacacion', 'texto' => 'Vacaciones'],
                                'Enfermedad' => ['clase' => 'indicator-enfermedad', 'texto' => 'Enfermedad'],
                                'Falta Justificada' => ['clase' => 'indicator-falta-justificada', 'texto' => 'Falta Just.'],
                                'Falta Injustificada' => ['clase' => 'indicator-falta-injustificada', 'texto' => 'Falta Inj.']
                            ];
                            
                            $info = $tipos_ausencia[$tipo] ?? ['clase' => 'indicator-incompleto', 'texto' => $tipo];
                            echo '<div class="day-indicator ' . $info['clase'] . '">' . $info['texto'] . '</div>';
                        } elseif ($tiene_marcacion) {
                            $m = $marcaciones[$fecha];
                            if ($m['hora_entrada'] && $m['hora_salida']) {
                                $horas = $m['horas_trabajadas'];
                                $tamaño = 44 + min(($horas / 12) * 16, 16); // 44-60px
                                echo '<div class="day-indicator indicator-trabajado" style="width: ' . $tamaño . 'px; height: ' . $tamaño . 'px;">';
                                echo number_format($horas, 1) . 'h';
                                echo '</div>';
                            } else {
                                echo '<div class="day-indicator indicator-incompleto">Incompleto</div>';
                            }
                        }

                        echo '</div>';
                    }

                    // Días vacíos después del último día
                    $dia_semana_final = (int)date('N', strtotime($ultimo_dia));
                    for ($i = $dia_semana_final; $i < 7; $i++) {
                        echo '<div class="calendar-day empty"></div>';
                    }
                    ?>
                </div>

                <!-- Leyenda -->
                <div class="legend">
                    <?php
                    // Array DRY para la leyenda
                    $leyenda_items = [
                        ['color' => 'linear-gradient(135deg, #4299e1, #3182ce)', 'texto' => 'Día Trabajado (tamaño según horas)'],
                        ['color' => 'linear-gradient(135deg, #f6ad55, #ed8936)', 'texto' => 'Vacaciones'],
                        ['color' => 'linear-gradient(135deg, #68d391, #38b2ac)', 'texto' => 'Enfermedad'],
                        ['color' => 'linear-gradient(135deg, #fc8181, #f56565)', 'texto' => 'Falta Justificada'],
                        ['color' => 'linear-gradient(135deg, #ef4444, #dc2626)', 'texto' => 'Falta Injustificada'],
                        ['color' => 'linear-gradient(135deg, #cbd5e0, #a0aec0)', 'texto' => 'Registro Incompleto']
                    ];

                    foreach ($leyenda_items as $item): ?>
                        <div class="legend-item">
                            <div class="legend-circle" style="background: <?php echo $item['color']; ?>;"></div>
                            <span class="legend-label"><?php echo $item['texto']; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
