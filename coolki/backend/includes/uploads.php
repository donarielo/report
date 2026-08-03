<?php
// Resuelve la carpeta real de subida de fotos de testimonios y borra archivos de forma
// segura. Necesario porque este código corre en dos disposiciones de carpetas distintas:
// en el repo, frontend/ y backend/ son hermanas; en producción (ver README §4), backend/
// va como subcarpeta del docroot donde vive el frontend (sin una carpeta "frontend/" aparte).
function directorioUploadsTestimonios() {
    $backendDir = dirname(__DIR__); // .../backend
    $repoFrontend = dirname($backendDir) . '/frontend';
    $raizFrontend = is_dir($repoFrontend) ? $repoFrontend : dirname($backendDir);

    $dir = $raizFrontend . '/uploads/testimonios';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir;
}

// A partir de la URL pública guardada (ej. "/uploads/testimonios/ab12....jpg"), borra el
// archivo del disco. Nunca confía a ciegas en el valor guardado: solo toma el nombre del
// archivo (descarta cualquier carpeta) y verifica con realpath() que la ruta resuelta sigue
// dentro de la carpeta de subidas antes de borrar.
function eliminarArchivoTestimonio($fotoUrl) {
    if (!$fotoUrl) {
        return;
    }
    $nombre = basename(parse_url($fotoUrl, PHP_URL_PATH) ?: '');
    if ($nombre === '' || $nombre === '.' || $nombre === '..') {
        return;
    }
    $dir = directorioUploadsTestimonios();
    $dirReal = realpath($dir);
    $ruta = realpath($dir . '/' . $nombre);
    if ($dirReal && $ruta && strpos($ruta, $dirReal) === 0 && is_file($ruta)) {
        @unlink($ruta);
    }
}
