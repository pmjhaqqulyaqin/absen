<?php
require_once 'includes/config.php';
cek_login();

$msg='';
$kelas_list = get_kelas_list();
$preview=[];
$imported=0;

if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (isset($_POST['confirm_import']) && !empty($_POST['preview_data'])) {
        $rows=json_decode($_POST['preview_data'],true);
        foreach ($rows as $r) {
            $nis=sanitize($r['nis']); $nama=sanitize($r['nama']); $kelas=sanitize($r['kelas']);
            if (!$nis||!$nama||!$kelas) continue;
            $chk=$conn->query("SELECT id FROM siswa WHERE nis='$nis'")->fetch_assoc();
            if ($chk) continue;
            $pw=password_hash($nis,PASSWORD_DEFAULT);
            $conn->query("INSERT INTO siswa (nis,nama,kelas,password) VALUES ('$nis','$nama','$kelas','$pw')");
            $imported++;
        }
        $msg="success:Berhasil import $imported siswa. Password default = NIS masing-masing.";
    }
    if (isset($_POST['parse_data']) && !empty($_POST['paste_data'])) {
        $lines=explode("\n",trim($_POST['paste_data']));
        foreach ($lines as $li=>$line) {
            $line=trim($line);
            if (!$line) continue;
            $cols=preg_split('/\t|,|;/',$line);
            if (count($cols)<2) continue;
            // Auto detect: cari kolom mana yg NIS (angka), nama (huruf), kelas
            $preview[]=array_map('trim',$cols);
        }
    }
}

include 'includes/header.php';
if ($msg){list($t,$tx)=explode(':',$msg,2);echo "<div class='alert alert-$t'><i class='fas fa-check-circle'></i> $tx</div>";}
?>

<div class="page-header">
    <div class="page-title"><i class="fas fa-file-excel"></i> Import dari Excel</div>
    <div class="page-subtitle">Copy dari Excel/Spreadsheet → Paste di sini → Import</div>
</div>

<div class="card mb-3">
    <div class="card-header"><i class="fas fa-paste" style="color:var(--primary)"></i> Cara Penggunaan</div>
    <div class="card-body">
        <div style="display:flex;gap:24px;flex-wrap:wrap">
            <div style="flex:1;min-width:200px">
                <h4 style="margin-bottom:8px">1️⃣ Format Excel yang dibutuhkan:</h4>
                <table style="border-collapse:collapse;font-size:.85rem">
                    <thead><tr>
                        <th style="border:1px solid #ddd;padding:6px 12px;background:#f0f0f0">NIS</th>
                        <th style="border:1px solid #ddd;padding:6px 12px;background:#f0f0f0">Nama</th>
                        <th style="border:1px solid #ddd;padding:6px 12px;background:#f0f0f0">Kelas</th>
                    </tr></thead>
                    <tbody>
                        <tr><td style="border:1px solid #ddd;padding:6px 12px">2024001</td><td style="border:1px solid #ddd;padding:6px 12px">Ahmad Fauzi</td><td style="border:1px solid #ddd;padding:6px 12px">X-A</td></tr>
                        <tr><td style="border:1px solid #ddd;padding:6px 12px">2024002</td><td style="border:1px solid #ddd;padding:6px 12px">Budi Santoso</td><td style="border:1px solid #ddd;padding:6px 12px">X-B</td></tr>
                    </tbody>
                </table>
            </div>
            <div style="flex:1;min-width:200px">
                <h4 style="margin-bottom:8px">2️⃣ Langkah-langkah:</h4>
                <ol style="padding-left:20px;font-size:.875rem;color:var(--text-muted)">
                    <li>Buka file Excel Anda</li>
                    <li>Seleksi data (tanpa header)</li>
                    <li>Tekan <kbd>Ctrl+C</kbd></li>
                    <li>Klik area teks di bawah</li>
                    <li>Tekan <kbd>Ctrl+V</kbd></li>
                    <li>Klik "Preview Data"</li>
                    <li>Cek hasilnya, lalu "Konfirmasi Import"</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<?php if (empty($preview) && $imported===0): ?>
