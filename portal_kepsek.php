<?php
require_once 'includes/config.php';

// Cek session kepsek
if (!isset($_SESSION['kepsek_id'])) {
    header('Location: '.BASE_URL.'portal_kepsek_login.php'); exit;
}

$pengaturan  = get_pengaturan();
$nama_bulan  = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$hari        = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
$tgl_indo    = $hari[date('w')].', '.date('d').' '.$nama_bulan[(int)date('n')].' '.date('Y');

$f_bulan = (int)($_GET['bulan'] ?? date('n'));
$f_tahun = (int)($_GET['tahun'] ?? date('Y'));
$f_kelas = sanitize($_GET['kelas'] ?? '');

$kelas_list_q = $conn->query("SELECT DISTINCT kelas AS nama_kelas FROM siswa ORDER BY kelas");
$kelas_list   = [];
while ($k = $kelas_list_q->fetch_assoc()) $kelas_list[] = $k['nama_kelas'];

// === DATA WALI KELAS ===
$wali_map = [];
$wali_q = $conn->query("SELECT id, nama, kelas_wali, no_hp FROM wali WHERE kelas_wali != '' AND kelas_wali IS NOT NULL ORDER BY kelas_wali");
while ($w = $wali_q->fetch_assoc()) {
    $wali_map[$w['kelas_wali']] = $w;
}

$today = date('Y-m-d');
$rekap_harian = [];
foreach ($kelas_list as $kls) {
    $kls_esc  = $conn->real_escape_string($kls);
    $jml_siswa = (int)$conn->query("SELECT COUNT(*) AS c FROM siswa WHERE kelas='$kls_esc'")->fetch_assoc()['c'];
    $ab_q = $conn->query("SELECT status, COUNT(*) AS t FROM absensi WHERE tanggal='$today' AND siswa_id IN (SELECT id FROM siswa WHERE kelas='$kls_esc') GROUP BY status");
    $ab = ['Hadir'=>0,'Terlambat'=>0,'Alpa'=>0,'Sakit'=>0,'Izin'=>0,'Bolos'=>0];
    while ($r = $ab_q->fetch_assoc()) $ab[$r['status']] = (int)$r['t'];
    $hadir_total = $ab['Hadir'] + $ab['Terlambat'];
    $wali_kls = $wali_map[$kls] ?? null;
    $rekap_harian[] = [
        'kelas'=>$kls,'jml_siswa'=>$jml_siswa,'hadir'=>$hadir_total,
        'alpa'=>$ab['Alpa'],'sakit'=>$ab['Sakit'],'izin'=>$ab['Izin'],'bolos'=>$ab['Bolos'],
        'belum'=>$jml_siswa-($hadir_total+$ab['Alpa']+$ab['Sakit']+$ab['Izin']+$ab['Bolos']),
        'wali_nama'=>$wali_kls?$wali_kls['nama']:null,
        'wali_no_hp'=>$wali_kls?$wali_kls['no_hp']:null,
    ];
}

$total_siswa_all = array_sum(array_column($rekap_harian,'jml_siswa'));
$total_hadir_all = array_sum(array_column($rekap_harian,'hadir'));
$total_alpa_all  = array_sum(array_column($rekap_harian,'alpa'));
$total_sakit_all = array_sum(array_column($rekap_harian,'sakit'));
$total_izin_all  = array_sum(array_column($rekap_harian,'izin'));
$total_bolos_all = array_sum(array_column($rekap_harian,'bolos'));
$total_belum_all = array_sum(array_column($rekap_harian,'belum'));

