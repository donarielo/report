<?php
require_once __DIR__ . '/../includes/auth.php';
$socioId = requireSocioAuth();

$in = jsonInput();
$monto = floatval($in['monto'] ?? 0);

$pdo = getDB();
$stmt = $pdo->prepare("SELECT s.*, o.retiro_minimo FROM socios s JOIN organizaciones o ON o.id = s.organizacion_id WHERE s.id = ?");
$stmt->execute([$socioId]);
$socio = $stmt->fetch(PDO::FETCH_ASSOC);

if ($monto < $socio['retiro_minimo']) {
    jsonResponse(['error' => 'El monto mínimo de retiro es $' . $socio['retiro_minimo']], 400);
}
if ($monto > $socio['saldo_disponible']) {
    jsonResponse(['error' => 'No tienes saldo suficiente disponible.'], 400);
}

$stmt = $pdo->prepare("INSERT INTO solicitudes_retiro (socio_id, monto, estado) VALUES (?, ?, 'pendiente')");
$stmt->execute([$socioId, $monto]);

jsonResponse(['ok' => true, 'solicitud_id' => $pdo->lastInsertId()]);
