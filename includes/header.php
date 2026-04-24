<?php
$pengaturan = get_pengaturan();
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#ffffff">
    <link rel="manifest" href="<?= BASE_URL ?>manifest.json">
    <link rel="apple-touch-icon" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/svgs/solid/qrcode.svg">
    <title><?= htmlspecialchars($pengaturan['nama_sekolah']) ?> - Sistem Absensi</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <?php if (!empty($pengaturan['logo']) && file_exists('uploads/logo/' . $pengaturan['logo'])): ?>
            <img src="<?= BASE_URL ?>uploads/logo/<?= $pengaturan['logo'] ?>" alt="Logo" class="school-logo">
        <?php else: ?>
            <div class="logo-placeholder"><i class="fas fa-school"></i></div>
        <?php endif; ?>
        <div class="school-name"><?= htmlspecialchars($pengaturan['nama_sekolah']) ?></div>
        <div class="school-sub">Sistem Absensi Digital v2</div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">UTAMA</div>
        <a href="<?= BASE_URL ?>dashboard.php" class="nav-item <?= $current_page=='dashboard'?'active':'' ?>">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        <a href="<?= BASE_URL ?>scan.php" class="nav-item <?= $current_page=='scan'?'active':'' ?>">
            <i class="fas fa-qrcode"></i> Scan QR
        </a>
        <a href="<?= BASE_URL ?>manual.php" class="nav-item <?= $current_page=='manual'?'active':'' ?>">
            <i class="fas fa-edit"></i> Input Absensi
        </a>
        <a href="<?= BASE_URL ?>belum_absen.php" class="nav-item <?= $current_page=='belum_absen'?'active':'' ?>">
            <i class="fas fa-user-times"></i> Belum Absen
        </a>
        <a href="<?= BASE_URL ?>edit_absensi.php" class="nav-item <?= $current_page=='edit_absensi'?'active':'' ?>">
            <i class="fas fa-pen-square"></i> Edit Absensi
        </a>

        <div class="nav-section">DATA SISWA</div>
        <a href="<?= BASE_URL ?>siswa.php" class="nav-item <?= $current_page=='siswa'?'active':'' ?>">
            <i class="fas fa-users"></i> Kelola Siswa
        </a>
        <a href="<?= BASE_URL ?>import_excel.php" class="nav-item <?= $current_page=='import_excel'?'active':'' ?>">
            <i class="fas fa-file-excel"></i> Import Excel
        </a>
        <a href="<?= BASE_URL ?>barcode_generate.php" class="nav-item <?= $current_page=='barcode_generate'?'active':'' ?>">
            <i class="fas fa-id-card"></i> Kartu QR Siswa
        </a>

        <div class="nav-section">LAPORAN</div>
        <a href="<?= BASE_URL ?>laporan_rekap_harian.php" class="nav-item <?= $current_page=='laporan_rekap_harian'?'active':'' ?>"
           style="<?= $current_page=='laporan_rekap_harian'?'':'border-left:3px solid #f59e0b;background:rgba(245,158,11,.08)' ?>">
            <i class="fas fa-clipboard-list" style="color:#f59e0b"></i>
            <span>Laporan Rekap Harian</span>
        </a>
        <a href="<?= BASE_URL ?>rekap_harian.php" class="nav-item <?= $current_page=='rekap_harian'?'active':'' ?>">
            <i class="fas fa-calendar-day"></i> Rekap Harian
        </a>
        <a href="<?= BASE_URL ?>rekap_bulanan.php" class="nav-item <?= $current_page=='rekap_bulanan'?'active':'' ?>">
            <i class="fas fa-calendar-alt"></i> Rekap Bulanan
        </a>
        <a href="<?= BASE_URL ?>rekap_status.php" class="nav-item <?= $current_page=='rekap_status'?'active':'' ?>">
            <i class="fas fa-chart-pie"></i> Rekap Status
        </a>
        <a href="<?= BASE_URL ?>grafik.php" class="nav-item <?= $current_page=='grafik'?'active':'' ?>">
            <i class="fas fa-chart-line"></i> Grafik
        </a>
        <a href="<?= BASE_URL ?>terlambat.php" class="nav-item <?= $current_page=='terlambat'?'active':'' ?>">
            <i class="fas fa-clock"></i> Terlambat
        </a>

        <div class="nav-section">MANAJEMEN</div>
        <a href="<?= BASE_URL ?>wali.php" class="nav-item <?= $current_page=='wali'?'active':'' ?>">
            <i class="fas fa-chalkboard-teacher"></i> Kelola Wali
        </a>
        <a href="<?= BASE_URL ?>pelanggaran.php" class="nav-item <?= $current_page=='pelanggaran'?'active':'' ?>">
            <i class="fas fa-exclamation-triangle" style="color:#f59e0b"></i> Kelola Pelanggaran
        </a>
        <a href="<?= BASE_URL ?>notif_wa_wali.php" class="nav-item <?= $current_page=='notif_wa_wali'?'active':'' ?>">
            <i class="fab fa-whatsapp" style="color:#25d366"></i> Notifikasi WA Wali
        </a>
        <a href="<?= BASE_URL ?>catatan.php" class="nav-item <?= $current_page=='catatan'?'active':'' ?>">
            <i class="fas fa-sticky-note"></i> Catatan Siswa
        </a>
        <a href="<?= BASE_URL ?>hapus_log.php" class="nav-item <?= $current_page=='hapus_log'?'active':'' ?>">
            <i class="fas fa-trash"></i> Hapus Log
        </a>

        <div class="nav-section">PENGATURAN</div>
        <a href="<?= BASE_URL ?>pengaturan.php" class="nav-item <?= $current_page=='pengaturan'?'active':'' ?>">
            <i class="fas fa-cog"></i> Pengaturan Sekolah
        </a>
        <a href="<?= BASE_URL ?>pengaturan_waktu.php" class="nav-item <?= $current_page=='pengaturan_waktu'?'active':'' ?>">
            <i class="fas fa-clock"></i> Pengaturan Waktu
        </a>
        <a href="<?= BASE_URL ?>atur_pin.php" class="nav-item <?= $current_page=='atur_pin'?'active':'' ?>">
            <i class="fas fa-key"></i> Kelola PIN Login
        </a>
        <a href="<?= BASE_URL ?>atur_pin_kepsek.php" class="nav-item <?= $current_page=='atur_pin_kepsek'?'active':'' ?>" target="_blank">
            <i class="fas fa-user-tie"></i> PIN Kepala Sekolah ↗
        </a>

        <div class="nav-section">PORTAL</div>
        <a href="<?= BASE_URL ?>portal_login.php?role=siswa" class="nav-item" target="_blank">
            <i class="fas fa-user-graduate"></i> Portal Siswa ↗
        </a>
        <a href="<?= BASE_URL ?>portal_login.php?role=wali" class="nav-item" target="_blank">
            <i class="fas fa-chalkboard-teacher"></i> Portal Wali ↗
        </a>
        <a href="<?= BASE_URL ?>portal_kepsek_login.php" class="nav-item" target="_blank">
            <i class="fas fa-user-tie"></i> Portal Kepsek ↗
        </a>

        <a href="<?= BASE_URL ?>logout.php" class="nav-item logout">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </nav>
</div>

<div class="main-content" id="mainContent">
    <header class="top-bar">
        <button class="sidebar-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
        <div class="top-bar-info">
            <div class="realtime-clock" id="realtimeClock"></div>
            <div class="admin-info"><i class="fas fa-user-shield"></i> <?= htmlspecialchars($_SESSION['admin_nama'] ?? 'Admin') ?></div>
        </div>
    </header>
    <div class="content-wrapper">
