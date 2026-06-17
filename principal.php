<?php
declare(strict_types=1);

session_start();
require_once 'config.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'empleado') {
    header('Location: index.php');
    exit;
}

$empleado_id = (int)($_SESSION['user_id'] ?? 0);

if ($empleado_id <= 0) {
    header('Location: index.php');
    exit;
}

$dias_correccion_horario = 7;

function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function columnas_perfil_usuario(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios'");
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $perfil = [];

    foreach (['apellido', 'telefono', 'correo'] as $col) {
        if (in_array($col, $cols, true)) {
            $perfil[] = $col;
        }
    }

    if (!in_array('correo', $perfil, true) && in_array('email', $cols, true)) {
        $perfil[] = 'email';
    }

    return $perfil;
}

function perfil_incompleto(PDO $pdo, int $usuario_id): bool
{
    $cols = columnas_perfil_usuario($pdo);

    if (empty($cols)) {
        return false;
    }

    $sql = 'SELECT ' . implode(', ', $cols) . ' FROM usuarios WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$usuario_id]);
    $datos = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$datos) {
        return true;
    }

    foreach ($cols as $col) {
        if ($datos[$col] === null || $datos[$col] === '') {
            return true;
        }
    }

    return false;
}

function segundos_a_reloj(int $segundos): string
{
    $segundos = max(0, $segundos);
    $h = str_pad((string)floor($segundos / 3600), 2, '0', STR_PAD_LEFT);
    $m = str_pad((string)floor(($segundos % 3600) / 60), 2, '0', STR_PAD_LEFT);
    $s = str_pad((string)($segundos % 60), 2, '0', STR_PAD_LEFT);

    return $h . ':' . $m . ':' . $s;
}

function segundos_a_compacto(int $segundos): string
{
    $segundos = max(0, $segundos);

    $horas = intdiv($segundos, 3600);
    $minutos = intdiv($segundos % 3600, 60);
    $resto = $segundos % 60;

    if ($horas > 0) {
        return $horas . ' h ' . $minutos . ' min';
    }

    if ($minutos > 0) {
        return $minutos . ' min';
    }

    return $resto . ' s';
}

function fecha_chip_es(DateTimeInterface $fecha): string
{
    $meses = ['ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC'];
    $mes = $meses[(int)$fecha->format('n') - 1] ?? strtoupper($fecha->format('M'));

    return $fecha->format('d') . ' ' . $mes . ' ' . $fecha->format('Y');
}

function fecha_humana_es(string $fecha, string $hoy): string
{
    $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    $dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];

    $fecha_dt = DateTimeImmutable::createFromFormat('Y-m-d', $fecha) ?: new DateTimeImmutable($fecha);
    $mes = $meses[(int)$fecha_dt->format('n') - 1] ?? $fecha_dt->format('m');
    $dia_numero = (int)$fecha_dt->format('j');

    if ($fecha === $hoy) {
        return 'Hoy · ' . $dia_numero . ' ' . $mes;
    }

    $ayer = (new DateTimeImmutable($hoy))->modify('-1 day')->format('Y-m-d');

    if ($fecha === $ayer) {
        return 'Ayer · ' . $dia_numero . ' ' . $mes;
    }

    $dia_semana = $dias[(int)$fecha_dt->format('w')] ?? $fecha_dt->format('d/m/Y');

    return $dia_semana . ' · ' . $dia_numero . ' ' . $mes;
}

function iniciales_usuario(string $nombre): string
{
    $partes = preg_split('/\s+/', trim($nombre)) ?: [];
    $iniciales = '';

    foreach ($partes as $parte) {
        if ($parte === '') {
            continue;
        }

        $iniciales .= strtoupper(substr($parte, 0, 1));

        if (strlen($iniciales) >= 2) {
            break;
        }
    }

    if ($iniciales === '') {
        $limpio = preg_replace('/\s+/', '', $nombre) ?? '';
        $iniciales = strtoupper(substr($limpio, 0, 2));
    }

    return $iniciales !== '' ? $iniciales : 'US';
}

$stmt_nombre = $pdo->prepare('SELECT nombre FROM usuarios WHERE id = ? LIMIT 1');
$stmt_nombre->execute([$empleado_id]);
$usuario_data = $stmt_nombre->fetch(PDO::FETCH_ASSOC);

$nombre_mostrar = trim((string)($usuario_data['nombre'] ?? ''));

