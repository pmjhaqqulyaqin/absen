<?php
require_once 'includes/config.php';
cek_login();

// ── Migrasi aman: tambah kolom kelas_wali jika belum ada ──────────────
$chk = $conn->query("SHOW COLUMNS FROM `wali` LIKE 'kelas_wali'");
if ($chk && $chk->num_rows === 0) {
    $conn->query("ALTER TABLE `wali` ADD COLUMN `kelas_wali` VARCHAR(30) DEFAULT ''");
}

// Pastikan kolom no_hp ada
$chk2 = $conn->query("SHOW COLUMNS FROM `wali` LIKE 'no_hp'");
if ($chk2 && $chk2->num_rows === 0) {
    $conn->query("ALTER TABLE `wali` ADD COLUMN `no_hp` VARCHAR(20) DEFAULT ''");
}

// Pastikan kolom foto_wali ada
$chk3 = $conn->query("SHOW COLUMNS FROM `wali` LIKE 'foto_wali'");
if ($chk3 && $chk3->num_rows === 0) {
    $conn->query("ALTER TABLE `wali` ADD COLUMN `foto_wali` VARCHAR(255) DEFAULT ''");
}

// Pastikan kolom pin ada (dipakai untuk login PIN Portal Wali)
$chk4 = $conn->query("SHOW COLUMNS FROM `wali` LIKE 'pin'");
if ($chk4 && $chk4->num_rows === 0) {
    $conn->query("ALTER TABLE `wali` ADD COLUMN `pin` VARCHAR(255) DEFAULT NULL");
}

// Pastikan kolom pin_plain ada (supaya PIN bisa ditampilkan apa adanya di Kelola PIN)
$chk5 = $conn->query("SHOW COLUMNS FROM `wali` LIKE 'pin_plain'");
if ($chk5 && $chk5->num_rows === 0) {
    $conn->query("ALTER TABLE `wali` ADD COLUMN `pin_plain` VARCHAR(10) DEFAULT NULL");
}

// Pastikan folder uploads/foto_wali ada
$foto_wali_dir = __DIR__ . '/uploads/foto_wali/';
if (!is_dir($foto_wali_dir)) {
    mkdir($foto_wali_dir, 0755, true);
    file_put_contents($foto_wali_dir . 'index.php', '<?php // empty ?>');
}

// Pastikan tabel kelas ada
$conn->query("CREATE TABLE IF NOT EXISTS `kelas` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kelas VARCHAR(30) NOT NULL UNIQUE,
    tingkat VARCHAR(10) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── AJAX: Kelola wali_siswa ─────────────────────────────────────────────
if (isset($_POST['ajax_kelola_siswa'])) {
    header('Content-Type: application/json');
    $wali_id   = (int)($_POST['wali_id'] ?? 0);
    $siswa_ids = isset($_POST['siswa_ids']) && is_array($_POST['siswa_ids'])
                 ? array_map('intval', $_POST['siswa_ids']) : [];
    if ($wali_id) {
        $conn->query("DELETE FROM wali_siswa WHERE wali_id=$wali_id");
        foreach ($siswa_ids as $sid) {
            if ($sid > 0) $conn->query("INSERT IGNORE INTO wali_siswa (wali_id,siswa_id) VALUES ($wali_id,$sid)");
        }
        echo json_encode(['ok'=>true,'jumlah'=>count($siswa_ids)]);
    } else {
        echo json_encode(['ok'=>false]);
    }
    exit;
}

// ── AJAX: Ambil daftar siswa per wali ─────────────────────────────────
if (isset($_GET['ajax_siswa_wali']) && isset($_GET['wali_id'])) {
    header('Content-Type: application/json');
    $wali_id  = (int)$_GET['wali_id'];
    $assigned = [];
    $res = $conn->query("SELECT siswa_id FROM wali_siswa WHERE wali_id=$wali_id");
    while ($r = $res->fetch_assoc()) $assigned[] = (int)$r['siswa_id'];
    $all = [];
    $res2 = $conn->query("SELECT id,nis,nama,kelas FROM siswa WHERE aktif=1 ORDER BY kelas,nama");
    while ($r = $res2->fetch_assoc()) {
        $r['assigned'] = in_array((int)$r['id'], $assigned);
        $all[] = $r;
    }
    echo json_encode(['siswa'=>$all]);
    exit;
}

$msg     = '';
$action  = $_GET['action']  ?? '';
$show    = $_GET['show']    ?? '';
$edit_id = (int)($_GET['edit_id'] ?? 0);
$edit_data = null;

// ── HAPUS ────────────────────────────────────────────────────────────
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $conn->query("DELETE FROM wali_siswa WHERE wali_id=$id");
    $conn->query("DELETE FROM wali WHERE id=$id");
    header('Location: wali.php?msg=deleted'); exit;
}

