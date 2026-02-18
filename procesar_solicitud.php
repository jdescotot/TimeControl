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
$propuesta_entrada = trim($_POST['nueva_entrada'] ?? '');
$propuesta_salida = trim($_POST['nueva_salida'] ?? '');

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
        } elseif ($accion === 'proponer') {
            if (empty($propuesta_salida)) {
                die('Debe indicar una hora de salida para la propuesta.');
            }

            $stmt_marcacion = $pdo->prepare("SELECT entrada FROM marcaciones WHERE id = ?");
            $stmt_marcacion->execute([$solicitud['marcacion_id']]);
            $marcacion = $stmt_marcacion->fetch(PDO::FETCH_ASSOC);

            if (!$marcacion) {
                die('Marcación no encontrada.');
            }

            $fecha_base = date('Y-m-d', strtotime($marcacion['entrada']));
            $entrada_dt = $propuesta_entrada !== ''
                ? new DateTime($fecha_base . ' ' . $propuesta_entrada)
                : new DateTime($marcacion['entrada']);
            $salida_dt = new DateTime($fecha_base . ' ' . $propuesta_salida);
            if ($salida_dt < $entrada_dt) {
                $salida_dt->modify('+1 day');
            }
            $limite_dt = clone $entrada_dt;
            $limite_dt->modify('+12 hours');
            if ($salida_dt > $limite_dt) {
                die('La hora de salida no puede exceder 12 horas desde la entrada.');
            }

            $update_estado = $pdo->prepare("UPDATE solicitudes_cambio SET nueva_hora_entrada = ?, nueva_hora_salida = ?, estado = 'pendiente_empleado' WHERE id = ?");
            $update_estado->execute([$propuesta_entrada, $propuesta_salida, $id_solicitud]);
        }
    }

    $pdo->commit();
    header('Location: gestionar_solicitudes.php?mensaje=procesado_ok');
} catch (Exception $e) {
    $pdo->rollBack();
    die("Error al procesar la solicitud: " . $e->getMessage());
}
?>