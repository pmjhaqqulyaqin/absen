<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/sync_config.php';
cek_login();

header('Content-Type: application/json');

// Pastikan kolom ext_id ada
$conn->query("ALTER TABLE kelas ADD COLUMN IF NOT EXISTS ext_id VARCHAR(36) DEFAULT NULL");
$conn->query("ALTER TABLE kelas ADD UNIQUE INDEX IF NOT EXISTS idx_kelas_ext_id (ext_id)");

// Pull kelas HANYA dari jadwal KBM (bukan semua kelas)
// Endpoint /classes/scheduled hanya mengembalikan kelas yang ada di distribusi jam
$response = sync_api_call('/classes/scheduled');

if (!$response['success']) {
    // Fallback ke endpoint lama jika endpoint baru belum tersedia
    $response = sync_api_call('/classes');
    if (!$response['success']) {
        echo json_encode(['success' => false, 'message' => 'Gagal koneksi ke mandaapp: ' . ($response['message'] ?? 'Unknown error')]);
        exit;
    }
}

$classes = $response['data'];
$source = $response['source'] ?? 'all';
$baru = 0;
$update = 0;
$skip = 0;

// Kumpulkan ext_id kelas dari jadwal untuk nanti dipakai sync siswa
$synced_ext_ids = [];

foreach ($classes as $cls) {
    $ext_id = $conn->real_escape_string($cls['id']);
    $nama = $conn->real_escape_string($cls['name']);
    
    if (defined('KELAS_EXCLUDED') && in_array($nama, KELAS_EXCLUDED)) {
        $skip++;
        continue;
    }
    
    $synced_ext_ids[] = $ext_id;
    
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

// Hitung kelas lokal yang TIDAK ada di jadwal (untuk informasi)
$kelas_non_jadwal = 0;
if (count($synced_ext_ids) > 0) {
    $placeholders = "'" . implode("','", array_map([$conn, 'real_escape_string'], $synced_ext_ids)) . "'";
    $res = $conn->query("SELECT COUNT(*) c FROM kelas WHERE ext_id IS NOT NULL AND ext_id NOT IN ($placeholders)");
    $kelas_non_jadwal = $res ? $res->fetch_assoc()['c'] : 0;
}

// Log sync
$detail = "Total dari jadwal KBM: " . count($classes);
if ($source === 'scheduled') {
    $detail .= " (filtered by schedule)";
}
sync_log($conn, 'kelas', $baru, $update, $skip, 0, $detail);

echo json_encode([
    'success' => true,
    'message' => "Sinkronisasi kelas selesai! (hanya kelas dari jadwal KBM)",
    'total_mandaapp' => count($classes),
    'source' => $source,
    'baru' => $baru,
    'update' => $update,
    'skip' => $skip,
    'kelas_non_jadwal' => $kelas_non_jadwal,
]);
?>
