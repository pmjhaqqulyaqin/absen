<?php
require_once 'includes/config.php';
cek_login();

$today = date('Y-m-d');
$tanggal = sanitize($_GET['tanggal'] ?? $today);
$kelas   = sanitize($_GET['kelas'] ?? '');
$kelas_list = get_kelas_list();
$pengaturan = get_pengaturan();

$where = "a.tanggal='$tanggal'";
if ($kelas) $where .= " AND a.kelas='$kelas'";

$data = $conn->query("SELECT a.*, s.foto FROM absensi a 
    LEFT JOIN siswa s ON s.id=a.siswa_id 
    WHERE $where ORDER BY a.kelas, a.nama");

// Stats
$stats = $conn->query("SELECT status, COUNT(*) as total FROM absensi WHERE tanggal='$tanggal'" . ($kelas?" AND kelas='$kelas'":'') . " GROUP BY status")->fetch_all(MYSQLI_ASSOC);
$stat_arr = array_column($stats, 'total', 'status');

include 'includes/header.php';
?>

<div class="page-header d-flex align-center">
    <div>
        <div class="page-title"><i class="fas fa-calendar-day"></i> Rekap Harian</div>
        <div class="page-subtitle"><?= format_tanggal($tanggal) ?></div>
    </div>
    <div class="ms-auto">
        <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> Print</button>
        <a href="ajax/export_harian.php?tanggal=<?= $tanggal ?>&kelas=<?= urlencode($kelas) ?>" class="btn btn-success">
            <i class="fas fa-file-csv"></i> Export CSV
        </a>
    </div>
</div>

<!-- Filter -->
<div class="card mb-3 no-print">
    <div class="card-body">
        <form method="GET" class="filter-bar">
            <div>
                <label class="form-label">Tanggal</label>
                <input type="date" name="tanggal" class="form-control" value="<?= $tanggal ?>">
            </div>
            <div>
                <label class="form-label">Kelas</label>
                <select name="kelas" class="form-select">
                    <option value="">Semua Kelas</option>
                    <?php foreach ($kelas_list as $k): ?>
                        <option value="<?= $k ?>" <?= $kelas==$k?'selected':'' ?>><?= $k ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:flex;align-items:flex-end">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Tampilkan</button>
            </div>
        </form>
    </div>
</div>

<!-- Stats Row -->
<div class="stats-grid mb-3 no-print" style="grid-template-columns:repeat(auto-fit,minmax(120px,1fr))">
    <?php
    $stat_config = [
        'Hadir' => ['icon'=>'user-check','class'=>'hadir'],
        'Terlambat' => ['icon'=>'clock','class'=>'terlambat'],
        'Alpa' => ['icon'=>'times-circle','class'=>'alpa'],
        'Sakit' => ['icon'=>'heartbeat','class'=>'sakit'],
        'Izin' => ['icon'=>'clipboard-list','class'=>'izin'],
        'Bolos' => ['icon'=>'ban','class'=>'bolos'],
    ];
    foreach ($stat_config as $st => $cfg):
    ?>
    <div class="stat-card <?= $cfg['class'] ?>">
        <div class="stat-icon"><i class="fas fa-<?= $cfg['icon'] ?>"></i></div>
        <div>
            <div class="stat-value"><?= $stat_arr[$st] ?? 0 ?></div>
            <div class="stat-label"><?= $st ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Header Print -->
<div class="print-header" style="display:none;text-align:center;margin-bottom:20px">
    <?php if (!empty($pengaturan['logo']) && file_exists(__DIR__.'/uploads/logo/'.$pengaturan['logo'])): ?>
        <img src="<?= BASE_URL ?>uploads/logo/<?= $pengaturan['logo'] ?>" style="height:70px;margin-bottom:8px">
    <?php endif; ?>
    <div style="font-size:1.2rem;font-weight:700;text-transform:uppercase"><?= htmlspecialchars($pengaturan['nama_sekolah']) ?></div>
    <div style="font-size:.85rem"><?= htmlspecialchars($pengaturan['alamat'] ?? '') ?></div>
    <hr style="border:2px solid #000;margin:8px 0">
    <div style="font-size:1rem;font-weight:700">REKAP ABSENSI HARIAN</div>
    <div style="font-size:.9rem"><?= format_tanggal($tanggal) ?><?= $kelas ? ' - Kelas '.$kelas : '' ?></div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-table" style="color:var(--primary)"></i> Data Absensi
        <div class="ms-auto no-print">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Cari...">
            </div>
        </div>
    </div>
    <div class="table-container">
        <table id="mainTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>NIS</th>
                    <th>Nama</th>
                    <th>Kelas</th>
                    <th>Jam Masuk</th>
                    <th>Jam Pulang</th>
                    <th>Status</th>
                    <th>Keterangan</th>
                    <th>Metode</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($data->num_rows === 0): ?>
                <tr><td colspan="9" style="text-align:center;padding:40px;color:var(--text-muted)">
                    Tidak ada data untuk tanggal ini
                </td></tr>
                <?php else:
                $no = 0;
                while ($row = $data->fetch_assoc()):
                $no++; ?>
                <tr>
                    <td><?= $no ?></td>
                    <td><?= $row['nis'] ?></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px">
                            <?php if (!empty($row['foto']) && file_exists(__DIR__.'/uploads/foto/'.$row['foto'])): ?>
                                <img src="<?= BASE_URL ?>uploads/foto/<?= $row['foto'] ?>" class="student-photo" style="width:32px;height:32px">
                            <?php else: ?>
                                <div class="student-avatar" style="width:32px;height:32px;font-size:.75rem;flex-shrink:0">
                                    <?= strtoupper(substr($row['nama'],0,1)) ?>
                                </div>
                            <?php endif; ?>
                            <?= htmlspecialchars($row['nama']) ?>
                        </div>
                    </td>
                    <td><?= $row['kelas'] ?></td>
                    <td><?= $row['jam_masuk'] ? date('H:i', strtotime($row['jam_masuk'])) : '-' ?></td>
                    <td><?= $row['jam_pulang'] ? date('H:i', strtotime($row['jam_pulang'])) : '-' ?></td>
                    <td><?= get_status_badge($row['status']) ?></td>
                    <td><?= htmlspecialchars($row['keterangan'] ?? '-') ?></td>
                    <td><small><?= $row['metode'] ?></small></td>
                </tr>
                <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Tanda Tangan Kepala Sekolah -->
<div class="ttd-section" style="margin-top:40px;display:flex;justify-content:flex-end;padding-right:40px">
    <div style="text-align:center;min-width:220px">
        <?php
        $bln = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        $tgl_ttd = date('d') . ' ' . $bln[(int)date('n')] . ' ' . date('Y');
        ?>
        <div><?= htmlspecialchars($pengaturan['nama_sekolah'] ?? '') ?>, <?= $tgl_ttd ?></div>
        <div style="margin-top:4px">Kepala Sekolah,</div>
        <div style="margin-top:70px;font-weight:700;text-decoration:underline">
            <?= htmlspecialchars($pengaturan['kepala_sekolah'] ?? '__________________________') ?>
        </div>
        <div style="font-size:.85rem">NIP. <?= htmlspecialchars($pengaturan['nip_kepala'] ?? '-') ?></div>
    </div>
</div>

<style>
@media print {
    .no-print, .sidebar, .top-bar, .btn, header { display:none !important; }
    .main-content { margin-left:0 !important; }
    .content-wrapper { padding:0 !important; }
    .print-header { display:block !important; }
    .ttd-section { display:flex !important; }
}
</style>

<?php include 'includes/footer.php'; ?>
