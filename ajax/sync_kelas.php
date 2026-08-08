<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/sync_config.php';
cek_login();

header('Content-Type: application/json');

// Pastikan kolom ext_id ada
$conn->query("ALTER TABLE kelas ADD COLUMN IF NOT EXISTS ext_id VARCHAR(36) DEFAULT NULL");
$conn->query("ALTER TABLE kelas ADD UNIQUE INDEX IF NOT EXISTS idx_kelas_ext_id (ext_id)");

// Pull kelas dari mandaapp
$response = sync_api_call('/classes');

if (!$response['success']) {
    echo json_encode(['success' => false, 'message' => 'Gagal koneksi ke mandaapp: ' . ($response['message'] ?? 'Unknown error')]);
    exit;
}

$classes = $response['data'];
$baru = 0;
$update = 0;
$skip = 0;

foreach ($classes as $cls) {
    $ext_id = $conn->real_escape_string($cls['id']);
    $nama = $conn->real_escape_string($cls['name']);
    
    // Cek apakah kelas sudah ada berdasarkan ext_id atau nama_kelas
    $existing = $conn->query("SELECT id, nama_kelas, ext_id FROM kelas WHERE ext_id = '$ext_id' OR nama_kelas = '$nama' LIMIT 1")->fetch_assoc();
    
    if ($existing) {
        if ($existing['ext_id'] !== $ext_id || $existing['nama_kelas'] !== $nama) {
            // Update
            $conn->query("UPDATE kelas SET nama_kelas = '$nama', ext_id = '$ext_id' WHERE id = {$existing['id']}");
            $update++;
        } else {
            $skip++;
        }
    } else {
        // Insert baru
        $conn->query("INSERT INTO kelas (nama_kelas, ext_id) VALUES ('$nama', '$ext_id')");
        $baru++;
    }
}

// Log sync
sync_log($conn, 'kelas', $baru, $update, $skip, 0, "Total dari mandaapp: " . count($classes));

echo json_encode([
    'success' => true,
    'message' => "Sinkronisasi kelas selesai!",
    'total_mandaapp' => count($classes),
    'baru' => $baru,
    'update' => $update,
    'skip' => $skip,
]);
?>
