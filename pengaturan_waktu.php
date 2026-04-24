<?php
require_once 'includes/config.php';
cek_login();

$msg = '';
$pengaturan = get_pengaturan();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jam_masuk    = sanitize($_POST['jam_masuk']);
    $jam_terlambat = sanitize($_POST['jam_terlambat']);
    $jam_pulang   = sanitize($_POST['jam_pulang']);
    
    if ($jam_terlambat <= $jam_masuk) {
        $msg = 'danger:Batas terlambat harus lebih besar dari jam masuk';
    } elseif ($jam_pulang <= $jam_terlambat) {
        $msg = 'danger:Jam pulang harus lebih besar dari batas terlambat';
    } else {
        $conn->query("UPDATE pengaturan SET jam_masuk='$jam_masuk', jam_terlambat='$jam_terlambat', jam_pulang='$jam_pulang' WHERE id=1");
        $msg = 'success:Pengaturan waktu berhasil disimpan';
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
    <div class="page-title"><i class="fas fa-clock"></i> Pengaturan Waktu</div>
    <div class="page-subtitle">Konfigurasi jam masuk, terlambat, dan pulang</div>
</div>

<div class="card" style="max-width:600px">
    <div class="card-header"><i class="fas fa-clock"></i> Konfigurasi Waktu Absensi</div>
    <div class="card-body">
        <form method="POST">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                Pengaturan waktu ini digunakan oleh sistem untuk otomatis menentukan status 
                <strong>Hadir</strong> atau <strong>Terlambat</strong> saat scan barcode.
            </div>
            
            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-sign-in-alt" style="color:var(--success)"></i> Jam Masuk
                </label>
                <input type="time" name="jam_masuk" class="form-control" 
                    value="<?= $pengaturan['jam_masuk'] ?>" required>
                <small style="color:var(--text-muted)">Jam mulai absensi dapat dilakukan</small>
            </div>
            
            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-exclamation-clock" style="color:var(--warning)"></i> Batas Terlambat
                </label>
                <input type="time" name="jam_terlambat" class="form-control" 
                    value="<?= $pengaturan['jam_terlambat'] ?>" required>
                <small style="color:var(--text-muted)">Siswa yang scan setelah jam ini otomatis berstatus <strong>Terlambat</strong></small>
            </div>
            
            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-sign-out-alt" style="color:var(--primary)"></i> Jam Pulang
                </label>
                <input type="time" name="jam_pulang" class="form-control" 
                    value="<?= $pengaturan['jam_pulang'] ?>" required>
                <small style="color:var(--text-muted)">Scan setelah jam ini dicatat sebagai <strong>Absen Pulang</strong></small>
            </div>
            
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-save"></i> Simpan Pengaturan Waktu
            </button>
        </form>
    </div>
</div>

<!-- Visual Timeline -->
<div class="card mt-3" style="max-width:600px">
    <div class="card-header"><i class="fas fa-chart-gantt"></i> Visualisasi Waktu</div>
    <div class="card-body">
        <div style="position:relative;padding:20px 0">
            <?php
            $masuk    = date('H:i', strtotime($pengaturan['jam_masuk']));
            $terlambat = date('H:i', strtotime($pengaturan['jam_terlambat']));
            $pulang   = date('H:i', strtotime($pengaturan['jam_pulang']));
            $sekarang = date('H:i');
            ?>
            <div style="display:flex;align-items:center;margin-bottom:20px">
                <div style="flex:1;height:12px;background:linear-gradient(to right,#16a34a,#d97706,#dc2626,#1d4ed8);border-radius:6px"></div>
            </div>
            <div style="display:flex;justify-content:space-between">
                <div style="text-align:center">
                    <div style="background:#16a34a;color:white;padding:4px 12px;border-radius:20px;font-size:.8rem;font-weight:700">
                        ✅ <?= $masuk ?>
                    </div>
                    <div style="font-size:.75rem;margin-top:4px;color:var(--text-muted)">Jam Masuk</div>
                </div>
                <div style="text-align:center">
                    <div style="background:#d97706;color:white;padding:4px 12px;border-radius:20px;font-size:.8rem;font-weight:700">
                        ⏰ <?= $terlambat ?>
                    </div>
                    <div style="font-size:.75rem;margin-top:4px;color:var(--text-muted)">Batas Terlambat</div>
                </div>
                <div style="text-align:center">
                    <div style="background:#1d4ed8;color:white;padding:4px 12px;border-radius:20px;font-size:.8rem;font-weight:700">
                        🏠 <?= $pulang ?>
                    </div>
                    <div style="font-size:.75rem;margin-top:4px;color:var(--text-muted)">Jam Pulang</div>
                </div>
            </div>
            
            <div style="margin-top:20px;padding:12px;background:#f8fafc;border-radius:8px;font-size:.85rem">
                <strong>Logika Sistem:</strong><br>
                • Scan antara <code><?= $masuk ?></code> - <code><?= $terlambat ?></code> → Status: <strong style="color:#15803d">Hadir</strong><br>
                • Scan setelah <code><?= $terlambat ?></code> (hingga jam pulang) → Status: <strong style="color:#854d0e">Terlambat</strong><br>
                • Scan setelah <code><?= $pulang ?></code> → Dicatat sebagai <strong style="color:#1d4ed8">Pulang</strong>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
