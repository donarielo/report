<?php
// Crea o actualiza (si viene "id") un testimonio. La foto se sube antes por separado
// (ver admin_subir_foto_testimonio.php) y aquí solo se recibe la URL ya guardada.
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/uploads.php';
$admin = requireAdminAuth();

$in = jsonInput();
$id = isset($in['id']) ? (int) $in['id'] : null;
$nombre = trim($in['nombre'] ?? '');
$ciudad = trim($in['ciudad'] ?? '') ?: null;
$testimonio = trim($in['testimonio'] ?? '');
$fechaRegistro = trim($in['fecha_registro'] ?? '') ?: null;
$fotoUrl = trim($in['foto_url'] ?? '') ?: null;
$autorizacionConfirmada = !empty($in['autorizacion_confirmada']);
$publicado = !empty($in['publicado']);

if ($nombre === '' || mb_strlen($nombre) > 100) {
    jsonResponse(['error' => 'El nombre es obligatorio (máx. 100 caracteres).'], 400);
}
if ($testimonio === '') {
    jsonResponse(['error' => 'El testimonio no puede estar vacío.'], 400);
}
if ($publicado && !$autorizacionConfirmada) {
    jsonResponse(['error' => 'Solo puedes publicar un testimonio si confirmas que el socio autorizó su uso.'], 400);
}
if ($fotoUrl !== null && !preg_match('#^/uploads/testimonios/[a-f0-9]{32}\.(jpg|jpeg|png|webp)$#i', $fotoUrl)) {
    jsonResponse(['error' => 'La foto no es válida.'], 400);
}

$pdo = getDB();

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM testimonios WHERE id = ? AND organizacion_id = ?");
    $stmt->execute([$id, $admin['organizacion_id']]);
    $actual = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$actual) {
        jsonResponse(['error' => 'Testimonio no encontrado.'], 404);
    }

    if ($actual['foto_url'] && $actual['foto_url'] !== $fotoUrl) {
        eliminarArchivoTestimonio($actual['foto_url']);
    }

    $stmt = $pdo->prepare(
        "UPDATE testimonios SET foto_url = ?, nombre = ?, ciudad = ?, testimonio = ?, fecha_registro = ?,
            publicado = ?, autorizacion_confirmada = ?
         WHERE id = ? AND organizacion_id = ?"
    );
    $stmt->execute([
        $fotoUrl, $nombre, $ciudad, $testimonio, $fechaRegistro,
        $publicado ? 1 : 0, $autorizacionConfirmada ? 1 : 0,
        $id, $admin['organizacion_id'],
    ]);
    jsonResponse(['ok' => true, 'id' => $id]);
}

$stmt = $pdo->prepare("SELECT COALESCE(MAX(orden), -1) + 1 AS siguiente FROM testimonios WHERE organizacion_id = ?");
$stmt->execute([$admin['organizacion_id']]);
$orden = (int) $stmt->fetch(PDO::FETCH_ASSOC)['siguiente'];

$stmt = $pdo->prepare(
    "INSERT INTO testimonios
        (organizacion_id, foto_url, nombre, ciudad, testimonio, fecha_registro, publicado, autorizacion_confirmada, orden)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
$stmt->execute([
    $admin['organizacion_id'], $fotoUrl, $nombre, $ciudad, $testimonio, $fechaRegistro,
    $publicado ? 1 : 0, $autorizacionConfirmada ? 1 : 0, $orden,
]);

jsonResponse(['ok' => true, 'id' => (int) $pdo->lastInsertId()]);
