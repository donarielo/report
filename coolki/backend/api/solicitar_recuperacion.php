<?php
// El socio pide un enlace para restablecer su contraseña. La respuesta es SIEMPRE el mismo
// mensaje genérico, exista o no una cuenta con ese correo — nunca se revela si un correo
// está registrado. El envío de correo nunca cambia la respuesta HTTP (falla en silencio).
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mailer.php';

$MENSAJE_GENERICO = 'Si el correo corresponde a una cuenta registrada, recibirás las instrucciones para recuperar tu acceso.';

$in = jsonInput();
$slug = trim($in['organizacion'] ?? '');
$email = trim($in['email'] ?? '');

$org = getOrganizacionPorSlug($slug);
if (!$org) {
    jsonResponse(['error' => 'Organización no encontrada.'], 404);
}
if (!$email) {
    jsonResponse(['error' => 'Ingresa tu correo electrónico.'], 400);
}

$pdo = getDB();
$stmt = $pdo->prepare("SELECT * FROM socios WHERE organizacion_id = ? AND email = ?");
$stmt->execute([$org['id'], $email]);
$socio = $stmt->fetch(PDO::FETCH_ASSOC);

if ($socio) {
    $pdo->prepare("UPDATE password_resets SET used_at = NOW() WHERE socio_id = ? AND used_at IS NULL")
        ->execute([$socio['id']]);

    $tokenCrudo = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $tokenCrudo);
    $stmt = $pdo->prepare(
        "INSERT INTO password_resets (socio_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))"
    );
    $stmt->execute([$socio['id'], $tokenHash]);

    $resetUrl = rtrim(SITE_URL, '/') . '/restablecer?token=' . urlencode($tokenCrudo) . '&org=' . urlencode($org['slug']);
    enviarCorreoRecuperacion($socio, $org, $resetUrl);
}

jsonResponse(['ok' => true, 'mensaje' => $MENSAJE_GENERICO]);
