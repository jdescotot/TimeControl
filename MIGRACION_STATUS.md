# Migración de Correos Fallidos

## Cambios realizados:

### En `procesar_cola.php`:
1. ✅ **Errores permanentes** (destinatario no existe, inválido) → Status: `permanent_error`
2. ✅ **Errores transitorios** (timeout, SMTP) → Status: `failed` (SIN incrementar intentos)
3. ✅ **Pausa de 1.5s entre correos** + **Reconexión cada 10 correos** (protección contra bloqueo IONOS)

### En `resetear_fallidos.php`:
- Solo resetea correos con status `failed` (SMTP transitorios)
- Los `permanent_error` quedan como están (NO se reintentarán)

### En `estado_envios.php`:
- Nueva tarjeta: "🚫 Error Permanente" (contador separado)
- Nueva opción de filtro: "Error Permanente"
- Aviso rojo para errores permanentes (no se reintentan)
- Aviso amarillo para errores SMTP (se pueden resetear)

---

## Si necesitas migrar datos en BD:

Si tenías correos con status `failed` que quieres convertir en `permanent_error` (por ejemplo, destinatarios que no existen):

```sql
-- Ver correos con emails sospechosos
SELECT id, recipient_email, last_error FROM email_queue 
WHERE status = 'failed' 
AND last_error LIKE '%does not exist%' 
OR last_error LIKE '%user unknown%'
OR last_error LIKE '%mailbox not found%';

-- Convertirlos a permanent_error
UPDATE email_queue 
SET status = 'permanent_error'
WHERE status = 'failed'
AND (last_error LIKE '%does not exist%'
  OR last_error LIKE '%user unknown%'
  OR last_error LIKE '%mailbox not found%');
```

---

## Estructura de status ahora:

| Status | Significado | ¿Se resetea? | ¿Se reintenta? |
|--------|------------|------------|------------|
| `queued` | En espera de envío | - | ✅ Sí |
| `sending` | Actualmente enviando | - | - |
| `sent` | Enviado exitosamente | - | ❌ No |
| `failed` | Error SMTP transitorio | ✅ Sí | ❌ No (ya esperamos) |
| `permanent_error` | Destinatario inválido/no existe | ❌ No | ❌ No |

