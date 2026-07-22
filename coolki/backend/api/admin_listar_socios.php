<?php
require_once __DIR__ . '/../includes/auth.php';
$admin = requireAdminAuth();

$pdo = getDB();
$stmt = $pdo->prepare(
    "SELECT id, nombre, email, saldo_disponible, saldo_congelado, estado, created_at
     FROM socios WHERE organizacion_id = ? ORDER BY created_at DESC"
);
$stmt->execute([$admin['organizacion_id']]);

jsonResponse(['socios' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
