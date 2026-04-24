<?php
// =============================================
// KONFIGURASI - Auto-detect Docker / Lokal
// =============================================
// Di Docker: host = 'absen-db' (nama service di docker-compose)
// Di Lokal:  host = 'localhost'
$is_docker = getenv('MYSQL_DATABASE') !== false;

define('DB_HOST', $is_docker ? 'absen-db' : 'localhost');
define('DB_USER', $is_docker ? getenv('MYSQL_USER') : 'mandalo1_presensi');
define('DB_PASS', $is_docker ? getenv('MYSQL_PASSWORD') : 'manoke2004');
define('DB_NAME', $is_docker ? getenv('MYSQL_DATABASE') : 'mandalo1_presensi');
define('BASE_URL', '/');

date_default_timezone_set('Asia/Makassar'); // WITA

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('<div style="background:#ff4444;color:white;padding:30px;font-family:Arial;text-align:center">
        <h2>❌ Koneksi Database Gagal</h2><p>'.$conn->connect_error.'</p>
        <p>Pastikan DB sudah diimport dan config sudah benar</p>
    </div>');
}
$conn->set_charset("utf8mb4");

if (session_status() === PHP_SESSION_NONE) session_start();

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
    $r = $conn->query("SELECT DISTINCT kelas FROM siswa ORDER BY kelas");
    $k = [];
    while ($row = $r->fetch_assoc()) $k[] = $row['kelas'];
    return $k;
}

function get_stats_hari_ini() {
    global $conn;
    $today = date('Y-m-d');
    $total = $conn->query("SELECT COUNT(*) as c FROM siswa")->fetch_assoc()['c'];
    $stats = $conn->query("SELECT status, COUNT(*) as t FROM absensi WHERE tanggal='$today' GROUP BY status")->fetch_all(MYSQLI_ASSOC);
    $r = ['total_siswa'=>$total,'Hadir'=>0,'Terlambat'=>0,'Alpa'=>0,'Sakit'=>0,'Izin'=>0,'Bolos'=>0,'sudah_absen'=>0];
    foreach ($stats as $s) { $r[$s['status']] = $s['t']; $r['sudah_absen'] += $s['t']; }
    $r['belum_absen'] = $total - $r['sudah_absen'];
    return $r;
}

function backup_rekap_bulanan($bulan, $tahun) {
    global $conn;
    $list = $conn->query("SELECT id,nis,nama,kelas FROM siswa");
    while ($s = $list->fetch_assoc()) {
        $sid = $s['id'];
        $rek = $conn->query("SELECT SUM(status='Hadir') h,SUM(status='Terlambat') t,SUM(status='Alpa') a,
            SUM(status='Sakit') sk,SUM(status='Izin') iz,SUM(status='Bolos') bo,COUNT(*) tot
            FROM absensi WHERE siswa_id=$sid AND MONTH(tanggal)=$bulan AND YEAR(tanggal)=$tahun")->fetch_assoc();
        if ($rek['tot'] > 0) {
            $conn->query("INSERT INTO rekap_bulanan (siswa_id,nis,nama,kelas,bulan,tahun,hadir,terlambat,alpa,sakit,izin,bolos,total_hari)
                VALUES ($sid,'{$s['nis']}','{$s['nama']}','{$s['kelas']}',$bulan,$tahun,
                {$rek['h']},{$rek['t']},{$rek['a']},{$rek['sk']},{$rek['iz']},{$rek['bo']},{$rek['tot']})
                ON DUPLICATE KEY UPDATE hadir={$rek['h']},terlambat={$rek['t']},alpa={$rek['a']},
                sakit={$rek['sk']},izin={$rek['iz']},bolos={$rek['bo']},total_hari={$rek['tot']}");
        }
    }
}
?>
