<?php
/**
 * ============================================================
 * RESET PIN DARURAT - MA NW TOYA
 * PENTING: HAPUS FILE INI SETELAH SELESAI DIGUNAKAN!
 * ============================================================
 */
require_once 'includes/config.php';

$msg = '';
$msg_type = '';

// Langkah 1: Auto-create kolom pin (support MySQL 5.7 & 8.0)
$check_col = $conn->query("SHOW COLUMNS FROM admin LIKE 'pin'");
if ($check_col->num_rows === 0) {
    $conn->query("ALTER TABLE admin ADD COLUMN pin VARCHAR(255) DEFAULT NULL");
}
$check_kepsek = $conn->query("SHOW COLUMNS FROM admin LIKE 'kepsek_pin'");
if ($check_kepsek->num_rows === 0) {
    $conn->query("ALTER TABLE admin ADD COLUMN kepsek_pin VARCHAR(255) DEFAULT NULL");
}

// Ambil data admin
$admin = $conn->query("SELECT * FROM admin LIMIT 1")->fetch_assoc();

// Proses form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    // Reset PIN ke default (hapus PIN, kembali ke 1234)
    if ($aksi === 'reset') {
        $conn->query("UPDATE admin SET pin=NULL WHERE id={$admin['id']}");
        $msg = '✅ PIN berhasil direset! Sekarang gunakan PIN default: 1234';
        $msg_type = 'success';
        $admin = $conn->query("SELECT * FROM admin LIMIT 1")->fetch_assoc();
    }

    // Set PIN baru
    if ($aksi === 'set_pin') {
        $pin_baru = trim($_POST['pin_baru'] ?? '');
        $pin_konfirm = trim($_POST['pin_konfirm'] ?? '');
        if (strlen($pin_baru) !== 4 || !ctype_digit($pin_baru)) {
            $msg = '❌ PIN harus 4 digit angka!'; $msg_type = 'danger';
        } elseif ($pin_baru !== $pin_konfirm) {
            $msg = '❌ Konfirmasi PIN tidak cocok!'; $msg_type = 'danger';
        } else {
            $hashed = password_hash($pin_baru, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE admin SET pin=? WHERE id=?");
            $stmt->bind_param("si", $hashed, $admin['id']);
            if ($stmt->execute()) {
                $msg = '✅ PIN berhasil disimpan! Sekarang login dengan PIN: ' . $pin_baru;
                $msg_type = 'success';
            } else {
                $msg = '❌ Gagal simpan: ' . $conn->error;
                $msg_type = 'danger';
            }
            $stmt->close();
            $admin = $conn->query("SELECT * FROM admin LIMIT 1")->fetch_assoc();
        }
    }
}

