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
    $num_solicitudes = (int) ($resultado['total'] ?? 0);
} catch (Exception $e) {
    $num_solicitudes = 0;
    error_log("Error al obtener solicitudes: " . $e->getMessage());
}

// Obtener todos los empleados (excluyendo al dueño) - con mejor manejo de charset
$dueño_id = $_SESSION['user_id'];
$stmt_empleados = $pdo->prepare("
    SELECT id, username, nombre
    FROM usuarios 
    WHERE rol = 'empleado' 
    AND propietario_id = ? 
    ORDER BY nombre IS NULL OR nombre = '', nombre, username
");
$stmt_empleados->execute([$dueño_id]);
$empleados = $stmt_empleados->fetchAll(PDO::FETCH_ASSOC);

// DEBUG: Descomentar para verificar cuántos empleados se obtienen
// echo "<!-- Total empleados encontrados: " . count($empleados) . " -->";

// Preparar estadísticas del día
$total_empleados = count($empleados);
$entraron_hoy = 0;
$en_jornada = 0;

// Obtener días de descanso para hoy
$stmt_descansos = $pdo->prepare("SELECT empleado_id FROM horarios_semanales WHERE fecha_descanso = ?");
$stmt_descansos->execute([$hoy]);
$empleados_con_descanso = [];
foreach ($stmt_descansos->fetchAll(PDO::FETCH_ASSOC) as $d) {
    $empleados_con_descanso[] = $d['empleado_id'];
}

// Para cada empleado, verificar su estado hoy
if (!empty($empleados)) {
    foreach ($empleados as $key => $emp) {
        // Verificar si tiene día de descanso
        $empleados[$key]['tiene_descanso'] = in_array($emp['id'], $empleados_con_descanso);
        
        $stmt_marcacion = $pdo->prepare("
            SELECT m.hora_entrada, m.hora_salida,
                   sc.nueva_hora_entrada, sc.nueva_hora_salida
            FROM marcaciones m
            LEFT JOIN solicitudes_cambio sc ON m.id = sc.marcacion_id AND sc.estado = 'aprobado'
            WHERE m.empleado_id = ? AND m.fecha = ?
            ORDER BY m.id DESC 
            LIMIT 1
        ");
        $stmt_marcacion->execute([$emp['id'], $hoy]);
        $registro = $stmt_marcacion->fetch(PDO::FETCH_ASSOC);

        $empleados[$key]['hora_entrada'] = $registro['hora_entrada'] ?? null;
        $empleados[$key]['hora_salida'] = $registro['hora_salida'] ?? null;
        $empleados[$key]['hora_entrada_ajustada'] = $registro['nueva_hora_entrada'] ?? null;
        $empleados[$key]['hora_salida_ajustada'] = $registro['nueva_hora_salida'] ?? null;
        $empleados[$key]['tiene_ajuste'] = !empty($registro['nueva_hora_entrada']);

        if ($empleados[$key]['hora_entrada']) {
            $entraron_hoy++;
            if (!$empleados[$key]['hora_salida']) {
                $en_jornada++;
            }
        }
    }
}

// Restar empleados con descanso del total de pendientes y evitar números negativos
$pendientes = max(0, $total_empleados - $entraron_hoy - count($empleados_con_descanso));
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel del Dueño - Control Horario</title>
    <link rel="stylesheet" href="empleado.css">
    <link rel="stylesheet" href="solicitudes_cambio.css">
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
                    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; justify-content: flex-end;">
                        <a href="horario_semanal.php" class="btn"
                            style="padding:8px 16px; font-size:14px; background:linear-gradient(135deg, #667eea 0%, #764ba2 100%); color:white; text-decoration:none; border-radius:8px; display:inline-flex; align-items:center; gap:8px;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                            Horario Semanal
                        </a>
                        <a href="reporte_mensual.php" class="btn"
                            style="padding:8px 16px; font-size:14px; background:linear-gradient(135deg, #48bb78 0%, #38a169 100%); color:white; text-decoration:none; border-radius:8px; display:inline-flex; align-items:center; gap:8px;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                            Reporte Mensual
                        </a>
                        <div style="text-align: right;">
                            <span class="welcome-text">Bienvenido,</span>
                            <span class="username"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Mensaje de éxito al crear empleado -->
            <?php if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'empleado_creado'): ?>
                <div class="status-message success" style="margin-bottom: 20px;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                    <span>Empleado "<?php echo htmlspecialchars($_GET['username'] ?? 'nuevo empleado'); ?>" creado
                        exitosamente</span>
                </div>
            <?php endif; ?>

            <!-- Mensaje de error al crear empleado -->
            <?php if (isset($_GET['error'])): ?>
                <div class="status-message"
                    style="background-color: #fed7d7; color: #c53030; border-left-color: #e53e3e; margin-bottom: 20px;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <span><?php echo htmlspecialchars($_GET['error']); ?></span>
                </div>
            <?php endif; ?>

            <!-- Notificación de Solicitudes Pendientes -->
            <?php if ($num_solicitudes > 0): ?>
                <div class="card notification-card">
                    <div class="card-body">
                        <div class="notification-content">
                            <div class="notification-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                                </svg>
                                <span class="notification-badge"><?php echo $num_solicitudes; ?></span>
                            </div>
                            <div class="notification-text">
                                <strong>Solicitudes Pendientes</strong>
                                <p>Tienes <?php echo $num_solicitudes; ?>
                                    <?php echo $num_solicitudes === 1 ? 'solicitud' : 'solicitudes'; ?> de cambio de horario
                                    pendiente<?php echo $num_solicitudes === 1 ? '' : 's'; ?> de revisión.</p>
                            </div>
                            <a href="gestionar_solicitudes.php" class="btn btn-notification">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2">
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
                    <button onclick="abrirModalEmpleado()" class="btn"
                        style="padding:10px 20px; font-size:15px; background:linear-gradient(135deg, #48bb78 0%, #38a169 100%); color:white; border:none; cursor:pointer; border-radius:8px; display:inline-flex; align-items:center; gap:8px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
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
                                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2">
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
                                            <td data-label="Empleado">
                                                <a href="historial_empleado.php?id=<?php echo $emp['id']; ?>" 
                                                   style="color: #667eea; text-decoration: none; font-weight: 500; cursor: pointer; transition: all 0.2s;"
                                                   onmouseover="this.style.color='#764ba2'; this.style.textDecoration='underline';"
                                                   onmouseout="this.style.color='#667eea'; this.style.textDecoration='none';">
                                                    <?php 
                                                    $nombre_mostrar = !empty($emp['nombre']) ? $emp['nombre'] : $emp['username'];
                                                    echo htmlspecialchars($nombre_mostrar); 
                                                    ?>
                                                </a>
                                                <!-- DEBUG: ID = <?php echo $emp['id']; ?> -->
                                            </td>
                                            <td data-label="Estado">
                                                <?php if ($emp['tiene_descanso']): ?>
                                                    <span style="color:#48bb78; font-weight: 500;">
                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 4px;">
                                                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                                            <line x1="16" y1="2" x2="16" y2="6"></line>
                                                            <line x1="8" y1="2" x2="8" y2="6"></line>
                                                            <line x1="3" y1="10" x2="21" y2="10"></line>
                                                        </svg>
                                                        Día Libre
                                                    </span>
                                                <?php elseif (!$emp['hora_entrada']): ?>
                                                    <span style="color:#e53e3e;">Sin marcar</span>
                                                <?php elseif ($emp['hora_entrada'] && !$emp['hora_salida']): ?>
                                                    <?php if ($emp['tiene_ajuste']): ?>
                                                        <span style="color:#ed8936;">En jornada (desde
                                                            <span style="text-decoration: line-through; opacity: 0.6;"><?php echo substr($emp['hora_entrada'], 0, 5); ?></span>
                                                            <strong style="color: #667eea;"><?php echo substr($emp['hora_entrada_ajustada'], 0, 5); ?></strong>
                                                            <span style="background: #667eea; color: white; padding: 2px 6px; border-radius: 4px; font-size: 11px; margin-left: 4px;">Ajustado</span>)
                                                        </span>
                                                    <?php else: ?>
                                                        <span style="color:#ed8936;">En jornada (desde
                                                            <?php echo substr($emp['hora_entrada'], 0, 5); ?>)</span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span style="color:#38a169;">Completado</span><br>
                                                    <?php if ($emp['tiene_ajuste']): ?>
                                                        <small>
                                                            Entrada: <span style="text-decoration: line-through; opacity: 0.6;"><?php echo substr($emp['hora_entrada'], 0, 5); ?></span> 
                                                            <strong style="color: #667eea;"><?php echo substr($emp['hora_entrada_ajustada'], 0, 5); ?></strong> | 
                                                            Salida: <span style="text-decoration: line-through; opacity: 0.6;"><?php echo substr($emp['hora_salida'], 0, 5); ?></span> 
                                                            <strong style="color: #667eea;"><?php echo substr($emp['hora_salida_ajustada'], 0, 5); ?></strong>
                                                            <span style="background: #667eea; color: white; padding: 2px 6px; border-radius: 4px; font-size: 10px; margin-left: 4px;">Ajustado</span>
                                                        </small>
                                                    <?php else: ?>
                                                        <small>Entrada: <?php echo substr($emp['hora_entrada'], 0, 5); ?> | Salida:
                                                            <?php echo substr($emp['hora_salida'], 0, 5); ?></small>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="Horas">
                                                <?php
                                                // Usar horas ajustadas si existen, de lo contrario usar originales
                                                $entrada_usar = $emp['hora_entrada_ajustada'] ?? $emp['hora_entrada'];
                                                $salida_usar = $emp['hora_salida_ajustada'] ?? $emp['hora_salida'];
                                                
                                                if ($entrada_usar && $salida_usar) {
                                                    try {
                                                        $inicio = new DateTime($hoy . ' ' . $entrada_usar);
                                                        $fin = new DateTime($hoy . ' ' . $salida_usar);
                                                        $intervalo = $inicio->diff($fin);
                                                        echo $intervalo->format('%h:%i');
                                                        if ($emp['tiene_ajuste']) {
                                                            echo ' <span style="color: #667eea; font-size: 11px;">*</span>';
                                                        }
                                                    } catch (Exception $e) {
                                                        echo '—';
                                                    }
                                                } else {
                                                    echo '—';
                                                }
                                                ?>
                                            </td>
                                            <td data-label="Acción">
                                                <a href="historial_empleado.php?id=<?php echo $emp['id']; ?>" class="btn"
                                                    style="padding:6px 12px; font-size:14px; background:linear-gradient(135deg, #667eea 0%, #764ba2 100%); color:white; text-decoration:none; display:inline-block;">
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

        <!-- Modal para crear empleado -->
        <div id="modalEmpleado" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Crear Nuevo Empleado</h3>
                </div>
                <form action="crear_empleado.php" method="POST">
                    <div class="form-group">
                        <label for="username">Nie / Nif / Pasaporte:</label>
                        <input type="text" name="username" id="username" required minlength="3" maxlength="50"
                            placeholder="Ej: X1234567L" autocomplete="off">
                        <small style="color: #718096; font-size: 12px; margin-top: 4px; display: block;">
                            Se convertirá automáticamente a minúsculas sin espacios (Jorge Escoto → jorgeescoto)
                        </small>
                    </div>
                    <div class="form-group">
                        <label for="fecha_inicio">Fecha de Inicio:</label>
                        <input type="date" name="fecha_inicio" id="fecha_inicio" required
                            value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>">
                        <small style="color: #718096; font-size: 12px; margin-top: 4px; display: block;">
                            Fecha en que el empleado comenzó a trabajar
                        </small>
                    </div>
                    <div class="form-group">
                        <label for="password">Contraseña Temporal:</label>
                        <div style="position: relative;">
                            <input type="password" name="password" id="password" required minlength="6"
                                placeholder="Mínimo 6 caracteres sin espacios" autocomplete="new-password"
                                style="padding-right: 45px;">
                            <button type="button" onclick="togglePassword('password', this)" 
                                style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; padding: 5px; color: #718096;"
                                aria-label="Mostrar contraseña">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                        </div>
                        <small style="color: #718096; font-size: 12px; margin-top: 4px; display: block;">
                            No se permiten espacios. El empleado deberá cambiarla en su primer inicio de sesión
                        </small>
                    </div>
                    <div class="form-group">
                        <label for="confirmar_password">Confirmar Contraseña:</label>
                        <div style="position: relative;">
                            <input type="password" name="confirmar_password" id="confirmar_password" required minlength="6"
                                placeholder="Repite la contraseña" autocomplete="new-password"
                                style="padding-right: 45px;">
                            <button type="button" onclick="togglePassword('confirmar_password', this)" 
                                style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; padding: 5px; color: #718096;"
                                aria-label="Mostrar contraseña">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary" style="flex: 1;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="8.5" cy="7" r="4"></circle>
                                <line x1="20" y1="8" x2="20" y2="14"></line>
                                <line x1="23" y1="11" x2="17" y2="11"></line>
                            </svg>
                            Crear Empleado
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="cerrarModalEmpleado()"
                            style="flex: 1;">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>

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

        // Sugerencia llamativa para formatear el usuario sin espacios ni mayúsculas
        function sanitizeUsername(value) {
            return value.toLowerCase().replace(/[^a-z0-9]/g, '');
        }

        function showUsernamePopup(original, sanitized) {
            const overlay = document.createElement('div');
            overlay.style = 'position:fixed; inset:0; background:rgba(45,55,72,0.6); display:flex; align-items:center; justify-content:center; z-index:9999; padding:16px;';

            const modal = document.createElement('div');
            modal.style = 'background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color:white; padding:24px; border-radius:14px; max-width:420px; width:100%; box-shadow:0 12px 32px rgba(0,0,0,0.25);';
            modal.innerHTML = `
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:14px;">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/>
                    </svg>
                    <div>
                        <div style="font-weight:700; font-size:18px;">No se permiten espacios ni mayúsculas</div>
                        <div style="opacity:0.9; font-size:14px;">Ingresaste: <strong>${original}</strong></div>
                    </div>
                </div>
                <div style="background:rgba(255,255,255,0.15); padding:12px 14px; border-radius:10px; margin-bottom:14px; font-size:14px;">
                    Podemos usar esta versión: <strong>${sanitized}</strong>
                </div>
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <button id="useSanitized" style="flex:1; background:white; color:#5a67d8; border:none; padding:12px; border-radius:10px; font-weight:700; cursor:pointer;">Usar ${sanitized}</button>
                    <button id="keepOriginal" style="flex:1; background:rgba(255,255,255,0.16); color:white; border:1px solid rgba(255,255,255,0.4); padding:12px; border-radius:10px; font-weight:600; cursor:pointer;">Corregir manualmente</button>
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
            document.getElementById('modalEmpleado').style.display = 'block';
            // Limpiar el formulario
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

        // Validación de contraseñas coincidentes
        document.getElementById('confirmar_password')?.addEventListener('input', function () {
            const password = document.getElementById('password').value;
            const confirmar = this.value;

            if (confirmar && password !== confirmar) {
                this.setCustomValidity('Las contraseñas no coinciden');
            } else {
                this.setCustomValidity('');
            }
        });

        // Disparar sugerencia cuando el usuario salga del campo
        document.getElementById('username')?.addEventListener('blur', sugerirUsername);
    </script>
</body>

</html>