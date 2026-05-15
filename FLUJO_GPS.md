# 📍 Flujo Completo de Geolocalización GPS

## 🔄 Cómo Funciona el Sistema GPS

### 1️⃣ **EMPLEADO MARCA ENTRADA/SALIDA**
- Archivo: `empleado.php`
- El empleado hace click en "Marcar Entrada" o "Marcar Salida"

### 2️⃣ **OBTENER UBICACIÓN (JavaScript)**
```javascript
navigator.geolocation.getCurrentPosition(
    {
        timeout: 8000,           // Espera máximo 8 segundos
        maximumAge: 30000,       // Reutiliza posición si es reciente
        enableHighAccuracy: true // Mayor precisión (usa GPS del dispositivo)
    }
)
```

**Lo que pasa:**
- ✅ Si consigue GPS → guarda `lat` y `lng` en campos ocultos
- ⏱️ Si tarda más de 8 segundos → envía de todas formas (sin coords)
- ❌ Si el usuario deniega GPS → envía de todas formas (sin coords)

### 3️⃣ **ENVIAR AL SERVIDOR** 
- Archivo: `marcar.php`
- POST con:
  - `accion` = entrada | salida | cerrar_y_entrar
  - `lat` = latitud (8 decimales) o vacío
  - `lng` = longitud (8 decimales) o vacío

### 4️⃣ **PROCESAR EN SERVIDOR**
```php
// marcar.php:68-79
$stmt = $pdo->prepare("INSERT INTO marcaciones 
    (empleado_id, entrada, lat_entrada, lng_entrada) 
    VALUES (?, ?, ?, ?)");
$stmt->execute([$empleado_id, $ahora, $lat, $lng]);
```

### 5️⃣ **GUARDAR EN BD**
Tabla: `marcaciones`
```
Campos:
- lat_entrada  (DECIMAL 10,8) NULL
- lng_entrada  (DECIMAL 11,8) NULL
- lat_salida   (DECIMAL 10,8) NULL
- lng_salida   (DECIMAL 11,8) NULL
```

### 6️⃣ **MOSTRAR EN MAPA**
- Archivo: `mapa_marcaciones.php`
- API: `api_mapa.php`
- Leaflet muestra los puntos en el mapa

---

## 📋 Checklist de Verificación

Antes de que el mapa funcione:

```
☐ 1. COLUMNAS DE GPS EN BD
   → Acceder a: verificar_db_gps.php
   → Si faltan columnas → ejecutar migrar_gps.php

☐ 2. DATOS GPS EN BD
   → Acceder a: diagnostico_mapa.php
   → Ver si hay marcaciones con lat/lng guardadas

☐ 3. ZONAS PERMITIDAS CONFIGURADAS
   → diagnostico_mapa.php muestra las zonas
   → Si hay 0 zonas → revisar jaen_geocoder.php

☐ 4. MAPA FUNCIONA
   → Ir a: mapa_marcaciones.php
   → Seleccionar rango (últimos 3 días)
   → Ver si cargan los marcadores
```

---

## 🛠️ Mejoras Realizadas

### En `empleado.php`:
- ✅ Mensaje más claro: "Obteniendo ubicación..."
- ✅ Animación spinner mientras obtiene GPS
- ✅ Confirmación visual: "Ubicación confirmada" (con checkmark)
- ✅ Timeout claro: 8 segundos
- ✅ Mejor logs en consola para debugging
- ✅ Manejo de errores con mensajes específicos

### En `marcar.php`:
- ✅ Validación de coordenadas (FILTER_VALIDATE_FLOAT)
- ✅ Ambas coords deben ser válidas o ambas NULL
- ✅ Guarda en tabla marcaciones correctamente

### En `api_mapa.php`:
- ✅ Acepta rango de fechas
- ✅ Máximo 200 marcadores por consulta
- ✅ Filtra solo ubicaciones en Jaén + zonas permitidas

### En `mapa_marcaciones.php`:
- ✅ Selector de rango: Hoy / 3 días / 7 días / Personalizado
- ✅ Mejor feedback de carga
- ✅ Muestra fecha + hora en popup

---

## 📍 Archivos Clave

| Archivo | Función |
|---------|---------|
| `empleado.php` | 🎯 Solicita GPS al empleado |
| `marcar.php` | 📤 Recibe y guarda lat/lng en BD |
| `verificar_db_gps.php` | 🔍 Verifica si tablas están creadas |
| `migrar_gps.php` | 🔧 Crea columnas de GPS en BD |
| `diagnostico_mapa.php` | 📊 Muestra estadísticas de GPS |
| `api_mapa.php` | 🌐 API JSON para obtener marcaciones |
| `mapa_marcaciones.php` | 🗺️ Interfaz del mapa |

---

## 🚀 Proceso de Prueba

1. **Abrír en navegador (como dueño):**
   ```
   verificar_db_gps.php
   ```
   → Verifica que las columnas GPS existan

2. **Si faltan columnas:**
   ```
   migrar_gps.php
   ```
   → Crea las columnas necesarias

3. **Ir a diagnostico:**
   ```
   diagnostico_mapa.php
   ```
   → Verifica datos guardados

4. **Ver mapa:**
   ```
   mapa_marcaciones.php?rango=3dias
   ```
   → Debería mostrar los marcadores

---

## 💡 Qué Pasa Si No Aparecen Datos en el Mapa

### ❌ Problema 1: Mapa en blanco, sin errores
- **Causa más común:** No hay datos GPS en BD
- **Solución:** 
  1. Accede a `diagnostico_mapa.php`
  2. Revisa columna "Con Alguna Ubicación"
  3. Si es 0 → los empleados no están enviando GPS

### ❌ Problema 2: Los empleados marcan pero sin GPS
- **Causa:** El navegador deniega GPS o no está habilitado
- **Solución:**
  1. Abre DevTools (F12) → Console
  2. Busca mensajes de "GPS error"
  3. Avisa al empleado que active GPS

### ❌ Problema 3: Las columnas no existen
- **Causa:** Migración no ejecutada
- **Solución:**
  1. Accede a `migrar_gps.php`
  2. Ejecuta la migración
  3. Espera confirmación

---

Última actualización: 2025-05-12 ✅
