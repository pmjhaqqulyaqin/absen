<?php
/**
 * ============================================================
 * SINKRONISASI GABUNGAN: KELAS + SISWA
 * ============================================================
 * Memanggil endpoint /v1/classes-students dari MandaApp
 * Integration API dalam satu request, lalu memproses kelas
 * dan siswa sekaligus.
 * 
 * Parameter GET:
 *   mode = "full" | "incremental" (default: incremental jika ada last_sync)
 * ============================================================
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/sync_config.php';
cek_login();

header('Content-Type: application/json');

// Pastikan kolom ext_id ada di tabel kelas dan siswa
$conn->query("ALTER TABLE kelas ADD COLUMN IF NOT EXISTS ext_id VARCHAR(36) DEFAULT NULL");
$conn->query("ALTER TABLE siswa ADD COLUMN IF NOT EXISTS ext_id VARCHAR(36) DEFAULT NULL");

// Cek & tambah index jika belum ada
$idx_kelas = $conn->query("SHOW INDEX FROM kelas WHERE Key_name = 'idx_kelas_ext_id'")->num_rows;
if ($idx_kelas === 0) {
    $conn->query("ALTER TABLE kelas ADD UNIQUE INDEX idx_kelas_ext_id (ext_id)");
}
$idx_siswa = $conn->query("SHOW INDEX FROM siswa WHERE Key_name = 'idx_siswa_ext_id'")->num_rows;
if ($idx_siswa === 0) {
    $conn->query("ALTER TABLE siswa ADD UNIQUE INDEX idx_siswa_ext_id (ext_id)");
}

// ═══════════════════════════════════════════════════════════════
// LANGKAH 1: Tentukan mode sync
// ═══════════════════════════════════════════════════════════════
$mode = $_GET['mode'] ?? 'auto';
$lastSync = null;

if ($mode === 'full') {
    // Full sync: tarik semua data tanpa filter last_sync
    $lastSync = null;
} elseif ($mode === 'incremental') {
    // Incremental: paksa gunakan last_sync
    $lastSync = sync_get_last_sync($conn, 'kelas_siswa');
    if (!$lastSync) {
        // Belum pernah sync, fallback ke full
        $lastSync = null;
    }
} else {
    // Auto: gunakan last_sync jika ada
    $lastSync = sync_get_last_sync($conn, 'kelas_siswa');
}

// ═══════════════════════════════════════════════════════════════
// LANGKAH 2: Panggil MandaApp Integration API
// ═══════════════════════════════════════════════════════════════
$response = mandaapp_fetch('/v1/classes-students', $lastSync);

if (!$response || !isset($response['success']) || !$response['success']) {
    echo json_encode([
        'success' => false, 
        'message' => 'Gagal koneksi ke MandaApp: ' . ($response['message'] ?? 'Unknown error')
    ]);
    exit;
}

$classes = $response['data']['classes'] ?? [];
$students = $response['data']['students'] ?? [];

// ═══════════════════════════════════════════════════════════════
// LANGKAH 3: Proses data KELAS
// ═══════════════════════════════════════════════════════════════
$kelas_baru = 0;
$kelas_update = 0;
$kelas_skip = 0;
$kelas_names = [];

foreach ($classes as $cls) {
    $ext_id = $conn->real_escape_string($cls['id']);
    $nama = $conn->real_escape_string($cls['name']);
    
    $kelas_names[] = $cls['name'];
    
    // Cek apakah kelas sudah ada berdasarkan ext_id atau nama_kelas
    $existing = $conn->query(
        "SELECT id, nama_kelas, ext_id FROM kelas 
         WHERE ext_id = '$ext_id' OR nama_kelas = '$nama' LIMIT 1"
    )->fetch_assoc();
    
    if ($existing) {
        if ($existing['ext_id'] !== $ext_id || $existing['nama_kelas'] !== $nama) {
            $conn->query("UPDATE kelas SET nama_kelas = '$nama', ext_id = '$ext_id' WHERE id = {$existing['id']}");
            $kelas_update++;
        } else {
            $kelas_skip++;
        }
    } else {
        $conn->query("INSERT INTO kelas (nama_kelas, ext_id) VALUES ('$nama', '$ext_id')");
        $kelas_baru++;
    }
}

// ═══════════════════════════════════════════════════════════════
// LANGKAH 4: Proses data SISWA
// ═══════════════════════════════════════════════════════════════
$siswa_baru = 0;
$siswa_update = 0;
$siswa_skip = 0;
$siswa_nonaktif = 0;
$errors = [];

// Kumpulkan ext_id siswa valid dari MandaApp
$mandaapp_siswa_ext_ids = [];

foreach ($students as $s) {
    $ext_id = $conn->real_escape_string($s['id']);
    $nis = $conn->real_escape_string($s['nis'] ?? '');
    $nama = $conn->real_escape_string($s['fullName'] ?? '');
    $kelas = $conn->real_escape_string($s['className'] ?? '');
    
    if (empty($nis)) {
        $siswa_skip++;
        if (!empty($nama)) {
            $errors[] = "Skip: $nama (NIS kosong)";
        }
        continue;
    }
    
    $mandaapp_siswa_ext_ids[] = $ext_id;
    
    // Cek apakah siswa sudah ada berdasarkan ext_id atau NIS
    $existing = $conn->query(
        "SELECT id, nis, nama, kelas, ext_id, aktif FROM siswa 
         WHERE ext_id = '$ext_id' OR nis = '$nis' LIMIT 1"
    )->fetch_assoc();
    
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
            $siswa_update++;
        } else {
            $siswa_skip++;
        }
    } else {
        // Insert siswa baru
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
// LANGKAH 5: Nonaktifkan siswa yang tidak ada di MandaApp
// Hanya pada FULL SYNC (tanpa last_sync) untuk menghindari
// false positive pada incremental sync
// ═══════════════════════════════════════════════════════════════
if (!$lastSync && count($mandaapp_siswa_ext_ids) > 0) {
    $placeholders = "'" . implode("','", array_map([$conn, 'real_escape_string'], $mandaapp_siswa_ext_ids)) . "'";
    
    $conn->query("UPDATE siswa SET aktif = 0 
        WHERE ext_id IS NOT NULL 
        AND ext_id NOT IN ($placeholders) 
        AND aktif = 1");
    $siswa_nonaktif = $conn->affected_rows;
}

// Sync kelas di tabel absensi juga (update kelas terbaru untuk hari ini)
$conn->query("UPDATE absensi a 
    INNER JOIN siswa s ON a.siswa_id = s.id 
    SET a.kelas = s.kelas, a.nama = s.nama 
    WHERE a.kelas <> s.kelas AND a.tanggal = CURDATE()");

// ═══════════════════════════════════════════════════════════════
// LANGKAH 6: Simpan waktu sync & log
// ═══════════════════════════════════════════════════════════════
sync_set_last_sync($conn, 'kelas_siswa');

$detail = "Mode: " . ($lastSync ? "incremental (since $lastSync)" : "full sync");
$detail .= " | API: " . count($classes) . " kelas, " . count($students) . " siswa";
$detail .= " | Kelas [+$kelas_baru ✏$kelas_update =$kelas_skip]";
$detail .= " | Siswa [+$siswa_baru ✏$siswa_update =$siswa_skip -$siswa_nonaktif]";

sync_log($conn, 'kelas_siswa', 
    $kelas_baru + $siswa_baru, 
    $kelas_update + $siswa_update, 
    $kelas_skip + $siswa_skip, 
    $siswa_nonaktif, 
    $detail
);

// ═══════════════════════════════════════════════════════════════
// LANGKAH 7: Response
// ═══════════════════════════════════════════════════════════════
echo json_encode([
    'success' => true,
    'message' => 'Sinkronisasi kelas & siswa selesai!',
    'mode' => $lastSync ? 'incremental' : 'full',
    'last_sync_used' => $lastSync,
    // Statistik kelas
    'kelas_total_api' => count($classes),
    'kelas_baru' => $kelas_baru,
    'kelas_update' => $kelas_update,
    'kelas_skip' => $kelas_skip,
    'kelas_names' => $kelas_names,
    // Statistik siswa
    'siswa_total_api' => count($students),
    'siswa_baru' => $siswa_baru,
    'siswa_update' => $siswa_update,
    'siswa_skip' => $siswa_skip,
    'siswa_nonaktif' => $siswa_nonaktif,
    // Errors
    'errors' => $errors,
]);
?>
