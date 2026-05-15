<?php
session_start();
require_once 'config.php';
require_once 'jaen_geocoder.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'dueño') {
    header('Location: index.php');
    exit;
}

$fecha_hoy    = date('Y-m-d');
$fecha_inicio = date('Y-m-d', strtotime('-90 days')); // límite del date picker

// Leer el tipo de rango solicitado (hoy, 3dias, 7dias, personalizado)
$rango_tipo = $_GET['rango'] ?? 'hoy';
$fecha_desde_param = $_GET['fecha_desde'] ?? '';
$fecha_hasta_param = $_GET['fecha_hasta'] ?? '';

// Determinar fechas según el tipo de rango
$fecha_desde = $fecha_hoy;
$fecha_hasta = $fecha_hoy;

if ($rango_tipo === '3dias') {
    $fecha_desde = date('Y-m-d', strtotime('-2 days'));
    $fecha_hasta = $fecha_hoy;
} elseif ($rango_tipo === '7dias') {
    $fecha_desde = date('Y-m-d', strtotime('-6 days'));
    $fecha_hasta = $fecha_hoy;
} elseif ($rango_tipo === 'personalizado' && $fecha_desde_param && $fecha_hasta_param) {
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_desde_param) && 
        preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_hasta_param)) {
        $fecha_desde = $fecha_desde_param;
        $fecha_hasta = $fecha_hasta_param;
        if ($fecha_desde > $fecha_hasta) {
            $temp = $fecha_desde;
            $fecha_desde = $fecha_hasta;
            $fecha_hasta = $temp;
        }
    }
}

// Preparar límites y centro de Jaén para pasar a JavaScript
$jaen_bounds = getJaenBoundsArray();
$jaen_center = getJaenCenter();
$allowed_zones = getAllowedMarkingLocations();
$jaen_bounds_json = json_encode($jaen_bounds);
$jaen_center_json = json_encode($jaen_center);
$allowed_zones_json = json_encode($allowed_zones);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapa de Marcaciones — Control Horario</title>

    <!-- Bootstrap 5 (solo para esta pantalla) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <!-- Leaflet 1.9.4 -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">

    <!-- Pasar límites de Jaén a JavaScript -->
    <script>
        window.JAEN_BOUNDS = <?php echo $jaen_bounds_json; ?>;
        window.JAEN_CENTER = <?php echo $jaen_center_json; ?>;
        window.ALLOWED_ZONES = <?php echo $allowed_zones_json; ?>;
    </script>

    <!-- Fallback CSS de Leaflet por si falla unpkg -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css">

    <!-- Estilos propios -->
    <link rel="stylesheet" href="empleado.css">
    <link rel="stylesheet" href="dueño.css">
    <link rel="stylesheet" href="mapa_marcaciones.css">
