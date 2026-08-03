<?php
// Cobra un monto fijo (ej. comisión de mantenimiento) a TODOS los socios de la
// organización, descontándolo de su saldo disponible. Nunca deja el saldo negativo:
// a un socio con menos saldo que el monto se le cobra solo lo que tiene disponible.
// Cada cobro queda registrado en transacciones (tipo 'comision_admin') para que el
// socio lo vea en su historial y quede trazabilidad completa para el administrador.
require_once __DIR__ . '/../includes/auth.php';
$admin = requireAdminAuth();

$in = jsonInput();
$monto = floatval($in['monto'] ?? 0);

if ($monto <= 0) {
    jsonResponse(['error' => 'El monto debe ser mayor a 0.'], 400);
}

$pdo = getDB();
$stmt = $pdo->prepare("SELECT id, saldo_disponible FROM socios WHERE organizacion_id = ?");
$stmt->execute([$admin['organizacion_id']]);
$socios = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cobrados = 0;
$parciales = 0;
$omitidos = 0;

$pdo->beginTransaction();
try {
    foreach ($socios as $socio) {
        $aCobrar = min($monto, floatval($socio['saldo_disponible']));
        if ($aCobrar <= 0) {
            $omitidos++;
            continue;
        }
        $stmt = $pdo->prepare("UPDATE socios SET saldo_disponible = saldo_disponible - ? WHERE id = ?");
        $stmt->execute([$aCobrar, $socio['id']]);
        $stmt = $pdo->prepare(
            "INSERT INTO transacciones (socio_id, tipo, monto, estado) VALUES (?, 'comision_admin', ?, 'confirmado')"
        );
        $stmt->execute([$socio['id'], $aCobrar]);
        $cobrados++;
        if ($aCobrar < $monto) $parciales++;
    }
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    jsonResponse(['error' => 'No se pudo procesar el cobro.'], 500);
}

jsonResponse([
    'ok' => true,
    'total_socios' => count($socios),
    'socios_cobrados' => $cobrados,
    'cobros_parciales' => $parciales,
    'socios_sin_saldo' => $omitidos,
]);
