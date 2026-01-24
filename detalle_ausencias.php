<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'dueño') {
    header('Location: index.php');
    exit;
}

// Obtener parámetros
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date('m');
$año = isset($_GET['año']) ? (int)$_GET['año'] : (int)date('Y');

// Validar tipo de ausencia
$tipos_validos = ['Vacación', 'Enfermedad', 'Falta Justificada', 'Falta Injustificada'];
if (!in_array($tipo, $tipos_validos)) {
    header('Location: reporte_mensual.php');
    exit;
}

// Obtener el ID del dueño
$dueño_id = $_SESSION['user_id'];

// Calcular primer y último día del mes
$primer_dia = "$año-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-01";
$ultimo_dia = date('Y-m-d', strtotime("$año-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-01 +1 month -1 day"));

// Obtener ausencias del tipo específico
$stmt = $pdo->prepare("
    SELECT 
        ae.fecha,
        ae.tipo_ausencia,
        u.id as empleado_id,
        u.username as empleado_nombre
    FROM ausencias_empleados ae
    INNER JOIN usuarios u ON ae.empleado_id = u.id
    WHERE ae.tipo_ausencia = ?
    AND ae.fecha BETWEEN ? AND ?
    AND u.propietario_id = ?
    ORDER BY ae.fecha ASC, u.username ASC
");
$stmt->execute([$tipo, $primer_dia, $ultimo_dia, $dueño_id]);
$ausencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Agrupar ausencias por empleado
$ausencias_por_empleado = [];
foreach ($ausencias as $ausencia) {
    $emp_id = $ausencia['empleado_id'];
    if (!isset($ausencias_por_empleado[$emp_id])) {
        $ausencias_por_empleado[$emp_id] = [
            'nombre' => $ausencia['empleado_nombre'],
            'fechas' => [],
            'cantidad' => 0
        ];
    }
    $ausencias_por_empleado[$emp_id]['fechas'][] = $ausencia['fecha'];
    $ausencias_por_empleado[$emp_id]['cantidad']++;
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

// Configuración por tipo de ausencia
$config_tipo = [
    'Vacación' => [
        'color' => '#feebc8',
        'icon_color' => '#dd6b20',
        'badge_class' => 'badge-yellow',
        'icono' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>'
    ],
    'Enfermedad' => [
        'color' => '#fed7d7',
        'icon_color' => '#c53030',
        'badge_class' => 'badge-orange',
        'icono' => '<path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>'
    ],
    'Falta Justificada' => [
        'color' => '#bee3f8',
        'icon_color' => '#2c5282',
        'badge_class' => 'badge-purple',
        'icono' => '<polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>'
    ],
    'Falta Injustificada' => [
        'color' => '#fbb6ce',
        'icon_color' => '#97266d',
        'badge_class' => 'badge-red',
        'icono' => '<circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line>'
    ]
];

$config = $config_tipo[$tipo];

// Función para formatear fechas
function formatearFecha($fecha) {
    $meses_cortos = [
        1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr',
        5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
        9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'
    ];
    $partes = explode('-', $fecha);
    return (int)$partes[2] . ' ' . $meses_cortos[(int)$partes[1]];
}

// Función para agrupar fechas consecutivas
function agruparFechas($fechas) {
    if (empty($fechas)) return [];
    
    sort($fechas);
    $grupos = [];
    $grupo_actual = [$fechas[0]];
    
    for ($i = 1; $i < count($fechas); $i++) {
        $fecha_anterior = new DateTime($fechas[$i - 1]);
        $fecha_actual = new DateTime($fechas[$i]);
        $diferencia = $fecha_anterior->diff($fecha_actual)->days;
        
        if ($diferencia == 1) {
            $grupo_actual[] = $fechas[$i];
        } else {
            $grupos[] = $grupo_actual;
            $grupo_actual = [$fechas[$i]];
        }
    }
    $grupos[] = $grupo_actual;
    
    return $grupos;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de <?php echo htmlspecialchars($tipo); ?> - Control Horario</title>
    <link rel="stylesheet" href="empleado.css">
    <style>
        .detalle-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .detalle-header-content {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .detalle-icon {
            width: 64px;
            height: 64px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
        }

        .detalle-info h1 {
            margin: 0;
            font-size: 1.75rem;
            font-weight: 600;
        }

        .detalle-info p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
            font-size: 1rem;
        }

        .stats-mini {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-mini {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
            border-left: 4px solid <?php echo $config['icon_color']; ?>;
        }

        .stat-mini-label {
            color: #718096;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
            display: block;
        }

        .stat-mini-value {
            color: #2d3748;
            font-size: 1.75rem;
            font-weight: 700;
        }

        .empleado-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .empleado-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.12);
        }

        .empleado-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .empleado-card-nombre {
            font-size: 1.125rem;
            font-weight: 600;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .empleado-card-badge {
            background: <?php echo $config['color']; ?>;
            color: <?php echo $config['icon_color']; ?>;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .fechas-container {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .fecha-tag {
            background: <?php echo $config['color']; ?>;
            color: <?php echo $config['icon_color']; ?>;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .fecha-rango {
            background: <?php echo $config['color']; ?>;
            color: <?php echo $config['icon_color']; ?>;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .fecha-rango svg {
            width: 16px;
            height: 16px;
        }

        .empty-state-mini {
            text-align: center;
            padding: 3rem 1rem;
            color: #718096;
        }

        .empty-state-mini svg {
            width: 64px;
            height: 64px;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-state-mini h3 {
            font-size: 1.25rem;
            color: #4a5568;
            margin-bottom: 0.5rem;
        }

        @media (max-width: 768px) {
            .detalle-header-content {
                flex-direction: column;
                text-align: center;
            }

            .empleado-card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
            }
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
                    <span class="welcome-text">Detalle de Ausencias</span>
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
            <!-- Header del tipo de ausencia -->
            <div class="detalle-header">
                <div class="detalle-header-content">
                    <div class="detalle-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <?php echo $config['icono']; ?>
                        </svg>
                    </div>
                    <div class="detalle-info">
                        <h1><?php echo htmlspecialchars($tipo); ?></h1>
                        <p><?php echo $meses[$mes]; ?> <?php echo $año; ?></p>
                    </div>
                </div>
            </div>

            <!-- Estadísticas rápidas -->
            <div class="stats-mini">
                <div class="stat-mini">
                    <span class="stat-mini-label">Total de Empleados</span>
                    <span class="stat-mini-value"><?php echo count($ausencias_por_empleado); ?></span>
                </div>
                <div class="stat-mini">
                    <span class="stat-mini-label">Total de Días</span>
                    <span class="stat-mini-value"><?php echo count($ausencias); ?></span>
                </div>
                <div class="stat-mini">
                    <span class="stat-mini-label">Promedio por Empleado</span>
                    <span class="stat-mini-value">
                        <?php 
                        echo count($ausencias_por_empleado) > 0 
                            ? number_format(count($ausencias) / count($ausencias_por_empleado), 1) 
                            : '0';
                        ?>
                    </span>
                </div>
            </div>

            <!-- Lista de empleados con ausencias -->
            <?php if (empty($ausencias_por_empleado)): ?>
                <div class="card">
                    <div class="card-body">
                        <div class="empty-state-mini">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                            <h3>No hay registros</h3>
                            <p>No se encontraron ausencias de tipo "<?php echo htmlspecialchars($tipo); ?>" en <?php echo $meses[$mes]; ?> <?php echo $año; ?></p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($ausencias_por_empleado as $emp_id => $datos): ?>
                    <div class="empleado-card">
                        <div class="empleado-card-header">
                            <div class="empleado-card-nombre">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                                <?php echo htmlspecialchars($datos['nombre']); ?>
                            </div>
                            <div class="empleado-card-badge">
                                <?php echo $datos['cantidad']; ?> <?php echo $datos['cantidad'] == 1 ? 'día' : 'días'; ?>
                            </div>
                        </div>
                        <div class="fechas-container">
                            <?php 
                            $grupos_fechas = agruparFechas($datos['fechas']);
                            foreach ($grupos_fechas as $grupo):
                                if (count($grupo) == 1):
                            ?>
                                <div class="fecha-tag">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                        <line x1="16" y1="2" x2="16" y2="6"></line>
                                        <line x1="8" y1="2" x2="8" y2="6"></line>
                                        <line x1="3" y1="10" x2="21" y2="10"></line>
                                    </svg>
                                    <?php echo formatearFecha($grupo[0]); ?>
                                </div>
                            <?php else: ?>
                                <div class="fecha-rango">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                        <line x1="16" y1="2" x2="16" y2="6"></line>
                                        <line x1="8" y1="2" x2="8" y2="6"></line>
                                        <line x1="3" y1="10" x2="21" y2="10"></line>
                                    </svg>
                                    <?php echo formatearFecha($grupo[0]); ?>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M5 12h14"></path>
                                        <polyline points="12 5 19 12 12 19"></polyline>
                                    </svg>
                                    <?php echo formatearFecha($grupo[count($grupo) - 1]); ?>
                                    <span style="font-size: 0.75rem; opacity: 0.8;">(<?php echo count($grupo); ?> días)</span>
                                </div>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
