<?php
require_once __DIR__ . '/../includes/auth.php';
$admin = requireAdminAuth();

$in = jsonInput();
$solicitudId = intval($in['solicitud_id'] ?? 0);
$accion = $in['accion'] ?? ''; // 'aprobar' o 'rechazar'

$pdo = getDB();
$stmt = $pdo->prepare(
    "SELECT sr.*, s.organizacion_id, s.saldo_disponible
     FROM solicitudes_retiro sr JOIN socios s ON s.id = sr.socio_id
     WHERE sr.id = ?"
);
$stmt->execute([$solicitudId]);
$solicitud = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$solicitud || $solicitud['organizacion_id'] != $admin['organizacion_id']) {
    jsonResponse(['error' => 'Solicitud no encontrada.'], 404);
}
if ($solicitud['estado'] !== 'pendiente') {
    jsonResponse(['error' => 'Esta solicitud ya fue resuelta.'], 400);
}

$pdo->beginTransaction();
try {
    if ($accion === 'aprobar') {
        if ($solicitud['monto'] > $solicitud['saldo_disponible']) {
            throw new Exception('El socio ya no tiene saldo suficiente.');
        }
        $stmt = $pdo->prepare("UPDATE socios SET saldo_disponible = saldo_disponible - ? WHERE id = ?");
        $stmt->execute([$solicitud['monto'], $solicitud['socio_id']]);
        $stmt = $pdo->prepare("UPDATE solicitudes_retiro SET estado = 'aprobado', resuelto_at = NOW() WHERE id = ?");
        $stmt->execute([$solicitudId]);
        // Nota: aprobar aquí solo actualiza el saldo interno. El envío real del dinero
        // al socio (transferencia o pago PayPhone hacia su cuenta) se hace aparte, desde
        // tu cuenta de PayPhone Business o tu banco — este sistema todavía no lo automatiza.
    } elseif ($accion === 'rechazar') {
        $stmt = $pdo->prepare("UPDATE solicitudes_retiro SET estado = 'rechazado', resuelto_at = NOW() WHERE id = ?");
        $stmt->execute([$solicitudId]);
    } else {
        throw new Exception('Acción inválida.');
    }
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    jsonResponse(['error' => $e->getMessage()], 400);
}

jsonResponse(['ok' => true]);
