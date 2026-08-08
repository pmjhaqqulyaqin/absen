<?php
require_once 'includes/config.php';
cek_login();

// Pastikan tabel tingkat ada + refresh daftar tingkat via helper di config.php
get_tingkat_list();

$msg    = '';
$action = $_GET['action'] ?? '';

// HAPUS
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $conn->query("DELETE FROM tingkat WHERE id=$id");
    $msg = 'success:Tingkat berhasil dihapus';
}

// SIMPAN (tambah/edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_tingkat'])) {
    $id           = (int)($_POST['id'] ?? 0);
    $nama_tingkat = sanitize($_POST['nama_tingkat']);
    $keterangan   = sanitize($_POST['keterangan'] ?? '');

    if (!$nama_tingkat) {
        $msg = 'error:Nama tingkat tidak boleh kosong';
    } else {
        if ($id) {
            $ok = $conn->query("UPDATE tingkat SET nama_tingkat='$nama_tingkat', keterangan='$keterangan' WHERE id=$id");
            $msg = $ok ? 'success:Tingkat berhasil diupdate' : 'error:Gagal update &mdash; '.htmlspecialchars($conn->error);
        } else {
            $urutan = (int)($conn->query("SELECT COALESCE(MAX(urutan),0) m FROM tingkat")->fetch_assoc()['m']) + 1;
            $r = $conn->query("INSERT INTO tingkat (nama_tingkat, keterangan, urutan) VALUES ('$nama_tingkat','$keterangan',$urutan)");
            $msg = $r ? 'success:Tingkat berhasil ditambahkan' : 'error:Gagal simpan &mdash; '.htmlspecialchars($conn->error);
        }
    }
}

$tingkat_rows = $conn->query("SELECT * FROM tingkat ORDER BY urutan ASC, id ASC");

include 'includes/header.php';
if ($msg) { list($t,$tx) = explode(':',$msg,2); echo "<div class='alert alert-$t'><i class='fas fa-check-circle'></i> $tx</div>"; }
?>

<div class="page-header d-flex align-center">
    <div>
        <div class="page-title"><i class="fas fa-layer-group"></i> Kelola Tingkat</div>
        <div class="page-subtitle">Tingkat adalah level kelas (misal: X, XI, XII). Buat tingkat dulu sebelum membuat Rombel.</div>
    </div>
    <div class="ms-auto">
        <button class="btn btn-primary" onclick="openTingkatModal()">
            <i class="fas fa-plus"></i> Tambah Tingkat
        </button>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="fas fa-layer-group" style="color:var(--primary)"></i>
        Daftar Tingkat (<?= $tingkat_rows->num_rows ?> tingkat)
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nama Tingkat</th>
                    <th>Keterangan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($tingkat_rows->num_rows === 0): ?>
                <tr><td colspan="4" style="text-align:center;padding:40px;color:var(--text-muted)">
                    <i class="fas fa-layer-group" style="font-size:2rem;margin-bottom:8px;display:block"></i>
                    Belum ada tingkat. Klik <strong>+ Tambah Tingkat</strong> untuk mulai.
                </td></tr>
                <?php else: $no=0; while ($row = $tingkat_rows->fetch_assoc()): $no++; ?>
                <tr>
                    <td><?= $no ?></td>
                    <td>
                        <span style="background:#fef3c7;color:#92400e;padding:3px 12px;border-radius:20px;font-size:.8rem;font-weight:700">
                            <?= htmlspecialchars($row['nama_tingkat']) ?>
                        </span>
                    </td>
                    <td><?= $row['keterangan'] ? htmlspecialchars($row['keterangan']) : '-' ?></td>
                    <td>
                        <button class="btn btn-sm btn-secondary" onclick='editTingkatModal(<?= json_encode($row) ?>)'>
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <a href="?action=delete&id=<?= $row['id'] ?>" class="btn btn-sm btn-danger"
                           onclick="return confirm('Hapus tingkat <?= htmlspecialchars($row['nama_tingkat']) ?>?\nRombel yang sudah memakai tingkat ini tidak akan terhapus.')">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah/Edit -->
<div id="modalTingkat" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center">
    <div style="background:white;border-radius:12px;padding:28px;width:100%;max-width:420px;box-shadow:var(--shadow-lg)">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
            <h3 id="modalTitleTingkat" style="font-size:1rem;font-weight:700"><i class="fas fa-layer-group" style="color:var(--primary)"></i> Tambah Tingkat</h3>
            <button type="button" onclick="closeTingkatModal()" style="background:none;border:none;font-size:1.2rem;cursor:pointer;color:#64748b">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="save_tingkat" value="1">
            <input type="hidden" name="id" id="editIdTingkat" value="0">
            <div style="margin-bottom:14px">
                <label class="form-label">Nama Tingkat <span style="color:red">*</span></label>
                <input type="text" name="nama_tingkat" id="inputNamaTingkat" class="form-control" placeholder="Contoh: X, XI, XII atau 1, 2, 3" required>
            </div>
            <div style="margin-bottom:20px">
                <label class="form-label">Keterangan (opsional)</label>
                <input type="text" name="keterangan" id="inputKeterangan" class="form-control" placeholder="Contoh: Kelas 1 SD">
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end">
                <button type="button" onclick="closeTingkatModal()" class="btn btn-secondary">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
// NOTE: sengaja dinamai unik (bukan openModal/closeModal) karena assets/js/app.js
// sudah mendefinisikan fungsi global openModal(id)/closeModal(id) dengan parameter
// berbeda — kalau nama sama, punya app.js akan menimpa punya halaman ini sehingga
// tombol Tambah/Edit Tingkat tidak bereaksi sama sekali.
function openTingkatModal() {
    document.getElementById('modalTitleTingkat').innerHTML = '<i class="fas fa-layer-group" style="color:var(--primary)"></i> Tambah Tingkat';
    document.getElementById('editIdTingkat').value = '0';
    document.getElementById('inputNamaTingkat').value = '';
    document.getElementById('inputKeterangan').value = '';
    document.getElementById('modalTingkat').style.display = 'flex';
}
function editTingkatModal(d) {
    document.getElementById('modalTitleTingkat').innerHTML = '<i class="fas fa-edit" style="color:var(--primary)"></i> Edit Tingkat';
    document.getElementById('editIdTingkat').value = d.id;
    document.getElementById('inputNamaTingkat').value = d.nama_tingkat;
    document.getElementById('inputKeterangan').value = d.keterangan || '';
    document.getElementById('modalTingkat').style.display = 'flex';
}
function closeTingkatModal() {
    document.getElementById('modalTingkat').style.display = 'none';
}
document.getElementById('modalTingkat').addEventListener('click', function(e) {
    if (e.target === this) closeTingkatModal();
});
</script>

<?php include 'includes/footer.php'; ?>
