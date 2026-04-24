<?php
require_once 'includes/config.php';
cek_login();

// ── Auto-buat tabel beranda_foto dan beranda_info ──────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS beranda_foto (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(200) DEFAULT '',
    deskripsi TEXT DEFAULT NULL,
    file_foto VARCHAR(255) NOT NULL,
    urutan INT DEFAULT 0,
    aktif TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS beranda_info (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(200) NOT NULL,
    isi TEXT NOT NULL,
    ikon VARCHAR(50) DEFAULT 'fa-info-circle',
    warna VARCHAR(20) DEFAULT '#3b82f6',
    urutan INT DEFAULT 0,
    aktif TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Folder upload foto beranda
$foto_dir = __DIR__ . '/uploads/beranda/';
if (!is_dir($foto_dir)) @mkdir($foto_dir, 0755, true);

$msg = '';

// ── TAMBAH / EDIT FOTO ──────────────────────────────────────────────────
if (isset($_POST['save_foto'])) {
    $id     = (int)($_POST['foto_id'] ?? 0);
    $judul  = $conn->real_escape_string(trim($_POST['judul'] ?? ''));
    $desk   = $conn->real_escape_string(trim($_POST['deskripsi'] ?? ''));
    $urutan = (int)($_POST['urutan'] ?? 0);
    $aktif  = isset($_POST['aktif']) ? 1 : 0;

    $file_foto = '';
    if (!empty($_FILES['file_foto']['name'])) {
        $ext = strtolower(pathinfo($_FILES['file_foto']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','webp']) && $_FILES['file_foto']['size'] < 5*1024*1024) {
            $file_foto = 'beranda_' . time() . '_' . rand(100,999) . '.' . $ext;
            move_uploaded_file($_FILES['file_foto']['tmp_name'], $foto_dir . $file_foto);
        } else {
            $msg = 'error:Format foto tidak valid atau terlalu besar (max 5MB)';
        }
    }

    if (!$msg) {
        if ($id > 0) {
            // Edit
            $old = $conn->query("SELECT file_foto FROM beranda_foto WHERE id=$id")->fetch_assoc();
            if ($file_foto && $old && $old['file_foto'] && file_exists($foto_dir.$old['file_foto'])) {
                unlink($foto_dir.$old['file_foto']);
            }
            $set_foto = $file_foto ? ", file_foto='$file_foto'" : '';
            $conn->query("UPDATE beranda_foto SET judul='$judul', deskripsi='$desk', urutan=$urutan, aktif=$aktif $set_foto WHERE id=$id");
            $msg = 'success:Foto berhasil diperbarui!';
        } else {
            // Tambah
            if (!$file_foto) { $msg = 'error:Foto wajib diunggah!'; }
            else {
                $conn->query("INSERT INTO beranda_foto (judul,deskripsi,file_foto,urutan,aktif) VALUES ('$judul','$desk','$file_foto',$urutan,$aktif)");
                $msg = 'success:Foto berhasil ditambahkan!';
            }
        }
    }
}

// ── HAPUS FOTO ──────────────────────────────────────────────────────────
if (isset($_GET['hapus_foto'])) {
    $id = (int)$_GET['hapus_foto'];
    $row = $conn->query("SELECT file_foto FROM beranda_foto WHERE id=$id")->fetch_assoc();
    if ($row && $row['file_foto'] && file_exists($foto_dir.$row['file_foto'])) unlink($foto_dir.$row['file_foto']);
    $conn->query("DELETE FROM beranda_foto WHERE id=$id");
    $msg = 'success:Foto berhasil dihapus!';
}

// ── TOGGLE AKTIF FOTO ───────────────────────────────────────────────────
if (isset($_GET['toggle_foto'])) {
    $id = (int)$_GET['toggle_foto'];
    $conn->query("UPDATE beranda_foto SET aktif = IF(aktif=1,0,1) WHERE id=$id");
    header('Location: kelola_beranda.php'); exit;
}

// ── TAMBAH / EDIT INFO ──────────────────────────────────────────────────
if (isset($_POST['save_info'])) {
    $id     = (int)($_POST['info_id'] ?? 0);
    $judul  = $conn->real_escape_string(trim($_POST['info_judul'] ?? ''));
    $isi    = $conn->real_escape_string(trim($_POST['info_isi'] ?? ''));
    $ikon   = $conn->real_escape_string(trim($_POST['info_ikon'] ?? 'fa-info-circle'));
    $warna  = $conn->real_escape_string(trim($_POST['info_warna'] ?? '#3b82f6'));
    $urutan = (int)($_POST['info_urutan'] ?? 0);
    $aktif  = isset($_POST['info_aktif']) ? 1 : 0;

    if (!$judul || !$isi) {
        $msg = 'error:Judul dan isi informasi wajib diisi!';
    } elseif ($id > 0) {
        $conn->query("UPDATE beranda_info SET judul='$judul', isi='$isi', ikon='$ikon', warna='$warna', urutan=$urutan, aktif=$aktif WHERE id=$id");
        $msg = 'success:Informasi berhasil diperbarui!';
    } else {
        $conn->query("INSERT INTO beranda_info (judul,isi,ikon,warna,urutan,aktif) VALUES ('$judul','$isi','$ikon','$warna',$urutan,$aktif)");
        $msg = 'success:Informasi berhasil ditambahkan!';
    }
}

// ── HAPUS INFO ──────────────────────────────────────────────────────────
if (isset($_GET['hapus_info'])) {
    $id = (int)$_GET['hapus_info'];
    $conn->query("DELETE FROM beranda_info WHERE id=$id");
    $msg = 'success:Informasi berhasil dihapus!';
}

// ── TOGGLE AKTIF INFO ───────────────────────────────────────────────────
if (isset($_GET['toggle_info'])) {
    $id = (int)$_GET['toggle_info'];
    $conn->query("UPDATE beranda_info SET aktif = IF(aktif=1,0,1) WHERE id=$id");
    header('Location: kelola_beranda.php'); exit;
}

// Ambil data
$foto_list = $conn->query("SELECT * FROM beranda_foto ORDER BY urutan ASC, id DESC")->fetch_all(MYSQLI_ASSOC);
$info_list = $conn->query("SELECT * FROM beranda_info ORDER BY urutan ASC, id DESC")->fetch_all(MYSQLI_ASSOC);

// Edit data
$edit_foto = null; $edit_info = null;
if (isset($_GET['edit_foto'])) $edit_foto = $conn->query("SELECT * FROM beranda_foto WHERE id=".(int)$_GET['edit_foto'])->fetch_assoc();
if (isset($_GET['edit_info'])) $edit_info = $conn->query("SELECT * FROM beranda_info WHERE id=".(int)$_GET['edit_info'])->fetch_assoc();

include 'includes/header.php';
if ($msg) {
    [$t,$tx] = explode(':', $msg, 2);
    echo "<div class='alert alert-$t'><i class='fas ".($t==='success'?'fa-check-circle':'fa-exclamation-circle')."'></i> $tx</div>";
}
?>

<div class="page-header">
    <div class="page-title"><i class="fas fa-home" style="color:#3b82f6"></i> Kelola Beranda</div>
    <div class="page-subtitle">Atur foto slideshow, informasi, dan tampilan halaman utama</div>
</div>

<style>
.tab-wrap{display:flex;gap:8px;margin-bottom:24px;border-bottom:2px solid var(--border);padding-bottom:0}
.tab-btn{padding:10px 22px;border:none;background:transparent;font-weight:700;font-size:.88rem;cursor:pointer;color:var(--text-muted);border-bottom:3px solid transparent;margin-bottom:-2px;transition:.2s;border-radius:6px 6px 0 0}
.tab-btn.active{color:#3b82f6;border-bottom-color:#3b82f6;background:rgba(59,130,246,.06)}
.tab-btn:hover:not(.active){background:var(--hover)}
.tab-pane{display:none}.tab-pane.active{display:block}

.beranda-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start}
@media(max-width:700px){.beranda-grid{grid-template-columns:1fr}}

.section-card{background:var(--card-bg);border:1px solid var(--border);border-radius:14px;overflow:hidden;margin-bottom:20px}
.section-card-header{padding:14px 20px;font-weight:700;font-size:.9rem;display:flex;align-items:center;gap:10px;border-bottom:1px solid var(--border)}
.section-card-body{padding:20px}

.foto-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:14px;margin-bottom:16px}
.foto-card{border:2px solid var(--border);border-radius:12px;overflow:hidden;position:relative;background:var(--card-bg)}
.foto-card img{width:100%;height:110px;object-fit:cover;display:block}
.foto-card-body{padding:8px 10px}
.foto-card-title{font-size:.8rem;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.foto-card-actions{display:flex;gap:5px;margin-top:6px}
.foto-badge{position:absolute;top:6px;right:6px;padding:2px 8px;border-radius:6px;font-size:.7rem;font-weight:700}
.badge-aktif{background:#dcfce7;color:#166534}
.badge-nonaktif{background:#fee2e2;color:#991b1b}

.ikon-picker{display:flex;flex-wrap:wrap;gap:6px;margin-top:8px}
.ikon-opt{width:34px;height:34px;border:2px solid var(--border);border-radius:8px;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:.9rem;transition:.15s;color:var(--text-muted)}
.ikon-opt:hover,.ikon-opt.selected{border-color:#3b82f6;background:#eff6ff;color:#3b82f6}

.info-card{border:1px solid var(--border);border-radius:12px;padding:14px 16px;margin-bottom:10px;display:flex;align-items:center;gap:14px;background:var(--card-bg)}
.info-ikon{width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;color:white;flex-shrink:0}
.info-judul{font-weight:700;font-size:.9rem;margin-bottom:2px}
.info-isi{font-size:.8rem;color:var(--text-muted);line-height:1.4;max-height:40px;overflow:hidden;text-overflow:ellipsis}
.info-actions{display:flex;gap:5px;margin-left:auto;flex-shrink:0}

.btn-sm{padding:5px 10px;border-radius:7px;border:none;cursor:pointer;font-size:.78rem;font-weight:700;display:inline-flex;align-items:center;gap:4px;text-decoration:none}
.btn-edit-sm{background:#eff6ff;color:#1d4ed8}
.btn-del-sm{background:#fef2f2;color:#dc2626}
.btn-toggle-sm{background:#f0fdf4;color:#166534}
.btn-toggle-off{background:#fef3c7;color:#92400e}

.preview-hero{background:linear-gradient(135deg,#1e3a8a,#0891b2);border-radius:14px;padding:0;overflow:hidden;margin-bottom:16px;position:relative;min-height:180px}
.preview-slide{width:100%;height:180px;object-fit:cover;display:block}
.preview-overlay{position:absolute;inset:0;background:linear-gradient(to right,rgba(0,0,0,.6) 0%,transparent 60%);display:flex;align-items:center;padding:24px}
.preview-text h3{color:white;font-size:1.2rem;font-weight:800;text-shadow:0 2px 8px rgba(0,0,0,.5)}
.preview-text p{color:rgba(255,255,255,.7);font-size:.82rem;margin-top:4px}
</style>

<!-- Tabs -->
<div class="tab-wrap">
    <button class="tab-btn active" onclick="switchTab('foto',this)"><i class="fas fa-images"></i> Foto / Slideshow</button>
    <button class="tab-btn" onclick="switchTab('info',this)"><i class="fas fa-bullhorn"></i> Informasi & Pengumuman</button>
    <button class="tab-btn" onclick="switchTab('preview',this)"><i class="fas fa-eye"></i> Preview Beranda</button>
</div>

<!-- ══ TAB FOTO ══ -->
<div class="tab-pane active" id="tab-foto">
<div class="beranda-grid">

    <!-- Form Tambah/Edit Foto -->
    <div class="section-card">
        <div class="section-card-header" style="background:linear-gradient(90deg,#dbeafe,var(--card-bg))">
            <i class="fas fa-<?= $edit_foto ? 'edit' : 'plus-circle' ?>" style="color:#3b82f6"></i>
            <?= $edit_foto ? 'Edit Foto' : 'Tambah Foto Baru' ?>
            <?php if ($edit_foto): ?>
            <a href="kelola_beranda.php" style="margin-left:auto;font-size:.78rem;color:#64748b;text-decoration:none"><i class="fas fa-times"></i> Batal</a>
            <?php endif; ?>
        </div>
        <div class="section-card-body">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="foto_id" value="<?= $edit_foto['id'] ?? 0 ?>">
                <div class="form-group">
                    <label class="form-label">Foto <span style="color:red"><?= $edit_foto ? '' : '*' ?></span></label>
                    <input type="file" name="file_foto" class="form-control" accept="image/*" onchange="previewFoto(this)" <?= $edit_foto ? '' : 'required' ?>>
                    <small style="color:var(--text-muted)">JPG/PNG/WEBP, max 5MB. Ukuran ideal: 1280×480px</small>
                    <div id="fotoPreviewWrap" style="margin-top:10px;display:<?= $edit_foto ? 'block' : 'none' ?>">
                        <?php if ($edit_foto && $edit_foto['file_foto']): ?>
                        <img id="fotoPreview" src="uploads/beranda/<?= htmlspecialchars($edit_foto['file_foto']) ?>" style="width:100%;height:120px;object-fit:cover;border-radius:10px;border:2px solid var(--border)">
                        <?php else: ?>
                        <img id="fotoPreview" style="width:100%;height:120px;object-fit:cover;border-radius:10px;border:2px solid var(--border);display:none">
                        <?php endif; ?>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Judul (opsional)</label>
                    <input type="text" name="judul" class="form-control" placeholder="Contoh: Kegiatan Pramuka" value="<?= htmlspecialchars($edit_foto['judul'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Deskripsi (opsional)</label>
                    <textarea name="deskripsi" class="form-control" rows="2" placeholder="Keterangan singkat foto"><?= htmlspecialchars($edit_foto['deskripsi'] ?? '') ?></textarea>
                </div>
                <div class="form-group" style="display:flex;gap:12px">
                    <div style="flex:1">
                        <label class="form-label">Urutan</label>
                        <input type="number" name="urutan" class="form-control" value="<?= $edit_foto['urutan'] ?? 0 ?>" min="0">
                    </div>
                    <div>
                        <label class="form-label">Status</label><br>
                        <label style="display:flex;align-items:center;gap:8px;margin-top:8px;cursor:pointer">
                            <input type="checkbox" name="aktif" value="1" <?= ($edit_foto['aktif'] ?? 1) ? 'checked' : '' ?> style="width:18px;height:18px">
                            Tampilkan
                        </label>
                    </div>
                </div>
                <button type="submit" name="save_foto" class="btn btn-primary" style="width:100%">
                    <i class="fas fa-save"></i> <?= $edit_foto ? 'Perbarui Foto' : 'Simpan Foto' ?>
                </button>
            </form>
        </div>
    </div>

    <!-- Daftar Foto -->
    <div>
        <div class="section-card">
            <div class="section-card-header" style="background:linear-gradient(90deg,#f0fdf4,var(--card-bg))">
                <i class="fas fa-th" style="color:#059669"></i>
                Daftar Foto (<?= count($foto_list) ?>)
            </div>
            <div class="section-card-body">
                <?php if (empty($foto_list)): ?>
                <div style="text-align:center;padding:30px;color:var(--text-muted)">
                    <i class="fas fa-images" style="font-size:2.5rem;opacity:.3;display:block;margin-bottom:10px"></i>
                    Belum ada foto. Tambahkan foto pertama!
                </div>
                <?php else: ?>
                <div class="foto-grid">
                    <?php foreach ($foto_list as $f): ?>
                    <div class="foto-card" style="<?= !$f['aktif'] ? 'opacity:.55' : '' ?>">
                        <?php if ($f['file_foto'] && file_exists($foto_dir.$f['file_foto'])): ?>
                        <img src="uploads/beranda/<?= htmlspecialchars($f['file_foto']) ?>" alt="">
                        <?php else: ?>
                        <div style="height:110px;background:#e2e8f0;display:flex;align-items:center;justify-content:center;color:#94a3b8"><i class="fas fa-image fa-2x"></i></div>
                        <?php endif; ?>
                        <span class="foto-badge <?= $f['aktif'] ? 'badge-aktif' : 'badge-nonaktif' ?>">
                            <?= $f['aktif'] ? 'Aktif' : 'Nonaktif' ?>
                        </span>
                        <div class="foto-card-body">
                            <div class="foto-card-title"><?= $f['judul'] ?: '<span style="color:#94a3b8;font-style:italic">Tanpa judul</span>' ?></div>
                            <div class="foto-card-actions">
                                <a href="?edit_foto=<?= $f['id'] ?>" class="btn-sm btn-edit-sm"><i class="fas fa-edit"></i></a>
                                <a href="?toggle_foto=<?= $f['id'] ?>" class="btn-sm <?= $f['aktif'] ? 'btn-toggle-off' : 'btn-toggle-sm' ?>">
                                    <i class="fas fa-<?= $f['aktif'] ? 'eye-slash' : 'eye' ?>"></i>
                                </a>
                                <a href="?hapus_foto=<?= $f['id'] ?>" class="btn-sm btn-del-sm" onclick="return confirm('Hapus foto ini?')"><i class="fas fa-trash"></i></a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>
</div><!-- /tab-foto -->

<!-- ══ TAB INFO ══ -->
<div class="tab-pane" id="tab-info">
<div class="beranda-grid">

    <!-- Form Tambah/Edit Info -->
    <div class="section-card">
        <div class="section-card-header" style="background:linear-gradient(90deg,#fef3c7,var(--card-bg))">
            <i class="fas fa-<?= $edit_info ? 'edit' : 'plus-circle' ?>" style="color:#f59e0b"></i>
            <?= $edit_info ? 'Edit Informasi' : 'Tambah Informasi Baru' ?>
            <?php if ($edit_info): ?>
            <a href="kelola_beranda.php#info" style="margin-left:auto;font-size:.78rem;color:#64748b;text-decoration:none"><i class="fas fa-times"></i> Batal</a>
            <?php endif; ?>
        </div>
        <div class="section-card-body">
            <form method="POST">
                <input type="hidden" name="info_id" value="<?= $edit_info['id'] ?? 0 ?>">
                <div class="form-group">
                    <label class="form-label">Judul *</label>
                    <input type="text" name="info_judul" class="form-control" required placeholder="Contoh: Penerimaan Siswa Baru" value="<?= htmlspecialchars($edit_info['judul'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Isi Informasi *</label>
                    <textarea name="info_isi" class="form-control" rows="4" required placeholder="Tulis isi pengumuman atau informasi di sini..."><?= htmlspecialchars($edit_info['isi'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Ikon</label>
                    <input type="hidden" name="info_ikon" id="selectedIkon" value="<?= htmlspecialchars($edit_info['ikon'] ?? 'fa-info-circle') ?>">
                    <div class="ikon-picker" id="ikonPicker">
                        <?php
                        $ikons = ['fa-info-circle','fa-bullhorn','fa-calendar-alt','fa-trophy','fa-book','fa-graduation-cap','fa-star','fa-heart','fa-bell','fa-flag','fa-rocket','fa-gift','fa-leaf','fa-sun','fa-music','fa-camera','fa-map-marker-alt','fa-clock','fa-users','fa-shield-alt'];
                        foreach ($ikons as $ik):
                            $sel = ($edit_info['ikon'] ?? 'fa-info-circle') === $ik ? 'selected' : '';
                        ?>
                        <div class="ikon-opt <?= $sel ?>" onclick="pickIkon('<?= $ik ?>',this)" title="<?= $ik ?>">
                            <i class="fas <?= $ik ?>"></i>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Warna Ikon</label>
                    <div style="display:flex;gap:8px;align-items:center">
                        <input type="color" name="info_warna" value="<?= $edit_info['warna'] ?? '#3b82f6' ?>" style="width:50px;height:36px;border-radius:8px;border:2px solid var(--border);cursor:pointer;padding:2px">
                        <small style="color:var(--text-muted)">Pilih warna untuk ikon informasi</small>
                    </div>
                </div>
                <div class="form-group" style="display:flex;gap:12px">
                    <div style="flex:1">
                        <label class="form-label">Urutan</label>
                        <input type="number" name="info_urutan" class="form-control" value="<?= $edit_info['urutan'] ?? 0 ?>" min="0">
                    </div>
                    <div>
                        <label class="form-label">Status</label><br>
                        <label style="display:flex;align-items:center;gap:8px;margin-top:8px;cursor:pointer">
                            <input type="checkbox" name="info_aktif" value="1" <?= ($edit_info['aktif'] ?? 1) ? 'checked' : '' ?> style="width:18px;height:18px">
                            Tampilkan
                        </label>
                    </div>
                </div>
                <button type="submit" name="save_info" class="btn btn-primary" style="width:100%;background:linear-gradient(135deg,#f59e0b,#d97706)">
                    <i class="fas fa-save"></i> <?= $edit_info ? 'Perbarui Informasi' : 'Simpan Informasi' ?>
                </button>
            </form>
        </div>
    </div>

    <!-- Daftar Info -->
    <div>
        <div class="section-card">
            <div class="section-card-header" style="background:linear-gradient(90deg,#fef3c7,var(--card-bg))">
                <i class="fas fa-list" style="color:#f59e0b"></i>
                Daftar Informasi (<?= count($info_list) ?>)
            </div>
            <div class="section-card-body" style="padding:12px">
                <?php if (empty($info_list)): ?>
                <div style="text-align:center;padding:30px;color:var(--text-muted)">
                    <i class="fas fa-bullhorn" style="font-size:2.5rem;opacity:.3;display:block;margin-bottom:10px"></i>
                    Belum ada informasi. Tambahkan pengumuman pertama!
                </div>
                <?php else: ?>
                <?php foreach ($info_list as $inf): ?>
                <div class="info-card" style="<?= !$inf['aktif'] ? 'opacity:.5' : '' ?>">
                    <div class="info-ikon" style="background:<?= htmlspecialchars($inf['warna']) ?>">
                        <i class="fas <?= htmlspecialchars($inf['ikon']) ?>"></i>
                    </div>
                    <div style="flex:1;min-width:0">
                        <div class="info-judul"><?= htmlspecialchars($inf['judul']) ?></div>
                        <div class="info-isi"><?= htmlspecialchars($inf['isi']) ?></div>
                        <div style="font-size:.72rem;color:var(--text-muted);margin-top:3px">
                            <?= $inf['aktif'] ? '<span style="color:#16a34a"><i class="fas fa-circle" style="font-size:.5rem"></i> Tampil</span>' : '<span style="color:#dc2626"><i class="fas fa-circle" style="font-size:.5rem"></i> Disembunyikan</span>' ?>
                        </div>
                    </div>
                    <div class="info-actions">
                        <a href="?edit_info=<?= $inf['id'] ?>" class="btn-sm btn-edit-sm"><i class="fas fa-edit"></i></a>
                        <a href="?toggle_info=<?= $inf['id'] ?>" class="btn-sm <?= $inf['aktif'] ? 'btn-toggle-off' : 'btn-toggle-sm' ?>">
                            <i class="fas fa-<?= $inf['aktif'] ? 'eye-slash' : 'eye' ?>"></i>
                        </a>
                        <a href="?hapus_info=<?= $inf['id'] ?>" class="btn-sm btn-del-sm" onclick="return confirm('Hapus informasi ini?')"><i class="fas fa-trash"></i></a>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>
</div><!-- /tab-info -->

<!-- ══ TAB PREVIEW ══ -->
<div class="tab-pane" id="tab-preview">
<div class="section-card">
    <div class="section-card-header" style="background:linear-gradient(90deg,#ede9fe,var(--card-bg))">
        <i class="fas fa-eye" style="color:#7c3aed"></i> Preview Tampilan Beranda
        <a href="<?= BASE_URL ?>index.php" target="_blank" class="btn btn-primary" style="margin-left:auto;padding:6px 14px;font-size:.82rem">
            <i class="fas fa-external-link-alt"></i> Buka Beranda
        </a>
    </div>
    <div class="section-card-body" style="padding:0">
        <?php $foto_aktif = array_filter($foto_list, fn($f) => $f['aktif']); ?>
        <?php if ($foto_aktif): ?>
        <div style="position:relative;height:200px;overflow:hidden">
            <?php foreach (array_values($foto_aktif) as $fi => $f): ?>
            <img src="uploads/beranda/<?= htmlspecialchars($f['file_foto']) ?>"
                 style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:<?= $fi===0?1:0 ?>;transition:opacity .6s"
                 class="preview-slide-img" id="prev-slide-<?= $fi ?>">
            <?php endforeach; ?>
            <div style="position:absolute;inset:0;background:linear-gradient(to right,rgba(0,0,0,.55) 0%,transparent 60%);display:flex;align-items:center;padding:24px">
                <div>
                    <div style="color:white;font-size:1.3rem;font-weight:900;text-shadow:0 2px 8px rgba(0,0,0,.5)"><?= htmlspecialchars(get_pengaturan()['nama_sekolah']) ?></div>
                    <div style="color:rgba(255,255,255,.75);font-size:.85rem;margin-top:4px">Sistem Absensi Digital</div>
                </div>
            </div>
            <div style="position:absolute;bottom:10px;left:0;width:100%;display:flex;justify-content:center;gap:6px">
                <?php foreach (array_values($foto_aktif) as $fi => $f): ?>
                <span class="prev-dot" id="prev-dot-<?= $fi ?>" style="width:8px;height:8px;border-radius:50%;background:<?= $fi===0?'white':'rgba(255,255,255,.4)' ?>;cursor:pointer;transition:.3s" onclick="goSlide(<?= $fi ?>)"></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <div style="height:160px;background:linear-gradient(135deg,#1e3a8a,#0891b2);display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.5);font-size:.9rem">
            <i class="fas fa-images fa-2x" style="margin-right:10px"></i> Belum ada foto aktif
        </div>
        <?php endif; ?>

        <?php $info_aktif = array_filter($info_list, fn($i) => $i['aktif']); ?>
        <?php if ($info_aktif): ?>
        <div style="padding:16px;display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:10px">
            <?php foreach ($info_aktif as $inf): ?>
            <div style="display:flex;gap:10px;align-items:flex-start;padding:12px;background:var(--sidebar-bg);border-radius:10px;border:1px solid var(--border)">
                <div style="width:36px;height:36px;border-radius:8px;background:<?= htmlspecialchars($inf['warna']) ?>;display:flex;align-items:center;justify-content:center;color:white;flex-shrink:0">
                    <i class="fas <?= htmlspecialchars($inf['ikon']) ?>"></i>
                </div>
                <div>
                    <div style="font-weight:700;font-size:.85rem"><?= htmlspecialchars($inf['judul']) ?></div>
                    <div style="font-size:.75rem;color:var(--text-muted);margin-top:2px;line-height:1.4"><?= nl2br(htmlspecialchars(substr($inf['isi'],0,80))) ?>...</div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div style="padding:20px;text-align:center;color:var(--text-muted);font-size:.85rem">
            <i class="fas fa-bullhorn" style="opacity:.3"></i> Belum ada informasi aktif
        </div>
        <?php endif; ?>
    </div>
</div>
</div><!-- /tab-preview -->

<script>
// Tab switcher
function switchTab(id, btn) {
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-'+id).classList.add('active');
    btn.classList.add('active');
}

// Preview foto
function previewFoto(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var img = document.getElementById('fotoPreview');
            img.src = e.target.result;
            img.style.display = 'block';
            document.getElementById('fotoPreviewWrap').style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Ikon picker
function pickIkon(ikon, el) {
    document.querySelectorAll('.ikon-opt').forEach(e => e.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('selectedIkon').value = ikon;
}

// Slideshow preview
var curSlide = 0;
var totalSlides = <?= count(array_filter($foto_list, fn($f) => $f['aktif'])) ?>;
function goSlide(n) {
    if (totalSlides < 2) return;
    document.getElementById('prev-slide-'+curSlide).style.opacity = 0;
    document.getElementById('prev-dot-'+curSlide).style.background = 'rgba(255,255,255,.4)';
    curSlide = n;
    document.getElementById('prev-slide-'+curSlide).style.opacity = 1;
    document.getElementById('prev-dot-'+curSlide).style.background = 'white';
}
if (totalSlides > 1) {
    setInterval(function() { goSlide((curSlide+1) % totalSlides); }, 3500);
}

// Auto switch tab jika ada anchor #info
if (window.location.hash === '#info') {
    document.querySelector('[onclick*="info"]').click();
}
<?php if ($edit_info): ?>
document.querySelector('[onclick*="info"]').click();
<?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>
