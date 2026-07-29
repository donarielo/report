<?php
// El socio actualiza sus propios datos (nombre, cédula, correo, celular) y,
// opcionalmente, su contraseña (exige la contraseña actual para cambiarla).
require_once __DIR__ . '/../includes/auth.php';
$socioId = requireSocioAuth();

$in = jsonInput();
$nombre = trim($in['nombre'] ?? '');
$cedula = trim($in['cedula'] ?? '');
$email = trim($in['email'] ?? '');
$celular = trim($in['celular'] ?? '');
$passwordActual = $in['password_actual'] ?? '';
$passwordNueva = $in['password_nueva'] ?? '';

// Datos adicionales de perfil: opcionales aquí (edición posterior). El primer llenado
// obligatorio ocurre en completar_perfil.php, que sí exige todos estos campos.
$ciudad = trim($in['ciudad'] ?? '') ?: null;
$provincia = trim($in['provincia'] ?? '') ?: null;
$direccion = trim($in['direccion'] ?? '') ?: null;
$tipoEmpleo = in_array($in['tipo_empleo'] ?? '', ['dependiente', 'independiente'], true) ? $in['tipo_empleo'] : null;
$ingresosMensuales = (isset($in['ingresos_mensuales']) && $in['ingresos_mensuales'] !== '') ? floatval($in['ingresos_mensuales']) : null;
$estadoCivilIn = $in['estado_civil'] ?? '';
$estadoCivil = in_array($estadoCivilIn, ['soltero', 'casado', 'divorciado', 'viudo', 'union_libre'], true) ? $estadoCivilIn : null;

if (!$nombre || !$cedula || !$email) {
    jsonResponse(['error' => 'Nombre, cédula y correo son obligatorios.'], 400);
}

$pdo = getDB();
$stmt = $pdo->prepare("SELECT * FROM socios WHERE id = ?");
$stmt->execute([$socioId]);
$socio = $stmt->fetch(PDO::FETCH_ASSOC);

if (strcasecmp($email, $socio['email']) !== 0) {
    $stmt = $pdo->prepare("SELECT id FROM socios WHERE organizacion_id = ? AND email = ? AND id != ?");
    $stmt->execute([$socio['organizacion_id'], $email, $socioId]);
    if ($stmt->fetch()) {
        jsonResponse(['error' => 'Ya existe una cuenta con ese correo en esta caja.'], 409);
    }
}

$setPassword = '';
$params = [$nombre, $cedula, $email, $celular, $ciudad, $provincia, $direccion, $tipoEmpleo, $ingresosMensuales, $estadoCivil];

if ($passwordNueva !== '') {
    if (strlen($passwordNueva) < 8) {
        jsonResponse(['error' => 'La nueva contraseña debe tener al menos 8 caracteres.'], 400);
    }
    if (!password_verify($passwordActual, $socio['password_hash'])) {
        jsonResponse(['error' => 'La contraseña actual no es correcta.'], 401);
    }
    $setPassword = ', password_hash = ?';
    $params[] = password_hash($passwordNueva, PASSWORD_DEFAULT);
}
$params[] = $socioId;

$stmt = $pdo->prepare(
    "UPDATE socios SET nombre = ?, cedula = ?, email = ?, celular = ?,
            ciudad = ?, provincia = ?, direccion = ?, tipo_empleo = ?, ingresos_mensuales = ?, estado_civil = ?"
    . $setPassword . " WHERE id = ?"
);
$stmt->execute($params);

jsonResponse(['ok' => true]);
