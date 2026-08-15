<?php
// =============================================
// KONFIGURASI SINKRONISASI
// Koneksi ke MandaApp Integration API
// Ref: Panduan-Integrasi.md
// =============================================

// URL Integration API MandaApp
define('MANDAAPP_API_URL', 'https://mandualotim.sch.id/api/integrations');

// API Key (dari Dashboard MandaApp > Integrasi API)
// Set via environment variable MANDAAPP_API_KEY, atau edit langsung di sini setelah deploy
define('MANDAAPP_API_KEY', getenv('MANDAAPP_API_KEY') ?: 'GANTI_DENGAN_API_KEY_ANDA');

// ══════════════════════════════════════════════
// FUNGSI UTAMA — Integration API
// ══════════════════════════════════════════════

/**
 * Mengambil data dari MandaApp Integration API
 * 
 * @param string $endpoint  Endpoint API (contoh: "/v1/classes-students")
 * @param string|null $lastSync  Tanggal sinkronisasi terakhir (format: Y-m-d)
 *                               Jika null, semua data akan ditarik.
 * @return array  Data hasil response API
 */
function mandaapp_fetch($endpoint, $lastSync = null) {
    $url = MANDAAPP_API_URL . $endpoint;
    if ($lastSync) {
        $url .= '?last_sync=' . urlencode($lastSync);
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'x-api-key: ' . MANDAAPP_API_KEY,
        'Accept: application/json',
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // Error handling sesuai Panduan-Integrasi.md
    if ($curlError) {
        return ['success' => false, 'message' => 'Koneksi gagal: ' . $curlError];
    }
    if ($httpCode === 401) {
        return ['success' => false, 'message' => 'API Key tidak valid atau tidak ditemukan. Periksa konfigurasi x-api-key.'];
    }
    if ($httpCode === 403) {
        return ['success' => false, 'message' => 'API Key sudah dinonaktifkan oleh admin MandaApp.'];
    }
    if ($httpCode !== 200) {
        return ['success' => false, 'message' => 'Error HTTP ' . $httpCode . ': ' . substr($response, 0, 500)];
    }

    $result = json_decode($response, true);
    if (!$result) {
        return ['success' => false, 'message' => 'Invalid JSON response dari MandaApp'];
    }

    return $result;
}

/**
 * Legacy: Panggil API mandaapp (backward compatible)
 * Fungsi ini tetap ada agar file lama (sync_kelas.php, sync_siswa.php) tidak error.
 */
function sync_api_call($endpoint, $method = 'GET', $data = null) {
    // Redirect ke fungsi baru untuk endpoint yang dikenali
    $mapping = [
        '/ping' => '/v1/classes-students',
        '/classes/scheduled' => '/v1/classes-students',
        '/classes' => '/v1/classes-students',
        '/students' => '/v1/classes-students',
    ];
    
    if (isset($mapping[$endpoint])) {
        return mandaapp_fetch($mapping[$endpoint]);
    }
    
    // Untuk endpoint lain, gunakan fungsi baru langsung
    return mandaapp_fetch($endpoint);
}

// ══════════════════════════════════════════════
// FUNGSI TRACKING LAST SYNC
// ══════════════════════════════════════════════

/**
 * Pastikan tabel sync_settings ada
 */
function sync_ensure_settings_table($conn) {
    static $checked = false;
    if ($checked) return;
    
    $conn->query("CREATE TABLE IF NOT EXISTS sync_settings (
        tipe VARCHAR(50) PRIMARY KEY,
        last_sync DATETIME DEFAULT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    $checked = true;
}

/**
 * Ambil waktu sinkronisasi terakhir untuk tipe tertentu
 * 
 * @param mysqli $conn
 * @param string $tipe  Tipe sync: 'kelas_siswa', 'absensi', dll.
 * @return string|null  Tanggal format Y-m-d atau null jika belum pernah sync
 */
function sync_get_last_sync($conn, $tipe) {
    sync_ensure_settings_table($conn);
    
    $stmt = $conn->prepare("SELECT last_sync FROM sync_settings WHERE tipe = ?");
    $stmt->bind_param("s", $tipe);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    return $result ? $result['last_sync'] : null;
}

/**
 * Simpan waktu sinkronisasi terakhir
 * 
 * @param mysqli $conn
 * @param string $tipe  Tipe sync
 */
function sync_set_last_sync($conn, $tipe) {
    sync_ensure_settings_table($conn);
    
    $now = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("INSERT INTO sync_settings (tipe, last_sync) VALUES (?, ?) 
                            ON DUPLICATE KEY UPDATE last_sync = ?");
    $stmt->bind_param("sss", $tipe, $now, $now);
    $stmt->execute();
    $stmt->close();
}

// ══════════════════════════════════════════════
// FUNGSI LOG SINKRONISASI
// ══════════════════════════════════════════════

/**
 * Log sinkronisasi ke database
 */
function sync_log($conn, $tipe, $baru = 0, $update = 0, $skip = 0, $nonaktif = 0, $detail = '') {
    // Pastikan tabel sync_log ada
    $conn->query("CREATE TABLE IF NOT EXISTS sync_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tipe ENUM('kelas','siswa','kelas_siswa','absensi_push','absensi_pull') NOT NULL,
        total_baru INT DEFAULT 0,
        total_update INT DEFAULT 0,
        total_skip INT DEFAULT 0,
        total_nonaktif INT DEFAULT 0,
        detail TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Tambah enum value kelas_siswa jika belum ada
    $conn->query("ALTER TABLE sync_log MODIFY COLUMN tipe ENUM('kelas','siswa','kelas_siswa','absensi_push','absensi_pull') NOT NULL");
    
    $stmt = $conn->prepare("INSERT INTO sync_log (tipe, total_baru, total_update, total_skip, total_nonaktif, detail) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("siiiis", $tipe, $baru, $update, $skip, $nonaktif, $detail);
    $stmt->execute();
    $stmt->close();
}

/**
 * Helper: Cek koneksi ke MandaApp Integration API
 */
function sync_ping() {
    $result = mandaapp_fetch('/v1/classes-students');
    
    if ($result && isset($result['success']) && $result['success']) {
        return [
            'success' => true,
            'message' => 'Koneksi ke MandaApp Integration API berhasil!',
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }
    
    return [
        'success' => false,
        'message' => $result['message'] ?? 'Tidak bisa terhubung ke MandaApp',
    ];
}
?>
