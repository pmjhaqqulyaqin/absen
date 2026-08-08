<?php
require_once 'includes/config.php';
cek_login();

$msg = '';
$pengaturan = get_pengaturan();
$daftar_periode = get_daftar_periode($conn);
$periode_aktif  = get_periode_aktif($conn);

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

<!-- Kelola Tahun Ajaran & Semester -->
<div class="card mt-3" style="max-width:700px" id="kelola-periode">
    <div class="card-header"><i class="fas fa-calendar-alt"></i> Kelola Tahun Ajaran &amp; Semester</div>
    <div class="card-body">
        <div id="periodeMsg"></div>

        <table style="width:100%;border-collapse:collapse;margin-bottom:20px;font-size:.88rem">
            <thead>
                <tr style="text-align:left;border-bottom:2px solid var(--border)">
                    <th style="padding:8px 6px">Tahun Ajaran</th>
                    <th style="padding:8px 6px">Semester</th>
                    <th style="padding:8px 6px">Tanggal Mulai</th>
                    <th style="padding:8px 6px">Tanggal Selesai</th>
                    <th style="padding:8px 6px">Status</th>
                    <th style="padding:8px 6px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($daftar_periode as $p):
                    $isAktif = ((int)$p['id'] === (int)$periode_aktif['id']); ?>
                    <tr style="border-bottom:1px solid var(--border)" data-periode-id="<?= (int)$p['id'] ?>">
                        <td style="padding:8px 6px;font-weight:600"><?= htmlspecialchars($p['tahun_ajaran']) ?></td>
                        <td style="padding:8px 6px"><?= htmlspecialchars($p['semester']) ?></td>
                        <td style="padding:8px 6px">
                            <input type="date" class="form-control inp-tgl-mulai" style="padding:4px 6px;font-size:.85rem"
                                   value="<?= $p['tanggal_mulai'] ? htmlspecialchars($p['tanggal_mulai']) : '' ?>">
                        </td>
                        <td style="padding:8px 6px">
                            <input type="date" class="form-control inp-tgl-selesai" style="padding:4px 6px;font-size:.85rem"
                                   value="<?= $p['tanggal_selesai'] ? htmlspecialchars($p['tanggal_selesai']) : '' ?>">
                        </td>
                        <td style="padding:8px 6px">
                            <?php if ($isAktif): ?>
                                <span style="background:#dcfce7;color:#16a34a;padding:3px 8px;border-radius:20px;font-size:.75rem;font-weight:700">✅ Aktif</span>
                            <?php else: ?>
                                <span style="background:#f1f5f9;color:#64748b;padding:3px 8px;border-radius:20px;font-size:.75rem;font-weight:600">Tidak aktif</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:8px 6px;white-space:nowrap">
                            <button type="button" class="btn btn-sm" style="background:#e2e8f0;color:#334155;padding:4px 10px;font-size:.78rem"
                                    onclick="simpanTanggalPeriode(this)">
                                <i class="fas fa-save"></i> Simpan
                            </button>
                            <?php if (!$isAktif): ?>
                                <button type="button" class="btn btn-sm btn-primary" style="padding:4px 10px;font-size:.78rem"
                                        onclick="aktifkanPeriode(<?= (int)$p['id'] ?>)">
                                    Aktifkan
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div style="border-top:1px solid var(--border);padding-top:16px">
            <div style="font-weight:700;margin-bottom:10px">+ Tambah Tahun Ajaran / Semester Baru</div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Tahun Ajaran</label>
                    <input type="text" id="newTahunAjaran" class="form-control" placeholder="Contoh: 2027/2028">
                </div>
                <div class="form-group">
                    <label class="form-label">Semester</label>
                    <select id="newSemester" class="form-control">
                        <option value="Ganjil">Ganjil</option>
                        <option value="Genap">Genap</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Tanggal Mulai</label>
                    <input type="date" id="newTglMulai" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Tanggal Selesai</label>
                    <input type="date" id="newTglSelesai" class="form-control">
                </div>
            </div>
            <small style="color:var(--text-muted);display:block;margin-bottom:12px">
                Contoh: Semester Ganjil TA 2026/2027 bisa diisi 14 Juli 2026 s/d 31 Desember 2026.
                Semester Genap TA 2026/2027 bisa diisi 1 Januari 2027 s/d 30 Juni 2027.
                Setelah ditambahkan, periode baru ini otomatis diaktifkan dan data absensi/pelanggaran akan mulai kosong untuk periode ini (data siswa tetap ada).
            </small>
            <button type="button" class="btn btn-primary" onclick="tambahPeriodeBaru()">
                <i class="fas fa-plus"></i> Tambah &amp; Aktifkan
            </button>
        </div>
    </div>
</div>

<script>
function tampilkanPesanPeriode(msg, sukses) {
    const box = document.getElementById('periodeMsg');
    box.innerHTML = `<div class="alert alert-${sukses ? 'success' : 'danger'}"><i class="fas fa-info-circle"></i> ${msg}</div>`;
    if (sukses) setTimeout(() => location.reload(), 900);
}

function aktifkanPeriode(id) {
    if (!confirm('Aktifkan periode ini? Semua tampilan data (absensi, pelanggaran, laporan) akan mengikuti periode yang dipilih.')) return;
    const fd = new FormData();
    fd.append('action', 'switch');
    fd.append('id', id);
    fetch('ajax/periode.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => tampilkanPesanPeriode(d.msg || '', d.success));
}

function simpanTanggalPeriode(btn) {
    const row = btn.closest('tr');
    const id = row.getAttribute('data-periode-id');
    const tglMulai = row.querySelector('.inp-tgl-mulai').value;
    const tglSelesai = row.querySelector('.inp-tgl-selesai').value;
    const fd = new FormData();
    fd.append('action', 'update_tanggal');
    fd.append('id', id);
    fd.append('tanggal_mulai', tglMulai);
    fd.append('tanggal_selesai', tglSelesai);
    fetch('ajax/periode.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => tampilkanPesanPeriode(d.msg || '', d.success));
}

function tambahPeriodeBaru() {
    const tahun = document.getElementById('newTahunAjaran').value.trim();
    const semester = document.getElementById('newSemester').value;
    const tglMulai = document.getElementById('newTglMulai').value;
    const tglSelesai = document.getElementById('newTglSelesai').value;

    if (!/^\d{4}\/\d{4}$/.test(tahun)) {
        tampilkanPesanPeriode('Format Tahun Ajaran harus seperti 2027/2028', false);
        return;
    }
    if (!tglMulai || !tglSelesai) {
        tampilkanPesanPeriode('Tanggal mulai dan tanggal selesai wajib diisi', false);
        return;
    }
    if (!confirm(`Tambahkan Tahun Ajaran ${tahun} - ${semester} (${tglMulai} s/d ${tglSelesai}) dan langsung aktifkan?`)) return;

    const fd = new FormData();
    fd.append('action', 'tambah');
    fd.append('tahun_ajaran', tahun);
    fd.append('semester', semester);
    fd.append('tanggal_mulai', tglMulai);
    fd.append('tanggal_selesai', tglSelesai);
    fetch('ajax/periode.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => tampilkanPesanPeriode(d.msg || '', d.success));
}
</script>

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
