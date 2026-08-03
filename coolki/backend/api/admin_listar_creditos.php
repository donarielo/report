<?php
// Cola de solicitudes de crédito para el administrador, con el contexto del socio
// (saldo, antigüedad de la cuenta, cupo sugerido) para que decida manualmente.
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/creditos.php';
$admin = requireAdminAuth();

$pdo = getDB();

$stmt = $pdo->prepare("SELECT * FROM organizaciones WHERE id = ?");
$stmt->execute([$admin['organizacion_id']]);
$org = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare(
    "SELECT c.id, c.socio_id, c.monto, c.plazo_meses, c.tasa_anual, c.cuota_mensual, c.estado,
            c.en_mora, c.mensaje_admin, c.created_at, c.resuelto_at,
            s.nombre AS socio_nombre, s.saldo_disponible, s.saldo_congelado, s.created_at AS socio_desde
     FROM creditos c JOIN socios s ON s.id = c.socio_id
     WHERE s.organizacion_id = ?
     ORDER BY (c.estado = 'pendiente') DESC, c.created_at DESC"
);
$stmt->execute([$admin['organizacion_id']]);
$creditos = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($creditos as &$c) {
    $c['cupo_sugerido'] = calcularCupoSugerido($pdo, $c['socio_id'], $org);
}

jsonResponse(['creditos' => $creditos]);
