# EJEMPLOS DE USO - Sistema de Correos Masivos

## 📧 Caso 1: Envío a Empleados Registrados

### Escenario
Quieres enviar un comunicado a todos los empleados registrados en la base de datos.

### Pasos

1. **Ir a Enviar Correo**
   ```
   URL: http://tudominio.com/enviar_correo.php
   ```

2. **Completar formulario**
   ```
   Asunto: "Comunicado: Cambio de horario"
   
   Cuerpo:
   <h2>Comunicado Importante</h2>
   <p>A partir del próximo lunes cambia el horario:</p>
   <ul>
     <li>Entrada: 09:00</li>
     <li>Salida: 18:00</li>
   </ul>
   
   Destinatarios: Usar datos de BD (automático)
   ```

3. **Enviar**
   - Click "Enviar Correos"
   - Sistema valida y encola automáticamente

4. **Resultado**
   ```
   ✓ Éxito: Se encolaron 145 correo(s) para envío.
   ```

---

## 📋 Caso 2: Envío a Grupo Específico

### Escenario
Quieres enviar un correo solo a 10 empleados específicos (ej: administrativos).

### Pasos

1. **Preparar emails**
   ```
   Copiar emails de los 10 empleados específicos:
   admin1@empresa.com
   admin2@empresa.com
   admin3@empresa.com
   ...
   ```

2. **Ir a Enviar Correo**
   ```
   URL: http://tudominio.com/enviar_correo.php
   ```

3. **Seleccionar entrada manual**
   ```
   Destinatarios: "Entrada manual"
   
   Pegar emails en el textarea:
   admin1@empresa.com
   admin2@empresa.com
   admin3@empresa.com
   ```

4. **Enviar**
   - Click "Enviar Correos"
   
5. **Resultado**
   ```
   ✓ Se encolaron 10 correo(s)
   ```

---

## 📊 Caso 3: Importar desde CSV

### Escenario
Tienes un archivo Excel con una lista de contactos y quieres enviarles información.

### Pasos

1. **Preparar CSV**
   ```csv
   correo@ejemplo.com,Juan Pérez
   otro@ejemplo.com,María García
   tercero@ejemplo.com,Carlos López
   ```

2. **Ir a Enviar Correo**
   ```
   URL: http://tudominio.com/enviar_correo.php
   ```

3. **Seleccionar CSV**
   ```
   Destinatarios: "Subir CSV"
   Click en: [Seleccionar archivo]
   Elegir: contactos.csv
   ```

4. **Completar mensaje**
   ```
   Asunto: "Información importante para usted"
   Cuerpo: "...contenido del correo..."
   ```

5. **Enviar**
   - Click "Enviar Correos"

6. **Resultado**
   ```
   ✓ Se encolaron 3 correo(s)
   ```

---

## 📎 Caso 4: Con Adjuntos PDF

### Escenario
Necesitas enviar un documento PDF (contrato, factura, etc.) a todos.

### Pasos

1. **Ir a Enviar Correo**
   ```
   URL: http://tudominio.com/enviar_correo.php
   ```

2. **Completar datos**
   ```
   Asunto: "Su contrato adjunto"
   Cuerpo: "Por favor, revise el contrato adjunto..."
   Destinatarios: "Entrada manual" o "Base de datos"
   ```

3. **Añadir archivo**
   ```
   Adjuntar archivos: [Seleccionar archivo]
   Click: contrato_2024.pdf
   ```

4. **Vista previa**
   ```
   El PDF se preview automáticamente antes de enviar
   Verifica que sea el correcto
   ```

5. **Enviar**
   - Click "Enviar Correos"

6. **Resultado**
   ```
   ✓ Se encolaron 50 correo(s)
   Todos incluyen: contrato_2024.pdf
   ```

---

## ⚡ Caso 5: Procesar Cola en Lotes

### Escenario
Encolaste 300 correos pero quieres procesarlos en 3 tandas para no sobrecargar el servidor SMTP.

### Pasos

1. **Ver Estado**
   ```
   URL: http://tudominio.com/estado_envios.php
   ```

2. **Observar cola**
   ```
   Panel mostrado:
   ⏳ Hay 300 correo(s) en cola
   
   Estadísticas:
   - 300 en cola
   - 0 enviados
   - 0 con error
   ```

3. **Procesar primer lote**
   ```
   Click: "▶ 1 Lote (50 correos)"
   Esperar a que complete...
   ```

4. **Resultado después lote 1**
   ```
   Lote 1: 50 enviados, 0 errores
   
   Estadísticas actualizadas:
   - 250 en cola
   - 50 enviados
   - 0 con error
   ```

5. **Procesar segundo lote**
   ```
   Click: "▶ 3 Lotes (150 correos)"
   Esperar a que complete...
   
   (Procesa lotes 2, 3 y 4 automáticamente)
   (Pausa 30s entre cada lote)
   ```

6. **Resultado final**
   ```
   Estadísticas finales:
   - 0 en cola ✓
   - 300 enviados ✓
   - 0 con error ✓
   ```

---

## 🔄 Caso 6: Reintentar Correos Fallidos

### Escenario
3 correos fallaron por problema temporal del servidor SMTP. Quieres reintentar.

