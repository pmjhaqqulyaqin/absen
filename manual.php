<?php
require_once 'includes/config.php';
cek_login();

$today   = date('Y-m-d');
$tanggal = isset($_GET['tanggal']) ? sanitize($_GET['tanggal']) : $today;
$kelas   = isset($_GET['kelas'])   ? sanitize($_GET['kelas'])   : '';
$kelas_list = get_kelas_list();
$msg = '';

// Save batch via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_batch'])) {
    $tanggal_save = sanitize($_POST['tanggal']);
    $siswa_ids    = $_POST['siswa_id']    ?? [];
    $statuses     = $_POST['status']      ?? [];
    $keterangans  = $_POST['keterangan']  ?? [];
    $jenis_absen  = ($_POST['jenis_absen'] ?? 'masuk') === 'pulang' ? 'pulang' : 'masuk';
    $count = 0;
    $skip_qr = 0;
    $pengaturan_waktu = get_pengaturan();
    $now = date('H:i:s');
    foreach ($siswa_ids as $i => $sid) {
        $sid = (int)$sid;
        // Gunakan trim biasa agar status tidak rusak oleh htmlspecialchars
        $st  = trim($statuses[$i] ?? 'Alpa');
        $ket = $conn->real_escape_string(trim($keterangans[$i] ?? ''));
        $s   = $conn->query("SELECT * FROM siswa WHERE id=$sid LIMIT 1")->fetch_assoc();
        if (!$s) continue;

        // Validasi status
        $valid_st = ['Hadir','Terlambat','Alpa','Sakit','Izin','Bolos'];
        if (!in_array($st, $valid_st)) $st = 'Alpa';

        // Escape nama dari DB dengan benar
        $s_nis   = $conn->real_escape_string($s['nis']);
        $s_nama  = $conn->real_escape_string($s['nama']);
        $s_kelas = $conn->real_escape_string($s['kelas']);

        if ($jenis_absen === 'pulang') {
            $ex = $conn->query("SELECT id,metode FROM absensi WHERE siswa_id=$sid AND tanggal='$tanggal_save' LIMIT 1")->fetch_assoc();
            if ($ex) {
                $conn->query("UPDATE absensi SET jam_pulang='$now' WHERE id={$ex['id']}");
                $count++;
            }
        } else {
            $ex = $conn->query("SELECT id,metode FROM absensi WHERE siswa_id=$sid AND tanggal='$tanggal_save' LIMIT 1")->fetch_assoc();
            if ($ex && $ex['metode'] === 'QR') {
                $skip_qr++;
                continue;
            }
            if ($st === 'Hadir' && $now > $pengaturan_waktu['jam_terlambat'] && $tanggal_save === date('Y-m-d')) {
                $st = 'Terlambat';
            }
            $jam = ($st === 'Hadir' || $st === 'Terlambat') ? "'$now'" : "NULL";

            // Keterangan otomatis jika kosong
            if ($ket === '') {
                $ket_default = ['Hadir'=>'','Terlambat'=>'Terlambat','Sakit'=>'Sakit','Izin'=>'Izin','Alpa'=>'Alpa','Bolos'=>'Bolos'];
                $ket = $conn->real_escape_string($ket_default[$st] ?? '');
            }

            $result = $conn->query("INSERT INTO absensi (siswa_id,nis,nama,kelas,tanggal,jam_masuk,status,keterangan,metode)
                VALUES ($sid,'$s_nis','$s_nama','$s_kelas','$tanggal_save',$jam,'$st','$ket','Manual')
                ON DUPLICATE KEY UPDATE status='$st',jam_masuk=$jam,keterangan='$ket',metode='Manual'");
            if ($result) $count++;
        }
    }
    $msg_extra = $skip_qr > 0 ? " ($skip_qr siswa dilewati karena sudah absen via QR)" : '';
    $msg = "success:Berhasil simpan $count absensi$msg_extra";
}

