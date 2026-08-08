<?php
require_once 'includes/config.php';
cek_siswa();

$sid   = $_SESSION['siswa_id'];
$siswa = $conn->query("SELECT * FROM siswa WHERE id=$sid")->fetch_assoc();
$pengaturan = get_pengaturan();

// Stats
$stats = $conn->query("SELECT status,COUNT(*) as t FROM absensi WHERE siswa_id=$sid" . periode_where($conn) . " GROUP BY status")->fetch_all(MYSQLI_ASSOC);
$stat  = ['Hadir'=>0,'Terlambat'=>0,'Alpa'=>0,'Sakit'=>0,'Izin'=>0,'Bolos'=>0,'total'=>0];
foreach ($stats as $s) { $stat[$s['status']]=$s['t']; $stat['total']+=$s['t']; }
$pct_hadir = $stat['total']>0 ? round(($stat['Hadir']+$stat['Terlambat'])/$stat['total']*100,1) : 0;

// Riwayat absensi
$riwayat = $conn->query("SELECT * FROM absensi WHERE siswa_id=$sid" . periode_where($conn) . " ORDER BY tanggal DESC LIMIT 60");

// ── Poin & Riwayat Pelanggaran (formula sama dengan portal_bk.php) ─────────
$conn->query("CREATE TABLE IF NOT EXISTS point_perbaikan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    siswa_id INT NOT NULL, nis VARCHAR(30), nama_siswa VARCHAR(100), kelas VARCHAR(20),
    kategori VARCHAR(20), jumlah INT DEFAULT 0, keterangan VARCHAR(255), tanggal DATE,
    guru_bk_id INT, nama_guru VARCHAR(100), tahun_ajaran VARCHAR(20), semester VARCHAR(10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$conn->query("CREATE TABLE IF NOT EXISTS pengaturan_poin_absen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    poin_alpa INT(11) DEFAULT 5, poin_terlambat INT(11) DEFAULT 2, keterangan VARCHAR(255) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$jmlPel  = (int)$conn->query("SELECT COUNT(*) c FROM pelanggaran WHERE siswa_id=$sid" . periode_where($conn))->fetch_assoc()['c'];
$poinPel = (int)($conn->query("SELECT COALESCE(SUM(COALESCE(poin,3)),0) s FROM pelanggaran WHERE siswa_id=$sid" . periode_where($conn))->fetch_assoc()['s'] ?? 0);
$poinBk  = 0;
$bkCheck = $conn->query("SHOW TABLES LIKE 'buku_kejadian'");
if ($bkCheck && $bkCheck->num_rows > 0) {
    $poinBk = (int)($conn->query("SELECT COALESCE(SUM(poin),0) s FROM buku_kejadian WHERE siswa_id=$sid" . periode_where($conn))->fetch_assoc()['s'] ?? 0);
}
$poinCfgRow       = $conn->query("SELECT * FROM pengaturan_poin_absen LIMIT 1")->fetch_assoc();
$poinAlpaCfg      = (int)($poinCfgRow['poin_alpa'] ?? 5);
$poinTerlambatCfg = (int)($poinCfgRow['poin_terlambat'] ?? 2);

$perbaikan = ['TERLAMBAT'=>0,'ALPA'=>0,'PELANGGARAN'=>0,'KUNJUNGAN'=>0];
$resPerb = $conn->query("SELECT kategori, SUM(jumlah) total FROM point_perbaikan WHERE siswa_id=$sid" . periode_where($conn) . " GROUP BY kategori");
if ($resPerb) { while ($r=$resPerb->fetch_assoc()) $perbaikan[$r['kategori']] = (int)$r['total']; }

$poinPelNet       = max(0, $poinPel - $perbaikan['PELANGGARAN']);
$poinTerlambatTot = $stat['Terlambat'] * $poinTerlambatCfg;
$poinAlpaTot      = $stat['Alpa'] * $poinAlpaCfg;
$poinTerlambatNet = max(0, $poinTerlambatTot - $perbaikan['TERLAMBAT']);
$poinAlpaNet      = max(0, $poinAlpaTot - $perbaikan['ALPA']);
$totalPoin        = max(0, $poinPelNet + $poinBk + $poinTerlambatNet + $poinAlpaNet - $perbaikan['KUNJUNGAN']);

// Riwayat gabungan: Pelanggaran Disiplin + Terlambat + Alpa (poin per kejadian)
$riwayatPoin = [];
$q3 = $conn->query("SELECT tanggal tgl, 'Pelanggaran Disiplin' tipe, UPPER(jenis_nama) judul, CONCAT('+',COALESCE(poin,3),' Poin') ket FROM pelanggaran WHERE siswa_id=$sid" . periode_where($conn));
while ($x=$q3->fetch_assoc()) $riwayatPoin[] = $x;
$q5 = $conn->query("SELECT tanggal tgl, 'Terlambat' tipe, 'TERLAMBAT' judul, CONCAT('+',$poinTerlambatCfg,' Poin') ket FROM absensi WHERE siswa_id=$sid AND status='Terlambat'" . periode_where($conn));
while ($x=$q5->fetch_assoc()) $riwayatPoin[] = $x;
$q6 = $conn->query("SELECT tanggal tgl, 'Alpa' tipe, 'ALPA' judul, CONCAT('+',$poinAlpaCfg,' Poin') ket FROM absensi WHERE siswa_id=$sid AND status='Alpa'" . periode_where($conn));
while ($x=$q6->fetch_assoc()) $riwayatPoin[] = $x;
usort($riwayatPoin, fn($a,$b)=>strtotime($b['tgl'])<=>strtotime($a['tgl']));

// Catatan
$catatan = $conn->query("SELECT c.*,COALESCE(w.nama,'Admin') as dari
    FROM catatan c LEFT JOIN wali w ON w.id=c.wali_id
    WHERE c.siswa_id=$sid" . periode_where($conn, 'c.') . " ORDER BY c.created_at DESC");

$tipe_colors=['Informasi'=>'#3b82f6','Peringatan'=>'#f59e0b','Urgent'=>'#ef4444','Apresiasi'=>'#10b981'];

// Logout
if (isset($_GET['logout'])) { session_destroy(); header('Location: '.BASE_URL.'portal_login.php?role=siswa'); exit; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        <div style="background:white;border-radius:12px;padding:16px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,.1);border-top:3px solid #ca8a04">
            <div style="font-size:1.6rem;font-weight:800;color:#ca8a04"><?= $jmlPel ?></div>
            <div style="font-size:.75rem;color:var(--text-muted)">Pelanggaran</div>
        </div>
        <div style="background:white;border-radius:12px;padding:16px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,.1);border-top:3px solid #be123c">
            <div style="font-size:1.6rem;font-weight:800;color:#be123c"><?= $totalPoin ?></div>
            <div style="font-size:.75rem;color:var(--text-muted)">Total Poin</div>
        </div>
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

    <!-- Riwayat Pelanggaran & Poin -->
    <div class="card mb-3">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
            <span><i class="fas fa-exclamation-triangle" style="color:#ca8a04"></i> Riwayat Pelanggaran & Poin</span>
            <button type="button" onclick="cetakRiwayatSiswa()" class="btn btn-sm" style="background:#1d4ed8;color:white">
                <i class="fas fa-print"></i> Cetak Riwayat
            </button>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr><th>No</th><th>Tanggal</th><th>Jenis</th><th>Keterangan</th><th>Poin</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($riwayatPoin)): ?>
                    <tr><td colspan="5" style="text-align:center;padding:40px;color:var(--text-muted)">Tidak ada pelanggaran/keterlambatan/alpa tercatat</td></tr>
                    <?php else: $no2=0; foreach ($riwayatPoin as $r): $no2++; ?>
                    <tr>
                        <td><?= $no2 ?></td>
                        <td><?= date('d/m/Y', strtotime($r['tgl'])) ?></td>
                        <td><?= htmlspecialchars($r['tipe']) ?></td>
                        <td><?= htmlspecialchars($r['judul']) ?></td>
                        <td><span style="color:#be123c;font-weight:700"><?= htmlspecialchars($r['ket']) ?></span></td>
                    </tr>
                    <?php endforeach; endif; ?>
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

<!-- ═══ PRINT AREA: RIWAYAT SISWA (khusus tampil saat cetak) ═══ -->
<style>
@media print {
    body { background: white !important; padding: 0 !important; }
    .portal-wrap { display: none !important; }
    .print-riwayat-siswa { display: block !important; }
}
.print-riwayat-siswa { display: none; font-family: Arial, sans-serif; font-size: 11pt; color: #000; }
.print-riwayat-siswa .pr-title { text-align:center; font-weight:bold; font-size:13pt; margin-bottom:18px; text-decoration:underline; }
.print-riwayat-siswa .pr-wrap { display:flex; gap:24px; align-items:flex-start; }
.print-riwayat-siswa .pr-foto { width:130px; height:150px; border-radius:12px; object-fit:cover; border:1px solid #000; }
.print-riwayat-siswa .pr-foto-placeholder { width:130px; height:150px; border-radius:12px; background:#1d4ed8; color:#fff;
    display:flex; align-items:center; justify-content:center; font-size:2.6rem; font-weight:800; }
.print-riwayat-siswa .pr-tbl { flex:1; border-collapse:collapse; font-size:11pt; }
.print-riwayat-siswa .pr-tbl td { padding:6px 8px; vertical-align:top; }
.print-riwayat-siswa .pr-tbl td:first-child { width:150px; font-weight:700; text-transform:uppercase; }
.print-riwayat-siswa .pr-tbl td:nth-child(2) { width:14px; }
</style>
<div class="print-riwayat-siswa">
    <div class="pr-title">RIWAYAT SISWA</div>
    <div class="pr-wrap">
        <?php if (!empty($siswa['foto']) && file_exists('uploads/foto/'.$siswa['foto'])): ?>
            <img src="<?= BASE_URL ?>uploads/foto/<?= $siswa['foto'] ?>" class="pr-foto">
        <?php else: ?>
            <div class="pr-foto-placeholder"><?= strtoupper(substr($siswa['nama'],0,1)) ?></div>
        <?php endif; ?>
        <table class="pr-tbl">
            <tr><td>NAMA</td><td>:</td><td><?= htmlspecialchars($siswa['nama']) ?></td></tr>
            <tr><td>KELAS</td><td>:</td><td><?= htmlspecialchars($siswa['kelas']) ?></td></tr>
            <tr><td>HADIR</td><td>:</td><td><?= $stat['Hadir'] ?></td></tr>
            <tr><td>TERLAMBAT</td><td>:</td><td><?= $stat['Terlambat'] ?></td></tr>
            <tr><td>PELANGGARAN</td><td>:</td><td><?= $jmlPel ?></td></tr>
            <tr><td>POINT</td><td>:</td><td><?= $totalPoin ?></td></tr>
            <tr><td>CATATAN</td><td>:</td><td>&nbsp;</td></tr>
        </table>
    </div>
</div>
<!-- ═══ END PRINT AREA ═══ -->

<script>
function cetakRiwayatSiswa() { window.print(); }
</script>
</body>
</html>
