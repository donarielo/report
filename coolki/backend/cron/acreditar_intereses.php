<?php
// COOLKI · cron diario de intereses de plazo fijo.
//
// Configúralo en hPanel > Avanzado > Tareas Cron, para que corra UNA VEZ AL DÍA.
// Comando (ajusta la ruta a donde subiste el backend en tu hosting):
//   php /home/TU_USUARIO/domains/tudominio.com/public_html/backend/cron/acreditar_intereses.php
//
// Qué hace:
// 1. A cada plazo fijo vigente le acumula el interés diario (capital * tasa_anual / 100 / 365)
//    en la columna `generado`.
// 2. Si un plazo fijo llegó a su fecha_vencimiento, acredita todo lo `generado` como saldo
//    disponible del socio (transacción tipo 'interes'), pone `generado` en 0 y renueva el
//    plazo un año más manteniendo el mismo capital — así lo anuncia la app ("se renueva
//    automáticamente"). El socio no pierde acceso a su capital: si quiere retirarlo, hoy
//    eso se coordina directamente con el administrador de su caja (no hay botón en la app
//    para deshacer un plazo fijo antes de tiempo).
//
// Este script solo debe ejecutarse por línea de comandos (cron), nunca vía navegador.

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('Este script solo se ejecuta por cron (línea de comandos).');
}

require_once __DIR__ . '/../includes/db.php';

$pdo = getDB();

$stmt = $pdo->query("SELECT * FROM plazos_fijos");
$plazos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$hoy = date('Y-m-d');
$procesados = 0;
$renovados = 0;

foreach ($plazos as $plazo) {
    $interesDiario = round(($plazo['capital'] * $plazo['tasa_anual'] / 100) / 365, 2);
    $nuevoGenerado = round($plazo['generado'] + $interesDiario, 2);

    $pdo->beginTransaction();
    try {
        if ($plazo['fecha_vencimiento'] <= $hoy) {
            // Vencido: acreditar lo generado al saldo disponible del socio y renovar el plazo.
            $stmt = $pdo->prepare("UPDATE socios SET saldo_disponible = saldo_disponible + ? WHERE id = ?");
            $stmt->execute([$nuevoGenerado, $plazo['socio_id']]);

            $stmt = $pdo->prepare(
                "INSERT INTO transacciones (socio_id, tipo, monto, estado) VALUES (?, 'interes', ?, 'confirmado')"
            );
            $stmt->execute([$plazo['socio_id'], $nuevoGenerado]);

            $stmt = $pdo->prepare(
                "UPDATE plazos_fijos SET generado = 0, fecha_inicio = ?, fecha_vencimiento = DATE_ADD(?, INTERVAL 1 YEAR) WHERE id = ?"
            );
            $stmt->execute([$hoy, $hoy, $plazo['id']]);

            $renovados++;
        } else {
            $stmt = $pdo->prepare("UPDATE plazos_fijos SET generado = ? WHERE id = ?");
            $stmt->execute([$nuevoGenerado, $plazo['id']]);
        }
        $pdo->commit();
        $procesados++;
    } catch (Exception $e) {
        $pdo->rollBack();
        fwrite(STDERR, "Error en plazo fijo #{$plazo['id']}: " . $e->getMessage() . "\n");
    }
}

echo "Plazos procesados: $procesados. Renovados hoy: $renovados.\n";
