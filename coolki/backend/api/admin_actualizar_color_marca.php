<?php
// Guarda el color de marca de la organización (aplicado por builder-runtime.js a las
// páginas públicas y a sistema.html/admin.html). Endpoint dedicado en vez de sumarlo a
// admin_actualizar_organizacion.php: ese archivo exige los doce campos numéricos de sus
// reglas de negocio, y mezclar ahí un guardado de un solo color rompería ese contrato.
require_once __DIR__ . '/../includes/auth.php';
$admin = requireAdminAuth();

$in = jsonInput();
$colorMarca = trim($in['color_marca'] ?? '');
if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $colorMarca)) {
    jsonResponse(['error' => 'El color debe ser un hex válido, ej. #2454FF.'], 400);
}

$pdo = getDB();
$stmt = $pdo->prepare("UPDATE organizaciones SET color_marca = ? WHERE id = ?");
$stmt->execute([$colorMarca, $admin['organizacion_id']]);

jsonResponse(['ok' => true]);
