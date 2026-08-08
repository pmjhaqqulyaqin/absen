<?php
// =============================================
// KONFIGURASI - Edit sesuai kebutuhan Anda
// XAMPP: DB_USER='root', DB_PASS='', BASE_URL='/absensi/'
// Hosting: sesuaikan DB_USER, DB_PASS, DB_NAME, BASE_URL
// =============================================
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'login276_absensi');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'Absen2026!');
define('DB_NAME', getenv('DB_NAME') ?: 'login276_absensi');
define('BASE_URL', '/');

date_default_timezone_set('Asia/Jakarta');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('<div style="background:#ff4444;color:white;padding:30px;font-family:Arial;text-align:center">
        <h2>❌ Koneksi Database Gagal</h2><p>'.$conn->connect_error.'</p>
        <p>1. Jalankan XAMPP<br>2. Import install.sql<br>3. Edit config.php</p>
    </div>');
}
$conn->set_charset("utf8mb4");

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/periode.php';

// =============================================
// HELPER FUNCTIONS
// =============================================
function cek_login() {
    if (!isset($_SESSION['admin_id'])) { header('Location: '.BASE_URL.'login.php'); exit; }
}
function cek_wali() {
    if (!isset($_SESSION['wali_id'])) { header('Location: '.BASE_URL.'portal_login.php?role=wali'); exit; }
}
function cek_siswa() {
    if (!isset($_SESSION['siswa_id'])) { header('Location: '.BASE_URL.'portal_login.php?role=siswa'); exit; }
}

function get_pengaturan() {
    global $conn;
    return $conn->query("SELECT * FROM pengaturan LIMIT 1")->fetch_assoc();
}

function get_status_badge($status) {
    $map = [
        'Hadir'     => ['badge-hadir',    '✅'],
        'Terlambat' => ['badge-terlambat','⏰'],
        'Alpa'      => ['badge-alpa',     '❌'],
        'Sakit'     => ['badge-sakit',    '🏥'],
        'Izin'      => ['badge-izin',     '📋'],
        'Bolos'     => ['badge-bolos',    '🚫'],
    ];
    $d = $map[$status] ?? ['',''];
    return '<span class="badge '.$d[0].'">'.$d[1].' '.$status.'</span>';
}

function sanitize($str) {
    global $conn;
    return $conn->real_escape_string(htmlspecialchars(strip_tags(trim($str))));
}

function format_tanggal($date) {
    if (!$date) return '-';
    $hari  = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    $bulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $ts = strtotime($date);
    return $hari[date('w',$ts)].', '.date('d',$ts).' '.$bulan[(int)date('n',$ts)].' '.date('Y',$ts);
}

