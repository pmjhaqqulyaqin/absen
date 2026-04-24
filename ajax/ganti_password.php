<?php
require_once '../includes/config.php';
cek_login();

$old = $_POST['old_password'] ?? '';
$new = $_POST['new_password'] ?? '';

$admin_id = $_SESSION['admin_id'];
$admin = $conn->query("SELECT * FROM admin WHERE id=$admin_id")->fetch_assoc();

if (!password_verify($old, $admin['password'])) {
    header('Location: ../pengaturan.php?msg=danger:Password lama salah');
    exit;
}

if (strlen($new) < 6) {
    header('Location: ../pengaturan.php?msg=danger:Password baru minimal 6 karakter');
    exit;
}

$hash = password_hash($new, PASSWORD_DEFAULT);
$conn->query("UPDATE admin SET password='$hash' WHERE id=$admin_id");
header('Location: ../pengaturan.php?msg=success:Password berhasil diubah');
exit;
?>
