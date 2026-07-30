<?php
// Cliente SMTP mínimo, escrito a mano (sin librerías externas, consistente con el resto de
// esta app) para enviar el correo de recuperación de contraseña. Habla el protocolo SMTP
// directo sobre un socket: EHLO, STARTTLS opcional, AUTH LOGIN, MAIL FROM/RCPT TO/DATA.
// Nunca expone el detalle crudo de un error al usuario final — solo error_log().

// Codifica un valor de cabecera (asunto, nombre) en MIME "encoded-word" si tiene caracteres
// no-ASCII (tildes, ñ), y SIEMPRE quita \r\n antes — nunca confiar en que un valor que viene
// de datos del usuario (ej. el nombre del socio) esté libre de saltos de línea, para evitar
// inyección de cabeceras.
function mimeEncodeWord(string $texto): string {
    $texto = str_replace(["\r", "\n"], '', $texto);
    if (preg_match('/^[\x20-\x7E]*$/', $texto)) {
        return $texto;
    }
    return '=?UTF-8?B?' . base64_encode($texto) . '?=';
}

function smtpLeerRespuesta($socket): string {
    $respuesta = '';
    while ($linea = fgets($socket, 515)) {
        $respuesta .= $linea;
        // La última línea de una respuesta (posiblemente multi-línea) trae un espacio
        // después del código de estado, no un guion (ej. "250 OK" vs "250-sigue...").
        if (isset($linea[3]) && $linea[3] === ' ') {
            break;
        }
    }
    return $respuesta;
}

function smtpComando($socket, string $comando, string $esperado): string {
    fwrite($socket, $comando . "\r\n");
    $respuesta = smtpLeerRespuesta($socket);
    if (strpos($respuesta, $esperado) !== 0) {
        throw new Exception('Respuesta SMTP inesperada a "' . trim($comando) . '": ' . trim($respuesta));
    }
    return $respuesta;
}

