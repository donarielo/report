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
$stmt = $pdo->prepare("SELECT * FROM admins WHERE organizacion_id = ? AND email = ?");
$stmt->execute([$org['id'], $email]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin || !password_verify($password, $admin['password_hash'])) {
    jsonResponse(['error' => 'Correo o contraseña incorrectos.'], 401);
}

$_SESSION['admin_id'] = $admin['id'];
$_SESSION['organizacion_id'] = $org['id'];

jsonResponse([
    'ok' => true,
    'admin' => ['id' => $admin['id'], 'email' => $admin['email']],
    'organizacion' => [
        'nombre' => $org['nombre'],
        'slug' => $org['slug'],
        'color_marca' => $org['color_marca'],
        'aporte_inicial' => $org['aporte_inicial'],
        'retiro_minimo' => $org['retiro_minimo'],
        'tasa_plazo_fijo' => $org['tasa_plazo_fijo'],
        'aprobacion_retiros' => $org['aprobacion_retiros'],
        'payphone_store_id' => $org['payphone_store_id'],
    ],
]);
