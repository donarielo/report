/* ------------------------------------------------------------------
   COOLKI · builder-runtime.js — script compartido incluido por index.html,
   landing-socios.html, landing-empresas.html (con data-pagina="...") y por
   sistema.html/admin.html (sin data-pagina, solo aplican el color).
   Consulta obtener_config_publica.php y pinta: color de marca + logo,
   orden/visibilidad de secciones, y el pop-up de la página si corresponde.
   En modo ?preview=1 (usado por el builder de admin.html) también escucha
   postMessage para actualizarse al instante sin pasar por la base de datos.
------------------------------------------------------------------ */
(function () {
  const CONFIG = window.COOLKI_CONFIG || {};
  const API_BASE = String(CONFIG.apiBase || '/backend/api').replace(/\/+$/, '');
  const PARAMS = new URLSearchParams(location.search);
  const ORG_SLUG = (PARAMS.get('org') || CONFIG.org || '').trim();
  const PAGINA = document.currentScript ? (document.currentScript.dataset.pagina || '') : '';
  const PREVIEW_MODE = PARAMS.get('preview') === '1';

  if (!ORG_SLUG) return;

  function aplicarColor(org) {
    if (!org || !org.color_marca) return;
    const raices = document.querySelectorAll('.coolki-b2c, .coolki-land, .coolki-app, .coolki-admin');
    raices.forEach(el => {
      el.style.setProperty('--brand', org.color_marca);
      el.style.setProperty('--brand-soft', org.brand_soft || org.color_marca);
    });
    document.querySelectorAll('svg rect[fill="#2454FF"]').forEach(rect => {
      rect.setAttribute('fill', org.color_marca);
    });
  }

  // Enlaces de redes sociales en el footer: solo se muestran si la organización configuró
  // esa URL en admin.html — mismo criterio que ya usa mostrarPopup() con sessionStorage y
  // los datos de soporte en sistema.html (esconder en vez de mostrar un link roto).
  function aplicarFooter(org) {
    if (!org) return;
    [
      ['footerInstagram', org.instagram_url],
      ['footerTiktok', org.tiktok_url],
      ['footerFacebook', org.facebook_url],
    ].forEach(([id, url]) => {
      const el = document.getElementById(id);
      if (!el) return;
      if (url) {
        el.href = url;
        el.style.display = '';
      } else {
        el.style.display = 'none';
      }
    });
  }

  function aplicarSecciones(secciones) {
    if (!Array.isArray(secciones) || !secciones.length) return;
    const footer = document.querySelector('[data-section="footer"]');
    if (!footer || !footer.parentNode) return;
    const padre = footer.parentNode;
    secciones.forEach(item => {
      const el = document.querySelector('[data-section="' + item.key + '"]:not([data-pinned])');
      if (!el) return;
      el.style.display = item.visible === false ? 'none' : '';
      padre.insertBefore(el, footer);
    });
  }

  function asegurarEstilosPopup() {
    if (document.getElementById('coolki-popup-style')) return;
    const style = document.createElement('style');
    style.id = 'coolki-popup-style';
    style.textContent =
      '.coolki-popup-overlay{position:fixed;inset:0;background:rgba(22,24,43,0.55);z-index:999;' +
      'display:flex;align-items:center;justify-content:center;padding:20px;font-family:"DM Sans",sans-serif;}' +
      '.coolki-popup-card{background:#fff;border-radius:18px;max-width:420px;width:100%;padding:26px;' +
      'position:relative;box-shadow:0 30px 60px -30px rgba(22,24,43,0.4);}' +
      '.coolki-popup-close{position:absolute;top:14px;right:16px;border:none;background:transparent;' +
      'font-size:22px;line-height:1;cursor:pointer;color:#6B6F85;}' +
      '.coolki-popup-img{width:100%;border-radius:12px;margin-bottom:14px;display:block;}' +
      '.coolki-popup-title{font-family:"Space Grotesk",sans-serif;font-weight:700;font-size:19px;margin-bottom:8px;}' +
      '.coolki-popup-text{font-size:13.5px;color:#333;line-height:1.5;margin-bottom:16px;white-space:pre-line;}' +
      '.coolki-popup-btn{display:inline-block;padding:11px 18px;border-radius:10px;background:var(--brand,#2454FF);' +
      'color:#fff;text-decoration:none;font-weight:600;font-size:13.5px;}';
    document.head.appendChild(style);
  }

  function quitarPopup() {
    const existente = document.getElementById('coolki-popup-overlay');
    if (existente) existente.remove();
  }

  function mostrarPopup(popup) {
    quitarPopup();
    if (!popup || !popup.activo) return;
    const key = 'coolki_popup_dismissed_' + ORG_SLUG + '_' + PAGINA;
    if (!PREVIEW_MODE && sessionStorage.getItem(key)) return;

    asegurarEstilosPopup();
    const overlay = document.createElement('div');
    overlay.className = 'coolki-popup-overlay';
    overlay.id = 'coolki-popup-overlay';

    const card = document.createElement('div');
    card.className = 'coolki-popup-card';

    const cerrar = document.createElement('button');
    cerrar.className = 'coolki-popup-close';
    cerrar.textContent = '×';
    cerrar.onclick = () => { sessionStorage.setItem(key, '1'); overlay.remove(); };
    card.appendChild(cerrar);

    if (popup.imagen_url) {
      const img = document.createElement('img');
      img.className = 'coolki-popup-img';
      img.src = popup.imagen_url;
      img.alt = '';
      card.appendChild(img);
    }
    if (popup.titulo) {
      const titulo = document.createElement('div');
      titulo.className = 'coolki-popup-title';
      titulo.textContent = popup.titulo;
      card.appendChild(titulo);
    }
    if (popup.texto) {
      const texto = document.createElement('div');
      texto.className = 'coolki-popup-text';
      texto.textContent = popup.texto;
      card.appendChild(texto);
    }
    if (popup.boton_texto && popup.boton_url) {
      const boton = document.createElement('a');
      boton.className = 'coolki-popup-btn';
      boton.href = popup.boton_url;
      boton.target = '_blank';
      boton.rel = 'noopener';
      boton.textContent = popup.boton_texto;
      card.appendChild(boton);
    }

    overlay.appendChild(card);
    overlay.onclick = (e) => { if (e.target === overlay) cerrar.onclick(); };
    document.body.appendChild(overlay);
  }

  function applyConfig(data) {
    aplicarColor(data.organizacion);
    aplicarFooter(data.organizacion);
    if (PAGINA) {
      aplicarSecciones(data.secciones);
      mostrarPopup(data.popup);
    }
  }

  const url = API_BASE + '/obtener_config_publica.php?org=' + encodeURIComponent(ORG_SLUG) +
    (PAGINA ? '&pagina=' + encodeURIComponent(PAGINA) : '');
  fetch(url).then(res => res.json()).then(data => {
    if (data && data.ok) applyConfig(data);
  }).catch(() => { /* si falla, la página se queda con sus valores por defecto */ });

  if (PREVIEW_MODE) {
    window.addEventListener('message', (e) => {
      if (e.origin !== location.origin) return;
      if (!e.data || e.data.source !== 'coolki-builder' || e.data.type !== 'preview-update') return;
      applyConfig(e.data.payload);
    });
  }
})();
