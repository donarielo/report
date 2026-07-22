<?php
require_once __DIR__ . '/../includes/auth.php';
$admin = requireAdminAuth();

$pdo = getDB();
$stmt = $pdo->prepare(
    "SELECT s.id, s.nombre, s.email, s.saldo_disponible, s.saldo_congelado, s.estado, s.created_at,
            COALESCE((SELECT SUM(p.capital) FROM plazos_fijos p WHERE p.socio_id = s.id), 0) AS plazo_fijo_total,
            (SELECT MAX(t.created_at) FROM transacciones t
             WHERE t.socio_id = s.id AND t.tipo IN ('aporte','aporte_inicial') AND t.estado = 'confirmado') AS ultimo_aporte
     FROM socios s
     WHERE s.organizacion_id = ? ORDER BY s.created_at DESC"
);
$stmt->execute([$admin['organizacion_id']]);

jsonResponse(['socios' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
