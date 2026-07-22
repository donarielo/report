<?php
// COOLKI · Ejecuta este archivo UNA SOLA VEZ desde el navegador (ej. https://tudominio.com/setup_admin.php)
// para crear tu primer administrador con una contraseña bien encriptada.
// Después de usarlo, BÓRRALO del servidor por seguridad.

require_once __DIR__ . '/includes/db.php';

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $slugOrg = trim($_POST['slug'] ?? 'coolki');

    if ($email && strlen($password) >= 8) {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id FROM organizaciones WHERE slug = ?");
        $stmt->execute([$slugOrg]);
        $org = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($org) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO admins (organizacion_id, email, password_hash) VALUES (?, ?, ?)");
            $stmt->execute([$org['id'], $email, $hash]);
            $mensaje = "✅ Administrador creado para la organización '$slugOrg'. Ya puedes borrar este archivo.";
        } else {
            $mensaje = "❌ No existe una organización con ese slug. Revisa schema.sql.";
        }
    } else {
        $mensaje = "❌ Completa el correo y una contraseña de al menos 8 caracteres.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Crear administrador — COOLKI</title></head>
<body style="font-family: sans-serif; max-width: 420px; margin: 60px auto;">
  <h2>Crear tu primer administrador</h2>
  <?php if ($mensaje): ?><p><?= htmlspecialchars($mensaje) ?></p><?php endif; ?>
  <form method="POST">
    <p><label>Slug de la organización<br><input type="text" name="slug" value="coolki" required></label></p>
    <p><label>Correo<br><input type="email" name="email" required></label></p>
    <p><label>Contraseña<br><input type="password" name="password" required></label></p>
    <button type="submit">Crear administrador</button>
  </form>
</body>
</html>
