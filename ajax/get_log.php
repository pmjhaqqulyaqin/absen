<?php
require_once '../includes/config.php';
cek_login();
header('Content-Type: application/json');

$today = date('Y-m-d');
$result = $conn->query("SELECT * FROM absensi WHERE tanggal='$today' ORDER BY updated_at DESC LIMIT 20");
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        'nama'      => $row['nama'],
        'nis'       => $row['nis'],
        'kelas'     => $row['kelas'],
        'status'    => $row['status'],
        'jam_masuk' => $row['jam_masuk'],
    ];
}
echo json_encode($data);
?>
