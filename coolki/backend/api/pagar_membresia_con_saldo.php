<?php
// El socio paga la actualización a "Cliente Premium" directamente desde su saldo
// disponible, sin pasar por PayPhone. Este monto NO se acredita a ningún saldo del
// socio (ni disponible ni congelado) — es ingreso puro de la organización
// ("fondos de reserva / gastos administrativos").
require_once __DIR__ . '/../includes/auth.php';
$socioId = requireSocioAuth();

$pdo = getDB();
$stmt = $pdo->prepare(
    "SELECT s.saldo_disponible, s.nivel, o.membresia_premium_costo
     FROM socios s JOIN organizaciones o ON o.id = s.organizacion_id
     WHERE s.id = ?"
);
$stmt->execute([$socioId]);
$socio = $stmt->fetch(PDO::FETCH_ASSOC);

if ($socio['nivel'] === 'premium') {
    jsonResponse(['error' => 'Ya eres Cliente Premium.'], 400);
}

$costo = floatval($socio['membresia_premium_costo']);
if (floatval($socio['saldo_disponible']) < $costo) {
    jsonResponse(['error' => 'No tienes saldo suficiente disponible para pagar la membresía Premium.'], 400);
}

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("UPDATE socios SET saldo_disponible = saldo_disponible - ?, nivel = 'premium' WHERE id = ?");
    $stmt->execute([$costo, $socioId]);

    $stmt = $pdo->prepare(
        "INSERT INTO transacciones (socio_id, tipo, monto, estado) VALUES (?, 'membresia_premium', ?, 'confirmado')"
    );
    $stmt->execute([$socioId, $costo]);

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    jsonResponse(['error' => 'No se pudo procesar el pago.'], 500);
}

jsonResponse(['ok' => true]);
