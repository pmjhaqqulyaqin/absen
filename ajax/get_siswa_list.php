<?php
require_once '../includes/config.php';
cek_login();
header('Content-Type: application/json');

$kelas = sanitize($_GET['kelas'] ?? '');
if (!$kelas) { echo json_encode([]); exit; }

$result = $conn->query("SELECT id, nis, nama, kelas FROM siswa WHERE kelas='$kelas' ORDER BY nama");
$data = [];
while ($row = $result->fetch_assoc()) $data[] = $row;
echo json_encode($data);
?>