if ($nombre_mostrar === '') {
    $nombre_mostrar = (string)($_SESSION['username'] ?? 'Usuario');
}

$flag_stmt = $pdo->prepare('SELECT requiere_cambio_password FROM usuarios WHERE id = ? LIMIT 1');
$flag_stmt->execute([$empleado_id]);
$flag = $flag_stmt->fetch(PDO::FETCH_ASSOC);

if ($flag && (int)$flag['requiere_cambio_password'] === 1) {
    header('Location: cambiar_password.php');
    exit;
}

if (perfil_incompleto($pdo, $empleado_id)) {
    header('Location: completar_perfil.php');
    exit;
}

$hoy = date('Y-m-d');
$ahora = new DateTimeImmutable('now');
$fecha_minima_correccion = (new DateTimeImmutable($hoy))
    ->modify('-' . $dias_correccion_horario . ' days')
    ->format('Y-m-d');

$stmt_ultimo = $pdo->prepare('
    SELECT id, entrada, salida
    FROM marcaciones
    WHERE empleado_id = ?
    ORDER BY entrada DESC
    LIMIT 1
');
$stmt_ultimo->execute([$empleado_id]);
$registro_hoy = $stmt_ultimo->fetch(PDO::FETCH_ASSOC) ?: null;

$tiene_registro_hoy = $registro_hoy && date('Y-m-d', strtotime((string)$registro_hoy['entrada'])) === $hoy;
$jornada_abierta = $registro_hoy && !empty($registro_hoy['entrada']) && empty($registro_hoy['salida']);
$ultimo_cerrado = $registro_hoy && !empty($registro_hoy['salida']);
$entrada_fecha = $registro_hoy && !empty($registro_hoy['entrada']) ? date('Y-m-d', strtotime((string)$registro_hoy['entrada'])) : null;
$entrada_es_hoy = $entrada_fecha === $hoy;
$entrada_es_ayer = $entrada_fecha === date('Y-m-d', strtotime('-1 day'));
$bloqueo_flag = isset($_GET['bloqueo']) && $_GET['bloqueo'] === 'salida_pendiente';
$estado_solicitud_pendiente = null;
$tiene_solicitud_pendiente = false;

if ($jornada_abierta && !empty($registro_hoy['id'])) {
    $stmt_pendiente = $pdo->prepare("SELECT estado FROM solicitudes_cambio WHERE marcacion_id = ? AND estado IN ('pendiente', 'pendiente_empleado', 'rechazado_empleado') ORDER BY id DESC LIMIT 1");
    $stmt_pendiente->execute([$registro_hoy['id']]);
    $solicitud_pendiente = $stmt_pendiente->fetch(PDO::FETCH_ASSOC);

    if ($solicitud_pendiente) {
        $tiene_solicitud_pendiente = true;
        $estado_solicitud_pendiente = (string)$solicitud_pendiente['estado'];
    }
}

$bloqueo_salida_pendiente = $jornada_abierta && ((!$entrada_es_hoy && !$entrada_es_ayer) || $bloqueo_flag) && !$tiene_solicitud_pendiente;

$entrada_dt_form = null;
$siguiente_dt_form = null;

if ($bloqueo_salida_pendiente && !empty($registro_hoy['entrada'])) {
    $entrada_dt_form = new DateTimeImmutable((string)$registro_hoy['entrada']);
    $siguiente_dt_form = $entrada_dt_form->modify('+1 day');
}

$stmt_pendientes_empleado = $pdo->prepare('
    SELECT s.id, s.nueva_hora_entrada, s.nueva_hora_salida, s.motivo, s.fecha_solicitud,
           m.entrada, m.salida, DATE(m.entrada) as fecha
    FROM solicitudes_cambio s
    JOIN marcaciones m ON s.marcacion_id = m.id
    WHERE s.empleado_id = ? AND s.estado = "pendiente_empleado"
    ORDER BY s.fecha_solicitud DESC
');
$stmt_pendientes_empleado->execute([$empleado_id]);
$solicitudes_empleado = $stmt_pendientes_empleado->fetchAll(PDO::FETCH_ASSOC);

$stmt_historial = $pdo->prepare('
    SELECT m.id, DATE(m.entrada) as fecha, m.entrada, m.salida,
           sc.nueva_hora_entrada, sc.nueva_hora_salida, sc.motivo
    FROM marcaciones m
    LEFT JOIN solicitudes_cambio sc ON m.id = sc.marcacion_id AND sc.estado = "aprobado"
    WHERE m.empleado_id = ?
    ORDER BY m.entrada DESC
');
$stmt_historial->execute([$empleado_id]);
$marcaciones = $stmt_historial->fetchAll(PDO::FETCH_ASSOC);

$historial = [];
$segundos_semana = 0;
$ultima_jornada_segundos = null;
$inicio_semana = (new DateTimeImmutable('monday this week'))->setTime(0, 0, 0);
$fin_semana = $inicio_semana->modify('+7 days');

foreach ($marcaciones as $fila) {
    $fecha_base = (string)($fila['fecha'] ?? '');
    $entrada_dt = !empty($fila['entrada']) ? new DateTimeImmutable((string)$fila['entrada']) : null;
    $salida_dt = !empty($fila['salida']) ? new DateTimeImmutable((string)$fila['salida']) : null;

    $entrada_calculo = $entrada_dt;
    $salida_calculo = $salida_dt;

    if (!empty($fila['nueva_hora_entrada']) && $fecha_base !== '') {
        $entrada_calculo = new DateTimeImmutable($fecha_base . ' ' . substr((string)$fila['nueva_hora_entrada'], 0, 8));
    }

    if (!empty($fila['nueva_hora_salida']) && $fecha_base !== '') {
        $salida_calculo = new DateTimeImmutable($fecha_base . ' ' . substr((string)$fila['nueva_hora_salida'], 0, 8));
    }

    $duracion_segundos = null;

    if ($entrada_calculo && $salida_calculo) {
        if ($salida_calculo < $entrada_calculo) {
            $salida_calculo = $salida_calculo->modify('+1 day');
        }

        $duracion_segundos = max(0, $salida_calculo->getTimestamp() - $entrada_calculo->getTimestamp());
    }

    if ($entrada_dt && $entrada_dt >= $inicio_semana && $entrada_dt < $fin_semana) {
        $salida_semana = $salida_dt ?? $ahora;

        if ($salida_semana < $entrada_dt) {
            $salida_semana = $salida_semana->modify('+1 day');
        }

        $segundos_semana += max(0, $salida_semana->getTimestamp() - $entrada_dt->getTimestamp());
    }

    if ($ultima_jornada_segundos === null && $duracion_segundos !== null && $salida_dt) {
        $ultima_jornada_segundos = $duracion_segundos;
    }

    $entrada_label = '—';
    if ($entrada_calculo instanceof DateTimeImmutable) {
        $entrada_label = $entrada_calculo->format('H:i');
        if (!empty($fila['nueva_hora_entrada'])) {
            $entrada_label .= ' (aj.)';
        }
    } elseif ($entrada_dt instanceof DateTimeImmutable) {
        $entrada_label = $entrada_dt->format('H:i');
    }

    $salida_label = 'En curso';
    if ($salida_calculo instanceof DateTimeImmutable) {
        $salida_label = $salida_calculo->format('H:i');
        if (!empty($fila['nueva_hora_salida'])) {
            $salida_label .= ' (aj.)';
        }
    } elseif ($salida_dt instanceof DateTimeImmutable) {
        $salida_label = $salida_dt->format('H:i');
    }

    $duracion_label = 'Jornada activa';
    if ($duracion_segundos !== null) {
        $duracion_label = segundos_a_compacto($duracion_segundos);
    }

    $puede_corregir = $fecha_base >= $fecha_minima_correccion && $fecha_base <= $hoy;
    $solo_salida = empty($fila['salida']);

    $historial[] = [
        'id' => (int)$fila['id'],
        'fecha_humana' => fecha_humana_es($fecha_base, $hoy),
        'entrada_label' => $entrada_label,
        'salida_label' => $salida_label,
        'duracion_label' => $duracion_label,
        'duracion_warning' => $solo_salida || ($duracion_segundos !== null && $duracion_segundos >= 11 * 3600),
        'puede_corregir' => $puede_corregir,
        'entrada_raw' => $entrada_dt ? $entrada_dt->format('H:i:s') : '',
        'salida_raw' => $salida_dt ? $salida_dt->format('H:i:s') : '',
        'solo_salida' => $solo_salida,
    ];
}

$objetivo_semana = 40 * 3600;
$porcentaje_semana = (int)min(100, round(($segundos_semana / max(1, $objetivo_semana)) * 100));
$semana_label = segundos_a_compacto($segundos_semana);
$ultima_label = $ultima_jornada_segundos !== null ? segundos_a_compacto($ultima_jornada_segundos) : 'Sin datos';

$timer_seconds = 0;

if ($jornada_abierta && !empty($registro_hoy['entrada'])) {
    $entrada_dt_timer = new DateTimeImmutable((string)$registro_hoy['entrada']);
    $timer_seconds = max(0, $ahora->getTimestamp() - $entrada_dt_timer->getTimestamp());
} elseif ($tiene_registro_hoy && !empty($registro_hoy['entrada']) && !empty($registro_hoy['salida'])) {
    $entrada_dt_timer = new DateTimeImmutable((string)$registro_hoy['entrada']);
    $salida_dt_timer = new DateTimeImmutable((string)$registro_hoy['salida']);

    if ($salida_dt_timer < $entrada_dt_timer) {
        $salida_dt_timer = $salida_dt_timer->modify('+1 day');
    }

    $timer_seconds = max(0, $salida_dt_timer->getTimestamp() - $entrada_dt_timer->getTimestamp());
}

$mostrar_boton_salida = $jornada_abierta && !$tiene_solicitud_pendiente && !$bloqueo_salida_pendiente;
$mostrar_boton_entrada = !$mostrar_boton_salida && !$bloqueo_salida_pendiente;
$texto_entrada = (!$tiene_registro_hoy && !$jornada_abierta) ? 'Marcar entrada' : 'Marcar nueva entrada';

$estado_jornada = 'Listo para fichar';

if ($mostrar_boton_salida) {
    $estado_jornada = 'Jornada activa';
} elseif ($bloqueo_salida_pendiente) {
    $estado_jornada = 'Salida pendiente';
} elseif ($jornada_abierta && $tiene_solicitud_pendiente) {
    $estado_jornada = 'Solicitud en revisión';
} elseif ($tiene_registro_hoy || $ultimo_cerrado) {
    $estado_jornada = 'Jornada cerrada';
}

$meta_entrada = !empty($registro_hoy['entrada'])
    ? 'Entrada ' . date('H:i:s', strtotime((string)$registro_hoy['entrada']))
    : 'Sin entrada registrada';

$meta_estado = 'Sin jornada activa';

if ($jornada_abierta) {
    $meta_estado = $bloqueo_salida_pendiente
        ? 'Pendiente de regularizar'
        : ($tiene_solicitud_pendiente ? 'Con solicitud en revisión' : 'Turno en curso');
} elseif (!empty($registro_hoy['salida'])) {
    $meta_estado = 'Salida ' . date('H:i:s', strtotime((string)$registro_hoy['salida']));
}

$hora_actual = (int)date('G');
$saludo = $hora_actual < 12 ? 'Buenos días' : ($hora_actual < 20 ? 'Buenas tardes' : 'Buenas noches');
$iniciales = iniciales_usuario($nombre_mostrar);

$alertas = [];

if (isset($_GET['perfil_actualizado'])) {
    $alertas[] = ['tipo' => 'success', 'texto' => 'Datos de seguridad actualizados.'];
}

if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'success') {
    $alertas[] = ['tipo' => 'success', 'texto' => 'Marcación registrada correctamente.'];
}

if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'solicitud_ok') {
    $alertas[] = ['tipo' => 'success', 'texto' => 'Solicitud enviada correctamente.'];
}

if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'respuesta_ok') {
    $alertas[] = ['tipo' => 'success', 'texto' => 'Respuesta enviada correctamente.'];
}

