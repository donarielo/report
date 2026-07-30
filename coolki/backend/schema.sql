-- COOLKI · esquema de base de datos (MySQL / MariaDB — compatible con Hostinger)
-- Importar esto desde phpMyAdmin en tu hPanel, sobre la base de datos que crees ahí.

CREATE TABLE organizaciones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(150) NOT NULL,
  slug VARCHAR(80) NOT NULL UNIQUE,
  color_marca VARCHAR(10) DEFAULT '#2454FF',
  logo_url VARCHAR(255) NULL,
  payphone_store_id VARCHAR(100) NULL,
  payphone_token TEXT NULL,               -- token secreto: solo se usa server-side, nunca se envía al frontend
  aporte_inicial DECIMAL(10,2) DEFAULT 5.00,
  retiro_minimo DECIMAL(10,2) DEFAULT 10.00,
  tasa_plazo_fijo DECIMAL(5,2) DEFAULT 5.00,
  coolcoin_tasa_anual DECIMAL(5,2) DEFAULT 5.50,     -- tasa anual de CoolCoin (segunda opción de inversión, igual mecanismo que el plazo fijo)
  aprobacion_retiros ENUM('manual','automatica') DEFAULT 'manual',
  comision_deposito_pct DECIMAL(5,2) DEFAULT 5.00,  -- % de comisión sobre depósitos (no aplica al aporte inicial)
  iva_pct DECIMAL(5,2) DEFAULT 15.00,               -- % de IVA, calculado sobre la comisión
  credito_tasa_anual DECIMAL(5,2) DEFAULT 28.23,        -- tasa efectiva anual (microcrédito minorista, máximo BCE)
  credito_limite_base DECIMAL(10,2) DEFAULT 100.00,     -- cupo sugerido para el primer crédito de un socio
  credito_incremento_por_pago DECIMAL(10,2) DEFAULT 50.00, -- + esto por cada crédito pagado a tiempo (sin mora)
  credito_limite_maximo DECIMAL(10,2) DEFAULT 500.00,   -- tope del cupo sugerido (el admin puede aprobar más igual)
  credito_plazo_min_meses INT DEFAULT 1,
  credito_plazo_max_meses INT DEFAULT 6,
  membresia_premium_costo DECIMAL(10,2) DEFAULT 20.00, -- pago único para pasar a Cliente Premium (requisito para pedir crédito)
  soporte_email VARCHAR(150) NULL,
  soporte_whatsapp VARCHAR(255) NULL, -- link completo, ej. https://wa.me/593999999999
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  organizacion_id INT NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  intentos_fallidos INT NOT NULL DEFAULT 0,
  bloqueado_hasta TIMESTAMP NULL,
  FOREIGN KEY (organizacion_id) REFERENCES organizaciones(id)
);

CREATE TABLE socios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  organizacion_id INT NOT NULL,
  nombre VARCHAR(150) NOT NULL,
  cedula VARCHAR(20) NOT NULL,
  email VARCHAR(150) NOT NULL,
  celular VARCHAR(20) NULL,
  ciudad VARCHAR(100) NULL,
  provincia VARCHAR(100) NULL,
  direccion VARCHAR(255) NULL,
  tipo_empleo ENUM('dependiente','independiente') NULL,
  ingresos_mensuales DECIMAL(10,2) NULL,
  estado_civil ENUM('soltero','casado','divorciado','viudo','union_libre') NULL,
  password_hash VARCHAR(255) NOT NULL,
  saldo_disponible DECIMAL(10,2) DEFAULT 0.00,
  saldo_congelado DECIMAL(10,2) DEFAULT 0.00,
  estado ENUM('pendiente_pago','activo') DEFAULT 'pendiente_pago',
  nivel ENUM('normal','premium') NOT NULL DEFAULT 'normal', -- 'premium' puede solicitar microcréditos
  perfil_completo TINYINT(1) NOT NULL DEFAULT 0, -- formulario obligatorio de datos adicionales, una vez que estado='activo'
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  terminos_aceptados_at TIMESTAMP NULL,
  intentos_fallidos INT NOT NULL DEFAULT 0,
  bloqueado_hasta TIMESTAMP NULL,
  UNIQUE KEY unico_por_org (organizacion_id, email),
  FOREIGN KEY (organizacion_id) REFERENCES organizaciones(id)
);

