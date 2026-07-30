<?php
require_once __DIR__ . '/../includes/auth.php';
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
$stmt = $pdo->prepare("SELECT * FROM socios WHERE organizacion_id = ? AND email = ?");
$stmt->execute([$org['id'], $email]);
$socio = $stmt->fetch(PDO::FETCH_ASSOC);

if ($socio && $socio['bloqueado_hasta'] && strtotime($socio['bloqueado_hasta']) > time()) {
    jsonResponse(['error' => 'Demasiados intentos fallidos. Tu cuenta quedó bloqueada temporalmente; intenta de nuevo en unos minutos.'], 429);
}

if (!$socio || !password_verify($password, $socio['password_hash'])) {
    if ($socio) {
        $intentos = intval($socio['intentos_fallidos']) + 1;
        if ($intentos >= LOGIN_INTENTOS_MAXIMOS) {
            $stmt = $pdo->prepare("UPDATE socios SET intentos_fallidos = 0, bloqueado_hasta = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE id = ?");
            $stmt->execute([LOGIN_BLOQUEO_MINUTOS, $socio['id']]);
        } else {
            $stmt = $pdo->prepare("UPDATE socios SET intentos_fallidos = ? WHERE id = ?");
            $stmt->execute([$intentos, $socio['id']]);
        }
    }
    jsonResponse(['error' => 'Correo o contraseña incorrectos.'], 401);
}

$stmt = $pdo->prepare("UPDATE socios SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id = ?");
$stmt->execute([$socio['id']]);

$_SESSION['socio_id'] = $socio['id'];
$_SESSION['organizacion_id'] = $org['id'];

unset($socio['password_hash'], $socio['intentos_fallidos'], $socio['bloqueado_hasta']);
jsonResponse(['ok' => true, 'socio' => $socio]);
