<?php
// Lista todos los testimonios de la organización (publicados o no) para el panel de admin.
require_once __DIR__ . '/../includes/auth.php';
$admin = requireAdminAuth();

$pdo = getDB();
$stmt = $pdo->prepare("SELECT * FROM testimonios WHERE organizacion_id = ? ORDER BY orden ASC, id ASC");
$stmt->execute([$admin['organizacion_id']]);
$filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$testimonios = array_map(function ($f) {
    return [
        'id' => (int) $f['id'],
        'foto_url' => $f['foto_url'],
        'nombre' => $f['nombre'],
        'ciudad' => $f['ciudad'],
        'testimonio' => $f['testimonio'],
        'fecha_registro' => $f['fecha_registro'],
        'publicado' => (bool) $f['publicado'],
        'autorizacion_confirmada' => (bool) $f['autorizacion_confirmada'],
    ];
}, $filas);

jsonResponse(['ok' => true, 'testimonios' => $testimonios]);
