<?php
require_once __DIR__ . '/../includes/auth.php';
iniciarSesion();

$in = jsonInput();
$slug = trim($in['organizacion'] ?? '');
$nombre = trim($in['nombre'] ?? '');
$cedula = trim($in['cedula'] ?? '');
$email = trim($in['email'] ?? '');
$celular = trim($in['celular'] ?? '');
$password = $in['password'] ?? '';
$terminosAceptados = !empty($in['terminos_aceptados']);

if (!$slug || !$nombre || !$cedula || !$email || strlen($password) < 8) {
    jsonResponse(['error' => 'Completa todos los campos. La contraseña debe tener al menos 8 caracteres.'], 400);
}
if (!$terminosAceptados) {
    jsonResponse(['error' => 'Debes aceptar los Términos y Condiciones para crear tu cuenta.'], 400);
}

$org = getOrganizacionPorSlug($slug);
if (!$org) {
    jsonResponse(['error' => 'Organización no encontrada.'], 404);
}

$pdo = getDB();

$stmt = $pdo->prepare("SELECT id FROM socios WHERE organizacion_id = ? AND email = ?");
$stmt->execute([$org['id'], $email]);
if ($stmt->fetch()) {
    jsonResponse(['error' => 'Ya existe una cuenta con ese correo en esta caja.'], 409);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare(
    "INSERT INTO socios (organizacion_id, nombre, cedula, email, celular, password_hash, estado, terminos_aceptados_at)
     VALUES (?, ?, ?, ?, ?, ?, 'pendiente_pago', NOW())"
);
$stmt->execute([$org['id'], $nombre, $cedula, $email, $celular, $hash]);
$socioId = $pdo->lastInsertId();

$_SESSION['socio_id'] = $socioId;
$_SESSION['organizacion_id'] = $org['id'];

jsonResponse([
    'ok' => true,
    'socio_id' => $socioId,
    'aporte_inicial' => $org['aporte_inicial'],
]);
