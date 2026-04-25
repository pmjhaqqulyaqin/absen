<?php
require_once 'includes/config.php';
cek_siswa();

$sid   = $_SESSION['siswa_id'];
$siswa = $conn->query("SELECT * FROM siswa WHERE id=$sid")->fetch_assoc();
$pengaturan = get_pengaturan();

// Stats
$stats = $conn->query("SELECT status,COUNT(*) as t FROM absensi WHERE siswa_id=$sid GROUP BY status")->fetch_all(MYSQLI_ASSOC);
$stat  = ['Hadir'=>0,'Terlambat'=>0,'Alpa'=>0,'Sakit'=>0,'Izin'=>0,'Bolos'=>0,'total'=>0];
foreach ($stats as $s) { $stat[$s['status']]=$s['t']; $stat['total']+=$s['t']; }
$pct_hadir = $stat['total']>0 ? round(($stat['Hadir']+$stat['Terlambat'])/$stat['total']*100,1) : 0;

// Riwayat absensi
$riwayat = $conn->query("SELECT * FROM absensi WHERE siswa_id=$sid ORDER BY tanggal DESC LIMIT 60");

// Catatan
$catatan = $conn->query("SELECT c.*,COALESCE(w.nama,'Admin') as dari
    FROM catatan c LEFT JOIN wali w ON w.id=c.wali_id
    WHERE c.siswa_id=$sid ORDER BY c.created_at DESC");

$tipe_colors=['Informasi'=>'#3b82f6','Peringatan'=>'#f59e0b','Urgent'=>'#ef4444','Apresiasi'=>'#10b981'];

// Logout
if (isset($_GET['logout'])) { session_destroy(); header('Location: '.BASE_URL.'portal_login.php?role=siswa'); exit; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- PWA: Theme & Status Bar -->
    <meta name="theme-color" content="#1a2332">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Absensi MAN2">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="application-name" content="Absensi MAN2">
    <meta name="msapplication-TileColor" content="#1a2332">
    <meta name="msapplication-TileImage" content="assets/pwa/pwa-icon-192x192.png">

    <!-- PWA: Web App Manifest & Apple Touch Icon -->
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
          navigator.serviceWorker.register('<?= BASE_URL ?>sw.js')
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
    <title>Portal Siswa - <?= htmlspecialchars($siswa['nama']) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    body { background:linear-gradient(135deg, #0f172a 0%, #1e3a8a 80%); min-height:100vh; padding:20px; }
    .portal-wrap { max-width:900px; margin:0 auto; }
    .profile-card { background:white; border-radius:20px; padding:32px; margin-bottom:20px;
        display:flex; align-items:center; gap:24px; box-shadow:0 10px 40px rgba(0,0,0,.2); }
    .profile-photo { width:100px; height:100px; border-radius:50%; object-fit:cover;
        border:4px solid var(--primary); flex-shrink:0; }
    .profile-placeholder { width:100px; height:100px; border-radius:50%; background:var(--primary);
        display:flex; align-items:center; justify-content:center; color:white; font-size:2.5rem;
        font-weight:800; flex-shrink:0; border:4px solid #1d4ed8; }
    .pct-ring { width:80px; height:80px; margin-left:auto; flex-shrink:0; }
    </style>
</head>
<body>
<div class="portal-wrap">

    <!-- Header bar -->
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
        <div style="color:rgba(255,255,255,.7);font-size:.85rem">
            <i class="fas fa-school"></i> <?= htmlspecialchars($pengaturan['nama_sekolah']) ?>
        </div>
        <a href="?logout=1" class="btn btn-sm" style="background:rgba(255,255,255,.15);color:white">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>

    <!-- Profile Card -->
    <div class="profile-card">
        <?php if (!empty($siswa['foto']) && file_exists('uploads/foto/'.$siswa['foto'])): ?>
            <img src="<?= BASE_URL ?>uploads/foto/<?= $siswa['foto'] ?>" class="profile-photo">
        <?php else: ?>
            <div class="profile-placeholder"><?= strtoupper(substr($siswa['nama'],0,1)) ?></div>
        <?php endif; ?>
        <div>
            <h2 style="margin:0;font-size:1.5rem"><?= htmlspecialchars($siswa['nama']) ?></h2>
            <div style="color:var(--text-muted);margin-top:6px">
                <span class="badge" style="background:#eff6ff;color:var(--primary)">NIS: <?= $siswa['nis'] ?></span>
                <span class="badge" style="background:#f0fdf4;color:#15803d;margin-left:4px">Kelas: <?= $siswa['kelas'] ?></span>
            </div>
        </div>
        <!-- % Kehadiran donut -->
        <div class="pct-ring" style="text-align:center">
            <svg viewBox="0 0 36 36" style="transform:rotate(-90deg)">
                <circle cx="18" cy="18" r="15.9" fill="none" stroke="#e2e8f0" stroke-width="3"/>
                <circle cx="18" cy="18" r="15.9" fill="none" stroke="<?= $pct_hadir>=80?'#16a34a':($pct_hadir>=60?'#d97706':'#dc2626') ?>" stroke-width="3"
                    stroke-dasharray="<?= $pct_hadir ?> 100" stroke-linecap="round"/>
            </svg>
            <div style="margin-top:-60px;font-size:1.1rem;font-weight:800"><?= $pct_hadir ?>%</div>
            <div style="font-size:.65rem;color:var(--text-muted);margin-top:56px">Kehadiran</div>
        </div>
    </div>

    <!-- Stats -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:12px;margin-bottom:20px">
        <?php foreach ([
            'Hadir'=>['#16a34a','#f0fdf4','user-check'],
            'Terlambat'=>['#d97706','#fffbeb','clock'],
            'Sakit'=>['#0891b2','#eff6ff','heartbeat'],
            'Izin'=>['#7c3aed','#f5f3ff','clipboard-list'],
            'Alpa'=>['#64748b','#f8fafc','times-circle'],
            'Bolos'=>['#dc2626','#fef2f2','ban'],
        ] as $s=>[$c,$bg,$ic]): ?>
        <div style="background:white;border-radius:12px;padding:16px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,.1);border-top:3px solid <?= $c ?>">
            <div style="font-size:1.6rem;font-weight:800;color:<?= $c ?>"><?= $stat[$s] ?></div>
            <div style="font-size:.75rem;color:var(--text-muted)"><?= $s ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Riwayat Absensi -->
    <div class="card mb-3">
        <div class="card-header"><i class="fas fa-calendar-alt" style="color:var(--primary)"></i> Riwayat Absensi (60 hari terakhir)</div>
        <div class="table-container">
            <table>
                <thead>
                    <tr><th>No</th><th>Hari</th><th>Tanggal</th><th>Status</th><th>Jam</th><th>Metode</th></tr>
                </thead>
                <tbody>
                    <?php $no=0; if ($riwayat->num_rows===0): ?>
                    <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--text-muted)">Belum ada riwayat absensi</td></tr>
                    <?php else: while ($r=$riwayat->fetch_assoc()): $no++; ?>
                    <tr>
                        <td><?= $no ?></td>
                        <td><?= date('l', strtotime($r['tanggal'])) ?></td>
                        <td><?= date('d/m/Y', strtotime($r['tanggal'])) ?></td>
                        <td><?= get_status_badge($r['status']) ?></td>
                        <td><?= $r['jam_masuk'] ? date('H:i',strtotime($r['jam_masuk'])) : '-' ?></td>
                        <td><small><?= $r['metode'] ?></small></td>
                    </tr>
                    <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Catatan dari Wali/Guru -->
    <?php if ($catatan->num_rows > 0): ?>
    <div class="card" style="margin-bottom:20px">
        <div class="card-header"><i class="fas fa-sticky-note" style="color:var(--warning)"></i> Catatan dari Guru/Wali</div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:12px">
            <?php while ($c=$catatan->fetch_assoc()):
                $clr=$tipe_colors[$c['tipe']]??'#64748b'; ?>
            <div style="border-left:4px solid <?= $clr ?>;padding:12px 16px;background:#f8fafc;border-radius:8px">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                    <span style="background:<?= $clr ?>;color:white;padding:2px 10px;border-radius:20px;font-size:.72rem;font-weight:600"><?= $c['tipe'] ?></span>
                    <strong><?= htmlspecialchars($c['judul']) ?></strong>
                </div>
                <p style="margin:0;font-size:.875rem;color:var(--text-muted)"><?= nl2br(htmlspecialchars($c['isi'])) ?></p>
                <small style="color:var(--text-muted);margin-top:6px;display:block">
                    dari <?= $c['dari'] ?> &bull; <?= date('d/m/Y H:i',strtotime($c['created_at'])) ?>
                </small>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>

</div>
<?php include 'includes/pwa_banner.php'; ?>
</body>
</html>
