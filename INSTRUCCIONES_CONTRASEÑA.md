# ⚠️ PROBLEMA IDENTIFICADO - CONTRASEÑA INCORRECTA

## 🔴 Error Actual:
```
535 Authentication credentials invalid
```

**El servidor SMTP de IONOS está rechazando la contraseña `Macarena.1710`**

---

## ✅ SOLUCIÓN PASO A PASO:

### Paso 1: Obtén la Contraseña Correcta

Tienes **3 opciones** para obtener la contraseña correcta:

#### **Opción A: Usar la Contraseña del Webmail** (Más Rápida)

1. Ve a https://webmail.ionos.es/
2. Intenta iniciar sesión con:
   - Email: `rcalatayud@hosturjaen.es`
   - Contraseña: `Macarena.1710`
3. **Si NO puedes entrar** → Necesitas recuperar la contraseña:
   - Haz clic en "¿Olvidaste tu contraseña?"
   - Sigue los pasos para restablecerla
   - Anota la nueva contraseña

#### **Opción B: Generar Contraseña de Aplicación** (Recomendada por IONOS)

1. Ve a https://my.ionos.es/
2. Inicia sesión con tu cuenta de IONOS
3. Ve a: **Correo electrónico** → **Buzones de correo**
4. Selecciona el buzón: `rcalatayud@hosturjaen.es`
5. Busca la opción **"Contraseña de aplicación"** o **"App Password"**
6. Haz clic en **"Generar nueva contraseña de aplicación"**
7. Copia la contraseña generada (será algo como: `abcd-efgh-ijkl-mnop`)
8. **Usa esta contraseña** en lugar de la contraseña normal

#### **Opción C: Contactar Soporte IONOS**

Si no puedes acceder:
- **Teléfono**: 900 102 413
- **Email**: info@ionos.es
- Pide ayuda para recuperar el acceso SMTP

---

### Paso 2: Actualizar los Archivos de Configuración

**Debes actualizar la contraseña en AMBOS archivos:**

#### 1. Edita `mail_config.php` (en la raíz del proyecto)

```php
'smtp' => [
    'host'     => 'smtp.ionos.es',
    'port'     => 587,
    'user'     => 'rcalatayud@hosturjaen.es',
    'pass'     => 'TU_CONTRASEÑA_CORRECTA_AQUI',  // ← Cambia esto
    'secure'   => 'tls',
],
```

#### 2. Edita `mail/config.php` (en la carpeta mail)

```php
'smtp' => [
    'host'     => 'smtp.ionos.es',
    'port'     => 587,
    'user'     => 'rcalatayud@hosturjaen.es',
    'pass'     => 'TU_CONTRASEÑA_CORRECTA_AQUI',  // ← Cambia esto también
    'secure'   => 'tls',
],
```

**⚠️ IMPORTANTE:** La contraseña debe ser **exactamente la misma** en ambos archivos.

---

### Paso 3: Probar la Conexión

1. Abre en tu navegador:
   ```
   http://tu-servidor/test_smtp.php
   ```

2. Ingresa tu email para recibir una prueba

3. Haz clic en **"Enviar correo de prueba"**

4. **Si funciona:**
   - Verás: ✅ Correo enviado exitosamente
   - Recibirás el email de prueba

5. **Si sigue fallando:**
   - Revisa el debug log nuevamente
   - Comparte el error conmigo

---

## 📝 NOTAS IMPORTANTES:

### Caracteres Especiales en la Contraseña

Si tu contraseña tiene caracteres especiales como: `@ # $ % & / ( ) = ? ¡ ! ¿ '`

**Asegúrate de:**
- Copiarla exactamente como es
- NO agregar espacios al inicio o al final
- Ponerla entre comillas simples en PHP: `'pass' => 'Mi$Contraseña123',`

### Seguridad

**No compartas las contraseñas conmigo ni con nadie.** Solo actualiza los archivos en tu servidor.

### Verificación

Después de actualizar, verifica que ambos archivos tengan la misma contraseña:

```bash
# En PowerShell:
cd c:\Users\jdani\Downloads\TimeControl
Select-String -Path "mail_config.php","mail/config.php" -Pattern "'pass'"
```

Deberías ver la misma contraseña en ambos archivos.

---

## 🎯 SIGUIENTE PASO:

1. **Obtén la contraseña correcta** (Opción A, B o C)
2. **Actualiza** ambos archivos de configuración
3. **Prueba** en http://tu-servidor/test_smtp.php
4. **Confirma** que funciona

---

**¿Necesitas ayuda para obtener la contraseña correcta de IONOS?**
