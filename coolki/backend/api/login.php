<?php
require_once __DIR__ . '/../includes/auth.php';
iniciarSesion();

$in = jsonInput();
$slug = trim($in['organizacion'] ?? '');
$email = trim($in['email'] ?? '');
$password = $in['password'] ?? '';

$org = getOrganizacionPorSlug($slug);
if (!$org) {
    jsonResponse(['error' => 'Organización no encontrada.'], 404);
}

$pdo = getDB();
$stmt = $pdo->prepare("SELECT * FROM socios WHERE organizacion_id = ? AND email = ?");
$stmt->execute([$org['id'], $email]);
$socio = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$socio || !password_verify($password, $socio['password_hash'])) {
    jsonResponse(['error' => 'Correo o contraseña incorrectos.'], 401);
}

$_SESSION['socio_id'] = $socio['id'];
$_SESSION['organizacion_id'] = $org['id'];

unset($socio['password_hash']);
jsonResponse(['ok' => true, 'socio' => $socio]);
