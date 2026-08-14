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

// ═══════════════════════════════════════════════════════════════════
// LANGKAH 1: Ambil daftar kelas aktif yang sudah di-sync (dari jadwal KBM)
// Hanya kelas yang punya ext_id (sudah di-sync dari mandaapp) yang valid
// ═══════════════════════════════════════════════════════════════════
$kelas_aktif = [];
$kelas_ext_ids = [];
$res_kelas = $conn->query("SELECT nama_kelas, ext_id FROM kelas WHERE ext_id IS NOT NULL");
while ($row = $res_kelas->fetch_assoc()) {
    $kelas_aktif[] = $row['nama_kelas'];
    if ($row['ext_id']) {
        $kelas_ext_ids[] = $row['ext_id'];
    }
}

if (empty($kelas_aktif)) {
    echo json_encode([
        'success' => false, 
        'message' => 'Tidak ada kelas yang tersinkron. Silakan sinkronkan kelas terlebih dahulu!'
    ]);
    exit;
}

// ═══════════════════════════════════════════════════════════════════
// LANGKAH 2: Tarik siswa dari mandaapp
// Coba endpoint filtered dulu, fallback ke endpoint biasa
// ═══════════════════════════════════════════════════════════════════
$students = [];
$source = 'all';

// Coba endpoint filtered: /students/by-classes?classIds=...
if (!empty($kelas_ext_ids)) {
    $classIdsParam = implode(',', $kelas_ext_ids);
    $response = sync_api_call('/students/by-classes?classIds=' . urlencode($classIdsParam));
    
    if ($response['success']) {
        $students = $response['data'];
        $source = 'filtered';
    }
}

// Fallback ke endpoint biasa jika filtered gagal
if (empty($students) && $source === 'all') {
    $response = sync_api_call('/students');
    
    if (!$response['success']) {
        echo json_encode(['success' => false, 'message' => 'Gagal koneksi ke mandaapp: ' . ($response['message'] ?? 'Unknown error')]);
        exit;
    }
    
    $students = $response['data'];
}

$baru = 0;
$update = 0;
$skip = 0;
$skip_kelas = 0;
$nonaktif = 0;

// Kumpulkan semua ext_id dari siswa yang valid untuk deteksi siswa yang dihapus
$mandaapp_ext_ids = [];

// ═══════════════════════════════════════════════════════════════════
// LANGKAH 3: Proses setiap siswa — filter berdasarkan kelas aktif
// ═══════════════════════════════════════════════════════════════════
foreach ($students as $s) {
    $ext_id = $conn->real_escape_string($s['id']);
    $nis = $conn->real_escape_string($s['nis'] ?? '');
    $nama = $conn->real_escape_string($s['fullName'] ?? '');
    $kelas = $conn->real_escape_string($s['className'] ?? '');
    
    if (empty($nis)) {
        $skip++;
        continue;
    }
    
    // Filter: hanya proses siswa yang kelasnya termasuk kelas aktif (dari jadwal)
    if ($source === 'all' && !in_array($kelas, $kelas_aktif)) {
        $skip_kelas++;
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

// ═══════════════════════════════════════════════════════════════════
// LANGKAH 4: Nonaktifkan siswa yang tidak ada di mandaapp lagi
// Hanya untuk siswa yang ext_id-nya sudah ada (pernah di-sync)
// ═══════════════════════════════════════════════════════════════════
if (count($mandaapp_ext_ids) > 0) {
    $placeholders = "'" . implode("','", array_map([$conn, 'real_escape_string'], $mandaapp_ext_ids)) . "'";
    
    // Nonaktifkan siswa yang:
    // 1. Punya ext_id (pernah di-sync)
    // 2. ext_id-nya TIDAK ADA di daftar siswa mandaapp terbaru
    // 3. Kelasnya termasuk kelas aktif (jangan sentuh kelas lain)
    $kelas_in = "'" . implode("','", array_map([$conn, 'real_escape_string'], $kelas_aktif)) . "'";
    $result = $conn->query("UPDATE siswa SET aktif = 0 
        WHERE ext_id IS NOT NULL 
        AND ext_id NOT IN ($placeholders) 
        AND kelas IN ($kelas_in)
        AND aktif = 1");
    $nonaktif = $conn->affected_rows;
}

// Sync kelas di tabel absensi juga (update kelas terbaru)
$conn->query("UPDATE absensi a 
    INNER JOIN siswa s ON a.siswa_id = s.id 
    SET a.kelas = s.kelas, a.nama = s.nama 
    WHERE a.kelas <> s.kelas AND a.tanggal = CURDATE()");

// Log sync
$total_from_api = count($students);
$detail = "Total dari mandaapp: $total_from_api";
if ($source === 'filtered') {
    $detail .= " (filtered by scheduled classes)";
}
if ($skip_kelas > 0) {
    $detail .= ", Skip kelas non-jadwal: $skip_kelas";
}
$detail .= ", Kelas aktif: " . count($kelas_aktif);
sync_log($conn, 'siswa', $baru, $update, $skip, $nonaktif, $detail);

echo json_encode([
    'success' => true,
    'message' => "Sinkronisasi siswa selesai! (hanya dari " . count($kelas_aktif) . " kelas jadwal)",
    'total_mandaapp' => $total_from_api,
    'total_kelas_aktif' => count($kelas_aktif),
    'kelas_aktif' => $kelas_aktif,
    'source' => $source,
    'baru' => $baru,
    'update' => $update,
    'skip' => $skip,
    'skip_kelas' => $skip_kelas,
    'nonaktif' => $nonaktif,
]);
?>
