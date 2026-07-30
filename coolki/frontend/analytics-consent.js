/* ------------------------------------------------------------------
   COOLKI · analytics-consent.js — banner de cookies + carga de Google Analytics /
   Meta Pixel, SOLO si ambas condiciones se cumplen: la organización configuró un
   ga_measurement_id/meta_pixel_id en admin.html, Y el visitante aceptó el aviso.
   El consentimiento se guarda en localStorage (no en una cookie), para no depender
   de consentimiento para poder leer el propio consentimiento.
------------------------------------------------------------------ */
(function () {
  const CONFIG = window.COOLKI_CONFIG || {};
  const API_BASE = String(CONFIG.apiBase || '/backend/api').replace(/\/+$/, '');
  const PARAMS = new URLSearchParams(location.search);
  const ORG_SLUG = (PARAMS.get('org') || CONFIG.org || '').trim();
  const CLAVE_CONSENTIMIENTO = 'coolki_cookies_consent';

  if (!ORG_SLUG) return;

  function cargarGoogleAnalytics(id) {
    const s = document.createElement('script');
    s.async = true;
    s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(id);
    document.head.appendChild(s);
    window.dataLayer = window.dataLayer || [];
    function gtag() { window.dataLayer.push(arguments); }
    gtag('js', new Date());
    gtag('config', id);
  }

  function cargarMetaPixel(id) {
    /* eslint-disable */
    (function (f, b, e, v, n, t, s) {
      if (f.fbq) return; n = f.fbq = function () { n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments); };
      if (!f._fbq) f._fbq = n; n.push = n; n.loaded = true; n.version = '2.0'; n.queue = [];
      t = b.createElement(e); t.async = true; t.src = v;
      s = b.getElementsByTagName(e)[0]; s.parentNode.insertBefore(t, s);
    })(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');
    /* eslint-enable */
    window.fbq('init', id);
    window.fbq('track', 'PageView');
  }

  function cargarAnalitica(org) {
    if (org.ga_measurement_id) cargarGoogleAnalytics(org.ga_measurement_id);
    if (org.meta_pixel_id) cargarMetaPixel(org.meta_pixel_id);
  }

  function asegurarEstilosBanner() {
    if (document.getElementById('coolki-cookie-style')) return;
    const style = document.createElement('style');
    style.id = 'coolki-cookie-style';
    style.textContent =
      '.coolki-cookie-banner{position:fixed;left:16px;right:16px;bottom:16px;z-index:998;' +
      'max-width:480px;margin:0 auto;background:#16182B;color:#fff;border-radius:14px;' +
      'padding:18px 20px;font-family:"DM Sans",sans-serif;font-size:13px;line-height:1.5;' +
      'box-shadow:0 20px 50px -20px rgba(0,0,0,0.5);}' +
      '.coolki-cookie-actions{display:flex;gap:10px;margin-top:12px;}' +
      '.coolki-cookie-btn{flex:1;padding:9px 14px;border-radius:9px;border:none;cursor:pointer;' +
      'font-weight:600;font-size:12.5px;font-family:inherit;}' +
      '.coolki-cookie-accept{background:var(--brand,#2454FF);color:#fff;}' +
      '.coolki-cookie-reject{background:rgba(255,255,255,0.12);color:#fff;}';
    document.head.appendChild(style);
  }

  function mostrarBanner(org) {
    asegurarEstilosBanner();
    const banner = document.createElement('div');
    banner.className = 'coolki-cookie-banner';
    banner.id = 'coolki-cookie-banner';
    banner.innerHTML =
      'Usamos cookies para entender cómo se usa este sitio y mejorar tu experiencia. ' +
      'Puedes aceptarlas o rechazarlas.' +
      '<div class="coolki-cookie-actions">' +
      '<button type="button" class="coolki-cookie-btn coolki-cookie-reject" id="coolkiCookieReject">Rechazar</button>' +
      '<button type="button" class="coolki-cookie-btn coolki-cookie-accept" id="coolkiCookieAccept">Aceptar</button>' +
      '</div>';
    document.body.appendChild(banner);

    document.getElementById('coolkiCookieAccept').addEventListener('click', () => {
      localStorage.setItem(CLAVE_CONSENTIMIENTO, 'accepted');
      banner.remove();
      cargarAnalitica(org);
    });
    document.getElementById('coolkiCookieReject').addEventListener('click', () => {
      localStorage.setItem(CLAVE_CONSENTIMIENTO, 'rejected');
      banner.remove();
    });
  }

  const url = API_BASE + '/obtener_config_publica.php?org=' + encodeURIComponent(ORG_SLUG);
  fetch(url).then(res => res.json()).then(data => {
    if (!data || !data.ok) return;
    const org = data.organizacion || {};
    if (!org.ga_measurement_id && !org.meta_pixel_id) return;

    const consentimiento = localStorage.getItem(CLAVE_CONSENTIMIENTO);
    if (consentimiento === 'accepted') {
      cargarAnalitica(org);
    } else if (consentimiento !== 'rejected') {
      mostrarBanner(org);
    }
  }).catch(() => { /* sin analítica si falla la consulta */ });
})();
