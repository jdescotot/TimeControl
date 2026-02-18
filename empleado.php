<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'empleado') {
    header('Location: index.php');
    exit;
}
$empleado_id = $_SESSION['user_id'];

// Obtener el nombre del empleado si existe
$stmt_nombre = $pdo->prepare("SELECT nombre FROM usuarios WHERE id = ?");
$stmt_nombre->execute([$empleado_id]);
$usuario_data = $stmt_nombre->fetch(PDO::FETCH_ASSOC);
$nombre_mostrar = (!empty($usuario_data['nombre'])) ? $usuario_data['nombre'] : $_SESSION['username'];

function columnas_perfil_usuario(PDO $pdo): array {
    $stmt = $pdo->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios'");
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $perfil = [];
    foreach (['apellido', 'telefono', 'correo'] as $col) {
        if (in_array($col, $cols, true)) {
            $perfil[] = $col;
        }
    }

    if (!in_array('correo', $perfil, true) && in_array('email', $cols, true)) {
        $perfil[] = 'email';
    }

    return $perfil;
}

function perfil_incompleto(PDO $pdo, int $usuario_id): bool {
    $cols = columnas_perfil_usuario($pdo);
    if (empty($cols)) {
        return false;
    }

    $sql = 'SELECT ' . implode(', ', $cols) . ' FROM usuarios WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$usuario_id]);
    $datos = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$datos) {
        return true;
    }

    foreach ($cols as $col) {
        if ($datos[$col] === null || $datos[$col] === '') {
            return true;
        }
    }

    return false;
}

$flag_stmt = $pdo->prepare("SELECT requiere_cambio_password FROM usuarios WHERE id = ?");
$flag_stmt->execute([$empleado_id]);
$flag = $flag_stmt->fetch(PDO::FETCH_ASSOC);

if ($flag && (int)$flag['requiere_cambio_password'] === 1) {
    header('Location: cambiar_password.php');
    exit;
}

