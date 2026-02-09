# VERIFICACIÓN RÁPIDA - Sistema de Correos Masivos

## ✅ Checklist de Instalación

### 1. Archivos Creados/Modificados

- [x] ✅ `procesar_cola.php` - **NUEVO** - Procesa correos desde navegador
- [x] ✅ `estado_envios.php` - Actualizado con botones de procesamiento  
- [x] ✅ `estado_envios.css` - Agregados estilos para panel de acciones
- [x] ✅ `enviar_correo.php` - Actualizado con instrucciones mejoradas

### 2. Dependencias PHP

Verifica que tengas instalado:
- [ ] PHP 7.4+
- [ ] PHPMailer (via Composer)
  ```bash
  composer require phpmailer/phpmailer
  ```
- [ ] Extensión mysqli o PDO MySQL habilitada

### 3. Base de Datos

Verifica que exista:
- [ ] Tabla `email_queue` con estructura correcta
- [ ] Tabla `tb_empleados` con campos `email`, `nombre`, `apellidos`

```sql
-- Crear tabla si no existe
CREATE TABLE IF NOT EXISTS email_queue (
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
) CHARSET=utf8mb4;
```

### 4. Configuración

Verifica que exista:
- [ ] `mail_config.php` con credenciales SMTP correctas
- [ ] Directorio `mail_uploads/` con permisos de escritura (755)

```bash
mkdir -p mail_uploads && chmod 755 mail_uploads
```

### 5. Permisos de Archivos

```bash
chmod 644 enviar_correo.php
chmod 644 estado_envios.php
chmod 644 process_send.php
chmod 644 procesar_cola.php
chmod 755 mail_uploads
```

---

## 🧪 Prueba Paso a Paso

### Paso 1: Enviar correos de prueba
1. Abre `http://tudominio.com/enviar_correo.php`
2. Completa el formulario:
   - Asunto: "Prueba de correos masivos"
   - Cuerpo: "Este es un correo de prueba"
   - Destinatarios: Ingresa 3-5 emails de prueba
   - Haz clic: "Enviar Correos"
3. Deberías ver: ✓ "Se encolaron X correo(s)"

### Paso 2: Verificar encolamiento
1. Abre `http://tudominio.com/estado_envios.php`
2. Deberías ver:
   - Estadísticas actualizada mostrando "X en cola"
   - Panel naranja: "⏳ Hay X correo(s) en cola"
   - 3 botones: "▶ 1 Lote", "▶ 3 Lotes", "▶ 5 Lotes"

### Paso 3: Procesar lotes
1. Haz clic en "▶ 1 Lote"
2. Deberías ver página `procesar_cola.php` con:
   - Estadísticas de envío (enviados, en cola, errores)
   - Resultado de cada lote procesado
   - Botones para procesar más lotes si hay pendientes

### Paso 4: Verificar resultados
1. Vuelve a `estado_envios.php`
2. Deberías ver:
   - Contadores actualizados
   - Correos con status "sent" en la tabla
   - Menos correos "en cola"

---

## 🔍 Troubleshooting

### Problema: "No se ve el botón de procesar"
**Solución**: 
- Verifica que `estado_envios.php` esté actualizado
- Recarga la página (Ctrl+Shift+Supr)
- Comprueba que hay correos en cola

### Problema: "Error conectando a SMTP"
**Solución**:
- Verifica `mail_config.php` con credenciales correctas
- Prueba las credenciales con un cliente SMTP independiente
- Para Gmail, usa contraseña de aplicación, no la contraseña de cuenta

### Problema: "Timeout procesando cola"
**Solución**:
- Reduce `batch_size` en `mail_config.php` a 20-30
- Procesa menos lotes a la vez (usa "▶ 1 Lote")
- Aumenta `max_execution_time` en `php.ini` a 300

### Problema: "Archivos no se suben"
**Solución**:
- Verifica que `mail_uploads/` existe
- ```bash
  chmod 755 mail_uploads
  ```
- Comprueba límite de upload en `php.ini`:
  ```ini
  upload_max_filesize = 100M
  post_max_size = 100M
  ```

### Problema: "Correos sin salir del estado 'sending'"
**Solución**:
- Recuperar correos con error (actualizar a 'queued'):
  ```sql
  UPDATE email_queue 
  SET status='queued' 
  WHERE status='sending' 
  AND created_at < NOW() - INTERVAL 1 HOUR;
  ```

---

## 📊 Monitoreo en Tiempo Real

### Verificar cola desde terminal
```bash
# Contar correos por estado
mysql -u usuario -p base_datos -e \
  "SELECT status, COUNT(*) FROM email_queue GROUP BY status;"

# Ver últimos errores
mysql -u usuario -p base_datos -e \
  "SELECT recipient_email, last_error FROM email_queue WHERE status='failed' LIMIT 10;"
```

### Limpiar correos antiguos
```bash
# Borrar enviados hace más de 30 días
mysql -u usuario -p base_datos -e \
  "DELETE FROM email_queue WHERE status='sent' AND sent_at < NOW() - INTERVAL 30 DAY;"
```

---

## 📈 Rendimiento Esperado

| Métrica | Valor |
|---------|-------|
| Correos por lote (default) | 50 |
| Duración por correo | 1-3 segundos |
| Tiempo total por lote | 50-150 segundos |
| Pausa entre lotes | 30 segundos |
| Máximo lotes web | 5 |
| Máximo correos por sesión web | 250 |

---

## 🔐 Seguridad

### Implementar autenticación
Agrega al inicio de `enviar_correo.php`:
```php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
```

### Validación de emails
Ya implementada en:
- `process_send.php` (validación al encolar)
- `procesar_cola.php` (validación al enviar)

### Limitar por usuario
```php
// En process_send.php
$user_id = $_SESSION['user_id'] ?? null;
$insert_stmt = $pdo->prepare(
    "INSERT INTO email_queue (..., user_id) 
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
);
```

---

## 📞 Soporte

Si encuentras problemas:

1. Verifica logs de PHP: `/var/log/php-fpm.log`
2. Verifica logs de MySQL: `/var/log/mysql/error.log`
3. Revisa la tabla `email_queue` manualmente:
   ```sql
   SELECT * FROM email_queue ORDER BY id DESC LIMIT 10;
   ```
4. Consulta `CAMBIOS_RESUMO.md` y `CORREO_MASIVO_README.md`

---

## ✨ Todo Listo

Si pasaste todos los pasos, el sistema está listo para usar.

**Flujo resumido:**
1. Redacta en `enviar_correo.php`
2. Encola presionando "Enviar Correos"
3. Procesa en `estado_envios.php` con botones
4. Monitorea resultados en tiempo real

¡A disfrutar del envío de correos masivos desde el navegador! 🚀
