<?php
require_once 'includes/config.php';
cek_login();
// Migrasi kolom no_wa_ortu jika belum ada
$conn->query("ALTER TABLE siswa ADD COLUMN IF NOT EXISTS no_wa_ortu VARCHAR(20) DEFAULT ''");


$msg    = '';
$action = $_GET['action'] ?? '';

// ── HAPUS MASSAL (via POST checkbox) ─────────────────────
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['hapus_massal'])) {
    $ids   = $_POST['siswa_ids'] ?? [];
    $count = 0;
    foreach ($ids as $sid) {
        $sid = (int)$sid;
        if (!$sid) continue;
        $s = $conn->query("SELECT foto FROM siswa WHERE id=$sid")->fetch_assoc();
        if ($s && $s['foto'] && file_exists(__DIR__.'/uploads/foto/'.$s['foto'])) unlink(__DIR__.'/uploads/foto/'.$s['foto']);
        $conn->query("DELETE FROM absensi   WHERE siswa_id=$sid");
        $conn->query("DELETE FROM wali_siswa WHERE siswa_id=$sid");
        $conn->query("DELETE FROM catatan   WHERE siswa_id=$sid");
        $conn->query("DELETE FROM siswa     WHERE id=$sid");
        $count++;
    }
    $msg = "success:$count siswa berhasil dihapus";
}

// ── DELETE satu siswa ─────────────────────────────────────
if ($action==='delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $s  = $conn->query("SELECT foto FROM siswa WHERE id=$id")->fetch_assoc();
    if ($s && $s['foto'] && file_exists(__DIR__.'/uploads/foto/'.$s['foto'])) unlink(__DIR__.'/uploads/foto/'.$s['foto']);
    $conn->query("DELETE FROM absensi    WHERE siswa_id=$id");
    $conn->query("DELETE FROM wali_siswa WHERE siswa_id=$id");
    $conn->query("DELETE FROM catatan    WHERE siswa_id=$id");
    $conn->query("DELETE FROM siswa      WHERE id=$id");
    $msg = 'success:Siswa berhasil dihapus';
}

// ── RESET PASSWORD ke NIS ─────────────────────────────────
if ($action==='reset_pw' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $s  = $conn->query("SELECT nis FROM siswa WHERE id=$id")->fetch_assoc();
    if ($s) {
        $hash = password_hash($s['nis'], PASSWORD_DEFAULT);
        $conn->query("UPDATE siswa SET password='$hash' WHERE id=$id");
        $msg = 'success:Password direset ke NIS: '.$s['nis'];
    }
}

// ── SAVE (tambah / edit) ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_siswa'])) {
    $id    = (int)($_POST['id'] ?? 0);
    $nis   = sanitize($_POST['nis']);
    $nama  = sanitize($_POST['nama']);
    $kelas     = sanitize($_POST['kelas']);
    $no_wa_ortu= preg_replace('/\D/','',$_POST['no_wa_ortu']??'');
    $pw_new    = $_POST['pw_new'] ?? '';

    $foto = '';
    if (!empty($_FILES['foto']['name'])) {
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        if (in_array($ext,['jpg','jpeg','png','gif','webp']) && $_FILES['foto']['size'] < 2*1024*1024) {
            $foto = $nis.'_'.time().'.'.$ext;
            move_uploaded_file($_FILES['foto']['tmp_name'], __DIR__.'/uploads/foto/'.$foto);
            if ($id) {
                $old = $conn->query("SELECT foto FROM siswa WHERE id=$id")->fetch_assoc();
                if ($old['foto'] && file_exists(__DIR__.'/uploads/foto/'.$old['foto'])) unlink(__DIR__.'/uploads/foto/'.$old['foto']);
            }
        }
    }

    $pw_sql = $pw_new ? ",password='".password_hash($pw_new,PASSWORD_DEFAULT)."'" : '';

    if ($id) {
        $foto_sql = $foto ? ",foto='$foto'" : '';
        $conn->query("UPDATE siswa SET nis='$nis',nama='$nama',kelas='$kelas',no_wa_ortu='$no_wa_ortu'$foto_sql$pw_sql WHERE id=$id");
        $msg = 'success:Data siswa diupdate';
    } else {
        $chk = $conn->query("SELECT id FROM siswa WHERE nis='$nis'")->fetch_assoc();
        if ($chk) {
            $msg = 'danger:NIS sudah ada';
        } else {
            $foto_val   = $foto ? "'$foto'" : 'NULL';
            $default_pw = password_hash($nis, PASSWORD_DEFAULT);
            $conn->query("INSERT INTO siswa (nis,nama,kelas,foto,password,no_wa_ortu) VALUES ('$nis','$nama','$kelas',$foto_val,'$default_pw','$no_wa_ortu')");
            $msg = 'success:Siswa ditambahkan. Password default = NIS';
        }
    }
}

