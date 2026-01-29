<?php
// enviar_correo.php - Interfaz para redactar y encolar correos masivos
// Instrucciones: Copia `mail_config.php.example` a `mail_config.php` y completa las credenciales.

session_start();
// TODO: agregar comprobación de autenticación si aplica

// Cargar configuración (devuelve un array)
if (file_exists(__DIR__ . '/mail_config.php')) {
    $mail_config = include __DIR__ . '/mail_config.php';
} else {
    $mail_config = [];
}

// Capturar mensajes de éxito o error
$enqueued = isset($_GET['enqueued']) ? (int)$_GET['enqueued'] : null;
$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : null;

?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Enviar correo masivo</title>
    <link rel="stylesheet" href="enviar_correo.css">
</head>
<body>
    <div class="container">
        <h1>Enviar correo masivo</h1>
        <p class="subtitle">Selecciona destinatarios y escribe el mensaje. Los archivos PDF se pueden previsualizar antes de enviar.</p>

        <?php if ($enqueued !== null): ?>
        <div class="alert alert-success">
            <strong>✓ Éxito:</strong> Se encolaron <?= $enqueued ?> correo(s) para envío.
            <a href="estado_envios.php" class="link-status">Ver estado de envíos</a>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-error">
            <strong>✕ Error:</strong> <?= $error ?>
        </div>
        <?php endif; ?>

        <form action="process_send.php" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label>Asunto <span>(requerido)</span></label>
                <input type="text" name="subject" placeholder="Ej: Comunicado importante para el equipo" required>
            </div>

            <div class="form-group">
                <label>Cuerpo <span>(HTML permitido - requerido)</span></label>
                <textarea name="body" placeholder="Escribe tu mensaje aquí..." required></textarea>
            </div>

            <div class="form-group">
                <label>Destinatarios</label>
                <select name="recipients_source" id="recipients_source">
                    <option value="all_employees">Todos los empleados (tabla tb_empleados)</option>
                    <option value="csv">Subir CSV con columna email</option>
                    <option value="manual">Ingresar correos manualmente</option>
                </select>
            </div>

            <div id="csv_upload" class="hidden-group">
                <div class="form-group">
                    <label>Carga CSV <span>(columna email requerida)</span></label>
                    <input type="file" name="csv_file" accept="text/csv">
                </div>
            </div>

            <div id="manual_input" class="hidden-group">
                <div class="form-group">
                    <label>Correos manuales <span>(separados por coma o salto de línea)</span></label>
                    <textarea name="manual_emails" placeholder="ejemplo@correo.com&#10;otro@correo.com"></textarea>
                </div>
            </div>

            <div class="form-group">
                <label>Adjuntar archivos <span>(PDF, imágenes, otros - múltiples permitidos)</span></label>
                <input type="file" name="attachments[]" id="attachments" multiple>
            </div>

            <div id="preview"></div>

            <button type="submit">Encolar envíos</button>
        </form>

        <hr class="divider">

        <div class="operations">
            <h3>Operaciones</h3>
            <p>Ejecuta en terminal: <code>php worker_send.php</code> para enviar los correos en lotes automáticamente.</p>
            <p style="margin-top: 15px;"><a href="estado_envios.php" class="btn-status">📊 Ver estado de todos los envíos</a></p>
        </div>
    </div>

    <script>
        const recipientsSource = document.getElementById('recipients_source');
        const csvUpload = document.getElementById('csv_upload');
        const manualInput = document.getElementById('manual_input');
        const manualEmails = document.querySelector('textarea[name="manual_emails"]');

        function syncRecipientBlocks(value) {
            const source = value || recipientsSource.value;
            csvUpload.classList.toggle('visible', source === 'csv');
            manualInput.classList.toggle('visible', source === 'manual');

            if (!manualEmails) return;
            if (source === 'manual') {
                manualEmails.setAttribute('required', 'required');
            } else {
                manualEmails.removeAttribute('required');
                manualEmails.value = '';
                const fg = manualEmails.closest('.form-group');
                if (fg) fg.classList.remove('error', 'complete');
            }
        }

        recipientsSource.addEventListener('change', function () {
            syncRecipientBlocks(this.value);
        });

        // Manejo de validación de formulario en tiempo real
        const inputs = document.querySelectorAll('input[type=text], textarea, select');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                updateFieldStatus(this);
            });
            input.addEventListener('input', function() {
                updateFieldStatus(this);
            });
        });

        function updateFieldStatus(element) {
            const formGroup = element.closest('.form-group');
            if (!formGroup) return;

            if (element.hasAttribute('required')) {
                if (element.value.trim() !== '') {
                    formGroup.classList.remove('error');
                    formGroup.classList.add('complete');
                } else {
                    formGroup.classList.remove('complete');
                    formGroup.classList.add('error');
                }
            }
        }

        // Preview de archivos
        document.getElementById('attachments').addEventListener('change', function (e) {
            const preview = document.getElementById('preview');
            preview.innerHTML = '';

            if (e.target.files.length === 0) return;

            const fileList = document.createElement('div');
            fileList.style.marginTop = '20px';

            Array.from(e.target.files).forEach(file => {
                const div = document.createElement('div');
                div.className = 'file-item';

                const info = document.createElement('div');
                const name = document.createElement('span');
                name.className = 'file-item-name';
                name.textContent = file.name;

                const size = document.createElement('span');
                size.className = 'file-item-size';
                size.textContent = '(' + Math.round(file.size / 1024) + ' KB)';

                info.appendChild(name);
                info.appendChild(document.createElement('br'));
                info.appendChild(size);

                div.appendChild(info);

                if (file.type === 'application/pdf') {
                    const url = URL.createObjectURL(file);
                    const iframe = document.createElement('iframe');
                    iframe.src = url;
                    div.appendChild(iframe);
                }

                fileList.appendChild(div);
            });

            preview.appendChild(fileList);
        });

        // Inicializar estado de campos al cargar
        window.addEventListener('load', function() {
            syncRecipientBlocks();
            inputs.forEach(input => {
                if (input.value.trim() !== '') {
                    updateFieldStatus(input);
                }
            });
        });
    </script>
</body>
</html>
