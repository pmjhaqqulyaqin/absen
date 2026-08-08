<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/sync_config.php';
cek_login();

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$tanggal = $_GET['tanggal'] ?? $_POST['tanggal'] ?? date('Y-m-d');

// ============================================================
// PULL: Tarik absensi dari mandaapp → absen app
// ============================================================
if ($action === 'pull') {
    $response = sync_api_call('/attendance?date=' . urlencode($tanggal));
    
    if (!$response['success']) {
        echo json_encode(['success' => false, 'message' => 'Gagal koneksi: ' . ($response['message'] ?? '')]);
        exit;
    }
    
    $records = $response['data'];
    $baru = 0;
    $update = 0;
    $skip = 0;
    
    foreach ($records as $rec) {
        $nis = $conn->real_escape_string($rec['nis'] ?? '');
        if (empty($nis)) { $skip++; continue; }
        
        // Cari siswa berdasarkan NIS
        $siswa = $conn->query("SELECT id, nama, kelas FROM siswa WHERE nis = '$nis' LIMIT 1")->fetch_assoc();
        if (!$siswa) { $skip++; continue; }
        
        $status = $conn->real_escape_string($rec['status'] ?? 'Alpa');
        $jam_masuk = $rec['checkIn'] ? "'" . $conn->real_escape_string($rec['checkIn']) . "'" : 'NULL';
        $jam_pulang = $rec['checkOut'] ? "'" . $conn->real_escape_string($rec['checkOut']) . "'" : 'NULL';
        $keterangan = $conn->real_escape_string($rec['note'] ?? '');
        $nama = $conn->real_escape_string($siswa['nama']);
        $kelas = $conn->real_escape_string($siswa['kelas']);
        
        // Cek apakah sudah ada
        $existing = $conn->query("SELECT id FROM absensi WHERE siswa_id = {$siswa['id']} AND tanggal = '$tanggal' LIMIT 1")->fetch_assoc();
        
        if ($existing) {
            $conn->query("UPDATE absensi SET status = '$status', jam_masuk = $jam_masuk, jam_pulang = $jam_pulang, 
                keterangan = '$keterangan', metode = 'Sistem' WHERE id = {$existing['id']}");
            $update++;
        } else {
            $conn->query("INSERT INTO absensi (siswa_id, nis, nama, kelas, tanggal, jam_masuk, jam_pulang, status, keterangan, metode) 
                VALUES ({$siswa['id']}, '$nis', '$nama', '$kelas', '$tanggal', $jam_masuk, $jam_pulang, '$status', '$keterangan', 'Sistem')");
            $baru++;
        }
    }
    
    sync_log($conn, 'absensi_pull', $baru, $update, $skip, 0, "Tanggal: $tanggal, Total dari mandaapp: " . count($records));
    
    echo json_encode([
        'success' => true,
        'message' => "Pull absensi tanggal $tanggal selesai!",
        'total_mandaapp' => count($records),
        'baru' => $baru,
        'update' => $update,
        'skip' => $skip,
    ]);
    exit;
}

// ============================================================
// PUSH: Kirim absensi dari absen app → mandaapp
// ============================================================
if ($action === 'push') {
    // Ambil semua absensi hari ini dari MySQL
    $pw = periode_where($conn);
    $rows = $conn->query("SELECT a.*, s.ext_id as siswa_ext_id FROM absensi a 
        LEFT JOIN siswa s ON a.siswa_id = s.id 
        WHERE a.tanggal = '$tanggal' $pw")->fetch_all(MYSQLI_ASSOC);
    
    if (empty($rows)) {
        echo json_encode(['success' => true, 'message' => "Tidak ada data absensi tanggal $tanggal untuk dikirim.", 'total' => 0]);
        exit;
    }
    
    // Siapkan data untuk push
    $records = [];
    foreach ($rows as $row) {
        $records[] = [
            'nis' => $row['nis'],
            'date' => $row['tanggal'],
            'checkIn' => $row['jam_masuk'],
            'checkOut' => $row['jam_pulang'],
            'status' => $row['status'],
            'method' => 'sync_from_absen',
            'note' => $row['keterangan'],
        ];
    }
    
    $response = sync_api_call('/attendance', 'POST', ['records' => $records]);
    
    if (!$response['success']) {
        echo json_encode(['success' => false, 'message' => 'Gagal push ke mandaapp: ' . ($response['message'] ?? '')]);
        exit;
    }
    
    sync_log($conn, 'absensi_push', $response['inserted'] ?? 0, $response['updated'] ?? 0, $response['skipped'] ?? 0, 0, 
        "Tanggal: $tanggal, Total dikirim: " . count($records));
    
    echo json_encode([
        'success' => true,
        'message' => "Push absensi tanggal $tanggal ke mandaapp selesai!",
        'total_dikirim' => count($records),
        'inserted' => $response['inserted'] ?? 0,
        'updated' => $response['updated'] ?? 0,
        'skipped' => $response['skipped'] ?? 0,
        'errors' => $response['errors'] ?? [],
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => "Parameter 'action' diperlukan (pull/push)"]);
?>
