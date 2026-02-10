<?php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'dueño') {
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Nuevo Empleado</title>
    <link rel="stylesheet" href="empleado.css">
    <link rel="stylesheet" href="solicitudes_cambio.css">
    <link rel="stylesheet" href="crear_empleado_nuevo.css">
</head>
<body>
    <div class="page-shell">
        <div class="modal-content page-card">
            <div class="modal-header">
                <h3>Crear Nuevo Empleado</h3>
            </div>
            <form action="crear_empleado.php" method="POST">
                <div class="form-group">
                    <label for="nombre">Nombre del Empleado:</label>
                    <input type="text" name="nombre" id="nombre" required minlength="2" maxlength="100"
                        placeholder="Ej: Juan Pérez García" autocomplete="off">
                </div>
                <div class="form-group">
                    <label for="username">DNI / NIE / NIF / Pasaporte:</label>
                    <input type="text" name="username" id="username" required minlength="3" maxlength="50"
                        placeholder="Ej: X1234567L" autocomplete="off">
                    <small style="color: #718096; font-size: 12px; margin-top: 4px; display: block;">
                        Se convertirá automáticamente a minúsculas sin espacios
                    </small>
                </div>
                <div class="form-group">
                    <label for="fecha_inicio">Fecha de Inicio:</label>
                    <input type="date" name="fecha_inicio" id="fecha_inicio" required
                        value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>">
                    <small style="color: #718096; font-size: 12px; margin-top: 4px; display: block;">
                        Fecha en que el empleado comenzó a trabajar
                    </small>
                </div>
                <div class="form-group">
                    <label for="password">Contraseña Temporal:</label>
                    <div style="position: relative;">
                        <input type="password" name="password" id="password" required minlength="6"
                            placeholder="Mínimo 6 caracteres sin espacios" autocomplete="new-password"
                            style="padding-right: 45px;">
                        <button type="button" onclick="togglePassword('password', this)"
                            style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; padding: 5px; color: #718096;"
                            aria-label="Mostrar contraseña">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                    <small style="color: #718096; font-size: 12px; margin-top: 4px; display: block;">
                        No se permiten espacios. El empleado deberá cambiarla en su primer inicio de sesión
                    </small>
                </div>
                <div class="form-group">
                    <label for="confirmar_password">Confirmar Contraseña:</label>
                    <div style="position: relative;">
                        <input type="password" name="confirmar_password" id="confirmar_password" required minlength="6"
                            placeholder="Repite la contraseña" autocomplete="new-password"
                            style="padding-right: 45px;">
                        <button type="button" onclick="togglePassword('confirmar_password', this)"
                            style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; padding: 5px; color: #718096;"
                            aria-label="Mostrar contraseña">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="modal-footer page-actions">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="8.5" cy="7" r="4"></circle>
                            <line x1="20" y1="8" x2="20" y2="14"></line>
                            <line x1="23" y1="11" x2="17" y2="11"></line>
                        </svg>
                        Crear Empleado
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='dueño.php'" style="flex: 1;">
                        Cancelar
                    </button>
                </div>
            </form>
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
    </script>
</body>
</html>
