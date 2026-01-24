<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'empleado') {
    header('Location: index.php');
    exit;
}
$empleado_id = $_SESSION['user_id'];
$hoy = date('Y-m-d');
// Verificar si ya marcó entrada hoy (y si ya salió)
$stmt = $pdo->prepare("
    SELECT id, hora_entrada, hora_salida 
    FROM marcaciones 
    WHERE empleado_id = ? AND fecha = ?
    ORDER BY id DESC 
    LIMIT 1
");
$stmt->execute([$empleado_id, $hoy]);
$registro_hoy = $stmt->fetch();
$ya_entró = $registro_hoy && !empty($registro_hoy['hora_entrada']);
$ya_salió = $registro_hoy && !empty($registro_hoy['hora_salida']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel del Empleado - Control Horario</title>
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
                    <span class="welcome-text">Bienvenido,</span>
                    <span class="username"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main-content">
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

                    <?php if (!$ya_entró): ?>
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
                    <?php elseif ($ya_entró && !$ya_salió): ?>
                        <div class="status-message warning">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            <span>Jornada en curso - Entrada marcada: <?php echo $registro_hoy['hora_entrada']; ?></span>
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
                    <?php else: ?>
                        <div class="status-message success">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                            <span>Jornada completada - Buen trabajo!</span>
                        </div>
                        <div class="jornada-info">
                            <div class="info-item">
                                <span class="label">Entrada:</span>
                                <span class="value"><?php echo $registro_hoy['hora_entrada']; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="label">Salida:</span>
                                <span class="value"><?php echo $registro_hoy['hora_salida']; ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

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
                                    SELECT m.id, m.fecha, m.hora_entrada, m.hora_salida,
                                           sc.nueva_hora_entrada, sc.nueva_hora_salida, sc.motivo
                                    FROM marcaciones m
                                    LEFT JOIN solicitudes_cambio sc ON m.id = sc.marcacion_id AND sc.estado = 'aprobado'
                                    WHERE m.empleado_id = ? 
                                    ORDER BY m.fecha DESC, m.hora_entrada DESC
                                ");
                                $stmt->execute([$empleado_id]);
                                $marcaciones = $stmt->fetchAll();
                                
                                $es_ultimo = true; // Flag para detectar la última entrada
                                if (count($marcaciones) > 0):
                                    foreach ($marcaciones as $fila):
                                        $entrada = $fila['hora_entrada'];
                                        $salida = $fila['hora_salida'];
                                        $entrada_ajustada = $fila['nueva_hora_entrada'];
                                        $salida_ajustada = $fila['nueva_hora_salida'];
                                        $tiene_ajuste = !empty($entrada_ajustada);
                                        
                                        // Usar horas ajustadas si existen
                                        $entrada_calcular = $entrada_ajustada ?? $entrada;
                                        $salida_calcular = $salida_ajustada ?? $salida;
                                        
                                        $horas = '—';
                                        if ($entrada_calcular && $salida_calcular) {
                                            $inicio = new DateTime($fila['fecha'] . ' ' . $entrada_calcular);
                                            $fin = new DateTime($fila['fecha'] . ' ' . $salida_calcular);
                                            $intervalo = $inicio->diff($fin);
                                            $horas = $intervalo->format('%h horas %i minutos');
                                        }
                                ?>
                                    <tr>
                                        <td data-label="Fecha"><?= htmlspecialchars($fila['fecha']) ?></td>
                                        <td data-label="Entrada">
                                            <?php if ($tiene_ajuste): ?>
                                                <span style="text-decoration: line-through; opacity: 0.6; font-size: 12px;"><?= $entrada ? substr($entrada, 0, 5) : '—' ?></span><br>
                                                <strong style="color: #667eea;"><?= substr($entrada_ajustada, 0, 5) ?></strong>
                                                <span style="background: #667eea; color: white; padding: 1px 4px; border-radius: 3px; font-size: 10px; margin-left: 3px;" title="<?= htmlspecialchars($fila['motivo']) ?>">Ajustado</span>
                                            <?php else: ?>
                                                <?= $entrada ? substr($entrada, 0, 5) : '—' ?>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="Salida">
                                            <?php if ($tiene_ajuste): ?>
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
                                                <button class="btn-request" onclick="abrirSolicitud(<?= $fila['id'] ?>, '<?= $fila['hora_entrada'] ?>', '<?= $fila['hora_salida'] ?>')">
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
                    <h3>Solicitar Cambio de Horario</h3>
                </div>
                <form action="solicitar_cambio.php" method="POST">
                    <input type="hidden" name="marcacion_id" id="form_id">
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
function abrirSolicitud(id, entrada, salida) {
    document.getElementById('form_id').value = id;
    document.getElementById('form_entrada').value = entrada ? entrada.substring(0, 5) : '';
    document.getElementById('form_salida').value = salida ? salida.substring(0, 5) : '';
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