if (isset($_GET['error'])) {
    $errores = [
        'salida_anterior_entrada' => 'La hora de salida no puede ser anterior o igual a la hora de entrada.',
        'salida_futuro' => 'La hora de salida no puede ser en el futuro.',
        'datos_invalidos' => 'No se han recibido datos válidos para cerrar la jornada pendiente.',
    ];

    $key = (string)$_GET['error'];
    if (isset($errores[$key])) {
        $alertas[] = ['tipo' => 'warning', 'texto' => $errores[$key]];
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#064b3d">
  <meta name="description" content="Panel de marcación de jornada">
  <title>Control Horario · Panel del empleado</title>
  <link rel="stylesheet" href="principal.css">
</head>
<body>
  <main class="app-shell">
    <header class="topbar">
      <div class="brand" aria-label="Control Horario">
        <div class="brand-mark" aria-hidden="true">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8.6"></circle><path d="M12 7.5v5l3.2 2"></path></svg>
        </div>
        <div class="brand-copy">
          <p class="brand-title">Control Horario</p>
          <p class="brand-kicker">Panel del empleado</p>
        </div>
      </div>

      <div class="profile" aria-label="Perfil de <?= e($nombre_mostrar) ?>">
        <div class="profile-copy"><small><?= e($saludo) ?></small><strong><?= e($nombre_mostrar) ?></strong></div>
        <div class="avatar" aria-hidden="true"><?= e($iniciales) ?></div>
        <a class="logout-chip" href="logout.php" aria-label="Cerrar sesión">
          <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><path d="M16 17l5-5-5-5"></path><path d="M21 12H9"></path></svg>
          <span>Salir</span>
        </a>
      </div>
    </header>

    <?php if (!empty($alertas)): ?>
      <section class="alerts" aria-label="Mensajes del sistema">
        <?php foreach ($alertas as $alerta): ?>
          <article class="status-banner <?= e($alerta['tipo']) ?>">
            <span class="status-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"></circle><path d="M12 8v5"></path><path d="M12 16h.01"></path></svg>
            </span>
            <p><?= e($alerta['texto']) ?></p>
          </article>
        <?php endforeach; ?>
      </section>
    <?php endif; ?>

    <section class="grid" aria-label="Resumen de jornada">
      <article class="hero">
        <div>
          <div class="hero-top">
            <div>
              <p class="eyebrow"><span class="status-dot"></span> <?= e($estado_jornada) ?></p>
              <h1>Tu tiempo,<br>todo bajo control.</h1>
            </div>
            <span class="date-chip"><?= e(fecha_chip_es($ahora)) ?></span>
          </div>

          <p class="timer-label">Tiempo trabajado hoy</p>
          <p class="timer" id="timer" data-running="<?= $jornada_abierta ? '1' : '0' ?>" data-seconds="<?= (int)$timer_seconds ?>" aria-live="polite"><?= e(segundos_a_reloj($timer_seconds)) ?></p>

          <div class="hero-meta">
            <span><svg viewBox="0 0 24 24"><path d="M12 7v5l3 2"></path><circle cx="12" cy="12" r="9"></circle></svg> <?= e($meta_entrada) ?></span>
            <span><svg viewBox="0 0 24 24"><path d="M4 12h16"></path><path d="M12 4v16"></path></svg> <?= e($meta_estado) ?></span>
          </div>
        </div>

        <div class="hero-actions">
          <?php if ($mostrar_boton_salida): ?>
            <button class="primary-btn js-clockout" type="button" data-form-id="salidaForm" aria-label="Finalizar jornada actual">
              <svg viewBox="0 0 24 24"><path d="M10 17l5-5-5-5"></path><path d="M15 12H3"></path><path d="M14 4h5a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-5"></path></svg>
              Finalizar jornada
            </button>
          <?php elseif ($mostrar_boton_entrada): ?>
            <form action="marcar.php" method="POST" class="inline-form">
              <input type="hidden" name="accion" value="entrada">
              <button class="primary-btn" type="submit" aria-label="<?= e($texto_entrada) ?>">
                <svg viewBox="0 0 24 24"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><path d="M10 17l5-5-5-5"></path><path d="M15 12H3"></path></svg>
                <?= e($texto_entrada) ?>
              </button>
            </form>
          <?php else: ?>
            <button class="primary-btn" type="button" data-scroll-target="bloqueo-salida">
              <svg viewBox="0 0 24 24"><path d="M12 7v5l3 2"></path><circle cx="12" cy="12" r="9"></circle></svg>
              Resolver salida pendiente
            </button>
          <?php endif; ?>

          <button class="secondary-btn" type="button" data-scroll-target="history">
            Ver historial
          </button>
        </div>
      </article>

      <aside class="metrics" aria-label="Indicadores rápidos">
        <article class="metric">
          <p class="metric-label">Esta semana</p>
          <p class="metric-value"><?= e($semana_label) ?></p>
          <div class="progress" aria-label="<?= (int)$porcentaje_semana ?>% de la jornada semanal"><span style="width: <?= (int)$porcentaje_semana ?>%"></span></div>
          <p class="metric-note"><?= (int)$porcentaje_semana ?>% de tu objetivo semanal (40 h).</p>
        </article>

        <article class="metric">
          <p class="metric-label">Última jornada cerrada</p>
          <p class="metric-value"><?= e($ultima_label) ?></p>
          <p class="metric-note">Duración de la última jornada completada.</p>
        </article>

        <article class="metric wide" id="bloqueo-salida">
          <?php if ($bloqueo_salida_pendiente && $entrada_dt_form && $siguiente_dt_form): ?>
            <div class="notice warning">
              <span class="notice-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5"></path><path d="M12 16h.01"></path></svg></span>
              <div>
                <strong>Tienes una jornada pendiente de cerrar</strong>
                <p>Entrada registrada: <?= e($entrada_dt_form->format('d/m/Y H:i')) ?>. Debes informar la salida para continuar.</p>
              </div>
            </div>

            <form action="marcar.php" method="POST" class="block-form">
              <input type="hidden" name="accion" value="cerrar_y_entrar">
              <input type="hidden" name="marcacion_id_anterior" value="<?= (int)($registro_hoy['id'] ?? 0) ?>">

              <div class="block-field">
                <label for="hora_salida">Hora de salida</label>
                <input id="hora_salida" type="time" name="hora_salida" required>
              </div>

              <div class="block-field">
                <span class="label">¿Qué día fue la salida?</span>
                <div class="radio-group">
                  <label class="radio-option">
                    <input type="radio" name="dia_salida" value="mismo" checked>
                    <span><?= e($entrada_dt_form->format('d/m/Y')) ?> · mismo día</span>
                  </label>
                  <label class="radio-option">
                    <input type="radio" name="dia_salida" value="siguiente">
                    <span><?= e($siguiente_dt_form->format('d/m/Y')) ?> · día siguiente</span>
                  </label>
                </div>
              </div>

              <button class="primary-btn" type="submit">
                <svg viewBox="0 0 24 24"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><path d="M10 17l5-5-5-5"></path><path d="M15 12H3"></path></svg>
                Confirmar salida y marcar nueva entrada
              </button>
            </form>
          <?php elseif ($tiene_solicitud_pendiente): ?>
            <div class="notice">
              <span class="notice-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"></circle><path d="M12 11v5"></path><path d="M12 8h.01"></path></svg></span>
              <div>
                <strong>Solicitud en revisión</strong>
                <p>Estado actual: <?= e($estado_solicitud_pendiente ?? 'pendiente') ?>. Puedes registrar una nueva entrada.</p>
              </div>
            </div>
          <?php else: ?>
            <div class="notice">
              <span class="notice-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"></circle><path d="M12 11v5"></path><path d="M12 8h.01"></path></svg></span>
              <div>
                <strong>Correcciones disponibles</strong>
                <p>Puedes modificar marcaciones desde el <?= e(date('d/m/Y', strtotime($fecha_minima_correccion))) ?> hasta hoy.</p>
              </div>
            </div>
            <p class="metric-note note-muted">Usa el botón de edición en el historial para abrir una solicitud.</p>
          <?php endif; ?>
        </article>
      </aside>
    </section>

    <?php if (!empty($solicitudes_empleado)): ?>
      <section class="pending-card" aria-labelledby="pending-title">
        <div class="pending-head">
          <h2 id="pending-title">Propuestas del dueño</h2>
          <p><?= count($solicitudes_empleado) ?> pendiente<?= count($solicitudes_empleado) !== 1 ? 's' : '' ?></p>
        </div>

        <div class="pending-list">
          <?php foreach ($solicitudes_empleado as $solicitud): ?>
            <article class="pending-row">
              <div class="pending-meta">
                <strong><?= e(date('d/m/Y', strtotime((string)$solicitud['fecha']))) ?></strong>
                <span>
                  Propuesta: E <?= e(!empty($solicitud['nueva_hora_entrada']) ? substr((string)$solicitud['nueva_hora_entrada'], 0, 5) : '—') ?>
                  · S <?= e(!empty($solicitud['nueva_hora_salida']) ? substr((string)$solicitud['nueva_hora_salida'], 0, 5) : '—') ?>
                </span>
                <p><?= e((string)$solicitud['motivo']) ?></p>
              </div>

              <form action="procesar_solicitud_empleado.php" method="POST" class="pending-actions">
                <input type="hidden" name="id_solicitud" value="<?= (int)$solicitud['id'] ?>">
                <button type="submit" name="accion" value="aprobar" class="pill-btn approve">Aprobar</button>
                <button type="submit" name="accion" value="rechazar" class="pill-btn reject">Rechazar</button>
              </form>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>

    <section class="history" id="history" aria-labelledby="history-title">
      <div class="section-head">
        <div>
          <h2 id="history-title">Historial reciente</h2>
          <p>Entradas, salidas y total trabajado.</p>
        </div>
        <button class="filter-btn" type="button" data-scroll-target="history-title" aria-label="Ir al historial">
          <svg viewBox="0 0 24 24"><path d="M4 7h16"></path><path d="M7 12h10"></path><path d="M10 17h4"></path></svg>
          Historial
        </button>
      </div>

      <div class="history-list">
        <?php if (empty($historial)): ?>
          <article class="empty-state">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><path d="M12 8v4"></path><path d="M12 16h.01"></path></svg>
            <p>No hay marcaciones registradas.</p>
          </article>
        <?php else: ?>
          <?php foreach ($historial as $fila_historial): ?>
            <article class="history-row">
              <div class="row-main">
                <p class="row-date"><?= e($fila_historial['fecha_humana']) ?></p>
              </div>

              <div class="row-times">
                <span><svg viewBox="0 0 24 24"><path d="M12 7v5l3 2"></path><circle cx="12" cy="12" r="9"></circle></svg> <?= e($fila_historial['entrada_label']) ?></span>
                <span><svg viewBox="0 0 24 24"><path d="M12 7v5l3 2"></path><circle cx="12" cy="12" r="9"></circle></svg> <?= e($fila_historial['salida_label']) ?></span>
              </div>

              <p class="row-duration">
                <span class="duration-pill <?= $fila_historial['duracion_warning'] ? 'warning' : '' ?>"><?= e($fila_historial['duracion_label']) ?></span>
              </p>

              <?php if ($fila_historial['puede_corregir']): ?>
                <a
                  class="icon-btn"
                  href="modificar_horario.php?id=<?= (int)$fila_historial['id'] ?>"
                  aria-label="Corregir marcación"
                >
                  <svg viewBox="0 0 24 24"><path d="M12 20h9"></path><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4Z"></path></svg>
                </a>
              <?php else: ?>
                <span class="out-range">Fuera de rango</span>
              <?php endif; ?>
            </article>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>
  </main>

  <form action="marcar.php" method="POST" class="hidden-submit" id="salidaForm">
    <input type="hidden" name="accion" value="salida">
  </form>

  <div class="mobile-action" aria-label="Acción principal">
    <?php if ($mostrar_boton_salida): ?>
      <button class="primary-btn js-clockout" type="button" data-form-id="salidaForm">
        <svg viewBox="0 0 24 24"><path d="M10 17l5-5-5-5"></path><path d="M15 12H3"></path><path d="M14 4h5a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-5"></path></svg>
        Finalizar jornada
      </button>
    <?php elseif ($mostrar_boton_entrada): ?>
      <form action="marcar.php" method="POST" class="inline-form">
        <input type="hidden" name="accion" value="entrada">
        <button class="primary-btn" type="submit">
          <svg viewBox="0 0 24 24"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><path d="M10 17l5-5-5-5"></path><path d="M15 12H3"></path></svg>
          <?= e($texto_entrada) ?>
        </button>
      </form>
    <?php else: ?>
      <button class="primary-btn" type="button" data-scroll-target="bloqueo-salida">
        <svg viewBox="0 0 24 24"><path d="M12 7v5l3 2"></path><circle cx="12" cy="12" r="9"></circle></svg>
        Resolver salida pendiente
      </button>
    <?php endif; ?>
  </div>

  <div class="modal-backdrop" id="clockoutModal" role="dialog" aria-modal="true" aria-labelledby="clockoutTitle">
    <div class="modal">
      <h3 id="clockoutTitle">¿Finalizar la jornada?</h3>
      <p>Se registrará la hora actual como salida. Podrás solicitar una corrección más tarde si fuera necesario.</p>
      <div class="modal-actions">
        <button class="secondary-btn" id="cancelClockout" type="button">Cancelar</button>
        <button class="primary-btn" id="confirmClockout" type="button">Sí, finalizar</button>
      </div>
    </div>
  </div>

  <a class="footer-link" href="logout.php">Cerrar sesión</a>

  <script>
    (() => {
      const timerNode = document.getElementById('timer');

      const formatClock = (seconds) => {
        const safe = Math.max(0, Number(seconds) || 0);
        const h = String(Math.floor(safe / 3600)).padStart(2, '0');
        const m = String(Math.floor((safe % 3600) / 60)).padStart(2, '0');
        const s = String(safe % 60).padStart(2, '0');
        return `${h}:${m}:${s}`;
      };

      if (timerNode) {
        let elapsed = Number(timerNode.dataset.seconds || '0');
        const isRunning = timerNode.dataset.running === '1';
        timerNode.textContent = formatClock(elapsed);

        if (isRunning) {
          window.setInterval(() => {
            elapsed += 1;
            timerNode.textContent = formatClock(elapsed);
          }, 1000);
        }
      }

      document.querySelectorAll('[data-scroll-target]').forEach((button) => {
        button.addEventListener('click', () => {
          const targetId = button.getAttribute('data-scroll-target');
          if (!targetId) {
            return;
          }

          const target = document.getElementById(targetId);
          target?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
      });

      const clockoutModal = document.getElementById('clockoutModal');
      const cancelClockout = document.getElementById('cancelClockout');
      const confirmClockout = document.getElementById('confirmClockout');
      let clockoutForm = null;

      const lockBody = () => document.body.classList.add('modal-open');
      const unlockBody = () => {
        const hasOpenModal = document.querySelector('.modal-backdrop.open');
        if (!hasOpenModal) {
          document.body.classList.remove('modal-open');
        }
      };

      const openClockoutModal = (formId) => {
        if (!clockoutModal) {
          return;
        }

        clockoutForm = document.getElementById(formId || '');
        if (!clockoutForm) {
          return;
        }

        clockoutModal.classList.add('open');
        lockBody();
        cancelClockout?.focus();
      };

      const closeClockoutModal = () => {
        if (!clockoutModal) {
          return;
        }

        clockoutModal.classList.remove('open');
        unlockBody();
      };

      document.querySelectorAll('.js-clockout').forEach((button) => {
        button.addEventListener('click', () => {
          openClockoutModal(button.getAttribute('data-form-id'));
        });
      });

      cancelClockout?.addEventListener('click', closeClockoutModal);

      confirmClockout?.addEventListener('click', () => {
        if (!clockoutForm) {
          closeClockoutModal();
          return;
        }

        closeClockoutModal();

        if (typeof clockoutForm.requestSubmit === 'function') {
          clockoutForm.requestSubmit();
        } else {
          HTMLFormElement.prototype.submit.call(clockoutForm);
        }
      });

      clockoutModal?.addEventListener('click', (event) => {
        if (event.target === clockoutModal) {
          closeClockoutModal();
        }
      });

      document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
          return;
        }

        closeClockoutModal();
      });

      document.querySelectorAll('form[action="marcar.php"]').forEach((form) => {
        const latInput = document.createElement('input');
        latInput.type = 'hidden';
        latInput.name = 'lat';

        const lngInput = document.createElement('input');
        lngInput.type = 'hidden';
        lngInput.name = 'lng';

        form.appendChild(latInput);
        form.appendChild(lngInput);

        form.addEventListener('submit', (event) => {
          if (form.dataset.gpsReady === '1') {
            form.dataset.gpsReady = '0';
            return;
          }

          if (!navigator.geolocation) {
            return;
          }

          event.preventDefault();

          const submitBtn = form.querySelector('button[type="submit"]');
          const originalHtml = submitBtn ? submitBtn.innerHTML : '';
          let sent = false;

          const submitNow = () => {
            if (sent) {
              return;
            }
            sent = true;

            if (submitBtn) {
              submitBtn.disabled = false;
              submitBtn.classList.remove('is-loading');
              submitBtn.innerHTML = originalHtml;
            }

            if (typeof form.requestSubmit === 'function') {
              form.dataset.gpsReady = '1';
              form.requestSubmit();
            } else {
              HTMLFormElement.prototype.submit.call(form);
            }
          };

          if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.classList.add('is-loading');
            submitBtn.innerHTML = '<span class="spinner" aria-hidden="true"></span> Obteniendo ubicación...';
          }

          const timeoutId = window.setTimeout(() => {
            submitNow();
          }, 8000);

          navigator.geolocation.getCurrentPosition(
            (position) => {
              clearTimeout(timeoutId);
              latInput.value = position.coords.latitude.toFixed(8);
              lngInput.value = position.coords.longitude.toFixed(8);
              submitNow();
            },
            () => {
              clearTimeout(timeoutId);
              submitNow();
            },
            {
              timeout: 8000,
              maximumAge: 30000,
              enableHighAccuracy: true,
            }
          );
        });
      });
    })();
  </script>
</body>
</html>