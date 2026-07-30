<?php
// Validaciones de formato ecuatoriano, compartidas por los endpoints que reciben datos
// personales directamente del socio (nunca confiar solo en la validación del navegador).

// Cédula ecuatoriana: 10 dígitos, código de provincia 01-24, tercer dígito 0-6 (persona
// natural), y dígito verificador por el algoritmo módulo 10 (coeficientes 2,1,2,1,2,1,2,1,2).
function validarCedulaEcuador(string $cedula): bool {
    if (!preg_match('/^\d{10}$/', $cedula)) {
        return false;
    }
    $d = array_map('intval', str_split($cedula));
    $provincia = $d[0] * 10 + $d[1];
    if ($provincia < 1 || $provincia > 24) {
        return false;
    }
    if ($d[2] > 6) {
        return false;
    }
    $coeficientes = [2, 1, 2, 1, 2, 1, 2, 1, 2];
    $suma = 0;
    for ($i = 0; $i < 9; $i++) {
        $v = $d[$i] * $coeficientes[$i];
        if ($v >= 10) {
            $v -= 9;
        }
        $suma += $v;
    }
    $dv = ((int) ceil($suma / 10) * 10) - $suma;
    if ($dv === 10) {
        $dv = 0;
    }
    return $dv === $d[9];
}

// Celular ecuatoriano: 10 dígitos, empieza en 09.
function validarCelularEcuador(string $celular): bool {
    return (bool) preg_match('/^09\d{8}$/', $celular);
}
