<?php
/**
 * ============================================================
 * SINKRONISASI ABSENSI
 * ============================================================
 * Pull: Tarik data presensi dari MandaApp Integration API
 *       Endpoint: GET /v1/attendances?last_sync=YYYY-MM-DD
 * Push: Coming soon (Integration API belum mendukung POST)
 * ============================================================
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/sync_config.php';
cek_login();

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$tanggal = $_GET['tanggal'] ?? $_POST['tanggal'] ?? date('Y-m-d');

// ============================================================
// PULL: Tarik absensi dari MandaApp Integration API
// Endpoint: GET /v1/attendances?last_sync=YYYY-MM-DD
// ============================================================
if ($action === 'pull') {
    // Gunakan tanggal sebagai last_sync untuk filter
    $response = mandaapp_fetch('/v1/attendances', $tanggal);
    
    if (!$response || !isset($response['success']) || !$response['success']) {
        echo json_encode([
            'success' => false, 
            'message' => 'Gagal koneksi ke MandaApp: ' . ($response['message'] ?? 'Unknown error')
        ]);
        exit;
    }
    
    $records = $response['data'] ?? [];
    $baru = 0;
    $update = 0;
    $skip = 0;
    $errors = [];
    
    foreach ($records as $rec) {
        // Cari siswa berdasarkan ext_id (studentId dari MandaApp)
        $studentExtId = $conn->real_escape_string($rec['studentId'] ?? '');
        $recDate = $conn->real_escape_string($rec['date'] ?? $tanggal);
        
        // Coba cari siswa via ext_id terlebih dahulu
        $siswa = null;
        if (!empty($studentExtId)) {
            $siswa = $conn->query(
                "SELECT id, nis, nama, kelas FROM siswa WHERE ext_id = '$studentExtId' LIMIT 1"
            )->fetch_assoc();
        }
        
        // Fallback: cari via NIS jika ada
        if (!$siswa && !empty($rec['nis'])) {
            $nis = $conn->real_escape_string($rec['nis']);
            $siswa = $conn->query(
                "SELECT id, nis, nama, kelas FROM siswa WHERE nis = '$nis' LIMIT 1"
            )->fetch_assoc();
        }
        
        if (!$siswa) {
            $skip++;
            continue;
        }
        
        // Mapping status dari MandaApp ke format lokal
        $statusMap = [
            'Hadir' => 'Hadir',
            'Terlambat' => 'Terlambat',
            'Alpa' => 'Alpa',
            'Sakit' => 'Sakit',
            'Izin' => 'Izin',
            'Bolos' => 'Bolos',
        ];
        $status = $statusMap[$rec['status'] ?? 'Alpa'] ?? 'Alpa';
        $status = $conn->real_escape_string($status);
        
        $jam_masuk = !empty($rec['checkIn']) ? "'" . $conn->real_escape_string($rec['checkIn']) . "'" : 'NULL';
        $jam_pulang = !empty($rec['checkOut']) ? "'" . $conn->real_escape_string($rec['checkOut']) . "'" : 'NULL';
        $keterangan = $conn->real_escape_string($rec['note'] ?? '');
        $nis = $conn->real_escape_string($siswa['nis']);
        $nama = $conn->real_escape_string($siswa['nama']);
        $kelas = $conn->real_escape_string($siswa['kelas']);
        
        // Cek apakah record absensi sudah ada
        $existing = $conn->query(
            "SELECT id FROM absensi WHERE siswa_id = {$siswa['id']} AND tanggal = '$recDate' LIMIT 1"
        )->fetch_assoc();
        
        if ($existing) {
            $conn->query("UPDATE absensi SET 
                status = '$status', 
                jam_masuk = $jam_masuk, 
                jam_pulang = $jam_pulang, 
                keterangan = '$keterangan', 
                metode = 'Sistem' 
                WHERE id = {$existing['id']}");
            $update++;
        } else {
            $result = $conn->query("INSERT INTO absensi 
                (siswa_id, nis, nama, kelas, tanggal, jam_masuk, jam_pulang, status, keterangan, metode) 
                VALUES ({$siswa['id']}, '$nis', '$nama', '$kelas', '$recDate', $jam_masuk, $jam_pulang, '$status', '$keterangan', 'Sistem')");
            if ($result) {
                $baru++;
            } else {
                $errors[] = "Gagal insert absensi: $nama - " . $conn->error;
            }
        }
    }
    
    sync_log($conn, 'absensi_pull', $baru, $update, $skip, 0, 
        "Tanggal filter: $tanggal | Total dari MandaApp: " . count($records));
    
    echo json_encode([
        'success' => true,
        'message' => "Pull absensi (sejak $tanggal) selesai!",
        'total_mandaapp' => count($records),
        'baru' => $baru,
        'update' => $update,
        'skip' => $skip,
        'errors' => $errors,
    ]);
    exit;
}

// ============================================================
// PUSH: Kirim absensi ke MandaApp — COMING SOON
// Integration API belum mendukung POST/write
// ============================================================
if ($action === 'push') {
    echo json_encode([
        'success' => false, 
        'message' => 'Fitur push absensi ke MandaApp belum tersedia. Integration API saat ini hanya mendukung pembacaan data (read-only).'
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => "Parameter 'action' diperlukan (pull/push)"]);
?>