$siswa_list = [];
if ($kelas) {
    $res = $conn->query("SELECT s.*,a.status,a.jam_masuk,a.keterangan,a.metode,a.id as absen_id
        FROM siswa s LEFT JOIN absensi a ON s.id=a.siswa_id AND a.tanggal='$tanggal'
        WHERE s.kelas='$kelas' ORDER BY s.nama");
    while ($row=$res->fetch_assoc()) $siswa_list[] = $row;
}

include 'includes/header.php';
if ($msg) { list($t,$tx)=explode(':',$msg,2); echo "<div class='alert alert-$t'><i class='fas fa-check-circle'></i> $tx</div>"; }
?>

<style>
/* ===== CHECKBOX STYLES ===== */
.cb-col { width: 44px; text-align: center; }
input[type="checkbox"] { width:18px; height:18px; cursor:pointer; accent-color:#2563eb; }
tr.row-selected { outline: 2px solid #3b82f6; outline-offset: -2px; }

/* Bulk bar */
#bulkBar {
    display:none; position:sticky; bottom:20px; z-index:999;
    background:#1e293b; color:#fff; border-radius:12px;
    padding:13px 20px; align-items:center; gap:10px; flex-wrap:wrap;
    box-shadow:0 8px 32px rgba(0,0,0,.28); margin-top:14px;
    animation: slideUp .2s ease;
}
#bulkBar.show { display:flex; }
@keyframes slideUp { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }
.btn-bulk {
    border:none; border-radius:8px; padding:7px 16px;
    font-size:.85rem; font-weight:700; cursor:pointer;
    transition:opacity .15s,transform .1s;
}
.btn-bulk:hover{opacity:.85;transform:scale(1.04)}
.btn-bulk.hadir  {background:#16a34a;color:#fff}
.btn-bulk.sakit  {background:#0891b2;color:#fff}
.btn-bulk.izin   {background:#7c3aed;color:#fff}
.btn-bulk.alpa   {background:#64748b;color:#fff}
.btn-bulk.bolos  {background:#dc2626;color:#fff}
.btn-bulk.cancel {background:#475569;color:#fff}

/* Status buttons */
.status-btn-group { display:flex; gap:6px; flex-wrap:wrap; }
.status-pick-btn {
    padding:5px 10px; border:2px solid #e2e8f0;
    border-radius:20px; cursor:pointer; font-size:.78rem;
    font-weight:600; background:white; color:#475569; transition:all .15s;
}
.status-pick-btn:hover { transform:scale(1.05); }
.status-pick-btn.active { color:white; }
</style>

<div class="page-header">
    <div class="page-title"><i class="fas fa-edit"></i> Input Absensi</div>
    <div class="page-subtitle">Klik tombol status langsung per siswa</div>
</div>

<!-- Toggle Masuk/Pulang -->
<div style="display:flex;gap:10px;margin-bottom:16px;max-width:400px">
    <button id="btnJenisMasuk" onclick="setJenisSave('masuk')"
        style="flex:1;padding:10px 16px;border:2px solid #16a34a;border-radius:10px;background:#16a34a;color:white;font-weight:700;cursor:pointer">
        🟢 Absen Masuk
    </button>
    <button id="btnJenisPulang" onclick="setJenisSave('pulang')"
        style="flex:1;padding:10px 16px;border:2px solid #e2e8f0;border-radius:10px;background:white;color:#64748b;font-weight:700;cursor:pointer">
        🔴 Absen Pulang
    </button>
</div>
<input type="hidden" id="jenisAbsenSave" value="masuk">

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

<?php if ($kelas && !empty($siswa_list)): ?>
<form method="POST" id="absenForm">
    <input type="hidden" name="tanggal" value="<?= $tanggal ?>">
    <input type="hidden" name="save_batch" value="1">
    <input type="hidden" name="jenis_absen" id="formJenisAbsen" value="masuk">

    <div class="card">
        <div class="card-header" style="flex-wrap:wrap;gap:8px;">
            <i class="fas fa-list" style="color:var(--primary)"></i>
            Kelas <strong><?= $kelas ?></strong> &mdash; <?= format_tanggal($tanggal) ?>

            <!-- SEARCH BOX -->
            <div style="position:relative;margin-left:12px;">
                <i class="fas fa-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.82rem;pointer-events:none"></i>
                <input type="text" id="searchManual" placeholder="Cari nama / NIS..."
                    style="padding:6px 10px 6px 30px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:.83rem;width:190px;outline:none;transition:border-color .2s"
                    onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
            </div>

            <div class="ms-auto" style="display:flex;gap:6px;flex-wrap:wrap;align-items:center">
                <span style="font-size:.8rem;color:var(--text-muted)">Set semua:</span>
                <?php foreach (['Hadir'=>'#16a34a','Sakit'=>'#0891b2','Izin'=>'#7c3aed','Alpa'=>'#64748b','Bolos'=>'#dc2626'] as $s=>$c): ?>
                <button type="button" onclick="setAll('<?= $s ?>')"
                    style="background:<?= $c ?>;color:white;border:none;padding:5px 12px;border-radius:20px;cursor:pointer;font-size:.8rem;font-weight:600">
                    <?= $s ?>
                </button>
                <?php endforeach; ?>
                <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-save"></i> Simpan</button>
            </div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th class="cb-col">
                            <input type="checkbox" id="checkAll" title="Pilih Semua">
                        </th>
                        <th width="4%">#</th>
                        <th width="12%">NIS</th>
                        <th width="25%">Nama</th>
                        <th width="33%">Status (Klik untuk pilih)</th>
                        <th width="18%">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($siswa_list as $i => $s):
                        $cur_status = $s['status'] ?? 'Alpa';
                        $is_qr = ($s['metode'] === 'QR');
                    ?>
                    <tr id="row-<?= $i ?>" style="background:<?= statusBg($cur_status) ?>;cursor:pointer"
                        onclick="rowClick(event,<?= $i ?>,<?= $is_qr?'true':'false' ?>)">
                        <td class="cb-col" onclick="event.stopPropagation()">
                            <?php if (!$is_qr): ?>
                            <input type="checkbox" class="row-cb" data-idx="<?= $i ?>" value="<?= $s['id'] ?>">
                            <?php else: ?>
                            <i class="fas fa-lock" style="color:#9ca3af;font-size:.8rem" title="Terkunci (QR)"></i>
                            <?php endif; ?>
                        </td>
                        <td><?= $i+1 ?></td>
                        <td>
                            <code><?= $s['nis'] ?></code>
                            <input type="hidden" name="siswa_id[]" value="<?= $s['id'] ?>">
                            <input type="hidden" name="status[]" id="stat-<?= $i ?>" value="<?= $cur_status ?>">
                            <?php if ($is_qr): ?>
                            <br><span style="font-size:.68rem;background:#dcfce7;color:#15803d;padding:2px 6px;border-radius:20px;font-weight:600">
                                <i class="fas fa-qrcode"></i> QR <?= $s['jam_masuk'] ? date('H:i',strtotime($s['jam_masuk'])) : '' ?>
                            </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display:flex;align-items:center;gap:8px">
                                <?php if (!empty($s['foto']) && file_exists('uploads/foto/'.$s['foto'])): ?>
                                    <img src="<?= BASE_URL ?>uploads/foto/<?= $s['foto'] ?>" class="student-photo" style="width:34px;height:34px">
                                <?php else: ?>
                                    <div class="student-avatar" style="width:34px;height:34px;font-size:.8rem;flex-shrink:0"><?= strtoupper(substr($s['nama'],0,1)) ?></div>
                                <?php endif; ?>
                                <span><?= htmlspecialchars($s['nama']) ?></span>
                            </div>
                        </td>
                        <td onclick="event.stopPropagation()">
                            <?php if ($is_qr): ?>
                            <div style="display:flex;align-items:center;gap:8px;padding:8px 12px;background:#f0fdf4;border-radius:8px;border:1px solid #bbf7d0">
                                <i class="fas fa-lock" style="color:#16a34a"></i>
                                <span style="font-weight:700;color:#15803d"><?= $cur_status ?></span>
                                <span style="font-size:.75rem;color:#6b7280">— Terkunci (sudah scan QR)</span>
                            </div>
                            <?php else: ?>
                            <div class="status-btn-group">
                                <?php foreach ([
                                    'Hadir'  => ['#16a34a','✅'],
                                    'Sakit'  => ['#0891b2','🏥'],
                                    'Izin'   => ['#7c3aed','📋'],
                                    'Alpa'   => ['#64748b','❌'],
                                    'Bolos'  => ['#dc2626','🚫'],
                                ] as $st => [$color, $icon]): ?>
                                <button type="button"
                                    id="btn-<?= $i ?>-<?= $st ?>"
                                    onclick="setStatus(<?= $i ?>, '<?= $st ?>');event.stopPropagation()"
                                    class="status-pick-btn <?= $cur_status===$st?'active':'' ?>"
                                    data-color="<?= $color ?>"
                                    style="<?= $cur_status===$st?"background:{$color};color:white;border-color:{$color}":'' ?>">
                                    <?= $icon ?> <?= $st ?>
                                </button>
                                <?php endforeach; ?>
                            </div>
                            <div style="font-size:.72rem;color:#94a3b8;margin-top:4px">
                                <i class="fas fa-info-circle"></i> Status Hadir otomatis jadi Terlambat jika lewat <?= date('H:i',strtotime($pengaturan['jam_terlambat'])) ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td onclick="event.stopPropagation()">
                            <input type="text" name="keterangan[]" id="ket-<?= $i ?>" class="form-control"
                                value="<?= htmlspecialchars($s['keterangan'] ?? '') ?>"
                                placeholder="Opsional" style="font-size:.82rem"
                                <?= $is_qr ? 'readonly style="font-size:.82rem;background:#f9fafb;color:#9ca3af"' : '' ?>>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div style="padding:16px 20px;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:10px">
            <button type="submit" class="btn btn-success btn-lg">
                <i class="fas fa-save"></i> Simpan Absensi Kelas <?= $kelas ?>
            </button>
        </div>
    </div>
</form>

<!-- ===== BULK BAR ===== -->
<div id="bulkBar">
    <span style="font-weight:700"><i class="fas fa-check-square"></i> <span id="selCount">0</span> dipilih</span>
    <span style="flex:1"></span>
    <span style="font-size:.82rem;color:#94a3b8">Set terpilih:</span>
    <button class="btn-bulk hadir" onclick="setMassal('Hadir')">✅ Hadir</button>
    <button class="btn-bulk sakit" onclick="setMassal('Sakit')">🏥 Sakit</button>
    <button class="btn-bulk izin"  onclick="setMassal('Izin')">📋 Izin</button>
    <button class="btn-bulk alpa"  onclick="setMassal('Alpa')">❌ Alpa</button>
    <button class="btn-bulk bolos" onclick="setMassal('Bolos')">🚫 Bolos</button>
    <button class="btn-bulk cancel" onclick="clearAll()">✕ Batal</button>
</div>

<?php elseif ($kelas): ?>
<div class="alert alert-warning"><i class="fas fa-info-circle"></i> Tidak ada siswa di kelas <?= $kelas ?></div>
<?php else: ?>
<div class="card"><div class="card-body" style="text-align:center;padding:60px">
    <i class="fas fa-hand-point-up fa-3x" style="color:var(--text-muted);opacity:.3"></i>
    <h3 style="margin-top:16px;color:var(--text-muted)">Pilih kelas terlebih dahulu</h3>
</div></div>
<?php endif; ?>

<?php
function statusBg($s) {
    $map=['Hadir'=>'#f0fdf4','Terlambat'=>'#fffbeb','Alpa'=>'#f8fafc','Sakit'=>'#eff6ff','Izin'=>'#f5f3ff','Bolos'=>'#fff7ed'];
    return $map[$s]??'';
}
?>

<script>
/* ===== Keterangan otomatis per status ===== */
const ketDefault = {
    'Hadir'  : '',
    'Sakit'  : 'Sakit',
    'Izin'   : 'Izin',
    'Alpa'   : 'Alpa',
    'Bolos'  : 'Bolos',
    'Terlambat': 'Terlambat'
};

const rowColors = {
    'Hadir':'#f0fdf4','Terlambat':'#fffbeb','Alpa':'#f8fafc',
    'Sakit':'#eff6ff','Izin':'#f5f3ff','Bolos':'#fff7ed'
};
const statuses  = ['Hadir','Sakit','Izin','Alpa','Bolos'];
const rowCount  = <?= count($siswa_list) ?>;

function setStatus(i, status) {
    // Update hidden input
    document.getElementById('stat-'+i).value = status;
    // Update row background
    const row = document.getElementById('row-'+i);
    if (row) row.style.background = rowColors[status]||'';
    // Update button active state
    statuses.forEach(s => {
        const btn = document.getElementById('btn-'+i+'-'+s);
        if (!btn) return;
        if (s === status) {
            btn.classList.add('active');
            btn.style.background   = btn.dataset.color;
            btn.style.color        = 'white';
            btn.style.borderColor  = btn.dataset.color;
        } else {
            btn.classList.remove('active');
            btn.style.background   = '';
            btn.style.color        = '';
            btn.style.borderColor  = '';
        }
    });
    // ✅ Auto-isi keterangan sesuai status
    const ketInput = document.getElementById('ket-'+i);
    if (ketInput && !ketInput.readOnly) {
        ketInput.value = ketDefault[status] ?? '';
    }
}

function setAll(status) {
    for (let i = 0; i < rowCount; i++) setStatus(i, status);
    showToast('Semua siswa diset: ' + status, 'info');
}

/* ===== Checkbox logic ===== */
const checkAll = document.getElementById('checkAll');
const bulkBar  = document.getElementById('bulkBar');
const selCountEl = document.getElementById('selCount');

function getCheckedBoxes() {
    return [...document.querySelectorAll('.row-cb:checked')];
}

function updateBulkBar() {
    const n = getCheckedBoxes().length;
    if (selCountEl) selCountEl.textContent = n;
    if (bulkBar) bulkBar.classList.toggle('show', n > 0);
    // highlight rows
    document.querySelectorAll('.row-cb').forEach(cb => {
        cb.closest('tr').classList.toggle('row-selected', cb.checked);
    });
    // sync checkAll
    const all = [...document.querySelectorAll('.row-cb')];
    const chk = all.filter(c => c.checked);
    if (checkAll) {
        checkAll.indeterminate = chk.length > 0 && chk.length < all.length;
        checkAll.checked = chk.length > 0 && chk.length === all.length;
    }
}

if (checkAll) {
    checkAll.addEventListener('change', () => {
        document.querySelectorAll('.row-cb').forEach(cb => cb.checked = checkAll.checked);
        updateBulkBar();
    });
}

document.addEventListener('change', e => {
    if (e.target.classList.contains('row-cb')) updateBulkBar();
});

// Klik baris toggle checkbox
function rowClick(e, i, isQr) {
    if (isQr) return;
    const cb = document.querySelector('.row-cb[data-idx="'+i+'"]');
    if (cb) { cb.checked = !cb.checked; updateBulkBar(); }
}

function clearAll() {
    document.querySelectorAll('.row-cb').forEach(cb => cb.checked = false);
    if (checkAll) checkAll.checked = false;
    updateBulkBar();
}

function setMassal(status) {
    const checked = getCheckedBoxes();
    checked.forEach(cb => {
        const idx = parseInt(cb.dataset.idx);
        setStatus(idx, status);
    });
    showToast(checked.length + ' siswa diset: ' + status, 'success');
    clearAll();
}

/* ===== Search nama / NIS ===== */
const searchInput = document.getElementById('searchManual');
if (searchInput) {
    searchInput.addEventListener('input', function() {
        const q = this.value.toLowerCase().trim();
        document.querySelectorAll('#absenForm tbody tr').forEach(tr => {
            const match = tr.textContent.toLowerCase().includes(q);
            tr.style.display = match ? '' : 'none';
            if (!match) {
                const cb = tr.querySelector('.row-cb');
                if (cb) { cb.checked = false; }
            }
        });
        updateBulkBar();
    });
}

function setJenisSave(jenis) {
    document.getElementById('jenisAbsenSave').value = jenis;
    const formEl = document.getElementById('formJenisAbsen');
    if (formEl) formEl.value = jenis;
    const btnM = document.getElementById('btnJenisMasuk');
    const btnP = document.getElementById('btnJenisPulang');
    if (jenis === 'masuk') {
        btnM.style.cssText = 'flex:1;padding:10px 16px;border:2px solid #16a34a;border-radius:10px;background:#16a34a;color:white;font-weight:700;cursor:pointer';
        btnP.style.cssText = 'flex:1;padding:10px 16px;border:2px solid #e2e8f0;border-radius:10px;background:white;color:#64748b;font-weight:700;cursor:pointer';
    } else {
        btnP.style.cssText = 'flex:1;padding:10px 16px;border:2px solid #dc2626;border-radius:10px;background:#dc2626;color:white;font-weight:700;cursor:pointer';
        btnM.style.cssText = 'flex:1;padding:10px 16px;border:2px solid #e2e8f0;border-radius:10px;background:white;color:#64748b;font-weight:700;cursor:pointer';
    }
    showToast('Mode: Absen ' + (jenis === 'masuk' ? 'MASUK' : 'PULANG'), 'info');
}
</script>

<?php include 'includes/footer.php'; ?>
