<?php
require_once '../includes/config.php';
cek_login();
header('Content-Type: application/json');

$sid    = (int)($_POST['siswa_id'] ?? 0);
$nis    = sanitize($_POST['nis'] ?? '');
$nama   = sanitize($_POST['nama'] ?? '');
$kelas  = sanitize($_POST['kelas'] ?? '');
$status = sanitize($_POST['status'] ?? 'Alpa');

if (!$sid) { echo json_encode(['success'=>false,'message'=>'Data tidak valid']); exit; }

$today = date('Y-m-d');
$now   = date('H:i:s');
$jam   = ($status==='Hadir'||$status==='Terlambat') ? "'$now'" : "NULL";

list($pta, $psem) = periode_values($conn);
$conn->query("INSERT INTO absensi (siswa_id,nis,nama,kelas,tanggal,jam_masuk,status,metode,tahun_ajaran,semester)
    VALUES ($sid,'$nis','$nama','$kelas','$today',$jam,'$status','Manual',$pta,$psem)
    ON DUPLICATE KEY UPDATE status='$status',jam_masuk=$jam,metode='Manual'");

echo json_encode(['success'=>true,'message'=>"$nama → $status"]);
?>
