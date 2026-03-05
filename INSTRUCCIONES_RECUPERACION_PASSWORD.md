# 🔐 Sistema de Recuperación de Contraseña - Instrucciones de Instalación

## 📋 Resumen del Sistema

Se ha implementado un sistema completo y seguro de recuperación de contraseña que incluye:

✅ **Validación de usuario + email** (ambos deben coincidir)  
✅ **Rate limiting** (3 intentos por email/hora, 10 por IP/hora)  
✅ **Tokens seguros** de 256 bits con expiración de 1 hora  
✅ **Email mediante cola** (sistema existente de `email_queue`)  
✅ **Auditoría completa** de todas las acciones  
✅ **Notificación por email** al cambiar contraseña  
✅ **Protección contra timing attacks**  
✅ **Headers de seguridad** HTTP implementados  

---

## 🚀 Instalación (3 pasos)

### 1️⃣ Crear las tablas de base de datos

Ejecuta el archivo SQL en tu base de datos:

```bash
mysql -u tu_usuario -p nombre_base_datos < setup_password_reset_tables.sql
```

O desde phpMyAdmin/Adminer: copia el contenido de `setup_password_reset_tables.sql` y ejecútalo.

**Tablas creadas:**
- `password_reset_attempts` - Rate limiting
- `password_reset_log` - Auditoría de seguridad

### 2️⃣ Procesar los correos en cola

Los emails de recuperación se encolan automáticamente. Para enviarlos, usa cualquiera de estos métodos:

**Opción A: Desde el navegador**
1. Ve a `estado_envios.php`
2. Haz clic en "▶ 1 Lote" para procesar correos

**Opción B: Desde terminal (recomendado para producción)**
```bash
php worker_send.php
```

**Opción C: Automatizar con cron (Linux) o Task Scheduler (Windows)**
```bash
# Cron (cada 5 minutos)
*/5 * * * * cd /ruta/a/TimeControl && php worker_send.php >> /var/log/email_worker.log 2>&1
```

### 3️⃣ ¡Listo! Prueba el sistema

1. Ve a `index.php`
2. Haz clic en "¿Olvidaste tu contraseña?"
3. Ingresa **usuario** y **email**
4. Revisa tu correo electrónico
5. Haz clic en el enlace de recuperación
6. Define tu nueva contraseña

---

## 📁 Archivos modificados/creados

| Archivo | Cambios |
|---------|---------|
| **setup_password_reset_tables.sql** | ✨ NUEVO - Script SQL para crear tablas |
| **procesar_recuperacion.php** | ✅ MEJORADO - Rate limiting, validación usuario+email, cola de emails |
| **restablecer_password.php** | ✅ MEJORADO - Auditoría, notificación email, mejor seguridad |
| **recuperar_password.php** | ✅ MEJORADO - Campo usuario agregado, mejor UI |
| **cambiar_password.php** | ✅ MEJORADO - Headers de seguridad, `session_regenerate_id()` |
| **INSTRUCCIONES_RECUPERACION_PASSWORD.md** | ✨ NUEVO - Este archivo |

---

## 🔒 Características de Seguridad Implementadas

### 1. Rate Limiting
- **3 intentos por email por hora** - Previene spam a usuarios específicos
- **10 intentos por IP por hora** - Previene abuso desde una misma ubicación
- Los límites se limpian automáticamente después de 1 hora
- Mensajes genéricos para no revelar si hay límites activos

### 2. Validación Usuario + Email
```php
// Ambos deben coincidir exactamente
WHERE username = ? AND (email = ? OR correo = ?)
```
- Previene que alguien con solo el email pueda resetear la cuenta
- Protege contra enumeración de usuarios

### 3. Tokens Criptográficamente Seguros
- 256 bits de entropía usando `random_bytes(32)`
- Expiración automática después de 1 hora
- Se invalidan todos los tokens previos al generar uno nuevo
- Protección contra excepciones de CSPRNG

