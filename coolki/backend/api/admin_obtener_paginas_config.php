<?php
// Trae de una sola vez la configuración guardada (o por defecto) de las 3 páginas con
// builder, para que el panel de administrador cargue todas las pestañas de un tirón.
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/paginas_config.php';
$admin = requireAdminAuth();

$pdo = getDB();
$stmt = $pdo->prepare("SELECT * FROM paginas_config WHERE organizacion_id = ?");
$stmt->execute([$admin['organizacion_id']]);
$filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$porPagina = [];
foreach ($filas as $fila) {
    $porPagina[$fila['pagina']] = $fila;
}

$paginas = [];
foreach (array_keys(SECCIONES_POR_PAGINA) as $pagina) {
    $fila = $porPagina[$pagina] ?? null;
    if ($fila) {
        $paginas[$pagina] = [
            'secciones' => normalizarSecciones($pagina, json_decode($fila['secciones'] ?? '[]', true)),
            'popup_activo' => (bool) $fila['popup_activo'],
            'popup_titulo' => $fila['popup_titulo'],
            'popup_texto' => $fila['popup_texto'],
            'popup_imagen_url' => $fila['popup_imagen_url'],
            'popup_boton_texto' => $fila['popup_boton_texto'],
            'popup_boton_url' => $fila['popup_boton_url'],
            'popup_fecha_inicio' => $fila['popup_fecha_inicio'],
            'popup_fecha_fin' => $fila['popup_fecha_fin'],
            'seo_titulo' => $fila['seo_titulo'],
            'seo_descripcion' => $fila['seo_descripcion'],
            'seo_imagen_url' => $fila['seo_imagen_url'],
            'seo_share_titulo' => $fila['seo_share_titulo'],
            'seo_share_descripcion' => $fila['seo_share_descripcion'],
        ];
    } else {
        $paginas[$pagina] = [
            'secciones' => seccionesPorDefecto($pagina),
            'popup_activo' => false,
            'popup_titulo' => null,
            'popup_texto' => null,
            'popup_imagen_url' => null,
            'popup_boton_texto' => null,
            'popup_boton_url' => null,
            'popup_fecha_inicio' => null,
            'popup_fecha_fin' => null,
            'seo_titulo' => null,
            'seo_descripcion' => null,
            'seo_imagen_url' => null,
            'seo_share_titulo' => null,
            'seo_share_descripcion' => null,
        ];
    }
}

jsonResponse(['ok' => true, 'paginas' => $paginas]);
