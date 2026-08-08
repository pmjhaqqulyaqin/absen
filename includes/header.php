<?php
$pengaturan = get_pengaturan();
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$periode_aktif_hdr  = get_periode_aktif($conn);
$daftar_periode_hdr = get_daftar_periode($conn);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pengaturan['nama_sekolah']) ?> - Monitoring Siswa Digital</title>
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
        <div class="school-sub">Sistem Monitoring Siswa Digital</div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">UTAMA</div>
        <a href="<?= BASE_URL ?>dashboard.php" class="nav-item <?= $current_page=='dashboard'?'active':'' ?>">
            <i class="fas fa-tachometer-alt"></i> Dashboard
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
        <a href="<?= BASE_URL ?>kelola_kelas.php" class="nav-item <?= $current_page=='kelola_kelas'?'active':'' ?>">
            <i class="fas fa-people-arrows"></i> Kelola Kelas
        </a>
        <a href="<?= BASE_URL ?>import_excel.php" class="nav-item <?= $current_page=='import_excel'?'active':'' ?>">
            <i class="fas fa-file-excel"></i> Import Excel
        </a>

        <div class="nav-section">LAPORAN</div>
        <a href="<?= BASE_URL ?>laporan_rekap_harian.php" class="nav-item <?= $current_page=='laporan_rekap_harian'?'active':'' ?>"
           style="<?= $current_page=='laporan_rekap_harian'?'':'border-left:3px solid #f59e0b;background:rgba(245,158,11,.08)' ?>">
            <i class="fas fa-clipboard-list" style="color:#f59e0b"></i>
            <span>Laporan Rekap Harian</span>
        </a>
        <a href="<?= BASE_URL ?>rekap_bulanan.php" class="nav-item <?= $current_page=='rekap_bulanan'?'active':'' ?>">
            <i class="fas fa-calendar-alt"></i> Rekap Bulanan
        </a>
        <a href="<?= BASE_URL ?>grafik.php" class="nav-item <?= $current_page=='grafik'?'active':'' ?>">
            <i class="fas fa-chart-line"></i> Grafik
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

        <!-- Menu PORTAL (Portal Siswa/Wali/Kepsek) dihapus dari sidebar admin karena
             portal-portal tersebut sudah bisa diakses langsung dari luar (tidak perlu
             lagi ditampilkan sebagai menu di dalam panel admin). -->

        <div class="nav-section">DATABASE</div>
        <a href="<?= BASE_URL ?>backup_db.php" class="nav-item <?= $current_page=='backup_db'?'active':'' ?>"
           style="background:linear-gradient(135deg,rgba(37,99,235,.15),rgba(29,78,216,.1));border-left:3px solid #2563eb;">
            <i class="fas fa-database" style="color:#2563eb"></i>
            <span style="color:#93c5fd;font-weight:600;">Backup &amp; Restore DB</span>
        </a>

        <a href="<?= BASE_URL ?>logout.php" class="nav-item logout">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </nav>
</div>

