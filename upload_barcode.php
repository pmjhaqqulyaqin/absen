<?php
require_once '../includes/config.php';
cek_login();

header('Content-Type: application/json');

$siswa_id = (int)($_POST['siswa_id'] ?? 0);
if (!$siswa_id) { echo json_encode(['success'=>false,'msg'=>'ID tidak valid']); exit; }

$dir = __DIR__.'/../uploads/barcode/';
if (!is_dir($dir)) mkdir($dir, 0755, true);

// ── HAPUS gambar ──────────────────────────────────────────
if (isset($_POST['hapus'])) {
    $old = $conn->query("SELECT barcode_img FROM siswa WHERE id=$siswa_id")->fetch_assoc();
    if ($old && $old['barcode_img'] && file_exists($dir.$old['barcode_img'])) unlink($dir.$old['barcode_img']);
    $conn->query("UPDATE siswa SET barcode_img=NULL WHERE id=$siswa_id");
    echo json_encode(['success'=>true,'msg'=>'Gambar dihapus']); exit;
}

// ── UPLOAD + KOMPRES ──────────────────────────────────────
if (empty($_FILES['barcode_img']['name'])) {
    echo json_encode(['success'=>false,'msg'=>'File tidak ditemukan']); exit;
}

$tmp  = $_FILES['barcode_img']['tmp_name'];
$ext  = strtolower(pathinfo($_FILES['barcode_img']['name'], PATHINFO_EXTENSION));
$allowed = ['jpg','jpeg','png','gif','webp'];

if (!in_array($ext, $allowed)) {
    echo json_encode(['success'=>false,'msg'=>'Format tidak didukung (JPG/PNG/WEBP)']); exit;
}
if ($_FILES['barcode_img']['size'] > 5*1024*1024) {
    echo json_encode(['success'=>false,'msg'=>'File terlalu besar (max 5MB)']); exit;
}

// ── Fungsi kompres pakai GD ───────────────────────────────
function kompresGambar($tmp, $outputPath) {
    $maxLebar = 400; // Cukup untuk barcode/QR code

    $info = getimagesize($tmp);
    if (!$info) return false;

    $lebarAsli  = $info[0];
    $tinggiAsli = $info[1];
    $mime       = $info['mime'];

    // Load gambar sesuai tipe
    switch ($mime) {
        case 'image/jpeg': $src = imagecreatefromjpeg($tmp); break;
        case 'image/png':  $src = imagecreatefrompng($tmp);  break;
        case 'image/gif':  $src = imagecreatefromgif($tmp);  break;
        case 'image/webp': $src = imagecreatefromwebp($tmp); break;
        default: return false;
    }
    if (!$src) return false;

    // Hitung ukuran baru proporsional
    if ($lebarAsli > $maxLebar) {
        $rasio      = $maxLebar / $lebarAsli;
        $lebarBaru  = $maxLebar;
        $tinggiBaru = (int)round($tinggiAsli * $rasio);
    } else {
        $lebarBaru  = $lebarAsli;
        $tinggiBaru = $tinggiAsli;
    }

    // Buat kanvas putih (penting agar barcode tetap terbaca)
    $dst   = imagecreatetruecolor($lebarBaru, $tinggiBaru);
    $putih = imagecolorallocate($dst, 255, 255, 255);
    imagefill($dst, 0, 0, $putih);

    // Resize
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $lebarBaru, $tinggiBaru, $lebarAsli, $tinggiAsli);

    // Simpan PNG (lossless — cocok untuk barcode agar tidak blur)
    $result = imagepng($dst, $outputPath, 6);

    imagedestroy($src);
    imagedestroy($dst);
    return $result;
}

// ── Hapus file lama ───────────────────────────────────────
$old = $conn->query("SELECT barcode_img FROM siswa WHERE id=$siswa_id")->fetch_assoc();
if ($old && $old['barcode_img'] && file_exists($dir.$old['barcode_img'])) {
    unlink($dir.$old['barcode_img']);
}

// ── Proses upload ─────────────────────────────────────────
$filename   = 'barcode_'.$siswa_id.'_'.time().'.png';
$outputPath = $dir.$filename;

if (!function_exists('imagecreatetruecolor')) {
    // GD tidak tersedia → simpan langsung tanpa kompres
    if (move_uploaded_file($tmp, $outputPath)) {
        $conn->query("UPDATE siswa SET barcode_img='$filename' WHERE id=$siswa_id");
        echo json_encode(['success'=>true,'msg'=>'Upload berhasil','file'=>BASE_URL.'uploads/barcode/'.$filename]);
    } else {
        echo json_encode(['success'=>false,'msg'=>'Gagal menyimpan file']);
    }
    exit;
}

$ok = kompresGambar($tmp, $outputPath);

if ($ok) {
    $ukuranAsli = $_FILES['barcode_img']['size'];
    $ukuranBaru = filesize($outputPath);
    $hemat      = $ukuranAsli > 0 ? round((1 - $ukuranBaru/$ukuranAsli)*100) : 0;
    $msg        = "✅ Berhasil! ".round($ukuranAsli/1024)."KB → ".round($ukuranBaru/1024)."KB (hemat {$hemat}%)";

    $conn->query("UPDATE siswa SET barcode_img='$filename' WHERE id=$siswa_id");
    echo json_encode(['success'=>true,'msg'=>$msg,'file'=>BASE_URL.'uploads/barcode/'.$filename]);
} else {
    // Fallback simpan langsung
    if (move_uploaded_file($tmp, $outputPath)) {
        $conn->query("UPDATE siswa SET barcode_img='$filename' WHERE id=$siswa_id");
        echo json_encode(['success'=>true,'msg'=>'Upload berhasil','file'=>BASE_URL.'uploads/barcode/'.$filename]);
    } else {
        echo json_encode(['success'=>false,'msg'=>'Gagal simpan. Cek permission folder uploads/barcode/']);
    }
}
?>
