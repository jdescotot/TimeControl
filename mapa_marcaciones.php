<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'dueño') {
    header('Location: index.php');
    exit;
}

$fecha_hoy    = date('Y-m-d');
$fecha_inicio = date('Y-m-d', strtotime('-90 days')); // límite del date picker

// Leer la fecha solicitada para preseleccionar el input (la API valida por su cuenta)
$fecha_sel_raw = $_GET['fecha'] ?? $fecha_hoy;
$fecha_sel     = preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_sel_raw) ? $fecha_sel_raw : $fecha_hoy;
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
                            <div class="date-badge" id="badge-fecha"><?php echo date('d/m/Y', strtotime($fecha_sel)); ?></div>
                        </div>
                        <div class="card-body">

                            <!-- Controles -->
                            <div class="mapa-controles d-flex flex-wrap align-items-center gap-2 gap-md-3">
                                <label for="input-fecha" class="mb-0">Fecha:</label>
                                <input type="date"
                                       class="form-control form-control-sm mapa-input-fecha"
                                       id="input-fecha"
                                       value="<?php echo htmlspecialchars($fecha_sel); ?>"
                                       min="<?php echo htmlspecialchars($fecha_inicio); ?>"
                                       max="<?php echo $fecha_hoy; ?>">
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<script>
(function () {
    'use strict';

    // ── Inicializar mapa ─────────────────────────────────────────────
    const JAEN_CENTER = [37.7860, -3.7825];
    const JAEN_ZOOM   = 14;

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

    // ── Estado ───────────────────────────────────────────────────────
    const layerGroup    = L.layerGroup().addTo(map);
    const overlayLoad   = document.getElementById('overlay-cargando');
    const overlaySin    = document.getElementById('overlay-sin-datos');
    const msgSin        = document.getElementById('msg-sin-datos');
    const contador      = document.getElementById('contador-resultados');
    const inputFecha    = document.getElementById('input-fecha');
    const btnVer        = document.getElementById('btn-ver');
    const badgeFecha    = document.getElementById('badge-fecha');

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

        let html = '<div class="popup-nombre">' + item.nombre + '</div>';
        html += '<div class="popup-fila"><span>' + horaLabel + '</span><span>' + (horaKey || '—') + '</span></div>';

        if (tipo === 'entrada' && item.hora_salida) {
            html += '<div class="popup-fila"><span>Hora salida</span><span>' + item.hora_salida + '</span></div>';
        } else if (tipo === 'entrada' && !item.hora_salida) {
            html += '<div class="popup-fila"><span>Estado</span><span>En jornada</span></div>';
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

    // ── Cargar marcaciones desde la API ─────────────────────────────
    function cargarMarcaciones(fecha) {
        overlayLoad.style.display = 'flex';
        overlaySin.style.display  = 'none';
        layerGroup.clearLayers();
        contador.textContent = '';

        fetch('api_mapa.php?fecha=' + encodeURIComponent(fecha))
            .then(function (r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(function (json) {
                overlayLoad.style.display = 'none';

                if (!json.ok || !json.data || json.data.length === 0) {
                    overlaySin.style.display  = 'flex';
                    msgSin.textContent = 'Sin marcaciones con ubicación para esta fecha';
                    return;
                }

                const items   = json.data;
                const bounds  = [];

                items.forEach(function (item) {
                    // Marcador de ENTRADA
                    if (item.lat_entrada !== null && item.lng_entrada !== null) {
                        const marker = L.marker([item.lat_entrada, item.lng_entrada], {
                            icon: crearIcono('entrada'),
                            title: item.nombre + ' — Entrada',
                        });
                        marker.bindPopup(popupHtml(item, 'entrada'));
                        layerGroup.addLayer(marker);
                        bounds.push([item.lat_entrada, item.lng_entrada]);
                    }

                    // Marcador de SALIDA (solo si coords son distintas de entrada)
                    if (item.lat_salida !== null && item.lng_salida !== null && !coordsIguales(item)) {
                        const marker = L.marker([item.lat_salida, item.lng_salida], {
                            icon: crearIcono('salida'),
                            title: item.nombre + ' — Salida',
                        });
                        marker.bindPopup(popupHtml(item, 'salida'));
                        layerGroup.addLayer(marker);
                        bounds.push([item.lat_salida, item.lng_salida]);
                    }
                });

                if (bounds.length > 0) {
                    map.fitBounds(bounds, { padding: [40, 40], maxZoom: 17 });
                }

                const totalMarcadores = layerGroup.getLayers().length;
                contador.textContent =
                    items.length + ' empleado' + (items.length !== 1 ? 's' : '') +
                    ' con ubicación · ' +
                    totalMarcadores + ' marcador' + (totalMarcadores !== 1 ? 'es' : '');
            })
            .catch(function () {
                overlayLoad.style.display = 'none';
                overlaySin.style.display  = 'flex';
                msgSin.textContent = 'Error al cargar los datos. Inténtalo de nuevo.';
            });
    }

    // ── Función de actualización del badge de fecha ──────────────────
    function formatearFecha(iso) {
        const p = iso.split('-');
        return p[2] + '/' + p[1] + '/' + p[0];
    }

    // ── Eventos ──────────────────────────────────────────────────────
    btnVer.addEventListener('click', function () {
        const f = inputFecha.value;
        if (!f) return;
        badgeFecha.textContent = formatearFecha(f);
        cargarMarcaciones(f);
    });

    inputFecha.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') btnVer.click();
    });

    // ── Carga inicial ────────────────────────────────────────────────
    cargarMarcaciones(inputFecha.value || '<?php echo $fecha_sel; ?>');

}());
</script>
</body>
</html>