// ── RESET PASSWORD ────────────────────────────────────────────────────
if ($action === 'reset_pw' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $w  = $conn->query("SELECT username FROM wali WHERE id=$id")->fetch_assoc();
    if ($w) {
        $hash = password_hash($w['username'], PASSWORD_DEFAULT);
        $conn->query("UPDATE wali SET password='$hash' WHERE id=$id");
        $msg = 'success:Password direset ke username: '.$w['username'];
    }
}

// ── SET / UBAH PIN WALI (langsung dari halaman ini) ───────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_pin_wali'])) {
    $pin_id  = (int)($_POST['pin_wali_id'] ?? 0);
    $pin_new = trim($_POST['pin_wali_value'] ?? '');
    if (!$pin_id || !ctype_digit($pin_new) || strlen($pin_new) !== 4) {
        $msg = 'error:PIN harus 4 digit angka!';
    } else {
        $pin_hashed = password_hash($pin_new, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE wali SET pin=?, pin_plain=? WHERE id=?");
        $stmt->bind_param("ssi", $pin_hashed, $pin_new, $pin_id);
        $stmt->execute(); $stmt->close();
        $msg = 'success:PIN wali berhasil diperbarui menjadi '.$pin_new;
    }
}

// ── SIMPAN (tambah/edit) ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_wali'])) {
    $id         = (int)($_POST['id'] ?? 0);
    $nama       = sanitize($_POST['nama'] ?? '');
    $jabatan    = sanitize($_POST['jabatan'] ?? 'Wali Kelas');
    $kelas_wali = sanitize($_POST['kelas_wali'] ?? '');
    $no_hp      = sanitize($_POST['no_hp'] ?? '');
    $pin_new    = trim($_POST['pin_wali_new'] ?? '1234');
    if (!ctype_digit($pin_new) || strlen($pin_new) !== 4) $pin_new = '1234';

    // ── Handle upload foto wali ──────────────────────────────────────
    $foto_wali_sql = '';
    if (!empty($_FILES['foto_wali']['name']) && $_FILES['foto_wali']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $ftype = mime_content_type($_FILES['foto_wali']['tmp_name']);
        if (in_array($ftype, $allowed_types) && $_FILES['foto_wali']['size'] <= 2 * 1024 * 1024) {
            $ext     = pathinfo($_FILES['foto_wali']['name'], PATHINFO_EXTENSION);
            $newname = 'wali_' . ($id ?: time()) . '_' . time() . '.' . strtolower($ext);
            $dest    = __DIR__ . '/uploads/foto_wali/' . $newname;
            if (move_uploaded_file($_FILES['foto_wali']['tmp_name'], $dest)) {
                // Hapus foto lama jika ada
                if ($id) {
                    $old = $conn->query("SELECT foto_wali FROM wali WHERE id=$id")->fetch_assoc();
                    if ($old && $old['foto_wali'] && file_exists(__DIR__ . '/uploads/foto_wali/' . $old['foto_wali'])) {
                        @unlink(__DIR__ . '/uploads/foto_wali/' . $old['foto_wali']);
                    }
                }
                $foto_wali_sql = ", foto_wali='" . $conn->real_escape_string($newname) . "'";
            }
        } else {
            $msg = 'error:Foto tidak valid. Gunakan JPG/PNG/GIF/WEBP maks 2MB';
        }
    }
    // Hapus foto jika checkbox hapus dicentang
    if (isset($_POST['hapus_foto']) && $_POST['hapus_foto'] == '1' && $id) {
        $old = $conn->query("SELECT foto_wali FROM wali WHERE id=$id")->fetch_assoc();
        if ($old && $old['foto_wali'] && file_exists(__DIR__ . '/uploads/foto_wali/' . $old['foto_wali'])) {
            @unlink(__DIR__ . '/uploads/foto_wali/' . $old['foto_wali']);
        }
        $foto_wali_sql = ", foto_wali=''";
    }

    if (!$nama) {
        $msg  = 'error:Nama wajib diisi';
        $show = $id ? 'edit' : 'add';
        if ($id) $edit_id = $id;
    } else {
        $has_kw = $conn->query("SHOW COLUMNS FROM `wali` LIKE 'kelas_wali'")->num_rows > 0;
        if ($id) {
            // Edit: username & PIN tidak diubah di sini (PIN diatur lewat
            // tombol Set PIN / Kelola PIN Login).
            $no_hp_safe = $conn->real_escape_string($no_hp);
            if ($has_kw)
                $conn->query("UPDATE wali SET nama='$nama',jabatan='$jabatan',kelas_wali='$kelas_wali',no_hp='$no_hp_safe'$foto_wali_sql WHERE id=$id");
            else
                $conn->query("UPDATE wali SET nama='$nama',jabatan='$jabatan',no_hp='$no_hp_safe'$foto_wali_sql WHERE id=$id");
            $msg = 'success:Data wali kelas berhasil diupdate';
        } else {
            // Tambah: username dibuat otomatis di belakang layar (tidak perlu
            // diisi manual) — login wali kelas sepenuhnya pakai PIN.
            $username   = 'wali_' . strtolower(preg_replace('/[^a-z0-9]+/i', '', $nama)) . '_' . substr(uniqid(), -4);
            $pw         = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT); // password acak, tidak dipakai (login pakai PIN)
            $pin_hashed = password_hash($pin_new, PASSWORD_DEFAULT);
            $pin_plain_safe = $conn->real_escape_string($pin_new);
            $no_hp_safe = $conn->real_escape_string($no_hp);
            if ($has_kw)
                $r = $conn->query("INSERT INTO wali (username,password,pin,pin_plain,nama,jabatan,kelas_wali,no_hp) VALUES ('$username','$pw','$pin_hashed','$pin_plain_safe','$nama','$jabatan','$kelas_wali','$no_hp_safe')");
            else
                $r = $conn->query("INSERT INTO wali (username,password,pin,pin_plain,nama,jabatan,no_hp) VALUES ('$username','$pw','$pin_hashed','$pin_plain_safe','$nama','$jabatan','$no_hp_safe')");
            // Update foto setelah INSERT berhasil
            if ($r && $foto_wali_sql) {
                $new_id = $conn->insert_id;
                $conn->query("UPDATE wali SET foto_wali=''$foto_wali_sql WHERE id=$new_id");
            }
            $msg = $r ? 'success:Wali kelas berhasil ditambahkan. PIN awal: '.$pin_new : 'error:Terjadi kesalahan saat menyimpan';
        }
    }
}

