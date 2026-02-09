# 📚 ÍNDICE DE DOCUMENTACIÓN - Sistema de Correos Masivos

## 🎯 Comienza Aquí

Si es tu primera vez usando el sistema, lee en este orden:

1. **[INSTALACION.md](INSTALACION.md)** ← Empieza aquí
   - Resumen de qué se hizo
   - Cómo empezar rápidamente
   - Verificación de funcionamiento

2. **[VERIFICACION_RAPIDA.md](VERIFICACION_RAPIDA.md)**
   - Checklist de instalación
   - Troubleshooting
   - Pruebas paso a paso

3. **[EJEMPLOS_USO.md](EJEMPLOS_USO.md)**
   - 8 casos de uso prácticos
   - Instrucciones detalladas
   - Tips y mejores prácticas

4. **[CORREO_MASIVO_README.md](CORREO_MASIVO_README.md)**
   - Guía completa del sistema
   - Configuración avanzada
   - Notas de seguridad

5. **[ARQUITECTURA_CORREOS.md](ARQUITECTURA_CORREOS.md)**
   - Diagramas técnicos
   - Arquitectura de BD
   - Flujos de datos

---

## 📖 Documentación por Tipo

### 🚀 Para Empezar (Usuarios)
- [INSTALACION.md](INSTALACION.md) - Overview y quick start
- [EJEMPLOS_USO.md](EJEMPLOS_USO.md) - Casos de uso prácticos
- [VERIFICACION_RAPIDA.md](VERIFICACION_RAPIDA.md) - Setup y troubleshooting

### 🛠️ Para Administradores
- [VERIFICACION_RAPIDA.md](VERIFICACION_RAPIDA.md) - Instalación y monitoreo
- [CORREO_MASIVO_README.md](CORREO_MASIVO_README.md) - Configuración completa
- [ARQUITECTURA_CORREOS.md](ARQUITECTURA_CORREOS.md) - Estructura técnica

### 👨‍💻 Para Desarrolladores
- [ARQUITECTURA_CORREOS.md](ARQUITECTURA_CORREOS.md) - Diagramas y flujos
- [CORREO_MASIVO_README.md](CORREO_MASIVO_README.md) - Detalles de implementación
- [CAMBIOS_RESUMO.md](CAMBIOS_RESUMO.md) - Qué cambió en el código

### 🔧 Para Soporte Técnico
- [VERIFICACION_RAPIDA.md](VERIFICACION_RAPIDA.md) - Troubleshooting
- [EJEMPLOS_USO.md](EJEMPLOS_USO.md) - Casos de uso
- [CAMBIOS_RESUMO.md](CAMBIOS_RESUMO.md) - Cambios de código

---

## 📁 Estructura de Archivos

```
TimeControl/
├── 📄 INSTALACION.md              ← Lee primero
├── 📄 VERIFICACION_RAPIDA.md      ← Setup y troubleshooting
├── 📄 EJEMPLOS_USO.md             ← Casos prácticos
├── 📄 CORREO_MASIVO_README.md     ← Guía completa
├── 📄 ARQUITECTURA_CORREOS.md     ← Diagramas técnicos
├── 📄 CAMBIOS_RESUMO.md           ← Qué cambió
├── 📄 INDICE_DOCUMENTACION.md     ← Este archivo
│
├── 🆕 procesar_cola.php           ← Nuevo: Procesa cola desde web
├── ✏️ enviar_correo.php           ← Actualizado: Instrucciones mejoradas
├── ✏️ estado_envios.php           ← Actualizado: Panel de procesamiento
├── ✏️ estado_envios.css           ← Actualizado: Estilos nuevos
│
├── process_send.php               ← Sin cambios: Encola correos
├── worker_send.php                ← Sin cambios: Alternativa CLI
├── mail_config.php                ← Config SMTP (no versionar)
│
└── (otros archivos del proyecto)
```

---

## 🎯 Guías Rápidas

### ¿Cómo envío correos masivos?

1. Abre **enviar_correo.php**
2. Completa: Asunto, Cuerpo, Destinatarios, Adjuntos
3. Click **"Enviar Correos"**
4. Ve a **estado_envios.php**
5. Click **"▶ Procesar Lotes"**

**Lee:** [EJEMPLOS_USO.md](EJEMPLOS_USO.md) Caso 1

---

### ¿Cómo configuro SMTP?

1. Copia `mail_config.php.example` a `mail_config.php`
2. Completa credenciales SMTP
3. Configura tamaño de lotes
4. Prueba conexión

**Lee:** [CORREO_MASIVO_README.md](CORREO_MASIVO_README.md) - Sección "Configuración"

---

### ¿Cómo soluciono problemas?

1. Verifica que `procesar_cola.php` exista
2. Comprueba tabla `email_queue` en BD
3. Revisa logs de PHP y MySQL
4. Consulta troubleshooting

**Lee:** [VERIFICACION_RAPIDA.md](VERIFICACION_RAPIDA.md) - Sección "Troubleshooting"

---

### ¿Cuál es la diferencia entre web y CLI?

| Aspecto | Web | CLI |
|--------|-----|-----|
| Acceso | Navegador | Terminal SSH |
| Interfaz | Gráfica | Línea de comandos |
| Control | Manual por lotes | Automático |
| Comando | Click botón | `php worker_send.php` |

**Lee:** [ARQUITECTURA_CORREOS.md](ARQUITECTURA_CORREOS.md) - Ventajas web vs CLI

