<?php
session_start();
require_once 'config.php';

function formatear_fecha($valor, $formato = 'd/m/Y H:i')
{
    if (empty($valor)) {
        return 'Sin fecha';
    }

    try {
        return (new DateTime($valor))->format($formato);
    } catch (Exception $e) {
        return 'Fecha invalida';
    }
}

if (isset($_GET['logout_hacienda'])) {
    unset($_SESSION['hacienda_master_access']);
    header('Location: hacienda.php');
    exit;
}

$master_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['master_password'])) {
    $master_password = trim((string)($_POST['master_password'] ?? ''));

    if (defined('PANEL_MAESTRO_PASSWORD') && hash_equals(PANEL_MAESTRO_PASSWORD, $master_password)) {
        $_SESSION['hacienda_master_access'] = true;
        header('Location: hacienda.php');
        exit;
    }

    $master_error = 'Clave incorrecta. Intenta nuevamente.';
}

$has_hacienda_role = isset($_SESSION['rol']) && $_SESSION['rol'] === 'hacienda';
$has_master_access = !empty($_SESSION['hacienda_master_access']) || $has_hacienda_role;

if (!$has_master_access):
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Panel Maestro</title>
    <link rel="stylesheet" href="empleado.css">
    <link rel="stylesheet" href="hacienda.css">
</head>
<body>
    <div class="container">
        <main class="main-content master-login-wrapper">
            <div class="card master-login-card">
                <div class="card-body">
                    <h1>Panel Maestro</h1>
                    <p>Ingresa la clave compartida para ver la actividad de todos los dueños y empleados.</p>

                    <?php if (!empty($master_error)): ?>
                        <div class="master-login-error"><?php echo htmlspecialchars($master_error); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="" class="master-login-form">
                        <label for="master_password">Clave maestra</label>
                        <input type="password" id="master_password" name="master_password" required autocomplete="current-password">
                        <button type="submit" class="btn-filter">Entrar al panel</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
<?php
exit;
endif;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_employee_password') {
    $empleado_id = isset($_POST['empleado_id']) ? (int)$_POST['empleado_id'] : 0;
    $dueno_id = isset($_POST['dueno_id']) ? (int)$_POST['dueno_id'] : 0;

    $redirect_busqueda = trim((string)($_POST['redirect_busqueda'] ?? ''));
    $redirect_mes = isset($_POST['redirect_mes']) ? (int)$_POST['redirect_mes'] : (int)date('n');
    $redirect_anio = isset($_POST['redirect_anio']) ? (int)$_POST['redirect_anio'] : (int)date('Y');

    if ($redirect_mes < 1 || $redirect_mes > 12) {
        $redirect_mes = (int)date('n');
    }
    if ($redirect_anio < 2020 || $redirect_anio > 2035) {
        $redirect_anio = (int)date('Y');
    }

    if ($empleado_id <= 0 || $dueno_id <= 0) {
        $_SESSION['hacienda_reset_error'] = 'No se pudo resetear: datos de empleado invalidos.';
    } else {
        try {
            $stmt_empleado = $pdo->prepare("SELECT id, username FROM usuarios WHERE id = ? AND rol = 'empleado' AND propietario_id = ? LIMIT 1");
            $stmt_empleado->execute([$empleado_id, $dueno_id]);
            $empleado = $stmt_empleado->fetch(PDO::FETCH_ASSOC);

            if (!$empleado) {
                $_SESSION['hacienda_reset_error'] = 'No se pudo resetear: empleado no encontrado para ese dueño.';
            } else {
                $password_temporal = '123456';
                $password_hash = password_hash($password_temporal, PASSWORD_DEFAULT);

                $stmt_update = $pdo->prepare('UPDATE usuarios SET password = ?, requiere_cambio_password = 1 WHERE id = ?');
                $stmt_update->execute([$password_hash, $empleado_id]);

                $_SESSION['hacienda_reset_success'] = 'Contraseña reseteada para el usuario ' . $empleado['username'] . '.';
                $_SESSION['hacienda_reset_temp_password'] = $password_temporal;
            }
        } catch (Exception $e) {
            $_SESSION['hacienda_reset_error'] = 'Error al resetear contraseña. Intenta nuevamente.';
            error_log('Error reset hacienda empleado: ' . $e->getMessage());
        }
    }

    $redirect_params = [
        'busqueda' => $redirect_busqueda,
        'mes' => $redirect_mes,
        'año' => $redirect_anio,
    ];
    header('Location: hacienda.php?' . http_build_query($redirect_params));
    exit;
}

