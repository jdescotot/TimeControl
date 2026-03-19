<?php
session_start();
require_once 'config.php';

$has_hacienda_role = isset($_SESSION['rol']) && $_SESSION['rol'] === 'hacienda';
$has_master_access = !empty($_SESSION['hacienda_master_access']);

if (!$has_hacienda_role && !$has_master_access) {
    http_response_code(403);
    echo 'Acceso no autorizado';
    exit;
}

$busqueda = trim((string)($_GET['busqueda'] ?? ''));
$mes_filtro = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date('n');
$anio_filtro = isset($_GET['año']) ? (int)$_GET['año'] : (int)date('Y');

if ($mes_filtro < 1 || $mes_filtro > 12) {
    $mes_filtro = (int)date('n');
}

if ($anio_filtro < 2020 || $anio_filtro > 2035) {
    $anio_filtro = (int)date('Y');
}

$meses = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];

$primer_dia = sprintf('%04d-%02d-01', $anio_filtro, $mes_filtro);
$ultimo_dia = date('Y-m-d', strtotime($primer_dia . ' +1 month -1 day'));

$query_duenos = "
    SELECT
        u.id,
        u.username,
        u.created_at,
        COUNT(DISTINCT emp.id) AS total_empleados,
        COALESCE(SUM(CASE WHEN emp.id IS NOT NULL AND um.ultima_marcacion IS NOT NULL THEN 1 ELSE 0 END), 0) AS empleados_con_marcacion,
        COALESCE(SUM(CASE WHEN emp.id IS NOT NULL AND um.ultima_marcacion IS NULL THEN 1 ELSE 0 END), 0) AS empleados_sin_marcacion,
        MAX(um.ultima_marcacion) AS ultima_actividad
    FROM usuarios u
    LEFT JOIN usuarios emp ON emp.propietario_id = u.id AND emp.rol = 'empleado'
    LEFT JOIN (
        SELECT empleado_id, MAX(entrada) AS ultima_marcacion
        FROM marcaciones
        GROUP BY empleado_id
    ) um ON um.empleado_id = emp.id
    WHERE u.rol = 'dueño'
";

if ($busqueda !== '') {
    $query_duenos .= " AND u.username LIKE :busqueda";
}

$query_duenos .= " GROUP BY u.id, u.username, u.created_at ORDER BY u.username";

$stmt_duenos = $pdo->prepare($query_duenos);
if ($busqueda !== '') {
    $stmt_duenos->bindValue(':busqueda', '%' . $busqueda . '%');
}
$stmt_duenos->execute();
$duenos = $stmt_duenos->fetchAll(PDO::FETCH_ASSOC);

$hoy = new DateTime('today');

$filename = 'reporte_maestro_' . $anio_filtro . '_' . str_pad((string)$mes_filtro, 2, '0', STR_PAD_LEFT) . '.csv';
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename=' . $filename);

echo "\xEF\xBB\xBF";
$out = fopen('php://output', 'w');

fputcsv($out, ['Reporte Maestro de Uso']);
fputcsv($out, ['Periodo', $meses[$mes_filtro] . ' ' . $anio_filtro]);
fputcsv($out, ['Filtro dueño', $busqueda !== '' ? $busqueda : 'Todos']);
fputcsv($out, []);

fputcsv($out, [
    'Dueño',
    'Estado Dueño',
    'Dueño Registrado',
    'Total Empleados',
    'Empleados Con Marcacion',
    'Empleados Sin Marcar',
    'Ultima Actividad Dueño',
    'Empleado',
    'Empleado Creado',
    'Ultima Marcacion Empleado',
    'Estado Empleado',
    'Dias Trabajados en Periodo',
    'Ajustes Aprobados'
]);

foreach ($duenos as $dueno) {
    $stmt_empleados = $pdo->prepare("
        SELECT
            u.id,
            u.username,
            u.created_at,
            (
                SELECT MAX(m2.entrada)
                FROM marcaciones m2
                WHERE m2.empleado_id = u.id
            ) AS ultima_marcacion,
            (
                SELECT COUNT(DISTINCT DATE(m3.entrada))
                FROM marcaciones m3
                WHERE m3.empleado_id = u.id
                  AND DATE(m3.entrada) BETWEEN ? AND ?
            ) AS dias_trabajados,
            (
                SELECT COUNT(DISTINCT sc.id)
                FROM solicitudes_cambio sc
                INNER JOIN marcaciones mm ON mm.id = sc.marcacion_id
                WHERE sc.empleado_id = u.id
                  AND sc.estado = 'aprobado'
                  AND DATE(mm.entrada) BETWEEN ? AND ?
            ) AS ajustes_aprobados
        FROM usuarios u
        WHERE u.rol = 'empleado' AND u.propietario_id = ?
        ORDER BY u.username
    ");

    $stmt_empleados->execute([$primer_dia, $ultimo_dia, $primer_dia, $ultimo_dia, $dueno['id']]);
    $empleados = $stmt_empleados->fetchAll(PDO::FETCH_ASSOC);

    $estado_dueno = ((int)$dueno['empleados_con_marcacion'] > 0) ? 'En uso' : 'Sin uso';
    $dueno_creado = !empty($dueno['created_at']) ? date('d/m/Y', strtotime($dueno['created_at'])) : '';
    $ultima_actividad_dueno = !empty($dueno['ultima_actividad']) ? date('d/m/Y H:i', strtotime($dueno['ultima_actividad'])) : 'Sin actividad';

    if (empty($empleados)) {
        fputcsv($out, [
            $dueno['username'],
            $estado_dueno,
            $dueno_creado,
            (int)$dueno['total_empleados'],
            (int)$dueno['empleados_con_marcacion'],
            (int)$dueno['empleados_sin_marcacion'],
            $ultima_actividad_dueno,
            '',
            '',
            '',
            'Sin empleados',
            '',
            ''
        ]);
        continue;
    }

    foreach ($empleados as $emp) {
        $ultima_marcacion = $emp['ultima_marcacion'] ?? null;
        $estado_empleado = 'Nunca marco';
        $ultima_marcacion_fmt = 'Nunca';

        if (!empty($ultima_marcacion)) {
            $ultima_fecha = new DateTime($ultima_marcacion);
            $dias_sin_marcar = (int)$hoy->diff(new DateTime($ultima_fecha->format('Y-m-d')))->days;
            $ultima_marcacion_fmt = $ultima_fecha->format('d/m/Y H:i');
            $estado_empleado = ($dias_sin_marcar <= 30) ? 'Activo' : 'Inactivo';
        }

        $emp_creado = !empty($emp['created_at']) ? date('d/m/Y', strtotime($emp['created_at'])) : '';

        fputcsv($out, [
            $dueno['username'],
            $estado_dueno,
            $dueno_creado,
            (int)$dueno['total_empleados'],
            (int)$dueno['empleados_con_marcacion'],
            (int)$dueno['empleados_sin_marcacion'],
            $ultima_actividad_dueno,
            $emp['username'],
            $emp_creado,
            $ultima_marcacion_fmt,
            $estado_empleado,
            (int)$emp['dias_trabajados'],
            (int)$emp['ajustes_aprobados']
        ]);
    }
}

fclose($out);
exit;
