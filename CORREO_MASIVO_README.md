# Sistema de Envío de Correos Masivos

## Descripción General

Este sistema permite enviar correos masivos directamente desde el navegador web, encolando los mensajes en una base de datos y procesándolos en lotes seguros para evitar problemas con los servidores SMTP.

## Flujo de Trabajo

### 1. **Enviar Correos** (`enviar_correo.php`)
- Accede a `enviar_correo.php`
- Completa el formulario:
  - **Asunto**: El tema del correo
  - **Cuerpo**: El contenido del correo (se permite HTML)
  - **Destinatarios**: Selecciona si quieres enviar a:
    - Entrada manual: Ingresa emails directamente
    - Subir CSV: Carga un archivo CSV con emails
    - Base de datos: Usa los emails registrados en `tb_empleados`
  - **Adjuntos**: Sube archivos PDF o cualquier otro formato
- Haz clic en **"Enviar Correos"** para encolar los mensajes

### 2. **Procesar Correos** (`procesar_cola.php`)
- Los correos se guardan en la tabla `email_queue` con estado `'queued'`
- Ve a **Estado de envíos** para ver los correos en cola
- Haz clic en uno de estos botones para procesar:
  - **▶ 1 Lote**: Procesa 50 correos (configurable en `mail_config.php`)
  - **▶ 3 Lotes**: Procesa 150 correos con pausas entre lotes
  - **▶ 5 Lotes**: Procesa 250 correos con pausas entre lotes

### 3. **Monitorear Estado** (`estado_envios.php`)
- Ve el estado de todos los correos:
  - **En cola**: Esperando ser procesados
  - **Enviando**: En proceso de envío
  - **Enviados**: Procesados exitosamente
  - **Fallidos**: Con errores en el envío
- Filtra por estado para ver detalles específicos
- Procesa lotes en cualquier momento desde el panel de acciones

## Configuración

### Archivo `mail_config.php`

Crea un archivo `mail_config.php` basado en `mail_config.php.example` con esta estructura:

```php
<?php
return [
    'db' => [
        'host' => 'localhost',
        'name' => 'tu_base_datos',
        'user' => 'usuario_bd',
        'pass' => 'contraseña_bd'
    ],
    'smtp' => [
        'host' => 'smtp.gmail.com',        // Servidor SMTP
        'port' => 587,                      // Puerto (587 para TLS, 465 para SSL)
        'user' => 'tu_email@gmail.com',    // Email SMTP
        'pass' => 'tu_contraseña_app',     // Contraseña de aplicación
        'secure' => 'tls'                   // 'tls' o 'ssl'
    ],
    'from' => [
        'email' => 'noreply@tudominio.com',
        'name' => 'Nombre del Sistema'
    ],
    'batch_size' => 50,      // Correos por lote
    'pause_seconds' => 30    // Segundos de pausa entre lotes
];
?>
```

## Base de Datos

Se requiere una tabla `email_queue` con la siguiente estructura:

```sql
CREATE TABLE email_queue (
    id INT PRIMARY KEY AUTO_INCREMENT,
    recipient_email VARCHAR(255) NOT NULL,
    recipient_name VARCHAR(255),
    subject VARCHAR(255) NOT NULL,
    body LONGTEXT NOT NULL,
    attachments JSON,
    status ENUM('queued', 'sending', 'sent', 'failed') DEFAULT 'queued',
    attempts INT DEFAULT 0,
    last_error TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    sent_at DATETIME
) CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

También necesitas una tabla `tb_empleados` con los campos:
- `email` (VARCHAR)
- `nombre` (VARCHAR)
- `apellidos` (VARCHAR)

## Archivos Principales

| Archivo | Descripción |
|---------|-------------|
| `enviar_correo.php` | Interfaz para redactar y encolar correos |
| `process_send.php` | Procesa el formulario y encola los correos |
| `procesar_cola.php` | Procesa los correos en cola desde el navegador |
| `estado_envios.php` | Muestra el estado de todos los correos |
| `worker_send.php` | Procesa la cola desde la línea de comandos (alternativa) |
| `mail_config.php` | Configuración SMTP y base de datos (crear a partir de `.example`) |

## Características

✅ **Encolar correos masivos** desde el navegador
✅ **Procesar en lotes seguros** para evitar bloqueos SMTP
✅ **Adjuntos múltiples** (PDF, imágenes, etc.)
✅ **Soporte HTML** en el cuerpo del correo
✅ **Importar desde CSV** con validación de emails
✅ **Importar desde BD** tabla `tb_empleados`
✅ **Entrada manual** de emails
✅ **Monitoreo en tiempo real** del estado de envíos
✅ **Reintentos automáticos** para correos fallidos
✅ **Pausa entre lotes** configurable para respetar límites SMTP
✅ **Mensajes de error** detallados para depuración
✅ **Procesamiento desde CLI** como alternativa (worker_send.php)

## Uso Típico

1. Redacta un correo en `enviar_correo.php`
2. Selecciona los destinatarios y adjuntos
3. Haz clic en "Enviar Correos" para encolar
4. Ve a `estado_envios.php` 
5. Haz clic en "▶ Procesar Lotes" según sea necesario
6. Monitorea el progreso en `estado_envios.php`

## Alternativa: Procesamiento CLI

Si prefieres procesar los correos desde la terminal:

```bash
php worker_send.php
```

Esto procesará todos los correos en cola automáticamente con pausas entre lotes.

## Solución de Problemas

### "Falta mail_config.php"
Copia `mail_config.php.example` a `mail_config.php` y completa las credenciales SMTP.

### "Error de conexión SMTP"
- Verifica las credenciales en `mail_config.php`
- Comprueba que el servidor SMTP esté activo
- Para Gmail, usa contraseña de aplicación, no la contraseña de cuenta

### "Correos en estado 'sending' no avanzan"
Actualiza manualmente:
```sql
UPDATE email_queue SET status='queued' WHERE status='sending' AND created_at < NOW() - INTERVAL 1 HOUR;
```

### "Timeout durante el procesamiento"
Reduce `batch_size` en `mail_config.php` a un número menor (ej: 20-30).

## Notas de Seguridad

- ⚠️ Considera agregar autenticación a `enviar_correo.php`
- ⚠️ Valida los emails del usuario antes de encolar
- ⚠️ Limita el tamaño de adjuntos máximo
- ⚠️ No guardes contraseñas en el código, usa variables de entorno
- ⚠️ Escaneando PDFs para detectar malware antes de enviar
