<?php
declare(strict_types=1);

session_start();

require_once 'config.php';

const ROUTES_BY_ROLE = [
	'dueño' => 'dueño.php',
	'dueno' => 'dueño.php',
	'hacienda' => 'hacienda.php',
];

$error = null;
$usernameValue = '';

ensureCsrfToken();

if (isPostRequest()) {
	$usernameValue = normalizeUsername($_POST['username'] ?? '');
	$password = (string)($_POST['password'] ?? '');

	try {
		validateCsrfToken($_POST['csrf_token'] ?? '');
		validateLoginInput($usernameValue, $password);

		$user = findUserByUsername($pdo, $usernameValue);

		if (!$user || !password_verify($password, (string)$user['password'])) {
			throw new RuntimeException('Usuario o contraseña incorrectos');
		}

		startUserSession($user);
		redirectLoggedUser($user);
	} catch (InvalidArgumentException | RuntimeException $exception) {
		$error = $exception->getMessage();
	} catch (Throwable $exception) {
		error_log($exception->getMessage());
		$error = 'No se ha podido iniciar sesión. Inténtalo de nuevo.';
	}
}

function isPostRequest(): bool
{
	return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function normalizeUsername(mixed $username): string
{
	$username = trim((string)$username);
	$username = preg_replace('/\s+/', '', $username) ?? '';

	return strtolower($username);
}

function validateLoginInput(string $username, string $password): void
{
	if ($username === '' || $password === '') {
		throw new InvalidArgumentException('Introduce usuario y contraseña');
	}

	if (preg_match('/\s/', $password) === 1) {
		throw new InvalidArgumentException('La contraseña no puede contener espacios');
	}
}

function findUserByUsername(PDO $pdo, string $username): ?array
{
	$stmt = $pdo->prepare(
		'SELECT 
			id, 
			username, 
			password, 
			rol, 
			requiere_cambio_password, 
			propietario_id, 
			es_gerente 
		FROM usuarios 
		WHERE LOWER(username) = ?
		LIMIT 1'
	);

	$stmt->execute([$username]);

	$user = $stmt->fetch(PDO::FETCH_ASSOC);

	return $user ?: null;
}

function startUserSession(array $user): void
{
	session_regenerate_id(true);

	$_SESSION['user_id'] = (int)$user['id'];
	$_SESSION['username'] = strtolower((string)$user['username']);
	$_SESSION['rol'] = (string)$user['rol'];
	$_SESSION['es_gerente'] = (int)($user['es_gerente'] ?? 0);
	$_SESSION['propietario_id'] = isset($user['propietario_id'])
		? (int)$user['propietario_id']
		: null;
}

function redirectLoggedUser(array $user): never
{
	if ((int)$user['requiere_cambio_password'] === 1) {
		redirectTo('cambiar_password.php');
	}

	$role = (string)$user['rol'];

	if (array_key_exists($role, ROUTES_BY_ROLE)) {
		redirectTo(ROUTES_BY_ROLE[$role]);
	}

	$destination = ((int)($user['es_gerente'] ?? 0) === 1)
		? 'gerente.php'
		: 'empleado.php';

	redirectTo($destination);
}

function redirectTo(string $url): never
{
	header('Location: ' . $url);
	exit;
}

function ensureCsrfToken(): void
{
	if (empty($_SESSION['csrf_token'])) {
		$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
	}
}

function validateCsrfToken(mixed $token): void
{
	$token = (string)$token;

	if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
		throw new RuntimeException('La sesión ha caducado. Recarga la página e inténtalo de nuevo.');
	}
}