### 4. Protección contra Timing Attacks
```php
usleep(rand(100000, 500000)); // Delay aleatorio 100-500ms
```
- Tiempo de respuesta uniforme sin importar si el usuario existe
- Previene análisis de tiempos de respuesta

### 5. Auditoría Completa
Todas las acciones se registran en `password_reset_log`:
- Generación de tokens (con IP y user agent)
- Uso exitoso de tokens
- Cambios de contraseña
- Timestamp de todas las operaciones

### 6. Headers de Seguridad HTTP
```php
header("X-Frame-Options: DENY");           // Anti-clickjacking
header("X-Content-Type-Options: nosniff"); // Anti-MIME sniffing
header("Referrer-Policy: strict-origin-when-cross-origin");
```

### 7. Regeneración de Sesión
```php
session_regenerate_id(true); // Previene session fixation
```

---

## 📧 Plantillas de Email

### Email de Recuperación
- Diseño responsive y profesional
- Botón call-to-action destacado
- Enlace alternativo para copiar/pegar
- Información de seguridad (IP, fecha, usuario)
- Advertencia de expiración (1 hora)
- Nota sobre qué hacer si no solicitó el cambio

### Email de Confirmación de Cambio
- Notificación automática al cambiar contraseña
- Detalles del cambio (fecha, hora, IP)
- Enlace directo al login
- Alerta de seguridad si no fue el usuario
- Diseño consistente con el resto del sistema

---

## 🧪 Pruebas Recomendadas

### Prueba 1: Flujo exitoso
1. Solicitar recuperación con usuario+email correctos
2. Verificar que el email llegue (revisar `email_queue` con status='queued')
3. Procesar cola desde `estado_envios.php`
4. Recibir email y hacer clic en enlace
5. Cambiar contraseña exitosamente
6. Recibir email de confirmación
7. Iniciar sesión con nueva contraseña

### Prueba 2: Rate Limiting
1. Solicitar recuperación 3 veces en menos de 1 hora (mismo email)
2. En el 4to intento, debe seguir mostrando mensaje de éxito
3. Verificar en `password_reset_attempts` que hay 4 registros
4. Verificar que NO se generó token en el 4to intento

### Prueba 3: Token expirado
1. Solicitar recuperación
2. En la BD, ejecutar:
   ```sql
   UPDATE usuarios 
   SET reset_token_expira = DATE_SUB(NOW(), INTERVAL 2 HOUR) 
   WHERE reset_token IS NOT NULL;
   ```
3. Intentar usar el enlace
4. Debe mostrar error de token expirado

### Prueba 4: Usuario incorrecto
1. Solicitar recuperación con usuario que no existe + email válido
2. Debe mostrar mensaje genérico de éxito
3. Verificar que NO se generó token en la BD
4. NO debe enviarse email

### Prueba 5: Auditoría
1. Completar flujo completo de recuperación
2. Verificar registros en `password_reset_log`:
   ```sql
   SELECT * FROM password_reset_log ORDER BY id DESC LIMIT 5;
   ```
3. Debe haber 2 registros: uno para `token_generated` y otro para `password_changed`

---

## ⚠️ Notas Importantes

### Base de datos
- Si ya tienes las columnas `reset_token` y `reset_token_expira` en la tabla `usuarios`, no necesitas ejecutar las líneas comentadas del SQL
- Las tablas nuevas usan `utf8mb4_unicode_ci` para soportar emojis y caracteres especiales

### SMTP
- El sistema usa la configuración de `mail_config.php` (no necesitas modificar nada)
- Los emails se encolan automáticamente, no se envían directamente
- Ventaja: si falla el SMTP, se reintentará automáticamente

### Seguridad en producción
- Considera implementar **Google reCAPTCHA v3** en el futuro
- Monitorea los logs de `password_reset_attempts` para detectar patrones de abuso
- Verifica que tu servidor SMTP tenga límites de envío adecuados
- Usa HTTPS en producción (los tokens se envían por URL)

### Performance
- Los intentos antiguos se limpian automáticamente en cada solicitud
- Considera un cron job para limpiar `password_reset_log` mensualmente:
  ```sql
  DELETE FROM password_reset_log WHERE token_generated_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
  DELETE FROM password_reset_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 7 DAY);
  ```

