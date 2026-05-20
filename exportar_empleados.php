<?php
/**
 * exportar_empleados.php
 * Exporta los empleados de TimeControl como sentencias SQL INSERT
 * listas para importar en la tabla empleados del sistema destino.
 *
 * Formato destino:
 *   ID_empleado, nombre, apellidos, telefono, identificador,
 *   email, pin, estado, informado, accedido
 */

session_start();
require_once 'config.php';

// Solo el dueÃ±o puede exportar
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'dueÃ±o') {
    header('Location: index.php');
    exit;
}

// â”€â”€ Detectar quÃ© columnas opcionales existen en la tabla usuarios â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$stmt = $pdo->query("
    SELECT COLUMN_NAME
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'usuarios'
");
$columnas_existentes = $stmt->fetchAll(PDO::FETCH_COLUMN);

$tiene_apellido  = in_array('apellido',  $columnas_existentes, true);
$tiene_telefono  = in_array('telefono',  $columnas_existentes, true);
$tiene_correo    = in_array('correo',    $columnas_existentes, true);
$tiene_email     = in_array('email',     $columnas_existentes, true);

// Construir SELECT dinÃ¡mico
$campos_select = ['id', 'username', 'nombre', 'requiere_cambio_password'];
if ($tiene_apellido)                  $campos_select[] = 'apellido';
if ($tiene_telefono)                  $campos_select[] = 'telefono';
if ($tiene_correo)                    $campos_select[] = 'correo';
elseif ($tiene_email)                 $campos_select[] = 'email';

$propietario_id = (int) $_SESSION['user_id'];
$sql_select = "SELECT " . implode(', ', $campos_select) . "
               FROM usuarios
               WHERE rol = 'empleado'
                 AND propietario_id = ?
               ORDER BY id ASC";

$stmt = $pdo->prepare($sql_select);
$stmt->execute([$propietario_id]);
$empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// â”€â”€ Generar PIN por defecto: Ãºltimos 4 dÃ­gitos numÃ©ricos del DNI/NIE â”€â”€â”€â”€â”€â”€â”€
function generarPin(string $username): string {
    preg_match_all('/\d/', $username, $matches);
    $digitos = implode('', $matches[0]);
    if (strlen($digitos) >= 4) {
        return substr($digitos, -4);
    }
    // Fallback: PIN numÃ©rico de 4 dÃ­gitos basado en un hash
    return str_pad((string)(crc32($username) % 10000), 4, '0', STR_PAD_LEFT);
}

// â”€â”€ Formatear valor para SQL â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function sqlVal(?string $v): string {
    if ($v === null || $v === '') return 'NULL';
    $v = str_replace("'", "''", $v);
    return "'" . $v . "'";
}

// â”€â”€ Decidir formato de salida â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$formato = $_GET['formato'] ?? 'html';  // html | sql | csv

if ($formato === 'sql') {
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="empleados_export_' . date('Ymd_His') . '.sql"');

    echo "-- ExportaciÃ³n de empleados desde TimeControl\n";
    echo "-- Generado: " . date('Y-m-d H:i:s') . "\n";
    echo "-- Tabla destino: empleados\n\n";

    foreach ($empleados as $e) {
        $nombre   = htmlspecialchars_decode($e['nombre'] ?? '');
        $apellido = $tiene_apellido ? ($e['apellido'] ?? '') : '';

        // Si no hay columna apellido, intentar separar del nombre completo
        if ($apellido === '' && strpos($nombre, ' ') !== false) {
            $partes   = explode(' ', $nombre, 2);
            $nombre   = $partes[0];
            $apellido = $partes[1];
        }

        $telefono   = $tiene_telefono ? ($e['telefono'] ?? null) : null;
        $email_val  = $tiene_correo   ? ($e['correo'] ?? null)
                    : ($tiene_email   ? ($e['email']   ?? null) : null);
        $pin        = generarPin($e['username']);
        $estado     = 1;
        $informado  = ($e['requiere_cambio_password'] == 0) ? 1 : 0;
        $accedido   = 0;

        echo "INSERT INTO empleados "
           . "(ID_empleado, nombre, apellidos, telefono, identificador, email, pin, estado, informado, accedido) VALUES ("
           . $e['id'] . ", "
           . sqlVal($nombre) . ", "
           . sqlVal($apellido) . ", "
           . sqlVal($telefono) . ", "
           . sqlVal($e['username']) . ", "
           . sqlVal($email_val) . ", "
           . sqlVal($pin) . ", "
           . $estado . ", "
           . $informado . ", "
           . $accedido
           . ");\n";
    }
    exit;
}

