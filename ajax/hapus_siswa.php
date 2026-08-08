<?php
require_once '../includes/config.php';
cek_login();
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$ids   = array_filter(array_map('intval', $input['ids'] ?? []), fn($id) => $id > 0);

if (empty($ids)) {
    echo json_encode(['success' => false, 'message' => 'Tidak ada siswa yang dipilih']);
    exit;
}

$placeholders = implode(',', $ids); // Sudah diintval, aman
$count = 0;

// Hapus data terkait (sama seperti logika delete di siswa.php asli)
foreach ($ids as $id) {
    $s = $conn->query("SELECT foto FROM siswa WHERE id=$id")->fetch_assoc();
    if ($s && $s['foto'] && file_exists('../uploads/foto/'.$s['foto'])) {
        unlink('../uploads/foto/'.$s['foto']);
    }
    $conn->query("DELETE FROM absensi WHERE siswa_id=$id");
    $conn->query("DELETE FROM wali_siswa WHERE siswa_id=$id");
    $conn->query("DELETE FROM catatan WHERE siswa_id=$id");
    if ($conn->query("DELETE FROM siswa WHERE id=$id")) $count++;
}

echo json_encode([
    'success' => $count > 0,
    'message' => $count > 0 ? "Berhasil hapus $count siswa beserta data absensinya" : 'Gagal menghapus'
]);
?>