// Notif dari redirect
if (!$msg && isset($_GET['msg'])) {
    if ($_GET['msg'] === 'deleted') $msg = 'success:Wali kelas berhasil dihapus';
}

// Load edit data
if ($show === 'edit' && $edit_id) {
    $edit_data = $conn->query("SELECT * FROM wali WHERE id=$edit_id")->fetch_assoc();
    if (!$edit_data) { $show = ''; $edit_id = 0; }
}

$wali_list  = $conn->query("SELECT w.*,
    (SELECT COUNT(*) FROM wali_siswa ws WHERE ws.wali_id=w.id) as jumlah_anak
    FROM wali w ORDER BY w.nama");

// Ambil daftar kelas dari tabel siswa (data yang sudah ada)
$kelas_arr  = [];
$kelas_q = $conn->query("SELECT DISTINCT kelas FROM siswa WHERE aktif=1 ORDER BY kelas");
if ($kelas_q && $kelas_q->num_rows > 0) {
    while ($k = $kelas_q->fetch_assoc()) $kelas_arr[] = $k['kelas'];
} else {
    // Fallback: dari tabel kelas jika ada
    $kelas_q2 = $conn->query("SELECT nama_kelas FROM kelas ORDER BY nama_kelas");
    if ($kelas_q2) while ($k = $kelas_q2->fetch_assoc()) $kelas_arr[] = $k['nama_kelas'];
}

include 'includes/header.php';
?>

<?php if ($msg): list($t,$tx) = explode(':',$msg,2); ?>
<div class="alert alert-<?= $t ?>" style="margin-bottom:16px">
    <i class="fas fa-<?= $t==='success'?'check-circle':'exclamation-circle' ?>"></i>
    <?= htmlspecialchars($tx) ?>
</div>
<?php endif; ?>

<!-- PAGE HEADER -->
<div class="page-header d-flex align-center" style="margin-bottom:20px">
    <div>
        <div class="page-title"><i class="fas fa-chalkboard-teacher"></i> Kelola Wali Kelas</div>
        <div class="page-subtitle">Manajemen akun wali kelas untuk Portal Wali</div>
    </div>
    <div class="ms-auto">
        <a href="wali.php?show=add" class="btn btn-primary">
            <i class="fas fa-plus"></i> Tambah Wali
        </a>
    </div>
</div>

<!-- FORM TAMBAH / EDIT (inline, tanpa JS modal) -->
<?php if ($show === 'add' || $show === 'edit'): ?>
<div class="card" style="margin-bottom:20px;border-top:4px solid var(--primary)">
    <div class="card-header" style="font-weight:700">
        <i class="fas fa-<?= $show==='edit'?'edit':'user-plus' ?>" style="color:var(--primary)"></i>
        <?= $show==='edit' ? 'Edit Wali Kelas' : 'Tambah Wali Kelas Baru' ?>
    </div>
    <div style="padding:24px">
        <form method="POST" action="wali.php" enctype="multipart/form-data">
            <input type="hidden" name="save_wali" value="1">
            <input type="hidden" name="id" value="<?= $edit_id ?>">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
                <div>
                    <label class="form-label">Nama Lengkap <span style="color:red">*</span></label>
                    <input type="text" name="nama" class="form-control"
                           value="<?= htmlspecialchars($edit_data['nama'] ?? '') ?>"
                           placeholder="Nama wali kelas" required>
                </div>
                <?php if ($show === 'add'): ?>
                <div>
                    <label class="form-label">PIN Awal (4 digit)</label>
                    <input type="text" name="pin_wali_new" class="form-control text-center fw-bold"
                           value="1234" maxlength="4" pattern="[0-9]{4}"
                           oninput="this.value=this.value.replace(/\D/g,'')" style="letter-spacing:6px">
                    <small style="color:var(--text-muted)">Wali kelas login pakai PIN ini di Portal Wali</small>
                </div>
                <?php endif; ?>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
                <div>
                    <label class="form-label">Jabatan</label>
                    <input type="text" name="jabatan" class="form-control"
                           value="<?= htmlspecialchars($edit_data['jabatan'] ?? 'Wali Kelas') ?>">
                </div>
                <div>
                    <label class="form-label">Kelas yang Dipegang</label>
                    <select name="kelas_wali" class="form-select">
                        <option value="">-- Pilih Kelas --</option>
                        <?php foreach ($kelas_arr as $kn): ?>
                        <option value="<?= htmlspecialchars($kn) ?>"
                            <?= (isset($edit_data['kelas_wali']) && $edit_data['kelas_wali']===$kn)?'selected':'' ?>>
                            <?= htmlspecialchars($kn) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div style="margin-bottom:12px">
                <label class="form-label">No. HP / WhatsApp</label>
                <input type="text" name="no_hp" class="form-control"
                       value="<?= htmlspecialchars($edit_data['no_hp'] ?? '') ?>"
                       placeholder="Contoh: 08123456789">
            </div>

            <!-- FOTO WALI -->
            <div style="margin-bottom:16px">
                <label class="form-label">Foto Wali Kelas
                    <small style="color:var(--text-muted)">(JPG/PNG/WEBP maks 2MB, opsional)</small>
                </label>
                <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
                    <?php
                    $foto_preview = $edit_data['foto_wali'] ?? '';
                    $foto_path    = 'uploads/foto_wali/' . $foto_preview;
                    ?>
                    <?php if ($foto_preview && file_exists($foto_path)): ?>
                    <div style="position:relative">
                        <img id="fotoPreview" src="<?= BASE_URL . $foto_path ?>?t=<?= time() ?>"
                             style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid #4f46e5;box-shadow:0 2px 8px rgba(0,0,0,.15)">
                        <label style="position:absolute;bottom:0;right:0;background:#4f46e5;color:white;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:.7rem" title="Ganti Foto">
                            <i class="fas fa-camera"></i>
                            <input type="file" name="foto_wali" accept="image/*" style="display:none" onchange="previewFoto(this)">
                        </label>
                    </div>
                    <div>
                        <label style="display:flex;align-items:center;gap:6px;font-size:.83rem;color:#dc2626;cursor:pointer">
                            <input type="checkbox" name="hapus_foto" value="1" onchange="toggleHapusFoto(this)">
                            Hapus foto
                        </label>
                        <div style="font-size:.75rem;color:#94a3b8;margin-top:3px">Atau pilih file baru untuk mengganti</div>
                    </div>
                    <?php else: ?>
                    <div style="width:80px;height:80px;border-radius:50%;background:#e2e8f0;display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden">
                        <img id="fotoPreview" src="" style="width:80px;height:80px;border-radius:50%;object-fit:cover;display:none">
                        <i id="fotoIcon" class="fas fa-user" style="font-size:2rem;color:#94a3b8"></i>
                    </div>
                    <div>
                        <label class="btn btn-secondary" style="cursor:pointer;font-size:.82rem">
                            <i class="fas fa-upload"></i> Pilih Foto
                            <input type="file" name="foto_wali" accept="image/*" style="display:none" onchange="previewFoto(this)">
                        </label>
                        <div style="font-size:.75rem;color:#94a3b8;margin-top:4px">Foto akan ditampilkan di Portal Wali</div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div style="display:flex;gap:10px">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
                <a href="wali.php" class="btn btn-secondary"><i class="fas fa-times"></i> Batal</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- TABEL DAFTAR WALI -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-users" style="color:var(--primary)"></i>
        Daftar Wali Kelas (<?= $wali_list ? $wali_list->num_rows : 0 ?> wali)
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>#</th><th>Foto</th><th>Nama Wali</th><th>Status PIN</th>
                    <th>Jabatan</th><th>Kelas</th><th>No. WA</th><th>Anak Didik</th><th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$wali_list || $wali_list->num_rows === 0): ?>
                <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text-muted)">
                    <i class="fas fa-chalkboard-teacher" style="font-size:2rem;display:block;margin-bottom:8px"></i>
                    Belum ada wali kelas. Klik <strong>+ Tambah Wali</strong> di atas.
                </td></tr>
            <?php else: $no=0; while ($row=$wali_list->fetch_assoc()): $no++; ?>
                <tr>
                    <td><?= $no ?></td>
                    <td>
                        <?php
                        $fp = !empty($row['foto_wali']) ? 'uploads/foto_wali/'.$row['foto_wali'] : '';
                        if ($fp && file_exists(__DIR__.'/'.$fp)):
                        ?>
                        <img src="<?= BASE_URL.$fp ?>?t=<?= filemtime(__DIR__.'/'.$fp) ?>"
                             style="width:42px;height:42px;border-radius:50%;object-fit:cover;border:2px solid #e2e8f0">
                        <?php else: ?>
                        <div style="width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,#4f46e5,#7c3aed);color:white;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1rem">
                            <?= strtoupper(substr($row['nama'],0,1)) ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td><strong><?= htmlspecialchars($row['nama']) ?></strong></td>
                    <td>
                        <?php if (!empty($row['pin'])): ?>
                        <span class="badge" style="background:#dcfce7;color:#166534;padding:3px 10px;border-radius:20px;font-size:.78rem;font-weight:600">
                            <i class="fas fa-check-circle"></i> PIN: <?= htmlspecialchars($row['pin_plain'] ?? '****') ?>
                        </span>
                        <?php else: ?>
                        <span class="badge" style="background:#f1f5f9;color:#64748b;padding:3px 10px;border-radius:20px;font-size:.78rem;font-weight:600">
                            <i class="fas fa-lock"></i> Belum ada PIN
                        </span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($row['jabatan']) ?></td>
                    <td>
                        <?php if (!empty($row['kelas_wali'])): ?>
                        <span style="background:#dcfce7;color:#166534;padding:2px 10px;border-radius:20px;font-size:.8rem;font-weight:600">
                            <?= htmlspecialchars($row['kelas_wali']) ?>
                        </span>
                        <?php else: ?>
                        <span style="color:#94a3b8;font-size:.8rem">Belum ditentukan</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span id="badge-anak-<?= (int)$row['id'] ?>"
                              style="background:#dbeafe;color:#1e40af;padding:2px 10px;border-radius:20px;font-size:.8rem;font-weight:600;cursor:pointer"
                              onclick="openSiswaModal(<?= (int)$row['id'] ?>, '<?= htmlspecialchars(addslashes($row['nama'])) ?>')">
                            <?= (int)$row['jumlah_anak'] ?> siswa
                        </span>
                    </td>
                    <td style="display:flex;gap:4px;flex-wrap:wrap">
                        <a href="wali.php?show=edit&edit_id=<?= (int)$row['id'] ?>" class="btn btn-sm btn-secondary" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button class="btn btn-sm" style="background:#6366f1;color:white" title="Kelola Siswa"
                                onclick="openSiswaModal(<?= (int)$row['id'] ?>, '<?= htmlspecialchars(addslashes($row['nama'])) ?>')">
                            <i class="fas fa-users"></i>
                        </button>
                        <button class="btn btn-sm btn-primary" title="Set / Ubah PIN Wali"
                                onclick="showSetPin(<?= (int)$row['id'] ?>, '<?= htmlspecialchars(addslashes($row['nama'])) ?>', '<?= htmlspecialchars($row['pin_plain'] ?? '') ?>')">
                            <i class="fas fa-key"></i>
                        </button>
                        <a href="wali.php?action=delete&id=<?= (int)$row['id'] ?>" class="btn btn-sm btn-danger"
                           onclick="return confirm('Hapus wali: <?= htmlspecialchars(addslashes($row['nama'])) ?>?')" title="Hapus">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card mt-3" style="border-left:4px solid var(--info);margin-top:16px">
    <div style="padding:12px 20px;font-size:.85rem;color:var(--text-muted)">
        <i class="fas fa-info-circle" style="color:var(--info)"></i>
        Wali kelas login menggunakan <strong>PIN 4 digit</strong> lewat
        <a href="portal_login.php?role=wali" target="_blank">Portal Wali ↗</a>.
        PIN awal diisi saat menambahkan wali kelas, dan bisa diubah kapan saja lewat
        tombol <i class="fas fa-key"></i> <strong>Set PIN</strong> di tabel di atas.
    </div>