</head>
<body class="owner-dashboard mapa-page">
<div class="container">

    <!-- ── Header (igual que dueño.php) ─────────────────────────────── -->
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                <span>Control Horario</span>
            </div>
            <div class="user-info">
                <div class="header-actions">
                    <a href="dueño.php" class="btn top-nav-btn top-nav-btn--schedule">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                        Panel Principal
                    </a>
                    <a href="reporte_mensual.php" class="btn top-nav-btn top-nav-btn--report">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                        Reporte Mensual
                    </a>
                    <div class="welcome-block">
                        <span class="welcome-text">Bienvenido,</span>
                        <span class="username"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- ── Contenido principal ───────────────────────────────────────── -->
    <main class="main-content">
        <div class="container-fluid px-0">
            <div class="row g-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <h2 class="mb-0">Mapa de Marcaciones</h2>
                            <div class="date-badge" id="badge-fecha"><?php 
                                if ($fecha_desde === $fecha_hasta) {
                                    echo date('d/m/Y', strtotime($fecha_desde));
                                } else {
                                    echo date('d/m/Y', strtotime($fecha_desde)) . ' — ' . date('d/m/Y', strtotime($fecha_hasta));
                                }
                            ?></div>
                        </div>
                        <div class="card-body">

                            <!-- Controles -->
                            <div class="mapa-controles d-flex flex-wrap align-items-center gap-2 gap-md-3">
                                <label for="rango-tipo" class="mb-0">Rango:</label>
                                <select class="form-control form-control-sm" id="rango-tipo" style="max-width: 150px;">
                                    <option value="hoy" <?php echo $rango_tipo === 'hoy' ? 'selected' : ''; ?>>Hoy</option>
                                    <option value="3dias" <?php echo $rango_tipo === '3dias' ? 'selected' : ''; ?>>Últimos 3 días</option>
                                    <option value="7dias" <?php echo $rango_tipo === '7dias' ? 'selected' : ''; ?>>Últimos 7 días</option>
                                    <option value="personalizado" <?php echo $rango_tipo === 'personalizado' ? 'selected' : ''; ?>>Personalizado</option>
                                </select>

                                <div id="controles-personalizado" style="display: <?php echo $rango_tipo === 'personalizado' ? 'flex' : 'none'; ?>; gap: 0.5rem; align-items: center;">
                                    <label for="fecha-desde" class="mb-0">Desde:</label>
                                    <input type="date"
                                           class="form-control form-control-sm"
                                           id="fecha-desde"
                                           value="<?php echo htmlspecialchars($fecha_desde); ?>"
                                           min="<?php echo htmlspecialchars($fecha_inicio); ?>"
                                           max="<?php echo $fecha_hoy; ?>">
                                    <label for="fecha-hasta" class="mb-0">Hasta:</label>
                                    <input type="date"
                                           class="form-control form-control-sm"
                                           id="fecha-hasta"
                                           value="<?php echo htmlspecialchars($fecha_hasta); ?>"
                                           min="<?php echo htmlspecialchars($fecha_inicio); ?>"
                                           max="<?php echo $fecha_hoy; ?>">
                                </div>

                                <button class="btn btn-primary btn-sm btn-ver-mapa" id="btn-ver" type="button">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="11" cy="11" r="8"></circle>
                                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                    </svg>
                                    Ver
                                </button>

                                <div class="mapa-leyenda d-flex align-items-center flex-wrap gap-3 ms-md-auto">
                                    <div class="leyenda-item">
                                        <div class="leyenda-dot leyenda-dot--entrada"></div>
                                        Entrada
                                    </div>
                                    <div class="leyenda-item">
                                        <div class="leyenda-dot leyenda-dot--salida"></div>
                                        Salida
                                    </div>
                                </div>
                            </div>

                            <!-- Mapa -->
                            <div class="mapa-wrapper">
                                <div id="map"></div>

                                <!-- Overlay: cargando -->
                                <div class="mapa-cargando" id="overlay-cargando" style="display:none;">
                                    <div class="spinner"></div>
                                </div>

                                <!-- Overlay: sin datos -->
                                <div class="mapa-sin-datos" id="overlay-sin-datos" style="display:none;">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="12" y1="8" x2="12" y2="12"></line>
                                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                    </svg>
                                    <p id="msg-sin-datos">Sin marcaciones con ubicación para esta fecha</p>
                                </div>
                            </div>

                            <p class="mapa-contador" id="contador-resultados"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <!-- Footer -->
    <footer class="footer">
        <a href="logout.php" class="logout-link">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            Cerrar Sesión
        </a>
    </footer>
