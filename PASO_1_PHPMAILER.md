# 🔴 PASO 1: INSTALA PHPMailer

## ¿Por Qué Este Error?

Tu servidor **NO TIENE** instalado el software **PHPMailer** que permite enviar correos.

Es como si necesitaras un correo (email) y no tuvieras cliente de email.

## ✅ Cómo Resolverlo

### **Lo MÁS RÁPIDO (Recomendado)**

Tienes 3 opciones. Sigue la más fácil para ti:

---

### **OPCIÓN A: Acceso SSH** (2 minutos)

Si tu proveedor IONOS te dio usuario SSH:

```bash
# Conecta por terminal
ssh usuario@hosturjaen.es

# Navega a TimeControl
cd public_html/TimeControl

# Instala PHPMailer
composer require phpmailer/phpmailer

# ¡Listo!
```

---

### **OPCIÓN B: Sin SSH - Descarga Manual** (5 minutos)

1. **Descarga PHPMailer:**
   - Ve a: https://github.com/PHPMailer/PHPMailer
   - Click en "Code" → "Download ZIP"
   - Descomprime

2. **Sube a tu servidor** (con FileZilla o panel IONOS):
   - Crea carpeta: `TimeControl/vendor/phpmailer/phpmailer/`
   - Copia los archivos descargados ahí

3. **Crea archivo autoload.php:**
   - Ubicación: `TimeControl/vendor/autoload.php`
   - Contenido: [Ver INSTALAR_PHPMAILER.md]

---

### **OPCIÓN C: Sin Comprar Nada** (3 minutos)

1. **Sube a tu servidor:** `instalador_phpmailer.php`
2. **Abre en navegador:** `http://tudominio.com/instalador_phpmailer.php`
3. **Sigue instrucciones** en la página

---

## 🎯 Entonces...

```
PASO 1: Instala PHPMailer
    ↓
PASO 2: Verifica con diagnostico.php
    ↓
PASO 3: Envía correos masivos
```

---

## 📁 Archivos que Te Ayudan

| Archivo | Propósito |
|---------|-----------|
| [INSTALAR_RAPIDO.md](INSTALAR_RAPIDO.md) | Guía rápida (3 opciones) |
| [INSTALAR_PHPMAILER.md](INSTALAR_PHPMAILER.md) | Guía detallada |
| [instalador_phpmailer.php](instalador_phpmailer.php) | Instalador automático web |
| [vendor_autoload_manual.php](vendor_autoload_manual.php) | Autoload manual si no tienes composer |

---

## ⚡ Recomendación

**Intenta OPCIÓN A (SSH) primero:**
- Es lo más rápido
- Es lo más seguro
- Es lo más confiable

**Si no tienes SSH, intenta OPCIÓN B:**
- Descarga + upload manual
- Un poco más lento
- Pero funciona igual

**Si nada funciona, usa OPCIÓN C:**
- Instalador web automático
- Zero requisitos
- El más fácil

---

## ✅ Después de Instalar

1. Abre: http://tudominio.com/diagnostico.php
2. Deberías ver ✓ (verde) en PHPMailer
3. ¡Listo! Ya puedes enviar correos

---

**¿Qué opción vas a intentar?**

**Una vez instalado, vuelve aquí y continúa con PASO 2.**