</div>

<!-- MODAL KELOLA SISWA -->
<div id="modalSiswa" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:10000;align-items:center;justify-content:center">
    <div style="background:white;border-radius:14px;width:100%;max-width:640px;max-height:90vh;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.3)">
        <div style="padding:20px 24px;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between">
            <div>
                <div style="font-weight:700;font-size:1rem;color:#1e293b">
                    <i class="fas fa-users" style="color:#6366f1"></i>
                    Siswa Wali: <span id="namaWaliSiswa" style="color:#6366f1">-</span>
                </div>
                <div style="font-size:.8rem;color:#64748b;margin-top:2px">Centang siswa yang menjadi anak didik wali ini</div>
            </div>
            <button onclick="closeSiswaModal()" style="background:none;border:none;font-size:1.4rem;cursor:pointer;color:#64748b">&times;</button>
        </div>
        <div style="padding:12px 24px;border-bottom:1px solid #f1f5f9;display:flex;gap:10px;align-items:center;background:#f8fafc">
            <input type="text" id="cariSiswa" placeholder="🔍 Cari nama siswa..." oninput="filterSiswa()"
                   style="flex:1;padding:7px 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:.875rem;outline:none">
            <select id="filterKelas" onchange="filterSiswa()"
                    style="padding:7px 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:.875rem;background:white;outline:none">
                <option value="">Semua Kelas</option>
            </select>
            <label style="font-size:.8rem;color:#64748b;white-space:nowrap;display:flex;align-items:center;gap:5px;cursor:pointer">
                <input type="checkbox" id="checkAll" onchange="toggleAll(this)"> Semua
            </label>
        </div>
        <div style="overflow-y:auto;flex:1;padding:12px 24px">
            <div id="listSiswaContainer"></div>
        </div>
        <div style="padding:16px 24px;border-top:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;background:#f8fafc;border-radius:0 0 14px 14px">
            <div style="font-size:.85rem;color:#64748b">
                Dipilih: <strong id="jumlahDipilih" style="color:#6366f1">0</strong> siswa
            </div>
            <div style="display:flex;gap:8px">
                <button onclick="closeSiswaModal()" class="btn btn-secondary">Batal</button>
                <button onclick="simpanSiswa()" class="btn btn-primary" id="btnSimpanSiswa">
                    <i class="fas fa-save"></i> Simpan
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL SET PIN WALI -->
<div id="modalSetPin" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:10000;align-items:center;justify-content:center">
    <div style="background:white;border-radius:14px;width:100%;max-width:360px;box-shadow:0 20px 60px rgba(0,0,0,.3)">
        <div style="padding:18px 22px;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between">
            <div style="font-weight:700;font-size:1rem;color:#1e293b">
                <i class="fas fa-key" style="color:#4f46e5"></i> Set PIN Wali
            </div>
            <button onclick="closeSetPin()" style="background:none;border:none;font-size:1.4rem;cursor:pointer;color:#64748b">&times;</button>
        </div>
        <form method="POST" action="wali.php">
            <input type="hidden" name="set_pin_wali" value="1">
            <input type="hidden" name="pin_wali_id" id="pinWaliId">
            <div style="padding:22px;text-align:center">
                <p style="margin:0 0 14px">Wali: <strong id="pinWaliNama"></strong></p>
                <label class="form-label fw-bold" style="display:block;margin-bottom:6px">PIN Baru (4 digit)</label>
                <input type="text" name="pin_wali_value" id="pinWaliInput"
                    class="form-control text-center fw-bold"
                    placeholder="● ● ● ●" maxlength="4" pattern="[0-9]{4}" required
                    oninput="this.value=this.value.replace(/\D/g,'')"
                    style="letter-spacing:10px;font-size:1.6rem;text-align:center">
            </div>
            <div style="padding:14px 22px;border-top:1px solid #e2e8f0;display:flex;gap:8px;justify-content:flex-end;background:#f8fafc;border-radius:0 0 14px 14px">
                <button type="button" onclick="closeSetPin()" class="btn btn-secondary">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan PIN</button>
            </div>
        </form>
    </div>