<!-- PASTE AREA -->
<div class="card">
    <div class="card-header"><i class="fas fa-clipboard" style="color:var(--success)"></i> Paste Data Excel</div>
    <div class="card-body">
        <form method="POST">
            <div class="form-group">
                <label class="form-label">Paste data di sini (Ctrl+V dari Excel):</label>
                <textarea name="paste_data" class="form-control" rows="12"
                    placeholder="Paste data Excel di sini...&#10;Contoh:&#10;2024001	Ahmad Fauzi	X-A&#10;2024002	Budi Santoso	X-B"
                    style="font-family:monospace;font-size:.875rem"
                    id="pasteArea"></textarea>
                <small style="color:var(--text-muted)">Mendukung Tab-separated (Excel) dan Comma-separated (CSV)</small>
            </div>
            <div style="margin-bottom:12px;padding:12px;background:#eff6ff;border-radius:8px;font-size:.82rem">
                <i class="fas fa-info-circle" style="color:#2563eb"></i>
                <strong>Kelas tersedia:</strong>
                <?php if (empty($kelas_list)): ?>
                <span style="color:#f59e0b"> Belum ada kelas. <a href="kelas.php"><strong>Tambah kelas dulu di sini</strong></a></span>
                <?php else: ?>
                <?php foreach($kelas_list as $k): ?>
                <span style="background:#dbeafe;color:#1e40af;padding:1px 8px;border-radius:10px;margin:2px;display:inline-block"><?= htmlspecialchars($k) ?></span>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div style="display:flex;gap:10px">
                <button type="submit" name="parse_data" class="btn btn-primary btn-lg">
                    <i class="fas fa-eye"></i> Preview Data
                </button>
                <button type="button" onclick="document.getElementById('pasteArea').value=''" class="btn btn-outline">
                    <i class="fas fa-times"></i> Bersihkan
                </button>
            </div>
        </form>
    </div>
</div>

<?php elseif (!empty($preview)): ?>
<!-- PREVIEW DATA -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-table" style="color:var(--success)"></i>
        Preview Data (<?= count($preview) ?> baris)
        <span class="badge" style="background:#f0fdf4;color:#15803d;margin-left:8px">Cek kolom sebelum import</span>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            Pastikan kolom <strong>Kolom 1=NIS, Kolom 2=Nama, Kolom 3=Kelas</strong>.
            Jika urutan berbeda, atur ulang di Excel terlebih dahulu.
        </div>
        <form method="POST">
            <!-- Mapping header -->
            <div style="margin-bottom:16px;display:flex;gap:12px">
                <label style="font-weight:600">Kolom 1:</label>
                <select id="col0" class="form-select" style="width:auto">
                    <option value="0">NIS</option><option value="1">Nama</option><option value="2">Kelas</option>
                </select>
                <label style="font-weight:600">Kolom 2:</label>
                <select id="col1" class="form-select" style="width:auto">
                    <option value="0">NIS</option><option value="1" selected>Nama</option><option value="2">Kelas</option>
                </select>
                <label style="font-weight:600">Kolom 3:</label>
                <select id="col2" class="form-select" style="width:auto">
                    <option value="0">NIS</option><option value="1">Nama</option><option value="2" selected>Kelas</option>
                </select>
            </div>
            <div class="table-container" style="max-height:400px;overflow-y:auto">
                <table>
                    <thead>
                        <tr><th>#</th><th>NIS</th><th>Nama</th><th>Kelas</th><th>Status</th></tr>
                    </thead>
                    <tbody id="previewBody">
                    <?php foreach ($preview as $pi=>$cols):
                        $nis=$cols[0]??''; $nama=$cols[1]??''; $kelas=$cols[2]??'';
                        $exists=$conn->query("SELECT id FROM siswa WHERE nis='".sanitize($nis)."'")->fetch_assoc();
                    ?>
                    <tr>
                        <td><?= $pi+1 ?></td>
                        <td><code><?= htmlspecialchars($nis) ?></code></td>
                        <td><?= htmlspecialchars($nama) ?></td>
                        <td><?= htmlspecialchars($kelas) ?></td>
                        <td><?= $exists
                            ? '<span class="badge badge-terlambat">⚠️ NIS duplikat (dilewati)</span>'
                            : '<span class="badge badge-hadir">✅ Akan diimport</span>' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <input type="hidden" name="preview_data" value="<?= htmlspecialchars(json_encode(array_map(fn($c)=>['nis'=>$c[0]??'','nama'=>$c[1]??'','kelas'=>$c[2]??''],$preview))) ?>">
            <div style="margin-top:16px;display:flex;gap:10px">
                <button type="submit" name="confirm_import" class="btn btn-success btn-lg" onclick="return confirm('Konfirmasi import data ini?')">
                    <i class="fas fa-upload"></i> Konfirmasi Import
                </button>
                <a href="import_excel.php" class="btn btn-outline btn-lg"><i class="fas fa-redo"></i> Ulang</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
