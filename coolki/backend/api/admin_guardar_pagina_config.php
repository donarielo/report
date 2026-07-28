<?php
// Guarda (crea o actualiza) la configuración de secciones + pop-up de una página.
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/paginas_config.php';
$admin = requireAdminAuth();

$in = jsonInput();
$pagina = trim($in['pagina'] ?? '');
if (!array_key_exists($pagina, SECCIONES_POR_PAGINA)) {
    jsonResponse(['error' => 'Página inválida.'], 400);
}

$secciones = normalizarSecciones($pagina, $in['secciones'] ?? []);

$popupActivo = !empty($in['popup_activo']) ? 1 : 0;
$popupTitulo = trim($in['popup_titulo'] ?? '') ?: null;
$popupTexto = trim($in['popup_texto'] ?? '') ?: null;
$popupImagenUrl = trim($in['popup_imagen_url'] ?? '') ?: null;
$popupBotonTexto = trim($in['popup_boton_texto'] ?? '') ?: null;
$popupBotonUrl = trim($in['popup_boton_url'] ?? '') ?: null;
$popupFechaInicio = trim($in['popup_fecha_inicio'] ?? '') ?: null;
$popupFechaFin = trim($in['popup_fecha_fin'] ?? '') ?: null;

foreach (['popup_imagen_url' => $popupImagenUrl, 'popup_boton_url' => $popupBotonUrl] as $campo => $valor) {
    if ($valor !== null && !filter_var($valor, FILTER_VALIDATE_URL)) {
        jsonResponse(['error' => 'El campo ' . $campo . ' no es una URL válida.'], 400);
    }
}
if ($popupFechaInicio && $popupFechaFin && $popupFechaFin < $popupFechaInicio) {
    jsonResponse(['error' => 'La fecha de fin del pop-up no puede ser anterior a la de inicio.'], 400);
}

$pdo = getDB();
$stmt = $pdo->prepare(
    "INSERT INTO paginas_config
        (organizacion_id, pagina, secciones, popup_activo, popup_titulo, popup_texto,
         popup_imagen_url, popup_boton_texto, popup_boton_url, popup_fecha_inicio, popup_fecha_fin)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE
        secciones = VALUES(secciones), popup_activo = VALUES(popup_activo),
        popup_titulo = VALUES(popup_titulo), popup_texto = VALUES(popup_texto),
        popup_imagen_url = VALUES(popup_imagen_url), popup_boton_texto = VALUES(popup_boton_texto),
        popup_boton_url = VALUES(popup_boton_url), popup_fecha_inicio = VALUES(popup_fecha_inicio),
        popup_fecha_fin = VALUES(popup_fecha_fin)"
);
$stmt->execute([
    $admin['organizacion_id'], $pagina, json_encode($secciones), $popupActivo, $popupTitulo, $popupTexto,
    $popupImagenUrl, $popupBotonTexto, $popupBotonUrl, $popupFechaInicio, $popupFechaFin,
]);

jsonResponse(['ok' => true]);
