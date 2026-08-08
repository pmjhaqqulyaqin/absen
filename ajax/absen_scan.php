<?php
require_once '../includes/config.php';
cek_login();
header('Content-Type: application/json');

$nis   = sanitize($_POST['nis'] ?? '');
$jenis = $_POST['jenis'] ?? 'masuk'; // 'masuk' atau 'pulang'
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

$pw = periode_where($conn);
$existing = $conn->query("SELECT * FROM absensi WHERE siswa_id={$siswa['id']} AND tanggal='$today' $pw LIMIT 1")->fetch_assoc();

if ($jenis === 'pulang') {
    // === ABSEN PULANG ===
    if (!$existing) {
        echo json_encode(['success'=>false,'message'=>"{$siswa['nama']} belum absen masuk hari ini"]);
        exit;
    }
    if (!empty($existing['jam_pulang'])) {
        echo json_encode(['success'=>false,'message'=>"{$siswa['nama']} sudah absen pulang ({$existing['jam_pulang']})"]);
        exit;
    }
    $conn->query("UPDATE absensi SET jam_pulang='$now' WHERE id={$existing['id']}");
    echo json_encode(['success'=>true,'status'=>'Pulang','nama'=>$siswa['nama'],
        'nis'=>$siswa['nis'],'kelas'=>$siswa['kelas'],'jam'=>date('H:i',strtotime($now))]);
    exit;
}

// === ABSEN MASUK (logika asli dipertahankan) ===
if ($existing) {
    // Jika sudah ada dan belum pulang, dan waktu sudah lewat jam pulang
    if ($now >= $jam_pulang && empty($existing['jam_pulang'])) {
        $conn->query("UPDATE absensi SET jam_pulang='$now' WHERE id={$existing['id']}");
        echo json_encode(['success'=>true,'status'=>'Pulang','nama'=>$siswa['nama'],
            'nis'=>$siswa['nis'],'kelas'=>$siswa['kelas'],'jam'=>date('H:i',strtotime($now))]);
    } else {
        echo json_encode(['success'=>false,'message'=>"{$siswa['nama']} sudah absen ({$existing['status']})"]);
    }
    exit;
}

$status = $now > $jam_terlambat ? 'Terlambat' : 'Hadir';
$sid=$siswa['id']; $nama=$siswa['nama']; $kls=$siswa['kelas'];
$periodeSkr = get_periode_aktif($conn);
$ta = $periodeSkr['tahun_ajaran']; $sem = $periodeSkr['semester'];
$stmt=$conn->prepare("INSERT INTO absensi (siswa_id,nis,nama,kelas,tanggal,jam_masuk,status,metode,tahun_ajaran,semester) VALUES (?,?,?,?,?,?,?,'QR',?,?)");
$stmt->bind_param("issssssss",$sid,$nis,$nama,$kls,$today,$now,$status,$ta,$sem);
$stmt->execute();

$jam_str = date('H:i', strtotime($now));
echo json_encode(['success'=>true,'status'=>$status,'nama'=>$siswa['nama'],
    'nis'=>$siswa['nis'],'kelas'=>$siswa['kelas'],'jam'=>$jam_str]);

// ── Kirim notifikasi WA ke ortu (non-blocking) ──
$wa_file = __DIR__.'/../wa_absen.php';
if (file_exists($wa_file)) {
    require_once $wa_file;
    wa_notif_hadir($siswa['id'], $siswa['nama'], $siswa['kelas'], $siswa['nis'], $status, $jam_str);
}
?>
