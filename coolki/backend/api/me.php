<?php
// Datos del panel del socio: saldo, movimientos recientes, plazos fijos y
// si tiene una solicitud de retiro pendiente.
require_once __DIR__ . '/../includes/auth.php';
$socioId = requireSocioAuth();

$pdo = getDB();

$stmt = $pdo->prepare(
    "SELECT s.id, s.nombre, s.email, s.saldo_disponible, s.saldo_congelado, s.estado, s.created_at,
            o.retiro_minimo, o.aporte_inicial, o.tasa_plazo_fijo
     FROM socios s JOIN organizaciones o ON o.id = s.organizacion_id
     WHERE s.id = ?"
);
$stmt->execute([$socioId]);
$socio = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$socio) {
    jsonResponse(['error' => 'Socio no encontrado.'], 404);
}

$stmt = $pdo->prepare(
    "SELECT tipo, monto, estado, created_at FROM transacciones
     WHERE socio_id = ? AND estado = 'confirmado' ORDER BY created_at DESC LIMIT 20"
);
$stmt->execute([$socioId]);
$movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare(
    "SELECT id, capital, tasa_anual, fecha_inicio, fecha_vencimiento, generado
     FROM plazos_fijos WHERE socio_id = ? ORDER BY fecha_inicio DESC"
);
$stmt->execute([$socioId]);
$plazosFijos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare(
    "SELECT id, monto, created_at FROM solicitudes_retiro
     WHERE socio_id = ? AND estado = 'pendiente' ORDER BY created_at DESC"
);
$stmt->execute([$socioId]);
$retirosPendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

jsonResponse([
    'ok' => true,
    'socio' => $socio,
    'movimientos' => $movimientos,
    'plazos_fijos' => $plazosFijos,
    'retiros_pendientes' => $retirosPendientes,
]);
