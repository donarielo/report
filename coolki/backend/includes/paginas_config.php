<?php
// Funciones compartidas para el constructor de páginas (secciones + pop-up por página).
// "nav" y "footer" no aparecen aquí: siempre van fijos al inicio/final, nunca se
// reordenan ni se ocultan desde el builder.
const SECCIONES_POR_PAGINA = [
    'index'            => ['hero', 'preview', 'benefits', 'trust', 'faq', 'final-cta'],
    'landing-socios'   => ['hero', 'preview', 'benefits', 'trust', 'faq', 'final-cta'],
    'landing-empresas' => ['hero', 'preview', 'steps', 'orgs', 'final-cta'],
];

function seccionesPorDefecto($pagina) {
    $keys = SECCIONES_POR_PAGINA[$pagina] ?? [];
    return array_map(fn($k) => ['key' => $k, 'visible' => true], $keys);
}

// Reconcilia una lista de secciones (guardada o recién enviada) contra la lista canónica
// de esa página: descarta keys desconocidas, agrega al final cualquier key canónica que
// falte (visible por defecto), y conserva el orden recibido para las keys conocidas. Así
// un cambio futuro al manifiesto de secciones nunca deja una página guardada en un estado
// inconsistente.
function normalizarSecciones($pagina, $entrada) {
    $canonicas = SECCIONES_POR_PAGINA[$pagina] ?? [];
    if (!is_array($entrada)) {
        $entrada = [];
    }

    $resultado = [];
    $vistas = [];
    foreach ($entrada as $item) {
        $key = is_array($item) ? ($item['key'] ?? null) : null;
        if ($key === null || !in_array($key, $canonicas, true) || isset($vistas[$key])) {
            continue;
        }
        $vistas[$key] = true;
        $resultado[] = ['key' => $key, 'visible' => !empty($item['visible'])];
    }
    foreach ($canonicas as $key) {
        if (!isset($vistas[$key])) {
            $resultado[] = ['key' => $key, 'visible' => true];
        }
    }
    return $resultado;
}
