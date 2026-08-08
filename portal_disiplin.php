<?php
require_once 'includes/config.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['disiplin_login'])) {
    header('Location: portal_disiplin_login.php'); exit;
}
if (isset($_GET['logout'])) {
    unset($_SESSION['disiplin_login']);
    header('Location: portal_disiplin_login.php'); exit;
}

$pengaturan = get_pengaturan();
$today      = date('Y-m-d');

// ══════════════════════════════════════════════════════════════════════
// AUTO-MIGRASI TABEL
// ══════════════════════════════════════════════════════════════════════
$conn->query("CREATE TABLE IF NOT EXISTS `pelanggaran_topik` (
    id INT AUTO_INCREMENT PRIMARY KEY, nama VARCHAR(150) NOT NULL,
    urutan INT DEFAULT 0, aktif TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS `pelanggaran_sub` (
    id INT AUTO_INCREMENT PRIMARY KEY, topik_id INT NOT NULL DEFAULT 0,
    nama VARCHAR(150) NOT NULL, kode VARCHAR(10) DEFAULT '',
    urutan INT DEFAULT 0, aktif TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS `pelanggaran_jenis` (
    id INT AUTO_INCREMENT PRIMARY KEY, topik_id INT DEFAULT 0,
    sub_id INT DEFAULT 0, nama VARCHAR(200) NOT NULL,
    kode VARCHAR(10) DEFAULT '', keterangan VARCHAR(255) DEFAULT '',
    poin INT DEFAULT 0, aktif TINYINT(1) DEFAULT 1,
    urutan INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

foreach ([
    ['pelanggaran_jenis','topik_id','INT DEFAULT 0 AFTER id'],
    ['pelanggaran_jenis','sub_id','INT DEFAULT 0 AFTER topik_id'],
    ['pelanggaran_jenis','poin','INT DEFAULT 0'],
    ['pelanggaran_jenis','kode',"VARCHAR(10) DEFAULT ''"],
] as [$tbl,$col,$def]) {
    $r = $conn->query("SHOW COLUMNS FROM `$tbl` LIKE '$col'");
    if ($r && $r->num_rows === 0) $conn->query("ALTER TABLE `$tbl` ADD COLUMN `$col` $def");
}

$conn->query("CREATE TABLE IF NOT EXISTS `pelanggaran` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    siswa_id INT DEFAULT 0, nis VARCHAR(30) DEFAULT '',
    nama VARCHAR(100) DEFAULT '', kelas VARCHAR(30) DEFAULT '',
    tanggal DATE NOT NULL, topik_id INT DEFAULT 0,
    sub_id INT DEFAULT 0, jenis_id INT DEFAULT 0,
    jenis_nama VARCHAR(200) DEFAULT '', poin INT DEFAULT 0,
    keterangan VARCHAR(255) DEFAULT '',
    input_oleh VARCHAR(100) DEFAULT 'Disiplin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

foreach ([
    ['pelanggaran','topik_id','INT DEFAULT 0'],
    ['pelanggaran','sub_id','INT DEFAULT 0'],
    ['pelanggaran','poin','INT DEFAULT 0'],
] as [$tbl,$col,$def]) {
    $r = $conn->query("SHOW COLUMNS FROM `$tbl` LIKE '$col'");
    if ($r && $r->num_rows === 0) $conn->query("ALTER TABLE `$tbl` ADD COLUMN `$col` $def");
}

// ══════════════════════════════════════════════════════════════════════
// AJAX: sub cascade
// ══════════════════════════════════════════════════════════════════════
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_sub') {
    header('Content-Type: application/json');
    $tid = (int)($_GET['topik_id'] ?? 0);
    $rows = [];
    if ($tid) {
        $r = $conn->query("SELECT id, nama, kode FROM pelanggaran_sub WHERE topik_id=$tid AND aktif=1 ORDER BY urutan, id");
        while ($row = $r->fetch_assoc()) $rows[] = $row;
    }
    echo json_encode($rows); exit;
}

if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_jenis') {
    header('Content-Type: application/json');
    $sid2 = (int)($_GET['sub_id'] ?? 0);
    $tid  = (int)($_GET['topik_id'] ?? 0);
    $rows = [];
    $cond = $sid2 ? "sub_id=$sid2" : ($tid ? "topik_id=$tid" : "1=1");
    $r = $conn->query("SELECT id, nama, kode, poin FROM pelanggaran_jenis WHERE $cond AND aktif=1 ORDER BY urutan, id");
    while ($row = $r->fetch_assoc()) $rows[] = $row;
    echo json_encode($rows); exit;
}

// AJAX: daftar siswa per kelas
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_siswa_kelas') {
    header('Content-Type: application/json');
    $kls = $conn->real_escape_string(trim($_GET['kelas'] ?? ''));
    $rows = [];
    if ($kls) {
        $r = $conn->query("SELECT id, nis, nama, kelas FROM siswa WHERE kelas='$kls' ORDER BY nama");
        while ($row = $r->fetch_assoc()) $rows[] = $row;
    }
    echo json_encode($rows); exit;
}

// ══════════════════════════════════════════════════════════════════════
// AJAX: Simpan satu pelanggaran (dipanggil JS per baris)
// ══════════════════════════════════════════════════════════════════════
if (isset($_GET['ajax']) && $_GET['ajax'] === 'simpan_satu') {
    header('Content-Type: application/json');
    $sid    = (int)($_POST['siswa_id'] ?? 0);
    $jid    = (int)($_POST['jenis_id'] ?? 0);
    $tid    = (int)($_POST['topik_id'] ?? 0);
    $subid  = (int)($_POST['sub_id']   ?? 0);
    $tgl    = $conn->real_escape_string($_POST['tanggal'] ?? $today);
    if (!$sid || !$jid) { echo json_encode(['ok'=>false,'msg'=>'Pilih jenis pelanggaran terlebih dahulu']); exit; }
    $sw = $conn->query("SELECT nis,nama,kelas FROM siswa WHERE id=$sid")->fetch_assoc();
    $jn = $conn->query("SELECT nama,poin FROM pelanggaran_jenis WHERE id=$jid")->fetch_assoc();
    if (!$sw || !$jn) { echo json_encode(['ok'=>false,'msg'=>'Data tidak ditemukan']); exit; }
    $nis   = $conn->real_escape_string($sw['nis']);
    $nama  = $conn->real_escape_string($sw['nama']);
    $kelas = $conn->real_escape_string($sw['kelas']);
    $jnama = $conn->real_escape_string($jn['nama']);
    $poin  = (int)$jn['poin'];
    list($ptaX, $psemX) = periode_values($conn);
    $conn->query("INSERT INTO pelanggaran (siswa_id,nis,nama,kelas,tanggal,topik_id,sub_id,jenis_id,jenis_nama,poin,keterangan,input_oleh,tahun_ajaran,semester)
        VALUES ($sid,'$nis','$nama','$kelas','$tgl',$tid,$subid,$jid,'$jnama',$poin,'','Disiplin',$ptaX,$psemX)");
    if ($conn->error) { echo json_encode(['ok'=>false,'msg'=>'Gagal: '.$conn->error]); exit; }
    echo json_encode(['ok'=>true,'msg'=>'Tersimpan!','poin'=>$poin,'nama'=>$sw['nama'],'jenis'=>$jn['nama']]); exit;
}

// Hapus pelanggaran
if (isset($_GET['hapus']) && is_numeric($_GET['hapus'])) {
    $conn->query("DELETE FROM pelanggaran WHERE id=".(int)$_GET['hapus']);
    header("Location: portal_disiplin.php?tgl=".($_GET['tgl']??$today)."&kelas=".urlencode($_GET['kelas']??'')); exit;
}

// ══════════════════════════════════════════════════════════════════════
// AJAX: Menu Edit — daftar pelanggaran yang sudah tercatat hari ini per siswa
// (dipakai di Daftar Siswa supaya bisa langsung menghapus tanpa reload halaman)
// ══════════════════════════════════════════════════════════════════════
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_existing') {
    header('Content-Type: application/json');
    $tgl3  = $conn->real_escape_string($_GET['tgl'] ?? $today);
    $kls3  = $conn->real_escape_string($_GET['kelas'] ?? '');
    $cond3 = $kls3 ? "AND kelas='$kls3'" : '';
    $res3  = $conn->query("SELECT id, siswa_id, jenis_nama, poin, DATE_FORMAT(created_at,'%H:%i') waktu
        FROM pelanggaran WHERE tanggal='$tgl3' $cond3" . periode_where($conn) . " ORDER BY created_at DESC");
    $rows3 = [];
    if ($res3) while ($r = $res3->fetch_assoc()) $rows3[] = $r;
    echo json_encode($rows3); exit;
}

// ── AJAX: Hapus satu pelanggaran (tanpa reload halaman, dipakai oleh menu Edit) ──
if (isset($_GET['ajax']) && $_GET['ajax'] === 'hapus_satu') {
    header('Content-Type: application/json');
    $hid = (int)($_POST['id'] ?? 0);
    if (!$hid) { echo json_encode(['ok'=>false,'msg'=>'ID tidak valid']); exit; }
    $conn->query("DELETE FROM pelanggaran WHERE id=$hid");
    if ($conn->error) { echo json_encode(['ok'=>false,'msg'=>'Gagal: '.$conn->error]); exit; }
    echo json_encode(['ok'=>true]); exit;
}

// ══════════════════════════════════════════════════════════════════════
// DATA
// ══════════════════════════════════════════════════════════════════════
$tgl_filter   = isset($_GET['tgl'])   ? sanitize($_GET['tgl'])   : $today;
$kelas_filter = isset($_GET['kelas']) ? sanitize($_GET['kelas']) : '';

$kelas_list = [];
$kr = $conn->query("SELECT DISTINCT kelas FROM siswa ORDER BY kelas");
while ($r = $kr->fetch_assoc()) $kelas_list[] = $r['kelas'];
if (!$kelas_filter && !empty($kelas_list)) $kelas_filter = $kelas_list[0];

$topik_list = [];
$tr2 = $conn->query("SELECT * FROM pelanggaran_topik WHERE aktif=1 ORDER BY urutan,id");
while ($r = $tr2->fetch_assoc()) $topik_list[] = $r;

$all_sub_raw = $conn->query("SELECT * FROM pelanggaran_sub WHERE aktif=1 ORDER BY topik_id,urutan,id")->fetch_all(MYSQLI_ASSOC);
$sub_by_topik = [];
foreach ($all_sub_raw as $sb) $sub_by_topik[$sb['topik_id']][] = $sb;

$all_jenis_raw = $conn->query("SELECT * FROM pelanggaran_jenis WHERE aktif=1 ORDER BY topik_id,sub_id,urutan,id")->fetch_all(MYSQLI_ASSOC);
$jenis_by_sub   = [];
$jenis_by_topik = [];
foreach ($all_jenis_raw as $jn) {
    $jenis_by_sub[$jn['sub_id']][]   = $jn;
    $jenis_by_topik[$jn['topik_id']][] = $jn;
}

$stat_hari  = (int)$conn->query("SELECT COUNT(*) c FROM pelanggaran WHERE tanggal='$today'" . periode_where($conn))->fetch_assoc()['c'];
$stat_poin  = (int)($conn->query("SELECT COALESCE(SUM(poin),0) s FROM pelanggaran WHERE tanggal='$today'" . periode_where($conn))->fetch_assoc()['s'] ?? 0);

$kelas_filter_esc = $conn->real_escape_string($kelas_filter);
$pel_res = $conn->query("SELECT p.*, pt.nama topik_nama, ps.nama sub_nama
    FROM pelanggaran p
    LEFT JOIN pelanggaran_topik pt ON pt.id=p.topik_id
    LEFT JOIN pelanggaran_sub   ps ON ps.id=p.sub_id
    WHERE p.tanggal='$tgl_filter'" . ($kelas_filter ? " AND p.kelas='$kelas_filter_esc'" : "") . periode_where($conn, 'p.') . "
    ORDER BY p.created_at DESC");

$siswa_list = [];
if ($kelas_filter) {
    $slr = $conn->query("SELECT id,nis,nama,kelas FROM siswa WHERE kelas='$kelas_filter_esc' ORDER BY nama");
    while ($r = $slr->fetch_assoc()) $siswa_list[] = $r;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Portal Disiplin — <?= htmlspecialchars($pengaturan['nama_sekolah'] ?? '') ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',system-ui,sans-serif;background:#0f172a;color:#e2e8f0;min-height:100vh}

/* HEADER */
.portal-header{background:linear-gradient(90deg,#1e3a8a,#0f172a);padding:10px 20px;display:flex;align-items:center;gap:14px;border-bottom:1px solid rgba(255,255,255,.1);position:sticky;top:0;z-index:100}
.portal-header .logo{width:40px;height:40px;border-radius:10px;object-fit:contain;background:white;padding:4px}
.portal-header .logo-icon{width:40px;height:40px;background:linear-gradient(135deg,#f59e0b,#d97706);border-radius:10px;display:flex;align-items:center;justify-content:center;color:white;font-size:1.1rem;flex-shrink:0}
.portal-header .school{flex:1}
.portal-header .school h1{font-size:.95rem;font-weight:800;color:white}
.portal-header .school small{font-size:.72rem;color:#94a3b8}
.htime{background:rgba(245,158,11,.15);border:1px solid rgba(245,158,11,.3);color:#fbbf24;padding:5px 12px;border-radius:20px;font-size:.8rem;font-weight:700;display:flex;align-items:center;gap:6px}
.btn-logout{color:#94a3b8;font-size:.78rem;text-decoration:none;display:flex;align-items:center;gap:5px;padding:6px 12px;border-radius:8px;transition:.15s}
.btn-logout:hover{background:rgba(255,255,255,.08);color:#f87171}

/* LAYOUT */
.page-wrap{padding:18px 20px;max-width:1400px;margin:0 auto}
.top-stats{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:18px}
.stat-card{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:12px;padding:14px 18px;text-align:center}
.stat-card .val{font-size:2rem;font-weight:900;color:#f59e0b}
.stat-card .lbl{font-size:.75rem;color:#94a3b8;margin-top:4px;font-weight:600}

/* KELAS PILLS */
.kelas-pills{display:flex;flex-wrap:wrap;gap:7px;margin-bottom:16px}
.kelas-pill{padding:6px 16px;border-radius:20px;font-size:.78rem;font-weight:700;cursor:pointer;text-decoration:none;background:rgba(255,255,255,.07);color:#94a3b8;border:1px solid rgba(255,255,255,.12);transition:.15s;white-space:nowrap}
.kelas-pill:hover{background:rgba(255,255,255,.13);color:#e2e8f0}
.kelas-pill.active{background:#f59e0b;color:#0f172a;border-color:#f59e0b}

/* CARD */
.card{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.1);border-radius:14px;overflow:hidden;margin-bottom:18px}
.card-header{padding:12px 18px;border-bottom:1px solid rgba(255,255,255,.1);font-weight:700;font-size:.88rem;display:flex;align-items:center;gap:8px;color:#e2e8f0;flex-wrap:wrap}

/* FILTER */
.filter-row{display:flex;flex-wrap:wrap;align-items:flex-end;gap:12px;padding:12px 16px;border-bottom:1px solid rgba(255,255,255,.07)}
.filter-row label{font-size:.72rem;font-weight:700;color:#94a3b8;letter-spacing:.04em;text-transform:uppercase;display:block;margin-bottom:5px}
.filter-row select,.filter-row input[type=date]{background:#1e293b;border:1px solid rgba(255,255,255,.15);border-radius:8px;color:#e2e8f0;padding:7px 11px;font-size:.83rem;outline:none;font-family:inherit}
.filter-row select:focus,.filter-row input:focus{border-color:#f59e0b}

/* SELECT ALL BAR */
.select-all-bar{display:flex;align-items:center;gap:10px;padding:9px 14px;background:rgba(245,158,11,.07);border-bottom:1px solid rgba(255,255,255,.07)}
.select-all-bar label{font-size:.82rem;font-weight:700;color:#fbbf24;cursor:pointer;display:flex;align-items:center;gap:8px}
.counter-badge{background:#f59e0b;color:#0f172a;padding:2px 8px;border-radius:20px;font-size:.75rem;font-weight:800}

/* TABEL SISWA */
.tbl-siswa{width:100%;border-collapse:collapse;font-size:.82rem}
.tbl-siswa thead th{background:#0f172a;color:#94a3b8;padding:9px 10px;font-size:.7rem;text-align:left;font-weight:700;letter-spacing:.05em;text-transform:uppercase;white-space:nowrap;border-bottom:1px solid rgba(255,255,255,.08)}
.tbl-siswa tbody tr{border-bottom:1px solid rgba(255,255,255,.05);transition:.12s}
.tbl-siswa tbody tr:hover{background:rgba(255,255,255,.03)}
.tbl-siswa tbody tr.selected{background:rgba(245,158,11,.07)}
.tbl-siswa td{padding:9px 10px;vertical-align:middle}

/* CHECKBOX */
.chk-siswa{width:20px;height:20px;appearance:none;-webkit-appearance:none;border:2px solid rgba(255,255,255,.25);border-radius:5px;background:transparent;cursor:pointer;transition:.15s;position:relative;display:block;margin:auto}
.chk-siswa:checked{background:#f59e0b;border-color:#f59e0b}
.chk-siswa:checked::after{content:'✓';position:absolute;top:50%;left:50%;transform:translate(-50%,-52%);color:#0f172a;font-weight:900;font-size:.75rem;line-height:1}
.chk-siswa:hover{border-color:#f59e0b}
.chk-master{width:18px;height:18px;appearance:none;-webkit-appearance:none;border:2px solid rgba(255,255,255,.3);border-radius:4px;background:transparent;cursor:pointer;position:relative;vertical-align:middle}
.chk-master:checked{background:#f59e0b;border-color:#f59e0b}
.chk-master:checked::after{content:'✓';position:absolute;top:50%;left:50%;transform:translate(-50%,-52%);color:#0f172a;font-weight:900;font-size:.7rem;line-height:1}

/* INLINE FORM FIELDS */
.inline-fields{display:none}
.inline-fields.show{display:block}
.sel-inline{background:#1e293b;border:1px solid rgba(255,255,255,.15);border-radius:7px;color:#e2e8f0;padding:6px 10px;font-size:.78rem;width:100%;outline:none;font-family:inherit}
.sel-inline:focus{border-color:#f59e0b}

/* BADGE */
.badge-poin{background:#fef3c7;color:#92400e;padding:2px 9px;border-radius:20px;font-size:.72rem;font-weight:800;white-space:nowrap}
.badge-ok{background:#dcfce7;color:#15803d;padding:2px 9px;border-radius:20px;font-size:.72rem;font-weight:800;white-space:nowrap;display:inline-flex;align-items:center;gap:4px}

/* TOMBOL SIMPAN per baris */
.btn-simpan-row{padding:6px 14px;background:linear-gradient(135deg,#ef4444,#b91c1c);color:white;border:none;border-radius:7px;font-weight:800;font-size:.78rem;cursor:pointer;transition:.15s;white-space:nowrap;display:inline-flex;align-items:center;gap:5px}
.btn-simpan-row:hover{opacity:.85;transform:translateY(-1px)}
.btn-simpan-row:disabled{opacity:.4;cursor:not-allowed;transform:none}
.btn-simpan-row.saved{background:linear-gradient(135deg,#16a34a,#15803d);cursor:default}

/* HISTORY TABLE */
.tbl-hist{width:100%;border-collapse:collapse;font-size:.78rem}
.tbl-hist thead th{background:#0f172a;color:#94a3b8;padding:8px 8px;text-align:center;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;border-bottom:1px solid rgba(255,255,255,.08);white-space:nowrap}
.tbl-hist tbody td{padding:7px 8px;border-bottom:1px solid rgba(255,255,255,.05);vertical-align:middle;text-align:center}
.tbl-hist tbody tr:hover td{background:rgba(255,255,255,.03)}
.badge-top{background:#ede9fe;color:#5b21b6;padding:2px 7px;border-radius:12px;font-size:.7rem;font-weight:700}
.badge-sub-h{background:#ecfdf5;color:#065f46;padding:2px 7px;border-radius:12px;font-size:.7rem;font-weight:700}
.btn-del{width:26px;height:26px;border-radius:6px;border:none;background:#fee2e2;color:#dc2626;cursor:pointer;font-size:.72rem;transition:.15s;display:inline-flex;align-items:center;justify-content:center}
.btn-del:hover{background:#fca5a5}

/* ALERT */
.alert{padding:12px 16px;border-radius:10px;margin-bottom:14px;font-size:.85rem;font-weight:600;display:flex;align-items:center;gap:8px}
.alert-success{background:rgba(22,163,74,.15);border:1px solid rgba(22,163,74,.3);color:#4ade80}

/* EMPTY */
.empty-state{text-align:center;padding:50px 20px;color:#475569}
.empty-state i{font-size:2rem;display:block;margin-bottom:12px;color:#22c55e}

/* TOAST */
#toast{position:fixed;bottom:24px;right:24px;background:#1e293b;border:1px solid rgba(255,255,255,.15);color:white;padding:12px 18px;border-radius:10px;font-size:.85rem;font-weight:600;display:none;align-items:center;gap:8px;z-index:999;box-shadow:0 8px 24px rgba(0,0,0,.4);max-width:320px}
#toast.show{display:flex}
#toast.ok{border-color:rgba(74,222,128,.4);background:#0f2d1a}
#toast.err{border-color:rgba(248,113,113,.4);background:#2d0f0f}

@media(max-width:768px){
    .page-wrap{padding:12px}
    .top-stats{grid-template-columns:1fr 1fr}
}
</style>
</head>
<body>

<!-- HEADER -->
<div class="portal-header">
    <?php if (!empty($pengaturan['logo']) && file_exists(__DIR__.'/uploads/logo/'.$pengaturan['logo'])): ?>
    <img src="<?= BASE_URL ?>uploads/logo/<?= $pengaturan['logo'] ?>" class="logo" alt="Logo">
    <?php else: ?>
    <div class="logo-icon"><i class="fas fa-shield-alt"></i></div>
    <?php endif; ?>
    <div class="school">
        <h1><i class="fas fa-shield-alt" style="color:#f59e0b;margin-right:6px"></i> Portal Disiplin</h1>
        <small><?= htmlspecialchars($pengaturan['nama_sekolah'] ?? '') ?></small>
    </div>
    <div class="htime"><i class="fas fa-clock"></i><span id="jamNow">--:--</span></div>
    <a href="?logout=1" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Keluar</a>
</div>

<div class="page-wrap">

<!-- STATS -->
<div class="top-stats">
    <div class="stat-card">
        <div class="val" id="statHari"><?= $stat_hari ?></div>
        <div class="lbl"><i class="fas fa-exclamation-triangle" style="color:#f59e0b"></i> Pelanggaran Hari Ini</div>
    </div>
    <div class="stat-card">
        <div class="val" id="statPoin"><?= $stat_poin ?></div>
        <div class="lbl"><i class="fas fa-star" style="color:#f59e0b"></i> Total Poin Hari Ini</div>
    </div>
</div>

<!-- KELAS PILLS -->
<div class="kelas-pills">
    <?php foreach ($kelas_list as $k): ?>
    <a href="?kelas=<?= urlencode($k) ?>&tgl=<?= $tgl_filter ?>"
       class="kelas-pill <?= $k===$kelas_filter?'active':'' ?>"><?= htmlspecialchars($k) ?></a>
    <?php endforeach; ?>
</div>

<!-- DAFTAR SISWA -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-users" style="color:#f59e0b"></i>
        <span id="headerKelas">Daftar Siswa — <?= htmlspecialchars($kelas_filter ?: 'Pilih Kelas') ?></span>
        <span style="margin-left:auto;font-size:.76rem;color:#64748b;font-weight:400">
            <i class="fas fa-info-circle"></i> Centang siswa lalu isi jenis pelanggaran
        </span>
    </div>

    <!-- FILTER TANGGAL & KELAS -->
    <div class="filter-row">
        <div>
            <label>Tanggal</label>
            <input type="date" id="filterTgl" value="<?= $tgl_filter ?>" max="<?= $today ?>" onchange="refreshHistori();updateStats();muatExistingHariIni();">
        </div>
        <div>
            <label>Kelas</label>
            <select id="filterKelas" onchange="muatSiswa(this.value)">
                <?php foreach ($kelas_list as $k): ?>
                <option value="<?= htmlspecialchars($k) ?>" <?= $k===$kelas_filter?'selected':'' ?>>
                    <?= htmlspecialchars($k) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- SELECT ALL BAR -->
    <div class="select-all-bar">
        <label>
            <input type="checkbox" class="chk-master" id="chkAll" onchange="toggleAll(this.checked)">
            Pilih Semua Siswa
        </label>
        <span>Dipilih: <span class="counter-badge" id="ctrDipilih">0</span></span>
    </div>

    <!-- TABEL -->
    <div style="overflow-x:auto">
        <table class="tbl-siswa">
            <thead>
                <tr>
                    <th style="width:42px;text-align:center">✓</th>
                    <th>Nama Siswa</th>
                    <th style="width:110px">NIS</th>
                    <th style="min-width:150px">Topik</th>
                    <th style="min-width:150px">Sub</th>
                    <th style="min-width:180px">Jenis Pelanggaran</th>
                    <th style="width:80px;text-align:center">Poin</th>
                    <th style="width:90px;text-align:center">Aksi</th>
                    <th style="width:110px;text-align:center">Edit</th>
                </tr>
            </thead>
            <tbody id="tbodySiswa">
<?php if (empty($siswa_list)): ?>
                <tr><td colspan="9" style="text-align:center;padding:40px;color:#475569">
                    <i class="fas fa-users" style="font-size:1.5rem;display:block;margin-bottom:8px"></i>
                    Pilih kelas untuk menampilkan daftar siswa
                </td></tr>
<?php else:
    foreach ($siswa_list as $sw):
        // Build topik options string
        $topikOpts = '<option value="">— Pilih Topik —</option>';
        foreach ($topik_list as $tp) {
            $topikOpts .= '<option value="'.$tp['id'].'">'.htmlspecialchars($tp['nama']).'</option>';
        }
?>
                <tr id="row_<?= $sw['id'] ?>">
                    <td style="text-align:center">
                        <input type="checkbox" class="chk-siswa" data-id="<?= $sw['id'] ?>"
                               onchange="onCheck(this,<?= $sw['id'] ?>)">
                    </td>
                    <td style="font-weight:700;color:#e2e8f0"><?= htmlspecialchars($sw['nama']) ?></td>
                    <td style="font-family:monospace;color:#94a3b8"><?= htmlspecialchars($sw['nis']) ?></td>

                    <!-- Topik -->
                    <td>
                        <div class="inline-fields" id="if_topik_<?= $sw['id'] ?>">
                            <select class="sel-inline sel-topik" data-sid="<?= $sw['id'] ?>"
                                    onchange="onTopikChange(this,<?= $sw['id'] ?>)">
                                <?= $topikOpts ?>
                            </select>
                        </div>
                    </td>

                    <!-- Sub -->
                    <td>
                        <div class="inline-fields" id="if_sub_<?= $sw['id'] ?>">
                            <select class="sel-inline sel-sub" id="sub_<?= $sw['id'] ?>"
                                    data-sid="<?= $sw['id'] ?>"
                                    onchange="onSubChange(this,<?= $sw['id'] ?>)">
                                <option value="">— Pilih Sub —</option>
                            </select>
                        </div>
                    </td>

                    <!-- Jenis -->
                    <td>
                        <div class="inline-fields" id="if_jenis_<?= $sw['id'] ?>">
                            <select class="sel-inline sel-jenis" id="jenis_<?= $sw['id'] ?>"
                                    data-sid="<?= $sw['id'] ?>"
                                    onchange="onJenisChange(this,<?= $sw['id'] ?>)">
                                <option value="">— Pilih Jenis —</option>
                            </select>
                        </div>
                    </td>

                    <!-- Poin -->
                    <td style="text-align:center">
                        <div class="inline-fields" id="if_poin_<?= $sw['id'] ?>">
                            <span id="poin_badge_<?= $sw['id'] ?>"></span>
                        </div>
                    </td>

                    <!-- Tombol Simpan -->
                    <td style="text-align:center">
                        <div class="inline-fields" id="if_btn_<?= $sw['id'] ?>">
                            <button type="button" class="btn-simpan-row"
                                    id="btnSimpan_<?= $sw['id'] ?>"
                                    onclick="simpanSatu(<?= $sw['id'] ?>)" disabled>
                                <i class="fas fa-save"></i> Simpan
                            </button>
                        </div>
                    </td>

                    <!-- Edit: pelanggaran yang sudah tercatat hari ini untuk siswa ini -->
                    <td style="text-align:center" id="existing_<?= $sw['id'] ?>"></td>
                </tr>
<?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- TABEL PELANGGARAN HARI INI -->
<div class="card" id="tabelHistori">
    <div class="card-header">
        <i class="fas fa-list-alt" style="color:#f59e0b"></i>
        Pelanggaran — <?= date('d/m/Y', strtotime($tgl_filter)) ?> | Kelas <?= htmlspecialchars($kelas_filter ?: 'Semua') ?>
        <span style="margin-left:auto;background:rgba(255,255,255,.1);padding:3px 10px;border-radius:20px;font-size:.75rem;font-weight:700" id="jmlData">
            <?= $pel_res ? $pel_res->num_rows : 0 ?> data
        </span>
    </div>
    <div id="isiHistori">
    <?php if (!$pel_res || $pel_res->num_rows === 0): ?>
    <div class="empty-state">
        <i class="fas fa-check-circle"></i>
        Tidak ada pelanggaran pada tanggal ini
    </div>
    <?php else: ?>
    <div style="overflow-x:auto">
        <table class="tbl-hist">
            <thead>
                <tr>
                    <th>#</th>
                    <th style="text-align:left">WAKTU</th>
                    <th style="text-align:left">SISWA</th>
                    <th>KELAS</th>
                    <th style="text-align:left">TOPIK</th>
                    <th style="text-align:left">SUB</th>
                    <th style="text-align:left">JENIS PELANGGARAN</th>
                    <th>POIN</th>
                    <th>AKSI</th>
                </tr>
            </thead>
            <tbody>
            <?php $no=0; while ($p=$pel_res->fetch_assoc()): $no++; ?>
            <tr>
                <td><?= $no ?></td>
                <td style="text-align:left;font-size:.74rem;color:#94a3b8"><?= date('H:i', strtotime($p['created_at'])) ?></td>
                <td style="text-align:left;font-weight:700;color:#e2e8f0"><?= htmlspecialchars($p['nama']) ?></td>
                <td><?= htmlspecialchars($p['kelas']) ?></td>
                <td style="text-align:left">
                    <?php if ($p['topik_nama']): ?><span class="badge-top"><?= htmlspecialchars($p['topik_nama']) ?></span>
                    <?php else: ?><span style="color:#475569">—</span><?php endif; ?>
                </td>
                <td style="text-align:left">
                    <?php if ($p['sub_nama']): ?><span class="badge-sub-h"><?= htmlspecialchars($p['sub_nama']) ?></span>
                    <?php else: ?><span style="color:#475569">—</span><?php endif; ?>
                </td>
                <td style="text-align:left;font-weight:600;color:#e2e8f0"><?= htmlspecialchars($p['jenis_nama']) ?></td>
                <td><span class="badge-poin"><?= $p['poin'] ?> Poin</span></td>
                <td>
                    <button type="button" class="btn-del" onclick="hapusPelanggaran(<?= $p['id'] ?>)">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    </div>
</div>

</div><!-- /page-wrap -->

<!-- TOAST -->
<div id="toast"></div>

<script>
var subByTopik  = <?= json_encode($sub_by_topik) ?>;
var jenisBySub  = <?= json_encode($jenis_by_sub) ?>;
var jenisByTopik= <?= json_encode($jenis_by_topik) ?>;

// Topik options HTML (untuk muatSiswa dynamic)
var topikOptsHtml = '<option value="">— Pilih Topik —</option>';
<?php foreach ($topik_list as $tp): ?>
topikOptsHtml += '<option value="<?= $tp['id'] ?>"><?= addslashes(htmlspecialchars($tp['nama'])) ?></option>';
<?php endforeach; ?>

// ── Jam ──
(function tick(){
    var n=new Date(),h=n.getHours(),m=n.getMinutes(),s=n.getSeconds();
    document.getElementById('jamNow').textContent=
        (h<10?'0':'')+h+':'+(m<10?'0':'')+m+':'+(s<10?'0':'')+s;
    setTimeout(tick,1000);
})();
muatExistingHariIni();

// ── Hitung dipilih ──
function updateCounter(){
    var n=document.querySelectorAll('.chk-siswa:checked').length;
    document.getElementById('ctrDipilih').textContent=n;
}

// ── Centang/uncentang satu baris ──
function onCheck(chk, sid){
    var row=document.getElementById('row_'+sid);
    var groups=['if_topik_','if_sub_','if_jenis_','if_poin_','if_btn_'];
    if(chk.checked){
        row.classList.add('selected');
        groups.forEach(function(g){ var el=document.getElementById(g+sid); if(el) el.classList.add('show'); });
    } else {
        row.classList.remove('selected');
        groups.forEach(function(g){ var el=document.getElementById(g+sid); if(el) el.classList.remove('show'); });
        // reset
        var st=row.querySelector('.sel-topik'); if(st) st.value='';
        var ss=document.getElementById('sub_'+sid);
        if(ss){ ss.innerHTML='<option value="">— Pilih Sub —</option>'; }
        var sj=document.getElementById('jenis_'+sid);
        if(sj){ sj.innerHTML='<option value="">— Pilih Jenis —</option>'; }
        var pb=document.getElementById('poin_badge_'+sid); if(pb) pb.textContent='';
        var btn=document.getElementById('btnSimpan_'+sid);
        if(btn){ btn.disabled=true; btn.className='btn-simpan-row'; btn.innerHTML='<i class="fas fa-save"></i> Simpan'; }
    }
    updateCounter();
}

// ── Pilih Semua ──
function toggleAll(checked){
    document.querySelectorAll('.chk-siswa').forEach(function(c){
        c.checked=checked; onCheck(c,parseInt(c.dataset.id));
    });
}

// ── Topik berubah ──
function onTopikChange(sel,sid){
    var tid=parseInt(sel.value)||0;
    var ss=document.getElementById('sub_'+sid);
    ss.innerHTML='<option value="">— Pilih Sub —</option>';
    (subByTopik[tid]||[]).forEach(function(s){
        ss.innerHTML+='<option value="'+s.id+'">'+s.nama+'</option>';
    });
    populateJenis(sid,0,tid);
    resetBtn(sid);
}

// ── Sub berubah ──
function onSubChange(sel,sid){
    var subid=parseInt(sel.value)||0;
    var tid=parseInt(document.querySelector('#row_'+sid+' .sel-topik').value)||0;
    populateJenis(sid,subid,tid);
    resetBtn(sid);
}

// ── Isi dropdown jenis ──
function populateJenis(sid,subid,tid){
    var sj=document.getElementById('jenis_'+sid);
    sj.innerHTML='<option value="">— Pilih Jenis —</option>';
    var arr=subid?(jenisBySub[subid]||[]):(tid?(jenisByTopik[tid]||[]):[]);
    arr.forEach(function(j){
        sj.innerHTML+='<option value="'+j.id+'" data-poin="'+j.poin+'">'+j.nama+' ('+j.poin+' poin)</option>';
    });
    var pb=document.getElementById('poin_badge_'+sid); if(pb) pb.textContent='';
    resetBtn(sid);
}

// ── Jenis berubah ──
function onJenisChange(sel,sid){
    var opt=sel.options[sel.selectedIndex];
    var jid=parseInt(sel.value)||0;
    var poin=parseInt(opt.dataset&&opt.dataset.poin||0);
    var pb=document.getElementById('poin_badge_'+sid);
    if(pb){
        pb.className='badge-poin';
        pb.textContent=jid?poin+' Poin':'';
    }
    var btn=document.getElementById('btnSimpan_'+sid);
    if(btn) btn.disabled=!jid;
}

function resetBtn(sid){
    var btn=document.getElementById('btnSimpan_'+sid);
    if(btn){ btn.disabled=true; btn.className='btn-simpan-row'; btn.innerHTML='<i class="fas fa-save"></i> Simpan'; }
}

// ── Simpan satu siswa (AJAX) ──
function simpanSatu(sid){
    var tgl   = document.getElementById('filterTgl').value;
    var tidEl = document.querySelector('#row_'+sid+' .sel-topik');
    var subEl = document.getElementById('sub_'+sid);
    var jnEl  = document.getElementById('jenis_'+sid);
    var tid   = tidEl ? (parseInt(tidEl.value)||0) : 0;
    var subid = subEl ? (parseInt(subEl.value)||0) : 0;
    var jid   = jnEl  ? (parseInt(jnEl.value)||0)  : 0;
    if(!jid){ showToast('Pilih jenis pelanggaran terlebih dahulu','err'); return; }

    var btn=document.getElementById('btnSimpan_'+sid);
    btn.disabled=true;
    btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Menyimpan...';

    var fd=new FormData();
    fd.append('siswa_id',sid);
    fd.append('jenis_id',jid);
    fd.append('topik_id',tid);
    fd.append('sub_id',subid);
    fd.append('tanggal',tgl);

    fetch('portal_disiplin.php?ajax=simpan_satu',{method:'POST',body:fd})
        .then(function(r){ return r.json(); })
        .then(function(d){
            if(d.ok){
                btn.disabled=true;
                btn.className='btn-simpan-row saved';
                btn.innerHTML='<i class="fas fa-check"></i> Tersimpan';
                showToast(d.nama+' — '+d.jenis+' ('+d.poin+' Poin)','ok');
                refreshHistori();
                updateStats();
                muatExistingHariIni();
            } else {
                btn.disabled=false;
                btn.innerHTML='<i class="fas fa-save"></i> Simpan';
                showToast(d.msg||'Gagal menyimpan','err');
            }
        })
        .catch(function(){
            btn.disabled=false;
            btn.innerHTML='<i class="fas fa-save"></i> Simpan';
            showToast('Koneksi gagal','err');
        });
}

// ── Refresh tabel histori tanpa reload halaman ──
function refreshHistori(){
    var tgl  = document.getElementById('filterTgl').value;
    var kelas= document.getElementById('filterKelas').value;
    fetch('portal_disiplin.php?ajax=get_histori&tgl='+encodeURIComponent(tgl)+'&kelas='+encodeURIComponent(kelas))
        .then(function(r){ return r.json(); })
        .then(function(data){
            var tbl=document.getElementById('isiHistori');
            if(!data.length){
                tbl.innerHTML='<div class="empty-state"><i class="fas fa-check-circle"></i>Tidak ada pelanggaran pada tanggal ini</div>';
                document.getElementById('jmlData').textContent='0 data';
                return;
            }
            document.getElementById('jmlData').textContent=data.length+' data';
            var html='<div style="overflow-x:auto"><table class="tbl-hist"><thead><tr>'
                +'<th>#</th><th style="text-align:left">WAKTU</th><th style="text-align:left">SISWA</th>'
                +'<th>KELAS</th><th style="text-align:left">TOPIK</th><th style="text-align:left">SUB</th>'
                +'<th style="text-align:left">JENIS PELANGGARAN</th><th>POIN</th><th>AKSI</th>'
                +'</tr></thead><tbody>';
            data.forEach(function(p,i){
                var topikBadge=p.topik_nama?'<span class="badge-top">'+p.topik_nama+'</span>':'<span style="color:#475569">—</span>';
                var subBadge  =p.sub_nama  ?'<span class="badge-sub-h">'+p.sub_nama+'</span>':'<span style="color:#475569">—</span>';
                html+='<tr>'
                    +'<td>'+(i+1)+'</td>'
                    +'<td style="text-align:left;font-size:.74rem;color:#94a3b8">'+p.waktu+'</td>'
                    +'<td style="text-align:left;font-weight:700;color:#e2e8f0">'+p.nama+'</td>'
                    +'<td>'+p.kelas+'</td>'
                    +'<td style="text-align:left">'+topikBadge+'</td>'
                    +'<td style="text-align:left">'+subBadge+'</td>'
                    +'<td style="text-align:left;font-weight:600;color:#e2e8f0">'+p.jenis_nama+'</td>'
                    +'<td><span class="badge-poin">'+p.poin+' Poin</span></td>'
                    +'<td><button type="button" class="btn-del" onclick="hapusPelanggaran('+p.id+')"><i class="fas fa-trash"></i></button></td>'
                    +'</tr>';
            });
            html+='</tbody></table></div>';
            tbl.innerHTML=html;
        });
}

// ── Update stats ──
function updateStats(){
    fetch('portal_disiplin.php?ajax=get_stats_today')
        .then(function(r){ return r.json(); })
        .then(function(d){
            if(document.getElementById('statHari')) document.getElementById('statHari').textContent=d.total||0;
            if(document.getElementById('statPoin')) document.getElementById('statPoin').textContent=d.poin||0;
        });
}

// ── Muat siswa dari kelas baru ──
function muatSiswa(kelas){
    var tbody=document.getElementById('tbodySiswa');
    tbody.innerHTML='<tr><td colspan="9" style="text-align:center;padding:30px;color:#475569"><i class="fas fa-spinner fa-spin"></i> Memuat...</td></tr>';
    document.getElementById('ctrDipilih').textContent='0';
    document.getElementById('chkAll').checked=false;
    document.getElementById('headerKelas').textContent='Daftar Siswa — '+kelas;
    fetch('portal_disiplin.php?ajax=get_siswa_kelas&kelas='+encodeURIComponent(kelas))
        .then(function(r){ return r.json(); })
        .then(function(data){
            if(!data.length){
                tbody.innerHTML='<tr><td colspan="9" style="text-align:center;padding:40px;color:#475569"><i class="fas fa-users"></i> Tidak ada siswa di kelas ini</td></tr>';
                return;
            }
            var html='';
            data.forEach(function(sw){
                html+='<tr id="row_'+sw.id+'">';
                html+='<td style="text-align:center"><input type="checkbox" class="chk-siswa" data-id="'+sw.id+'" onchange="onCheck(this,'+sw.id+')"></td>';
                html+='<td style="font-weight:700;color:#e2e8f0">'+sw.nama+'</td>';
                html+='<td style="font-family:monospace;color:#94a3b8">'+sw.nis+'</td>';
                html+='<td><div class="inline-fields" id="if_topik_'+sw.id+'"><select class="sel-inline sel-topik" data-sid="'+sw.id+'" onchange="onTopikChange(this,'+sw.id+')">'+topikOptsHtml+'</select></div></td>';
                html+='<td><div class="inline-fields" id="if_sub_'+sw.id+'"><select class="sel-inline sel-sub" id="sub_'+sw.id+'" data-sid="'+sw.id+'" onchange="onSubChange(this,'+sw.id+')"><option value="">— Pilih Sub —</option></select></div></td>';
                html+='<td><div class="inline-fields" id="if_jenis_'+sw.id+'"><select class="sel-inline sel-jenis" id="jenis_'+sw.id+'" data-sid="'+sw.id+'" onchange="onJenisChange(this,'+sw.id+')"><option value="">— Pilih Jenis —</option></select></div></td>';
                html+='<td style="text-align:center"><div class="inline-fields" id="if_poin_'+sw.id+'"><span id="poin_badge_'+sw.id+'"></span></div></td>';
                html+='<td style="text-align:center"><div class="inline-fields" id="if_btn_'+sw.id+'"><button type="button" class="btn-simpan-row" id="btnSimpan_'+sw.id+'" onclick="simpanSatu('+sw.id+')" disabled><i class="fas fa-save"></i> Simpan</button></div></td>';
                html+='<td style="text-align:center" id="existing_'+sw.id+'"></td>';
                html+='</tr>';
            });
            tbody.innerHTML=html;
            muatExistingHariIni();
        })
        .catch(function(){ tbody.innerHTML='<tr><td colspan="9" style="text-align:center;padding:30px;color:#ef4444">Gagal memuat data siswa</td></tr>'; });
}

// ── Menu Edit: tampilkan pelanggaran yang sudah tercatat hari ini per siswa
//    beserta tombol hapus (tanpa reload halaman) ──
function muatExistingHariIni(){
    var tgl  = document.getElementById('filterTgl').value;
    var kelas= document.getElementById('filterKelas').value;
    fetch('portal_disiplin.php?ajax=get_existing&tgl='+encodeURIComponent(tgl)+'&kelas='+encodeURIComponent(kelas))
        .then(function(r){ return r.json(); })
        .then(function(data){
            document.querySelectorAll('[id^="existing_"]').forEach(function(el){ el.innerHTML=''; });
            var grouped={};
            data.forEach(function(p){ (grouped[p.siswa_id]=grouped[p.siswa_id]||[]).push(p); });
            Object.keys(grouped).forEach(function(sid){
                var el=document.getElementById('existing_'+sid);
                if(!el) return;
                var html='<div style="display:flex;flex-direction:column;align-items:center;gap:4px">';
                grouped[sid].forEach(function(p){
                    html+='<span style="display:inline-flex;align-items:center;gap:5px;background:rgba(245,158,11,.12);border:1px solid rgba(245,158,11,.3);color:#fbbf24;padding:3px 8px;border-radius:20px;font-size:.68rem;font-weight:700;max-width:170px">'
                        + '<span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">'+escDisH(p.jenis_nama)+' ('+p.poin+')</span>'
                        + '<button type="button" onclick="hapusPelanggaran('+p.id+')" style="background:none;border:none;color:#f87171;cursor:pointer;font-size:.78rem;padding:0;flex-shrink:0" title="Hapus pelanggaran ini"><i class="fas fa-times-circle"></i></button>'
                        + '</span>';
                });
                html+='</div>';
                el.innerHTML=html;
            });
        });
}

function escDisH(s){
    return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Hapus satu pelanggaran (dipakai tombol Edit/Hapus di Daftar Siswa & di tabel Histori) ──
function hapusPelanggaran(id){
    if(!confirm('Hapus pelanggaran ini?')) return;
    var fd=new FormData(); fd.append('id',id);
    fetch('portal_disiplin.php?ajax=hapus_satu',{method:'POST',body:fd})
        .then(function(r){ return r.json(); })
        .then(function(d){
            if(d.ok){
                showToast('Pelanggaran dihapus','ok');
                refreshHistori();
                updateStats();
                muatExistingHariIni();
            } else {
                showToast(d.msg||'Gagal menghapus','err');
            }
        })
        .catch(function(){ showToast('Koneksi gagal','err'); });
}

// ── Toast notifikasi ──
var toastTimer;
function showToast(msg,type){
    var t=document.getElementById('toast');
    t.className='show '+(type||'');
    t.innerHTML=(type==='ok'?'<i class="fas fa-check-circle" style="color:#4ade80"></i> ':'<i class="fas fa-exclamation-circle" style="color:#f87171"></i> ')+msg;
    clearTimeout(toastTimer);
    toastTimer=setTimeout(function(){ t.className=''; },3500);
}
</script>

<?php
// ── AJAX: histori untuk refresh ──
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_histori') {
    header('Content-Type: application/json');
    $tgl2   = $conn->real_escape_string($_GET['tgl']   ?? $today);
    $kls2   = $conn->real_escape_string($_GET['kelas'] ?? '');
    $klsCond= $kls2 ? "AND p.kelas='$kls2'" : '';
    $res2   = $conn->query("SELECT p.id,p.nama,p.kelas,p.jenis_nama,p.poin,
        DATE_FORMAT(p.created_at,'%H:%i') waktu,
        pt.nama topik_nama, ps.nama sub_nama
        FROM pelanggaran p
        LEFT JOIN pelanggaran_topik pt ON pt.id=p.topik_id
        LEFT JOIN pelanggaran_sub   ps ON ps.id=p.sub_id
        WHERE p.tanggal='$tgl2' $klsCond" . periode_where($conn, 'p.') . " ORDER BY p.created_at DESC");
    $rows2=[];
    while($r=$res2->fetch_assoc()) $rows2[]=$r;
    echo json_encode($rows2); exit;
}

// ── AJAX: stats ──
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_stats_today') {
    header('Content-Type: application/json');
    $tot = (int)$conn->query("SELECT COUNT(*) c FROM pelanggaran WHERE tanggal='$today'" . periode_where($conn))->fetch_assoc()['c'];
    $poi = (int)($conn->query("SELECT COALESCE(SUM(poin),0) s FROM pelanggaran WHERE tanggal='$today'" . periode_where($conn))->fetch_assoc()['s']??0);
    echo json_encode(['total'=>$tot,'poin'=>$poi]); exit;
}
?>
</body>
</html>
