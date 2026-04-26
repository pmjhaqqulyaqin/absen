<?php
require_once 'includes/config.php';
cek_login();

$nama_bulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$nama_hari  = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];

// Default: hari ini
$tgl_default = date('j');
$bln_default = date('n');
$thn_default = date('Y');

$tgl  = (int)($_GET['tgl']   ?? $tgl_default);
$bln  = (int)($_GET['bulan'] ?? $bln_default);
$thn  = (int)($_GET['tahun'] ?? $thn_default);

// Validasi
$max_hari = cal_days_in_month(CAL_GREGORIAN, $bln, $thn);
if ($tgl < 1 || $tgl > $max_hari) $tgl = 1;

$tanggal_full = sprintf('%04d-%02d-%02d', $thn, $bln, $tgl);
$hari_idx     = date('w', strtotime($tanggal_full));
$hari_nama    = $nama_hari[$hari_idx];

$kelas_list = get_kelas_list();
$pengaturan = get_pengaturan();

// ------------------------------------------------
// Rekap per kelas
// ------------------------------------------------
$rekap_kelas = [];
$grand = ['siswa'=>0,'Hadir'=>0,'Terlambat'=>0,'Alpa'=>0,'Sakit'=>0,'Izin'=>0,'Bolos'=>0];

foreach ($kelas_list as $k) {
    $k_safe = $conn->real_escape_string($k);
    $total_siswa = $conn->query("SELECT COUNT(*) c FROM siswa WHERE kelas='$k_safe'")->fetch_assoc()['c'];

    $stats_q = $conn->query("SELECT status, COUNT(*) total
        FROM absensi
        WHERE tanggal='$tanggal_full' AND kelas='$k_safe'
        GROUP BY status");
    $s = ['Hadir'=>0,'Terlambat'=>0,'Alpa'=>0,'Sakit'=>0,'Izin'=>0,'Bolos'=>0];
    while ($r = $stats_q->fetch_assoc()) {
        if (isset($s[$r['status']])) $s[$r['status']] = (int)$r['total'];
    }
    $total_hadir_eff = $s['Hadir'] + $s['Terlambat'];
    $pct = $total_siswa > 0 ? round(($total_hadir_eff / $total_siswa) * 100, 1) : 0;

    $rekap_kelas[] = [
        'kelas'   => $k,
        'siswa'   => $total_siswa,
        'Hadir'   => $s['Hadir'],
        'Terlambat'=> $s['Terlambat'],
        'Alpa'    => $s['Alpa'],
        'Sakit'   => $s['Sakit'],
        'Izin'    => $s['Izin'],
        'Bolos'   => $s['Bolos'],
        'pct'     => $pct,
    ];

    $grand['siswa']     += $total_siswa;
    foreach (['Hadir','Terlambat','Alpa','Sakit','Izin','Bolos'] as $st)
        $grand[$st] += $s[$st];
}

$grand_hadir_eff = $grand['Hadir'] + $grand['Terlambat'];
$grand_pct = $grand['siswa'] > 0 ? round(($grand_hadir_eff / $grand['siswa']) * 100, 1) : 0;


// ------------------------------------------------
// Rekap semua status - semua kelas (untuk tabel bawah & export)
// ------------------------------------------------
$filter_status = isset($_GET["filter_status"]) ? $conn->real_escape_string(htmlspecialchars(strip_tags(trim($_GET["filter_status"])))) : "";
$valid_status  = ["Alpa","Sakit","Izin","Bolos","Terlambat","Hadir"];
if (!in_array($filter_status, $valid_status)) $filter_status = "";
$status_sql    = $filter_status ? "AND a.status='$filter_status'" : "AND a.status IN ('Alpa','Sakit','Izin','Bolos','Terlambat','Hadir')";
$rekap_semua_q = $conn->query("SELECT a.nis, a.nama, a.kelas, a.status, a.keterangan FROM absensi a WHERE a.tanggal='$tanggal_full' ".$status_sql." ORDER BY a.status, a.kelas, a.nama");
$rekap_semua   = $rekap_semua_q ? $rekap_semua_q->fetch_all(MYSQLI_ASSOC) : [];

// Export Excel
if (isset($_GET["export_excel"])) {
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    $fn = "Rekap_".$tanggal_full.($filter_status?"_".$filter_status:"_Semua").".xls";
    header("Content-Disposition: attachment; filename=\"".$fn."\"");
    header("Cache-Control: max-age=0");
    echo "﻿";
    echo "<table border='1'>";
    echo "<tr><th colspan='6' style='text-align:center;font-size:14pt;font-weight:bold'>".htmlspecialchars($pengaturan["nama_sekolah"])."</th></tr>";
    echo "<tr><th colspan='6' style='text-align:center'>REKAP ABSENSI HARIAN - ".strtoupper($hari_nama).", ".$tgl." ".$nama_bulan[$bln]." ".$thn.($filter_status?" - STATUS: ".$filter_status:" - SEMUA STATUS")."</th></tr>";
    echo "<tr><th>NO</th><th>NIS</th><th>NAMA SISWA</th><th>KELAS</th><th>STATUS</th><th>KETERANGAN</th></tr>";
    foreach ($rekap_semua as $i => $row) {
        echo "<tr><td>".($i+1)."</td><td>".$row["nis"]."</td><td>".$row["nama"]."</td><td>".$row["kelas"]."</td><td>".$row["status"]."</td><td>".($row["keterangan"]??"")."</td></tr>";
    }
    echo "</table>";
    exit;
}
// Rentang tahun untuk dropdown
$thn_start = 2020;
$thn_end   = (int)date('Y') + 2;

include 'includes/header.php';
?>

<div class="page-header d-flex align-center">
    <div>
        <div class="page-title"><i class="fas fa-clipboard-list"></i> Laporan Rekap Harian</div>
        <div class="page-subtitle">
            <?= $hari_nama ?>, <?= $tgl ?> <?= $nama_bulan[$bln] ?> <?= $thn ?>
            &nbsp;—&nbsp; Semua Kelas
        </div>
    </div>
    <div class="ms-auto no-print" style="display:flex;gap:8px;flex-wrap:wrap">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print"></i> Print
        </button>
        <a href="?tgl=<?= $tgl ?>&bulan=<?= $bln ?>&tahun=<?= $thn ?>&export=csv"
           class="btn btn-success">
            <i class="fas fa-file-csv"></i> Export CSV
        </a>
    </div>
</div>

<!-- ====== FILTER DROPDOWN ====== -->
<div class="card mb-3 no-print">
    <div class="card-body">
        <form method="GET" class="filter-bar" style="flex-wrap:wrap;gap:12px">
            <!-- Tanggal -->
            <div>
                <label class="form-label"><i class="fas fa-calendar-day"></i> Tanggal</label>
                <select name="tgl" class="form-select" style="min-width:80px">
                    <?php for ($d = 1; $d <= 31; $d++): ?>
                        <option value="<?= $d ?>" <?= $tgl==$d?'selected':'' ?>>
                            <?= str_pad($d, 2, '0', STR_PAD_LEFT) ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <!-- Bulan -->
            <div>
                <label class="form-label"><i class="fas fa-calendar-alt"></i> Bulan</label>
                <select name="bulan" class="form-select" style="min-width:130px">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= $bln==$m?'selected':'' ?>>
                            <?= $nama_bulan[$m] ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <!-- Tahun -->
            <div>
                <label class="form-label"><i class="fas fa-calendar"></i> Tahun</label>
                <select name="tahun" class="form-select" style="min-width:100px">
                    <?php for ($y = $thn_start; $y <= $thn_end; $y++): ?>
                        <option value="<?= $y ?>" <?= $thn==$y?'selected':'' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <div style="display:flex;align-items:flex-end">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Tampilkan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ====== RINGKASAN TOTAL ====== -->
<div class="stats-grid mb-3 no-print" style="grid-template-columns:repeat(auto-fit,minmax(120px,1fr))">
    <?php
    $stat_config = [
        'Hadir'     => ['user-check',      'hadir'],
        'Terlambat' => ['clock',           'terlambat'],
        'Alpa'      => ['times-circle',    'alpa'],
        'Sakit'     => ['heartbeat',       'sakit'],
        'Izin'      => ['clipboard-list',  'izin'],
        'Bolos'     => ['ban',             'bolos'],
    ];
    foreach ($stat_config as $st => $cfg): ?>
    <div class="stat-card <?= $cfg[1] ?>">
        <div class="stat-icon"><i class="fas fa-<?= $cfg[0] ?>"></i></div>
        <div>
            <div class="stat-value"><?= $grand[$st] ?></div>
            <div class="stat-label"><?= $st ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ====== HEADER PRINT ====== -->
<div class="print-header" style="display:none;text-align:center;margin-bottom:20px">
    <?php
    $logo_file = defined('LOGO_FILE') ? LOGO_FILE : ($pengaturan['logo'] ?? '');
    if (!empty($logo_file) && file_exists(__DIR__.'/uploads/logo/'.$logo_file)): ?>
        <img src="<?= BASE_URL ?>uploads/logo/<?= $logo_file ?>" style="height:70px;margin-bottom:8px" alt="Logo">
    <?php endif; ?>
    <div style="font-size:1.2rem;font-weight:700;text-transform:uppercase"><?= htmlspecialchars($pengaturan['nama_sekolah']) ?></div>
    <div style="font-size:.85rem"><?= htmlspecialchars($pengaturan['alamat'] ?? '') ?></div>
    <hr style="border:2px solid #000;margin:8px 0">
    <div style="font-size:1.1rem;font-weight:700;letter-spacing:1px">LAPORAN REKAP HARIAN ABSENSI</div>
    <div style="font-size:.9rem">
        Hari / Tanggal &nbsp;: &nbsp;<strong><?= $hari_nama ?>, <?= $tgl ?> <?= $nama_bulan[$bln] ?> <?= $thn ?></strong>
    </div>
</div>

<!-- ====== TABEL REKAP PER KELAS ====== -->
<div class="card">
    <div class="card-header" style="background:linear-gradient(135deg,#1e40af,#2563eb);color:white;padding:14px 20px">
        <i class="fas fa-table"></i>
        REKAPITULASI KEHADIRAN HARIAN &nbsp;|&nbsp;
        <span style="font-size:.85rem;font-weight:400">
            <?= $hari_nama ?>, <?= $tgl ?> <?= $nama_bulan[$bln] ?> <?= $thn ?>
        </span>
        <div class="ms-auto no-print">
            <div class="search-box" style="background:rgba(255,255,255,.15);border-radius:8px">
                <i class="fas fa-search" style="color:rgba(255,255,255,.7)"></i>
                <input type="text" id="searchInput" placeholder="Cari kelas..." style="background:transparent;color:white;">
            </div>
        </div>
    </div>
    <div class="table-container">
        <table id="mainTable" style="width:100%;border-collapse:collapse">
            <thead>
                <tr style="background:#1e3a8a;color:white;text-align:center">
                    <th style="padding:12px 10px;text-align:left;width:40px">No</th>
                    <th style="padding:12px 10px;text-align:left">Kelas</th>
                    <th style="padding:12px 10px">Jml Siswa</th>
                    <th style="padding:12px 10px;background:#15803d">Hadir</th>
                    <th style="padding:12px 10px;background:#b45309">Terlambat</th>
                    <th style="padding:12px 10px;background:#9f1239">Alpa</th>
                    <th style="padding:12px 10px;background:#1d4ed8">Sakit</th>
                    <th style="padding:12px 10px;background:#6d28d9">Izin</th>
                    <th style="padding:12px 10px;background:#7c2d12">Bolos</th>
                    <th style="padding:12px 10px">% Hadir</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rekap_kelas)): ?>
                <tr>
                    <td colspan="10" style="text-align:center;padding:40px;color:var(--text-muted)">
                        <i class="fas fa-inbox" style="font-size:2rem;margin-bottom:10px;display:block"></i>
                        Tidak ada data kelas tersedia
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($rekap_kelas as $no => $r): ?>
                <?php
                    $pct_color = $r['pct'] >= 90 ? '#15803d' : ($r['pct'] >= 75 ? '#d97706' : '#dc2626');
                    $row_bg    = ($no % 2 === 0) ? '#ffffff' : '#f8fafc';
                ?>
                <tr style="background:<?= $row_bg ?>;transition:background .15s"
                    onmouseover="this.style.background='#eff6ff'"
                    onmouseout="this.style.background='<?= $row_bg ?>'">
                    <td style="padding:10px 12px;text-align:center;font-weight:600;color:#64748b"><?= $no+1 ?></td>
                    <td style="padding:10px 12px;font-weight:700;font-size:.95rem">
                        <i class="fas fa-door-open" style="color:#2563eb;margin-right:6px;font-size:.8rem"></i>
                        <?= htmlspecialchars($r['kelas']) ?>
                    </td>
                    <td style="padding:10px 12px;text-align:center;font-weight:700"><?= $r['siswa'] ?></td>
                    <td style="padding:10px 12px;text-align:center">
                        <span style="background:#dcfce7;color:#15803d;padding:4px 12px;border-radius:20px;font-weight:700;font-size:.9rem">
                            <?= $r['Hadir'] ?>
                        </span>
                    </td>
                    <td style="padding:10px 12px;text-align:center">
                        <span style="background:#fef3c7;color:#b45309;padding:4px 12px;border-radius:20px;font-weight:700;font-size:.9rem">
                            <?= $r['Terlambat'] ?>
                        </span>
                    </td>
                    <td style="padding:10px 12px;text-align:center">
                        <span style="background:#fee2e2;color:#dc2626;padding:4px 12px;border-radius:20px;font-weight:700;font-size:.9rem">
                            <?= $r['Alpa'] ?>
                        </span>
                    </td>
                    <td style="padding:10px 12px;text-align:center">
                        <span style="background:#dbeafe;color:#1d4ed8;padding:4px 12px;border-radius:20px;font-weight:700;font-size:.9rem">
                            <?= $r['Sakit'] ?>
                        </span>
                    </td>
                    <td style="padding:10px 12px;text-align:center">
                        <span style="background:#ede9fe;color:#6d28d9;padding:4px 12px;border-radius:20px;font-weight:700;font-size:.9rem">
                            <?= $r['Izin'] ?>
                        </span>
                    </td>
                    <td style="padding:10px 12px;text-align:center">
                        <span style="background:#ffedd5;color:#9a3412;padding:4px 12px;border-radius:20px;font-weight:700;font-size:.9rem">
                            <?= $r['Bolos'] ?>
                        </span>
                    </td>
                    <td style="padding:10px 12px;text-align:center">
                        <div style="display:flex;flex-direction:column;align-items:center;gap:4px">
                            <span style="font-weight:800;font-size:1rem;color:<?= $pct_color ?>">
                                <?= $r['pct'] ?>%
                            </span>
                            <div style="width:80px;height:6px;background:#e2e8f0;border-radius:3px;overflow:hidden">
                                <div style="width:<?= $r['pct'] ?>%;height:100%;background:<?= $pct_color ?>;border-radius:3px;transition:width .5s"></div>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <!-- GRAND TOTAL -->
            <tfoot>
                <tr style="background:#1e3a8a;color:white;font-weight:800;font-size:.95rem">
                    <td colspan="2" style="padding:12px 16px;text-align:right;letter-spacing:.5px">
                        <i class="fas fa-sigma"></i> TOTAL
                    </td>
                    <td style="padding:12px;text-align:center"><?= $grand['siswa'] ?></td>
                    <td style="padding:12px;text-align:center"><?= $grand['Hadir'] ?></td>
                    <td style="padding:12px;text-align:center"><?= $grand['Terlambat'] ?></td>
                    <td style="padding:12px;text-align:center"><?= $grand['Alpa'] ?></td>
                    <td style="padding:12px;text-align:center"><?= $grand['Sakit'] ?></td>
                    <td style="padding:12px;text-align:center"><?= $grand['Izin'] ?></td>
                    <td style="padding:12px;text-align:center"><?= $grand['Bolos'] ?></td>
                    <td style="padding:12px;text-align:center">
                        <?php
                        $gt_color = $grand_pct >= 90 ? '#86efac' : ($grand_pct >= 75 ? '#fde68a' : '#fca5a5');
                        ?>
                        <span style="background:<?= $gt_color ?>;color:#1e3a8a;padding:3px 10px;border-radius:20px">
                            <?= $grand_pct ?>%
                        </span>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>


