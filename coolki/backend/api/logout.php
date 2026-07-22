<?php
// Cierra la sesión, sea de socio o de administrador.
require_once __DIR__ . '/../includes/auth.php';
iniciarSesion();

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();

jsonResponse(['ok' => true]);
