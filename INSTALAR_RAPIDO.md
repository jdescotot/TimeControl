# 🚀 INSTALAR PHPMailer - GUÍA RÁPIDA (3 PASOS)

## El Problema
```
ERROR: PHPMailer no está instalado
```

## La Solución (Elige UNA opción)

---

## ✅ OPCIÓN 1: SSH (La Mejor - 2 minutos)

**Tienes acceso SSH en IONOS:**

1. **Conecta por SSH** a tu servidor IONOS
   ```bash
   ssh usuario@tudominio.com
   ```

2. **Navega a tu carpeta TimeControl**
   ```bash
   cd public_html/TimeControl
   ```

3. **Instala PHPMailer**
   ```bash
   composer require phpmailer/phpmailer
   ```

4. **Listo.** Abre en navegador:
   ```
   http://tudominio.com/enviar_correo.php
   ```

---

## ✅ OPCIÓN 2: Sin SSH (5 minutos)

**No tienes SSH disponible:**

### Paso 1: Descarga PHPMailer
1. Ve a: https://github.com/PHPMailer/PHPMailer/releases
2. Haz clic en **"Source code (zip)"** del último release
3. Descomprime el ZIP

### Paso 2: Sube a IONOS
Usando **File Manager** de IONOS o **FileZilla**:

1. Crea esta estructura de carpetas:
   ```
   TimeControl/
   └── vendor/
       └── phpmailer/
           └── phpmailer/
   ```

2. Sube todo el contenido descargado a esa carpeta

3. La estructura final debe ser:
   ```
   TimeControl/vendor/phpmailer/phpmailer/
   ├── src/
   │   ├── Exception.php
   │   ├── PHPMailer.php
   │   ├── SMTP.php
   │   └── ...
   ├── language/
   ├── composer.json
   └── ...
   ```

### Paso 3: Crea autoload.php
En `TimeControl/vendor/` crea un archivo `autoload.php` con este contenido:

```php
<?php
spl_autoload_register(function ($class) {
    if (strpos($class, 'PHPMailer\\PHPMailer\\') === 0) {
        $class_name = str_replace('PHPMailer\\PHPMailer\\', '', $class);
        $file = __DIR__ . '/phpmailer/phpmailer/src/' . $class_name . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

require __DIR__ . '/phpmailer/phpmailer/src/PHPMailer.php';
require __DIR__ . '/phpmailer/phpmailer/src/SMTP.php';
require __DIR__ . '/phpmailer/phpmailer/src/Exception.php';
```

4. **Listo.** Abre en navegador:
   ```
   http://tudominio.com/enviar_correo.php
   ```

---

## ✅ OPCIÓN 3: Instalar desde Navegador (Automático)

1. Sube este archivo a tu servidor:
   ```
   instalador_phpmailer.php
   ```

2. Abre en navegador:
   ```
   http://tudominio.com/instalador_phpmailer.php
   ```

3. Sigue las instrucciones en la página

---

## 🔍 Verificar que Funciona

Después de instalar PHPMailer, abre en navegador:

```
http://tudominio.com/diagnostico.php
```

Deberías ver una pantalla verde ✓ en "PHPMailer" si está correcto.

Si ves rojo ✗, necesitas revisar la instalación.

---

## 📊 Comparación de Opciones

| Opción | Dificultad | Tiempo | Requisito |
|--------|-----------|--------|-----------|
| SSH | Fácil | 2 min | Acceso SSH |
| Manual | Media | 5 min | FileZilla |
| Auto | Fácil | 3 min | Ninguno |

---

## ❓ ¿Cuál Elegir?

- **¿Tienes SSH?** → OPCIÓN 1
- **¿Tienes FileZilla?** → OPCIÓN 2
- **¿No sabes qué es eso?** → OPCIÓN 3

---

## ✅ Después de Instalar

1. Abre: http://tudominio.com/enviar_correo.php
2. Redacta un correo
3. Click "Enviar Correos"
4. Abre: http://tudominio.com/estado_envios.php
5. Click "▶ Procesar 1 Lote"
6. ¡Listo! Correos enviados ✓

---

## 🆘 Si Sigue Sin Funcionar

1. **Verifica con:** http://tudominio.com/diagnostico.php
2. **Revisa carpetas con:** http://tudominio.com/ver_cola.php
3. **Lee:** INSTALAR_PHPMAILER.md (guía completa)
4. **Contacta IONOS** si aún hay problemas

---

**¿Cuál opción vas a intentar primero?**