### Pasos

1. **Ver Estado**
   ```
   URL: http://tudominio.com/estado_envios.php
   ```

2. **Filtrar fallidos**
   ```
   Click en filtro: "Fallidos"
   Observar lista de correos con estado 'failed'
   Ver error en columna "last_error"
   ```

3. **Recuperar con SQL**
   ```sql
   -- Cambiar fallidos a queued para reintentar
   UPDATE email_queue 
   SET status='queued' 
   WHERE status='failed' 
   AND last_error LIKE '%temporary%';
   ```

4. **Procesar nuevamente**
   ```
   Volver a estado_envios.php
   Click: "▶ 1 Lote"
   ```

5. **Resultado**
   ```
   Lote: 3 enviados, 0 errores
   
   Los 3 se reintentaron exitosamente
   ```

---

## 📈 Caso 7: Monitoreo en Tiempo Real

### Escenario
Estás enviando 1000 correos y quieres monitorear el progreso.

### Pasos

1. **Iniciar envío**
   ```
   Encolar 1000 correos en enviar_correo.php
   ```

2. **Abrir Estado en nueva pestaña**
   ```
   URL: http://tudominio.com/estado_envios.php
   ```

3. **Procesar lotes**
   ```
   Click: "▶ 5 Lotes" (250 correos máximo)
   ```

4. **Monitorear**
   ```
   Mientras se procesa, la página muestra:
   - Estadísticas en tiempo real
   - Resultado de cada lote procesado
   - Contador actualizado
   ```

5. **Repetir si es necesario**
   ```
   Si aún hay cola:
   Click nuevamente: "▶ 5 Lotes"
   Repetir hasta vaciar la cola
   ```

6. **Verificar final**
   ```
   Estadísticas finales:
   📧 Total: 1000
   ✅ Enviados: 1000
   ⏳ En cola: 0
   ❌ Fallidos: 0
   ```

---

## 🛠️ Caso 8: Uso Desde Terminal (Alternativa)

### Escenario
Prefieres procesar la cola automáticamente desde cron jobs o terminal.

### Opción 1: Ejecutar manualmente
```bash
# Desde terminal del servidor
cd /ruta/proyecto/TimeControl
php worker_send.php

# Salida esperada:
# 2024-01-30T10:30:45+00:00 - Procesando lote de 50 correos...
# Enviado a usuario1@ejemplo.com
# Enviado a usuario2@ejemplo.com
# ...
# Pausa de 30 segundos antes del siguiente lote...
```

### Opción 2: Programar con Cron
```bash
# Editar crontab
crontab -e

# Agregar línea para ejecutar cada 5 minutos:
*/5 * * * * cd /ruta/proyecto/TimeControl && php worker_send.php >> /tmp/worker.log 2>&1

# Esto ejecutará worker_send.php automáticamente cada 5 minutos
```

### Opción 3: Usar supervisor (producción)
```ini
# /etc/supervisor/conf.d/mail-worker.conf
[program:mail-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /ruta/proyecto/TimeControl/worker_send.php
autostart=true
autorestart=true
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/mail-worker.log
```

---

## 💡 Tips y Mejores Prácticas

### ✅ DO (Haz esto)
- [ ] Prueba primero con 5-10 correos
- [ ] Procesa lotes en horarios no pico
- [ ] Monitorea los fallos y aprende del error
- [ ] Mantén backup de la cola en caso de problemas
- [ ] Usa HTML válido en el cuerpo
- [ ] Prueba los enlaces en el correo antes de enviar

### ❌ DON'T (Evita esto)
- [ ] No envíes 10,000 correos en un solo lote
- [ ] No cambies la configuración SMTP sin probar
- [ ] No ignores los correos fallidos
- [ ] No uses espacios en blanco en emails
- [ ] No hagas cambios en BD mientras procesa
- [ ] No cierres la pestaña del navegador durante procesamiento

---

## 🎯 Flujo Óptimo Resumido

```
1. Redacta en enviar_correo.php
   ↓
2. Encola presionando "Enviar Correos"
   ↓
3. Va a estado_envios.php
   ↓
4. Procesa con "▶ 5 Lotes" (máximo web)
   ↓
5. Espera completar
   ↓
6. Si hay más cola, repite paso 4
   ↓
7. Verifica estadísticas finales
   ↓
8. ✓ Listo
```

---

## 📞 Preguntas Frecuentes

**P: ¿Cuántos correos puedo enviar a la vez?**
R: Máximo 250 por sesión web (5 lotes × 50 correos). Usa CLI worker para cantidades mayores.

**P: ¿Qué pasa si falla internet durante envío?**
R: Los correos quedan en estado 'sending' o 'queued'. Puedes reintentar cuando se restaure.

**P: ¿Puedo enviar a más de 10,000 correos?**
R: Sí, pero procesa en múltiples sesiones. Usa CLI worker para hacerlo automático.

**P: ¿Cómo sé si un correo fue entregado?**
R: Status 'sent' significa entregado al servidor SMTP. Usa bounce handlers para delivery actual.

**P: ¿Puedo ver quién abrió el correo?**
R: No incluido en este sistema. Requiere píxeles de tracking (avanzado).

---

**¡Listo para enviar! 🚀**
