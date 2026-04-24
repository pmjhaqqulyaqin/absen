<?php
require_once 'includes/config.php';
cek_wali();

$wid  = $_SESSION['wali_id'];
$wali = $conn->query("SELECT * FROM wali WHERE id=$wid")->fetch_assoc();
$pengaturan = get_pengaturan();

if (isset($_GET['logout'])) { session_destroy(); header('Location: '.BASE_URL.'portal_login.php?role=wali'); exit; }

// ── Buat tabel pesan_wali jika belum ada ──────────────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS pesan_wali (
    id INT AUTO_INCREMENT PRIMARY KEY,
    wali_id INT NOT NULL,
    siswa_id INT NOT NULL,
    pengirim ENUM('wali','siswa') NOT NULL DEFAULT 'wali',
    pesan TEXT NOT NULL,
    dibaca TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ws (wali_id, siswa_id),
    INDEX idx_siswa (siswa_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── Page routing ──────────────────────────────────────────────────────
$page    = $_GET['page']    ?? 'dashboard';
$sid_url = isset($_GET['sid']) ? (int)$_GET['sid'] : 0;
$bulan   = (int)($_GET['bulan'] ?? date('n'));
$tahun   = (int)($_GET['tahun'] ?? date('Y'));

// ── EDIT ABSENSI: Simpan satu record ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wali_edit_single'])) {
    header('Content-Type: application/json');
    $absen_id   = (int)($_POST['absen_id'] ?? 0);
    $new_status = trim($_POST['new_status'] ?? '');
    $new_ket    = $conn->real_escape_string(trim($_POST['new_keterangan'] ?? ''));
    $valid_st   = ['Hadir','Terlambat','Alpa','Sakit','Izin','Bolos'];
    // Pastikan siswa ini milik wali yang login
    $ok = false;
    if ($absen_id && in_array($new_status, $valid_st)) {
        $ab = $conn->query("SELECT a.siswa_id FROM absensi a WHERE a.id=$absen_id")->fetch_assoc();
        if ($ab && in_array((int)$ab['siswa_id'], $anak_ids)) {
            $ex = $conn->query("SELECT jam_masuk FROM absensi WHERE id=$absen_id")->fetch_assoc();
            $jam_final = ($ex && $ex['jam_masuk'] && in_array($new_status,['Hadir','Terlambat'])) ? "'{$ex['jam_masuk']}'" : "NULL";
            if (in_array($new_status,['Alpa','Sakit','Izin','Bolos'])) $jam_final = "NULL";
            $conn->query("UPDATE absensi SET status='$new_status', keterangan='$new_ket', jam_masuk=$jam_final, metode='Manual', updated_at=NOW() WHERE id=$absen_id");
            $ok = true;
        }
    }
    echo json_encode(['ok'=>$ok]);
    exit;
}

// ── EDIT ABSENSI: Hapus record ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wali_hapus_absen'])) {
    header('Content-Type: application/json');
    $absen_id = (int)($_POST['absen_id'] ?? 0);
    $ok = false;
    if ($absen_id) {
        $ab = $conn->query("SELECT siswa_id FROM absensi WHERE id=$absen_id")->fetch_assoc();
        if ($ab && in_array((int)$ab['siswa_id'], $anak_ids)) {
            $conn->query("DELETE FROM absensi WHERE id=$absen_id");
            $ok = true;
        }
    }
    echo json_encode(['ok'=>$ok]);
    exit;
}

