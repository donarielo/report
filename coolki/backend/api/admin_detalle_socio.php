<?php
// Detalle completo de un socio para el panel de administrador: datos personales,
// saldo, plazos fijos, historial de transacciones y solicitudes de retiro.
require_once __DIR__ . '/../includes/auth.php';
$admin = requireAdminAuth();

$socioId = intval($_GET['socio_id'] ?? 0);
if (!$socioId) {
    jsonResponse(['error' => 'Falta el id del socio.'], 400);
}

$pdo = getDB();
$stmt = $pdo->prepare(
    "SELECT id, nombre, cedula, email, celular, saldo_disponible, saldo_congelado, estado, created_at
     FROM socios WHERE id = ? AND organizacion_id = ?"
);
$stmt->execute([$socioId, $admin['organizacion_id']]);
$socio = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$socio) {
    jsonResponse(['error' => 'Socio no encontrado.'], 404);
}

$stmt = $pdo->prepare(
    "SELECT id, tipo, monto, comision, iva, estado, created_at FROM transacciones
     WHERE socio_id = ? ORDER BY created_at DESC LIMIT 100"
);
$stmt->execute([$socioId]);
$transacciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare(
    "SELECT id, capital, tasa_anual, fecha_inicio, fecha_vencimiento, generado
     FROM plazos_fijos WHERE socio_id = ? ORDER BY fecha_inicio DESC"
);
$stmt->execute([$socioId]);
$plazosFijos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare(
    "SELECT id, monto, estado, created_at, resuelto_at FROM solicitudes_retiro
     WHERE socio_id = ? ORDER BY created_at DESC"
);
$stmt->execute([$socioId]);
$solicitudesRetiro = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare(
    "SELECT id, monto, plazo_meses, tasa_anual, cuota_mensual, estado, en_mora, mensaje_admin, created_at, resuelto_at
     FROM creditos WHERE socio_id = ? ORDER BY created_at DESC"
);
$stmt->execute([$socioId]);
$creditos = $stmt->fetchAll(PDO::FETCH_ASSOC);

jsonResponse([
    'ok' => true,
    'socio' => $socio,
    'transacciones' => $transacciones,
    'plazos_fijos' => $plazosFijos,
    'solicitudes_retiro' => $solicitudesRetiro,
    'creditos' => $creditos,
]);
