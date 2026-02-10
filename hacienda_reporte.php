<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'hacienda') {
    header('Location: index.php');
    exit;
}

$dueno_id = $_GET['dueno_id'] ?? 0;
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

// Obtener información del dueño
$stmt_dueno = $pdo->prepare("SELECT username FROM usuarios WHERE id = ? AND rol = 'dueño'");
$stmt_dueno->execute([$dueno_id]);
$dueno = $stmt_dueno->fetch();

if (!$dueno) {
    die('Dueño no encontrado.');
}

// Obtener empleados del dueño
$stmt_empleados = $pdo->prepare("
    SELECT id, username 
    FROM usuarios 
    WHERE rol = 'empleado' 
    AND propietario_id = ? 
    ORDER BY username
");
$stmt_empleados->execute([$dueno_id]);
$empleados = $stmt_empleados->fetchAll(PDO::FETCH_ASSOC);

// Calcular primer y último día del mes
$primer_dia = "$año-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-01";
$ultimo_dia = date('Y-m-d', strtotime("$año-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-01 +1 month -1 day"));

// Obtener datos de marcaciones del mes
$stmt_marcaciones = $pdo->prepare("
    SELECT 
        empleado_id,
        COUNT(DISTINCT DATE(entrada)) as dias_trabajados,
        SUM(CASE WHEN entrada IS NOT NULL THEN 1 ELSE 0 END) as marcaciones_entrada,
        SUM(CASE WHEN salida IS NOT NULL THEN 1 ELSE 0 END) as marcaciones_salida
    FROM marcaciones 
    WHERE DATE(entrada) BETWEEN ? AND ? AND empleado_id IN (
        SELECT id FROM usuarios WHERE rol = 'empleado' AND propietario_id = ?
    )
    GROUP BY empleado_id
");
$stmt_marcaciones->execute([$primer_dia, $ultimo_dia, $dueno_id]);
$marcaciones_data = [];
foreach ($stmt_marcaciones->fetchAll(PDO::FETCH_ASSOC) as $m) {
    $marcaciones_data[$m['empleado_id']] = $m;
}

// Obtener total de horas trabajadas por empleado (usando horas ajustadas si existen)
$stmt_horas = $pdo->prepare("
    SELECT 
        m.empleado_id,
        SEC_TO_TIME(SUM(
            CASE
                WHEN sc.nueva_hora_entrada IS NOT NULL AND sc.nueva_hora_salida IS NOT NULL THEN
                    TIMESTAMPDIFF(SECOND,
                        CONCAT(DATE(m.entrada), ' ', sc.nueva_hora_entrada),
                        CASE
                            WHEN sc.nueva_hora_salida < sc.nueva_hora_entrada THEN DATE_ADD(CONCAT(DATE(m.entrada), ' ', sc.nueva_hora_salida), INTERVAL 1 DAY)
                            ELSE CONCAT(DATE(m.entrada), ' ', sc.nueva_hora_salida)
                        END
                    )
                ELSE TIMESTAMPDIFF(SECOND, m.entrada, m.salida)
            END
        )) as total_horas
    FROM marcaciones m
    LEFT JOIN solicitudes_cambio sc ON m.id = sc.marcacion_id AND sc.estado = 'aprobado'
    WHERE DATE(m.entrada) BETWEEN ? AND ? 
    AND (
        (sc.nueva_hora_entrada IS NOT NULL AND sc.nueva_hora_salida IS NOT NULL)
        OR (m.entrada IS NOT NULL AND m.salida IS NOT NULL)
    )
    AND m.empleado_id IN (
        SELECT id FROM usuarios WHERE rol = 'empleado' AND propietario_id = ?
    )
    GROUP BY m.empleado_id
");
$stmt_horas->execute([$primer_dia, $ultimo_dia, $dueno_id]);
$horas_data = [];
foreach ($stmt_horas->fetchAll(PDO::FETCH_ASSOC) as $h) {
    $horas_data[$h['empleado_id']] = $h['total_horas'];
}

// Obtener cantidad de ajustes aprobados por empleado
$stmt_ajustes = $pdo->prepare("
    SELECT empleado_id, COUNT(*) as cantidad_ajustes
    FROM solicitudes_cambio
    WHERE estado = 'aprobado'
    AND marcacion_id IN (
        SELECT id FROM marcaciones WHERE DATE(entrada) BETWEEN ? AND ?
    )
    AND empleado_id IN (
        SELECT id FROM usuarios WHERE rol = 'empleado' AND propietario_id = ?
    )
    GROUP BY empleado_id
");
$stmt_ajustes->execute([$primer_dia, $ultimo_dia, $dueno_id]);
$ajustes_data = [];
foreach ($stmt_ajustes->fetchAll(PDO::FETCH_ASSOC) as $a) {
    $ajustes_data[$a['empleado_id']] = $a['cantidad_ajustes'];
}

// Nombres de meses
$meses = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
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
    <title>Reporte Mensual - <?php echo htmlspecialchars($dueno['username']); ?></title>
    <link rel="stylesheet" href="empleado.css">
    <link rel="stylesheet" href="reporte_mensual.css">
</head>
<body>
    <div class="container">
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
                    <span class="welcome-text">Reporte de: <?php echo htmlspecialchars($dueno['username']); ?></span>
                    <a href="hacienda.php" class="btn-nav" style="margin-top: 8px; text-decoration: none;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 12H5"></path>
                            <polyline points="12 19 5 12 12 5"></polyline>
                        </svg>
                        Volver a Hacienda
                    </a>
                </div>
            </div>
        </header>

        <main class="main-content">
            <!-- Selector de Mes -->
            <div class="card">
                <div class="card-body">
                    <div class="month-selector">
                        <a href="?dueno_id=<?php echo $dueno_id; ?>&mes=<?php echo $mes_anterior; ?>&año=<?php echo $año_anterior; ?>" class="btn-nav-month">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="15 18 9 12 15 6"></polyline>
                            </svg>
                        </a>
                        <div class="month-display">
                            <h2><?php echo $meses[$mes]; ?> <?php echo $año; ?></h2>
                        </div>
                        <a href="?dueno_id=<?php echo $dueno_id; ?>&mes=<?php echo $mes_siguiente; ?>&año=<?php echo $año_siguiente; ?>" class="btn-nav-month">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </a>
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
                                    <th>Ajustes Aprobados</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($empleados)): ?>
                                    <tr>
                                        <td colspan="5" class="empty-state">
                                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                            </svg>
                                            <p>No hay empleados registrados</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($empleados as $emp): 
                                        $marcaciones = $marcaciones_data[$emp['id']] ?? ['dias_trabajados' => 0];
                                        $horas_totales = $horas_data[$emp['id']] ?? '00:00:00';
                                        $num_ajustes = $ajustes_data[$emp['id']] ?? 0;
                                        $dias_registrados = $marcaciones['dias_trabajados'];
                                    ?>
                                    <tr>
                                        <td data-label="Empleado">
                                            <span class="empleado-name"><?php echo htmlspecialchars($emp['username']); ?></span>
                                        </td>
                                        <td data-label="Días Trabajados">
                                            <span class="badge badge-blue"><?php echo $dias_registrados; ?></span>
                                        </td>
                                        <td data-label="Horas Totales">
                                            <span class="badge badge-green"><?php echo substr($horas_totales, 0, 5); ?></span>
                                        </td>
                                        <td data-label="Ajustes Aprobados">
                                            <?php if ($num_ajustes > 0): ?>
                                                <span class="badge badge-purple" title="<?php echo $num_ajustes; ?> hora(s) ajustada(s)"><?php echo $num_ajustes; ?></span>
                                            <?php else: ?>
                                                <span style="color: #a0aec0;">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="Acción">
                                            <a href="historial_empleado.php?id=<?php echo $emp['id']; ?>" 
                                               style="padding:6px 12px; font-size:13px; background:#667eea; color:white; text-decoration:none; border-radius:6px; display:inline-flex; align-items:center; gap:6px;">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                    <circle cx="12" cy="12" r="3"></circle>
                                                </svg>
                                                Ver Historial
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
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
