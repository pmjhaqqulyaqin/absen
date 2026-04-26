<?php
require_once 'includes/config.php';
cek_login();

$bulan      = (int)($_GET['bulan'] ?? date('n'));
$tahun      = (int)($_GET['tahun'] ?? date('Y'));
$kelas      = sanitize($_GET['kelas'] ?? '');
$kelas_list = $conn->query("SELECT nama_kelas FROM kelas ORDER BY nama_kelas");
// Fallback: ambil dari data siswa jika tabel kelas kosong
if ($kelas_list->num_rows === 0) {
    $kelas_list = $conn->query("SELECT DISTINCT kelas AS nama_kelas FROM siswa ORDER BY kelas");
}
$pengaturan = get_pengaturan();
$nama_bulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

// Jumlah hari dalam bulan ini
$jumlah_hari = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);

// Ambil data siswa sesuai kelas
$kelas_sql = $kelas ? "AND kelas='$kelas'" : '';
$siswa_list = $conn->query("SELECT id, nis, nama, kelas FROM siswa WHERE 1=1 $kelas_sql ORDER BY kelas, nama");

// Ambil semua data absensi bulan ini
$absensi_raw = $conn->query("
    SELECT siswa_id, DAY(tanggal) as tgl, status
    FROM absensi
    WHERE MONTH(tanggal)=$bulan AND YEAR(tanggal)=$tahun
    " . ($kelas ? "AND siswa_id IN (SELECT id FROM siswa WHERE kelas='$kelas')" : "") . "
");
$absensi_map = [];
while ($a = $absensi_raw->fetch_assoc()) {
    $absensi_map[$a['siswa_id']][$a['tgl']] = $a['status'];
}

// Kode warna status
$status_kode = [
    'Hadir'     => ['H', '#16a34a', '#dcfce7'],
    'Terlambat' => ['T', '#d97706', '#fef3c7'],
    'Alpa'      => ['A', '#dc2626', '#fee2e2'],
    'Sakit'     => ['S', '#2563eb', '#dbeafe'],
    'Izin'      => ['I', '#7c3aed', '#ede9fe'],
    'Bolos'     => ['B', '#9a3412', '#ffedd5'],
];

include 'includes/header.php';
?>

<div class="page-header d-flex align-center">
    <div>
        <div class="page-title"><i class="fas fa-calendar-alt"></i> Rekap Bulanan</div>
        <div class="page-subtitle">Daftar Hadir <?= $nama_bulan[$bulan].' '.$tahun ?><?= $kelas ? ' - Kelas '.$kelas : '' ?></div>
    </div>
    <div class="ms-auto no-print" style="display:flex;gap:8px;flex-wrap:wrap">
        <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> Print</button>
        <a href="ajax/export_persentase.php?bulan=<?= $bulan ?>&tahun=<?= $tahun ?>&kelas=<?= urlencode($kelas) ?>"
           class="btn btn-success" title="Export rekap ringkasan dengan % kehadiran">
            <i class="fas fa-file-excel"></i> Export % Kehadiran
        </a>
        <a href="ajax/export_kalender.php?bulan=<?= $bulan ?>&tahun=<?= $tahun ?>&kelas=<?= urlencode($kelas) ?>"
           class="btn btn-success" style="background:#0e7490;border-color:#0e7490" title="Export rekap per hari dengan kalender">
            <i class="fas fa-calendar-alt"></i> Export Kalender
        </a>
    </div>
</div>

<!-- Filter -->
<div class="card mb-3 no-print">
    <div class="card-body">
        <form method="GET" class="filter-bar">
            <div>
                <label class="form-label">Bulan</label>
                <select name="bulan" class="form-select">
                    <?php for ($m=1;$m<=12;$m++): ?>
                    <option value="<?= $m ?>" <?= $bulan==$m?'selected':'' ?>><?= $nama_bulan[$m] ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label class="form-label">Tahun</label>
                <select name="tahun" class="form-select">
                    <?php for ($y=date('Y');$y>=date('Y')-3;$y--): ?>
                    <option value="<?= $y ?>" <?= $tahun==$y?'selected':'' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label class="form-label">Kelas</label>
                <select name="kelas" class="form-select">
                    <option value="">Semua Kelas</option>
                    <?php $kelas_list->data_seek(0); while ($k = $kelas_list->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($k['nama_kelas']) ?>" <?= $kelas==$k['nama_kelas']?'selected':'' ?>><?= htmlspecialchars($k['nama_kelas']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div style="display:flex;align-items:flex-end">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Tampilkan</button>
            </div>
        </form>
    </div>
</div>

<!-- Legenda -->
<div class="card mb-3 no-print">
    <div class="card-body" style="display:flex;gap:14px;flex-wrap:wrap;align-items:center;padding:14px 20px">
        <span style="font-size:.85rem;font-weight:800;color:#1e293b">Keterangan:</span>
        <?php $ket_arr=[
            'H'=>['#16a34a','#dcfce7','Hadir'],
            'T'=>['#d97706','#fef3c7','Terlambat'],
            'A'=>['#dc2626','#fee2e2','Alpa'],
            'S'=>['#2563eb','#dbeafe','Sakit'],
            'I'=>['#7c3aed','#ede9fe','Izin'],
            'B'=>['#9a3412','#ffedd5','Bolos'],
        ]; foreach($ket_arr as $kd2=>[$w2,$bg2,$lbl2]): ?>
        <span style="display:inline-flex;align-items:center;gap:6px;font-size:.82rem;font-weight:700">
            <span style="width:26px;height:22px;border-radius:4px;background:<?= $bg2 ?>;color:<?= $w2 ?>;display:inline-flex;align-items:center;justify-content:center;font-weight:800;font-size:.8rem"><?= $kd2 ?></span>
            = <?= $lbl2 ?>
        </span>
        <?php endforeach; ?>
        <span style="font-size:.78rem;color:#94a3b8">
            <span style="color:#64748b;font-weight:700">—</span> = Libur/Weekend &nbsp;
            <span style="color:#cbd5e1;font-weight:700">·</span> = Tidak ada data
        </span>
    </div>
</div>

<!-- Header Print -->
<div class="print-header" style="display:none;text-align:center;margin-bottom:16px">
    <?php
    $logo_file = defined('LOGO_FILE') ? LOGO_FILE : ($pengaturan['logo'] ?? '');
    if (!empty($logo_file) && file_exists(__DIR__.'/uploads/logo/'.$logo_file)): ?>
    <img src="<?= BASE_URL ?>uploads/logo/<?= $logo_file ?>" style="height:60px;margin-bottom:6px" alt="Logo">
    <?php endif; ?>
    <div style="font-size:1.1rem;font-weight:700;text-transform:uppercase"><?= htmlspecialchars($pengaturan['nama_sekolah']) ?></div>
    <div style="font-size:.8rem"><?= htmlspecialchars($pengaturan['alamat'] ?? '') ?></div>
    <hr style="border:2px solid #000;margin:6px 0">
    <div style="font-size:1rem;font-weight:700">DAFTAR HADIR SISWA</div>
    <div style="font-size:.85rem;margin-bottom:4px">
        BULAN : <?= strtoupper($nama_bulan[$bulan]).' '.$tahun ?>
        &nbsp;&nbsp;&nbsp; KELAS : <?= $kelas ?: 'SEMUA KELAS' ?>
    </div>
</div>

<!-- Tabel Kalender -->
<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <span><i class="fas fa-table" style="color:var(--primary)"></i>
        Daftar Hadir <?= $nama_bulan[$bulan].' '.$tahun ?><?= $kelas ? ' — Kelas '.$kelas : '' ?></span>
        <span style="font-size:.8rem;color:var(--text-muted)"><?= $siswa_list->num_rows ?> siswa</span>
    </div>
    <div class="table-container" style="overflow-x:auto">
        <table id="rekapTable" style="font-size:.75rem;min-width:max-content;border-collapse:collapse">
            <thead>
                <tr style="background:#1e293b;color:white">
                    <th style="padding:8px 6px;text-align:left;white-space:nowrap;position:sticky;left:0;background:#1e293b;z-index:2;min-width:30px">#</th>
                    <th style="padding:8px 6px;text-align:left;white-space:nowrap;position:sticky;left:30px;background:#1e293b;z-index:2;min-width:40px">NIS</th>
                    <th style="padding:8px 10px;text-align:left;white-space:nowrap;position:sticky;left:90px;background:#1e293b;z-index:2;min-width:160px">NAMA</th>
                    <?php if (!$kelas): ?>
                    <th style="padding:8px 6px;white-space:nowrap;background:#1e293b">KELAS</th>
                    <?php endif; ?>
                    <?php for ($d=1;$d<=$jumlah_hari;$d++):
                        $ts      = mktime(0,0,0,$bulan,$d,$tahun);
                        $hari_ke = date('N',$ts); // 1=Senin..7=Minggu
                        $isWeekend = $hari_ke >= 6;
                        $hari_singkat = ['','Sen','Sel','Rab','Kam','Jum','Sab','Min'][$hari_ke];
                    ?>
                    <th style="padding:4px 3px;text-align:center;min-width:28px;<?= $isWeekend?'background:#374151;':'background:#1e293b;' ?>" title="<?= $hari_singkat ?> <?= $d ?>">
                        <div style="font-size:.65rem;font-weight:400;opacity:.7"><?= $hari_singkat ?></div>
                        <div><?= $d ?></div>
                    </th>
                    <?php endfor; ?>
                    <th style="padding:8px 6px;background:#166534;text-align:center;min-width:30px;color:white;font-weight:800;font-size:.85rem" title="Hadir">H</th>
                    <th style="padding:8px 6px;background:#854d0e;text-align:center;min-width:30px;color:white;font-weight:800;font-size:.85rem" title="Terlambat">T</th>
                    <th style="padding:8px 6px;background:#991b1b;text-align:center;min-width:30px;color:white;font-weight:800;font-size:.85rem" title="Alpa">A</th>
                    <th style="padding:8px 6px;background:#1e40af;text-align:center;min-width:30px;color:white;font-weight:800;font-size:.85rem" title="Sakit">S</th>
                    <th style="padding:8px 6px;background:#5b21b6;text-align:center;min-width:30px;color:white;font-weight:800;font-size:.85rem" title="Izin">I</th>
                    <th style="padding:8px 6px;background:#9a3412;text-align:center;min-width:30px;color:white;font-weight:800;font-size:.85rem" title="Bolos">B</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($siswa_list->num_rows === 0): ?>
                <tr><td colspan="<?= 6 + $jumlah_hari + (!$kelas?1:0) ?>" style="text-align:center;padding:40px;color:var(--text-muted)">
                    Pilih kelas untuk menampilkan data, atau belum ada siswa terdaftar
                </td></tr>
                <?php else:
                $no = 0;
                while ($s = $siswa_list->fetch_assoc()):
                    $no++;
                    $sid   = $s['id'];
                    $total = ['H'=>0,'T'=>0,'A'=>0,'S'=>0,'I'=>0,'B'=>0];
                ?>
                <tr style="<?= $no%2==0?'background:#f8fafc':'' ?>">
                    <td style="padding:5px 6px;font-weight:600;position:sticky;left:0;background:<?= $no%2==0?'#f8fafc':'white' ?>;z-index:1"><?= $no ?></td>
                    <td style="padding:5px 6px;font-family:monospace;position:sticky;left:30px;background:<?= $no%2==0?'#f8fafc':'white' ?>;z-index:1"><?= $s['nis'] ?></td>
                    <td style="padding:5px 10px;white-space:nowrap;font-weight:600;position:sticky;left:90px;background:<?= $no%2==0?'#f8fafc':'white' ?>;z-index:1"><?= htmlspecialchars($s['nama']) ?></td>
                    <?php if (!$kelas): ?><td style="padding:5px 6px;white-space:nowrap"><?= $s['kelas'] ?></td><?php endif; ?>
                    <?php for ($d=1;$d<=$jumlah_hari;$d++):
                        $ts      = mktime(0,0,0,$bulan,$d,$tahun);
                        $hari_ke = date('N',$ts);
                        $isWeekend = $hari_ke >= 6;
                        $st = $absensi_map[$sid][$d] ?? null;
                        if ($st && isset($status_kode[$st])) {
                            [$kd,$warna,$bg] = $status_kode[$st];
                            $kode_total = $kd;
                            if (isset($total[$kode_total])) $total[$kode_total]++;
                        }
                    ?>
                    <td style="padding:3px 2px;text-align:center;<?= $isWeekend?'background:#f1f5f9;':'' ?>">
                        <?php if ($st && isset($status_kode[$st])): [$kd,$warna,$bg] = $status_kode[$st]; ?>
                        <span style="display:inline-block;width:20px;height:20px;line-height:20px;border-radius:4px;background:<?= $bg ?>;color:<?= $warna ?>;font-weight:700;font-size:.7rem">
                            <?= $kd ?>
                        </span>
                        <?php elseif ($isWeekend): ?>
                        <span style="color:#cbd5e1;font-size:.7rem">-</span>
                        <?php else: ?>
                        <span style="color:#e2e8f0;font-size:.6rem">·</span>
                        <?php endif; ?>
                    </td>
                    <?php endfor; ?>
                    <td style="text-align:center;font-weight:700;color:#166534;background:#f0fdf4;padding:5px 4px"><?= $total['H'] ?></td>
                    <td style="text-align:center;font-weight:700;color:#854d0e;background:#fffbeb;padding:5px 4px"><?= $total['T'] ?></td>
                    <td style="text-align:center;font-weight:700;color:#991b1b;background:#fef2f2;padding:5px 4px"><?= $total['A'] ?></td>
                    <td style="text-align:center;font-weight:700;color:#1e40af;background:#eff6ff;padding:5px 4px"><?= $total['S'] ?></td>
                    <td style="text-align:center;font-weight:700;color:#5b21b6;background:#f5f3ff;padding:5px 4px"><?= $total['I'] ?></td>
                    <td style="text-align:center;font-weight:700;color:#9a3412;background:#fff7ed;padding:5px 4px"><?= $total['B'] ?></td>
                </tr>
                <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Tanda Tangan -->
<div style="margin-top:32px;display:flex;justify-content:flex-end;padding-right:40px">
    <div style="text-align:center;min-width:200px">
        <div><?= htmlspecialchars($pengaturan['nama_sekolah'] ?? '') ?>, <?= date('d') ?> <?= $nama_bulan[(int)date('n')] ?> <?= date('Y') ?></div>
        <div style="margin-top:4px">Kepala Sekolah,</div>
        <div style="margin-top:60px;font-weight:700;text-decoration:underline">
            <?= htmlspecialchars($pengaturan['kepala_sekolah'] ?? '__________________________') ?>
        </div>
        <div style="font-size:.82rem">NIP. <?= htmlspecialchars($pengaturan['nip_kepala'] ?? '-') ?></div>
    </div>
</div>

<style>
@media print {
    .no-print, .sidebar, .top-bar, .btn, header, .page-header .ms-auto { display:none !important; }
    .main-content { margin-left:0 !important; }
    .content-wrapper { padding:0 !important; }
    .print-header { display:block !important; }
    .card { box-shadow:none !important; border:1px solid #ddd; }
    table { font-size:.65rem !important; }
}
</style>

<?php include 'includes/footer.php'; ?>
