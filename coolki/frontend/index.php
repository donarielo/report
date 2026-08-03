<?php
require __DIR__ . '/seo_bootstrap.php';

$org = resolverOrganizacionPublica('coolki');
if (!$org) {
    http_response_code(404);
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Página no encontrada</title></head>' .
         '<body style="font-family:sans-serif;text-align:center;padding:60px 20px;">' .
         '<p>No pudimos encontrar esta caja de ahorro.</p></body></html>';
    exit;
}
$orgSlug = $org['slug'];

$pdo = getDB();
$stmt = $pdo->prepare("SELECT * FROM paginas_config WHERE organizacion_id = ? AND pagina = 'index'");
$stmt->execute([$org['id']]);
$seoConfig = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

$stmt = $pdo->prepare("SELECT * FROM pagina_pasos WHERE organizacion_id = ? AND pagina = 'index' ORDER BY orden ASC, id ASC");
$stmt->execute([$org['id']]);
$pasosGuardados = $stmt->fetchAll(PDO::FETCH_ASSOC);
$pasos = $pasosGuardados
    ? array_values(array_filter($pasosGuardados, fn($p) => !empty($p['visible'])))
    : pasosPorDefecto();

$stmt = $pdo->prepare("SELECT * FROM testimonios WHERE organizacion_id = ? AND publicado = 1 ORDER BY orden ASC, id ASC");
$stmt->execute([$org['id']]);
$testimonios = $stmt->fetchAll(PDO::FETCH_ASSOC);

$e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php renderSeoHead($org, 'index', $seoConfig); ?>
<script>window.COOLKI_CONFIG = { org: <?= json_encode($orgSlug) ?> };</script>
</head>
<body style="margin:0;">
<style>
@import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=DM+Sans:wght@400;500;600;700&display=swap');

.coolki-b2c, .coolki-b2c * { box-sizing: border-box; }

.coolki-b2c {
  --bg: #F7F8FC;
  --card: #FFFFFF;
  --ink: #16182B;
  --muted: #6B6F85;
  --border: #E7E9F5;
  --brand: #2454FF;
  --brand-soft: #E8EEFF;
  --mint: #1FAE7A;
  --mint-soft: #DFF6EA;

  font-family: 'DM Sans', sans-serif;
  color: var(--ink);
  background: var(--bg);
  min-height: 100vh;
}

