<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'empleado') {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $empleado_id = $_SESSION['user_id'];
    $dias_correccion_horario = 7;
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
        $check = $pdo->prepare("SELECT id, entrada FROM marcaciones WHERE id = ? AND empleado_id = ?");
        $check->execute([$marcacion_id, $empleado_id]);
        $marcacion = $check->fetch(PDO::FETCH_ASSOC);
        
        if (!$marcacion) {
            die('Operación no permitida.');
        }

        $fecha_base = date('Y-m-d', strtotime($marcacion['entrada']));
        $fecha_marcacion = new DateTime($fecha_base);
        $fecha_minima_correccion = (new DateTime('today'))
            ->modify("-{$dias_correccion_horario} days");
        $fecha_maxima_correccion = new DateTime('today');

        if ($fecha_marcacion < $fecha_minima_correccion || $fecha_marcacion > $fecha_maxima_correccion) {
            die('Solo puedes solicitar correcciones dentro del rango permitido de fechas.');
        }

        if ($solo_salida) {
            $entrada_dt = new DateTime($marcacion['entrada']);
            $salida_dt = new DateTime($fecha_base . ' ' . $nueva_salida);
            if ($salida_dt < $entrada_dt) {
                $salida_dt->modify('+1 day');
            }
            $limite_dt = clone $entrada_dt;
            $limite_dt->modify('+19 hours');
            if ($salida_dt > $limite_dt) {
                die('La hora de salida no puede exceder 19 horas desde la entrada.');
            }
        } else {
            $entrada_dt = new DateTime($fecha_base . ' ' . $nueva_entrada);
            $salida_dt = new DateTime($fecha_base . ' ' . $nueva_salida);
            if ($salida_dt < $entrada_dt) {
                $salida_dt->modify('+1 day');
            }
            $limite_dt = clone $entrada_dt;
            $limite_dt->modify('+19 hours');
            if ($salida_dt > $limite_dt) {
                die('La hora de salida no puede exceder 19 horas desde la entrada.');
            }
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