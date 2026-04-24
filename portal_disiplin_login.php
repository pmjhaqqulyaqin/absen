<?php
require_once 'includes/config.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['disiplin_login'])) { header('Location: portal_disiplin.php'); exit; }

$pengaturan = get_pengaturan();
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pin'])) {
    $pin_input = trim($_POST['pin'] ?? '');
    $row = $conn->query("SELECT pin FROM disiplin_pin LIMIT 1")->fetch_assoc();
    if ($row && password_verify($pin_input, $row['pin'])) {
        $_SESSION['disiplin_login'] = true;
        header('Location: portal_disiplin.php'); exit;
    } else {
        $err = 'PIN salah. Coba lagi.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login Disiplin — <?= htmlspecialchars($pengaturan['nama_sekolah']) ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',sans-serif;background:linear-gradient(135deg,#0f172a,#1e3a8a,#0f172a);min-height:100vh;display:flex;align-items:center;justify-content:center}
.card{background:rgba(255,255,255,.05);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,.12);border-radius:24px;padding:40px 36px;width:100%;max-width:380px;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.4)}
.logo{width:72px;height:72px;border-radius:18px;object-fit:contain;background:white;padding:6px;margin:0 auto 16px}
.logo-icon{width:72px;height:72px;background:linear-gradient(135deg,#f59e0b,#d97706);border-radius:18px;display:flex;align-items:center;justify-content:center;font-size:2rem;margin:0 auto 16px;box-shadow:0 8px 24px rgba(245,158,11,.4)}
h2{color:white;font-size:1.3rem;font-weight:800;margin-bottom:4px}
.sub{color:#94a3b8;font-size:.85rem;margin-bottom:28px}
.pin-display{display:flex;justify-content:center;gap:12px;margin-bottom:24px}
.pin-dot{width:16px;height:16px;border-radius:50%;border:2px solid rgba(255,255,255,.3);background:transparent;transition:.2s}
.pin-dot.filled{background:#f59e0b;border-color:#f59e0b;box-shadow:0 0 10px rgba(245,158,11,.5)}
.numpad{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:16px}
.num-btn{height:64px;border-radius:14px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.08);color:white;font-size:1.4rem;font-weight:700;cursor:pointer;transition:.2s;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:2px}
.num-btn:hover{background:rgba(255,255,255,.16);transform:scale(1.05)}
.num-btn:active{transform:scale(.96);background:rgba(245,158,11,.3)}
.num-btn .sub-txt{font-size:.45rem;font-weight:600;letter-spacing:1.5px;color:#94a3b8;text-transform:uppercase}
.num-btn.del-btn{font-size:1rem;color:#f87171}
.num-btn.del-btn:hover{background:rgba(248,113,113,.15)}
.btn-login{width:100%;padding:14px;border-radius:12px;border:none;background:linear-gradient(135deg,#f59e0b,#d97706);color:white;font-size:1rem;font-weight:700;cursor:pointer;transition:.2s;margin-top:4px}
.btn-login:hover{opacity:.9;transform:translateY(-1px);box-shadow:0 8px 24px rgba(245,158,11,.4)}
.err{color:#f87171;font-size:.83rem;margin-bottom:10px;background:rgba(248,113,113,.1);border:1px solid rgba(248,113,113,.3);padding:8px 14px;border-radius:8px}
.back{display:inline-flex;align-items:center;gap:6px;color:#94a3b8;font-size:.8rem;text-decoration:none;margin-top:18px;transition:.2s}
.back:hover{color:#38bdf8}
</style>
</head>
<body>
<div class="card">
    <?php if (!empty($pengaturan['logo']) && file_exists(__DIR__.'/uploads/logo/'.$pengaturan['logo'])): ?>
    <img src="<?= BASE_URL ?>uploads/logo/<?= $pengaturan['logo'] ?>" class="logo" alt="Logo">
    <?php else: ?>
    <div class="logo-icon"><i class="fas fa-shield-alt"></i></div>
    <?php endif; ?>

    <h2>Portal Disiplin Sekolah</h2>
    <div class="sub"><?= htmlspecialchars($pengaturan['nama_sekolah']) ?></div>

    <?php if ($err): ?>
    <div class="err"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <!-- Indikator PIN -->
    <div class="pin-display" id="pinDisplay">
        <div class="pin-dot" id="d0"></div>
        <div class="pin-dot" id="d1"></div>
        <div class="pin-dot" id="d2"></div>
        <div class="pin-dot" id="d3"></div>
        <div class="pin-dot" id="d4"></div>
        <div class="pin-dot" id="d5"></div>
    </div>

    <!-- Numpad -->
    <div class="numpad">
        <?php
        $nums = [
            ['1',''],['2','ABC'],['3','DEF'],
            ['4','GHI'],['5','JKL'],['6','MNO'],
            ['7','PQRS'],['8','TUV'],['9','WXYZ'],
        ];
        foreach ($nums as [$n,$s]): ?>
        <button class="num-btn" onclick="addPin('<?= $n ?>')">
            <?= $n ?>
            <?php if ($s): ?><span class="sub-txt"><?= $s ?></span><?php endif; ?>
        </button>
        <?php endforeach; ?>
        <button class="num-btn" onclick="addPin('0')">0</button>
        <button class="num-btn del-btn" onclick="delPin()"><i class="fas fa-backspace"></i></button>
    </div>

    <form method="POST" id="pinForm">
        <input type="hidden" name="pin" id="pinInput">
        <button type="button" class="btn-login" onclick="submitPin()">
            <i class="fas fa-unlock-alt"></i> Masuk
        </button>
    </form>


</div>

<script>
var pinVal = '';

function updateDots() {
    for (var i = 0; i < 6; i++) {
        var d = document.getElementById('d' + i);
        if (d) d.classList.toggle('filled', i < pinVal.length);
    }
}

function addPin(n) {
    if (pinVal.length >= 8) return;
    pinVal += n;
    updateDots();
    // Auto submit jika 4+ digit dan sesuai panjang PIN (coba 4-8 digit)
    // User bisa tekan tombol Masuk manual
}

function delPin() {
    pinVal = pinVal.slice(0, -1);
    updateDots();
}

function submitPin() {
    if (!pinVal) return;
    document.getElementById('pinInput').value = pinVal;
    document.getElementById('pinForm').submit();
}

// Keyboard support
document.addEventListener('keydown', function(e) {
    if (e.key >= '0' && e.key <= '9') addPin(e.key);
    else if (e.key === 'Backspace') delPin();
    else if (e.key === 'Enter') submitPin();
});
</script>
<?php include 'includes/bottom_nav_mobile.php'; ?>
</body>
</html>
