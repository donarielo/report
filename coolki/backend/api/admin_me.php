<?php
// Restaura la sesión del administrador al recargar la página (sin pedir login de nuevo).
require_once __DIR__ . '/../includes/auth.php';
$admin = requireAdminAuth();

$pdo = getDB();
$stmt = $pdo->prepare("SELECT email FROM admins WHERE id = ?");
$stmt->execute([$admin['admin_id']]);
$adminRow = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM organizaciones WHERE id = ?");
$stmt->execute([$admin['organizacion_id']]);
$org = $stmt->fetch(PDO::FETCH_ASSOC);

jsonResponse([
    'ok' => true,
    'admin' => ['email' => $adminRow['email'] ?? ''],
    'organizacion' => [
        'nombre' => $org['nombre'],
        'slug' => $org['slug'],
        'aporte_inicial' => $org['aporte_inicial'],
        'retiro_minimo' => $org['retiro_minimo'],
        'tasa_plazo_fijo' => $org['tasa_plazo_fijo'],
        'aprobacion_retiros' => $org['aprobacion_retiros'],
        'payphone_store_id' => $org['payphone_store_id'],
    ],
]);