<!-- ====== REKAP SEMUA STATUS ====== -->
<div style="margin-top:28px">
    <!-- Header + Filter -->
    <div style="background:linear-gradient(135deg,#1e3a8a,#1d4ed8);color:white;border-radius:12px 12px 0 0;padding:14px 20px;display:flex;align-items:center;flex-wrap:wrap;gap:10px">
        <div style="font-weight:700;font-size:1rem;flex:1;min-width:200px">
            <i class="fas fa-layer-group"></i> REKAP SEMUA STATUS
            <span style="font-size:.78rem;font-weight:400;margin-left:8px;opacity:.85">— Semua Kelas</span>
        </div>
        <form method="GET" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
            <input type="hidden" name="tgl"   value="<?= $tgl ?>">
            <input type="hidden" name="bulan" value="<?= $bln ?>">
            <input type="hidden" name="tahun" value="<?= $thn ?>">
            <label style="font-size:.82rem;white-space:nowrap"><i class="fas fa-filter"></i> Filter Status:</label>
            <select name="filter_status" class="form-select" onchange="this.form.submit()"
                    style="background:rgba(255,255,255,.15);color:white;border:1px solid rgba(255,255,255,.3);border-radius:8px;padding:5px 10px;font-size:.85rem;min-width:130px">
                <option value="" <?= $filter_status===''?'selected':'' ?> style="color:#000">Semua Status</option>
                <?php foreach (['Hadir','Terlambat','Alpa','Sakit','Izin','Bolos'] as $opt): ?>
                <option value="<?= $opt ?>" <?= $filter_status===$opt?'selected':'' ?> style="color:#000"><?= $opt ?></option>
                <?php endforeach; ?>
            </select>
            <a href="?tgl=<?= $tgl ?>&bulan=<?= $bln ?>&tahun=<?= $thn ?>&filter_status=<?= urlencode($filter_status) ?>&export_excel=1"
               style="background:#16a34a;color:white;padding:6px 14px;border-radius:8px;text-decoration:none;font-size:.82rem;font-weight:700;display:flex;align-items:center;gap:6px;white-space:nowrap">
                <i class="fas fa-file-excel"></i> Download Excel
            </a>
        </form>
    </div>

    <!-- Tabel -->
    <div style="overflow-x:auto;border:1px solid #e2e8f0;border-top:none;border-radius:0 0 12px 12px;background:white">
        <table id="rekapSemuaTable" style="width:100%;border-collapse:collapse;font-size:.875rem">
            <thead>
                <tr style="background:#f1f5f9;color:#334155;font-weight:700;font-size:.8rem">
                    <th style="padding:10px 12px;text-align:center;width:44px">NO</th>
                    <th style="padding:10px 12px;text-align:left">NIS</th>
                    <th style="padding:10px 12px;text-align:left">NAMA SISWA</th>
                    <th style="padding:10px 12px;text-align:left">KELAS</th>
                    <th style="padding:10px 12px;text-align:center">STATUS</th>
                    <th style="padding:10px 12px;text-align:left">KETERANGAN</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($rekap_semua)): ?>
                <tr><td colspan="6" style="text-align:center;padding:40px;color:#94a3b8">
                    <i class="fas fa-inbox" style="font-size:1.5rem;display:block;margin-bottom:8px"></i>
                    Tidak ada data untuk filter ini
                </td></tr>
            <?php else: ?>
            <?php
            $bg_map2 = ['Alpa'=>'#fff5f5','Sakit'=>'#f0f5ff','Izin'=>'#f5f3ff','Bolos'=>'#fff7ed','Hadir'=>'#f0fdf4','Terlambat'=>'#fffbeb'];
            $prev2   = '';
            foreach ($rekap_semua as $ri => $rrow):
                if ($rrow['status'] !== $prev2 && $prev2 !== ''):
            ?>
            <tr><td colspan="6" style="padding:0;height:5px;background:#e2e8f0"></td></tr>
            <?php endif; $prev2 = $rrow['status']; ?>
            <tr style="background:<?= $bg_map2[$rrow['status']] ?? '#fff' ?>;border-top:1px solid #f1f5f9"
                onmouseover="this.style.filter='brightness(.96)'" onmouseout="this.style.filter=''">
                <td style="padding:9px 12px;text-align:center;color:#94a3b8;font-weight:600"><?= $ri+1 ?></td>
                <td style="padding:9px 12px;color:#64748b;font-size:.8rem"><?= htmlspecialchars($rrow['nis']) ?></td>
                <td style="padding:9px 12px;font-weight:700"><?= htmlspecialchars($rrow['nama']) ?></td>
                <td style="padding:9px 12px">
                    <span style="background:#e2e8f0;color:#334155;padding:2px 10px;border-radius:20px;font-size:.8rem;font-weight:600">
                        <?= htmlspecialchars($rrow['kelas']) ?>
                    </span>
                </td>
                <td style="padding:9px 12px;text-align:center"><?= get_status_badge($rrow['status']) ?></td>
                <td style="padding:9px 12px;color:#64748b"><?= htmlspecialchars($rrow['keterangan'] ?? '-') ?></td>
            </tr>
            <?php endforeach; ?>
            <!-- TOTAL -->
            <tr style="background:#1e3a8a;color:white;font-weight:800">
                <td colspan="2" style="padding:10px 12px;text-align:right">TOTAL</td>
                <td colspan="4" style="padding:10px 12px"><?= count($rekap_semua) ?> siswa
                    <?= $filter_status ? "— Status: <strong>$filter_status</strong>" : "— Semua Status" ?>
                </td>
            </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Tanda Tangan -->
