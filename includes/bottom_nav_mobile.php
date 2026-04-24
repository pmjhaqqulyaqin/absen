<?php
// Include file untuk bottom nav mobile pada halaman portal login
// Tambahkan di akhir body sebelum </body>
if (!defined('DB_HOST')) {
    require_once __DIR__.'/config.php';
}
?>
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/mobile-views.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
@media(max-width: 768px) {
    body { padding-bottom: 80px !important; min-height: auto !important; }
    .bottom-nav {
        display: flex !important; position: fixed; bottom: 0; left: 0; right: 0;
        background: rgba(15,23,42,0.97); backdrop-filter: blur(10px);
        border-top: 1px solid rgba(255,255,255,0.08); z-index: 999;
        padding: 6px 8px 14px; justify-content: space-between; align-items: flex-end;
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
        width: 60px; height: 60px; background: linear-gradient(135deg, #3b82f6, #0891b2);
        border-radius: 50%; display: flex; align-items: center; justify-content: center;
        color: white; font-size: 1.8rem; box-shadow: 0 4px 15px rgba(59,130,246,0.4);
        border: 4px solid #0f172a; position: absolute; top: -35px; left: 50%; transform: translateX(-50%);
    }
    .scan-center span { margin-top: 24px; color: #38bdf8; }
    .bnav-dot { width: 4px; height: 4px; border-radius: 50%; background: #38bdf8; margin-top: 2px; display: none; }
    .bnav-item.active .bnav-dot { display: block; }
}
@media(min-width: 769px) {
    .bottom-nav { display: none !important; }
}
</style>
<div class="bottom-nav">
    <a href="<?= BASE_URL ?>index.php" class="bnav-item" onclick="localStorage.setItem('absensi_view','beranda')">
        <i class="fas fa-home"></i><span>Beranda</span><div class="bnav-dot"></div>
    </a>
    <a href="<?= BASE_URL ?>index.php" class="bnav-item" onclick="localStorage.setItem('absensi_view','rekap')">
        <i class="fas fa-chart-bar"></i><span>Rekap</span><div class="bnav-dot"></div>
    </a>
    <a href="<?= BASE_URL ?>index.php" class="bnav-item scan-center" onclick="localStorage.setItem('absensi_view','scan')">
        <div class="scan-circle"><i class="fas fa-qrcode"></i></div><span>Scan QR</span>
    </a>
    <a href="<?= BASE_URL ?>index.php" class="bnav-item" onclick="localStorage.setItem('absensi_view','riwayat')">
        <i class="fas fa-clipboard-list"></i><span>Riwayat</span><div class="bnav-dot"></div>
    </a>
    <a href="<?= BASE_URL ?>index.php" class="bnav-item active" onclick="localStorage.setItem('absensi_view','akun')">
        <i class="fas fa-user-circle"></i><span>Akun</span><div class="bnav-dot"></div>
    </a>
</div>
