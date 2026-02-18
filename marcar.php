<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'empleado') {
    header('Location: index.php');
    exit;
}

$empleado_id = $_SESSION['user_id'];
$hoy = date('Y-m-d');
$ahora = date('Y-m-d H:i:s');
$accion = $_POST['accion'] ?? '';

if (!in_array($accion, ['entrada', 'salida'])) {
    die('Acción inválida');
}

// Verificar si hoy es día de descanso programado
$stmt_descanso = $pdo->prepare("SELECT id FROM horarios_semanales WHERE empleado_id = ? AND fecha_descanso = ?");
$stmt_descanso->execute([$empleado_id, $hoy]);

if ($stmt_descanso->fetch()) {
    die('No puedes marcar en un día programado como descanso.');
}

try {
    if ($accion === 'entrada') {
        // Verificar que no exista una jornada abierta (entrada sin salida)
        $stmt = $pdo->prepare("
            SELECT id, salida FROM marcaciones 
            WHERE empleado_id = ?
            ORDER BY entrada DESC LIMIT 1
        ");
        $stmt->execute([$empleado_id]);
        $ultimo = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($ultimo && empty($ultimo['salida'])) {
            $stmt_pendiente = $pdo->prepare("SELECT id FROM solicitudes_cambio WHERE marcacion_id = ? AND estado IN ('pendiente', 'pendiente_empleado', 'rechazado_empleado') ORDER BY id DESC LIMIT 1");
            $stmt_pendiente->execute([$ultimo['id']]);
            $solicitud_pendiente = $stmt_pendiente->fetch(PDO::FETCH_ASSOC);

            if (!$solicitud_pendiente) {
                $redirect = 'empleado.php?bloqueo=salida_pendiente';
                if (!empty($ultimo['id'])) {
                    $redirect .= '&marcacion_id=' . urlencode((string) $ultimo['id']);
                }
                header('Location: ' . $redirect);
                exit;
            }
        }

        // Insertar nueva marca con entrada
        $stmt = $pdo->prepare("INSERT INTO marcaciones (empleado_id, entrada) VALUES (?, ?)");
        $stmt->execute([$empleado_id, $ahora]);
    } 
    elseif ($accion === 'salida') {
        // Buscar registro de hoy sin salida
        $stmt = $pdo->prepare("
            SELECT id, entrada FROM marcaciones 
            WHERE empleado_id = ? AND salida IS NULL
            ORDER BY entrada DESC LIMIT 1
        ");
        $stmt->execute([$empleado_id]);
        $registro = $stmt->fetch();

        if (!$registro) {
            die('No puedes marcar salida sin tener una jornada abierta.');
        }

        $entrada_dt = new DateTime($registro['entrada']);
        $limite_dt = clone $entrada_dt;
        $limite_dt->modify('+12 hours');
        $ahora_dt = new DateTime($ahora);

        if ($ahora_dt > $limite_dt) {
            $redirect = 'empleado.php?bloqueo=salida_pendiente&mensaje=salida_fuera_rango';
            if (!empty($registro['id'])) {
                $redirect .= '&marcacion_id=' . urlencode((string) $registro['id']);
            }
            header('Location: ' . $redirect);
            exit;
        }

        // Actualizar salida
        $stmt = $pdo->prepare("UPDATE marcaciones SET salida = ? WHERE id = ?");
        $stmt->execute([$ahora, $registro['id']]);
    }

    header('Location: empleado.php?mensaje=success');
    exit;
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}
?>