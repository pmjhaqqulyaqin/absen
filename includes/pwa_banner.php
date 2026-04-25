<!-- PWA Install Banner — TOP position (above content) -->
<style>
  @keyframes pwa-fade-in {
    from { opacity: 0; transform: translateY(-100%); }
    to   { opacity: 1; transform: translateY(0); }
  }
  @keyframes pwa-fade-out {
    from { opacity: 1; transform: translateY(0); }
    to   { opacity: 0; transform: translateY(-100%); }
  }
  @keyframes pwa-pulse {
    0%, 100% { box-shadow: 0 2px 12px rgba(0,200,212,0.3); }
    50%      { box-shadow: 0 2px 20px rgba(0,200,212,0.55); }
  }
  #pwa-install-banner {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 99999;
    padding: 8px 10px;
    pointer-events: auto;
  }
  #pwa-install-banner.pwa-show {
    display: block;
    animation: pwa-fade-in 0.4s cubic-bezier(0.16,1,0.3,1) forwards;
  }
  #pwa-install-banner.pwa-hide {
    animation: pwa-fade-out 0.3s ease forwards;
  }
  .pwa-banner-inner {
    background: linear-gradient(135deg, #0d1b2a 0%, #1b2d45 100%);
    border: 1px solid rgba(0, 200, 212, 0.3);
    border-radius: 14px;
    padding: 10px 14px;
    display: flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.4), 0 0 12px rgba(0,200,212,0.06);
  }
  .pwa-banner-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    flex-shrink: 0;
  }
  .pwa-banner-text {
    flex: 1;
    min-width: 0;
  }
  .pwa-banner-title {
    font-family: 'Segoe UI', system-ui, sans-serif;
    font-weight: 700;
    font-size: 13px;
    color: #ffffff;
    line-height: 1.3;
  }
  .pwa-banner-desc {
    font-family: 'Segoe UI', system-ui, sans-serif;
    font-size: 11px;
    color: rgba(255,255,255,0.5);
    line-height: 1.3;
    margin-top: 1px;
  }
  .pwa-btn-install {
    background: linear-gradient(135deg, #00c8d4, #00a5b4);
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 8px 14px;
    font-family: 'Segoe UI', system-ui, sans-serif;
    font-weight: 700;
    font-size: 12px;
    cursor: pointer;
    white-space: nowrap;
    flex-shrink: 0;
    animation: pwa-pulse 2s ease-in-out infinite;
  }
  .pwa-btn-install:active { transform: scale(0.95); }
  .pwa-btn-close {
    background: none;
    border: none;
    color: rgba(255,255,255,0.3);
    cursor: pointer;
    padding: 2px 4px;
    font-size: 16px;
    line-height: 1;
    flex-shrink: 0;
  }
  .pwa-btn-close:active { color: rgba(255,255,255,0.7); }
  /* Manual instructions overlay */
  .pwa-overlay {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 100000;
    background: rgba(0,0,0,0.7);
    backdrop-filter: blur(4px);
    align-items: center;
    justify-content: center;
    padding: 20px;
  }
  .pwa-overlay.visible { display: flex; }
  .pwa-overlay-box {
    background: #1a2332;
    border: 1px solid rgba(0,200,212,0.3);
    border-radius: 18px;
    padding: 24px 20px;
    max-width: 320px;
    width: 100%;
    text-align: center;
    color: white;
  }
  .pwa-overlay-box h3 {
    font-size: 15px;
    font-weight: 800;
    margin-bottom: 14px;
    color: #00c8d4;
  }
  .pwa-ol-step {
    display: flex;
    align-items: center;
    gap: 10px;
    text-align: left;
    padding: 8px 0;
    border-bottom: 1px solid rgba(255,255,255,0.06);
  }
  .pwa-ol-step:last-of-type { border-bottom: none; }
  .pwa-ol-num {
    width: 24px; height: 24px;
    border-radius: 50%;
    background: rgba(0,200,212,0.15);
    color: #00c8d4;
    display: flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: 12px; flex-shrink: 0;
  }
  .pwa-ol-txt { font-size: 12px; color: rgba(255,255,255,0.75); line-height: 1.4; }
  .pwa-overlay-close {
    margin-top: 16px;
    background: linear-gradient(135deg, #00c8d4, #00a5b4);
    color: white; border: none; border-radius: 10px;
    padding: 10px 28px; font-weight: 700; font-size: 13px; cursor: pointer;
  }
  .pwa-overlay-close:active { transform: scale(0.95); }
</style>

<div id="pwa-install-banner">
  <div class="pwa-banner-inner">
    <img src="<?= defined('BASE_URL') ? BASE_URL : '/' ?>assets/pwa/pwa-icon-192x192.png" alt="Absensi" class="pwa-banner-icon" />
    <div class="pwa-banner-text">
      <div class="pwa-banner-title" id="pwa-title">Install Absensi MAN2</div>
      <div class="pwa-banner-desc" id="pwa-desc">Akses cepat dari layar utama</div>
    </div>
    <button class="pwa-btn-install" id="pwa-btn-install">Install</button>
    <button class="pwa-btn-close" id="pwa-btn-close" aria-label="Tutup">✕</button>
  </div>
</div>

<div class="pwa-overlay" id="pwa-overlay">
  <div class="pwa-overlay-box">
    <h3>📲 Install Aplikasi</h3>
    <div class="pwa-ol-step">
      <div class="pwa-ol-num">1</div>
      <div class="pwa-ol-txt">Ketuk ikon <strong style="color:white">⋮</strong> (titik tiga) di pojok kanan atas Chrome</div>
    </div>
    <div class="pwa-ol-step">
      <div class="pwa-ol-num">2</div>
      <div class="pwa-ol-txt">Pilih <strong style="color:#00c8d4">"Install app"</strong> atau <strong style="color:#00c8d4">"Add to Home screen"</strong></div>
    </div>
    <div class="pwa-ol-step">
      <div class="pwa-ol-num">3</div>
      <div class="pwa-ol-txt">Ketuk <strong style="color:white">Install</strong> pada dialog</div>
    </div>
    <button class="pwa-overlay-close" id="pwa-overlay-close">Mengerti</button>
  </div>
</div>

<script>
(function() {
  var DK = 'pwa-install-dismissed', DD = 7;
  var banner = document.getElementById('pwa-install-banner');
  var btnI = document.getElementById('pwa-btn-install');
  var btnX = document.getElementById('pwa-btn-close');
  var overlay = document.getElementById('pwa-overlay');
  var overlayClose = document.getElementById('pwa-overlay-close');
  var descEl = document.getElementById('pwa-desc');
  var dp = null;

  if (!banner) return;

  function dismissed() {
    try { var v = localStorage.getItem(DK); if (!v) return false; return (Date.now()-parseInt(v,10))/864e5 < DD; }
    catch(e) { return false; }
  }
  function standalone() {
    return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
  }
  function mobile() { return /android|iphone|ipad|ipod|mobile/i.test(navigator.userAgent); }
  function ios() { return /iphone|ipad|ipod/i.test(navigator.userAgent); }
  function safari() { return /safari/i.test(navigator.userAgent) && !/chrome|crios|fxios/i.test(navigator.userAgent); }

  function show() { banner.classList.remove('pwa-hide'); banner.classList.add('pwa-show'); }
  function hide() { banner.classList.remove('pwa-show'); banner.classList.add('pwa-hide'); setTimeout(function(){banner.style.display='none';},350); }
  function dismiss() { try{localStorage.setItem(DK,Date.now().toString());}catch(e){} hide(); }

  if (standalone() || dismissed()) return;

  // Grab early-captured event
  if (window.__pwaInstallEvent) dp = window.__pwaInstallEvent;
  window.__pwaInstallCallbacks = window.__pwaInstallCallbacks || [];
  window.__pwaInstallCallbacks.push(function(e) { dp = e; });
  window.addEventListener('beforeinstallprompt', function(e) { e.preventDefault(); dp = e; });

  // iOS
  if (ios() || safari()) {
    descEl.innerHTML = 'Ketuk <strong style="color:#00c8d4">⬆</strong> lalu "Add to Home Screen"';
    btnI.textContent = 'OK';
    btnI.style.animation = 'none';
    btnI.onclick = dismiss;
  } else {
    // Install button: try native, fallback to manual instructions
    btnI.onclick = function() {
      var p = dp || window.__pwaInstallEvent;
      if (p) {
        p.prompt();
        p.userChoice.then(function(c) { if (c.outcome==='accepted') hide(); dp = null; window.__pwaInstallEvent = null; })
          .catch(function() { overlay.classList.add('visible'); });
      } else {
        overlay.classList.add('visible');
      }
    };
  }

  btnX.onclick = dismiss;
  overlayClose.onclick = function() { overlay.classList.remove('visible'); dismiss(); };
  overlay.onclick = function(e) { if (e.target===overlay) overlay.classList.remove('visible'); };
  window.addEventListener('appinstalled', function() { hide(); dp = null; });

  // ★ SHOW BANNER after 2 seconds — ALWAYS on mobile, on desktop only if prompt available
  if (mobile()) {
    setTimeout(show, 2000);
  } else if (dp) {
    show();
  } else {
    window.__pwaInstallCallbacks.push(function() { show(); });
  }
})();
</script>
