<?php
// Confirma un token de recuperación (de un solo uso, con vencimiento) y fija la nueva
// contraseña. No inicia sesión automáticamente — el socio se loguea normal después.
require_once __DIR__ . '/../includes/auth.php';

$in = jsonInput();
$tokenCrudo = trim($in['token'] ?? '');
$password = $in['password'] ?? '';

if (!$tokenCrudo || strlen($password) < 8) {
    jsonResponse(['error' => 'Completa el formulario. La contraseña debe tener al menos 8 caracteres.'], 400);
}

$pdo = getDB();
$stmt = $pdo->prepare(
    "SELECT * FROM password_resets WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW()"
);
$stmt->execute([hash('sha256', $tokenCrudo)]);
$reset = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reset) {
    jsonResponse(['error' => 'Este enlace no es válido o ya expiró. Solicita uno nuevo.'], 400);
}

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare(
        "UPDATE socios SET password_hash = ?, intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id = ?"
    );
    $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $reset['socio_id']]);

    $stmt = $pdo->prepare("UPDATE password_resets SET used_at = NOW() WHERE socio_id = ? AND used_at IS NULL");
    $stmt->execute([$reset['socio_id']]);

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    jsonResponse(['error' => 'No se pudo restablecer la contraseña.'], 500);
}

jsonResponse(['ok' => true]);
