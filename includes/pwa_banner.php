<!-- PWA Install Banner (Vanilla JS — Mobile-First) -->
<style>
  @keyframes pwa-slide-up {
    from { transform: translateY(100%); opacity: 0; }
    to   { transform: translateY(0);    opacity: 1; }
  }
  @keyframes pwa-slide-down {
    from { transform: translateY(0);    opacity: 1; }
    to   { transform: translateY(100%); opacity: 0; }
  }
  @keyframes pwa-pulse {
    0%, 100% { box-shadow: 0 2px 12px rgba(0,200,212,0.3); }
    50%      { box-shadow: 0 2px 20px rgba(0,200,212,0.55); }
  }
  #pwa-install-banner {
    display: none;
    position: fixed;
    bottom: 84px; /* Above bottom-nav on mobile */
    left: 0;
    right: 0;
    z-index: 99999;
    padding: 0 12px 12px;
    pointer-events: auto;
  }
  @media(min-width: 769px) {
    #pwa-install-banner {
      bottom: 0; /* Desktop: no bottom-nav */
    }
  }
  #pwa-install-banner.pwa-show {
    display: block;
    animation: pwa-slide-up 0.4s cubic-bezier(0.16,1,0.3,1) forwards;
  }
  #pwa-install-banner.pwa-hide {
    animation: pwa-slide-down 0.35s cubic-bezier(0.7,0,0.84,0) forwards;
  }
  .pwa-banner-content {
    background: linear-gradient(135deg, #1a2332 0%, #0f1923 100%);
    border: 1px solid rgba(0, 200, 212, 0.35);
    border-radius: 16px;
    padding: 14px 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: 0 -4px 30px rgba(0,0,0,0.35), 0 0 20px rgba(0,200,212,0.08);
  }
  .pwa-banner-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    flex-shrink: 0;
  }
  .pwa-banner-text-wrap {
    flex: 1;
    min-width: 0;
  }
  .pwa-banner-title {
    font-family: 'Segoe UI', system-ui, sans-serif;
    font-weight: 700;
    font-size: 14px;
    color: #ffffff;
    line-height: 1.3;
  }
  .pwa-banner-desc {
    font-family: 'Segoe UI', system-ui, sans-serif;
    font-size: 12px;
    color: rgba(255,255,255,0.55);
    line-height: 1.4;
    margin-top: 2px;
  }
  .pwa-install-btn {
    background: linear-gradient(135deg, #00c8d4, #00a5b4);
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: 9px 18px;
    font-family: 'Segoe UI', system-ui, sans-serif;
    font-weight: 700;
    font-size: 13px;
    cursor: pointer;
    white-space: nowrap;
    flex-shrink: 0;
    transition: transform 0.15s;
    animation: pwa-pulse 2s ease-in-out infinite;
  }
  .pwa-install-btn:active {
    transform: scale(0.95);
  }
  .pwa-dismiss-btn {
    background: none;
    border: none;
    color: rgba(255,255,255,0.35);
    cursor: pointer;
    padding: 4px;
    font-size: 18px;
    line-height: 1;
    flex-shrink: 0;
    transition: color 0.15s;
  }
  .pwa-dismiss-btn:hover,
  .pwa-dismiss-btn:active {
    color: rgba(255,255,255,0.7);
  }
  /* Fallback menu instructions overlay */
  .pwa-menu-instructions {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    z-index: 100000;
    background: rgba(0,0,0,0.75);
    backdrop-filter: blur(4px);
    padding: 20px;
    align-items: center;
    justify-content: center;
  }
  .pwa-menu-instructions.visible {
    display: flex;
  }
  .pwa-menu-box {
    background: #1a2332;
    border: 1px solid rgba(0,200,212,0.3);
    border-radius: 20px;
    padding: 28px 24px;
    max-width: 340px;
    width: 100%;
    text-align: center;
    color: white;
    box-shadow: 0 20px 60px rgba(0,0,0,0.5);
  }
  .pwa-menu-box h3 {
    font-size: 16px;
    font-weight: 800;
    margin-bottom: 16px;
    color: #00c8d4;
  }
  .pwa-step {
    display: flex;
    align-items: center;
    gap: 12px;
    text-align: left;
    padding: 10px 0;
    border-bottom: 1px solid rgba(255,255,255,0.06);
  }
  .pwa-step:last-child {
    border-bottom: none;
  }
  .pwa-step-num {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: rgba(0,200,212,0.15);
    color: #00c8d4;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 13px;
    flex-shrink: 0;
  }
  .pwa-step-text {
    font-size: 13px;
    color: rgba(255,255,255,0.8);
    line-height: 1.4;
  }
  .pwa-menu-close {
    margin-top: 18px;
    background: linear-gradient(135deg, #00c8d4, #00a5b4);
    color: white;
    border: none;
    border-radius: 12px;
    padding: 12px 32px;
    font-weight: 700;
    font-size: 14px;
    cursor: pointer;
    transition: transform 0.15s;
  }
  .pwa-menu-close:active {
    transform: scale(0.95);
  }
</style>

<div id="pwa-install-banner">
  <div class="pwa-banner-content">
    <img src="<?= BASE_URL ?>assets/pwa/pwa-icon-192x192.png" alt="Absensi" class="pwa-banner-icon" />
    <div class="pwa-banner-text-wrap">
      <div class="pwa-banner-title">Install Absensi MAN2</div>
      <div class="pwa-banner-desc" id="pwa-banner-desc">
        Akses lebih cepat dari layar utama
      </div>
    </div>
    <button class="pwa-install-btn" id="pwa-install-btn">Install</button>
    <button class="pwa-dismiss-btn" id="pwa-dismiss-btn" aria-label="Tutup">✕</button>
  </div>
</div>

<!-- Manual install instructions overlay (fallback when beforeinstallprompt not available) -->
<div class="pwa-menu-instructions" id="pwa-menu-overlay">
  <div class="pwa-menu-box">
    <h3>📲 Install Aplikasi</h3>
    <div class="pwa-step">
      <div class="pwa-step-num">1</div>
      <div class="pwa-step-text">Ketuk ikon <strong style="color:white">⋮</strong> (titik tiga) di pojok kanan atas Chrome</div>
    </div>
    <div class="pwa-step">
      <div class="pwa-step-num">2</div>
      <div class="pwa-step-text">Pilih <strong style="color:#00c8d4">"Install app"</strong> atau <strong style="color:#00c8d4">"Add to Home Screen"</strong></div>
    </div>
    <div class="pwa-step">
      <div class="pwa-step-num">3</div>
      <div class="pwa-step-text">Ketuk <strong style="color:white">Install</strong> pada dialog konfirmasi</div>
    </div>
    <button class="pwa-menu-close" id="pwa-menu-close">Mengerti</button>
  </div>
</div>

<script>
(function() {
  var DISMISS_KEY = 'pwa-install-dismissed';
  var DISMISS_DAYS = 7;
  var banner = document.getElementById('pwa-install-banner');
  var btnInstall = document.getElementById('pwa-install-btn');
  var btnDismiss = document.getElementById('pwa-dismiss-btn');
  var descEl = document.getElementById('pwa-banner-desc');
  var menuOverlay = document.getElementById('pwa-menu-overlay');
  var menuClose = document.getElementById('pwa-menu-close');
  var deferredPrompt = null;

  function isDismissed() {
    try {
      var val = localStorage.getItem(DISMISS_KEY);
      if (!val) return false;
      return (Date.now() - parseInt(val, 10)) / (1000 * 60 * 60 * 24) < DISMISS_DAYS;
    } catch(e) { return false; }
  }

  function isStandalone() {
    return window.matchMedia('(display-mode: standalone)').matches ||
           window.navigator.standalone === true ||
           document.referrer.includes('android-app://');
  }

  function isMobile() {
    return /android|iphone|ipad|ipod|mobile/i.test(navigator.userAgent);
  }

  function isIOS() {
    return /iphone|ipad|ipod/i.test(navigator.userAgent);
  }

  function isSafari() {
    return /safari/i.test(navigator.userAgent) && !/chrome|crios|fxios/i.test(navigator.userAgent);
  }

  function showBanner() {
    banner.classList.remove('pwa-hide');
    banner.classList.add('pwa-show');
  }

  function hideBanner() {
    banner.classList.remove('pwa-show');
    banner.classList.add('pwa-hide');
    setTimeout(function() { banner.style.display = 'none'; }, 400);
  }

  // Don't show if already installed or recently dismissed
  if (isStandalone() || isDismissed()) return;

  // iOS Safari path
  if (isIOS() || isSafari()) {
    descEl.innerHTML = 'Ketuk <span style="display:inline-flex;align-items:center;vertical-align:middle;background:rgba(0,200,212,0.15);border-radius:4px;padding:1px 5px;margin:0 2px;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#00c8d4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle"><path d="M4 12v8a2 2 0 002 2h12a2 2 0 002-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg></span> lalu <strong style="color:#fff">"Add to Home Screen"</strong>';
    btnInstall.textContent = 'OK';
    btnInstall.style.animation = 'none';
    setTimeout(showBanner, 2000);
    btnInstall.addEventListener('click', function() {
      hideBanner();
      try { localStorage.setItem(DISMISS_KEY, Date.now().toString()); } catch(e) {}
    });
    btnDismiss.addEventListener('click', function() {
      try { localStorage.setItem(DISMISS_KEY, Date.now().toString()); } catch(e) {}
      hideBanner();
    });
    return;
  }

  // ─── Android / Chrome path ────────────────────────────────
  // Pick up early-captured event from index.html
  if (window.__pwaInstallEvent) {
    deferredPrompt = window.__pwaInstallEvent;
  }

  // Also listen for late events
  window.__pwaInstallCallbacks = window.__pwaInstallCallbacks || [];
  window.__pwaInstallCallbacks.push(function(e) {
    deferredPrompt = e;
  });
  window.addEventListener('beforeinstallprompt', function(e) {
    e.preventDefault();
    deferredPrompt = e;
  });

  // KEY FIX: Show banner after 3 seconds on mobile REGARDLESS of
  // whether beforeinstallprompt has fired. We show the banner always,
  // and handle the "no prompt available" case with manual instructions.
  if (isMobile()) {
    setTimeout(showBanner, 3000);
  } else {
    // Desktop: only show if beforeinstallprompt is available
    if (deferredPrompt) {
      showBanner();
    } else {
      window.__pwaInstallCallbacks.push(function() { showBanner(); });
    }
  }

  // Listen for successful install
  window.addEventListener('appinstalled', function() {
    hideBanner();
    deferredPrompt = null;
    window.__pwaInstallEvent = null;
  });

  // Install button click
  btnInstall.addEventListener('click', function() {
    var prompt = deferredPrompt || window.__pwaInstallEvent;
    if (prompt) {
      // Native install dialog available!
      prompt.prompt();
      prompt.userChoice.then(function(choiceResult) {
        if (choiceResult.outcome === 'accepted') {
          hideBanner();
        }
        deferredPrompt = null;
        window.__pwaInstallEvent = null;
      }).catch(function(err) {
        console.warn('[PWA] Install prompt error:', err);
        // Fallback to manual instructions
        menuOverlay.classList.add('visible');
      });
    } else {
      // No native prompt — show manual instructions
      menuOverlay.classList.add('visible');
    }
  });

  // Dismiss button
  btnDismiss.addEventListener('click', function() {
    try { localStorage.setItem(DISMISS_KEY, Date.now().toString()); } catch(e) {}
    hideBanner();
  });

  // Close manual instructions overlay
  menuClose.addEventListener('click', function() {
    menuOverlay.classList.remove('visible');
    try { localStorage.setItem(DISMISS_KEY, Date.now().toString()); } catch(e) {}
    hideBanner();
  });

  // Close overlay on background click
  menuOverlay.addEventListener('click', function(e) {
    if (e.target === menuOverlay) {
      menuOverlay.classList.remove('visible');
    }
  });
})();
</script>
