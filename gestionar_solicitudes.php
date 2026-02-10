<?php
session_start();
require_once 'config.php';

// Verificación de seguridad similar a la de dueño.php
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'dueño') {
    header('Location: index.php');
    exit;
}

// Mensaje de éxito si viene de procesar una solicitud
$mensaje_exito = '';
if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'procesado_ok') {
    $mensaje_exito = 'Solicitud procesada correctamente';
}

// Consulta para obtener solicitudes pendientes solo de empleados del dueño actual
$dueño_id = $_SESSION['user_id'];
$query = "
    SELECT 
        s.id,
        s.marcacion_id,
        s.empleado_id,
        s.nueva_hora_entrada,
        s.nueva_hora_salida,
        s.motivo,
        s.estado,
        s.fecha_solicitud,
        u.username,
        u.nombre,
        DATE(m.entrada) as fecha,
        m.entrada as entrada_original,
        m.salida as salida_original
    FROM solicitudes_cambio s
    JOIN usuarios u ON s.empleado_id = u.id
    JOIN marcaciones m ON s.marcacion_id = m.id
    WHERE s.estado = 'pendiente' AND u.propietario_id = ?
    ORDER BY s.fecha_solicitud DESC
";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute([$dueño_id]);
    $solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error al obtener solicitudes: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Solicitudes - Control Horario</title>
    <link rel="stylesheet" href="empleado.css">
    <link rel="stylesheet" href="gestionar_solicitud.css">
    <style>

    </style>
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
                    <span class="welcome-text">Gestión de Solicitudes</span>
                    <span class="username"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </div>
            </div>
        </header>

        <main class="main-content">
            <!-- Botón de regreso -->
            <a href="dueño.php" class="back-button" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; margin-bottom: 20px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5"></path>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Volver al Panel
            </a>

            <!-- Mensaje de éxito -->
            <?php if ($mensaje_exito): ?>
            <div class="status-message success" style="margin-bottom: 20px;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <span><?php echo htmlspecialchars($mensaje_exito); ?></span>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h2>Solicitudes de Cambio Pendientes</h2>
                    <div class="date-badge"><?php echo count($solicitudes); ?> pendiente<?php echo count($solicitudes) !== 1 ? 's' : ''; ?></div>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Empleado</th>
                                    <th>Fecha</th>
                                    <th>Horario Original</th>
                                    <th>Horario Solicitado</th>
                                    <th>Motivo</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($solicitudes) > 0): ?>
                                    <?php foreach ($solicitudes as $s): ?>
                                    <tr>
                                        <td data-label="Empleado">
                                            <?php 
                                            $nombre_mostrar = !empty($s['nombre']) ? $s['nombre'] : $s['username'];
                                            echo htmlspecialchars($nombre_mostrar); 
                                            ?>
                                        </td>
                                        <td data-label="Fecha"><?php echo date('d/m/Y', strtotime($s['fecha'])); ?></td>
                                        <td data-label="Horario Original">
                                            <small>
                                                <strong>E:</strong> <?php echo $s['entrada_original'] ? date('H:i', strtotime($s['entrada_original'])) : '—'; ?><br>
                                                <strong>S:</strong> <?php echo $s['salida_original'] ? date('H:i', strtotime($s['salida_original'])) : '—'; ?>
                                            </small>
                                        </td>
                                        <td data-label="Horario Solicitado" class="horario-highlight">
                                            <strong>E:</strong> <?php echo substr($s['nueva_hora_entrada'], 0, 5); ?><br>
                                            <strong>S:</strong> <?php echo substr($s['nueva_hora_salida'], 0, 5); ?>
                                        </td>
                                        <td data-label="Motivo" class="motivo-cell"><?php echo htmlspecialchars($s['motivo']); ?></td>
                                        <td data-label="Acciones">
                                            <form action="procesar_solicitud.php" method="POST" style="display:inline;" class="actions-cell">
                                                <input type="hidden" name="id_solicitud" value="<?php echo $s['id']; ?>">
                                                <button name="accion" value="aprobar" class="btn-aprobar">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline; vertical-align: middle; margin-right: 4px;">
                                                        <polyline points="20 6 9 17 4 12"></polyline>
                                                    </svg>
                                                    Aprobar
                                                </button>
                                                <button name="accion" value="rechazar" class="btn-rechazar">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline; vertical-align: middle; margin-right: 4px;">
                                                        <line x1="18" y1="6" x2="6" y2="18"></line>
                                                        <line x1="6" y1="6" x2="18" y2="18"></line>
                                                    </svg>
                                                    Rechazar
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="empty-state">
                                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                            </svg>
                                            <p>No hay solicitudes pendientes en este momento</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
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
</body>
</html>