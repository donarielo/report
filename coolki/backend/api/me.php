<?php
// Datos del panel del socio: saldo, movimientos recientes, plazos fijos,
// si tiene una solicitud de retiro pendiente, y su situación de crédito.
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/creditos.php';
$socioId = requireSocioAuth();

$pdo = getDB();

$stmt = $pdo->prepare(
    "SELECT s.id, s.nombre, s.cedula, s.email, s.celular, s.saldo_disponible, s.saldo_congelado, s.estado, s.created_at,
            o.retiro_minimo, o.aporte_inicial, o.tasa_plazo_fijo, o.comision_deposito_pct, o.iva_pct,
            o.credito_tasa_anual, o.credito_limite_base, o.credito_incremento_por_pago,
            o.credito_limite_maximo, o.credito_plazo_min_meses, o.credito_plazo_max_meses
     FROM socios s JOIN organizaciones o ON o.id = s.organizacion_id
     WHERE s.id = ?"
);
$stmt->execute([$socioId]);
$socio = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$socio) {
    jsonResponse(['error' => 'Socio no encontrado.'], 404);
}

$stmt = $pdo->prepare(
    "SELECT tipo, monto, comision, iva, estado, created_at FROM transacciones
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

// Crédito vigente (pendiente de revisión o ya activo) con su tabla de cuotas.
$stmt = $pdo->prepare(
    "SELECT * FROM creditos WHERE socio_id = ? AND estado IN ('pendiente', 'activo')
     ORDER BY created_at DESC LIMIT 1"
);
$stmt->execute([$socioId]);
$creditoActual = $stmt->fetch(PDO::FETCH_ASSOC);

$cuotasCredito = [];
if ($creditoActual) {
    $stmt = $pdo->prepare(
        "SELECT id, numero_cuota, fecha_vencimiento, monto_cuota, estado, pagado_at
         FROM credito_cuotas WHERE credito_id = ? ORDER BY numero_cuota ASC"
    );
    $stmt->execute([$creditoActual['id']]);
    $cuotasCredito = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$stmt = $pdo->prepare(
    "SELECT id, monto, plazo_meses, estado, en_mora, mensaje_admin, created_at, resuelto_at
     FROM creditos WHERE socio_id = ? ORDER BY created_at DESC"
);
$stmt->execute([$socioId]);
$historialCreditos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cupoSugerido = calcularCupoSugerido($pdo, $socioId, $socio);

jsonResponse([
    'ok' => true,
    'socio' => $socio,
    'movimientos' => $movimientos,
    'plazos_fijos' => $plazosFijos,
    'retiros_pendientes' => $retirosPendientes,
    'credito_actual' => $creditoActual,
    'cuotas_credito' => $cuotasCredito,
    'historial_creditos' => $historialCreditos,
    'cupo_sugerido' => $cupoSugerido,
]);