function get_kelas_list() {
    global $conn;
    // Pastikan tabel referensi kelas ada (aman dipanggil dari halaman manapun,
    // tidak hanya dari kelas.php / kelola_kelas.php)
    static $sudah_cek_tabel = false;
    if (!$sudah_cek_tabel) {
        $conn->query("CREATE TABLE IF NOT EXISTS kelas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nama_kelas VARCHAR(30) NOT NULL UNIQUE,
            tingkat VARCHAR(10) DEFAULT '',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $sudah_cek_tabel = true;
    }

    // Gabungan: kelas yang sudah terdaftar di tabel `kelas` (termasuk kelas
    // baru yang belum punya siswa sama sekali) + kelas yang masih dipakai
    // siswa (jaga-jaga untuk data lama yang belum sempat didaftarkan).
    // Dengan cara ini, kelas TIDAK akan hilang dari daftar walau SELURUH
    // siswanya sudah dipindah ke kelas lain (akar masalah "kelas asal hilang").
    $r = $conn->query("
        SELECT nama_kelas AS kelas FROM kelas
        UNION
        SELECT DISTINCT kelas FROM siswa WHERE kelas <> ''
        ORDER BY kelas
    ");
    $k = [];
    while ($row = $r->fetch_assoc()) $k[] = $row['kelas'];
    return $k;
}

function get_tingkat_list() {
    global $conn;
    // Pastikan tabel tingkat ada (aman dipanggil dari halaman manapun)
    static $sudah_cek_tabel_tingkat = false;
    if (!$sudah_cek_tabel_tingkat) {
        $conn->query("CREATE TABLE IF NOT EXISTS tingkat (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nama_tingkat VARCHAR(20) NOT NULL UNIQUE,
            keterangan VARCHAR(100) DEFAULT '',
            urutan INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        // Patch: kalau tabel `tingkat` sempat dibuat lebih dulu oleh versi kode
        // lama (tanpa kolom keterangan/urutan), tambahkan kolomnya di sini
        // supaya INSERT/UPDATE tidak gagal diam-diam.
        $conn->query("ALTER TABLE tingkat ADD COLUMN IF NOT EXISTS keterangan VARCHAR(100) DEFAULT ''");
        $conn->query("ALTER TABLE tingkat ADD COLUMN IF NOT EXISTS urutan INT DEFAULT 0");
        $sudah_cek_tabel_tingkat = true;
    }
    $r = $conn->query("SELECT nama_tingkat FROM tingkat ORDER BY urutan ASC, id ASC");
    $t = [];
    while ($row = $r->fetch_assoc()) $t[] = $row['nama_tingkat'];
    return $t;
}

function get_stats_hari_ini() {
    global $conn;
    $today = date('Y-m-d');
    $pw = periode_where($conn);
    $total = $conn->query("SELECT COUNT(*) as c FROM siswa")->fetch_assoc()['c'];
    $stats = $conn->query("SELECT status, COUNT(*) as t FROM absensi WHERE tanggal='$today' $pw GROUP BY status")->fetch_all(MYSQLI_ASSOC);
    $r = ['total_siswa'=>$total,'Hadir'=>0,'Terlambat'=>0,'Alpa'=>0,'Sakit'=>0,'Izin'=>0,'Bolos'=>0,'sudah_absen'=>0];
    foreach ($stats as $s) { $r[$s['status']] = $s['t']; $r['sudah_absen'] += $s['t']; }
    $r['belum_absen'] = $total - $r['sudah_absen'];
    return $r;
}

function backup_rekap_bulanan($bulan, $tahun) {
    global $conn;
    $pw = periode_where($conn);
    list($pta, $psem) = periode_values($conn);
    $list = $conn->query("SELECT id,nis,nama,kelas FROM siswa");
    while ($s = $list->fetch_assoc()) {
        $sid = $s['id'];
        $rek = $conn->query("SELECT SUM(status='Hadir') h,SUM(status='Terlambat') t,SUM(status='Alpa') a,
            SUM(status='Sakit') sk,SUM(status='Izin') iz,SUM(status='Bolos') bo,COUNT(*) tot
            FROM absensi WHERE siswa_id=$sid AND MONTH(tanggal)=$bulan AND YEAR(tanggal)=$tahun $pw")->fetch_assoc();
        if ($rek['tot'] > 0) {
            $conn->query("INSERT INTO rekap_bulanan (siswa_id,nis,nama,kelas,bulan,tahun,hadir,terlambat,alpa,sakit,izin,bolos,total_hari,tahun_ajaran,semester)
                VALUES ($sid,'{$s['nis']}','{$s['nama']}','{$s['kelas']}',$bulan,$tahun,
                {$rek['h']},{$rek['t']},{$rek['a']},{$rek['sk']},{$rek['iz']},{$rek['bo']},{$rek['tot']},$pta,$psem)
                ON DUPLICATE KEY UPDATE hadir={$rek['h']},terlambat={$rek['t']},alpa={$rek['a']},
                sakit={$rek['sk']},izin={$rek['iz']},bolos={$rek['bo']},total_hari={$rek['tot']}");
        }
    }
}
?>