</div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV/XN/TO=" crossorigin=""></script>
<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<script>
(function () {
    'use strict';

    if (typeof L === 'undefined') {
        const msgSin = document.getElementById('msg-sin-datos');
        const overlaySin = document.getElementById('overlay-sin-datos');
        if (msgSin && overlaySin) {
            msgSin.textContent = 'No se pudo cargar Leaflet (mapa). Revisa conexión/CDN.';
            overlaySin.style.display = 'flex';
        }
        return;
    }

    // ── Inicializar mapa ─────────────────────────────────────────────
    const JAEN_CENTER = window.JAEN_CENTER || [37.7860, -3.7825];
    const JAEN_BOUNDS = window.JAEN_BOUNDS || [[37.0, -4.5], [38.8, -3.5]];
    const ALLOWED_ZONES = Array.isArray(window.ALLOWED_ZONES) ? window.ALLOWED_ZONES : [];
    const JAEN_ZOOM   = 10;

    const map = L.map('map', {
        center: JAEN_CENTER,
        zoom:   JAEN_ZOOM,
        zoomControl: true,
    });

    L.tileLayer(
        'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png',
        {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/attributions">CARTO</a>',
            subdomains: 'abcd',
            maxZoom: 19,
        }
    ).addTo(map);

    // ── Dibujar límites de Jaén ─────────────────────────────────────
    const rectangle = L.rectangle(JAEN_BOUNDS, {
        color: '#0284c7',
        weight: 2,
        opacity: 0.6,
        fill: true,
        fillColor: '#0ea5e9',
        fillOpacity: 0.05,
        dashArray: '5, 5',
    }).addTo(map);

    // Tooltip para mostrar qué es el rectángulo
    rectangle.bindTooltip('Límite aproximado de la provincia de Jaén', {
        permanent: false,
        direction: 'center',
        offset: [0, 0]
    });

    // Dibujar 1-3 zonas permitidas de marcación
    const zonesLayer = L.layerGroup().addTo(map);
    ALLOWED_ZONES.forEach(function (zone) {
        if (typeof zone.lat !== 'number' || typeof zone.lng !== 'number') {
            return;
        }

        const radius = typeof zone.radius_m === 'number' ? zone.radius_m : 200;
        L.circle([zone.lat, zone.lng], {
            radius: radius,
            color: '#0f766e',
            weight: 2,
            fillColor: '#14b8a6',
            fillOpacity: 0.12,
        }).addTo(zonesLayer).bindPopup(
            '<strong>' + (zone.name || 'Zona permitida') + '</strong><br>Radio: ' + Math.round(radius) + ' m'
        );
    });

    // ── Estado ───────────────────────────────────────────────────────
    const layerGroup    = L.layerGroup().addTo(map);
    const overlayLoad   = document.getElementById('overlay-cargando');
    const overlaySin    = document.getElementById('overlay-sin-datos');
    const msgSin        = document.getElementById('msg-sin-datos');
    const contador      = document.getElementById('contador-resultados');
    const rangoTipo     = document.getElementById('rango-tipo');
    const fechaDesde    = document.getElementById('fecha-desde');
    const fechaHasta    = document.getElementById('fecha-hasta');
    const btnVer        = document.getElementById('btn-ver');
    const badgeFecha    = document.getElementById('badge-fecha');
    const controlesPerso = document.getElementById('controles-personalizado');

    // ── Crear icono de marcador ──────────────────────────────────────
    function crearIcono(tipo) {
        return L.divIcon({
            className: '',
            html: '<div class="marker-pin marker-pin--' + tipo + '"></div>',
            iconSize:   [22, 22],
            iconAnchor: [11, 22],
            popupAnchor:[0, -24],
        });
    }

    // ── Construir HTML del popup ─────────────────────────────────────
    function popupHtml(item, tipo) {
        const horaKey  = tipo === 'entrada' ? item.hora_entrada : item.hora_salida;
        const badgeClass = 'popup-badge--' + tipo;
        const badgeText  = tipo === 'entrada' ? 'Entrada' : 'Salida';
        const horaLabel  = tipo === 'entrada' ? 'Hora entrada' : 'Hora salida';
        const zonaLabel = tipo === 'entrada' ? item.zona_entrada : item.zona_salida;
        const distLabel = tipo === 'entrada' ? item.dist_entrada_m : item.dist_salida_m;
        const fechaLabel = tipo === 'entrada' ? item.fecha_entrada : item.fecha_salida;

        let html = '<div class="popup-nombre">' + item.nombre + '</div>';
        if (fechaLabel) {
            html += '<div class="popup-fila"><span>Fecha</span><span>' + fechaLabel + '</span></div>';
        }
        html += '<div class="popup-fila"><span>' + horaLabel + '</span><span>' + (horaKey || '—') + '</span></div>';

        if (tipo === 'entrada' && item.hora_salida) {
            html += '<div class="popup-fila"><span>Hora salida</span><span>' + item.hora_salida + '</span></div>';
        } else if (tipo === 'entrada' && !item.hora_salida) {
            html += '<div class="popup-fila"><span>Estado</span><span>En jornada</span></div>';
        }

        if (zonaLabel) {
            html += '<div class="popup-fila"><span>Zona</span><span>' + zonaLabel + '</span></div>';
        }

        if (distLabel !== null && distLabel !== undefined) {
            html += '<div class="popup-fila"><span>Distancia</span><span>' + distLabel + ' m</span></div>';
        }

        html += '<span class="popup-badge ' + badgeClass + '">' + badgeText + '</span>';
        return html;
    }

    // ── Comparar coords para evitar marcador duplicado ───────────────
    function coordsIguales(item) {
        if (item.lat_salida === null) return false;
        return (
            Math.abs(item.lat_entrada - item.lat_salida) < 0.000001 &&
            Math.abs(item.lng_entrada - item.lng_salida) < 0.000001
        );
    }

    // ── Actualizar badge con rango de fechas ─────────────────────────
    function actualizarBadgeFecha(desde, hasta) {
        const fromDate = new Date(desde + 'T00:00:00Z');
        const toDate = new Date(hasta + 'T00:00:00Z');
        const formatter = new Intl.DateTimeFormat('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
        
        if (desde === hasta) {
            badgeFecha.textContent = formatter.format(fromDate);
        } else {
            badgeFecha.textContent = formatter.format(fromDate) + ' — ' + formatter.format(toDate);
        }
    }

    // ── Cargar marcaciones desde la API ─────────────────────────────
    function cargarMarcaciones(desde, hasta) {
        overlayLoad.style.display = 'flex';
        overlaySin.style.display  = 'none';
        layerGroup.clearLayers();
        contador.textContent = '';

        const url = 'api_mapa.php?fecha_desde=' + encodeURIComponent(desde) + '&fecha_hasta=' + encodeURIComponent(hasta);
        
        fetch(url)
            .then(function (r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(function (json) {
                overlayLoad.style.display = 'none';

                if (!json.ok || !json.data || json.data.length === 0) {
                    overlaySin.style.display  = 'flex';
                    msgSin.textContent = 'Sin marcaciones con ubicación en este rango';
                    return;
                }

                const items   = json.data;
                const boundsEntrada = [];
                const boundsTodos   = [];

                items.forEach(function (item) {
                    // Marcador de ENTRADA
                    if (item.lat_entrada !== null && item.lng_entrada !== null) {
                        const marker = L.marker([item.lat_entrada, item.lng_entrada], {
                            icon: crearIcono('entrada'),
                            title: item.nombre + ' — Entrada',
                        });
                        marker.bindPopup(popupHtml(item, 'entrada'));
                        layerGroup.addLayer(marker);
                        boundsEntrada.push([item.lat_entrada, item.lng_entrada]);
                        boundsTodos.push([item.lat_entrada, item.lng_entrada]);
                    }

                    // Marcador de SALIDA (solo si coords son distintas de entrada)
                    if (item.lat_salida !== null && item.lng_salida !== null && !coordsIguales(item)) {
                        const marker = L.marker([item.lat_salida, item.lng_salida], {
                            icon: crearIcono('salida'),
                            title: item.nombre + ' — Salida',
                        });
                        marker.bindPopup(popupHtml(item, 'salida'));
                        layerGroup.addLayer(marker);
                        boundsTodos.push([item.lat_salida, item.lng_salida]);
                    }
                });

                // Enfocar prioritariamente donde se marcan entradas válidas.
                if (boundsEntrada.length > 0) {
                    map.fitBounds(boundsEntrada, { padding: [40, 40], maxZoom: 17 });
                } else if (boundsTodos.length > 0) {
                    map.fitBounds(boundsTodos, { padding: [40, 40], maxZoom: 17 });
                } else {
                    map.fitBounds(JAEN_BOUNDS, { padding: [20, 20], maxZoom: 11 });
                }

                const totalMarcadores = layerGroup.getLayers().length;
                contador.textContent =
                    items.length + ' empleado' + (items.length !== 1 ? 's' : '') +
                    ' con ubicación · ' +
                    totalMarcadores + ' marcador' + (totalMarcadores !== 1 ? 'es' : '');
            })
            .catch(function (err) {
                overlayLoad.style.display = 'none';
                overlaySin.style.display  = 'flex';
                msgSin.textContent = 'Error al cargar los datos: ' + (err.message || 'Intenta nuevamente');
                console.error('Error cargando mapa:', err);
            });
    }

    // ── Mostrar/ocultar controles personalizados ─────────────────────
    rangoTipo.addEventListener('change', function () {
        if (this.value === 'personalizado') {
            controlesPerso.style.display = 'flex';
        } else {
            controlesPerso.style.display = 'none';
        }
    });

    // ── Botón ver ────────────────────────────────────────────────────
    btnVer.addEventListener('click', function () {
        let desde = '<?php echo $fecha_hoy; ?>';
        let hasta = '<?php echo $fecha_hoy; ?>';

        const tipo = rangoTipo.value;
        if (tipo === 'hoy') {
            desde = hasta = '<?php echo $fecha_hoy; ?>';
        } else if (tipo === '3dias') {
            // El servidor calcula esto, pero enviamos el mismo parámetro
            desde = new Date();
            desde.setDate(desde.getDate() - 2);
            desde = desde.toISOString().split('T')[0];
            hasta = '<?php echo $fecha_hoy; ?>';
        } else if (tipo === '7dias') {
            desde = new Date();
            desde.setDate(desde.getDate() - 6);
            desde = desde.toISOString().split('T')[0];
            hasta = '<?php echo $fecha_hoy; ?>';
        } else if (tipo === 'personalizado') {
            desde = fechaDesde.value;
            hasta = fechaHasta.value;
            if (!desde || !hasta) {
                alert('Por favor completa ambas fechas');
                return;
            }
            if (desde > hasta) {
                const temp = desde;
                desde = hasta;
                hasta = temp;
            }
        }

        actualizarBadgeFecha(desde, hasta);
        cargarMarcaciones(desde, hasta);
    });

    // Enter en inputs personalizado
    fechaDesde.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') btnVer.click();
    });
    fechaHasta.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') btnVer.click();
    });

    // ── Carga inicial ────────────────────────────────────────────────
    const fechaInicial_desde = '<?php echo $fecha_desde; ?>';
    const fechaInicial_hasta = '<?php echo $fecha_hasta; ?>';
    actualizarBadgeFecha(fechaInicial_desde, fechaInicial_hasta);
    cargarMarcaciones(fechaInicial_desde, fechaInicial_hasta);
    setTimeout(function () {
        map.invalidateSize();
    }, 200);

}());
</script>
</body>
</html>
