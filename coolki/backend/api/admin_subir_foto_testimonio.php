<?php
// Única función de esta app con subida real de archivos (excepción puntual para fotos de
// testimonios, ver README §19). Se trata el archivo como si viniera de un origen no confiable
// aunque quien sube sea un administrador autenticado: tamaño medido por el servidor, tipo real
// verificado dos veces de forma independiente, nombre siempre aleatorio, y guardado con
// move_uploaded_file() en una carpeta que tiene su propio .htaccess sin ejecución de PHP.
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/uploads.php';
$admin = requireAdminAuth();

const TAMANO_MAXIMO_BYTES = 3 * 1024 * 1024; // 3 MB
const TIPOS_PERMITIDOS = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];
const DIMENSION_MINIMA = 40;
const DIMENSION_MAXIMA = 6000;

if (empty($_FILES['foto']) || !is_uploaded_file($_FILES['foto']['tmp_name'] ?? '')) {
    jsonResponse(['error' => 'No se recibió ningún archivo.'], 400);
}

$archivo = $_FILES['foto'];
if ($archivo['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(['error' => 'Hubo un problema al subir el archivo.'], 400);
}

$tamano = filesize($archivo['tmp_name']);
if ($tamano === false || $tamano <= 0 || $tamano > TAMANO_MAXIMO_BYTES) {
    jsonResponse(['error' => 'La imagen no puede pesar más de 3 MB.'], 400);
}

// Primera verificación de tipo real: se sniffea el contenido, nunca se confía en la
// extensión ni en el Content-Type que manda el navegador.
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeReal = finfo_file($finfo, $archivo['tmp_name']);
finfo_close($finfo);

if (!isset(TIPOS_PERMITIDOS[$mimeReal])) {
    jsonResponse(['error' => 'Solo se permiten imágenes JPG, PNG o WEBP.'], 400);
}

// Segunda verificación, independiente de finfo: getimagesize() debe reconocer el mismo
// tipo (esto descarta archivos "polyglot" que intentan pasar código como si fuera imagen).
$info = @getimagesize($archivo['tmp_name']);
if ($info === false || $info['mime'] !== $mimeReal) {
    jsonResponse(['error' => 'El archivo no es una imagen válida.'], 400);
}
[$ancho, $alto] = $info;
if ($ancho < DIMENSION_MINIMA || $alto < DIMENSION_MINIMA || $ancho > DIMENSION_MAXIMA || $alto > DIMENSION_MAXIMA) {
    jsonResponse(['error' => 'Las dimensiones de la imagen no son válidas.'], 400);
}

$extension = TIPOS_PERMITIDOS[$mimeReal];
$nombreArchivo = bin2hex(random_bytes(16)) . '.' . $extension;
$dir = directorioUploadsTestimonios();

if (!move_uploaded_file($archivo['tmp_name'], $dir . '/' . $nombreArchivo)) {
    jsonResponse(['error' => 'No se pudo guardar la imagen.'], 500);
}

jsonResponse(['ok' => true, 'foto_url' => '/uploads/testimonios/' . $nombreArchivo]);
