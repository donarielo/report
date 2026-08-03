<?php
// Borra un testimonio y, si tenía foto, también su archivo en disco.
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/uploads.php';
$admin = requireAdminAuth();

$in = jsonInput();
$id = (int) ($in['id'] ?? 0);
if (!$id) {
    jsonResponse(['error' => 'Falta el id del testimonio.'], 400);
}

$pdo = getDB();
$stmt = $pdo->prepare("SELECT foto_url FROM testimonios WHERE id = ? AND organizacion_id = ?");
$stmt->execute([$id, $admin['organizacion_id']]);
$fila = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$fila) {
    jsonResponse(['error' => 'Testimonio no encontrado.'], 404);
}

$stmt = $pdo->prepare("DELETE FROM testimonios WHERE id = ? AND organizacion_id = ?");
$stmt->execute([$id, $admin['organizacion_id']]);

if ($fila['foto_url']) {
    eliminarArchivoTestimonio($fila['foto_url']);
}

jsonResponse(['ok' => true]);
