<?php
require_once __DIR__.'/../includes/config.php';
// NO cek_login() - ini endpoint publik untuk scan di beranda
header('Content-Type: application/json');

$nis   = sanitize($_POST['nis'] ?? '');
$jenis = $_POST['jenis'] ?? 'masuk';
if (!in_array($jenis, ['masuk','pulang'])) $jenis = 'masuk';

if (empty($nis)) { echo json_encode(['success'=>false,'message'=>'NIS kosong']); exit; }

$stmt = $conn->prepare("SELECT * FROM siswa WHERE nis = ? LIMIT 1");
$stmt->bind_param("s", $nis);
$stmt->execute();
$siswa = $stmt->get_result()->fetch_assoc();

if (!$siswa) { echo json_encode(['success'=>false,'message'=>"NIS '$nis' tidak ditemukan"]); exit; }

$pengaturan = get_pengaturan();
$today = date('Y-m-d');
$now   = date('H:i:s');
$jam_terlambat = $pengaturan['jam_terlambat'];
$jam_pulang    = $pengaturan['jam_pulang'];

$existing = $conn->query("SELECT * FROM absensi WHERE siswa_id={$siswa['id']} AND tanggal='$today' LIMIT 1")->fetch_assoc();

if ($jenis === 'pulang') {
    if (!$existing) { echo json_encode(['success'=>false,'message'=>"{$siswa['nama']} belum absen masuk hari ini"]); exit; }
    if (!empty($existing['jam_pulang'])) { echo json_encode(['success'=>false,'message'=>"{$siswa['nama']} sudah absen pulang ({$existing['jam_pulang']})"]); exit; }
    $conn->query("UPDATE absensi SET jam_pulang='$now' WHERE id={$existing['id']}");
    echo json_encode(['success'=>true,'status'=>'Pulang','nama'=>$siswa['nama'],'nis'=>$siswa['nis'],'kelas'=>$siswa['kelas'],'jam'=>date('H:i',strtotime($now)),'foto'=>$siswa['foto']??'']);
    exit;
}

if ($existing) {
    if ($now >= $jam_pulang && empty($existing['jam_pulang'])) {
        $conn->query("UPDATE absensi SET jam_pulang='$now' WHERE id={$existing['id']}");
        echo json_encode(['success'=>true,'status'=>'Pulang','nama'=>$siswa['nama'],'nis'=>$siswa['nis'],'kelas'=>$siswa['kelas'],'jam'=>date('H:i',strtotime($now)),'foto'=>$siswa['foto']??'']);
    } else {
        echo json_encode(['success'=>false,'message'=>"{$siswa['nama']} sudah absen ({$existing['status']})"]);
    }
    exit;
}

$status = $now > $jam_terlambat ? 'Terlambat' : 'Hadir';
$sid=$siswa['id']; $nama=$siswa['nama']; $kls=$siswa['kelas'];
$stmt=$conn->prepare("INSERT INTO absensi (siswa_id,nis,nama,kelas,tanggal,jam_masuk,status,metode) VALUES (?,?,?,?,?,?,?,'QR')");
$stmt->bind_param("issssss",$sid,$nis,$nama,$kls,$today,$now,$status);
$stmt->execute();

echo json_encode(['success'=>true,'status'=>$status,'nama'=>$siswa['nama'],'nis'=>$siswa['nis'],'kelas'=>$siswa['kelas'],'jam'=>date('H:i',strtotime($now)),'foto'=>$siswa['foto']??'']);
