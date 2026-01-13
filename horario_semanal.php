<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'dueño') {
    header('Location: index.php');
    exit;
}

// Configurar locale para español si es posible, o usar mapeo manual
$dias_semana = [
    1 => 'Lunes',
    2 => 'Martes',
    3 => 'Miércoles',
    4 => 'Jueves',
    5 => 'Viernes',
    6 => 'Sábado',
    7 => 'Domingo'
];

// Obtener semana y año
$semana = isset($_GET['semana']) ? (int)$_GET['semana'] : (int)date('W');
$año = isset($_GET['año']) ? (int)$_GET['año'] : (int)date('Y');

// Ajustar semana si es necesario (manejo simple de fin de año)
if ($semana < 1) {
    $semana = 52;
    $año--;
} elseif ($semana > 53) {
    $semana = 1;
    $año++;
}

// Calcular fechas de la semana
$dto = new DateTime();
$dto->setISODate($año, $semana);
$inicio_semana = clone $dto;
$fechas_semana = [];
for ($i = 0; $i < 7; $i++) {
    $fechas_semana[$i+1] = $dto->format('Y-m-d');
    $dto->modify('+1 day');
}

// Obtener empleados
$dueño_id = $_SESSION['user_id'];
$stmt_empleados = $pdo->prepare("SELECT id, username FROM usuarios WHERE rol = 'empleado' AND propietario_id = ? ORDER BY username");
$stmt_empleados->execute([$dueño_id]);
$empleados = $stmt_empleados->fetchAll(PDO::FETCH_ASSOC);

// Obtener horarios de descanso de la semana
$stmt_horarios = $pdo->prepare("SELECT empleado_id, fecha_descanso FROM horarios_semanales WHERE semana_año = ? AND año = ?");
$stmt_horarios->execute([$semana, $año]);
$descansos_raw = $stmt_horarios->fetchAll(PDO::FETCH_ASSOC);
$descansos = [];
foreach ($descansos_raw as $d) {
    $descansos[$d['empleado_id']] = $d['fecha_descanso'];
}

