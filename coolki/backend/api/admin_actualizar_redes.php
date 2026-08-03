<?php
// Links de redes sociales (footer) + IDs de analítica. Endpoint dedicado, igual criterio que
// admin_actualizar_soporte.php: son campos de texto opcionales, no encajan en el contrato
// numérico de admin_actualizar_organizacion.php.
require_once __DIR__ . '/../includes/auth.php';
$admin = requireAdminAuth();

$in = jsonInput();
$instagram = trim($in['instagram_url'] ?? '');
$tiktok = trim($in['tiktok_url'] ?? '');
$facebook = trim($in['facebook_url'] ?? '');
$gaId = trim($in['ga_measurement_id'] ?? '');
$pixelId = trim($in['meta_pixel_id'] ?? '');

foreach (['instagram_url' => $instagram, 'tiktok_url' => $tiktok, 'facebook_url' => $facebook] as $campo => $valor) {
    if ($valor !== '' && !preg_match('#^https?://#i', $valor)) {
        jsonResponse(['error' => "El campo $campo debe ser un link válido (empezar con https://)."], 400);
    }
}
if ($gaId !== '' && !preg_match('/^G-[A-Z0-9]{4,20}$/i', $gaId)) {
    jsonResponse(['error' => 'El ID de Google Analytics debe tener el formato G-XXXXXXX.'], 400);
}
if ($pixelId !== '' && !preg_match('/^\d{5,25}$/', $pixelId)) {
    jsonResponse(['error' => 'El ID de Meta Pixel debe ser solo números.'], 400);
}

$pdo = getDB();
$stmt = $pdo->prepare(
    "UPDATE organizaciones
     SET instagram_url = ?, tiktok_url = ?, facebook_url = ?, ga_measurement_id = ?, meta_pixel_id = ?
     WHERE id = ?"
);
$stmt->execute([
    $instagram !== '' ? $instagram : null,
    $tiktok !== '' ? $tiktok : null,
    $facebook !== '' ? $facebook : null,
    $gaId !== '' ? $gaId : null,
    $pixelId !== '' ? $pixelId : null,
    $admin['organizacion_id'],
]);

jsonResponse(['ok' => true]);
