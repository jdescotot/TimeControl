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

// Obtener marcaciones del empleado
$stmt = $pdo->prepare("
    SELECT fecha, hora_entrada, hora_salida 
    FROM marcaciones 
    WHERE empleado_id = ? 
    ORDER BY fecha DESC, hora_entrada DESC
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
                    <span class="welcome-text">Dueño</span>
                    <span class="username"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Botón de regreso destacado -->
            <a href="dueño.php" class="back-button">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5"></path>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                <span>Volver al Panel de Control</span>
            </a>

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
                                        $horas = '—';
                                        if ($entrada && $salida) {
                                            $inicio = new DateTime($fila['fecha'] . ' ' . $entrada);
                                            $fin = new DateTime($fila['fecha'] . ' ' . $salida);
                                            $intervalo = $inicio->diff($fin);
                                            $horas = $intervalo->format('%h horas %i minutos');
                                        }
                                    ?>
                                        <tr>
                                            <td data-label="Fecha"><?= htmlspecialchars($fila['fecha']) ?></td>
                                            <td data-label="Entrada"><?= $entrada ?: '—' ?></td>
                                            <td data-label="Salida"><?= $salida ?: '—' ?></td>
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