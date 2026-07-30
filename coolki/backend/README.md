# COOLKI — backend real (PHP + MySQL para Hostinger)

Esto es el backend de verdad: guarda datos en una base de datos, confirma pagos con PayPhone
del lado del servidor (nunca confiando en el navegador), y es lo que reemplaza a las maquetas
HTML que armamos antes.

## 1. Crear la base de datos en Hostinger

1. Entra a **hPanel > Bases de datos > MySQL**.
2. Crea una base de datos y un usuario (Hostinger te da un nombre tipo `u123456789_coolki`).
3. Anota: host (casi siempre `localhost`), nombre de la base, usuario y contraseña.

## 2. Importar el esquema

1. Entra a **phpMyAdmin** desde hPanel.
2. Selecciona tu base de datos → pestaña **Importar** → sube `schema.sql`.

## 3. Configurar credenciales

Edita `config.php` y reemplaza `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` con los datos del paso 1,
y `SITE_URL` con tu dominio real.

## 4. Subir los archivos

Sube **toda esta carpeta** (`config.php`, `schema.sql`, `setup_admin.php`, `includes/`, `api/`)
a tu hosting vía **Administrador de archivos** o FTP. Recomendado: en una subcarpeta o subdominio
separado del frontend, por ejemplo `api.tudominio.com` o `tudominio.com/backend`.

## 5. Crear tu primer administrador

Abre en el navegador: `https://tudominio.com/backend/setup_admin.php`, completa el formulario,
y **borra ese archivo del servidor** apenas termines (por seguridad, no debe quedar accesible).

## 6. Activar SSL

