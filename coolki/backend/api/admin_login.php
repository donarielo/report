<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/color.php';
iniciarSesion();

$in = jsonInput();
$slug = trim($in['organizacion'] ?? '');
$email = trim($in['email'] ?? '');
$password = $in['password'] ?? '';

$org = getOrganizacionPorSlug($slug);
if (!$org) {
    jsonResponse(['error' => 'Organización no encontrada.'], 404);
}

$pdo = getDB();
$stmt = $pdo->prepare("SELECT * FROM admins WHERE organizacion_id = ? AND email = ?");
$stmt->execute([$org['id'], $email]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if ($admin && $admin['bloqueado_hasta'] && strtotime($admin['bloqueado_hasta']) > time()) {
    jsonResponse(['error' => 'Demasiados intentos fallidos. Tu cuenta quedó bloqueada temporalmente; intenta de nuevo en unos minutos.'], 429);
}

if (!$admin || !password_verify($password, $admin['password_hash'])) {
    if ($admin) {
        $intentos = intval($admin['intentos_fallidos']) + 1;
        if ($intentos >= LOGIN_INTENTOS_MAXIMOS) {
            $stmt = $pdo->prepare("UPDATE admins SET intentos_fallidos = 0, bloqueado_hasta = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE id = ?");
            $stmt->execute([LOGIN_BLOQUEO_MINUTOS, $admin['id']]);
        } else {
            $stmt = $pdo->prepare("UPDATE admins SET intentos_fallidos = ? WHERE id = ?");
            $stmt->execute([$intentos, $admin['id']]);
        }
    }
    jsonResponse(['error' => 'Correo o contraseña incorrectos.'], 401);
}

$stmt = $pdo->prepare("UPDATE admins SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id = ?");
$stmt->execute([$admin['id']]);

$_SESSION['admin_id'] = $admin['id'];
$_SESSION['organizacion_id'] = $org['id'];

jsonResponse([
    'ok' => true,
    'admin' => ['id' => $admin['id'], 'email' => $admin['email']],
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
        'soporte_email' => $org['soporte_email'],
        'soporte_whatsapp' => $org['soporte_whatsapp'],
        'aprobacion_retiros' => $org['aprobacion_retiros'],
        'payphone_store_id' => $org['payphone_store_id'],
        'payphone_conectado' => !empty($org['payphone_token']),
    ],
]);