<div class="main-content" id="mainContent">
    <header class="top-bar" style="position:relative;">
        <button class="sidebar-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>

        <!-- PEMILIH TAHUN AJARAN & SEMESTER - selalu di tengah topbar -->
        <div style="position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);">
            <button type="button" id="btnPeriode" onclick="togglePeriodePanel()"
                style="display:inline-flex;align-items:center;gap:6px;background:linear-gradient(135deg,#0f766e,#0d9488);
                       color:#fff;border:none;text-decoration:none;padding:8px 18px;border-radius:8px;font-size:.85rem;
                       font-weight:700;box-shadow:0 2px 8px rgba(13,148,136,.35);cursor:pointer;white-space:nowrap;">
                <i class="fas fa-calendar-check"></i>
                <span>Semester <?= htmlspecialchars($periode_aktif_hdr['semester']) ?> TP. <?= htmlspecialchars($periode_aktif_hdr['tahun_ajaran']) ?></span>
                <i class="fas fa-caret-down"></i>
            </button>

            <div id="panelPeriode" style="display:none;position:absolute;left:50%;transform:translateX(-50%);top:calc(100% + 8px);width:280px;
                 background:#1e293b;border:1px solid #334155;border-radius:10px;box-shadow:0 10px 30px rgba(0,0,0,.4);
                 padding:14px;z-index:999;text-align:left;">
                <div style="font-size:.75rem;color:#94a3b8;font-weight:700;text-transform:uppercase;margin-bottom:8px;">
                    Pilih Tahun Ajaran &amp; Semester
                </div>
                <div id="listPeriode" style="display:flex;flex-direction:column;gap:4px;margin-bottom:12px;max-height:200px;overflow-y:auto;">
                    <?php foreach ($daftar_periode_hdr as $p):
                        $isAktif = ((int)$p['id'] === (int)$periode_aktif_hdr['id']); ?>
                        <button type="button" onclick="gantiPeriode(<?= (int)$p['id'] ?>)"
                            style="text-align:left;padding:8px 10px;border-radius:6px;border:none;cursor:pointer;
                                   font-size:.85rem;font-weight:600;
                                   background:<?= $isAktif ? '#0d9488' : '#334155' ?>;color:#fff;">
                            <?= $isAktif ? '✅ ' : '' ?><?= htmlspecialchars($p['tahun_ajaran']) ?> - <?= htmlspecialchars($p['semester']) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <div style="border-top:1px solid #334155;padding-top:10px;">
                    <a href="<?= BASE_URL ?>pengaturan.php#kelola-periode"
                       style="display:flex;align-items:center;justify-content:center;gap:6px;width:100%;padding:8px;
                              border-radius:6px;background:#334155;color:#e2e8f0;text-decoration:none;
                              font-weight:600;font-size:.85rem;box-sizing:border-box;">
                        <i class="fas fa-cog"></i> Kelola Tahun Ajaran &amp; Semester
                    </a>
                </div>
            </div>
        </div>
        <script>
        function togglePeriodePanel() {
            const p = document.getElementById('panelPeriode');
            p.style.display = (p.style.display === 'none' || !p.style.display) ? 'block' : 'none';
        }
        document.addEventListener('click', function(e) {
            const wrap = document.getElementById('btnPeriode');
            const panel = document.getElementById('panelPeriode');
            if (panel && wrap && !wrap.contains(e.target) && !panel.contains(e.target)) {
                panel.style.display = 'none';
            }
        });
        function gantiPeriode(id) {
            if (!confirm('Ganti periode aktif? Semua tampilan data akan mengikuti periode yang dipilih.')) return;
            const fd = new FormData();
            fd.append('action', 'switch');
            fd.append('id', id);
            fetch('<?= BASE_URL ?>ajax/periode.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    if (d.success) { location.reload(); }
                    else { alert(d.msg || 'Gagal ganti periode'); }
                });
        }
        </script>

        <div class="top-bar-info">
            <div class="realtime-clock" id="realtimeClock"></div>

            <!-- TOMBOL BACKUP & RESTORE DATABASE -->
            <a href="<?= BASE_URL ?>backup_db.php" title="Backup & Restore Database"
               style="display:inline-flex;align-items:center;gap:6px;background:linear-gradient(135deg,#2563eb,#1d4ed8);
                      color:#fff;text-decoration:none;padding:7px 14px;border-radius:8px;font-size:.82rem;
                      font-weight:600;box-shadow:0 2px 8px rgba(37,99,235,.35);transition:all .2s;"
               onmouseover="this.style.transform='translateY(-1px)';this.style.boxShadow='0 4px 14px rgba(37,99,235,.5)'"
               onmouseout="this.style.transform='';this.style.boxShadow='0 2px 8px rgba(37,99,235,.35)'">
                <i class="fas fa-database"></i>
                <span>Backup DB</span>
            </a>
            <div class="admin-info"><i class="fas fa-user-shield"></i> <?= htmlspecialchars($_SESSION['admin_nama'] ?? 'Admin') ?></div>
        </div>
    </header>
    <div class="content-wrapper">
