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

function fecha_humana_larga(DateTimeInterface $fecha): string
{
    $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    $dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];

    $dia = $dias[(int)$fecha->format('w')] ?? $fecha->format('d/m/Y');
    $mes = $meses[(int)$fecha->format('n') - 1] ?? $fecha->format('m');

    return $dia . ' · ' . $fecha->format('d') . ' ' . $mes . ' ' . $fecha->format('Y');
}

$flag_stmt = $pdo->prepare('SELECT requiere_cambio_password, nombre FROM usuarios WHERE id = ? LIMIT 1');
$flag_stmt->execute([$empleado_id]);
$usuario_data = $flag_stmt->fetch(PDO::FETCH_ASSOC) ?: [];

if ((int)($usuario_data['requiere_cambio_password'] ?? 0) === 1) {
    header('Location: cambiar_password.php');
    exit;
}

if (perfil_incompleto($pdo, $empleado_id)) {
    header('Location: completar_perfil.php');
    exit;
}

$nombre_mostrar = trim((string)($usuario_data['nombre'] ?? ''));
if ($nombre_mostrar === '') {
    $nombre_mostrar = (string)($_SESSION['username'] ?? 'Usuario');
}

$marcacion_id = (int)($_GET['id'] ?? 0);
if ($marcacion_id <= 0) {
    header('Location: principal.php');
    exit;
}

$stmt_marcacion = $pdo->prepare('SELECT id, entrada, salida FROM marcaciones WHERE id = ? AND empleado_id = ? LIMIT 1');
$stmt_marcacion->execute([$marcacion_id, $empleado_id]);
$marcacion = $stmt_marcacion->fetch(PDO::FETCH_ASSOC) ?: null;

if (!$marcacion) {
    header('Location: principal.php');
    exit;
}

$entrada_original = new DateTimeImmutable((string)$marcacion['entrada']);
$salida_original = !empty($marcacion['salida']) ? new DateTimeImmutable((string)$marcacion['salida']) : null;
$hoy = new DateTimeImmutable('today');
$fecha_marcacion = new DateTimeImmutable($entrada_original->format('Y-m-d'));
$fecha_minima = $hoy->modify('-' . $dias_correccion_horario . ' days');

if ($fecha_marcacion < $fecha_minima || $fecha_marcacion > $hoy) {
    header('Location: principal.php');
    exit;
}

$stmt_solicitud = $pdo->prepare(
    'SELECT id, nueva_hora_entrada, nueva_hora_salida, motivo, estado, fecha_solicitud
     FROM solicitudes_cambio
     WHERE marcacion_id = ? AND empleado_id = ?
     ORDER BY id DESC
     LIMIT 1'
);
$stmt_solicitud->execute([$marcacion_id, $empleado_id]);
$ultima_solicitud = $stmt_solicitud->fetch(PDO::FETCH_ASSOC) ?: null;

$solo_salida = $salida_original === null;
$entrada_sugerida = !$solo_salida && !empty($ultima_solicitud['nueva_hora_entrada'])
    ? substr((string)$ultima_solicitud['nueva_hora_entrada'], 0, 5)
    : $entrada_original->format('H:i');
$salida_sugerida = !empty($ultima_solicitud['nueva_hora_salida'])
    ? substr((string)$ultima_solicitud['nueva_hora_salida'], 0, 5)
    : ($salida_original ? $salida_original->format('H:i') : '');
$motivo_sugerido = (string)($ultima_solicitud['motivo'] ?? '');

$estado_actual = (string)($ultima_solicitud['estado'] ?? '');
$bloquear_envio = in_array($estado_actual, ['pendiente', 'pendiente_empleado'], true);

$saludo_hora = (int)date('G');
$saludo = $saludo_hora < 12 ? 'Buenos días' : ($saludo_hora < 20 ? 'Buenas tardes' : 'Buenas noches');
$iniciales = iniciales_usuario($nombre_mostrar);

$mensajes_error = [
    'datos_insuficientes' => 'Completa las horas requeridas y el motivo para enviar la solicitud.',
    'marcacion_invalida' => 'No se ha encontrado la marcación que intentas corregir.',
    'fuera_de_rango' => 'Solo puedes modificar marcaciones dentro de los últimos 7 días.',
    'salida_limite' => 'La hora de salida no puede exceder 19 horas desde la entrada.',
    'solicitud_en_revision' => 'Ya existe una solicitud en revisión para esta marcación.',
    'error_guardado' => 'No se ha podido guardar la solicitud. Inténtalo de nuevo.',
];

$error_actual = isset($_GET['error']) ? (string)$_GET['error'] : '';
$mensaje_error = $mensajes_error[$error_actual] ?? null;

$estado_info = [
    'pendiente' => ['clase' => 'warning', 'texto' => 'En revisión por el dueño'],
    'pendiente_empleado' => ['clase' => 'primary', 'texto' => 'Tienes una propuesta pendiente de respuesta'],
    'rechazado' => ['clase' => 'danger', 'texto' => 'Solicitud rechazada'],
    'rechazado_empleado' => ['clase' => 'danger', 'texto' => 'Propuesta rechazada por ti'],
    'aprobado' => ['clase' => 'success', 'texto' => 'Solicitud aprobada'],
];

$badge = $estado_info[$estado_actual] ?? ['clase' => 'neutral', 'texto' => 'Sin solicitudes previas'];
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#064b3d">
  <title>Modificar horario · Control Horario</title>
  <link rel="stylesheet" href="principal.css">