.wrap { max-width: 1040px; margin: 0 auto; padding: 0 24px; }
.nav { display: flex; align-items: center; justify-content: space-between; padding: 24px 0; position: relative; }
.logo { display: flex; align-items: center; gap: 10px; }
.logo-word { font-family: 'Space Grotesk', sans-serif; font-weight: 700; font-size: 19px; }
.nav-right { display: flex; align-items: center; gap: 16px; }
.nav-menu { display: flex; align-items: center; gap: 22px; margin: 0 24px; }
.nav-menu a { font-size: 13.5px; font-weight: 600; color: var(--muted); text-decoration: none; }
.nav-menu a:hover { color: var(--brand); }
.nav-login { font-size: 13.5px; font-weight: 600; color: var(--muted); text-decoration: none; }
.nav-login:hover { color: var(--brand); }
.nav-login-mobile { display: none; }
.nav-cta { padding: 10px 18px; border-radius: 10px; background: var(--brand); color: #fff; font-weight: 600; font-size: 13.5px; border: none; cursor: pointer; text-decoration: none; display: inline-block; }
.nav-toggle { display: none; background: none; border: none; cursor: pointer; padding: 6px; }
.nav-toggle span { display: block; width: 22px; height: 2px; background: var(--ink); margin: 5px 0; border-radius: 2px; }

.org-pill {
  display: inline-flex; align-items: center; gap: 8px; font-size: 12.5px; font-weight: 600;
  color: var(--brand); background: var(--brand-soft); padding: 6px 14px; border-radius: 20px; margin-bottom: 22px;
}

.hero { padding: 40px 0 50px; text-align: center; }
.hero h1 { font-family: 'Space Grotesk', sans-serif; font-weight: 700; font-size: clamp(34px, 5.5vw, 54px); line-height: 1.08; letter-spacing: -0.02em; margin: 0 0 18px; max-width: 680px; margin-inline: auto; }
.hero p { font-size: 17px; color: var(--muted); max-width: 480px; margin: 0 auto 30px; line-height: 1.6; }
.btn-primary { padding: 15px 28px; border-radius: 13px; background: var(--brand); color: #fff; font-weight: 700; font-size: 15px; border: none; cursor: pointer; font-family: 'Space Grotesk', sans-serif; text-decoration: none; display: inline-block; }
.hero-note { font-size: 12.5px; color: var(--muted); margin-top: 14px; }

.preview-wrap { display: flex; justify-content: center; margin: 46px 0 20px; }
.mini-card { width: 300px; background: var(--brand); color: #fff; border-radius: 22px; padding: 26px 22px; box-shadow: 0 24px 48px -22px rgba(36,84,255,0.5); }
.mini-label { font-size: 12.5px; opacity: 0.85; }
.mini-balance { font-family: 'Space Grotesk', sans-serif; font-weight: 700; font-size: 32px; margin: 6px 0 4px; }
.mini-sub { font-size: 12px; opacity: 0.85; margin-bottom: 16px; }
.mini-tag { display: inline-block; font-size: 11.5px; background: rgba(255,255,255,0.2); padding: 4px 11px; border-radius: 20px; }

.section-title { font-family: 'Space Grotesk', sans-serif; font-weight: 700; font-size: 24px; text-align: center; margin: 0 0 30px; }

.como-funciona { padding: 50px 0; }
.steps-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
.step-card { background: var(--card); border: 1px solid var(--border); border-radius: 18px; padding: 26px 22px; text-align: center; }
.step-icon { width: 46px; height: 46px; border-radius: 14px; background: var(--brand-soft); display: flex; align-items: center; justify-content: center; font-size: 22px; margin: 0 auto 14px; }
.step-title { font-weight: 700; font-size: 15.5px; margin-bottom: 8px; }
.step-desc { font-size: 13.5px; color: var(--muted); line-height: 1.55; }

.benefits { padding: 50px 0; }
.benefit-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
.benefit-card { background: var(--card); border: 1px solid var(--border); border-radius: 18px; padding: 26px 22px; }
.benefit-icon { width: 42px; height: 42px; border-radius: 12px; background: var(--brand-soft); color: var(--brand); display: flex; align-items: center; justify-content: center; font-size: 19px; margin-bottom: 14px; }
.benefit-title { font-weight: 700; font-size: 15.5px; margin-bottom: 8px; }
.benefit-desc { font-size: 13.5px; color: var(--muted); line-height: 1.55; }

.trust-strip { background: var(--card); border: 1px solid var(--border); border-radius: 18px; padding: 28px 30px; display: flex; justify-content: space-between; gap: 20px; flex-wrap: wrap; margin-bottom: 50px; }
.trust-item { flex: 1; min-width: 150px; }
.trust-num { font-family: 'Space Grotesk', sans-serif; font-weight: 700; font-size: 26px; color: var(--brand); }
.trust-label { font-size: 12.5px; color: var(--muted); margin-top: 4px; }

.testimonios { padding: 50px 0; }
.testimonios-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
.testimonio-card { background: var(--card); border: 1px solid var(--border); border-radius: 18px; padding: 22px; }
.testimonio-head { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
.testimonio-photo { width: 44px; height: 44px; border-radius: 50%; object-fit: cover; background: var(--brand-soft); }
.testimonio-photo-fallback { width: 44px; height: 44px; border-radius: 50%; background: var(--brand-soft); color: var(--brand); display: flex; align-items: center; justify-content: center; font-weight: 700; font-family: 'Space Grotesk', sans-serif; }
.testimonio-name { font-weight: 700; font-size: 14px; }
.testimonio-city { font-size: 12px; color: var(--muted); }
.testimonio-text { font-size: 13.5px; color: var(--ink); line-height: 1.6; }

.seguridad { padding: 50px 0; }
.seguridad-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
.seguridad-item { display: flex; gap: 12px; align-items: flex-start; }
.seguridad-icon { font-size: 20px; }
.seguridad-text b { display: block; font-size: 14px; margin-bottom: 3px; }
.seguridad-text span { font-size: 13px; color: var(--muted); line-height: 1.5; }

.faq { padding-bottom: 50px; }
.faq-item { background: var(--card); border: 1px solid var(--border); border-radius: 14px; padding: 18px 20px; margin-bottom: 12px; }
.faq-q { font-weight: 700; font-size: 14px; margin-bottom: 6px; }
.faq-a { font-size: 13.5px; color: var(--muted); line-height: 1.55; }

.final-cta { text-align: center; padding: 20px 0 50px; }
.final-cta h2 { font-family: 'Space Grotesk', sans-serif; font-size: 26px; margin: 0 0 10px; letter-spacing: -0.01em; }
.final-cta p { color: var(--muted); margin-bottom: 22px; }

.site-footer { border-top: 1px solid var(--border); padding: 30px 0 40px; }
.footer-social { display: flex; justify-content: center; gap: 14px; margin-bottom: 16px; }
.footer-social-link { width: 36px; height: 36px; border-radius: 50%; background: var(--brand-soft); color: var(--brand); display: flex; align-items: center; justify-content: center; text-decoration: none; }
.footer-links { text-align: center; font-size: 12.5px; margin-bottom: 12px; }
.footer-links a { color: var(--muted); text-decoration: none; }
.footer-links a:hover { color: var(--brand); }
.legal-footnote { font-size: 11.5px; color: var(--muted); line-height: 1.7; text-align: center; }

@media (max-width: 720px) {
  .benefit-grid, .steps-grid, .testimonios-grid, .seguridad-grid { grid-template-columns: 1fr; }
  .trust-strip { flex-direction: column; }
  .nav-menu, .nav-login { display: none; }
  .nav-toggle { display: block; }
  .nav-menu.nav-menu-open {
    display: flex; flex-direction: column; position: absolute; top: 100%; left: 0; right: 0;
    background: var(--card); border: 1px solid var(--border); border-radius: 14px; padding: 16px; gap: 14px; margin: 8px 0 0; z-index: 20;
  }
  .nav-menu.nav-menu-open a { display: block; }
}
</style>

<div class="coolki-b2c">
  <div class="wrap">
    <div class="nav" data-section="nav" data-pinned="true">
      <div class="logo">
        <svg width="30" height="30" viewBox="0 0 100 100"><rect width="100" height="100" rx="26" fill="#2454FF"/><circle cx="36" cy="40" r="5" fill="white"/><circle cx="64" cy="40" r="5" fill="white"/><path d="M28 58 Q50 78 72 58" stroke="white" stroke-width="7" fill="none" stroke-linecap="round"/></svg>
        <div class="logo-word"><?= $e($org['nombre']) ?></div>
      </div>
      <nav class="nav-menu" id="navMenu">
        <a href="#como-funciona">Cómo funciona</a>
        <?php if ($testimonios): ?><a href="#testimonios">Testimonios</a><?php endif; ?>
        <a href="#faq">Preguntas frecuentes</a>
        <a href="sistema" class="nav-login-mobile" id="navLoginMobile">Ya tengo cuenta</a>
      </nav>
      <div class="nav-right">
        <a class="nav-login" id="navLogin" href="sistema">Ya tengo cuenta</a>
        <a class="nav-cta" id="navCta" href="registro">Abrir mi cuenta</a>
        <button class="nav-toggle" id="navToggle" aria-label="Abrir menú" aria-expanded="false">
          <span></span><span></span><span></span>
        </button>
      </div>
    </div>

    <div class="hero" data-section="hero">
      <span class="org-pill">✦ Tu caja de ahorro digital</span>
      <h1>Ahorra sin darte cuenta, retira cuando quieras</h1>
      <p>Aporta desde $5, deja crecer tu dinero a plazo fijo, y retíralo cuando lo necesites.</p>
      <a class="btn-primary" id="heroCta" href="registro">Abrir mi cuenta gratis</a>
      <div class="hero-note">Toma menos de 2 minutos</div>
    </div>

    <div class="preview-wrap" data-section="preview">
      <div class="mini-card">
        <div class="mini-label">Saldo disponible</div>
        <div class="mini-balance">$119.50</div>
        <div class="mini-sub">Ejemplo ilustrativo</div>
        <span class="mini-tag">Plazo fijo 5.0% anual</span>
      </div>
    </div>

    <div class="como-funciona" data-section="como-funciona" id="como-funciona">
      <h2 class="section-title">Cómo funciona</h2>
      <div class="steps-grid">
        <?php foreach ($pasos as $paso): ?>
        <div class="step-card">
          <div class="step-icon"><?= $e($paso['icono']) ?></div>
          <div class="step-title"><?= $e($paso['titulo']) ?></div>
          <div class="step-desc"><?= $e($paso['descripcion']) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="benefits" data-section="benefits">
      <div class="benefit-grid">
        <div class="benefit-card"><div class="benefit-icon">＄</div><div class="benefit-title">Empieza con $5</div><div class="benefit-desc">Sin mínimos complicados. Abres tu cuenta y aportas lo que puedas, cuando puedas.</div></div>
        <div class="benefit-card"><div class="benefit-icon">%</div><div class="benefit-title">5% de rendimiento anual</div><div class="benefit-desc">Deja tu ahorro a plazo fijo y velo crecer, sin mover un dedo.</div></div>
        <div class="benefit-card"><div class="benefit-icon">↩</div><div class="benefit-title">Retira desde $10</div><div class="benefit-desc">Es tu dinero. Solicita tu retiro cuando lo necesites, sin letra chica.</div></div>
      </div>
    </div>

    <div class="trust-strip" data-section="trust">
      <div class="trust-item"><div class="trust-num">$5</div><div class="trust-label">Aporte inicial para activar tu cuenta</div></div>
      <div class="trust-item"><div class="trust-num">5%</div><div class="trust-label">Rendimiento anual a plazo fijo</div></div>
      <div class="trust-item"><div class="trust-num">$10</div><div class="trust-label">Monto mínimo de retiro</div></div>
    </div>

    <?php if ($testimonios): ?>
    <div class="testimonios" data-section="testimonios" id="testimonios">
      <h2 class="section-title">Lo que dicen nuestros socios</h2>
      <div class="testimonios-grid">
        <?php foreach ($testimonios as $t): ?>
        <div class="testimonio-card">
          <div class="testimonio-head">
            <?php if ($t['foto_url']): ?>
              <img class="testimonio-photo" src="<?= $e($t['foto_url']) ?>" alt="">
            <?php else: ?>
              <div class="testimonio-photo-fallback"><?= $e(mb_strtoupper(mb_substr($t['nombre'], 0, 1))) ?></div>
            <?php endif; ?>
            <div>
              <div class="testimonio-name"><?= $e($t['nombre']) ?></div>
              <?php if ($t['ciudad']): ?><div class="testimonio-city"><?= $e($t['ciudad']) ?></div><?php endif; ?>
            </div>
          </div>
          <div class="testimonio-text">“<?= $e($t['testimonio']) ?>”</div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="seguridad" data-section="seguridad" id="seguridad">
      <h2 class="section-title">Tu dinero y tus datos, protegidos</h2>
      <div class="seguridad-grid">
        <div class="seguridad-item"><div class="seguridad-icon">🔒</div><div class="seguridad-text"><b>Contraseñas cifradas</b><span>Nunca guardamos tu contraseña en texto plano.</span></div></div>
        <div class="seguridad-item"><div class="seguridad-icon">💳</div><div class="seguridad-text"><b>Pagos con PayPhone</b><span>Tus aportes y retiros se procesan a través de una pasarela de pago verificada.</span></div></div>
        <div class="seguridad-item"><div class="seguridad-icon">🛡️</div><div class="seguridad-text"><b>Acceso protegido</b><span>Tu cuenta se bloquea temporalmente tras varios intentos fallidos de inicio de sesión.</span></div></div>
      </div>
    </div>

    <div class="faq" data-section="faq" id="faq">
      <h2 class="section-title">Preguntas frecuentes</h2>
      <div class="faq-item"><div class="faq-q">¿Quién puede abrir una cuenta?</div><div class="faq-a">Cualquier persona puede abrir su cuenta de ahorro en <?= $e($org['nombre']) ?> en un par de minutos.</div></div>
      <div class="faq-item"><div class="faq-q">¿Dónde queda mi dinero?</div><div class="faq-a">Tu dinero llega directo a la cuenta de <?= $e($org['nombre']) ?> en PayPhone. La app es la herramienta que usas para verlo y moverlo.</div></div>
      <div class="faq-item"><div class="faq-q">¿Puedo retirar cuando quiera?</div><div class="faq-a">Sí, desde $10, solicitándolo directamente desde la app. Se procesa en 1–2 días hábiles.</div></div>
    </div>

    <div class="final-cta" data-section="final-cta">
      <h2>Tu ahorro, a un clic de distancia</h2>
      <p>Abre tu cuenta y empieza a ahorrar con <?= $e($org['nombre']) ?>.</p>
      <a class="btn-primary" id="finalCta" href="registro">Abrir mi cuenta gratis</a>
    </div>

    <div class="site-footer" data-section="footer" data-pinned="true">
      <div class="footer-social">
        <a id="footerInstagram" class="footer-social-link" href="#" target="_blank" rel="noopener" style="display:none" aria-label="Instagram">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1"/></svg>
        </a>
        <a id="footerTiktok" class="footer-social-link" href="#" target="_blank" rel="noopener" style="display:none" aria-label="TikTok">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor"><path d="M15 3c.3 2 1.7 3.6 4 3.9v3c-1.4 0-2.8-.4-4-1.2v6.4c0 3.3-2.7 5.9-6 5.9s-6-2.6-6-5.9 2.7-5.9 6-5.9c.3 0 .6 0 .9.1v3.1c-.3-.1-.6-.1-.9-.1-1.6 0-2.9 1.3-2.9 2.9s1.3 2.9 2.9 2.9 2.9-1.3 2.9-2.9V3h3.1z"/></svg>
        </a>
        <a id="footerFacebook" class="footer-social-link" href="#" target="_blank" rel="noopener" style="display:none" aria-label="Facebook">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor"><path d="M14 9h3V6h-3c-2.2 0-4 1.8-4 4v2H8v3h2v6h3v-6h3l1-3h-4v-2c0-.6.4-1 1-1z"/></svg>
        </a>
      </div>
      <div class="footer-links">
        <a href="terminos<?= $orgSlug ? '?org=' . urlencode($orgSlug) : '' ?>">Términos y condiciones</a> ·
        <a href="privacidad<?= $orgSlug ? '?org=' . urlencode($orgSlug) : '' ?>">Política de privacidad</a>
      </div>
      <div class="legal-footnote">
        <?= $e($org['nombre']) ?> es un servicio de ahorro digital. Los aportes y retiros se procesan a través de PayPhone.
      </div>
    </div>
  </div>
</div>

<script>
  // El slug de la organización no está hardcodeado en la lógica: se toma de la URL
  // (?org=coolki) o de window.COOLKI_CONFIG (definido arriba). Se propaga a los
  // enlaces de "Abrir mi cuenta" / "Ya tengo cuenta" para que lleguen a la caja correcta.
  const CONFIG = window.COOLKI_CONFIG || {};
  const PARAMS = new URLSearchParams(location.search);
  const ORG_SLUG = (PARAMS.get('org') || CONFIG.org || '').trim();

  if (ORG_SLUG) {
    const qs = '?org=' + encodeURIComponent(ORG_SLUG);
    ['navCta', 'heroCta', 'finalCta'].forEach(id => {
      document.getElementById(id).href = 'registro' + qs;
    });
    document.getElementById('navLogin').href = 'sistema' + qs;
    document.getElementById('navLoginMobile').href = 'sistema' + qs;
  }

  const navToggle = document.getElementById('navToggle');
  const navMenu = document.getElementById('navMenu');
  navToggle.addEventListener('click', () => {
    const abierto = navMenu.classList.toggle('nav-menu-open');
    navToggle.setAttribute('aria-expanded', abierto ? 'true' : 'false');
  });
</script>
<script src="builder-runtime.js" data-pagina="index"></script>
<script src="analytics-consent.js"></script>

</body>
</html>