// Obtener ausencias y observaciones de la semana
$inicio_f = $fechas_semana[1];
$fin_f = $fechas_semana[7];
$stmt_ausencias = $pdo->prepare("SELECT * FROM ausencias_empleados WHERE fecha BETWEEN ? AND ?");
$stmt_ausencias->execute([$inicio_f, $fin_f]);
$ausencias_raw = $stmt_ausencias->fetchAll(PDO::FETCH_ASSOC);
$ausencias = [];
foreach ($ausencias_raw as $a) {
    $ausencias[$a['empleado_id']][$a['fecha']] = [
        'tipo' => $a['tipo_ausencia'],
        'obs' => $a['observaciones']
    ];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Horario Semanal - Control Horario</title>
    <link rel="stylesheet" href="empleado.css">
    <link rel="stylesheet" href="horario_semanal.css">
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
                    <span class="welcome-text">Gestión de Horarios</span>
                    <a href="dueño.php" class="btn-nav" style="margin-top: 8px; text-decoration: none;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                        Volver al Panel
                    </a>
                </div>
            </div>
        </header>

        <main class="main-content">
            <div class="schedule-container">
                <div class="week-navigation">
                    <button class="btn-nav" onclick="cambiarSemana(<?php echo $semana-1; ?>, <?php echo $año; ?>)">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="15 18 9 12 15 6"></polyline>
                        </svg>
                        Semana Anterior
                    </button>
                    <div class="current-week-title">
                        Semana <?php echo $semana; ?> (<?php echo $inicio_semana->format('d/m'); ?> - <?php echo (clone $inicio_semana)->modify('+6 days')->format('d/m'); ?>, <?php echo $año; ?>)
                        <span id="save-status" class="save-status">Guardando...</span>
                    </div>
                    <button class="btn-nav" onclick="cambiarSemana(<?php echo $semana+1; ?>, <?php echo $año; ?>)">
                        Semana Siguiente
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="schedule-table">
                        <thead>
                            <tr>
                                <th class="employee-cell">Empleado</th>
                                <?php foreach ($dias_semana as $num => $nombre): ?>
                                    <th>
                                        <?php echo $nombre; ?><br>
                                        <small><?php echo date('d/m', strtotime($fechas_semana[$num])); ?></small>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($empleados as $emp): ?>
                                <tr>
                                    <td class="employee-cell"><?php echo htmlspecialchars($emp['username']); ?></td>
                                    <?php foreach ($dias_semana as $num => $nombre): 
                                        $fecha_dia = $fechas_semana[$num];
                                        $es_descanso = ($descansos[$emp['id']] ?? '') === $fecha_dia;
                                        $ausencia = $ausencias[$emp['id']][$fecha_dia] ?? null;
                                        $tipo_ausencia = $ausencia['tipo'] ?? 'observacion';
                                        $obs = $ausencia['obs'] ?? '';
                                        
                                        $cell_class = $es_descanso ? 'is-rest-day' : '';
                                        if ($ausencia && $tipo_ausencia !== 'observacion') $cell_class .= ' has-absence';
                                        elseif ($obs) $cell_class .= ' has-observation';
                                    ?>
                                        <td class="day-cell <?php echo $cell_class; ?>" id="cell-<?php echo $emp['id']; ?>-<?php echo $fecha_dia; ?>">
                                            <div class="rest-day-toggle">
                                                <input type="radio" 
                                                       name="rest_<?php echo $emp['id']; ?>" 
                                                       value="<?php echo $fecha_dia; ?>"
                                                       <?php echo $es_descanso ? 'checked' : ''; ?>
                                                       onchange="guardarDescanso(<?php echo $emp['id']; ?>, '<?php echo $fecha_dia; ?>')">
                                                <span class="rest-day-label">Descanso</span>
                                            </div>

                                            <select class="absence-select" 
                                                    onchange="guardarAusencia(<?php echo $emp['id']; ?>, '<?php echo $fecha_dia; ?>', this.value, document.getElementById('obs-<?php echo $emp['id']; ?>-<?php echo $fecha_dia; ?>').value)">
                                                <option value="observacion" <?php echo $tipo_ausencia === 'observacion' ? 'selected' : ''; ?>>Asistente / Obs.</option>
                                                <option value="enfermedad" <?php echo $tipo_ausencia === 'enfermedad' ? 'selected' : ''; ?>>Enfermedad</option>
                                                <option value="emergencia_familiar" <?php echo $tipo_ausencia === 'emergencia_familiar' ? 'selected' : ''; ?>>Emergencia Fam.</option>
                                                <option value="fuerza_mayor" <?php echo $tipo_ausencia === 'fuerza_mayor' ? 'selected' : ''; ?>>Fuerza Mayor</option>
                                                <option value="vacaciones_ley" <?php echo $tipo_ausencia === 'vacaciones_ley' ? 'selected' : ''; ?>>Vacaciones Ley</option>
                                            </select>

                                            <textarea id="obs-<?php echo $emp['id']; ?>-<?php echo $fecha_dia; ?>" 
                                                      class="observation-textarea" 
                                                      placeholder="Notas..."
                                                      onblur="guardarAusencia(<?php echo $emp['id']; ?>, '<?php echo $fecha_dia; ?>', this.previousElementSibling.value, this.value)"><?php echo htmlspecialchars($obs); ?></textarea>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        function cambiarSemana(s, a) {
            window.location.href = `horario_semanal.php?semana=${s}&año=${a}`;
        }

        function showStatus(text, type) {
            const status = document.getElementById('save-status');
            status.textContent = text;
            status.className = 'save-status visible ' + type;
            setTimeout(() => {
                status.className = 'save-status';
            }, 2000);
        }

        function guardarDescanso(empleadoId, fecha) {
            showStatus('Guardando...', '');
            const formData = new FormData();
            formData.append('empleado_id', empleadoId);
            formData.append('fecha_descanso', fecha);
            formData.append('semana', <?php echo $semana; ?>);
            formData.append('año', <?php echo $año; ?>);

            fetch('guardar_horario.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showStatus('Guardado', 'success');
                    // Actualizar clases visuales
                    document.querySelectorAll(`[name="rest_${empleadoId}"]`).forEach(input => {
                        const cell = document.getElementById(`cell-${empleadoId}-${input.value}`);
                        if (input.checked) cell.classList.add('is-rest-day');
                        else cell.classList.remove('is-rest-day');
                    });
                } else {
                    showStatus('Error', 'error');
                }
            });
        }

        function guardarAusencia(empleadoId, fecha, tipo, obs) {
            showStatus('Guardando...', '');
            const formData = new FormData();
            formData.append('empleado_id', empleadoId);
            formData.append('fecha', fecha);
            formData.append('tipo_ausencia', tipo);
            formData.append('observaciones', obs);

            fetch('guardar_ausencia.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showStatus('Guardado', 'success');
                    const cell = document.getElementById(`cell-${empleadoId}-${fecha}`);
                    if (tipo !== 'observacion') {
                        cell.classList.add('has-absence');
                    } else {
                        cell.classList.remove('has-absence');
                    }
                    if (obs) {
                        cell.classList.add('has-observation');
                    } else {
                        cell.classList.remove('has-observation');
                    }
                } else {
                    showStatus('Error', 'error');
                }
            });
        }
    </script>
</body>
</html>
