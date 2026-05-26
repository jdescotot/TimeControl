<?php
session_start();
require_once 'config.php';

$owner_id = require_dueno_o_gerente($pdo);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Nuevo Empleado</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="empleado.css">
    <link rel="stylesheet" href="solicitudes_cambio.css">
    <link rel="stylesheet" href="crear_empleado_nuevo.css">
</head>
<body class="new-employee-page">
    <div class="page-shell">
        <section class="create-employee-card">
            <header class="create-employee-head">
                <div class="head-title-block">
                    <h1>Crear Nuevo Empleado</h1>
                    <p>Configura los datos iniciales y los permisos de acceso.</p>
                </div>
                <a href="dueño.php" class="head-back-btn" aria-label="Volver al panel del due&ntilde;o">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 12H5"></path>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    Volver al panel
                </a>
            </header>

            <form action="crear_empleado.php" method="POST" class="create-employee-form">
                <div class="row g-3">
                    <div class="col-12">
                        <label for="nombre" class="field-label">Nombre del empleado</label>
                        <input
                            type="text"
                            class="form-control field-input"
                            name="nombre"
                            id="nombre"
                            required
                            minlength="2"
                            maxlength="100"
                            placeholder="Ej: Juan Perez Garcia"
                            autocomplete="off"
                        >
                    </div>

                    <div class="col-12">
                        <label for="username" class="field-label">DNI / NIE / NIF / Pasaporte</label>
                        <input
                            type="text"
                            class="form-control field-input"
                            name="username"
                            id="username"
                            required
                            minlength="3"
                            maxlength="50"
                            placeholder="Ej: X1234567L"
                            autocomplete="off"
                        >
                        <small class="form-help">Se convertir&aacute; autom&aacute;ticamente a min&uacute;sculas sin espacios.</small>
                    </div>

                    <div class="col-12 col-lg-6">
                        <label for="fecha_inicio" class="field-label">Fecha de inicio</label>
                        <input
                            type="date"
                            class="form-control field-input"
                            name="fecha_inicio"
                            id="fecha_inicio"
                            required
                            value="<?php echo date('Y-m-d'); ?>"
                            max="<?php echo date('Y-m-d'); ?>"
                        >
                        <small class="form-help">Fecha en que el empleado comenz&oacute; a trabajar.</small>
                    </div>

                    <div class="col-12 col-lg-6">
                        <div class="permission-box">
                            <div class="form-check permission-check">
                                <input class="form-check-input" type="checkbox" name="es_gerente" id="es_gerente" value="1">
                                <label class="form-check-label permission-title" for="es_gerente">
                                    Conceder permisos de gerente
                                </label>
                            </div>
                            <small class="form-help">El due&ntilde;o podr&aacute; revocarlo despu&eacute;s desde el panel.</small>
                        </div>
                    </div>

                    <div class="col-12 col-lg-6">
                        <label for="password" class="field-label">Contrase&ntilde;a temporal</label>
                        <div class="password-field-wrap">
                            <input
                                type="password"
                                class="form-control field-input password-input"
                                name="password"
                                id="password"
                                required
                                minlength="6"
                                placeholder="M&iacute;nimo 6 caracteres sin espacios"
                                autocomplete="new-password"
                            >
                            <button
                                type="button"
                                onclick="togglePassword('password', this)"
                                class="toggle-password-btn"
                                aria-label="Mostrar contrase&ntilde;a"
                            >
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                        </div>
                        <small class="form-help">No se permiten espacios. Debe cambiarse al primer inicio de sesi&oacute;n.</small>
                    </div>

                    <div class="col-12 col-lg-6">
                        <label for="confirmar_password" class="field-label">Confirmar contrase&ntilde;a</label>
                        <div class="password-field-wrap">
                            <input
                                type="password"
                                class="form-control field-input password-input"
                                name="confirmar_password"
                                id="confirmar_password"
                                required
                                minlength="6"
                                placeholder="Repite la contrase&ntilde;a"
                                autocomplete="new-password"
                            >
                            <button
                                type="button"
                                onclick="togglePassword('confirmar_password', this)"
                                class="toggle-password-btn"
                                aria-label="Mostrar contrase&ntilde;a"
                            >
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <footer class="page-actions">
                    <button type="submit" class="btn btn-primary btn-create">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="8.5" cy="7" r="4"></circle>
                            <line x1="20" y1="8" x2="20" y2="14"></line>
                            <line x1="23" y1="11" x2="17" y2="11"></line>
                        </svg>
                        Crear empleado
                    </button>
                    <a href="dueño.php" class="btn btn-outline-secondary btn-cancel">Cancelar</a>
                </footer>
            </form>
        </section>
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
    </script>
</body>
</html>