---

## 🔍 Búsqueda Rápida

### Necesito...

**Enviar a empleados de BD**
→ [EJEMPLOS_USO.md](EJEMPLOS_USO.md) - Caso 1

**Importar CSV de contactos**
→ [EJEMPLOS_USO.md](EJEMPLOS_USO.md) - Caso 3

**Adjuntar PDF**
→ [EJEMPLOS_USO.md](EJEMPLOS_USO.md) - Caso 4

**Procesar 1000+ correos**
→ [EJEMPLOS_USO.md](EJEMPLOS_USO.md) - Caso 7

**Reintentar fallidos**
→ [EJEMPLOS_USO.md](EJEMPLOS_USO.md) - Caso 6

**Configurar SMTP Gmail**
→ [CORREO_MASIVO_README.md](CORREO_MASIVO_README.md) - Sección "Configuración"

**Entender arquitectura**
→ [ARQUITECTURA_CORREOS.md](ARQUITECTURA_CORREOS.md)

**Instalar y verificar**
→ [VERIFICACION_RAPIDA.md](VERIFICACION_RAPIDA.md)

---

## ⚡ Comandos Útiles

### Terminal

```bash
# Procesar cola desde terminal
cd /ruta/TimeControl
php worker_send.php

# Ver correos en cola
mysql -u user -p database -e "SELECT * FROM email_queue WHERE status='queued';"

# Contar por estado
mysql -u user -p database -e "SELECT status, COUNT(*) FROM email_queue GROUP BY status;"

# Limpiar correos antiguos
mysql -u user -p database -e "DELETE FROM email_queue WHERE sent_at < NOW() - INTERVAL 30 DAY;"
```

### URLs del Sitio

```
Redactar correo:      http://tudominio.com/enviar_correo.php
Ver estado:           http://tudominio.com/estado_envios.php
Procesar cola:        http://tudominio.com/procesar_cola.php
Procesar N lotes:     http://tudominio.com/procesar_cola.php?lotes=3
```

---

## 📞 FAQs Rápidas

**P: ¿Es seguro?**
R: Sí. Incluye validación de emails, sanitización HTML, prepared statements.
→ [CORREO_MASIVO_README.md](CORREO_MASIVO_README.md) - "Notas de Seguridad"

**P: ¿Funciona con Gmail?**
R: Sí. Requiere contraseña de aplicación, no contraseña de cuenta.
→ [CORREO_MASIVO_README.md](CORREO_MASIVO_README.md) - Ejemplo Gmail

**P: ¿Cuántos correos máximo?**
R: 250 por sesión web, ilimitado con múltiples sesiones o CLI.
→ [ARQUITECTURA_CORREOS.md](ARQUITECTURA_CORREOS.md) - "Capacidad"

**P: ¿Se pierden los correos si falla?**
R: No. Se guardan en BD y puedes reintentar.
→ [EJEMPLOS_USO.md](EJEMPLOS_USO.md) - Caso 6

**P: ¿Puedo programar envíos automáticos?**
R: Sí, con cron + worker_send.php
→ [EJEMPLOS_USO.md](EJEMPLOS_USO.md) - Caso 8

---

## 📊 Resumen de Cambios

```
✅ Archivos Nuevos:        1  (procesar_cola.php)
✏️  Archivos Actualizados:  3  (enviar_correo.php, estado_envios.php, estado_envios.css)
📚 Documentación:          6  (README, guías, arquitectura, etc)
```

**Cambios de código:**
- Nuevo formulario de encola ✓
- Nuevo procesamiento de cola ✓
- Nuevos estilos CSS ✓
- Instrucciones mejoradas ✓

**Lo que NO cambió:**
- Estructura BD ✓
- worker_send.php ✓
- Configuración SMTP ✓

---

## 🎓 Nivel de Dificultad

| Tarea | Dificultad | Tiempo |
|-------|-----------|--------|
| Usar el sistema | 🟢 Fácil | 5 min |
| Configurar SMTP | 🟡 Media | 15 min |
| Entender arquitectura | 🔴 Difícil | 30 min |
| Modificar código | 🔴 Difícil | 1+ hora |

---

## 🎯 Checklist de Implementación

- [ ] Leído INSTALACION.md
- [ ] Ejecutado VERIFICACION_RAPIDA.md checklist
- [ ] Probado envío de 5 correos de prueba
- [ ] Procesado cola desde estado_envios.php
- [ ] Verificado que correos tienen status 'sent'
- [ ] Leído EJEMPLOS_USO.md para casos prácticos
- [ ] Configurado permisos de archivos
- [ ] Backup de BD realizado
- [ ] Sistema listo para producción ✓

---

## 🚀 Siguiente Paso

**Recomendado:** Abre [INSTALACION.md](INSTALACION.md) para empezar.

Si ya lo completaste, prueba los [EJEMPLOS_USO.md](EJEMPLOS_USO.md).

---

## 📋 Resumen Final

| Aspecto | Estado |
|--------|--------|
| Implementación | ✅ Completa |
| Documentación | ✅ Completa |
| Testing | ✅ Ready to test |
| Producción | ✅ Ready to deploy |
| Soporte | ✅ Documentado |

---

**Última actualización:** 30 de enero de 2026
**Versión:** 1.0
**Estado:** Listo para usar ✓

¡Disfruta del sistema de correos masivos! 🎉
