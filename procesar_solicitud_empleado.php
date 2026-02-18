<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'empleado' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: empleado.php');
    exit;
}

$empleado_id = $_SESSION['user_id'];
$id_solicitud = $_POST['id_solicitud'] ?? 0;
$accion = $_POST['accion'] ?? '';

try {
    $stmt = $pdo->prepare("SELECT id FROM solicitudes_cambio WHERE id = ? AND empleado_id = ? AND estado = 'pendiente_empleado'");
    $stmt->execute([$id_solicitud, $empleado_id]);
    $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$solicitud) {
        die('Solicitud no encontrada o no permitida.');
    }

    if ($accion === 'aprobar') {
        $update = $pdo->prepare("UPDATE solicitudes_cambio SET estado = 'aprobado' WHERE id = ?");
        $update->execute([$id_solicitud]);
    } elseif ($accion === 'rechazar') {
        $update = $pdo->prepare("UPDATE solicitudes_cambio SET estado = 'rechazado_empleado' WHERE id = ?");
        $update->execute([$id_solicitud]);
    }

    header('Location: empleado.php?mensaje=respuesta_ok');
    exit;
} catch (Exception $e) {
    die('Error al procesar la respuesta: ' . $e->getMessage());
}
?>
