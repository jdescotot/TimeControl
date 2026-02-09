# Arquitectura del Sistema de Correos Masivos

## Flujo de Datos

```
┌──────────────────────────────────────────────────────────────────┐
│ USUARIO EN EL NAVEGADOR                                          │
└──────────────────────────────────────────────────────────────────┘
                              │
                              ▼
                    ┌─────────────────────┐
                    │ enviar_correo.php   │
                    │  (Formulario Web)   │
                    └─────────────────────┘
                              │
                    Completa el formulario
                              │
                              ▼
                    ┌─────────────────────┐
                    │ process_send.php    │
                    │ (Procesa formulario)│
                    └─────────────────────┘
                              │
              Valida y guarda en cola
                              │
                              ▼
               ┌─────────────────────────────┐
               │    Tabla: email_queue       │
               │  Status: 'queued'           │
               │  (Esperando procesar)       │
               └─────────────────────────────┘
                              │
        El usuario visita estado_envios.php
                              │
                              ▼
                    ┌─────────────────────────┐
                    │ estado_envios.php       │
                    │ (Ver cola de correos)   │
                    └─────────────────────────┘
                              │
           Haz clic en "Procesar Lotes"
                              │
                              ▼
                    ┌─────────────────────────┐
                    │ procesar_cola.php       │
                    │ (Procesa N lotes)       │
                    └─────────────────────────┘
                              │
              Por cada correo en cola:
                              │
                    ┌─────────────────────┐
                    │   PHPMailer (SMTP)  │
                    │ (Envío real del     │
                    │  correo)            │
                    └─────────────────────┘
                              │
                      Intenta enviar
                              │
                ┌─────────────────────────────┐
                │ ¿Éxito?                     │
                │ Sí → status='sent'          │
                │ No → status='failed'        │
                │      attempts++             │
                └─────────────────────────────┘
                              │
                              ▼
               ┌─────────────────────────────┐
               │    Tabla: email_queue       │
               │  Status: 'sent' o 'failed'  │
               │  (Procesado)                │
               └─────────────────────────────┘
                              │
                    Pausa entre lotes
                    (evitar bloqueo SMTP)
                              │
                              ▼
                   Siguiente lote o fin
```

## Arquitectura de Bases de Datos

```
┌─────────────────────────────────────┐
│       email_queue                   │
├─────────────────────────────────────┤
│ id (PK)                             │
│ recipient_email                     │
│ recipient_name                      │
│ subject                             │
│ body (HTML permitido)               │
│ attachments (JSON array)            │
│ status (queued/sending/sent/failed) │
│ attempts                            │
│ last_error                          │
│ created_at                          │
│ sent_at                             │
└─────────────────────────────────────┘
        ▲
        │ Referencia (opcional)
        │
┌─────────────────────────────────────┐
│       tb_empleados                  │
├─────────────────────────────────────┤
│ id (PK)                             │
│ nombre                              │
│ apellidos                           │
│ email                               │
│ ... (otros campos)                  │
└─────────────────────────────────────┘
```

## Componentes del Sistema

### Frontend (Navegador)
- `enviar_correo.php` - Formulario de redacción
- `estado_envios.php` - Panel de monitoreo
- `enviar_correo.css` - Estilos del formulario
- `estado_envios.css` - Estilos del panel

### Backend (Servidor Web)
- `process_send.php` - Validación y encolamiento
- `procesar_cola.php` - Procesamiento en lotes desde web
- `mail_config.php` - Configuración (no versionar)

### Backend (Línea de Comandos - Opcional)
- `worker_send.php` - Procesamiento desde CLI

### Dependencias
- PHPMailer (vía Composer)
- MySQL/MariaDB
- Servidor SMTP (Gmail, SendGrid, etc.)

## Flujo de Estados

```
Estados de un correo en la cola:

queued → sending → sent ✓
  ↓
  └─→ failed → queued (reintentos)
```

## Configuración de Lotes

Cada lote procesa N correos (por defecto 50) con pausa entre lotes:

```
Lote 1: Correos 1-50 → Envío → Pausa 30s
Lote 2: Correos 51-100 → Envío → Pausa 30s
Lote 3: Correos 101-150 → Envío → Pausa 30s
...
```

## Opciones de Destinatarios

### 1. Entrada Manual
```
usuario@ejemplo.com
otro@ejemplo.com
tercero@ejemplo.com
```

### 2. Archivo CSV
```
usuario@ejemplo.com,Nombre Usuario
otro@ejemplo.com,Otro Nombre
```

### 3. Base de Datos
Automáticamente de tabla `tb_empleados` donde email NO esté vacío

## Manejo de Errores y Reintentos

```
Intento de envío:
├─ Éxito → status='sent', sent_at=ahora
└─ Error → status='failed', attempts++, last_error=mensaje
          (Opcional: reintentar si attempts < max_attempts)
```

## Alternativa: Procesamiento CLI

Para entornos con soporte a cron jobs:

```bash
# Agregar a crontab cada 5 minutos:
*/5 * * * * cd /ruta/proyecto && php worker_send.php

# O ejecutar manualmente:
php worker_send.php
```

Worker.php continuará procesando hasta vaciar la cola, con pausas entre lotes configurables.

## Ventajas del Sistema Web vs CLI

| Aspecto | Web | CLI |
|--------|-----|-----|
| Interfaz | Gráfica | Terminal |
| Acceso | Navegador | SSH |
| Procesamiento | Bajo demanda | Automático (cron) |
| Control | Manual por lotes | Automático sin pausa |
| Timeout | Limitado por PHP | Ilimitado |
| Escalabilidad | Lotes pequeños | Lotes grandes |

## Recomendaciones

✅ Usar procesamiento **web** para:
- Envíos bajo demanda
- Control granular de lotes
- Entornos compartidos

✅ Usar procesamiento **CLI** para:
- Sendos automáticos recurrentes
- Procesar toda la cola sin intervención
- Servidores dedicados
