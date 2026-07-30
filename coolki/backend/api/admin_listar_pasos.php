<?php
// Lista los pasos de "Cómo funciona" de la portada, en el orden guardado. Si el admin
// todavía no ha guardado ninguno, devuelve los 3 por defecto (sin persistirlos todavía).
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/paginas_config.php';
$admin = requireAdminAuth();

$pdo = getDB();
$stmt = $pdo->prepare("SELECT * FROM pagina_pasos WHERE organizacion_id = ? AND pagina = 'index' ORDER BY orden ASC, id ASC");
$stmt->execute([$admin['organizacion_id']]);
$filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$filas) {
    $pasos = array_map(function ($p) {
        $p['visible'] = true;
        return $p;
    }, pasosPorDefecto());
} else {
    $pasos = array_map(function ($f) {
        return [
            'id' => (int) $f['id'],
            'icono' => $f['icono'],
            'titulo' => $f['titulo'],
            'descripcion' => $f['descripcion'],
            'visible' => (bool) $f['visible'],
        ];
    }, $filas);
}

jsonResponse(['ok' => true, 'pasos' => $pasos]);
