<?php
require_once 'includes/config.php';

// Auth
if (!isset($_SESSION['bk_id'])) { header('Location: '.BASE_URL.'portal_bk_login.php'); exit; }
if (isset($_GET['logout']))     { header('Location: '.BASE_URL.'portal_bk_logout.php'); exit; }

$bk_id   = (int)$_SESSION['bk_id'];
$bk_nama = $_SESSION['bk_nama'] ?? 'Guru BK';

$pengaturan = get_pengaturan();
$page = $_GET['page'] ?? 'dashboard';

// Ambil semua kelas
$kelas_list = [];
$kr = $conn->query("SELECT DISTINCT kelas FROM siswa ORDER BY kelas");
while ($r = $kr->fetch_assoc()) $kelas_list[] = $r['kelas'];

$nama_bulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$today = date('Y-m-d');

// Stats hari ini
$stats_today = ['Hadir'=>0,'Terlambat'=>0,'Alpa'=>0,'Sakit'=>0,'Izin'=>0,'Bolos'=>0];
$sr = $conn->query("SELECT status, COUNT(*) c FROM absensi WHERE tanggal='$today' GROUP BY status");
while ($r = $sr->fetch_assoc()) $stats_today[$r['status']] = (int)$r['c'];
$total_siswa   = (int)$conn->query("SELECT COUNT(*) c FROM siswa")->fetch_assoc()['c'];
$sudah_absen   = array_sum($stats_today);
$belum_absen   = $total_siswa - $sudah_absen;

// Foto & data BK — ambil dari tabel sesuai sumber login
$bk_source = $_SESSION['bk_source'] ?? 'guru_bk';
if ($bk_source === 'wali') {
    $bk_row = $conn->query("SELECT * FROM wali WHERE id=$bk_id")->fetch_assoc();
    $foto_bk = $bk_row['foto'] ?? '';
    $foto_dir = 'uploads/foto_wali/';
} else {
    $r = $conn->query("SELECT * FROM guru_bk WHERE id=$bk_id");
    $bk_row  = $r ? $r->fetch_assoc() : [];
    $foto_bk = $bk_row['foto'] ?? '';
    $foto_dir = 'uploads/foto_bk/';
}

