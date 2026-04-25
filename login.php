<?php
require_once 'includes/config.php';

// Redirect jika sudah login
if (isset($_SESSION['admin_id'])) { header('Location: dashboard.php'); exit; }

// ============================================================
// AUTO-CREATE kolom pin jika belum ada (fix utama)
// ============================================================
$conn->query("ALTER TABLE admin ADD COLUMN IF NOT EXISTS pin VARCHAR(255) DEFAULT NULL");

$pengaturan = get_pengaturan();
$error = '';

// ============================================================
// PROSES LOGIN VIA PIN (POST)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pin'])) {
    $pin_input = trim($_POST['pin']);

    if (strlen($pin_input) !== 4 || !ctype_digit($pin_input)) {
        $error = 'PIN harus 4 digit angka!';
    } else {
        $admin = $conn->query("SELECT * FROM admin LIMIT 1")->fetch_assoc();

        if ($admin) {
            $pin_ok = false;

            if (!empty($admin['pin'])) {
                // PIN sudah diatur → pakai password_verify
                $pin_ok = password_verify($pin_input, $admin['pin']);
            } else {
                // PIN belum diatur → default 1234
                $pin_ok = ($pin_input === '1234');
            }

            if ($pin_ok) {
                $_SESSION['admin_id']   = $admin['id'];
                $_SESSION['admin_nama'] = $admin['nama'];
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'PIN salah! Coba lagi.';
            }
        } else {
            $error = 'Data admin tidak ditemukan!';
        }
    }
}

// ============================================================
// PROSES LOGIN VIA PASSWORD (fallback)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT * FROM admin WHERE username=? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id']   = $admin['id'];
        $_SESSION['admin_nama'] = $admin['nama'];
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Username atau password salah!';
    }
}

