<?php
// =============================================
// PERIODE (TAHUN AJARAN & SEMESTER) - HELPER
// File ini di-require dari config.php, jangan diakses langsung
// =============================================

function _periode_pastikan_tabel($conn) {
    // Buat tabel kalau belum ada (aman dipanggil berkali-kali)
    $conn->query("CREATE TABLE IF NOT EXISTS periode_ajaran (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tahun_ajaran VARCHAR(20) NOT NULL,
        semester VARCHAR(10) NOT NULL,
        tanggal_mulai DATE NULL,
        tanggal_selesai DATE NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_periode (tahun_ajaran, semester)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Untuk instalasi lama yang tabelnya sudah ada tanpa kolom tanggal
    $conn->query("ALTER TABLE periode_ajaran ADD COLUMN IF NOT EXISTS tanggal_mulai DATE NULL");
    $conn->query("ALTER TABLE periode_ajaran ADD COLUMN IF NOT EXISTS tanggal_selesai DATE NULL");

    $conn->query("CREATE TABLE IF NOT EXISTS periode_aktif (
        id INT PRIMARY KEY,
        periode_id INT NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Pastikan minimal ada 1 periode default: 2026/2027 Ganjil
    $cek = $conn->query("SELECT COUNT(*) c FROM periode_ajaran")->fetch_assoc();
    if ((int)$cek['c'] === 0) {
        $conn->query("INSERT INTO periode_ajaran (tahun_ajaran, semester) VALUES ('2026/2027','Ganjil')");
        $newId = $conn->insert_id;
        $conn->query("INSERT INTO periode_aktif (id, periode_id) VALUES (1, $newId)
                      ON DUPLICATE KEY UPDATE periode_id = $newId");
    }

    // Pastikan ada baris periode_aktif
    $cekAktif = $conn->query("SELECT COUNT(*) c FROM periode_aktif WHERE id=1")->fetch_assoc();
    if ((int)$cekAktif['c'] === 0) {
        $first = $conn->query("SELECT id FROM periode_ajaran ORDER BY id ASC LIMIT 1")->fetch_assoc();
        if ($first) {
            $conn->query("INSERT INTO periode_aktif (id, periode_id) VALUES (1, {$first['id']})");
        }
    }
}

/**
 * Ambil periode yang sedang aktif. Hasil di-cache di session supaya tidak query berulang.
 * Return: ['id'=>periode_id, 'tahun_ajaran'=>'2026/2027', 'semester'=>'Ganjil']
 */
function get_periode_aktif($conn) {
    if (isset($_SESSION['periode_aktif']) && isset($_SESSION['periode_aktif_ts'])
        && (time() - $_SESSION['periode_aktif_ts'] < 30)) {
        return $_SESSION['periode_aktif'];
    }
    _periode_pastikan_tabel($conn);
    $row = $conn->query("SELECT pa.periode_id id, pj.tahun_ajaran, pj.semester
                          FROM periode_aktif pa
                          JOIN periode_ajaran pj ON pj.id = pa.periode_id
                          WHERE pa.id = 1 LIMIT 1")->fetch_assoc();
    if (!$row) {
        $row = ['id' => 0, 'tahun_ajaran' => '2026/2027', 'semester' => 'Ganjil'];
    }
    $_SESSION['periode_aktif'] = $row;
    $_SESSION['periode_aktif_ts'] = time();
    return $row;
}

/** Daftar semua periode (tahun ajaran + semester), terbaru duluan */
function get_daftar_periode($conn) {
    _periode_pastikan_tabel($conn);
    $r = $conn->query("SELECT * FROM periode_ajaran ORDER BY tahun_ajaran DESC, semester ASC");
    $out = [];
    while ($row = $r->fetch_assoc()) $out[] = $row;
    return $out;
}

/**
 * Fragment SQL siap pakai untuk filter periode aktif, contoh hasil:
 *   AND tahun_ajaran='2026/2027' AND semester='Ganjil'
 * $alias contoh 'a.' kalau tabel di-alias di query (misal "FROM absensi a")
 */
function periode_where($conn, $alias = '') {
    $p = get_periode_aktif($conn);
    $ta  = $conn->real_escape_string($p['tahun_ajaran']);
    $sem = $conn->real_escape_string($p['semester']);
    return " AND {$alias}tahun_ajaran='{$ta}' AND {$alias}semester='{$sem}' ";
}

/** Nilai tahun_ajaran & semester aktif, siap dipakai di query INSERT (sudah di-escape, sudah pakai kutip) */
function periode_values($conn) {
    $p = get_periode_aktif($conn);
    $ta  = $conn->real_escape_string($p['tahun_ajaran']);
    $sem = $conn->real_escape_string($p['semester']);
    return ["'{$ta}'", "'{$sem}'"];
}

/** Format tanggal singkat Indonesia tanpa nama hari, contoh: 14 Juli 2026. Return '-' kalau kosong. */
function format_tgl_periode($date) {
    if (!$date || $date === '0000-00-00') return '-';
    $bulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $ts = strtotime($date);
    if ($ts === false) return '-';
    return date('d', $ts) . ' ' . $bulan[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}

/** Label lengkap periode aktif untuk ditampilkan, contoh: "Semester Ganjil TP. 2026/2027" */
function label_periode_aktif($conn) {
    $p = get_periode_aktif($conn);
    return 'Semester ' . $p['semester'] . ' TP. ' . $p['tahun_ajaran'];
}
