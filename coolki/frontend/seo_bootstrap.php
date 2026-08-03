<?php
// Bootstrap común de index.php y landing-empresas.php: resuelve dónde vive backend/ sin
// importar la disposición de carpetas (ver README §4) — en el repo, frontend/ y backend/
// son carpetas hermanas; en producción, backend/ es una subcarpeta de este mismo docroot.
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'seo_bootstrap.php') {
    http_response_code(403);
    exit('Acceso no permitido.');
}

$backendDir = is_dir(__DIR__ . '/backend') ? __DIR__ . '/backend' : dirname(__DIR__) . '/backend';
require_once $backendDir . '/includes/auth.php';
require_once $backendDir . '/includes/color.php';
require_once $backendDir . '/includes/paginas_config.php';
require_once $backendDir . '/includes/seo.php';