$reset_success = $_SESSION['hacienda_reset_success'] ?? null;
$reset_error = $_SESSION['hacienda_reset_error'] ?? null;
$reset_temp_password = $_SESSION['hacienda_reset_temp_password'] ?? null;
unset($_SESSION['hacienda_reset_success'], $_SESSION['hacienda_reset_error'], $_SESSION['hacienda_reset_temp_password']);

$busqueda = trim((string)($_GET['busqueda'] ?? ''));
$mes_filtro = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date('n');
$anio_filtro = isset($_GET['año']) ? (int)$_GET['año'] : (int)date('Y');

if ($mes_filtro < 1 || $mes_filtro > 12) {
    $mes_filtro = (int)date('n');
}

if ($anio_filtro < 2020 || $anio_filtro > 2035) {
    $anio_filtro = (int)date('Y');
}

$meses = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];

$primer_dia = sprintf('%04d-%02d-01', $anio_filtro, $mes_filtro);
$ultimo_dia = date('Y-m-d', strtotime($primer_dia . ' +1 month -1 day'));
$export_query = http_build_query([
    'busqueda' => $busqueda,
    'mes' => $mes_filtro,
    'año' => $anio_filtro,
]);

$query_duenos = "
    SELECT
        u.id,
        u.username,
        u.created_at,
        COUNT(DISTINCT emp.id) AS total_empleados,
        COALESCE(SUM(CASE WHEN emp.id IS NOT NULL AND um.ultima_marcacion IS NOT NULL THEN 1 ELSE 0 END), 0) AS empleados_con_marcacion,
        COALESCE(SUM(CASE WHEN emp.id IS NOT NULL AND um.ultima_marcacion IS NULL THEN 1 ELSE 0 END), 0) AS empleados_sin_marcacion,
        MAX(um.ultima_marcacion) AS ultima_actividad
    FROM usuarios u
    LEFT JOIN usuarios emp ON emp.propietario_id = u.id AND emp.rol = 'empleado'
    LEFT JOIN (
        SELECT empleado_id, MAX(entrada) AS ultima_marcacion
        FROM marcaciones
        GROUP BY empleado_id
    ) um ON um.empleado_id = emp.id
    WHERE u.rol = 'dueño'
";

if ($busqueda !== '') {
    $query_duenos .= " AND u.username LIKE :busqueda";
}

$query_duenos .= " GROUP BY u.id, u.username, u.created_at ORDER BY u.username";

$stmt_duenos = $pdo->prepare($query_duenos);
if ($busqueda !== '') {
    $stmt_duenos->bindValue(':busqueda', '%' . $busqueda . '%');
}
$stmt_duenos->execute();
$duenos = $stmt_duenos->fetchAll(PDO::FETCH_ASSOC);

$hoy = new DateTime('today');

