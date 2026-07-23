<?php
// El socio solicita un microcrédito. Queda en 'pendiente' hasta que el administrador lo
// revise manualmente (ver admin_resolver_credito.php) — aquí NO se aprueba nada automático.
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/creditos.php';
$socioId = requireSocioAuth();

$in = jsonInput();
$monto = floatval($in['monto'] ?? 0);
$plazoMeses = intval($in['plazo_meses'] ?? 0);

$pdo = getDB();
$stmt = $pdo->prepare(
    "SELECT s.*, o.credito_tasa_anual, o.credito_limite_base, o.credito_incremento_por_pago,
            o.credito_limite_maximo, o.credito_plazo_min_meses, o.credito_plazo_max_meses
     FROM socios s JOIN organizaciones o ON o.id = s.organizacion_id
     WHERE s.id = ?"
);
$stmt->execute([$socioId]);
$socio = $stmt->fetch(PDO::FETCH_ASSOC);

if ($socio['estado'] !== 'activo') {
    jsonResponse(['error' => 'Debes completar tu aporte inicial antes de solicitar un crédito.'], 400);
}
if ($monto <= 0 || $monto > floatval($socio['credito_limite_maximo'])) {
    jsonResponse(['error' => 'Monto inválido. El máximo permitido es $' . $socio['credito_limite_maximo'] . '.'], 400);
}
if ($plazoMeses < intval($socio['credito_plazo_min_meses']) || $plazoMeses > intval($socio['credito_plazo_max_meses'])) {
    jsonResponse(['error' => 'El plazo debe estar entre ' . $socio['credito_plazo_min_meses'] . ' y ' . $socio['credito_plazo_max_meses'] . ' meses.'], 400);
}

$stmt = $pdo->prepare("SELECT id FROM creditos WHERE socio_id = ? AND estado IN ('pendiente', 'activo')");
$stmt->execute([$socioId]);
if ($stmt->fetch()) {
    jsonResponse(['error' => 'Ya tienes un crédito pendiente o activo. Debes terminar de pagarlo antes de solicitar otro.'], 400);
}

$tasaAnual = floatval($socio['credito_tasa_anual']);
$cuotaMensual = calcularCuotaMensual($monto, $tasaAnual, $plazoMeses);

$stmt = $pdo->prepare(
    "INSERT INTO creditos (socio_id, monto, plazo_meses, tasa_anual, cuota_mensual, estado)
     VALUES (?, ?, ?, ?, ?, 'pendiente')"
);
$stmt->execute([$socioId, $monto, $plazoMeses, $tasaAnual, $cuotaMensual]);

jsonResponse([
    'ok' => true,
    'credito_id' => $pdo->lastInsertId(),
    'cuota_mensual' => $cuotaMensual,
    'tasa_anual' => $tasaAnual,
]);
