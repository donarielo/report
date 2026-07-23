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
| `crear_plazo_fijo.php` | `sistema.html` | El socio coloca saldo a plazo fijo |
| `admin_listar_socios.php` | `sistema.html` | Lista de socios + saldo + total en plazo fijo (para el admin) |
| `admin_listar_retiros.php` | `sistema.html` | Solicitudes de retiro pendientes (para el admin) |
| `admin_resolver_retiro.php` | `sistema.html` | Aprobar / rechazar un retiro |
| `admin_actualizar_organizacion.php` | `sistema.html` | Guarda tasa de plazo fijo, aporte inicial y retiro mínimo |

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