foreach ($duenos as &$dueno) {
    $stmt_empleados = $pdo->prepare("
        SELECT
            u.id,
            u.username,
            u.created_at,
            (
                SELECT MAX(m2.entrada)
                FROM marcaciones m2
                WHERE m2.empleado_id = u.id
            ) AS ultima_marcacion,
            (
                SELECT COUNT(DISTINCT DATE(m3.entrada))
                FROM marcaciones m3
                WHERE m3.empleado_id = u.id
                  AND DATE(m3.entrada) BETWEEN ? AND ?
            ) AS dias_trabajados,
            (
                SELECT COUNT(DISTINCT sc.id)
                FROM solicitudes_cambio sc
                INNER JOIN marcaciones mm ON mm.id = sc.marcacion_id
                WHERE sc.empleado_id = u.id
                  AND sc.estado = 'aprobado'
                  AND DATE(mm.entrada) BETWEEN ? AND ?
            ) AS ajustes_aprobados
        FROM usuarios u
        WHERE u.rol = 'empleado' AND u.propietario_id = ?
        ORDER BY u.username
    ");

    $stmt_empleados->execute([$primer_dia, $ultimo_dia, $primer_dia, $ultimo_dia, $dueno['id']]);
    $empleados = $stmt_empleados->fetchAll(PDO::FETCH_ASSOC);

    foreach ($empleados as &$emp) {
        $ultima_marcacion = $emp['ultima_marcacion'] ?? null;

        if (empty($ultima_marcacion)) {
            $emp['estado_uso'] = 'sin_marcacion';
            $emp['estado_label'] = 'Nunca marco';
            $emp['ultima_marcacion_fmt'] = 'Nunca';
        } else {
            $ultima_fecha = new DateTime($ultima_marcacion);
            $dias_sin_marcar = (int)$hoy->diff(new DateTime($ultima_fecha->format('Y-m-d')))->days;

            $emp['ultima_marcacion_fmt'] = $ultima_fecha->format('d/m/Y H:i');

            if ($dias_sin_marcar <= 30) {
                $emp['estado_uso'] = 'activo';
                $emp['estado_label'] = 'Activo';
            } else {
                $emp['estado_uso'] = 'inactivo';
                $emp['estado_label'] = 'Inactivo';
            }
        }

        $emp['created_at_fmt'] = formatear_fecha($emp['created_at'], 'd/m/Y');
    }
    unset($emp);

    $dueno['empleados'] = $empleados;
    $dueno['ultima_actividad_fmt'] = empty($dueno['ultima_actividad'])
        ? 'Sin actividad'
        : formatear_fecha($dueno['ultima_actividad']);
}
unset($dueno);

$total_duenos = count($duenos);
$total_empleados = 0;
$total_empleados_activos = 0;
$total_empleados_sin_marcacion = 0;
$duenos_en_uso = 0;

foreach ($duenos as $dueno) {
    $total_empleados += (int)$dueno['total_empleados'];
    $total_empleados_activos += (int)$dueno['empleados_con_marcacion'];
    $total_empleados_sin_marcacion += (int)$dueno['empleados_sin_marcacion'];

    if ((int)$dueno['empleados_con_marcacion'] > 0) {
        $duenos_en_uso++;
    }
}

$duenos_sin_uso = $total_duenos - $duenos_en_uso;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Maestro - Control Horario</title>
    <link rel="stylesheet" href="empleado.css">
    <link rel="stylesheet" href="hacienda.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="header-content">
                <div class="logo">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                    <span>Control Horario - Panel Maestro</span>
                </div>
                <div class="user-info">
                    <span class="welcome-text">Vista global de uso</span>
                    <span class="username"><?php echo $has_hacienda_role ? htmlspecialchars((string)($_SESSION['username'] ?? 'Hacienda')) : 'Acceso Maestro'; ?></span>
                    <a href="?logout_hacienda=1" class="btn-master-logout">Salir</a>
                </div>
            </div>
        </header>

        <main class="main-content">
            <div class="hacienda-header">
                <h1>Panel Maestro de Actividad</h1>
                <p>Monitorea dueños con empleados creados y empleados con o sin marcaciones.</p>
            </div>

            <?php if (!empty($reset_success)): ?>
                <div class="card" style="border-left: 4px solid #16a34a; margin-bottom: 16px;">
                    <div class="card-body" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between;">
                        <div>
                            <strong style="color: #166534;">✓ <?php echo htmlspecialchars((string)$reset_success); ?></strong>
                            <div style="margin-top: 6px; color: #334155;">
                                Contraseña temporal: <strong id="temp-password-text"><?php echo htmlspecialchars((string)$reset_temp_password); ?></strong>
                                <small style="display: block; margin-top: 4px; color: #64748b;">El empleado debera cambiarla en su siguiente inicio de sesion.</small>
                            </div>
                        </div>
                        <button type="button" class="btn-action" id="copy-temp-password-btn" onclick="copyTempPassword()">
                            Copiar contraseña
                        </button>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($reset_error)): ?>
                <div class="card" style="border-left: 4px solid #dc2626; margin-bottom: 16px;">
                    <div class="card-body">
                        <strong style="color: #991b1b;">✕ <?php echo htmlspecialchars((string)$reset_error); ?></strong>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card filters-card">
                <div class="card-body">
                    <form method="GET" action="" class="filters-form">
                        <div class="filter-group">
                            <label for="busqueda">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <path d="m21 21-4.35-4.35"></path>
                                </svg>
                                Buscar dueño
                            </label>
                            <input type="text" id="busqueda" name="busqueda"
                                   placeholder="Nombre del dueño..."
                                   value="<?php echo htmlspecialchars($busqueda); ?>">
                        </div>

                        <div class="filter-group">
                            <label for="mes">Mes</label>
                            <select id="mes" name="mes">
                                <?php foreach ($meses as $num => $nombre): ?>
                                    <option value="<?php echo $num; ?>" <?php echo $mes_filtro === $num ? 'selected' : ''; ?>>
                                        <?php echo $nombre; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="año">Año</label>
                            <input type="number" id="año" name="año"
                                   value="<?php echo $anio_filtro; ?>"
                                   min="2020" max="2035">
                        </div>

                        <button type="submit" class="btn-filter">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                            </svg>
                            Filtrar
                        </button>

                        <a href="export_reporte_hacienda.php?<?php echo htmlspecialchars($export_query, ENT_QUOTES, 'UTF-8'); ?>" class="btn-export">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="7 10 12 15 17 10"></polyline>
                                <line x1="12" y1="15" x2="12" y2="3"></line>
                            </svg>
                            Exportar Excel
                        </a>
                    </form>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #bee3f8;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="8.5" cy="7" r="4"></circle>
                            <path d="M20 8v6M23 11h-6"></path>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <span class="stat-label">Dueños totales</span>
                        <span class="stat-value"><?php echo $total_duenos; ?></span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: #c6f6d5;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <span class="stat-label">Dueños en uso</span>
                        <span class="stat-value"><?php echo $duenos_en_uso; ?></span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: #fef3c7;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M16 11h6"></path>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <span class="stat-label">Dueños sin uso</span>
                        <span class="stat-value"><?php echo $duenos_sin_uso; ?></span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: #dbeafe;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M21 8v6M18 11h6"></path>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <span class="stat-label">Empleados creados</span>
                        <span class="stat-value"><?php echo $total_empleados; ?></span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: #bbf7d0;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <span class="stat-label">Empleados con marcacion</span>
                        <span class="stat-value"><?php echo $total_empleados_activos; ?></span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: #fecaca;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <span class="stat-label">Nunca marcaron</span>
                        <span class="stat-value"><?php echo $total_empleados_sin_marcacion; ?></span>
                    </div>
                </div>
            </div>

            <div class="period-info">
                Periodo seleccionado: <?php echo $meses[$mes_filtro]; ?> <?php echo $anio_filtro; ?>
            </div>

            <?php if (empty($duenos)): ?>
                <div class="card">
                    <div class="card-body empty-state">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <p>No se encontraron dueños con ese filtro.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($duenos as $dueno): ?>
                    <?php
                        $dueno_en_uso = (int)$dueno['empleados_con_marcacion'] > 0;
                        $dueno_badge_clase = $dueno_en_uso ? 'badge-green' : 'badge-red';
                        $dueno_badge_label = $dueno_en_uso ? 'En uso' : 'Sin uso';
                    ?>
                    <div class="card dueno-card">
                        <div class="dueno-header" onclick="toggleDueno(<?php echo (int)$dueno['id']; ?>)">
                            <div class="dueno-info">
                                <h3>
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="12" cy="7" r="4"></circle>
                                    </svg>
                                    <?php echo htmlspecialchars((string)$dueno['username']); ?>
                                </h3>
                                <div class="dueno-stats">
                                    <span class="badge badge-blue"><?php echo (int)$dueno['total_empleados']; ?> empleados</span>
                                    <span class="badge badge-green"><?php echo (int)$dueno['empleados_con_marcacion']; ?> con marcacion</span>
                                    <span class="badge badge-red"><?php echo (int)$dueno['empleados_sin_marcacion']; ?> sin marcar</span>
                                    <span class="badge <?php echo $dueno_badge_clase; ?>"><?php echo $dueno_badge_label; ?></span>
                                    <span class="date-info">Ultima actividad: <?php echo htmlspecialchars((string)$dueno['ultima_actividad_fmt']); ?></span>
                                    <span class="date-info">Dueño registrado: <?php echo formatear_fecha($dueno['created_at'], 'd/m/Y'); ?></span>
                                </div>
                            </div>
                            <div class="dueno-actions">
                                <a href="hacienda_reporte.php?dueno_id=<?php echo (int)$dueno['id']; ?>&mes=<?php echo $mes_filtro; ?>&año=<?php echo $anio_filtro; ?>"
                                   class="btn-action" onclick="event.stopPropagation();">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                        <polyline points="14 2 14 8 20 8"></polyline>
                                        <line x1="16" y1="13" x2="8" y2="13"></line>
                                        <line x1="16" y1="17" x2="8" y2="17"></line>
                                        <polyline points="10 9 9 9 8 9"></polyline>
                                    </svg>
                                    Ver Reporte
                                </a>
                                <svg class="toggle-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="6 9 12 15 18 9"></polyline>
                                </svg>
                            </div>
                        </div>

                        <div class="dueno-content" id="dueno-<?php echo (int)$dueno['id']; ?>" style="display: none;">
                            <?php if (empty($dueno['empleados'])): ?>
                                <div class="empty-state-small">
                                    <p>Este dueño aun no tiene empleados registrados.</p>
                                </div>
                            <?php else: ?>
                                <div class="empleados-table">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Empleado</th>
                                                <th>Creado</th>
                                                <th>Ultima marcacion</th>
                                                <th>Estado de uso</th>
                                                <th>Dias en periodo</th>
                                                <th>Ajustes aprobados</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($dueno['empleados'] as $emp): ?>
                                                <?php
                                                    $estado_clase = 'badge-red';
                                                    if ($emp['estado_uso'] === 'activo') {
                                                        $estado_clase = 'badge-green';
                                                    } elseif ($emp['estado_uso'] === 'inactivo') {
                                                        $estado_clase = 'badge-orange';
                                                    }
                                                ?>
                                                <tr>
                                                    <td>
                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                                            <circle cx="12" cy="7" r="4"></circle>
                                                        </svg>
                                                        <?php echo htmlspecialchars((string)$emp['username']); ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars((string)$emp['created_at_fmt']); ?></td>
                                                    <td><?php echo htmlspecialchars((string)$emp['ultima_marcacion_fmt']); ?></td>
                                                    <td>
                                                        <span class="badge <?php echo $estado_clase; ?>"><?php echo htmlspecialchars((string)$emp['estado_label']); ?></span>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-blue"><?php echo (int)$emp['dias_trabajados']; ?> dias</span>
                                                    </td>
                                                    <td>
                                                        <?php if ((int)$emp['ajustes_aprobados'] > 0): ?>
                                                            <span class="badge badge-purple"><?php echo (int)$emp['ajustes_aprobados']; ?> ajustes</span>
                                                        <?php else: ?>
                                                            <span style="color: #a0aec0;">0</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <form method="POST" action="" onsubmit="return confirm('Se reseteara la contraseña de este empleado a 123456. ¿Continuar?');" style="display:inline;">
                                                            <input type="hidden" name="action" value="reset_employee_password">
                                                            <input type="hidden" name="empleado_id" value="<?php echo (int)$emp['id']; ?>">
                                                            <input type="hidden" name="dueno_id" value="<?php echo (int)$dueno['id']; ?>">
                                                            <input type="hidden" name="redirect_busqueda" value="<?php echo htmlspecialchars($busqueda, ENT_QUOTES, 'UTF-8'); ?>">
                                                            <input type="hidden" name="redirect_mes" value="<?php echo (int)$mes_filtro; ?>">
                                                            <input type="hidden" name="redirect_anio" value="<?php echo (int)$anio_filtro; ?>">
                                                            <button type="submit" class="btn-action">Resetear contraseña</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>

        <footer class="footer master-footer">
            <a href="?logout_hacienda=1" class="logout-link">Cerrar panel maestro</a>
            <?php if ($has_hacienda_role): ?>
                <a href="logout.php" class="logout-link">Cerrar sesion completa</a>
            <?php endif; ?>
        </footer>
    </div>

    <script>
        function toggleDueno(duenoId) {
            const content = document.getElementById(`dueno-${duenoId}`);
            const card = content.closest('.dueno-card');

            if (content.style.display === 'none') {
                content.style.display = 'block';
                card.classList.add('expanded');
            } else {
                content.style.display = 'none';
                card.classList.remove('expanded');
            }
        }

        function copyTempPassword() {
            const passwordElement = document.getElementById('temp-password-text');
            const copyButton = document.getElementById('copy-temp-password-btn');

            if (!passwordElement || !copyButton) {
                return;
            }

            const tempPassword = passwordElement.textContent.trim();
            navigator.clipboard.writeText(tempPassword).then(function () {
                copyButton.textContent = 'Copiada ✓';
                setTimeout(function () {
                    copyButton.textContent = 'Copiar contraseña';
                }, 1800);
            }).catch(function () {
                copyButton.textContent = 'No se pudo copiar';
                setTimeout(function () {
                    copyButton.textContent = 'Copiar contraseña';
                }, 1800);
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            const button = document.getElementById('copy-temp-password-btn');
            if (button) {
                copyTempPassword();
            }
        });
    </script>
</body>
</html>
