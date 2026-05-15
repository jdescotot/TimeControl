<?php
/**
 * Utilidades de geolocalización para filtrar marcaciones por provincia de Jaén
 * Se usa en api_mapa.php y mapa_marcaciones.php
 */

// Demarcación geográfica de provincia de Jaén (España)
// Bounding box aproximado basado en coordenadas reales
const JAEN_BOUNDS = [
    'lat_min' => 37.0,    // límite sur (cerca de Andújar)
    'lat_max' => 38.8,    // límite norte (sierra de Cazorla)
    'lng_min' => -4.5,    // límite oeste (cerca de Córdoba)
    'lng_max' => -3.5,    // límite este (cerca de Granada)
];

// 1 a 3 ubicaciones permitidas para marcación.
// Ajusta estos puntos según tus sucursales reales.
const ALLOWED_MARKING_LOCATIONS = [
    [
        'name' => 'Oficina Central Jaen',
        'lat' => 37.7796,
        'lng' => -3.7849,
        'radius_m' => 220,
    ],
    [
        'name' => 'Sucursal Bulevar',
        'lat' => 37.7860,
        'lng' => -3.7825,
        'radius_m' => 220,
    ],
    [
        'name' => 'Delegacion Norte',
        'lat' => 37.8084,
        'lng' => -3.7751,
        'radius_m' => 220,
    ],
];

/**
 * Verifica si una coordenada (lat, lng) está dentro de la provincia de Jaén
 * 
 * @param float|null $lat Latitud
 * @param float|null $lng Longitud
 * @return bool true si está dentro de Jaén, false si está fuera o es nula
 */
function isInJaen(?float $lat, ?float $lng): bool {
    if ($lat === null || $lng === null) {
        return false;
    }
    
    return $lat >= JAEN_BOUNDS['lat_min'] &&
           $lat <= JAEN_BOUNDS['lat_max'] &&
           $lng >= JAEN_BOUNDS['lng_min'] &&
           $lng <= JAEN_BOUNDS['lng_max'];
}

/**
 * Obtiene los límites de Jaén como array para usar en Leaflet LatLngBounds
 * 
 * @return array [[lat_min, lng_min], [lat_max, lng_max]] - SW y NE corners
 */
function getJaenBoundsArray(): array {
    return [
        [JAEN_BOUNDS['lat_min'], JAEN_BOUNDS['lng_min']], // SW corner
        [JAEN_BOUNDS['lat_max'], JAEN_BOUNDS['lng_max']]  // NE corner
    ];
}

/**
 * Obtiene el center point de Jaén para inicializar el mapa
 * 
 * @return array [lat, lng]
 */
function getJaenCenter(): array {
    return [
        (JAEN_BOUNDS['lat_min'] + JAEN_BOUNDS['lat_max']) / 2,
        (JAEN_BOUNDS['lng_min'] + JAEN_BOUNDS['lng_max']) / 2,
    ];
}

/**
 * Distancia aproximada entre 2 puntos (metros) con Haversine.
 */
function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float {
    $earthRadius = 6371000.0;

    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);

    $a = sin($dLat / 2) * sin($dLat / 2) +
        cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
        sin($dLng / 2) * sin($dLng / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $earthRadius * $c;
}

/**
 * Retorna true si el punto está cerca de una ubicación permitida.
 * También retorna por referencia el nombre de la zona y la distancia.
 */
function isNearAllowedLocation(?float $lat, ?float $lng, ?string &$zoneName = null, ?float &$distance = null): bool {
    if ($lat === null || $lng === null) {
        return false;
    }

    foreach (ALLOWED_MARKING_LOCATIONS as $zone) {
        $meters = distanceMeters($lat, $lng, $zone['lat'], $zone['lng']);
        if ($meters <= $zone['radius_m']) {
            $zoneName = $zone['name'];
            $distance = $meters;
            return true;
        }
    }

    return false;
}

/**
 * Lista de zonas permitidas para dibujar en mapa.
 */
function getAllowedMarkingLocations(): array {
    return ALLOWED_MARKING_LOCATIONS;
}
