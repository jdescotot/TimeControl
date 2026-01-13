<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'dueño') {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$empleado_id = $_POST['empleado_id'] ?? null;
$fecha_descanso = $_POST['fecha_descanso'] ?? null;
$semana = $_POST['semana'] ?? null;
$año = $_POST['año'] ?? null;

if (!$empleado_id || !$fecha_descanso || !$semana || !$año) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

// Validar que la fecha de descanso esté dentro de la semana y año especificados
$fecha = DateTime::createFromFormat('Y-m-d', $fecha_descanso);
if (!$fecha || (int) $fecha->format('W') != $semana || (int) $fecha->format('Y') != $año) {
    echo json_encode(['success' => false, 'error' => 'Fecha fuera de rango de la semana']);
    exit;
}

try {
    // Usar INSERT ... ON DUPLICATE KEY UPDATE para manejar la restricción única
    $stmt = $pdo->prepare("
        INSERT INTO horarios_semanales (empleado_id, fecha_descanso, semana_año, año) 
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE fecha_descanso = VALUES(fecha_descanso)
    ");
    $stmt->execute([$empleado_id, $fecha_descanso, $semana, $año]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>