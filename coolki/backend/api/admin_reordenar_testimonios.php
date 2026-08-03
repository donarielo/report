<?php
// Guarda el nuevo orden de los testimonios (arrastrar y soltar en admin.html). Recibe la
// lista completa de ids en el orden deseado; cualquier id que no sea del admin se ignora.
require_once __DIR__ . '/../includes/auth.php';
$admin = requireAdminAuth();

$in = jsonInput();
$ids = is_array($in['ids'] ?? null) ? $in['ids'] : [];

$pdo = getDB();
$stmt = $pdo->prepare("UPDATE testimonios SET orden = ? WHERE id = ? AND organizacion_id = ?");
foreach ($ids as $i => $id) {
    $stmt->execute([$i, (int) $id, $admin['organizacion_id']]);
}

jsonResponse(['ok' => true]);
