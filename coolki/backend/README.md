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
   `https://tudominio.com/registro.html`
   Ahí es donde el navegador vuelve tras pagar (PayPhone añade `?id=...&clientTransactionId=...`).
   `registro.html` toma esos parámetros y llama por su cuenta a `payphone_confirm.php` para
   confirmar el cobro del lado del servidor antes de mostrar la pantalla de "pago acreditado".
4. Prueba primero en el ambiente de **pruebas/sandbox** de PayPhone antes de pasar a producción.
   Revisa `api/payphone_confirm.php` — dejé un comentario ahí señalando que debes verificar,
   con una transacción de prueba real, cómo identifica PayPhone un pago aprobado en su respuesta,
   porque el formato exacto puede variar según la versión de su API.

## 8. Conectar el frontend

`registro.html` **ya está conectado** al backend real (Paso 1 → `registro.php`, Paso 2 →
`payphone_prepare.php` + Cajita de Pagos → `payphone_confirm.php`). Cosas que debes saber:

- **Slug de la organización:** no está hardcodeado. Se toma de la URL (`registro.html?org=coolki`)
  o de `window.COOLKI_CONFIG.org`. Así el mismo archivo sirve para varios clientes.
- **Base de la API:** por defecto `/backend/api`. Si subes el backend a otra ruta o subdominio
  (ej. `https://api.tudominio.com`), defínelo antes del `<script>` de `registro.html`:

  ```html
  <script>window.COOLKI_CONFIG = { org: 'coolki', apiBase: 'https://api.tudominio.com/api' };</script>
  ```

  Si frontend y backend quedan en dominios distintos, el backend debe habilitar **CORS con
  credenciales** (las llamadas usan `credentials: 'include'` para mantener la sesión PHP).
- **Contraseña:** el Paso 1 ahora incluye un campo de contraseña (mínimo 8 caracteres), porque
  `registro.php` la exige para poder crear el socio y permitir el login posterior.

Los demás HTML (`sistema.html`, etc.) todavía simulan con JavaScript de mentira; se conectan
endpoint por endpoint igual que este cuando quieras.

## Qué falta todavía (para ser 100% honestos)

- **Cálculo automático de intereses de plazo fijo**: falta una tarea programada (cron job,
  disponible en hPanel > Avanzado > Tareas Cron) que corra una vez al día y acredite intereses.
- **Pago real del retiro al socio**: aprobar un retiro aquí solo descuenta el saldo interno;
  el envío real del dinero (transferencia bancaria o pago PayPhone hacia el socio) lo haces
  manualmente desde tu cuenta de PayPhone Business o tu banco, o se automatiza en una fase futura.
- **Multi-organización por subdominio**: aquí cada organización se identifica por un `slug`
  (ej. `coolki`); si quieres subdominios reales (`textiles-otavalo.coolki.app`) se configura
  aparte en Hostinger (subdominios apuntando a la misma carpeta del backend).
