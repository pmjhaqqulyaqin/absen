<?php
/**
 * pelanggaran.php
 * Kelola Pelanggaran: Topik, Sub, Jenis Pelanggaran, Rekap Harian,
 * Rekap Kalender, dan Penentuan Point Alpa & Terlambat (BARU)
 *
 * PERUBAHAN TERBARU:
 * - Tambah tab "Penentuan Point Alpa & Terlambat"
 *   Data alpa/terlambat berasal dari absensi siswa, bukan input manual.
 *   Admin bisa menetapkan berapa poin per 1x Alpa dan per 1x Terlambat.
 *   Poin ini akan muncul di Portal BK pada menu Data Siswa Riwayat.
 */
require_once 'includes/config.php';
cek_login();

// ── Auto-create semua tabel yang dibutuhkan ──────────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS pelanggaran_topik (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nama        VARCHAR(150) NOT NULL,
    keterangan  TEXT DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS pelanggaran_sub (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    topik_id    INT(11) NOT NULL,
    nama        VARCHAR(150) NOT NULL,
    keterangan  TEXT DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS pelanggaran_jenis (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    topik_id    INT(11) DEFAULT 0,
    sub_id      INT(11) DEFAULT 0,
    nama        VARCHAR(255) NOT NULL,
    poin        INT(11) DEFAULT 0,
    keterangan  TEXT DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS pelanggaran (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    siswa_id    INT(11) NOT NULL,
    nis         VARCHAR(20)  DEFAULT '',
    nama        VARCHAR(100) DEFAULT '',
    kelas       VARCHAR(20)  DEFAULT '',
    jenis_id    INT(11) DEFAULT 0,
    nama_jenis  VARCHAR(255) DEFAULT '',
    poin        INT(11) DEFAULT 0,
    keterangan  TEXT DEFAULT NULL,
    tanggal     DATE NOT NULL,
    admin_id    INT(11) DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_siswa (siswa_id),
    INDEX idx_tanggal (tanggal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS buku_kejadian (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    siswa_id    INT(11) NOT NULL,
    nis         VARCHAR(20)  DEFAULT '',
    nama        VARCHAR(100) DEFAULT '',
    kelas       VARCHAR(20)  DEFAULT '',
    judul       VARCHAR(255) NOT NULL,
    isi         TEXT DEFAULT NULL,
    poin        INT(11) DEFAULT 0,
    tindak_lanjut TEXT DEFAULT NULL,
    tanggal     DATE NOT NULL,
    admin_id    INT(11) DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_siswa (siswa_id),
    INDEX idx_tanggal (tanggal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── Tabel baru: Pengaturan Poin Alpa & Terlambat ─────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS pengaturan_poin_absen (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    poin_alpa       INT(11) DEFAULT 5,
    poin_terlambat  INT(11) DEFAULT 2,
    keterangan      VARCHAR(255) DEFAULT '',
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$cekPoin = $conn->query("SELECT COUNT(*) as c FROM pengaturan_poin_absen")->fetch_assoc();
if ((int)$cekPoin['c'] === 0) {
    $conn->query("INSERT INTO pengaturan_poin_absen (poin_alpa, poin_terlambat, keterangan)
                  VALUES (5, 2, 'Setiap 1x Alpa = 5 Poin, Setiap 1x Terlambat = 2 Poin')");
}

// ── Handle POST Actions ───────────────────────────────────────────────
$msg = ''; $msg_type = '';

// ── POST: Simpan / Hapus Topik ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_topik'])) {
    $act = $_POST['action_topik'];
    if ($act === 'tambah') {
        $nm = $conn->real_escape_string(trim($_POST['nama_topik'] ?? ''));
        $kt = $conn->real_escape_string(trim($_POST['ket_topik'] ?? ''));
        if ($nm) {
            $conn->query("INSERT INTO pelanggaran_topik (nama, keterangan) VALUES ('$nm','$kt')");
            $msg = 'Topik berhasil ditambahkan!'; $msg_type = 'success';
        } else { $msg = 'Nama topik tidak boleh kosong!'; $msg_type = 'error'; }
    } elseif ($act === 'hapus') {
        $id = (int)$_POST['id'];
        $conn->query("DELETE FROM pelanggaran_topik WHERE id=$id");
        $conn->query("DELETE FROM pelanggaran_sub WHERE topik_id=$id");
        $conn->query("DELETE FROM pelanggaran_jenis WHERE topik_id=$id");
        $msg = 'Topik dan sub/jenis terkait berhasil dihapus!'; $msg_type = 'success';
    } elseif ($act === 'edit') {
        $id = (int)$_POST['id'];
        $nm = $conn->real_escape_string(trim($_POST['nama_topik'] ?? ''));
        $kt = $conn->real_escape_string(trim($_POST['ket_topik'] ?? ''));
        $conn->query("UPDATE pelanggaran_topik SET nama='$nm', keterangan='$kt' WHERE id=$id");
        $msg = 'Topik berhasil diperbarui!'; $msg_type = 'success';
    }
}

// ── POST: Simpan / Hapus Sub ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_sub'])) {
    $act = $_POST['action_sub'];
    if ($act === 'tambah') {
        $tid = (int)$_POST['topik_id'];
        $nm  = $conn->real_escape_string(trim($_POST['nama_sub'] ?? ''));
        $kt  = $conn->real_escape_string(trim($_POST['ket_sub'] ?? ''));
        if ($nm && $tid) {
            $conn->query("INSERT INTO pelanggaran_sub (topik_id, nama, keterangan) VALUES ($tid,'$nm','$kt')");
            $msg = 'Sub pelanggaran berhasil ditambahkan!'; $msg_type = 'success';
        } else { $msg = 'Nama sub dan topik tidak boleh kosong!'; $msg_type = 'error'; }
    } elseif ($act === 'hapus') {
        $id = (int)$_POST['id'];
        $conn->query("DELETE FROM pelanggaran_sub WHERE id=$id");
        $msg = 'Sub pelanggaran berhasil dihapus!'; $msg_type = 'success';
    } elseif ($act === 'edit') {
        $id  = (int)$_POST['id'];
        $tid = (int)$_POST['topik_id'];
        $nm  = $conn->real_escape_string(trim($_POST['nama_sub'] ?? ''));
        $kt  = $conn->real_escape_string(trim($_POST['ket_sub'] ?? ''));
        $conn->query("UPDATE pelanggaran_sub SET topik_id=$tid, nama='$nm', keterangan='$kt' WHERE id=$id");
        $msg = 'Sub pelanggaran berhasil diperbarui!'; $msg_type = 'success';
    }
}

// ── POST: Simpan / Hapus Jenis Pelanggaran ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_jenis'])) {
    $act = $_POST['action_jenis'];
    if ($act === 'tambah') {
        $tid = (int)($_POST['topik_id'] ?? 0);
        $sid = (int)($_POST['sub_id'] ?? 0);
        $nm  = $conn->real_escape_string(trim($_POST['nama_jenis'] ?? ''));
        $pn  = (int)($_POST['poin'] ?? 0);
        $kt  = $conn->real_escape_string(trim($_POST['ket_jenis'] ?? ''));
        if ($nm) {
            $conn->query("INSERT INTO pelanggaran_jenis (topik_id, sub_id, nama, poin, keterangan)
                          VALUES ($tid, $sid, '$nm', $pn, '$kt')");
            $msg = 'Jenis pelanggaran berhasil ditambahkan!'; $msg_type = 'success';
        } else { $msg = 'Nama jenis tidak boleh kosong!'; $msg_type = 'error'; }
    } elseif ($act === 'hapus') {
        $id = (int)$_POST['id'];
        $conn->query("DELETE FROM pelanggaran_jenis WHERE id=$id");
        $msg = 'Jenis pelanggaran berhasil dihapus!'; $msg_type = 'success';
    } elseif ($act === 'edit') {
        $id  = (int)$_POST['id'];
        $tid = (int)($_POST['topik_id'] ?? 0);
        $sid = (int)($_POST['sub_id'] ?? 0);
        $nm  = $conn->real_escape_string(trim($_POST['nama_jenis'] ?? ''));
        $pn  = (int)($_POST['poin'] ?? 0);
        $kt  = $conn->real_escape_string(trim($_POST['ket_jenis'] ?? ''));
        $conn->query("UPDATE pelanggaran_jenis SET topik_id=$tid, sub_id=$sid, nama='$nm', poin=$pn, keterangan='$kt' WHERE id=$id");
        $msg = 'Jenis pelanggaran berhasil diperbarui!'; $msg_type = 'success';
    }
}

// ── POST: Simpan Pengaturan Poin Alpa & Terlambat ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_poin_absen'])) {
    $poin_alpa      = max(0, (int)($_POST['poin_alpa'] ?? 5));
    $poin_terlambat = max(0, (int)($_POST['poin_terlambat'] ?? 2));
    $ket            = $conn->real_escape_string(trim($_POST['keterangan_poin'] ?? ''));
    $conn->query("UPDATE pengaturan_poin_absen SET poin_alpa=$poin_alpa, poin_terlambat=$poin_terlambat, keterangan='$ket'");
    $msg = 'Pengaturan poin alpa & terlambat berhasil disimpan!'; $msg_type = 'success';
}

// ── Ambil data untuk halaman ──────────────────────────────────────────
$topik_list = [];
$res = $conn->query("SELECT * FROM pelanggaran_topik ORDER BY nama");
while ($r = $res->fetch_assoc()) $topik_list[] = $r;

$sub_list = [];
$res = $conn->query("SELECT s.*, t.nama as nama_topik FROM pelanggaran_sub s LEFT JOIN pelanggaran_topik t ON t.id=s.topik_id ORDER BY t.nama, s.nama");
while ($r = $res->fetch_assoc()) $sub_list[] = $r;

$jenis_list = [];
$res = $conn->query("SELECT j.*, t.nama as nama_topik, s.nama as nama_sub
    FROM pelanggaran_jenis j
    LEFT JOIN pelanggaran_topik t ON t.id=j.topik_id
    LEFT JOIN pelanggaran_sub s ON s.id=j.sub_id
    ORDER BY t.nama, s.nama, j.nama");
while ($r = $res->fetch_assoc()) $jenis_list[] = $r;

// Rekap Harian
$filter_tgl = $_GET['tgl'] ?? date('Y-m-d');
$filter_kelas = $_GET['kelas'] ?? '';
$rekap_harian = [];
$sql_h = "SELECT p.*, s.foto FROM pelanggaran p LEFT JOIN siswa s ON s.id=p.siswa_id WHERE p.tanggal='".($conn->real_escape_string($filter_tgl))."'";
if ($filter_kelas) $sql_h .= " AND p.kelas='".$conn->real_escape_string($filter_kelas)."'";
$sql_h .= periode_where($conn, 'p.');
$sql_h .= " ORDER BY p.created_at DESC";
$res = $conn->query($sql_h);
while ($r = $res->fetch_assoc()) $rekap_harian[] = $r;

// Rekap Kalender
$filter_bulan = (int)($_GET['bulan'] ?? date('m'));
$filter_tahun = (int)($_GET['tahun'] ?? date('Y'));
$filter_kelas_kal = $_GET['kelas_kal'] ?? '';
$rekap_kalender = [];
$sql_k = "SELECT p.*, DAYOFMONTH(p.tanggal) as hari
    FROM pelanggaran p
    WHERE MONTH(p.tanggal)=$filter_bulan AND YEAR(p.tanggal)=$filter_tahun";
if ($filter_kelas_kal) $sql_k .= " AND p.kelas='".$conn->real_escape_string($filter_kelas_kal)."'";
$sql_k .= periode_where($conn, 'p.');
$res = $conn->query($sql_k);
$kal_data = [];
while ($r = $res->fetch_assoc()) {
    $kal_data[$r['hari']][] = $r;
}

$kelas_list = get_kelas_list();

// Ambil pengaturan poin
$poin_cfg = $conn->query("SELECT * FROM pengaturan_poin_absen LIMIT 1")->fetch_assoc();

$current_page = 'pelanggaran';
include 'includes/header.php';
?>

<div class="page-header">
    <div class="page-title">
        <i class="fas fa-exclamation-triangle" style="color:#f59e0b"></i> Kelola Pelanggaran
    </div>
    <div class="page-subtitle">Topik, Sub, Jenis pelanggaran, rekap data, dan penentuan poin absensi</div>
</div>

<?php if ($msg): ?>
<div class="alert alert-<?= $msg_type === 'success' ? 'success' : 'danger' ?>" style="padding:12px 16px;border-radius:8px;margin-bottom:16px;background:<?= $msg_type==='success'?'#dcfce7':'#fee2e2' ?>;color:<?= $msg_type==='success'?'#15803d':'#991b1b' ?>;border:1px solid <?= $msg_type==='success'?'#86efac':'#fca5a5' ?>;">
    <i class="fas fa-<?= $msg_type==='success'?'check-circle':'exclamation-circle' ?>"></i> <?= htmlspecialchars($msg) ?>
</div>
<?php endif; ?>

<!-- ── TABS ─────────────────────────────────────────────── -->
<div class="card" style="overflow:visible">
    <div style="border-bottom:1px solid var(--border); padding:0 20px; display:flex; gap:4px; flex-wrap:wrap; background:white; border-radius:12px 12px 0 0;">
        <?php
        $tabs = [
            'topik'       => ['icon'=>'fa-layer-group',         'label'=>'Topik'],
            'sub'         => ['icon'=>'fa-sitemap',              'label'=>'Sub'],
            'jenis'       => ['icon'=>'fa-list-alt',             'label'=>'Jenis Pelanggaran'],
            'rekap_harian'=> ['icon'=>'fa-calendar-day',         'label'=>'Rekap Harian'],
            'rekap_kal'   => ['icon'=>'fa-calendar-alt',         'label'=>'Rekap Kalender'],
            'poin_absen'  => ['icon'=>'fa-sliders-h',            'label'=>'Penentuan Point Alpa &amp; Terlambat'],
        ];
        $active_tab = $_GET['tab'] ?? 'topik';
        if (!array_key_exists($active_tab, $tabs)) $active_tab = 'topik';
        foreach ($tabs as $key => $t):
            $isActive = ($active_tab === $key);
        ?>
        <a href="?tab=<?= $key ?>" class="tab-link" style="padding:14px 16px;text-decoration:none;font-size:.85rem;font-weight:<?= $isActive?'700':'500' ?>;color:<?= $isActive?'var(--primary)':'var(--text-muted)' ?>;border-bottom:<?= $isActive?'3px solid var(--primary)':'3px solid transparent' ?>;white-space:nowrap;transition:all .2s;display:flex;align-items:center;gap:7px;">
            <i class="fas <?= $t['icon'] ?>"></i> <?= $t['label'] ?>
        </a>
        <?php endforeach; ?>
    </div>

    <div class="card-body">

    <!-- ══════════════════ TAB TOPIK ══════════════════ -->
    <?php if ($active_tab === 'topik'): ?>
    <div style="display:grid;grid-template-columns:380px 1fr;gap:24px;align-items:start">
        <!-- Form Tambah Topik -->
        <div class="card" style="border:1px solid var(--border)">
            <div class="card-header" style="background:#f8fafc"><i class="fas fa-plus-circle" style="color:var(--primary)"></i> Tambah Topik</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action_topik" value="tambah">
                    <div class="form-group">
                        <label class="form-label">Nama Topik *</label>
                        <input type="text" name="nama_topik" class="form-control" placeholder="Contoh: Ketertiban" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Keterangan</label>
                        <textarea name="ket_topik" class="form-control" rows="2" placeholder="Opsional"></textarea>
                    </div>
                    <button class="btn btn-primary" style="width:100%"><i class="fas fa-save"></i> Simpan Topik</button>
                </form>
            </div>
        </div>
        <!-- Tabel Topik -->
        <div>
            <div class="table-container">
                <table style="width:100%;border-collapse:collapse;font-size:.875rem">
                    <thead>
                        <tr style="background:#f8fafc;border-bottom:2px solid var(--border)">
                            <th style="padding:10px 12px;text-align:left">#</th>
                            <th style="padding:10px 12px;text-align:left">Nama Topik</th>
                            <th style="padding:10px 12px;text-align:left">Keterangan</th>
                            <th style="padding:10px 12px;text-align:left">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($topik_list)): ?>
                        <tr><td colspan="4" style="padding:20px;text-align:center;color:var(--text-muted)"><i class="fas fa-inbox"></i> Belum ada data topik</td></tr>
                    <?php else: foreach ($topik_list as $i => $t): ?>
                        <tr style="border-bottom:1px solid var(--border)">
                            <td style="padding:10px 12px;color:var(--text-muted)"><?= $i+1 ?></td>
                            <td style="padding:10px 12px;font-weight:600"><?= htmlspecialchars($t['nama']) ?></td>
                            <td style="padding:10px 12px;color:var(--text-muted)"><?= htmlspecialchars($t['keterangan'] ?? '-') ?></td>
                            <td style="padding:10px 12px">
                                <button onclick="editTopik(<?= $t['id'] ?>, '<?= addslashes($t['nama']) ?>', '<?= addslashes($t['keterangan']??'') ?>')" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></button>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Hapus topik ini dan semua sub/jenis terkait?')">
                                    <input type="hidden" name="action_topik" value="hapus">
                                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                    <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Edit Topik -->
    <div id="modalEditTopik" class="modal-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center">
        <div class="modal" style="background:white;border-radius:12px;width:400px;max-width:95vw">
            <div class="modal-header" style="padding:16px 20px;border-bottom:1px solid var(--border);font-weight:700;display:flex;justify-content:space-between;align-items:center">
                Edit Topik <button onclick="document.getElementById('modalEditTopik').style.display='none'" style="background:none;border:none;font-size:1.2rem;cursor:pointer">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action_topik" value="edit">
                    <input type="hidden" name="id" id="edit_topik_id">
                    <div class="form-group">
                        <label class="form-label">Nama Topik *</label>
                        <input type="text" name="nama_topik" id="edit_topik_nama" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Keterangan</label>
                        <textarea name="ket_topik" id="edit_topik_ket" class="form-control" rows="2"></textarea>
                    </div>
                    <button class="btn btn-primary" style="width:100%"><i class="fas fa-save"></i> Update Topik</button>
                </form>
            </div>
        </div>
    </div>
    <script>
    function editTopik(id, nama, ket) {
        document.getElementById('edit_topik_id').value = id;
        document.getElementById('edit_topik_nama').value = nama;
        document.getElementById('edit_topik_ket').value = ket;
        document.getElementById('modalEditTopik').style.display = 'flex';
    }
    </script>

    <!-- ══════════════════ TAB SUB ══════════════════ -->
    <?php elseif ($active_tab === 'sub'): ?>
    <div style="display:grid;grid-template-columns:380px 1fr;gap:24px;align-items:start">
        <div class="card" style="border:1px solid var(--border)">
            <div class="card-header" style="background:#f8fafc"><i class="fas fa-plus-circle" style="color:var(--primary)"></i> Tambah Sub</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action_sub" value="tambah">
                    <div class="form-group">
                        <label class="form-label">Topik *</label>
                        <select name="topik_id" class="form-select" required>
                            <option value="">-- Pilih Topik --</option>
                            <?php foreach ($topik_list as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nama']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nama Sub *</label>
                        <input type="text" name="nama_sub" class="form-control" placeholder="Contoh: Seragam" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Keterangan</label>
                        <textarea name="ket_sub" class="form-control" rows="2"></textarea>
                    </div>
                    <button class="btn btn-primary" style="width:100%"><i class="fas fa-save"></i> Simpan Sub</button>
                </form>
            </div>
        </div>
        <div class="table-container">
            <table style="width:100%;border-collapse:collapse;font-size:.875rem">
                <thead>
                    <tr style="background:#f8fafc;border-bottom:2px solid var(--border)">
                        <th style="padding:10px 12px">#</th>
                        <th style="padding:10px 12px;text-align:left">Topik</th>
                        <th style="padding:10px 12px;text-align:left">Nama Sub</th>
                        <th style="padding:10px 12px;text-align:left">Keterangan</th>
                        <th style="padding:10px 12px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($sub_list)): ?>
                    <tr><td colspan="5" style="padding:20px;text-align:center;color:var(--text-muted)"><i class="fas fa-inbox"></i> Belum ada data sub</td></tr>
                <?php else: foreach ($sub_list as $i => $s): ?>
                    <tr style="border-bottom:1px solid var(--border)">
                        <td style="padding:10px 12px;color:var(--text-muted)"><?= $i+1 ?></td>
                        <td style="padding:10px 12px"><span style="background:#eff6ff;color:#1d4ed8;padding:3px 10px;border-radius:20px;font-size:.8rem"><?= htmlspecialchars($s['nama_topik'] ?? '-') ?></span></td>
                        <td style="padding:10px 12px;font-weight:600"><?= htmlspecialchars($s['nama']) ?></td>
                        <td style="padding:10px 12px;color:var(--text-muted)"><?= htmlspecialchars($s['keterangan'] ?? '-') ?></td>
                        <td style="padding:10px 12px">
                            <button onclick="editSub(<?= $s['id'] ?>, <?= $s['topik_id'] ?>, '<?= addslashes($s['nama']) ?>', '<?= addslashes($s['keterangan']??'') ?>')" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></button>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Hapus sub ini?')">
                                <input type="hidden" name="action_sub" value="hapus">
                                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Edit Sub -->
    <div id="modalEditSub" class="modal-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center">
        <div class="modal" style="background:white;border-radius:12px;width:420px;max-width:95vw">
            <div class="modal-header" style="padding:16px 20px;border-bottom:1px solid var(--border);font-weight:700;display:flex;justify-content:space-between;align-items:center">
                Edit Sub <button onclick="document.getElementById('modalEditSub').style.display='none'" style="background:none;border:none;font-size:1.2rem;cursor:pointer">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action_sub" value="edit">
                    <input type="hidden" name="id" id="edit_sub_id">
                    <div class="form-group">
                        <label class="form-label">Topik *</label>
                        <select name="topik_id" id="edit_sub_topik" class="form-select">
                            <?php foreach ($topik_list as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nama']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nama Sub *</label>
                        <input type="text" name="nama_sub" id="edit_sub_nama" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Keterangan</label>
                        <textarea name="ket_sub" id="edit_sub_ket" class="form-control" rows="2"></textarea>
                    </div>
                    <button class="btn btn-primary" style="width:100%"><i class="fas fa-save"></i> Update Sub</button>
                </form>
            </div>
        </div>
    </div>
    <script>
    function editSub(id, topik_id, nama, ket) {
        document.getElementById('edit_sub_id').value = id;
        document.getElementById('edit_sub_topik').value = topik_id;
        document.getElementById('edit_sub_nama').value = nama;
        document.getElementById('edit_sub_ket').value = ket;
        document.getElementById('modalEditSub').style.display = 'flex';
    }
    </script>

    <!-- ══════════════════ TAB JENIS PELANGGARAN ══════════════════ -->
    <?php elseif ($active_tab === 'jenis'): ?>
    <div style="display:grid;grid-template-columns:400px 1fr;gap:24px;align-items:start">
        <div class="card" style="border:1px solid var(--border)">
            <div class="card-header" style="background:#f8fafc"><i class="fas fa-plus-circle" style="color:var(--primary)"></i> Tambah Jenis Pelanggaran</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action_jenis" value="tambah">
                    <div class="form-group">
                        <label class="form-label">Topik</label>
                        <select name="topik_id" id="jenis_topik_id" class="form-select" onchange="loadSubForJenis(this.value)">
                            <option value="0">-- Tidak ada / Umum --</option>
                            <?php foreach ($topik_list as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nama']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sub</label>
                        <select name="sub_id" id="jenis_sub_id" class="form-select">
                            <option value="0">-- Pilih Sub --</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nama Jenis Pelanggaran *</label>
                        <textarea name="nama_jenis" class="form-control" rows="2" placeholder="Contoh: Tidak memakai seragam lengkap" required></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Poin Pelanggaran *</label>
                        <input type="number" name="poin" class="form-control" value="5" min="1" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Keterangan</label>
                        <textarea name="ket_jenis" class="form-control" rows="2"></textarea>
                    </div>
                    <button class="btn btn-primary" style="width:100%"><i class="fas fa-save"></i> Simpan Jenis</button>
                </form>
            </div>
        </div>
        <div class="table-container">
            <table style="width:100%;border-collapse:collapse;font-size:.875rem">
                <thead>
                    <tr style="background:#f8fafc;border-bottom:2px solid var(--border)">
                        <th style="padding:10px 12px">#</th>
                        <th style="padding:10px 12px;text-align:left">Topik / Sub</th>
                        <th style="padding:10px 12px;text-align:left">Nama Jenis Pelanggaran</th>
                        <th style="padding:10px 12px;text-align:center">Poin</th>
                        <th style="padding:10px 12px;text-align:center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($jenis_list)): ?>
                    <tr><td colspan="5" style="padding:20px;text-align:center;color:var(--text-muted)"><i class="fas fa-inbox"></i> Belum ada jenis pelanggaran</td></tr>
                <?php else: foreach ($jenis_list as $i => $j): ?>
                    <tr style="border-bottom:1px solid var(--border)">
                        <td style="padding:10px 12px;color:var(--text-muted)"><?= $i+1 ?></td>
                        <td style="padding:10px 12px">
                            <?php if ($j['nama_topik']): ?><span style="background:#eff6ff;color:#1d4ed8;padding:2px 8px;border-radius:20px;font-size:.75rem"><?= htmlspecialchars($j['nama_topik']) ?></span><?php endif; ?>
                            <?php if ($j['nama_sub']): ?><br><span style="background:#f0fdf4;color:#15803d;padding:2px 8px;border-radius:20px;font-size:.75rem;margin-top:3px;display:inline-block"><?= htmlspecialchars($j['nama_sub']) ?></span><?php endif; ?>
                        </td>
                        <td style="padding:10px 12px;font-weight:600;max-width:300px"><?= htmlspecialchars($j['nama']) ?></td>
                        <td style="padding:10px 12px;text-align:center">
                            <span style="background:#fee2e2;color:#991b1b;padding:4px 12px;border-radius:20px;font-weight:700"><?= $j['poin'] ?></span>
                        </td>
                        <td style="padding:10px 12px;text-align:center">
                            <button onclick="editJenis(<?= $j['id'] ?>, <?= $j['topik_id'] ?>, <?= $j['sub_id'] ?>, '<?= addslashes($j['nama']) ?>', <?= $j['poin'] ?>, '<?= addslashes($j['keterangan']??'') ?>')" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></button>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Hapus jenis ini?')">
                                <input type="hidden" name="action_jenis" value="hapus">
                                <input type="hidden" name="id" value="<?= $j['id'] ?>">
                                <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Edit Jenis -->
    <div id="modalEditJenis" class="modal-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center">
        <div class="modal" style="background:white;border-radius:12px;width:450px;max-width:95vw">
            <div class="modal-header" style="padding:16px 20px;border-bottom:1px solid var(--border);font-weight:700;display:flex;justify-content:space-between;align-items:center">
                Edit Jenis Pelanggaran <button onclick="document.getElementById('modalEditJenis').style.display='none'" style="background:none;border:none;font-size:1.2rem;cursor:pointer">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action_jenis" value="edit">
                    <input type="hidden" name="id" id="edit_jenis_id">
                    <div class="form-group">
                        <label class="form-label">Topik</label>
                        <select name="topik_id" id="edit_jenis_topik" class="form-select">
                            <option value="0">-- Tidak ada / Umum --</option>
                            <?php foreach ($topik_list as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nama']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sub</label>
                        <select name="sub_id" id="edit_jenis_sub" class="form-select">
                            <option value="0">-- Tidak ada --</option>
                            <?php foreach ($sub_list as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nama_topik'].' > '.$s['nama']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nama Jenis *</label>
                        <textarea name="nama_jenis" id="edit_jenis_nama" class="form-control" rows="2" required></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Poin *</label>
                        <input type="number" name="poin" id="edit_jenis_poin" class="form-control" min="1" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Keterangan</label>
                        <textarea name="ket_jenis" id="edit_jenis_ket" class="form-control" rows="2"></textarea>
                    </div>
                    <button class="btn btn-primary" style="width:100%"><i class="fas fa-save"></i> Update Jenis</button>
                </form>
            </div>
        </div>
    </div>
    <script>
    const subDataAll = <?= json_encode($sub_list) ?>;
    function loadSubForJenis(topik_id) {
        const sel = document.getElementById('jenis_sub_id');
        sel.innerHTML = '<option value="0">-- Pilih Sub --</option>';
        subDataAll.filter(s => s.topik_id == topik_id).forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.id; opt.textContent = s.nama;
            sel.appendChild(opt);
        });
    }
    function editJenis(id, topik_id, sub_id, nama, poin, ket) {
        document.getElementById('edit_jenis_id').value = id;
        document.getElementById('edit_jenis_topik').value = topik_id;
        document.getElementById('edit_jenis_sub').value = sub_id;
        document.getElementById('edit_jenis_nama').value = nama;
        document.getElementById('edit_jenis_poin').value = poin;
        document.getElementById('edit_jenis_ket').value = ket;
        document.getElementById('modalEditJenis').style.display = 'flex';
    }
    </script>

    <!-- ══════════════════ TAB REKAP HARIAN ══════════════════ -->
    <?php elseif ($active_tab === 'rekap_harian'): ?>
    <form method="GET" class="filter-bar" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;margin-bottom:20px">
        <input type="hidden" name="tab" value="rekap_harian">
        <div class="form-group" style="margin:0">
            <label class="form-label">Tanggal</label>
            <input type="date" name="tgl" class="form-control" value="<?= htmlspecialchars($filter_tgl) ?>">
        </div>
        <div class="form-group" style="margin:0">
            <label class="form-label">Kelas</label>
            <select name="kelas" class="form-select">
                <option value="">-- Semua Kelas --</option>
                <?php foreach ($kelas_list as $kl): ?>
                <option value="<?= htmlspecialchars($kl) ?>" <?= $filter_kelas===$kl?'selected':'' ?>><?= htmlspecialchars($kl) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn btn-primary"><i class="fas fa-search"></i> Tampilkan</button>
    </form>

    <div style="margin-bottom:12px;font-weight:600;color:var(--text-muted)">
        <i class="fas fa-calendar-day"></i> Rekap Pelanggaran — <?= format_tanggal($filter_tgl) ?>
        <span style="margin-left:8px;background:#fee2e2;color:#991b1b;padding:3px 10px;border-radius:20px;font-size:.8rem"><?= count($rekap_harian) ?> data</span>
    </div>

    <?php if (empty($rekap_harian)): ?>
    <div style="text-align:center;padding:40px;color:var(--text-muted)"><i class="fas fa-inbox fa-2x" style="margin-bottom:10px"></i><br>Tidak ada pelanggaran pada tanggal ini</div>
    <?php else: ?>
    <div class="table-container">
        <table style="width:100%;border-collapse:collapse;font-size:.875rem">
            <thead>
                <tr style="background:#f8fafc;border-bottom:2px solid var(--border)">
                    <th style="padding:10px 12px">#</th>
                    <th style="padding:10px 12px;text-align:left">Nama Siswa</th>
                    <th style="padding:10px 12px;text-align:left">Kelas</th>
                    <th style="padding:10px 12px;text-align:left">Jenis Pelanggaran</th>
                    <th style="padding:10px 12px;text-align:center">Poin</th>
                    <th style="padding:10px 12px;text-align:left">Keterangan</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rekap_harian as $i => $r): ?>
            <tr style="border-bottom:1px solid var(--border)">
                <td style="padding:10px 12px;color:var(--text-muted)"><?= $i+1 ?></td>
                <td style="padding:10px 12px;font-weight:600"><?= htmlspecialchars($r['nama']) ?></td>
                <td style="padding:10px 12px"><span style="background:#eff6ff;color:#1d4ed8;padding:3px 10px;border-radius:20px;font-size:.8rem"><?= htmlspecialchars($r['kelas']) ?></span></td>
                <td style="padding:10px 12px"><?= htmlspecialchars($r['nama_jenis']) ?></td>
                <td style="padding:10px 12px;text-align:center"><span style="background:#fee2e2;color:#991b1b;padding:3px 10px;border-radius:20px;font-weight:700"><?= $r['poin'] ?></span></td>
                <td style="padding:10px 12px;color:var(--text-muted)"><?= htmlspecialchars($r['keterangan'] ?? '-') ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- ══════════════════ TAB REKAP KALENDER ══════════════════ -->
    <?php elseif ($active_tab === 'rekap_kal'): ?>
    <?php
    $nama_bulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $hari_nama  = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];
    $jml_hari   = (int)date('t', mktime(0,0,0,$filter_bulan,1,$filter_tahun));
    $hari_awal  = (int)date('w', mktime(0,0,0,$filter_bulan,1,$filter_tahun));
    ?>
    <form method="GET" class="filter-bar" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;margin-bottom:20px">
        <input type="hidden" name="tab" value="rekap_kal">
        <div class="form-group" style="margin:0">
            <label class="form-label">Bulan</label>
            <select name="bulan" class="form-select">
                <?php for ($b=1;$b<=12;$b++): ?>
                <option value="<?= $b ?>" <?= $filter_bulan===$b?'selected':'' ?>><?= $nama_bulan[$b] ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="form-group" style="margin:0">
            <label class="form-label">Tahun</label>
            <input type="number" name="tahun" class="form-control" value="<?= $filter_tahun ?>" style="width:100px">
        </div>
        <div class="form-group" style="margin:0">
            <label class="form-label">Kelas</label>
            <select name="kelas_kal" class="form-select">
                <option value="">-- Semua Kelas --</option>
                <?php foreach ($kelas_list as $kl): ?>
                <option value="<?= htmlspecialchars($kl) ?>" <?= $filter_kelas_kal===$kl?'selected':'' ?>><?= htmlspecialchars($kl) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn btn-primary"><i class="fas fa-search"></i> Tampilkan</button>
    </form>

    <h4 style="margin-bottom:16px;color:var(--text-muted)"><i class="fas fa-calendar-alt"></i> Rekap Pelanggaran — <?= $nama_bulan[$filter_bulan] ?> <?= $filter_tahun ?></h4>
    <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:4px;margin-bottom:4px">
        <?php foreach ($hari_nama as $h): ?>
        <div style="text-align:center;font-weight:700;font-size:.8rem;color:var(--text-muted);padding:8px"><?= $h ?></div>
        <?php endforeach; ?>
    </div>
    <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:4px">
        <?php for ($i=0; $i<$hari_awal; $i++): ?><div></div><?php endfor; ?>
        <?php for ($d=1; $d<=$jml_hari; $d++):
            $data = $kal_data[$d] ?? [];
            $jumlah = count($data);
            $isToday = ($d==(int)date('d') && $filter_bulan==(int)date('m') && $filter_tahun==(int)date('Y'));
        ?>
        <div style="background:<?= $jumlah>0?'#fee2e2':($isToday?'#eff6ff':'#f8fafc') ?>;border:1px solid <?= $isToday?'var(--primary)':'var(--border)' ?>;border-radius:8px;padding:8px 6px;min-height:60px;cursor:<?= $jumlah>0?'pointer':'default' ?>"
             <?php if ($jumlah): ?>onclick="showKalDetail(<?= $d ?>)"<?php endif ?>>
            <div style="font-weight:700;font-size:.9rem;color:<?= $isToday?'var(--primary)':'var(--text)' ?>"><?= $d ?></div>
            <?php if ($jumlah): ?>
            <div style="background:#dc2626;color:white;border-radius:20px;font-size:.75rem;font-weight:700;text-align:center;margin-top:4px;padding:1px 6px"><?= $jumlah ?></div>
            <?php endif; ?>
        </div>
        <?php endfor; ?>
    </div>

    <div id="modalKalDetail" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center">
        <div style="background:white;border-radius:12px;width:500px;max-width:95vw;max-height:80vh;overflow-y:auto">
            <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-weight:700;display:flex;justify-content:space-between">
                <span id="kalDetailTitle"></span>
                <button onclick="document.getElementById('modalKalDetail').style.display='none'" style="background:none;border:none;font-size:1.2rem;cursor:pointer">&times;</button>
            </div>
            <div style="padding:20px" id="kalDetailBody"></div>
        </div>
    </div>
    <script>
    const kalData = <?= json_encode($kal_data) ?>;
    const namaBulan = <?= json_encode($nama_bulan) ?>;
    function showKalDetail(hari) {
        const data = kalData[hari] || [];
        document.getElementById('kalDetailTitle').textContent = 'Pelanggaran Tanggal ' + hari + ' <?= $nama_bulan[$filter_bulan] ?> <?= $filter_tahun ?>';
        let html = '<table style="width:100%;border-collapse:collapse;font-size:.875rem"><thead><tr style="background:#f8fafc"><th style="padding:8px">Nama</th><th style="padding:8px">Kelas</th><th style="padding:8px">Jenis</th><th style="padding:8px">Poin</th></tr></thead><tbody>';
        data.forEach(r => {
            html += `<tr style="border-bottom:1px solid #e2e8f0"><td style="padding:8px;font-weight:600">${r.nama}</td><td style="padding:8px">${r.kelas}</td><td style="padding:8px">${r.nama_jenis}</td><td style="padding:8px;text-align:center"><span style="background:#fee2e2;color:#991b1b;padding:2px 8px;border-radius:20px;font-weight:700">${r.poin}</span></td></tr>`;
        });
        html += '</tbody></table>';
        document.getElementById('kalDetailBody').innerHTML = html;
        document.getElementById('modalKalDetail').style.display = 'flex';
    }
    </script>

    <!-- ══════════════════ TAB PENENTUAN POINT ALPA & TERLAMBAT ══════════════════ -->
    <?php elseif ($active_tab === 'poin_absen'): ?>
    <div style="max-width:700px">
        <!-- Info Box -->
        <div style="background:linear-gradient(135deg,#eff6ff,#dbeafe);border:1px solid #93c5fd;border-radius:12px;padding:18px 20px;margin-bottom:24px;display:flex;gap:14px;align-items:flex-start">
            <i class="fas fa-info-circle" style="color:#2563eb;font-size:1.4rem;margin-top:2px"></i>
            <div>
                <div style="font-weight:700;color:#1d4ed8;margin-bottom:6px">Apa ini?</div>
                <p style="color:#1e40af;font-size:.9rem;margin:0;line-height:1.6">
                    Poin <strong>Alpa</strong> dan <strong>Terlambat</strong> tidak diinput secara manual karena datanya langsung diambil dari <strong>hasil absensi siswa</strong>.
                    Di halaman ini, Anda menentukan <em>berapa besar poin</em> yang diberikan untuk setiap 1x Alpa dan setiap 1x Terlambat.
                    Poin ini akan otomatis dihitung di <strong>Portal BK → Data Siswa → Riwayat</strong>.
                </p>
            </div>
        </div>

        <!-- Ilustrasi Perhitungan -->
        <div style="background:#f8fafc;border:1px solid var(--border);border-radius:12px;padding:18px 20px;margin-bottom:24px">
            <div style="font-weight:700;margin-bottom:14px;color:var(--text)"><i class="fas fa-calculator" style="color:var(--primary)"></i> Contoh Perhitungan</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                <div style="background:white;border-radius:8px;padding:14px;border:1px solid #fef3c7">
                    <div style="font-size:.8rem;font-weight:700;color:#92400e;margin-bottom:6px"><i class="fas fa-clock"></i> TERLAMBAT</div>
                    <div style="font-size:.85rem;color:var(--text-muted)">Siswa terlambat <strong style="color:#1e293b">5 kali</strong></div>
                    <div style="font-size:.85rem;color:var(--text-muted)">× <strong id="demo_terlambat" style="color:#d97706"><?= $poin_cfg['poin_terlambat'] ?> poin</strong> per kejadian</div>
                    <div style="margin-top:8px;font-weight:700;color:#92400e">= <span id="demo_total_terlambat"><?= 5 * $poin_cfg['poin_terlambat'] ?></span> poin</div>
                </div>
                <div style="background:white;border-radius:8px;padding:14px;border:1px solid #fee2e2">
                    <div style="font-size:.8rem;font-weight:700;color:#991b1b;margin-bottom:6px"><i class="fas fa-times-circle"></i> ALPA</div>
                    <div style="font-size:.85rem;color:var(--text-muted)">Siswa alpa <strong style="color:#1e293b">3 kali</strong></div>
                    <div style="font-size:.85rem;color:var(--text-muted)">× <strong id="demo_alpa" style="color:#dc2626"><?= $poin_cfg['poin_alpa'] ?> poin</strong> per kejadian</div>
                    <div style="margin-top:8px;font-weight:700;color:#991b1b">= <span id="demo_total_alpa"><?= 3 * $poin_cfg['poin_alpa'] ?></span> poin</div>
                </div>
            </div>
        </div>

        <!-- Form Pengaturan -->
        <div class="card" style="border:1px solid var(--border)">
            <div class="card-header" style="background:linear-gradient(135deg,#f59e0b,#d97706);color:white">
                <i class="fas fa-sliders-h"></i> Pengaturan Poin Alpa &amp; Terlambat
            </div>
            <div class="card-body">
                <form method="POST" id="formPoinAbsen">
                    <input type="hidden" name="action_poin_absen" value="1">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:20px">
                        <div>
                            <label class="form-label" style="font-size:1rem">
                                <i class="fas fa-times-circle" style="color:#dc2626"></i>
                                1 × Alpa = <strong style="color:#dc2626">... poin</strong>
                            </label>
                            <div style="display:flex;align-items:center;gap:12px">
                                <input type="number" name="poin_alpa" id="inp_poin_alpa"
                                       class="form-control" style="font-size:1.5rem;font-weight:700;text-align:center;color:#dc2626;border-color:#fca5a5;width:120px"
                                       value="<?= (int)$poin_cfg['poin_alpa'] ?>" min="0" max="100" required
                                       oninput="updateDemo()">
                                <span style="font-size:.9rem;color:var(--text-muted)">poin per 1x Alpa</span>
                            </div>
                            <p style="font-size:.8rem;color:var(--text-muted);margin-top:6px">Rekomendasi: 5 poin</p>
                        </div>
                        <div>
                            <label class="form-label" style="font-size:1rem">
                                <i class="fas fa-clock" style="color:#d97706"></i>
                                1 × Terlambat = <strong style="color:#d97706">... poin</strong>
                            </label>
                            <div style="display:flex;align-items:center;gap:12px">
                                <input type="number" name="poin_terlambat" id="inp_poin_terlambat"
                                       class="form-control" style="font-size:1.5rem;font-weight:700;text-align:center;color:#d97706;border-color:#fcd34d;width:120px"
                                       value="<?= (int)$poin_cfg['poin_terlambat'] ?>" min="0" max="100" required
                                       oninput="updateDemo()">
                                <span style="font-size:.9rem;color:var(--text-muted)">poin per 1x Terlambat</span>
                            </div>
                            <p style="font-size:.8rem;color:var(--text-muted);margin-top:6px">Rekomendasi: 2 poin</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Keterangan / Catatan</label>
                        <input type="text" name="keterangan_poin" class="form-control"
                               value="<?= htmlspecialchars($poin_cfg['keterangan'] ?? '') ?>"
                               placeholder="Contoh: Berlaku mulai semester ganjil 2026/2027">
                    </div>
                    <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:8px;padding:12px 16px;margin-bottom:16px;font-size:.875rem;color:#15803d">
                        <i class="fas fa-check-circle"></i>
                        <strong>Pengaturan ini akan berlaku di:</strong> Portal BK → Data Siswa → Riwayat (kolom Total Poin)
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg" style="width:100%">
                        <i class="fas fa-save"></i> Simpan Pengaturan Poin
                    </button>
                </form>
            </div>
        </div>

        <!-- Tabel ringkasan poin saat ini -->
        <div style="margin-top:24px;background:#1e293b;border-radius:12px;padding:20px;color:white">
            <div style="font-weight:700;margin-bottom:14px;font-size:1rem"><i class="fas fa-table"></i> Ringkasan Poin Aktif Saat Ini</div>
            <table style="width:100%;border-collapse:collapse;font-size:.9rem">
                <thead>
                    <tr style="border-bottom:1px solid rgba(255,255,255,.2)">
                        <th style="padding:10px;text-align:left;color:rgba(255,255,255,.6)">Kategori</th>
                        <th style="padding:10px;text-align:left;color:rgba(255,255,255,.6)">Sumber Data</th>
                        <th style="padding:10px;text-align:center;color:rgba(255,255,255,.6)">Poin per Kejadian</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="border-bottom:1px solid rgba(255,255,255,.1)">
                        <td style="padding:10px"><span style="background:#fef3c7;color:#92400e;padding:3px 10px;border-radius:20px;font-size:.8rem;font-weight:700">⏰ TERLAMBAT</span></td>
                        <td style="padding:10px;color:rgba(255,255,255,.7)">Tabel absensi (otomatis)</td>
                        <td style="padding:10px;text-align:center"><span style="background:#f59e0b;color:white;padding:4px 14px;border-radius:20px;font-weight:700;font-size:1.1rem"><?= $poin_cfg['poin_terlambat'] ?></span></td>
                    </tr>
                    <tr style="border-bottom:1px solid rgba(255,255,255,.1)">
                        <td style="padding:10px"><span style="background:#fee2e2;color:#991b1b;padding:3px 10px;border-radius:20px;font-size:.8rem;font-weight:700">❌ ALPA</span></td>
                        <td style="padding:10px;color:rgba(255,255,255,.7)">Tabel absensi (otomatis)</td>
                        <td style="padding:10px;text-align:center"><span style="background:#dc2626;color:white;padding:4px 14px;border-radius:20px;font-weight:700;font-size:1.1rem"><?= $poin_cfg['poin_alpa'] ?></span></td>
                    </tr>
                    <tr style="border-bottom:1px solid rgba(255,255,255,.1)">
                        <td style="padding:10px"><span style="background:#ede9fe;color:#5b21b6;padding:3px 10px;border-radius:20px;font-size:.8rem;font-weight:700">🚨 PELANGGARAN DISIPLIN</span></td>
                        <td style="padding:10px;color:rgba(255,255,255,.7)">Tabel pelanggaran (input BK)</td>
                        <td style="padding:10px;text-align:center;color:rgba(255,255,255,.5);font-style:italic">Sesuai jenis pelanggaran</td>
                    </tr>
                    <tr>
                        <td style="padding:10px"><span style="background:#dcfce7;color:#15803d;padding:3px 10px;border-radius:20px;font-size:.8rem;font-weight:700">📖 BUKU KEJADIAN</span></td>
                        <td style="padding:10px;color:rgba(255,255,255,.7)">Tabel buku kejadian (input BK)</td>
                        <td style="padding:10px;text-align:center;color:rgba(255,255,255,.5);font-style:italic">Sesuai input poin kejadian</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    function updateDemo() {
        const a = parseInt(document.getElementById('inp_poin_alpa').value) || 0;
        const t = parseInt(document.getElementById('inp_poin_terlambat').value) || 0;
        document.getElementById('demo_alpa').textContent = a + ' poin';
        document.getElementById('demo_terlambat').textContent = t + ' poin';
        document.getElementById('demo_total_alpa').textContent = (3 * a);
        document.getElementById('demo_total_terlambat').textContent = (5 * t);
    }
    </script>

    <?php endif; ?>

    </div><!-- end card-body -->
</div><!-- end card -->

<?php include 'includes/footer.php'; ?>
