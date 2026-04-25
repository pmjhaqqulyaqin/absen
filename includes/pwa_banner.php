<!-- PWA Install Banner (Vanilla JS Implementation) -->
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
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 99999;
    padding: 0 12px 12px;
    pointer-events: auto;
  }
  #pwa-install-banner.pwa-banner-enter {
    display: block;
    animation: pwa-slide-up 0.4s cubic-bezier(0.16,1,0.3,1) forwards;
  }
  #pwa-install-banner.pwa-banner-exit {
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
    font-family: 'Inter', system-ui, sans-serif;
    font-weight: 600;
    font-size: 14px;
    color: #ffffff;
    line-height: 1.3;
  }
  .pwa-banner-desc {
    font-family: 'Inter', system-ui, sans-serif;
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
    font-family: 'Inter', system-ui, sans-serif;
    font-weight: 600;
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
  .pwa-dismiss-btn:hover {
    color: rgba(255,255,255,0.7);
  }
</style>

<div id="pwa-install-banner">
  <div class="pwa-banner-content">
    <img src="<?= BASE_URL ?>assets/pwa/pwa-icon-192x192.png" alt="MAN 2 Lotim" class="pwa-banner-icon" />
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

<script>
document.addEventListener('DOMContentLoaded', function() {
  const DISMISS_KEY = 'pwa-install-dismissed';
  const DISMISS_DAYS = 7;
  const banner = document.getElementById('pwa-install-banner');
  const btnInstall = document.getElementById('pwa-install-btn');
  const btnDismiss = document.getElementById('pwa-dismiss-btn');
  const descEl = document.getElementById('pwa-banner-desc');
  let deferredPrompt = null;

  function isDismissed() {
    try {
      const val = localStorage.getItem(DISMISS_KEY);
      if (!val) return false;
      return (Date.now() - parseInt(val, 10)) / (1000 * 60 * 60 * 24) < DISMISS_DAYS;
    } catch(e) { return false; }
  }

  function isStandalone() {
    return window.matchMedia('(display-mode: standalone)').matches ||
           window.navigator.standalone === true ||
           document.referrer.includes('android-app://');
  }

  function isIOS() {
    return /iphone|ipad|ipod/i.test(navigator.userAgent);
  }

  function isSafari() {
    return /safari/i.test(navigator.userAgent) && !/chrome|crios|fxios/i.test(navigator.userAgent);
  }

  function showBanner() {
    banner.classList.remove('pwa-banner-exit');
    banner.classList.add('pwa-banner-enter');
  }

  function hideBanner() {
    banner.classList.remove('pwa-banner-enter');
    banner.classList.add('pwa-banner-exit');
    setTimeout(() => { banner.style.display = 'none'; }, 400);
  }

  if (isStandalone() || isDismissed()) return;

  const isIosDevice = isIOS() || isSafari();

  if (isIosDevice) {
    descEl.innerHTML = `Ketuk <span style="display:inline-flex;align-items:center;vertical-align:middle;background:rgba(0,200,212,0.15);border-radius:4px;padding:1px 5px;margin:0 2px;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#00c8d4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle"><path d="M4 12v8a2 2 0 002 2h12a2 2 0 002-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg></span> lalu <strong style="color:#fff">"Add to Home Screen"</strong>`;
    btnInstall.textContent = 'OK';
    btnInstall.style.animation = 'none';
    setTimeout(showBanner, 2000);
  } else {
    // Check if event was captured early
    if (window.__pwaInstallEvent) {
      console.log('[PWA Banner] Found early-captured install event');
      deferredPrompt = window.__pwaInstallEvent;
      showBanner();
    } else {
      // Setup callback
      window.__pwaInstallCallbacks = window.__pwaInstallCallbacks || [];
      window.__pwaInstallCallbacks.push(function(e) {
        console.log('[PWA Banner] Received install event via callback');
        deferredPrompt = e;
        showBanner();
      });
      // Belt and suspenders listener
      window.addEventListener('beforeinstallprompt', function(e) {
        e.preventDefault();
        deferredPrompt = e;
        showBanner();
      });
    }
  }

  window.addEventListener('appinstalled', function() {
    hideBanner();
    deferredPrompt = null;
    window.__pwaInstallEvent = null;
  });

  btnInstall.addEventListener('click', function() {
    if (isIosDevice) {
      hideBanner();
      try { localStorage.setItem(DISMISS_KEY, Date.now().toString()); } catch(e) {}
      return;
    }
    const prompt = deferredPrompt || window.__pwaInstallEvent;
    if (!prompt) return;
    prompt.prompt();
    prompt.userChoice.then((choiceResult) => {
      if (choiceResult.outcome === 'accepted') {
        hideBanner();
      }
      deferredPrompt = null;
      window.__pwaInstallEvent = null;
    }).catch((err) => console.warn('[PWA] Install prompt error:', err));
  });

  btnDismiss.addEventListener('click', function() {
    try { localStorage.setItem(DISMISS_KEY, Date.now().toString()); } catch(e) {}
    hideBanner();
  });
});
</script>
