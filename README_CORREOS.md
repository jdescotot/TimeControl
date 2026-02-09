# 🚀 GUÍA RÁPIDA - Sistema de Correos Masivos

## ¿Qué es esto?

Un sistema web para enviar correos masivos en lotes seguros **directamente desde el navegador**.

---

## 3 Pasos para Empezar

### 1️⃣ Redacta
```
http://tudominio.com/enviar_correo.php
```
- Asunto
- Contenido HTML
- Destinatarios (manual, CSV, o BD)
- Adjuntos (PDF, imágenes, etc)

### 2️⃣ Encola
Click: **"Enviar Correos"**
→ Se guardan en cola automáticamente

### 3️⃣ Procesa
```
http://tudominio.com/estado_envios.php
```
Click: **"▶ 1 Lote"** o **"▶ 3 Lotes"** o **"▶ 5 Lotes"**
→ Envío automático en lotes seguros

---

## URLs Principales

| URL | Propósito |
|-----|-----------|
| `/enviar_correo.php` | Redactar correos |
| `/estado_envios.php` | Ver cola y procesar |
| `/procesar_cola.php` | Procesar lotes (se abre al hacer click) |

---

## 📁 Archivos Nuevos

```
procesar_cola.php          ← Nuevo archivo principal
```

---

## 📁 Archivos Actualizados

```
enviar_correo.php          ← Instrucciones mejoradas
estado_envios.php          ← Panel de procesamiento nuevo
estado_envios.css          ← Estilos nuevos
```

---

## 📚 Documentación

**Comienza aquí:** [SUMARIO_EJECUTIVO.md](SUMARIO_EJECUTIVO.md)

Luego lee:
1. [INSTALACION.md](INSTALACION.md) - Overview
2. [VERIFICACION_RAPIDA.md](VERIFICACION_RAPIDA.md) - Setup
3. [EJEMPLOS_USO.md](EJEMPLOS_USO.md) - Casos prácticos

---

## ✅ Capacidades

- ✅ Enviar a 1 o 10,000+ contactos
- ✅ Importar de BD, CSV o manual
- ✅ Adjuntar múltiples archivos
- ✅ Soporte HTML en contenido
- ✅ Pausas automáticas SMTP
- ✅ Manejo de errores
- ✅ Monitoreo en tiempo real

---

## ⚙️ Requisitos

- PHP 7.4+
- MySQL/MariaDB
- PHPMailer (Composer)
- `mail_config.php` con SMTP

---

## 🧪 Prueba Rápida

```
1. Abre http://localhost/enviar_correo.php
2. Redacta un correo de prueba
3. Ingresa 3 emails de prueba
4. Click "Enviar Correos"
5. Ve a http://localhost/estado_envios.php
6. Click "▶ 1 Lote"
7. Verifica resultados
```

---

## 💡 Ejemplo Real

**Usuario envía comunicado a 200 empleados:**

```
1. Abre enviar_correo.php
   ↓ Redacta: "Cambio de horario"
   ↓ Selecciona: "Importar de BD"
   ↓ Click: "Enviar Correos"

2. Sistema encola 200 correos automáticamente

3. Abre estado_envios.php
   ↓ Ve: "⏳ Hay 200 correos en cola"
   ↓ Click: "▶ 5 Lotes" (250 máximo)
   ↓ Espera completar

4. Resultado:
   ✅ 200 enviados
   ⏳ 0 en cola
   ❌ 0 errores
```

---

## 🔒 Seguridad

✓ Validación de emails
✓ SQL injection prevented
✓ HTML sanitized
✓ Error handling completo

---

## 🎯 Estado

```
✅ IMPLEMENTADO
✅ DOCUMENTADO  
✅ READY TO USE
```

---

## 📞 Ayuda

1. Lee [INDICE_DOCUMENTACION.md](INDICE_DOCUMENTACION.md)
2. Busca tu pregunta en el índice
3. Sigue el documento recomendado

---

**¡Ya estás listo para enviar correos masivos desde el navegador!** 🚀

Próximo paso: Abre [SUMARIO_EJECUTIVO.md](SUMARIO_EJECUTIVO.md)
