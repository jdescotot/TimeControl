<?php
session_start();
require_once 'config.php';

// Solo el dueño puede procesar estas acciones
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'dueño' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dueño.php');
    exit;
}

$id_solicitud = $_POST['id_solicitud'] ?? 0;
$accion = $_POST['accion'] ?? '';

try {
    $pdo->beginTransaction();

    // Obtener los detalles de la solicitud
    $stmt = $pdo->prepare("SELECT * FROM solicitudes_cambio WHERE id = ?");
    $stmt->execute([$id_solicitud]);
    $solicitud = $stmt->fetch();

    if ($solicitud) {
        if ($accion === 'aprobar') {
            // Marcar la solicitud como aprobada (SIN modificar la tabla marcaciones)
            // Las horas originales se preservan, solo se marca la solicitud como aprobada
            $update_estado = $pdo->prepare("UPDATE solicitudes_cambio SET estado = 'aprobado' WHERE id = ?");
            $update_estado->execute([$id_solicitud]);
        } elseif ($accion === 'rechazar') {
            // Simplemente marcar como rechazada
            $update_estado = $pdo->prepare("UPDATE solicitudes_cambio SET estado = 'rechazado' WHERE id = ?");
            $update_estado->execute([$id_solicitud]);
        }
    }

    $pdo->commit();
    header('Location: gestionar_solicitudes.php?mensaje=procesado_ok');
} catch (Exception $e) {
    $pdo->rollBack();
    die("Error al procesar la solicitud: " . $e->getMessage());
}
?>