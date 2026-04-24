<?php
require_once 'includes/config.php';
cek_login();

// ── Auto-migrasi tabel ──────────────────────────────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS `pelanggaran_jenis` (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nama        VARCHAR(100) NOT NULL,
    kode        VARCHAR(10)  DEFAULT '',
    keterangan  VARCHAR(255) DEFAULT '',
    aktif       TINYINT(1)   DEFAULT 1,
    urutan      INT          DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Migrasi: tambah kolom kode jika belum ada
$cols = $conn->query("SHOW COLUMNS FROM pelanggaran_jenis LIKE 'kode'");
if ($cols->num_rows === 0) {
    $conn->query("ALTER TABLE pelanggaran_jenis ADD COLUMN kode VARCHAR(10) DEFAULT '' AFTER nama");
}

$conn->query("CREATE TABLE IF NOT EXISTS `pelanggaran` (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    siswa_id    INT          DEFAULT 0,
    nis         VARCHAR(30)  DEFAULT '',
    nama        VARCHAR(100) DEFAULT '',
    kelas       VARCHAR(30)  DEFAULT '',
    tanggal     DATE         NOT NULL,
    jenis_id    INT          DEFAULT 0,
    jenis_nama  VARCHAR(100) DEFAULT '',
    keterangan  VARCHAR(255) DEFAULT '',
    input_oleh  VARCHAR(100) DEFAULT 'Disiplin',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS `disiplin_pin` (
    id   INT AUTO_INCREMENT PRIMARY KEY,
    pin  VARCHAR(255) NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Seed PIN default jika kosong
$chk_pin = $conn->query("SELECT COUNT(*) c FROM disiplin_pin")->fetch_assoc();
if ($chk_pin['c'] == 0) {
    $def = password_hash('1234', PASSWORD_DEFAULT);
    $conn->query("INSERT INTO disiplin_pin (pin) VALUES ('$def')");
}

$page = $_GET['tab'] ?? 'jenis';
$msg  = '';

// ────────────────────────────────────────────────────────────────────
// KELOLA JENIS PELANGGARAN
// ────────────────────────────────────────────────────────────────────
if ($page === 'jenis') {
    // Hapus
    if (isset($_GET['hapus'])) {
        $hid = (int)$_GET['hapus'];
        $conn->query("DELETE FROM pelanggaran_jenis WHERE id=$hid");
        header('Location: pelanggaran.php?tab=jenis&msg=deleted'); exit;
    }
    // Toggle aktif
    if (isset($_GET['toggle'])) {
        $tid = (int)$_GET['toggle'];
        $conn->query("UPDATE pelanggaran_jenis SET aktif = IF(aktif=1,0,1) WHERE id=$tid");
        header('Location: pelanggaran.php?tab=jenis'); exit;
    }
    // Simpan
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_jenis'])) {
        $jid   = (int)($_POST['jid'] ?? 0);
        $nama  = $conn->real_escape_string(trim($_POST['nama'] ?? ''));
        $kode  = strtoupper($conn->real_escape_string(trim($_POST['kode'] ?? '')));
        $ket   = $conn->real_escape_string(trim($_POST['keterangan'] ?? ''));
        $urut  = (int)($_POST['urutan'] ?? 0);
        if ($nama) {
            if ($jid)
                $conn->query("UPDATE pelanggaran_jenis SET nama='$nama',kode='$kode',keterangan='$ket',urutan=$urut WHERE id=$jid");
            else
                $conn->query("INSERT INTO pelanggaran_jenis (nama,kode,keterangan,urutan) VALUES ('$nama','$kode','$ket',$urut)");
            $msg = 'success:Jenis pelanggaran berhasil disimpan';
        } else {
            $msg = 'error:Nama jenis pelanggaran wajib diisi';
        }
    }
    // Simpan PIN Disiplin
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_pin'])) {
        $pin1 = $_POST['pin_baru'] ?? '';
        $pin2 = $_POST['pin_konfirm'] ?? '';
        if (!ctype_digit($pin1) || strlen($pin1) < 4) {
            $msg = 'error:PIN minimal 4 digit angka';
        } elseif ($pin1 !== $pin2) {
            $msg = 'error:Konfirmasi PIN tidak cocok';
        } else {
            $hashed = password_hash($pin1, PASSWORD_DEFAULT);
            $conn->query("UPDATE disiplin_pin SET pin='$hashed'");
            $msg = 'success:PIN Disiplin berhasil diperbarui';
        }
    }
    if (!$msg && isset($_GET['msg']) && $_GET['msg'] === 'deleted') $msg = 'success:Jenis pelanggaran dihapus';
}

// Edit data
$edit_jenis = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $edit_jenis = $conn->query("SELECT * FROM pelanggaran_jenis WHERE id=$eid")->fetch_assoc();
}

$jenis_list = $conn->query("SELECT * FROM pelanggaran_jenis ORDER BY urutan ASC, id ASC");

// ────────────────────────────────────────────────────────────────────
// REKAP HARIAN
// ────────────────────────────────────────────────────────────────────
$today     = date('Y-m-d');
$tgl_h     = isset($_GET['tanggal']) ? sanitize($_GET['tanggal']) : $today;
$kelas_h   = isset($_GET['kelas'])   ? sanitize($_GET['kelas'])  : '';
$kelas_list = get_kelas_list();