// ── Data anak didik ───────────────────────────────────────────────────
$anak_res = $conn->query("SELECT s.* FROM siswa s
    JOIN wali_siswa ws ON ws.siswa_id=s.id
    WHERE ws.wali_id=$wid ORDER BY s.nama");
$anak_list = [];
while ($r = $anak_res->fetch_assoc()) $anak_list[] = $r;
$anak_ids = array_column($anak_list, 'id');
$anak_ids_str = $anak_ids ? implode(',', $anak_ids) : '0';

// ── Validasi akses ke siswa tertentu ─────────────────────────────────
$siswa_ok = $sid_url && in_array($sid_url, $anak_ids);

// ── KIRIM PESAN (AJAX) ────────────────────────────────────────────────
if (isset($_POST['ajax_kirim_pesan'])) {
    header('Content-Type: application/json');
    $sid  = (int)($_POST['siswa_id'] ?? 0);
    $pesan = trim($_POST['pesan'] ?? '');
    if ($sid && in_array($sid, $anak_ids) && $pesan !== '') {
        $ps = $conn->real_escape_string($pesan);
        $conn->query("INSERT INTO pesan_wali (wali_id,siswa_id,pengirim,pesan) VALUES ($wid,$sid,'wali','$ps')");
        echo json_encode(['ok'=>true,'waktu'=>date('H:i'),'pesan'=>htmlspecialchars($pesan)]);
    } else {
        echo json_encode(['ok'=>false]);
    }
    exit;
}

// ── ABSEN MANUAL (AJAX) ──────────────────────────────────────────────
if (isset($_POST['ajax_absen_manual'])) {
    header('Content-Type: application/json');
    $sid      = (int)($_POST['siswa_id'] ?? 0);
    $status   = $_POST['status'] ?? '';
    $tanggal  = $_POST['tanggal'] ?? date('Y-m-d');
    $ket      = $conn->real_escape_string(trim($_POST['keterangan'] ?? ''));
    $valid_st = ['Hadir','Terlambat','Alpa','Sakit','Izin','Bolos'];
    // Validasi tanggal tidak lebih dari hari ini
    if ($tanggal > date('Y-m-d')) { echo json_encode(['ok'=>false,'msg'=>'Tanggal tidak boleh melebihi hari ini']); exit; }
    if ($sid && in_array($sid, $anak_ids) && in_array($status, $valid_st)) {
        $s_info = $conn->query("SELECT nis, nama, kelas FROM siswa WHERE id=$sid")->fetch_assoc();
        $existing = $conn->query("SELECT id FROM absensi WHERE siswa_id=$sid AND tanggal='$tanggal'")->fetch_assoc();
        $jam = date('H:i:s');
        if ($existing) {
            $conn->query("UPDATE absensi SET status='$status', jam_masuk='$jam', keterangan='$ket', metode='Manual' WHERE id={$existing['id']}");
        } else {
            $nis   = $conn->real_escape_string($s_info['nis']);
            $nama  = $conn->real_escape_string($s_info['nama']);
            $kelas = $conn->real_escape_string($s_info['kelas']);
            $conn->query("INSERT INTO absensi (siswa_id, nis, nama, kelas, tanggal, jam_masuk, status, keterangan, metode) VALUES ($sid,'$nis','$nama','$kelas','$tanggal','$jam','$status','$ket','Manual')");
        }
        echo json_encode(['ok'=>true,'status'=>$status,'msg'=>'Absensi berhasil disimpan']);
    } else {
        echo json_encode(['ok'=>false,'msg'=>'Data tidak valid']);
    }
    exit;
}

// ── AMBIL PESAN (AJAX) ────────────────────────────────────────────────
if (isset($_GET['ajax_pesan']) && isset($_GET['sid'])) {
    header('Content-Type: application/json');
    $sid  = (int)$_GET['sid'];
    $last = (int)($_GET['last_id'] ?? 0);
    if (!in_array($sid, $anak_ids)) { echo json_encode(['messages'=>[]]); exit; }
    $conn->query("UPDATE pesan_wali SET dibaca=1 WHERE siswa_id=$sid AND wali_id=$wid AND pengirim='siswa'");
    $rows = [];
    $res  = $conn->query("SELECT * FROM pesan_wali WHERE wali_id=$wid AND siswa_id=$sid AND id>$last ORDER BY id ASC LIMIT 50");
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    echo json_encode(['messages'=>$rows]);
    exit;
}

// ── STATISTIK DASHBOARD ───────────────────────────────────────────────
$today      = date('Y-m-d');
$nama_bulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$stat_today = ['Hadir'=>0,'Terlambat'=>0,'Alpa'=>0,'Sakit'=>0,'Izin'=>0,'Bolos'=>0];
if ($anak_ids) {
    $sr = $conn->query("SELECT status, COUNT(*) c FROM absensi WHERE tanggal='$today' AND siswa_id IN ($anak_ids_str) GROUP BY status");
    while ($r = $sr->fetch_assoc()) $stat_today[$r['status']] = (int)$r['c'];
}
$total_siswa = count($anak_list);

// ── REKAP KALENDER ───────────────────────────────────────────────────
$jumlah_hari = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);
$absensi_map = [];
if ($page === 'rekap' && $anak_ids) {
    $filter_sid = $siswa_ok ? "AND siswa_id=$sid_url" : "AND siswa_id IN ($anak_ids_str)";
    $ar = $conn->query("SELECT siswa_id, DAY(tanggal) tgl, status FROM absensi
        WHERE MONTH(tanggal)=$bulan AND YEAR(tanggal)=$tahun $filter_sid");
    while ($r = $ar->fetch_assoc()) $absensi_map[$r['siswa_id']][$r['tgl']] = $r['status'];
}

$status_kode = [
    'Hadir'     => ['H','#16a34a','#dcfce7'],
    'Terlambat' => ['T','#d97706','#fef3c7'],
    'Alpa'      => ['A','#dc2626','#fee2e2'],
    'Sakit'     => ['S','#2563eb','#dbeafe'],
    'Izin'      => ['I','#7c3aed','#ede9fe'],
    'Bolos'     => ['B','#9a3412','#ffedd5'],
];

// ── Detail siswa untuk halaman chat ──────────────────────────────────
$detail_siswa = null;
if ($siswa_ok) {
    $detail_siswa = $conn->query("SELECT * FROM siswa WHERE id=$sid_url")->fetch_assoc();
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Portal Wali – <?= htmlspecialchars($wali['nama']) ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',system-ui,sans-serif;background:#f1f5f9;display:flex;min-height:100vh;font-size:14px}

/* ── SIDEBAR ─────────────────────────────────── */
.sidebar{width:220px;background:linear-gradient(180deg,#4f46e5 0%,#3730a3 100%);color:white;display:flex;flex-direction:column;flex-shrink:0;min-height:100vh}
.sidebar-logo{padding:20px 18px 14px;border-bottom:1px solid rgba(255,255,255,.15)}
.sidebar-logo .title{font-weight:800;font-size:1rem;display:flex;align-items:center;gap:8px}
.sidebar-logo .sub{font-size:.75rem;opacity:.7;margin-top:3px}
.nav-section{padding:14px 12px 4px;font-size:.65rem;font-weight:700;letter-spacing:1.5px;opacity:.5;text-transform:uppercase}
.nav-item{display:flex;align-items:center;gap:10px;padding:10px 18px;color:rgba(255,255,255,.8);text-decoration:none;transition:.15s;font-size:.875rem;border-radius:8px;margin:2px 8px}
.nav-item:hover{background:rgba(255,255,255,.12);color:white}
.nav-item.active{background:rgba(255,255,255,.2);color:white;font-weight:600}
.nav-item i{width:18px;text-align:center;font-size:.9rem}
.sidebar-footer{margin-top:auto;padding:14px;border-top:1px solid rgba(255,255,255,.12)}

/* ── MAIN ────────────────────────────────────── */
.main{flex:1;display:flex;flex-direction:column;min-height:100vh;overflow:hidden}
.topbar{background:white;padding:12px 24px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05)}
.topbar .page-name{font-weight:700;font-size:1rem;color:#1e293b}
.topbar .time{font-size:.8rem;color:#64748b}
.content{padding:24px;flex:1;overflow-y:auto}

/* ── CARDS ───────────────────────────────────── */
.stat-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:14px;margin-bottom:24px}
.stat-card{background:white;border-radius:12px;padding:16px;box-shadow:0 2px 8px rgba(0,0,0,.06);border-top:4px solid var(--c)}
.stat-card .val{font-size:2rem;font-weight:800;color:var(--c)}
.stat-card .lbl{font-size:.75rem;color:#64748b;margin-top:4px;font-weight:600}
.card{background:white;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.06);overflow:hidden;margin-bottom:20px}
.card-header{padding:14px 20px;font-weight:700;font-size:.9rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:8px;color:#1e293b}
.card-body{padding:20px}

/* ── TABLE ───────────────────────────────────── */
.tbl-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse;font-size:.82rem}
th{background:#1e293b;color:white;padding:8px 6px;text-align:center;white-space:nowrap;font-size:.75rem}
th.sticky-no{position:sticky;left:0;z-index:3;background:#1e293b}
th.sticky-nis{position:sticky;left:30px;z-index:3;background:#1e293b}
th.sticky-nama{position:sticky;left:80px;z-index:3;background:#1e293b}
td{padding:5px 6px;border-bottom:1px solid #f1f5f9;vertical-align:middle}
td.sticky-no{position:sticky;left:0;z-index:1;background:white}
td.sticky-nis{position:sticky;left:30px;z-index:1;background:white}
td.sticky-nama{position:sticky;left:80px;z-index:1;background:white;font-weight:600;white-space:nowrap}
tr:nth-child(even) td{background:#f8fafc}
tr:nth-child(even) td.sticky-no,
tr:nth-child(even) td.sticky-nis,
tr:nth-child(even) td.sticky-nama{background:#f8fafc}
.st-box{display:inline-block;width:22px;height:22px;line-height:22px;border-radius:4px;font-weight:800;font-size:.72rem;text-align:center}
.weekend{background:#f1f5f9 !important}

/* ── LEGEND ──────────────────────────────────── */
.legend{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:16px}
.legend-item{display:flex;align-items:center;gap:6px;font-size:.8rem;font-weight:600}
.legend-dot{width:26px;height:22px;border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:800}

/* ── SUMMARY COL ─────────────────────────────── */
.sum-h{background:#f0fdf4;color:#166534;font-weight:800}
.sum-t{background:#fffbeb;color:#854d0e;font-weight:800}
.sum-a{background:#fef2f2;color:#991b1b;font-weight:800}
.sum-s{background:#eff6ff;color:#1e40af;font-weight:800}
.sum-i{background:#f5f3ff;color:#5b21b6;font-weight:800}
.sum-b{background:#fff7ed;color:#9a3412;font-weight:800}

/* ── SISWA LIST ──────────────────────────────── */
.siswa-list{display:flex;flex-direction:column;gap:8px}
.siswa-row{display:flex;align-items:center;gap:14px;padding:12px 16px;border-radius:10px;cursor:pointer;text-decoration:none;color:#1e293b;background:#f8fafc;transition:.15s;border:1px solid #e2e8f0}
.siswa-row:hover{background:#eef2ff;border-color:#c7d2fe}
.avatar{width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:white;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:1.1rem;flex-shrink:0}

/* ── CHAT ────────────────────────────────────── */
.chat-wrap{display:flex;flex-direction:column;height:calc(100vh - 200px)}
.chat-messages{flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:10px;background:#f8fafc;border-radius:12px;margin-bottom:12px}
.bubble{max-width:70%;padding:10px 14px;border-radius:14px;font-size:.875rem;line-height:1.5;position:relative}
.bubble.sent{align-self:flex-end;background:#4f46e5;color:white;border-bottom-right-radius:4px}
.bubble.recv{align-self:flex-start;background:white;color:#1e293b;border-bottom-left-radius:4px;box-shadow:0 1px 4px rgba(0,0,0,.08)}
.bubble .time{font-size:.65rem;opacity:.6;margin-top:4px;text-align:right}
.chat-input-wrap{display:flex;gap:10px}
.chat-input{flex:1;border:2px solid #e2e8f0;border-radius:10px;padding:10px 14px;outline:none;font-size:.875rem;resize:none;font-family:inherit;transition:.2s}
.chat-input:focus{border-color:#6366f1}
.btn{padding:9px 18px;border-radius:8px;border:none;cursor:pointer;font-size:.875rem;font-weight:600;transition:.15s;display:inline-flex;align-items:center;gap:6px}
.btn-primary{background:#4f46e5;color:white}
.btn-primary:hover{background:#4338ca}
.btn-sm{padding:5px 10px;font-size:.78rem;border-radius:6px}
.btn-secondary{background:#e2e8f0;color:#475569}
.alert{padding:10px 16px;border-radius:8px;margin-bottom:14px;font-size:.875rem}
.alert-success{background:#dcfce7;color:#166534}
.alert-error{background:#fee2e2;color:#991b1b}
.progress-bar{height:6px;background:#e2e8f0;border-radius:3px;overflow:hidden}
.progress-inner{height:100%;border-radius:3px}
.badge-pill{display:inline-block;padding:2px 10px;border-radius:20px;font-size:.72rem;font-weight:700}
.unread-dot{width:8px;height:8px;background:#ef4444;border-radius:50%;display:inline-block;margin-left:4px;flex-shrink:0}
</style>
</head>
<body>

<!-- ══════════════════ SIDEBAR ══════════════════ -->
<div class="sidebar">
    <div class="sidebar-logo">
        <?php
        $foto_wali_file = $wali['foto_wali'] ?? '';
        $foto_wali_path = 'uploads/foto_wali/' . $foto_wali_file;
        ?>
        <?php if ($foto_wali_file && file_exists(__DIR__ . '/' . $foto_wali_path)): ?>
        <div style="display:flex;flex-direction:column;align-items:center;padding:8px 0 12px">
            <img src="<?= BASE_URL . $foto_wali_path ?>?t=<?= filemtime(__DIR__.'/'.$foto_wali_path) ?>"
                 style="width:160px;height:180px;border-radius:12px;object-fit:cover;border:3px solid rgba(255,255,255,.5);box-shadow:0 6px 20px rgba(0,0,0,.35);margin-bottom:12px">
            <div style="text-align:center;padding:0 8px">
                <div style="font-weight:800;font-size:1rem;line-height:1.3;color:white"><?= htmlspecialchars($wali['nama']) ?></div>
                <div style="font-size:.75rem;opacity:.65;margin-top:4px;color:white"><?= htmlspecialchars($wali['jabatan'] ?? 'Wali Kelas') ?></div>
            </div>
        </div>
        <?php else: ?>
        <div class="title"><i class="fas fa-chalkboard-teacher"></i> Portal Wali</div>
        <?php endif; ?>
        <div class="sub">
            <?php if (!empty($wali['kelas_wali'])): ?>
            Kelas <?= htmlspecialchars($wali['kelas_wali']) ?>
            <?php else: ?>
            <?= htmlspecialchars($wali['nama']) ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="nav-section">Utama</div>
    <a href="portal_wali.php?page=dashboard" class="nav-item <?= $page==='dashboard'?'active':'' ?>">
        <i class="fas fa-home"></i> Dashboard
    </a>
    <div class="nav-section">Laporan</div>
    <a href="portal_wali.php?page=rekap" class="nav-item <?= $page==='rekap'?'active':'' ?>">
        <i class="fas fa-calendar-alt"></i> Rekap Absensi
    </a>
    <a href="portal_wali.php?page=laporan_rekap" class="nav-item <?= $page==='laporan_rekap'?'active':'' ?>"
       style="<?= $page==='laporan_rekap'?'':'border-left:3px solid #f59e0b;background:rgba(245,158,11,.08)' ?>">
        <i class="fas fa-clipboard-list" style="color:#f59e0b"></i> Laporan Rekap Harian
    </a>

    <div class="nav-section">Manajemen</div>
    <a href="portal_wali.php?page=absen" class="nav-item <?= $page==='absen'?'active':'' ?>">
        <i class="fas fa-clipboard-check"></i> Absen Manual
    </a>
    <a href="portal_wali.php?page=edit_absensi" class="nav-item <?= $page==='edit_absensi'?'active':'' ?>">
        <i class="fas fa-pen-square"></i> Edit Absensi
    </a>
    <a href="portal_wali.php?page=siswa" class="nav-item <?= $page==='siswa'?'active':'' ?>">
        <i class="fas fa-users"></i> Data Siswa
    </a>

    <div class="nav-section">Komunikasi</div>
    <a href="portal_wali.php?page=chat" class="nav-item <?= $page==='chat'?'active':'' ?>">
        <i class="fas fa-comments"></i> Chat Siswa
    </a>

    <div class="sidebar-footer">
        <a href="?logout=1" style="color:rgba(255,255,255,.7);text-decoration:none;font-size:.8rem;display:flex;align-items:center;gap:6px">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<!-- ══════════════════ MAIN ══════════════════════ -->
<div class="main">
    <div class="topbar">
        <div class="page-name">
            <?php
            $page_titles = ['dashboard'=>'Dashboard','rekap'=>'Rekap Absensi','laporan_rekap'=>'Laporan Rekap Harian','siswa'=>'Data Siswa','chat'=>'Chat Siswa','absen'=>'Absen Manual','edit_absensi'=>'Edit Absensi'];
            echo '<i class="fas fa-'.(['dashboard'=>'home','rekap'=>'calendar-alt','laporan_rekap'=>'clipboard-list','siswa'=>'users','chat'=>'comments','absen'=>'clipboard-check','edit_absensi'=>'pen-square'][$page] ?? 'home').'"></i>&nbsp; ';
            echo $page_titles[$page] ?? 'Portal Wali';
            ?>
        </div>
        <div class="time">
            <i class="fas fa-clock"></i>
            <span id="jam">--:--:--</span> &nbsp;|&nbsp;
            <?= htmlspecialchars($pengaturan['nama_sekolah'] ?? '') ?>
            &nbsp;<span style="font-weight:700;color:#4f46e5"><?= htmlspecialchars($wali['nama']) ?></span>
        </div>
    </div>

    <div class="content">

<?php // ═══════════════════════════════ DASHBOARD ═══════════════════════════════
if ($page === 'dashboard'): ?>

    <!-- Stat cards -->
    <div class="stat-grid">
        <div class="stat-card" style="--c:#4f46e5">
            <div class="val"><?= $total_siswa ?></div>
            <div class="lbl"><i class="fas fa-users"></i> Total Siswa</div>
        </div>
        <div class="stat-card" style="--c:#16a34a">
            <div class="val"><?= $stat_today['Hadir'] ?></div>
            <div class="lbl"><i class="fas fa-check-circle"></i> Hadir</div>
        </div>
        <div class="stat-card" style="--c:#d97706">
            <div class="val"><?= $stat_today['Terlambat'] ?></div>
            <div class="lbl"><i class="fas fa-clock"></i> Terlambat</div>
        </div>
        <div class="stat-card" style="--c:#dc2626">
            <div class="val"><?= $stat_today['Alpa'] ?></div>
            <div class="lbl"><i class="fas fa-times-circle"></i> Alpa</div>
        </div>
        <div class="stat-card" style="--c:#2563eb">
            <div class="val"><?= $stat_today['Sakit'] ?></div>
            <div class="lbl"><i class="fas fa-hospital"></i> Sakit</div>
        </div>
        <div class="stat-card" style="--c:#7c3aed">
            <div class="val"><?= $stat_today['Izin'] ?></div>
            <div class="lbl"><i class="fas fa-file-alt"></i> Izin</div>
        </div>
        <div class="stat-card" style="--c:#9a3412">
            <div class="val"><?= $stat_today['Bolos'] ?></div>
            <div class="lbl"><i class="fas fa-ban"></i> Bolos</div>
        </div>
    </div>

    <!-- Absensi hari ini -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-calendar-day" style="color:#4f46e5"></i>
            Absensi Hari Ini &mdash; <?= format_tanggal($today) ?>
            <span style="margin-left:auto;font-size:.75rem;color:#64748b;font-weight:400"><?= $total_siswa ?> siswa</span>
        </div>
        <div class="tbl-wrap">
            <table>
                <thead>
                    <tr>
                        <th class="sticky-no" style="min-width:32px">No</th>
                        <th style="text-align:left;min-width:180px;padding-left:10px">Nama Siswa</th>
                        <th style="min-width:34px;background:#166534" title="Hadir">H</th>
                        <th style="min-width:34px;background:#854d0e" title="Terlambat">T</th>
                        <th style="min-width:34px;background:#991b1b" title="Alpa">A</th>
                        <th style="min-width:34px;background:#1e40af" title="Sakit">S</th>
                        <th style="min-width:34px;background:#5b21b6" title="Izin">I</th>
                        <th style="min-width:34px;background:#9a3412" title="Bolos">B</th>
                        <th style="min-width:100px">Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$anak_list): ?>
                    <tr><td colspan="9" style="text-align:center;padding:40px;color:#94a3b8">Belum ada siswa yang di-assign ke Anda.</td></tr>
                <?php else: $no=0; foreach($anak_list as $s):
                    $no++;
                    $ab = $conn->query("SELECT status FROM absensi WHERE siswa_id={$s['id']} AND tanggal='$today'")->fetch_assoc();
                    $st = $ab['status'] ?? null;
                ?>
                    <tr>
                        <td class="sticky-no" style="text-align:center;font-weight:600"><?= $no ?></td>
                        <td style="padding-left:10px"><?= htmlspecialchars($s['nama']) ?> <small style="color:#94a3b8"><?= $s['kelas'] ?></small></td>
                        <?php foreach(['Hadir','Terlambat','Alpa','Sakit','Izin','Bolos'] as $ss):
                            $k = $status_kode[$ss];
                        ?>
                        <td style="text-align:center">
                            <?php if ($st === $ss): ?>
                            <span class="st-box" style="background:<?= $k[2] ?>;color:<?= $k[1] ?>"><?= $k[0] ?></span>
                            <?php else: ?><span style="color:#e2e8f0">·</span><?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                        <td style="text-align:center">
                            <?php if ($st): [$kd,$w,$bg] = $status_kode[$st];
                                echo "<span class='badge-pill' style='background:$bg;color:$w'>$st</span>";
                            else: echo "<span class='badge-pill' style='background:#f1f5f9;color:#94a3b8'>Belum absen</span>";
                            endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <!-- Keterangan -->
        <div style="padding:10px 16px;background:#f8fafc;border-top:1px solid #f1f5f9;display:flex;flex-wrap:wrap;gap:10px;font-size:.75rem">
            <strong style="color:#64748b">Keterangan:</strong>
            <?php foreach(['H'=>['#16a34a','#dcfce7','Hadir'],'T'=>['#d97706','#fef3c7','Terlambat'],'A'=>['#dc2626','#fee2e2','Alpa'],'S'=>['#2563eb','#dbeafe','Sakit'],'I'=>['#7c3aed','#ede9fe','Izin'],'B'=>['#9a3412','#ffedd5','Bolos']] as $kd=>$v): ?>
            <span style="display:flex;align-items:center;gap:5px;font-weight:600">
                <span style="width:22px;height:20px;border-radius:4px;background:<?= $v[1] ?>;color:<?= $v[0] ?>;display:inline-flex;align-items:center;justify-content:center;font-weight:800"><?= $kd ?></span>
                = <?= $v[2] ?>
            </span>
            <?php endforeach; ?>
        </div>
    </div>

<?php // ═══════════════════════════════ REKAP ═══════════════════════════════
elseif ($page === 'rekap'): ?>

    <!-- Filter -->
    <form method="GET" style="display:flex;gap:10px;align-items:flex-end;margin-bottom:20px;flex-wrap:wrap">
        <input type="hidden" name="page" value="rekap">
        <div>
            <div style="font-size:.75rem;font-weight:600;color:#64748b;margin-bottom:4px">Bulan</div>
            <select name="bulan" style="padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;background:white;outline:none;font-size:.875rem">
                <?php for($m=1;$m<=12;$m++): ?>
                <option value="<?= $m ?>" <?= $bulan==$m?'selected':'' ?>><?= $nama_bulan[$m] ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div>
            <div style="font-size:.75rem;font-weight:600;color:#64748b;margin-bottom:4px">Tahun</div>
            <select name="tahun" style="padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;background:white;outline:none;font-size:.875rem">
                <?php for($y=date('Y');$y>=date('Y')-3;$y--): ?>
                <option value="<?= $y ?>" <?= $tahun==$y?'selected':'' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <?php if (count($anak_list)>1): ?>
        <div>
            <div style="font-size:.75rem;font-weight:600;color:#64748b;margin-bottom:4px">Siswa</div>
            <select name="sid" style="padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;background:white;outline:none;font-size:.875rem">
                <option value="">Semua Siswa</option>
                <?php foreach($anak_list as $s): ?>
                <option value="<?= $s['id'] ?>" <?= $sid_url==$s['id']?'selected':'' ?>><?= htmlspecialchars($s['nama']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Tampilkan</button>
    </form>

    <!-- Keterangan -->
    <div class="legend" style="margin-bottom:16px">
        <span style="font-size:.8rem;font-weight:700;color:#64748b;align-self:center">Keterangan:</span>
        <?php foreach(['H'=>['#16a34a','#dcfce7','Hadir'],'T'=>['#d97706','#fef3c7','Terlambat'],'A'=>['#dc2626','#fee2e2','Alpa'],'S'=>['#2563eb','#dbeafe','Sakit'],'I'=>['#7c3aed','#ede9fe','Izin'],'B'=>['#9a3412','#ffedd5','Bolos']] as $kd=>$v): ?>
        <div class="legend-item">
            <span class="legend-dot" style="background:<?= $v[1] ?>;color:<?= $v[0] ?>"><?= $kd ?></span>
            <span style="color:#475569">=&nbsp;<?= $v[2] ?></span>
        </div>
        <?php endforeach; ?>
        <div class="legend-item"><span style="color:#94a3b8;font-size:.75rem">(<span style="color:#374151">—</span> = Libur/Weekend)</span></div>
    </div>

    <!-- Rekap Tabel -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-calendar-alt" style="color:#4f46e5"></i>
            Daftar Hadir <?= $nama_bulan[$bulan] ?> <?= $tahun ?>
            <span style="margin-left:auto;font-size:.75rem;font-weight:400;color:#64748b"><?= count($anak_list) ?> siswa</span>
        </div>
        <div class="tbl-wrap">
        <?php
        $show_list = ($siswa_ok && $sid_url) ? array_filter($anak_list, fn($s)=>$s['id']==$sid_url) : $anak_list;
        ?>
        <table>
            <thead>
                <tr>
                    <th class="sticky-no" style="min-width:30px">#</th>
                    <th class="sticky-nis" style="min-width:50px;text-align:left">NIS</th>
                    <th class="sticky-nama" style="min-width:160px;text-align:left;padding-left:10px">NAMA</th>
                    <?php for($d=1;$d<=$jumlah_hari;$d++):
                        $ts = mktime(0,0,0,$bulan,$d,$tahun);
                        $hn = date('N',$ts);
                        $iswk = $hn >= 6;
                        $hari_s = ['','Sen','Sel','Rab','Kam','Jum','Sab','Min'][$hn];
                    ?>
                    <th style="min-width:30px;<?= $iswk?'background:#374151':'' ?>" title="<?= $hari_s ?> <?= $d ?>">
                        <div style="font-size:.6rem;font-weight:400;opacity:.7"><?= $hari_s ?></div>
                        <div style="font-size:.78rem"><?= $d ?></div>
                    </th>
                    <?php endfor; ?>
                    <th style="min-width:28px;background:#166534;font-size:.8rem" title="Hadir">H</th>
                    <th style="min-width:28px;background:#854d0e;font-size:.8rem" title="Terlambat">T</th>
                    <th style="min-width:28px;background:#991b1b;font-size:.8rem" title="Alpa">A</th>
                    <th style="min-width:28px;background:#1e40af;font-size:.8rem" title="Sakit">S</th>
                    <th style="min-width:28px;background:#5b21b6;font-size:.8rem" title="Izin">I</th>
                    <th style="min-width:28px;background:#9a3412;font-size:.8rem" title="Bolos">B</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$anak_list): ?>
                <tr><td colspan="<?= 3+$jumlah_hari+6 ?>" style="text-align:center;padding:40px;color:#94a3b8">Belum ada siswa.</td></tr>
            <?php else: $no=0; foreach($show_list as $s):
                $no++; $sid2=$s['id'];
                $tot=['H'=>0,'T'=>0,'A'=>0,'S'=>0,'I'=>0,'B'=>0];
            ?>
                <tr>
                    <td class="sticky-no" style="text-align:center;font-weight:600"><?= $no ?></td>
                    <td class="sticky-nis" style="font-family:monospace;font-size:.75rem"><?= $s['nis'] ?></td>
                    <td class="sticky-nama" style="padding-left:10px"><?= htmlspecialchars($s['nama']) ?></td>
                    <?php for($d=1;$d<=$jumlah_hari;$d++):
                        $ts = mktime(0,0,0,$bulan,$d,$tahun);
                        $iswk = date('N',$ts) >= 6;
                        $st = $absensi_map[$sid2][$d] ?? null;
                        if ($st && isset($status_kode[$st])) {
                            [$kd2] = $status_kode[$st];
                            if (isset($tot[$kd2])) $tot[$kd2]++;
                        }
                    ?>
                    <td style="text-align:center;<?= $iswk?'background:#f1f5f9':'' ?>">
                        <?php if ($st && isset($status_kode[$st])): [$kd2,$w,$bg]=$status_kode[$st]; ?>
                        <span class="st-box" style="background:<?= $bg ?>;color:<?= $w ?>"><?= $kd2 ?></span>
                        <?php elseif($iswk): ?><span style="color:#cbd5e1;font-size:.75rem">—</span>
                        <?php else: ?><span style="color:#e2e8f0;font-size:.7rem">·</span>
                        <?php endif; ?>
                    </td>
                    <?php endfor; ?>
                    <td style="text-align:center" class="sum-h"><?= $tot['H'] ?></td>
                    <td style="text-align:center" class="sum-t"><?= $tot['T'] ?></td>
                    <td style="text-align:center" class="sum-a"><?= $tot['A'] ?></td>
                    <td style="text-align:center" class="sum-s"><?= $tot['S'] ?></td>
                    <td style="text-align:center" class="sum-i"><?= $tot['I'] ?></td>
                    <td style="text-align:center" class="sum-b"><?= $tot['B'] ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>
    </div>

<?php // ═══════════════════════════════ DATA SISWA ═══════════════════════════════
elseif ($page === 'siswa'): ?>

    <?php if (!$anak_list): ?>
    <div style="text-align:center;padding:60px;color:#94a3b8">
        <i class="fas fa-users fa-3x" style="opacity:.3;display:block;margin-bottom:16px"></i>
        Belum ada siswa yang di-assign ke Anda.<br>Hubungi admin untuk menambahkan siswa.
    </div>
    <?php else: ?>
    <div class="siswa-list">
    <?php foreach($anak_list as $s):
        $alpa = (int)$conn->query("SELECT COUNT(*) c FROM absensi WHERE siswa_id={$s['id']} AND status='Alpa'")->fetch_assoc()['c'];
        $tot2 = (int)$conn->query("SELECT COUNT(*) c FROM absensi WHERE siswa_id={$s['id']}")->fetch_assoc()['c'];
        $pct  = $tot2>0?round(($tot2-$alpa)/$tot2*100):100;
        $ab   = $conn->query("SELECT status FROM absensi WHERE siswa_id={$s['id']} AND tanggal='$today'")->fetch_assoc();
        $st   = $ab['status'] ?? null;
    ?>
    <a href="portal_wali.php?page=siswa&sid=<?= $s['id'] ?>" class="siswa-row">
        <div class="avatar"><?= strtoupper(substr($s['nama'],0,1)) ?></div>
        <div style="flex:1;min-width:0">
            <div style="font-weight:700;margin-bottom:2px"><?= htmlspecialchars($s['nama']) ?></div>
            <div style="font-size:.75rem;color:#64748b">NIS: <?= $s['nis'] ?> &bull; Kelas <?= $s['kelas'] ?></div>
            <div style="margin-top:6px">
                <div class="progress-bar"><div class="progress-inner" style="width:<?= $pct ?>%;background:<?= $pct>=80?'#16a34a':($pct>=60?'#d97706':'#dc2626') ?>"></div></div>
                <div style="font-size:.7rem;color:#64748b;margin-top:2px"><?= $pct ?>% kehadiran &bull; Alpa <?= $alpa ?>x</div>
            </div>
        </div>
        <div style="text-align:right;flex-shrink:0">
            <?php if ($st): [$kd2,$w,$bg]=$status_kode[$st];
                echo "<span class='badge-pill' style='background:$bg;color:$w'>$st</span>";
            else: echo "<span class='badge-pill' style='background:#f1f5f9;color:#94a3b8'>Belum absen</span>";
            endif; ?>
            <?php if ($alpa>=3): ?><br><span style="font-size:.7rem;color:#dc2626;margin-top:4px;display:block">⚠️ Alpa <?= $alpa ?>x</span><?php endif; ?>
        </div>
        <i class="fas fa-chevron-right" style="color:#cbd5e1;flex-shrink:0"></i>
    </a>
    <?php endforeach; ?>
    </div>

    <?php // Detail siswa
    if ($siswa_ok && $detail_siswa): ?>
    <div id="detail-overlay" style="position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9000;display:flex;align-items:center;justify-content:center">
        <div style="background:white;border-radius:14px;width:100%;max-width:700px;max-height:90vh;overflow-y:auto;padding:24px">
            <div style="display:flex;align-items:center;gap:14px;margin-bottom:20px">
                <div class="avatar" style="width:56px;height:56px;font-size:1.4rem"><?= strtoupper(substr($detail_siswa['nama'],0,1)) ?></div>
                <div>
                    <div style="font-weight:800;font-size:1.1rem"><?= htmlspecialchars($detail_siswa['nama']) ?></div>
                    <div style="font-size:.8rem;color:#64748b">NIS <?= $detail_siswa['nis'] ?> &bull; Kelas <?= $detail_siswa['kelas'] ?></div>
                </div>
                <a href="portal_wali.php?page=siswa" style="margin-left:auto;color:#64748b;font-size:1.2rem;text-decoration:none">&times;</a>
            </div>
            <?php
            $st_r=$conn->query("SELECT status,COUNT(*) t FROM absensi WHERE siswa_id=$sid_url GROUP BY status")->fetch_all(MYSQLI_ASSOC);
            $st2=['Hadir'=>0,'Terlambat'=>0,'Alpa'=>0,'Sakit'=>0,'Izin'=>0,'Bolos'=>0,'total'=>0];
            foreach($st_r as $r){$st2[$r['status']]=(int)$r['t'];$st2['total']+=(int)$r['t'];}
            $pct2=$st2['total']>0?round(($st2['Hadir']+$st2['Terlambat'])/$st2['total']*100,1):0;
            ?>
            <div style="display:grid;grid-template-columns:repeat(6,1fr);gap:8px;margin-bottom:20px">
                <?php foreach(['Hadir'=>'#16a34a','Terlambat'=>'#d97706','Alpa'=>'#dc2626','Sakit'=>'#2563eb','Izin'=>'#7c3aed','Bolos'=>'#9a3412'] as $ss=>$c): ?>
                <div style="text-align:center;padding:10px;background:#f8fafc;border-radius:8px;border-top:3px solid <?= $c ?>">
                    <div style="font-size:1.4rem;font-weight:800;color:<?= $c ?>"><?= $st2[$ss] ?></div>
                    <div style="font-size:.7rem;color:#64748b"><?= $ss ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <!-- Riwayat absensi -->
            <table style="width:100%;border-collapse:collapse;font-size:.82rem">
                <thead><tr>
                    <th style="background:#1e293b;color:white;padding:8px;text-align:center">No</th>
                    <th style="background:#1e293b;color:white;padding:8px;text-align:left">Tanggal</th>
                    <th style="background:#1e293b;color:white;padding:8px;text-align:center">Status</th>
                    <th style="background:#1e293b;color:white;padding:8px;text-align:center">Jam</th>
                </tr></thead>
                <tbody>
                <?php $ab2=$conn->query("SELECT * FROM absensi WHERE siswa_id=$sid_url ORDER BY tanggal DESC LIMIT 60");
                $n2=0; while($r2=$ab2->fetch_assoc()): $n2++;
                    [$kd3,$w3,$bg3]=$status_kode[$r2['status']]; ?>
                <tr style="<?= $n2%2==0?'background:#f8fafc':'' ?>">
                    <td style="text-align:center;padding:6px"><?= $n2 ?></td>
                    <td style="padding:6px"><?= format_tanggal($r2['tanggal']) ?></td>
                    <td style="text-align:center;padding:6px"><span class="badge-pill" style="background:<?= $bg3 ?>;color:<?= $w3 ?>"><?= $r2['status'] ?></span></td>
                    <td style="text-align:center;padding:6px"><?= $r2['jam_masuk']?date('H:i',strtotime($r2['jam_masuk'])):'-' ?></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>

<?php // ═══════════════════════════════ ABSEN MANUAL ═══════════════════════════════
elseif ($page === 'absen'):
    $absen_tgl = $_GET['absen_tgl'] ?? date('Y-m-d');
    if ($absen_tgl > date('Y-m-d')) $absen_tgl = date('Y-m-d');
    // Ambil status absensi hari terpilih untuk setiap siswa
    $absen_today_map = [];
    if ($anak_ids) {
        $ar2 = $conn->query("SELECT siswa_id, status, keterangan FROM absensi WHERE tanggal='$absen_tgl' AND siswa_id IN ($anak_ids_str)");
        while ($r = $ar2->fetch_assoc()) $absen_today_map[$r['siswa_id']] = $r;
    }
?>
    <!-- Filter tanggal -->
    <form method="GET" style="display:flex;gap:10px;align-items:flex-end;margin-bottom:20px;flex-wrap:wrap">
        <input type="hidden" name="page" value="absen">
        <div>
            <div style="font-size:.75rem;font-weight:600;color:#64748b;margin-bottom:4px">Tanggal Absensi</div>
            <input type="date" name="absen_tgl" value="<?= $absen_tgl ?>" max="<?= date('Y-m-d') ?>"
                   style="padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;background:white;outline:none;font-size:.875rem">
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Tampilkan</button>
    </form>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-clipboard-check" style="color:#4f46e5"></i>
            Absen Manual — <?= format_tanggal($absen_tgl) ?>
            <span style="margin-left:auto;font-size:.75rem;color:#64748b;font-weight:400"><?= count($anak_list) ?> siswa</span>
        </div>
        <div id="absen-alert" style="display:none;padding:10px 20px;font-size:.85rem;border-radius:0;font-weight:600"></div>

        <?php if (!$anak_list): ?>
        <div style="text-align:center;padding:40px;color:#94a3b8">Belum ada siswa yang di-assign ke Anda.</div>
        <?php else: ?>

        <!-- ── Toolbar: Pilih Semua + Aksi Massal ── -->
        <div style="padding:12px 16px;background:#f8fafc;border-bottom:1px solid #e2e8f0;display:flex;gap:10px;align-items:center;flex-wrap:wrap">
            <!-- Checkbox Pilih Semua -->
            <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:.85rem;font-weight:700;color:#475569;user-select:none">
                <input type="checkbox" id="chkAll" onchange="toggleCheckAll(this.checked)"
                       style="width:16px;height:16px;cursor:pointer;accent-color:#4f46e5">
                Pilih Semua
            </label>
            <span style="color:#e2e8f0">|</span>

            <!-- Dropdown set status untuk yang diceklis -->
            <div style="display:flex;align-items:center;gap:6px">
                <span style="font-size:.8rem;font-weight:600;color:#64748b">Set status terpilih:</span>
                <select id="bulkStatus" style="padding:6px 10px;border:1px solid #e2e8f0;border-radius:8px;font-size:.82rem;background:white;outline:none;font-weight:600">
                    <option value="">-- Pilih Status --</option>
                    <option value="Hadir"     style="color:#16a34a">✅ Hadir</option>
                    <option value="Terlambat" style="color:#d97706">⏰ Terlambat</option>
                    <option value="Alpa"      style="color:#dc2626">❌ Alpa</option>
                    <option value="Sakit"     style="color:#2563eb">🏥 Sakit</option>
                    <option value="Izin"      style="color:#7c3aed">📄 Izin</option>
                    <option value="Bolos"     style="color:#9a3412">🚫 Bolos</option>
                </select>
                <button type="button" onclick="terapkanBulk()"
                        style="padding:6px 14px;border-radius:8px;border:none;background:#4f46e5;color:white;font-size:.8rem;font-weight:700;cursor:pointer">
                    <i class="fas fa-check"></i> Terapkan
                </button>
            </div>
            <span style="color:#e2e8f0">|</span>

            <!-- Shortcut cepat -->
            <?php foreach(['Hadir'=>['#16a34a','✅'],'Terlambat'=>['#d97706','⏰'],'Alpa'=>['#dc2626','❌']] as $qs=>[$qc,$qi]): ?>
            <button type="button" onclick="pilihSemuaStatus('<?= $qs ?>','<?= $absen_tgl ?>')"
                    style="padding:5px 12px;border-radius:20px;border:2px solid <?= $qc ?>;background:white;color:<?= $qc ?>;font-size:.75rem;font-weight:700;cursor:pointer">
                <?= $qi ?> Semua <?= $qs ?>
            </button>
            <?php endforeach; ?>

            <span id="selCount" style="margin-left:auto;font-size:.8rem;font-weight:700;color:#4f46e5;background:#eef2ff;padding:4px 10px;border-radius:20px;display:none">
                0 dipilih
            </span>
        </div>

        <!-- ── Tabel Siswa ── -->
        <div style="padding:0 16px 16px">
            <div style="overflow-x:auto;margin-top:12px">
            <table style="width:100%;border-collapse:collapse;font-size:.875rem">
                <thead>
                    <tr>
                        <th style="background:#1e293b;color:white;padding:10px 8px;text-align:center;width:36px">
                            <input type="checkbox" id="chkAllTh" onchange="toggleCheckAll(this.checked)"
                                   style="width:15px;height:15px;cursor:pointer;accent-color:#6366f1">
                        </th>
                        <th style="background:#1e293b;color:white;padding:10px 8px;text-align:center;width:36px">No</th>
                        <th style="background:#1e293b;color:white;padding:10px 8px;text-align:left;min-width:180px">Nama Siswa</th>
                        <th style="background:#1e293b;color:white;padding:10px 8px;text-align:center;min-width:360px">Status Kehadiran</th>
                        <th style="background:#1e293b;color:white;padding:10px 8px;text-align:center;min-width:100px">Keterangan</th>
                        <th style="background:#1e293b;color:white;padding:10px 8px;text-align:center;width:80px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php $no=0; foreach($anak_list as $s):
                    $no++;
                    $cur = $absen_today_map[$s['id']] ?? null;
                    $cur_st  = $cur['status'] ?? '';
                    $cur_ket = $cur['keterangan'] ?? '';
                    $sid2 = $s['id'];
                ?>
                <tr id="row-<?= $sid2 ?>" style="<?= $no%2==0?'background:#f8fafc':'' ?>">
                    <td style="text-align:center;padding:8px">
                        <input type="checkbox" class="chk-siswa" data-sid="<?= $sid2 ?>"
                               onchange="updateSelCount()"
                               style="width:16px;height:16px;cursor:pointer;accent-color:#4f46e5">
                    </td>
                    <td style="text-align:center;font-weight:600;padding:10px 8px"><?= $no ?></td>
                    <td style="padding:10px 8px">
                        <div style="font-weight:700"><?= htmlspecialchars($s['nama']) ?></div>
                        <div style="font-size:.73rem;color:#94a3b8"><?= $s['kelas'] ?></div>
                    </td>
                    <td style="padding:8px;text-align:center">
                        <div style="display:flex;gap:6px;flex-wrap:wrap;justify-content:center">
                        <?php
                        $st_colors = [
                            'Hadir'     => ['#16a34a','#dcfce7'],
                            'Terlambat' => ['#d97706','#fef3c7'],
                            'Alpa'      => ['#dc2626','#fee2e2'],
                            'Sakit'     => ['#2563eb','#dbeafe'],
                            'Izin'      => ['#7c3aed','#ede9fe'],
                            'Bolos'     => ['#9a3412','#ffedd5'],
                        ];
                        foreach($st_colors as $st=>[$c,$bg]):
                            $active = ($cur_st === $st);
                        ?>
                        <button type="button"
                            onclick="pilihStatus(<?= $sid2 ?>, '<?= $st ?>')"
                            id="btn-<?= $sid2 ?>-<?= $st ?>"
                            style="padding:5px 12px;border-radius:20px;border:2px solid <?= $c ?>;
                                background:<?= $active ? $c : 'white' ?>;
                                color:<?= $active ? 'white' : $c ?>;
                                font-size:.76rem;font-weight:700;cursor:pointer;transition:.15s">
                            <?= $st ?>
                        </button>
                        <?php endforeach; ?>
                        </div>
                    </td>
                    <td style="padding:8px;text-align:center">
                        <input type="text" id="ket-<?= $sid2 ?>" value="<?= htmlspecialchars($cur_ket) ?>"
                               placeholder="Opsional"
                               style="width:90%;padding:5px 8px;border:1px solid #e2e8f0;border-radius:6px;font-size:.78rem;outline:none">
                    </td>
                    <td style="padding:8px;text-align:center">
                        <button type="button" onclick="simpanAbsen(<?= $sid2 ?>, '<?= $absen_tgl ?>')"
                                id="save-<?= $sid2 ?>"
                                style="padding:6px 14px;border-radius:8px;border:none;background:#4f46e5;color:white;font-size:.78rem;font-weight:700;cursor:pointer">
                            <i class="fas fa-save"></i> Simpan
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>

            <!-- Tombol Simpan Semua -->
            <div style="margin-top:16px;display:flex;gap:10px;align-items:center;flex-wrap:wrap">
                <button type="button" onclick="simpanSemua('<?= $absen_tgl ?>')"
                        style="padding:10px 24px;border-radius:8px;border:none;background:#16a34a;color:white;font-weight:700;cursor:pointer;font-size:.875rem">
                    <i class="fas fa-check-double"></i> Simpan Semua
                </button>
                <button type="button" onclick="simpanTerpilih('<?= $absen_tgl ?>')"
                        id="btnSimpanTerpilih"
                        style="padding:10px 18px;border-radius:8px;border:2px solid #4f46e5;color:#4f46e5;background:white;font-weight:700;cursor:pointer;font-size:.8rem;display:none">
                    <i class="fas fa-save"></i> Simpan Terpilih
                </button>
                <span style="font-size:.8rem;color:#64748b"><i class="fas fa-info-circle"></i> Centang siswa → set status → Terapkan → Simpan Terpilih, atau Simpan Semua sekaligus.</span>
            </div>
        </div>
        <?php endif; ?>
    </div>

<script>
// State status yang dipilih per siswa
var selectedStatus = {};
<?php foreach($anak_list as $s): $sid2=$s['id']; $cur_st = $absen_today_map[$sid2]['status'] ?? ''; ?>
selectedStatus[<?= $sid2 ?>] = '<?= $cur_st ?>';
<?php endforeach; ?>

var statusColors = {
    'Hadir':     ['#16a34a','#dcfce7'],
    'Terlambat': ['#d97706','#fef3c7'],
    'Alpa':      ['#dc2626','#fee2e2'],
    'Sakit':     ['#2563eb','#dbeafe'],
    'Izin':      ['#7c3aed','#ede9fe'],
    'Bolos':     ['#9a3412','#ffedd5'],
};

function pilihStatus(sid, status) {
    selectedStatus[sid] = status;
    // Reset semua tombol untuk siswa ini
    ['Hadir','Terlambat','Alpa','Sakit','Izin','Bolos'].forEach(function(st) {
        var btn = document.getElementById('btn-'+sid+'-'+st);
        if (!btn) return;
        var c = statusColors[st][0], bg = statusColors[st][1];
        if (st === status) {
            btn.style.background = c; btn.style.color = 'white';
        } else {
            btn.style.background = 'white'; btn.style.color = c;
        }
    });
}

function pilihSemuaStatus(status, tgl) {
    var sids = [<?= implode(',', $anak_ids) ?>];
    sids.forEach(function(sid){ pilihStatus(sid, status); });
}

// ── Checkbox functions ────────────────────────────────────────
function toggleCheckAll(checked) {
    document.querySelectorAll('.chk-siswa').forEach(function(c){ c.checked = checked; });
    // Sync both checkboxes
    var ca = document.getElementById('chkAll');
    var ct = document.getElementById('chkAllTh');
    if (ca) ca.checked = checked;
    if (ct) ct.checked = checked;
    updateSelCount();
}

function updateSelCount() {
    var n = document.querySelectorAll('.chk-siswa:checked').length;
    var el = document.getElementById('selCount');
    var btnT = document.getElementById('btnSimpanTerpilih');
    if (el)  { el.textContent = n + ' dipilih'; el.style.display = n>0?'inline-block':'none'; }
    if (btnT){ btnT.style.display = n>0?'inline-flex':'none'; }
    // Sync header checkboxes
    var total = document.querySelectorAll('.chk-siswa').length;
    var ca = document.getElementById('chkAll');
    var ct = document.getElementById('chkAllTh');
    if (ca) ca.checked = (n === total && total > 0);
    if (ct) ct.checked = (n === total && total > 0);
}

function terapkanBulk() {
    var status = document.getElementById('bulkStatus').value;
    if (!status) { showAlert('Pilih status terlebih dahulu di dropdown!', 'danger'); return; }
    var checked = document.querySelectorAll('.chk-siswa:checked');
    if (checked.length === 0) { showAlert('Centang minimal 1 siswa terlebih dahulu!', 'danger'); return; }
    checked.forEach(function(c){ pilihStatus(parseInt(c.getAttribute('data-sid')), status); });
    showAlert(checked.length + ' siswa ditandai sebagai ' + status + '. Klik Simpan Terpilih untuk menyimpan.', 'success');
}

function simpanTerpilih(tgl) {
    var checked = document.querySelectorAll('.chk-siswa:checked');
    if (checked.length === 0) { showAlert('Centang minimal 1 siswa!', 'danger'); return; }
    var sids = Array.from(checked).map(function(c){ return parseInt(c.getAttribute('data-sid')); });
    var allSet = true;
    sids.forEach(function(sid){ if (!selectedStatus[sid]) allSet = false; });
    if (!allSet) { showAlert('Pastikan semua siswa yang dipilih sudah ada statusnya!', 'danger'); return; }
    var pending = sids.length;
    sids.forEach(function(sid){
        var ket = document.getElementById('ket-'+sid)?.value || '';
        var btn = document.getElementById('save-'+sid);
        btn.disabled=true; btn.innerHTML='<i class="fas fa-spinner fa-spin"></i>';
        var fd = new FormData();
        fd.append('ajax_absen_manual','1');
        fd.append('siswa_id', sid);
        fd.append('status', selectedStatus[sid]);
        fd.append('tanggal', tgl);
        fd.append('keterangan', ket);
        fetch('portal_wali.php', {method:'POST',body:fd})
            .then(function(r){return r.json();})
            .then(function(d){
                if (d.ok) { btn.innerHTML='<i class="fas fa-check"></i> Tersimpan'; btn.style.background='#16a34a'; }
                else { btn.innerHTML='<i class="fas fa-save"></i> Simpan'; btn.disabled=false; }
                pending--;
                if (pending===0) showAlert(sids.length + ' absensi berhasil disimpan!', 'success');
            });
    });
}

function simpanAbsen(sid, tgl) {
    var status = selectedStatus[sid] || '';
    if (!status) { showAlert('Pilih status untuk siswa ini terlebih dahulu!', 'danger'); return; }
    var ket = document.getElementById('ket-'+sid)?.value || '';
    var btn = document.getElementById('save-'+sid);
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    var fd = new FormData();
    fd.append('ajax_absen_manual','1');
    fd.append('siswa_id', sid);
    fd.append('status', status);
    fd.append('tanggal', tgl);
    fd.append('keterangan', ket);
    fetch('portal_wali.php', {method:'POST',body:fd})
        .then(function(r){return r.json();})
        .then(function(d){
            if (d.ok) {
                btn.innerHTML = '<i class="fas fa-check"></i> Tersimpan';
                btn.style.background = '#16a34a';
                showAlert('Absensi '+status+' berhasil disimpan!', 'success');
            } else {
                btn.innerHTML = '<i class="fas fa-save"></i> Simpan';
                btn.disabled = false;
                showAlert(d.msg || 'Gagal menyimpan', 'danger');
            }
        })
        .catch(function(){ btn.innerHTML='<i class="fas fa-save"></i> Simpan'; btn.disabled=false; });
}

function simpanSemua(tgl) {
    var sids = [<?= implode(',', $anak_ids) ?>];
    var all_ok = true;
    sids.forEach(function(sid){
        if (!selectedStatus[sid]) { all_ok = false; }
    });
    if (!all_ok) { showAlert('Harap pilih status untuk semua siswa terlebih dahulu!', 'danger'); return; }
    var pending = sids.length;
    sids.forEach(function(sid){
        var ket = document.getElementById('ket-'+sid)?.value || '';
        var fd = new FormData();
        fd.append('ajax_absen_manual','1');
        fd.append('siswa_id', sid);
        fd.append('status', selectedStatus[sid]);
        fd.append('tanggal', tgl);
        fd.append('keterangan', ket);
        var btn = document.getElementById('save-'+sid);
        btn.disabled=true; btn.innerHTML='<i class="fas fa-spinner fa-spin"></i>';
        fetch('portal_wali.php', {method:'POST',body:fd})
            .then(function(r){return r.json();})
            .then(function(d){
                if (d.ok) { btn.innerHTML='<i class="fas fa-check"></i> Tersimpan'; btn.style.background='#16a34a'; }
                else { btn.innerHTML='<i class="fas fa-save"></i> Simpan'; btn.disabled=false; }
                pending--;
                if (pending===0) showAlert('Semua absensi berhasil disimpan!', 'success');
            });
    });
}

function showAlert(msg, type) {
    var el = document.getElementById('absen-alert');
    el.style.display='block';
    el.style.background = type==='success'?'#dcfce7':'#fee2e2';
    el.style.color = type==='success'?'#166534':'#991b1b';
    el.innerHTML = '<i class="fas fa-'+(type==='success'?'check-circle':'exclamation-circle')+'"></i> '+msg;
    setTimeout(function(){ el.style.display='none'; }, 4000);
}
</script>

<?php // ═══════════════════════════ EDIT ABSENSI ═══════════════════════════
elseif ($page === 'edit_absensi'):
    $today_ea  = date('Y-m-d');
    $tgl_ea    = isset($_GET['tanggal']) ? sanitize($_GET['tanggal']) : $today_ea;
    if ($tgl_ea > $today_ea) $tgl_ea = $today_ea;

    // Ambil kelas unik dari anak didik
    $kelas_anak = [];
    foreach ($anak_list as $s) {
        if (!in_array($s['kelas'], $kelas_anak)) $kelas_anak[] = $s['kelas'];
    }
    sort($kelas_anak);
    $filter_kelas_ea = isset($_GET['kelas']) ? sanitize($_GET['kelas']) : '';
    // Validasi kelas hanya boleh milik anak didik wali ini
    if ($filter_kelas_ea && !in_array($filter_kelas_ea, $kelas_anak)) $filter_kelas_ea = '';

    // Ambil data absensi
    $ea_list = [];
    if ($anak_ids) {
        $kelas_cond = $filter_kelas_ea ? "AND a.kelas='$filter_kelas_ea'" : '';
        $res_ea = $conn->query("
            SELECT a.*, s.foto
            FROM absensi a
            JOIN siswa s ON a.siswa_id = s.id
            WHERE a.tanggal='$tgl_ea' AND a.siswa_id IN ($anak_ids_str) $kelas_cond
            ORDER BY a.kelas, a.nama
        ");
        while ($r = $res_ea->fetch_assoc()) $ea_list[] = $r;
    }

    $ea_stats = ['Hadir'=>0,'Terlambat'=>0,'Sakit'=>0,'Izin'=>0,'Alpa'=>0,'Bolos'=>0];
    foreach ($ea_list as $a) { if (isset($ea_stats[$a['status']])) $ea_stats[$a['status']]++; }
?>
<style>
.ea-stat-mini{display:inline-flex;align-items:center;gap:6px;padding:5px 13px;border-radius:20px;font-size:.8rem;font-weight:700;margin:2px}
.ea-sm-hadir{background:#dcfce7;color:#15803d}.ea-sm-terlambat{background:#fef9c3;color:#854d0e}
.ea-sm-alpa{background:#fee2e2;color:#991b1b}.ea-sm-sakit{background:#dbeafe;color:#1e40af}
.ea-sm-izin{background:#ede9fe;color:#5b21b6}.ea-sm-bolos{background:#ffedd5;color:#9a3412}
#eaModal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center}
#eaModal.show{display:flex}
.ea-modal-box{background:#fff;border-radius:16px;padding:26px 28px;min-width:320px;max-width:400px;width:92%;box-shadow:0 20px 60px rgba(0,0,0,.25)}
.ea-status-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:14px}
.ea-st-opt{border:2px solid #e2e8f0;border-radius:10px;padding:10px 6px;text-align:center;cursor:pointer;font-size:.8rem;font-weight:600;color:#475569;background:#fff;transition:.15s}
.ea-st-opt:hover{transform:scale(1.04)}
.ea-st-opt.sel{color:#fff;border-color:transparent}
.ea-st-hadir{--c:#16a34a}.ea-st-terlambat{--c:#d97706}.ea-st-sakit{--c:#0891b2}
.ea-st-izin{--c:#7c3aed}.ea-st-alpa{--c:#64748b}.ea-st-bolos{--c:#dc2626}
.ea-st-opt.sel{background:var(--c);border-color:var(--c)}
.ea-edit-btn{background:#eff6ff;color:#2563eb;border:none;padding:5px 12px;border-radius:7px;font-size:.77rem;font-weight:600;cursor:pointer}
.ea-edit-btn:hover{background:#dbeafe}
#eaTable tbody tr{cursor:pointer;transition:background .12s}
#eaTable tbody tr:hover{background:#eff6ff !important}
</style>

<!-- Filter -->
<div class="card" style="margin-bottom:16px">
    <div class="card-body">
        <form method="GET" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
            <input type="hidden" name="page" value="edit_absensi">
            <div>
                <div style="font-size:.75rem;font-weight:600;color:#64748b;margin-bottom:4px">Tanggal</div>
                <input type="date" name="tanggal" value="<?= $tgl_ea ?>" max="<?= $today_ea ?>"
                       style="padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:.875rem;outline:none">
            </div>
            <div>
                <div style="font-size:.75rem;font-weight:600;color:#64748b;margin-bottom:4px">Kelas</div>
                <select name="kelas" style="padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:.875rem;background:white;outline:none">
                    <option value="">-- Semua Kelas --</option>
                    <?php foreach ($kelas_anak as $kn): ?>
                    <option value="<?= $kn ?>" <?= $filter_kelas_ea===$kn?'selected':'' ?>><?= htmlspecialchars($kn) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" style="padding:8px 18px;background:#4f46e5;color:white;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:.875rem">
                <i class="fas fa-search"></i> Tampilkan
            </button>
        </form>
    </div>
</div>

<?php if (!empty($ea_list)): ?>
<!-- Statistik mini -->
<div style="margin-bottom:12px;display:flex;flex-wrap:wrap;gap:4px;align-items:center">
    <span style="font-size:.8rem;color:#64748b;margin-right:4px"><i class="fas fa-chart-bar"></i> Rekap:</span>
    <?php
    $ea_stat_icons = ['Hadir'=>'✅','Terlambat'=>'⏰','Sakit'=>'🏥','Izin'=>'📋','Alpa'=>'❌','Bolos'=>'🚫'];
    $ea_stat_cls   = ['Hadir'=>'ea-sm-hadir','Terlambat'=>'ea-sm-terlambat','Sakit'=>'ea-sm-sakit','Izin'=>'ea-sm-izin','Alpa'=>'ea-sm-alpa','Bolos'=>'ea-sm-bolos'];
    foreach ($ea_stats as $st => $jml): if ($jml > 0): ?>
    <span class="ea-stat-mini <?= $ea_stat_cls[$st] ?>"><?= $ea_stat_icons[$st] ?> <?= $st ?>: <?= $jml ?></span>
    <?php endif; endforeach; ?>
    <span style="margin-left:8px;font-size:.8rem;color:#64748b">Total: <?= count($ea_list) ?> siswa</span>
</div>

<!-- Tabel -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-pen-square" style="color:#4f46e5"></i>
        <?php
        $hari_ea = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
        $nama_hari_ea = $hari_ea[date('l', strtotime($tgl_ea))] ?? '';
        echo $nama_hari_ea . ', ' . date('d', strtotime($tgl_ea)) . ' ' . ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'][(int)date('m', strtotime($tgl_ea))] . ' ' . date('Y', strtotime($tgl_ea));
        ?>
        <?php if ($filter_kelas_ea): ?> &mdash; Kelas <strong><?= $filter_kelas_ea ?></strong><?php endif; ?>
        <div style="margin-left:auto">
            <input type="text" id="eaSearch" placeholder="🔍 Cari nama..." oninput="eaSearchFilter()"
                   style="padding:6px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:.8rem;outline:none;width:180px">
        </div>
    </div>
    <div style="overflow-x:auto">
        <table id="eaTable" style="width:100%;border-collapse:collapse;font-size:.82rem">
            <thead>
                <tr>
                    <th style="background:#1e293b;color:white;padding:8px 6px;text-align:center;width:4%">#</th>
                    <th style="background:#1e293b;color:white;padding:8px 6px;text-align:left;width:28%">Nama Siswa</th>
                    <th style="background:#1e293b;color:white;padding:8px 6px;text-align:center;width:10%">Kelas</th>
                    <th style="background:#1e293b;color:white;padding:8px 6px;text-align:center;width:12%">Jam Masuk</th>
                    <th style="background:#1e293b;color:white;padding:8px 6px;text-align:center;width:14%">Status</th>
                    <th style="background:#1e293b;color:white;padding:8px 6px;text-align:left;width:22%">Keterangan</th>
                    <th style="background:#1e293b;color:white;padding:8px 6px;text-align:center;width:10%">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $ea_bg = ['Hadir'=>'#f0fdf4','Terlambat'=>'#fffbeb','Alpa'=>'#f8fafc','Sakit'=>'#eff6ff','Izin'=>'#f5f3ff','Bolos'=>'#fff7ed'];
            $ea_badge = [
                'Hadir'     => ['background:#dcfce7;color:#15803d'],
                'Terlambat' => ['background:#fef9c3;color:#854d0e'],
                'Alpa'      => ['background:#fee2e2;color:#991b1b'],
                'Sakit'     => ['background:#dbeafe;color:#1e40af'],
                'Izin'      => ['background:#ede9fe;color:#5b21b6'],
                'Bolos'     => ['background:#ffedd5;color:#9a3412'],
            ];
            foreach ($ea_list as $i => $a):
                $bg = $ea_bg[$a['status']] ?? '';
                $bd = $ea_badge[$a['status']][0] ?? 'background:#f1f5f9;color:#64748b';
            ?>
            <tr onclick="eaOpenEdit(<?= htmlspecialchars(json_encode($a), ENT_QUOTES) ?>)" style="background:<?= $bg ?>">
                <td style="text-align:center;padding:8px 4px"><?= $i+1 ?></td>
                <td style="padding:8px 6px">
                    <div style="display:flex;align-items:center;gap:8px">
                        <?php if (!empty($a['foto']) && file_exists('uploads/foto/'.$a['foto'])): ?>
                            <img src="<?= BASE_URL ?>uploads/foto/<?= $a['foto'] ?>" style="width:30px;height:30px;border-radius:50%;object-fit:cover;flex-shrink:0">
                        <?php else: ?>
                            <div style="width:30px;height:30px;border-radius:50%;background:#4f46e5;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.75rem;flex-shrink:0">
                                <?= strtoupper(substr($a['nama'],0,1)) ?>
                            </div>
                        <?php endif; ?>
                        <div>
                            <div style="font-weight:600"><?= htmlspecialchars($a['nama']) ?></div>
                            <div style="font-size:.72rem;color:#94a3b8"><?= $a['nis'] ?></div>
                        </div>
                    </div>
                </td>
                <td style="text-align:center;padding:8px 4px">
                    <span style="background:#eef2ff;color:#4338ca;padding:2px 8px;border-radius:12px;font-size:.75rem;font-weight:600"><?= $a['kelas'] ?></span>
                </td>
                <td style="text-align:center;padding:8px 4px;color:#64748b;font-size:.82rem">
                    <?= $a['jam_masuk'] ? date('H:i', strtotime($a['jam_masuk'])) : '<span style="color:#cbd5e1">—</span>' ?>
                </td>
                <td style="text-align:center;padding:8px 4px">
                    <span style="<?= $bd ?>;padding:3px 10px;border-radius:20px;font-size:.78rem;font-weight:700"><?= $a['status'] ?></span>
                </td>
                <td style="padding:8px 6px;font-size:.8rem;color:#475569"><?= htmlspecialchars($a['keterangan'] ?? '') ?: '<span style="color:#cbd5e1">—</span>' ?></td>
                <td style="text-align:center;padding:8px 4px">
                    <button class="ea-edit-btn" onclick="event.stopPropagation();eaOpenEdit(<?= htmlspecialchars(json_encode($a), ENT_QUOTES) ?>)">
                        <i class="fas fa-pen"></i> Edit
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php elseif (isset($_GET['tanggal'])): ?>
<div style="background:white;border-radius:12px;padding:40px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,.06)">
    <i class="fas fa-info-circle" style="font-size:2rem;color:#94a3b8;margin-bottom:12px;display:block"></i>
    <div style="color:#64748b;font-weight:600">Tidak ada data absensi pada tanggal ini<?= $filter_kelas_ea ? ' untuk kelas '.$filter_kelas_ea : '' ?>.</div>
</div>
<?php else: ?>
<div class="card"><div class="card-body" style="text-align:center;padding:60px">
    <i class="fas fa-pen-square fa-3x" style="color:#cbd5e1;margin-bottom:16px;display:block"></i>
    <div style="font-weight:700;font-size:1rem;color:#64748b;margin-bottom:8px">Pilih Tanggal</div>
    <div style="color:#94a3b8;font-size:.875rem">Pilih tanggal di atas untuk melihat dan mengedit data absensi siswa Anda</div>
</div></div>
<?php endif; ?>

<!-- Modal Edit Absensi -->
<div id="eaModal">
    <div class="ea-modal-box">
        <h3 id="eaModalNama" style="margin:0 0 4px;font-size:1rem;color:#1e293b">—</h3>
        <div id="eaModalSub" style="font-size:.78rem;color:#64748b;margin-bottom:18px">NIS • Kelas</div>

        <div style="font-size:.8rem;font-weight:600;color:#374151;margin-bottom:8px">Ubah Status:</div>
        <div class="ea-status-grid">
            <?php foreach([
                'Hadir'     => ['✅','ea-st-hadir'],
                'Terlambat' => ['⏰','ea-st-terlambat'],
                'Sakit'     => ['🏥','ea-st-sakit'],
                'Izin'      => ['📋','ea-st-izin'],
                'Alpa'      => ['❌','ea-st-alpa'],
                'Bolos'     => ['🚫','ea-st-bolos'],
            ] as $st => [$ico,$cls]): ?>
            <div class="ea-st-opt <?= $cls ?>" data-status="<?= $st ?>" onclick="eaPilihStatus('<?= $st ?>')">
                <?= $ico ?><br><?= $st ?>
            </div>
            <?php endforeach; ?>
        </div>

        <div style="font-size:.8rem;font-weight:600;color:#374151;margin-bottom:5px">Keterangan:</div>
        <input type="text" id="eaModalKet" placeholder="Opsional..."
               style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:.875rem;outline:none;margin-bottom:4px">

        <div style="display:flex;gap:8px;margin-top:16px">
            <button onclick="eaCloseModal()" style="background:#f1f5f9;color:#475569;border:none;padding:10px 16px;border-radius:8px;font-weight:600;cursor:pointer;font-size:.875rem">Batal</button>
            <button onclick="eaHapusAbsen()" style="background:#fee2e2;color:#991b1b;border:none;padding:10px 14px;border-radius:8px;font-weight:600;cursor:pointer;font-size:.875rem" title="Hapus — reset ke Belum Absen">
                <i class="fas fa-trash"></i>
            </button>
            <button onclick="eaSimpan()" style="flex:1;background:#4f46e5;color:white;border:none;padding:10px;border-radius:8px;font-weight:700;cursor:pointer;font-size:.875rem">
                <i class="fas fa-save"></i> Simpan
            </button>
        </div>
        <p style="font-size:.7rem;color:#94a3b8;margin-top:10px;text-align:center"><i class="fas fa-info-circle"></i> Hapus = siswa kembali ke Belum Absen</p>
    </div>
</div>

<script>
var eaCurrentId   = null;
var eaSelStatus   = null;
var eaCurrentRow  = null;
var eaKetDefaults = {Hadir:'',Terlambat:'Terlambat',Sakit:'Sakit',Izin:'Izin',Alpa:'Alpa',Bolos:'Bolos'};

function eaOpenEdit(data) {
    eaCurrentId  = data.id;
    eaSelStatus  = data.status;
    document.getElementById('eaModalNama').textContent = data.nama;
    document.getElementById('eaModalSub').textContent  = 'NIS: '+data.nis+' • Kelas: '+data.kelas+(data.jam_masuk?' • Jam: '+data.jam_masuk.substring(0,5):'');
    document.getElementById('eaModalKet').value = data.keterangan || '';
    document.querySelectorAll('.ea-st-opt').forEach(function(el){
        el.classList.toggle('sel', el.dataset.status === data.status);
    });
    document.getElementById('eaModal').classList.add('show');
}

function eaPilihStatus(st) {
    eaSelStatus = st;
    document.querySelectorAll('.ea-st-opt').forEach(function(el){
        el.classList.toggle('sel', el.dataset.status === st);
    });
    var ket = document.getElementById('eaModalKet');
    var defVals = Object.values(eaKetDefaults);
    if (!ket.value || defVals.indexOf(ket.value) !== -1) ket.value = eaKetDefaults[st] || '';
}

function eaCloseModal() {
    document.getElementById('eaModal').classList.remove('show');
    eaCurrentId = null; eaSelStatus = null;
}

function eaSimpan() {
    if (!eaCurrentId || !eaSelStatus) return;
    var ket = document.getElementById('eaModalKet').value;
    var fd = new FormData();
    fd.append('wali_edit_single','1');
    fd.append('absen_id', eaCurrentId);
    fd.append('new_status', eaSelStatus);
    fd.append('new_keterangan', ket);
    fetch('portal_wali.php', {method:'POST',body:fd})
        .then(function(r){return r.json();})
        .then(function(d){
            if (d.ok) {
                eaCloseModal();
                eaShowToast('Absensi berhasil diperbarui!','success');
                setTimeout(function(){ location.reload(); }, 1200);
            } else {
                eaShowToast('Gagal menyimpan perubahan.','error');
            }
        });
}

function eaHapusAbsen() {
    if (!eaCurrentId) return;
    if (!confirm('Hapus absensi ini? Siswa akan kembali ke daftar Belum Absen.')) return;
    var fd = new FormData();
    fd.append('wali_hapus_absen','1');
    fd.append('absen_id', eaCurrentId);
    fetch('portal_wali.php', {method:'POST',body:fd})
        .then(function(r){return r.json();})
        .then(function(d){
            if (d.ok) {
                eaCloseModal();
                eaShowToast('Absensi berhasil dihapus!','success');
                setTimeout(function(){ location.reload(); }, 1200);
            } else {
                eaShowToast('Gagal menghapus.','error');
            }
        });
}

document.getElementById('eaModal').addEventListener('click', function(e){
    if (e.target === this) eaCloseModal();
});

function eaSearchFilter() {
    var q = document.getElementById('eaSearch').value.toLowerCase();
    document.querySelectorAll('#eaTable tbody tr').forEach(function(tr){
        tr.style.display = tr.textContent.toLowerCase().indexOf(q) !== -1 ? '' : 'none';
    });
}

function eaShowToast(msg, type) {
    var el = document.createElement('div');
    el.textContent = msg;
    el.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:99999;padding:12px 20px;border-radius:10px;font-size:.875rem;font-weight:600;box-shadow:0 4px 20px rgba(0,0,0,.2);color:white;background:'+(type==='success'?'#10b981':'#ef4444');
    document.body.appendChild(el);
    setTimeout(function(){ el.style.transition='opacity .3s'; el.style.opacity='0'; setTimeout(function(){ el.remove(); },300); }, 3000);
}
</script>

<?php // ═══════════════════════════════ CHAT ═══════════════════════════════
elseif ($page === 'chat'): ?>

<?php if (!$siswa_ok || !$detail_siswa): ?>
    <!-- Daftar siswa untuk chat -->
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:12px">
    <?php if (!$anak_list): ?>
        <div style="grid-column:1/-1;text-align:center;padding:60px;color:#94a3b8">Belum ada siswa yang di-assign ke Anda.</div>
    <?php else: foreach($anak_list as $s):
        $unread = (int)$conn->query("SELECT COUNT(*) c FROM pesan_wali WHERE wali_id=$wid AND siswa_id={$s['id']} AND pengirim='siswa' AND dibaca=0")->fetch_assoc()['c'];
        $last_msg = $conn->query("SELECT pesan,created_at FROM pesan_wali WHERE wali_id=$wid AND siswa_id={$s['id']} ORDER BY id DESC LIMIT 1")->fetch_assoc();
    ?>
    <a href="portal_wali.php?page=chat&sid=<?= $s['id'] ?>" class="siswa-row" style="flex-direction:row">
        <div class="avatar" style="width:48px;height:48px;font-size:1.1rem;position:relative">
            <?= strtoupper(substr($s['nama'],0,1)) ?>
            <?php if ($unread>0): ?><span style="position:absolute;top:-4px;right:-4px;background:#ef4444;color:white;font-size:.6rem;width:18px;height:18px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700"><?= $unread ?></span><?php endif; ?>
        </div>
        <div style="flex:1;min-width:0">
            <div style="font-weight:700;display:flex;align-items:center;gap:6px">
                <?= htmlspecialchars($s['nama']) ?>
                <?php if ($unread>0): ?><span class="unread-dot"></span><?php endif; ?>
            </div>
            <div style="font-size:.75rem;color:#64748b">Kelas <?= $s['kelas'] ?></div>
            <?php if ($last_msg): ?>
            <div style="font-size:.75rem;color:#94a3b8;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:200px;margin-top:2px">
                <?= htmlspecialchars(mb_strimwidth($last_msg['pesan'],0,40,'...')) ?>
            </div>
            <?php endif; ?>
        </div>
        <i class="fas fa-chevron-right" style="color:#cbd5e1"></i>
    </a>
    <?php endforeach; endif; ?>
    </div>

<?php else:
    // Chat dengan siswa tertentu
    $conn->query("UPDATE pesan_wali SET dibaca=1 WHERE siswa_id=$sid_url AND wali_id=$wid AND pengirim='siswa'");
    $messages = $conn->query("SELECT * FROM pesan_wali WHERE wali_id=$wid AND siswa_id=$sid_url ORDER BY id ASC LIMIT 100");
    $last_id  = 0;
    $msgs_arr = [];
    while ($m = $messages->fetch_assoc()) { $msgs_arr[] = $m; $last_id = $m['id']; }
?>
    <div style="display:flex;flex-direction:column;height:calc(100vh - 130px)">
        <!-- Header chat -->
        <div style="display:flex;align-items:center;gap:12px;padding:14px 20px;background:white;border-radius:12px;margin-bottom:14px;box-shadow:0 2px 8px rgba(0,0,0,.06)">
            <a href="portal_wali.php?page=chat" style="color:#64748b;text-decoration:none;font-size:1.1rem"><i class="fas fa-arrow-left"></i></a>
            <div class="avatar" style="width:42px;height:42px;font-size:1rem"><?= strtoupper(substr($detail_siswa['nama'],0,1)) ?></div>
            <div>
                <div style="font-weight:700"><?= htmlspecialchars($detail_siswa['nama']) ?></div>
                <div style="font-size:.75rem;color:#64748b">Kelas <?= $detail_siswa['kelas'] ?> &bull; NIS <?= $detail_siswa['nis'] ?></div>
            </div>
        </div>

        <!-- Pesan -->
        <div id="chatBox" class="chat-messages" style="flex:1">
            <?php if (!$msgs_arr): ?>
            <div style="text-align:center;color:#94a3b8;font-size:.875rem;margin:auto">Belum ada pesan. Mulai percakapan!</div>
            <?php else: foreach($msgs_arr as $m): ?>
            <div class="bubble <?= $m['pengirim']==='wali'?'sent':'recv' ?>">
                <?= nl2br(htmlspecialchars($m['pesan'])) ?>
                <div class="time"><?= date('H:i', strtotime($m['created_at'])) ?></div>
            </div>
            <?php endforeach; endif; ?>
        </div>

        <!-- Input pesan -->
        <div class="chat-input-wrap">
            <textarea id="inputPesan" class="chat-input" rows="2" placeholder="Ketik pesan..." onkeydown="handleKey(event)"></textarea>
            <button class="btn btn-primary" onclick="kirimPesan()" id="btnKirim">
                <i class="fas fa-paper-plane"></i> Kirim
            </button>
        </div>
    </div>

<script>
var lastId = <?= $last_id ?>;
var waliSid = <?= $sid_url ?>;

function scrollBottom() {
    var box = document.getElementById('chatBox');
    if (box) box.scrollTop = box.scrollHeight;
}
scrollBottom();

function handleKey(e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); kirimPesan(); }
}

function kirimPesan() {
    var inp  = document.getElementById('inputPesan');
    var pesan = inp.value.trim();
    if (!pesan) return;
    inp.value = '';
    inp.disabled = true;
    var fd = new FormData();
    fd.append('ajax_kirim_pesan','1');
    fd.append('siswa_id', waliSid);
    fd.append('pesan', pesan);
    fetch('portal_wali.php', {method:'POST',body:fd})
        .then(function(r){return r.json();})
        .then(function(d){
            if (d.ok) {
                var box = document.getElementById('chatBox');
                var div = document.createElement('div');
                div.className = 'bubble sent';
                div.innerHTML = pesan.replace(/\n/g,'<br>') + '<div class="time">' + d.waktu + '</div>';
                box.appendChild(div);
                scrollBottom();
                // Hapus placeholder kosong
                var empty = box.querySelector('[style*="text-align:center"]');
                if (empty) empty.remove();
            }
        })
        .finally(function(){ inp.disabled=false; inp.focus(); });
}

// Poll pesan baru setiap 5 detik
setInterval(function(){
    fetch('portal_wali.php?ajax_pesan=1&sid='+waliSid+'&last_id='+lastId)
        .then(function(r){return r.json();})
        .then(function(d){
            if (d.messages && d.messages.length) {
                var box = document.getElementById('chatBox');
                d.messages.forEach(function(m){
                    if (m.pengirim === 'siswa') {
                        var div = document.createElement('div');
                        div.className = 'bubble recv';
                        div.innerHTML = m.pesan.replace(/\n/g,'<br>') + '<div class="time">' + m.created_at.substr(11,5) + '</div>';
                        box.appendChild(div);
                    }
                    lastId = Math.max(lastId, parseInt(m.id));
                });
                scrollBottom();
            }
        });
}, 5000);
</script>

<?php endif; ?>

<?php endif; ?>

<?php
// ============================================================
// === PAGE: LAPORAN REKAP HARIAN (Wali Kelas)             ===
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

    // Wali hanya melihat kelas sendiri
    $lr_kelas_wali = $wali['kelas_wali'] ?? '';

    // Kelas yang bisa dilihat wali (kelasnya sendiri + semua kelas jika kelas_wali kosong)
    $lr_kelas_list = [];
    if ($lr_kelas_wali) {
        $lr_kelas_list = [$lr_kelas_wali];
    } else {
        $kq = $conn->query("SELECT DISTINCT kelas FROM siswa ORDER BY kelas");
        while ($kr = $kq->fetch_assoc()) $lr_kelas_list[] = $kr['kelas'];
    }

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
    $lr_valid_status  = ['Alpa','Sakit','Izin','Bolos','Terlambat','Hadir'];
    if (!in_array($lr_filter_status, $lr_valid_status)) $lr_filter_status = '';
    $lr_kelas_esc = $conn->real_escape_string($lr_kelas_wali);
    $lr_kelas_sql = $lr_kelas_wali ? "AND a.kelas='$lr_kelas_esc'" : '';
    $lr_status_sql = $lr_filter_status ? "AND a.status='$lr_filter_status'" : "AND a.status IN ('Alpa','Sakit','Izin','Bolos','Terlambat','Hadir')";
    $lr_semua_q   = $conn->query("SELECT a.nis, a.nama, a.kelas, a.status, a.keterangan FROM absensi a WHERE a.tanggal='$lr_date' $lr_kelas_sql $lr_status_sql ORDER BY a.status, a.kelas, a.nama");
    $lr_rekap_semua = $lr_semua_q ? $lr_semua_q->fetch_all(MYSQLI_ASSOC) : [];

    // Export Excel
    if (isset($_GET['lr_export_excel'])) {
        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        header("Content-Disposition: attachment; filename=\"LaporanRekap_{$lr_date}".($lr_kelas_wali?"_Kelas{$lr_kelas_wali}":"")."".($lr_filter_status?"_{$lr_filter_status}":"_Semua").".xls\"");
        header("Cache-Control: max-age=0");
        echo "\xEF\xBB\xBF<table border='1'>";
        echo "<tr><th colspan='6' style='text-align:center;font-size:14pt;font-weight:bold'>".htmlspecialchars($pengaturan['nama_sekolah'])."</th></tr>";
        echo "<tr><th colspan='6' style='text-align:center'>LAPORAN REKAP HARIAN — ".strtoupper($lr_hari_nama).", {$lr_tgl} ".$lr_nb[$lr_bln]." {$lr_thn}".($lr_kelas_wali?" — KELAS: $lr_kelas_wali":"")."</th></tr>";
        echo "<tr><th>NO</th><th>NIS</th><th>NAMA SISWA</th><th>KELAS</th><th>STATUS</th><th>KETERANGAN</th></tr>";
        foreach ($lr_rekap_semua as $i => $row)
            echo "<tr><td>".($i+1)."</td><td>{$row['nis']}</td><td>{$row['nama']}</td><td>{$row['kelas']}</td><td>{$row['status']}</td><td>".($row['keterangan']??'')."</td></tr>";
        echo "</table>";
        exit;
    }
?>
<style>
.lr-stats-w{display:grid;grid-template-columns:repeat(auto-fit,minmax(100px,1fr));gap:10px;margin-bottom:20px}
.lr-stat-w{background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:12px;text-align:center}
.lr-stat-w .num{font-size:1.6rem;font-weight:900;line-height:1}
.lr-stat-w .lbl{font-size:.67rem;color:#94a3b8;margin-top:4px;font-weight:600;text-transform:uppercase;letter-spacing:.5px}
.lr-filter-w{background:#fffbeb;border:1px solid #fde68a;border-radius:12px;padding:14px;margin-bottom:18px}
.lr-filter-w label{font-size:.72rem;font-weight:700;color:#92400e;text-transform:uppercase;letter-spacing:.4px;display:block;margin-bottom:4px}
.lr-filter-w select,.lr-filter-w button{padding:8px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:.84rem;background:white;cursor:pointer}
.lr-filter-w .btn-go{background:linear-gradient(135deg,#d97706,#b45309);color:white;border:none;font-weight:700;display:inline-flex;align-items:center;gap:5px}
.lr-filter-w .btn-excel{background:linear-gradient(135deg,#16a34a,#15803d);color:white;border:none;font-weight:700;display:inline-flex;align-items:center;gap:5px}
.lr-tbl{width:100%;border-collapse:collapse;font-size:.82rem}
.lr-tbl thead th{background:#1e3a8a;color:white;padding:10px 12px;text-align:center}
.lr-tbl thead th:first-child,.lr-tbl thead th:nth-child(3){text-align:left}
.lr-tbl tbody tr:hover{background:#eff6ff}
.lr-tbl tbody td{padding:9px 12px;border-top:1px solid #f1f5f9;text-align:center;vertical-align:middle}
.lr-tbl tbody td:first-child,.lr-tbl tbody td:nth-child(3){text-align:left}
.lr-tbl tfoot td{background:#1e3a8a;color:white;padding:10px 12px;font-weight:800}
.lr-badge{display:inline-block;padding:3px 10px;border-radius:6px;font-weight:700;font-size:.78rem}
.lr-hadir{background:#dcfce7;color:#15803d}.lr-terlambat{background:#fef3c7;color:#b45309}
.lr-alpa{background:#fee2e2;color:#dc2626}.lr-sakit{background:#dbeafe;color:#1d4ed8}
.lr-izin{background:#ede9fe;color:#6d28d9}.lr-bolos{background:#ffedd5;color:#9a3412}
.lr-pbar{width:70px;height:6px;background:#e2e8f0;border-radius:3px;display:inline-block;vertical-align:middle;margin-right:5px;overflow:hidden}
.lr-pfill{height:100%;border-radius:3px;background:#15803d}
@media print{.lr-no-print-w{display:none!important}.sidebar,.top-bar{display:none!important}.main-content{margin-left:0!important}}
</style>

<div style="margin-bottom:18px">
    <div style="font-size:1.1rem;font-weight:800;color:#1e40af;display:flex;align-items:center;gap:8px;margin-bottom:4px">
        <i class="fas fa-clipboard-list" style="color:#f59e0b"></i>
        Laporan Rekap Harian
    </div>
    <div style="font-size:.85rem;color:#64748b">
        <?= $lr_hari_nama ?>, <?= $lr_tgl ?> <?= $lr_nb[$lr_bln] ?> <?= $lr_thn ?>
        <?php if($lr_kelas_wali): ?> &nbsp;|&nbsp; <strong>Kelas <?= htmlspecialchars($lr_kelas_wali) ?></strong><?php endif; ?>
    </div>
</div>

<!-- Filter -->
<div class="lr-filter-w lr-no-print-w">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
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
        <div style="display:flex;gap:8px">
            <button type="submit" class="btn-go"><i class="fas fa-search"></i> Tampilkan</button>
            <button type="submit" name="lr_export_excel" value="1" class="btn-excel"><i class="fas fa-file-excel"></i> Excel</button>
            <button type="button" onclick="window.print()" style="padding:8px 12px;background:#1d4ed8;color:white;border:none;border-radius:8px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:5px;font-size:.84rem">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </form>
</div>

<!-- Stats Ringkasan -->
<div class="lr-stats-w">
    <div class="lr-stat-w"><div class="num" style="color:#3b82f6"><?= $lr_grand['siswa'] ?></div><div class="lbl">Total Siswa</div></div>
    <div class="lr-stat-w"><div class="num" style="color:#16a34a"><?= $lr_grand['Hadir'] ?></div><div class="lbl">Hadir</div></div>
    <div class="lr-stat-w"><div class="num" style="color:#b45309"><?= $lr_grand['Terlambat'] ?></div><div class="lbl">Terlambat</div></div>
    <div class="lr-stat-w"><div class="num" style="color:#dc2626"><?= $lr_grand['Alpa'] ?></div><div class="lbl">Alpa</div></div>
    <div class="lr-stat-w"><div class="num" style="color:#1d4ed8"><?= $lr_grand['Sakit'] ?></div><div class="lbl">Sakit</div></div>
    <div class="lr-stat-w"><div class="num" style="color:#6d28d9"><?= $lr_grand['Izin'] ?></div><div class="lbl">Izin</div></div>
    <div class="lr-stat-w"><div class="num" style="color:#9a3412"><?= $lr_grand['Bolos'] ?></div><div class="lbl">Bolos</div></div>
    <div class="lr-stat-w"><div class="num" style="color:#16a34a"><?= $lr_grand_pct ?>%</div><div class="lbl">% Hadir</div></div>
</div>

<!-- Tabel Rekap Per Kelas -->
<div style="font-weight:700;font-size:.85rem;color:#1e40af;margin-bottom:8px;display:flex;align-items:center;gap:6px">
    <i class="fas fa-table"></i> Rekapitulasi Per Kelas
</div>
<div style="overflow-x:auto;border-radius:12px;border:1px solid #e2e8f0;margin-bottom:22px">
    <table class="lr-tbl">
        <thead>
            <tr>
                <th style="text-align:left;width:40px">No</th>
                <th style="text-align:left">Kelas</th>
                <th>Siswa</th>
                <th style="background:#15803d">Hadir</th>
                <th style="background:#b45309">Terlambat</th>
                <th style="background:#dc2626">Alpa</th>
                <th style="background:#1d4ed8">Sakit</th>
                <th style="background:#6d28d9">Izin</th>
                <th style="background:#9a3412">Bolos</th>
                <th>% Hadir</th>
            </tr>
        </thead>
        <tbody>
        <?php if(empty($lr_rekap_kelas)): ?>
        <tr><td colspan="10" style="text-align:center;padding:28px;color:#94a3b8">
            <i class="fas fa-inbox" style="font-size:1.5rem;display:block;margin-bottom:8px"></i>Tidak ada data
        </td></tr>
        <?php else: ?>
        <?php foreach($lr_rekap_kelas as $i => $r):
            $pc = $r['pct']>=90?'#16a34a':($r['pct']>=75?'#d97706':'#dc2626');
        ?>
        <tr>
            <td style="color:#64748b;font-weight:600"><?= $i+1 ?></td>
            <td style="font-weight:700"><i class="fas fa-door-open" style="color:#2563eb;font-size:.78rem;margin-right:4px"></i><?= htmlspecialchars($r['kelas']) ?></td>
            <td><?= $r['siswa'] ?></td>
            <td><span class="lr-badge lr-hadir"><?= $r['Hadir'] ?></span></td>
            <td><span class="lr-badge lr-terlambat"><?= $r['Terlambat'] ?></span></td>
            <td><span class="lr-badge lr-alpa"><?= $r['Alpa'] ?></span></td>
            <td><span class="lr-badge lr-sakit"><?= $r['Sakit'] ?></span></td>
            <td><span class="lr-badge lr-izin"><?= $r['Izin'] ?></span></td>
            <td><span class="lr-badge lr-bolos"><?= $r['Bolos'] ?></span></td>
            <td>
                <div class="lr-pbar"><div class="lr-pfill" style="width:<?= min($r['pct'],100) ?>%;background:<?= $pc ?>"></div></div>
                <span style="font-weight:800;font-size:.82rem;color:<?= $pc ?>"><?= $r['pct'] ?>%</span>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" style="text-align:right">TOTAL</td>
                <td><?= $lr_grand['siswa'] ?></td>
                <td><?= $lr_grand['Hadir'] ?></td>
                <td><?= $lr_grand['Terlambat'] ?></td>
                <td><?= $lr_grand['Alpa'] ?></td>
                <td><?= $lr_grand['Sakit'] ?></td>
                <td><?= $lr_grand['Izin'] ?></td>
                <td><?= $lr_grand['Bolos'] ?></td>
                <td style="color:#86efac"><?= $lr_grand_pct ?>%</td>
            </tr>
        </tfoot>
    </table>
</div>

<!-- Tabel Detail Siswa -->
<div style="font-weight:700;font-size:.85rem;color:#1e40af;margin-bottom:8px;display:flex;align-items:center;gap:6px">
    <i class="fas fa-layer-group"></i> Rekap Detail Siswa
    <?php if($lr_filter_status): ?>
    <span style="background:#fef3c7;color:#92400e;padding:2px 8px;border-radius:6px;font-size:.72rem;margin-left:6px">Status: <?= $lr_filter_status ?></span>
    <?php endif; ?>
</div>
<div style="overflow-x:auto;border-radius:12px;border:1px solid #e2e8f0">
    <table class="lr-tbl">
        <thead>
            <tr>
                <th style="text-align:center;width:44px">No</th>
                <th style="text-align:left">NIS</th>
                <th style="text-align:left">Nama Siswa</th>
                <th>Kelas</th>
                <th>Status</th>
                <th style="text-align:left">Keterangan</th>
            </tr>
        </thead>
        <tbody>
        <?php if(empty($lr_rekap_semua)): ?>
        <tr><td colspan="6" style="text-align:center;padding:28px;color:#94a3b8">
            <i class="fas fa-inbox" style="font-size:1.5rem;display:block;margin-bottom:8px"></i>Tidak ada data
        </td></tr>
        <?php else:
        $lr_bg_map = ['Alpa'=>'#fff5f5','Sakit'=>'#eff6ff','Izin'=>'#f5f3ff','Bolos'=>'#fff7ed','Hadir'=>'#f0fdf4','Terlambat'=>'#fffbeb'];
        $lr_bm = ['Hadir'=>'lr-hadir','Terlambat'=>'lr-terlambat','Alpa'=>'lr-alpa','Sakit'=>'lr-sakit','Izin'=>'lr-izin','Bolos'=>'lr-bolos'];
        $lr_prev = '';
        foreach ($lr_rekap_semua as $ri => $rrow):
            if ($rrow['status'] !== $lr_prev && $lr_prev !== ''):?>
        <tr><td colspan="6" style="padding:0;height:4px;background:#f1f5f9"></td></tr>
        <?php endif; $lr_prev = $rrow['status']; ?>
        <tr style="background:<?= $lr_bg_map[$rrow['status']] ?? '#fff' ?>;border-top:1px solid #f8fafc">
            <td style="text-align:center;color:#94a3b8;font-weight:600"><?= $ri+1 ?></td>
            <td style="color:#64748b;font-size:.78rem"><?= htmlspecialchars($rrow['nis']) ?></td>
            <td style="font-weight:700"><?= htmlspecialchars($rrow['nama']) ?></td>
            <td><span style="background:#e2e8f0;color:#334155;padding:2px 8px;border-radius:6px;font-size:.78rem;font-weight:600"><?= htmlspecialchars($rrow['kelas']) ?></span></td>
            <td><span class="lr-badge <?= $lr_bm[$rrow['status']] ?? '' ?>"><?= $rrow['status'] ?></span></td>
            <td style="color:#64748b;font-size:.8rem"><?= htmlspecialchars($rrow['keterangan'] ?? '-') ?></td>
        </tr>
        <?php endforeach; ?>
        <tr style="background:#1e3a8a;color:white;font-weight:800">
            <td colspan="2" style="text-align:right">TOTAL</td>
            <td colspan="4"><?= count($lr_rekap_semua) ?> siswa<?= $lr_filter_status?" — Status: <strong>{$lr_filter_status}</strong>":" — Semua Status" ?></td>
        </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Tanda Tangan (print) -->
<div style="margin-top:40px;display:flex;justify-content:flex-end;padding-right:30px">
    <div style="text-align:center;min-width:200px">
        <div><?= htmlspecialchars($pengaturan['nama_sekolah'] ?? '') ?>, <?= $lr_tgl.' '.$lr_nb[$lr_bln].' '.$lr_thn ?></div>
        <div style="margin-top:4px">Wali Kelas<?= $lr_kelas_wali?" {$lr_kelas_wali}":"" ?>,</div>
        <div style="margin-top:65px;font-weight:700;text-decoration:underline"><?= htmlspecialchars($wali['nama']) ?></div>
    </div>
</div>

<?php endif; // end laporan_rekap ?>
</div><!-- /main -->

<script>
// Jam realtime
function updateJam() {
    var now = new Date();
    var h = String(now.getHours()).padStart(2,'0');
    var m = String(now.getMinutes()).padStart(2,'0');
    var s = String(now.getSeconds()).padStart(2,'0');
    var el = document.getElementById('jam');
    if (el) el.textContent = h+':'+m+':'+s;
}
updateJam(); setInterval(updateJam, 1000);
</script>
</body>
</html>
