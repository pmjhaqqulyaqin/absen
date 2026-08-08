<?php
require_once 'includes/config.php';
cek_login();

$kelas_list    = get_kelas_list();
$filter_kelas  = sanitize($_GET['kelas'] ?? '');
$filter_nis    = sanitize($_GET['nis'] ?? '');
$format        = sanitize($_GET['format'] ?? 'qr');

$where = '1=1';
if ($filter_kelas) $where .= " AND kelas='$filter_kelas'";
if ($filter_nis)   $where .= " AND nis='$filter_nis'";

$siswa_list = $conn->query("SELECT * FROM siswa WHERE $where ORDER BY kelas, nama");
$pengaturan = get_pengaturan();

include 'includes/header.php';
?>

<div class="page-header d-flex align-center">
    <div>
        <div class="page-title"><i class="fas fa-id-card"></i> Kartu QR Siswa</div>
        <div class="page-subtitle">Generate QR Code & Barcode untuk kartu siswa</div>
    </div>
    <button onclick="window.print()" class="btn btn-primary ms-auto no-print"><i class="fas fa-print"></i> Cetak</button>
</div>

<div class="card mb-3 no-print">
    <div class="card-body">
        <form method="GET" class="filter-bar">
            <select name="kelas" class="form-select">
                <option value="">Semua Kelas</option>
                <?php foreach ($kelas_list as $k): ?>
                    <option value="<?= $k ?>" <?= $filter_kelas==$k?'selected':'' ?>><?= $k ?></option>
                <?php endforeach; ?>
            </select>
            <select name="format" class="form-select" style="width:auto">
                <option value="qr" <?= $format=='qr'?'selected':'' ?>>QR Code</option>
                <option value="barcode" <?= $format=='barcode'?'selected':'' ?>>Barcode</option>
                <option value="custom" <?= $format=='custom'?'selected':'' ?>>Gambar Upload</option>
            </select>
            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Tampilkan</button>
        </form>
    </div>
</div>

<!-- Toast notifikasi -->
<div id="toast" style="display:none;position:fixed;bottom:24px;right:24px;background:#16a34a;color:white;padding:12px 20px;border-radius:10px;font-weight:600;z-index:9999;box-shadow:0 4px 12px rgba(0,0,0,.2)"></div>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px" id="cardGrid">
    <?php 
    // Re-query karena pointer sudah dipakai di bawah untuk json
    $siswa_list2 = $conn->query("SELECT * FROM siswa WHERE $where ORDER BY kelas, nama");
    while ($s = $siswa_list2->fetch_assoc()): 
        $barcode_custom = !empty($s['barcode_img']) && file_exists(__DIR__.'/uploads/barcode/'.$s['barcode_img']);
    ?>
    <div class="card" style="text-align:center;padding:16px;page-break-inside:avoid;position:relative" id="card-<?= $s['id'] ?>">

        <!-- Tombol Edit (no-print) -->
        <div class="no-print" style="position:absolute;top:8px;right:8px">
            <button onclick="toggleUpload(<?= $s['id'] ?>)" 
                title="Upload gambar barcode" 
                style="background:var(--primary);color:white;border:none;border-radius:6px;padding:4px 8px;cursor:pointer;font-size:.75rem">
                <i class="fas fa-upload"></i> Edit
            </button>
        </div>

        <!-- Area Upload (tersembunyi) -->
        <div id="upload-<?= $s['id'] ?>" style="display:none;background:#f8fafc;border:2px dashed var(--primary);border-radius:8px;padding:10px;margin-bottom:10px">
            <div style="font-size:.75rem;color:var(--text-muted);margin-bottom:6px">Upload gambar barcode/QR</div>
            <input type="file" id="file-<?= $s['id'] ?>" accept="image/*" 
                   style="font-size:.75rem;width:100%" 
                   onchange="uploadBarcode(<?= $s['id'] ?>)">
            <div id="upstatus-<?= $s['id'] ?>" style="font-size:.72rem;margin-top:4px;color:var(--text-muted)"></div>
            <?php if ($barcode_custom): ?>
            <button onclick="hapusBarcode(<?= $s['id'] ?>)" 
                style="margin-top:4px;background:#ef4444;color:white;border:none;border-radius:4px;padding:2px 8px;font-size:.72rem;cursor:pointer">
                <i class="fas fa-trash"></i> Hapus Gambar
            </button>
            <?php endif; ?>
        </div>

        <!-- Logo sekolah -->
        <div style="font-size:.7rem;font-weight:700;color:var(--primary);margin-bottom:8px;border-bottom:1px solid #e2e8f0;padding-bottom:8px">
            <?= htmlspecialchars($pengaturan['nama_sekolah']) ?>
        </div>

        <!-- Foto siswa -->
        <?php if (!empty($s['foto']) && file_exists(__DIR__.'/uploads/foto/'.$s['foto'])): ?>
            <img src="<?= BASE_URL ?>uploads/foto/<?= $s['foto'] ?>" style="width:60px;height:60px;border-radius:50%;object-fit:cover;border:2px solid var(--primary);margin:0 auto 8px">
        <?php else: ?>
            <div style="width:60px;height:60px;border-radius:50%;background:var(--primary);display:flex;align-items:center;justify-content:center;color:white;font-size:1.5rem;font-weight:700;margin:0 auto 8px">
                <?= strtoupper(substr($s['nama'],0,1)) ?>
            </div>
        <?php endif; ?>

        <div style="font-weight:700;font-size:.9rem;margin-bottom:2px"><?= htmlspecialchars($s['nama']) ?></div>
        <div style="font-size:.75rem;color:var(--text-muted);margin-bottom:12px"><?= $s['nis'] ?> | Kelas <?= $s['kelas'] ?></div>

        <!-- Container QR/Barcode/Custom -->
        <div id="code-<?= $s['id'] ?>" style="display:flex;justify-content:center;align-items:center;min-height:120px">
            <?php if ($format === 'custom'): ?>
                <?php if ($barcode_custom): ?>
                    <img src="<?= BASE_URL ?>uploads/barcode/<?= $s['barcode_img'] ?>?t=<?= time() ?>" 
                         style="max-width:160px;max-height:120px;object-fit:contain" 
                         id="customimg-<?= $s['id'] ?>">
                <?php else: ?>
                    <div id="customimg-<?= $s['id'] ?>" style="color:var(--text-muted);font-size:.75rem;text-align:center;padding:10px">
                        <i class="fas fa-image" style="font-size:2rem;opacity:.3;display:block;margin-bottom:6px"></i>
                        Belum ada gambar<br>Klik <b>Edit</b> untuk upload
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div style="font-size:.7rem;letter-spacing:2px;font-weight:600;margin-top:6px;color:var(--text-muted)"><?= $s['nis'] ?></div>
    </div>
    <?php endwhile; ?>
