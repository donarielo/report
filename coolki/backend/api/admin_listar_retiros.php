<?php
require_once __DIR__ . '/../includes/auth.php';
$admin = requireAdminAuth();

$pdo = getDB();
$stmt = $pdo->prepare(
    "SELECT sr.id, sr.monto, sr.created_at, s.nombre AS socio_nombre
     FROM solicitudes_retiro sr JOIN socios s ON s.id = sr.socio_id
     WHERE s.organizacion_id = ? AND sr.estado = 'pendiente'
     ORDER BY sr.created_at ASC"
);
$stmt->execute([$admin['organizacion_id']]);

jsonResponse(['retiros' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