</head>
<body>
  <main class="app-shell app-shell--subpage">
    <header class="topbar">
      <div class="brand" aria-label="Control Horario">
        <div class="brand-mark" aria-hidden="true">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8.6"></circle><path d="M12 7.5v5l3.2 2"></path></svg>
        </div>
        <div class="brand-copy">
          <p class="brand-title">Control Horario</p>
          <p class="brand-kicker">Panel de modificación</p>
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

    <?php if ($mensaje_error !== null): ?>
      <section class="alerts" aria-label="Mensajes del sistema">
        <article class="status-banner warning">
          <span class="status-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"></circle><path d="M12 8v5"></path><path d="M12 16h.01"></path></svg>
          </span>
          <p><?= e($mensaje_error) ?></p>
        </article>
      </section>
    <?php endif; ?>

    <section class="subpage-hero" aria-label="Resumen de la marcación a corregir">
      <div>
        <p class="eyebrow"><span class="status-dot"></span> Ajuste de jornada</p>
        <h1>Modificar horario<br>sin salir del flujo.</h1>
        <p class="subpage-copy">Revisa la marcación original, prepara la corrección y envía la solicitud al dueño desde una vista alineada con tu panel principal.</p>
      </div>
      <div class="subpage-summary">
        <span class="date-chip"><?= e(fecha_humana_larga($fecha_marcacion)) ?></span>
        <span class="status-badge <?= e($badge['clase']) ?>"><?= e($badge['texto']) ?></span>
      </div>
    </section>

    <section class="edit-layout">
      <aside class="detail-card" aria-labelledby="detalleMarcacionTitle">
        <div class="section-head section-head--stack">
          <div>
            <h2 id="detalleMarcacionTitle">Marcación original</h2>
            <p>Datos base sobre los que se aplicará la solicitud.</p>
          </div>
        </div>

        <div class="detail-list">
          <div class="detail-item">
            <span>Entrada registrada</span>
            <strong><?= e($entrada_original->format('H:i:s')) ?></strong>
          </div>
          <div class="detail-item">
            <span>Salida registrada</span>
            <strong><?= e($salida_original ? $salida_original->format('H:i:s') : 'Sin salida') ?></strong>
          </div>
          <div class="detail-item">
            <span>Ventana disponible</span>
            <strong>Últimos <?= (int)$dias_correccion_horario ?> días</strong>
          </div>
          <?php if ($ultima_solicitud): ?>
            <div class="detail-item detail-item--full">
              <span>Última solicitud</span>
              <strong><?= e($badge['texto']) ?></strong>
              <small>Enviada el <?= e(date('d/m/Y H:i', strtotime((string)$ultima_solicitud['fecha_solicitud']))) ?></small>
            </div>
          <?php endif; ?>
        </div>

        <?php if ($bloquear_envio): ?>
          <div class="notice warning notice--spaced">
            <span class="notice-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5"></path><path d="M12 16h.01"></path></svg></span>
            <div>
              <strong>Solicitud bloqueada temporalmente</strong>
              <p>Esta marcación ya tiene una solicitud en revisión. Debes esperar la respuesta antes de enviar otra.</p>
            </div>
          </div>
        <?php endif; ?>
      </aside>

      <section class="edit-card" aria-labelledby="formCambioTitle">
        <div class="section-head section-head--stack">
          <div>
            <h2 id="formCambioTitle"><?= $solo_salida ? 'Solicitar hora de salida' : 'Solicitar corrección completa' ?></h2>
            <p><?= $solo_salida ? 'Solo falta registrar la salida de esta jornada.' : 'Propón la nueva entrada, la nueva salida y el motivo del ajuste.' ?></p>
          </div>
        </div>

        <form action="solicitar_cambio.php" method="POST" class="edit-form">
          <input type="hidden" name="marcacion_id" value="<?= (int)$marcacion_id ?>">
          <input type="hidden" name="solo_salida" value="<?= $solo_salida ? '1' : '0' ?>">

          <div class="field-grid<?= $solo_salida ? ' single' : '' ?>">
            <?php if (!$solo_salida): ?>
              <label class="field-card" for="nueva_entrada">
                <span>Nueva hora de entrada</span>
                <input type="time" name="nueva_entrada" id="nueva_entrada" step="60" value="<?= e($entrada_sugerida) ?>" required>
              </label>
            <?php else: ?>
              <div class="field-card field-card--read">
                <span>Entrada registrada</span>
                <strong><?= e($entrada_original->format('H:i:s')) ?></strong>
                <small>Se conservará tal como está.</small>
              </div>
            <?php endif; ?>

            <label class="field-card" for="nueva_salida">
              <span><?= $solo_salida ? 'Hora de salida' : 'Nueva hora de salida' ?></span>
              <input type="time" name="nueva_salida" id="nueva_salida" step="60" value="<?= e($salida_sugerida) ?>" required>
            </label>
          </div>

          <label class="field-card field-card--textarea" for="motivo">
            <span>Motivo del cambio</span>
            <textarea id="motivo" name="motivo" rows="5" placeholder="Describe por qué necesitas corregir esta marcación" required><?= e($motivo_sugerido) ?></textarea>
          </label>

          <div class="notice notice--soft">
            <span class="notice-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"></circle><path d="M12 11v5"></path><path d="M12 8h.01"></path></svg></span>
            <div>
              <strong>Qué pasará al enviar</strong>
              <p>La solicitud se guardará para revisión. Cuando el dueño la responda, verás el resultado en tu panel principal.</p>
            </div>
          </div>

          <div class="edit-actions">
            <a href="principal.php#history" class="secondary-btn secondary-btn--link">Volver al historial</a>
            <button type="submit" class="primary-btn"<?= $bloquear_envio ? ' disabled aria-disabled="true"' : '' ?>>
              <svg viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><path d="M22 4 12 14.01 9 11.01"></path></svg>
              Enviar solicitud
            </button>
          </div>
        </form>
      </section>
    </section>
  </main>

  <a class="footer-link" href="principal.php">Volver al panel</a>
</body>
</html>
