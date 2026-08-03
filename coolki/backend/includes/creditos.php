<?php
// Funciones compartidas para el módulo de créditos.

// Cuota mensual fija (sistema francés / amortización estándar), a partir de una tasa
// EFECTIVA ANUAL (la que publica el Banco Central para microcrédito). Se convierte a una
// tasa mensual equivalente por composición: (1+TEA)^(1/12) - 1 — no es TEA/12 simple.
function calcularCuotaMensual($monto, $tasaAnualPct, $plazoMeses) {
    $tasaAnual = floatval($tasaAnualPct) / 100;
    $plazoMeses = max(1, intval($plazoMeses));
    $tasaMensual = pow(1 + $tasaAnual, 1 / 12) - 1;

    if ($tasaMensual <= 0) {
        return round($monto / $plazoMeses, 2);
    }

    $factor = pow(1 + $tasaMensual, $plazoMeses);
    $cuota = $monto * ($tasaMensual * $factor) / ($factor - 1);
    return round($cuota, 2);
}

// Cupo sugerido: $100 base + $50 por cada crédito que el socio pagó por completo y sin
// ningún atraso (nunca marcado en_mora), hasta el tope configurado. Es solo informativo
// para guiar al administrador — no bloquea la solicitud del socio ni la decisión del admin.
function calcularCupoSugerido($pdo, $socioId, $org) {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM creditos WHERE socio_id = ? AND estado = 'pagado' AND en_mora = 0"
    );
    $stmt->execute([$socioId]);
    $pagadosATiempo = (int) $stmt->fetchColumn();

    $cupo = floatval($org['credito_limite_base']) + $pagadosATiempo * floatval($org['credito_incremento_por_pago']);
    return round(min($cupo, floatval($org['credito_limite_maximo'])), 2);
}
