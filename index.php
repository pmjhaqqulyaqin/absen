<?php
require_once 'includes/config.php';

// Redirect jika sudah login admin
if (isset($_SESSION['admin_id'])) { header('Location: dashboard.php'); exit; }

$pengaturan = get_pengaturan();
$stats = get_stats_hari_ini();

$hari  = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
$bulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$tgl_indo = $hari[date('w')].', '.date('d').' '.$bulan[(int)date('n')].' '.date('Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1a2332">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Absensi MAN2">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="application-name" content="Absensi MAN2">
    <meta name="msapplication-TileColor" content="#1a2332">
    <meta name="msapplication-TileImage" content="assets/pwa/pwa-icon-192x192.png">

    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/pwa/pwa-icon-180x180.png">
    
    <script>
      window.__pwaInstallEvent = null;
      window.__pwaInstallCallbacks = [];
      window.addEventListener('beforeinstallprompt', function(e) {
        e.preventDefault();
        window.__pwaInstallEvent = e;
        console.log('[PWA] beforeinstallprompt captured early');
        window.__pwaInstallCallbacks.forEach(function(cb) { cb(e); });
      });

      if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
          navigator.serviceWorker.register('/sw.js')
            .then(function(reg) {
              console.log('[PWA] Service Worker registered, scope:', reg.scope);
              setInterval(function() { reg.update(); }, 60 * 60 * 1000);
            })
            .catch(function(err) {
              console.warn('[PWA] Service Worker registration failed:', err);
            });
        });
      }
    </script>
    <title><?= htmlspecialchars($pengaturan['nama_sekolah']) ?> - Absensi Digital</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/mobile-views.css?v=<?= time() ?>">
    <style>
    *{margin:0;padding:0;box-sizing:border-box}
    body{font-family:'Segoe UI',sans-serif;background:#0f172a;color:white;min-height:100vh}

    /* NAVBAR */
    .navbar{background:rgba(15,23,42,.96);backdrop-filter:blur(10px);border-bottom:1px solid rgba(255,255,255,.08);padding:0 24px;height:64px;display:flex;align-items:center;position:sticky;top:0;z-index:100;gap:12px}
    .navbar-brand{display:flex;align-items:center;gap:10px;text-decoration:none;color:white}
    .navbar-logo{width:38px;height:38px;border-radius:10px;object-fit:contain;background:white;padding:3px}
    .navbar-logo-icon{width:38px;height:38px;background:linear-gradient(135deg,#3b82f6,#0891b2);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.2rem}
    .navbar-title{font-weight:800;font-size:.95rem;line-height:1.2}
    .navbar-sub{font-size:.68rem;color:#94a3b8}
    .navbar-clock{margin-left:auto;font-size:1.3rem;font-weight:800;font-family:monospace;color:#38bdf8;letter-spacing:2px;white-space:nowrap}
    .btn-nav{padding:8px 16px;border-radius:10px;font-weight:700;font-size:.82rem;text-decoration:none;border:none;cursor:pointer;transition:.2s}
    .btn-nav:hover{opacity:.9;transform:translateY(-1px)}
    .btn-login-nav{background:linear-gradient(135deg,#f59e0b,#d97706);color:white;font-weight:800;letter-spacing:.5px}
    .btn-kepsek-nav{background:linear-gradient(135deg,#7c3aed,#5b21b6);color:white}
    .btn-disiplin-nav{background:linear-gradient(135deg,#f59e0b,#d97706);color:white}
    .btn-bk-nav{background:linear-gradient(135deg,#0e7490,#0891b2);color:white}
    @media(max-width:480px){.btn-kepsek-txt{display:none}.btn-disiplin-txt{display:none}.btn-bk-txt{display:none}}

    /* HERO */
    .hero{position:relative;overflow:hidden;min-height:300px}
    .hero-bg-grad{position:absolute;top:0;left:0;width:100%;height:100%;background:linear-gradient(135deg,#1e3a8a,#0891b2,#0f172a)}
    .hero-content{position:relative;z-index:2;padding:40px 32px;display:flex;align-items:center;gap:32px}
    .hero-left{flex:1;min-width:0}
    .hero-title{font-size:clamp(1.3rem,3.5vw,2rem);font-weight:900;margin-bottom:8px;text-align:left}
    .hero-sub{color:#cbd5e1;font-size:.9rem;margin-bottom:18px;text-align:left}
    .hero-date{background:rgba(0,0,0,.35);border:1px solid rgba(255,255,255,.12);display:inline-block;padding:7px 18px;border-radius:30px;font-size:.85rem;font-weight:600}
    .hero-right{display:flex;flex-direction:column;gap:10px;min-width:260px;max-width:320px}
    .portal-btn{display:block;padding:14px 20px;border-radius:12px;font-size:.95rem;font-weight:800;text-decoration:none;color:white;text-align:center;letter-spacing:.5px;transition:.2s;text-transform:uppercase;border:none;cursor:pointer}
    .portal-btn:hover{transform:translateY(-2px);filter:brightness(1.1);box-shadow:0 8px 20px rgba(0,0,0,.3)}
    .pb-disiplin{background:linear-gradient(135deg,#f59e0b,#d97706)}
    .pb-bk{background:linear-gradient(135deg,#06b6d4,#0891b2)}
    .pb-wali{background:linear-gradient(135deg,#7c3aed,#6d28d9)}
    .pb-siswa{background:rgba(99,102,241,.35);border:1px solid rgba(99,102,241,.5)}
    @media(max-width:680px){
        .hero-content{flex-direction:column;padding:28px 20px;text-align:center}
        .hero-title,.hero-sub{text-align:center}
        .hero-right{min-width:100%;max-width:100%}
    }

    /* STATS */
    .stats-bar{background:#1e293b;border-bottom:1px solid rgba(255,255,255,.06)}
    .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(90px,1fr));max-width:900px;margin:0 auto}
    .stat-item{padding:18px 8px;text-align:center;border-right:1px solid rgba(255,255,255,.06)}
    .stat-item:last-child{border-right:none}
    .stat-num{font-size:1.8rem;font-weight:900;line-height:1}
    .stat-lbl{font-size:.68rem;color:#94a3b8;margin-top:3px;font-weight:600;text-transform:uppercase;letter-spacing:.5px}
    .c-total{color:#38bdf8}.c-hadir{color:#4ade80}.c-terlambat{color:#fb923c}
    .c-alpa{color:#f87171}.c-sakit{color:#60a5fa}.c-izin{color:#c084fc}.c-bolos{color:#f472b6}

    /* LAYOUT */
    .main{max-width:980px;margin:0 auto;padding:32px 20px}
    .grid-scan{display:grid;grid-template-columns:1fr 1fr;gap:24px}
    @media(max-width:700px){.grid-scan{grid-template-columns:1fr}}

    /* SCANNER CARD */
    .card{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:18px;overflow:hidden}
    .card-header{padding:16px 20px;font-weight:700;font-size:.9rem;border-bottom:1px solid rgba(255,255,255,.08);display:flex;align-items:center;gap:8px}
    .card-body{padding:20px}

    .mode-btns{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:14px}
    .mode-btn{padding:10px;border:2px solid;border-radius:10px;font-weight:700;font-size:.85rem;cursor:pointer;transition:.2s;display:flex;align-items:center;justify-content:center;gap:6px}
    .mode-masuk{background:#16a34a;border-color:#16a34a;color:white}
    .mode-masuk.inactive{background:transparent;border-color:rgba(255,255,255,.15);color:#94a3b8}
    .mode-pulang{background:#dc2626;border-color:#dc2626;color:white}
    .mode-pulang.inactive{background:transparent;border-color:rgba(255,255,255,.15);color:#94a3b8}

    .mode-label{text-align:center;font-size:.8rem;font-weight:700;margin-bottom:12px;padding:6px;border-radius:8px}
    .mode-label.masuk{color:#4ade80;background:rgba(74,222,128,.1)}
    .mode-label.pulang{color:#f87171;background:rgba(248,113,113,.1)}

    .scan-tabs{display:flex;gap:6px;margin-bottom:12px}
    .scan-tab{flex:1;padding:8px;border:1px solid rgba(255,255,255,.15);border-radius:8px;background:transparent;color:#94a3b8;font-size:.78rem;font-weight:600;cursor:pointer;transition:.2s}
    .scan-tab.active{background:#3b82f6;border-color:#3b82f6;color:white}

    .video-box{background:#111;border-radius:12px;overflow:hidden;position:relative}
    #scanPlaceholder{height:260px;display:flex;flex-direction:column;align-items:center;justify-content:center;color:rgba(255,255,255,.3)}
    #cameraVideo{width:100%;max-height:260px;object-fit:cover;display:none}
    #scanOverlay{display:none;position:absolute;top:0;left:0;width:100%;height:100%;pointer-events:none}
    .scan-frame{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:60%;max-width:220px;height:60%;max-height:180px;border:3px solid #22c55e;border-radius:12px;box-shadow:0 0 0 9999px rgba(0,0,0,.45)}
    .scan-line{position:absolute;top:0;left:0;width:100%;height:3px;background:linear-gradient(90deg,transparent,#22c55e,transparent);animation:scanline 1.5s ease-in-out infinite}
    .scan-hint{position:absolute;bottom:8px;width:100%;text-align:center;color:white;font-size:.75rem;font-weight:600;text-shadow:0 1px 3px rgba(0,0,0,.8)}
    #detectFlash{display:none;position:absolute;top:0;left:0;width:100%;height:100%;background:rgba(34,197,94,.35);pointer-events:none}
    @keyframes scanline{0%{top:0}50%{top:calc(100% - 3px)}100%{top:0}}

    .scan-controls{display:flex;gap:8px;margin-top:10px}
    .btn-scan{flex:1;padding:10px;border:none;border-radius:10px;font-weight:700;font-size:.85rem;cursor:pointer;transition:.2s}
    .btn-start{background:#16a34a;color:white}.btn-stop{background:#dc2626;color:white;display:none}
    .btn-scan:hover{opacity:.9}

    .usb-box{background:rgba(255,255,255,.03);border:2px dashed rgba(255,255,255,.15);border-radius:12px;padding:24px;text-align:center}
    .usb-box input{width:100%;padding:12px;background:rgba(255,255,255,.08);border:2px solid rgba(255,255,255,.15);border-radius:10px;color:white;font-size:1rem;font-weight:700;letter-spacing:2px;text-align:center;margin-top:12px;outline:none}
    .usb-box input:focus{border-color:#3b82f6}
    .usb-box input::placeholder{color:rgba(255,255,255,.3);letter-spacing:0;font-weight:400}

    .manual-box{margin-top:14px;padding-top:14px;border-top:1px solid rgba(255,255,255,.08)}
    .manual-box label{display:block;font-size:.78rem;color:#94a3b8;margin-bottom:6px;font-weight:600}
    .manual-row{display:flex;gap:8px}
    .manual-row input{flex:1;padding:10px 12px;background:rgba(255,255,255,.08);border:2px solid rgba(255,255,255,.1);border-radius:10px;color:white;font-size:.9rem;outline:none}
    .manual-row input:focus{border-color:#3b82f6}
    .manual-row input::placeholder{color:rgba(255,255,255,.3)}
    .btn-cari{padding:10px 14px;background:#3b82f6;color:white;border:none;border-radius:10px;cursor:pointer;font-weight:700}

    /* RESULT */
    .result-box{border-radius:14px;padding:24px;text-align:center;margin-bottom:16px;display:none}
    .result-box.success-hadir{background:rgba(74,222,128,.1);border:1px solid rgba(74,222,128,.25)}
    .result-box.success-terlambat{background:rgba(251,146,60,.1);border:1px solid rgba(251,146,60,.25)}
    .result-box.success-pulang{background:rgba(96,165,250,.1);border:1px solid rgba(96,165,250,.25)}
    .result-box.error{background:rgba(248,113,113,.1);border:1px solid rgba(248,113,113,.25)}
    .result-emoji{font-size:2.5rem;margin-bottom:8px}
    .result-nama{font-size:1.1rem;font-weight:800;margin-bottom:4px}
    .result-detail{font-size:.8rem;color:#94a3b8}
    .result-status{font-size:1.3rem;font-weight:900;margin-top:8px}
    .result-foto{width:64px;height:64px;border-radius:50%;object-fit:cover;border:3px solid rgba(255,255,255,.2);margin-bottom:10px}

    /* LOG */
    .log-item{display:flex;align-items:center;gap:10px;padding:10px 16px;border-bottom:1px solid rgba(255,255,255,.05)}
    .log-item:last-child{border-bottom:none}
    .log-avatar{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#3b82f6,#0891b2);color:white;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;flex-shrink:0}
    .log-name{font-size:.85rem;font-weight:600}
    .log-detail{font-size:.72rem;color:#64748b}
    .log-badge{display:inline-block;padding:2px 8px;border-radius:6px;font-size:.72rem;font-weight:700}
    .badge-hadir{background:rgba(74,222,128,.15);color:#4ade80}
    .badge-terlambat{background:rgba(251,146,60,.15);color:#fb923c}
    .badge-pulang{background:rgba(96,165,250,.15);color:#60a5fa}
    .log-time{font-size:.72rem;color:#475569;margin-left:auto;white-space:nowrap}

    /* LOGIN CARDS */
    .login-section{margin-top:32px}
    .login-section h2{font-size:.85rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:1px;margin-bottom:16px}
    .login-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}
    @media(max-width:500px){.login-grid{grid-template-columns:1fr}}
    .login-card{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:20px 14px;text-align:center;text-decoration:none;color:white;transition:.2s}
    .login-card:hover{transform:translateY(-3px);background:rgba(255,255,255,.08)}
    .login-card .ic{font-size:1.8rem;margin-bottom:8px;display:block}
    .login-card h3{font-size:.88rem;font-weight:800;margin-bottom:4px}
    .login-card p{font-size:.72rem;color:#64748b}
    .lc-admin .ic{color:#f59e0b}.lc-siswa .ic{color:#4ade80}.lc-wali .ic{color:#60a5fa}

    footer{text-align:center;padding:20px;color:#334155;font-size:.75rem;border-top:1px solid rgba(255,255,255,.04);margin-top:32px}

    /* BOTTOM NAV */
    .bottom-nav { display: none; }
    @media(max-width: 768px) {
        body { padding-bottom: 80px; } /* ruang untuk bottom nav */
        .navbar { display: none; } /* sembunyikan navbar atas */
        
        .bottom-nav {
            display: flex; position: fixed; bottom: 0; left: 0; right: 0;
            background: rgba(15,23,42,0.95); backdrop-filter: blur(10px);
            border-top: 1px solid rgba(255,255,255,0.08); z-index: 999;
            padding: 8px 12px 16px; justify-content: space-between; align-items: flex-end;
            box-shadow: 0 -4px 20px rgba(0,0,0,0.5);
        }
        .bnav-item {
            flex: 1; display: flex; flex-direction: column; align-items: center;
            color: #94a3b8; text-decoration: none; font-size: 0.65rem; font-weight: 600; gap: 4px;
            transition: 0.2s;
        }
        .bnav-item i { font-size: 1.2rem; }
        .bnav-item:hover, .bnav-item.active { color: #38bdf8; }
        
        .scan-center { position: relative; }
        .scan-circle {
            width: 64px; height: 64px; background: linear-gradient(135deg, #3b82f6, #0891b2);
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            color: white; font-size: 2rem; box-shadow: 0 4px 15px rgba(59,130,246,0.4);
            border: 5px solid #0f172a; position: absolute; top: -42px; left: 50%; transform: translateX(-50%);
            transition: 0.2s;
        }
        .scan-center span { margin-top: 24px; color: #38bdf8; }
        .scan-center:active .scan-circle { transform: translateX(-50%) scale(0.95); }
        
        /* Maksimalkan layar scan (Full-Screen Camera Area) */
        .video-box { height: auto !important; max-height: none !important; }
        #scanPlaceholder { height: 50vh !important; }
        #cameraVideo { max-height: 55vh !important; height: auto; object-fit: cover; }
        
        /* Fat-Finger Friendly Design */
        .mode-btn { padding: 16px 12px; font-size: 0.95rem; border-radius: 14px; }
        .btn-scan { padding: 16px 12px; font-size: 0.95rem; border-radius: 14px; }
        .scan-tab { padding: 14px 8px; font-size: 0.85rem; border-radius: 12px; }
        .manual-row input { padding: 14px; font-size: 1rem; }
        .btn-cari { padding: 14px 20px; border-radius: 12px; }
        
        .hero { min-height: auto; padding-top: 20px; }
    }
    </style>
</head>
<body>

<!-- ============================================
     MOBILE VIEWS (hanya tampil di HP ≤768px)
     ============================================ -->
<div class="views-container">

<!-- VIEW 1: BERANDA -->
<div id="view-beranda" class="view active">
    <div class="v-home-header">
        <div class="v-home-greeting" id="mobileGreeting">🌅 Selamat Pagi</div>
        <div class="v-home-school"><?= htmlspecialchars($pengaturan['nama_sekolah']) ?></div>
        <div class="v-home-date"><i class="fas fa-calendar-alt" style="margin-right:4px"></i><?= $tgl_indo ?></div>
        <div class="v-home-clock" id="mobileClock"><?= date('H:i:s') ?></div>
    </div>
    <div class="v-home-stats stagger">
        <div class="v-stat-card hadir"><div class="num" id="m-hadir"><?= $stats['Hadir'] ?></div><div class="lbl">Hadir</div></div>
        <div class="v-stat-card terlambat"><div class="num" id="m-terlambat"><?= $stats['Terlambat'] ?></div><div class="lbl">Terlambat</div></div>
        <div class="v-stat-card belum"><div class="num" id="m-belum"><?= $stats['belum_absen'] ?></div><div class="lbl">Belum</div></div>
        <div class="v-stat-card total"><div class="num" id="m-total"><?= $stats['total_siswa'] ?></div><div class="lbl">Total Siswa</div></div>
    </div>
    <div class="v-home-donut">
        <div class="donut-ring" id="donutRing" style="background:conic-gradient(#4ade80 0% 0%,rgba(255,255,255,0.08) 0% 100%)">
            <div class="donut-center"><span class="pct" id="donutPct">0%</span><span class="sub">Kehadiran</span></div>
        </div>
        <div class="donut-legend">
            <div class="donut-legend-item"><div class="donut-legend-dot" style="background:#4ade80"></div>Hadir</div>
            <div class="donut-legend-item"><div class="donut-legend-dot" style="background:#fb923c"></div>Terlambat</div>
            <div class="donut-legend-item"><div class="donut-legend-dot" style="background:#f87171"></div>Belum Absen</div>
        </div>
    </div>
    <div class="v-home-quickscan" onclick="switchView('scan')">
        <i class="fas fa-qrcode"></i> Mulai Scan Absensi
    </div>
    <div class="v-home-recent">
        <h3><i class="fas fa-clock" style="margin-right:5px"></i>Scan Terakhir</h3>
        <div id="recentLogFeed" style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:12px;overflow:hidden">
            <div style="padding:16px;text-align:center;color:#475569;font-size:.8rem"><i class="fas fa-inbox" style="opacity:.3"></i> Belum ada scan</div>
        </div>
    </div>
</div>

<!-- VIEW 2: REKAP -->
<div id="view-rekap" class="view">
    <div class="v-rekap-header">
        <h2><i class="fas fa-chart-bar" style="color:#38bdf8;margin-right:8px"></i>Rekap Hari Ini</h2>
        <p><?= $tgl_indo ?></p>
    </div>
    <div class="v-rekap-stats stagger">
        <div class="v-rekap-stat"><div class="n" style="color:#38bdf8" id="r-total"><?= $stats['total_siswa'] ?></div><div class="l">Total</div></div>
        <div class="v-rekap-stat"><div class="n" style="color:#4ade80" id="r-hadir"><?= $stats['Hadir'] ?></div><div class="l">Hadir</div></div>
        <div class="v-rekap-stat"><div class="n" style="color:#fb923c" id="r-terlambat"><?= $stats['Terlambat'] ?></div><div class="l">Telat</div></div>
        <div class="v-rekap-stat"><div class="n" style="color:#f87171" id="r-belum"><?= $stats['belum_absen'] ?></div><div class="l">Belum</div></div>
    </div>
    <div class="v-rekap-stats" style="padding-top:0">
        <div class="v-rekap-stat"><div class="n" style="color:#60a5fa" id="r-sakit"><?= $stats['Sakit'] ?></div><div class="l">Sakit</div></div>
        <div class="v-rekap-stat"><div class="n" style="color:#c084fc" id="r-izin"><?= $stats['Izin'] ?></div><div class="l">Izin</div></div>
        <div class="v-rekap-stat"><div class="n" style="color:#f472b6" id="r-bolos"><?= $stats['Bolos'] ?></div><div class="l">Bolos</div></div>
        <div class="v-rekap-stat"><div class="n" style="color:#94a3b8"><?= $stats['Hadir'] + $stats['Terlambat'] ?></div><div class="l">Sudah</div></div>
    </div>
    <div class="v-rekap-bars stagger">
        <div class="v-bar-row"><div class="v-bar-label">Hadir</div><div class="v-bar-track"><div class="v-bar-fill" id="bar-hadir" style="background:#4ade80;width:2%"><span id="barnum-hadir">0</span></div></div></div>
        <div class="v-bar-row"><div class="v-bar-label">Terlambat</div><div class="v-bar-track"><div class="v-bar-fill" id="bar-terlambat" style="background:#fb923c;width:2%"><span id="barnum-terlambat">0</span></div></div></div>
        <div class="v-bar-row"><div class="v-bar-label">Belum</div><div class="v-bar-track"><div class="v-bar-fill" id="bar-belum" style="background:#f87171;width:2%"><span id="barnum-belum">0</span></div></div></div>
        <div class="v-bar-row"><div class="v-bar-label">Sakit</div><div class="v-bar-track"><div class="v-bar-fill" id="bar-sakit" style="background:#60a5fa;width:2%"><span id="barnum-sakit">0</span></div></div></div>
        <div class="v-bar-row"><div class="v-bar-label">Izin</div><div class="v-bar-track"><div class="v-bar-fill" id="bar-izin" style="background:#c084fc;width:2%"><span id="barnum-izin">0</span></div></div></div>
        <div class="v-bar-row"><div class="v-bar-label">Bolos</div><div class="v-bar-track"><div class="v-bar-fill" id="bar-bolos" style="background:#f472b6;width:2%"><span id="barnum-bolos">0</span></div></div></div>
    </div>
    <div class="v-rekap-jadwal">
        <div class="v-jadwal-item"><div class="t">Masuk</div><div class="v" style="color:#4ade80"><?= date('H:i',strtotime($pengaturan['jam_masuk'])) ?></div></div>
        <div class="v-jadwal-item"><div class="t">Terlambat</div><div class="v" style="color:#fb923c"><?= date('H:i',strtotime($pengaturan['jam_terlambat'])) ?></div></div>
        <div class="v-jadwal-item"><div class="t">Pulang</div><div class="v" style="color:#60a5fa"><?= date('H:i',strtotime($pengaturan['jam_pulang'])) ?></div></div>
        <div class="v-jadwal-item"><div class="t">Sekarang</div><div class="v" style="color:#38bdf8" id="mobileClock2"><?= date('H:i') ?></div></div>
    </div>
</div>

<!-- VIEW 3: SCAN QR (scanner ditampilkan dari .main via CSS class 'scan-active') -->
<div id="view-scan" class="view" style="min-height:0;padding:0"></div>

<!-- VIEW 4: RIWAYAT -->
<div id="view-riwayat" class="view">
    <div class="v-riwayat-header">
        <h2><i class="fas fa-clipboard-list" style="color:#38bdf8;margin-right:8px"></i>Riwayat</h2>
        <span style="background:rgba(59,130,246,.2);color:#60a5fa;padding:4px 12px;border-radius:20px;font-size:.75rem;font-weight:700" id="riwayatCount">0</span>
    </div>
    <div class="v-riwayat-filters">
        <div class="v-filter-chip active" data-filter="all" onclick="filterRiwayat('all')">📋 Semua</div>
        <div class="v-filter-chip" data-filter="Hadir" onclick="filterRiwayat('Hadir')">✅ Hadir</div>
        <div class="v-filter-chip" data-filter="Terlambat" onclick="filterRiwayat('Terlambat')">⏰ Terlambat</div>
        <div class="v-filter-chip" data-filter="Pulang" onclick="filterRiwayat('Pulang')">🏠 Pulang</div>
    </div>
    <div class="v-riwayat-list" id="mobileLogList">
        <div style="padding:40px;text-align:center;color:#475569"><i class="fas fa-inbox fa-2x" style="opacity:.3;display:block;margin-bottom:10px"></i>Memuat data...</div>
    </div>
</div>

<!-- VIEW 5: AKUN -->
<div id="view-akun" class="view">
    <div class="v-akun-header">
        <?php
        $logo_file = defined('LOGO_FILE') ? LOGO_FILE : ($pengaturan['logo'] ?? '');
        if (!empty($logo_file) && file_exists(__DIR__.'/uploads/logo/'.$logo_file)): ?>
            <img src="<?= BASE_URL ?>uploads/logo/<?= $logo_file ?>" class="v-akun-logo" alt="Logo MAN 2 Lombok Timur">
        <?php else: ?>
            <div style="width:72px;height:72px;background:linear-gradient(135deg,#3b82f6,#0891b2);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2rem;margin:0 auto"><i class="fas fa-school"></i></div>
        <?php endif; ?>
        <div class="v-akun-school"><?= htmlspecialchars($pengaturan['nama_sekolah']) ?></div>
        <div class="v-akun-sub">Sistem Absensi Digital</div>
    </div>
    <div class="v-akun-grid stagger">
        <a href="login.php" class="v-akun-card"><i class="fas fa-user-shield" style="color:#f59e0b"></i><h4>Admin</h4><p>Panel Administrasi</p></a>
        <a href="portal_kepsek_login.php" class="v-akun-card"><i class="fas fa-user-tie" style="color:#a78bfa"></i><h4>Kepala Sekolah</h4><p>Monitoring</p></a>
        <a href="portal_disiplin_login.php" class="v-akun-card"><i class="fas fa-shield-alt" style="color:#fbbf24"></i><h4>Disiplin</h4><p>Pelanggaran</p></a>
        <a href="portal_bk_login.php" class="v-akun-card"><i class="fas fa-user-shield" style="color:#22d3ee"></i><h4>BK</h4><p>Konseling</p></a>
        <a href="portal_login.php?role=wali" class="v-akun-card"><i class="fas fa-chalkboard-teacher" style="color:#818cf8"></i><h4>Wali Kelas</h4><p>Data Siswa</p></a>
        <a href="portal_login.php?role=siswa" class="v-akun-card"><i class="fas fa-user-graduate" style="color:#34d399"></i><h4>Siswa</h4><p>Lihat Absensi</p></a>
    </div>
    <div class="v-akun-info">
        <p><i class="fas fa-info-circle" style="margin-right:4px"></i> Absensi Digital v2.0 — <?= date('Y') ?></p>
    </div>
</div>

</div><!-- /views-container -->

<!-- ============================================
     DESKTOP CONTENT (tetap tampil di ≥769px)
     ============================================ -->
<div class="desktop-content">
<!-- NAVBAR -->
<nav class="navbar">
    <a href="index.php" class="navbar-brand">
        <?php
        $logo_nav = defined('LOGO_FILE') ? LOGO_FILE : ($pengaturan['logo'] ?? '');
        if (!empty($logo_nav) && file_exists(__DIR__.'/uploads/logo/'.$logo_nav)): ?>
            <img src="<?= BASE_URL ?>uploads/logo/<?= $logo_nav ?>" class="navbar-logo" alt="Logo MAN 2 Lombok Timur">
        <?php else: ?>
            <div class="navbar-logo-icon"><i class="fas fa-school"></i></div>
        <?php endif; ?>
        <div>
            <div class="navbar-title"><?= htmlspecialchars($pengaturan['nama_sekolah']) ?></div>
            <div class="navbar-sub">Sistem Absensi Digital</div>
        </div>
    </a>
    <div class="navbar-clock" id="navClock"><?= date('H:i:s') ?></div>
    <a href="portal_kepsek_login.php" class="btn-nav btn-kepsek-nav" title="Portal Kepala Sekolah"><i class="fas fa-user-tie"></i> <span class="btn-kepsek-txt">Kepsek</span></a>
    <a href="login.php" class="btn-nav btn-login-nav"><i class="fas fa-user-shield"></i> ADMIN</a>
</nav>

<!-- HERO -->
<div class="hero">
    <div class="hero-bg-grad"></div>
    <div class="hero-content">
        <!-- Kiri: Judul & Tanggal -->
        <div class="hero-left">
            <div class="hero-title">Absensi Digital<br><?= htmlspecialchars($pengaturan['nama_sekolah']) ?></div>
            <div class="hero-sub">Scan QR / Barcode untuk absensi langsung</div>
            <div class="hero-date"><i class="fas fa-calendar-alt" style="margin-right:7px;color:#38bdf8"></i><?= $tgl_indo ?></div>
        </div>
        <!-- Kanan: Tombol Portal -->
        <div class="hero-right">
            <a href="portal_disiplin_login.php" class="portal-btn pb-disiplin">
                <i class="fas fa-shield-alt"></i> PELANGGARAN DISIPLIN
            </a>
            <a href="portal_bk_login.php" class="portal-btn pb-bk">
                <i class="fas fa-user-shield"></i> BIMBINGAN KONSELING
            </a>
            <a href="portal_login.php?role=wali" class="portal-btn pb-wali">
                <i class="fas fa-chalkboard-teacher"></i> WALI KELAS
            </a>
            <a href="portal_login.php?role=siswa" class="portal-btn pb-siswa">
                <i class="fas fa-user-graduate"></i> SISWA
            </a>
        </div>
    </div>
</div>

<!-- STATS -->
<div class="stats-bar">
    <div class="stats-grid">
        <div class="stat-item"><div class="stat-num c-total" id="s-total"><?= $stats['total_siswa'] ?></div><div class="stat-lbl">Total</div></div>
        <div class="stat-item"><div class="stat-num c-hadir" id="s-hadir"><?= $stats['Hadir'] ?></div><div class="stat-lbl">Hadir</div></div>
        <div class="stat-item"><div class="stat-num c-terlambat" id="s-terlambat"><?= $stats['Terlambat'] ?></div><div class="stat-lbl">Terlambat</div></div>
        <div class="stat-item"><div class="stat-num c-alpa" id="s-alpa"><?= $stats['belum_absen'] ?></div><div class="stat-lbl">Belum</div></div>
        <div class="stat-item"><div class="stat-num c-sakit" id="s-sakit"><?= $stats['Sakit'] ?></div><div class="stat-lbl">Sakit</div></div>
        <div class="stat-item"><div class="stat-num c-izin" id="s-izin"><?= $stats['Izin'] ?></div><div class="stat-lbl">Izin</div></div>
        <div class="stat-item"><div class="stat-num c-bolos" id="s-bolos"><?= $stats['Bolos'] ?></div><div class="stat-lbl">Bolos</div></div>
    </div>
</div>

<!-- MAIN -->
<div class="main">
<div class="grid-scan">

    <!-- KOLOM KIRI: SCANNER -->
    <div>
        <div class="card">
            <div class="card-header"><i class="fas fa-qrcode" style="color:#38bdf8"></i> Scan Absensi</div>
            <div class="card-body">
                <div class="mode-btns">
                    <button id="btnMasuk" class="mode-btn mode-masuk" onclick="setMode('masuk')">🟢 Absen Masuk</button>
                    <button id="btnPulang" class="mode-btn mode-pulang inactive" onclick="setMode('pulang')">🔴 Absen Pulang</button>
                </div>
                <div class="mode-label masuk" id="modeLabel">Mode aktif: ABSEN MASUK</div>
                <div class="scan-tabs">
                    <button class="scan-tab active" onclick="switchTab('camera',this)"><i class="fas fa-camera"></i> Kamera</button>
                    <button class="scan-tab" onclick="switchTab('usb',this)"><i class="fas fa-usb"></i> USB Scanner</button>
                </div>
                <div id="tab-camera">
                    <div id="libStatus" style="text-align:center;padding:6px;font-size:.75rem;color:#64748b;background:rgba(255,255,255,.04);border-radius:6px;margin-bottom:8px">
                        <i class="fas fa-spinner fa-spin"></i> Memuat library...
                    </div>
                    <div class="video-box">
                        <div id="scanPlaceholder" onclick="startScan()" style="cursor:pointer">
                            <i class="fas fa-camera fa-3x" style="margin-bottom:10px"></i>
                            <p style="font-size:.85rem">Tap untuk <strong style="color:white">Mulai Scan</strong></p>
                        </div>
                        <video id="cameraVideo" autoplay playsinline muted></video>
                        <!-- Tombol Close Kamera (muncul saat kamera aktif) -->
                        <button id="closeCamBtn" onclick="stopScan()" style="display:none;position:absolute;top:10px;left:10px;background:rgba(220,38,38,0.85);border:none;color:white;width:40px;height:40px;border-radius:50%;cursor:pointer;z-index:20;font-size:1.1rem;backdrop-filter:blur(4px);box-shadow:0 2px 8px rgba(0,0,0,.4)"><i class="fas fa-times"></i></button>
                        <canvas id="scanCanvas" style="display:none"></canvas>
                        <div id="scanOverlay">
                            <div class="scan-frame"><div class="scan-line"></div></div>
                            <div class="scan-hint">Arahkan QR/Barcode ke kotak hijau</div>
                        </div>
                        <div id="detectFlash"></div>
                        <button id="torchBtn" onclick="toggleTorch()" style="display:none;position:absolute;top:10px;right:10px;background:rgba(0,0,0,0.6);border:1px solid rgba(255,255,255,0.2);color:white;width:44px;height:44px;border-radius:50%;cursor:pointer;z-index:10;"><i class="fas fa-lightbulb"></i></button>
                    </div>
                    <div class="scan-controls">
                        <button class="btn-scan btn-start" id="startBtn" onclick="startScan()"><i class="fas fa-play"></i> Mulai Scan</button>
                        <button class="btn-scan btn-stop" id="stopBtn" onclick="stopScan()"><i class="fas fa-stop"></i> Stop</button>
                        <select id="cameraSelect" style="flex:1;padding:8px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.1);border-radius:10px;color:white;font-size:.8rem"></select>
                    </div>
                    <div id="debugInfo" style="margin-top:5px;font-size:.7rem;color:#475569;text-align:center;min-height:14px"></div>
                </div>
                <div id="tab-usb" style="display:none">
                    <div class="usb-box">
                        <i class="fas fa-barcode fa-2x" style="color:#38bdf8"></i>
                        <p style="margin-top:8px;font-size:.82rem;color:#94a3b8">Hubungkan USB Scanner, klik field lalu scan kartu</p>
                        <input type="text" id="usbInput" placeholder="🎯 Klik di sini, lalu scan...">
                    </div>
                </div>
                <div class="manual-box">
                    <label><i class="fas fa-keyboard"></i> Input NIS Manual</label>
                    <div class="manual-row">
                        <input type="text" id="manualNis" placeholder="Ketik NIS lalu Enter">
                        <button class="btn-cari" onclick="processNIS(document.getElementById('manualNis').value.trim())"><i class="fas fa-search"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- KOLOM KANAN: RESULT + LOG -->
    <div>
        <div class="result-box" id="resultBox">
            <div id="resultContent"></div>
        </div>
        <div class="card">
            <div class="card-header">
                <i class="fas fa-list-ul" style="color:#38bdf8"></i> Log Hari Ini
                <span id="logCount" style="margin-left:auto;background:rgba(59,130,246,.2);color:#60a5fa;padding:2px 10px;border-radius:20px;font-size:.75rem">0</span>
            </div>
            <div id="logFeed" style="max-height:420px;overflow-y:auto">
                <div id="logEmpty" style="padding:30px;text-align:center;color:#334155;font-size:.85rem">
                    <i class="fas fa-inbox fa-2x" style="opacity:.3;display:block;margin-bottom:8px"></i>Belum ada scan
                </div>
            </div>
        </div>
    </div>
</div><!-- /grid-scan -->

<!-- LOGIN ADMIN -->
<div class="login-section">
    <h2><i class="fas fa-user-shield" style="margin-right:6px;color:#f59e0b"></i>Login Administrator</h2>
    <a href="login.php" style="display:inline-flex;align-items:center;gap:10px;background:linear-gradient(135deg,#f59e0b,#d97706);color:white;padding:14px 28px;border-radius:12px;font-weight:800;font-size:.95rem;text-decoration:none;transition:.2s;letter-spacing:.5px" onmouseover="this.style.opacity='.9'" onmouseout="this.style.opacity='1'">
        <i class="fas fa-sign-in-alt"></i> MASUK SEBAGAI ADMIN
    </a>
</div>
</div><!-- /main -->

<footer>&copy; <?= date('Y') ?> <?= htmlspecialchars($pengaturan['nama_sekolah']) ?> — Sistem Absensi Digital</footer>
</div><!-- /desktop-content -->

<!-- BOTTOM NAV -->
<div class="bottom-nav">
    <a href="javascript:void(0)" class="bnav-item active" data-view="beranda" onclick="switchView('beranda')">
        <i class="fas fa-home"></i>
        <span>Beranda</span>
        <div class="bnav-dot"></div>
    </a>
    <a href="javascript:void(0)" class="bnav-item" data-view="rekap" onclick="switchView('rekap')">
        <i class="fas fa-chart-bar"></i>
        <span>Rekap</span>
        <div class="bnav-dot"></div>
    </a>
    <a href="javascript:void(0)" class="bnav-item scan-center" data-view="scan" onclick="switchView('scan')">
        <div class="scan-circle">
            <i class="fas fa-qrcode"></i>
        </div>
        <span>Scan QR</span>
    </a>
    <a href="javascript:void(0)" class="bnav-item" data-view="riwayat" onclick="switchView('riwayat')">
        <i class="fas fa-clipboard-list"></i>
        <span>Riwayat</span>
        <div class="bnav-dot"></div>
    </a>
    <a href="javascript:void(0)" class="bnav-item" data-view="akun" onclick="switchView('akun')">
        <i class="fas fa-user-circle"></i>
        <span>Akun</span>
        <div class="bnav-dot"></div>
    </a>
</div>

<!-- Libraries -->
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
<script src="https://unpkg.com/@zxing/library@0.19.1/umd/index.min.js"></script>

<script>
var videoStream=null, scanInterval=null, scanning=false;
var jenisAbsen='masuk', lastScanned='', lastScannedAt=0;
var audioCtx=null, zxingDecoder=null;
var wakeLock = null; // Wake Lock API
var videoTrack = null; // Untuk kontrol Senter
const BASE = '<?= BASE_URL ?>';

// Cek library
var libCheck = setInterval(function(){
    var jsQRok = typeof jsQR==='function';
    var zxingOk = typeof ZXing!=='undefined';
    var el = document.getElementById('libStatus');
    if (jsQRok && zxingOk) {
        clearInterval(libCheck);
        el.innerHTML = '<i class="fas fa-check-circle" style="color:#4ade80"></i> Scanner siap (QR + Barcode)';
        el.style.color='#4ade80';
        try {
            var hints=new Map();
            hints.set(ZXing.DecodeHintType.POSSIBLE_FORMATS,[ZXing.BarcodeFormat.CODE_128,ZXing.BarcodeFormat.CODE_39,ZXing.BarcodeFormat.EAN_13,ZXing.BarcodeFormat.QR_CODE]);
            hints.set(ZXing.DecodeHintType.TRY_HARDER,true);
            zxingDecoder=new ZXing.BrowserMultiFormatReader(hints);
        } catch(e){}
    }
}, 400);

// Clock
setInterval(()=>{
    const n=new Date();
    document.getElementById('navClock').textContent=String(n.getHours()).padStart(2,'0')+':'+String(n.getMinutes()).padStart(2,'0')+':'+String(n.getSeconds()).padStart(2,'0');
},1000);

// Mode masuk/pulang
function setMode(m) {
    jenisAbsen = m;
    const bM=document.getElementById('btnMasuk'), bP=document.getElementById('btnPulang'), lbl=document.getElementById('modeLabel');
    if (m==='masuk') {
        bM.className='mode-btn mode-masuk'; bP.className='mode-btn mode-pulang inactive';
        lbl.className='mode-label masuk'; lbl.textContent='Mode aktif: ABSEN MASUK';
    } else {
        bP.className='mode-btn mode-pulang'; bM.className='mode-btn mode-masuk inactive';
        lbl.className='mode-label pulang'; lbl.textContent='Mode aktif: ABSEN PULANG';
    }
}

// Tab switch
function switchTab(tab, btn) {
    document.querySelectorAll('.scan-tab').forEach(b=>b.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('tab-camera').style.display = tab==='camera'?'':'none';
    document.getElementById('tab-usb').style.display    = tab==='usb'?'':'none';
    if (tab==='usb') { stopScan(); setTimeout(()=>document.getElementById('usbInput').focus(),150); }
}

// Load kamera
async function loadCameras() {
    var sel=document.getElementById('cameraSelect');
    if (!navigator.mediaDevices||!navigator.mediaDevices.getUserMedia) {
        sel.innerHTML='<option>Butuh HTTPS</option>'; return;
    }
    try {
        var tmp=await navigator.mediaDevices.getUserMedia({video:true});
        tmp.getTracks().forEach(t=>t.stop());
        var devs=await navigator.mediaDevices.enumerateDevices();
        var cams=devs.filter(d=>d.kind==='videoinput');
        sel.innerHTML=cams.map((c,i)=>`<option value="${c.deviceId}">${c.label||'Kamera '+(i+1)}</option>`).join('');
        var back=cams.find(c=>/back|rear|belakang|environment/i.test(c.label));
        if(back) sel.value=back.deviceId;
    } catch(e){ sel.innerHTML='<option>Izin ditolak</option>'; }
}
loadCameras();

// Start scan
async function startScan() {
    if (!navigator.mediaDevices||!navigator.mediaDevices.getUserMedia) {
        showResult({success:false,message:'Kamera perlu HTTPS!'}); return;
    }
    var did=document.getElementById('cameraSelect').value;
    var c={video:did?{deviceId:{exact:did},width:{ideal:1280},height:{ideal:720},advanced:[{focusMode:"continuous"}]}:{facingMode:{ideal:'environment'},width:{ideal:1280},height:{ideal:720},advanced:[{focusMode:"continuous"}]}};
    try {
        videoStream=await navigator.mediaDevices.getUserMedia(c);
        var v=document.getElementById('cameraVideo');
        v.srcObject=videoStream; await v.play();
        
        // Setup Senter
        videoTrack = videoStream.getVideoTracks()[0];
        if (videoTrack && videoTrack.getCapabilities) {
            const capabilities = videoTrack.getCapabilities();
            if (capabilities.torch) {
                document.getElementById('torchBtn').style.display = 'block';
            }
        }
        
        // Wake Lock Request (Layar tidak mati)
        try {
            if ('wakeLock' in navigator) {
                wakeLock = await navigator.wakeLock.request('screen');
            }
        } catch (err) {}

        document.getElementById('scanPlaceholder').style.display='none';
        v.style.display='block';
        document.getElementById('scanOverlay').style.display='block';
        document.getElementById('startBtn').style.display='none';
        document.getElementById('stopBtn').style.display='';
        var closeBtn=document.getElementById('closeCamBtn'); if(closeBtn) closeBtn.style.display='block';
        scanning=true; dbg('📷 Kamera aktif...');
        startLoop(v);
    } catch(e) {
        var msg='Error: '+e.message;
        if(e.name==='NotAllowedError') msg='Izin kamera ditolak.';
        showResult({success:false,message:msg});
    }
}

function startLoop(video) {
    var canvas=document.getElementById('scanCanvas');
    var ctx=canvas.getContext('2d',{willReadFrequently:true});
    var frame=0;
    scanInterval=setInterval(function(){
        if(!scanning||video.readyState<2||video.videoWidth===0) return;
        if(canvas.width!==video.videoWidth){canvas.width=video.videoWidth;canvas.height=video.videoHeight;}
        ctx.drawImage(video,0,0,canvas.width,canvas.height);
        frame++;
        var cw=Math.floor(canvas.width*.7),ch=Math.floor(canvas.height*.7);
        var cx=Math.floor((canvas.width-cw)/2),cy=Math.floor((canvas.height-ch)/2);
        var imgData;
        try{imgData=ctx.getImageData(cx,cy,cw,ch);}catch(e){return;}
        if(typeof jsQR==='function'){
            var qr=jsQR(imgData.data,imgData.width,imgData.height,{inversionAttempts:'dontInvert'});
            if(qr&&qr.data){onDetected(qr.data);return;}
            if(frame%2===0){try{var fd=ctx.getImageData(0,0,canvas.width,canvas.height);var q2=jsQR(fd.data,fd.width,fd.height,{inversionAttempts:'attemptBoth'});if(q2&&q2.data){onDetected(q2.data);return;}}catch(e){}}
        }
        if(zxingDecoder&&frame%3===0){try{var lum=new ZXing.HTMLCanvasElementLuminanceSource(canvas);var bmp=new ZXing.BinaryBitmap(new ZXing.HybridBinarizer(lum));var res=zxingDecoder.decode(bmp);if(res&&res.getText()){onDetected(res.getText());return;}}catch(e){}}
        if(frame%90===0) dbg('Frame: '+frame+' — Arahkan ke kotak hijau');
    },80);
}

function onDetected(code) {
    code=code.trim(); if(!code) return;
    var now=Date.now();
    if(code===lastScanned&&now-lastScannedAt<2500) return;
    lastScanned=code; lastScannedAt=now;
    var f=document.getElementById('detectFlash');
    f.style.display='block'; setTimeout(()=>f.style.display='none',300);
    beep(880,150);
    processNIS(code);
}

function stopScan() {
    scanning=false;
    if(scanInterval){clearInterval(scanInterval);scanInterval=null;}
    if(videoStream){videoStream.getTracks().forEach(t=>t.stop());videoStream=null;}
    videoTrack = null;
    document.getElementById('torchBtn').style.display = 'none';
    if(wakeLock !== null) { wakeLock.release().then(()=>wakeLock=null); }
    
    var v=document.getElementById('cameraVideo');
    v.srcObject=null; v.style.display='none';
    document.getElementById('scanPlaceholder').style.display='flex';
    document.getElementById('scanOverlay').style.display='none';
    document.getElementById('startBtn').style.display='';
    document.getElementById('stopBtn').style.display='none';
    var closeBtn=document.getElementById('closeCamBtn'); if(closeBtn) closeBtn.style.display='none';
    dbg('');
}

// Fitur Senter
var torchState = false;
function toggleTorch() {
    if (videoTrack) {
        torchState = !torchState;
        videoTrack.applyConstraints({
            advanced: [{torch: torchState}]
        }).then(() => {
            var tb = document.getElementById('torchBtn');
            if(torchState) { tb.style.background = '#eab308'; tb.style.color = 'black'; }
            else { tb.style.background = 'rgba(0,0,0,0.6)'; tb.style.color = 'white'; }
        }).catch(e => dbg('Senter error: '+e.message));
    }
}

// USB
document.getElementById('usbInput').addEventListener('keydown',function(e){
    if(e.key==='Enter'){var v=this.value.trim();if(v){processNIS(v);this.value='';}}
});
// Manual
document.getElementById('manualNis').addEventListener('keydown',function(e){
    if(e.key==='Enter'){processNIS(this.value.trim());this.value='';}
});

// Proses NIS → kirim ke ajax publik
async function processNIS(nis) {
    if(!nis||nis.length<3) return;
    dbg('⏳ Memproses: '+nis);
    try {
        var resp=await fetch(BASE+'ajax/absen_publik.php',{
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:'nis='+encodeURIComponent(nis)+'&jenis='+encodeURIComponent(jenisAbsen)
        });
        var data=await resp.json();
        showResult(data);
        if(data.success){
            addToLog(data);
            beep(880,150);
            refreshStats();
            if(navigator.vibrate) navigator.vibrate([80,40,80]);
        } else {
            beep(300,400);
            if(navigator.vibrate) navigator.vibrate(400);
        }
    } catch(e){ showResult({success:false,message:'Error koneksi'}); }
}

function showResult(data) {
    const box=document.getElementById('resultBox');
    box.style.display='block';
    const colors={Hadir:'#4ade80',Terlambat:'#fb923c',Pulang:'#60a5fa'};
    const emoji={Hadir:'✅',Terlambat:'⏰',Pulang:'🏠'};
    if(!data.success) {
        box.className='result-box error';
        box.innerHTML=`<div class="result-emoji">❌</div><div class="result-nama" style="color:#f87171">${data.message}</div>`;
        return;
    }
    const cls='result-box success-'+data.status.toLowerCase();
    const fotoHtml = data.foto ? `<img src="${BASE}uploads/foto/${data.foto}" class="result-foto">` : '';
    box.className=cls;
    box.innerHTML=`
        ${fotoHtml}
        <div class="result-emoji">${emoji[data.status]||'✅'}</div>
        <div class="result-nama">${data.nama}</div>
        <div class="result-detail">${data.nis} | Kelas ${data.kelas}</div>
        <div class="result-status" style="color:${colors[data.status]||'#4ade80'}">${data.status}</div>
        <div style="font-size:.8rem;color:#64748b;margin-top:4px">${data.jam}</div>`;
    // Auto hide setelah 5 detik
    setTimeout(()=>{ box.style.display='none'; }, 5000);
}

function addToLog(data) {
    const feed=document.getElementById('logFeed');
    document.getElementById('logEmpty')?.remove();
    const item=document.createElement('div'); item.className='log-item';
    const badgeCls={Hadir:'badge-hadir',Terlambat:'badge-terlambat',Pulang:'badge-pulang'}[data.status]||'badge-hadir';
    item.innerHTML=`<div class="log-avatar">${data.nama.charAt(0).toUpperCase()}</div>
        <div style="flex:1;min-width:0"><div class="log-name">${data.nama}</div><div class="log-detail">${data.nis} | ${data.kelas}</div></div>
        <div><span class="log-badge ${badgeCls}">${data.status}</span><div class="log-time">${data.jam}</div></div>`;
    feed.insertBefore(item, feed.firstChild);
    const cnt=document.getElementById('logCount');
    cnt.textContent=parseInt(cnt.textContent||0)+1;
}

// Refresh stats
async function refreshStats() {
    try {
        var r=await fetch(BASE+'ajax/get_stats.php');
        var d=await r.json();
        if(d){
            document.getElementById('s-hadir').textContent=d.Hadir||0;
            document.getElementById('s-terlambat').textContent=d.Terlambat||0;
            document.getElementById('s-alpa').textContent=d.belum_absen||0;
            document.getElementById('s-sakit').textContent=d.Sakit||0;
            document.getElementById('s-izin').textContent=d.Izin||0;
            document.getElementById('s-bolos').textContent=d.Bolos||0;
        }
    } catch(e){}
}

// Load log hari ini
async function loadLog() {
    try {
        var r=await fetch(BASE+'ajax/get_log.php'); var d=await r.json();
        if(d&&d.length>0){
            document.getElementById('logEmpty')?.remove();
            d.forEach(function(row){
                var feed=document.getElementById('logFeed');
                var item=document.createElement('div'); item.className='log-item';
                var st=row.status||'Hadir';
                var bc={Hadir:'badge-hadir',Terlambat:'badge-terlambat',Pulang:'badge-pulang'}[st]||'badge-hadir';
                item.innerHTML=`<div class="log-avatar">${row.nama.charAt(0).toUpperCase()}</div>
                    <div style="flex:1;min-width:0"><div class="log-name">${row.nama}</div><div class="log-detail">${row.nis} | ${row.kelas}</div></div>
                    <div><span class="log-badge ${bc}">${st}</span><div class="log-time">${row.jam_masuk?row.jam_masuk.slice(0,5):'-'}</div></div>`;
                feed.appendChild(item);
            });
            document.getElementById('logCount').textContent=d.length;
        }
    } catch(e){}
}
loadLog();

function dbg(msg){var e=document.getElementById('debugInfo');if(e)e.textContent=msg;}

function beep(freq,dur){
    try{
        if(!audioCtx) audioCtx=new(window.AudioContext||window.webkitAudioContext)();
        if(audioCtx.state==='suspended') audioCtx.resume();
        var o=audioCtx.createOscillator(),g=audioCtx.createGain();
        o.connect(g);g.connect(audioCtx.destination);
        o.type='square';o.frequency.value=freq||880;
        g.gain.setValueAtTime(.4,audioCtx.currentTime);
        g.gain.exponentialRampToValueAtTime(.001,audioCtx.currentTime+dur/1000);
        o.start();o.stop(audioCtx.currentTime+dur/1000);
    }catch(e){}
}

document.addEventListener('click',function(){if(!audioCtx)audioCtx=new(window.AudioContext||window.webkitAudioContext)();});
window.addEventListener('beforeunload',stopScan);
</script>

<!-- Mobile Views JS -->
<script src="assets/js/mobile-views.js?v=<?= time() ?>"></script>

<?php include 'includes/pwa_banner.php'; ?>
</body>
</html>
