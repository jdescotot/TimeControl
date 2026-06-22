<?php
session_start();
require_once 'config.php';

$owner_id = require_dueno_o_gerente($pdo);
$panel_home = panel_home_url();
$current_user_name = trim((string)($_SESSION['nombre'] ?? $_SESSION['username'] ?? 'Usuario'));
$current_user_role = es_dueno() ? 'Dueno' : (es_gerente() ? 'Gerente' : 'Usuario');

$initials_seed = preg_replace('/\s+/', '', $current_user_name);
$avatar_initials = strtoupper(substr($initials_seed, 0, 2));
if ($avatar_initials === '') {
    $avatar_initials = 'US';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Nuevo Empleado</title>
    <link rel="stylesheet" href="nuevo_empleado.css">
</head>
<body class="new-employee-page">
    <div class="employee-create-shell">
        <header class="employee-create-topbar">
            <a href="<?php echo htmlspecialchars($panel_home, ENT_QUOTES, 'UTF-8'); ?>" class="brand-link" aria-label="Volver al panel principal">
                <span class="brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M3 10.5 12 3l9 7.5"></path>
                        <path d="M5 9.5V21h14V9.5"></path>
                        <path d="M9 21v-6h6v6"></path>
                    </svg>
                </span>
                <span class="brand-copy">
                    <strong>HOSTURJAEN</strong>
                    <small>Alta de personal</small>
                </span>
            </a>

            <div class="profile-pill" aria-label="Sesion activa">
                <div class="profile-copy">
                    <small><?php echo htmlspecialchars($current_user_role, ENT_QUOTES, 'UTF-8'); ?></small>
                    <strong><?php echo htmlspecialchars($current_user_name, ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>
                <span class="avatar" aria-hidden="true"><?php echo htmlspecialchars($avatar_initials, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        </header>

        <section class="create-hero" aria-label="Introduccion al alta de empleado">
            <div>
                <p class="eyebrow"><span class="status-dot" aria-hidden="true"></span>Gestion de Personal</p>
                <h1>Crear nuevo empleado.</h1>
                <p class="hero-text">
                    Configura los datos iniciales, la fecha de inicio y los permisos de acceso del nuevo integrante del equipo.
                </p>
            </div>
            <div class="hero-card" role="status" aria-live="polite">
                <span class="hero-card-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <rect x="4" y="10" width="16" height="10" rx="2"></rect>
                        <path d="M8 10V7a4 4 0 0 1 8 0v3"></path>
                    </svg>
                </span>
                <span>
                    <small>Acceso seguro</small>
                    <strong>Credenciales temporales</strong>
                </span>
            </div>
        </section>

        <div class="create-layout">
            <aside class="guidance-card" aria-label="Guia rapida">
                <div class="guidance-head">
                    <span class="guidance-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="9"></circle>
                            <path d="M12 8v4"></path>
                            <path d="M12 16h.01"></path>
                        </svg>
                    </span>
                    <h2>Antes de crear el empleado</h2>
                </div>

                <div class="guidance-list">
                    <article class="guidance-item">
                        <strong>Identificacion oficial</strong>
                        <span>DNI, NIE, NIF o pasaporte en vigor para registrar su cuenta.</span>
                    </article>
                    <article class="guidance-item">
                        <strong>Contrasena temporal</strong>
                        <span>Minimo 6 caracteres sin espacios. El empleado debera cambiarla al iniciar sesion.</span>
                    </article>
                    <article class="guidance-item">
                        <strong>Permisos de gerente</strong>
                        <span>Activa esta opcion solo si necesita acceso avanzado al panel.</span>
                    </article>
                </div>
            </aside>

            <section class="form-card">
                <header class="form-card-head">
                    <div>
                        <p class="section-kicker">Formulario Oficial</p>
                        <h2>Datos del empleado</h2>
                    </div>
                    <span class="safe-badge">Solo usuarios autorizados</span>
                </header>

                <form action="crear_empleado.php" method="POST" class="create-employee-form" id="createEmployeeForm">
                    <div class="field-grid">
                        <div class="field-card field-card--full">
                            <label for="nombre">Nombre del empleado</label>
                            <div class="input-shell">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                                <input
                                    type="text"
                                    name="nombre"
                                    id="nombre"
                                    required
                                    minlength="2"
                                    maxlength="100"
                                    placeholder="Ej: Juan Perez Garcia"
                                    autocomplete="off"
                                >
                            </div>
                        </div>

                        <div class="field-card field-card--full">
                            <label for="username">DNI / NIE / NIF / Pasaporte</label>
                            <div class="input-shell">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                                    <path d="M7 9h6"></path>
                                    <path d="M7 13h10"></path>
                                </svg>
                                <input
                                    type="text"
                                    name="username"
                                    id="username"
                                    required
                                    minlength="3"
                                    maxlength="50"
                                    placeholder="Ej: X1234567L"
                                    autocomplete="off"
                                >
                            </div>
                            <small>Se convertira automaticamente a minusculas sin espacios.</small>
                        </div>

                        <div class="field-card">
                            <label for="fecha_inicio">Fecha de inicio</label>
                            <div class="input-shell">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                                    <path d="M16 2v4"></path>
                                    <path d="M8 2v4"></path>
                                    <path d="M3 10h18"></path>
                                </svg>
                                <input
                                    type="date"
                                    name="fecha_inicio"
                                    id="fecha_inicio"
                                    required
                                    value="<?php echo date('Y-m-d'); ?>"
                                    max="<?php echo date('Y-m-d'); ?>"
                                >
                            </div>
                            <small>Fecha en que el empleado comenzo a trabajar.</small>
                        </div>

                        <div class="field-card field-card--permission">
                            <div class="permission-row">
                                <input type="checkbox" name="es_gerente" id="es_gerente" value="1">
                                <label for="es_gerente">
                                    <strong>Conceder permisos de gerente</strong>
                                    <span>El dueno podra revocarlo despues desde el panel.</span>
                                </label>
                            </div>
                        </div>

                        <div class="field-card">
                            <label for="password">Contrasena temporal</label>
                            <div class="input-shell input-shell--password">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <rect x="4" y="10" width="16" height="10" rx="2"></rect>
                                    <path d="M8 10V7a4 4 0 0 1 8 0v3"></path>
                                </svg>
                                <input
                                    type="password"
                                    name="password"
                                    id="password"
                                    required
                                    minlength="6"
                                    placeholder="Minimo 6 caracteres sin espacios"
                                    autocomplete="new-password"
                                >
                                <button
                                    type="button"
                                    onclick="togglePassword('password', this)"
                                    class="password-toggle"
                                    aria-label="Mostrar contrasena"
                                >
                                    <svg viewBox="0 0 24 24">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>
                            </div>
                            <small>No se permiten espacios. Debe cambiarse al primer inicio de sesion.</small>
                        </div>

                        <div class="field-card">
                            <label for="confirmar_password">Confirmar contrasena</label>
                            <div class="input-shell input-shell--password">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M9 12l2 2 4-4"></path>
                                    <rect x="4" y="10" width="16" height="10" rx="2"></rect>
                                    <path d="M8 10V7a4 4 0 0 1 8 0v3"></path>
                                </svg>
                                <input
                                    type="password"
                                    name="confirmar_password"
                                    id="confirmar_password"
                                    required
                                    minlength="6"
                                    placeholder="Repite la contrasena"
                                    autocomplete="new-password"
                                >
                                <button
                                    type="button"
                                    onclick="togglePassword('confirmar_password', this)"
                                    class="password-toggle"
                                    aria-label="Mostrar contrasena"
                                >
                                    <svg viewBox="0 0 24 24">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <p class="form-error" id="formError" hidden></p>

                    <footer class="form-actions">
                        <a href="<?php echo htmlspecialchars($panel_home, ENT_QUOTES, 'UTF-8'); ?>" class="secondary-btn">Cancelar</a>
                        <button type="submit" class="primary-btn" id="submitBtn">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="8.5" cy="7" r="4"></circle>
                                <line x1="20" y1="8" x2="20" y2="14"></line>
                                <line x1="23" y1="11" x2="17" y2="11"></line>
                            </svg>
                            <span>Crear empleado</span>
                            <span class="btn-spinner" id="submitSpinner" hidden></span>
                        </button>
                    </footer>
                </form>
            </section>
        </div>
    </div>

    <script>
        function togglePassword(inputId, button) {
            const input = document.getElementById(inputId);
            const svg = button.querySelector('svg');

            if (input.type === 'password') {
                input.type = 'text';
                svg.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
            } else {
                input.type = 'password';
                svg.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
            }
        }

        (function () {
            const form = document.getElementById('createEmployeeForm');
            const fechaInicio = document.getElementById('fecha_inicio');
            const usernameInput = document.getElementById('username');
            const submitButton = document.getElementById('submitBtn');
            const spinner = document.getElementById('submitSpinner');
            const formError = document.getElementById('formError');

            const today = new Date().toISOString().split('T')[0];
            if (fechaInicio && !fechaInicio.value) {
                fechaInicio.value = today;
            }
            if (fechaInicio) {
                fechaInicio.max = today;
            }

            if (!form) {
                return;
            }

            form.addEventListener('submit', function (event) {
                const password = document.getElementById('password');
                const confirmPassword = document.getElementById('confirmar_password');

                if (formError) {
                    formError.hidden = true;
                    formError.textContent = '';
                }

                if (!password || !confirmPassword) {
                    return;
                }

                if (usernameInput) {
                    usernameInput.value = usernameInput.value.toLowerCase().replace(/\s+/g, '');
                }

                if (password.value !== confirmPassword.value) {
                    event.preventDefault();
                    if (formError) {
                        formError.textContent = 'Las contrasenas no coinciden.';
                        formError.hidden = false;
                    }
                    return;
                }

                if (/\s/.test(password.value)) {
                    event.preventDefault();
                    if (formError) {
                        formError.textContent = 'La contrasena no puede contener espacios.';
                        formError.hidden = false;
                    }
                    return;
                }

                if (submitButton) {
                    submitButton.setAttribute('disabled', 'disabled');
                }
                if (spinner) {
                    spinner.hidden = false;
                }
            });
        })();
    </script>
</body>
</html>
