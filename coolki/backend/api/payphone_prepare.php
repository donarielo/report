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
$tipo = $in['tipo'] ?? 'aporte'; // 'aporte_inicial' o 'aporte'

if ($monto <= 0) {
    jsonResponse(['error' => 'Monto inválido.'], 400);
}

$pdo = getDB();
$stmt = $pdo->prepare("SELECT s.*, o.payphone_store_id, o.payphone_token, o.nombre AS org_nombre
                        FROM socios s JOIN organizaciones o ON o.id = s.organizacion_id
                        WHERE s.id = ?");
$stmt->execute([$socioId]);
$socio = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$socio['payphone_token']) {
    jsonResponse(['error' => 'Esta caja aún no ha conectado su cuenta de PayPhone.'], 400);
}

// clientTransactionId: PayPhone exige máximo 15 caracteres y que sea único
$clientTransactionId = substr(uniqid(), -12) . rand(10, 99);

$stmt = $pdo->prepare(
    "INSERT INTO transacciones (socio_id, tipo, monto, payphone_client_transaction_id, estado)
     VALUES (?, ?, ?, ?, 'pendiente')"
);
$stmt->execute([$socioId, $tipo, $monto, $clientTransactionId]);

jsonResponse([
    'storeId' => $socio['payphone_store_id'] ?: null,   // null si la organización tiene una sola tienda
    'token' => $socio['payphone_token'],    // requerido por la Cajita de Pagos en el navegador
    'clientTransactionId' => $clientTransactionId,
    'amount' => round($monto * 100),        // PayPhone recibe montos en centavos
    'amountWithoutTax' => round($monto * 100),
    'currency' => 'USD',
    'reference' => $socio['org_nombre'] . ' — ' . ucfirst(str_replace('_', ' ', $tipo)),
    'responseUrl' => SITE_URL . '/api/payphone_confirm.php',
]);

// Con estos datos, el frontend inicializa el widget "Cajita de Pagos" de PayPhone
// (PPaymentButtonBox, script oficial v2.0) pasándole token, clientTransactionId, amount,
// amountWithoutTax, currency y reference — y storeId SOLO si viene presente (ver nota arriba).
// Revisa la guía oficial (docs.payphone.app/cajita-de-pagos) si PayPhone actualiza su SDK.
