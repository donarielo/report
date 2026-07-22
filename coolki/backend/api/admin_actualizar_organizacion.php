<?php
// El administrador ajusta las reglas de su caja (no las credenciales de PayPhone,
// esas se cambian directamente en la base de datos por seguridad).
require_once __DIR__ . '/../includes/auth.php';
$admin = requireAdminAuth();

$in = jsonInput();
$tasaPlazoFijo = floatval($in['tasa_plazo_fijo'] ?? -1);
$retiroMinimo = floatval($in['retiro_minimo'] ?? -1);
$aporteInicial = floatval($in['aporte_inicial'] ?? -1);

if ($tasaPlazoFijo < 0 || $retiroMinimo < 0 || $aporteInicial < 0) {
    jsonResponse(['error' => 'Todos los valores deben ser números válidos mayores o iguales a 0.'], 400);
}

$pdo = getDB();
$stmt = $pdo->prepare(
    "UPDATE organizaciones SET tasa_plazo_fijo = ?, retiro_minimo = ?, aporte_inicial = ? WHERE id = ?"
);
$stmt->execute([$tasaPlazoFijo, $retiroMinimo, $aporteInicial, $admin['organizacion_id']]);

jsonResponse(['ok' => true]);
