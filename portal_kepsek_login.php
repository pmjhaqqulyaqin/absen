<?php
require_once 'includes/config.php';

if (isset($_SESSION['kepsek_id'])) { header('Location: '.BASE_URL.'portal_kepsek.php'); exit; }

$error = '';

// Cek apakah kolom kepsek_pin sudah ada
$kepsek_pin_col = false;
$chk = $conn->query("SHOW COLUMNS FROM admin LIKE 'kepsek_pin'");
if ($chk && $chk->num_rows > 0) $kepsek_pin_col = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pw = $_POST['password'] ?? '';

    $all = $conn->query("SELECT * FROM admin");
    $found = null;
    while ($row = $all->fetch_assoc()) {
        // Cek kepsek_pin dulu (kolom terpisah)
        if ($kepsek_pin_col && !empty($row['kepsek_pin']) && password_verify($pw, $row['kepsek_pin'])) {
            $found = $row; break;
        }
        // Fallback ke password lama
        if (!$found && password_verify($pw, $row['password'])) {
            $found = $row; break;
        }
    }
    if ($found) {
        $_SESSION['kepsek_id']   = $found['id'];
        $_SESSION['kepsek_nama'] = $found['nama'];
        header('Location: '.BASE_URL.'portal_kepsek.php'); exit;
    }
    $error = 'PIN atau Password salah!';
}

$pengaturan = get_pengaturan();

