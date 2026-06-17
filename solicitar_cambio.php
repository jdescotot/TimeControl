<?php
session_start();
require_once 'config.php';

function redirect_to(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function redirect_to_modificacion(int $marcacion_id, string $error): never
{
    $query = http_build_query([
        'id' => $marcacion_id,
        'error' => $error,
    ]);

    redirect_to('modificar_horario.php?' . $query);
}

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'empleado') {
    redirect_to('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $empleado_id = (int)($_SESSION['user_id'] ?? 0);
    $dias_correccion_horario = 7;
    $marcacion_id = (int)($_POST['marcacion_id'] ?? 0);
    $nueva_entrada = trim($_POST['nueva_entrada'] ?? '');
    $nueva_salida = trim($_POST['nueva_salida'] ?? '');
    $motivo = trim($_POST['motivo'] ?? '');
    $solo_salida = ($_POST['solo_salida'] ?? '0') === '1';

    if ($empleado_id <= 0 || $marcacion_id <= 0 || $motivo === '') {
        redirect_to_modificacion($marcacion_id, 'datos_insuficientes');
    }

    if ($solo_salida) {
        if ($nueva_salida === '') {
            redirect_to_modificacion($marcacion_id, 'datos_insuficientes');
        }
        $nueva_entrada = '';
    } else {
        if ($nueva_entrada === '' || $nueva_salida === '') {
            redirect_to_modificacion($marcacion_id, 'datos_insuficientes');
        }
    }

    try {
        $check = $pdo->prepare("SELECT id, entrada FROM marcaciones WHERE id = ? AND empleado_id = ?");
        $check->execute([$marcacion_id, $empleado_id]);
        $marcacion = $check->fetch(PDO::FETCH_ASSOC);
        
        if (!$marcacion) {
            redirect_to_modificacion($marcacion_id, 'marcacion_invalida');
        }

        $fecha_base = date('Y-m-d', strtotime($marcacion['entrada']));
        $fecha_marcacion = new DateTime($fecha_base);
        $fecha_minima_correccion = (new DateTime('today'))
            ->modify("-{$dias_correccion_horario} days");
        $fecha_maxima_correccion = new DateTime('today');

        if ($fecha_marcacion < $fecha_minima_correccion || $fecha_marcacion > $fecha_maxima_correccion) {
            redirect_to_modificacion($marcacion_id, 'fuera_de_rango');
        }

        $stmt_pendiente = $pdo->prepare(
            "SELECT id FROM solicitudes_cambio
             WHERE marcacion_id = ?
               AND empleado_id = ?
               AND estado IN ('pendiente', 'pendiente_empleado')
             ORDER BY id DESC
             LIMIT 1"
        );
        $stmt_pendiente->execute([$marcacion_id, $empleado_id]);
        if ($stmt_pendiente->fetch(PDO::FETCH_ASSOC)) {
            redirect_to_modificacion($marcacion_id, 'solicitud_en_revision');
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
                redirect_to_modificacion($marcacion_id, 'salida_limite');
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
                redirect_to_modificacion($marcacion_id, 'salida_limite');
            }
        }

        $stmt = $pdo->prepare("
            INSERT INTO solicitudes_cambio (marcacion_id, empleado_id, nueva_hora_entrada, nueva_hora_salida, motivo) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$marcacion_id, $empleado_id, $nueva_entrada, $nueva_salida, $motivo]);

        redirect_to('principal.php?mensaje=solicitud_ok');
    } catch (Exception $e) {
        error_log('Error al guardar la solicitud: ' . $e->getMessage());
        redirect_to_modificacion($marcacion_id, 'error_guardado');
    }
} else {
    redirect_to('principal.php');
}
?>