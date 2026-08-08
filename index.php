<?php
require_once 'includes/config.php';

// Redirect jika sudah login admin
if (isset($_SESSION['admin_id'])) { header('Location: dashboard.php'); exit; }

$pengaturan = get_pengaturan();
$stats = get_stats_hari_ini();
$periode_aktif = get_periode_aktif($conn);

$hari  = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
$bulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$tgl_indo = $hari[date('w')].', '.date('d').' '.$bulan[(int)date('n')].' '.date('Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pengaturan['nama_sekolah']) ?> - Monitoring Siswa Digital</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

    /* HERO */
    .hero{position:relative;overflow:hidden;min-height:220px}
    .hero-bg-grad{position:absolute;top:0;left:0;width:100%;height:100%;background:linear-gradient(135deg,#1e3a8a,#0891b2,#0f172a)}
    .hero-content{position:relative;z-index:2;padding:40px 32px;text-align:center;display:flex;flex-direction:column;align-items:center;gap:10px}
    .hero-title{font-size:clamp(1.3rem,3.5vw,2rem);font-weight:900}
    .hero-sub{color:#cbd5e1;font-size:.9rem}
    .hero-date{background:rgba(0,0,0,.35);border:1px solid rgba(255,255,255,.12);display:inline-block;padding:7px 18px;border-radius:30px;font-size:.85rem;font-weight:600;margin-top:6px}

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
    .main{max-width:980px;margin:0 auto;padding:8px 20px 32px}

    /* AKSES PORTAL */
    .portal-section{padding:28px 0 10px}
    .portal-section-title{font-size:.95rem;font-weight:800;color:#e2e8f0;text-transform:uppercase;letter-spacing:1px;margin-bottom:4px;display:flex;align-items:center;gap:8px}
    .portal-section-sub{font-size:.8rem;color:#64748b;margin-bottom:18px}
    .portal-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}
    @media(max-width:820px){.portal-grid{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:480px){.portal-grid{grid-template-columns:1fr}}
    .portal-card{display:flex;flex-direction:column;gap:12px;padding:22px;border-radius:18px;text-decoration:none;transition:.2s;border:1.5px solid transparent;color:#e2e8f0}
    .portal-card:hover{transform:translateY(-3px);filter:brightness(1.12)}
    .portal-card .pc-icon{width:46px;height:46px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.25rem}
    .portal-card .pc-title{font-weight:800;font-size:.94rem;margin-bottom:2px}
    .portal-card .pc-sub{font-size:.76rem;font-weight:700;opacity:1}

    /* Wali Kelas — ungu */
    .pc-wali{background:rgba(124,58,237,.16);border-color:rgba(167,139,250,.5)}
    .pc-wali .pc-icon{background:rgba(124,58,237,.3);color:#ddd6fe}
    .pc-wali .pc-title{color:#c4b5fd}
    .pc-wali .pc-sub{color:#a78bfa}

    /* Kepala Sekolah — oranye tua */
    .pc-kepsek{background:rgba(234,88,12,.16);border-color:rgba(251,146,60,.5)}
    .pc-kepsek .pc-icon{background:rgba(234,88,12,.3);color:#ffedd5}
    .pc-kepsek .pc-title{color:#fb923c}
    .pc-kepsek .pc-sub{color:#fdba74}

    /* Siswa — biru */
    .pc-siswa{background:rgba(37,99,235,.16);border-color:rgba(96,165,250,.5)}
    .pc-siswa .pc-icon{background:rgba(37,99,235,.3);color:#dbeafe}
    .pc-siswa .pc-title{color:#60a5fa}
    .pc-siswa .pc-sub{color:#93c5fd}

    /* Pelanggaran Disiplin — kuning emas (dibedakan dari oranye Kepsek) */
    .pc-disiplin{background:rgba(202,138,4,.16);border-color:rgba(250,204,21,.5)}
    .pc-disiplin .pc-icon{background:rgba(202,138,4,.3);color:#fef9c3}
    .pc-disiplin .pc-title{color:#facc15}
    .pc-disiplin .pc-sub{color:#fde047}

    /* Bimbingan Konseling — cyan/teal */
    .pc-bk{background:rgba(8,145,178,.16);border-color:rgba(34,211,238,.5)}
    .pc-bk .pc-icon{background:rgba(8,145,178,.3);color:#cffafe}
    .pc-bk .pc-title{color:#22d3ee}
    .pc-bk .pc-sub{color:#67e8f9}

    /* Admin — merah muda keabuan supaya beda dari 5 warna lain */
    .pc-admin{background:rgba(225,29,72,.14);border-color:rgba(251,113,133,.45)}
    .pc-admin .pc-icon{background:rgba(225,29,72,.28);color:#ffe4e6}
    .pc-admin .pc-title{color:#fb7185}
    .pc-admin .pc-sub{color:#fda4af}

    footer{text-align:center;padding:20px;color:#334155;font-size:.75rem;border-top:1px solid rgba(255,255,255,.04);margin-top:12px}
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <a href="index.php" class="navbar-brand">
        <?php if (!empty($pengaturan['logo']) && file_exists(__DIR__.'/uploads/logo/'.$pengaturan['logo'])): ?>
            <img src="<?= BASE_URL ?>uploads/logo/<?= $pengaturan['logo'] ?>" class="navbar-logo" alt="Logo">
        <?php else: ?>
            <div class="navbar-logo-icon"><i class="fas fa-school"></i></div>
        <?php endif; ?>
        <div>
            <div class="navbar-title"><?= htmlspecialchars($pengaturan['nama_sekolah']) ?></div>
            <div class="navbar-sub">Monitoring Siswa Digital</div>
        </div>
    </a>
    <div class="navbar-clock" id="navClock"><?= date('H:i:s') ?></div>
</nav>

<!-- HERO -->
<div class="hero">
    <div class="hero-bg-grad"></div>
    <div class="hero-content">
        <div class="hero-title">Monitoring Siswa Digital<br><?= htmlspecialchars($pengaturan['nama_sekolah']) ?></div>
        <div class="hero-sub">Pantau kehadiran, kedisiplinan, dan perkembangan siswa secara online</div>
        <div style="display:flex;flex-wrap:wrap;gap:8px;justify-content:center;margin-top:6px">
            <div class="hero-date"><i class="fas fa-calendar-alt" style="margin-right:7px;color:#38bdf8"></i><?= $tgl_indo ?></div>
            <div class="hero-date"><i class="fas fa-graduation-cap" style="margin-right:7px;color:#facc15"></i>TP. <?= htmlspecialchars($periode_aktif['tahun_ajaran']) ?> &bull; Semester <?= htmlspecialchars($periode_aktif['semester']) ?></div>
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

    <!-- AKSES PORTAL -->
    <div class="portal-section">
        <div class="portal-section-title"><i class="fas fa-th-large"></i> Akses Portal</div>
        <div class="portal-section-sub">Pilih portal sesuai peran Anda</div>
        <div class="portal-grid">
            <a href="portal_login.php?role=wali" class="portal-card pc-wali">
                <div class="pc-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                <div>
                    <div class="pc-title">Wali Kelas</div>
                    <div class="pc-sub">Monitoring kehadiran kelas</div>
                </div>
            </a>
            <a href="portal_kepsek_login.php" class="portal-card pc-kepsek">
                <div class="pc-icon"><i class="fas fa-user-tie"></i></div>
                <div>
                    <div class="pc-title">Kepala Sekolah</div>
                    <div class="pc-sub">Monitoring & rekap KBM</div>
                </div>
            </a>
            <a href="portal_login.php?role=siswa" class="portal-card pc-siswa">
                <div class="pc-icon"><i class="fas fa-user-graduate"></i></div>
                <div>
                    <div class="pc-title">Siswa</div>
                    <div class="pc-sub">Ujian & tugas dari guru</div>
                </div>
            </a>
            <a href="portal_disiplin_login.php" class="portal-card pc-disiplin">
                <div class="pc-icon"><i class="fas fa-shield-alt"></i></div>
                <div>
                    <div class="pc-title">Pelanggaran Disiplin</div>
                    <div class="pc-sub">Catat & pantau pelanggaran</div>
                </div>
            </a>
            <a href="portal_bk_login.php" class="portal-card pc-bk">
                <div class="pc-icon"><i class="fas fa-user-shield"></i></div>
                <div>
                    <div class="pc-title">Bimbingan Konseling</div>
                    <div class="pc-sub">Layanan BK siswa</div>
                </div>
            </a>
            <a href="login.php" class="portal-card pc-admin">
                <div class="pc-icon"><i class="fas fa-user-cog"></i></div>
                <div>
                    <div class="pc-title">Admin</div>
                    <div class="pc-sub">Kelola sistem sekolah</div>
                </div>
            </a>
        </div>
    </div>

</div><!-- /main -->

<footer>&copy; <?= date('Y') ?> <?= htmlspecialchars($pengaturan['nama_sekolah']) ?> — Monitoring Siswa Digital</footer>

<script>
// Clock
setInterval(()=>{
    const n=new Date();
    document.getElementById('navClock').textContent=String(n.getHours()).padStart(2,'0')+':'+String(n.getMinutes()).padStart(2,'0')+':'+String(n.getSeconds()).padStart(2,'0');
},1000);
</script>
</body>
</html>
