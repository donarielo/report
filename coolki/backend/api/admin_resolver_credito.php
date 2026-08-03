<?php
// El administrador aprueba o rechaza una solicitud de crédito, dejando siempre un
// mensaje que el socio va a ver explicando la decisión.
// Al aprobar: se desembolsa el monto al saldo disponible del socio y se genera la
// tabla de cuotas mensuales (fecha de vencimiento y monto de cada una).
require_once __DIR__ . '/../includes/auth.php';
$admin = requireAdminAuth();

$in = jsonInput();
$creditoId = intval($in['credito_id'] ?? 0);
$accion = $in['accion'] ?? ''; // 'aprobar' o 'rechazar'
$mensaje = trim($in['mensaje'] ?? '');

if (!$mensaje) {
    jsonResponse(['error' => 'Escribe un mensaje explicando la decisión para el socio.'], 400);
}

$pdo = getDB();
$stmt = $pdo->prepare(
    "SELECT c.*, s.organizacion_id FROM creditos c JOIN socios s ON s.id = c.socio_id WHERE c.id = ?"
);
$stmt->execute([$creditoId]);
$credito = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$credito || $credito['organizacion_id'] != $admin['organizacion_id']) {
    jsonResponse(['error' => 'Crédito no encontrado.'], 404);
}
if ($credito['estado'] !== 'pendiente') {
    jsonResponse(['error' => 'Esta solicitud ya fue resuelta.'], 400);
}

$pdo->beginTransaction();
try {
    if ($accion === 'aprobar') {
        $stmt = $pdo->prepare(
            "UPDATE creditos SET estado = 'activo', mensaje_admin = ?, resuelto_at = NOW() WHERE id = ?"
        );
        $stmt->execute([$mensaje, $creditoId]);

        $stmt = $pdo->prepare("UPDATE socios SET saldo_disponible = saldo_disponible + ? WHERE id = ?");
        $stmt->execute([$credito['monto'], $credito['socio_id']]);

        $stmt = $pdo->prepare(
            "INSERT INTO transacciones (socio_id, tipo, monto, estado) VALUES (?, 'credito_desembolso', ?, 'confirmado')"
        );
        $stmt->execute([$credito['socio_id'], $credito['monto']]);

        $stmt = $pdo->prepare(
            "INSERT INTO credito_cuotas (credito_id, numero_cuota, fecha_vencimiento, monto_cuota)
             VALUES (?, ?, DATE_ADD(CURDATE(), INTERVAL ? MONTH), ?)"
        );
        for ($i = 1; $i <= intval($credito['plazo_meses']); $i++) {
            $stmt->execute([$creditoId, $i, $i, $credito['cuota_mensual']]);
        }
    } elseif ($accion === 'rechazar') {
        $stmt = $pdo->prepare(
            "UPDATE creditos SET estado = 'rechazado', mensaje_admin = ?, resuelto_at = NOW() WHERE id = ?"
        );
        $stmt->execute([$mensaje, $creditoId]);
    } else {
        throw new Exception('Acción inválida.');
    }
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    jsonResponse(['error' => 'No se pudo procesar la solicitud.'], 500);
}

jsonResponse(['ok' => true]);
