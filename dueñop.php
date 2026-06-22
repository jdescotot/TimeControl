<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once 'config.php';

$dueno_id = require_dueno_o_gerente($pdo);
$is_gerente_view = defined('TC_GERENTE_VIEW');

if (es_gerente() && !$is_gerente_view) {
    header('Location: gerente.php');
    exit;
}

$mostrar_panel_gerente = es_gerente();

$hoy = date('Y-m-d');
$mes_actual = (int)date('n');
$año_actual = (int)date('Y');
$pdf_mes_query = http_build_query([
    'mes' => $mes_actual,
    'año' => $año_actual,
]);

// Obtener número de solicitudes pendientes solo de empleados del dueño actual
try {
    $stmt_pendientes = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM solicitudes_cambio sc
        INNER JOIN usuarios u ON sc.empleado_id = u.id
        WHERE sc.estado IN ('pendiente', 'rechazado_empleado') AND u.propietario_id = ?
    ");
    $stmt_pendientes->execute([$dueno_id]);
    $resultado = $stmt_pendientes->fetch(PDO::FETCH_ASSOC);
    $num_solicitudes = (int) ($resultado['total'] ?? 0);
} catch (Exception $e) {
    $num_solicitudes = 0;
    error_log("Error al obtener solicitudes: " . $e->getMessage());
}

