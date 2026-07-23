<?php
// COOLKI · cron diario de mora en créditos.
//
// Configúralo en hPanel > Avanzado > Tareas Cron, para que corra UNA VEZ AL DÍA (igual que
// acreditar_intereses.php). Comando (ajusta la ruta a donde subiste el backend):
//   php /home/TU_USUARIO/domains/tudominio.com/public_html/backend/cron/marcar_mora.php
//
// Qué hace: busca cuotas de crédito vencidas (fecha_vencimiento ya pasó) que sigan
// 'pendiente', las marca 'vencida', y marca el crédito completo como en_mora = 1.
// en_mora queda así para siempre en ese crédito (aunque la cuota se pague después) — se usa
// para calcular el cupo sugerido del socio (solo cuentan los créditos pagados SIN mora).
//
// Este script solo debe ejecutarse por línea de comandos (cron), nunca abriéndolo desde
// el navegador.

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('Este script solo se ejecuta por cron (línea de comandos).');
}

require_once __DIR__ . '/../includes/db.php';

$pdo = getDB();
$hoy = date('Y-m-d');

$stmt = $pdo->prepare(
    "SELECT id, credito_id FROM credito_cuotas WHERE estado = 'pendiente' AND fecha_vencimiento < ?"
);
$stmt->execute([$hoy]);
$vencidas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$marcadas = 0;
foreach ($vencidas as $cuota) {
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("UPDATE credito_cuotas SET estado = 'vencida' WHERE id = ?");
        $stmt->execute([$cuota['id']]);

        $stmt = $pdo->prepare("UPDATE creditos SET en_mora = 1 WHERE id = ?");
        $stmt->execute([$cuota['credito_id']]);

        $pdo->commit();
        $marcadas++;
    } catch (Exception $e) {
        $pdo->rollBack();
        fwrite(STDERR, "Error en cuota #{$cuota['id']}: " . $e->getMessage() . "\n");
    }
}

echo "Cuotas marcadas en mora: $marcadas.\n";