function e(mixed $value): string
{
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="es">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>HOSTURJAÉN · Control Horario</title>

	<link
		rel="stylesheet"
		href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
		integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
		crossorigin="anonymous"
	>

	<link rel="stylesheet" href="login.css">
</head>

<body>
	<main class="login-page" aria-labelledby="loginTitle">
		<section class="login-shell">
			<aside class="hero-panel" aria-label="HOSTURJAÉN Control Horario">
				<div class="brand-row">
					<div class="logo-box">
						<img src="imagenes/logohosturjaen600x.png" alt="HOSTURJAÉN">
					</div>
					<div class="brand-copy">
						<strong>HOSTURJAÉN</strong>
						<span>Hostelería y turismo de Jaén</span>
					</div>
				</div>

				<div class="hero-content">
					<p class="hero-kicker">Control horario</p>
					<h1>Fichajes claros para equipos de hostelería.</h1>
					<p>
						Acceso seguro para empresas asociadas, equipos de sala, cocina,
						alojamientos y negocios turísticos de la provincia.
					</p>

					<div class="time-card" aria-live="polite">
						<div class="time-label">Hora actual</div>
						<div id="clock">--:--</div>
						<div id="date">Cargando fecha...</div>

						<div class="chips" aria-hidden="true">
							<span>Entrada</span>
							<span>Pausa</span>
							<span>Salida</span>
							<span>Incidencias</span>
						</div>
					</div>
				</div>
			</aside>

			<section class="form-panel" aria-labelledby="loginTitle">
				<div class="form-card">
                    <div class="mobile-brand" aria-label="HOSTURJAÉN"> 
                        <img src="imagenes/logohosturjaen600x.png" class="logo-login" alt="HOSTURJAÉN">
                    </div>
					<div class="form-heading">
						<p class="eyebrow">Control Privado</p>
						<h2 id="loginTitle">Accede a tu jornada</h2>
						<p>
							Introduce tus credenciales para registrar fichajes, revisar
							horarios o gestionar incidencias.
						</p>
					</div>

					<?php if ($error): ?>
						<div class="alert alert-danger error-message" role="alert">
							<?= e($error) ?>
						</div>
					<?php endif; ?>

					<form action="" method="post" novalidate>
						<input
							type="hidden"
							name="csrf_token"
							value="<?= e($_SESSION['csrf_token']) ?>"
						>

						<div class="field">
							<label for="username">Usuario</label>

							<div class="input-wrap">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
									<path d="M20 21a8 8 0 1 0-16 0" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
									<circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
								</svg>

								<input
									id="username"
									name="username"
									type="text"
									autocomplete="username"
									placeholder="DNI, email o usuario"
									value="<?= e($usernameValue) ?>"
									data-username
									required
								>
							</div>
						</div>

						<div class="field">
							<label for="password">Contraseña</label>

							<div class="input-wrap">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
									<rect x="4" y="10" width="16" height="10" rx="2" stroke="currentColor" stroke-width="2"/>
									<path d="M8 10V7a4 4 0 0 1 8 0v3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
								</svg>

								<input
									id="password"
									name="password"
									type="password"
									autocomplete="current-password"
									placeholder="Tu contraseña"
									required
								>

								<button
									class="toggle-password"
									type="button"
									id="togglePassword"
									aria-label="Mostrar contraseña"
									aria-controls="password"
								>
									Ver
								</button>
							</div>
						</div>

						<div class="options">
							<label class="remember">
								<input type="checkbox" name="remember">
								<span>Recordarme</span>
							</label>

							<a href="recuperar_password.php">¿Olvidaste tu contraseña?</a>
						</div>

						<button class="btn-login" type="submit">
							Iniciar sesión
						</button>
					</form>

					<div class="secondary" aria-label="Información del sistema">
						<div class="mini-card">
							<b>Fichaje móvil</b>
							<span>Diseño preparado para teléfono y tablet.</span>
						</div>

						<div class="mini-card">
							<b>Soporte</b>
							<span>Ayuda para empresas asociadas.</span>
						</div>
					</div>

					<p class="legal">
						HOSTURJAÉN · Control horario. Acceso restringido a usuarios autorizados.
					</p>
				</div>
			</section>
		</section>
	</main>

	<script
		src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
		integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
		crossorigin="anonymous"
	></script>

	<script>
		(() => {
			const clock = document.getElementById('clock');
			const dateNode = document.getElementById('date');
			const passwordInput = document.getElementById('password');
			const togglePasswordBtn = document.getElementById('togglePassword');
			const usernameInput = document.querySelector('[data-username]');

			function updateClock() {
				const now = new Date();

				if (clock) {
					clock.textContent = now.toLocaleTimeString('es-ES', {
						hour: '2-digit',
						minute: '2-digit'
					});
				}

				if (dateNode) {
					dateNode.textContent = now.toLocaleDateString('es-ES', {
						weekday: 'long',
						day: 'numeric',
						month: 'long',
						year: 'numeric'
					});
				}
			}

			function normalizeUsernameInput() {
				if (!usernameInput) return;

				usernameInput.value = usernameInput.value
					.toLowerCase()
					.replace(/\s+/g, '');
			}

			function togglePasswordVisibility() {
				if (!passwordInput || !togglePasswordBtn) return;

				const isHidden = passwordInput.type === 'password';

				passwordInput.type = isHidden ? 'text' : 'password';
				togglePasswordBtn.textContent = isHidden ? 'Ocultar' : 'Ver';
				togglePasswordBtn.setAttribute(
					'aria-label',
					isHidden ? 'Ocultar contraseña' : 'Mostrar contraseña'
				);
			}

			usernameInput?.addEventListener('input', normalizeUsernameInput);
			togglePasswordBtn?.addEventListener('click', togglePasswordVisibility);

			updateClock();
			window.setInterval(updateClock, 1000);
		})();
	</script>
</body>
</html>