$kelas_sql_b   = $f_kelas ? "AND kelas='".($conn->real_escape_string($f_kelas))."'" : '';
$siswa_bulanan = $conn->query("SELECT id, nis, nama, kelas FROM siswa WHERE 1=1 $kelas_sql_b ORDER BY kelas, nama");
$jumlah_hari_b = cal_days_in_month(CAL_GREGORIAN, $f_bulan, $f_tahun);
$absensi_bul   = [];
$siswa_bul_arr = [];
while ($s = $siswa_bulanan->fetch_assoc()) $siswa_bul_arr[] = $s;
$ids_bul = array_column($siswa_bul_arr,'id');
if ($ids_bul) {
    $ids_str = implode(',',$ids_bul);
    $ab_bul  = $conn->query("SELECT siswa_id, DAY(tanggal) as tgl, status FROM absensi WHERE MONTH(tanggal)=$f_bulan AND YEAR(tanggal)=$f_tahun AND siswa_id IN ($ids_str)");
    while ($a = $ab_bul->fetch_assoc()) $absensi_bul[$a['siswa_id']][$a['tgl']] = $a['status'];
}

$ringkasan_bul = [];
foreach ($siswa_bul_arr as $s) {
    $sid=$s['id']; $ab=$absensi_bul[$sid]??[];
    $h=$t=$a=$sk=$iz=$bo=0;
    foreach ($ab as $status) {
        if ($status==='Hadir') $h++;
        elseif ($status==='Terlambat') $t++;
        elseif ($status==='Alpa') $a++;
        elseif ($status==='Sakit') $sk++;
        elseif ($status==='Izin') $iz++;
        elseif ($status==='Bolos') $bo++;
    }
    $ringkasan_bul[] = array_merge($s,['hadir'=>$h,'terlambat'=>$t,'alpa'=>$a,'sakit'=>$sk,'izin'=>$iz,'bolos'=>$bo,'total'=>$h+$t+$a+$sk+$iz+$bo,'persen'=>($jumlah_hari_b>0?round(($h+$t)/$jumlah_hari_b*100,1):0)]);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Kepala Sekolah - <?= htmlspecialchars($pengaturan['nama_sekolah']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    *{margin:0;padding:0;box-sizing:border-box}
    body{font-family:'Segoe UI',sans-serif;background:#0f172a;color:white;min-height:100vh}
    .navbar{background:rgba(15,23,42,.96);backdrop-filter:blur(10px);border-bottom:1px solid rgba(255,255,255,.08);padding:0 20px;height:60px;display:flex;align-items:center;gap:12px;position:sticky;top:0;z-index:100}
    .navbar-brand{display:flex;align-items:center;gap:10px;text-decoration:none;color:white}
    .nb-icon{width:36px;height:36px;background:linear-gradient(135deg,#7c3aed,#5b21b6);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem}
    .nb-title{font-weight:800;font-size:.9rem;line-height:1.2}
    .nb-sub{font-size:.65rem;color:#94a3b8}
    .ms-auto{margin-left:auto}
    .btn-nav{padding:7px 14px;border-radius:8px;font-weight:700;font-size:.8rem;text-decoration:none;border:none;cursor:pointer;transition:.2s;display:inline-flex;align-items:center;gap:6px}
    .btn-out{background:rgba(255,255,255,.1);color:#e2e8f0}
    .btn-out:hover{background:rgba(255,255,255,.2)}
    .hero-kepsek{background:linear-gradient(135deg,#1e1b4b,#312e81,#1e1b4b);padding:28px 20px;border-bottom:1px solid rgba(255,255,255,.06)}
    .hero-inner{max-width:1100px;margin:0 auto;display:flex;align-items:center;gap:16px;flex-wrap:wrap}
    .hero-text h1{font-size:1.3rem;font-weight:900}
    .hero-text p{color:#a5b4fc;font-size:.85rem;margin-top:4px}
    .hero-date{margin-left:auto;background:rgba(124,58,237,.2);border:1px solid rgba(124,58,237,.3);padding:8px 16px;border-radius:30px;font-size:.82rem;font-weight:600;color:#c4b5fd}
    .main{max-width:1100px;margin:0 auto;padding:24px 16px}
    .section-title{font-size:.95rem;font-weight:800;display:flex;align-items:center;gap:8px;margin-bottom:16px;padding-bottom:10px;border-bottom:1px solid rgba(255,255,255,.08)}
    .section-title .ic{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:.85rem}
    .ic-harian{background:rgba(56,189,248,.15);color:#38bdf8}
    .ic-bulanan{background:rgba(124,58,237,.15);color:#c084fc}
    .ic-notif{background:rgba(34,197,94,.15);color:#22c55e}
    .stats-mini{display:grid;grid-template-columns:repeat(auto-fit,minmax(100px,1fr));gap:10px;margin-bottom:24px}
    .stat-mini-card{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);border-radius:12px;padding:14px;text-align:center}
    .stat-mini-num{font-size:1.6rem;font-weight:900;line-height:1}
    .stat-mini-lbl{font-size:.68rem;color:#64748b;margin-top:4px;font-weight:600;text-transform:uppercase;letter-spacing:.5px}
    .table-wrap{overflow-x:auto;border-radius:14px;border:1px solid rgba(255,255,255,.08)}
    table{width:100%;border-collapse:collapse;min-width:600px}
    thead th{background:#1e293b;padding:10px 12px;font-size:.75rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;text-align:center;border-bottom:1px solid rgba(255,255,255,.08)}
    thead th:first-child{text-align:left}
    tbody tr{border-bottom:1px solid rgba(255,255,255,.04);transition:.15s}
    tbody tr:hover{background:rgba(255,255,255,.04)}
    tbody tr:last-child{border-bottom:none}
    td{padding:10px 12px;font-size:.83rem;text-align:center;vertical-align:middle}
    td:first-child{text-align:left;font-weight:600}
    tfoot td{background:#1e293b;font-weight:800;font-size:.82rem;padding:11px 12px;border-top:2px solid rgba(255,255,255,.12)}
    .badge-num{display:inline-block;padding:3px 10px;border-radius:6px;font-weight:700;font-size:.8rem}
    .bn-hadir{background:rgba(74,222,128,.15);color:#4ade80}
    .bn-alpa{background:rgba(248,113,113,.15);color:#f87171}
    .bn-sakit{background:rgba(96,165,250,.15);color:#60a5fa}
    .bn-izin{background:rgba(192,132,252,.15);color:#c084fc}
    .bn-bolos{background:rgba(244,114,182,.15);color:#f472b6}
    .bn-belum{background:rgba(251,146,60,.15);color:#fb923c}
    .bn-terlambat{background:rgba(251,191,36,.15);color:#fbbf24}
    .pbar-wrap{width:80px;height:6px;background:rgba(255,255,255,.1);border-radius:3px;display:inline-block;vertical-align:middle;margin-right:6px}
    .pbar-fill{height:100%;border-radius:3px;background:linear-gradient(90deg,#4ade80,#16a34a);transition:.3s}
    .filter-card{background:rgba(124,58,237,.07);border:1px solid rgba(124,58,237,.2);border-radius:14px;padding:18px;margin-bottom:20px}
    .filter-row{display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end}
    .filter-group{display:flex;flex-direction:column;gap:6px;min-width:120px}
    .filter-group label{font-size:.75rem;font-weight:700;color:#a5b4fc;text-transform:uppercase;letter-spacing:.4px}
    .form-select{padding:9px 12px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.15);border-radius:8px;color:white;font-size:.85rem;outline:none;cursor:pointer}
    .form-select option{background:#1e293b;color:white}
    .btn-filter{padding:9px 18px;background:linear-gradient(135deg,#7c3aed,#5b21b6);color:white;border:none;border-radius:8px;font-weight:700;font-size:.85rem;cursor:pointer;transition:.2s;display:flex;align-items:center;gap:6px}
    .btn-filter:hover{opacity:.9}
    .btn-download{padding:9px 18px;background:linear-gradient(135deg,#0e7490,#0891b2);color:white;border:none;border-radius:8px;font-weight:700;font-size:.85rem;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:.2s}
    .btn-download:hover{opacity:.9}
    .btn-download-pct{background:linear-gradient(135deg,#16a34a,#15803d)}
    .section{margin-bottom:36px}
    .pct-high{color:#4ade80}.pct-mid{color:#fb923c}.pct-low{color:#f87171}
    .kelas-badge{background:rgba(124,58,237,.2);color:#c084fc;padding:2px 10px;border-radius:6px;font-size:.75rem;font-weight:700}
    .wali-badge{font-size:.75rem;color:#a5b4fc;font-weight:600}
    .wali-empty{font-size:.72rem;color:#475569;font-style:italic}
    /* NOTIFIKASI WA */
    .notif-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px;margin-top:4px}
    .notif-card{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:16px;display:flex;flex-direction:column;gap:10px;transition:.2s}
    .notif-card:hover{border-color:rgba(34,197,94,.3);background:rgba(34,197,94,.04)}
    .notif-card-header{display:flex;align-items:center;gap:10px}
    .notif-kelas-icon{width:38px;height:38px;min-width:38px;background:linear-gradient(135deg,#7c3aed,#5b21b6);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:800;color:white}
    .notif-kelas-nama{font-weight:800;font-size:.9rem}
    .notif-wali-nama{font-size:.75rem;color:#94a3b8;margin-top:2px}
    .notif-stats{display:flex;gap:8px;flex-wrap:wrap}
    .notif-stat{padding:3px 10px;border-radius:6px;font-size:.75rem;font-weight:700}
    .ns-hadir{background:rgba(74,222,128,.15);color:#4ade80}
    .ns-belum{background:rgba(251,146,60,.15);color:#fb923c}
    .ns-absen{background:rgba(248,113,113,.15);color:#f87171}
    .btn-wa{display:flex;align-items:center;justify-content:center;gap:8px;padding:9px 14px;background:linear-gradient(135deg,#16a34a,#15803d);color:white;border:none;border-radius:9px;font-weight:700;font-size:.82rem;cursor:pointer;text-decoration:none;transition:.2s;width:100%}
    .btn-wa:hover{opacity:.9;transform:translateY(-1px)}
    .btn-wa-disabled{background:rgba(255,255,255,.06);color:#475569;cursor:not-allowed}
    .no-wa-warn{font-size:.72rem;color:#ef4444;display:flex;align-items:center;gap:4px}
    @media print{.no-print{display:none!important}.navbar{display:none}.main{padding:0}}
    @media(max-width:600px){.hero-inner{flex-direction:column}.hero-date{margin-left:0}}
    </style>
</head>
<body>

<nav class="navbar">
    <a href="portal_kepsek.php" class="navbar-brand">
        <div class="nb-icon"><i class="fas fa-user-tie"></i></div>
        <div>
            <div class="nb-title">Portal Kepala Sekolah</div>
            <div class="nb-sub"><?= htmlspecialchars($pengaturan['nama_sekolah']) ?></div>
        </div>
    </a>
    <div class="ms-auto" style="display:flex;gap:8px;align-items:center">
        <span style="font-size:.8rem;color:#94a3b8;white-space:nowrap"><i class="fas fa-user-circle" style="margin-right:4px;color:#c4b5fd"></i><?= htmlspecialchars($_SESSION['kepsek_nama']) ?></span>
        <a href="portal_kepsek_logout.php" class="btn-nav btn-out"><i class="fas fa-sign-out-alt"></i> Keluar</a>
    </div>
</nav>

<div class="hero-kepsek">
    <div class="hero-inner">
        <div class="hero-text">
            <h1><i class="fas fa-chart-bar" style="margin-right:8px;color:#c4b5fd"></i>Dashboard Rekap Absensi</h1>
            <p>Data absensi siswa <?= htmlspecialchars($pengaturan['nama_sekolah']) ?></p>
        </div>
        <div class="hero-date"><i class="fas fa-calendar-alt" style="margin-right:6px"></i><?= $tgl_indo ?></div>
    </div>
</div>

<div class="main">

<!-- ===== REKAP HARIAN ===== -->
<div class="section">
    <div class="section-title">
        <div class="ic ic-harian"><i class="fas fa-calendar-day"></i></div>
        Rekap Harian — Hari Ini
    </div>
    <div class="stats-mini">
        <div class="stat-mini-card"><div class="stat-mini-num" style="color:#38bdf8"><?= $total_siswa_all ?></div><div class="stat-mini-lbl">Total Siswa</div></div>
        <div class="stat-mini-card"><div class="stat-mini-num" style="color:#4ade80"><?= $total_hadir_all ?></div><div class="stat-mini-lbl">Hadir</div></div>
        <div class="stat-mini-card"><div class="stat-mini-num" style="color:#f87171"><?= $total_alpa_all ?></div><div class="stat-mini-lbl">Alpa</div></div>
        <div class="stat-mini-card"><div class="stat-mini-num" style="color:#60a5fa"><?= $total_sakit_all ?></div><div class="stat-mini-lbl">Sakit</div></div>
        <div class="stat-mini-card"><div class="stat-mini-num" style="color:#c084fc"><?= $total_izin_all ?></div><div class="stat-mini-lbl">Izin</div></div>
        <div class="stat-mini-card"><div class="stat-mini-num" style="color:#f472b6"><?= $total_bolos_all ?></div><div class="stat-mini-lbl">Bolos</div></div>
        <div class="stat-mini-card"><div class="stat-mini-num" style="color:#fb923c"><?= $total_belum_all ?></div><div class="stat-mini-lbl">Belum Absen</div></div>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr>
                <th style="text-align:left">No</th>
                <th style="text-align:left">Kelas</th>
                <th style="text-align:left">Wali Kelas</th>
                <th>Jml Siswa</th><th>Hadir</th><th>Alpa</th><th>Sakit</th><th>Izin</th><th>Bolos</th><th>Belum Absen</th><th>% Hadir</th>
            </tr></thead>
            <tbody>
            <?php foreach ($rekap_harian as $i => $r):
                $pct = $r['jml_siswa']>0 ? round($r['hadir']/$r['jml_siswa']*100,1) : 0;
                $pct_cls = $pct>=80?'pct-high':($pct>=60?'pct-mid':'pct-low');
            ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td><span class="kelas-badge"><?= htmlspecialchars($r['kelas']) ?></span></td>
                <td>
                    <?php if($r['wali_nama']): ?>
                    <span class="wali-badge"><i class="fas fa-chalkboard-teacher" style="margin-right:4px;color:#a5b4fc"></i><?= htmlspecialchars($r['wali_nama']) ?></span>
                    <?php else: ?><span class="wali-empty">— belum diset —</span><?php endif; ?>
                </td>
                <td><strong><?= $r['jml_siswa'] ?></strong></td>
                <td><span class="badge-num bn-hadir"><?= $r['hadir'] ?></span></td>
                <td><span class="badge-num bn-alpa"><?= $r['alpa'] ?></span></td>
                <td><span class="badge-num bn-sakit"><?= $r['sakit'] ?></span></td>
                <td><span class="badge-num bn-izin"><?= $r['izin'] ?></span></td>
                <td><span class="badge-num bn-bolos"><?= $r['bolos'] ?></span></td>
                <td><span class="badge-num bn-belum"><?= $r['belum'] ?></span></td>
                <td>
                    <div class="pbar-wrap"><div class="pbar-fill" style="width:<?= min($pct,100) ?>%"></div></div>
                    <span class="<?= $pct_cls ?>" style="font-weight:700;font-size:.82rem"><?= $pct ?>%</span>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot><tr>
                <td colspan="3" style="text-align:left">TOTAL</td>
                <td><?= $total_siswa_all ?></td>
                <td><span class="badge-num bn-hadir"><?= $total_hadir_all ?></span></td>
                <td><span class="badge-num bn-alpa"><?= $total_alpa_all ?></span></td>
                <td><span class="badge-num bn-sakit"><?= $total_sakit_all ?></span></td>
                <td><span class="badge-num bn-izin"><?= $total_izin_all ?></span></td>
                <td><span class="badge-num bn-bolos"><?= $total_bolos_all ?></span></td>
                <td><span class="badge-num bn-belum"><?= $total_belum_all ?></span></td>
                <td><?php $pct_tot=$total_siswa_all>0?round($total_hadir_all/$total_siswa_all*100,1):0; ?>
                    <span style="font-weight:800;color:#4ade80"><?= $pct_tot ?>%</span></td>
            </tr></tfoot>
        </table>
    </div>
</div>


<!-- ===== NOTIFIKASI WA ===== -->
<div class="section no-print">
    <div class="section-title">
        <div class="ic ic-notif"><i class="fab fa-whatsapp"></i></div>
        Notifikasi WhatsApp — Wali Kelas
    </div>
    <div style="font-size:.8rem;color:#64748b;margin-bottom:16px;display:flex;align-items:center;gap:6px;background:rgba(56,189,248,.06);border:1px solid rgba(56,189,248,.15);border-radius:10px;padding:10px 14px">
        <i class="fas fa-info-circle" style="color:#38bdf8;flex-shrink:0"></i>
        Klik tombol hijau untuk langsung membuka WhatsApp dengan pesan otomatis. No WA dikelola admin di menu <strong style="color:#a5b4fc">Kelola Wali → No HP/WA</strong>.
    </div>
    <div class="notif-grid">
    <?php foreach ($rekap_harian as $r):
        $no_wa = preg_replace('/[^0-9]/','', $r['wali_no_hp'] ?? '');
        if ($no_wa && $no_wa[0]==='0') $no_wa = '62'.substr($no_wa,1);
        $pct_h = $r['jml_siswa']>0 ? round($r['hadir']/$r['jml_siswa']*100,1) : 0;
        $pesan = urlencode(
            "Assalamu'alaikum Wr. Wb.\n\n".
            "\xF0\x9F\x93\x8B *Laporan Absensi Harian*\n".
            "\xF0\x9F\x8F\xAB ".$pengaturan['nama_sekolah']."\n".
            "\xF0\x9F\x93\x85 ".$tgl_indo."\n".
            "\xF0\x9F\x8F\x9B\xEF\xB8\x8F Kelas: *".$r['kelas']."*\n\n".
            "\xE2\x9C\x85 Hadir       : ".$r['hadir']." siswa\n".
            "\xE2\x9D\x8C Alpa        : ".$r['alpa']." siswa\n".
            "\xF0\x9F\x8F\xA5 Sakit       : ".$r['sakit']." siswa\n".
            "\xF0\x9F\x93\x8B Izin        : ".$r['izin']." siswa\n".
            "\xF0\x9F\x9A\xAB Bolos       : ".$r['bolos']." siswa\n".
            "\xE2\x8F\xB3 Belum absen : ".$r['belum']." siswa\n\n".
            "Total siswa: ".$r['jml_siswa']." | Kehadiran: ".$pct_h."%\n\n".
            "🔗 Cek absensi siswa:\nhttps://presensi.mandalotim.sch.id/portal_wali.php\n\n".
            "Mohon ditindaklanjuti. Terima kasih. \xF0\x9F\x99\x8F"
        );
        $wa_url = $no_wa ? "https://wa.me/{$no_wa}?text={$pesan}" : '';
    ?>
    <div class="notif-card">
        <div class="notif-card-header">
            <div class="notif-kelas-icon"><?= htmlspecialchars($r['kelas']) ?></div>
            <div style="flex:1">
                <div class="notif-kelas-nama">Kelas <?= htmlspecialchars($r['kelas']) ?></div>
                <div class="notif-wali-nama">
                    <?php if($r['wali_nama']): ?>
                    <i class="fas fa-chalkboard-teacher" style="margin-right:3px;color:#a5b4fc"></i><?= htmlspecialchars($r['wali_nama']) ?>
                    <?php else: ?><em style="color:#475569">Wali kelas belum diset</em><?php endif; ?>
                </div>
                <?php if(!$no_wa && $r['wali_nama']): ?>
                <div class="no-wa-warn"><i class="fas fa-exclamation-circle"></i> No WA belum diisi admin</div>
                <?php endif; ?>
            </div>
        </div>
        <div class="notif-stats">
            <span class="notif-stat ns-hadir">✅ <?= $r['hadir'] ?> Hadir (<?= $pct_h ?>%)</span>
            <span class="notif-stat ns-belum">⏳ <?= $r['belum'] ?> Belum</span>
            <?php if($r['alpa']+$r['bolos']>0): ?>
            <span class="notif-stat ns-absen">❌ <?= $r['alpa']+$r['bolos'] ?> Alpa/Bolos</span>
            <?php endif; ?>
        </div>
        <?php if($wa_url): ?>
        <a href="<?= $wa_url ?>" target="_blank" class="btn-wa"><i class="fab fa-whatsapp" style="font-size:1.1rem"></i> Kirim Notifikasi WA</a>
        <?php else: ?>
        <span class="btn-wa btn-wa-disabled"><i class="fas fa-ban"></i> No WA Tidak Tersedia</span>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    </div>
</div>


<!-- ===== REKAP BULANAN ===== -->
<div class="section">
    <div class="section-title">
        <div class="ic ic-bulanan"><i class="fas fa-calendar-alt"></i></div>
        Rekap Bulanan — <?= $nama_bulan[$f_bulan].' '.$f_tahun ?><?= $f_kelas?' (Kelas '.$f_kelas.')':'' ?>
    </div>

    <div class="filter-card no-print">
        <form method="GET">
            <div class="filter-row">
                <div class="filter-group">
                    <label><i class="fas fa-calendar"></i> Bulan</label>
                    <select name="bulan" class="form-select">
                        <?php for($m=1;$m<=12;$m++): ?><option value="<?= $m ?>" <?= $f_bulan==$m?'selected':'' ?>><?= $nama_bulan[$m] ?></option><?php endfor; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-clock"></i> Tahun</label>
                    <select name="tahun" class="form-select">
                        <?php for($y=date('Y');$y>=date('Y')-3;$y--): ?><option value="<?= $y ?>" <?= $f_tahun==$y?'selected':'' ?>><?= $y ?></option><?php endfor; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-layer-group"></i> Kelas</label>
                    <select name="kelas" class="form-select">
                        <option value="">Semua Kelas</option>
                        <?php foreach($kelas_list as $kls): ?><option value="<?= htmlspecialchars($kls) ?>" <?= $f_kelas===$kls?'selected':'' ?>><?= htmlspecialchars($kls) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group" style="justify-content:flex-end">
                    <button type="submit" class="btn-filter"><i class="fas fa-search"></i> Tampilkan</button>
                </div>
                <div class="filter-group" style="justify-content:flex-end">
                    <a href="ajax/export_kalender_kepsek.php?bulan=<?= $f_bulan ?>&tahun=<?= $f_tahun ?>&kelas=<?= urlencode($f_kelas) ?>" class="btn-download" target="_blank">
                        <i class="fas fa-calendar-alt"></i> Download Kalender
                    </a>
                </div>
                <div class="filter-group" style="justify-content:flex-end">
                    <a href="ajax/export_persentase_kepsek.php?bulan=<?= $f_bulan ?>&tahun=<?= $f_tahun ?>&kelas=<?= urlencode($f_kelas) ?>" class="btn-download btn-download-pct" target="_blank">
                        <i class="fas fa-file-excel"></i> Download % Kehadiran
                    </a>
                </div>
            </div>
        </form>
    </div>

    <?php if(empty($ringkasan_bul)): ?>
    <div style="text-align:center;padding:40px;color:#475569">
        <i class="fas fa-inbox" style="font-size:2rem;margin-bottom:10px;display:block"></i>
        Tidak ada data absensi untuk filter ini
    </div>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead><tr>
                <th style="text-align:left">No</th>
                <th style="text-align:left">Nama Siswa</th>
                <th>Kelas</th><th>NIS</th>
                <th>Hadir</th><th>Terlambat</th><th>Alpa</th><th>Sakit</th><th>Izin</th><th>Bolos</th><th>% Hadir</th>
            </tr></thead>
            <tbody>
            <?php $no=1; $prev_kelas=null;
            foreach($ringkasan_bul as $s):
                $pct=$s['persen'];
                $pct_cls=$pct>=80?'pct-high':($pct>=60?'pct-mid':'pct-low');
            ?>
            <?php if(!$f_kelas && $s['kelas']!==$prev_kelas): $prev_kelas=$s['kelas']; $no=1;
                $wali_bul=$wali_map[$s['kelas']]??null;
            ?>
            <tr style="background:rgba(124,58,237,.1)">
                <td colspan="11" style="text-align:left;padding:8px 12px">
                    <strong style="color:#c084fc;font-size:.85rem"><i class="fas fa-users" style="margin-right:6px"></i>Kelas <?= htmlspecialchars($s['kelas']) ?></strong>
                    <?php if($wali_bul): ?><span style="color:#94a3b8;font-size:.75rem;margin-left:10px"><i class="fas fa-chalkboard-teacher" style="margin-right:3px"></i><?= htmlspecialchars($wali_bul['nama']) ?></span><?php endif; ?>
                </td>
            </tr>
            <?php endif; ?>
            <tr>
                <td><?= $no++ ?></td>
                <td style="font-weight:600;max-width:180px"><?= htmlspecialchars($s['nama']) ?></td>
                <td><span class="kelas-badge"><?= htmlspecialchars($s['kelas']) ?></span></td>
                <td style="color:#94a3b8;font-size:.78rem"><?= htmlspecialchars($s['nis']) ?></td>
                <td><span class="badge-num bn-hadir"><?= $s['hadir'] ?></span></td>
                <td><span class="badge-num bn-terlambat"><?= $s['terlambat'] ?></span></td>
                <td><span class="badge-num bn-alpa"><?= $s['alpa'] ?></span></td>
                <td><span class="badge-num bn-sakit"><?= $s['sakit'] ?></span></td>
                <td><span class="badge-num bn-izin"><?= $s['izin'] ?></span></td>
                <td><span class="badge-num bn-bolos"><?= $s['bolos'] ?></span></td>
                <td>
                    <div class="pbar-wrap"><div class="pbar-fill" style="width:<?= min($pct,100) ?>%"></div></div>
                    <span class="<?= $pct_cls ?>" style="font-weight:700;font-size:.82rem"><?= $pct ?>%</span>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot><tr>
                <td colspan="4" style="text-align:left">TOTAL (<?= count($ringkasan_bul) ?> siswa)</td>
                <td><span class="badge-num bn-hadir"><?= array_sum(array_column($ringkasan_bul,'hadir')) ?></span></td>
                <td><span class="badge-num bn-terlambat"><?= array_sum(array_column($ringkasan_bul,'terlambat')) ?></span></td>
                <td><span class="badge-num bn-alpa"><?= array_sum(array_column($ringkasan_bul,'alpa')) ?></span></td>
                <td><span class="badge-num bn-sakit"><?= array_sum(array_column($ringkasan_bul,'sakit')) ?></span></td>
                <td><span class="badge-num bn-izin"><?= array_sum(array_column($ringkasan_bul,'izin')) ?></span></td>
                <td><span class="badge-num bn-bolos"><?= array_sum(array_column($ringkasan_bul,'bolos')) ?></span></td>
                <td><?php $avg_pct=count($ringkasan_bul)>0?round(array_sum(array_column($ringkasan_bul,'persen'))/count($ringkasan_bul),1):0; $avg_cls=$avg_pct>=80?'pct-high':($avg_pct>=60?'pct-mid':'pct-low'); ?>
                    <span class="<?= $avg_cls ?>" style="font-weight:800"><?= $avg_pct ?>%</span></td>
            </tr></tfoot>
        </table>
    </div>
    <?php endif; ?>
</div>

</div><!-- end main -->
<footer style="text-align:center;padding:16px;color:#334155;font-size:.73rem;border-top:1px solid rgba(255,255,255,.04)">
    <?= htmlspecialchars($pengaturan['nama_sekolah']) ?> &mdash; Portal Kepala Sekolah &copy; <?= date('Y') ?>
</footer>
</body>
</html>
