<?php
/**
 * SYNC SEKALI-JALAN: betulkan absensi.kelas yang masih "nyangkut" di kelas lama
 * gara-gara siswa dipindah kelas SEBELUM fitur auto-sync di kelola_kelas.php aktif.
 *
 * Cara pakai:
 *  1. Upload file ini ke folder aplikasi (sejajar dengan kelola_kelas.php).
 *  2. Buka di browser: https://domain-anda/sync_kelas_absensi.php
 *  3. Klik "Jalankan Sinkronisasi".
 *  4. Setelah selesai dan sudah dicek hasilnya di Edit Absensi, file ini
 *     BOLEH DIHAPUS dari server (tidak dipakai lagi sehari-hari).
 *
 * Yang disentuh HANYA absensi tanggal HARI INI yang kelasnya beda dari
 * kelas siswa saat ini (siswa.kelas). Data absensi hari-hari sebelumnya
 * (histori) TIDAK diubah sama sekali.
 */
require_once 'includes/config.php';
cek_login();

$today = date('Y-m-d');
$msg   = '';
$preview = [];

// Cari mismatch: absensi hari ini yang kelasnya beda dari kelas siswa saat ini
$sqlPreview = "
    SELECT a.id absen_id, a.nis, a.nama, a.kelas AS kelas_di_absensi,
           s.kelas AS kelas_sekarang
    FROM absensi a
    JOIN siswa s ON s.id = a.siswa_id
    WHERE a.tanggal = '$today'
      AND a.kelas <> s.kelas" . periode_where($conn, 'a.') . "
    ORDER BY s.kelas, a.nama
";
$res = $conn->query($sqlPreview);
while ($row = $res->fetch_assoc()) $preview[] = $row;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['jalankan_sync'])) {
    $count = 0;
    foreach ($preview as $p) {
        $kelas_baru = $conn->real_escape_string($p['kelas_sekarang']);
        $absen_id   = (int)$p['absen_id'];
        $conn->query("UPDATE absensi SET kelas='$kelas_baru' WHERE id=$absen_id");
        $count++;
    }
    $msg = "success:$count data absensi berhasil disinkronkan ke kelas terbaru.";
    // Refresh preview (harusnya sudah kosong)
    $preview = [];
    $res = $conn->query($sqlPreview);
    while ($row = $res->fetch_assoc()) $preview[] = $row;
}

include 'includes/header.php';
if ($msg) { list($t,$tx)=explode(':',$msg,2); echo "<div class='alert alert-$t'><i class='fas fa-check-circle'></i> $tx</div>"; }
?>

<div class="page-header">
    <div class="page-title"><i class="fas fa-sync"></i> Sinkronisasi Kelas Absensi (Sekali Jalan)</div>
    <div class="page-subtitle">Membetulkan absensi hari ini yang masih tercatat di kelas lama akibat pindah kelas</div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <p style="font-size:.88rem;color:#475569">
            Tanggal yang diperiksa: <strong><?= date('d/m/Y', strtotime($today)) ?></strong> (hari ini).
            Hanya data absensi hari ini yang kelasnya berbeda dari kelas siswa saat ini yang akan disentuh.
            Data absensi hari-hari sebelumnya tidak diubah.
        </p>

        <?php if (empty($preview)): ?>
            <div class="alert alert-success" style="margin:0">
                <i class="fas fa-check-circle"></i> Tidak ada data yang perlu disinkronkan. Semua sudah cocok.
            </div>
        <?php else: ?>
            <div class="table-container" style="margin-bottom:16px">
                <table>
                    <thead><tr>
                        <th>NIS</th><th>Nama</th><th>Kelas di Absensi (lama)</th><th></th><th>Kelas Sekarang (benar)</th>
                    </tr></thead>
                    <tbody>
                        <?php foreach ($preview as $p): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($p['nis']) ?></code></td>
                            <td><?= htmlspecialchars($p['nama']) ?></td>
                            <td><span class="badge" style="background:#fef2f2;color:#991b1b"><?= htmlspecialchars($p['kelas_di_absensi']) ?></span></td>
                            <td><i class="fas fa-arrow-right" style="color:var(--text-muted)"></i></td>
                            <td><span class="badge" style="background:#f0fdf4;color:#15803d"><?= htmlspecialchars($p['kelas_sekarang']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <form method="POST" onsubmit="return confirm('Sinkronkan <?= count($preview) ?> data absensi ke kelas terbaru?');">
                <input type="hidden" name="jalankan_sync" value="1">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-sync"></i> Jalankan Sinkronisasi (<?= count($preview) ?> data)
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
