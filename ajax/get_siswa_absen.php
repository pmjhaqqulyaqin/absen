<?php
session_start();
if (!isset($_SESSION['admin'])) {
    echo json_encode([]);
    exit;
}

include '../includes/config.php';
header('Content-Type: application/json');

$kelas   = $_GET['kelas'] ?? '';
$tanggal = $_GET['tanggal'] ?? date('Y-m-d');

$sql = "
    SELECT s.id, s.nis, s.nama, s.kelas,
           a.jam_masuk, a.jam_pulang, a.status
    FROM siswa s
    LEFT JOIN absensi a ON a.id_siswa = s.id AND a.tanggal = ?
    WHERE s.aktif = 1 OR s.aktif IS NULL
";

$params = [$tanggal];
$types  = "s";

if (!empty($kelas)) {
    $sql .= " AND s.kelas = ?";
    $params[] = $kelas;
    $types .= "s";
}

$sql .= " ORDER BY s.kelas, s.nama";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}
$stmt->close();

echo json_encode($rows);
?>
