<?php
// Datos de contacto de soporte, mostrados en la pantalla de inicio de sesión del socio.
// Endpoint dedicado (no se mete en admin_actualizar_organizacion.php, que exige que sus
// doce campos sean numéricos >= 0 — estos dos son texto y opcionales).
require_once __DIR__ . '/../includes/auth.php';
$admin = requireAdminAuth();

$in = jsonInput();
$soporteEmail = trim($in['soporte_email'] ?? '');
$soporteWhatsapp = trim($in['soporte_whatsapp'] ?? '');

if ($soporteEmail !== '' && !filter_var($soporteEmail, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['error' => 'El correo de soporte no es válido.'], 400);
}
if ($soporteWhatsapp !== '' && !preg_match('#^https?://#i', $soporteWhatsapp)) {
    jsonResponse(['error' => 'El WhatsApp debe ser un link válido (ej. https://wa.me/593999999999).'], 400);
}

$pdo = getDB();
$stmt = $pdo->prepare("UPDATE organizaciones SET soporte_email = ?, soporte_whatsapp = ? WHERE id = ?");
$stmt->execute([
    $soporteEmail !== '' ? $soporteEmail : null,
    $soporteWhatsapp !== '' ? $soporteWhatsapp : null,
    $admin['organizacion_id'],
]);

jsonResponse(['ok' => true]);
