<?php
session_start();
require_once 'config.php';
require_once 'config_correos.php';

// Registrar errores para debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Verificar autenticación (solo dueño)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'dueño') {
    header('Location: index.php');
    exit;
}

// Obtener año del filtro (por defecto el año actual)
$anio_filtro = isset($_GET['anio']) ? (int)$_GET['anio'] : date('Y');

// Exportar a Excel si se solicita
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="reporte_altas_' . $anio_filtro . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    echo "\xEF\xBB\xBF"; // BOM UTF-8
}

// Consulta de altas
$query = "
    SELECT 
        tb_cuentas.razonsocial,
        tb_cuentas.fechaalta,
        tb_cuentas.telefono,
        tb_cuentas.email,
        tb_cuentas.identificador AS nif,
        tb_establecimientos.nombrecomercial,
        tb_establecimientos.direccion,
        tb_establecimientos.cp,
        tb_nivel4.municipio,
        tb_nivel3.nombre_nivel3 AS provincia
    FROM tb_establecimientos
    LEFT JOIN tb_cuentas ON tb_cuentas.ID = tb_establecimientos.ID
    LEFT JOIN tb_nivel3 ON tb_establecimientos.ID_NIVEL3 = tb_nivel3.ID_NIVEL3
    LEFT JOIN tb_nivel4 ON tb_establecimientos.codigo = tb_nivel4.codigo
    WHERE YEAR(tb_cuentas.fechaalta) = ?
    ORDER BY tb_cuentas.fechaalta DESC
";

$stmt = $pdo_correos->prepare($query);
$stmt->execute([$anio_filtro]);
$altas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Si es exportación a Excel, solo mostrar la tabla
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    echo '<table border="1">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Razón Social</th>';
    echo '<th>Fecha Alta</th>';
    echo '<th>Teléfono</th>';
    echo '<th>Email</th>';
    echo '<th>NIF</th>';
    echo '<th>Nombre Comercial</th>';
    echo '<th>Dirección</th>';
    echo '<th>CP</th>';
    echo '<th>Municipio</th>';
    echo '<th>Provincia</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    foreach ($altas as $alta) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($alta['razonsocial'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($alta['fechaalta'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($alta['telefono'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($alta['email'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($alta['nif'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($alta['nombrecomercial'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($alta['direccion'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($alta['cp'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($alta['municipio'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($alta['provincia'] ?? '') . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    exit;
}

// Obtener años disponibles
$stmt_anios = $pdo_correos->query("SELECT DISTINCT YEAR(fechaalta) as anio FROM tb_cuentas WHERE fechaalta IS NOT NULL ORDER BY anio DESC");
$anios_disponibles = $stmt_anios->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Altas <?php echo $anio_filtro; ?></title>
    <link rel="stylesheet" href="reporte_altas.css">
    <link rel="stylesheet" href="reporte_altas.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white; padding: 20px; }
            .container { max-width: 100%; box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header no-print">
            <div class="header-content">
                <div class="logo">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                    <span>Reporte de Altas</span>
                </div>
                <div class="actions">
                    <a href="dueño.php" class="btn btn-secondary">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 12H5M12 19l-7-7 7-7"/>
                        </svg>
                        Volver
                    </a>
                </div>
            </div>
        </header>

        <main class="main-content">
            <div class="card">
                <div class="card-header no-print">
                    <div class="header-left">
                        <h1>Reporte de Altas - Año <?php echo $anio_filtro; ?></h1>
                        <p class="subtitle">Total de registros: <?php echo count($altas); ?></p>
                    </div>
                    <div class="header-actions">
                        <form method="GET" class="filter-form">
                            <select name="anio" onchange="this.form.submit()" class="year-select">
                                <?php foreach ($anios_disponibles as $anio): ?>
                                    <option value="<?php echo $anio; ?>" <?php echo $anio == $anio_filtro ? 'selected' : ''; ?>>
                                        <?php echo $anio; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                        <a href="?anio=<?php echo $anio_filtro; ?>&export=excel" class="btn btn-excel">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="7 10 12 15 17 10"></polyline>
                                <line x1="12" y1="15" x2="12" y2="3"></line>
                            </svg>
                            Exportar Excel
                        </a>
                        <button onclick="window.print()" class="btn btn-print">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 6 2 18 2 18 9"></polyline>
                                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                                <rect x="6" y="14" width="12" height="8"></rect>
                            </svg>
                            Imprimir
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <?php if (count($altas) > 0): ?>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Razón Social</th>
                                        <th>Fecha Alta</th>
                                        <th>Teléfono</th>
                                        <th>Email</th>
                                        <th>NIF</th>
                                        <th>Nombre Comercial</th>
                                        <th>Dirección</th>
                                        <th>CP</th>
                                        <th>Municipio</th>
                                        <th>Provincia</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($altas as $alta): ?>
                                        <tr>
                                            <td data-label="Razón Social"><?php echo htmlspecialchars($alta['razonsocial'] ?? '—'); ?></td>
                                            <td data-label="Fecha Alta"><?php echo $alta['fechaalta'] ? date('d/m/Y', strtotime($alta['fechaalta'])) : '—'; ?></td>
                                            <td data-label="Teléfono"><?php echo htmlspecialchars($alta['telefono'] ?? '—'); ?></td>
                                            <td data-label="Email"><?php echo htmlspecialchars($alta['email'] ?? '—'); ?></td>
                                            <td data-label="NIF"><?php echo htmlspecialchars($alta['nif'] ?? '—'); ?></td>
                                            <td data-label="Nombre Comercial"><?php echo htmlspecialchars($alta['nombrecomercial'] ?? '—'); ?></td>
                                            <td data-label="Dirección"><?php echo htmlspecialchars($alta['direccion'] ?? '—'); ?></td>
                                            <td data-label="CP"><?php echo htmlspecialchars($alta['cp'] ?? '—'); ?></td>
                                            <td data-label="Municipio"><?php echo htmlspecialchars($alta['municipio'] ?? '—'); ?></td>
                                            <td data-label="Provincia"><?php echo htmlspecialchars($alta['provincia'] ?? '—'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                            <p>No hay registros de altas para el año <?php echo $anio_filtro; ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
