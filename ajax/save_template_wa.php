<?php
require_once __DIR__ . '/../includes/config.php';
cek_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'msg' => 'Method not allowed']);
    exit;
}

$template = trim($_POST['template'] ?? '');

if (empty($template)) {
    echo json_encode(['ok' => false, 'msg' => 'Template tidak boleh kosong']);
    exit;
}

// Pastikan kolom template_wa_wali ada
$conn->query("ALTER TABLE pengaturan ADD COLUMN IF NOT EXISTS template_wa_wali TEXT DEFAULT NULL");

// Simpan template
$stmt = $conn->prepare("UPDATE pengaturan SET template_wa_wali = ? WHERE id = 1");
$stmt->bind_param('s', $template);

if ($stmt->execute()) {
    echo json_encode(['ok' => true, 'msg' => 'Template berhasil disimpan']);
} else {
    echo json_encode(['ok' => false, 'msg' => 'Gagal menyimpan: ' . $conn->error]);
}
$stmt->close();
