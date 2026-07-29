<?php
// Restaura la sesión del administrador al recargar la página (sin pedir login de nuevo).
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/color.php';
$admin = requireAdminAuth();

$pdo = getDB();
$stmt = $pdo->prepare("SELECT email FROM admins WHERE id = ?");
$stmt->execute([$admin['admin_id']]);
$adminRow = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM organizaciones WHERE id = ?");
$stmt->execute([$admin['organizacion_id']]);
$org = $stmt->fetch(PDO::FETCH_ASSOC);

jsonResponse([
    'ok' => true,
    'admin' => ['email' => $adminRow['email'] ?? ''],
    'organizacion' => [
        'nombre' => $org['nombre'],
        'slug' => $org['slug'],
        'color_marca' => $org['color_marca'],
        'brand_soft' => mezclarConBlanco($org['color_marca']),
        'aporte_inicial' => $org['aporte_inicial'],
        'retiro_minimo' => $org['retiro_minimo'],
        'tasa_plazo_fijo' => $org['tasa_plazo_fijo'],
        'coolcoin_tasa_anual' => $org['coolcoin_tasa_anual'],
        'comision_deposito_pct' => $org['comision_deposito_pct'],
        'iva_pct' => $org['iva_pct'],
        'credito_tasa_anual' => $org['credito_tasa_anual'],
        'credito_limite_base' => $org['credito_limite_base'],
        'credito_incremento_por_pago' => $org['credito_incremento_por_pago'],
        'credito_limite_maximo' => $org['credito_limite_maximo'],
        'credito_plazo_min_meses' => $org['credito_plazo_min_meses'],
        'credito_plazo_max_meses' => $org['credito_plazo_max_meses'],
        'membresia_premium_costo' => $org['membresia_premium_costo'],
        'aprobacion_retiros' => $org['aprobacion_retiros'],
        'payphone_store_id' => $org['payphone_store_id'],
        'payphone_conectado' => !empty($org['payphone_token']),
    ],
]);
