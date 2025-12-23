<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'dueño') {
    header('Location: index.php');
    exit;
}

$hoy = date('Y-m-d');

// Obtener número de solicitudes pendientes con manejo de errores robusto
try {
    $stmt_pendientes = $pdo->query("SELECT COUNT(*) as total FROM solicitudes_cambio WHERE estado = 'pendiente'");
    $resultado = $stmt_pendientes->fetch(PDO::FETCH_ASSOC);
    $num_solicitudes = (int)($resultado['total'] ?? 0);
} catch (Exception $e) {
    $num_solicitudes = 0;
    error_log("Error al obtener solicitudes: " . $e->getMessage());
}

// Obtener todos los empleados (excluyendo al dueño)
$stmt = $pdo->prepare("SELECT id, username FROM usuarios WHERE rol = 'empleado' ORDER BY username");
$stmt->execute();
$empleados = $stmt->fetchAll();

// Preparar estadísticas del día
$total_empleados = count($empleados);
$entraron_hoy = 0;
$en_jornada = 0;

// Para cada empleado, verificar su estado hoy
foreach ($empleados as &$emp) {
    $stmt = $pdo->prepare("
        SELECT hora_entrada, hora_salida 
        FROM marcaciones 
        WHERE empleado_id = ? AND fecha = ?
        ORDER BY id DESC 
        LIMIT 1
    ");
    $stmt->execute([$emp['id'], $hoy]);
    $registro = $stmt->fetch();

    $emp['hora_entrada'] = $registro['hora_entrada'] ?? null;
    $emp['hora_salida'] = $registro['hora_salida'] ?? null;

    if ($emp['hora_entrada']) {
        $entraron_hoy++;
        if (!$emp['hora_salida']) {
            $en_jornada++;
        }
    }
}
$pendientes = $total_empleados - $entraron_hoy;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel del Dueño - Control Horario</title>
    <link rel="stylesheet" href="empleado.css">
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
                    <span class="welcome-text">Bienvenido,</span>
                    <span class="username"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Notificación de Solicitudes Pendientes -->
            <?php if ($num_solicitudes > 0): ?>
            <div class="card notification-card">
                <div class="card-body">
                    <div class="notification-content">
                        <div class="notification-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                            </svg>
                            <span class="notification-badge"><?php echo $num_solicitudes; ?></span>
                        </div>
                        <div class="notification-text">
                            <strong>Solicitudes Pendientes</strong>
                            <p>Tienes <?php echo $num_solicitudes; ?> <?php echo $num_solicitudes === 1 ? 'solicitud' : 'solicitudes'; ?> de cambio de horario pendiente<?php echo $num_solicitudes === 1 ? '' : 's'; ?> de revisión.</p>
                        </div>
                        <a href="gestionar_solicitudes.php" class="btn btn-notification">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 11l3 3L22 4"></path>
                                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                            </svg>
                            Gestionar Solicitudes
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Resumen del día -->
            <div class="card">
                <div class="card-header">
                    <h2>Actividad de Hoy</h2>
                    <div class="date-badge"><?php echo date('d/m/Y'); ?></div>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                        <div class="info-item" style="border-left-color: #48bb78;">
                            <span class="label">Total Empleados</span>
                            <span class="value"><?php echo $total_empleados; ?></span>
                        </div>
                        <div class="info-item" style="border-left-color: #3182ce;">
                            <span class="label">Ya Entraron</span>
                            <span class="value"><?php echo $entraron_hoy; ?></span>
                        </div>
                        <div class="info-item" style="border-left-color: #ed8936;">
                            <span class="label">En Jornada</span>
                            <span class="value"><?php echo $en_jornada; ?></span>
                        </div>
                        <div class="info-item" style="border-left-color: #e53e3e;">
                            <span class="label">Pendientes</span>
                            <span class="value"><?php echo $pendientes; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lista de empleados -->
            <div class="card">
                <div class="card-header">
                    <h2>Empleados</h2>
                    <button onclick="abrirModalEmpleado()" class="btn" style="padding:10px 20px; font-size:15px; background:linear-gradient(135deg, #48bb78 0%, #38a169 100%); color:white; border:none; cursor:pointer; border-radius:8px; display:inline-flex; align-items:center; gap:8px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="8.5" cy="7" r="4"></circle>
                            <line x1="20" y1="8" x2="20" y2="14"></line>
                            <line x1="23" y1="11" x2="17" y2="11"></line>
                        </svg>
                        Agregar Empleado
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Empleado</th>
                                    <th>Estado Hoy</th>
                                    <th>Horas Trabajadas</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($empleados)): ?>
                                    <tr>
                                        <td colspan="4" class="empty-state">
                                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                            </svg>
                                            <p>No hay empleados registrados</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($empleados as $emp): ?>
                                        <tr>
                                            <td data-label="Empleado"><?php echo htmlspecialchars($emp['username']); ?></td>
                                            <td data-label="Estado">
                                                <?php if (!$emp['hora_entrada']): ?>
                                                    <span style="color:#e53e3e;">Sin marcar</span>
                                                <?php elseif ($emp['hora_entrada'] && !$emp['hora_salida']): ?>
                                                    <span style="color:#ed8936;">En jornada (desde <?php echo $emp['hora_entrada']; ?>)</span>
                                                <?php else: ?>
                                                    <span style="color:#38a169;">Completado</span><br>
                                                    <small>Entrada: <?php echo $emp['hora_entrada']; ?> | Salida: <?php echo $emp['hora_salida']; ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="Horas">
                                                <?php if ($emp['hora_entrada'] && $emp['hora_salida']): 
                                                    $inicio = new DateTime($hoy . ' ' . $emp['hora_entrada']);
                                                    $fin = new DateTime($hoy . ' ' . $emp['hora_salida']);
                                                    $intervalo = $inicio->diff($fin);
                                                    echo $intervalo->format('%h:%i');
                                                else:
                                                    echo '—';
                                                endif; ?>
                                            </td>
                                            <td data-label="Acción">
                                                <a href="historial_empleado.php?id=<?php echo $emp['id']; ?>" class="btn" style="padding:6px 12px; font-size:14px; background:linear-gradient(135deg, #667eea 0%, #764ba2 100%); color:white; text-decoration:none; display:inline-block;">
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

        <!-- Footer -->
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
</body>
</html>