CREATE TABLE transacciones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  socio_id INT NOT NULL,
  tipo ENUM('aporte_inicial','aporte','retiro','interes','comision_admin','credito_desembolso','pago_credito','membresia_premium') NOT NULL,
  monto DECIMAL(10,2) NOT NULL,              -- monto neto que se acredita al socio (sin comisión ni IVA)
  comision DECIMAL(10,2) DEFAULT 0.00,       -- comisión cobrada encima del monto (0 en aporte_inicial)
  iva DECIMAL(10,2) DEFAULT 0.00,            -- IVA calculado sobre la comisión
  credito_cuota_id INT NULL,                 -- solo si tipo = 'pago_credito': qué cuota se pagó
  payphone_transaction_id VARCHAR(100) NULL,
  payphone_client_transaction_id VARCHAR(20) NULL,
  estado ENUM('pendiente','confirmado','rechazado') DEFAULT 'pendiente',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (socio_id) REFERENCES socios(id)
);

CREATE TABLE plazos_fijos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  socio_id INT NOT NULL,
  producto ENUM('plazo_fijo','coolcoin') NOT NULL DEFAULT 'plazo_fijo', -- misma mecánica, dos productos con tasas distintas
  capital DECIMAL(10,2) NOT NULL,
  tasa_anual DECIMAL(5,2) NOT NULL,
  fecha_inicio DATE NOT NULL,
  fecha_vencimiento DATE NOT NULL,
  generado DECIMAL(10,2) DEFAULT 0.00,
  FOREIGN KEY (socio_id) REFERENCES socios(id)
);

CREATE TABLE creditos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  socio_id INT NOT NULL,
  monto DECIMAL(10,2) NOT NULL,
  plazo_meses INT NOT NULL,
  tasa_anual DECIMAL(5,2) NOT NULL,        -- tasa vigente al momento de la solicitud (queda fija para este crédito)
  cuota_mensual DECIMAL(10,2) NOT NULL,
  estado ENUM('pendiente','rechazado','activo','pagado') DEFAULT 'pendiente',
  en_mora TINYINT(1) DEFAULT 0,            -- true si alguna cuota está vencida sin pagar
  mensaje_admin TEXT NULL,                 -- por qué se aprobó/rechazó, visible para el socio
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  resuelto_at TIMESTAMP NULL,
  FOREIGN KEY (socio_id) REFERENCES socios(id)
);

CREATE TABLE credito_cuotas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  credito_id INT NOT NULL,
  numero_cuota INT NOT NULL,
  fecha_vencimiento DATE NOT NULL,
  monto_cuota DECIMAL(10,2) NOT NULL,
  estado ENUM('pendiente','pagada','vencida') DEFAULT 'pendiente',
  pagado_at TIMESTAMP NULL,
  FOREIGN KEY (credito_id) REFERENCES creditos(id)
);

CREATE TABLE solicitudes_retiro (
  id INT AUTO_INCREMENT PRIMARY KEY,
  socio_id INT NOT NULL,
  monto DECIMAL(10,2) NOT NULL,
  estado ENUM('pendiente','aprobado','rechazado') DEFAULT 'pendiente',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  resuelto_at TIMESTAMP NULL,
  FOREIGN KEY (socio_id) REFERENCES socios(id)
);

CREATE TABLE paginas_config (
  id INT AUTO_INCREMENT PRIMARY KEY,
  organizacion_id INT NOT NULL,
  pagina ENUM('index','landing-socios','landing-empresas') NOT NULL,
  secciones JSON NULL,                 -- [{"key":"hero","visible":true}, ...] en el orden a mostrar
  popup_activo TINYINT(1) DEFAULT 0,
  popup_titulo VARCHAR(150) NULL,
  popup_texto TEXT NULL,
  popup_imagen_url VARCHAR(255) NULL,  -- por URL, no hay subida de archivos en esta app
  popup_boton_texto VARCHAR(60) NULL,
  popup_boton_url VARCHAR(255) NULL,
  popup_fecha_inicio DATE NULL,
  popup_fecha_fin DATE NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unico_por_org_pagina (organizacion_id, pagina),
  FOREIGN KEY (organizacion_id) REFERENCES organizaciones(id)
);

CREATE TABLE password_resets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  socio_id INT NOT NULL,
  token_hash CHAR(64) NOT NULL,   -- sha256 hex del token; el token crudo NUNCA se guarda
  expires_at TIMESTAMP NOT NULL,
  used_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unico_token_hash (token_hash),
  FOREIGN KEY (socio_id) REFERENCES socios(id)
);

-- Organización de ejemplo para poder probar de inmediato.
-- El admin de esta organización se crea aparte, ejecutando setup_admin.php una sola vez
-- (así la contraseña queda correctamente encriptada, no puesta a mano en el SQL).
INSERT INTO organizaciones (nombre, slug, color_marca, aporte_inicial, retiro_minimo, tasa_plazo_fijo)
VALUES ('Coolki', 'coolki', '#2454FF', 5.00, 10.00, 5.00);
