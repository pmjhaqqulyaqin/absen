<?php
require_once 'includes/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['disiplin_login'])) { header('Location: portal_disiplin_login.php'); exit; }

if (isset($_GET['logout'])) { unset($_SESSION['disiplin_login']); header('Location: portal_disiplin_login.php'); exit; }

$pengaturan = get_pengaturan();
$today = date('Y-m-d');
$kelas_list = get_kelas_list();

// ── AJAX: Simpan pelanggaran ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_simpan_pel'])) {
    header('Content-Type: application/json');
    $siswa_id  = (int)($_POST['siswa_id'] ?? 0);
    $jenis_id  = (int)($_POST['jenis_id'] ?? 0);
    $tanggal   = $conn->real_escape_string($_POST['tanggal'] ?? $today);
    $ket       = $conn->real_escape_string(trim($_POST['keterangan'] ?? ''));

    if ($siswa_id && $jenis_id && $tanggal <= $today) {
        $s    = $conn->query("SELECT nis,nama,kelas FROM siswa WHERE id=$siswa_id AND aktif=1")->fetch_assoc();
        $j    = $conn->query("SELECT nama FROM pelanggaran_jenis WHERE id=$jenis_id AND aktif=1")->fetch_assoc();
        if ($s && $j) {
            $nis   = $conn->real_escape_string($s['nis']);
            $nama  = $conn->real_escape_string($s['nama']);
            $kelas = $conn->real_escape_string($s['kelas']);
            $jnama = $conn->real_escape_string($j['nama']);
            $conn->query("INSERT INTO pelanggaran (siswa_id,nis,nama,kelas,tanggal,jenis_id,jenis_nama,keterangan,input_oleh)
                VALUES ($siswa_id,'$nis','$nama','$kelas','$tanggal',$jenis_id,'$jnama','$ket','Petugas Disiplin')");
            $insert_id = $conn->insert_id;
            echo json_encode(['ok'=>true,'id'=>$insert_id,'jenis'=>$j['nama'],'msg'=>$j['nama'].' berhasil dicatat']);
        } else {
            echo json_encode(['ok'=>false,'msg'=>'Data siswa atau jenis tidak valid']);
        }
    } else {
        echo json_encode(['ok'=>false,'msg'=>'Data tidak lengkap atau tanggal tidak valid']);
    }
    exit;
}

// ── AJAX: Hapus pelanggaran ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_hapus_pel'])) {
    header('Content-Type: application/json');
    $pid = (int)($_POST['pel_id'] ?? 0);
    if ($pid) {
        $conn->query("DELETE FROM pelanggaran WHERE id=$pid");
        echo json_encode(['ok'=>true]);
    } else {
        echo json_encode(['ok'=>false]);
    }
    exit;
}

// ── AJAX: Edit keterangan pelanggaran ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_edit_ket'])) {
    header('Content-Type: application/json');
    $pid = (int)($_POST['pel_id'] ?? 0);
    $ket = $conn->real_escape_string(trim($_POST['keterangan'] ?? ''));
    if ($pid) {
        $conn->query("UPDATE pelanggaran SET keterangan='$ket' WHERE id=$pid");
        echo json_encode(['ok'=>true]);
    } else {
        echo json_encode(['ok'=>false]);
    }
    exit;
}

