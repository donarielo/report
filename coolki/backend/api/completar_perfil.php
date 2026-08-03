<?php
// Formulario obligatorio de una sola vez, mostrado justo después de que la cuenta pasa a
// 'activo' (aporte inicial confirmado). Bloquea el resto del dashboard hasta completarse —
// distinto de actualizar_perfil.php (edición posterior, opcional, en "Mi perfil").
require_once __DIR__ . '/../includes/auth.php';
$socioId = requireSocioAuth();

$in = jsonInput();
$ciudad = trim($in['ciudad'] ?? '');
$provincia = trim($in['provincia'] ?? '');
$direccion = trim($in['direccion'] ?? '');
$tipoEmpleo = $in['tipo_empleo'] ?? '';
$ingresosMensuales = floatval($in['ingresos_mensuales'] ?? 0);
$estadoCivil = $in['estado_civil'] ?? '';

if (!$ciudad || !$provincia || !$direccion) {
    jsonResponse(['error' => 'Ciudad, provincia y dirección son obligatorios.'], 400);
}
if (!in_array($tipoEmpleo, ['dependiente', 'independiente'], true)) {
    jsonResponse(['error' => 'Selecciona un tipo de empleo válido.'], 400);
}
if (!in_array($estadoCivil, ['soltero', 'casado', 'divorciado', 'viudo', 'union_libre'], true)) {
    jsonResponse(['error' => 'Selecciona un estado civil válido.'], 400);
}
if ($ingresosMensuales <= 0) {
    jsonResponse(['error' => 'Ingresa un ingreso mensual válido.'], 400);
}

$pdo = getDB();
$stmt = $pdo->prepare(
    "UPDATE socios SET ciudad = ?, provincia = ?, direccion = ?, tipo_empleo = ?,
            ingresos_mensuales = ?, estado_civil = ?, perfil_completo = 1
     WHERE id = ?"
);
$stmt->execute([$ciudad, $provincia, $direccion, $tipoEmpleo, $ingresosMensuales, $estadoCivil, $socioId]);

jsonResponse(['ok' => true]);
