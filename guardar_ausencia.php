<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'dueño') {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$empleado_id = $_POST['empleado_id'] ?? null;
$fecha = $_POST['fecha'] ?? null;
$tipo_ausencia = $_POST['tipo_ausencia'] ?? null;
$observaciones = $_POST['observaciones'] ?? '';

if (!$empleado_id || !$fecha || !$tipo_ausencia) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

try {
    // Verificar si ya existe un registro para esta fecha y empleado
    $stmt = $pdo->prepare("SELECT id FROM ausencias_empleados WHERE empleado_id = ? AND fecha = ?");
    $stmt->execute([$empleado_id, $fecha]);
    $existente = $stmt->fetch();

    if ($existente) {
        $stmt = $pdo->prepare("UPDATE ausencias_empleados SET tipo_ausencia = ?, observaciones = ? WHERE id = ?");
        $stmt->execute([$tipo_ausencia, $observaciones, $existente['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO ausencias_empleados (empleado_id, fecha, tipo_ausencia, observaciones) VALUES (?, ?, ?, ?)");
        $stmt->execute([$empleado_id, $fecha, $tipo_ausencia, $observaciones]);
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>