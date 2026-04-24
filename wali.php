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

// ── SIMPAN (tambah/edit) ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_wali'])) {
    $id         = (int)($_POST['id'] ?? 0);
    $username   = sanitize($_POST['username'] ?? '');
    $nama       = sanitize($_POST['nama'] ?? '');
    $jabatan    = sanitize($_POST['jabatan'] ?? 'Wali Kelas');
    $kelas_wali = sanitize($_POST['kelas_wali'] ?? '');
    $no_hp      = sanitize($_POST['no_hp'] ?? '');
    $pw_new     = $_POST['pw_new'] ?? '';

    // ── Handle upload foto wali (base64 dari crop tool) ─────────────────
    $foto_wali_sql = '';
    $foto_wali_b64 = trim($_POST['foto_wali_data'] ?? '');

    if ($foto_wali_b64 && strpos($foto_wali_b64, 'data:image/') === 0) {
        // Decode base64
        $parts = explode(',', $foto_wali_b64, 2);
        if (count($parts) === 2) {
            $img_data = base64_decode($parts[1]);
            if ($img_data && strlen($img_data) <= 3 * 1024 * 1024) {
                $newname = 'wali_' . ($id ?: time()) . '_' . time() . '.jpg';
                $dest    = __DIR__ . '/uploads/foto_wali/' . $newname;
                if (file_put_contents($dest, $img_data)) {
                    // Hapus foto lama jika ada
                    if ($id) {
                        $old = $conn->query("SELECT foto_wali FROM wali WHERE id=$id")->fetch_assoc();
                        if ($old && $old['foto_wali'] && file_exists(__DIR__ . '/uploads/foto_wali/' . $old['foto_wali'])) {
                            @unlink(__DIR__ . '/uploads/foto_wali/' . $old['foto_wali']);
                        }
                    }
                    $foto_wali_sql = ", foto_wali='" . $conn->real_escape_string($newname) . "'";
                }
            }
        }
    } elseif (!empty($_FILES['foto_wali']['name']) && $_FILES['foto_wali']['error'] === UPLOAD_ERR_OK) {
        // Fallback: upload biasa (tanpa crop)
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $ftype = mime_content_type($_FILES['foto_wali']['tmp_name']);
        if (in_array($ftype, $allowed_types) && $_FILES['foto_wali']['size'] <= 2 * 1024 * 1024) {
            $ext     = pathinfo($_FILES['foto_wali']['name'], PATHINFO_EXTENSION);
            $newname = 'wali_' . ($id ?: time()) . '_' . time() . '.' . strtolower($ext);
            $dest    = __DIR__ . '/uploads/foto_wali/' . $newname;
            if (move_uploaded_file($_FILES['foto_wali']['tmp_name'], $dest)) {
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

    if (!$username || !$nama) {
        $msg  = 'error:Username dan nama wajib diisi';
        $show = $id ? 'edit' : 'add';
        if ($id) $edit_id = $id;
    } else {
        $has_kw = $conn->query("SHOW COLUMNS FROM `wali` LIKE 'kelas_wali'")->num_rows > 0;
        if ($id) {
            $pw_sql = $pw_new ? ",password='".password_hash($pw_new,PASSWORD_DEFAULT)."'" : '';
            $no_hp_safe = $conn->real_escape_string($no_hp);
            if ($has_kw)
                $conn->query("UPDATE wali SET username='$username',nama='$nama',jabatan='$jabatan',kelas_wali='$kelas_wali',no_hp='$no_hp_safe'$pw_sql$foto_wali_sql WHERE id=$id");
            else
                $conn->query("UPDATE wali SET username='$username',nama='$nama',jabatan='$jabatan',no_hp='$no_hp_safe'$pw_sql$foto_wali_sql WHERE id=$id");
            $msg = 'success:Data wali kelas berhasil diupdate';
        } else {
            $pw = password_hash($pw_new ?: $username, PASSWORD_DEFAULT);
            $no_hp_safe = $conn->real_escape_string($no_hp);
            if ($has_kw)
                $r = $conn->query("INSERT INTO wali (username,password,nama,jabatan,kelas_wali,no_hp) VALUES ('$username','$pw','$nama','$jabatan','$kelas_wali','$no_hp_safe')");
            else
                $r = $conn->query("INSERT INTO wali (username,password,nama,jabatan,no_hp) VALUES ('$username','$pw','$nama','$jabatan','$no_hp_safe')");
            // Update foto setelah INSERT berhasil
            if ($r && $foto_wali_sql) {
                $new_id = $conn->insert_id;
                $conn->query("UPDATE wali SET foto_wali=''$foto_wali_sql WHERE id=$new_id");
            }
            $msg = $r ? 'success:Wali kelas berhasil ditambahkan' : 'error:Username sudah digunakan atau terjadi kesalahan';
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
                <div>
                    <label class="form-label">Username <span style="color:red">*</span></label>
                    <input type="text" name="username" class="form-control"
                           value="<?= htmlspecialchars($edit_data['username'] ?? '') ?>"
                           placeholder="Untuk login" required>
                </div>
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

            <!-- FOTO WALI dengan CROP TOOL -->
            <div style="margin-bottom:16px">
                <label class="form-label">Foto Wali Kelas
                    <small style="color:var(--text-muted)">(JPG/PNG/WEBP, akan di-crop sebelum disimpan)</small>
                </label>
                <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
                    <?php
                    $foto_preview = $edit_data['foto_wali'] ?? '';
                    $foto_path    = 'uploads/foto_wali/' . $foto_preview;
                    ?>
                    <!-- Preview hasil crop -->
                    <div style="position:relative;flex-shrink:0">
                        <?php if ($foto_preview && file_exists($foto_path)): ?>
                        <img id="fotoPreview"
                             src="<?= BASE_URL . $foto_path ?>?t=<?= time() ?>"
                             style="width:90px;height:100px;border-radius:10px;object-fit:cover;border:3px solid #4f46e5;box-shadow:0 2px 10px rgba(79,70,229,.3)">
                        <?php else: ?>
                        <div id="fotoPreviewBox" style="width:90px;height:100px;border-radius:10px;background:#f1f5f9;border:2px dashed #cbd5e1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px">
                            <i class="fas fa-user" style="font-size:1.8rem;color:#94a3b8"></i>
                            <span style="font-size:.65rem;color:#94a3b8">Belum ada foto</span>
                        </div>
                        <img id="fotoPreview" src="" style="width:90px;height:100px;border-radius:10px;object-fit:cover;border:3px solid #4f46e5;box-shadow:0 2px 10px rgba(79,70,229,.3);display:none">
                        <?php endif; ?>
                    </div>

                    <div style="display:flex;flex-direction:column;gap:8px">
                        <!-- Tombol pilih file -->
                        <label style="display:inline-flex;align-items:center;gap:7px;padding:8px 16px;background:#4f46e5;color:white;border-radius:8px;cursor:pointer;font-size:.83rem;font-weight:600;width:fit-content">
                            <i class="fas fa-camera"></i> <?= ($foto_preview && file_exists($foto_path)) ? 'Ganti Foto' : 'Pilih Foto' ?>
                            <input type="file" id="fileInputFoto" accept="image/*" style="display:none" onchange="bukaModalCrop(this)">
                        </label>
                        <?php if ($foto_preview && file_exists($foto_path)): ?>
                        <label style="display:flex;align-items:center;gap:6px;font-size:.82rem;color:#dc2626;cursor:pointer">
                            <input type="checkbox" name="hapus_foto" value="1" onchange="toggleHapusFoto(this)">
                            Hapus foto ini
                        </label>
                        <?php endif; ?>
                        <div style="font-size:.73rem;color:#94a3b8"><i class="fas fa-crop-alt"></i> Foto bisa di-crop & diatur simetris sebelum disimpan</div>
                    </div>
                </div>
                <!-- Hidden input untuk data crop (base64) -->
                <input type="hidden" name="foto_wali_data" id="fotoWaliData">
            </div>
            <div style="margin-bottom:20px">
                <label class="form-label">Password Baru
                    <small style="color:var(--text-muted)">
                        <?= $show==='add'?'(kosongkan = default pakai username)':'(kosongkan = tidak diubah)' ?>
                    </small>
                </label>
                <input type="password" name="pw_new" class="form-control" placeholder="Kosongkan jika tidak ingin mengubah">
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
                    <th>#</th><th>Foto</th><th>Nama Wali</th><th>Username</th>
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
                    <td><code style="background:#f1f5f9;padding:2px 8px;border-radius:4px">
                        <?= htmlspecialchars($row['username']) ?>
                    </code></td>
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
                        <a href="wali.php?action=reset_pw&id=<?= (int)$row['id'] ?>" class="btn btn-sm btn-warning"
                           onclick="return confirm('Reset password ke username: <?= htmlspecialchars(addslashes($row['username'])) ?>?')" title="Reset Password">
                            <i class="fas fa-key"></i>
                        </a>
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
        <strong>Password default</strong> = username. Wali kelas login melalui
        <a href="portal_login.php?role=wali" target="_blank">Portal Wali ↗</a>
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

<!-- Cropper.js -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>

<!-- MODAL CROP -->
<div id="modalCrop" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:99999;align-items:center;justify-content:center">
    <div style="background:white;border-radius:16px;width:100%;max-width:600px;margin:16px;box-shadow:0 20px 60px rgba(0,0,0,.4);display:flex;flex-direction:column;max-height:95vh">

        <!-- Header -->
        <div style="padding:16px 20px;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between">
            <div>
                <div style="font-weight:700;font-size:1rem;color:#1e293b"><i class="fas fa-crop-alt" style="color:#4f46e5"></i> Edit & Crop Foto</div>
                <div style="font-size:.75rem;color:#64748b;margin-top:2px">Seret untuk memindahkan • Scroll untuk zoom • Drag sudut untuk resize</div>
            </div>
            <button onclick="tutupModalCrop()" style="background:none;border:none;font-size:1.5rem;cursor:pointer;color:#94a3b8;line-height:1">&times;</button>
        </div>

        <!-- Area crop -->
        <div style="flex:1;overflow:hidden;background:#1e293b;min-height:320px;position:relative">
            <img id="cropImage" style="display:block;max-width:100%">
        </div>

        <!-- Toolbar: Rasio + Rotasi -->
        <div style="padding:12px 20px;background:#f8fafc;border-top:1px solid #e2e8f0;display:flex;flex-wrap:wrap;gap:8px;align-items:center">
            <!-- Rasio -->
            <div style="display:flex;gap:6px;align-items:center">
                <span style="font-size:.75rem;font-weight:600;color:#64748b">Rasio:</span>
                <button class="crop-ratio-btn active" data-ratio="3/4"   onclick="setRatio(3/4,  this)" title="3:4 Potrait">3:4</button>
                <button class="crop-ratio-btn"        data-ratio="1/1"   onclick="setRatio(1/1,  this)" title="1:1 Kotak">1:1</button>
                <button class="crop-ratio-btn"        data-ratio="4/3"   onclick="setRatio(4/3,  this)" title="4:3 Landscape">4:3</button>
                <button class="crop-ratio-btn"        data-ratio="16/9"  onclick="setRatio(16/9, this)" title="16:9 Wide">16:9</button>
                <button class="crop-ratio-btn"        data-ratio="free"  onclick="setRatio(null, this)" title="Bebas">Bebas</button>
            </div>
            <div style="width:1px;height:24px;background:#e2e8f0;margin:0 4px"></div>
            <!-- Rotasi & Flip -->
            <div style="display:flex;gap:6px">
                <button onclick="cropRotate(-90)" class="crop-tool-btn" title="Putar Kiri"><i class="fas fa-undo"></i></button>
                <button onclick="cropRotate(90)"  class="crop-tool-btn" title="Putar Kanan"><i class="fas fa-redo"></i></button>
                <button onclick="cropFlipX()"     class="crop-tool-btn" title="Flip Horizontal"><i class="fas fa-arrows-alt-h"></i></button>
                <button onclick="cropFlipY()"     class="crop-tool-btn" title="Flip Vertikal"><i class="fas fa-arrows-alt-v"></i></button>
                <button onclick="cropReset()"     class="crop-tool-btn" title="Reset"><i class="fas fa-sync-alt"></i></button>
            </div>
        </div>

        <!-- Preview & Aksi -->
        <div style="padding:14px 20px;border-top:1px solid #e2e8f0;display:flex;align-items:center;gap:16px;flex-wrap:wrap">
            <div style="display:flex;flex-direction:column;align-items:center;gap:6px">
                <div style="font-size:.7rem;font-weight:600;color:#64748b">PREVIEW</div>
                <div style="width:70px;height:80px;border-radius:8px;overflow:hidden;border:2px solid #4f46e5;background:#f1f5f9">
                    <div id="cropPreviewBox" style="width:100%;height:100%"></div>
                </div>
            </div>
            <div style="flex:1;min-width:180px">
                <div style="font-size:.75rem;color:#64748b;margin-bottom:8px">
                    <i class="fas fa-info-circle" style="color:#4f46e5"></i>
                    Foto akan disimpan sebagai <strong>JPG</strong> ukuran <strong>400×533px</strong> (rasio 3:4)
                </div>
                <div style="display:flex;gap:8px">
                    <button onclick="tutupModalCrop()" style="padding:9px 16px;border:1px solid #e2e8f0;border-radius:8px;background:white;color:#475569;font-weight:600;cursor:pointer;font-size:.875rem">
                        Batal
                    </button>
                    <button onclick="gunaknaFotoCrop()" style="flex:1;padding:9px;background:#4f46e5;color:white;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:.875rem">
                        <i class="fas fa-check"></i> Gunakan Foto Ini
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.crop-ratio-btn {
    padding:4px 10px;border-radius:6px;border:1px solid #cbd5e1;
    background:white;color:#475569;font-size:.75rem;font-weight:600;cursor:pointer;transition:.15s;
}
.crop-ratio-btn:hover,.crop-ratio-btn.active {
    background:#4f46e5;color:white;border-color:#4f46e5;
}
.crop-tool-btn {
    width:32px;height:32px;border-radius:7px;border:1px solid #e2e8f0;
    background:white;color:#475569;cursor:pointer;font-size:.8rem;
    display:flex;align-items:center;justify-content:center;transition:.15s;
}
.crop-tool-btn:hover { background:#4f46e5;color:white;border-color:#4f46e5; }
</style>

<script>
// ── Cropper.js ─────────────────────────────────────────────────────────
var cropper        = null;
var cropScaleX     = 1;
var cropScaleY     = 1;

function bukaModalCrop(input) {
    if (!input.files || !input.files[0]) return;
    var file = input.files[0];
    if (!file.type.startsWith('image/')) { alert('Pilih file gambar!'); return; }

    var reader = new FileReader();
    reader.onload = function(e) {
        var img = document.getElementById('cropImage');
        img.src = e.target.result;
        document.getElementById('modalCrop').style.display = 'flex';

        // Destroy cropper lama
        if (cropper) { cropper.destroy(); cropper = null; }
        cropScaleX = 1; cropScaleY = 1;

        // Init cropper setelah gambar load
        img.onload = function() {
            cropper = new Cropper(img, {
                aspectRatio   : 3 / 4,
                viewMode      : 1,
                dragMode      : 'move',
                autoCropArea  : 0.85,
                restore       : false,
                guides        : true,
                center        : true,
                highlight     : false,
                cropBoxMovable: true,
                cropBoxResizable: true,
                toggleDragModeOnDblclick: false,
                preview       : '#cropPreviewBox',
                ready         : function() {
                    // Set rasio tombol aktif ke 3:4 default
                    document.querySelectorAll('.crop-ratio-btn').forEach(function(b){
                        b.classList.toggle('active', b.dataset.ratio === '3/4');
                    });
                }
            });
        };
        if (img.complete) img.onload();
    };
    reader.readAsDataURL(file);
    // Reset input agar bisa pilih file yang sama lagi
    input.value = '';
}

function setRatio(ratio, btn) {
    if (!cropper) return;
    document.querySelectorAll('.crop-ratio-btn').forEach(function(b){ b.classList.remove('active'); });
    btn.classList.add('active');
    cropper.setAspectRatio(ratio === null ? NaN : ratio);
}

function cropRotate(deg) {
    if (cropper) cropper.rotate(deg);
}

function cropFlipX() {
    if (!cropper) return;
    cropScaleX *= -1;
    cropper.scaleX(cropScaleX);
}

function cropFlipY() {
    if (!cropper) return;
    cropScaleY *= -1;
    cropper.scaleY(cropScaleY);
}

function cropReset() {
    if (!cropper) return;
    cropScaleX = 1; cropScaleY = 1;
    cropper.reset();
}

function gunaknaFotoCrop() {
    if (!cropper) return;
    // Ambil canvas hasil crop, resize ke max 400x533
    var canvas = cropper.getCroppedCanvas({
        width      : 400,
        height     : 533,
        imageSmoothingEnabled: true,
        imageSmoothingQuality: 'high',
        fillColor  : '#fff',
    });
    var dataUrl = canvas.toDataURL('image/jpeg', 0.88);

    // Simpan ke hidden input
    document.getElementById('fotoWaliData').value = dataUrl;

    // Tampilkan preview di form
    var prev = document.getElementById('fotoPreview');
    var box  = document.getElementById('fotoPreviewBox');
    if (prev) { prev.src = dataUrl; prev.style.display = 'block'; }
    if (box)  { box.style.display = 'none'; }

    tutupModalCrop();
}

function tutupModalCrop() {
    document.getElementById('modalCrop').style.display = 'none';
    if (cropper) { cropper.destroy(); cropper = null; }
}

// Tutup modal klik di luar
document.getElementById('modalCrop').addEventListener('click', function(e) {
    if (e.target === this) tutupModalCrop();
});

function toggleHapusFoto(cb) {
    var prev = document.getElementById('fotoPreview');
    if (prev) prev.style.opacity = cb.checked ? '0.3' : '1';
    if (cb.checked) document.getElementById('fotoWaliData').value = '';
}

// ── Kelola Siswa Modal ──────────────────────────────────────────────
var currentWaliId = null;
var allSiswaData  = [];

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
