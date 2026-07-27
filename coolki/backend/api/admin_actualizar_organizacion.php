<?php
// El administrador ajusta las reglas de su caja (no las credenciales de PayPhone,
// esas se cambian directamente en la base de datos por seguridad).
require_once __DIR__ . '/../includes/auth.php';
$admin = requireAdminAuth();

$in = jsonInput();
$tasaPlazoFijo = floatval($in['tasa_plazo_fijo'] ?? -1);
$coolcoinTasaAnual = floatval($in['coolcoin_tasa_anual'] ?? -1);
$retiroMinimo = floatval($in['retiro_minimo'] ?? -1);
$aporteInicial = floatval($in['aporte_inicial'] ?? -1);
$comisionDeposito = floatval($in['comision_deposito_pct'] ?? -1);
$ivaPct = floatval($in['iva_pct'] ?? -1);
$creditoTasaAnual = floatval($in['credito_tasa_anual'] ?? -1);
$creditoLimiteBase = floatval($in['credito_limite_base'] ?? -1);
$creditoIncrementoPorPago = floatval($in['credito_incremento_por_pago'] ?? -1);
$creditoLimiteMaximo = floatval($in['credito_limite_maximo'] ?? -1);
$creditoPlazoMin = intval($in['credito_plazo_min_meses'] ?? -1);
$creditoPlazoMax = intval($in['credito_plazo_max_meses'] ?? -1);

$valores = [
    $tasaPlazoFijo, $coolcoinTasaAnual, $retiroMinimo, $aporteInicial, $comisionDeposito, $ivaPct,
    $creditoTasaAnual, $creditoLimiteBase, $creditoIncrementoPorPago, $creditoLimiteMaximo,
    $creditoPlazoMin, $creditoPlazoMax,
];
foreach ($valores as $v) {
    if ($v < 0) {
        jsonResponse(['error' => 'Todos los valores deben ser números válidos mayores o iguales a 0.'], 400);
    }
}
if ($creditoPlazoMin < 1 || $creditoPlazoMax < $creditoPlazoMin) {
    jsonResponse(['error' => 'El rango de plazo de crédito no es válido.'], 400);
}

$pdo = getDB();
$stmt = $pdo->prepare(
    "UPDATE organizaciones
     SET tasa_plazo_fijo = ?, coolcoin_tasa_anual = ?, retiro_minimo = ?, aporte_inicial = ?,
         comision_deposito_pct = ?, iva_pct = ?,
         credito_tasa_anual = ?, credito_limite_base = ?, credito_incremento_por_pago = ?,
         credito_limite_maximo = ?, credito_plazo_min_meses = ?, credito_plazo_max_meses = ?
     WHERE id = ?"
);
$stmt->execute([
    $tasaPlazoFijo, $coolcoinTasaAnual, $retiroMinimo, $aporteInicial, $comisionDeposito, $ivaPct,
    $creditoTasaAnual, $creditoLimiteBase, $creditoIncrementoPorPago, $creditoLimiteMaximo,
    $creditoPlazoMin, $creditoPlazoMax, $admin['organizacion_id'],
]);

jsonResponse(['ok' => true]);