</div>

<script>
function showSetPin(id, nama, pinLama) {
    document.getElementById('pinWaliId').value = id;
    document.getElementById('pinWaliNama').textContent = nama;
    document.getElementById('pinWaliInput').value = pinLama || '';
    document.getElementById('modalSetPin').style.display = 'flex';
    setTimeout(function(){ document.getElementById('pinWaliInput').focus(); }, 200);
}
function closeSetPin() {
    document.getElementById('modalSetPin').style.display = 'none';
}
document.getElementById('modalSetPin').addEventListener('click', function(e){ if (e.target === this) closeSetPin(); });
</script>

<script>
// ── Foto Wali Preview ─────────────────────────────────────────────────
function previewFoto(input) {
    if (!input.files || !input.files[0]) return;
    var reader = new FileReader();
    reader.onload = function(e) {
        var prev = document.getElementById('fotoPreview');
        var icon = document.getElementById('fotoIcon');
        if (prev) { prev.src = e.target.result; prev.style.display = 'block'; }
        if (icon) icon.style.display = 'none';
    };
    reader.readAsDataURL(input.files[0]);
}
function toggleHapusFoto(cb) {
    var prev = document.getElementById('fotoPreview');
    if (prev) prev.style.opacity = cb.checked ? '0.3' : '1';
}