if ($formato === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="empleados_export_' . date('Ymd_His') . '.csv"');

    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8 para Excel
    fputcsv($out, ['ID_empleado', 'nombre', 'apellidos', 'telefono', 'identificador', 'email', 'pin', 'estado', 'informado', 'accedido'], ';');

    foreach ($empleados as $e) {
        $nombre   = $e['nombre'] ?? '';
        $apellido = $tiene_apellido ? ($e['apellido'] ?? '') : '';

        if ($apellido === '' && strpos($nombre, ' ') !== false) {
            $partes   = explode(' ', $nombre, 2);
            $nombre   = $partes[0];
            $apellido = $partes[1];
        }

        fputcsv($out, [
            $e['id'],
            $nombre,
            $apellido,
            $tiene_telefono ? ($e['telefono'] ?? '') : '',
            $e['username'],
            $tiene_correo ? ($e['correo'] ?? '') : ($tiene_email ? ($e['email'] ?? '') : ''),
            generarPin($e['username']),
            1,
            ($e['requiere_cambio_password'] == 0) ? 1 : 0,
            0,
        ], ';');
    }
    fclose($out);
    exit;
}

// â”€â”€ Vista HTML de previsualizaciÃ³n â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exportar Empleados</title>
    <link rel="stylesheet" href="empleado.css">
    <style>
        body { padding: 30px; }
        h2  { margin-bottom: 16px; }
        .acciones { display: flex; gap: 12px; margin-bottom: 24px; flex-wrap: wrap; }
        .btn {
            display: inline-block; padding: 10px 20px; border-radius: 8px;
            text-decoration: none; font-weight: 600; font-size: 14px; cursor: pointer; border: none;
        }
        .btn-primary { background: #4f46e5; color: #fff; }
        .btn-green   { background: #16a34a; color: #fff; }
        .btn-gray    { background: #6b7280; color: #fff; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { border: 1px solid #e5e7eb; padding: 8px 10px; text-align: left; }
        th     { background: #f3f4f6; font-weight: 600; }
        tr:nth-child(even) td { background: #f9fafb; }
        .pin-nota { font-size: 12px; color: #6b7280; margin-top: 8px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; }
        .badge-si  { background: #d1fae5; color: #065f46; }
        .badge-no  { background: #fee2e2; color: #991b1b; }
        .total { font-weight: 600; margin-bottom: 16px; color: #374151; }
    </style>
</head>
<body>
    <h2>Exportar empleados al sistema destino</h2>

    <p class="total">Total de empleados: <?= count($empleados) ?></p>

    <div class="acciones">
        <a href="?formato=sql" class="btn btn-primary">Descargar SQL (.sql)</a>
        <a href="?formato=csv" class="btn btn-green">Descargar CSV (.csv)</a>
        <a href="dueÃ±o.php"   class="btn btn-gray">Volver al panel</a>
    </div>

    <p class="pin-nota">
        El PIN se genera automÃ¡ticamente con los Ãºltimos 4 dÃ­gitos numÃ©ricos del DNI/NIE del empleado.
        Puedes comunicÃ¡rselo manualmente una vez importado en el otro sistema.
    </p>

    <table>
        <thead>
            <tr>
                <th>ID_empleado</th>
                <th>nombre</th>
                <th>apellidos</th>
                <th>telefono</th>
                <th>identificador</th>
                <th>email</th>
                <th>pin</th>
                <th>estado</th>
                <th>informado</th>
                <th>accedido</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($empleados as $e):
            $nombre   = $e['nombre'] ?? '';
            $apellido = $tiene_apellido ? ($e['apellido'] ?? '') : '';

            if ($apellido === '' && strpos($nombre, ' ') !== false) {
                $partes   = explode(' ', $nombre, 2);
                $nombre   = $partes[0];
                $apellido = $partes[1];
            }

            $telefono  = $tiene_telefono ? ($e['telefono'] ?? '') : '';
            $email_val = $tiene_correo   ? ($e['correo'] ?? '')
                       : ($tiene_email   ? ($e['email']   ?? '') : '');
            $pin       = generarPin($e['username']);
            $informado = ($e['requiere_cambio_password'] == 0) ? 1 : 0;
        ?>
            <tr>
                <td><?= (int)$e['id'] ?></td>
                <td><?= htmlspecialchars($nombre) ?></td>
                <td><?= htmlspecialchars($apellido) ?></td>
                <td><?= htmlspecialchars($telefono) ?></td>
                <td><?= htmlspecialchars($e['username']) ?></td>
                <td><?= htmlspecialchars($email_val) ?></td>
                <td><?= htmlspecialchars($pin) ?></td>
                <td><span class="badge badge-si">1 (activo)</span></td>
                <td><span class="badge <?= $informado ? 'badge-si' : 'badge-no' ?>"><?= $informado ?></span></td>
                <td>0</td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>