// Envía un correo HTML a un solo destinatario. Devuelve true/false — quien llama debe seguir
// mostrando su propio mensaje (ej. el genérico de recuperación de contraseña) sin importar
// el resultado real del envío.
function enviarCorreo(string $paraEmail, string $paraNombre, string $asunto, string $cuerpoHtml): bool {
    if (!filter_var($paraEmail, FILTER_VALIDATE_EMAIL)) {
        error_log('mailer: destinatario inválido: ' . $paraEmail);
        return false;
    }

    $socket = null;
    try {
        $contexto = stream_context_create(['ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
            'peer_name' => SMTP_HOST,
        ]]);

        $prefijo = SMTP_SECURE === 'ssl' ? 'ssl://' : 'tcp://';
        $socket = stream_socket_client(
            $prefijo . SMTP_HOST . ':' . SMTP_PORT,
            $errno, $errstr, 15,
            STREAM_CLIENT_CONNECT, $contexto
        );
        if (!$socket) {
            throw new Exception('No se pudo conectar al servidor SMTP: ' . $errstr);
        }
        stream_set_timeout($socket, 15);

        smtpLeerRespuesta($socket); // saludo inicial del servidor (220)
        $ehloHost = parse_url(SITE_URL, PHP_URL_HOST) ?: 'localhost';
        smtpComando($socket, 'EHLO ' . $ehloHost, '250');

        if (SMTP_SECURE === 'tls') {
            smtpComando($socket, 'STARTTLS', '220');
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new Exception('No se pudo negociar TLS con el servidor SMTP.');
            }
            smtpComando($socket, 'EHLO ' . $ehloHost, '250');
        }

        smtpComando($socket, 'AUTH LOGIN', '334');
        smtpComando($socket, base64_encode(SMTP_USER), '334');
        smtpComando($socket, base64_encode(SMTP_PASS), '235');

        smtpComando($socket, 'MAIL FROM:<' . SMTP_USER . '>', '250');
        smtpComando($socket, 'RCPT TO:<' . $paraEmail . '>', '250');
        smtpComando($socket, 'DATA', '354');

        $de = mimeEncodeWord(SMTP_FROM_NAME) . ' <' . SMTP_USER . '>';
        $para = mimeEncodeWord($paraNombre) . ' <' . $paraEmail . '>';

        $cabeceras =
            'Date: ' . date('r') . "\n" .
            'From: ' . $de . "\n" .
            'To: ' . $para . "\n" .
            'Subject: ' . mimeEncodeWord($asunto) . "\n" .
            'MIME-Version: 1.0' . "\n" .
            'Content-Type: text/html; charset=UTF-8' . "\n" .
            'Content-Transfer-Encoding: quoted-printable' . "\n";

        // quoted-printable: codificación segura en 7 bits, sin depender de que el servidor
        // soporte 8BITMIME. El "dot-stuffing" (una línea que empieza con "." se duplica el
        // punto) es obligatorio por el protocolo SMTP para no confundirla con el terminador
        // de DATA, y se aplica DESPUÉS de codificar, sobre el texto final que viaja por el cable.
        $cuerpoNormalizado = str_replace("\r\n", "\n", $cuerpoHtml);
        $cuerpoCodificado = quoted_printable_encode($cuerpoNormalizado);
        $cuerpoStuffed = preg_replace('/^\./m', '..', $cuerpoCodificado);

        $mensaje = $cabeceras . "\n" . $cuerpoStuffed . "\n";
        $mensaje = str_replace("\n", "\r\n", $mensaje);

        fwrite($socket, $mensaje . ".\r\n");
        $respuesta = smtpLeerRespuesta($socket);
        if (strpos($respuesta, '250') !== 0) {
            throw new Exception('El servidor SMTP rechazó el mensaje: ' . trim($respuesta));
        }

        fwrite($socket, "QUIT\r\n");
        fclose($socket);
        return true;
    } catch (Exception $e) {
        error_log('mailer: ' . $e->getMessage());
        if (is_resource($socket)) {
            fclose($socket);
        }
        return false;
    }
}

// Correo específico de recuperación de contraseña — usa siempre el nombre real de la
// organización (esta app es multi-organización, nunca se hardcodea "COOLKI").
function enviarCorreoRecuperacion(array $socio, array $org, string $resetUrl): bool {
    $nombreOrg = htmlspecialchars($org['nombre'], ENT_QUOTES, 'UTF-8');
    $nombreSocio = htmlspecialchars($socio['nombre'], ENT_QUOTES, 'UTF-8');
    $urlEscapada = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');

    $asunto = 'Recupera tu acceso a ' . $org['nombre'];
    $cuerpo = '<div style="font-family: Arial, sans-serif; max-width: 480px; margin: 0 auto; color: #16182B;">'
        . '<h2 style="color:#2454FF;">' . $nombreOrg . '</h2>'
        . '<p>Hola ' . $nombreSocio . ',</p>'
        . '<p>Recibimos una solicitud para restablecer la contraseña de tu cuenta en ' . $nombreOrg . '.</p>'
        . '<p style="text-align:center; margin: 28px 0;">'
        . '<a href="' . $urlEscapada . '" style="background:#2454FF; color:#fff; padding:14px 24px; border-radius:10px; text-decoration:none; font-weight:bold;">Restablecer mi contraseña</a>'
        . '</p>'
        . '<p style="font-size:12px; color:#6B6F85;">Si el botón no funciona, copia y pega este enlace en tu navegador:<br>' . $urlEscapada . '</p>'
        . '<p style="font-size:12px; color:#6B6F85;">Este enlace vence en 30 minutos. Si tú no solicitaste este cambio, puedes ignorar este correo — tu contraseña actual sigue siendo válida.</p>'
        . '</div>';

    return enviarCorreo($socio['email'], $socio['nombre'], $asunto, $cuerpo);
}
