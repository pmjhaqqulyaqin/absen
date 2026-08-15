<?php
/**
 * ============================================================
 * SYNC PREVIEW — Fetch & Compare, JANGAN SIMPAN
 * ============================================================
 * Panggil MandaApp API, bandingkan dengan data lokal,
 * return JSON preview dengan status per item.
 * 
 * Parameter GET:
 *   mode = "full" | "incremental" | "auto"
 * ============================================================
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/sync_config.php';
cek_login();

header('Content-Type: application/json');

// Pastikan kolom ext_id ada
$conn->query("ALTER TABLE kelas ADD COLUMN IF NOT EXISTS ext_id VARCHAR(36) DEFAULT NULL");
$conn->query("ALTER TABLE siswa ADD COLUMN IF NOT EXISTS ext_id VARCHAR(36) DEFAULT NULL");

// ═══════════════════════════════════════════════════════════════
// LANGKAH 1: Tentukan mode & panggil API
// ═══════════════════════════════════════════════════════════════
$mode = $_GET['mode'] ?? 'auto';
$lastSync = null;

if ($mode === 'incremental') {
    $lastSync = sync_get_last_sync($conn, 'kelas_siswa');
} elseif ($mode === 'auto') {
    $lastSync = sync_get_last_sync($conn, 'kelas_siswa');
}
// mode 'full' → lastSync tetap null

$response = mandaapp_fetch('/v1/classes-students', $lastSync);

if (!$response || !isset($response['success']) || !$response['success']) {
    echo json_encode([
        'success' => false,
        'message' => 'Gagal koneksi ke MandaApp: ' . ($response['message'] ?? 'Unknown error')
    ]);
    exit;
}

$apiClasses = $response['data']['classes'] ?? [];
$apiStudents = $response['data']['students'] ?? [];

// ═══════════════════════════════════════════════════════════════
// LANGKAH 2: Bandingkan KELAS dengan data lokal
// ═══════════════════════════════════════════════════════════════
$previewClasses = [];

foreach ($apiClasses as $cls) {
    $ext_id = $cls['id'];
    $nama = $cls['name'];
    $esc_ext = $conn->real_escape_string($ext_id);
    $esc_nama = $conn->real_escape_string($nama);
    
    $existing = $conn->query(
        "SELECT id, nama_kelas, ext_id FROM kelas 
         WHERE ext_id = '$esc_ext' OR nama_kelas = '$esc_nama' LIMIT 1"
    )->fetch_assoc();
    
    $status = 'baru';
    $localName = null;
    $localId = null;
    
    if ($existing) {
        $localName = $existing['nama_kelas'];
        $localId = $existing['id'];
        if ($existing['ext_id'] === $ext_id && $existing['nama_kelas'] === $nama) {
            $status = 'sama';
        } else {
            $status = 'update';
        }
    }
    
    $previewClasses[] = [
        'ext_id' => $ext_id,
        'name' => $nama,
        'status' => $status,
        'local_id' => $localId,
        'local_name' => $localName,
    ];
}

// ═══════════════════════════════════════════════════════════════
// LANGKAH 3: Bandingkan SISWA dengan data lokal
// ═══════════════════════════════════════════════════════════════
$previewStudents = [];
$apiExtIds = [];

foreach ($apiStudents as $s) {
    $ext_id = $s['id'];
    $nis = $s['nis'] ?? '';
    $nama = $s['fullName'] ?? '';
    $kelas = $s['className'] ?? '';
    
    if (empty($nis)) continue; // skip siswa tanpa NIS
    
    $apiExtIds[] = $ext_id;
    $esc_ext = $conn->real_escape_string($ext_id);
    $esc_nis = $conn->real_escape_string($nis);
    
    $existing = $conn->query(
        "SELECT id, nis, nama, kelas, ext_id, aktif FROM siswa 
         WHERE ext_id = '$esc_ext' OR nis = '$esc_nis' LIMIT 1"
    )->fetch_assoc();
    
    $status = 'baru';
    $localName = null;
    $localClass = null;
    $localId = null;
    $changes = [];
    
    if ($existing) {
        $localName = $existing['nama'];
        $localClass = $existing['kelas'];
        $localId = $existing['id'];
        
        $isChanged = false;
        if ($existing['ext_id'] !== $ext_id) { $isChanged = true; $changes[] = 'ext_id'; }
        if ($existing['nama'] !== $nama && !empty($nama)) { $isChanged = true; $changes[] = 'nama'; }
        if ($existing['kelas'] !== $kelas && !empty($kelas)) { $isChanged = true; $changes[] = 'kelas'; }
        if ($existing['aktif'] != 1) { $isChanged = true; $changes[] = 'reaktivasi'; }
        
        $status = $isChanged ? 'update' : 'sama';
    }
    
    $previewStudents[] = [
        'ext_id' => $ext_id,
        'nis' => $nis,
        'name' => $nama,
        'class' => $kelas,
        'status' => $status,
        'changes' => $changes,
        'local_id' => $localId,
        'local_name' => $localName,
        'local_class' => $localClass,
    ];
}

// ═══════════════════════════════════════════════════════════════
// LANGKAH 4: Deteksi siswa yang perlu dinonaktifkan
// (hanya pada full sync — siswa di lokal yang tidak ada di API)
// ═══════════════════════════════════════════════════════════════
$studentsNonaktif = [];

if (!$lastSync && count($apiExtIds) > 0) {
    $placeholders = "'" . implode("','", array_map([$conn, 'real_escape_string'], $apiExtIds)) . "'";
    $res = $conn->query(
        "SELECT id, nis, nama, kelas FROM siswa 
         WHERE ext_id IS NOT NULL 
         AND ext_id NOT IN ($placeholders) 
         AND aktif = 1"
    );
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $studentsNonaktif[] = [
                'id' => (int)$row['id'],
                'nis' => $row['nis'],
                'name' => $row['nama'],
                'class' => $row['kelas'],
            ];
        }
    }
}

// ═══════════════════════════════════════════════════════════════
// LANGKAH 5: Hitung ringkasan
// ═══════════════════════════════════════════════════════════════
$countKelas = ['baru' => 0, 'update' => 0, 'sama' => 0];
foreach ($previewClasses as $c) $countKelas[$c['status']]++;

$countSiswa = ['baru' => 0, 'update' => 0, 'sama' => 0];
foreach ($previewStudents as $s) $countSiswa[$s['status']]++;

echo json_encode([
    'success' => true,
    'mode' => $lastSync ? 'incremental' : 'full',
    'last_sync_used' => $lastSync,
    'summary' => [
        'kelas' => $countKelas,
        'siswa' => $countSiswa,
        'nonaktif' => count($studentsNonaktif),
    ],
    'classes' => $previewClasses,
    'students' => $previewStudents,
    'students_nonaktif' => $studentsNonaktif,
]);
?>
