<?php
require_once 'includes/config.php';
cek_login();

// Pastikan kolom no_hp ada
$chk = $conn->query("SHOW COLUMNS FROM `wali` LIKE 'no_hp'");
if ($chk && $chk->num_rows === 0) {
    $conn->query("ALTER TABLE `wali` ADD COLUMN `no_hp` VARCHAR(20) DEFAULT ''");
}

$msg = '';

// SIMPAN no WA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_wa'])) {
    $ids    = $_POST['wali_id']   ?? [];
    $no_hps = $_POST['no_hp']     ?? [];
    foreach ($ids as $i => $wid) {
        $wid  = (int)$wid;
        $nohp = $conn->real_escape_string(preg_replace('/[^0-9+]/', '', $no_hps[$i] ?? ''));
        if ($wid > 0) {
            $conn->query("UPDATE wali SET no_hp='$nohp' WHERE id=$wid");
        }
    }
    $msg = 'success:No WA wali kelas berhasil disimpan';
}

// Ambil semua wali
$wali_list = $conn->query("SELECT w.id, w.nama, w.kelas_wali, w.no_hp,
    (SELECT COUNT(*) FROM wali_siswa ws WHERE ws.wali_id = w.id) as jumlah_siswa
    FROM wali w ORDER BY w.kelas_wali, w.nama");

include 'includes/header.php';
if ($msg) { list($t,$tx) = explode(':',$msg,2); echo "<div class='alert alert-$t'><i class='fas fa-check-circle'></i> $tx</div>"; }
?>

<div class="page-header d-flex align-center">
    <div>
        <div class="page-title"><i class="fab fa-whatsapp" style="color:#22c55e"></i> Kelola No WA Wali Kelas</div>
        <div class="page-subtitle">Nomor ini digunakan untuk notifikasi absensi di Portal Kepala Sekolah</div>
    </div>
</div>

<div class="card">
    <div class="card-header" style="display:flex;align-items:center;gap:10px">
        <i class="fab fa-whatsapp" style="color:#22c55e;font-size:1.1rem"></i>
        Daftar Wali Kelas &amp; No WhatsApp
        <span style="margin-left:auto;font-size:.78rem;color:var(--text-muted)">
            <i class="fas fa-info-circle"></i> Format: 08xxx atau 628xxx (tanpa tanda hubung/spasi)
        </span>
    </div>

    <?php if ($wali_list->num_rows === 0): ?>
    <div style="text-align:center;padding:40px;color:var(--text-muted)">
        <i class="fab fa-whatsapp" style="font-size:2.5rem;margin-bottom:10px;display:block;color:#22c55e;opacity:.4"></i>
        Belum ada data wali kelas. Tambahkan wali di menu <strong>Kelola Wali</strong> terlebih dahulu.
    </div>
    <?php else: ?>
    <form method="POST">
        <input type="hidden" name="save_wa" value="1">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nama Wali Kelas</th>
                        <th>Kelas yang Diampu</th>
                        <th>Jumlah Siswa</th>
                        <th>No HP / WhatsApp</th>
                        <th>Status WA</th>
                    </tr>
                </thead>
                <tbody>
                <?php $no=1; while ($w = $wali_list->fetch_assoc()): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td>
                        <input type="hidden" name="wali_id[]" value="<?= $w['id'] ?>">
                        <strong><?= htmlspecialchars($w['nama']) ?></strong>
                    </td>
                    <td>
                        <?php if ($w['kelas_wali']): ?>
                        <span style="background:#dbeafe;color:#1e40af;padding:2px 10px;border-radius:20px;font-size:.8rem;font-weight:600"><?= htmlspecialchars($w['kelas_wali']) ?></span>
                        <?php else: ?>
                        <span style="color:#94a3b8;font-style:italic;font-size:.8rem">— belum ada kelas —</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:center">
                        <span style="background:#dcfce7;color:#166534;padding:2px 10px;border-radius:20px;font-size:.8rem;font-weight:600"><?= $w['jumlah_siswa'] ?> siswa</span>
                    </td>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px">
                            <span style="color:#22c55e;font-size:1rem"><i class="fab fa-whatsapp"></i></span>
                            <input type="text" name="no_hp[]"
                                   value="<?= htmlspecialchars($w['no_hp'] ?? '') ?>"
                                   placeholder="Contoh: 08123456789"
                                   style="width:180px;padding:7px 10px;border:1px solid var(--border);border-radius:8px;font-size:.85rem;background:var(--card-bg);color:var(--text);outline:none;transition:.2s"
                                   onfocus="this.style.borderColor='#22c55e'"
                                   onblur="this.style.borderColor='var(--border)'"
                                   pattern="[0-9+]{10,15}"
                                   title="Masukkan nomor HP/WA (10-15 digit)">
                        </div>
                    </td>
                    <td style="text-align:center">
                        <?php
                        $no_hp = preg_replace('/[^0-9]/','', $w['no_hp'] ?? '');
                        if (strlen($no_hp) >= 10):
                        ?>
                        <span style="background:#dcfce7;color:#166534;padding:3px 10px;border-radius:20px;font-size:.78rem;font-weight:700">
                            <i class="fas fa-check-circle"></i> Sudah diisi
                        </span>
                        <?php else: ?>
                        <span style="background:#fef2f2;color:#991b1b;padding:3px 10px;border-radius:20px;font-size:.78rem;font-weight:700">
                            <i class="fas fa-times-circle"></i> Belum diisi
                        </span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <div style="padding:16px;display:flex;gap:10px;justify-content:flex-end;border-top:1px solid var(--border)">
            <a href="wali.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali ke Kelola Wali</a>
            <button type="submit" class="btn btn-success" style="background:linear-gradient(135deg,#16a34a,#15803d)">
                <i class="fas fa-save"></i> Simpan Semua No WA
            </button>
        </div>
    </form>
    <?php endif; ?>
</div>

<div class="card" style="margin-top:16px;background:rgba(34,197,94,.04);border-color:rgba(34,197,94,.2)">
    <div class="card-header" style="border-color:rgba(34,197,94,.2)">
        <i class="fas fa-lightbulb" style="color:#22c55e"></i> Cara Penggunaan Notifikasi WA
    </div>
    <div style="padding:16px;font-size:.85rem;color:var(--text-muted);line-height:1.8">
        <ol style="padding-left:20px;display:flex;flex-direction:column;gap:8px">
            <li>Isi nomor WhatsApp wali kelas di kolom <strong>No HP / WhatsApp</strong> di atas (format: 08xxx atau 628xxx)</li>
            <li>Klik <strong>Simpan Semua No WA</strong></li>
            <li>Kepala sekolah masuk ke <strong>Portal Kepala Sekolah</strong></li>
            <li>Di bagian <strong>Notifikasi WhatsApp</strong>, tombol kirim akan aktif untuk setiap kelas yang sudah ada nomor WA</li>
            <li>Kepala sekolah klik tombol hijau → WhatsApp terbuka dengan pesan otomatis berisi rekap absensi hari itu</li>
        </ol>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
