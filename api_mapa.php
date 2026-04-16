<?php
/**
 * API privada: marcaciones con coordenadas GPS para el mapa del dueño.
 * GET api_mapa.php?fecha=YYYY-MM-DD
 * Solo accesible con sesión de rol 'dueño'.
 */
session_start();
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

// Guard de sesión
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'dueño') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Acceso denegado']);
    exit;
}

$dueño_id = (int)$_SESSION['user_id'];

// Validar parámetro fecha (YYYY-MM-DD), default hoy
$fecha_raw = $_GET['fecha'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-(?:0[1-9]|1[0-2])-(?:0[1-9]|[12]\d|3[01])$/', $fecha_raw)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Fecha inválida']);
    exit;
}
$fecha = $fecha_raw;

try {
    // Traer marcaciones del día con al menos un par de coords (entrada o salida)
    // filtradas por empleados que pertenecen al dueño autenticado
    $stmt = $pdo->prepare("
        SELECT
            m.id,
            u.nombre,
            u.apellido,
            u.username,
            DATE_FORMAT(m.entrada,  '%H:%i') AS hora_entrada,
            DATE_FORMAT(m.salida,   '%H:%i') AS hora_salida,
            m.lat_entrada,
            m.lng_entrada,
            m.lat_salida,
            m.lng_salida
        FROM marcaciones m
        INNER JOIN usuarios u ON m.empleado_id = u.id
        WHERE u.propietario_id = ?
          AND DATE(m.entrada)  = ?
          AND (
                (m.lat_entrada IS NOT NULL AND m.lng_entrada IS NOT NULL)
             OR (m.lat_salida  IS NOT NULL AND m.lng_salida  IS NOT NULL)
          )
        ORDER BY m.entrada ASC
    ");
    $stmt->execute([$dueño_id, $fecha]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formatear datos: convertir strings de coords a float, nombre legible
    $data = [];
    foreach ($rows as $row) {
        $nombre_completo = trim(
            ($row['nombre']   ? htmlspecialchars($row['nombre'],   ENT_QUOTES, 'UTF-8') : '') . ' ' .
            ($row['apellido'] ? htmlspecialchars($row['apellido'], ENT_QUOTES, 'UTF-8') : '')
        );
        if ($nombre_completo === '') {
            $nombre_completo = htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8');
        }

        $data[] = [
            'id'           => (int)$row['id'],
            'nombre'       => $nombre_completo,
            'hora_entrada' => $row['hora_entrada'],
            'hora_salida'  => $row['hora_salida'],
            'lat_entrada'  => $row['lat_entrada'] !== null ? (float)$row['lat_entrada'] : null,
            'lng_entrada'  => $row['lng_entrada'] !== null ? (float)$row['lng_entrada'] : null,
            'lat_salida'   => $row['lat_salida']  !== null ? (float)$row['lat_salida']  : null,
            'lng_salida'   => $row['lng_salida']  !== null ? (float)$row['lng_salida']  : null,
        ];
    }

    echo json_encode([
        'ok'    => true,
        'fecha' => $fecha,
        'total' => count($data),
        'data'  => $data,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error interno del servidor']);
    error_log('api_mapa.php error: ' . $e->getMessage());
}