<div class="ttd-section" style="margin-top:40px;display:flex;justify-content:flex-end;padding-right:40px">
    <div style="text-align:center;min-width:220px">
        <?php
        $bln_t = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        $tgl_ttd = date('d') . ' ' . $bln_t[(int)date('n')] . ' ' . date('Y');
        ?>
        <div><?= htmlspecialchars($pengaturan['nama_sekolah'] ?? '') ?>, <?= $tgl_ttd ?></div>
        <div style="margin-top:4px">Kepala Sekolah,</div>
        <div style="margin-top:70px;font-weight:700;text-decoration:underline">
            <?= htmlspecialchars($pengaturan['kepala_sekolah'] ?? '__________________________') ?>
        </div>
        <div style="font-size:.85rem">NIP. <?= htmlspecialchars($pengaturan['nip_kepala'] ?? '-') ?></div>
    </div>
</div>

<script>
// Accordion
function toggleDetail(idx) {
    const el   = document.getElementById('detail-' + idx);
    const icon = document.querySelector('.acc-icon-' + idx);
    const open = el.style.display !== 'none';
    el.style.display   = open ? 'none' : 'block';
    icon.style.transform = open ? '' : 'rotate(90deg)';
}

// Search filter
document.getElementById('searchInput')?.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#mainTable tbody tr').forEach(tr => {
        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});
</script>

<style>
@media print {
    .no-print, .sidebar, .top-bar, .btn, header { display:none !important; }
    .main-content { margin-left:0 !important; }
    .content-wrapper { padding:10px !important; }
    .print-header { display:block !important; }
    .ttd-section { display:flex !important; }
    .accordion-kelas { display:none !important; }
    #mainTable tbody tr { background:white !important; }
}
</style>

<?php include 'includes/footer.php'; ?>