// ── Status badge helper ────────────────────────────────────────────────────
$status_kode = [
    'Hadir'     => ['H','#16a34a','#dcfce7'],
    'Terlambat' => ['T','#d97706','#fef3c7'],
    'Alpa'      => ['A','#dc2626','#fee2e2'],
    'Sakit'     => ['S','#2563eb','#dbeafe'],
    'Izin'      => ['I','#7c3aed','#ede9fe'],
    'Bolos'     => ['B','#9a3412','#ffedd5'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Portal Guru BK – <?= htmlspecialchars($pengaturan['nama_sekolah'] ?? '') ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',system-ui,sans-serif;background:#f1f5f9;display:flex;min-height:100vh;font-size:14px}

/* ── SIDEBAR ──────────────────────────────────────── */
.sidebar{width:220px;background:linear-gradient(180deg,#1e3a8a 0%,#0e7490 100%);color:white;display:flex;flex-direction:column;flex-shrink:0;min-height:100vh}
.sidebar-logo{padding:20px 16px 14px;border-bottom:1px solid rgba(255,255,255,.15);text-align:center}
.sidebar-logo img.school-logo{height:48px;margin-bottom:8px}
.sidebar-logo .school-name{font-weight:800;font-size:.88rem;line-height:1.3;color:white}
.sidebar-logo .school-sub{font-size:.7rem;opacity:.65;margin-top:3px}
.bk-profile{padding:14px 16px;border-bottom:1px solid rgba(255,255,255,.12);display:flex;align-items:center;gap:10px}
.bk-avatar{width:40px;height:40px;border-radius:50%;background:rgba(255,255,255,.2);border:2px solid rgba(255,255,255,.4);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:1rem;flex-shrink:0;overflow:hidden}
.bk-avatar img{width:100%;height:100%;object-fit:cover}
.bk-info .name{font-weight:700;font-size:.82rem;color:white}
.bk-info .role{font-size:.7rem;opacity:.65;margin-top:2px}
.nav-section{padding:12px 14px 3px;font-size:.63rem;font-weight:700;letter-spacing:1.5px;opacity:.5;text-transform:uppercase}
.nav-item{display:flex;align-items:center;gap:9px;padding:9px 16px;color:rgba(255,255,255,.8);text-decoration:none;transition:.15s;font-size:.85rem;border-radius:8px;margin:2px 8px}
.nav-item:hover{background:rgba(255,255,255,.12);color:white}
.nav-item.active{background:rgba(255,255,255,.2);color:white;font-weight:600}
.nav-item i{width:16px;text-align:center;font-size:.88rem}
.sidebar-footer{margin-top:auto;padding:12px 14px;border-top:1px solid rgba(255,255,255,.12)}

/* ── MAIN ─────────────────────────────────────────── */
.main{flex:1;display:flex;flex-direction:column;min-height:100vh;overflow:hidden}
.topbar{background:white;padding:11px 22px;display:flex;align-items:center;gap:10px;border-bottom:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05)}
.topbar .page-name{font-weight:700;font-size:.95rem;color:#1e293b}
.topbar .topbar-right{margin-left:auto;display:flex;align-items:center;gap:12px;font-size:.8rem;color:#64748b}
.content{padding:20px;flex:1;overflow-y:auto}

/* ── STAT CARDS ───────────────────────────────────── */
.stat-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:12px;margin-bottom:20px}
.stat-card{background:white;border-radius:12px;padding:14px 16px;box-shadow:0 2px 8px rgba(0,0,0,.06);border-top:4px solid var(--c)}
.stat-card .val{font-size:1.9rem;font-weight:800;color:var(--c)}
.stat-card .lbl{font-size:.72rem;color:#64748b;margin-top:3px;font-weight:600}

/* ── CARDS ────────────────────────────────────────── */
.card{background:white;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.06);overflow:hidden;margin-bottom:18px}
.card-header{padding:13px 18px;font-weight:700;font-size:.88rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:8px;color:#1e293b;flex-wrap:wrap}
.card-body{padding:18px}

/* ── FILTER ───────────────────────────────────────── */
.filter-row{display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap}
.filter-row label{font-size:.75rem;font-weight:600;color:#64748b;display:block;margin-bottom:4px}
.filter-row input,.filter-row select{padding:7px 11px;border:1px solid #e2e8f0;border-radius:8px;font-size:.85rem;outline:none;background:white;min-width:120px}
.filter-row input:focus,.filter-row select:focus{border-color:#0e7490}
.btn-filter{padding:8px 18px;background:#0e7490;color:white;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:.85rem;white-space:nowrap}
.btn-filter:hover{background:#0c6678}

/* ── LEGENDA ──────────────────────────────────────── */
.legenda{display:flex;flex-wrap:wrap;gap:10px;align-items:center;padding:10px 16px;background:#f8fafc;border-bottom:1px solid #f1f5f9}
.leg-item{display:inline-flex;align-items:center;gap:6px;font-size:.78rem;font-weight:600}
.leg-box{width:22px;height:20px;border-radius:4px;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.72rem}

/* ── TABLE ────────────────────────────────────────── */
.tbl-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch}
table{width:100%;border-collapse:collapse;font-size:.78rem}
th{background:#1e293b;color:white;padding:7px 5px;text-align:center;white-space:nowrap;font-size:.72rem}
th.th-left{text-align:left}
th.sticky{position:sticky;z-index:3;background:#1e293b}
td{padding:5px 5px;border-bottom:1px solid #f1f5f9;vertical-align:middle}
td.sticky{position:sticky;z-index:1;background:white}
tr:nth-child(even) td{background:#f8fafc}
tr:nth-child(even) td.sticky{background:#f8fafc}
.st-box{display:inline-block;width:20px;height:20px;line-height:20px;border-radius:4px;font-weight:800;font-size:.68rem;text-align:center}
.weekend{background:#f1f5f9 !important}
.sum-cell{text-align:center;font-weight:700;padding:5px 4px}

/* ── STATUS BADGE ─────────────────────────────────── */
.badge{display:inline-block;padding:2px 10px;border-radius:20px;font-size:.74rem;font-weight:700}
.b-hadir{background:#dcfce7;color:#15803d}
.b-terlambat{background:#fef9c3;color:#854d0e}
.b-alpa{background:#fee2e2;color:#991b1b}
.b-sakit{background:#dbeafe;color:#1e40af}
.b-izin{background:#ede9fe;color:#5b21b6}
.b-bolos{background:#ffedd5;color:#9a3412}

/* ── HAMBURGER & OVERLAY ──────────────────────────── */
.hamburger{display:none;flex-direction:column;justify-content:center;gap:5px;width:34px;height:34px;cursor:pointer;padding:4px;border:none;background:transparent;flex-shrink:0}
.hamburger span{display:block;width:22px;height:2px;background:#1e293b;border-radius:2px;transition:.25s}
.hamburger.open span:nth-child(1){transform:translateY(7px) rotate(45deg)}
.hamburger.open span:nth-child(2){opacity:0}
.hamburger.open span:nth-child(3){transform:translateY(-7px) rotate(-45deg)}
.sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:998}
.sidebar-overlay.show{display:block}

/* ── PRINT ────────────────────────────────────────── */
.print-header{display:none}
@media print{
    .no-print,.sidebar,.topbar,.hamburger,.sidebar-overlay{display:none!important}
    .main{width:100%}
    .content{padding:0}
    .print-header{display:block!important;text-align:center;margin-bottom:14px}
    .card{box-shadow:none;border:1px solid #ddd}
    table{font-size:.65rem!important}
    .filter-row,.legenda{display:none}
}

/* ── MOBILE ───────────────────────────────────────── */
@media(max-width:768px){
    body{flex-direction:column}
    .sidebar{position:fixed;left:-240px;top:0;bottom:0;width:240px;z-index:999;transition:left .28s;overflow-y:auto}
    .sidebar.open{left:0}
    .hamburger{display:flex}
    .main{width:100%}
    .topbar{padding:9px 12px}
    .topbar .topbar-right .school-txt{display:none}
    .content{padding:12px}
    .stat-grid{grid-template-columns:repeat(2,1fr);gap:8px}
    .stat-card .val{font-size:1.5rem}
    .filter-row{flex-direction:column}
    .filter-row input,.filter-row select,.btn-filter{width:100%}
}
@media(max-width:400px){
    .stat-grid{grid-template-columns:repeat(2,1fr)}
    .topbar .topbar-right{display:none}
}
</style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- ═══════════ SIDEBAR ═══════════ -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <?php if (!empty($pengaturan['logo']) && file_exists('uploads/logo/'.$pengaturan['logo'])): ?>
        <img src="<?= BASE_URL ?>uploads/logo/<?= $pengaturan['logo'] ?>" class="school-logo">
        <?php else: ?>
        <div style="font-size:1.8rem;margin-bottom:8px">🏫</div>
        <?php endif; ?>
        <div class="school-name"><?= htmlspecialchars($pengaturan['nama_sekolah'] ?? 'Portal BK') ?></div>
        <div class="school-sub">Sistem Absensi Digital</div>
    </div>

    <div class="bk-profile">
        <div class="bk-avatar">
            <?php if ($foto_bk && file_exists($foto_dir.$foto_bk)): ?>
                <img src="<?= BASE_URL ?><?= $foto_dir ?><?= $foto_bk ?>">
            <?php else: ?>
                <?= strtoupper(substr($bk_nama,0,1)) ?>
            <?php endif; ?>
        </div>
        <div class="bk-info">
            <div class="name"><?= htmlspecialchars($bk_nama) ?></div>
            <div class="role"><i class="fas fa-user-shield" style="font-size:.65rem"></i> Guru BK</div>
        </div>
    </div>

    <div class="nav-section">Utama</div>
    <a href="portal_bk.php?page=dashboard" class="nav-item <?= $page==='dashboard'?'active':'' ?>">
        <i class="fas fa-home"></i> Dashboard
    </a>

    <div class="nav-section">Laporan</div>
    <a href="portal_bk.php?page=rekap_harian" class="nav-item <?= $page==='rekap_harian'?'active':'' ?>">
        <i class="fas fa-calendar-day"></i> Rekap Harian
    </a>
    <a href="portal_bk.php?page=rekap_bulanan" class="nav-item <?= $page==='rekap_bulanan'?'active':'' ?>">
        <i class="fas fa-calendar-alt"></i> Rekap Bulanan
    </a>
    <a href="portal_bk.php?page=laporan_rekap" class="nav-item <?= $page==='laporan_rekap'?'active':'' ?>"
       style="<?= $page==='laporan_rekap'?'':'border-left:3px solid #f59e0b;background:rgba(245,158,11,.08)' ?>">
        <i class="fas fa-clipboard-list" style="color:#f59e0b"></i> Laporan Rekap Harian
    </a>

    <div class="sidebar-footer">
        <a href="?logout=1" style="color:rgba(255,255,255,.7);text-decoration:none;font-size:.8rem;display:flex;align-items:center;gap:6px">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<!-- ═══════════ MAIN ═══════════ -->
<div class="main">
    <div class="topbar no-print">
        <button class="hamburger" id="hamburgerBtn" onclick="toggleSidebar()"><span></span><span></span><span></span></button>
        <div class="page-name">
            <?php
            $icons = ['dashboard'=>'home','rekap_harian'=>'calendar-day','rekap_bulanan'=>'calendar-alt','laporan_rekap'=>'clipboard-list'];
            $titles= ['dashboard'=>'Dashboard','rekap_harian'=>'Rekap Harian','rekap_bulanan'=>'Rekap Bulanan','laporan_rekap'=>'Laporan Rekap Harian'];
            echo '<i class="fas fa-'.($icons[$page]??'home').'"></i> '.($titles[$page]??'Portal BK');
            ?>
        </div>
        <div class="topbar-right">
            <span class="school-txt"><?= htmlspecialchars($pengaturan['nama_sekolah'] ?? '') ?> &nbsp;|&nbsp;</span>
            <i class="fas fa-clock"></i> <span id="jam">--:--:--</span>
        </div>
    </div>

    <div class="content">

<?php /* ═══════════════════════ DASHBOARD ═══════════════════════ */
if ($page === 'dashboard'): ?>

<!-- Print header -->
<div class="print-header">
    <div style="font-size:1.1rem;font-weight:700"><?= htmlspecialchars($pengaturan['nama_sekolah'] ?? '') ?></div>
    <div style="font-size:.85rem">Dashboard Absensi – <?= format_tanggal($today) ?></div>
    <hr style="border:1px solid #000;margin:6px 0">
</div>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:8px">
    <div>
        <div style="font-size:1rem;font-weight:700;color:#1e293b"><i class="fas fa-calendar-day" style="color:#0e7490"></i> Kehadiran Hari Ini</div>
        <div style="font-size:.8rem;color:#64748b"><?= format_tanggal($today) ?></div>
    </div>
    <button onclick="window.print()" class="no-print" style="padding:7px 16px;background:#0e7490;color:white;border:none;border-radius:8px;font-weight:600;cursor:pointer;font-size:.82rem">
        <i class="fas fa-print"></i> Print
    </button>
</div>

<div class="stat-grid">
    <?php
    $sc = [
        'Total Siswa'  => [$total_siswa, '#4f46e5','fas fa-users'],
        'Hadir'        => [$stats_today['Hadir'],  '#16a34a','fas fa-check-circle'],
        'Terlambat'    => [$stats_today['Terlambat'],'#d97706','fas fa-clock'],
        'Alpa'         => [$stats_today['Alpa'],   '#dc2626','fas fa-times-circle'],
        'Sakit'        => [$stats_today['Sakit'],  '#2563eb','fas fa-hospital'],
        'Izin'         => [$stats_today['Izin'],   '#7c3aed','fas fa-file-alt'],
        'Bolos'        => [$stats_today['Bolos'],  '#9a3412','fas fa-ban'],
        'Belum Absen'  => [$belum_absen,           '#64748b','fas fa-question-circle'],
    ];
    foreach ($sc as $lbl => [$val,$c,$ico]):
    ?>
    <div class="stat-card" style="--c:<?= $c ?>">
        <div class="val"><?= $val ?></div>
        <div class="lbl"><i class="<?= $ico ?>"></i> <?= $lbl ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Progress kehadiran -->
<div class="card">
    <div class="card-header"><i class="fas fa-chart-bar" style="color:#0e7490"></i> Persentase Kehadiran Hari Ini</div>
    <div class="card-body">
        <?php
        $pct_data = [
            'Hadir'    =>[$stats_today['Hadir'],   '#16a34a'],
            'Terlambat'=>[$stats_today['Terlambat'],'#d97706'],
            'Alpa'     =>[$stats_today['Alpa'],    '#dc2626'],
            'Sakit'    =>[$stats_today['Sakit'],   '#2563eb'],
            'Izin'     =>[$stats_today['Izin'],    '#7c3aed'],
            'Bolos'    =>[$stats_today['Bolos'],   '#9a3412'],
        ];
        foreach ($pct_data as $lbl=>[$val,$c]):
            $pct = $total_siswa>0 ? round($val/$total_siswa*100,1) : 0;
        ?>
        <div style="margin-bottom:10px">
            <div style="display:flex;justify-content:space-between;font-size:.8rem;font-weight:600;margin-bottom:4px;color:#374151">
                <span><?= $lbl ?></span>
                <span style="color:<?= $c ?>"><?= $val ?> siswa (<?= $pct ?>%)</span>
            </div>
            <div style="height:8px;background:#f1f5f9;border-radius:4px;overflow:hidden">
                <div style="height:100%;width:<?= $pct ?>%;background:<?= $c ?>;border-radius:4px;transition:.6s"></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Rekap per kelas hari ini -->
<div class="card no-print">
    <div class="card-header"><i class="fas fa-school" style="color:#0e7490"></i> Rekap Per Kelas – Hari Ini</div>
    <div class="tbl-wrap">
        <table>
            <thead>
                <tr>
                    <th class="th-left" style="padding:7px 10px;min-width:80px">Kelas</th>
                    <th style="background:#166534;min-width:40px">H</th>
                    <th style="background:#854d0e;min-width:40px">T</th>
                    <th style="background:#991b1b;min-width:40px">A</th>
                    <th style="background:#1e40af;min-width:40px">S</th>
                    <th style="background:#5b21b6;min-width:40px">I</th>
                    <th style="background:#9a3412;min-width:40px">B</th>
                    <th style="min-width:50px">Total</th>
                    <th style="background:#374151;min-width:50px">Belum</th>
                </tr>
            </thead>
            <tbody>
            <?php
            foreach ($kelas_list as $kls):
                $jml_kls = (int)$conn->query("SELECT COUNT(*) c FROM siswa WHERE kelas='$kls'")->fetch_assoc()['c'];
                $st_kls  = $conn->query("SELECT status, COUNT(*) c FROM absensi WHERE tanggal='$today' AND kelas='$kls' GROUP BY status")->fetch_all(MYSQLI_ASSOC);
                $sk = array_column($st_kls,'c','status');
                $H=$sk['Hadir']??0; $T=$sk['Terlambat']??0; $A=$sk['Alpa']??0; $S=$sk['Sakit']??0; $Iz=$sk['Izin']??0; $B=$sk['Bolos']??0;
                $tot_absen=$H+$T+$A+$S+$Iz+$B; $blm=$jml_kls-$tot_absen;
            ?>
            <tr>
                <td style="font-weight:700;padding:5px 10px"><?= htmlspecialchars($kls) ?></td>
                <td class="sum-cell" style="color:#166534;background:#f0fdf4"><?= $H ?></td>
                <td class="sum-cell" style="color:#854d0e;background:#fffbeb"><?= $T ?></td>
                <td class="sum-cell" style="color:#991b1b;background:#fef2f2"><?= $A ?></td>
                <td class="sum-cell" style="color:#1e40af;background:#eff6ff"><?= $S ?></td>
                <td class="sum-cell" style="color:#5b21b6;background:#f5f3ff"><?= $Iz ?></td>
                <td class="sum-cell" style="color:#9a3412;background:#fff7ed"><?= $B ?></td>
                <td class="sum-cell" style="color:#374151;font-weight:800"><?= $tot_absen ?>/<?= $jml_kls ?></td>
                <td class="sum-cell" style="color:#ef4444;font-weight:700;background:#fef2f2"><?= $blm ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php /* ═══════════════════════ REKAP HARIAN ═══════════════════════ */
elseif ($page === 'rekap_harian'):
    $tgl_h    = sanitize($_GET['tanggal'] ?? $today);
    $kelas_h  = sanitize($_GET['kelas']   ?? '');
    if ($tgl_h > $today) $tgl_h = $today;

    $where_h  = "a.tanggal='$tgl_h'";
    if ($kelas_h) $where_h .= " AND a.kelas='$kelas_h'";

    $data_h = $conn->query("SELECT a.*, s.foto FROM absensi a LEFT JOIN siswa s ON s.id=a.siswa_id WHERE $where_h ORDER BY a.kelas, a.nama");
    $sr2 = $conn->query("SELECT status,COUNT(*) c FROM absensi WHERE tanggal='$tgl_h'" . ($kelas_h?" AND kelas='$kelas_h'":"") . " GROUP BY status")->fetch_all(MYSQLI_ASSOC);
    $stat_h = array_column($sr2,'c','status');
?>
<!-- Print header -->
<div class="print-header">
    <?php if (!empty($pengaturan['logo']) && file_exists('uploads/logo/'.$pengaturan['logo'])): ?>
    <img src="<?= BASE_URL ?>uploads/logo/<?= $pengaturan['logo'] ?>" style="height:56px;margin-bottom:6px">
    <?php endif; ?>
    <div style="font-size:1.1rem;font-weight:700;text-transform:uppercase"><?= htmlspecialchars($pengaturan['nama_sekolah'] ?? '') ?></div>
    <div style="font-size:.8rem"><?= htmlspecialchars($pengaturan['alamat'] ?? '') ?></div>
    <hr style="border:2px solid #000;margin:6px 0">
    <div style="font-size:.95rem;font-weight:700">REKAP ABSENSI HARIAN</div>
    <div style="font-size:.82rem"><?= format_tanggal($tgl_h) ?><?= $kelas_h ? ' – Kelas '.$kelas_h : '' ?></div>
</div>

<!-- Filter -->
<div class="card no-print">
    <div class="card-body">
        <form method="GET" class="filter-row">
            <input type="hidden" name="page" value="rekap_harian">
            <div>
                <label>Tanggal</label>
                <input type="date" name="tanggal" value="<?= $tgl_h ?>" max="<?= $today ?>">
            </div>
            <div>
                <label>Kelas</label>
                <select name="kelas">
                    <option value="">-- Semua Kelas --</option>
                    <?php foreach ($kelas_list as $k): ?>
                    <option value="<?= $k ?>" <?= $kelas_h===$k?'selected':'' ?>><?= htmlspecialchars($k) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>&nbsp;</label>
                <button type="submit" class="btn-filter"><i class="fas fa-search"></i> Tampilkan</button>
            </div>
        </form>
    </div>
</div>

<!-- Stat mini -->
<div class="stat-grid" style="grid-template-columns:repeat(auto-fill,minmax(110px,1fr))">
    <?php
    $sc2=[
        'Hadir'    =>['#16a34a','fas fa-check-circle'],
        'Terlambat'=>['#d97706','fas fa-clock'],
        'Alpa'     =>['#dc2626','fas fa-times-circle'],
        'Sakit'    =>['#2563eb','fas fa-hospital'],
        'Izin'     =>['#7c3aed','fas fa-file-alt'],
        'Bolos'    =>['#9a3412','fas fa-ban'],
    ];
    foreach ($sc2 as $lbl=>[$c,$ico]):
    ?>
    <div class="stat-card" style="--c:<?= $c ?>">
        <div class="val"><?= $stat_h[$lbl] ?? 0 ?></div>
        <div class="lbl"><i class="<?= $ico ?>"></i> <?= $lbl ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Tabel -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-table" style="color:#0e7490"></i>
        Data Absensi – <?= format_tanggal($tgl_h) ?><?= $kelas_h ? ' &mdash; Kelas <strong>'.$kelas_h.'</strong>' : '' ?>
        <span style="margin-left:auto;font-size:.75rem;color:#64748b;font-weight:400"><?= $data_h->num_rows ?> siswa</span>
        <div class="no-print" style="margin-left:8px;display:flex;gap:8px">
            <input type="text" id="srHarian" placeholder="🔍 Cari nama..." oninput="srFilter('hTbl',this.value)"
                   style="padding:5px 10px;border:1px solid #e2e8f0;border-radius:7px;font-size:.78rem;outline:none;width:150px">
            <button onclick="window.print()" style="padding:5px 12px;border:none;background:#0e7490;color:white;border-radius:7px;font-size:.78rem;font-weight:600;cursor:pointer">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>
    <div class="tbl-wrap">
        <table id="hTbl">
            <thead>
                <tr>
                    <th style="width:35px">#</th>
                    <th style="text-align:left;min-width:80px">NIS</th>
                    <th style="text-align:left;min-width:140px">Nama</th>
                    <th style="min-width:60px">Kelas</th>
                    <th style="min-width:65px">Jam Masuk</th>
                    <th style="min-width:65px">Jam Pulang</th>
                    <th style="min-width:80px">Status</th>
                    <th style="text-align:left;min-width:120px">Keterangan</th>
                    <th style="min-width:60px">Metode</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($data_h->num_rows===0): ?>
            <tr><td colspan="9" style="text-align:center;padding:40px;color:#94a3b8">
                <i class="fas fa-inbox" style="font-size:1.8rem;display:block;margin-bottom:8px"></i>
                Tidak ada data untuk tanggal ini<?= $kelas_h ? ' di kelas '.$kelas_h : '' ?>
            </td></tr>
            <?php else:
            $no=0; while ($row=$data_h->fetch_assoc()): $no++;
                $badge_cls = ['Hadir'=>'b-hadir','Terlambat'=>'b-terlambat','Alpa'=>'b-alpa','Sakit'=>'b-sakit','Izin'=>'b-izin','Bolos'=>'b-bolos'];
            ?>
            <tr>
                <td style="text-align:center"><?= $no ?></td>
                <td style="font-family:monospace"><?= $row['nis'] ?></td>
                <td>
                    <div style="display:flex;align-items:center;gap:7px">
                        <?php if (!empty($row['foto']) && file_exists('uploads/foto/'.$row['foto'])): ?>
                            <img src="<?= BASE_URL ?>uploads/foto/<?= $row['foto'] ?>" style="width:28px;height:28px;border-radius:50%;object-fit:cover;flex-shrink:0">
                        <?php else: ?>
                            <div style="width:28px;height:28px;border-radius:50%;background:#0e7490;color:white;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.7rem;flex-shrink:0"><?= strtoupper(substr($row['nama'],0,1)) ?></div>
                        <?php endif; ?>
                        <?= htmlspecialchars($row['nama']) ?>
                    </div>
                </td>
                <td style="text-align:center"><?= $row['kelas'] ?></td>
                <td style="text-align:center"><?= $row['jam_masuk'] ? date('H:i',strtotime($row['jam_masuk'])) : '<span style="color:#cbd5e1">—</span>' ?></td>
                <td style="text-align:center"><?= $row['jam_pulang'] ? date('H:i',strtotime($row['jam_pulang'])) : '<span style="color:#cbd5e1">—</span>' ?></td>
                <td style="text-align:center"><span class="badge <?= $badge_cls[$row['status']] ?? '' ?>"><?= $row['status'] ?></span></td>
                <td><?= htmlspecialchars($row['keterangan'] ?? '') ?: '<span style="color:#cbd5e1">—</span>' ?></td>
                <td style="text-align:center"><small style="color:#64748b"><?= $row['metode'] ?? '—' ?></small></td>
            </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- TTD -->
<div style="margin-top:28px;display:flex;justify-content:flex-end;padding-right:20px;print-show:block">
    <div style="text-align:center;min-width:200px;font-size:.85rem">
        <div><?= htmlspecialchars($pengaturan['nama_sekolah'] ?? '') ?>, <?= date('d') ?> <?= $nama_bulan[(int)date('n')] ?> <?= date('Y') ?></div>
        <div style="margin-top:3px">Guru BK,</div>
        <div style="margin-top:56px;font-weight:700;text-decoration:underline"><?= htmlspecialchars($bk_nama) ?></div>
        <div style="font-size:.78rem">NIP. <?= htmlspecialchars($bk_row['nip'] ?? '-') ?></div>
    </div>
</div>

<?php /* ═══════════════════════ REKAP BULANAN ═══════════════════════ */
elseif ($page === 'rekap_bulanan'):
    $bulan_b  = (int)($_GET['bulan'] ?? date('n'));
    $tahun_b  = (int)($_GET['tahun'] ?? date('Y'));
    $kelas_b  = sanitize($_GET['kelas'] ?? '');

    $jumlah_hari = cal_days_in_month(CAL_GREGORIAN, $bulan_b, $tahun_b);

    $kelas_cond_b = $kelas_b ? "AND kelas='$kelas_b'" : '';
    $siswa_b = $conn->query("SELECT id,nis,nama,kelas FROM siswa WHERE 1=1 $kelas_cond_b ORDER BY kelas,nama");

    $ab_raw = $conn->query("
        SELECT siswa_id, DAY(tanggal) tgl, status FROM absensi
        WHERE MONTH(tanggal)=$bulan_b AND YEAR(tanggal)=$tahun_b
        " . ($kelas_b ? "AND siswa_id IN (SELECT id FROM siswa WHERE kelas='$kelas_b')" : "") . "
    ");
    $ab_map = [];
    while ($a=$ab_raw->fetch_assoc()) $ab_map[$a['siswa_id']][$a['tgl']] = $a['status'];
?>
<!-- Print header -->
<div class="print-header">
    <?php if (!empty($pengaturan['logo']) && file_exists('uploads/logo/'.$pengaturan['logo'])): ?>
    <img src="<?= BASE_URL ?>uploads/logo/<?= $pengaturan['logo'] ?>" style="height:56px;margin-bottom:6px">
    <?php endif; ?>
    <div style="font-size:1.1rem;font-weight:700;text-transform:uppercase"><?= htmlspecialchars($pengaturan['nama_sekolah'] ?? '') ?></div>
    <div style="font-size:.8rem"><?= htmlspecialchars($pengaturan['alamat'] ?? '') ?></div>
    <hr style="border:2px solid #000;margin:6px 0">
    <div style="font-size:.95rem;font-weight:700">DAFTAR HADIR SISWA</div>
    <div style="font-size:.82rem">BULAN: <?= strtoupper($nama_bulan[$bulan_b].' '.$tahun_b) ?> &nbsp;|&nbsp; KELAS: <?= $kelas_b ?: 'SEMUA KELAS' ?></div>
</div>

<!-- Filter -->
<div class="card no-print">
    <div class="card-body">
        <form method="GET" class="filter-row">
            <input type="hidden" name="page" value="rekap_bulanan">
            <div>
                <label>Bulan</label>
                <select name="bulan">
                    <?php for ($m=1;$m<=12;$m++): ?>
                    <option value="<?= $m ?>" <?= $bulan_b==$m?'selected':'' ?>><?= $nama_bulan[$m] ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label>Tahun</label>
                <select name="tahun">
                    <?php for ($y=date('Y');$y>=date('Y')-3;$y--): ?>
                    <option value="<?= $y ?>" <?= $tahun_b==$y?'selected':'' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label>Kelas</label>
                <select name="kelas">
                    <option value="">-- Semua Kelas --</option>
                    <?php foreach ($kelas_list as $k): ?>
                    <option value="<?= $k ?>" <?= $kelas_b===$k?'selected':'' ?>><?= htmlspecialchars($k) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>&nbsp;</label>
                <button type="submit" class="btn-filter"><i class="fas fa-search"></i> Tampilkan</button>
            </div>
        </form>
    </div>
</div>

<!-- Legenda -->
<div class="card">
    <div class="legenda no-print">
        <span style="font-size:.78rem;font-weight:700;color:#374151">Keterangan:</span>
        <?php foreach (['H'=>['#16a34a','#dcfce7','Hadir'],'T'=>['#d97706','#fef3c7','Terlambat'],'A'=>['#dc2626','#fee2e2','Alpa'],'S'=>['#2563eb','#dbeafe','Sakit'],'I'=>['#7c3aed','#ede9fe','Izin'],'B'=>['#9a3412','#ffedd5','Bolos']] as $kd=>[$warna,$bg,$lbl]): ?>
        <span class="leg-item">
            <span class="leg-box" style="background:<?= $bg ?>;color:<?= $warna ?>"><?= $kd ?></span>= <?= $lbl ?>
        </span>
        <?php endforeach; ?>
        <span style="font-size:.74rem;color:#94a3b8"><span style="color:#64748b;font-weight:700">—</span> = Libur &nbsp;<span style="color:#e2e8f0">·</span> = Tidak ada data</span>
    </div>

    <div class="card-header">
        <i class="fas fa-table" style="color:#0e7490"></i>
        Daftar Hadir <?= $nama_bulan[$bulan_b].' '.$tahun_b ?><?= $kelas_b ? ' &mdash; Kelas <strong>'.$kelas_b.'</strong>' : '' ?>
        <span style="margin-left:auto;font-size:.75rem;color:#64748b;font-weight:400"><?= $siswa_b->num_rows ?> siswa</span>
        <div class="no-print" style="margin-left:8px">
            <button onclick="window.print()" style="padding:5px 12px;border:none;background:#0e7490;color:white;border-radius:7px;font-size:.78rem;font-weight:600;cursor:pointer">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>
    <div class="tbl-wrap">
        <table id="bTbl" style="font-size:.72rem;min-width:max-content">
            <thead>
                <tr>
                    <th class="sticky" style="left:0;min-width:32px;padding:7px 5px">#</th>
                    <th class="sticky th-left" style="left:32px;min-width:80px;padding:7px 6px">NIS</th>
                    <th class="sticky th-left" style="left:112px;min-width:150px;padding:7px 8px">NAMA</th>
                    <?php if (!$kelas_b): ?><th style="min-width:55px">KELAS</th><?php endif; ?>
                    <?php for ($d=1;$d<=$jumlah_hari;$d++):
                        $ts = mktime(0,0,0,$bulan_b,$d,$tahun_b);
                        $hk = date('N',$ts);
                        $isWe = $hk>=6;
                        $hs = ['','Sen','Sel','Rab','Kam','Jum','Sab','Min'][$hk];
                    ?>
                    <th style="min-width:28px;padding:3px 2px;<?= $isWe?'background:#374151':'' ?>" title="<?= $hs ?> <?= $d ?>">
                        <div style="font-size:.6rem;font-weight:400;opacity:.7"><?= $hs ?></div>
                        <div><?= $d ?></div>
                    </th>
                    <?php endfor; ?>
                    <th style="background:#166534;min-width:28px" title="Hadir">H</th>
                    <th style="background:#854d0e;min-width:28px" title="Terlambat">T</th>
                    <th style="background:#991b1b;min-width:28px" title="Alpa">A</th>
                    <th style="background:#1e40af;min-width:28px" title="Sakit">S</th>
                    <th style="background:#5b21b6;min-width:28px" title="Izin">I</th>
                    <th style="background:#9a3412;min-width:28px" title="Bolos">B</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($siswa_b->num_rows===0): ?>
            <tr><td colspan="<?= 3+$jumlah_hari+6+(!$kelas_b?1:0) ?>" style="text-align:center;padding:40px;color:#94a3b8">
                <i class="fas fa-inbox" style="font-size:1.8rem;display:block;margin-bottom:8px"></i>
                Belum ada data<?= $kelas_b ? ' untuk kelas '.$kelas_b : '' ?>
            </td></tr>
            <?php else:
            $no=0; while ($s=$siswa_b->fetch_assoc()): $no++;
                $sid=$s['id'];
                $tot=['H'=>0,'T'=>0,'A'=>0,'S'=>0,'I'=>0,'B'=>0];
                $bg_row=$no%2==0?'#f8fafc':'white';
            ?>
            <tr>
                <td class="sticky" style="left:0;text-align:center;background:<?= $bg_row ?>"><?= $no ?></td>
                <td class="sticky" style="left:32px;font-family:monospace;background:<?= $bg_row ?>"><?= $s['nis'] ?></td>
                <td class="sticky" style="left:112px;white-space:nowrap;font-weight:600;background:<?= $bg_row ?>"><?= htmlspecialchars($s['nama']) ?></td>
                <?php if (!$kelas_b): ?><td style="text-align:center"><?= $s['kelas'] ?></td><?php endif; ?>
                <?php for ($d=1;$d<=$jumlah_hari;$d++):
                    $ts=mktime(0,0,0,$bulan_b,$d,$tahun_b);
                    $hk=date('N',$ts); $isWe=$hk>=6;
                    $st=$ab_map[$sid][$d]??null;
                    if ($st && isset($status_kode[$st])) {
                        [$kd,$warna,$bgc]=$status_kode[$st];
                        if (isset($tot[$kd])) $tot[$kd]++;
                    }
                ?>
                <td style="padding:2px;text-align:center;<?= $isWe?'background:#f1f5f9':'' ?>">
                    <?php if ($st && isset($status_kode[$st])): [$kd,$warna,$bgc]=$status_kode[$st]; ?>
                    <span class="st-box" style="background:<?= $bgc ?>;color:<?= $warna ?>"><?= $kd ?></span>
                    <?php elseif ($isWe): ?>
                    <span style="color:#cbd5e1;font-size:.65rem">-</span>
                    <?php else: ?>
                    <span style="color:#e2e8f0;font-size:.6rem">·</span>
                    <?php endif; ?>
                </td>
                <?php endfor; ?>
                <td class="sum-cell" style="color:#166534;background:#f0fdf4"><?= $tot['H'] ?></td>
                <td class="sum-cell" style="color:#854d0e;background:#fffbeb"><?= $tot['T'] ?></td>
                <td class="sum-cell" style="color:#991b1b;background:#fef2f2"><?= $tot['A'] ?></td>
                <td class="sum-cell" style="color:#1e40af;background:#eff6ff"><?= $tot['S'] ?></td>
                <td class="sum-cell" style="color:#5b21b6;background:#f5f3ff"><?= $tot['I'] ?></td>
                <td class="sum-cell" style="color:#9a3412;background:#fff7ed"><?= $tot['B'] ?></td>
            </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- TTD -->
<div style="margin-top:28px;display:flex;justify-content:flex-end;padding-right:20px">
    <div style="text-align:center;min-width:200px;font-size:.85rem">
        <div><?= htmlspecialchars($pengaturan['nama_sekolah'] ?? '') ?>, <?= date('d') ?> <?= $nama_bulan[(int)date('n')] ?> <?= date('Y') ?></div>
        <div style="margin-top:3px">Guru BK,</div>
        <div style="margin-top:56px;font-weight:700;text-decoration:underline"><?= htmlspecialchars($bk_nama) ?></div>
        <div style="font-size:.78rem">NIP. <?= htmlspecialchars($bk_row['nip'] ?? '-') ?></div>
    </div>
</div>

<?php endif; ?>

<?php
// ============================================================
// === PAGE: LAPORAN REKAP HARIAN (BK)                     ===
// ============================================================
if ($page === 'laporan_rekap'):
    $lr_nb = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $lr_nh = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];

    $lr_tgl = (int)($_GET['lr_tgl'] ?? date('j'));
    $lr_bln = (int)($_GET['lr_bln'] ?? date('n'));
    $lr_thn = (int)($_GET['lr_thn'] ?? date('Y'));
    $lr_max = cal_days_in_month(CAL_GREGORIAN, $lr_bln, $lr_thn);
    if ($lr_tgl < 1 || $lr_tgl > $lr_max) $lr_tgl = 1;
    $lr_date      = sprintf('%04d-%02d-%02d', $lr_thn, $lr_bln, $lr_tgl);
    $lr_hari_nama = $lr_nh[date('w', strtotime($lr_date))];

    $lr_kelas_list_q = $conn->query("SELECT DISTINCT kelas FROM siswa ORDER BY kelas");
    $lr_kelas_list = [];
    while ($kr = $lr_kelas_list_q->fetch_assoc()) $lr_kelas_list[] = $kr['kelas'];

    $lr_rekap_kelas = [];
    $lr_grand = ['siswa'=>0,'Hadir'=>0,'Terlambat'=>0,'Alpa'=>0,'Sakit'=>0,'Izin'=>0,'Bolos'=>0];
    foreach ($lr_kelas_list as $k) {
        $k_esc = $conn->real_escape_string($k);
        $total = (int)$conn->query("SELECT COUNT(*) c FROM siswa WHERE kelas='$k_esc'")->fetch_assoc()['c'];
        $sq    = $conn->query("SELECT status, COUNT(*) total FROM absensi WHERE tanggal='$lr_date' AND kelas='$k_esc' GROUP BY status");
        $s2    = ['Hadir'=>0,'Terlambat'=>0,'Alpa'=>0,'Sakit'=>0,'Izin'=>0,'Bolos'=>0];
        while ($r = $sq->fetch_assoc()) if (isset($s2[$r['status']])) $s2[$r['status']] = (int)$r['total'];
        $pct = $total > 0 ? round(($s2['Hadir']+$s2['Terlambat'])/$total*100,1) : 0;
        $lr_rekap_kelas[] = ['kelas'=>$k,'siswa'=>$total,'Hadir'=>$s2['Hadir'],'Terlambat'=>$s2['Terlambat'],
            'Alpa'=>$s2['Alpa'],'Sakit'=>$s2['Sakit'],'Izin'=>$s2['Izin'],'Bolos'=>$s2['Bolos'],'pct'=>$pct];
        $lr_grand['siswa'] += $total;
        foreach (['Hadir','Terlambat','Alpa','Sakit','Izin','Bolos'] as $st) $lr_grand[$st] += $s2[$st];
    }
    $lr_grand_pct = $lr_grand['siswa'] > 0 ? round(($lr_grand['Hadir']+$lr_grand['Terlambat'])/$lr_grand['siswa']*100,1) : 0;

    $lr_filter_status = isset($_GET['lr_status']) ? $conn->real_escape_string(htmlspecialchars(strip_tags(trim($_GET['lr_status'])))) : '';
    if (!in_array($lr_filter_status, ['Alpa','Sakit','Izin','Bolos','Terlambat','Hadir'])) $lr_filter_status = '';
    $lr_status_sql = $lr_filter_status ? "AND a.status='$lr_filter_status'" : "AND a.status IN ('Alpa','Sakit','Izin','Bolos','Terlambat','Hadir')";
    $lr_semua_q    = $conn->query("SELECT a.nis, a.nama, a.kelas, a.status, a.keterangan FROM absensi a WHERE a.tanggal='$lr_date' $lr_status_sql ORDER BY a.status, a.kelas, a.nama");
    $lr_rekap_semua = $lr_semua_q ? $lr_semua_q->fetch_all(MYSQLI_ASSOC) : [];

    // Export Excel
    if (isset($_GET['lr_export_excel'])) {
        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        header("Content-Disposition: attachment; filename=\"LaporanRekap_BK_{$lr_date}".($lr_filter_status?"_{$lr_filter_status}":"_Semua").".xls\"");
        header("Cache-Control: max-age=0");
        echo "\xEF\xBB\xBF<table border='1'>";
        echo "<tr><th colspan='9' style='text-align:center;font-size:14pt;font-weight:bold'>".htmlspecialchars($pengaturan['nama_sekolah'])."</th></tr>";
        echo "<tr><th colspan='9' style='text-align:center'>LAPORAN REKAP HARIAN — ".strtoupper($lr_hari_nama).", {$lr_tgl} ".$lr_nb[$lr_bln]." {$lr_thn}</th></tr>";
        echo "<tr><th>NO</th><th>KELAS</th><th>JML SISWA</th><th>HADIR</th><th>TERLAMBAT</th><th>ALPA</th><th>SAKIT</th><th>IZIN</th><th>BOLOS</th></tr>";
        foreach ($lr_rekap_kelas as $i => $r)
            echo "<tr><td>".($i+1)."</td><td>{$r['kelas']}</td><td>{$r['siswa']}</td><td>{$r['Hadir']}</td><td>{$r['Terlambat']}</td><td>{$r['Alpa']}</td><td>{$r['Sakit']}</td><td>{$r['Izin']}</td><td>{$r['Bolos']}</td></tr>";
        echo "<tr><td colspan='2'><b>TOTAL</b></td><td><b>{$lr_grand['siswa']}</b></td><td><b>{$lr_grand['Hadir']}</b></td><td><b>{$lr_grand['Terlambat']}</b></td><td><b>{$lr_grand['Alpa']}</b></td><td><b>{$lr_grand['Sakit']}</b></td><td><b>{$lr_grand['Izin']}</b></td><td><b>{$lr_grand['Bolos']}</b></td></tr>";
        echo "</table>";
        exit;
    }
?>
<style>
.lr-bk-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(90px,1fr));gap:8px;margin-bottom:18px}
.lr-bk-stat{background:rgba(14,116,144,.08);border:1px solid rgba(14,116,144,.18);border-radius:10px;padding:10px;text-align:center}
.lr-bk-stat .num{font-size:1.4rem;font-weight:900;line-height:1}
.lr-bk-stat .lbl{font-size:.63rem;color:#64748b;margin-top:3px;font-weight:600;text-transform:uppercase;letter-spacing:.4px}
.lr-bk-filter{background:rgba(245,158,11,.06);border:1px solid rgba(245,158,11,.25);border-radius:10px;padding:12px;margin-bottom:16px}
.lr-bk-filter label{font-size:.68rem;font-weight:700;color:#92400e;text-transform:uppercase;letter-spacing:.4px;display:block;margin-bottom:3px}
.lr-bk-filter select,.lr-bk-filter button{padding:7px 10px;border:1px solid rgba(255,255,255,.15);border-radius:7px;font-size:.8rem;background:rgba(255,255,255,.1);color:white;cursor:pointer}
.lr-bk-filter .btn-go{background:rgba(14,116,144,.7);font-weight:700;display:inline-flex;align-items:center;gap:4px}
.lr-bk-filter .btn-excel{background:rgba(22,163,74,.7);font-weight:700;display:inline-flex;align-items:center;gap:4px}
.lr-bk-filter .btn-prnt{background:rgba(29,78,216,.7);font-weight:700;display:inline-flex;align-items:center;gap:4px}
@media print{.lr-bk-no-print{display:none!important}.sidebar,.topbar{display:none!important}.main{padding:0!important}}
</style>

<div style="margin-bottom:14px">
    <div style="font-size:1rem;font-weight:800;display:flex;align-items:center;gap:8px;margin-bottom:4px">
        <i class="fas fa-clipboard-list" style="color:#f59e0b"></i> Laporan Rekap Harian
    </div>
    <div style="font-size:.8rem;color:#64748b">
        <?= $lr_hari_nama ?>, <?= $lr_tgl ?> <?= $lr_nb[$lr_bln] ?> <?= $lr_thn ?>
        &nbsp;|&nbsp; Semua Kelas
    </div>
</div>

<!-- Filter -->
<div class="lr-bk-filter lr-bk-no-print">
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
        <input type="hidden" name="page" value="laporan_rekap">
        <div>
            <label><i class="fas fa-calendar-day"></i> Tanggal</label>
            <select name="lr_tgl">
                <?php for($d=1;$d<=31;$d++): ?>
                <option value="<?= $d ?>" <?= $lr_tgl==$d?'selected':'' ?>><?= str_pad($d,2,'0',STR_PAD_LEFT) ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div>
            <label><i class="fas fa-calendar-alt"></i> Bulan</label>
            <select name="lr_bln">
                <?php for($m=1;$m<=12;$m++): ?>
                <option value="<?= $m ?>" <?= $lr_bln==$m?'selected':'' ?>><?= $lr_nb[$m] ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div>
            <label><i class="fas fa-calendar"></i> Tahun</label>
            <select name="lr_thn">
                <?php for($y=date('Y')+1;$y>=date('Y')-3;$y--): ?>
                <option value="<?= $y ?>" <?= $lr_thn==$y?'selected':'' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div>
            <label><i class="fas fa-filter"></i> Status</label>
            <select name="lr_status">
                <option value="">Semua Status</option>
                <?php foreach(['Hadir','Terlambat','Alpa','Sakit','Izin','Bolos'] as $opt): ?>
                <option value="<?= $opt ?>" <?= $lr_filter_status===$opt?'selected':'' ?>><?= $opt ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="display:flex;gap:6px">
            <button type="submit" class="btn-go"><i class="fas fa-search"></i> Tampilkan</button>
            <button type="submit" name="lr_export_excel" value="1" class="btn-excel"><i class="fas fa-file-excel"></i> Excel</button>
            <button type="button" onclick="window.print()" class="btn-prnt"><i class="fas fa-print"></i> Print</button>
        </div>
    </form>
</div>

<!-- Stats -->
<div class="lr-bk-stats">
    <div class="lr-bk-stat"><div class="num" style="color:#38bdf8"><?= $lr_grand['siswa'] ?></div><div class="lbl">Total</div></div>
    <div class="lr-bk-stat"><div class="num" style="color:#4ade80"><?= $lr_grand['Hadir'] ?></div><div class="lbl">Hadir</div></div>
    <div class="lr-bk-stat"><div class="num" style="color:#fbbf24"><?= $lr_grand['Terlambat'] ?></div><div class="lbl">Terlambat</div></div>
    <div class="lr-bk-stat"><div class="num" style="color:#f87171"><?= $lr_grand['Alpa'] ?></div><div class="lbl">Alpa</div></div>
    <div class="lr-bk-stat"><div class="num" style="color:#60a5fa"><?= $lr_grand['Sakit'] ?></div><div class="lbl">Sakit</div></div>
    <div class="lr-bk-stat"><div class="num" style="color:#c084fc"><?= $lr_grand['Izin'] ?></div><div class="lbl">Izin</div></div>
    <div class="lr-bk-stat"><div class="num" style="color:#f472b6"><?= $lr_grand['Bolos'] ?></div><div class="lbl">Bolos</div></div>
    <div class="lr-bk-stat"><div class="num" style="color:#4ade80"><?= $lr_grand_pct ?>%</div><div class="lbl">% Hadir</div></div>
</div>

<!-- Tabel Per Kelas -->
<div style="font-size:.78rem;font-weight:700;margin-bottom:8px;display:flex;align-items:center;gap:5px">
    <i class="fas fa-table" style="color:#0e7490"></i> Rekapitulasi Per Kelas
</div>
<div class="tbl-wrap" style="margin-bottom:20px">
    <table id="lrKelasTable">
        <thead>
            <tr>
                <th>No</th><th style="text-align:left">Kelas</th><th>Siswa</th>
                <th style="background:rgba(74,222,128,.2)">Hadir</th>
                <th style="background:rgba(251,191,36,.2)">Terlambat</th>
                <th style="background:rgba(248,113,113,.2)">Alpa</th>
                <th style="background:rgba(96,165,250,.2)">Sakit</th>
                <th style="background:rgba(192,132,252,.2)">Izin</th>
                <th style="background:rgba(244,114,182,.2)">Bolos</th>
                <th>% Hadir</th>
            </tr>
        </thead>
        <tbody>
        <?php if(empty($lr_rekap_kelas)): ?>
        <tr><td colspan="10" style="text-align:center;padding:24px;color:#64748b">
            <i class="fas fa-inbox" style="font-size:1.3rem;display:block;margin-bottom:6px"></i>Tidak ada data
        </td></tr>
        <?php else: ?>
        <?php foreach($lr_rekap_kelas as $i => $r):
            $pc=$r['pct']>=90?'#4ade80':($r['pct']>=75?'#fb923c':'#f87171');
        ?>
        <tr>
            <td style="color:#64748b"><?= $i+1 ?></td>
            <td style="font-weight:700;text-align:left"><?= htmlspecialchars($r['kelas']) ?></td>
            <td><?= $r['siswa'] ?></td>
            <td style="color:#4ade80;font-weight:700"><?= $r['Hadir'] ?></td>
            <td style="color:#fbbf24;font-weight:700"><?= $r['Terlambat'] ?></td>
            <td style="color:#f87171;font-weight:700"><?= $r['Alpa'] ?></td>
            <td style="color:#60a5fa;font-weight:700"><?= $r['Sakit'] ?></td>
            <td style="color:#c084fc;font-weight:700"><?= $r['Izin'] ?></td>
            <td style="color:#f472b6;font-weight:700"><?= $r['Bolos'] ?></td>
            <td style="color:<?= $pc ?>;font-weight:800"><?= $r['pct'] ?>%</td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" style="text-align:right">TOTAL</td>
                <td><?= $lr_grand['siswa'] ?></td>
                <td style="color:#4ade80"><?= $lr_grand['Hadir'] ?></td>
                <td style="color:#fbbf24"><?= $lr_grand['Terlambat'] ?></td>
                <td style="color:#f87171"><?= $lr_grand['Alpa'] ?></td>
                <td style="color:#60a5fa"><?= $lr_grand['Sakit'] ?></td>
                <td style="color:#c084fc"><?= $lr_grand['Izin'] ?></td>
                <td style="color:#f472b6"><?= $lr_grand['Bolos'] ?></td>
                <td style="color:#4ade80"><?= $lr_grand_pct ?>%</td>
            </tr>
        </tfoot>
    </table>
</div>

<!-- Tabel Detail Siswa -->
<div style="font-size:.78rem;font-weight:700;margin-bottom:8px;display:flex;align-items:center;gap:5px">
    <i class="fas fa-layer-group" style="color:#0e7490"></i> Rekap Detail Siswa
    <?php if($lr_filter_status): ?>
    <span style="background:rgba(245,158,11,.15);color:#fbbf24;padding:2px 8px;border-radius:5px;font-size:.68rem;margin-left:6px">Status: <?= $lr_filter_status ?></span>
    <?php endif; ?>
</div>
<div class="tbl-wrap">
    <table id="lrDetailTable">
        <thead>
            <tr>
                <th>No</th><th style="text-align:left">NIS</th>
                <th style="text-align:left">Nama Siswa</th>
                <th>Kelas</th><th>Status</th>
                <th style="text-align:left">Keterangan</th>
            </tr>
        </thead>
        <tbody>
        <?php if(empty($lr_rekap_semua)): ?>
        <tr><td colspan="6" style="text-align:center;padding:24px;color:#64748b">
            <i class="fas fa-inbox" style="font-size:1.3rem;display:block;margin-bottom:6px"></i>Tidak ada data
        </td></tr>
        <?php else:
        $lrbm=['Hadir'=>['#dcfce7','#15803d'],'Terlambat'=>['#fef3c7','#b45309'],'Alpa'=>['#fee2e2','#dc2626'],
               'Sakit'=>['#dbeafe','#1d4ed8'],'Izin'=>['#ede9fe','#6d28d9'],'Bolos'=>['#ffedd5','#9a3412']];
        $lr_prev='';
        foreach($lr_rekap_semua as $ri => $rrow):
            if($rrow['status']!==$lr_prev && $lr_prev!==''): ?>
        <tr><td colspan="6" style="padding:0;height:3px;background:rgba(255,255,255,.04)"></td></tr>
        <?php endif; $lr_prev=$rrow['status'];
        $bc=$lrbm[$rrow['status']]??['rgba(255,255,255,.03)','#94a3b8'];
        ?>
        <tr>
            <td style="color:#64748b"><?= $ri+1 ?></td>
            <td style="color:#64748b;font-size:.75rem;text-align:left"><?= htmlspecialchars($rrow['nis']) ?></td>
            <td style="font-weight:600;text-align:left"><?= htmlspecialchars($rrow['nama']) ?></td>
            <td><span style="background:rgba(255,255,255,.08);padding:2px 8px;border-radius:5px;font-size:.75rem;font-weight:600"><?= htmlspecialchars($rrow['kelas']) ?></span></td>
            <td><span style="background:<?= $bc[0] ?>;color:<?= $bc[1] ?>;padding:3px 10px;border-radius:6px;font-weight:700;font-size:.78rem"><?= $rrow['status'] ?></span></td>
            <td style="color:#64748b;font-size:.78rem;text-align:left"><?= htmlspecialchars($rrow['keterangan']??'-') ?></td>
        </tr>
        <?php endforeach; ?>
        <tr style="background:#1e293b;font-weight:800">
            <td colspan="2" style="text-align:right">TOTAL</td>
            <td colspan="4"><?= count($lr_rekap_semua) ?> siswa<?= $lr_filter_status?" — <strong>{$lr_filter_status}</strong>":" — Semua Status" ?></td>
        </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- TTD -->
<div style="margin-top:32px;display:flex;justify-content:flex-end;padding-right:20px">
    <div style="text-align:center;min-width:200px;font-size:.82rem">
        <div><?= htmlspecialchars($pengaturan['nama_sekolah'] ?? '') ?>, <?= $lr_tgl.' '.$lr_nb[$lr_bln].' '.$lr_thn ?></div>
        <div style="margin-top:3px">Guru BK,</div>
        <div style="margin-top:56px;font-weight:700;text-decoration:underline"><?= htmlspecialchars($bk_nama) ?></div>
        <div style="font-size:.75rem;color:#64748b">NIP. <?= htmlspecialchars($bk_row['nip'] ?? '-') ?></div>
    </div>
</div>

<?php endif; // end laporan_rekap BK ?>
</div><!-- /main -->

<script>
// Jam realtime
function updateJam(){
    var n=new Date();
    var h=String(n.getHours()).padStart(2,'0');
    var m=String(n.getMinutes()).padStart(2,'0');
    var s=String(n.getSeconds()).padStart(2,'0');
    var el=document.getElementById('jam');
    if(el) el.textContent=h+':'+m+':'+s;
}
updateJam(); setInterval(updateJam,1000);

// Hamburger
function toggleSidebar(){
    var sb=document.getElementById('sidebar');
    var ov=document.getElementById('sidebarOverlay');
    var btn=document.getElementById('hamburgerBtn');
    var open=sb.classList.toggle('open');
    ov.classList.toggle('show',open);
    btn.classList.toggle('open',open);
    document.body.style.overflow=open?'hidden':'';
}
function closeSidebar(){
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('show');
    document.getElementById('hamburgerBtn').classList.remove('open');
    document.body.style.overflow='';
}
document.querySelectorAll('.nav-item').forEach(function(el){
    el.addEventListener('click',function(){ if(window.innerWidth<=768) closeSidebar(); });
});
window.addEventListener('resize',function(){ if(window.innerWidth>768) closeSidebar(); });

// Search filter tabel
function srFilter(tblId, q){
    q=q.toLowerCase();
    var rows=document.getElementById(tblId).querySelectorAll('tbody tr');
    rows.forEach(function(r){
        r.style.display=r.textContent.toLowerCase().includes(q)?'':'none';
    });
}
</script>
</body>
</html>
