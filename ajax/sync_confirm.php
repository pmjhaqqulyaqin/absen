<?php
/**
 * ============================================================
 * SYNC CONFIRM — Simpan item yang dipilih user
 * ============================================================
 * Menerima POST JSON berisi daftar kelas, siswa, dan
 * ID siswa yang perlu dinonaktifkan.
 * ============================================================
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/sync_config.php';
cek_login();

header('Content-Type: application/json');

// Baca JSON body
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
    exit;
}

// Pastikan kolom ext_id ada
$conn->query("ALTER TABLE kelas ADD COLUMN IF NOT EXISTS ext_id VARCHAR(36) DEFAULT NULL");
$conn->query("ALTER TABLE siswa ADD COLUMN IF NOT EXISTS ext_id VARCHAR(36) DEFAULT NULL");

$selectedClasses = $input['classes'] ?? [];
$selectedStudents = $input['students'] ?? [];
$nonaktifIds = $input['nonaktif_ids'] ?? [];

$kelas_baru = 0;
$kelas_update = 0;
$siswa_baru = 0;
$siswa_update = 0;
$siswa_nonaktif = 0;
$errors = [];

// ═══════════════════════════════════════════════════════════════
// PROSES KELAS TERPILIH
// ═══════════════════════════════════════════════════════════════
foreach ($selectedClasses as $cls) {
    $ext_id = $conn->real_escape_string($cls['ext_id'] ?? '');
    $nama = $conn->real_escape_string($cls['name'] ?? '');
    
    if (empty($ext_id) || empty($nama)) continue;
    
    $existing = $conn->query(
        "SELECT id, nama_kelas, ext_id FROM kelas 
         WHERE ext_id = '$ext_id' OR nama_kelas = '$nama' LIMIT 1"
    )->fetch_assoc();
    
    if ($existing) {
        $conn->query("UPDATE kelas SET nama_kelas = '$nama', ext_id = '$ext_id' WHERE id = {$existing['id']}");
        $kelas_update++;
    } else {
        $conn->query("INSERT INTO kelas (nama_kelas, ext_id) VALUES ('$nama', '$ext_id')");
        $kelas_baru++;
    }
}

// ═══════════════════════════════════════════════════════════════
// PROSES SISWA TERPILIH
// ═══════════════════════════════════════════════════════════════
foreach ($selectedStudents as $s) {
    $ext_id = $conn->real_escape_string($s['ext_id'] ?? '');
    $nis = $conn->real_escape_string($s['nis'] ?? '');
    $nama = $conn->real_escape_string($s['name'] ?? '');
    $kelas = $conn->real_escape_string($s['class'] ?? '');
    
    if (empty($ext_id) || empty($nis)) continue;
    
    $existing = $conn->query(
        "SELECT id, nis, nama, kelas, ext_id, aktif FROM siswa 
         WHERE ext_id = '$ext_id' OR nis = '$nis' LIMIT 1"
    )->fetch_assoc();
    
    if ($existing) {
        $sets = [];
        if ($existing['ext_id'] !== $ext_id) $sets[] = "ext_id = '$ext_id'";
        if ($existing['nama'] !== $nama && !empty($nama)) $sets[] = "nama = '$nama'";
        if ($existing['kelas'] !== $kelas && !empty($kelas)) $sets[] = "kelas = '$kelas'";
        if ($existing['aktif'] != 1) $sets[] = "aktif = 1";
        
        if (count($sets) > 0) {
            $conn->query("UPDATE siswa SET " . implode(', ', $sets) . " WHERE id = {$existing['id']}");
            $siswa_update++;
        }
    } else {
        $stmt = $conn->prepare("INSERT INTO siswa (nis, nama, kelas, aktif, ext_id) VALUES (?, ?, ?, 1, ?)");
        $stmt->bind_param("ssss", $nis, $nama, $kelas, $ext_id);
        if ($stmt->execute()) {
            $siswa_baru++;
        } else {
            $errors[] = "Gagal insert: $nama ($nis) - " . $stmt->error;
        }
        $stmt->close();
    }
}

// ═══════════════════════════════════════════════════════════════
// NONAKTIFKAN SISWA TERPILIH
// ═══════════════════════════════════════════════════════════════
if (!empty($nonaktifIds)) {
    foreach ($nonaktifIds as $id) {
        $id = (int)$id;
        if ($id > 0) {
            $conn->query("UPDATE siswa SET aktif = 0 WHERE id = $id AND aktif = 1");
            if ($conn->affected_rows > 0) $siswa_nonaktif++;
        }
    }
}

// Update kelas di tabel absensi hari ini
$conn->query("UPDATE absensi a 
    INNER JOIN siswa s ON a.siswa_id = s.id 
    SET a.kelas = s.kelas, a.nama = s.nama 
    WHERE a.kelas <> s.kelas AND a.tanggal = CURDATE()");

// Simpan waktu sync & log
sync_set_last_sync($conn, 'kelas_siswa');

$detail = "User-selected sync";
$detail .= " | Kelas [+$kelas_baru ✏$kelas_update]";
$detail .= " | Siswa [+$siswa_baru ✏$siswa_update -$siswa_nonaktif]";
sync_log($conn, 'kelas_siswa', 
    $kelas_baru + $siswa_baru, 
    $kelas_update + $siswa_update, 
    0, 
    $siswa_nonaktif, 
    $detail
);

echo json_encode([
    'success' => true,
    'message' => 'Sinkronisasi selesai! Data terpilih berhasil disimpan.',
    'kelas_baru' => $kelas_baru,
    'kelas_update' => $kelas_update,
    'siswa_baru' => $siswa_baru,
    'siswa_update' => $siswa_update,
    'siswa_nonaktif' => $siswa_nonaktif,
    'errors' => $errors,
]);
?>
