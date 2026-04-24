<?php
require_once 'includes/config.php';
cek_login();

$today = date('Y-m-d');
$kelas = sanitize($_GET['kelas'] ?? '');
$kelas_list = get_kelas_list();

// FIX: gunakan id bukan s.id di subquery
$where_sub = "id NOT IN (SELECT siswa_id FROM absensi WHERE tanggal='$today')";
if ($kelas) $where_sub .= " AND kelas='$kelas'";

$data  = $conn->query("SELECT * FROM siswa WHERE $where_sub ORDER BY kelas, nama");
$total = $conn->query("SELECT COUNT(*) as c FROM siswa WHERE $where_sub")->fetch_assoc()['c'];

include 'includes/header.php';
?>

<style>
/* === CHECKBOX & BULK ACTION STYLES === */
.cb-row { width: 40px; text-align: center; }
input[type="checkbox"] {
    width: 18px; height: 18px; cursor: pointer;
    accent-color: var(--primary, #2563eb);
}
tr.row-selected {
    background: #eff6ff !important;
}
#bulkBar {
    display: none;
    position: sticky;
    bottom: 20px;
    z-index: 999;
    margin: 16px 0 0 0;
    background: #1e293b;
    color: #fff;
    border-radius: 12px;
    padding: 14px 20px;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    box-shadow: 0 8px 32px rgba(0,0,0,0.28);
    animation: slideUp .25s ease;
}
#bulkBar.show { display: flex; }
@keyframes slideUp {
    from { opacity:0; transform: translateY(20px); }
    to   { opacity:1; transform: translateY(0); }
}
#bulkBar .bulk-label {
    font-weight: 600;
    font-size: .95rem;
    margin-right: 4px;
    white-space: nowrap;
}
.btn-bulk {
    border: none; border-radius: 8px;
    padding: 8px 18px; font-size: .9rem;
    font-weight: 600; cursor: pointer;
    transition: opacity .15s, transform .1s;
}
.btn-bulk:hover { opacity:.88; transform:scale(1.04); }
.btn-bulk:active { transform:scale(.97); }
.btn-bulk.hadir   { background:#22c55e; color:#fff; }
.btn-bulk.sakit   { background:#06b6d4; color:#fff; }
.btn-bulk.izin    { background:#f59e0b; color:#fff; }
.btn-bulk.alpa    { background:#64748b; color:#fff; }
.btn-bulk.bolos   { background:#ef4444; color:#fff; }
.btn-bulk.cancel  { background:#475569; color:#fff; }
#bulkProgress {
    display:none; position:fixed; inset:0;
    background:rgba(0,0,0,.45); z-index:9999;
    align-items:center; justify-content:center;
}
#bulkProgress.show { display:flex; }
.progress-box {
    background:#fff; border-radius:14px;
    padding:32px 40px; text-align:center; min-width:280px;
    box-shadow:0 8px 40px rgba(0,0,0,.25);
}
.progress-box h3 { margin:0 0 12px; color:#1e293b; }
.progress-bar-wrap {
    background:#e2e8f0; border-radius:999px;
    height:12px; overflow:hidden; margin:16px 0 8px;
}
.progress-bar-fill {
    height:100%; background:#2563eb;
    border-radius:999px; transition: width .2s ease;
}
.progress-text { font-size:.9rem; color:#64748b; }
</style>

<div class="page-header d-flex align-center">
    <div>
        <div class="page-title"><i class="fas fa-user-times"></i> Siswa Belum Absen</div>
        <div class="page-subtitle">Hari ini, <?= format_tanggal($today) ?></div>
    </div>
    <div style="margin-left:auto;display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
        <span class="badge" style="background:#fef2f2;color:#991b1b;font-size:1.1rem;padding:10px 20px;border-radius:8px;">
            <?= $total ?> siswa belum absen
        </span>
        <?php if($total>0): ?>
        <a href="notif_belum_absen.php<?= $kelas?"?kelas=$kelas":''?>" class="btn btn-success" style="background:#22c55e;color:#fff;">
            <i class="fab fa-whatsapp"></i> Kirim Notif WA
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="filter-bar">
            <select name="kelas" class="form-select">
                <option value="">Semua Kelas</option>
                <?php foreach ($kelas_list as $k): ?>
                    <option value="<?= $k ?>" <?= $kelas==$k?'selected':'' ?>><?= $k ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="fas fa-list"></i> Daftar Belum Absen
        <div class="ms-auto search-box" style="width:200px">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Cari...">
        </div>
    </div>
    <div class="table-container">
        <table id="mainTable">
            <thead>
                <tr>
                    <th class="cb-row">
                        <input type="checkbox" id="checkAll" title="Pilih Semua">
                    </th>
                    <th>#</th><th>NIS</th><th>Nama</th><th>Kelas</th><th>Aksi Cepat</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($data->num_rows === 0): ?>
                <tr><td colspan="6" style="text-align:center;padding:60px;color:var(--success)">
                    <i class="fas fa-check-circle fa-3x" style="margin-bottom:16px"></i>
                    <br>Semua siswa sudah absen hari ini! 🎉
                </td></tr>
                <?php else: $no=0; while ($row=$data->fetch_assoc()): $no++; ?>
                <tr data-id="<?= $row['id'] ?>"
                    data-nis="<?= $row['nis'] ?>"
                    data-nama="<?= htmlspecialchars($row['nama'], ENT_QUOTES) ?>"
                    data-kelas="<?= $row['kelas'] ?>">
                    <td class="cb-row">
                        <input type="checkbox" class="row-cb" value="<?= $row['id'] ?>">
                    </td>
                    <td><?= $no ?></td>
                    <td><?= $row['nis'] ?></td>
                    <td><?= htmlspecialchars($row['nama']) ?></td>
                    <td><?= $row['kelas'] ?></td>
                    <td>
                        <?php foreach (['Hadir'=>'success','Sakit'=>'info','Izin'=>'warning','Alpa'=>'secondary','Bolos'=>'danger'] as $st=>$cls): ?>
                        <button onclick="absenCepat(<?= $row['id'] ?>, '<?= $row['nis'] ?>', '<?= addslashes($row['nama']) ?>', '<?= $row['kelas'] ?>', '<?= $st ?>')"
                            class="btn btn-sm btn-<?= $cls ?>" title="<?= $st ?>" style="margin:2px">
                            <?= $st ?>
                        </button>
                        <?php endforeach; ?>
                    </td>
                </tr>
                <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- BULK ACTION BAR -->
<div id="bulkBar">
    <span class="bulk-label">✅ <span id="selCount">0</span> siswa dipilih</span>
    <span style="flex:1"></span>
    <span style="font-size:.85rem;color:#94a3b8;margin-right:4px;">Absen massal sebagai:</span>
    <button class="btn-bulk hadir" onclick="absenMassal('Hadir')">✔ Hadir</button>
    <button class="btn-bulk sakit" onclick="absenMassal('Sakit')">🏥 Sakit</button>
    <button class="btn-bulk izin"  onclick="absenMassal('Izin')">📋 Izin</button>
    <button class="btn-bulk alpa"  onclick="absenMassal('Alpa')">❌ Alpa</button>
    <button class="btn-bulk bolos" onclick="absenMassal('Bolos')">🚫 Bolos</button>
    <button class="btn-bulk cancel" onclick="clearAll()">✕ Batal</button>
</div>

<!-- Progress overlay -->
<div id="bulkProgress">
    <div class="progress-box">
        <h3 id="progTitle">Memproses Absensi...</h3>
        <div class="progress-bar-wrap">
            <div class="progress-bar-fill" id="progFill" style="width:0%"></div>
        </div>
        <div class="progress-text" id="progText">0 / 0</div>
    </div>
</div>

<script>
const checkAll = document.getElementById('checkAll');
const bulkBar  = document.getElementById('bulkBar');
const selCount = document.getElementById('selCount');

function getChecked() {
    return [...document.querySelectorAll('.row-cb:checked')];
}

function updateBulkBar() {
    const n = getChecked().length;
    selCount.textContent = n;
    bulkBar.classList.toggle('show', n > 0);
    document.querySelectorAll('.row-cb').forEach(cb => {
        cb.closest('tr').classList.toggle('row-selected', cb.checked);
    });
    const all = [...document.querySelectorAll('.row-cb')].filter(cb => cb.closest('tr').style.display !== 'none');
    const checked = all.filter(cb => cb.checked);
    checkAll.indeterminate = checked.length > 0 && checked.length < all.length;
    checkAll.checked = checked.length > 0 && checked.length === all.length;
}

checkAll.addEventListener('change', () => {
    document.querySelectorAll('.row-cb').forEach(cb => {
        if (cb.closest('tr').style.display !== 'none') cb.checked = checkAll.checked;
    });
    updateBulkBar();
});

document.addEventListener('change', e => {
    if (e.target.classList.contains('row-cb')) updateBulkBar();
});

// klik baris untuk toggle centang
document.querySelectorAll('#mainTable tbody tr').forEach(tr => {
    tr.style.cursor = 'pointer';
    tr.addEventListener('click', e => {
        if (e.target.tagName === 'BUTTON' || e.target.tagName === 'INPUT') return;
        const cb = tr.querySelector('.row-cb');
        if (cb) { cb.checked = !cb.checked; updateBulkBar(); }
    });
});

function clearAll() {
    document.querySelectorAll('.row-cb').forEach(cb => cb.checked = false);
    checkAll.checked = false;
    updateBulkBar();
}

async function absenMassal(status) {
    const checked = getChecked();
    if (!checked.length) return;
    if (!confirm(`Absen ${status} untuk ${checked.length} siswa yang dipilih?`)) return;

    const overlay  = document.getElementById('bulkProgress');
    const progFill = document.getElementById('progFill');
    const progText = document.getElementById('progText');
    document.getElementById('progTitle').textContent = `Memproses Absensi (${status})...`;
    progFill.style.width = '0%';
    progText.textContent = `0 / ${checked.length}`;
    overlay.classList.add('show');

    let done = 0, errors = 0;
    for (const cb of checked) {
        const tr    = cb.closest('tr');
        const id    = tr.dataset.id;
        const nis   = tr.dataset.nis;
        const nama  = tr.dataset.nama;
        const kelas = tr.dataset.kelas;
        try {
            const resp = await fetch('ajax/absen_manual_cepat.php', {
                method: 'POST',
                headers: {'Content-Type':'application/x-www-form-urlencoded'},
                body: `siswa_id=${id}&nis=${nis}&nama=${encodeURIComponent(nama)}&kelas=${kelas}&status=${status}`
            });
            const d = await resp.json();
            if (!d.success) errors++;
        } catch(e) { errors++; }
        done++;
        progFill.style.width = Math.round(done / checked.length * 100) + '%';
        progText.textContent = `${done} / ${checked.length}`;
    }

    overlay.classList.remove('show');
    if (errors === 0) {
        showToast(`✅ ${done} siswa berhasil diabsen sebagai ${status}`, 'success');
    } else {
        showToast(`⚠️ ${done-errors} berhasil, ${errors} gagal`, 'error');
    }
    setTimeout(() => location.reload(), 900);
}

async function absenCepat(id, nis, nama, kelas, status) {
    if (!confirm(`Absen ${status} untuk ${nama}?`)) return;
    const resp = await fetch('ajax/absen_manual_cepat.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: `siswa_id=${id}&nis=${nis}&nama=${encodeURIComponent(nama)}&kelas=${kelas}&status=${status}`
    });
    const d = await resp.json();
    if (d.success) {
        showToast(`${nama} → ${status}`, 'success');
        setTimeout(() => location.reload(), 800);
    } else {
        showToast(d.message, 'error');
    }
}

document.getElementById('searchInput').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#mainTable tbody tr').forEach(tr => {
        const match = tr.textContent.toLowerCase().includes(q);
        tr.style.display = match ? '' : 'none';
        if (!match) { const cb = tr.querySelector('.row-cb'); if(cb) cb.checked = false; }
    });
    updateBulkBar();
});
</script>

<?php include 'includes/footer.php'; ?>