// ── AJAX: Ambil pelanggaran hari ini per siswa ───────────────────────
if (isset($_GET['ajax_pel_siswa'])) {
    header('Content-Type: application/json');
    $sid  = (int)($_GET['sid'] ?? 0);
    $tgl  = $conn->real_escape_string($_GET['tgl'] ?? $today);
    $rows = [];
    if ($sid) {
        $res = $conn->query("SELECT p.id, pj.nama as jenis, p.keterangan, p.created_at
            FROM pelanggaran p
            LEFT JOIN pelanggaran_jenis pj ON pj.id = p.jenis_id
            WHERE p.siswa_id=$sid AND p.tanggal='$tgl'
            ORDER BY p.id ASC");
        while ($r = $res->fetch_assoc()) $rows[] = $r;
    }
    echo json_encode(['data'=>$rows]);
    exit;
}

// Page params
$kelas_sel = isset($_GET['kelas']) ? sanitize($_GET['kelas']) : ($kelas_list[0] ?? '');
$tanggal   = isset($_GET['tanggal']) ? sanitize($_GET['tanggal']) : $today;
if ($tanggal > $today) $tanggal = $today;

// Ambil jenis pelanggaran aktif
$jenis_list_arr = [];
$jr = $conn->query("SELECT * FROM pelanggaran_jenis WHERE aktif=1 ORDER BY urutan,id");
while ($j = $jr->fetch_assoc()) $jenis_list_arr[] = $j;

// Ambil siswa sesuai kelas
$siswa_arr = [];
if ($kelas_sel) {
    $res = $conn->query("SELECT id,nis,nama,kelas,foto FROM siswa WHERE kelas='$kelas_sel' AND aktif=1 ORDER BY nama");
    while ($s = $res->fetch_assoc()) $siswa_arr[] = $s;
}

// Ambil ringkasan pelanggaran hari ini untuk kelas ini
$pel_today_map = [];
if ($siswa_arr && $kelas_sel) {
    $ids_str = implode(',', array_column($siswa_arr,'id'));
    $rp = $conn->query("SELECT siswa_id, jenis_id, COUNT(*) as c FROM pelanggaran WHERE tanggal='$tanggal' AND siswa_id IN ($ids_str) GROUP BY siswa_id, jenis_id");
    while ($r = $rp->fetch_assoc()) {
        $pel_today_map[$r['siswa_id']][$r['jenis_id']] = (int)$r['c'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Portal Disiplin — <?= htmlspecialchars($pengaturan['nama_sekolah']) ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',sans-serif;background:#0f172a;color:white;min-height:100vh}

.navbar{background:rgba(15,23,42,.97);backdrop-filter:blur(10px);border-bottom:1px solid rgba(255,255,255,.08);padding:0 20px;height:60px;display:flex;align-items:center;gap:10px;position:sticky;top:0;z-index:100}
.navbar .title{font-weight:800;font-size:.95rem}
.navbar .sub{font-size:.7rem;color:#94a3b8}
.btn-n{padding:7px 14px;border-radius:8px;border:none;font-size:.78rem;font-weight:700;cursor:pointer;color:white;text-decoration:none;display:inline-flex;align-items:center;gap:5px}
.btn-danger-n{background:#dc2626}
.btn-warning-n{background:#f59e0b}

.content{max-width:1100px;margin:0 auto;padding:20px}
.filter-bar{display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:14px;padding:16px;margin-bottom:20px}
.filter-bar label{font-size:.75rem;font-weight:600;color:#94a3b8;display:block;margin-bottom:4px}
.filter-bar select,.filter-bar input{padding:8px 12px;border-radius:8px;border:1px solid rgba(255,255,255,.15);background:rgba(255,255,255,.08);color:white;font-size:.875rem;outline:none}
.filter-bar select option{background:#1e293b;color:white}
.btn-filter{padding:8px 18px;background:#3b82f6;border:none;border-radius:8px;color:white;font-weight:700;cursor:pointer;font-size:.875rem}

/* ── TABLE LAYOUT ── */
.wrap{padding:20px;max-width:100%;overflow-x:auto}
.tbl-disiplin{width:100%;border-collapse:separate;border-spacing:0;background:#1e293b;border-radius:14px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.4)}
.tbl-disiplin thead tr th{padding:11px 14px;font-size:.75rem;font-weight:800;text-transform:uppercase;letter-spacing:.5px;white-space:nowrap;border-bottom:2px solid rgba(255,255,255,.1)}
.tbl-disiplin thead .th-no{background:#0f172a;color:#475569;text-align:center;width:36px;min-width:36px;max-width:36px;padding:11px 6px}
.tbl-disiplin thead .th-nama{background:#0f172a;color:#94a3b8;text-align:left;min-width:180px}
.tbl-disiplin thead .th-jenis{text-align:center;min-width:110px;color:white}
.tbl-disiplin thead .th-aksi{background:#0f172a;color:#64748b;text-align:center;width:90px;min-width:90px}
.tbl-disiplin tbody tr{border-bottom:1px solid rgba(255,255,255,.05);transition:.15s}
.tbl-disiplin tbody tr:last-child{border-bottom:none}
.tbl-disiplin tbody tr:hover{background:rgba(255,255,255,.04)}
.tbl-disiplin tbody tr:nth-child(even){background:rgba(255,255,255,.025)}
.tbl-disiplin tbody tr:nth-child(even):hover{background:rgba(255,255,255,.05)}

/* Sel nama */
.td-nama{padding:8px 14px;font-weight:700;font-size:.85rem;color:#e2e8f0;white-space:nowrap}
.td-nama .nis{font-size:.68rem;color:#64748b;font-weight:400}
.td-no{padding:6px 4px;font-size:.72rem;color:#475569;text-align:center;font-weight:600;width:36px}

/* Sel pelanggaran — tombol klik */
.td-pel{padding:5px 8px;text-align:center}
.pel-btn{display:inline-flex;flex-direction:column;align-items:center;justify-content:center;width:100%;min-width:52px;max-width:70px;padding:6px 4px;border:none;border-radius:8px;cursor:pointer;font-weight:800;color:white;transition:.15s;user-select:none;gap:1px}
.pel-btn:hover{filter:brightness(1.2);transform:scale(1.04);box-shadow:0 4px 12px rgba(0,0,0,.4)}
.pel-btn:active{transform:scale(.96)}
.pel-btn:disabled{opacity:.5;cursor:not-allowed;transform:none}
.pel-btn .pbl-nama{font-size:.72rem;font-weight:700;opacity:.9;text-transform:uppercase;letter-spacing:.3px}
.pel-btn .pbl-cnt{font-size:1rem;font-weight:900;line-height:1}
.pel-btn .pbl-cnt.zero{opacity:.4;font-size:.95rem}

/* Warna per kolom (berulang) */
.jc-0{background:#f59e0b} .jc-0:hover{background:#d97706}
.jc-1{background:#22c55e} .jc-1:hover{background:#16a34a}
.jc-2{background:#ef4444} .jc-2:hover{background:#dc2626}
.jc-3{background:#3b82f6} .jc-3:hover{background:#2563eb}
.jc-4{background:#a855f7} .jc-4:hover{background:#9333ea}
.jc-5{background:#06b6d4} .jc-5:hover{background:#0891b2}
.jc-6{background:#ec4899} .jc-6:hover{background:#db2777}
.jc-7{background:#84cc16} .jc-7:hover{background:#65a30d}

/* Header warna sesuai kolom */
.th-jc-0{background:#f59e0b!important} .th-jc-1{background:#22c55e!important}
.th-jc-2{background:#ef4444!important} .th-jc-3{background:#3b82f6!important}
.th-jc-4{background:#a855f7!important} .th-jc-5{background:#06b6d4!important}
.th-jc-6{background:#ec4899!important} .th-jc-7{background:#84cc16!important}

/* Aksi buttons */
.td-aksi{padding:5px 6px;text-align:center;white-space:nowrap}
.btn-detail{padding:5px 9px;border-radius:6px;border:1px solid rgba(96,165,250,.4);background:rgba(96,165,250,.1);color:#60a5fa;font-size:.72rem;cursor:pointer;transition:.15s;display:inline-flex;align-items:center;gap:3px}
.btn-detail:hover{background:rgba(96,165,250,.25);border-color:#60a5fa;color:white}
.btn-edit{padding:5px 9px;border-radius:6px;border:1px solid rgba(52,211,153,.4);background:rgba(52,211,153,.1);color:#34d399;font-size:.72rem;cursor:pointer;transition:.15s;display:inline-flex;align-items:center;gap:3px;margin-left:4px}
.btn-edit:hover{background:rgba(52,211,153,.25);border-color:#34d399;color:white}

/* Modal detail */
#modalDetail,#modalEdit{display:none;position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:9999;justify-content:center;align-items:center}
#modalDetail.show,#modalEdit.show{display:flex}
.modal-box{background:#1e293b;border-radius:18px;width:100%;max-width:440px;margin:16px;box-shadow:0 20px 60px rgba(0,0,0,.5);border:1px solid rgba(255,255,255,.1)}
.modal-head{padding:16px 20px;border-bottom:1px solid rgba(255,255,255,.08);display:flex;align-items:center;justify-content:space-between}
.modal-head h3{font-size:.95rem;font-weight:700}
.modal-close{background:none;border:none;color:#94a3b8;font-size:1.4rem;cursor:pointer;line-height:1}
.modal-body{padding:16px 20px;max-height:50vh;overflow-y:auto}
.pel-item{display:flex;align-items:center;justify-content:space-between;padding:8px 10px;border-radius:8px;background:rgba(255,255,255,.04);margin-bottom:6px;font-size:.82rem}
.pel-item .jenis-tag{background:rgba(245,158,11,.2);color:#f59e0b;padding:2px 10px;border-radius:12px;font-size:.75rem;font-weight:700}
.pel-item .ket{color:#94a3b8;font-size:.75rem;margin-top:2px}
.btn-hapus-pel{background:rgba(239,68,68,.15);color:#f87171;border:none;padding:4px 10px;border-radius:6px;font-size:.72rem;cursor:pointer}
.btn-hapus-pel:hover{background:rgba(239,68,68,.3)}
.modal-foot{padding:14px 20px;border-top:1px solid rgba(255,255,255,.08)}

/* Toast */
.toast{position:fixed;bottom:24px;right:24px;z-index:99999;padding:12px 18px;border-radius:10px;font-size:.85rem;font-weight:600;color:white;box-shadow:0 8px 24px rgba(0,0,0,.3);display:flex;align-items:center;gap:8px;transition:opacity .3s}
.toast.success{background:linear-gradient(135deg,#16a34a,#15803d)}
.toast.error{background:linear-gradient(135deg,#dc2626,#b91c1c)}
</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
    <?php
    $logo_file = defined('LOGO_FILE') ? LOGO_FILE : ($pengaturan['logo'] ?? '');
    if (!empty($logo_file) && file_exists(__DIR__.'/uploads/logo/'.$logo_file)): ?>
    <img src="<?= BASE_URL ?>uploads/logo/<?= $logo_file ?>" style="width:34px;height:34px;border-radius:8px;object-fit:contain;background:white;padding:2px" alt="Logo">
    <?php else: ?>
    <div style="width:34px;height:34px;background:linear-gradient(135deg,#f59e0b,#d97706);border-radius:8px;display:flex;align-items:center;justify-content:center">
        <i class="fas fa-shield-alt"></i>
    </div>
    <?php endif; ?>
    <div>
        <div class="title"><i class="fas fa-shield-alt" style="color:#f59e0b"></i> Portal Disiplin Sekolah</div>
        <div class="sub"><?= htmlspecialchars($pengaturan['nama_sekolah']) ?></div>
    </div>
    <div style="margin-left:auto;display:flex;align-items:center;gap:8px">
        <span style="font-size:.75rem;color:#64748b" id="jamNav"></span>
        <a href="?logout=1" class="btn-n btn-danger-n"><i class="fas fa-sign-out-alt"></i> Keluar</a>
    </div>
</nav>

<div class="content">

    <!-- Filter -->
    <div class="filter-bar">
        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;width:100%">
            <div>
                <label>Tanggal</label>
                <input type="date" name="tanggal" value="<?= $tanggal ?>" max="<?= $today ?>">
            </div>
            <div>
                <label>Kelas</label>
                <select name="kelas">
                    <option value="">-- Pilih Kelas --</option>
                    <?php foreach ($kelas_list as $k): ?>
                    <option value="<?= $k ?>" <?= $kelas_sel==$k?'selected':'' ?>><?= $k ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn-filter"><i class="fas fa-search"></i> Tampilkan</button>
        </form>
    </div>

    <?php if (empty($jenis_list_arr)): ?>
    <div style="background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.3);border-radius:12px;padding:20px;text-align:center;color:#fde68a;margin-bottom:20px">
        <i class="fas fa-exclamation-triangle" style="font-size:1.5rem;margin-bottom:8px;display:block"></i>
        <strong>Belum ada jenis pelanggaran.</strong><br>
        <span style="font-size:.85rem">Silakan tambahkan jenis pelanggaran di Admin → Kelola Pelanggaran terlebih dahulu.</span>
    </div>
    <?php endif; ?>

    <?php if (!$kelas_sel): ?>
    <div style="text-align:center;padding:60px;color:#64748b">
        <i class="fas fa-users fa-3x" style="margin-bottom:16px;display:block;opacity:.3"></i>
        <div style="font-size:1rem;font-weight:600">Pilih kelas terlebih dahulu</div>
    </div>

    <?php elseif (empty($siswa_arr)): ?>
    <div style="text-align:center;padding:60px;color:#64748b">Tidak ada siswa di kelas <?= $kelas_sel ?>.</div>

    <?php else: ?>
    <!-- Info bar -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:8px">
        <div style="font-weight:700;font-size:.9rem">
            <i class="fas fa-users" style="color:#f59e0b"></i>
            Kelas <?= htmlspecialchars($kelas_sel) ?> &mdash; <?= count($siswa_arr) ?> Siswa
            <span style="font-size:.75rem;color:#64748b;font-weight:400;margin-left:6px">
                <?= date('d',strtotime($tanggal)).' '.['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'][(int)date('m',strtotime($tanggal))].' '.date('Y',strtotime($tanggal)) ?>
            </span>
        </div>
        <div style="font-size:.75rem;color:#94a3b8">Klik sel berwarna untuk mencatat pelanggaran</div>
    </div>

    <!-- LEGENDA KODE -->
    <?php if (!empty($jenis_list_arr)): ?>
    <div style="background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);border-radius:10px;padding:10px 16px;margin-bottom:12px;display:flex;flex-wrap:wrap;gap:10px;align-items:center">
        <span style="font-size:.72rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.05em">Keterangan:</span>
        <?php $ci=0; foreach ($jenis_list_arr as $j): ?>
        <span style="display:inline-flex;align-items:center;gap:5px;font-size:.78rem">
            <span style="background:<?= ['#f59e0b','#10b981','#ef4444','#3b82f6','#8b5cf6','#06b6d4','#ec4899','#f97316'][$ci%8] ?>;color:white;padding:2px 8px;border-radius:6px;font-weight:800;font-size:.75rem;letter-spacing:.03em">
                <?= htmlspecialchars($j['kode'] ?: $j['nama']) ?>
            </span>
            <span style="color:#cbd5e1;font-size:.75rem">: <?= htmlspecialchars($j['nama']) ?></span>
        </span>
        <?php $ci++; endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- TABEL DISIPLIN -->
    <div style="overflow-x:auto;border-radius:14px">
    <table class="tbl-disiplin">
        <thead>
            <tr>
                <th class="th-no" style="width:36px;min-width:36px;padding:11px 4px">#</th>
                <th class="th-nama">NAMA SISWA</th>
                <?php
                $ci = 0;
                foreach ($jenis_list_arr as $j): ?>
                <th class="th-jenis th-jc-<?= $ci % 8 ?>" title="<?= htmlspecialchars($j['nama']) ?>"
                    style="min-width:60px;max-width:70px;width:65px;padding:8px 4px">
                    <?= htmlspecialchars(strtoupper($j['kode'] ?: $j['nama'])) ?>
                </th>
                <?php $ci++; endforeach; ?>
                <th class="th-aksi">AKSI</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $no = 1;
        foreach ($siswa_arr as $s):
            $total_pel = 0;
            foreach (($pel_today_map[$s['id']] ?? []) as $c) $total_pel += $c;
        ?>
        <tr id="sc-<?= $s['id'] ?>">
            <td class="td-no"><?= $no++ ?></td>
            <td class="td-nama">
                <?= htmlspecialchars($s['nama']) ?>
                <div class="nis">NIS: <?= $s['nis'] ?></div>
            </td>
            <?php
            $ci2 = 0;
            foreach ($jenis_list_arr as $j):
                $cnt = $pel_today_map[$s['id']][$j['id']] ?? 0;
            ?>
            <td class="td-pel">
                <button class="pel-btn jc-<?= $ci2 % 8 ?>" id="jr-<?= $s['id'] ?>-<?= $j['id'] ?>"
                        onclick="catatPel(<?= $s['id'] ?>,<?= $j['id'] ?>,'<?= htmlspecialchars(addslashes($j['nama'])) ?>','<?= $tanggal ?>')"
                        title="Catat <?= htmlspecialchars($j['nama']) ?> untuk <?= htmlspecialchars($s['nama']) ?>">
                    <span class="pbl-cnt <?= $cnt>0?'':'zero' ?>" id="cnt-<?= $s['id'] ?>-<?= $j['id'] ?>"><?= $cnt ?: '0' ?></span>
                    <span class="pbl-nama" style="font-size:.8rem;font-weight:800;letter-spacing:.03em"><?= htmlspecialchars($j['kode'] ?: $j['nama']) ?></span>
                </button>
            </td>
            <?php $ci2++; endforeach; ?>
            <td class="td-aksi">
                <button class="btn-detail" onclick="bukaDetail(<?= $s['id'] ?>,'<?= htmlspecialchars(addslashes($s['nama'])) ?>','<?= $tanggal ?>')">
                    <i class="fas fa-list-ul"></i> Detail
                </button>
                <button class="btn-edit" onclick="bukaEdit(<?= $s['id'] ?>,'<?= htmlspecialchars(addslashes($s['nama'])) ?>','<?= $tanggal ?>')">
                    <i class="fas fa-pen"></i> Edit
                </button>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Detail Pelanggaran -->
<div id="modalDetail">
    <div class="modal-box">
        <div class="modal-head">
            <div>
                <h3 id="mdNama">—</h3>
                <div style="font-size:.75rem;color:#94a3b8" id="mdSub">Detail pelanggaran hari ini</div>
            </div>
            <button class="modal-close" onclick="tutupDetail()">&times;</button>
        </div>
        <div class="modal-body" id="mdBody">
            <div style="text-align:center;color:#64748b;padding:20px">Memuat...</div>
        </div>
        <div class="modal-foot" style="text-align:right">
            <button onclick="tutupDetail()" style="padding:8px 18px;border-radius:8px;border:1px solid rgba(255,255,255,.15);background:transparent;color:white;cursor:pointer;font-size:.875rem">
                Tutup
            </button>
        </div>
    </div>
</div>

<!-- Modal Edit Pelanggaran -->
<div id="modalEdit">
    <div class="modal-box" style="max-width:480px">
        <div class="modal-head" style="background:linear-gradient(135deg,rgba(52,211,153,.15),rgba(52,211,153,.05))">
            <div>
                <h3 id="meNama" style="color:#34d399"><i class="fas fa-pen" style="font-size:.85rem;margin-right:6px"></i>Edit Pelanggaran</h3>
                <div style="font-size:.75rem;color:#94a3b8" id="meSub"></div>
            </div>
            <button class="modal-close" onclick="tutupEdit()">&times;</button>
        </div>
        <div class="modal-body" id="meBody" style="padding:16px 20px">
            <!-- List catatan + form tambah -->
        </div>
        <div class="modal-foot" style="text-align:right">
            <button onclick="tutupEdit()" style="padding:8px 18px;border-radius:8px;border:1px solid rgba(255,255,255,.15);background:transparent;color:white;cursor:pointer;font-size:.875rem">
                Tutup
            </button>
        </div>
    </div>
</div>

<script>
var mdSiswaId = null, mdTanggal = null;
var meSiswaId = null, meTanggal = null;

// ── Catat pelanggaran ────────────────────────────────────────────────
function catatPel(sid, jid, jnama, tgl) {
    var btnEl = document.getElementById('jr-'+sid+'-'+jid);
    if (btnEl) { btnEl.style.opacity='.5'; btnEl.disabled=true; }

    var fd = new FormData();
    fd.append('ajax_simpan_pel','1');
    fd.append('siswa_id', sid);
    fd.append('jenis_id', jid);
    fd.append('tanggal', tgl);
    fd.append('keterangan', '');

    fetch('portal_disiplin.php', {method:'POST',body:fd})
        .then(function(r){return r.json();})
        .then(function(d){
            if (btnEl) { btnEl.style.opacity=''; btnEl.disabled=false; }
            if (d.ok) {
                var badge = document.getElementById('cnt-'+sid+'-'+jid);
                if (badge) {
                    var n = (parseInt(badge.textContent)||0) + 1;
                    badge.textContent = n;
                    badge.classList.remove('zero');
                }
                showToast('✅ ' + d.msg, 'success');
                if (mdSiswaId==sid) loadDetail(sid, mdTanggal);
                if (meSiswaId==sid) loadEdit(sid, meTanggal);
            } else {
                showToast('❌ ' + d.msg, 'error');
            }
        })
        .catch(function(){ if(btnEl){btnEl.style.opacity='';btnEl.disabled=false;} });
}

// ── Modal DETAIL ──────────────────────────────────────────────────────
function bukaDetail(sid, nama, tgl) {
    mdSiswaId = sid; mdTanggal = tgl;
    document.getElementById('mdNama').textContent = nama;
    document.getElementById('mdSub').textContent  = 'Pelanggaran tanggal ' + tgl;
    document.getElementById('modalDetail').classList.add('show');
    loadDetail(sid, tgl);
}
function tutupDetail() {
    document.getElementById('modalDetail').classList.remove('show');
    mdSiswaId = null;
}
document.getElementById('modalDetail').addEventListener('click', function(e){ if(e.target===this) tutupDetail(); });

function loadDetail(sid, tgl) {
    var body = document.getElementById('mdBody');
    body.innerHTML = '<div style="text-align:center;color:#64748b;padding:24px"><i class="fas fa-spinner fa-spin"></i> Memuat...</div>';
    fetch('portal_disiplin.php?ajax_pel_siswa=1&sid='+encodeURIComponent(sid)+'&tgl='+encodeURIComponent(tgl))
        .then(function(r){ return r.json(); })
        .then(function(d){
            if (!d.data || !d.data.length) {
                body.innerHTML = '<div style="text-align:center;color:#64748b;padding:28px"><i class="fas fa-check-circle" style="font-size:2rem;opacity:.3;display:block;margin-bottom:8px"></i>Belum ada pelanggaran tercatat.</div>';
                return;
            }
            var html = '';
            d.data.forEach(function(p){
                html += '<div class="pel-item">'
                    + '<div><span class="jenis-tag">' + escH(p.jenis) + '</span>'
                    + (p.keterangan ? '<div class="ket">' + escH(p.keterangan) + '</div>' : '<div class="ket" style="font-style:italic;color:#475569">—</div>') + '</div>'
                    + '<div style="display:flex;align-items:center;gap:6px">'
                    + '<span style="font-size:.7rem;color:#64748b">' + (p.created_at||'').substr(11,5) + '</span>'
                    + '<button class="btn-hapus-pel" onclick="hapusPel('+p.id+','+sid+')"><i class="fas fa-trash"></i></button>'
                    + '</div></div>';
            });
            body.innerHTML = html;
        })
        .catch(function(){ body.innerHTML='<div style="text-align:center;color:#f87171;padding:20px">Gagal memuat data.</div>'; });
}

function hapusPel(pid, sid) {
    if (!confirm('Hapus catatan pelanggaran ini?')) return;
    var fd = new FormData();
    fd.append('ajax_hapus_pel','1');
    fd.append('pel_id', pid);
    fetch('portal_disiplin.php', {method:'POST',body:fd})
        .then(function(r){ return r.json(); })
        .then(function(d){
            if (d.ok) {
                showToast('Pelanggaran dihapus','success');
                loadDetail(sid, mdTanggal);
                setTimeout(function(){ location.reload(); }, 1200);
            }
        });
}

// ── Modal EDIT ────────────────────────────────────────────────────────
function bukaEdit(sid, nama, tgl) {
    meSiswaId = sid; meTanggal = tgl;
    document.getElementById('meNama').innerHTML = '<i class="fas fa-pen" style="font-size:.85rem;margin-right:6px"></i>Edit Pelanggaran';
    document.getElementById('meSub').textContent = nama + ' — ' + tgl;
    document.getElementById('modalEdit').classList.add('show');
    loadEdit(sid, tgl);
}
function tutupEdit() {
    document.getElementById('modalEdit').classList.remove('show');
    meSiswaId = null;
}
document.getElementById('modalEdit').addEventListener('click', function(e){ if(e.target===this) tutupEdit(); });

function loadEdit(sid, tgl) {
    var body = document.getElementById('meBody');
    body.innerHTML = '<div style="text-align:center;color:#64748b;padding:24px"><i class="fas fa-spinner fa-spin"></i> Memuat...</div>';
    fetch('portal_disiplin.php?ajax_pel_siswa=1&sid='+encodeURIComponent(sid)+'&tgl='+encodeURIComponent(tgl))
        .then(function(r){ return r.json(); })
        .then(function(d){
            var html = '';
            if (!d.data || !d.data.length) {
                html = '<div style="text-align:center;color:#64748b;padding:20px;font-size:.85rem">Belum ada catatan pelanggaran hari ini.</div>';
            } else {
                html += '<div style="font-size:.75rem;color:#94a3b8;margin-bottom:10px;font-weight:600">CATATAN PELANGGARAN — klik ✏️ untuk edit keterangan, 🗑️ untuk hapus</div>';
                d.data.forEach(function(p){
                    html += '<div class="pel-item" id="peli-'+p.id+'">'
                        + '<div style="flex:1;min-width:0">'
                        + '<span class="jenis-tag">' + escH(p.jenis) + '</span>'
                        + '<div id="ket-disp-'+p.id+'" class="ket" style="margin-top:4px;cursor:pointer" onclick="editKet('+p.id+')" title="Klik untuk edit">'
                        + (p.keterangan ? escH(p.keterangan) : '<span style="color:#475569;font-style:italic">Klik untuk tambah keterangan...</span>')
                        + '</div>'
                        + '<div id="ket-form-'+p.id+'" style="display:none;margin-top:6px">'
                        + '<input type="text" id="ket-inp-'+p.id+'" style="width:100%;padding:6px 10px;background:rgba(255,255,255,.08);border:1px solid rgba(52,211,153,.4);border-radius:6px;color:white;font-size:.8rem;outline:none" placeholder="Keterangan..." value="'+escAttr(p.keterangan)+'">'
                        + '<div style="display:flex;gap:6px;margin-top:5px">'
                        + '<button onclick="simpanKet('+p.id+','+sid+')" style="padding:4px 12px;background:#34d399;border:none;border-radius:5px;color:#0f172a;font-size:.75rem;font-weight:700;cursor:pointer">Simpan</button>'
                        + '<button onclick="batalKet('+p.id+')" style="padding:4px 10px;background:transparent;border:1px solid rgba(255,255,255,.2);border-radius:5px;color:#94a3b8;font-size:.75rem;cursor:pointer">Batal</button>'
                        + '</div></div>'
                        + '</div>'
                        + '<div style="display:flex;gap:5px;align-items:center;margin-left:8px">'
                        + '<button onclick="editKet('+p.id+')" style="padding:4px 7px;background:rgba(52,211,153,.1);border:1px solid rgba(52,211,153,.3);border-radius:5px;color:#34d399;font-size:.7rem;cursor:pointer" title="Edit keterangan"><i class="fas fa-pen"></i></button>'
                        + '<button onclick="hapusPelEdit('+p.id+','+sid+')" style="padding:4px 7px;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);border-radius:5px;color:#f87171;font-size:.7rem;cursor:pointer" title="Hapus"><i class="fas fa-trash"></i></button>'
                        + '</div></div>';
                });
            }
            body.innerHTML = html;
        })
        .catch(function(){ body.innerHTML='<div style="color:#f87171;padding:20px;text-align:center">Gagal memuat data.</div>'; });
}

function editKet(pid) {
    document.getElementById('ket-disp-'+pid).style.display = 'none';
    document.getElementById('ket-form-'+pid).style.display = 'block';
    document.getElementById('ket-inp-'+pid).focus();
}
function batalKet(pid) {
    document.getElementById('ket-form-'+pid).style.display = 'none';
    document.getElementById('ket-disp-'+pid).style.display = 'block';
}
function simpanKet(pid, sid) {
    var ket = document.getElementById('ket-inp-'+pid).value;
    var fd  = new FormData();
    fd.append('ajax_edit_ket','1');
    fd.append('pel_id', pid);
    fd.append('keterangan', ket);
    fetch('portal_disiplin.php', {method:'POST',body:fd})
        .then(function(r){ return r.json(); })
        .then(function(d){
            if (d.ok) { showToast('✅ Keterangan disimpan','success'); loadEdit(sid, meTanggal); }
        });
}
function hapusPelEdit(pid, sid) {
    if (!confirm('Hapus catatan pelanggaran ini?')) return;
    var fd = new FormData();
    fd.append('ajax_hapus_pel','1');
    fd.append('pel_id', pid);
    fetch('portal_disiplin.php', {method:'POST',body:fd})
        .then(function(r){ return r.json(); })
        .then(function(d){
            if (d.ok) {
                showToast('Pelanggaran dihapus','success');
                loadEdit(sid, meTanggal);
                setTimeout(function(){ location.reload(); }, 1200);
            }
        });
}

function escH(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function escAttr(s){ return String(s||'').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }

// ── Toast ─────────────────────────────────────────────────────────────
function showToast(msg, type) {
    var el = document.createElement('div');
    el.className = 'toast ' + (type||'success');
    el.innerHTML = msg;
    document.body.appendChild(el);
    setTimeout(function(){ el.style.opacity='0'; setTimeout(function(){ el.remove(); },300); }, 2800);
}

// Jam realtime
function updateJam() {
    var n=new Date(), el=document.getElementById('jamNav');
    if(el) el.textContent = String(n.getHours()).padStart(2,'0')+':'+String(n.getMinutes()).padStart(2,'0')+':'+String(n.getSeconds()).padStart(2,'0');
}
updateJam(); setInterval(updateJam,1000);
</script>
</body>
</html>
