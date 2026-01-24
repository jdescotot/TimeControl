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
$accion = $_POST['accion'] ?? 'agregar'; // 'agregar' o 'eliminar'

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
    if ($accion === 'eliminar') {
        // Eliminar el día de descanso
        $stmt = $pdo->prepare("
            DELETE FROM horarios_semanales 
            WHERE empleado_id = ? AND fecha_descanso = ? AND semana_año = ? AND año = ?
        ");
        $stmt->execute([$empleado_id, $fecha_descanso, $semana, $año]);
    } else {
        // Verificar si ya existe antes de insertar
        $stmt_check = $pdo->prepare("
            SELECT id FROM horarios_semanales 
            WHERE empleado_id = ? AND fecha_descanso = ? AND semana_año = ? AND año = ?
        ");
        $stmt_check->execute([$empleado_id, $fecha_descanso, $semana, $año]);
        
        if (!$stmt_check->fetch()) {
            // Solo insertar si no existe
            $stmt = $pdo->prepare("
                INSERT INTO horarios_semanales (empleado_id, fecha_descanso, semana_año, año) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$empleado_id, $fecha_descanso, $semana, $año]);
        }
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>