var currentWaliId = null;

function openSiswaModal(waliId, waliNama) {
    currentWaliId = waliId;
    document.getElementById('namaWaliSiswa').textContent = waliNama;
    document.getElementById('listSiswaContainer').innerHTML =
        '<div style="text-align:center;padding:40px;color:#94a3b8"><i class="fas fa-spinner fa-spin"></i><br>Memuat...</div>';
    document.getElementById('cariSiswa').value  = '';
    document.getElementById('filterKelas').value = '';
    document.getElementById('checkAll').checked  = false;
    document.getElementById('modalSiswa').style.display = 'flex';

    fetch('wali.php?ajax_siswa_wali=1&wali_id=' + waliId)
        .then(function(r){ return r.json(); })
        .then(function(data) {
            allSiswaData = data.siswa || [];
            var kelasList = [];
            allSiswaData.forEach(function(s){
                if (kelasList.indexOf(s.kelas) === -1) kelasList.push(s.kelas);
            });
            kelasList.sort();
            var fk = document.getElementById('filterKelas');
            fk.innerHTML = '<option value="">Semua Kelas</option>';
            kelasList.forEach(function(k){
                var o = document.createElement('option');
                o.value = k; o.textContent = k; fk.appendChild(o);
            });
            renderSiswa(allSiswaData);
        })
        .catch(function(){
            document.getElementById('listSiswaContainer').innerHTML =
                '<div style="text-align:center;padding:40px;color:#ef4444">Gagal memuat data siswa.</div>';
        });
}

