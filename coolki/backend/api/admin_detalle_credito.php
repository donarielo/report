<?php
// Detalle completo de una solicitud de crédito: datos del socio, su historial de
// créditos anteriores, y la tabla de cuotas si ya fue aprobado.
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/creditos.php';
$admin = requireAdminAuth();

$creditoId = intval($_GET['credito_id'] ?? 0);
if (!$creditoId) {
    jsonResponse(['error' => 'Falta el id del crédito.'], 400);
}

$pdo = getDB();

$stmt = $pdo->prepare(
    "SELECT c.*, s.nombre AS socio_nombre, s.cedula, s.email, s.celular,
            s.saldo_disponible, s.saldo_congelado, s.estado AS socio_estado, s.created_at AS socio_desde,
            s.organizacion_id
     FROM creditos c JOIN socios s ON s.id = c.socio_id
     WHERE c.id = ?"
);
$stmt->execute([$creditoId]);
$credito = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$credito || $credito['organizacion_id'] != $admin['organizacion_id']) {
    jsonResponse(['error' => 'Crédito no encontrado.'], 404);
}

$stmt = $pdo->prepare("SELECT * FROM organizaciones WHERE id = ?");
$stmt->execute([$admin['organizacion_id']]);
$org = $stmt->fetch(PDO::FETCH_ASSOC);
$credito['cupo_sugerido'] = calcularCupoSugerido($pdo, $credito['socio_id'], $org);

$stmt = $pdo->prepare(
    "SELECT id, numero_cuota, fecha_vencimiento, monto_cuota, estado, pagado_at
     FROM credito_cuotas WHERE credito_id = ? ORDER BY numero_cuota ASC"
);
$stmt->execute([$creditoId]);
$cuotas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare(
    "SELECT id, monto, plazo_meses, estado, en_mora, created_at, resuelto_at
     FROM creditos WHERE socio_id = ? AND id != ? ORDER BY created_at DESC"
);
$stmt->execute([$credito['socio_id'], $creditoId]);
$historial = $stmt->fetchAll(PDO::FETCH_ASSOC);

jsonResponse([
    'ok' => true,
    'credito' => $credito,
    'cuotas' => $cuotas,
    'historial_creditos' => $historial,
]);