$pin_status = !empty($admin['pin']) ? '🔒 Sudah diatur (ter-hash)' : '⚠️ Belum diatur — default PIN: 1234';
$pengaturan = get_pengaturan();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset PIN Darurat</title>
    <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Segoe UI', sans-serif; background: #0f172a; color: white; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
    .card { background: #1e293b; border: 1px solid #334155; border-radius: 20px; padding: 32px; max-width: 480px; width: 100%; }
    .warn-banner { background: #7f1d1d; border: 1px solid #dc2626; border-radius: 10px; padding: 14px 18px; margin-bottom: 24px; font-size: .85rem; line-height: 1.6; }
    .warn-banner strong { color: #fca5a5; display: block; font-size: 1rem; margin-bottom: 4px; }
    h2 { font-size: 1.2rem; font-weight: 800; margin-bottom: 6px; color: #f8fafc; }
    .sub { color: #64748b; font-size: .82rem; margin-bottom: 24px; }
    .info-row { display: flex; justify-content: space-between; align-items: center; background: #0f172a; border-radius: 10px; padding: 12px 16px; margin-bottom: 20px; font-size: .85rem; }
    .info-row span { color: #94a3b8; }
    .section { border: 1px solid #334155; border-radius: 12px; padding: 20px; margin-bottom: 16px; }
    .section h3 { font-size: .9rem; font-weight: 700; margin-bottom: 14px; }
    label { display: block; font-size: .78rem; color: #94a3b8; margin-bottom: 6px; font-weight: 600; }
    input[type=text], input[type=password] { width: 100%; padding: 10px 14px; background: #0f172a; border: 2px solid #334155; border-radius: 8px; color: white; font-size: .95rem; margin-bottom: 12px; outline: none; }
    input:focus { border-color: #3b82f6; }
    .btn { padding: 11px 20px; border: none; border-radius: 10px; font-weight: 700; font-size: .88rem; cursor: pointer; width: 100%; transition: .2s; }
    .btn:hover { opacity: .88; }
    .btn-danger { background: #dc2626; color: white; }
    .btn-primary { background: #3b82f6; color: white; }
    .alert { padding: 12px 16px; border-radius: 10px; margin-bottom: 18px; font-size: .85rem; font-weight: 600; }
    .alert-success { background: rgba(74,222,128,.1); border: 1px solid rgba(74,222,128,.3); color: #4ade80; }
    .alert-danger { background: rgba(248,113,113,.1); border: 1px solid rgba(248,113,113,.3); color: #f87171; }
    .btn-login { display: block; text-align: center; margin-top: 20px; padding: 12px; background: linear-gradient(135deg,#f59e0b,#d97706); color: white; border-radius: 12px; font-weight: 800; text-decoration: none; }
    .delete-note { margin-top: 20px; background: #451a03; border: 1px solid #92400e; border-radius: 10px; padding: 12px 16px; font-size: .78rem; color: #fbbf24; line-height: 1.6; }
    </style>
</head>
<body>
<div class="card">
    <div class="warn-banner">
        <strong>⚠️ HALAMAN DARURAT — JANGAN BAGIKAN LINK INI!</strong>
        Halaman ini hanya untuk reset PIN. Hapus file ini dari server setelah selesai!
    </div>

    <h2>🔧 Reset PIN Admin</h2>
    <div class="sub"><?= htmlspecialchars($pengaturan['nama_sekolah'] ?? '') ?></div>

    <?php if ($msg): ?>
    <div class="alert alert-<?= $msg_type ?>"><?= $msg ?></div>
    <?php endif; ?>

    <div class="info-row">
        <span>Status PIN saat ini:</span>
        <strong style="color:#f59e0b"><?= $pin_status ?></strong>
    </div>

    <!-- Reset ke default 1234 -->
    <div class="section">
        <h3>🔄 Reset ke Default (PIN: 1234)</h3>
        <p style="font-size:.8rem;color:#64748b;margin-bottom:14px">Menghapus PIN yang tersimpan, sehingga bisa login dengan PIN default 1234.</p>
        <form method="POST" onsubmit="return confirm('Reset PIN ke default 1234?')">
            <input type="hidden" name="aksi" value="reset">
            <button type="submit" class="btn btn-danger">🔄 Reset PIN ke 1234</button>
        </form>
    </div>

    <!-- Set PIN baru langsung -->
    <div class="section">
        <h3>🔑 Set PIN Baru Sekarang</h3>
        <form method="POST">
            <input type="hidden" name="aksi" value="set_pin">
            <label>PIN Baru (4 digit)</label>
            <input type="text" name="pin_baru" maxlength="4" placeholder="Contoh: 5678" pattern="[0-9]{4}" required oninput="this.value=this.value.replace(/\D/g,'')">
            <label>Konfirmasi PIN</label>
            <input type="text" name="pin_konfirm" maxlength="4" placeholder="Ulangi PIN" pattern="[0-9]{4}" required oninput="this.value=this.value.replace(/\D/g,'')">
            <button type="submit" class="btn btn-primary">✅ Simpan PIN Baru</button>
        </form>
    </div>

    <a href="login.php" class="btn-login">→ Pergi ke Halaman Login</a>

    <div class="delete-note">
        ⚠️ <strong>PENTING:</strong> Setelah selesai, hapus file <code>reset_pin.php</code> dari server melalui File Manager hosting Anda!
    </div>
</div>
</body>
</html>