// ── Query data ────────────────────────────────────────────
$search     = sanitize($_GET['search'] ?? '');
$fkelas     = sanitize($_GET['kelas']  ?? '');
$page       = max(1,(int)($_GET['page'] ?? 1));
$per        = 20;
$off        = ($page-1)*$per;
$kelas_list = get_kelas_list();

$where = '1=1';
if ($search) $where .= " AND (nis LIKE '%$search%' OR nama LIKE '%$search%')";
if ($fkelas) $where .= " AND kelas='$fkelas'";

$total = $conn->query("SELECT COUNT(*) c FROM siswa WHERE $where")->fetch_assoc()['c'];
$pages = ceil($total/$per);
$res   = $conn->query("SELECT * FROM siswa WHERE $where ORDER BY kelas,nama LIMIT $per OFFSET $off");

$edit_siswa = null;
if ($action==='edit' && isset($_GET['id']))
    $edit_siswa = $conn->query("SELECT * FROM siswa WHERE id=".(int)$_GET['id'])->fetch_assoc();

$view_qr = null;
if ($action==='qr' && isset($_GET['id']))
    $view_qr = $conn->query("SELECT * FROM siswa WHERE id=".(int)$_GET['id'])->fetch_assoc();

include 'includes/header.php';
if ($msg) { list($t,$tx)=explode(':',$msg,2); echo "<div class='alert alert-$t'><i class='fas fa-check-circle'></i> $tx</div>"; }
?>

<div class="page-header d-flex align-center">
    <div>
        <div class="page-title"><i class="fas fa-users"></i> Kelola Siswa</div>
        <div class="page-subtitle">CRUD, foto, QR code, reset password portal</div>
    </div>
    <div class="ms-auto" style="display:flex;gap:8px">
        <button class="btn btn-success" onclick="openModal('modalSiswa')"><i class="fas fa-plus"></i> Tambah</button>
                <a href="notif_belum_absen.php" class="btn btn-primary" style="background:#22c55e;"><i class="fab fa-whatsapp"></i> Notif Belum Absen</a>
        <a href="import_excel.php" class="btn btn-info"><i class="fas fa-file-excel"></i> Import</a>
        <a href="barcode_generate.php" class="btn btn-warning"><i class="fas fa-qrcode"></i> QR Cards</a>
    </div>
</div>

<!-- ── Filter Bar: kelas auto-submit, search + Enter ─── -->
<div class="card mb-3"><div class="card-body">
    <form method="GET" class="filter-bar" id="filterForm">
        <div class="search-box" style="flex:1;max-width:300px">
            <i class="fas fa-search"></i>
            <input type="text" name="search" id="searchInput"
                   placeholder="Cari NIS/nama... (Enter)" value="<?= $search ?>">
        </div>
        <!-- onchange → langsung submit, tanpa klik tombol filter -->
        <select name="kelas" class="form-select" style="width:auto"
                onchange="document.getElementById('filterForm').submit()">
            <option value="">Semua Kelas</option>
            <?php foreach ($kelas_list as $k): ?>
                <option value="<?= $k ?>" <?= $fkelas===$k?'selected':'' ?>><?= $k ?></option>
            <?php endforeach; ?>
        </select>
        <?php if ($search || $fkelas): ?>
            <a href="siswa.php" class="btn btn-outline" title="Reset filter"><i class="fas fa-times"></i></a>
        <?php endif; ?>
    </form>
</div></div>

<!-- ── Form hapus massal membungkus tabel ──────────────── -->
<form method="POST" id="formHapusMassal">
<input type="hidden" name="hapus_massal" value="1">

