<?php
require_once 'includes/config.php';

$current_page = 'atur_pin';
// Harus login sebagai admin
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }

$admin_id = $_SESSION['admin_id'];
$msg = '';
$msg_type = '';

// Proses ganti PIN Admin
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['aksi'])) {
    $aksi = $_POST['aksi'];

    // Ganti PIN Kepala Sekolah (kolom kepsek_pin)
    if ($aksi === 'pin_kepsek') {
        $pin_baru    = $_POST['pin_kepsek_baru'] ?? '';
        $pin_konfirm = $_POST['pin_kepsek_konfirm'] ?? '';
        if (strlen($pin_baru) !== 4 || !ctype_digit($pin_baru)) {
            $msg = 'PIN harus 4 digit angka!'; $msg_type='danger';
        } elseif ($pin_baru !== $pin_konfirm) {
            $msg = 'PIN konfirmasi tidak cocok!'; $msg_type='danger';
        } else {
            // Auto-create kolom jika belum ada
            $conn->query("ALTER TABLE admin ADD COLUMN IF NOT EXISTS kepsek_pin VARCHAR(255) DEFAULT NULL");
            $hashed = password_hash($pin_baru, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE admin SET kepsek_pin=? WHERE id=?");
            $stmt->bind_param("si", $hashed, $admin_id);
            $stmt->execute(); $stmt->close();
            $msg = 'PIN Kepala Sekolah berhasil diperbarui!'; $msg_type='success';
        }
    }

    // Ganti PIN admin sendiri
    if ($aksi === 'pin_admin') {
        $pin_baru = $_POST['pin_baru'] ?? '';
        $pin_konfirm = $_POST['pin_konfirm'] ?? '';
        if (strlen($pin_baru) !== 4 || !ctype_digit($pin_baru)) {
            $msg = 'PIN harus 4 digit angka!'; $msg_type='danger';
        } elseif ($pin_baru !== $pin_konfirm) {
            $msg = 'PIN konfirmasi tidak cocok!'; $msg_type='danger';
        } else {
            $hashed = password_hash($pin_baru, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE admin SET pin=? WHERE id=?");
            $stmt->bind_param("si", $hashed, $admin_id);
            $stmt->execute(); $stmt->close();
            $msg = 'PIN Admin berhasil diperbarui!'; $msg_type='success';
        }
    }

    // Ganti PIN Wali
    if ($aksi === 'pin_wali') {
        $wali_id = (int)$_POST['wali_id'];
        $pin_wali = $_POST['pin_wali'] ?? '';
        if (strlen($pin_wali) !== 4 || !ctype_digit($pin_wali)) {
            $msg = 'PIN harus 4 digit angka!'; $msg_type='danger';
        } else {
            $hashed = password_hash($pin_wali, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE wali SET pin=? WHERE id=?");
            $stmt->bind_param("si", $hashed, $wali_id);
            $stmt->execute(); $stmt->close();
            $msg = 'PIN Wali berhasil diperbarui!'; $msg_type='success';
        }
    }

    // Ganti PIN Guru BK
    if ($aksi === 'pin_bk') {
        $bk_id_t   = (int)$_POST['bk_id'];
        $pin_bk    = $_POST['pin_bk'] ?? '';
        if (strlen($pin_bk) !== 4 || !ctype_digit($pin_bk)) {
            $msg = 'PIN harus 4 digit angka!'; $msg_type='danger';
        } else {
            $hashed = password_hash($pin_bk, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE guru_bk SET pin=? WHERE id=?");
            $stmt->bind_param("si", $hashed, $bk_id_t);
            $stmt->execute(); $stmt->close();
            $msg = 'PIN Guru BK berhasil diperbarui!'; $msg_type='success';
        }
    }

    // Tambah Guru BK baru
    if ($aksi === 'tambah_bk') {
        $nama_bk = trim($_POST['nama_bk'] ?? '');
        $nip_bk  = trim($_POST['nip_bk'] ?? '');
        $pin_new  = $_POST['pin_bk_new'] ?? '1234';
        if ($nama_bk) {
            $hashed = password_hash($pin_new, PASSWORD_DEFAULT);
            $nb = $conn->real_escape_string($nama_bk);
            $np = $conn->real_escape_string($nip_bk);
            $conn->query("INSERT INTO guru_bk (nama,nip,pin) VALUES ('$nb','$np','$hashed')");
            $msg = 'Guru BK berhasil ditambahkan!'; $msg_type='success';
        }
    }

    // Hapus Guru BK
    if ($aksi === 'hapus_bk') {
        $bk_id_h = (int)$_POST['bk_id'];
        $conn->query("DELETE FROM guru_bk WHERE id=$bk_id_h");
        $msg = 'Guru BK berhasil dihapus!'; $msg_type='success';
    }

    // Reset PIN Wali (hapus PIN, kembali ke password)
    if ($aksi === 'reset_pin_wali') {
        $wali_id = (int)$_POST['wali_id'];
        $stmt = $conn->prepare("UPDATE wali SET pin=NULL WHERE id=?");
        $stmt->bind_param("i", $wali_id);
        $stmt->execute(); $stmt->close();
        $msg = 'PIN Wali berhasil direset ke password!'; $msg_type='info';
    }
}

// Ambil data admin
$admin = $conn->query("SELECT * FROM admin WHERE id=$admin_id")->fetch_assoc();

// Ambil semua wali
$walis = $conn->query("SELECT * FROM wali ORDER BY nama")->fetch_all(MYSQLI_ASSOC);

// Auto-create tabel guru_bk jika belum ada
$conn->query("CREATE TABLE IF NOT EXISTS guru_bk (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    nip  VARCHAR(30) DEFAULT '',
    pin  VARCHAR(255) NOT NULL,
    foto VARCHAR(100) DEFAULT '',
    aktif TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$cek_bk = $conn->query("SELECT COUNT(*) c FROM guru_bk")->fetch_assoc();
if ((int)$cek_bk['c'] === 0) {
    $conn->query("INSERT INTO guru_bk (nama,nip,pin) VALUES ('Guru BK','-','".password_hash('1234',PASSWORD_DEFAULT)."')");
}
// Ambil semua Guru BK
$guru_bk_list = $conn->query("SELECT * FROM guru_bk ORDER BY nama")->fetch_all(MYSQLI_ASSOC);

$pengaturan = get_pengaturan();
include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <div class="d-flex align-items-center mb-4">
                <a href="dashboard.php" class="btn btn-light me-3"><i class="fas fa-arrow-left"></i></a>
                <div>
                    <h4 class="mb-0 fw-bold"><i class="fas fa-key text-primary me-2"></i>Kelola PIN Login</h4>
                    <small class="text-muted">Atur PIN untuk login Admin, Wali Kelas, Kepala Sekolah, dan Guru BK</small>
                </div>
            </div>

            <?php if ($msg): ?>
            <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show">
                <i class="fas fa-<?= $msg_type==='success'?'check-circle':($msg_type==='danger'?'exclamation-circle':'info-circle') ?>"></i>
                <?= htmlspecialchars($msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- PIN ADMIN -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-warning bg-opacity-10 border-0">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-user-shield text-warning me-2"></i>PIN Admin</h5>
                    <small class="text-muted">Digunakan untuk login tab Admin saja</small>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="aksi" value="pin_admin">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">PIN Baru <span class="text-danger">*</span></label>
                                <input type="text" name="pin_baru" class="form-control form-control-lg text-center fw-bold"
                                    placeholder="● ● ● ●" maxlength="4" pattern="[0-9]{4}"
                                    style="letter-spacing:8px;font-size:1.5rem" required
                                    oninput="this.value=this.value.replace(/\D/g,'')">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Konfirmasi PIN <span class="text-danger">*</span></label>
                                <input type="text" name="pin_konfirm" class="form-control form-control-lg text-center fw-bold"
                                    placeholder="● ● ● ●" maxlength="4" pattern="[0-9]{4}"
                                    style="letter-spacing:8px;font-size:1.5rem" required
                                    oninput="this.value=this.value.replace(/\D/g,'')">
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-warning w-100 fw-bold">
                                    <i class="fas fa-save me-1"></i> Simpan PIN Admin
                                </button>
                            </div>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted"><i class="fas fa-info-circle"></i> Status PIN: 
                                <?php if (!empty($admin['pin'])): ?>
                                    <span class="badge bg-success">Sudah diatur</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Belum diatur (menggunakan password)</span>
                                <?php endif; ?>
                            </small>
                        </div>
                    </form>
                </div>
            </div>

            <!-- PIN KEPALA SEKOLAH -->
            <?php
            $kepsek_pin_col = false;
            $chkk = $conn->query("SHOW COLUMNS FROM admin LIKE 'kepsek_pin'");
            if ($chkk && $chkk->num_rows > 0) $kepsek_pin_col = true;
            ?>
            <div class="card shadow-sm mb-4" style="border-left:4px solid #7c3aed">
                <div class="card-header border-0" style="background:rgba(124,58,237,.07)">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-user-tie me-2" style="color:#7c3aed"></i>PIN Kepala Sekolah</h5>
                    <small class="text-muted">PIN khusus untuk login Portal Kepala Sekolah (terpisah dari PIN Admin)</small>
                </div>
                <div class="card-body">
                    <?php if (!$kepsek_pin_col): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Kolom <code>kepsek_pin</code> belum ada. Jalankan <strong>migration_pin.sql</strong> dulu, atau atur PIN langsung — sistem akan otomatis menambahkan kolom.
                    </div>
                    <?php endif; ?>
                    <form method="POST">
                        <input type="hidden" name="aksi" value="pin_kepsek">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">PIN Baru <span class="text-danger">*</span></label>
                                <input type="text" name="pin_kepsek_baru" class="form-control form-control-lg text-center fw-bold"
                                    placeholder="● ● ● ●" maxlength="4" pattern="[0-9]{4}"
                                    style="letter-spacing:8px;font-size:1.5rem" required
                                    oninput="this.value=this.value.replace(/\D/g,'')">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Konfirmasi PIN <span class="text-danger">*</span></label>
                                <input type="text" name="pin_kepsek_konfirm" class="form-control form-control-lg text-center fw-bold"
                                    placeholder="● ● ● ●" maxlength="4" pattern="[0-9]{4}"
                                    style="letter-spacing:8px;font-size:1.5rem" required
                                    oninput="this.value=this.value.replace(/\D/g,'')">
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn w-100 fw-bold text-white" style="background:#7c3aed">
                                    <i class="fas fa-save me-1"></i> Simpan PIN Kepsek
                                </button>
                            </div>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted"><i class="fas fa-info-circle"></i> Status PIN Kepsek:
                                <?php if ($kepsek_pin_col && !empty($admin['kepsek_pin'])): ?>
                                    <span class="badge bg-success">Sudah diatur</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Belum diatur (menggunakan password)</span>
                                <?php endif; ?>
                            </small>
                        </div>
                    </form>
                </div>
            </div>

            <!-- PIN WALI -->
            <div class="card shadow-sm">
                <div class="card-header bg-primary bg-opacity-10 border-0">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-chalkboard-teacher text-primary me-2"></i>PIN Wali Kelas</h5>
                    <small class="text-muted">Atur PIN untuk masing-masing wali kelas / guru</small>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($walis)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-users fa-2x mb-2"></i><br>Belum ada data wali kelas
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nama</th>
                                    <th>Username</th>
                                    <th>Status PIN</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($walis as $w): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($w['nama']) ?></strong></td>
                                <td><code><?= htmlspecialchars($w['username']) ?></code></td>
                                <td>
                                    <?php if (!empty($w['pin'])): ?>
                                        <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Sudah ada PIN</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><i class="fas fa-lock me-1"></i>Pakai password</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="showSetPin(<?= $w['id'] ?>, '<?= htmlspecialchars($w['nama']) ?>')">
                                        <i class="fas fa-key me-1"></i>Set PIN
                                    </button>
                                    <?php if (!empty($w['pin'])): ?>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Reset PIN wali ini?')">
                                        <input type="hidden" name="aksi" value="reset_pin_wali">
                                        <input type="hidden" name="wali_id" value="<?= $w['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-undo me-1"></i>Reset
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

            <!-- PIN GURU BK -->
            <div class="card shadow-sm mt-4">
                <div class="card-header border-0" style="background:linear-gradient(135deg,#e0f2fe,#ccfbf1)">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="mb-0 fw-bold"><i class="fas fa-user-shield me-2" style="color:#0e7490"></i>PIN Guru BK</h5>
                            <small class="text-muted">Atur PIN untuk login Portal Guru BK</small>
                        </div>
                        <button class="btn btn-sm" style="background:#0e7490;color:white;font-weight:600"
                                onclick="document.getElementById('formTambahBK').style.display=document.getElementById('formTambahBK').style.display==='none'?'block':'none'">
                            <i class="fas fa-plus me-1"></i>Tambah BK
                        </button>
                    </div>
                </div>
                <div class="card-body" id="formTambahBK" style="display:none;border-bottom:1px solid #e2e8f0;background:#f8fafc">
                    <form method="POST" class="row g-2 align-items-end">
                        <input type="hidden" name="aksi" value="tambah_bk">
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Nama Guru BK <span class="text-danger">*</span></label>
                            <input type="text" name="nama_bk" class="form-control" placeholder="Nama lengkap" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small">NIP</label>
                            <input type="text" name="nip_bk" class="form-control" placeholder="NIP (opsional)">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold small">PIN Awal</label>
                            <input type="text" name="pin_bk_new" class="form-control text-center fw-bold"
                                   value="1234" maxlength="4" pattern="[0-9]{4}"
                                   oninput="this.value=this.value.replace(/\D/g,'')" style="letter-spacing:6px">
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn w-100 fw-bold" style="background:#0e7490;color:white">
                                <i class="fas fa-save me-1"></i>Simpan
                            </button>
                        </div>
                    </form>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($guru_bk_list)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-user-shield fa-2x mb-2"></i><br>Belum ada Guru BK
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr><th>Nama</th><th>NIP</th><th>Status PIN</th><th>Aksi</th></tr>
                            </thead>
                            <tbody>
                            <?php foreach ($guru_bk_list as $bk): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($bk['nama']) ?></strong></td>
                                <td><code><?= htmlspecialchars($bk['nip'] ?: '-') ?></code></td>
                                <td><span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Sudah ada PIN</span></td>
                                <td>
                                    <button class="btn btn-sm fw-bold" style="background:#0e7490;color:white"
                                            onclick="showSetPinBK(<?= $bk['id'] ?>, '<?= htmlspecialchars($bk['nama']) ?>')">
                                        <i class="fas fa-key me-1"></i>Set PIN
                                    </button>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Hapus Guru BK ini?')">
                                        <input type="hidden" name="aksi" value="hapus_bk">
                                        <input type="hidden" name="bk_id" value="<?= $bk['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash me-1"></i>Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Modal Set PIN Wali -->
<div class="modal fade" id="modalSetPin" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h6 class="modal-title fw-bold"><i class="fas fa-key me-2"></i>Set PIN Wali</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="formSetPin">
                <input type="hidden" name="aksi" value="pin_wali">
                <input type="hidden" name="wali_id" id="modalWaliId">
                <div class="modal-body text-center">
                    <p class="mb-3">Wali: <strong id="modalWaliNama"></strong></p>
                    <label class="form-label fw-bold">PIN Baru (4 digit)</label>
                    <input type="text" name="pin_wali" id="modalPinInput"
                        class="form-control form-control-lg text-center fw-bold"
                        placeholder="● ● ● ●" maxlength="4" pattern="[0-9]{4}"
                        style="letter-spacing:10px;font-size:1.8rem" required
                        oninput="this.value=this.value.replace(/\D/g,'')">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary fw-bold"><i class="fas fa-save me-1"></i>Simpan PIN</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Set PIN BK -->
<div class="modal fade" id="modalSetPinBK" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header text-white" style="background:#0e7490">
                <h6 class="modal-title fw-bold"><i class="fas fa-key me-2"></i>Set PIN Guru BK</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="formSetPinBK">
                <input type="hidden" name="aksi" value="pin_bk">
                <input type="hidden" name="bk_id" id="modalBKId">
                <div class="modal-body text-center">
                    <p class="mb-3">Guru BK: <strong id="modalBKNama"></strong></p>
                    <label class="form-label fw-bold">PIN Baru (4 digit)</label>
                    <input type="text" name="pin_bk" id="modalPinBKInput"
                        class="form-control form-control-lg text-center fw-bold"
                        placeholder="● ● ● ●" maxlength="4" pattern="[0-9]{4}"
                        style="letter-spacing:10px;font-size:1.8rem" required
                        oninput="this.value=this.value.replace(/\D/g,'')">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn fw-bold text-white" style="background:#0e7490"><i class="fas fa-save me-1"></i>Simpan PIN</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showSetPin(id, nama) {
    document.getElementById('modalWaliId').value = id;
    document.getElementById('modalWaliNama').textContent = nama;
    document.getElementById('modalPinInput').value = '';
    new bootstrap.Modal(document.getElementById('modalSetPin')).show();
    setTimeout(function(){ document.getElementById('modalPinInput').focus(); }, 400);
}
function showSetPinBK(id, nama) {
    document.getElementById('modalBKId').value = id;
    document.getElementById('modalBKNama').textContent = nama;
    document.getElementById('modalPinBKInput').value = '';
    new bootstrap.Modal(document.getElementById('modalSetPinBK')).show();
    setTimeout(function(){ document.getElementById('modalPinBKInput').focus(); }, 400);
}
</script>

<?php include 'includes/footer.php'; ?>
