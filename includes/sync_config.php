<?php
// =============================================
// KONFIGURASI SINKRONISASI
// Koneksi ke mandualotim.sch.id API
// =============================================

// URL internal API mandaapp (via Docker network di VPS yang sama)
define('MANDAAPP_API_URL', 'http://127.0.0.1:3001/api/sync');

// Token sinkronisasi (harus sama dengan SYNC_API_TOKEN di mandaapp .env)
define('SYNC_TOKEN', 'manda-absen-sync-2026-secure');

// Daftar kelas yang TIDAK AKTIF / tidak perlu disinkronkan
// Kelas-kelas ini masih ada di MandaApp tapi bukan bagian jadwal KBM aktif
define('KELAS_EXCLUDED', [
    'XI AGAMA',
    'XI BHS', 
    'XI IPA',
    'XII PAI',
]);

/**
 * Helper: Panggil API mandaapp
 */
function sync_api_call($endpoint, $method = 'GET', $data = null) {
    $url = MANDAAPP_API_URL . $endpoint;
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-Sync-Token: ' . SYNC_TOKEN,
        ],
    ]);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'message' => 'CURL Error: ' . $error];
    }
    
    $result = json_decode($response, true);
    if (!$result) {
        return ['success' => false, 'message' => 'Invalid JSON response (HTTP ' . $httpCode . ')'];
    }
    
    return $result;
}

/**
 * Helper: Log sinkronisasi ke database
 */
function sync_log($conn, $tipe, $baru = 0, $update = 0, $skip = 0, $nonaktif = 0, $detail = '') {
    // Pastikan tabel sync_log ada
    $conn->query("CREATE TABLE IF NOT EXISTS sync_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tipe ENUM('kelas','siswa','absensi_push','absensi_pull') NOT NULL,
        total_baru INT DEFAULT 0,
        total_update INT DEFAULT 0,
        total_skip INT DEFAULT 0,
        total_nonaktif INT DEFAULT 0,
        detail TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    $stmt = $conn->prepare("INSERT INTO sync_log (tipe, total_baru, total_update, total_skip, total_nonaktif, detail) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("siiiis", $tipe, $baru, $update, $skip, $nonaktif, $detail);
    $stmt->execute();
    $stmt->close();
}

/**
 * Helper: Cek koneksi ke sync API
 */
function sync_ping() {
    return sync_api_call('/ping');
}
?>
