<?php
// SEO del lado del servidor para las páginas públicas convertidas a .php (index, landing-empresas).
// Necesario porque los rastreadores de redes sociales (Facebook/WhatsApp/Twitter) no ejecutan
// JavaScript: builder-runtime.js resuelve color/secciones/pop-ups desde el navegador, pero el
// <title>/meta descripción/Open Graph tienen que venir ya listos en la respuesta del servidor.
require_once __DIR__ . '/auth.php';

// La URL canónica de cada página NO es editable desde admin — se calcula siempre a partir de
// esta tabla fija, para que nunca quede mal escrita a mano ni arrastre "?org=" u otros parámetros.
const RUTA_CANONICA_POR_PAGINA = [
    'index'            => '/',
    'landing-empresas' => '/landing-empresas',
];

const SEO_POR_DEFECTO = [
    'index' => [
        'titulo' => 'COOLKI — Tu caja de ahorro digital',
        'descripcion' => 'Ahorra desde $5, deja crecer tu dinero a plazo fijo y retira cuando lo necesites. Abre tu cuenta en minutos.',
    ],
    'landing-empresas' => [
        'titulo' => 'COOLKI para organizaciones — Caja de ahorro digital para tus socios',
        'descripcion' => 'Lleva la caja de ahorro de tu organización a una app moderna: aportes, plazo fijo y retiros, todo digital.',
    ],
];

// Resuelve la organización pública a partir de ?org= (o un slug por defecto, para que el
// sitio siga funcionando si alguien entra sin el parámetro). Devuelve null si no existe.
function resolverOrganizacionPublica($slugPorDefecto = 'coolki') {
    $slug = trim($_GET['org'] ?? '') ?: $slugPorDefecto;
    return getOrganizacionPorSlug($slug);
}

// Imprime (echo) las etiquetas de <head> específicas de SEO para una página pública.
// $seoConfig es la fila de paginas_config (o null si el admin no ha guardado nada todavía).
function renderSeoHead($org, $pagina, $seoConfig) {
    $defaults = SEO_POR_DEFECTO[$pagina] ?? ['titulo' => $org['nombre'], 'descripcion' => ''];

    $titulo = trim($seoConfig['seo_titulo'] ?? '') ?: $defaults['titulo'];
    $descripcion = trim($seoConfig['seo_descripcion'] ?? '') ?: $defaults['descripcion'];
    $shareTitulo = trim($seoConfig['seo_share_titulo'] ?? '') ?: $titulo;
    $shareDescripcion = trim($seoConfig['seo_share_descripcion'] ?? '') ?: $descripcion;
    $imagen = trim($seoConfig['seo_imagen_url'] ?? '') ?: (rtrim(SITE_URL, '/') . '/apple-touch-icon.png');
    $ruta = RUTA_CANONICA_POR_PAGINA[$pagina] ?? '/';
    $canonical = rtrim(SITE_URL, '/') . $ruta;

    $t = htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8');
    $d = htmlspecialchars($descripcion, ENT_QUOTES, 'UTF-8');
    $st = htmlspecialchars($shareTitulo, ENT_QUOTES, 'UTF-8');
    $sd = htmlspecialchars($shareDescripcion, ENT_QUOTES, 'UTF-8');
    $img = htmlspecialchars($imagen, ENT_QUOTES, 'UTF-8');
    $url = htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8');

    echo "<title>{$t}</title>\n";
    echo "<meta name=\"description\" content=\"{$d}\">\n";
    echo "<link rel=\"canonical\" href=\"{$url}\">\n";
    echo "<meta property=\"og:type\" content=\"website\">\n";
    echo "<meta property=\"og:title\" content=\"{$st}\">\n";
    echo "<meta property=\"og:description\" content=\"{$sd}\">\n";
    echo "<meta property=\"og:image\" content=\"{$img}\">\n";
    echo "<meta property=\"og:url\" content=\"{$url}\">\n";
    echo "<meta name=\"twitter:card\" content=\"summary_large_image\">\n";
    echo "<meta name=\"twitter:title\" content=\"{$st}\">\n";
    echo "<meta name=\"twitter:description\" content=\"{$sd}\">\n";
    echo "<meta name=\"twitter:image\" content=\"{$img}\">\n";
    echo "<link rel=\"icon\" href=\"/favicon.svg\" type=\"image/svg+xml\">\n";
    echo "<link rel=\"icon\" href=\"/favicon-32.png\" sizes=\"32x32\" type=\"image/png\">\n";
    echo "<link rel=\"icon\" href=\"/favicon-192.png\" sizes=\"192x192\" type=\"image/png\">\n";
    echo "<link rel=\"apple-touch-icon\" href=\"/apple-touch-icon.png\">\n";
    echo "<script type=\"application/ld+json\">" . construirJsonLd($org) . "</script>\n";
}

// Datos estructurados schema.org. Se usa "Organization" (no un tipo específico de institución
// financiera) para no insinuar una regulación que la cooperativa no tiene.
function construirJsonLd($org) {
    $data = [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => $org['nombre'],
        'url' => rtrim(SITE_URL, '/') . '/',
        'logo' => rtrim(SITE_URL, '/') . '/apple-touch-icon.png',
    ];
    if (!empty($org['soporte_email'])) {
        $data['email'] = $org['soporte_email'];
    }
    return json_encode($data, JSON_UNESCAPED_UNICODE);
}