En hPanel, activa el SSL gratuito (Let's Encrypt) para tu dominio. Es obligatorio: PayPhone
no permite configurar URLs de respuesta sin HTTPS.

## 7. Conectar PayPhone

1. En tu cuenta de **PayPhone Developer**, crea una aplicación tipo "WEB"
   (en el selector de plataforma elige **PHP**; no cambia los tokens, sólo las guías que te muestran).
2. Guarda el **Token** y el **StoreId** que te den — estos van en la tabla `organizaciones`
   (columnas `payphone_token` y `payphone_store_id`). Ambos se guardan en la BD, nunca en un
   archivo del frontend. Ojo: la **Cajita de Pagos** entrega el token al navegador al renderizarse
   (así funciona ese método de PayPhone). La seguridad no depende de ocultarlo: el pago sólo se
   acredita en `payphone_confirm.php`, que verifica contra PayPhone del lado del servidor.
3. Configura la **URL de respuesta** de PayPhone apuntando a la **página de registro**, por ejemplo:
   `https://tudominio.com/registro` (sin `.html` — ver sección 11, "URLs limpias").
   Ahí es donde el navegador vuelve tras pagar (PayPhone añade `?id=...&clientTransactionId=...`).
   `registro.html` toma esos parámetros y llama por su cuenta a `payphone_confirm.php` para
   confirmar el cobro del lado del servidor antes de mostrar la pantalla de "pago acreditado".
4. Prueba primero en el ambiente de **pruebas/sandbox** de PayPhone antes de pasar a producción.
   Revisa `api/payphone_confirm.php` — dejé un comentario ahí señalando que debes verificar,
   con una transacción de prueba real, cómo identifica PayPhone un pago aprobado en su respuesta,
   porque el formato exacto puede variar según la versión de su API.

## 8. Conectar el frontend

`registro.html` y `sistema.html` **ya están conectados** al backend real:

- `registro.html`: Paso 1 → `registro.php`, Paso 2 → `payphone_prepare.php` + Cajita de Pagos →
  `payphone_confirm.php`.
- `sistema.html`: pantalla de login (socio o administrador) → dashboard real del socio
  (saldo, aportes recurrentes con PayPhone, retiros, plazo fijo) y panel real del administrador
  (lista de socios, aprobar/rechazar retiros, editar configuración de la caja).

Cosas que debes saber:

- **Slug de la organización:** no está hardcodeado. Se toma de la URL (`?org=coolki`) o de
  `window.COOLKI_CONFIG.org`. Así el mismo archivo sirve para varios clientes.
- **Base de la API:** por defecto `/backend/api`. Si subes el backend a otra ruta o subdominio
  (ej. `https://api.tudominio.com`), defínelo antes del `<script>` de cada HTML:

  ```html
  <script>window.COOLKI_CONFIG = { org: 'coolki', apiBase: 'https://api.tudominio.com/api' };</script>
  ```

  Si frontend y backend quedan en dominios distintos, el backend debe habilitar **CORS con
  credenciales** (las llamadas usan `credentials: 'include'` para mantener la sesión PHP).
- **Contraseña:** el Paso 1 de `registro.html` incluye un campo de contraseña (mínimo 8
  caracteres), porque `registro.php` la exige para poder crear el socio y permitir el login
  posterior.

`onboarding.html`, `landing-empresas.html`, `landing-socios.html` e `index.html` siguen siendo
solo material de venta/demo (no tienen ni necesitan backend): en este modelo, cada organización
nueva se da de alta **manualmente** (insertando su fila en `organizaciones` y corriendo
`setup_admin.php`), no por auto-registro público.

## 9. Endpoints del backend (referencia completa)

| Endpoint | Quién lo usa | Qué hace |
|---|---|---|
| `registro.php` | `registro.html` | Crea el socio (estado `pendiente_pago`) |
| `login.php` | `sistema.html` | Login de socio |
| `admin_login.php` | `sistema.html` | Login de administrador |
| `logout.php` | `sistema.html` | Cierra sesión (socio o admin) |
| `me.php` | `sistema.html` | Dashboard del socio: saldo, movimientos, plazos fijos, retiros pendientes |
| `admin_me.php` | `sistema.html` | Restaura la sesión del admin al recargar |
| `payphone_prepare.php` | `registro.html`, `sistema.html` | Prepara una transacción (`aporte_inicial` o `aporte`) |
| `payphone_confirm.php` | PayPhone (URL de respuesta) | Confirma el pago server-to-server y acredita el saldo |
| `solicitar_retiro.php` | `sistema.html` | El socio pide un retiro |
| `crear_plazo_fijo.php` | `sistema.html` | El socio coloca saldo a plazo fijo o a CoolCoin (según el campo `producto`) |
| `admin_listar_socios.php` | `sistema.html` | Lista de socios + saldo + total en plazo fijo (para el admin) |
| `admin_listar_retiros.php` | `sistema.html` | Solicitudes de retiro pendientes (para el admin) |
| `admin_resolver_retiro.php` | `admin.html` | Aprobar / rechazar un retiro (deja registro en `transacciones`) |
| `admin_actualizar_organizacion.php` | `admin.html` | Guarda todas las reglas configurables de la caja |
| `actualizar_perfil.php` | `sistema.html` | El socio edita sus propios datos y, opcional, su contraseña |
| `admin_detalle_socio.php` | `admin.html` | Detalle completo de un socio (modal): datos, saldo, plazos fijos, transacciones, retiros, créditos |
| `admin_listar_transacciones.php` | `admin.html` | Historial global de transacciones confirmadas de toda la caja |
| `admin_cobrar_mantenimiento.php` | `admin.html` | Cobra un monto fijo a todas las cuentas (ej. comisión de mantenimiento) |
| `solicitar_credito.php` | `sistema.html` | El socio solicita un microcrédito (monto + plazo) |
| `admin_listar_creditos.php` | `admin.html` | Cola de solicitudes de crédito, con contexto y cupo sugerido por socio |
| `admin_detalle_credito.php` | `admin.html` | Detalle de una solicitud de crédito específica |
| `admin_resolver_credito.php` | `admin.html` | Aprobar (desembolsa + genera cuotas) / rechazar un crédito, con mensaje al socio |
| `pagar_cuota_con_saldo.php` | `sistema.html` | Paga una cuota de crédito descontando el saldo disponible (alternativa a PayPhone) |
| `pagar_membresia_con_saldo.php` | `sistema.html` | Paga la membresía Cliente Premium descontando el saldo disponible |
| `completar_perfil.php` | `sistema.html` | Formulario obligatorio de datos adicionales, una sola vez, al activarse la cuenta |
| `obtener_config_publica.php` | `index.html`, `landing-*.html`, `terminos.html` | Color de marca, secciones/pop-up de una página, y (nuevo) aporte inicial y costo de membresía Premium |

## 10. Cron de intereses de plazo fijo

`cron/acreditar_intereses.php` acumula el interés diario de cada plazo fijo y, al vencer,
acredita lo generado al saldo disponible del socio y renueva el plazo un año más (mismo capital).

En hPanel → **Avanzado → Tareas Cron**, crea una tarea que corra **una vez al día** con:

```
php /home/TU_USUARIO/domains/tudominio.com/public_html/backend/cron/acreditar_intereses.php
```

(ajusta la ruta exacta a donde subiste la carpeta `backend/` en tu hosting). Este script está
protegido para que solo se ejecute por línea de comandos (cron), nunca abriéndolo desde el
navegador.

**Supuesto que asumí y debes validar con tu negocio:** el interés se calcula como
`capital × tasa_anual / 100 / 365` cada día, y solo se acredita al saldo disponible del socio
cuando el plazo cumple un año (no día a día). Si tu caja de ahorro maneja el interés distinto
(otra fórmula, otra frecuencia de pago, retiro anticipado del capital), este script hay que
ajustarlo — hoy no existe una forma de que el socio retire el capital de un plazo fijo antes de
su vencimiento; eso se coordinaría manualmente con el administrador.

## 11. URLs limpias (sin `.html`)

El archivo `.htaccess` (en la raíz de `public_html`, junto a `index.html`) hace dos cosas:

1. Si alguien pide `/registro.html` directamente, redirige (301) a `/registro`.
2. Al pedir `/registro` (sin extensión), sirve internamente `registro.html` — el navegador
   nunca ve el `.html` en la barra de direcciones.

No afecta a `backend/` — los endpoints `.php` funcionan exactamente igual que antes.

**Importante:** actualiza la **URL de respuesta** en PayPhone Developer a
`https://tudominio.com/registro` (sin `.html`) para evitar depender del salto de redirección.

Requiere que tu hosting tenga `mod_rewrite` habilitado y permita `.htaccess`
(`AllowOverride All`) — en Hostinger compartido esto ya viene activado por defecto. Si después
de subir el `.htaccess` las URLs limpias no funcionan, revisa en hPanel si hay alguna opción de
"Reescritura de URL" que debas activar, o contacta a soporte de Hostinger.

## 12. Módulo de créditos (microcréditos a los socios)

### Migración de base de datos requerida

Este módulo agrega columnas nuevas y dos tablas nuevas. Si tu base de datos ya está en
producción (no es una instalación nueva desde `schema.sql`), corre esto en phpMyAdmin
**antes** de subir el código nuevo:

```sql
ALTER TABLE organizaciones
  ADD COLUMN credito_tasa_anual DECIMAL(5,2) DEFAULT 28.23 AFTER iva_pct,
  ADD COLUMN credito_limite_base DECIMAL(10,2) DEFAULT 100.00 AFTER credito_tasa_anual,
  ADD COLUMN credito_incremento_por_pago DECIMAL(10,2) DEFAULT 50.00 AFTER credito_limite_base,
  ADD COLUMN credito_limite_maximo DECIMAL(10,2) DEFAULT 500.00 AFTER credito_incremento_por_pago,
  ADD COLUMN credito_plazo_min_meses INT DEFAULT 1 AFTER credito_limite_maximo,
  ADD COLUMN credito_plazo_max_meses INT DEFAULT 6 AFTER credito_plazo_min_meses;

ALTER TABLE transacciones
  ADD COLUMN credito_cuota_id INT NULL AFTER iva,
  MODIFY tipo ENUM('aporte_inicial','aporte','retiro','interes','comision_admin','credito_desembolso','pago_credito') NOT NULL;

CREATE TABLE creditos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  socio_id INT NOT NULL,
  monto DECIMAL(10,2) NOT NULL,
  plazo_meses INT NOT NULL,
  tasa_anual DECIMAL(5,2) NOT NULL,
  cuota_mensual DECIMAL(10,2) NOT NULL,
  estado ENUM('pendiente','rechazado','activo','pagado') DEFAULT 'pendiente',
  en_mora TINYINT(1) DEFAULT 0,
  mensaje_admin TEXT NULL,
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
```

### Cómo funciona

- **Tasa:** 28.23% efectiva anual por defecto — la tasa máxima legal vigente para
  **microcrédito minorista** en Ecuador (Banco Central, la más alta de los tres segmentos de
  microcrédito). El BCE la actualiza periódicamente; ajústala desde `admin.html` →
  Configuración → "Reglas de crédito" cuando cambie. Nunca la subas por encima del límite legal
  vigente — sería usura.
- **Cuota mensual:** sistema francés (cuota fija), con la tasa anual convertida a mensual por
  composición: `(1 + tasa_anual)^(1/12) - 1` (no es una simple división entre 12).
- **Cupo sugerido:** $100 base + $50 por cada crédito que el socio pagó por completo y sin
  ningún atraso, hasta un tope de $500 (todo configurable). Es **solo informativo** — no bloquea
  ninguna solicitud ni obliga al administrador a nada; el admin decide caso por caso.
- **Aprobación:** 100% manual. `admin.html` muestra el saldo, la antigüedad de la cuenta y el
  cupo sugerido junto a cada solicitud. El administrador **debe** escribir un mensaje (se lo
  guarda y el socio lo ve) explicando por qué se aprobó o se rechazó.
- **Al aprobar:** se acredita el monto al saldo disponible del socio y se genera la tabla de
  cuotas (fecha de vencimiento y monto de cada una).
- **Pago de cuotas:** el socio paga cada cuota con la Cajita de Pagos de PayPhone (dinero
  externo, no sale de su saldo de ahorro). Al confirmarse, se marca la cuota como pagada y, si
  ya se pagaron todas, el crédito completo pasa a `pagado`.
- **Mora:** `cron/marcar_mora.php` — configúralo igual que `acreditar_intereses.php`, corriendo
  **una vez al día**:
  ```
  php /home/TU_USUARIO/domains/tudominio.com/public_html/backend/cron/marcar_mora.php
  ```
  Marca cuotas vencidas y deja el crédito marcado `en_mora` permanentemente (aunque se pague
  después) — eso es lo que excluye a ese crédito del cupo sugerido futuro.

### Limitaciones conocidas de este módulo (revísalas con tu negocio)

- **No hay penalización por mora** (interés moratorio, recargo por atraso) — una cuota vencida
  solo se marca como tal; no se le suma ningún cargo adicional. Si tu negocio cobra mora, hay
  que agregarlo.
- **Las estadísticas del panel admin ("Total ahorrado") suman el saldo disponible sin
  distinguir si parte de ese saldo es dinero prestado** (un crédito desembolsado se acredita al
  mismo `saldo_disponible` que los ahorros reales). Mientras un socio tenga un crédito activo,
  ese número va a verse más alto de lo que realmente "ahorró". Si te importa un reporte de
  patrimonio neto real (ahorros menos deuda pendiente), eso es un desarrollo aparte.
- **No hay cobro automático de cuotas** — el socio tiene que entrar y pagar cada cuota
  manualmente desde `sistema.html`; no se le hace ningún cargo automático a su tarjeta.
- **Un socio no puede tener dos créditos a la vez** (ni dos solicitudes pendientes) — debe
  terminar de pagar el actual antes de solicitar otro. Es una regla de sentido común que agregué
  sin que me la pidieras explícitamente; dime si prefieres permitir créditos simultáneos.

## 13. CoolCoin (segunda opción de inversión, misma mecánica del plazo fijo)

CoolCoin **no es una criptomoneda ni un sistema de puntos** — es un producto de inversión real,
idéntico en mecánica al plazo fijo: el socio coloca dinero real de su saldo disponible, ese
capital queda bloqueado 1 año, se renueva solo, y genera una rentabilidad fija (5.5% anual por
defecto) que se acredita a su saldo disponible al vencer — exactamente igual que ya sucede con
el plazo fijo. La única diferencia es la tasa y que aparece en su propia sección del dashboard.

Por eso, en vez de crear una tabla y un flujo paralelos, se reutiliza `plazos_fijos` /
`crear_plazo_fijo.php` / `cron/acreditar_intereses.php` por completo, agregando solo una columna
discriminadora `producto`. **El cron no necesita ningún cambio** — ya calcula el interés con la
`tasa_anual` guardada en cada fila individual, sin importar de qué producto sea.

### Migración de base de datos requerida

Si tu base de datos ya está en producción, corre esto en phpMyAdmin **antes** de subir el código
nuevo:

```sql
ALTER TABLE plazos_fijos
  ADD COLUMN producto ENUM('plazo_fijo','coolcoin') NOT NULL DEFAULT 'plazo_fijo' AFTER socio_id;

ALTER TABLE organizaciones
  ADD COLUMN coolcoin_tasa_anual DECIMAL(5,2) DEFAULT 5.50 AFTER tasa_plazo_fijo;
```

### Cómo funciona

- El socio elige, desde `sistema.html`, colocar su dinero a "plazo fijo" o a "CoolCoin" — son dos
  formularios separados que llaman al mismo endpoint `crear_plazo_fijo.php` con
  `producto: 'plazo_fijo'` o `producto: 'coolcoin'`.
- Cada fila de `plazos_fijos` guarda su propia `tasa_anual` al momento de crearse (tomada de
  `tasa_plazo_fijo` o `coolcoin_tasa_anual` de la organización, según el producto), así que si el
  administrador cambia la tasa de CoolCoin después, no afecta las inversiones ya colocadas.
- El panel del administrador (`admin.html`) ajusta la tasa de CoolCoin junto a la de plazo fijo,
  en Configuración → "Reglas de ahorro", y muestra el total invertido en CoolCoin como estadística
  separada del total en plazo fijo — nunca se suman entre sí para no confundir un producto con
  otro.

## 14. Constructor de páginas (page builder)

Un panel dentro de `admin.html` para reordenar/ocultar secciones de las páginas públicas,
elegir un color de marca (aplicado también a `sistema.html`/`admin.html`), y configurar un
pop-up distinto por página — todo con vista previa en tiempo real antes de guardar.

### Migración de base de datos requerida

Si tu base de datos ya está en producción, corre esto en phpMyAdmin **antes** de subir el código
nuevo:

```sql
CREATE TABLE paginas_config (
  id INT AUTO_INCREMENT PRIMARY KEY,
  organizacion_id INT NOT NULL,
  pagina ENUM('index','landing-socios','landing-empresas') NOT NULL,
  secciones JSON NULL,
  popup_activo TINYINT(1) DEFAULT 0,
  popup_titulo VARCHAR(150) NULL,
  popup_texto TEXT NULL,
  popup_imagen_url VARCHAR(255) NULL,
  popup_boton_texto VARCHAR(60) NULL,
  popup_boton_url VARCHAR(255) NULL,
  popup_fecha_inicio DATE NULL,
  popup_fecha_fin DATE NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unico_por_org_pagina (organizacion_id, pagina),
  FOREIGN KEY (organizacion_id) REFERENCES organizaciones(id)
);
```

Ninguna organización necesita una fila de por sí: si no existe una fila para
`(organizacion_id, pagina)`, se asume el orden por defecto (todas las secciones visibles, en el
orden original de cada archivo) y el pop-up inactivo — así `coolki` sigue viéndose exactamente
igual que hoy hasta que alguien use el builder.

### Cómo funciona

- **Páginas con builder completo** (secciones + pop-up + color): `index.html`,
  `landing-socios.html`, `landing-empresas.html`.
- **Páginas que solo reciben el color** (sin secciones ni pop-up): `sistema.html`, `admin.html`.
- **`onboarding.html` queda fuera** — es el asistente interno de CDH SOFT para dar de alta
  organizaciones nuevas, no una página de contenido para socios/visitantes.
- El color de marca ya existía en `organizaciones.color_marca` (columna sin usar hasta ahora); el
  builder lo aplica en tiempo real a las 5 páginas de arriba, incluyendo el logo SVG.
- Un solo script compartido, `frontend/builder-runtime.js`, es lo que cada página incluye para
  pintarse: consulta `obtener_config_publica.php` (endpoint público, sin login) y aplica color,
  orden/visibilidad de secciones, y el pop-up si corresponde.
- El pop-up de cada página se puede programar con fecha de inicio y fin; fuera de ese rango no se
  muestra aunque esté marcado como activo. Las imágenes van por URL — esta app no tiene
  mecanismo de subida de archivos.
- En el panel del administrador, arrastrar secciones, cambiar el color o editar el pop-up
  actualiza al instante una vista previa en un `<iframe>` (sin tocar la base de datos) — solo el
  botón "Guardar cambios" persiste.

### Limitaciones conocidas

- **No hay historial de versiones** — guardar sobreescribe la configuración anterior de esa
  página, sin deshacer.
- **Sin subida de imágenes** para el pop-up, solo URL externa.
- **`onboarding.html` no se beneficia de este color** — sigue con sus swatches fijos, ya que es
  la herramienta para crear organizaciones nuevas (antes de que exista un color que aplicar).

## 15. Membresía Cliente Premium (requisito para pedir crédito)

Todo socio empieza como "Cliente Normal" y no puede solicitar microcréditos. Para poder pedirlos,
debe pagar una sola vez la membresía "Cliente Premium" (monto configurable, $20 por defecto) —
con PayPhone o descontado directo de su saldo disponible. Ese pago **no se acredita a ningún
saldo del socio** — es ingreso puro de la organización ("fondos de reserva / gastos
administrativos"), a diferencia de un aporte normal.

### Migración de base de datos requerida

```sql
ALTER TABLE organizaciones
  ADD COLUMN membresia_premium_costo DECIMAL(10,2) DEFAULT 20.00 AFTER credito_plazo_max_meses;

ALTER TABLE socios
  ADD COLUMN nivel ENUM('normal','premium') NOT NULL DEFAULT 'normal' AFTER estado;

ALTER TABLE transacciones
  MODIFY tipo ENUM('aporte_inicial','aporte','retiro','interes','comision_admin','credito_desembolso','pago_credito','membresia_premium') NOT NULL;

-- Los socios que YA están activos hoy quedan exonerados del pago (Premium automático).
-- Esto solo exonera el pago de $20 — el formulario de perfil de la sección 17 sigue
-- siendo obligatorio para todos, incluidos estos socios.
UPDATE socios SET nivel = 'premium' WHERE estado = 'activo';
```

### Cómo funciona

- `solicitar_credito.php` rechaza la solicitud si `nivel !== 'premium'`.
- `sistema.html` (pestaña Créditos) muestra una tarjeta de actualización en vez del formulario de
  solicitud mientras el socio sea "Normal", con el costo real tomado de
  `membresia_premium_costo` (nunca un texto fijo).
- Pago con PayPhone: `payphone_prepare.php`/`payphone_confirm.php` tratan `tipo =
  'membresia_premium'` igual que ya tratan `pago_credito` — el monto sale siempre de la base de
  datos, nunca del navegador.
- Pago con saldo: `pagar_membresia_con_saldo.php` hace el mismo cambio de `nivel` de forma
  síncrona, sin pasar por PayPhone.
- Configúralo desde `admin.html` → Configuración → "Reglas de crédito".

## 16. Términos y condiciones en el registro

`registro.html` exige aceptar un checkbox de Términos y Condiciones antes de crear la cuenta, con
un link a la nueva página `terminos.html`. El documento describe, con los montos reales de la
organización (no textos fijos): el aporte inicial como costo administrativo de apertura de
cuenta (ya existente, solo documentado por escrito), el costo de la membresía Premium, y el
consentimiento del socio para ser parte de la "Comunidad Digital COOLKI" y recibir contenido
educativo de ahorro y finanzas personales por correo.

### Migración de base de datos requerida

```sql
ALTER TABLE socios
  ADD COLUMN terminos_aceptados_at TIMESTAMP NULL AFTER created_at;
```

### Cómo funciona

- `terminos.html` consulta `obtener_config_publica.php?org=slug` (público, sin login) para
  mostrar los montos reales de `aporte_inicial` y `membresia_premium_costo`.
- `registro.php` rechaza la creación de la cuenta si no llega `terminos_aceptados: true`, y
  guarda `terminos_aceptados_at = NOW()` al crear el socio.

## 17. Perfil obligatorio del socio (datos adicionales)

Justo cuando la cuenta de un socio pasa a `activo` (aporte inicial confirmado), se le muestra un
formulario obligatorio y bloqueante — no puede usar el resto del dashboard hasta llenarlo — con:
ciudad, provincia, dirección domiciliaria, tipo de empleo (dependiente/independiente), ingresos
mensuales y estado civil. Es una sola vez; después queda disponible para editar (opcionalmente)
desde la pestaña "Mi perfil".

**Aplica también a socios que ya están activos hoy** — la próxima vez que entren verán este
formulario (decisión explícita: solo se exoneró el pago de la membresía Premium, no este
formulario).

### Migración de base de datos requerida

```sql
ALTER TABLE socios
  ADD COLUMN perfil_completo TINYINT(1) NOT NULL DEFAULT 0 AFTER nivel,
  ADD COLUMN ciudad VARCHAR(100) NULL AFTER celular,
  ADD COLUMN provincia VARCHAR(100) NULL AFTER ciudad,
  ADD COLUMN direccion VARCHAR(255) NULL AFTER provincia,
  ADD COLUMN tipo_empleo ENUM('dependiente','independiente') NULL AFTER direccion,
  ADD COLUMN ingresos_mensuales DECIMAL(10,2) NULL AFTER tipo_empleo,
  ADD COLUMN estado_civil ENUM('soltero','casado','divorciado','viudo','union_libre') NULL AFTER ingresos_mensuales;
```

### Cómo funciona

- `sistema.html` revisa, en cada carga del dashboard, si `estado === 'activo' &&
  !perfil_completo` — si es así, muestra un overlay que no se puede cerrar hasta guardar.
- `completar_perfil.php` exige los 6 campos y marca `perfil_completo = 1`.
- `actualizar_perfil.php` (edición posterior, opcional) también acepta estos mismos campos, sin
  tocar `perfil_completo`.
- El panel de administrador (`admin.html`, modal de detalle de socio) muestra estos datos — son
  relevantes para decidir sobre una solicitud de crédito.

## 18. Seguridad de acceso, recuperación de contraseña y datos de soporte

### Migración de base de datos requerida

```sql
ALTER TABLE socios
  ADD COLUMN intentos_fallidos INT NOT NULL DEFAULT 0 AFTER terminos_aceptados_at,
  ADD COLUMN bloqueado_hasta TIMESTAMP NULL AFTER intentos_fallidos;

ALTER TABLE admins
  ADD COLUMN intentos_fallidos INT NOT NULL DEFAULT 0 AFTER created_at,
  ADD COLUMN bloqueado_hasta TIMESTAMP NULL AFTER intentos_fallidos;

ALTER TABLE organizaciones
  ADD COLUMN soporte_email VARCHAR(150) NULL AFTER membresia_premium_costo,
  ADD COLUMN soporte_whatsapp VARCHAR(255) NULL AFTER soporte_email;

CREATE TABLE password_resets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  socio_id INT NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at TIMESTAMP NOT NULL,
  used_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unico_token_hash (token_hash),
  FOREIGN KEY (socio_id) REFERENCES socios(id)
);
```

### Cómo funciona

- **Bloqueo por intentos fallidos**: 5 intentos fallidos seguidos (socio o administrador)
  bloquean el acceso por 15 minutos (`LOGIN_INTENTOS_MAXIMOS`/`LOGIN_BLOQUEO_MINUTOS` en
  `config.php`, ajustables). El bloqueo se revisa **antes** de comprobar la contraseña.
- **Recuperación de contraseña**: `recuperar.html` pide el correo → `solicitar_recuperacion.php`
  genera un token de un solo uso (30 minutos de vigencia, invalida cualquier token anterior sin
  usar de ese socio) y lo envía por correo real vía SMTP. La respuesta es **siempre el mismo
  mensaje genérico**, exista o no una cuenta con ese correo — nunca se revela si un correo está
  registrado. `restablecer.html` recibe el link del correo y permite fijar una contraseña nueva;
  el token deja de servir apenas se usa una vez.
- **Envío de correo real**: requiere configurar en `config.php` una cuenta de correo real de tu
  hosting (`SMTP_USER`/`SMTP_PASS`/`SMTP_HOST`/`SMTP_PORT`/`SMTP_SECURE` — en Hostinger, hPanel
  > Correo > Cuentas de correo). El envío usa un cliente SMTP propio (`backend/includes/mailer.php`),
  sin librerías externas.
- **Validación de cédula y celular ecuatorianos**: `registro.php` valida el algoritmo real de
  cédula (módulo 10) y que el celular tenga 10 dígitos empezando en `09` — del lado del
  servidor, nunca solo en el navegador.
- **Datos de soporte**: `soporte_email`/`soporte_whatsapp` son opcionales — si no están
  configurados en `admin.html`, sus enlaces simplemente no aparecen en la pantalla de login.

### Limitaciones conocidas

- El correo de recuperación no puede probarse en un entorno de desarrollo sin servidor SMTP
  real — pruébalo primero con una cuenta de correo real antes de confiar en el flujo completo.
- La solicitud de recuperación tarda ligeramente más cuando el correo SÍ existe (por el trabajo
  extra de generar el token y enviar el correo) — es una fuga de tiempo mínima y aceptada, no
  se intentó igualar artificialmente el tiempo de respuesta.

## Qué falta todavía (para ser 100% honestos)

- **Pago real del retiro al socio**: aprobar un retiro solo descuenta el saldo interno; el envío
  real del dinero (transferencia bancaria o pago PayPhone hacia el socio) lo haces manualmente
  desde tu cuenta de PayPhone Business o tu banco, o se automatiza en una fase futura.
- **Retiro anticipado de un plazo fijo**: hoy el capital colocado a plazo fijo se renueva solo
  cada año; no hay una acción para deshacerlo antes de tiempo desde la app.
- **Exportar reporte SEPS**: el botón existe en el panel del administrador pero está deshabilitado
  a propósito — generar un reporte regulatorio real es un desarrollo aparte, no algo que deba
  fingirse.
- **`aprobacion_retiros = 'automatica'`**: la columna existe en `organizaciones` pero hoy todos
  los retiros siempre requieren acción manual del administrador; el modo automático no está
  implementado.
- **Multi-organización por subdominio**: aquí cada organización se identifica por un `slug`
  (ej. `coolki`); si quieres subdominios reales (`textiles-otavalo.coolki.app`) se configura
  aparte en Hostinger (subdominios apuntando a la misma carpeta del backend).
- **Auto-registro de organizaciones nuevas**: `onboarding.html` sigue siendo una maqueta de venta;
  dar de alta un cliente nuevo se hace manualmente (SQL + `setup_admin.php`), no hay un flujo
  público para que una organización se autoconfigure.
- **Envío de correos educativos/marketing de "Comunidad Digital COOLKI"**: el registro captura
  por escrito el consentimiento del socio (checkbox de Términos y Condiciones), pero no existe
  ninguna infraestructura de envío masivo de correos en esta app — ni lista de suscriptores, ni
  plantillas, ni cron de envío. Construir eso es un desarrollo aparte; si el negocio ya promete
  esto como parte de la membresía, conviene priorizarlo pronto para no incumplir lo que el socio
  aceptó.
