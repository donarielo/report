<?php
require_once __DIR__ . '/db.php';

function iniciarSesion() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function requireSocioAuth() {
    iniciarSesion();
    if (empty($_SESSION['socio_id'])) {
        jsonResponse(['error' => 'No autenticado'], 401);
    }
    return $_SESSION['socio_id'];
}

function requireAdminAuth() {
    iniciarSesion();
    if (empty($_SESSION['admin_id'])) {
        jsonResponse(['error' => 'No autenticado'], 401);
    }
    return ['admin_id' => $_SESSION['admin_id'], 'organizacion_id' => $_SESSION['organizacion_id']];
}

function getOrganizacionPorSlug($slug) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM organizaciones WHERE slug = ?");
    $stmt->execute([$slug]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