if (perfil_incompleto($pdo, $empleado_id)) {
    header('Location: completar_perfil.php');
    exit;
}
$hoy = date('Y-m-d');
// Verificar si ya marcó entrada hoy (y si ya salió)
$stmt = $pdo->prepare("
    SELECT id, entrada, salida
    FROM marcaciones 
    WHERE empleado_id = ?
    ORDER BY entrada DESC 
    LIMIT 1
");
$stmt->execute([$empleado_id]);
$registro_hoy = $stmt->fetch();
$tiene_registro_hoy = $registro_hoy && date('Y-m-d', strtotime($registro_hoy['entrada'])) === $hoy;
$jornada_abierta = $registro_hoy && !empty($registro_hoy['entrada']) && empty($registro_hoy['salida']);
$ultimo_cerrado = $registro_hoy && !empty($registro_hoy['salida']);
$entrada_fecha = $registro_hoy && !empty($registro_hoy['entrada']) ? date('Y-m-d', strtotime($registro_hoy['entrada'])) : null;
$entrada_es_hoy = $entrada_fecha === $hoy;
$bloqueo_flag = isset($_GET['bloqueo']) && $_GET['bloqueo'] === 'salida_pendiente';
$estado_solicitud_pendiente = null;
$tiene_solicitud_pendiente = false;

if ($jornada_abierta && !empty($registro_hoy['id'])) {
    $stmt_pendiente = $pdo->prepare("SELECT estado FROM solicitudes_cambio WHERE marcacion_id = ? AND estado IN ('pendiente', 'pendiente_empleado', 'rechazado_empleado') ORDER BY id DESC LIMIT 1");
    $stmt_pendiente->execute([$registro_hoy['id']]);
    $solicitud_pendiente = $stmt_pendiente->fetch(PDO::FETCH_ASSOC);
    if ($solicitud_pendiente) {
        $tiene_solicitud_pendiente = true;
        $estado_solicitud_pendiente = $solicitud_pendiente['estado'];
    }
}

$bloqueo_salida_pendiente = $jornada_abierta && (!$entrada_es_hoy || $bloqueo_flag) && !$tiene_solicitud_pendiente;

$stmt_pendientes_empleado = $pdo->prepare("
    SELECT s.id, s.nueva_hora_entrada, s.nueva_hora_salida, s.motivo, s.fecha_solicitud,
           m.entrada, m.salida, DATE(m.entrada) as fecha
    FROM solicitudes_cambio s
    JOIN marcaciones m ON s.marcacion_id = m.id
    WHERE s.empleado_id = ? AND s.estado = 'pendiente_empleado'
    ORDER BY s.fecha_solicitud DESC
");
$stmt_pendientes_empleado->execute([$empleado_id]);
$solicitudes_empleado = $stmt_pendientes_empleado->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel del Empleado - Control Horario</title>
    <link rel="stylesheet" href="empleado.css">
    <link rel="stylesheet" href="solicitudes_cambio.css">
    <link rel="stylesheet" href="gestionar_solicitud.css">
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
                    <span class="username"><?php echo htmlspecialchars($nombre_mostrar); ?></span>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main-content">
            <?php if (isset($_GET['perfil_actualizado'])): ?>
                <div class="status-message success" style="margin-bottom: 15px;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                    <span>Datos de seguridad actualizados.</span>
                </div>
            <?php endif; ?>

            <!-- Card de marcación -->
            <div class="card marcacion-card">
                <div class="card-header">
                    <h2>Marcación de Hoy</h2>
                    <div class="date-badge"><?php echo date('d/m/Y'); ?></div>
                </div>
                <div class="card-body">
                    <?php if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'solicitud_ok'): ?>
                        <div class="status-message success" style="margin-bottom: 15px;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                            <span>Solicitud enviada correctamente.</span>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'respuesta_ok'): ?>
                        <div class="status-message success" style="margin-bottom: 15px;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                            <span>Respuesta enviada correctamente.</span>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'salida_fuera_rango'): ?>
                        <div class="status-message warning" style="margin-bottom: 15px;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                            <span>La salida no puede exceder 12 horas desde la entrada. Solicita una corrección.</span>
                        </div>
                    <?php endif; ?>

                    <?php if (!$tiene_registro_hoy && !$jornada_abierta): ?>
                        <div class="status-message info">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="16" x2="12" y2="12"></line>
                                <line x1="12" y1="8" x2="12.01" y2="8"></line>
                            </svg>
                            <span>Aún no has marcado entrada hoy</span>
                        </div>
                        <form action="marcar.php" method="POST" class="marcacion-form">
                            <input type="hidden" name="accion" value="entrada">
                            <button type="submit" class="btn btn-primary">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                                    <polyline points="10 17 15 12 10 7"></polyline>
                                    <line x1="15" y1="12" x2="3" y2="12"></line>
                                </svg>
                                Marcar Entrada
                            </button>
                        </form>
                    <?php elseif ($jornada_abierta): ?>
                        <?php if ($tiene_solicitud_pendiente): ?>
                            <div class="status-message info">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="16" x2="12" y2="12"></line>
                                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                </svg>
                                <span>
                                    Tienes una solicitud en revisión. Puedes marcar una nueva entrada mientras se procesa.
                                </span>
                            </div>
                            <div class="jornada-info" style="margin-bottom: 12px;">
                                <div class="info-item">
                                    <span class="label">Entrada pendiente:</span>
                                    <span class="value"><?php echo $registro_hoy && $registro_hoy['entrada'] ? date('H:i', strtotime($registro_hoy['entrada'])) : '—'; ?></span>
                                </div>
                                <?php if ($estado_solicitud_pendiente): ?>
                                <div class="info-item">
                                    <span class="label">Estado:</span>
                                    <span class="value"><?php echo htmlspecialchars($estado_solicitud_pendiente); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                            <form action="marcar.php" method="POST" class="marcacion-form">
                                <input type="hidden" name="accion" value="entrada">
                                <button type="submit" class="btn btn-primary">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                                        <polyline points="10 17 15 12 10 7"></polyline>
                                        <line x1="15" y1="12" x2="3" y2="12"></line>
                                    </svg>
                                    Marcar Nueva Entrada
                                </button>
                            </form>
                        <?php elseif ($bloqueo_salida_pendiente): ?>
                            <div class="status-message warning">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                <span>
                                    Jornada pendiente sin salida. No puedes marcar una nueva entrada.
                                    Solicita la hora de salida para revisión.
                                </span>
                            </div>
                            <div class="jornada-info" style="margin-bottom: 12px;">
                                <div class="info-item">
                                    <span class="label">Entrada:</span>
                                    <span class="value"><?php echo $registro_hoy && $registro_hoy['entrada'] ? date('H:i', strtotime($registro_hoy['entrada'])) : '—'; ?></span>
                                </div>
                            </div>
                            <div class="marcacion-form" style="display: flex; gap: 12px; flex-wrap: wrap;">
                                <button type="button" class="btn btn-primary" onclick="abrirSolicitud(<?= (int)($registro_hoy['id'] ?? 0) ?>, '<?= $registro_hoy && $registro_hoy['entrada'] ? date('H:i', strtotime($registro_hoy['entrada'])) : '' ?>', '', true)">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                    Solicitar Salida
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="status-message warning">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                <span>Jornada en curso - Entrada marcada: <?php echo $registro_hoy && $registro_hoy['entrada'] ? date('H:i', strtotime($registro_hoy['entrada'])) : '—'; ?></span>
                            </div>
                            <form action="marcar.php" method="POST" class="marcacion-form">
                                <input type="hidden" name="accion" value="salida">
                                <button type="submit" class="btn btn-secondary">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                        <polyline points="16 17 21 12 16 7"></polyline>
                                        <line x1="21" y1="12" x2="9" y2="12"></line>
                                    </svg>
                                    Marcar Salida
                                </button>
                            </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="status-message success">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                            <span>Última jornada completada</span>
                        </div>
                        <div class="jornada-info">
                            <div class="info-item">
                                <span class="label">Entrada:</span>
                                <span class="value"><?php echo $registro_hoy && $registro_hoy['entrada'] ? date('H:i', strtotime($registro_hoy['entrada'])) : '—'; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="label">Salida:</span>
                                <span class="value"><?php echo $registro_hoy && $registro_hoy['salida'] ? date('H:i', strtotime($registro_hoy['salida'])) : '—'; ?></span>
                            </div>
                        </div>
                        <form action="marcar.php" method="POST" class="marcacion-form" style="margin-top: 12px;">
                            <input type="hidden" name="accion" value="entrada">
                            <button type="submit" class="btn btn-primary">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                                    <polyline points="10 17 15 12 10 7"></polyline>
                                    <line x1="15" y1="12" x2="3" y2="12"></line>
                                </svg>
                                Marcar Nueva Entrada
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($solicitudes_empleado)): ?>
            <div class="card solicitudes-card" style="margin-top: 20px;">
                <div class="card-header">
                    <h2>Propuestas del Dueño</h2>
                    <div class="date-badge"><?php echo count($solicitudes_empleado); ?> pendiente<?php echo count($solicitudes_empleado) !== 1 ? 's' : ''; ?></div>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Horario Original</th>
                                    <th>Horario Propuesto</th>
                                    <th>Motivo</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($solicitudes_empleado as $s): ?>
                                    <tr>
                                        <td data-label="Fecha"><?php echo date('d/m/Y', strtotime($s['fecha'])); ?></td>
                                        <td data-label="Horario Original">
                                            <small>
                                                <strong>E:</strong> <?php echo $s['entrada'] ? date('H:i', strtotime($s['entrada'])) : '—'; ?><br>
                                                <strong>S:</strong> <?php echo $s['salida'] ? date('H:i', strtotime($s['salida'])) : '—'; ?>
                                            </small>
                                        </td>
                                        <td data-label="Horario Propuesto" class="horario-highlight">
                                            <strong>E:</strong> <?php echo !empty($s['nueva_hora_entrada']) ? substr($s['nueva_hora_entrada'], 0, 5) : '—'; ?><br>
                                            <strong>S:</strong> <?php echo !empty($s['nueva_hora_salida']) ? substr($s['nueva_hora_salida'], 0, 5) : '—'; ?>
                                        </td>
                                        <td data-label="Motivo" class="motivo-cell"><?php echo htmlspecialchars($s['motivo']); ?></td>
                                        <td data-label="Acciones">
                                            <form action="procesar_solicitud_empleado.php" method="POST" style="display:inline;" class="actions-cell">
                                                <input type="hidden" name="id_solicitud" value="<?php echo $s['id']; ?>">
                                                <button name="accion" value="aprobar" class="btn-aprobar">Aprobar</button>
                                                <button name="accion" value="rechazar" class="btn-rechazar">Rechazar</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Card de historial -->
            <div class="card historial-card">
                <div class="card-header">
                    <h2>Historial de Marcaciones</h2>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Entrada</th>
                                    <th>Salida</th>
                                    <th>Horas Trabajadas</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $pdo->prepare("
                                    SELECT m.id, DATE(m.entrada) as fecha, m.entrada, m.salida,
                                           sc.nueva_hora_entrada, sc.nueva_hora_salida, sc.motivo
                                    FROM marcaciones m
                                    LEFT JOIN solicitudes_cambio sc ON m.id = sc.marcacion_id AND sc.estado = 'aprobado'
                                    WHERE m.empleado_id = ? 
                                    ORDER BY m.entrada DESC
                                ");
                                $stmt->execute([$empleado_id]);
                                $marcaciones = $stmt->fetchAll();
                                
                                $es_ultimo = true; // Flag para detectar la última entrada
                                if (count($marcaciones) > 0):
                                    foreach ($marcaciones as $fila):
                                        $entrada_dt = $fila['entrada'] ? new DateTime($fila['entrada']) : null;
                                        $salida_dt = $fila['salida'] ? new DateTime($fila['salida']) : null;
                                        $entrada = $entrada_dt ? $entrada_dt->format('H:i') : null;
                                        $salida = $salida_dt ? $salida_dt->format('H:i') : null;
                                        $entrada_ajustada = $fila['nueva_hora_entrada'];
                                        $salida_ajustada = $fila['nueva_hora_salida'];
                                        $tiene_ajuste_entrada = !empty($entrada_ajustada);
                                        $tiene_ajuste_salida = !empty($salida_ajustada);
                                        
                                        // Usar horas ajustadas si existen (con día de inicio)
                                        $fecha_base = $fila['fecha'];
                                        $entrada_calcular_dt = $entrada_ajustada ? new DateTime($fecha_base . ' ' . $entrada_ajustada) : $entrada_dt;
                                        if ($salida_ajustada) {
                                            $salida_calcular_dt = new DateTime($fecha_base . ' ' . $salida_ajustada);
                                        } else {
                                            $salida_calcular_dt = $salida_dt;
                                        }
                                        
                                        $horas = '—';
                                        if ($entrada_calcular_dt && $salida_calcular_dt) {
                                            if ($salida_calcular_dt < $entrada_calcular_dt) {
                                                $salida_calcular_dt->modify('+1 day');
                                            }
                                            $intervalo = $entrada_calcular_dt->diff($salida_calcular_dt);
                                            $horas = $intervalo->format('%h horas %i minutos');
                                        }
                                ?>
                                    <tr>
                                        <td data-label="Fecha"><?= htmlspecialchars($fila['fecha']) ?></td>
                                        <td data-label="Entrada">
                                            <?php if ($tiene_ajuste_entrada): ?>
                                                <span style="text-decoration: line-through; opacity: 0.6; font-size: 12px;"><?= $entrada ? substr($entrada, 0, 5) : '—' ?></span><br>
                                                <strong style="color: #667eea;"><?= substr($entrada_ajustada, 0, 5) ?></strong>
                                                <span style="background: #667eea; color: white; padding: 1px 4px; border-radius: 3px; font-size: 10px; margin-left: 3px;" title="<?= htmlspecialchars($fila['motivo']) ?>">Ajustado</span>
                                            <?php else: ?>
                                                <?= $entrada ? substr($entrada, 0, 5) : '—' ?>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="Salida">
                                            <?php if ($tiene_ajuste_salida): ?>
                                                <span style="text-decoration: line-through; opacity: 0.6; font-size: 12px;"><?= $salida ? substr($salida, 0, 5) : '—' ?></span><br>
                                                <strong style="color: #667eea;"><?= substr($salida_ajustada, 0, 5) ?></strong>
                                                <span style="background: #667eea; color: white; padding: 1px 4px; border-radius: 3px; font-size: 10px; margin-left: 3px;" title="<?= htmlspecialchars($fila['motivo']) ?>">Ajustado</span>
                                            <?php else: ?>
                                                <?= $salida ? substr($salida, 0, 5) : '—' ?>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="Horas"><?= $horas ?></td>
                                        <td data-label="Acción">
                                            <?php if ($es_ultimo): ?>
                                                <button class="btn-request" onclick="abrirSolicitud(<?= $fila['id'] ?>, '<?= $entrada ?? '' ?>', '<?= $salida ?? '' ?>')">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                    </svg>
                                                    Corregir
                                                </button>
                                                <?php $es_ultimo = false; ?>
                                            <?php else: ?>
                                                —
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php 
                                    endforeach;
                                else:
                                ?>
                                    <tr>
                                        <td colspan="5" class="empty-state">
                                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                            </svg>
                                            <p>No hay marcaciones registradas</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>

        <!-- Modal de Solicitud de Cambio -->
        <div id="modalCambio" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="modalTitulo">Solicitar Cambio de Horario</h3>
                </div>
                <form action="solicitar_cambio.php" method="POST">
                    <input type="hidden" name="marcacion_id" id="form_id">
                    <input type="hidden" name="solo_salida" id="form_solo_salida" value="0">
                        <div class="form-group">
                            <label for="nueva_entrada">Nueva Hora de Entrada:</label>
                            <input type="time" name="nueva_entrada" id="form_entrada" step="60" required>
                        </div>
                        <div class="form-group">
                            <label for="nueva_salida">Nueva Hora de Salida:</label>
                            <input type="time" name="nueva_salida" id="form_salida" step="60" required>
                        </div>
                    <div class="form-group">
                        <label for="motivo">Motivo del Cambio:</label>
                        <textarea name="motivo" rows="4" placeholder="Escribe aquí el por qué de la solicitud..." required></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary" style="flex: 1;">Enviar Solicitud</button>
                        <button type="button" class="btn btn-secondary" onclick="cerrarSolicitud()" style="flex: 1;">Cancelar</button>
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
function abrirSolicitud(id, entrada, salida, soloSalida = false) {
    const entradaInput = document.getElementById('form_entrada');
    const salidaInput = document.getElementById('form_salida');
    const soloSalidaInput = document.getElementById('form_solo_salida');
    const titulo = document.getElementById('modalTitulo');

    document.getElementById('form_id').value = id;
    entradaInput.value = entrada ? entrada.substring(0, 5) : '';
    salidaInput.value = salida ? salida.substring(0, 5) : '';
    soloSalidaInput.value = soloSalida ? '1' : '0';

    if (soloSalida) {
        titulo.textContent = 'Solicitar Hora de Salida';
        entradaInput.required = false;
        entradaInput.disabled = true;
    } else {
        titulo.textContent = 'Solicitar Cambio de Horario';
        entradaInput.required = true;
        entradaInput.disabled = false;
    }

    salidaInput.required = true;
    document.getElementById('modalCambio').style.display = 'block';
}

        function cerrarSolicitud() {
            document.getElementById('modalCambio').style.display = 'none';
        }

        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            let modal = document.getElementById('modalCambio');
            if (event.target == modal) {
                cerrarSolicitud();
            }
        }
    </script>
</body>
</html>