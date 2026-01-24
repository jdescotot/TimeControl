<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'dueño') {
    header('Location: index.php');
    exit;
}

$empleado_id = $_GET['id'] ?? 0;

// Validar que el empleado exista y sea realmente un empleado
$stmt = $pdo->prepare("SELECT username FROM usuarios WHERE id = ? AND rol = 'empleado'");
$stmt->execute([$empleado_id]);
$empleado = $stmt->fetch();

if (!$empleado) {
    die('Empleado no encontrado.');
}

$username = $empleado['username'];

// Obtener marcaciones del empleado con ajustes aprobados
$stmt = $pdo->prepare("
    SELECT m.fecha, m.hora_entrada, m.hora_salida,
           sc.nueva_hora_entrada, sc.nueva_hora_salida, sc.motivo
    FROM marcaciones m
    LEFT JOIN solicitudes_cambio sc ON m.id = sc.marcacion_id AND sc.estado = 'aprobado'
    WHERE m.empleado_id = ? 
    ORDER BY m.fecha DESC, m.hora_entrada DESC
");
$stmt->execute([$empleado_id]);
$marcaciones = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial - <?php echo htmlspecialchars($username); ?></title>
    <link rel="stylesheet" href="historial_empleado.css">
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
                    <?php 
                    $mes_param = $_GET['mes'] ?? null;
                    $año_param = $_GET['año'] ?? null;
                    $back_url = ($mes_param && $año_param) ? "reporte_mensual.php?mes=$mes_param&año=$año_param" : "dueño.php";
                    $back_text = ($mes_param && $año_param) ? "Volver al Reporte" : "Volver al Panel";
                    ?>
                    <span class="welcome-text">Historial de <?php echo htmlspecialchars($username); ?></span>
                    <a href="<?php echo $back_url; ?>" class="btn-back">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 12H5"></path>
                            <polyline points="12 19 5 12 12 5"></polyline>
                        </svg>
                        <span><?php echo $back_text; ?></span>
                    </a>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Card de historial -->
            <div class="card historial-card">
                <div class="card-header">
                    <div class="header-title">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <h2>Historial de <?php echo htmlspecialchars($username); ?></h2>
                    </div>
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
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($marcaciones) > 0): ?>
                                    <?php foreach ($marcaciones as $fila):
                                        $entrada = $fila['hora_entrada'];
                                        $salida = $fila['hora_salida'];
                                        $entrada_ajustada = $fila['nueva_hora_entrada'];
                                        $salida_ajustada = $fila['nueva_hora_salida'];
                                        $tiene_ajuste = !empty($entrada_ajustada);
                                        
                                        // Usar horas ajustadas para cálculos si existen
                                        $entrada_calcular = $entrada_ajustada ?? $entrada;
                                        $salida_calcular = $salida_ajustada ?? $salida;
                                        
                                        $horas = '—';
                                        if ($entrada_calcular && $salida_calcular) {
                                            $inicio = new DateTime($fila['fecha'] . ' ' . $entrada_calcular_calcular);
                                            $fin = new DateTime($fila['fecha'] . ' ' . $salida_calcular);
                                            $intervalo = $inicio->diff($fin);
                                            $horas = $intervalo->format('%h horas %i minutos');
                                        }
                                    ?>
                                        <tr>
                                            <td data-label="Fecha"><?= htmlspecialchars($fila['fecha']) ?></td>
                                            <td data-label="Entrada">
                                                <?php if ($tiene_ajuste): ?>
                                                    <div style="display: flex; flex-direction: column; gap: 4px;">
                                                        <span style="text-decoration: line-through; opacity: 0.5; font-size: 12px;">
                                                            <?= $entrada ? substr($entrada, 0, 5) : '—' ?>
                                                        </span>
                                                        <div>
                                                            <strong style="color: #667eea; font-size: 15px;"><?= substr($entrada_ajustada, 0, 5) ?></strong>
                                                            <span style="background: #667eea; color: white; padding: 2px 5px; border-radius: 3px; font-size: 10px; margin-left: 5px;" title="Motivo: <?= htmlspecialchars($fila['motivo']) ?>">Ajustado</span>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <?= $entrada ? substr($entrada, 0, 5) : '—' ?>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="Salida">
                                                <?php if ($tiene_ajuste): ?>
                                                    <div style="display: flex; flex-direction: column; gap: 4px;">
                                                        <span style="text-decoration: line-through; opacity: 0.5; font-size: 12px;">
                                                            <?= $salida ? substr($salida, 0, 5) : '—' ?>
                                                        </span>
                                                        <div>
                                                            <strong style="color: #667eea; font-size: 15px;"><?= substr($salida_ajustada, 0, 5) ?></strong>
                                                            <span style="background: #667eea; color: white; padding: 2px 5px; border-radius: 3px; font-size: 10px; margin-left: 5px;" title="Motivo: <?= htmlspecialchars($fila['motivo']) ?>">Ajustado</span>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <?= $salida ? substr($salida, 0, 5) : '—' ?>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="Horas"><?= $horas ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="empty-state">
                                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                            </svg>
                                            <p>No hay marcaciones registradas para este empleado</p>
                                        </td>
                                    </tr>
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