<div class="card">
    <div class="card-header" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
        <i class="fas fa-list" style="color:var(--primary)"></i>
        Daftar Siswa
        <span class="badge" style="background:#eff6ff;color:var(--primary)"><?= $total ?> siswa</span>
        <div style="margin-left:auto;display:flex;align-items:center;gap:10px">
            <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:.85rem;color:var(--text-muted);margin:0;font-weight:500">
                <input type="checkbox" id="checkAll" onchange="toggleAll(this)"
                       style="width:16px;height:16px;cursor:pointer;accent-color:var(--primary)">
                Pilih Semua
            </label>
            <button type="button" id="btnHapusMassal"
                    onclick="hapusTerpilih()"
                    class="btn btn-danger btn-sm"
                    style="display:none;align-items:center;gap:6px">
                <i class="fas fa-trash"></i> Hapus Terpilih
                (<span id="jumlahPilih">0</span>)
            </button>
        </div>
    </div>

    <div class="table-container">
        <table>
            <thead><tr>
                <th style="width:44px;text-align:center">✓</th>
                <th>#</th><th>NIS</th><th>Foto</th><th>Nama</th>
                <th>Kelas</th><th>Portal</th><th>Aksi</th>
            </tr></thead>
            <tbody>
                <?php if ($res->num_rows===0): ?>
                <tr><td colspan="8" style="text-align:center;padding:48px;color:var(--text-muted)">
                    <i class="fas fa-users-slash fa-2x" style="opacity:.3;display:block;margin-bottom:8px"></i>
                    Tidak ada data siswa
                </td></tr>
                <?php else: $no=$off; while($s=$res->fetch_assoc()): $no++; ?>
                <tr id="row-<?= $s['id'] ?>">
                    <td style="text-align:center">
                        <input type="checkbox" name="siswa_ids[]" value="<?= $s['id'] ?>"
                               class="checkSiswa" onchange="updateHapusBtn()"
                               style="width:16px;height:16px;cursor:pointer;accent-color:var(--primary)">
                    </td>
                    <td><?= $no ?></td>
                    <td><code><?= $s['nis'] ?></code></td>
                    <td>
                        <?php if (!empty($s['foto'])&&file_exists(__DIR__.'/uploads/foto/'.$s['foto'])): ?>
                            <img src="<?= BASE_URL ?>uploads/foto/<?= $s['foto'] ?>" class="student-photo">
                        <?php else: ?>
                            <div class="student-avatar"><?= strtoupper(substr($s['nama'],0,1)) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($s['nama']) ?></td>
                    <td><span class="badge" style="background:#eff6ff;color:var(--primary)"><?= $s['kelas'] ?></span></td>
                    <td>
                        <span title="<?= $s['password']?'Password custom':'Password = NIS' ?>">
                            <?= $s['password']
                                ?'<i class="fas fa-key" style="color:var(--success)"></i>'
                                :'<i class="fas fa-key" style="color:var(--text-muted);opacity:.4"></i>' ?>
                        </span>
                        <a href="siswa.php?action=reset_pw&id=<?= $s['id'] ?>"
                           onclick="return confirm('Reset password ke NIS: <?= $s['nis'] ?>?')"
                           title="Reset password ke NIS" style="margin-left:4px">
                            <i class="fas fa-redo" style="color:var(--warning);font-size:.85rem"></i>
                        </a>
                    </td>
                    <td>
                        <a href="siswa.php?action=qr&id=<?= $s['id'] ?>" class="btn btn-sm btn-info" title="QR Code"><i class="fas fa-qrcode"></i></a>
                        <a href="siswa.php?action=edit&id=<?= $s['id'] ?>" class="btn btn-sm btn-warning" title="Edit"><i class="fas fa-edit"></i></a>
                        <button type="button"
                                onclick="confirmDelete('siswa.php?action=delete&id=<?= $s['id'] ?>')"
                                class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
                <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pages>1): ?>
    <div style="padding:16px 20px">
        <div class="pagination">
            <?php for($p=1;$p<=$pages;$p++): ?>
            <a href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&kelas=<?= urlencode($fkelas) ?>"
               class="page-btn <?= $p==$page?'active':'' ?>"><?= $p ?></a>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
</form><!-- /formHapusMassal -->

<!-- ── MODAL QR Code ──────────────────────────────────── -->
<?php if ($view_qr): ?>
<div class="modal-overlay show" id="modalQR">
    <div class="modal" style="max-width:360px">
        <div class="modal-header">
            <span><i class="fas fa-qrcode"></i> QR Code Siswa</span>
            <a href="siswa.php" class="close-btn"><i class="fas fa-times"></i></a>
        </div>
        <div class="modal-body" style="text-align:center">
            <div style="font-weight:700;font-size:1.1rem;margin-bottom:4px"><?= htmlspecialchars($view_qr['nama']) ?></div>
            <div style="color:var(--text-muted);margin-bottom:16px"><?= $view_qr['nis'] ?> | <?= $view_qr['kelas'] ?></div>
            <div id="qrSiswa" style="display:flex;justify-content:center"></div>
            <div style="margin-top:8px;letter-spacing:4px;font-family:monospace;font-size:1.1rem;font-weight:700"><?= $view_qr['nis'] ?></div>
        </div>
        <div class="modal-footer">
            <a href="siswa.php" class="btn btn-outline">Tutup</a>
            <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> Cetak</button>
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
new QRCode(document.getElementById('qrSiswa'),{
    text:'<?= $view_qr['nis'] ?>',width:200,height:200,
    colorDark:'#000',colorLight:'#fff',correctLevel:QRCode.CorrectLevel.M
});
</script>
<?php endif; ?>

