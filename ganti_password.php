<?php
require_once '../includes/config.php';
cek_login();

// Hanya terima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pengaturan.php');
    exit;
}

$old = $_POST['old_password'] ?? '';
$new = $_POST['new_password'] ?? '';
$admin_id = (int) $_SESSION['admin_id'];

// Ambil data admin pakai prepared statement
$stmt = $conn->prepare("SELECT * FROM admin WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Cek apakah admin ditemukan
if (!$admin) {
    header('Location: ../pengaturan.php?msg=danger:Session tidak valid. Silakan login ulang');
    exit;
}

// ============================================================
// PERBAIKAN UTAMA: Dukung password lama yang belum di-hash
// (plain text "password" dari default install)
// ============================================================
$password_valid = false;

if (password_needs_rehash($admin['password'], PASSWORD_DEFAULT) === false && 
    substr($admin['password'], 0, 1) === '$') {
    // Password sudah berbentuk hash bcrypt → verifikasi normal
    $password_valid = password_verify($old, $admin['password']);
} else {
    // Password masih plain text (belum pernah diganti) → bandingkan langsung
    $password_valid = ($old === $admin['password']);
}

if (!$password_valid) {
    header('Location: ../pengaturan.php?msg=danger:Password lama salah');
    exit;
}

// Validasi password baru
if (strlen($new) < 6) {
    header('Location: ../pengaturan.php?msg=danger:Password baru minimal 6 karakter');
    exit;
}

if ($new === $old) {
    header('Location: ../pengaturan.php?msg=danger:Password baru tidak boleh sama dengan password lama');
    exit;
}

// ============================================================
// PERBAIKAN UTAMA: Pakai prepared statement agar hash bcrypt
// tidak rusak saat dimasukkan ke query SQL
// ============================================================
$hash = password_hash($new, PASSWORD_DEFAULT);

$stmt2 = $conn->prepare("UPDATE admin SET password = ? WHERE id = ?");
$stmt2->bind_param("si", $hash, $admin_id);
$result = $stmt2->execute();
$affected = $stmt2->affected_rows;
$stmt2->close();

if ($result && $affected >= 0) {
    // Logout paksa agar harus login ulang dengan password baru
    session_destroy();
    header('Location: ../login.php?msg=Password berhasil diubah. Silakan login dengan password baru');
    exit;
} else {
    // Tampilkan error database
    header('Location: ../pengaturan.php?msg=danger:Gagal menyimpan password. Error: ' . $conn->error);
    exit;
}
?>
