<?php
// El socio coloca parte de su saldo disponible a plazo fijo.
// El capital queda apartado (se descuenta de saldo_disponible) y gana interés
// mediante el cron de intereses (cron/acreditar_intereses.php). Al vencer el
// plazo, ese cron acredita el interés generado y renueva el plazo automáticamente
// (así lo anuncia la app: "vence... se renueva automáticamente").
require_once __DIR__ . '/../includes/auth.php';
$socioId = requireSocioAuth();

$in = jsonInput();
$monto = floatval($in['monto'] ?? 0);

if ($monto <= 0) {
    jsonResponse(['error' => 'Monto inválido.'], 400);
}

$pdo = getDB();
$stmt = $pdo->prepare(
    "SELECT s.saldo_disponible, o.tasa_plazo_fijo
     FROM socios s JOIN organizaciones o ON o.id = s.organizacion_id
     WHERE s.id = ?"
);
$stmt->execute([$socioId]);
$socio = $stmt->fetch(PDO::FETCH_ASSOC);

if ($monto > $socio['saldo_disponible']) {
    jsonResponse(['error' => 'No tienes saldo suficiente disponible.'], 400);
}

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("UPDATE socios SET saldo_disponible = saldo_disponible - ? WHERE id = ?");
    $stmt->execute([$monto, $socioId]);

    $stmt = $pdo->prepare(
        "INSERT INTO plazos_fijos (socio_id, capital, tasa_anual, fecha_inicio, fecha_vencimiento, generado)
         VALUES (?, ?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 0)"
    );
    $stmt->execute([$socioId, $monto, $socio['tasa_plazo_fijo']]);
    $plazoId = $pdo->lastInsertId();

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    jsonResponse(['error' => 'No se pudo crear el plazo fijo.'], 500);
}

jsonResponse(['ok' => true, 'plazo_fijo_id' => $plazoId]);
