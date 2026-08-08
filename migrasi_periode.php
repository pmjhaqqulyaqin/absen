<?php
// =============================================
// MIGRASI FITUR TAHUN AJARAN & SEMESTER
// Jalankan file ini SEKALI lewat browser: https://loginku.xyz/migrasi_periode.php
// Aman dijalankan berkali-kali (tidak akan merusak data kalau diulang)
// SETELAH SUKSES, HAPUS FILE INI DARI SERVER.
// =============================================
require_once __DIR__ . '/includes/config.php';
cek_login(); // hanya admin yang boleh jalankan

header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Migrasi Periode</title>
<style>body{font-family:Arial;background:#0f172a;color:#e2e8f0;padding:30px;line-height:1.7}
.ok{color:#4ade80}.err{color:#f87171}.box{background:#1e293b;padding:20px;border-radius:10px;max-width:800px;margin:auto}
h2{color:#38bdf8}</style></head><body><div class='box'>";
echo "<h2>🔧 Migrasi Fitur Tahun Ajaran & Semester</h2>";

function kolomAda($conn, $tabel, $kolom) {
    $r = $conn->query("SHOW COLUMNS FROM `$tabel` LIKE '$kolom'");
    return $r && $r->num_rows > 0;
}
function tabelAda($conn, $tabel) {
    $r = $conn->query("SHOW TABLES LIKE '$tabel'");
    return $r && $r->num_rows > 0;
}

// 1. Buat tabel periode_ajaran & periode_aktif + periode default (fungsi ini juga dipanggil otomatis oleh get_periode_aktif, tapi kita panggil eksplisit di sini)
_periode_pastikan_tabel($conn);
$aktif = get_periode_aktif($conn);
echo "<p class='ok'>✔ Tabel periode_ajaran & periode_aktif siap. Periode aktif sekarang: <b>{$aktif['tahun_ajaran']} - {$aktif['semester']}</b></p>";

// 2. Tabel transaksi yang perlu kolom tahun_ajaran + semester
$tabelTransaksi = ['absensi', 'pelanggaran', 'catatan', 'catatan_bk', 'kunjungan_rumah', 'rekap_bulanan', 'buku_kejadian', 'point_perbaikan'];

list($ta, $sem) = periode_values($conn); // sudah dalam bentuk 'xxx' siap pakai di query

foreach ($tabelTransaksi as $t) {
    if (!tabelAda($conn, $t)) {
        echo "<p style='color:#94a3b8'>— Tabel <b>$t</b> tidak ditemukan di database, dilewati.</p>";
        continue;
    }
    $adaTa  = kolomAda($conn, $t, 'tahun_ajaran');
    $adaSem = kolomAda($conn, $t, 'semester');

    if (!$adaTa) {
        $ok = $conn->query("ALTER TABLE `$t` ADD COLUMN tahun_ajaran VARCHAR(20) NOT NULL DEFAULT ''");
        echo $ok ? "<p class='ok'>✔ Kolom tahun_ajaran ditambahkan ke <b>$t</b></p>"
                 : "<p class='err'>✘ Gagal tambah tahun_ajaran ke $t: {$conn->error}</p>";
    }
    if (!$adaSem) {
        $ok = $conn->query("ALTER TABLE `$t` ADD COLUMN semester VARCHAR(10) NOT NULL DEFAULT ''");
        echo $ok ? "<p class='ok'>✔ Kolom semester ditambahkan ke <b>$t</b></p>"
                 : "<p class='err'>✘ Gagal tambah semester ke $t: {$conn->error}</p>";
    }

    // Tandai data lama yang masih kosong sebagai periode aktif sekarang (2026/2027 Ganjil)
    $upd = $conn->query("UPDATE `$t` SET tahun_ajaran=$ta, semester=$sem WHERE tahun_ajaran='' OR tahun_ajaran IS NULL");
    $terpengaruh = $conn->affected_rows;
    if ($upd) {
        echo "<p class='ok'>✔ $terpengaruh baris data lama di <b>$t</b> ditandai sebagai {$aktif['tahun_ajaran']} - {$aktif['semester']}</p>";
    } else {
        echo "<p class='err'>✘ Gagal update data lama di $t: {$conn->error}</p>";
    }

    // Index biar filter periode cepat
    $idxName = "idx_periode";
    $cekIdx = $conn->query("SHOW INDEX FROM `$t` WHERE Key_name='$idxName'");
    if ($cekIdx && $cekIdx->num_rows === 0) {
        $conn->query("ALTER TABLE `$t` ADD INDEX $idxName (tahun_ajaran, semester)");
    }
}

echo "<hr style='border-color:#334155'><p class='ok' style='font-size:1.1rem'>🎉 Migrasi selesai. Sekarang HAPUS file migrasi_periode.php ini dari server (lewat File Manager cPanel) supaya tidak bisa diakses orang lain.</p>";
echo "</div></body></html>";