function renderSiswa(arr) {
    if (!arr.length) {
        document.getElementById('listSiswaContainer').innerHTML =
            '<div style="text-align:center;padding:30px;color:#94a3b8">Tidak ada siswa ditemukan.</div>';
        hitungDipilih(); return;
    }
    var byKelas = {};
    arr.forEach(function(s){ if (!byKelas[s.kelas]) byKelas[s.kelas]=[]; byKelas[s.kelas].push(s); });
    var keys = Object.keys(byKelas).sort();
    var html = '';
    keys.forEach(function(kelas){
        html += '<div style="margin-bottom:12px"><div style="font-weight:700;font-size:.8rem;color:#6366f1;' +
            'padding:6px 10px;background:#eef2ff;border-radius:6px;margin-bottom:6px">📚 Kelas ' + escH(kelas) +
            ' <small style="font-weight:400;color:#94a3b8">(' + byKelas[kelas].length + ' siswa)</small></div>';
        byKelas[kelas].forEach(function(s){
            html += '<label style="display:flex;align-items:center;gap:10px;padding:8px 10px;border-radius:8px;' +
                'cursor:pointer;margin-bottom:2px" onmouseover="this.style.background=\'#f8fafc\'" onmouseout="this.style.background=\'\'">' +
                '<input type="checkbox" class="siswa-check" data-id="' + s.id + '" ' +
                (s.assigned?'checked':'') + ' onchange="hitungDipilih()" style="width:16px;height:16px;cursor:pointer;accent-color:#6366f1">' +
                '<div style="flex:1"><div style="font-weight:600;font-size:.875rem">' + escH(s.nama) + '</div>' +
                '<div style="font-size:.75rem;color:#94a3b8">NIS: ' + escH(s.nis) + '</div></div>' +
                '<span style="font-size:.75rem;background:#e0e7ff;color:#4338ca;padding:2px 8px;border-radius:12px;font-weight:600">' +
                escH(s.kelas) + '</span></label>';
        });
        html += '</div>';
    });
    document.getElementById('listSiswaContainer').innerHTML = html;
    hitungDipilih();
}

