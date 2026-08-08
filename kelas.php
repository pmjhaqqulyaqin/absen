<?php
require_once 'includes/config.php';
cek_login();

// Auto-create tabel kelas jika belum ada
$conn->query("CREATE TABLE IF NOT EXISTS kelas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kelas VARCHAR(30) NOT NULL UNIQUE,
    tingkat VARCHAR(10) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Tambah kolom kelas_wali ke tabel wali jika belum ada
$conn->query("ALTER TABLE wali ADD COLUMN IF NOT EXISTS kelas_wali VARCHAR(30) DEFAULT ''");

$msg = '';
$action = $_GET['action'] ?? '';

// HAPUS
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $conn->query("DELETE FROM kelas WHERE id=$id");
    $msg = 'success:Rombel berhasil dihapus';
}

// SIMPAN (tambah/edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_kelas'])) {
    $id         = (int)($_POST['id'] ?? 0);
    $nama_kelas = sanitize($_POST['nama_kelas']);
    $tingkat    = sanitize($_POST['tingkat']);

    if (!$nama_kelas) {
        $msg = 'error:Nama rombel tidak boleh kosong';
    } else {
        if ($id) {
            $conn->query("UPDATE kelas SET nama_kelas='$nama_kelas', tingkat='$tingkat' WHERE id=$id");
            $msg = 'success:Rombel berhasil diupdate';
        } else {
            $r = $conn->query("INSERT INTO kelas (nama_kelas, tingkat) VALUES ('$nama_kelas','$tingkat')");
            $msg = $r ? 'success:Rombel berhasil ditambahkan' : 'error:Rombel sudah ada atau terjadi kesalahan';
        }
    }
}

$tingkat_list = get_tingkat_list();

$kelas_list = $conn->query("SELECT k.*, 
    (SELECT COUNT(*) FROM siswa s WHERE s.kelas = k.nama_kelas) as jumlah_siswa,
    (SELECT nama FROM wali WHERE kelas_wali = k.nama_kelas LIMIT 1) as nama_wali
    FROM kelas k ORDER BY nama_kelas");

include 'includes/header.php';
if ($msg) { list($t,$tx) = explode(':',$msg,2); echo "<div class='alert alert-$t'><i class='fas fa-check-circle'></i> $tx</div>"; }
?>

<div class="page-header d-flex align-center">
    <div>
        <div class="page-title"><i class="fas fa-chalkboard"></i> Kelola Rombel</div>
        <div class="page-subtitle">Data rombel tersinkron ke Kelola Siswa, Import Excel, Kelola Wali &amp; Pindah Kelas</div>
    </div>
    <div class="ms-auto">
        <?php if (empty($tingkat_list)): ?>
        <a href="tingkat.php" class="btn btn-outline"><i class="fas fa-layer-group"></i> Buat Tingkat Dulu</a>
        <?php else: ?>
        <button class="btn btn-primary" onclick="openKelasModal()">
            <i class="fas fa-plus"></i> Tambah Rombel
        </button>
        <?php endif; ?>
    </div>
</div>

<?php if (empty($tingkat_list)): ?>
<div class="card mb-3" style="border-left:4px solid var(--warning)">
    <div class="card-body" style="display:flex;align-items:center;gap:10px">
        <i class="fas fa-exclamation-triangle" style="color:var(--warning);font-size:1.2rem"></i>
        <div style="font-size:.85rem">Belum ada <strong>Tingkat</strong>. Buat tingkat dulu di menu
            <a href="tingkat.php">Kelola Tingkat</a> sebelum membuat Rombel.</div>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <i class="fas fa-list" style="color:var(--primary)"></i>
        Daftar Rombel (<?= $kelas_list->num_rows ?> rombel)
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nama Rombel</th>
                    <th>Tingkat</th>
                    <th>Jumlah Siswa</th>
                    <th>Wali Kelas</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($kelas_list->num_rows === 0): ?>
                <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--text-muted)">
                    <i class="fas fa-chalkboard" style="font-size:2rem;margin-bottom:8px;display:block"></i>
                    Belum ada rombel. Klik <strong>+ Tambah Rombel</strong> untuk mulai.
                </td></tr>
                <?php else: $no=0; while ($row = $kelas_list->fetch_assoc()): $no++; ?>
                <tr>
                    <td><?= $no ?></td>
                    <td><strong><?= htmlspecialchars($row['nama_kelas']) ?></strong></td>
                    <td>
                        <?php if ($row['tingkat']): ?>
                        <span style="background:#fef3c7;color:#92400e;padding:3px 12px;border-radius:20px;font-size:.8rem;font-weight:700">
                            <?= htmlspecialchars($row['tingkat']) ?>
                        </span>
                        <?php else: ?>-<?php endif; ?>
                    </td>
                    <td>
                        <span style="background:#dbeafe;color:#1e40af;padding:2px 10px;border-radius:20px;font-size:.8rem;font-weight:600">
                            <?= $row['jumlah_siswa'] ?> siswa
                        </span>
                    </td>
                    <td><?= $row['nama_wali'] ? htmlspecialchars($row['nama_wali']) : '<span style="color:#94a3b8">Belum ada</span>' ?></td>
                    <td>
                        <button class="btn btn-sm btn-secondary" onclick='editKelasModal(<?= json_encode($row) ?>)'>
                            <i class="fas fa-edit"></i>
                        </button>
                        <a href="?action=delete&id=<?= $row['id'] ?>" class="btn btn-sm btn-danger"
                           onclick="return confirm('Hapus rombel <?= htmlspecialchars($row['nama_kelas']) ?>?\nSiswa yang sudah ada tidak akan terhapus.')">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Info Sinkronisasi -->
