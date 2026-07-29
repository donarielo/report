<?php
// Prepara una transacción antes de mostrar el widget de PayPhone.
// La "Cajita de Pagos" (payment box) de PayPhone REQUIERE el token en el navegador para
// renderizarse — es inherente a ese método de integración. Por eso aquí sí devolvemos el
// token junto con el ID de transacción propio.
// La seguridad NO depende de ocultar el token: el pago sólo se acredita en payphone_confirm.php,
// que verifica el estado real contra PayPhone del lado del servidor (nunca confía en el frontend).
// (Si quisieras que el token jamás salga al navegador, tendrías que usar el "Botón de pago por
//  redirección" en lugar de la Cajita — es otro método de integración.)
//
// storeId: SOLO aplica si la organización maneja varias tiendas/sucursales dentro de una misma
// cuenta de PayPhone (se obtiene en PayPhone Developer, sección "Lista de tiendas" de la empresa,
// NO es el "Identificador" de la aplicación). Si la organización tiene una sola tienda, este campo
// debe omitirse por completo — enviar cualquier valor ahí revienta el pago con el error de
// PayPhone "La tienda asociada no existe".

require_once __DIR__ . '/../includes/auth.php';
$socioId = requireSocioAuth();

$in = jsonInput();
$monto = floatval($in['monto'] ?? 0);
$tipo = $in['tipo'] ?? 'aporte'; // 'aporte_inicial', 'aporte', 'pago_credito' o 'membresia_premium'
$cuotaId = intval($in['cuota_id'] ?? 0);

$pdo = getDB();

// Para el pago de una cuota de crédito, el monto SIEMPRE se toma de la base de datos
// (nunca de lo que mande el navegador) — así el socio no puede alterar cuánto paga.
$cuota = null;
if ($tipo === 'pago_credito') {
    if (!$cuotaId) {
        jsonResponse(['error' => 'Falta indicar qué cuota se va a pagar.'], 400);
    }
    $stmt = $pdo->prepare(
        "SELECT cc.* FROM credito_cuotas cc
         JOIN creditos c ON c.id = cc.credito_id
         WHERE cc.id = ? AND c.socio_id = ? AND cc.estado IN ('pendiente', 'vencida')"
    );
    $stmt->execute([$cuotaId, $socioId]);
    $cuota = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$cuota) {
        jsonResponse(['error' => 'Cuota no encontrada o ya pagada.'], 404);
    }
    $monto = floatval($cuota['monto_cuota']);
}

// Para la membresía Premium, el monto SIEMPRE sale de organizaciones.membresia_premium_costo
// (nunca del cliente) — mismo principio que 'pago_credito' de arriba.
if ($tipo === 'membresia_premium') {
    $stmt = $pdo->prepare(
        "SELECT o.membresia_premium_costo, s.nivel FROM socios s
         JOIN organizaciones o ON o.id = s.organizacion_id WHERE s.id = ?"
    );
    $stmt->execute([$socioId]);
    $membresiaInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($membresiaInfo['nivel'] === 'premium') {
        jsonResponse(['error' => 'Ya eres Cliente Premium.'], 400);
    }
    $monto = floatval($membresiaInfo['membresia_premium_costo']);
}

if ($monto <= 0) {
    jsonResponse(['error' => 'Monto inválido.'], 400);
}

$stmt = $pdo->prepare("SELECT s.*, o.payphone_store_id, o.payphone_token, o.nombre AS org_nombre,
                               o.comision_deposito_pct, o.iva_pct
                        FROM socios s JOIN organizaciones o ON o.id = s.organizacion_id
                        WHERE s.id = ?");
$stmt->execute([$socioId]);
$socio = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$socio['payphone_token']) {
    jsonResponse(['error' => 'Esta caja aún no ha conectado su cuenta de PayPhone.'], 400);
}

// Costo de transacción: SOLO se le suma al aporte recurrente ('aporte'), nunca al aporte
// inicial (ese es un monto fijo operativo, no un ahorro). El socio paga monto + comisión + IVA
// en PayPhone, pero se le acredita el monto COMPLETO que quiso ahorrar — la comisión y el IVA
// no salen de sus ahorros.
if ($tipo === 'aporte') {
    $comisionPct = floatval($socio['comision_deposito_pct']);
    $ivaPct = floatval($socio['iva_pct']);
    $comision = round($monto * $comisionPct / 100, 2);
    $iva = round($comision * $ivaPct / 100, 2);
} else {
    $comision = 0;
    $iva = 0;
}
$montoACobrar = $monto + $comision + $iva;

// clientTransactionId: PayPhone exige máximo 15 caracteres y que sea único
$clientTransactionId = substr(uniqid(), -12) . rand(10, 99);

$stmt = $pdo->prepare(
    "INSERT INTO transacciones (socio_id, tipo, monto, comision, iva, credito_cuota_id, payphone_client_transaction_id, estado)
     VALUES (?, ?, ?, ?, ?, ?, ?, 'pendiente')"
);
$stmt->execute([$socioId, $tipo, $monto, $comision, $iva, $cuota ? $cuotaId : null, $clientTransactionId]);

jsonResponse([
    'storeId' => $socio['payphone_store_id'] ?: null,   // null si la organización tiene una sola tienda
    'token' => $socio['payphone_token'],    // requerido por la Cajita de Pagos en el navegador
    'clientTransactionId' => $clientTransactionId,
    'amount' => round($montoACobrar * 100),      // lo que realmente se cobra en PayPhone, en centavos
    'amountWithoutTax' => round($montoACobrar * 100),
    'currency' => 'USD',
    'reference' => $socio['org_nombre'] . ' — ' . ucfirst(str_replace('_', ' ', $tipo)),
    'responseUrl' => SITE_URL . '/api/payphone_confirm.php',
    // Desglose informativo para que el frontend muestre "ahorras X + comisión Y = total Z"
    // antes de abrir el widget. El monto que se acredita al socio sigue siendo $monto (neto).
    'montoNeto' => $monto,
    'comision' => $comision,
    'iva' => $iva,
    'montoCobrado' => $montoACobrar,
]);

// Con estos datos, el frontend inicializa el widget "Cajita de Pagos" de PayPhone
// (PPaymentButtonBox, script oficial v2.0) pasándole token, clientTransactionId, amount,
// amountWithoutTax, currency y reference — y storeId SOLO si viene presente (ver nota arriba).
// Revisa la guía oficial (docs.payphone.app/cajita-de-pagos) si PayPhone actualiza su SDK.
