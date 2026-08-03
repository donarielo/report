<?php
// Reemplaza por completo la lista de pasos de "Cómo funciona" (se borra y se vuelve a
// insertar en el orden recibido) — más simple y sin margen de inconsistencia que ir
// calculando altas/bajas/reordenamientos fila por fila.
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/paginas_config.php';
$admin = requireAdminAuth();

$in = jsonInput();
$pasos = is_array($in['pasos'] ?? null) ? $in['pasos'] : [];

if (count($pasos) > 8) {
    jsonResponse(['error' => 'No puedes tener más de 8 pasos.'], 400);
}

$limpios = [];
foreach ($pasos as $p) {
    $icono = trim($p['icono'] ?? '');
    $titulo = trim($p['titulo'] ?? '');
    $descripcion = trim($p['descripcion'] ?? '');
    $visible = !empty($p['visible']);

    if (!in_array($icono, ICONOS_PASOS, true)) {
        jsonResponse(['error' => 'Ícono de paso no válido.'], 400);
    }
    if ($titulo === '' || mb_strlen($titulo) > 100) {
        jsonResponse(['error' => 'Cada paso necesita un título (máx. 100 caracteres).'], 400);
    }
    if ($descripcion === '' || mb_strlen($descripcion) > 280) {
        jsonResponse(['error' => 'Cada paso necesita una descripción (máx. 280 caracteres).'], 400);
    }
    $limpios[] = ['icono' => $icono, 'titulo' => $titulo, 'descripcion' => $descripcion, 'visible' => $visible];
}

$pdo = getDB();
$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("DELETE FROM pagina_pasos WHERE organizacion_id = ? AND pagina = 'index'");
    $stmt->execute([$admin['organizacion_id']]);

    $stmt = $pdo->prepare(
        "INSERT INTO pagina_pasos (organizacion_id, pagina, orden, icono, titulo, descripcion, visible)
         VALUES (?, 'index', ?, ?, ?, ?, ?)"
    );
    foreach ($limpios as $i => $p) {
        $stmt->execute([$admin['organizacion_id'], $i, $p['icono'], $p['titulo'], $p['descripcion'], $p['visible'] ? 1 : 0]);
    }
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    jsonResponse(['error' => 'No se pudieron guardar los pasos.'], 500);
}

jsonResponse(['ok' => true]);