---

## 🐛 Troubleshooting

### No llega el email de recuperación

1. **Verificar cola:**
   ```sql
   SELECT * FROM email_queue WHERE subject LIKE '%Recuperación%' ORDER BY id DESC LIMIT 5;
   ```

2. **Ver errores:**
   ```sql
   SELECT id, recipient_email, status, last_error 
   FROM email_queue 
   WHERE status IN ('failed', 'permanent_error') 
   ORDER BY id DESC LIMIT 10;
   ```

3. **Probar SMTP:**
   - Ve a `test_smtp.php` en el navegador
   - Revisa las credenciales en `mail_config.php`

### Token inválido/expirado

1. **Verificar expiración:**
   ```sql
   SELECT username, reset_token_expira, 
          TIMESTAMPDIFF(MINUTE, NOW(), reset_token_expira) as minutos_restantes
   FROM usuarios 
   WHERE reset_token IS NOT NULL;
   ```

2. **Extender manualmente (solo pruebas):**
   ```sql
   UPDATE usuarios 
   SET reset_token_expira = DATE_ADD(NOW(), INTERVAL 1 HOUR) 
   WHERE username = 'usuario_prueba';
   ```

### Rate limit muy restrictivo

Si necesitas ajustar los límites, edita en `procesar_recuperacion.php`:

```php
// Línea ~35: Cambiar de 3 a 5 intentos por email
if ($email_attempts >= 5) { // era 3

// Línea ~44: Cambiar de 10 a 20 intentos por IP
if ($ip_attempts >= 20) { // era 10
```

---

## 📊 Estadísticas de Uso

Consultas útiles para monitoreo:

```sql
-- Intentos de recuperación en las últimas 24 horas
SELECT COUNT(*) as total_intentos, 
       COUNT(DISTINCT email) as emails_unicos,
       COUNT(DISTINCT ip_address) as ips_unicas
FROM password_reset_attempts 
WHERE attempted_at > DATE_SUB(NOW(), INTERVAL 24 HOUR);

-- Recuperaciones exitosas esta semana
SELECT COUNT(*) as recuperaciones_exitosas
FROM password_reset_log 
WHERE action = 'password_changed' 
  AND token_used_at > DATE_SUB(NOW(), INTERVAL 7 DAY);

-- Usuarios que más intentan recuperar (posible abuso)
SELECT email, COUNT(*) as intentos
FROM password_reset_attempts
WHERE attempted_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY email
HAVING intentos > 5
ORDER BY intentos DESC;
```

---

## 🎯 Próximas Mejoras (Opcionales)

- [ ] Google reCAPTCHA v3 para prevenir bots
- [ ] Autenticación de 2 factores (2FA/MFA)
- [ ] Preguntas de seguridad como alternativa
- [ ] Notificación SMS (Twilio/similar)
- [ ] Historial de cambios de contraseña en el perfil
- [ ] Política de contraseñas más estricta (mayúsculas, números, símbolos)
- [ ] Blacklist de contraseñas comunes
- [ ] Verificación de email al registrarse

---

## ✅ Checklist de Implementación

- [x] Ejecutar SQL para crear tablas
- [ ] Probar flujo completo de recuperación
- [ ] Verificar que llegan los emails
- [ ] Verificar notificación de cambio de contraseña
- [ ] Probar rate limiting
- [ ] Probar tokens expirados
- [ ] Configurar cron job para procesar cola (opcional)
- [ ] Documentar proceso para el equipo

---

## 📞 Soporte

Si encuentras problemas:

1. Revisa los logs de PHP: `error_log` o `/var/log/apache2/error.log`
2. Verifica la tabla `email_queue` para errores de SMTP
3. Consulta la tabla `password_reset_log` para auditoría
4. Ejecuta `test_smtp.php` para diagnosticar problemas de email

---

**Última actualización:** 3 de Marzo, 2026  
**Versión del sistema:** 2.0 - Recuperación de Contraseña Segura
