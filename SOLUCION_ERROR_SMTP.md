# 🔧 Guía de Solución: Error SMTP Authentication (IONOS)

## ❌ Problema
```
SMTP Error: Could not authenticate.
```

Este error significa que el servidor SMTP de IONOS está rechazando las credenciales proporcionadas.

---

## ✅ SOLUCIONES (Prueba en este orden)

### 🎯 Solución 1: Verificar Credenciales Básicas

1. **Abre `mail_config.php`** y verifica:
   ```php
   'user' => 'rcalatayud@hosturjaen.es',  // ✓ Email completo
   'pass' => 'PaseoDeLaEstacion.30',      // ✓ Contraseña exacta
   ```

2. **Prueba iniciar sesión** en el webmail de IONOS:
   - Ve a https://webmail.ionos.es/
   - Usa el mismo email y contraseña
   - Si NO puedes entrar → la contraseña es incorrecta

---

### 🎯 Solución 2: Contraseña de Aplicación (MÁS COMÚN)

**IONOS puede requerir una "Contraseña de Aplicación" en lugar de tu contraseña normal.**

#### Pasos para crear una Contraseña de Aplicación en IONOS:

1. **Inicia sesión** en https://my.ionos.es/
2. **Ve a**: Correo electrónico → Tu dominio → Buzones de correo
3. **Selecciona** el buzón `rcalatayud@hosturjaen.es`
4. **Busca** la opción "Contraseña de aplicación" o "App Password"
5. **Genera** una nueva contraseña de aplicación
6. **Copia** esa contraseña (será algo como: `abcd-efgh-ijkl-mnop`)
7. **Actualiza** `mail_config.php`:
   ```php
   'pass' => 'abcd-efgh-ijkl-mnop',  // Nueva contraseña de aplicación
   ```
8. **Guarda** y prueba de nuevo

---

### 🎯 Solución 3: Verificar Configuración SMTP

Asegúrate de que estos valores sean exactos:

```php
'smtp' => [
    'host'     => 'smtp.ionos.es',     // ✓ Correcto
    'port'     => 587,                 // ✓ Correcto (TLS)
    'user'     => 'rcalatayud@hosturjaen.es',  // ✓ Email completo
    'pass'     => 'TU_CONTRASEÑA',     // ⚠️ Verifica esto
    'secure'   => 'tls',               // ✓ Correcto
],
```

**Alternativa:** Puedes probar el puerto 465 con SSL:
```php
'port'     => 465,
'secure'   => 'ssl',
```

---

### 🎯 Solución 4: Desactivar Autenticación de Dos Factores (2FA)

Si tienes **2FA activado** en tu cuenta IONOS:
- Necesitas usar una **Contraseña de Aplicación** (ver Solución 2)
- O temporalmente desactivar 2FA para pruebas

---

### 🎯 Solución 5: Verificar Límites y Bloqueos

**IONOS puede bloquear el acceso SMTP si:**
1. Has excedido el límite de envíos (ej: 500/día)
2. Detectaron actividad sospechosa
3. Tu IP está bloqueada

**Solución:**
- Contacta al soporte de IONOS
- Verifica tu panel de control por notificaciones
- Revisa si hay límites activos en tu plan

---

### 🎯 Solución 6: Probar Servidor SMTP Alternativo

IONOS tiene servidores alternativos:

```php
'host' => 'smtp.ionos.com',  // En lugar de smtp.ionos.es
```

O por país:
```php
'host' => 'smtp-mail.outlook.com',  // Si IONOS usa Microsoft
```

---

## 🧪 CÓMO DIAGNOSTICAR

### Paso 1: Usar la Herramienta de Diagnóstico

1. **Abre en tu navegador:**
   ```
   http://tu-servidor.com/test_smtp.php
   ```

2. **Ingresa tu email** para recibir una prueba

3. **Revisa el Debug Log** que aparece abajo
   - Busca líneas que digan `AUTH LOGIN` o `AUTH PLAIN`
   - Busca códigos de error (535, 535-5.7.0, etc.)

### Paso 2: Interpretar los Códigos de Error

- **535 Authentication failed**: Credenciales incorrectas
- **535-5.7.0**: Requiere contraseña de aplicación
- **454 TLS not available**: Problema con TLS/SSL
- **Connection timeout**: Puerto bloqueado o firewall

---

## 📋 CHECKLIST DE VERIFICACIÓN

Marca cada punto que hayas verificado:

- [ ] La contraseña en `mail_config.php` es correcta
- [ ] Puedes iniciar sesión en webmail con las mismas credenciales
- [ ] Has probado con una Contraseña de Aplicación
- [ ] El puerto 587 está abierto en tu servidor
- [ ] Has probado `test_smtp.php` y revisado el debug log
- [ ] No has excedido los límites de envío de IONOS
- [ ] El email remitente pertenece al dominio configurado en IONOS

---

## 🚀 PRUEBA RÁPIDA

**Ejecuta este test desde terminal:**

```bash
cd c:\Users\jdani\Downloads\TimeControl
php -r "
\$config = include 'mail_config.php';
\$smtp = \$config['smtp'];
echo 'Probando conexión a ' . \$smtp['host'] . ':' . \$smtp['port'] . \"...\n\";
\$socket = @fsockopen(\$smtp['host'], \$smtp['port'], \$errno, \$errstr, 10);
if (\$socket) {
    echo \"✅ Conexión exitosa!\n\";
    fclose(\$socket);
} else {
    echo \"❌ Error de conexión: \$errstr (\$errno)\n\";
}
"
```

---

## 📞 SOPORTE IONOS

Si nada funciona, contacta al soporte de IONOS:

- **Teléfono**: 900 102 413
- **Web**: https://www.ionos.es/ayuda/
- **Email**: info@ionos.es

**Pregunta específicamente:**
- "¿Necesito una contraseña de aplicación para SMTP?"
- "¿Hay algún bloqueo en mi cuenta para envío SMTP?"
- "¿Cuáles son los límites de envío de mi plan?"

---

## 🎯 SIGUIENTE PASO RECOMENDADO

1. **PRIMERO:** Abre `test_smtp.php` en tu navegador
2. **Envía un correo de prueba** a tu propio email
3. **Revisa el Debug Log** para ver el error exacto
4. **Aplica la solución** según el código de error que veas

---

**Última actualización:** 2026-02-24
