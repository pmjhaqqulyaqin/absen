<?php
require_once '../includes/config.php';
cek_login();
header('Content-Type: application/json');

$sid    = (int)($_POST['siswa_id'] ?? 0);
$status = trim($_POST['status'] ?? 'Alpa');

if (!$sid) { echo json_encode(['success'=>false,'message'=>'Data tidak valid']); exit; }

// Ambil data siswa langsung dari DB (lebih aman, tidak bergantung POST)
$s = $conn->query("SELECT * FROM siswa WHERE id=$sid LIMIT 1")->fetch_assoc();
if (!$s) { echo json_encode(['success'=>false,'message'=>'Siswa tidak ditemukan']); exit; }

// Validasi status
$valid_status = ['Hadir','Terlambat','Alpa','Sakit','Izin','Bolos'];
if (!in_array($status, $valid_status)) $status = 'Alpa';

$nis   = $conn->real_escape_string($s['nis']);
$nama  = $conn->real_escape_string($s['nama']);
$kelas = $conn->real_escape_string($s['kelas']);

$today = date('Y-m-d');
$now   = date('H:i:s');

// Cek jam terlambat
$peng = $conn->query("SELECT jam_terlambat FROM pengaturan LIMIT 1")->fetch_assoc();
if ($status === 'Hadir' && $peng && $now > $peng['jam_terlambat']) {
    $status = 'Terlambat';
}

$jam = ($status === 'Hadir' || $status === 'Terlambat') ? "'$now'" : "NULL";

$keterangan_default = [
    'Hadir' => '', 'Terlambat' => 'Terlambat',
    'Sakit' => 'Sakit', 'Izin' => 'Izin',
    'Alpa'  => 'Alpa', 'Bolos' => 'Bolos'
];
$ket = $conn->real_escape_string($keterangan_default[$status] ?? '');

$sql = "INSERT INTO absensi (siswa_id,nis,nama,kelas,tanggal,jam_masuk,status,keterangan,metode)
    VALUES ($sid,'$nis','$nama','$kelas','$today',$jam,'$status','$ket','Manual')
    ON DUPLICATE KEY UPDATE status='$status',jam_masuk=$jam,keterangan='$ket',metode='Manual'";

$result = $conn->query($sql);

if ($result) {
    echo json_encode(['success'=>true,'message'=>$s['nama'].' → '.$status,'status'=>$status]);
} else {
    echo json_encode(['success'=>false,'message'=>'Gagal simpan: '.$conn->error]);
}
?>
