<?php
// Esta es la "URL de respuesta" que configuras en tu cuenta de PayPhone Developer.
// PayPhone llama a esta URL server-to-server (o el navegador redirige aquí) enviando
// el id de transacción y el clientTransactionId. Aquí, y SOLO aquí, se confirma el pago
// y se actualiza el saldo — nunca confiamos en lo que diga el frontend.

require_once __DIR__ . '/../includes/db.php';

$id = $_REQUEST['id'] ?? null;
$clientTransactionId = $_REQUEST['clientTransactionId'] ?? null;

if (!$id || !$clientTransactionId) {
    jsonResponse(['error' => 'Faltan parámetros de PayPhone.'], 400);
}

$pdo = getDB();
$stmt = $pdo->prepare(
    "SELECT t.*, s.organizacion_id, o.payphone_token
     FROM transacciones t
     JOIN socios s ON s.id = t.socio_id
     JOIN organizaciones o ON o.id = s.organizacion_id
     WHERE t.payphone_client_transaction_id = ?"
);
$stmt->execute([$clientTransactionId]);
$tx = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tx) {
    jsonResponse(['error' => 'Transacción no encontrada.'], 404);
}
if ($tx['estado'] !== 'pendiente') {
    jsonResponse(['ok' => true, 'nota' => 'Esta transacción ya había sido procesada.']);
}

// Llamada server-to-server a PayPhone para confirmar el estado real del pago.
// Endpoint según la documentación oficial de "Botón / Cajita de pagos":
$ch = curl_init('https://pay.payphonetodoesposible.com/api/button/V2/Confirm');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $tx['payphone_token'],
        'Content-Type: application/json',
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'id' => (int) $id,
        'clientTxId' => $clientTransactionId,
    ]),
]);
$respuesta = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($respuesta, true);

// IMPORTANTE: verifica en tus pruebas reales cómo identifica PayPhone un pago aprobado
// en la respuesta de Confirm (puede ser un campo "statusCode", "transactionStatus" u otro,
// y puede cambiar entre versiones de su API). Ajusta esta condición con el valor real
// que veas en tu ambiente de pruebas de PayPhone antes de pasar a producción.
$aprobado = $httpCode === 200 && isset($data['transactionStatus']) && $data['transactionStatus'] === 'Approved';

$pdo->beginTransaction();
try {
    if ($aprobado) {
        $stmt = $pdo->prepare(
            "UPDATE transacciones SET estado = 'confirmado', payphone_transaction_id = ? WHERE id = ?"
        );
        $stmt->execute([$id, $tx['id']]);

        if ($tx['tipo'] === 'aporte_inicial') {
            $stmt = $pdo->prepare(
                "UPDATE socios SET saldo_congelado = saldo_congelado + ?, estado = 'activo' WHERE id = ?"
            );
            $stmt->execute([$tx['monto'], $tx['socio_id']]);
        } else {
            $stmt = $pdo->prepare(
                "UPDATE socios SET saldo_disponible = saldo_disponible + ? WHERE id = ?"
            );
            $stmt->execute([$tx['monto'], $tx['socio_id']]);
        }
    } else {
        $stmt = $pdo->prepare("UPDATE transacciones SET estado = 'rechazado' WHERE id = ?");
        $stmt->execute([$tx['id']]);
    }
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    jsonResponse(['error' => 'Error al procesar la confirmación.'], 500);
}

jsonResponse(['ok' => true, 'aprobado' => $aprobado]);
