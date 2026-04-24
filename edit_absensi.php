<?php
require_once 'includes/config.php';
cek_login();

$today    = date('Y-m-d');
$tanggal  = isset($_GET['tanggal']) ? sanitize($_GET['tanggal']) : $today;
$kelas    = isset($_GET['kelas'])   ? sanitize($_GET['kelas'])   : '';
$kelas_list = get_kelas_list();
$msg = '';

/* ===== SIMPAN EDIT SATU RECORD ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_single'])) {
    $absen_id  = (int)($_POST['absen_id'] ?? 0);
    $new_status = trim($_POST['new_status'] ?? '');
    $new_ket    = $conn->real_escape_string(trim($_POST['new_keterangan'] ?? ''));
    $valid_st   = ['Hadir','Terlambat','Alpa','Sakit','Izin','Bolos'];
    if ($absen_id && in_array($new_status, $valid_st)) {
        $now = date('H:i:s');
        $jam = ($new_status === 'Hadir' || $new_status === 'Terlambat') ? "'$now'" : "NULL";
        // Jika sudah ada jam_masuk sebelumnya, pertahankan
        $ex = $conn->query("SELECT jam_masuk FROM absensi WHERE id=$absen_id LIMIT 1")->fetch_assoc();
        $jam_final = ($ex && $ex['jam_masuk']) ? "'{$ex['jam_masuk']}'" : $jam;
        if ($new_status === 'Alpa' || $new_status === 'Sakit' || $new_status === 'Izin' || $new_status === 'Bolos') {
            $jam_final = "NULL";
        }
        $conn->query("UPDATE absensi SET status='$new_status', keterangan='$new_ket', jam_masuk=$jam_final, metode='Manual', updated_at=NOW() WHERE id=$absen_id");
        $msg = "success:Absensi berhasil diperbarui menjadi $new_status";
    } else {
        $msg = "danger:Data tidak valid";
    }
}

/* ===== HAPUS RECORD (reset ke belum absen) ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_absen'])) {
    $absen_id = (int)($_POST['absen_id'] ?? 0);
    if ($absen_id) {
        $conn->query("DELETE FROM absensi WHERE id=$absen_id");
        $msg = "success:Absensi berhasil dihapus (siswa kembali ke Belum Absen)";
    }
}

/* ===== AMBIL DATA ===== */
$absen_list = [];
if ($kelas) {
    $res = $conn->query("
        SELECT a.*, s.foto
        FROM absensi a
        JOIN siswa s ON a.siswa_id = s.id
        WHERE a.tanggal='$tanggal' AND a.kelas='$kelas'
        ORDER BY a.nama
    ");
    while ($row = $res->fetch_assoc()) $absen_list[] = $row;
}

/* ===== HITUNG STATISTIK ===== */
$stats = ['Hadir'=>0,'Terlambat'=>0,'Sakit'=>0,'Izin'=>0,'Alpa'=>0,'Bolos'=>0];
foreach ($absen_list as $a) {
    if (isset($stats[$a['status']])) $stats[$a['status']]++;
}

include 'includes/header.php';
if ($msg) {
    list($t,$tx) = explode(':', $msg, 2);
    echo "<div class='alert alert-$t'><i class='fas fa-check-circle'></i> $tx</div>";
}
?>

<style>
.stat-mini {
    display:inline-flex; align-items:center; gap:6px;
    padding:6px 14px; border-radius:20px; font-size:.82rem;
    font-weight:700; margin:3px;
}
.sm-hadir     { background:#dcfce7; color:#15803d; }
.sm-terlambat { background:#fef9c3; color:#854d0e; }
.sm-alpa      { background:#fee2e2; color:#991b1b; }
.sm-sakit     { background:#dbeafe; color:#1e40af; }
.sm-izin      { background:#ede9fe; color:#5b21b6; }
.sm-bolos     { background:#ffedd5; color:#9a3412; }

/* Modal */
#editModal {
    display:none; position:fixed; inset:0;
    background:rgba(0,0,0,.5); z-index:9999;
    align-items:center; justify-content:center;
}
#editModal.show { display:flex; }
.modal-box {
    background:#fff; border-radius:16px; padding:28px 32px;
    min-width:340px; max-width:420px; width:90%;
    box-shadow:0 20px 60px rgba(0,0,0,.25);
    animation: popIn .2s ease;
}
@keyframes popIn {
    from{opacity:0;transform:scale(.93)} to{opacity:1;transform:scale(1)}
}
.modal-box h3 { margin:0 0 6px; font-size:1.1rem; color:#1e293b; }
.modal-box .sub { font-size:.82rem; color:#64748b; margin-bottom:20px; }
.status-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:8px; margin-bottom:16px; }
.st-opt {
    border:2px solid #e2e8f0; border-radius:10px; padding:10px 6px;
    text-align:center; cursor:pointer; font-size:.82rem; font-weight:600;
    color:#475569; background:#fff; transition:all .15s;
}
.st-opt:hover { transform:scale(1.04); }
.st-opt.sel { color:#fff; border-color:transparent; }
.st-hadir   { --c:#16a34a }
.st-terlambat{ --c:#d97706 }
.st-sakit   { --c:#0891b2 }
.st-izin    { --c:#7c3aed }
.st-alpa    { --c:#64748b }
.st-bolos   { --c:#dc2626 }
.st-opt.sel { background: var(--c); border-color: var(--c); }
.modal-label { font-size:.82rem; font-weight:600; color:#374151; margin-bottom:5px; display:block; }
.modal-actions { display:flex; gap:10px; margin-top:20px; }
.btn-modal-save {
    flex:1; background:#2563eb; color:#fff; border:none;
    padding:11px; border-radius:9px; font-weight:700;
    cursor:pointer; font-size:.9rem;
}
.btn-modal-save:hover { background:#1d4ed8; }
.btn-modal-cancel {
    background:#f1f5f9; color:#475569; border:none;
    padding:11px 18px; border-radius:9px; font-weight:600;
    cursor:pointer; font-size:.9rem;
}
.btn-modal-delete {
    background:#fee2e2; color:#991b1b; border:none;
    padding:11px 14px; border-radius:9px; font-weight:600;
    cursor:pointer; font-size:.9rem; title:"Hapus (reset ke belum absen)";
}
.btn-modal-delete:hover { background:#fecaca; }

/* Tabel row hover */
#editTable tbody tr { cursor:pointer; transition:background .12s; }
#editTable tbody tr:hover { background:#eff6ff !important; }
.edit-btn {
    background:#eff6ff; color:#2563eb; border:none;
    padding:5px 12px; border-radius:7px; font-size:.78rem;
    font-weight:600; cursor:pointer;
}
.edit-btn:hover { background:#dbeafe; }
</style>

<div class="page-header d-flex align-center">
    <div>
        <div class="page-title"><i class="fas fa-pen-square"></i> Edit Absensi</div>
        <div class="page-subtitle">Koreksi status absensi yang salah input</div>
    </div>
</div>

<!-- Filter -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="filter-bar">
            <div>
                <label class="form-label">Tanggal</label>
                <input type="date" name="tanggal" class="form-control" value="<?= $tanggal ?>" max="<?= $today ?>">
            </div>
            <div>
                <label class="form-label">Kelas</label>
                <select name="kelas" class="form-select">
                    <option value="">-- Pilih Kelas --</option>
                    <?php foreach ($kelas_list as $k): ?>
                        <option value="<?= $k ?>" <?= $kelas==$k?'selected':'' ?>><?= $k ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:flex;align-items:flex-end">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Tampilkan</button>
            </div>
        </form>
    </div>
</div>

<?php if ($kelas && !empty($absen_list)): ?>

<!-- Statistik Mini -->
<div style="margin-bottom:14px;display:flex;flex-wrap:wrap;gap:4px;align-items:center">
    <span style="font-size:.82rem;color:#64748b;margin-right:4px"><i class="fas fa-chart-bar"></i> Rekap:</span>
    <?php
    $stat_icons = ['Hadir'=>'✅','Terlambat'=>'⏰','Sakit'=>'🏥','Izin'=>'📋','Alpa'=>'❌','Bolos'=>'🚫'];
    $stat_cls   = ['Hadir'=>'sm-hadir','Terlambat'=>'sm-terlambat','Sakit'=>'sm-sakit','Izin'=>'sm-izin','Alpa'=>'sm-alpa','Bolos'=>'sm-bolos'];
    foreach ($stats as $st => $jml): if ($jml > 0): ?>
    <span class="stat-mini <?= $stat_cls[$st] ?>"><?= $stat_icons[$st] ?> <?= $st ?>: <?= $jml ?></span>
    <?php endif; endforeach; ?>
    <span style="margin-left:8px;font-size:.82rem;color:#64748b">Total dicatat: <?= count($absen_list) ?> siswa</span>
</div>

<!-- Tabel Edit -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-pen-square" style="color:var(--primary)"></i>
        Kelas <strong><?= $kelas ?></strong> &mdash; <?= format_tanggal($tanggal) ?>
        <div class="ms-auto search-box" style="width:200px">
            <i class="fas fa-search"></i>
            <input type="text" id="searchEdit" placeholder="Cari nama / NIS...">
        </div>
    </div>
    <div class="table-container">
        <table id="editTable">
            <thead>
                <tr>
                    <th width="4%">#</th>
                    <th width="12%">NIS</th>
                    <th width="28%">Nama</th>
                    <th width="14%">Jam Masuk</th>
                    <th width="14%">Status</th>
                    <th width="18%">Keterangan</th>
                    <th width="10%">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($absen_list as $i => $a): ?>
                <tr onclick="openEdit(<?= htmlspecialchars(json_encode($a), ENT_QUOTES) ?>)"
                    style="background:<?= rowBg($a['status']) ?>">
                    <td><?= $i+1 ?></td>
                    <td><code><?= $a['nis'] ?></code></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px">
                            <?php if (!empty($a['foto']) && file_exists('uploads/foto/'.$a['foto'])): ?>
                                <img src="<?= BASE_URL ?>uploads/foto/<?= $a['foto'] ?>" style="width:32px;height:32px;border-radius:50%;object-fit:cover">
                            <?php else: ?>
                                <div style="width:32px;height:32px;border-radius:50%;background:#3b82f6;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem;flex-shrink:0">
                                    <?= strtoupper(substr($a['nama'],0,1)) ?>
                                </div>
                            <?php endif; ?>
                            <?= htmlspecialchars($a['nama']) ?>
                        </div>
                    </td>
                    <td style="color:#64748b;font-size:.85rem">
                        <?= $a['jam_masuk'] ? date('H:i', strtotime($a['jam_masuk'])) : '<span style="color:#cbd5e1">—</span>' ?>
                    </td>
                    <td><?= get_status_badge($a['status']) ?></td>
                    <td style="font-size:.83rem;color:#475569"><?= htmlspecialchars($a['keterangan'] ?? '') ?: '<span style="color:#cbd5e1">—</span>' ?></td>
                    <td>
                        <button class="edit-btn" onclick="event.stopPropagation();openEdit(<?= htmlspecialchars(json_encode($a), ENT_QUOTES) ?>)">
                            <i class="fas fa-pen"></i> Edit
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php elseif ($kelas): ?>
<div class="alert alert-warning"><i class="fas fa-info-circle"></i> Tidak ada data absensi untuk kelas <?= $kelas ?> pada tanggal ini.</div>
<?php else: ?>
<div class="card"><div class="card-body" style="text-align:center;padding:60px">
    <i class="fas fa-search fa-3x" style="color:var(--text-muted);opacity:.25"></i>
    <h3 style="margin-top:16px;color:var(--text-muted)">Pilih tanggal dan kelas</h3>
    <p style="color:#94a3b8">Data absensi yang sudah tersimpan akan muncul di sini untuk diedit</p>
</div></div>
<?php endif; ?>

<!-- ===== MODAL EDIT ===== -->
<div id="editModal">
    <div class="modal-box">
        <h3 id="modalNama">—</h3>
        <div class="sub" id="modalSub">NIS • Kelas</div>

        <label class="modal-label">Ubah Status:</label>
        <div class="status-grid">
            <?php foreach ([
                'Hadir'     => ['✅','st-hadir'],
                'Terlambat' => ['⏰','st-terlambat'],
                'Sakit'     => ['🏥','st-sakit'],
                'Izin'      => ['📋','st-izin'],
                'Alpa'      => ['❌','st-alpa'],
                'Bolos'     => ['🚫','st-bolos'],
            ] as $st => [$ico, $cls]): ?>
            <div class="st-opt <?= $cls ?>" data-status="<?= $st ?>" onclick="pilihStatus('<?= $st ?>')">
                <?= $ico ?><br><?= $st ?>
            </div>
            <?php endforeach; ?>
        </div>

        <label class="modal-label">Keterangan:</label>
        <input type="text" id="modalKet" class="form-control" placeholder="Opsional..." style="margin-bottom:4px">

        <div class="modal-actions">
            <button class="btn-modal-cancel" onclick="closeModal()">Batal</button>
            <button class="btn-modal-delete" onclick="hapusAbsen()" title="Hapus — siswa kembali ke Belum Absen">
                <i class="fas fa-trash"></i>
            </button>
            <button class="btn-modal-save" onclick="simpanEdit()">
                <i class="fas fa-save"></i> Simpan
            </button>
        </div>
        <p style="font-size:.72rem;color:#94a3b8;margin-top:10px;text-align:center">
            <i class="fas fa-info-circle"></i> Hapus = siswa kembali ke daftar Belum Absen
        </p>
    </div>
</div>

<!-- Form POST tersembunyi -->
<form method="POST" id="formEdit" style="display:none">
    <input type="hidden" name="absen_id"       id="fAbsenId">
    <input type="hidden" name="new_status"      id="fStatus">
    <input type="hidden" name="new_keterangan"  id="fKet">
    <input type="hidden" name="edit_single"     value="1">
</form>
<form method="POST" id="formHapus" style="display:none">
    <input type="hidden" name="absen_id"    id="fHapusId">
    <input type="hidden" name="hapus_absen" value="1">
</form>

<?php
function rowBg($s) {
    $map=['Hadir'=>'#f0fdf4','Terlambat'=>'#fffbeb','Alpa'=>'#f8fafc','Sakit'=>'#eff6ff','Izin'=>'#f5f3ff','Bolos'=>'#fff7ed'];
    return $map[$s]??'';
}
?>

<script>
let currentAbsenId = null;
let selectedStatus = null;

const ketDefaults = {
    'Hadir':'', 'Terlambat':'Terlambat',
    'Sakit':'Sakit', 'Izin':'Izin',
    'Alpa':'Alpa', 'Bolos':'Bolos'
};

function openEdit(data) {
    currentAbsenId = data.id;
    selectedStatus = data.status;

    document.getElementById('modalNama').textContent = data.nama;
    document.getElementById('modalSub').textContent  = 'NIS: ' + data.nis + ' • Kelas: ' + data.kelas
        + (data.jam_masuk ? ' • Jam: ' + data.jam_masuk.substring(0,5) : '')
        + ' • Metode: ' + (data.metode || '—');
    document.getElementById('modalKet').value = data.keterangan || '';

    // Set status aktif
    document.querySelectorAll('.st-opt').forEach(el => {
        el.classList.toggle('sel', el.dataset.status === data.status);
    });

    document.getElementById('editModal').classList.add('show');
}

function pilihStatus(st) {
    selectedStatus = st;
    document.querySelectorAll('.st-opt').forEach(el => {
        el.classList.toggle('sel', el.dataset.status === st);
    });
    // Auto isi keterangan
    const ketInput = document.getElementById('modalKet');
    if (!ketInput.value || Object.values(ketDefaults).includes(ketInput.value)) {
        ketInput.value = ketDefaults[st] ?? '';
    }
}

function closeModal() {
    document.getElementById('editModal').classList.remove('show');
    currentAbsenId = null;
    selectedStatus = null;
}

function simpanEdit() {
    if (!currentAbsenId || !selectedStatus) return;
    document.getElementById('fAbsenId').value = currentAbsenId;
    document.getElementById('fStatus').value  = selectedStatus;
    document.getElementById('fKet').value     = document.getElementById('modalKet').value;
    document.getElementById('formEdit').submit();
}

function hapusAbsen() {
    if (!currentAbsenId) return;
    if (!confirm('Hapus absensi ini? Siswa akan kembali ke daftar Belum Absen.')) return;
    document.getElementById('fHapusId').value = currentAbsenId;
    document.getElementById('formHapus').submit();
}

// Tutup modal klik luar
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

// Search
document.getElementById('searchEdit')?.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#editTable tbody tr').forEach(tr => {
        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});
</script>

<?php include 'includes/footer.php'; ?>
