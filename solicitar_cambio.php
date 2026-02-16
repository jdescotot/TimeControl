<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'empleado') {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $empleado_id = $_SESSION['user_id'];
    $marcacion_id = $_POST['marcacion_id'] ?? 0;
    $nueva_entrada = trim($_POST['nueva_entrada'] ?? '');
    $nueva_salida = trim($_POST['nueva_salida'] ?? '');
    $motivo = trim($_POST['motivo'] ?? '');
    $solo_salida = ($_POST['solo_salida'] ?? '0') === '1';

    if (empty($marcacion_id) || empty($motivo)) {
        die('Datos insuficientes para la solicitud.');
    }

    if ($solo_salida) {
        if (empty($nueva_salida)) {
            die('Datos insuficientes para la solicitud.');
        }
        $nueva_entrada = '';
    } else {
        if (empty($nueva_entrada) || empty($nueva_salida)) {
            die('Datos insuficientes para la solicitud.');
        }
    }

    try {
        $check = $pdo->prepare("SELECT id FROM marcaciones WHERE id = ? AND empleado_id = ?");
        $check->execute([$marcacion_id, $empleado_id]);
        
        if (!$check->fetch()) {
            die('Operación no permitida.');
        }

        $stmt = $pdo->prepare("
            INSERT INTO solicitudes_cambio (marcacion_id, empleado_id, nueva_hora_entrada, nueva_hora_salida, motivo) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$marcacion_id, $empleado_id, $nueva_entrada, $nueva_salida, $motivo]);

        header('Location: empleado.php?mensaje=solicitud_ok');
        exit;
    } catch (Exception $e) {
        die('Error al guardar la solicitud: ' . $e->getMessage());
    }
} else {
    header('Location: empleado.php');
    exit;
}
?>