function filterSiswa() {
    var q  = document.getElementById('cariSiswa').value.toLowerCase();
    var kl = document.getElementById('filterKelas').value;
    renderSiswa(allSiswaData.filter(function(s){
        return (kl===''||s.kelas===kl) && (q===''||s.nama.toLowerCase().indexOf(q)!==-1||s.nis.indexOf(q)!==-1);
    }));
}
function hitungDipilih() {
    document.getElementById('jumlahDipilih').textContent =
        document.querySelectorAll('#listSiswaContainer .siswa-check:checked').length;
}
function toggleAll(cb) {
    document.querySelectorAll('#listSiswaContainer .siswa-check').forEach(function(c){ c.checked=cb.checked; });
    hitungDipilih();
}
function simpanSiswa() {
    var ids = Array.from(document.querySelectorAll('#listSiswaContainer .siswa-check:checked')).map(function(c){ return c.dataset.id; });
    var btn = document.getElementById('btnSimpanSiswa');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
    var body = new FormData();
    body.append('ajax_kelola_siswa','1');
    body.append('wali_id', currentWaliId);
    ids.forEach(function(id){ body.append('siswa_ids[]', id); });
    fetch('wali.php', {method:'POST',body:body})
        .then(function(r){ return r.json(); })
        .then(function(data){
            if (data.ok) {
                var b = document.getElementById('badge-anak-'+currentWaliId);
                if (b) b.textContent = data.jumlah + ' siswa';
                closeSiswaModal();
                showToast('Berhasil menyimpan '+data.jumlah+' siswa!','success');
            } else { showToast('Gagal menyimpan.','error'); }
        })
        .catch(function(){ showToast('Kesalahan koneksi.','error'); })
        .finally(function(){ btn.disabled=false; btn.innerHTML='<i class="fas fa-save"></i> Simpan'; });
}
function closeSiswaModal() {
    document.getElementById('modalSiswa').style.display='none';
    currentWaliId=null; allSiswaData=[];
}
document.getElementById('modalSiswa').addEventListener('click',function(e){ if(e.target===this) closeSiswaModal(); });
function escH(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function showToast(msg,type){
    var el=document.createElement('div');
    el.textContent=msg;
    el.style.cssText='position:fixed;bottom:24px;right:24px;z-index:99999;padding:12px 20px;border-radius:10px;'+
        'font-size:.875rem;font-weight:600;box-shadow:0 4px 20px rgba(0,0,0,.2);color:white;'+
        'background:'+(type==='success'?'#10b981':'#ef4444');
    document.body.appendChild(el);
    setTimeout(function(){ el.style.transition='opacity .3s'; el.style.opacity='0';
        setTimeout(function(){ el.remove(); },300); },3000);
}
</script>

<?php include 'includes/footer.php'; ?>