$rekap_harian  = [];
$jenis_all_arr = [];
if ($page === 'harian') {
    // Ambil semua jenis aktif
    $jr = $conn->query("SELECT * FROM pelanggaran_jenis WHERE aktif=1 ORDER BY urutan,id");
    while ($j = $jr->fetch_assoc()) $jenis_all_arr[] = $j;

    // Ambil data
    $kls_cond = $kelas_h ? "AND p.kelas='$kelas_h'" : '';
    $res = $conn->query("SELECT p.*, pj.nama as jenis_label
        FROM pelanggaran p
        LEFT JOIN pelanggaran_jenis pj ON pj.id = p.jenis_id
        WHERE p.tanggal='$tgl_h' $kls_cond
        ORDER BY p.nama, p.jenis_id");

    // Group by siswa
    while ($r = $res->fetch_assoc()) {
        $key = $r['siswa_id'] ?: $r['nis'];
        if (!isset($rekap_harian[$key])) {
            $rekap_harian[$key] = [
                'siswa_id' => $r['siswa_id'],
                'nis'      => $r['nis'],
                'nama'     => $r['nama'],
                'kelas'    => $r['kelas'],
                'jenis'    => [],
            ];
        }
        $rekap_harian[$key]['jenis'][$r['jenis_id']] = ($rekap_harian[$key]['jenis'][$r['jenis_id']] ?? 0) + 1;
    }
}

// ────────────────────────────────────────────────────────────────────
// REKAP KALENDER (bulanan) — format per-hari (kolom = tanggal 1-31)
// ────────────────────────────────────────────────────────────────────
$bulan_kal  = (int)($_GET['bulan'] ?? date('n'));
$tahun_kal  = (int)($_GET['tahun'] ?? date('Y'));
$kelas_kal  = isset($_GET['kelas_kal']) ? sanitize($_GET['kelas_kal']) : '';
$rekap_kal  = [];
$max_hari   = cal_days_in_month(CAL_GREGORIAN, $bulan_kal, $tahun_kal);

if ($page === 'kalender') {
    $kls_cond2 = $kelas_kal ? "AND kelas='$kelas_kal'" : '';
    // Group by siswa + hari (jumlah semua jenis pelanggaran per hari)
    $res2 = $conn->query("SELECT siswa_id, nis, nama, kelas,
            DAY(tanggal) as hari, COUNT(*) as jml
        FROM pelanggaran
        WHERE MONTH(tanggal)=$bulan_kal AND YEAR(tanggal)=$tahun_kal $kls_cond2
        GROUP BY siswa_id, hari
        ORDER BY nama, hari");
    while ($r = $res2->fetch_assoc()) {
        $key = $r['siswa_id'] ?: $r['nis'];
        if (!isset($rekap_kal[$key])) {
            $rekap_kal[$key] = ['siswa_id'=>$r['siswa_id'],'nis'=>$r['nis'],'nama'=>$r['nama'],'kelas'=>$r['kelas'],'hari'=>[]];
        }
        $rekap_kal[$key]['hari'][(int)$r['hari']] = (int)$r['jml'];
    }
}

$nama_bulan_arr = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

// ────────────────────────────────────────────────────────────────────
// EXPORT EXCEL — intercept sebelum output HTML
// ────────────────────────────────────────────────────────────────────
if ($page === 'kalender' && isset($_GET['export']) && $_GET['export'] === 'excel' && !empty($rekap_kal)) {
    $set = get_pengaturan();
    $nm_sekolah  = $set['nama_sekolah'] ?? 'Nama Sekolah';
    $alamat_sek  = $set['alamat'] ?? '';
    $kepala_sek  = $set['kepala_sekolah'] ?? '';
    $nip_kepala  = $set['nip_kepala'] ?? '';
    // Guru BP: admin yang sedang login
    $admin_nama  = $_SESSION['admin_nama'] ?? 'Guru BP';

    // Bulan & tahun export
    $label_bln = strtoupper($nama_bulan_arr[$bulan_kal] . ' ' . $tahun_kal);
    $fn_bln    = strtolower($nama_bulan_arr[$bulan_kal]) . '_' . $tahun_kal;
    if ($kelas_kal) $fn_bln .= '_' . preg_replace('/[^a-z0-9]/i', '', $kelas_kal);

    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="rekap_pelanggaran_' . $fn_bln . '.xls"');
    header('Pragma: no-cache');
    echo "\xEF\xBB\xBF"; // BOM UTF-8
    ?>
<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:x="urn:schemas-microsoft-com:office:excel"
      xmlns="http://www.w3.org/TR/REC-html40">
<head><meta charset="UTF-8">
<style>
  body{font-family:Arial,sans-serif;font-size:10pt}
  table{border-collapse:collapse;width:100%}
  td,th{border:1px solid #000;padding:4px 6px;text-align:center;vertical-align:middle}
  .kop-title{font-size:14pt;font-weight:bold;text-align:center;border:none}
  .kop-sub{font-size:10pt;text-align:center;border:none}
  .kop-alamat{font-size:9pt;text-align:center;border:none}
  .th-main{background:#d9d9d9;font-weight:bold}
  .td-nama{text-align:left;font-weight:600}
  .td-zero{color:#aaa}
  .ttd-area{border:none;padding-top:8px;text-align:center}
</style>
</head>
<body>
<table>
  <tr><td colspan="<?= 3+$max_hari+1 ?>" class="kop-title">REKAP PELANGGARAN SISWA</td></tr>
  <tr><td colspan="<?= 3+$max_hari+1 ?>" class="kop-sub"><?= htmlspecialchars($nm_sekolah) ?></td></tr>
  <?php if ($alamat_sek): ?>
  <tr><td colspan="<?= 3+$max_hari+1 ?>" class="kop-alamat"><?= htmlspecialchars($alamat_sek) ?></td></tr>
  <?php endif; ?>
  <tr><td colspan="<?= 3+$max_hari+1 ?>" style="border:none;padding:4px"></td></tr>
  <tr><td colspan="<?= 3+$max_hari+1 ?>" class="kop-sub">REKAP PELANGGARAN — <?= $label_bln ?><?= $kelas_kal?' — KELAS '.htmlspecialchars(strtoupper($kelas_kal)):'' ?></td></tr>
  <tr><td colspan="<?= 3+$max_hari+1 ?>" style="border:none;padding:3px"></td></tr>
  <!-- Header -->
  <tr>
    <th class="th-main" rowspan="2" width="30">NO</th>
    <th class="th-main" rowspan="2" style="text-align:left;min-width:160px">NAMA SISWA</th>
    <th class="th-main" rowspan="2" width="60">KELAS</th>
    <th class="th-main" colspan="<?= $max_hari ?>">BULAN <?= $label_bln ?></th>
    <th class="th-main" rowspan="2" width="50">JUMLAH</th>
  </tr>
  <tr>
    <?php for ($d=1;$d<=$max_hari;$d++): ?>
    <th class="th-main" width="22"><?= $d ?></th>
    <?php endfor; ?>
  </tr>
  <!-- Data -->
  <?php $no=0; foreach ($rekap_kal as $s): $no++;
      $total_row = array_sum($s['hari']);
  ?>
  <tr>
    <td><?= $no ?></td>
    <td class="td-nama"><?= htmlspecialchars($s['nama']) ?></td>
    <td><?= htmlspecialchars($s['kelas']) ?></td>
    <?php for ($d=1;$d<=$max_hari;$d++):
        $jml = $s['hari'][$d] ?? 0;
    ?>
    <td><?= $jml > 0 ? $jml : '' ?></td>
    <?php endfor; ?>
    <td><strong><?= $total_row ?></strong></td>
  </tr>
  <?php endforeach; ?>
  <!-- Spacer -->
  <tr><td colspan="<?= 3+$max_hari+1 ?>" style="border:none;padding:16px"></td></tr>
  <!-- Tanda Tangan -->
  <tr>
    <td colspan="<?= intdiv(3+$max_hari+1, 2) ?>" class="ttd-area">Guru BP</td>
    <td colspan="<?= (3+$max_hari+1) - intdiv(3+$max_hari+1, 2) ?>" class="ttd-area">Kepala Sekolah</td>
  </tr>
  <tr>
    <td colspan="<?= intdiv(3+$max_hari+1, 2) ?>" class="ttd-area" style="padding-top:50px">
      <?php if ($admin_nama): ?>
      <u><?= htmlspecialchars($admin_nama) ?></u>
      <?php else: ?>
      (...............................................)</td>
      <?php endif; ?>
    </td>
    <td colspan="<?= (3+$max_hari+1) - intdiv(3+$max_hari+1, 2) ?>" class="ttd-area" style="padding-top:50px">
      <?php if ($kepala_sek): ?>
      <u><?= htmlspecialchars($kepala_sek) ?></u>
      <?php else: ?>
      (...............................................)
      <?php endif; ?>
    </td>
  </tr>
  <?php if ($nip_kepala): ?>
  <tr>
    <td colspan="<?= intdiv(3+$max_hari+1, 2) ?>" class="ttd-area"></td>
    <td colspan="<?= (3+$max_hari+1) - intdiv(3+$max_hari+1, 2) ?>" class="ttd-area" style="font-size:9pt">NIP. <?= htmlspecialchars($nip_kepala) ?></td>
  </tr>
  <?php endif; ?>
</table>
</body></html>
    <?php
    exit;
}

include 'includes/header.php';
?>

<style>
.tab-bar{display:flex;gap:4px;margin-bottom:20px;background:#f1f5f9;padding:5px;border-radius:12px;width:fit-content}
.tab-btn{padding:8px 18px;border-radius:9px;border:none;font-weight:700;font-size:.82rem;cursor:pointer;color:#64748b;background:transparent;transition:.15s}
.tab-btn.active{background:white;color:#4f46e5;box-shadow:0 2px 8px rgba(0,0,0,.1)}
.tab-btn:hover:not(.active){background:rgba(255,255,255,.6)}

.tbl-pel th{background:#1e293b;color:white;padding:8px 6px;font-size:.75rem;text-align:center;white-space:nowrap}
.tbl-pel td{padding:7px 6px;border-bottom:1px solid #f1f5f9;font-size:.82rem;vertical-align:middle}
.tbl-pel tr:hover td{background:#f8fafc}
.badge-aktif{background:#dcfce7;color:#15803d;padding:2px 10px;border-radius:20px;font-size:.75rem;font-weight:700}
.badge-nonaktif{background:#fee2e2;color:#991b1b;padding:2px 10px;border-radius:20px;font-size:.75rem;font-weight:700}
.num-box{display:inline-block;min-width:26px;height:26px;line-height:26px;border-radius:6px;background:#fef9c3;color:#854d0e;font-weight:800;font-size:.78rem;text-align:center;padding:0 4px}
.num-box.zero{background:#f1f5f9;color:#cbd5e1}
</style>

<!-- PAGE HEADER -->
<div class="page-header d-flex align-center" style="margin-bottom:16px">
    <div>
        <div class="page-title"><i class="fas fa-exclamation-triangle" style="color:#f59e0b"></i> Kelola Pelanggaran</div>
        <div class="page-subtitle">Jenis pelanggaran, input disiplin, dan rekap data</div>
    </div>
    <div class="ms-auto" style="display:flex;gap:8px">
        <a href="portal_disiplin_login.php" target="_blank" class="btn btn-warning" style="font-size:.82rem">
            <i class="fas fa-shield-alt"></i> Buka Portal Disiplin ↗
        </a>
    </div>
</div>

<?php if ($msg): [$t,$tx] = explode(':',$msg,2); ?>
<div class="alert alert-<?= $t ?>" style="margin-bottom:14px">
    <i class="fas fa-<?= $t==='success'?'check-circle':'exclamation-circle' ?>"></i> <?= htmlspecialchars($tx) ?>
</div>
<?php endif; ?>

<!-- TAB BAR -->
<div class="tab-bar">
    <button class="tab-btn <?= $page==='jenis'?'active':'' ?>" onclick="location='pelanggaran.php?tab=jenis'">
        <i class="fas fa-list"></i> Jenis Pelanggaran
    </button>
    <button class="tab-btn <?= $page==='harian'?'active':'' ?>" onclick="location='pelanggaran.php?tab=harian'">
        <i class="fas fa-calendar-day"></i> Rekap Harian
    </button>
    <button class="tab-btn <?= $page==='kalender'?'active':'' ?>" onclick="location='pelanggaran.php?tab=kalender'">
        <i class="fas fa-calendar-alt"></i> Rekap Kalender
    </button>
</div>

<?php // ═══════════════════ TAB: JENIS PELANGGARAN ═══════════════════
if ($page === 'jenis'): ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start">

    <!-- Form Tambah / Edit -->
    <div class="card">
        <div class="card-header" style="font-weight:700">
            <i class="fas fa-<?= $edit_jenis?'edit':'plus-circle' ?>" style="color:#4f46e5"></i>
            <?= $edit_jenis ? 'Edit Jenis Pelanggaran' : 'Tambah Jenis Pelanggaran' ?>
        </div>
        <div style="padding:20px">
            <form method="POST" action="pelanggaran.php?tab=jenis">
                <input type="hidden" name="save_jenis" value="1">
                <input type="hidden" name="jid" value="<?= $edit_jenis['id'] ?? 0 ?>">
                <div style="margin-bottom:14px">
                    <label class="form-label">Nama Pelanggaran <span style="color:red">*</span></label>
                    <input type="text" name="nama" class="form-control" required
                           value="<?= htmlspecialchars($edit_jenis['nama'] ?? '') ?>"
                           placeholder="Contoh: Terlambat, Merokok, Bolos...">
                </div>
                <div style="margin-bottom:14px">
                    <label class="form-label">Kode Singkatan <span style="color:red">*</span>
                        <small style="color:#64748b;font-weight:400">(maks. 5 huruf, contoh: TR, MR, KTI)</small>
                    </label>
                    <input type="text" name="kode" class="form-control" required maxlength="10"
                           value="<?= htmlspecialchars($edit_jenis['kode'] ?? '') ?>"
                           placeholder="Contoh: TR, MR, KTI, BL..."
                           style="width:140px;text-transform:uppercase;font-weight:700;letter-spacing:.05em"
                           oninput="this.value=this.value.toUpperCase()">
                </div>
                <div style="margin-bottom:14px">
                    <label class="form-label">Keterangan</label>
                    <input type="text" name="keterangan" class="form-control"
                           value="<?= htmlspecialchars($edit_jenis['keterangan'] ?? '') ?>"
                           placeholder="Deskripsi singkat (opsional)">
                </div>
                <div style="margin-bottom:20px">
                    <label class="form-label">Urutan Tampil</label>
                    <input type="number" name="urutan" class="form-control" min="0"
                           value="<?= $edit_jenis['urutan'] ?? 0 ?>"
                           style="width:100px" placeholder="0">
                </div>
                <div style="display:flex;gap:8px">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
                    <?php if ($edit_jenis): ?>
                    <a href="pelanggaran.php?tab=jenis" class="btn btn-secondary"><i class="fas fa-times"></i> Batal</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- PIN Disiplin -->
    <div class="card">
        <div class="card-header" style="font-weight:700">
            <i class="fas fa-key" style="color:#f59e0b"></i> PIN Portal Disiplin
        </div>
        <div style="padding:20px">
            <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:12px;margin-bottom:16px;font-size:.82rem;color:#92400e">
                <i class="fas fa-info-circle"></i>
                PIN ini digunakan untuk login ke <strong>Portal Disiplin</strong> dari halaman depan.
                PIN default: <strong>1234</strong>
            </div>
            <form method="POST" action="pelanggaran.php?tab=jenis">
                <input type="hidden" name="save_pin" value="1">
                <div style="margin-bottom:12px">
                    <label class="form-label">PIN Baru <small>(min. 4 digit angka)</small></label>
                    <input type="password" name="pin_baru" class="form-control" maxlength="8"
                           placeholder="Masukkan PIN baru" inputmode="numeric" pattern="[0-9]*">
                </div>
                <div style="margin-bottom:16px">
                    <label class="form-label">Konfirmasi PIN</label>
                    <input type="password" name="pin_konfirm" class="form-control" maxlength="8"
                           placeholder="Ulangi PIN" inputmode="numeric" pattern="[0-9]*">
                </div>
                <button type="submit" class="btn btn-warning"><i class="fas fa-key"></i> Simpan PIN</button>
            </form>
        </div>
    </div>
</div>

<!-- Tabel Jenis -->
<div class="card" style="margin-top:16px">
    <div class="card-header">
        <i class="fas fa-list" style="color:#4f46e5"></i>
        Daftar Jenis Pelanggaran (<?= $jenis_list ? $jenis_list->num_rows : 0 ?>)
    </div>
    <div class="table-container">
        <table class="tbl-pel" style="width:100%;border-collapse:collapse">
            <thead>
                <tr>
                    <th width="4%">#</th>
                    <th style="text-align:left">Nama Pelanggaran</th>
                    <th width="10%">Kode</th>
                    <th style="text-align:left">Keterangan</th>
                    <th width="8%">Urutan</th>
                    <th width="10%">Status</th>
                    <th width="16%">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$jenis_list || $jenis_list->num_rows === 0): ?>
            <tr><td colspan="6" style="text-align:center;padding:40px;color:#94a3b8">
                Belum ada jenis pelanggaran. Tambahkan di form kiri.
            </td></tr>
            <?php else: $no=0; while ($j=$jenis_list->fetch_assoc()): $no++; ?>
            <tr>
                <td style="text-align:center"><?= $no ?></td>
                <td><strong><?= htmlspecialchars($j['nama']) ?></strong></td>
                <td style="text-align:center">
                    <span style="background:#1e293b;color:#fef9c3;padding:3px 10px;border-radius:20px;font-size:.8rem;font-weight:800;letter-spacing:.05em">
                        <?= htmlspecialchars($j['kode'] ?: '—') ?>
                    </span>
                </td>
                <td style="color:#64748b"><?= htmlspecialchars($j['keterangan']) ?></td>
                <td style="text-align:center"><?= $j['urutan'] ?></td>
                <td style="text-align:center">
                    <span class="<?= $j['aktif'] ? 'badge-aktif' : 'badge-nonaktif' ?>">
                        <?= $j['aktif'] ? 'Aktif' : 'Nonaktif' ?>
                    </span>
                </td>
                <td style="display:flex;gap:4px">
                    <a href="pelanggaran.php?tab=jenis&edit=<?= $j['id'] ?>" class="btn btn-sm btn-secondary" title="Edit">
                        <i class="fas fa-edit"></i>
                    </a>
                    <a href="pelanggaran.php?tab=jenis&toggle=<?= $j['id'] ?>" class="btn btn-sm"
                       style="background:<?= $j['aktif']?'#f59e0b':'#10b981' ?>;color:white" title="<?= $j['aktif']?'Nonaktifkan':'Aktifkan' ?>">
                        <i class="fas fa-<?= $j['aktif']?'toggle-on':'toggle-off' ?>"></i>
                    </a>
                    <a href="pelanggaran.php?tab=jenis&hapus=<?= $j['id'] ?>" class="btn btn-sm btn-danger"
                       onclick="return confirm('Hapus jenis pelanggaran ini?')" title="Hapus">
                        <i class="fas fa-trash"></i>
                    </a>
                </td>
            </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php // ═══════════════════ TAB: REKAP HARIAN ═══════════════════
elseif ($page === 'harian'): ?>

<!-- Filter -->
<div class="card" style="margin-bottom:16px">
    <div class="card-body">
        <form method="GET" class="filter-bar">
            <input type="hidden" name="tab" value="harian">
            <div>
                <label class="form-label">Tanggal</label>
                <input type="date" name="tanggal" class="form-control" value="<?= $tgl_h ?>" max="<?= $today ?>">
            </div>
            <div>
                <label class="form-label">Kelas</label>
                <select name="kelas" class="form-select">
                    <option value="">-- Semua Kelas --</option>
                    <?php foreach ($kelas_list as $k): ?>
                    <option value="<?= $k ?>" <?= $kelas_h==$k?'selected':'' ?>><?= $k ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:flex;align-items:flex-end">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Tampilkan</button>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($rekap_harian) && !empty($jenis_all_arr)): ?>
<!-- Summary chips -->
<div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:14px">
    <span style="font-size:.8rem;color:#64748b;font-weight:600;align-self:center">Jenis Pelanggaran:</span>
    <?php
    $total_per_jenis = [];
    foreach ($rekap_harian as $s) foreach ($s['jenis'] as $jid => $jml) {
        $total_per_jenis[$jid] = ($total_per_jenis[$jid] ?? 0) + $jml;
    }
    foreach ($jenis_all_arr as $j):
        $tot = $total_per_jenis[$j['id']] ?? 0;
        if ($tot > 0):
    ?>
    <span style="background:#fef9c3;color:#854d0e;padding:4px 12px;border-radius:20px;font-size:.78rem;font-weight:700">
        <?= htmlspecialchars($j['nama']) ?>: <?= $tot ?>×
    </span>
    <?php endif; endforeach; ?>
    <span style="background:#e0e7ff;color:#3730a3;padding:4px 12px;border-radius:20px;font-size:.78rem;font-weight:700;margin-left:auto">
        <?= count($rekap_harian) ?> siswa
    </span>
</div>

<div class="card">
    <div class="card-header">
        <i class="fas fa-calendar-day" style="color:#f59e0b"></i>
        Rekap Pelanggaran Harian &mdash;
        <?php
        $hari_arr = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
        echo ($hari_arr[date('l',strtotime($tgl_h))]??'').', '.date('d',strtotime($tgl_h)).' '.$nama_bulan_arr[(int)date('m',strtotime($tgl_h))].' '.date('Y',strtotime($tgl_h));
        if ($kelas_h) echo ' &mdash; Kelas <strong>'.$kelas_h.'</strong>';
        ?>
        <div class="ms-auto">
            <input type="text" id="cariHarian" placeholder="🔍 Cari nama..." oninput="cariH()"
                   style="padding:5px 10px;border:1px solid #e2e8f0;border-radius:7px;font-size:.78rem;outline:none;width:160px">
        </div>
    </div>
    <div style="overflow-x:auto">
        <table id="tblHarian" class="tbl-pel" style="width:100%;border-collapse:collapse">
            <thead>
                <tr>
                    <th style="position:sticky;left:0;z-index:3;background:#1e293b;min-width:32px">#</th>
                    <th style="position:sticky;left:32px;z-index:3;background:#1e293b;text-align:left;min-width:170px">NAMA</th>
                    <th style="background:#1e293b;min-width:80px">KELAS</th>
                    <?php foreach ($jenis_all_arr as $j): ?>
                    <th style="background:#92400e;color:#fef9c3;min-width:70px;max-width:90px;line-height:1.3;font-size:.75rem"
                        title="<?= htmlspecialchars($j['nama']) ?>">
                        <?= htmlspecialchars($j['kode'] ?: $j['nama']) ?>
                        <div style="font-size:.6rem;opacity:.8;font-weight:400"><?= htmlspecialchars($j['nama']) ?></div>
                    </th>
                    <?php endforeach; ?>
                    <th style="background:#1e3a8a;min-width:70px">TOTAL</th>
                </tr>
            </thead>
            <tbody>
            <?php $no=0; foreach ($rekap_harian as $s): $no++;
                $total_row = array_sum($s['jenis']);
            ?>
            <tr>
                <td style="text-align:center;position:sticky;left:0;background:white;z-index:1"><?= $no ?></td>
                <td style="position:sticky;left:32px;background:white;z-index:1;font-weight:600"><?= htmlspecialchars($s['nama']) ?></td>
                <td style="text-align:center">
                    <span style="background:#e0e7ff;color:#3730a3;padding:2px 8px;border-radius:12px;font-size:.75rem;font-weight:600"><?= $s['kelas'] ?></span>
                </td>
                <?php foreach ($jenis_all_arr as $j):
                    $jml = $s['jenis'][$j['id']] ?? 0;
                ?>
                <td style="text-align:center">
                    <?php if ($jml > 0): ?>
                    <span class="num-box"><?= $jml ?></span>
                    <?php else: ?>
                    <span class="num-box zero">—</span>
                    <?php endif; ?>
                </td>
                <?php endforeach; ?>
                <td style="text-align:center">
                    <span style="background:#1e3a8a;color:white;padding:3px 10px;border-radius:12px;font-weight:700;font-size:.82rem"><?= $total_row ?></span>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php elseif (isset($_GET['tanggal'])): ?>
<div class="alert alert-warning"><i class="fas fa-info-circle"></i> Tidak ada data pelanggaran pada tanggal tersebut.</div>
<?php else: ?>
<div class="card"><div class="card-body" style="text-align:center;padding:60px">
    <i class="fas fa-calendar-day fa-3x" style="color:#e2e8f0;margin-bottom:16px;display:block"></i>
    <div style="color:#64748b">Pilih tanggal di atas untuk melihat rekap pelanggaran harian</div>
</div></div>
<?php endif; ?>

<?php // ═══════════════════ TAB: REKAP KALENDER ═══════════════════
elseif ($page === 'kalender'): ?>

<!-- Filter -->
<div class="card" style="margin-bottom:16px">
    <div class="card-body">
        <form method="GET" class="filter-bar">
            <input type="hidden" name="tab" value="kalender">
            <div>
                <label class="form-label">Bulan</label>
                <select name="bulan" class="form-select">
                    <?php for ($m=1;$m<=12;$m++): ?>
                    <option value="<?= $m ?>" <?= $bulan_kal==$m?'selected':'' ?>><?= $nama_bulan_arr[$m] ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label class="form-label">Tahun</label>
                <select name="tahun" class="form-select">
                    <?php for ($y=date('Y');$y>=date('Y')-3;$y--): ?>
                    <option value="<?= $y ?>" <?= $tahun_kal==$y?'selected':'' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label class="form-label">Kelas</label>
                <select name="kelas_kal" class="form-select">
                    <option value="">-- Semua Kelas --</option>
                    <?php foreach ($kelas_list as $k): ?>
                    <option value="<?= $k ?>" <?= $kelas_kal==$k?'selected':'' ?>><?= $k ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:flex;align-items:flex-end;gap:6px">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Tampilkan</button>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($rekap_kal)): ?>

<?php
// Data pengaturan untuk kop & TTD
$set_kal     = get_pengaturan();
$nm_sek_kal  = $set_kal['nama_sekolah'] ?? 'Nama Sekolah';
$alamat_kal  = $set_kal['alamat'] ?? '';
$kepala_kal  = $set_kal['kepala_sekolah'] ?? '';
$nip_kal     = $set_kal['nip_kepala'] ?? '';
$admin_kal   = $_SESSION['admin_nama'] ?? 'Guru BP';
// URL untuk export
$exp_url = 'pelanggaran.php?tab=kalender&export=excel&bulan='.$bulan_kal.'&tahun='.$tahun_kal.($kelas_kal?'&kelas_kal='.urlencode($kelas_kal):'');
?>

<!-- Action bar -->
<div style="display:flex;align-items:center;gap:8px;margin-bottom:12px">
    <span style="font-weight:700;color:#1e293b;font-size:.9rem">
        <i class="fas fa-calendar-alt" style="color:#4f46e5"></i>
        Rekap <?= $nama_bulan_arr[$bulan_kal].' '.$tahun_kal ?>
        <?php if ($kelas_kal) echo ' &mdash; Kelas <strong>'.$kelas_kal.'</strong>'; ?>
        — <span style="color:#10b981"><?= count($rekap_kal) ?> siswa</span>
    </span>
    <a href="<?= $exp_url ?>"
       style="margin-left:auto;display:inline-flex;align-items:center;gap:6px;padding:7px 16px;background:#16a34a;color:white;border-radius:8px;font-size:.82rem;font-weight:700;text-decoration:none">
        <i class="fas fa-file-excel"></i> Download Excel
    </a>
    <input type="text" id="cariKal" placeholder="🔍 Cari nama..." oninput="cariK()"
           style="padding:6px 10px;border:1px solid #e2e8f0;border-radius:7px;font-size:.78rem;outline:none;width:160px">
</div>

<div class="card">
    <div style="overflow-x:auto">
        <table id="tblKal" class="tbl-pel" style="width:100%;border-collapse:collapse">
            <thead>
                <!-- Kop Sekolah -->
                <tr>
                    <th colspan="<?= 3 + $max_hari + 1 ?>"
                        style="background:white;color:#1e293b;text-align:center;font-size:1rem;font-weight:800;padding:10px 6px;border-bottom:none">
                        REKAP PELANGGARAN SISWA
                    </th>
                </tr>
                <tr>
                    <th colspan="<?= 3 + $max_hari + 1 ?>"
                        style="background:white;color:#1e293b;text-align:center;font-size:.9rem;font-weight:700;padding:2px 6px;border-bottom:none">
                        <?= htmlspecialchars($nm_sek_kal) ?>
                    </th>
                </tr>
                <?php if ($alamat_kal): ?>
                <tr>
                    <th colspan="<?= 3 + $max_hari + 1 ?>"
                        style="background:white;color:#64748b;text-align:center;font-size:.75rem;font-weight:400;padding:2px 6px;border-bottom:2px solid #1e293b">
                        <?= htmlspecialchars($alamat_kal) ?>
                    </th>
                </tr>
                <?php else: ?>
                <tr><th colspan="<?= 3 + $max_hari + 1 ?>" style="background:white;border-bottom:2px solid #1e293b;padding:2px"></th></tr>
                <?php endif; ?>
                <!-- Header tabel -->
                <tr>
                    <th rowspan="2" style="background:#1e293b;min-width:28px">#</th>
                    <th rowspan="2" style="background:#1e293b;text-align:left;min-width:170px;position:sticky;left:28px;z-index:3">NAMA SISWA</th>
                    <th rowspan="2" style="background:#1e293b;min-width:70px">KELAS</th>
                    <th colspan="<?= $max_hari ?>" style="background:#374151;letter-spacing:.5px">
                        BULAN <?= strtoupper($nama_bulan_arr[$bulan_kal].' '.$tahun_kal) ?>
                    </th>
                    <th rowspan="2" style="background:#1e3a8a;min-width:50px">JUMLAH</th>
                </tr>
                <tr>
                    <?php for ($d=1; $d<=$max_hari; $d++): ?>
                    <th style="background:#374151;min-width:26px;font-size:.7rem"><?= $d ?></th>
                    <?php endfor; ?>
                </tr>
            </thead>
            <tbody>
            <?php $no=0; foreach ($rekap_kal as $s): $no++;
                $tot_row = array_sum($s['hari']);
            ?>
            <tr>
                <td style="text-align:center"><?= $no ?></td>
                <td style="font-weight:600;text-align:left;position:sticky;left:28px;background:white;z-index:1"><?= htmlspecialchars($s['nama']) ?></td>
                <td style="text-align:center">
                    <span style="background:#e0e7ff;color:#3730a3;padding:2px 8px;border-radius:12px;font-size:.73rem;font-weight:600"><?= $s['kelas'] ?></span>
                </td>
                <?php for ($d=1; $d<=$max_hari; $d++):
                    $jml = $s['hari'][$d] ?? 0;
                ?>
                <td style="text-align:center">
                    <?php if ($jml > 0): ?>
                    <span class="num-box"><?= $jml ?></span>
                    <?php else: ?>
                    <span class="num-box zero" style="font-size:.65rem">—</span>
                    <?php endif; ?>
                </td>
                <?php endfor; ?>
                <td style="text-align:center">
                    <span style="background:<?= $tot_row>0?'#1e3a8a':'#f1f5f9' ?>;color:<?= $tot_row>0?'white':'#94a3b8' ?>;padding:3px 10px;border-radius:12px;font-weight:700;font-size:.82rem"><?= $tot_row ?: '—' ?></span>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <!-- Tanda Tangan -->
            <tfoot>
                <tr>
                    <td colspan="<?= 3 + $max_hari + 1 ?>" style="border:none;padding:8px"></td>
                </tr>
                <tr>
                    <td colspan="<?= intdiv(3+$max_hari+1,2) ?>"
                        style="border:none;text-align:center;font-size:.82rem;padding:4px 6px">
                        Guru BP
                    </td>
                    <td colspan="<?= (3+$max_hari+1) - intdiv(3+$max_hari+1,2) ?>"
                        style="border:none;text-align:center;font-size:.82rem;padding:4px 6px">
                        Kepala Sekolah
                    </td>
                </tr>
                <tr>
                    <td colspan="<?= intdiv(3+$max_hari+1,2) ?>"
                        style="border:none;text-align:center;padding:50px 6px 4px;font-size:.82rem;font-weight:700">
                        <?= $admin_kal ? '<u>'.htmlspecialchars($admin_kal).'</u>' : '(.......................)' ?>
                    </td>
                    <td colspan="<?= (3+$max_hari+1) - intdiv(3+$max_hari+1,2) ?>"
                        style="border:none;text-align:center;padding:50px 6px 4px;font-size:.82rem;font-weight:700">
                        <?= $kepala_kal ? '<u>'.htmlspecialchars($kepala_kal).'</u>' : '(.......................)' ?>
                    </td>
                </tr>
                <?php if ($nip_kal): ?>
                <tr>
                    <td colspan="<?= intdiv(3+$max_hari+1,2) ?>" style="border:none;text-align:center;font-size:.75rem;color:#64748b;padding:2px"></td>
                    <td colspan="<?= (3+$max_hari+1) - intdiv(3+$max_hari+1,2) ?>"
                        style="border:none;text-align:center;font-size:.75rem;color:#64748b;padding:2px">
                        NIP. <?= htmlspecialchars($nip_kal) ?>
                    </td>
                </tr>
                <?php endif; ?>
            </tfoot>
        </table>
    </div>
</div>

<?php elseif (isset($_GET['bulan'])): ?>
<div class="alert alert-warning"><i class="fas fa-info-circle"></i> Tidak ada data pelanggaran untuk periode tersebut.</div>
<?php else: ?>
<div class="card"><div class="card-body" style="text-align:center;padding:60px">
    <i class="fas fa-calendar-alt fa-3x" style="color:#e2e8f0;margin-bottom:16px;display:block"></i>
    <div style="color:#64748b">Pilih bulan dan tahun untuk melihat rekap pelanggaran</div>
</div></div>
<?php endif; ?>

<?php endif; ?>

<script>
function cariH() {
    var q = document.getElementById('cariHarian').value.toLowerCase();
    document.querySelectorAll('#tblHarian tbody tr').forEach(function(tr){
        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}
function cariK() {
    var q = document.getElementById('cariKal').value.toLowerCase();
    document.querySelectorAll('#tblKal tbody tr').forEach(function(tr){
        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}
</script>

<?php include 'includes/footer.php'; ?>
