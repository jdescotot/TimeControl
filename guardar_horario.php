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

// Validar formato de fecha
$fecha = DateTime::createFromFormat('Y-m-d', $fecha_descanso);
if (!$fecha) {
    echo json_encode(['success' => false, 'error' => 'Formato de fecha inválido']);
    exit;
}

try {
    if ($accion === 'eliminar') {
        // Eliminar el día de descanso
        $stmt = $pdo->prepare("
            DELETE FROM horarios_semanales 
            WHERE empleado_id = ? AND fecha_descanso = ?
        ");
        $stmt->execute([$empleado_id, $fecha_descanso]);
    } else {
        // Verificar si ya existe antes de insertar (solo por empleado y fecha)
        $stmt_check = $pdo->prepare("
            SELECT id FROM horarios_semanales 
            WHERE empleado_id = ? AND fecha_descanso = ?
        ");
        $stmt_check->execute([$empleado_id, $fecha_descanso]);
        
        if (!$stmt_check->fetch()) {
            // Solo insertar si no existe
            $stmt = $pdo->prepare("
                INSERT INTO horarios_semanales (empleado_id, fecha_descanso, semana_año, año) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$empleado_id, $fecha_descanso, $semana, $año]);
        } else {
            // Ya existe, considerarlo como éxito
            echo json_encode(['success' => true, 'message' => 'Ya existe']);
            exit;
        }
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    // Log detallado del error
    error_log("Error en horarios_semanales: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage(),
        'code' => $e->getCode(),
        'debug' => [
            'empleado_id' => $empleado_id,
            'fecha_descanso' => $fecha_descanso,
            'semana' => $semana,
            'año' => $año
        ]
    ]);
}
?>