<?php
session_start();
require_once 'config.php';
require_once 'config_correos.php';

// Registrar errores para debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Verificar autenticaciÃ³n (solo dueño)
require_dueno_o_gerente($pdo);

// Obtener año del filtro (por defecto el año actual)
$anio_filtro = isset($_GET['anio']) ? (int)$_GET['anio'] : date('Y');

// Exportar a Excel si se solicita
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="reporte_bajas_' . $anio_filtro . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    echo "\xEF\xBB\xBF"; // BOM UTF-8
}

// Consulta de bajas
$query = "
    SELECT 
        tb_cuentas.razonsocial,
        tb_cuentas.fechaalta,
        tb_cuentas.fechabaja,
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
    WHERE YEAR(tb_cuentas.fechabaja) = ?
    ORDER BY tb_cuentas.fechabaja DESC
";

$stmt = $pdo_correos->prepare($query);
$stmt->execute([$anio_filtro]);
$bajas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Si es exportaciÃ³n a Excel, solo mostrar la tabla
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    echo '<table border="1">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>RazÃ³n Social</th>';
    echo '<th>Fecha Alta</th>';
    echo '<th>Fecha Baja</th>';
    echo '<th>TelÃ©fono</th>';
    echo '<th>Email</th>';
    echo '<th>NIF</th>';
    echo '<th>Nombre Comercial</th>';
    echo '<th>DirecciÃ³n</th>';
    echo '<th>CP</th>';
    echo '<th>Municipio</th>';
    echo '<th>Provincia</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    foreach ($bajas as $baja) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($baja['razonsocial'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($baja['fechaalta'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($baja['fechabaja'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($baja['telefono'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($baja['email'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($baja['nif'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($baja['nombrecomercial'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($baja['direccion'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($baja['cp'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($baja['municipio'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($baja['provincia'] ?? '') . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    exit;
}

// Obtener años disponibles
$stmt_anios = $pdo_correos->query("SELECT DISTINCT YEAR(fechabaja) as anio FROM tb_cuentas WHERE fechabaja IS NOT NULL ORDER BY anio DESC");
$anios_disponibles = $stmt_anios->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Bajas <?php echo $anio_filtro; ?></title>
    <link rel="stylesheet" href="reporte_bajas.css">
    <link rel="stylesheet" href="reporte_bajas.css">
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
                    <span>Reporte de Bajas</span>
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
                        <h1>Reporte de Bajas - AÃ±o <?php echo $anio_filtro; ?></h1>
                        <p class="subtitle">Total de registros: <?php echo count($bajas); ?></p>
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
                    <?php if (count($bajas) > 0): ?>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>RazÃ³n Social</th>
                                        <th>Fecha Alta</th>
                                        <th>Fecha Baja</th>
                                        <th>TelÃ©fono</th>
                                        <th>Email</th>
                                        <th>NIF</th>
                                        <th>Nombre Comercial</th>
                                        <th>DirecciÃ³n</th>
                                        <th>CP</th>
                                        <th>Municipio</th>
                                        <th>Provincia</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bajas as $baja): ?>
                                        <tr>
                                            <td data-label="RazÃ³n Social"><?php echo htmlspecialchars($baja['razonsocial'] ?? 'â€”'); ?></td>
                                            <td data-label="Fecha Alta"><?php echo $baja['fechaalta'] ? date('d/m/Y', strtotime($baja['fechaalta'])) : 'â€”'; ?></td>
                                            <td data-label="Fecha Baja"><?php echo $baja['fechabaja'] ? date('d/m/Y', strtotime($baja['fechabaja'])) : 'â€”'; ?></td>
                                            <td data-label="TelÃ©fono"><?php echo htmlspecialchars($baja['telefono'] ?? 'â€”'); ?></td>
                                            <td data-label="Email"><?php echo htmlspecialchars($baja['email'] ?? 'â€”'); ?></td>
                                            <td data-label="NIF"><?php echo htmlspecialchars($baja['nif'] ?? 'â€”'); ?></td>
                                            <td data-label="Nombre Comercial"><?php echo htmlspecialchars($baja['nombrecomercial'] ?? 'â€”'); ?></td>
                                            <td data-label="DirecciÃ³n"><?php echo htmlspecialchars($baja['direccion'] ?? 'â€”'); ?></td>
                                            <td data-label="CP"><?php echo htmlspecialchars($baja['cp'] ?? 'â€”'); ?></td>
                                            <td data-label="Municipio"><?php echo htmlspecialchars($baja['municipio'] ?? 'â€”'); ?></td>
                                            <td data-label="Provincia"><?php echo htmlspecialchars($baja['provincia'] ?? 'â€”'); ?></td>
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
                            <p>No hay registros de bajas para el año <?php echo $anio_filtro; ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>

