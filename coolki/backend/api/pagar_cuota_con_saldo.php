<?php
// Alternativa a pagar una cuota de crédito con PayPhone: se descuenta directamente del
// saldo_disponible del socio. El monto SIEMPRE se toma de la base de datos
// (credito_cuotas.monto_cuota), nunca del cliente — mismo principio que
// payphone_prepare.php para tipo 'pago_credito'.
require_once __DIR__ . '/../includes/auth.php';
$socioId = requireSocioAuth();

$in = jsonInput();
$cuotaId = intval($in['cuota_id'] ?? 0);
if (!$cuotaId) {
    jsonResponse(['error' => 'Falta indicar qué cuota se va a pagar.'], 400);
}

$pdo = getDB();
$stmt = $pdo->prepare(
    "SELECT cc.*, s.saldo_disponible FROM credito_cuotas cc
     JOIN creditos c ON c.id = cc.credito_id
     JOIN socios s ON s.id = c.socio_id
     WHERE cc.id = ? AND c.socio_id = ? AND cc.estado IN ('pendiente', 'vencida')"
);
$stmt->execute([$cuotaId, $socioId]);
$cuota = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$cuota) {
    jsonResponse(['error' => 'Cuota no encontrada o ya pagada.'], 404);
}

$monto = floatval($cuota['monto_cuota']);
if (floatval($cuota['saldo_disponible']) < $monto) {
    jsonResponse(['error' => 'No tienes saldo suficiente disponible para pagar esta cuota.'], 400);
}

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("UPDATE socios SET saldo_disponible = saldo_disponible - ? WHERE id = ?");
    $stmt->execute([$monto, $socioId]);

    $stmt = $pdo->prepare(
        "INSERT INTO transacciones (socio_id, tipo, monto, credito_cuota_id, estado) VALUES (?, 'pago_credito', ?, ?, 'confirmado')"
    );
    $stmt->execute([$socioId, $monto, $cuotaId]);

    $stmt = $pdo->prepare("UPDATE credito_cuotas SET estado = 'pagada', pagado_at = NOW() WHERE id = ?");
    $stmt->execute([$cuotaId]);

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM credito_cuotas WHERE credito_id = ? AND estado != 'pagada'");
    $stmt->execute([$cuota['credito_id']]);
    if ((int) $stmt->fetchColumn() === 0) {
        $stmt = $pdo->prepare("UPDATE creditos SET estado = 'pagado' WHERE id = ?");
        $stmt->execute([$cuota['credito_id']]);
    }

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    jsonResponse(['error' => 'No se pudo procesar el pago.'], 500);
}

jsonResponse(['ok' => true]);
