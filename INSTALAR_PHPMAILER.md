# 🔧 Cómo Instalar PHPMailer en IONOS

## ⚡ El Problema

Tu servidor IONOS no tiene PHPMailer instalado. Sin él, no puedes enviar correos.

## ✅ Soluciones

### **Opción 1: Por SSH (La Mejor)**

Si tu servidor IONOS tiene **acceso SSH** habilitado:

1. Abre Terminal / PuTTY / MobaXterm
2. Conecta a tu servidor:
```bash
ssh usuario@tudominio.com
```

3. Navega a tu carpeta:
```bash
cd public_html/TimeControl
# o si está en otra carpeta:
# cd ~/TimeControl
```

4. Instala PHPMailer:
```bash
composer require phpmailer/phpmailer
```

5. Verifica que creó la carpeta `vendor`:
```bash
ls -la vendor/
```

**Listo.** Ya funciona.

---

### **Opción 2: Usar el Instalador Web**

Si NO tienes acceso SSH:

1. **Sube este archivo a tu servidor:**
   - Archivo: `instalador_phpmailer.php`
   - Ubicación: `https://tudominio.com/instalador_phpmailer.php`

2. **Abre en navegador:**
   ```
   http://tudominio.com/instalador_phpmailer.php
   ```

3. **Sigue las instrucciones** que muestra la página

---

### **Opción 3: Descarga Manual**

Si las opciones anteriores no funcionan:

#### Paso 1: Descarga PHPMailer
1. Ve a: https://github.com/PHPMailer/PHPMailer/releases
2. Descarga el ZIP más reciente (ej: `v6.x.x`)
3. Descomprime en tu PC

#### Paso 2: Sube al servidor
1. Usando FileZilla o Cyberduck:
   - Crea carpeta: `/TimeControl/vendor/phpmailer/phpmailer/`
   - Copia los archivos de PHPMailer ahí
   
2. Estructura final debe ser:
```
/TimeControl/
├── vendor/
│   ├── autoload.php (crear este)
│   └── phpmailer/
│       └── phpmailer/
│           ├── src/
│           ├── language/
│           └── ...
├── enviar_correo.php
├── procesar_cola.php
└── ...
```

#### Paso 3: Crea archivo autoload.php
Crea `/vendor/autoload.php` con este contenido:

```php
<?php
// Autoload manual para PHPMailer

$vendor_dir = __DIR__ . '/phpmailer/phpmailer/src';

spl_autoload_register(function ($class) {
    global $vendor_dir;
    $file = $vendor_dir . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Para PHPMailer
require __DIR__ . '/phpmailer/phpmailer/src/PHPMailer.php';
require __DIR__ . '/phpmailer/phpmailer/src/SMTP.php';
require __DIR__ . '/phpmailer/phpmailer/src/Exception.php';
```

---

## 🔍 Verificar Instalación

### Por SSH:
```bash
ls vendor/phpmailer/phpmailer/src/PHPMailer.php
```
Debe mostrar: `/path/to/vendor/phpmailer/phpmailer/src/PHPMailer.php`

### Por Navegador:
1. Abre: `http://tudominio.com/diagnostico.php`
2. Si ves ✓ en "PHPMailer" → Está instalado
3. Si ves ✗ → Sigue intentando

---

## 📞 Si Nada Funciona

### Opción A: Contacta a IONOS
```
Soporte IONOS
- Pide que instalen Composer en tu cuenta
- Pide que ejecuten: composer require phpmailer/phpmailer
- Ubicación: /home/tu_usuario/public_html/TimeControl/
```

### Opción B: Usa nuestro Servicio SMTP Alternativo
Si IONOS no puede instalar, usa: **SendGrid**, **Mailgun** o **AWS SES**

Estos tienen API HTTP en lugar de SMTP tradicional.

---

## ✅ Después de Instalar

1. Abre: `http://tudominio.com/diagnostico.php`
   - Verifica que todo esté en verde ✓

2. Abre: `http://tudominio.com/enviar_correo.php`
   - Redacta un correo de prueba
   - Haz clic "Enviar Correos"

3. Abre: `http://tudominio.com/estado_envios.php`
   - Haz clic "▶ Procesar 1 Lote"
   - Verifica que los correos se enviaron ✓

---

## 🎯 Resumen Rápido

| Paso | Acción |
|------|--------|
| 1 | Conecta por SSH (Opción 1) O sube instalador_phpmailer.php (Opción 2) |
| 2 | Ejecuta instalación |
| 3 | Verifica con diagnostico.php |
| 4 | ¡Listo! Envía correos |

---

**¿Cuál opción prefieres? Necesitas acceso SSH de tu servidor IONOS para elegir.**
