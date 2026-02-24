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
        <p class="subtitle">Selecciona destinatarios y escribe el mensaje. Los correos se encolarán y podrán procesarse en lotes seguros desde la página de estado.</p>

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
                <label for="recipients_source">Selecciona fuente de destinatarios:</label>
                <select id="recipients_source" name="recipients_source">
                    <option value="all_employees">📊 Todos los empleados (Base de datos)</option>
                    <option value="manual">✋ Entrada manual (copiar/pegar emails)</option>
                    <option value="csv">📄 Subir archivo CSV</option>
                </select>
            </div>

            <div class="form-group" id="csv_upload" style="display: none;">
                <label for="csv_file">Archivo CSV:</label>
                <input type="file" name="csv_file" id="csv_file" accept=".csv" />
                <p style="font-size: 12px; color: #666; margin-top: 5px;">
                    Formato: Primera columna = email, Segunda columna (opcional) = nombre
                </p>
            </div>

            <div class="form-group" id="manual_input" style="display: none;">
                <label for="manual_emails">Emails manuales:</label>
                <textarea name="manual_emails" id="manual_emails" rows="5" placeholder="Ingresa un email por línea"></textarea>
            </div>

            <div class="form-group">
                <label for="attachments">Adjuntar archivos:</label>
                <input type="file" name="attachments[]" id="attachments" multiple>
            </div>

            <div id="preview"></div>

            <button type="submit" class="btn btn-primary">Enviar Correos</button>
        </form>

        <hr class="divider">

        <div class="operations">
            <h3>Próximos pasos</h3>
            <p>1. Completa el formulario arriba y haz clic en <strong>"Enviar Correos"</strong> para encolar los mensajes.</p>
            <p>2. Los correos se almacenarán en la cola de envío para procesarse en lotes seguros.</p>
            <p>3. Ve a <a href="estado_envios.php" style="font-weight: bold; color: #2196F3;">📊 Estado de envíos</a> para ver la cola y procesar los correos en lotes desde el navegador.</p>
            <p style="margin-top: 15px; padding: 15px; background: #e7f3ff; border-left: 4px solid #2196F3; border-radius: 4px;">
                <strong>🔧 ¿Problemas con el envío?</strong> 
                <a href="test_smtp.php" style="font-weight: bold; color: #0066cc;">Prueba la conexión SMTP aquí</a>
            </p>
        </div>
    </div>

    <script>
        const recipientsSource = document.getElementById('recipients_source');
        const csvUpload = document.getElementById('csv_upload');
        const manualInput = document.getElementById('manual_input');
        const manualEmails = document.getElementById('manual_emails');
        const csvFile = document.getElementById('csv_file');

        function syncRecipientBlocks(value) {
            const source = value || recipientsSource.value;
            
            // Mostrar/ocultar campos según opción
            csvUpload.style.display = source === 'csv' ? 'block' : 'none';
            manualInput.style.display = source === 'manual' ? 'block' : 'none';

            // Base de datos: no requiere campo adicional
            // CSV: requiere archivo
            // Manual: requiere emails

            if (source === 'manual') {
                manualEmails.setAttribute('required', 'required');
                csvFile.removeAttribute('required');
                manualEmails.value = '';
            } else if (source === 'csv') {
                csvFile.setAttribute('required', 'required');
                manualEmails.removeAttribute('required');
                csvFile.value = '';
            } else {
                // all_employees (base de datos)
                csvFile.removeAttribute('required');
                manualEmails.removeAttribute('required');
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
