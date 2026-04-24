<?php
require_once __DIR__.'/../includes/config.php';
cek_login();
header('Content-Type: application/json');

$siswa_id = (int)($_POST['siswa_id'] ?? 0);
if (!$siswa_id) { echo json_encode(['success'=>false,'msg'=>'ID tidak valid']); exit; }

$dir = __DIR__.'/../uploads/barcode/';
if (!is_dir($dir)) mkdir($dir, 0755, true);

if (isset($_POST['hapus'])) {
    $old = $conn->query("SELECT barcode_img FROM siswa WHERE id=$siswa_id")->fetch_assoc();
    if ($old && $old['barcode_img'] && file_exists($dir.$old['barcode_img'])) unlink($dir.$old['barcode_img']);
    $conn->query("UPDATE siswa SET barcode_img=NULL WHERE id=$siswa_id");
    echo json_encode(['success'=>true,'msg'=>'Gambar dihapus']); exit;
}

if (empty($_FILES['barcode_img']['name'])) { echo json_encode(['success'=>false,'msg'=>'File tidak ditemukan']); exit; }

$tmp = $_FILES['barcode_img']['tmp_name'];
$ext = strtolower(pathinfo($_FILES['barcode_img']['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) { echo json_encode(['success'=>false,'msg'=>'Format tidak didukung']); exit; }
if ($_FILES['barcode_img']['size'] > 5*1024*1024) { echo json_encode(['success'=>false,'msg'=>'Max 5MB']); exit; }

function kompresBarcode($tmp, $out) {
    $info = getimagesize($tmp); if (!$info) return false;
    $w=$info[0]; $h=$info[1];
    switch($info['mime']) {
        case 'image/jpeg': $src=imagecreatefromjpeg($tmp); break;
        case 'image/png':  $src=imagecreatefrompng($tmp);  break;
        case 'image/gif':  $src=imagecreatefromgif($tmp);  break;
        case 'image/webp': $src=imagecreatefromwebp($tmp); break;
        default: return false;
    }
    if (!$src) return false;
    $nw = min($w, 400); $nh = (int)round($h * $nw / $w);
    $dst = imagecreatetruecolor($nw, $nh);
    imagefill($dst, 0, 0, imagecolorallocate($dst,255,255,255));
    imagecopyresampled($dst,$src,0,0,0,0,$nw,$nh,$w,$h);
    $r = imagepng($dst, $out, 6);
    imagedestroy($src); imagedestroy($dst);
    return $r;
}

$old = $conn->query("SELECT barcode_img FROM siswa WHERE id=$siswa_id")->fetch_assoc();
if ($old && $old['barcode_img'] && file_exists($dir.$old['barcode_img'])) unlink($dir.$old['barcode_img']);

$filename = 'barcode_'.$siswa_id.'_'.time().'.png';
$out = $dir.$filename;

if (!function_exists('imagecreatetruecolor')) {
    move_uploaded_file($tmp, $out);
    $conn->query("UPDATE siswa SET barcode_img='$filename' WHERE id=$siswa_id");
    echo json_encode(['success'=>true,'msg'=>'Upload berhasil','file'=>BASE_URL.'uploads/barcode/'.$filename]); exit;
}

if (kompresBarcode($tmp, $out)) {
    $sebelum = $_FILES['barcode_img']['size'];
    $sesudah = filesize($out);
    $hemat   = $sebelum > 0 ? round((1-$sesudah/$sebelum)*100) : 0;
    $conn->query("UPDATE siswa SET barcode_img='$filename' WHERE id=$siswa_id");
    echo json_encode(['success'=>true,'msg'=>"✅ ".round($sebelum/1024)."KB → ".round($sesudah/1024)."KB (hemat {$hemat}%)",'file'=>BASE_URL.'uploads/barcode/'.$filename]);
} else {
    move_uploaded_file($tmp, $out);
    $conn->query("UPDATE siswa SET barcode_img='$filename' WHERE id=$siswa_id");
    echo json_encode(['success'=>true,'msg'=>'Upload berhasil','file'=>BASE_URL.'uploads/barcode/'.$filename]);
}
