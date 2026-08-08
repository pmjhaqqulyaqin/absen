<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/sync_config.php';
cek_login();

header('Content-Type: application/json');

// Pastikan kolom ext_id ada di tabel siswa
$conn->query("ALTER TABLE siswa ADD COLUMN IF NOT EXISTS ext_id VARCHAR(36) DEFAULT NULL");
// Cek apakah index sudah ada sebelum menambahkan
$idx = $conn->query("SHOW INDEX FROM siswa WHERE Key_name = 'idx_siswa_ext_id'")->num_rows;
if ($idx === 0) {
    $conn->query("ALTER TABLE siswa ADD UNIQUE INDEX idx_siswa_ext_id (ext_id)");
}

// Pull siswa dari mandaapp
$response = sync_api_call('/students');

if (!$response['success']) {
    echo json_encode(['success' => false, 'message' => 'Gagal koneksi ke mandaapp: ' . ($response['message'] ?? 'Unknown error')]);
    exit;
}

$students = $response['data'];
$baru = 0;
$update = 0;
$skip = 0;
$nonaktif = 0;

// Kumpulkan semua ext_id dari mandaapp untuk deteksi siswa yang dihapus
$mandaapp_ext_ids = [];

foreach ($students as $s) {
    $ext_id = $conn->real_escape_string($s['id']);
    $nis = $conn->real_escape_string($s['nis'] ?? '');
    $nama = $conn->real_escape_string($s['fullName'] ?? '');
    $kelas = $conn->real_escape_string($s['className'] ?? '');
    
    if (empty($nis)) {
        $skip++;
        continue;
    }
    
    $mandaapp_ext_ids[] = $ext_id;
    
    // Cek apakah siswa sudah ada berdasarkan ext_id atau NIS
    $existing = $conn->query("SELECT id, nis, nama, kelas, ext_id, aktif FROM siswa WHERE ext_id = '$ext_id' OR nis = '$nis' LIMIT 1")->fetch_assoc();
    
    if ($existing) {
        $changed = false;
        $sets = [];
        
        if ($existing['ext_id'] !== $ext_id) {
            $sets[] = "ext_id = '$ext_id'";
            $changed = true;
        }
        if ($existing['nama'] !== $nama && !empty($nama)) {
            $sets[] = "nama = '$nama'";
            $changed = true;
        }
        if ($existing['kelas'] !== $kelas && !empty($kelas)) {
            $sets[] = "kelas = '$kelas'";
            $changed = true;
        }
        if ($existing['aktif'] != 1) {
            $sets[] = "aktif = 1";
            $changed = true;
        }
        
        if ($changed && count($sets) > 0) {
            $conn->query("UPDATE siswa SET " . implode(', ', $sets) . " WHERE id = {$existing['id']}");
            $update++;
        } else {
            $skip++;
        }
    } else {
        // Insert siswa baru
        $stmt = $conn->prepare("INSERT INTO siswa (nis, nama, kelas, aktif, ext_id) VALUES (?, ?, ?, 1, ?)");
        $stmt->bind_param("ssss", $nis, $nama, $kelas, $ext_id);
        $stmt->execute();
        $stmt->close();
        $baru++;
    }
}

// Nonaktifkan siswa yang tidak ada di mandaapp lagi (jika ada ext_id)
if (count($mandaapp_ext_ids) > 0) {
    $placeholders = "'" . implode("','", array_map([$conn, 'real_escape_string'], $mandaapp_ext_ids)) . "'";
    $result = $conn->query("UPDATE siswa SET aktif = 0 WHERE ext_id IS NOT NULL AND ext_id NOT IN ($placeholders) AND aktif = 1");
    $nonaktif = $conn->affected_rows;
}

// Sync kelas di tabel absensi juga (update kelas terbaru)
$conn->query("UPDATE absensi a 
    INNER JOIN siswa s ON a.siswa_id = s.id 
    SET a.kelas = s.kelas, a.nama = s.nama 
    WHERE a.kelas <> s.kelas AND a.tanggal = CURDATE()");

// Log sync
sync_log($conn, 'siswa', $baru, $update, $skip, $nonaktif, "Total dari mandaapp: " . count($students));

echo json_encode([
    'success' => true,
    'message' => "Sinkronisasi siswa selesai!",
    'total_mandaapp' => count($students),
    'baru' => $baru,
    'update' => $update,
    'skip' => $skip,
    'nonaktif' => $nonaktif,
]);
?>