$mode = $_GET['mode'] ?? 'pin'; // pin | password
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- PWA: Theme & Status Bar -->
    <meta name="theme-color" content="#1a2332">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Absensi MAN2">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="application-name" content="Absensi MAN2">
    <meta name="msapplication-TileColor" content="#1a2332">
    <meta name="msapplication-TileImage" content="assets/pwa/pwa-icon-192x192.png">

    <!-- PWA: Web App Manifest & Apple Touch Icon -->
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/pwa/pwa-icon-180x180.png">
    
    <script>
      window.__pwaInstallEvent = null;
      window.__pwaInstallCallbacks = [];
      window.addEventListener('beforeinstallprompt', function(e) {
        e.preventDefault();
        window.__pwaInstallEvent = e;
        console.log('[PWA] beforeinstallprompt captured early');
        window.__pwaInstallCallbacks.forEach(function(cb) { cb(e); });
      });

      if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
          navigator.serviceWorker.register('/sw.js')
            .then(function(reg) {
              console.log('[PWA] Service Worker registered, scope:', reg.scope);
              setInterval(function() { reg.update(); }, 60 * 60 * 1000);
            })
            .catch(function(err) {
              console.warn('[PWA] Service Worker registration failed:', err);
            });
        });
      }
    </script>
    <title>Login Admin — <?= htmlspecialchars($pengaturan['nama_sekolah'] ?? 'Absensi Digital') ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    *{margin:0;padding:0;box-sizing:border-box}
    body{font-family:'Segoe UI',sans-serif;min-height:100vh;
         background:linear-gradient(135deg,#1e3a8a 0%,#1e40af 40%,#1d4ed8 100%);
         display:flex;flex-direction:column;align-items:center;justify-content:center;padding:20px}

    .login-card{background:white;border-radius:24px;overflow:hidden;
                width:100%;max-width:380px;box-shadow:0 25px 60px rgba(0,0,0,.35)}

    /* Header orange */
    .card-header{background:linear-gradient(135deg,#f59e0b,#d97706);
                 padding:32px 24px 28px;text-align:center;color:white}
    .school-logo{width:72px;height:72px;border-radius:50%;border:3px solid rgba(255,255,255,.4);
                 object-fit:contain;background:white;padding:6px;margin-bottom:12px}
    .school-logo-icon{width:72px;height:72px;border-radius:50%;border:3px solid rgba(255,255,255,.4);
                      background:rgba(255,255,255,.2);display:inline-flex;align-items:center;
                      justify-content:center;font-size:2rem;margin-bottom:12px}
    .school-name{font-size:1.15rem;font-weight:900;letter-spacing:.3px;margin-bottom:4px}
    .school-sub{font-size:.78rem;opacity:.85;margin-bottom:14px}
    .btn-login-badge{display:inline-flex;align-items:center;gap:7px;
                     background:rgba(255,255,255,.25);border:1px solid rgba(255,255,255,.4);
                     padding:7px 18px;border-radius:30px;font-size:.82rem;font-weight:700}

    /* Body */
    .card-body{padding:28px 24px 24px}
    .pin-title{display:flex;align-items:center;gap:8px;font-size:.9rem;font-weight:800;
               color:#1e293b;margin-bottom:18px}
    .pin-title i{color:#3b82f6}

    /* Dots indicator */
    .pin-dots{display:flex;justify-content:center;gap:14px;margin-bottom:22px}
    .dot{width:18px;height:18px;border-radius:50%;border:2.5px solid #cbd5e1;transition:.2s}
    .dot.filled{background:#f59e0b;border-color:#f59e0b;transform:scale(1.1)}

    /* Error */
    .alert-error{background:#fef2f2;border:1px solid #fecaca;color:#dc2626;
                 border-radius:10px;padding:10px 14px;font-size:.82rem;font-weight:600;
                 margin-bottom:16px;display:flex;align-items:center;gap:8px}

    /* Numpad */
    .numpad{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:16px}
    .key{background:#f8fafc;border:2px solid #e2e8f0;border-radius:14px;
         padding:16px 8px;font-size:1.4rem;font-weight:700;color:#1e293b;
         cursor:pointer;transition:.15s;text-align:center;user-select:none}
    .key:hover{background:#eff6ff;border-color:#93c5fd;transform:translateY(-1px)}
    .key:active{transform:scale(.95);background:#dbeafe}
    .key.del{background:#fef2f2;border-color:#fecaca;color:#ef4444;font-size:1.1rem}
    .key.del:hover{background:#fee2e2;border-color:#f87171}
    .key.zero{grid-column:2}

    /* Footer links */
    .login-hint{text-align:center;font-size:.72rem;color:#94a3b8;margin-bottom:10px}
    .login-hint strong{color:#f59e0b}
    .link-btn{display:block;text-align:center;font-size:.78rem;color:#3b82f6;
              text-decoration:none;font-weight:600;margin-bottom:6px;padding:4px}
    .link-btn:hover{color:#1d4ed8;text-decoration:underline}
    .back-link{display:flex;align-items:center;justify-content:center;gap:6px;
               font-size:.78rem;color:#64748b;text-decoration:none;margin-top:4px}
    .back-link:hover{color:#334155}

    /* Password mode */
    .form-group{margin-bottom:14px}
    .form-group label{display:block;font-size:.8rem;font-weight:700;color:#374151;margin-bottom:6px}
    .form-group input{width:100%;padding:11px 14px;border:2px solid #e2e8f0;border-radius:10px;
                      font-size:.95rem;outline:none;transition:.2s}
    .form-group input:focus{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.1)}
    .btn-submit{width:100%;padding:12px;background:linear-gradient(135deg,#f59e0b,#d97706);
                color:white;border:none;border-radius:12px;font-size:.95rem;font-weight:800;
                cursor:pointer;transition:.2s;letter-spacing:.5px}
    .btn-submit:hover{opacity:.9;transform:translateY(-1px)}
    </style>
</head>
<body>

<div class="login-card">
    <!-- Header -->
    <div class="card-header">
        <?php if (!empty($pengaturan['logo']) && file_exists(__DIR__.'/uploads/logo/'.$pengaturan['logo'])): ?>
            <img src="<?= BASE_URL ?>uploads/logo/<?= $pengaturan['logo'] ?>" class="school-logo" alt="Logo">
        <?php else: ?>
            <div class="school-logo-icon"><i class="fas fa-school"></i></div>
        <?php endif; ?>
        <div class="school-name"><?= htmlspecialchars($pengaturan['nama_sekolah'] ?? 'Nama Sekolah') ?></div>
        <div class="school-sub">Sistem Absensi Digital</div>
        <div class="btn-login-badge"><i class="fas fa-user-shield"></i> Login Administrator</div>
    </div>

    <div class="card-body">

        <?php if ($error): ?>
        <div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($mode === 'pin'): ?>
        <!-- ====== MODE PIN ====== -->
        <div class="pin-title"><i class="fas fa-hashtag"></i> Masukkan PIN Admin 4 Digit</div>

        <div class="pin-dots">
            <div class="dot" id="d0"></div>
            <div class="dot" id="d1"></div>
            <div class="dot" id="d2"></div>
            <div class="dot" id="d3"></div>
        </div>

        <form method="POST" id="pinForm">
            <input type="hidden" name="pin" id="pinInput">
        </form>

        <div class="numpad">
            <?php for($i=1;$i<=9;$i++): ?>
            <div class="key" onclick="pressKey('<?= $i ?>')"><?= $i ?></div>
            <?php endfor; ?>
            <div class="key zero" onclick="pressKey('0')">0</div>
            <div class="key del" onclick="deleteKey()"><i class="fas fa-backspace"></i></div>
        </div>

        <a href="login.php?mode=password" class="link-btn"><i class="fas fa-key"></i> Belum punya PIN? Gunakan Password</a>


        <?php else: ?>
        <!-- ====== MODE PASSWORD ====== -->
        <div class="pin-title"><i class="fas fa-lock"></i> Login dengan Password</div>

        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" placeholder="Masukkan username" required autofocus>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Masukkan password" required>
            </div>
            <button type="submit" class="btn-submit"><i class="fas fa-sign-in-alt"></i> MASUK</button>
        </form>

        <a href="login.php?mode=pin" class="link-btn" style="margin-top:14px"><i class="fas fa-hashtag"></i> Gunakan PIN</a>


        <?php endif; ?>
    </div>
</div>

<?php if ($mode === 'pin'): ?>
<script>
var pin = '';

function pressKey(num) {
    if (pin.length >= 4) return;
    pin += num;
    updateDots();
    if (pin.length === 4) {
        document.getElementById('pinInput').value = pin;
        document.getElementById('pinForm').submit();
    }
}

function deleteKey() {
    pin = pin.slice(0, -1);
    updateDots();
}

function updateDots() {
    for (var i = 0; i < 4; i++) {
        var dot = document.getElementById('d' + i);
        dot.className = 'dot' + (i < pin.length ? ' filled' : '');
    }
}

// Keyboard support
document.addEventListener('keydown', function(e) {
    if (e.key >= '0' && e.key <= '9') pressKey(e.key);
    if (e.key === 'Backspace') deleteKey();
});
</script>
<?php endif; ?>
<?php include 'includes/bottom_nav_mobile.php'; ?>
<?php include 'includes/pwa_banner.php'; ?>
</body>
</html>
