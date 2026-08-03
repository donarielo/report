<?php
// Deriva un tono claro ("--brand-soft") a partir de un solo color de marca guardado,
// mezclando cada canal RGB hacia blanco. factor=0.90 reproduce la relación que ya
// existía entre #2454FF y #E8EEFF (el valor que estuvo hardcodeado hasta ahora).
function mezclarConBlanco($hex, $factor = 0.90) {
    $hex = ltrim($hex, '#');
    if (!preg_match('/^[0-9A-Fa-f]{6}$/', $hex)) {
        $hex = '2454FF';
    }
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));

    $mezclar = function ($canal) use ($factor) {
        return (int) round($canal + (255 - $canal) * $factor);
    };

    return sprintf('#%02X%02X%02X', $mezclar($r), $mezclar($g), $mezclar($b));
}
