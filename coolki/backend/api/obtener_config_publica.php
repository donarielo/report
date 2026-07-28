<?php
// Endpoint público (sin sesión) que leen las páginas públicas para pintarse: color de
// marca y, si se pide una página concreta, el orden/visibilidad de sus secciones y su
// pop-up. Mismo nivel de confianza que getOrganizacionPorSlug(), que ya usan login.php
// y registro.php sin autenticación.
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/color.php';
require_once __DIR__ . '/../includes/paginas_config.php';

$slug = trim($_GET['org'] ?? '');
$pagina = trim($_GET['pagina'] ?? '');

$org = getOrganizacionPorSlug($slug);
if (!$org) {
    jsonResponse(['error' => 'Organización no encontrada.'], 404);
}

$respuesta = [
    'ok' => true,
    'organizacion' => [
        'nombre' => $org['nombre'],
        'color_marca' => $org['color_marca'],
        'brand_soft' => mezclarConBlanco($org['color_marca']),
    ],
];

if ($pagina !== '') {
    if (!array_key_exists($pagina, SECCIONES_POR_PAGINA)) {
        jsonResponse(['error' => 'Página inválida.'], 400);
    }

    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM paginas_config WHERE organizacion_id = ? AND pagina = ?");
    $stmt->execute([$org['id'], $pagina]);
    $config = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($config) {
        $secciones = normalizarSecciones($pagina, json_decode($config['secciones'] ?? '[]', true));
        $hoy = date('Y-m-d');
        $activo = !empty($config['popup_activo'])
            && (!$config['popup_fecha_inicio'] || $config['popup_fecha_inicio'] <= $hoy)
            && (!$config['popup_fecha_fin'] || $config['popup_fecha_fin'] >= $hoy);
        $popup = [
            'activo' => $activo,
            'titulo' => $config['popup_titulo'],
            'texto' => $config['popup_texto'],
            'imagen_url' => $config['popup_imagen_url'],
            'boton_texto' => $config['popup_boton_texto'],
            'boton_url' => $config['popup_boton_url'],
        ];
    } else {
        $secciones = seccionesPorDefecto($pagina);
        $popup = ['activo' => false];
    }

    $respuesta['secciones'] = $secciones;
    $respuesta['popup'] = $popup;
}

jsonResponse($respuesta);