// Obtener todos los empleados (excluyendo al dueño) - con mejor manejo de charset
$stmt_empleados = $pdo->prepare(" 
    SELECT id, username, nombre, es_gerente
    FROM usuarios 
    WHERE rol = 'empleado' 
    AND propietario_id = ? 
    ORDER BY nombre IS NULL OR nombre = '', nombre, username
");
$stmt_empleados->execute([$dueno_id]);
$empleados = $stmt_empleados->fetchAll(PDO::FETCH_ASSOC);

// DEBUG: Descomentar para verificar cuÃ¡ntos empleados se obtienen
// echo "<!-- Total empleados encontrados: " . count($empleados) . " -->";

// Preparar estadÃ­sticas del dÃ­a
$total_empleados = count($empleados);
$entraron_hoy = 0;
$en_jornada = 0;

// Obtener dÃ­as de descanso para hoy
$stmt_descansos = $pdo->prepare("SELECT empleado_id FROM horarios_semanales WHERE fecha_descanso = ?");
$stmt_descansos->execute([$hoy]);
$empleados_con_descanso = [];
foreach ($stmt_descansos->fetchAll(PDO::FETCH_ASSOC) as $d) {
    $empleados_con_descanso[] = $d['empleado_id'];
}

// Obtener ausencias marcadas hoy (solo empleados del dueño)
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

// Obtener todas las marcaciones de hoy para todos los empleados en UNA sola query
$empleado_ids = array_column($empleados, 'id');
$marcaciones_por_emp = [];

if (!empty($empleado_ids)) {
    // Crear placeholders dinÃ¡micos para la consulta IN
    $placeholders = implode(',', array_fill(0, count($empleado_ids), '?'));
    
    $stmt_marcaciones = $pdo->prepare("
        SELECT m.empleado_id, m.entrada, m.salida,
               sc.nueva_hora_entrada, sc.nueva_hora_salida,
               ROW_NUMBER() OVER (PARTITION BY m.empleado_id ORDER BY m.entrada DESC) as rn
        FROM marcaciones m
        LEFT JOIN solicitudes_cambio sc ON m.id = sc.marcacion_id AND sc.estado = 'aprobado'
        WHERE m.empleado_id IN ($placeholders) AND DATE(m.entrada) = ?
    ");
    
    // Ejecutar la query una sola vez con todos los IDs
    $stmt_marcaciones->execute([...$empleado_ids, $hoy]);
    
    // Guardar resultado en array keyed por empleado_id (solo el primer registro por empleado)
    foreach ($stmt_marcaciones->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if ($row['rn'] == 1 && !isset($marcaciones_por_emp[$row['empleado_id']])) {
            $marcaciones_por_emp[$row['empleado_id']] = $row;
        }
    }
}

// Para cada empleado, asignar datos obtenidos
if (!empty($empleados)) {
    foreach ($empleados as $key => $emp) {
        // Verificar si tiene dÃ­a de descanso
        $empleados[$key]['tiene_descanso'] = in_array($emp['id'], $empleados_con_descanso);
        $empleados[$key]['ausencia_hoy'] = $ausencias_hoy[$emp['id']] ?? null;
        
        // Usar datos obtenidos en la query Ãºnica
        $registro = $marcaciones_por_emp[$emp['id']] ?? null;
        
        $empleados[$key]['entrada'] = $registro['entrada'] ?? null;
        $empleados[$key]['salida'] = $registro['salida'] ?? null;
        $empleados[$key]['entrada_hora'] = !empty($registro['entrada']) ? date('H:i', strtotime($registro['entrada'])) : null;
        $empleados[$key]['salida_hora'] = !empty($registro['salida']) ? date('H:i', strtotime($registro['salida'])) : null;
        $empleados[$key]['hora_entrada_ajustada'] = $registro['nueva_hora_entrada'] ?? null;
        $empleados[$key]['hora_salida_ajustada'] = $registro['nueva_hora_salida'] ?? null;
        $empleados[$key]['tiene_ajuste'] = !empty($registro['nueva_hora_entrada']) || !empty($registro['nueva_hora_salida']);

        if ($empleados[$key]['entrada']) {
            $entraron_hoy++;
            if (!$empleados[$key]['salida']) {
                $en_jornada++;
            }
        }
    }
}

// Restar empleados con descanso del total de pendientes y evitar números negativos
$pendientes = max(0, $total_empleados - $entraron_hoy - count($empleados_con_descanso));

$tiene_registro_hoy = false;
$jornada_abierta = false;
$estado_solicitud_pendiente = null;
$tiene_solicitud_pendiente = false;
$bloqueo_salida_pendiente = false;
$registro_hoy = null;

if ($mostrar_panel_gerente) {
    $empleado_id_gerente = (int)$_SESSION['user_id'];

    $stmt_registro = $pdo->prepare("SELECT id, entrada, salida FROM marcaciones WHERE empleado_id = ? ORDER BY entrada DESC LIMIT 1");
    $stmt_registro->execute([$empleado_id_gerente]);
    $registro_hoy = $stmt_registro->fetch(PDO::FETCH_ASSOC);

    $tiene_registro_hoy = $registro_hoy && date('Y-m-d', strtotime($registro_hoy['entrada'])) === $hoy;
    $jornada_abierta = $registro_hoy && !empty($registro_hoy['entrada']) && empty($registro_hoy['salida']);
    $entrada_fecha = $registro_hoy && !empty($registro_hoy['entrada']) ? date('Y-m-d', strtotime($registro_hoy['entrada'])) : null;
    $entrada_es_hoy = $entrada_fecha === $hoy;
    $entrada_es_ayer = $entrada_fecha === date('Y-m-d', strtotime('-1 day'));
    $bloqueo_flag = isset($_GET['bloqueo']) && $_GET['bloqueo'] === 'salida_pendiente';

    if ($jornada_abierta && !empty($registro_hoy['id'])) {
        $stmt_pendiente = $pdo->prepare("SELECT estado FROM solicitudes_cambio WHERE marcacion_id = ? AND estado IN ('pendiente', 'pendiente_empleado', 'rechazado_empleado') ORDER BY id DESC LIMIT 1");
        $stmt_pendiente->execute([$registro_hoy['id']]);
        $solicitud_pendiente = $stmt_pendiente->fetch(PDO::FETCH_ASSOC);
        if ($solicitud_pendiente) {
            $tiene_solicitud_pendiente = true;
            $estado_solicitud_pendiente = $solicitud_pendiente['estado'];
        }
    }

    $bloqueo_salida_pendiente = $jornada_abierta && ((!$entrada_es_hoy && !$entrada_es_ayer) || $bloqueo_flag) && !$tiene_solicitud_pendiente;
}

$titulo_panel = $mostrar_panel_gerente ? 'Panel de Gerente - Control Horario' : 'Panel del Dueño - Control Horario';


if (!function_exists('duenop_e')) {
    function duenop_e($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('duenop_iniciales')) {
    function duenop_iniciales(string $nombre): string
    {
        $partes = preg_split('/\s+/', trim($nombre)) ?: [];
        $iniciales = '';

        foreach ($partes as $parte) {
            if ($parte === '') {
                continue;
            }
            $iniciales .= strtoupper(substr($parte, 0, 1));
            if (strlen($iniciales) >= 2) {
                break;
            }
        }

        if ($iniciales === '') {
            $limpio = preg_replace('/\s+/', '', $nombre) ?? '';
            $iniciales = strtoupper(substr($limpio, 0, 2));
        }

        return $iniciales !== '' ? $iniciales : 'US';
    }
}

if (!function_exists('duenop_fecha_chip')) {
    function duenop_fecha_chip(): string
    {
        $meses = ['ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC'];
        $mes = $meses[(int)date('n') - 1] ?? strtoupper(date('M'));
        return date('d') . ' ' . $mes . ' ' . date('Y');
    }
}

$nombre_header = trim((string)($_SESSION['username'] ?? 'Usuario'));
$iniciales_header = duenop_iniciales($nombre_header);
$panel_label = $mostrar_panel_gerente ? 'Panel de gerente' : 'Panel del dueño';
$porcentaje_entraron = $total_empleados > 0 ? (int)min(100, round(($entraron_hoy / $total_empleados) * 100)) : 0;
$porcentaje_jornada = $total_empleados > 0 ? (int)min(100, round(($en_jornada / $total_empleados) * 100)) : 0;

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#064b3d">
    <title><?php echo duenop_e($titulo_panel); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap-utilities.min.css">
    <link rel="stylesheet" href="empleado.css">
    <link rel="stylesheet" href="solicitudes_cambio.css">
    <link rel="stylesheet" href="dueño.css">
    <link rel="stylesheet" href="dueñop.css">
</head>

<body class="owner-dashboard">
    <main class="owner-shell">
        <header class="owner-topbar">
            <a class="owner-brand" href="dueñop.php" aria-label="Control Horario">
                <span class="owner-brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8.6"></circle><path d="M12 7.5v5l3.2 2"></path></svg>
                </span>
                <span class="owner-brand-copy">
                    <span class="owner-brand-title">Control Horario</span>
                    <span class="owner-brand-kicker"><?php echo duenop_e($panel_label); ?></span>
                </span>
            </a>

            <div class="owner-profile" aria-label="Perfil de <?php echo duenop_e($nombre_header); ?>">
                <span class="owner-profile-copy">
                    <small>Sesión activa</small>
                    <strong><?php echo duenop_e($nombre_header); ?></strong>
                </span>
                <span class="owner-avatar" aria-hidden="true"><?php echo duenop_e($iniciales_header); ?></span>
                <a class="owner-logout-chip" href="logout.php" aria-label="Cerrar sesión">
                    <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><path d="M16 17l5-5-5-5"></path><path d="M21 12H9"></path></svg>
                    <span>Salir</span>
                </a>
            </div>
        </header>

        <section class="owner-alerts" aria-label="Mensajes del sistema">
            <?php if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'empleado_creado'): ?>
                <article class="owner-status success">
                    <span class="owner-status-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><path d="M22 4 12 14.01 9 11.01"></path></svg>
                    </span>
                    <p>Empleado "<?php echo duenop_e($_GET['username'] ?? 'nuevo empleado'); ?>" creado exitosamente.</p>
                </article>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <article class="owner-status error">
                    <span class="owner-status-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><path d="M12 8v4"></path><path d="M12 16h.01"></path></svg>
                    </span>
                    <p><?php echo duenop_e($_GET['error']); ?></p>
                </article>
            <?php endif; ?>
        </section>

        <?php if ($mostrar_panel_gerente): ?>
            <section class="manager-clock-card">
                <?php include __DIR__ . '/partials/empleado_marcacion_card.php'; ?>
            </section>
        <?php endif; ?>

        <section class="owner-hero-grid" aria-label="Resumen general">
            <article class="owner-hero">
                <div>
                    <div class="owner-hero-top">
                        <div>
                            <p class="owner-eyebrow"><span class="owner-status-dot"></span> Supervisión en tiempo real</p>
                            <h1>Tu equipo,<br>todo bajo control.</h1>
                        </div>
                        <span class="owner-date-chip"><?php echo duenop_e(duenop_fecha_chip()); ?></span>
                    </div>

                    <p class="owner-hero-copy">
                        Consulta entradas, salidas, ausencias, permisos y reportes desde un panel más claro y coherente con la experiencia del empleado.
                    </p>

                    <div class="owner-hero-meta">
                        <span><svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg> <?php echo (int)$total_empleados; ?> empleados registrados</span>
                        <span><svg viewBox="0 0 24 24"><path d="M12 7v5l3 2"></path><circle cx="12" cy="12" r="9"></circle></svg> <?php echo (int)$en_jornada; ?> en jornada ahora</span>
                        <span><svg viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg> <?php echo (int)$num_solicitudes; ?> solicitudes pendientes</span>
                    </div>
                </div>

                <div class="owner-hero-actions" aria-label="Accesos rápidos">
                    <a href="horario_semanal.php" class="owner-btn owner-btn--primary">
                        <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"></rect><path d="M16 2v4"></path><path d="M8 2v4"></path><path d="M3 10h18"></path></svg>
                        Horario semanal
                    </a>
                    <a href="reporte_mensual.php" class="owner-btn owner-btn--ghost">
                        <svg viewBox="0 0 24 24"><path d="M3 3v18h18"></path><path d="M8 17V9"></path><path d="M13 17V5"></path><path d="M18 17v-3"></path></svg>
                        Reporte mensual
                    </a>
                    <a href="mapa_marcaciones.php" class="owner-btn owner-btn--ghost">
                        <svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                        Mapa
                    </a>
                </div>
            </article>

            <aside class="owner-metrics" aria-label="Indicadores rápidos">
                <article class="owner-metric">
                    <p class="owner-metric-label">Total empleados</p>
                    <p class="owner-metric-value"><?php echo (int)$total_empleados; ?></p>
                    <p class="owner-metric-note">Plantilla activa asignada a tu cuenta.</p>
                </article>

                <article class="owner-metric">
                    <p class="owner-metric-label">Ya entraron</p>
                    <p class="owner-metric-value"><?php echo (int)$entraron_hoy; ?></p>
                    <div class="owner-progress" aria-label="<?php echo (int)$porcentaje_entraron; ?>% con entrada registrada"><span style="width: <?php echo (int)$porcentaje_entraron; ?>%"></span></div>
                    <p class="owner-metric-note"><?php echo (int)$porcentaje_entraron; ?>% del equipo con entrada registrada.</p>
                </article>

                <article class="owner-metric">
                    <p class="owner-metric-label">En jornada</p>
                    <p class="owner-metric-value"><?php echo (int)$en_jornada; ?></p>
                    <div class="owner-progress" aria-label="<?php echo (int)$porcentaje_jornada; ?>% en jornada"><span style="width: <?php echo (int)$porcentaje_jornada; ?>%"></span></div>
                    <p class="owner-metric-note">Personas trabajando en este momento.</p>
                </article>

                <article class="owner-metric owner-metric--attention">
                    <p class="owner-metric-label">Pendientes</p>
                    <p class="owner-metric-value"><?php echo (int)$pendientes; ?></p>
                    <p class="owner-metric-note">Sin entrada registrada hoy, excluyendo descansos.</p>
                </article>
            </aside>
        </section>

        <?php if ($num_solicitudes > 0): ?>
            <section class="owner-notice owner-notice--warning" aria-label="Solicitudes pendientes">
                <span class="owner-notice-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                    <span><?php echo (int)$num_solicitudes; ?></span>
                </span>
                <div>
                    <strong>Solicitudes pendientes</strong>
                    <p>Tienes <?php echo (int)$num_solicitudes; ?> <?php echo $num_solicitudes === 1 ? 'solicitud' : 'solicitudes'; ?> de cambio de horario pendiente<?php echo $num_solicitudes === 1 ? '' : 's'; ?> de revisión.</p>
                </div>
                <a href="gestionar_solicitudes.php" class="owner-btn owner-btn--warning">Gestionar</a>
            </section>
        <?php endif; ?>

        <section class="owner-panel" aria-labelledby="employees-title">
            <div class="owner-section-head">
                <div>
                    <p class="owner-section-kicker">Actividad de hoy</p>
                    <h2 id="employees-title">Empleados</h2>
                    <p>Estado actual, horas trabajadas y acciones rápidas por empleado.</p>
                </div>

                <div class="owner-panel-actions">
                    <a href="export_reporte_mensual_pdf.php?<?php echo duenop_e($pdf_mes_query); ?>" class="owner-btn owner-btn--danger">
                        <svg viewBox="0 0 24 24"><path d="M7 2h8l5 5v15a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2z"></path><path d="M14 2v6h6"></path></svg>
                        PDF del mes
                    </a>
                    <?php if (es_dueno()): ?>
                        <button type="button" class="owner-btn owner-btn--primary" onclick="abrirModalEmpleado()">
                            <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><path d="M20 8v6"></path><path d="M23 11h-6"></path></svg>
                            Agregar empleado
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="owner-table-wrap">
                <table class="owner-table">
                    <thead>
                        <tr>
                            <th>Empleado</th>
                            <th>Estado hoy</th>
                            <th>Horas</th>
                            <th>Permiso</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($empleados)): ?>
                            <tr>
                                <td colspan="5">
                                    <div class="owner-empty">
                                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><path d="M12 8v4"></path><path d="M12 16h.01"></path></svg>
                                        <p>No hay empleados registrados.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($empleados as $emp): ?>
                                <?php
                                    $nombre_mostrar_emp = !empty($emp['nombre']) ? $emp['nombre'] : $emp['username'];
                                    $iniciales_emp = duenop_iniciales((string)$nombre_mostrar_emp);
                                ?>
                                <tr>
                                    <td data-label="Empleado">
                                        <div class="owner-employee-cell">
                                            <span class="owner-employee-avatar" aria-hidden="true"><?php echo duenop_e($iniciales_emp); ?></span>
                                            <span class="owner-employee-copy">
                                                <a href="historial_empleado.php?id=<?php echo (int)$emp['id']; ?>" class="owner-employee-link">
                                                    <?php echo duenop_e($nombre_mostrar_emp); ?>
                                                </a>
                                                <?php if (!empty($emp['es_gerente'])): ?>
                                                    <span class="owner-mini-badge">Gerente</span>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td data-label="Estado hoy">
                                        <?php if ($emp['tiene_descanso']): ?>
                                            <span class="owner-status-pill success">
                                                <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"></rect><path d="M16 2v4"></path><path d="M8 2v4"></path><path d="M3 10h18"></path></svg>
                                                Día libre
                                            </span>
                                        <?php elseif ($emp['ausencia_hoy']): ?>
                                            <?php
                                                $tipo = $emp['ausencia_hoy'];
                                                $map = [
                                                    'vacaciones_ley' => ['clase' => 'warning', 'texto' => 'Vacaciones Ley'],
                                                    'enfermedad' => ['clase' => 'danger', 'texto' => 'Enfermedad'],
                                                    'emergencia_familiar' => ['clase' => 'accent', 'texto' => 'Falta Justificada'],
                                                    'fuerza_mayor' => ['clase' => 'accent', 'texto' => 'Fuerza Mayor'],
                                                ];
                                                $info = $map[$tipo] ?? ['clase' => 'danger', 'texto' => 'Ausencia'];
                                            ?>
                                            <span class="owner-status-pill <?php echo duenop_e($info['clase']); ?>">
                                                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><path d="M12 8v4"></path><path d="M12 16h.01"></path></svg>
                                                <?php echo duenop_e($info['texto']); ?>
                                            </span>
                                        <?php elseif (!$emp['entrada']): ?>
                                            <span class="owner-status-pill danger">Sin marcar</span>
                                        <?php elseif ($emp['entrada'] && !$emp['salida']): ?>
                                            <?php if ($emp['tiene_ajuste']): ?>
                                                <span class="owner-status-pill warning">En jornada</span>
                                                <small class="owner-status-detail">Desde <span class="time-original"><?php echo duenop_e($emp['entrada_hora'] ?? '—'); ?></span> <strong class="time-adjusted"><?php echo duenop_e(substr((string)$emp['hora_entrada_ajustada'], 0, 5)); ?></strong> <span class="owner-mini-badge">Ajustado</span></small>
                                            <?php else: ?>
                                                <span class="owner-status-pill warning">En jornada</span>
                                                <small class="owner-status-detail">Desde <?php echo duenop_e($emp['entrada_hora'] ?? '—'); ?></small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="owner-status-pill success">Completado</span>
                                            <?php if ($emp['tiene_ajuste']): ?>
                                                <small class="owner-status-detail">
                                                    Entrada <span class="time-original"><?php echo duenop_e($emp['entrada_hora'] ?? '—'); ?></span>
                                                    <strong class="time-adjusted"><?php echo duenop_e(substr((string)$emp['hora_entrada_ajustada'], 0, 5)); ?></strong> ·
                                                    Salida <span class="time-original"><?php echo duenop_e($emp['salida_hora'] ?? '—'); ?></span>
                                                    <strong class="time-adjusted"><?php echo duenop_e(substr((string)$emp['hora_salida_ajustada'], 0, 5)); ?></strong>
                                                    <span class="owner-mini-badge">Ajustado</span>
                                                </small>
                                            <?php else: ?>
                                                <small class="owner-status-detail">Entrada <?php echo duenop_e($emp['entrada_hora'] ?? '—'); ?> · Salida <?php echo duenop_e($emp['salida_hora'] ?? '—'); ?></small>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Horas" class="owner-hours-cell">
                                        <?php
                                        $fecha_base = $emp['entrada'] ? date('Y-m-d', strtotime($emp['entrada'])) : $hoy;
                                        $entrada_usar_dt = $emp['hora_entrada_ajustada'] ? new DateTime($fecha_base . ' ' . $emp['hora_entrada_ajustada']) : ($emp['entrada'] ? new DateTime($emp['entrada']) : null);
                                        if ($emp['hora_salida_ajustada']) {
                                            $salida_usar_dt = new DateTime($fecha_base . ' ' . $emp['hora_salida_ajustada']);
                                        } else {
                                            $salida_usar_dt = $emp['salida'] ? new DateTime($emp['salida']) : null;
                                        }

                                        if ($entrada_usar_dt && $salida_usar_dt) {
                                            try {
                                                if ($salida_usar_dt < $entrada_usar_dt) {
                                                    $salida_usar_dt->modify('+1 day');
                                                }
                                                $intervalo = $entrada_usar_dt->diff($salida_usar_dt);
                                                echo duenop_e($intervalo->format('%h:%i'));
                                                if ($emp['tiene_ajuste']) {
                                                    echo ' <span class="hours-adjusted-mark">*</span>';
                                                }
                                            } catch (Exception $e) {
                                                echo '&mdash;';
                                            }
                                        } else {
                                            echo '&mdash;';
                                        }
                                        ?>
                                    </td>
                                    <td data-label="Permiso">
                                        <?php if (!empty($emp['es_gerente'])): ?>
                                            <span class="owner-role-badge success">Con permisos</span>
                                        <?php else: ?>
                                            <span class="owner-role-badge neutral">Empleado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Acción" class="owner-action-cell">
                                        <div class="owner-table-actions">
                                            <a href="historial_empleado.php?id=<?php echo (int)$emp['id']; ?>" class="owner-action-btn">Historial</a>
                                            <a href="historial_empleado_pdf.php?id=<?php echo (int)$emp['id']; ?>" class="owner-action-btn danger">PDF</a>
                                            <?php if (es_dueno()): ?>
                                                <form action="actualizar_gerente.php" method="POST" class="owner-manager-form">
                                                    <input type="hidden" name="empleado_id" value="<?php echo (int)$emp['id']; ?>">
                                                    <input type="hidden" name="es_gerente" value="<?php echo empty($emp['es_gerente']) ? 1 : 0; ?>">
                                                    <button type="submit" class="owner-action-btn dark">
                                                        <?php echo empty($emp['es_gerente']) ? 'Hacer gerente' : 'Quitar gerente'; ?>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <?php if (es_dueno()): ?>
        <div id="modalEmpleado" class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-empleado-title">
            <div class="modal-content">
                <div class="modal-header">
                    <p class="owner-section-kicker">Nuevo acceso</p>
                    <h3 id="modal-empleado-title">Crear nuevo empleado</h3>
                </div>
                <form action="crear_empleado.php" method="POST">
                    <div class="form-group">
                        <label for="nombre">Nombre del empleado</label>
                        <input type="text" name="nombre" id="nombre" required minlength="2" maxlength="100" placeholder="Ej: Juan Pérez García" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="username">DNI / NIE / NIF / Pasaporte</label>
                        <input type="text" name="username" id="username" required minlength="3" maxlength="50" placeholder="Ej: X1234567L" autocomplete="off">
                        <small class="form-help">Se convertirá automáticamente a minúsculas sin espacios.</small>
                    </div>
                    <div class="form-group">
                        <label for="fecha_inicio">Fecha de inicio</label>
                        <input type="date" name="fecha_inicio" id="fecha_inicio" required value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>">
                        <small class="form-help">Fecha en que el empleado comenzó a trabajar.</small>
                    </div>
                    <div class="form-group">
                        <label for="password">Contraseña temporal</label>
                        <div class="password-wrapper">
                            <input type="password" name="password" id="password" required minlength="6" placeholder="Mínimo 6 caracteres sin espacios" autocomplete="new-password" class="input-with-eye">
                            <button type="button" onclick="togglePassword('password', this)" class="btn-eye" aria-label="Mostrar contraseña">
                                <svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            </button>
                        </div>
                        <small class="form-help">No se permiten espacios. El empleado deberá cambiarla en su primer inicio de sesión.</small>
                    </div>
                    <div class="form-group">
                        <label for="confirmar_password">Confirmar contraseña</label>
                        <div class="password-wrapper">
                            <input type="password" name="confirmar_password" id="confirmar_password" required minlength="6" placeholder="Repite la contraseña" autocomplete="new-password" class="input-with-eye">
                            <button type="button" onclick="togglePassword('confirmar_password', this)" class="btn-eye" aria-label="Mostrar contraseña">
                                <svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            </button>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="owner-btn owner-btn--primary">
                            <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><path d="M20 8v6"></path><path d="M23 11h-6"></path></svg>
                            Crear empleado
                        </button>
                        <button type="button" class="owner-btn owner-btn--muted" onclick="cerrarModalEmpleado()">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </main>
    <script>
        function togglePassword(inputId, button) {
            const input = document.getElementById(inputId);
            const svg = button.querySelector('svg');
            
            if (input.type === 'password') {
                input.type = 'text';
                svg.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
            } else {
                input.type = 'password';
                svg.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
            }
        }

        // Sugerencia llamativa para formatear el usuario sin espacios ni mayÃºsculas
        function sanitizeUsername(value) {
            return value.toLowerCase().replace(/[^a-z0-9]/g, '');
        }

        function showUsernamePopup(original, sanitized) {
            const overlay = document.createElement('div');
            overlay.className = 'username-popup-overlay';

            const modal = document.createElement('div');
            modal.className = 'username-popup-modal';
            modal.innerHTML = `
                <div class="username-popup-head">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/>
                    </svg>
                    <div>
                        <div class="username-popup-title">No se permiten espacios ni mayÃºsculas</div>
                        <div class="username-popup-subtitle">Ingresaste: <strong>${original}</strong></div>
                    </div>
                </div>
                <div class="username-popup-preview">
                    Podemos usar esta versiÃ³n: <strong>${sanitized}</strong>
                </div>
                <div class="username-popup-actions">
                    <button id="useSanitized" class="username-popup-btn username-popup-btn--primary">Usar ${sanitized}</button>
                    <button id="keepOriginal" class="username-popup-btn username-popup-btn--ghost">Corregir manualmente</button>
                </div>
            `;

            overlay.appendChild(modal);
            document.body.appendChild(overlay);

            const usernameInput = document.getElementById('username');
            const pass1 = document.getElementById('password');
            const pass2 = document.getElementById('confirmar_password');

            overlay.querySelector('#useSanitized').onclick = () => {
                usernameInput.value = sanitized;
                if (pass1) pass1.value = '';
                if (pass2) pass2.value = '';
                document.body.removeChild(overlay);
                pass1?.focus();
            };

            overlay.querySelector('#keepOriginal').onclick = () => {
                document.body.removeChild(overlay);
                usernameInput.focus();
            };
        }

        function sugerirUsername() {
            const input = document.getElementById('username');
            if (!input) return;

            const raw = input.value.trim();
            if (!raw) return;

            const sanitized = sanitizeUsername(raw);
            if (!sanitized || sanitized === raw) return;

            showUsernamePopup(raw, sanitized);
        }

        function abrirModalEmpleado() {
            document.getElementById('modalEmpleado').style.display = 'grid';
            // Limpiar el formulario
            document.getElementById('nombre').value = '';
            document.getElementById('username').value = '';
            document.getElementById('password').value = '';
            document.getElementById('confirmar_password').value = '';
            document.getElementById('fecha_inicio').value = '<?php echo date('Y-m-d'); ?>';
        }

        function cerrarModalEmpleado() {
            document.getElementById('modalEmpleado').style.display = 'none';
        }

        // Cerrar modal al hacer clic fuera
        window.onclick = function (event) {
            let modal = document.getElementById('modalEmpleado');
            if (event.target == modal) {
                cerrarModalEmpleado();
            }
        }

        // Validación de contraseÃ±as coincidentes
        document.getElementById('confirmar_password')?.addEventListener('input', function () {
            const password = document.getElementById('password').value;
            const confirmar = this.value;

            if (confirmar && password !== confirmar) {
                this.setCustomValidity('Las contraseÃ±as no coinciden');
            } else {
                this.setCustomValidity('');
            }
        });

        // Disparar sugerencia cuando el usuario salga del campo
        document.getElementById('username')?.addEventListener('blur', sugerirUsername);

        // GPS para formularios de marcación (solo aplica cuando el bloque existe, ej. gerente)
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('form[action="marcar.php"]').forEach(function (form) {
                var latInput = document.createElement('input');
                latInput.type = 'hidden';
                latInput.name = 'lat';

                var lngInput = document.createElement('input');
                lngInput.type = 'hidden';
                lngInput.name = 'lng';

                form.appendChild(latInput);
                form.appendChild(lngInput);

                form.addEventListener('submit', function (e) {
                    if (!navigator.geolocation) {
                        return;
                    }

                    e.preventDefault();

                    var btn = form.querySelector('button[type="submit"]');
                    var htmlOriginal = btn ? btn.innerHTML : null;

                    if (btn) {
                        btn.disabled = true;
                        btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; animation: spin 1s linear infinite;"><circle cx="12" cy="12" r="10"/></svg> Obteniendo ubicación...';
                    }

                    var timeoutHandle = setTimeout(function () {
                        if (btn && htmlOriginal) {
                            btn.disabled = false;
                            btn.innerHTML = htmlOriginal;
                        }
                        form.submit();
                    }, 8000);

                    navigator.geolocation.getCurrentPosition(
                        function (pos) {
                            clearTimeout(timeoutHandle);
                            latInput.value = pos.coords.latitude.toFixed(8);
                            lngInput.value = pos.coords.longitude.toFixed(8);

                            if (btn) {
                                btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg> Ubicación confirmada';
                                setTimeout(function () {
                                    form.submit();
                                }, 300);
                            } else {
                                form.submit();
                            }
                        },
                        function () {
                            clearTimeout(timeoutHandle);
                            if (btn && htmlOriginal) {
                                btn.disabled = false;
                                btn.innerHTML = htmlOriginal;
                            }
                            form.submit();
                        },
                        {
                            timeout: 8000,
                            maximumAge: 30000,
                            enableHighAccuracy: true
                        }
                    );
                });
            });
        });
    </script>
</body>

</html>