</div>

<!-- Libraries -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.11.5/JsBarcode.all.min.js"></script>

<script>
const format = '<?= $format ?>';
<?php if ($format !== 'custom'): ?>
const data = <?php
    $arr=[];
    $siswa_json = $conn->query("SELECT id,nis FROM siswa WHERE $where ORDER BY kelas,nama");
    while($r=$siswa_json->fetch_assoc()) $arr[]=$r;
    echo json_encode($arr);
?>;

data.forEach(s => {
    const el = document.getElementById('code-' + s.id);
    if (!el) return;
    if (format === 'qr') {
        new QRCode(el, { text: s.nis, width: 120, height: 120, colorDark:'#000', colorLight:'#fff', correctLevel: QRCode.CorrectLevel.M });
    } else {
        const svg = document.createElementNS('http://www.w3.org/2000/svg','svg');
        el.appendChild(svg);
        try { JsBarcode(svg, s.nis, { format:'CODE128', width:2, height:60, displayValue:false, margin:0 }); }
        catch(e) { el.textContent='Error'; }
    }
});
<?php endif; ?>

// Toggle panel upload
function toggleUpload(id) {
    const el = document.getElementById('upload-' + id);
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}

// Upload gambar barcode
function uploadBarcode(id) {
    const fileInput = document.getElementById('file-' + id);
    const status = document.getElementById('upstatus-' + id);
    if (!fileInput.files[0]) return;

    status.textContent = '⏳ Mengupload...';
    status.style.color = '#f59e0b';

    const fd = new FormData();
    fd.append('siswa_id', id);
    fd.append('barcode_img', fileInput.files[0]);

    fetch('ajax/upload_barcode.php', { method:'POST', body:fd })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            status.textContent = '✅ Berhasil!';
            status.style.color = '#16a34a';
            // Update tampilan gambar
            const imgEl = document.getElementById('customimg-' + id);
            if (imgEl) {
                imgEl.outerHTML = `<img src="${data.file}" style="max-width:160px;max-height:120px;object-fit:contain" id="customimg-${id}">`;
            }
            showToast('Gambar berhasil diupload!');
            // Refresh halaman setelah 1.5s
            setTimeout(() => location.reload(), 1500);
        } else {
            status.textContent = '❌ ' + data.msg;
            status.style.color = '#ef4444';
        }
    })
    .catch(() => { status.textContent = '❌ Error koneksi'; status.style.color='#ef4444'; });
}

// Hapus gambar barcode
function hapusBarcode(id) {
    if (!confirm('Hapus gambar barcode siswa ini?')) return;
    fetch('ajax/upload_barcode.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'siswa_id='+id+'&hapus=1'
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) { showToast('Gambar dihapus'); setTimeout(() => location.reload(), 1000); }
    });
}

function showToast(msg) {
    const t = document.getElementById('toast');
    t.textContent = msg; t.style.display = 'block';
    setTimeout(() => t.style.display = 'none', 2500);
}
</script>

<style>
@media print {
    .no-print, .sidebar, .top-bar, .btn, header { display:none !important; }
    .main-content { margin-left:0 !important; }
    .content-wrapper { padding:0 !important; }
    #cardGrid { grid-template-columns: repeat(4, 1fr) !important; }
    .card { border:1px solid #ddd !important; break-inside:avoid; }
}
</style>

<?php include 'includes/footer.php'; ?>
