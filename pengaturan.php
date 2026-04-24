<?php
require_once 'includes/config.php';
cek_login();

$msg = '';
$pengaturan = get_pengaturan();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_sekolah   = sanitize($_POST['nama_sekolah']);
    $alamat         = sanitize($_POST['alamat']);
    $kepala_sekolah = sanitize($_POST['kepala_sekolah'] ?? '');
    $nip_kepala     = sanitize($_POST['nip_kepala'] ?? '');
    
    // Logo upload
    $logo = $pengaturan['logo'];
    if (!empty($_FILES['logo']['name'])) {
        $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','svg','webp'];
        if (in_array($ext, $allowed) && $_FILES['logo']['size'] < 2*1024*1024) {
            // Delete old
            if ($logo && file_exists(__DIR__.'/uploads/logo/'.$logo)) unlink(__DIR__.'/uploads/logo/'.$logo);
            $logo = 'logo_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['logo']['tmp_name'], __DIR__.'/uploads/logo/' . $logo);
        } else {
            $msg = 'danger:Format logo tidak valid atau terlalu besar (max 2MB)';
        }
    }
    
    if (!$msg) {
        $conn->query("UPDATE pengaturan SET nama_sekolah='$nama_sekolah', alamat='$alamat', kepala_sekolah='$kepala_sekolah', nip_kepala='$nip_kepala', logo=" . ($logo?"'$logo'":"NULL") . " WHERE id=1");
        $msg = 'success:Pengaturan sekolah berhasil disimpan';
        $pengaturan = get_pengaturan();
    }
}

include 'includes/header.php';

if ($msg) {
    list($type, $text) = explode(':', $msg, 2);
    echo "<div class='alert alert-$type'><i class='fas fa-info-circle'></i> $text</div>";
}
?>

<div class="page-header">
    <div class="page-title"><i class="fas fa-cog"></i> Pengaturan Sekolah</div>
    <div class="page-subtitle">Konfigurasi nama, alamat, dan logo sekolah</div>
</div>

<div class="card" style="max-width:700px">
    <div class="card-header"><i class="fas fa-school"></i> Informasi Sekolah</div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label class="form-label">Logo Sekolah</label>
                <div style="display:flex;align-items:center;gap:20px;margin-bottom:12px">
                    <?php if (!empty($pengaturan['logo']) && file_exists(__DIR__.'/uploads/logo/'.$pengaturan['logo'])): ?>
                        <img src="<?= BASE_URL ?>uploads/logo/<?= $pengaturan['logo'] ?>" 
                            style="width:80px;height:80px;object-fit:contain;border:1px solid var(--border);border-radius:8px;padding:4px">
                    <?php else: ?>
                        <div style="width:80px;height:80px;background:#f1f5f9;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:2rem;color:var(--text-muted)">
                            <i class="fas fa-school"></i>
                        </div>
                    <?php endif; ?>
                    <div>
                        <input type="file" name="logo" class="form-control" accept="image/*" id="logoInput" onchange="previewLogo(this)">
                        <small style="color:var(--text-muted)">Max 2MB. JPG/PNG/SVG/WEBP. Logo akan tampil di navbar sidebar.</small>
                    </div>
                </div>
                <img id="logoPreview" style="display:none;width:120px;height:120px;object-fit:contain;border:2px solid var(--primary);border-radius:8px;padding:4px">
            </div>
            
            <div class="form-group">
                <label class="form-label">Nama Sekolah <span style="color:red">*</span></label>
                <input type="text" name="nama_sekolah" class="form-control" required
                    value="<?= htmlspecialchars($pengaturan['nama_sekolah']) ?>"
                    placeholder="Contoh: SMA NEGERI 1 KOTA">
            </div>
            
            <div class="form-group">
                <label class="form-label">Nama Kepala Sekolah</label>
                <input type="text" name="kepala_sekolah" class="form-control"
                    value="<?= htmlspecialchars($pengaturan['kepala_sekolah'] ?? '') ?>"
                    placeholder="Contoh: Drs. Ahmad Fauzi, M.Pd">
            </div>
            
            <div class="form-group">
                <label class="form-label">NIP Kepala Sekolah</label>
                <input type="text" name="nip_kepala" class="form-control"
                    value="<?= htmlspecialchars($pengaturan['nip_kepala'] ?? '') ?>"
                    placeholder="Contoh: 196501011990031001">
            </div>
            
            <div class="form-group">
                <label class="form-label">Alamat Sekolah</label>
                <textarea name="alamat" class="form-control" rows="3" 
                    placeholder="Alamat lengkap sekolah"><?= htmlspecialchars($pengaturan['alamat'] ?? '') ?></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-save"></i> Simpan Pengaturan
            </button>
        </form>
    </div>
</div>

<!-- Admin Password Change -->
<div class="card mt-3" style="max-width:700px">
    <div class="card-header"><i class="fas fa-key"></i> Ganti Password Admin</div>
    <div class="card-body">
        <form action="ajax/ganti_password.php" method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Password Lama</label>
                    <input type="password" name="old_password" class="form-control" required placeholder="Password saat ini">
                </div>
                <div class="form-group">
                    <label class="form-label">Password Baru</label>
                    <input type="password" name="new_password" class="form-control" required placeholder="Min 6 karakter" minlength="6">
                </div>
            </div>
            <button type="submit" class="btn btn-warning">
                <i class="fas fa-key"></i> Ganti Password
            </button>
        </form>
    </div>
</div>

<script>
function previewLogo(input) {
    const preview = document.getElementById('logoPreview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php include 'includes/footer.php'; ?>
