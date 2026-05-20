<?php
/**
 * API privada: marcaciones con coordenadas GPS para el mapa del dueÃ±o.
 * GET api_mapa.php?fecha=YYYY-MM-DD o ?fecha_desde=YYYY-MM-DD&fecha_hasta=YYYY-MM-DD
 * Solo accesible con sesiÃ³n de rol 'dueÃ±o'.
 * Filtra resultados para mostrar solo marcaciones dentro de JaÃ©n
 * y dentro de zonas permitidas de marcaciÃ³n.
 */
session_start();
require_once 'config.php';
require_once 'jaen_geocoder.php';

header('Content-Type: application/json; charset=utf-8');

// Guard de sesión
if (!es_dueno_o_gerente()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Acceso denegado']);
    exit;
}

$dueno_id = owner_scope_id($pdo);

// Validar parÃ¡metros de fecha - permite rango o fecha Ãºnica
$fecha_raw = $_GET['fecha'] ?? '';
$fecha_desde_raw = $_GET['fecha_desde'] ?? '';
$fecha_hasta_raw = $_GET['fecha_hasta'] ?? '';

$fecha_desde = date('Y-m-d');
$fecha_hasta = date('Y-m-d');

if ($fecha_raw) {
    // ParÃ¡metro de fecha Ãºnica
    if (!preg_match('/^\d{4}-(?:0[1-9]|1[0-2])-(?:0[1-9]|[12]\d|3[01])$/', $fecha_raw)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'ParÃ¡metro fecha invÃ¡lido']);
        exit;
    }
    $fecha_desde = $fecha_raw;
    $fecha_hasta = $fecha_raw;
} elseif ($fecha_desde_raw && $fecha_hasta_raw) {
    // ParÃ¡metros de rango
    if (!preg_match('/^\d{4}-(?:0[1-9]|1[0-2])-(?:0[1-9]|[12]\d|3[01])$/', $fecha_desde_raw) ||
        !preg_match('/^\d{4}-(?:0[1-9]|1[0-2])-(?:0[1-9]|[12]\d|3[01])$/', $fecha_hasta_raw)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'ParÃ¡metros de rango invÃ¡lidos']);
        exit;
    }
    $fecha_desde = $fecha_desde_raw;
    $fecha_hasta = $fecha_hasta_raw;
}


try {
    // Traer marcaciones del rango de fechas con al menos un par de coords (entrada o salida)
    // filtradas por empleados que pertenecen al dueÃ±o autenticado
    // LÃ­mite a 200 marcadores para no sobrecargar el mapa
    $stmt = $pdo->prepare("
        SELECT
            m.id,
            u.nombre,
            u.apellido,
            u.username,
            DATE_FORMAT(m.entrada,  '%Y-%m-%d %H:%i') AS fecha_hora_entrada,
            DATE_FORMAT(m.salida,   '%Y-%m-%d %H:%i') AS fecha_hora_salida,
            DATE_FORMAT(m.entrada,  '%H:%i') AS hora_entrada,
            DATE_FORMAT(m.salida,   '%H:%i') AS hora_salida,
            DATE_FORMAT(m.entrada,  '%Y-%m-%d') AS fecha_entrada,
            m.lat_entrada,
            m.lng_entrada,
            m.lat_salida,
            m.lng_salida
        FROM marcaciones m
        INNER JOIN usuarios u ON m.empleado_id = u.id
        WHERE u.propietario_id = ?
          AND DATE(m.entrada) BETWEEN ? AND ?
          AND (
                (m.lat_entrada IS NOT NULL AND m.lng_entrada IS NOT NULL)
             OR (m.lat_salida  IS NOT NULL AND m.lng_salida  IS NOT NULL)
          )
        ORDER BY m.entrada DESC
        LIMIT 200
    ");
    $stmt->execute([$dueno_id, $fecha_desde, $fecha_hasta]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formatear datos: convertir strings de coords a float, nombre legible
    // Filtrar solo marcaciones dentro de JaÃ©n
    $data = [];
    foreach ($rows as $row) {
        $nombre_completo = trim(
            ($row['nombre']   ? htmlspecialchars($row['nombre'],   ENT_QUOTES, 'UTF-8') : '') . ' ' .
            ($row['apellido'] ? htmlspecialchars($row['apellido'], ENT_QUOTES, 'UTF-8') : '')
        );
        if ($nombre_completo === '') {
            $nombre_completo = htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8');
        }

        $lat_entrada = $row['lat_entrada'] !== null ? (float)$row['lat_entrada'] : null;
        $lng_entrada = $row['lng_entrada'] !== null ? (float)$row['lng_entrada'] : null;
        $lat_salida  = $row['lat_salida']  !== null ? (float)$row['lat_salida']  : null;
        $lng_salida  = $row['lng_salida']  !== null ? (float)$row['lng_salida']  : null;

        $zona_entrada = null;
        $zona_salida = null;
        $dist_entrada = null;
        $dist_salida = null;

        $entrada_valida = isInJaen($lat_entrada, $lng_entrada)
            && isNearAllowedLocation($lat_entrada, $lng_entrada, $zona_entrada, $dist_entrada);
        $salida_valida = isInJaen($lat_salida, $lng_salida)
            && isNearAllowedLocation($lat_salida, $lng_salida, $zona_salida, $dist_salida);

        // Mantener en respuesta solo puntos vÃ¡lidos
        if (!$entrada_valida) {
            $lat_entrada = null;
            $lng_entrada = null;
            $zona_entrada = null;
            $dist_entrada = null;
        }

        if (!$salida_valida) {
            $lat_salida = null;
            $lng_salida = null;
            $zona_salida = null;
            $dist_salida = null;
        }

        // Filtrar: si no hay entrada/salida vÃ¡lida, no mostrar en el mapa
        if ($lat_entrada === null && $lat_salida === null) {
            continue; // Saltar esta marcaciÃ³n, no estÃ¡ en JaÃ©n
        }

        $data[] = [
            'id'           => (int)$row['id'],
            'nombre'       => $nombre_completo,
            'hora_entrada' => $row['hora_entrada'],
            'hora_salida'  => $row['hora_salida'],
            'lat_entrada'  => $lat_entrada,
            'lng_entrada'  => $lng_entrada,
            'lat_salida'   => $lat_salida,
            'lng_salida'   => $lng_salida,
            'zona_entrada' => $zona_entrada,
            'zona_salida'  => $zona_salida,
            'dist_entrada_m' => $dist_entrada !== null ? round($dist_entrada, 1) : null,
            'dist_salida_m'  => $dist_salida !== null ? round($dist_salida, 1) : null,
        ];
    }

    echo json_encode([
        'ok'            => true,
        'fecha_desde'   => $fecha_desde,
        'fecha_hasta'   => $fecha_hasta,
        'total'         => count($data),
        'data'          => $data,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error interno del servidor']);
    error_log('api_mapa.php error: ' . $e->getMessage());
}

