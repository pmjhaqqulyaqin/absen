<?php
/**
 * includes/header_bk.php
 * Header + sidebar KHUSUS Portal BK — TIDAK memakai sidebar Admin.
 * Dipakai oleh portal_bk.php (login via $_SESSION['bk_id'], bukan admin_id).
 */
$pengaturan = get_pengaturan();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal BK - <?= htmlspecialchars($pengaturan['nama_sekolah']) ?></title>
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
        <div class="school-sub">Portal Bimbingan Konseling</div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">PORTAL BK</div>
        <a href="<?= BASE_URL ?>portal_bk.php" class="nav-item active">
            <i class="fas fa-user-graduate" style="color:#7c3aed"></i> Data Siswa &amp; Riwayat
        </a>

        <a href="<?= BASE_URL ?>portal_bk.php?logout=1" class="nav-item logout">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </nav>
</div>

<div class="main-content" id="mainContent">
    <header class="top-bar">
        <button class="sidebar-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
        <div class="top-bar-info">
            <div class="realtime-clock" id="realtimeClock"></div>
            <div class="admin-info"><i class="fas fa-user-graduate"></i> <?= htmlspecialchars($_SESSION['bk_nama'] ?? 'Guru BK') ?></div>
        </div>
    </header>
    <div class="content-wrapper">
