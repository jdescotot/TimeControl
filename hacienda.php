<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'hacienda') {
    header('Location: index.php');
    exit;
}

// Obtener filtros
$busqueda = $_GET['busqueda'] ?? '';
$mes_filtro = $_GET['mes'] ?? date('m');
$año_filtro = $_GET['año'] ?? date('Y');

// Obtener todos los dueños con sus estadísticas
$query_duenos = "
    SELECT 
        u.id,
        u.username,
        u.created_at,
        COUNT(DISTINCT emp.id) as total_empleados
    FROM usuarios u
    LEFT JOIN usuarios emp ON emp.propietario_id = u.id AND emp.rol = 'empleado'
    WHERE u.rol = 'dueño'
";

if ($busqueda) {
    $query_duenos .= " AND u.username LIKE :busqueda";
}

$query_duenos .= " GROUP BY u.id ORDER BY u.username";

$stmt_duenos = $pdo->prepare($query_duenos);
if ($busqueda) {
    $stmt_duenos->bindValue(':busqueda', "%$busqueda%");
}
$stmt_duenos->execute();
$duenos = $stmt_duenos->fetchAll(PDO::FETCH_ASSOC);

// Para cada dueño, obtener sus empleados con estadísticas del mes
$primer_dia = "$año_filtro-" . str_pad($mes_filtro, 2, '0', STR_PAD_LEFT) . "-01";
$ultimo_dia = date('Y-m-d', strtotime("$año_filtro-" . str_pad($mes_filtro, 2, '0', STR_PAD_LEFT) . "-01 +1 month -1 day"));

foreach ($duenos as &$dueno) {
    $stmt_empleados = $pdo->prepare("
        SELECT 
            u.id,
            u.username,
            COUNT(DISTINCT m.id) as dias_trabajados,
            COUNT(DISTINCT CASE WHEN sc.estado = 'aprobado' THEN sc.id END) as ajustes_aprobados
        FROM usuarios u
        LEFT JOIN marcaciones m ON m.empleado_id = u.id AND m.fecha BETWEEN ? AND ?
        LEFT JOIN solicitudes_cambio sc ON sc.empleado_id = u.id AND sc.estado = 'aprobado'
            AND sc.marcacion_id IN (SELECT id FROM marcaciones WHERE fecha BETWEEN ? AND ?)
        WHERE u.rol = 'empleado' AND u.propietario_id = ?
        GROUP BY u.id
        ORDER BY u.username
    ");
    $stmt_empleados->execute([$primer_dia, $ultimo_dia, $primer_dia, $ultimo_dia, $dueno['id']]);
    $dueno['empleados'] = $stmt_empleados->fetchAll(PDO::FETCH_ASSOC);
}

$meses = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Hacienda - Control Horario</title>
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
                    <span>Control Horario - Hacienda</span>
                </div>
                <div class="user-info">
                    <span class="welcome-text">Panel Universal</span>
                    <span class="username"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </div>
            </div>
        </header>

        <main class="main-content">
            <div class="hacienda-header">
                <h1>🏛️ Panel de Hacienda</h1>
                <p>Vista universal de todos los dueños y empleados del sistema</p>
            </div>

            <!-- Filtros y búsqueda -->
            <div class="card filters-card">
                <div class="card-body">
                    <form method="GET" action="" class="filters-form">
                        <div class="filter-group">
                            <label for="busqueda">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <path d="m21 21-4.35-4.35"></path>
                                </svg>
                                Buscar Dueño
                            </label>
                            <input type="text" id="busqueda" name="busqueda" 
                                   placeholder="Nombre del dueño..." 
                                   value="<?php echo htmlspecialchars($busqueda); ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label for="mes">Mes</label>
                            <select id="mes" name="mes">
                                <?php foreach ($meses as $num => $nombre): ?>
                                    <option value="<?php echo $num; ?>" <?php echo $mes_filtro == $num ? 'selected' : ''; ?>>
                                        <?php echo $nombre; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="año">Año</label>
                            <input type="number" id="año" name="año" 
                                   value="<?php echo $año_filtro; ?>" 
                                   min="2020" max="2030">
                        </div>

                        <button type="submit" class="btn-filter">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                            </svg>
                            Filtrar
                        </button>
                    </form>
                </div>
            </div>

            <!-- Estadísticas generales -->
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
                        <span class="stat-label">Total Dueños</span>
                        <span class="stat-value"><?php echo count($duenos); ?></span>
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
                        <span class="stat-label">Total Empleados</span>
                        <span class="stat-value"><?php echo array_sum(array_column($duenos, 'total_empleados')); ?></span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: #feebc8;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <span class="stat-label">Período</span>
                        <span class="stat-value"><?php echo $meses[$mes_filtro]; ?> <?php echo $año_filtro; ?></span>
                    </div>
                </div>
            </div>

            <!-- Lista de dueños -->
            <?php if (empty($duenos)): ?>
                <div class="card">
                    <div class="card-body empty-state">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <p>No se encontraron dueños</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($duenos as $dueno): ?>
                    <div class="card dueno-card">
                        <div class="dueno-header" onclick="toggleDueno(<?php echo $dueno['id']; ?>)">
                            <div class="dueno-info">
                                <h3>
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="12" cy="7" r="4"></circle>
                                    </svg>
                                    <?php echo htmlspecialchars($dueno['username']); ?>
                                </h3>
                                <div class="dueno-stats">
                                    <span class="badge badge-blue"><?php echo $dueno['total_empleados']; ?> empleados</span>
                                    <span class="date-info">Registrado: <?php echo date('d/m/Y', strtotime($dueno['created_at'])); ?></span>
                                </div>
                            </div>
                            <div class="dueno-actions">
                                <a href="hacienda_reporte.php?dueno_id=<?php echo $dueno['id']; ?>&mes=<?php echo $mes_filtro; ?>&año=<?php echo $año_filtro; ?>" 
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

                        <div class="dueno-content" id="dueno-<?php echo $dueno['id']; ?>" style="display: none;">
                            <?php if (empty($dueno['empleados'])): ?>
                                <div class="empty-state-small">
                                    <p>No hay empleados registrados para este dueño</p>
                                </div>
                            <?php else: ?>
                                <div class="empleados-table">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Empleado</th>
                                                <th>Días Trabajados (<?php echo $meses[$mes_filtro]; ?>)</th>
                                                <th>Ajustes Aprobados</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($dueno['empleados'] as $emp): ?>
                                                <tr>
                                                    <td>
                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                                            <circle cx="12" cy="7" r="4"></circle>
                                                        </svg>
                                                        <?php echo htmlspecialchars($emp['username']); ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-green"><?php echo $emp['dias_trabajados']; ?> días</span>
                                                    </td>
                                                    <td>
                                                        <?php if ($emp['ajustes_aprobados'] > 0): ?>
                                                            <span class="badge badge-purple"><?php echo $emp['ajustes_aprobados']; ?> ajustes</span>
                                                        <?php else: ?>
                                                            <span style="color: #a0aec0;">—</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <a href="historial_empleado.php?id=<?php echo $emp['id']; ?>" 
                                                           class="btn-action-small">
                                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                                <circle cx="12" cy="12" r="3"></circle>
                                                            </svg>
                                                            Ver Historial
                                                        </a>
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

        <footer class="footer">
            <a href="logout.php" class="logout-link">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                Cerrar Sesión
            </a>
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
    </script>
</body>
</html>
