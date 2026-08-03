<?php
// Historial de todas las transacciones (aportes, retiros, intereses, comisiones de
// mantenimiento) de todos los socios de la organización. Para auditoría del administrador.
require_once __DIR__ . '/../includes/auth.php';
$admin = requireAdminAuth();

$pdo = getDB();
$stmt = $pdo->prepare(
    "SELECT t.id, t.tipo, t.monto, t.comision, t.iva, t.estado, t.created_at, s.nombre AS socio_nombre
     FROM transacciones t JOIN socios s ON s.id = t.socio_id
     WHERE s.organizacion_id = ? AND t.estado = 'confirmado'
     ORDER BY t.created_at DESC LIMIT 200"
);
$stmt->execute([$admin['organizacion_id']]);

jsonResponse(['transacciones' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