<div class="card mt-3" style="border-left:4px solid var(--primary)">
    <div class="card-body" style="display:flex;gap:24px;flex-wrap:wrap">
        <div style="display:flex;align-items:center;gap:10px">
            <i class="fas fa-sync-alt" style="color:var(--primary);font-size:1.2rem"></i>
            <div>
                <div style="font-weight:600;font-size:.85rem">Tersinkron Otomatis</div>
                <div style="font-size:.78rem;color:var(--text-muted)">Rombel di sini muncul sebagai dropdown di Kelola Siswa, Import Excel, Kelola Wali &amp; Pindah Kelas</div>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:10px">
            <i class="fas fa-users" style="color:var(--success);font-size:1.2rem"></i>
            <div>
                <div style="font-weight:600;font-size:.85rem">Jumlah Siswa Real-time</div>
                <div style="font-size:.78rem;color:var(--text-muted)">Kolom jumlah siswa dihitung langsung dari data aktual</div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah/Edit -->
<div id="modalKelas" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;display:none;align-items:center;justify-content:center">
    <div style="background:white;border-radius:12px;padding:28px;width:100%;max-width:420px;box-shadow:var(--shadow-lg)">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
            <h3 id="modalTitleKelas" style="font-size:1rem;font-weight:700"><i class="fas fa-chalkboard" style="color:var(--primary)"></i> Tambah Rombel</h3>
            <button onclick="closeKelasModal()" style="background:none;border:none;font-size:1.2rem;cursor:pointer;color:#64748b">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="save_kelas" value="1">
            <input type="hidden" name="id" id="editIdKelas" value="0">
            <div style="margin-bottom:14px">
                <label class="form-label">Nama Rombel <span style="color:red">*</span></label>
                <input type="text" name="nama_kelas" id="inputNamaKelas" class="form-control" placeholder="Contoh: X-A, XI-IPA-1, XII-IPS-2" required>
            </div>
            <div style="margin-bottom:20px">
                <label class="form-label">Tingkat</label>
                <select name="tingkat" id="inputTingkat" class="form-select">
                    <option value="">-- Pilih Tingkat --</option>
                    <?php foreach ($tingkat_list as $t): ?>
                        <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($tingkat_list)): ?>
                <small style="color:var(--warning)">
                    <i class="fas fa-exclamation-triangle"></i> Belum ada tingkat. <a href="tingkat.php">Buat dulu di sini</a>.
                </small>
                <?php endif; ?>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end">
                <button type="button" onclick="closeKelasModal()" class="btn btn-secondary">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openKelasModal() {
    document.getElementById('modalTitleKelas').innerHTML = '<i class="fas fa-chalkboard" style="color:var(--primary)"></i> Tambah Rombel';
    document.getElementById('editIdKelas').value = '0';
    document.getElementById('inputNamaKelas').value = '';
    document.getElementById('inputTingkat').value = '';
    document.getElementById('modalKelas').style.display = 'flex';
}
function editKelasModal(d) {
    document.getElementById('modalTitleKelas').innerHTML = '<i class="fas fa-edit" style="color:var(--primary)"></i> Edit Rombel';
    document.getElementById('editIdKelas').value = d.id;
    document.getElementById('inputNamaKelas').value = d.nama_kelas;
    document.getElementById('inputTingkat').value = d.tingkat || '';
    document.getElementById('modalKelas').style.display = 'flex';
}
function closeKelasModal() {
    document.getElementById('modalKelas').style.display = 'none';
}
document.getElementById('modalKelas').addEventListener('click', function(e) {
    if (e.target === this) closeKelasModal();
});
</script>

<?php include 'includes/footer.php'; ?>