<!-- ── MODAL TAMBAH/EDIT ──────────────────────────────── -->
<div class="modal-overlay <?= $edit_siswa?'show':'' ?>" id="modalSiswa">
    <div class="modal">
        <div class="modal-header">
            <span><i class="fas fa-user-plus"></i> <?= $edit_siswa?'Edit':'Tambah' ?> Siswa</span>
            <button class="close-btn" onclick="closeModal('modalSiswa')"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <?php if ($edit_siswa): ?><input type="hidden" name="id" value="<?= $edit_siswa['id'] ?>"><?php endif; ?>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">NIS *</label>
                    <input type="text" name="nis" class="form-control" required placeholder="Nomor Induk Siswa"
                           value="<?= htmlspecialchars($edit_siswa['nis']??'') ?>" <?= $edit_siswa?'readonly':'' ?>>
                </div>
                <div class="form-group">
                    <label class="form-label">Nama Lengkap *</label>
                    <input type="text" name="nama" class="form-control" required value="<?= htmlspecialchars($edit_siswa['nama']??'') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Kelas *</label>
                    <select name="kelas" class="form-select" required>
                        <option value="">-- Pilih Kelas --</option>
                        <?php foreach($kelas_list as $k): ?>
                        <option value="<?= htmlspecialchars($k) ?>" <?= ($edit_siswa['kelas']??'')===$k?'selected':'' ?>><?= htmlspecialchars($k) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($kelas_list)): ?>
                    <small style="color:#f59e0b"><i class="fas fa-exclamation-triangle"></i> Belum ada kelas. <a href="kelas.php">Tambah kelas dulu</a></small>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label class="form-label">Foto (opsional)</label>
                    <input type="file" name="foto" class="form-control" accept="image/*">
                    <?php if (!empty($edit_siswa['foto'])&&file_exists(__DIR__.'/uploads/foto/'.$edit_siswa['foto'])): ?>
                        <img src="<?= BASE_URL ?>uploads/foto/<?= $edit_siswa['foto'] ?>" style="width:60px;height:60px;object-fit:cover;border-radius:8px;margin-top:6px">
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label class="form-label"><i class="fab fa-whatsapp" style="color:#22c55e;"></i> No WA Orang Tua</label>
                    <input type="text" name="no_wa_ortu" class="form-control"
                           value="<?= htmlspecialchars($edit_siswa['no_wa_ortu']??'') ?>"
                           placeholder="08xxxxxxxxxx (untuk notifikasi otomatis)">
                </div>
                <div class="form-group">
                    <label class="form-label">Password Portal <?= $edit_siswa?'(kosong = tidak berubah)':'(default = NIS)' ?></label>
                    <input type="password" name="pw_new" class="form-control" placeholder="<?= $edit_siswa?'Kosongkan jika tidak ingin ganti':'Default = NIS siswa' ?>">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('modalSiswa')">Batal</button>
                <button type="submit" name="save_siswa" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>

<?php if ($edit_siswa): ?>
<script>document.addEventListener('DOMContentLoaded',()=>openModal('modalSiswa'));</script>
<?php endif; ?>

<script>
// ── Checkbox hapus massal ─────────────────────────────────
function toggleAll(master) {
    document.querySelectorAll('.checkSiswa').forEach(cb => cb.checked = master.checked);
    updateHapusBtn();
}

function updateHapusBtn() {
    const checked = document.querySelectorAll('.checkSiswa:checked');
    const total   = document.querySelectorAll('.checkSiswa').length;
    const btn     = document.getElementById('btnHapusMassal');
    const master  = document.getElementById('checkAll');

    document.getElementById('jumlahPilih').textContent = checked.length;
    btn.style.display        = checked.length > 0 ? 'inline-flex' : 'none';
    master.indeterminate     = checked.length > 0 && checked.length < total;
    master.checked           = total > 0 && checked.length === total;
}

function hapusTerpilih() {
    const n = document.querySelectorAll('.checkSiswa:checked').length;
    if (!n) return;
    if (!confirm(`Yakin hapus ${n} siswa terpilih?\nSemua data absensi mereka ikut terhapus dan tidak bisa dikembalikan!`)) return;
    document.getElementById('formHapusMassal').submit();
}

// ── Search: submit saat tekan Enter ──────────────────────
document.getElementById('searchInput').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); document.getElementById('filterForm').submit(); }
});
</script>

<?php include 'includes/footer.php'; ?>
