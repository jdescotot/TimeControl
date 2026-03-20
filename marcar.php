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

if (!in_array($accion, ['entrada', 'salida', 'cerrar_y_entrar'])) {
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
        // Buscar registro sin salida
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

        // Actualizar salida
        $stmt = $pdo->prepare("UPDATE marcaciones SET salida = ? WHERE id = ?");
        $stmt->execute([$ahora, $registro['id']]);
    }
    elseif ($accion === 'cerrar_y_entrar') {
        $marcacion_id_anterior = (int)($_POST['marcacion_id_anterior'] ?? 0);
        $hora_salida           = trim($_POST['hora_salida'] ?? '');
        $dia_salida            = $_POST['dia_salida'] ?? '';

        if (!$marcacion_id_anterior || !preg_match('/^\d{2}:\d{2}$/', $hora_salida) || !in_array($dia_salida, ['mismo', 'siguiente'])) {
            header('Location: empleado.php?bloqueo=salida_pendiente&error=datos_invalidos');
            exit;
        }

        // Verificar que la marcación abierta pertenece al empleado
        $stmt = $pdo->prepare("
            SELECT id, entrada FROM marcaciones
            WHERE id = ? AND empleado_id = ? AND salida IS NULL
        ");
        $stmt->execute([$marcacion_id_anterior, $empleado_id]);
        $marcacion_anterior = $stmt->fetch();

        if (!$marcacion_anterior) {
            header('Location: empleado.php?bloqueo=salida_pendiente&error=datos_invalidos');
            exit;
        }

        $fecha_entrada_base = date('Y-m-d', strtotime($marcacion_anterior['entrada']));
        $fecha_salida_base  = ($dia_salida === 'siguiente')
            ? date('Y-m-d', strtotime($fecha_entrada_base . ' +1 day'))
            : $fecha_entrada_base;
        $salida_completa = $fecha_salida_base . ' ' . $hora_salida . ':00';

        $entrada_dt = new DateTime($marcacion_anterior['entrada']);
        $salida_dt  = new DateTime($salida_completa);
        $ahora_dt   = new DateTime($ahora);

        if ($salida_dt <= $entrada_dt) {
            header('Location: empleado.php?bloqueo=salida_pendiente&marcacion_id=' . $marcacion_id_anterior . '&error=salida_anterior_entrada');
            exit;
        }

        if ($salida_dt > $ahora_dt) {
            header('Location: empleado.php?bloqueo=salida_pendiente&marcacion_id=' . $marcacion_id_anterior . '&error=salida_futuro');
            exit;
        }

        // Cerrar turno anterior
        $stmt = $pdo->prepare("UPDATE marcaciones SET salida = ? WHERE id = ? AND empleado_id = ?");
        $stmt->execute([$salida_completa, $marcacion_id_anterior, $empleado_id]);

        // Registrar nueva entrada
        $stmt = $pdo->prepare("INSERT INTO marcaciones (empleado_id, entrada) VALUES (?, ?)");
        $stmt->execute([$empleado_id, $ahora]);
    }

    header('Location: empleado.php?mensaje=success');
    exit;
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}
?>