// Tampilkan mode password jika kepsek_pin belum ada di DB
$show_pin_mode = $kepsek_pin_col;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Kepala Sekolah - <?= htmlspecialchars($pengaturan['nama_sekolah']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    *{margin:0;padding:0;box-sizing:border-box}
    body{font-family:'Segoe UI',sans-serif;background:linear-gradient(135deg,#1e1b4b,#312e81,#1e1b4b);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
    .login-wrap{width:100%;max-width:420px}
    .login-header{text-align:center;margin-bottom:32px}
    .icon-wrap{width:80px;height:80px;background:linear-gradient(135deg,#7c3aed,#5b21b6);border-radius:20px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:2rem;color:white;box-shadow:0 8px 24px rgba(124,58,237,.4)}
    .login-header h1{color:white;font-size:1.3rem;font-weight:800;margin-bottom:4px}
    .login-header p{color:#a5b4fc;font-size:.85rem}
    .card{background:rgba(255,255,255,.06);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,.12);border-radius:20px;padding:32px}
    .form-group{margin-bottom:18px}
    .form-group label{display:block;font-size:.8rem;font-weight:700;color:#c4b5fd;margin-bottom:8px;text-transform:uppercase;letter-spacing:.5px}
    .input-wrap{position:relative}
    .input-wrap i.ico{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#7c3aed;font-size:.9rem}
    .form-control{width:100%;padding:12px 14px 12px 40px;background:rgba(255,255,255,.08);border:2px solid rgba(255,255,255,.1);border-radius:12px;color:white;font-size:.95rem;outline:none;transition:.2s}
    .form-control:focus{border-color:#7c3aed;background:rgba(124,58,237,.1)}
    .form-control::placeholder{color:rgba(255,255,255,.3)}
    /* PIN */
    .pin-label{font-size:.8rem;font-weight:700;color:#c4b5fd;margin-bottom:8px;text-transform:uppercase;letter-spacing:.5px}
    .pin-display{display:flex;justify-content:center;gap:16px;margin-bottom:18px;padding:16px;background:rgba(255,255,255,.05);border-radius:14px;border:2px solid rgba(255,255,255,.1)}
    .pin-dot{width:18px;height:18px;border-radius:50%;border:2.5px solid rgba(255,255,255,.2);background:transparent;transition:.2s}
    .pin-dot.filled{background:#7c3aed;border-color:#a78bfa;transform:scale(1.15);box-shadow:0 0 10px rgba(124,58,237,.6)}
    .pin-pad{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;max-width:260px;margin:0 auto}
    .pin-btn{padding:14px 10px;border:2px solid rgba(255,255,255,.12);border-radius:12px;background:rgba(255,255,255,.05);font-size:1.3rem;font-weight:700;color:white;cursor:pointer;transition:.15s;display:flex;align-items:center;justify-content:center}
    .pin-btn:hover{background:rgba(124,58,237,.3);border-color:#7c3aed;transform:translateY(-1px)}
    .pin-btn:active{transform:scale(.95)}
    .pin-btn.del{color:#f87171;font-size:1.1rem}
    .pin-btn.empty{border:none;background:transparent;cursor:default;pointer-events:none}
    .btn-login{width:100%;padding:14px;background:linear-gradient(135deg,#7c3aed,#5b21b6);color:white;border:none;border-radius:12px;font-size:1rem;font-weight:700;cursor:pointer;transition:.2s;margin-top:6px;display:flex;align-items:center;justify-content:center;gap:8px}
    .btn-login:hover{opacity:.9;transform:translateY(-1px);box-shadow:0 8px 20px rgba(124,58,237,.4)}
    .alert-error{background:rgba(248,113,113,.15);border:1px solid rgba(248,113,113,.3);border-radius:10px;padding:12px 16px;color:#fca5a5;font-size:.85rem;margin-bottom:18px;display:flex;align-items:center;gap:8px}
    .fallback-toggle{text-align:center;margin-top:12px}
    .fallback-toggle a{color:#a5b4fc;font-size:.8rem;cursor:pointer;display:inline-flex;align-items:center;gap:5px}
    .fallback-toggle a:hover{color:white}
    .pw-section{display:none}
    .pw-section .form-control{background:rgba(253,230,138,.08);border-color:rgba(253,230,138,.3)}
    .back-link{text-align:center;margin-top:20px}
    .back-link a{color:#a5b4fc;font-size:.85rem;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
    .back-link a:hover{color:white}
    .school-name{color:#e0e7ff;font-size:.78rem;text-align:center;margin-top:20px;opacity:.7}
    </style>
</head>
<body>
<div class="login-wrap">
    <div class="login-header">
        <div class="icon-wrap"><i class="fas fa-user-tie"></i></div>
        <h1>Portal Kepala Sekolah</h1>
        <p>Masuk untuk melihat rekap absensi siswa</p>
    </div>

    <div class="card">
        <?php if ($error): ?>
        <div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" id="kepsekForm" onsubmit="return doSubmit()">
            <input type="hidden" name="username" id="usernameHidden" value="">
            <input type="hidden" name="password" id="pwHidden">
            <div id="pinSection" style="<?= !$show_pin_mode ? 'display:none' : '' ?>">
                <div class="form-group">
                    <div class="pin-label">Masukkan PIN Kepala Sekolah 4 Digit</div>
                    <div class="pin-display">
                        <span class="pin-dot" id="kd0"></span>
                        <span class="pin-dot" id="kd1"></span>
                        <span class="pin-dot" id="kd2"></span>
                        <span class="pin-dot" id="kd3"></span>
                    </div>
                    <div class="pin-pad">
                        <button type="button" class="pin-btn" onclick="pinEnter('1')">1</button>
                        <button type="button" class="pin-btn" onclick="pinEnter('2')">2</button>
                        <button type="button" class="pin-btn" onclick="pinEnter('3')">3</button>
                        <button type="button" class="pin-btn" onclick="pinEnter('4')">4</button>
                        <button type="button" class="pin-btn" onclick="pinEnter('5')">5</button>
                        <button type="button" class="pin-btn" onclick="pinEnter('6')">6</button>
                        <button type="button" class="pin-btn" onclick="pinEnter('7')">7</button>
                        <button type="button" class="pin-btn" onclick="pinEnter('8')">8</button>
                        <button type="button" class="pin-btn" onclick="pinEnter('9')">9</button>
                        <button type="button" class="pin-btn empty"></button>
                        <button type="button" class="pin-btn" onclick="pinEnter('0')">0</button>
                        <button type="button" class="pin-btn del" onclick="pinDel()"><i class="fas fa-backspace"></i></button>
                    </div>
                </div>
                <div class="fallback-toggle">
                    <a onclick="toggleMode()"><i class="fas fa-key"></i> Belum punya PIN? Gunakan Password</a>
                </div>
            </div>

            <!-- Mode Password (fallback) -->
            <div id="pwSection" class="pw-section" style="<?= !$show_pin_mode ? 'display:block' : '' ?>">
                <div class="form-group">
                    <label>Password Lama</label>
                    <div class="input-wrap" style="position:relative">
                        <i class="fas fa-lock ico"></i>
                        <input type="password" id="pwInput" class="form-control" placeholder="Masukkan password" style="padding-right:40px">
                        <button type="button" onclick="togglePw()" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#a5b4fc">
                            <i class="fas fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                    <p style="color:#fde68a;font-size:.75rem;margin-top:6px">⚠️ Mode password — atur PIN setelah masuk.</p>
                </div>
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Masuk ke Portal
                </button>
                <div class="fallback-toggle">
                    <a onclick="toggleMode()"><i class="fas fa-hashtag"></i> Kembali ke PIN</a>
                </div>
            </div>
        </form>
    </div>


    </div>
    <div class="school-name"><?= htmlspecialchars($pengaturan['nama_sekolah']) ?></div>
</div>
<script>
var kPin = '';
var usePassword = <?= !$show_pin_mode ? 'true' : 'false' ?>;
var PIN_LEN = 4;

function toggleMode() {
    usePassword = !usePassword;
    document.getElementById('pinSection').style.display = usePassword ? 'none' : '';
    document.getElementById('pwSection').style.display  = usePassword ? 'block' : 'none';
    if (usePassword) { document.getElementById('pwInput').focus(); }
    else { kPin=''; updateDots(); }
}

function pinEnter(d) {
    if (kPin.length >= PIN_LEN) return;
    kPin += d;
    updateDots();
    if (kPin.length === PIN_LEN) {
        setTimeout(function(){
            document.getElementById('usernameHidden').value = '';
            document.getElementById('pwHidden').value = kPin;
            document.getElementById('kepsekForm').submit();
        }, 150);
    }
}

function pinDel() {
    if (!kPin.length) return;
    kPin = kPin.slice(0,-1);
    updateDots();
}

function updateDots() {
    for (var i=0;i<PIN_LEN;i++) {
        var d=document.getElementById('kd'+i);
        if(d) d.className='pin-dot'+(i<kPin.length?' filled':'');
    }
}

function doSubmit() {
    if (!usePassword) return false;
    var pw = document.getElementById('pwInput').value;
    if (!pw) { alert('Password harus diisi!'); return false; }
    document.getElementById('usernameHidden').value = '';
    document.getElementById('pwHidden').value = pw;
    return true;
}

function togglePw() {
    var p=document.getElementById('pwInput'),i=document.getElementById('eyeIcon');
    p.type=p.type==='password'?'text':'password';
    i.className=p.type==='password'?'fas fa-eye':'fas fa-eye-slash';
}

document.addEventListener('keydown', function(e) {
    if (usePassword) return;
    if (document.activeElement && document.activeElement.tagName==='INPUT') return;
    if (e.key>='0'&&e.key<='9') pinEnter(e.key);
    if (e.key==='Backspace') { e.preventDefault(); pinDel(); }
});

document.addEventListener('DOMContentLoaded', function(){ });
</script>
<?php include 'includes/bottom_nav_mobile.php'; ?>
</body>
</html>
