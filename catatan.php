<?php
require_once 'includes/config.php';
cek_login();

$msg='';
// DELETE
if (isset($_GET['action']) && $_GET['action']==='delete' && isset($_GET['id'])) {
    $conn->query("DELETE FROM catatan WHERE id=".(int)$_GET['id']);
    $msg='success:Catatan dihapus';
}
// SAVE
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_catatan'])) {
    $sid   = (int)$_POST['siswa_id'];
    $tipe  = sanitize($_POST['tipe']);
    $judul = sanitize($_POST['judul']);
    $isi   = sanitize($_POST['isi']);
    $conn->query("INSERT INTO catatan (siswa_id,wali_id,tipe,judul,isi) VALUES ($sid,NULL,'$tipe','$judul','$isi')");
    $msg='success:Catatan berhasil ditambah';
}

$filter_siswa=(int)($_GET['siswa']??0);
$semua_siswa=$conn->query("SELECT id,nis,nama,kelas FROM siswa ORDER BY kelas,nama");
$where=$filter_siswa?"WHERE c.siswa_id=$filter_siswa":'';
$catatan=$conn->query("SELECT c.*,s.nama as nama_siswa,s.kelas,s.nis,
    COALESCE(w.nama,'Admin') as nama_wali
    FROM catatan c
    JOIN siswa s ON s.id=c.siswa_id
    LEFT JOIN wali w ON w.id=c.wali_id
    $where ORDER BY c.created_at DESC");

$tipe_colors=['Informasi'=>'#3b82f6','Peringatan'=>'#f59e0b','Urgent'=>'#ef4444','Apresiasi'=>'#10b981'];
$tipe_icons=['Informasi'=>'info-circle','Peringatan'=>'exclamation-triangle','Urgent'=>'exclamation-circle','Apresiasi'=>'star'];

include 'includes/header.php';
if ($msg){list($t,$tx)=explode(':',$msg,2);echo "<div class='alert alert-$t'><i class='fas fa-check-circle'></i> $tx</div>";}
?>

<div class="page-header d-flex align-center">
    <div>
        <div class="page-title"><i class="fas fa-sticky-note"></i> Catatan Siswa</div>
        <div class="page-subtitle">Informasi, peringatan, apresiasi untuk siswa</div>
    </div>
    <button class="btn btn-success ms-auto" onclick="openModal('modalCatatan')"><i class="fas fa-plus"></i> Tambah Catatan</button>
</div>

<!-- Filter -->
<div class="card mb-3"><div class="card-body">
    <form method="GET" class="filter-bar">
        <select name="siswa" class="form-select">
            <option value="">Semua Siswa</option>
            <?php $semua_siswa->data_seek(0); while($s=$semua_siswa->fetch_assoc()): ?>
            <option value="<?= $s['id'] ?>" <?= $filter_siswa==$s['id']?'selected':'' ?>><?= htmlspecialchars($s['nama']) ?> (<?= $s['kelas'] ?>)</option>
            <?php endwhile; ?>
        </select>
        <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
        <a href="catatan.php" class="btn btn-outline"><i class="fas fa-times"></i></a>
    </form>
</div></div>

<!-- List catatan -->
<div style="display:grid;gap:16px">
    <?php if ($catatan->num_rows===0): ?>
    <div class="card"><div class="card-body" style="text-align:center;padding:60px;color:var(--text-muted)">
        <i class="fas fa-sticky-note fa-3x" style="opacity:.3"></i>
        <p style="margin-top:16px">Belum ada catatan</p>
    </div></div>
    <?php else: while ($c=$catatan->fetch_assoc()): 
        $clr=$tipe_colors[$c['tipe']]??'#64748b';
        $ico=$tipe_icons[$c['tipe']]??'sticky-note'; ?>
    <div class="card" style="border-left:4px solid <?= $clr ?>">
        <div class="card-body" style="padding:16px 20px">
            <div style="display:flex;align-items:flex-start;gap:12px">
                <div style="width:44px;height:44px;background:<?= $clr ?>20;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    <i class="fas fa-<?= $ico ?>" style="color:<?= $clr ?>;font-size:1.2rem"></i>
                </div>
                <div style="flex:1">
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                        <span style="background:<?= $clr ?>;color:white;padding:2px 10px;border-radius:20px;font-size:.75rem;font-weight:600"><?= $c['tipe'] ?></span>
                        <strong><?= htmlspecialchars($c['judul']) ?></strong>
                        <span class="badge" style="background:#eff6ff;color:var(--primary)"><?= htmlspecialchars($c['nama_siswa']) ?> (<?= $c['kelas'] ?>)</span>
                    </div>
                    <p style="margin:8px 0;color:var(--text-muted);font-size:.875rem"><?= nl2br(htmlspecialchars($c['isi'])) ?></p>
                    <div style="font-size:.75rem;color:var(--text-muted)">
                        <i class="fas fa-user"></i> <?= $c['nama_wali'] ?> &nbsp;|&nbsp;
                        <i class="fas fa-clock"></i> <?= date('d/m/Y H:i', strtotime($c['created_at'])) ?>
                    </div>
                </div>
                <button onclick="confirmDelete('catatan.php?action=delete&id=<?= $c['id'] ?>')" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
            </div>
        </div>
    </div>
    <?php endwhile; endif; ?>
</div>

<!-- MODAL -->
<div class="modal-overlay" id="modalCatatan">
    <div class="modal">
        <div class="modal-header">
            <span><i class="fas fa-sticky-note"></i> Tambah Catatan</span>
            <button class="close-btn" onclick="closeModal('modalCatatan')"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Siswa *</label>
                    <select name="siswa_id" class="form-select" required>
                        <option value="">-- Pilih Siswa --</option>
                        <?php $semua_siswa->data_seek(0); while($s=$semua_siswa->fetch_assoc()): ?>
                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nama']) ?> (<?= $s['kelas'] ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Tipe *</label>
                        <select name="tipe" class="form-select" required>
                            <option value="Informasi">📘 Informasi</option>
                            <option value="Peringatan">⚠️ Peringatan</option>
                            <option value="Urgent">🚨 Urgent</option>
                            <option value="Apresiasi">⭐ Apresiasi</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Judul *</label>
                        <input type="text" name="judul" class="form-control" required placeholder="Judul catatan">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Isi Catatan *</label>
                    <textarea name="isi" class="form-control" rows="4" required placeholder="Tulis catatan..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('modalCatatan')">Batal</button>
                <button type="submit" name="save_catatan" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
