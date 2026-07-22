<?php
// COOLKI · configuración
// Reemplaza estos 4 valores con los datos de tu base de datos MySQL de Hostinger
// (los encuentras en hPanel > Bases de datos > MySQL)

define('DB_HOST', 'localhost');           // en Hostinger casi siempre es 'localhost'
define('DB_NAME', 'u000000000_coolki');   // el nombre de tu base de datos
define('DB_USER', 'u000000000_admin');    // tu usuario de MySQL
define('DB_PASS', 'TU_CONTRASENA_AQUI');  // tu contraseña de MySQL

// URL base de tu sitio (sin barra final) — se usa para armar los links que le compartes a tus socios
define('SITE_URL', 'https://coolkiecuador.com');

// Zona horaria de Ecuador, para que las fechas de vencimiento y reportes salgan correctas
date_default_timezone_set('America/Guayaquil');
