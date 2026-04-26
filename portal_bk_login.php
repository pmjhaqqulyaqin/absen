<?php
require_once 'includes/config.php';

// Auto-create tabel guru_bk jika belum ada
$conn->query("CREATE TABLE IF NOT EXISTS guru_bk (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    nip  VARCHAR(30) DEFAULT '',
    pin  VARCHAR(255) NOT NULL COMMENT 'bcrypt hash',
    foto VARCHAR(100) DEFAULT '',
    aktif TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Seed default jika tabel kosong
$cek = $conn->query("SELECT COUNT(*) c FROM guru_bk")->fetch_assoc();
if ((int)$cek['c'] === 0) {
    $pin_default = password_hash('1234', PASSWORD_DEFAULT);
    $conn->query("INSERT INTO guru_bk (nama, nip, pin) VALUES ('Guru BK', '-', '$pin_default')");
}

if (isset($_SESSION['bk_id'])) { header('Location: '.BASE_URL.'portal_bk.php'); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pw = $_POST['password'] ?? '';
    $found = null;

    // ── 1. Cek dari tabel wali (is_bk=1) — PIN diatur lewat menu Kelola PIN ──
    // Pastikan kolom is_bk & pin ada
    $chk = $conn->query("SHOW COLUMNS FROM wali LIKE 'is_bk'");
    if ($chk && $chk->num_rows === 0) {
        $conn->query("ALTER TABLE wali ADD COLUMN is_bk TINYINT(1) DEFAULT 0");
    }
    $chk = $conn->query("SHOW COLUMNS FROM wali LIKE 'pin'");
    if ($chk && $chk->num_rows === 0) {
        $conn->query("ALTER TABLE wali ADD COLUMN pin VARCHAR(255) DEFAULT NULL");
    }

    $r1 = $conn->query("SELECT * FROM wali WHERE is_bk=1 AND pin IS NOT NULL AND pin != ''");
    if ($r1) {
        while ($row = $r1->fetch_assoc()) {
            if (password_verify($pw, $row['pin'])) {
                $found = $row;
                $_SESSION['bk_source'] = 'wali';
                break;
            }
        }
    }

    // ── 2. Fallback: cek tabel guru_bk (PIN default 1234) ──
    if (!$found) {
        $r2 = $conn->query("SELECT * FROM guru_bk WHERE aktif=1");
        if ($r2) {
            while ($row = $r2->fetch_assoc()) {
                if (password_verify($pw, $row['pin'])) {
                    $found = $row;
                    $_SESSION['bk_source'] = 'guru_bk';
                    break;
                }
            }
        }
    }

    if ($found) {
        $_SESSION['bk_id']   = $found['id'];
        $_SESSION['bk_nama'] = $found['nama'];
        $_SESSION['bk_nip']  = $found['nip'] ?? '';
        header('Location: '.BASE_URL.'portal_bk.php'); exit;
    }
    $error = 'PIN salah! Coba lagi.';
}

$pengaturan = get_pengaturan();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Portal Guru BK – Login</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',system-ui,sans-serif;background:linear-gradient(135deg,#0f172a 0%,#1e3a8a 55%,#0e7490 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{background:white;border-radius:24px;width:100%;max-width:400px;box-shadow:0 30px 80px rgba(0,0,0,.35);overflow:hidden}
.card-top{background:linear-gradient(135deg,#1e3a8a,#0e7490);padding:32px 28px;text-align:center;color:white}
.card-top .logo{width:72px;height:72px;border-radius:50%;object-fit:cover;border:3px solid rgba(255,255,255,.4);margin-bottom:14px}
.card-top .logo-icon{width:72px;height:72px;border-radius:50%;background:rgba(255,255,255,.15);display:inline-flex;align-items:center;justify-content:center;font-size:2rem;margin-bottom:14px;border:3px solid rgba(255,255,255,.3)}
.card-top h1{font-size:1.15rem;font-weight:800;margin-bottom:4px}
.card-top p{font-size:.8rem;opacity:.75}
.badge-bk{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.15);border-radius:20px;padding:5px 14px;font-size:.78rem;font-weight:700;margin-top:10px;border:1px solid rgba(255,255,255,.25)}
.card-body{padding:32px 28px}
.error{background:#fef2f2;color:#991b1b;border:1px solid #fecaca;border-radius:10px;padding:10px 14px;font-size:.85rem;font-weight:600;margin-bottom:18px;display:flex;align-items:center;gap:8px}
.pin-label{font-size:.82rem;font-weight:700;color:#374151;margin-bottom:10px;display:flex;align-items:center;gap:6px}
.pin-dots{display:flex;justify-content:center;gap:14px;margin-bottom:20px;padding:16px;background:#f8fafc;border-radius:14px;border:2px solid #e2e8f0;transition:.2s}
.pin-dots.shake{animation:shake .4s}
@keyframes shake{0%,100%{transform:translateX(0)}20%,60%{transform:translateX(-6px)}40%,80%{transform:translateX(6px)}}
.pin-dot{width:18px;height:18px;border-radius:50%;border:2.5px solid #cbd5e1;background:transparent;transition:.2s}
.pin-dot.filled{background:#1e3a8a;border-color:#1e3a8a;transform:scale(1.1)}
.pin-pad{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;max-width:240px;margin:0 auto}
.pin-btn{padding:15px 10px;border:2px solid #e2e8f0;border-radius:12px;background:white;font-size:1.3rem;font-weight:700;color:#1e293b;cursor:pointer;transition:.15s;display:flex;align-items:center;justify-content:center;-webkit-tap-highlight-color:transparent}
.pin-btn:hover{background:#f1f5f9;border-color:#cbd5e1}
.pin-btn:active{transform:scale(.93);background:#e2e8f0}
.pin-btn.del{color:#ef4444;font-size:1.1rem}
.pin-btn.empty{border:none;background:transparent;cursor:default;pointer-events:none}
.hint{text-align:center;font-size:.76rem;color:#94a3b8;margin-top:14px}
.back-link{display:block;text-align:center;margin-top:16px;color:#64748b;font-size:.8rem;text-decoration:none}
.back-link:hover{color:#1e3a8a}
</style>
</head>
<body>
<div class="card">
    <div class="card-top">
        <?php
        $logo_file = defined('LOGO_FILE') ? LOGO_FILE : ($pengaturan['logo'] ?? '');
        if (!empty($logo_file) && file_exists(__DIR__.'/uploads/logo/'.$logo_file)): ?>
            <img src="<?= BASE_URL ?>uploads/logo/<?= $logo_file ?>" class="logo" alt="Logo MAN 2 Lombok Timur">
        <?php else: ?>
            <div class="logo-icon"><i class="fas fa-school"></i></div>
        <?php endif; ?>
        <h1><?= htmlspecialchars($pengaturan['nama_sekolah'] ?? 'Sistem Absensi') ?></h1>
        <p>Sistem Absensi Digital</p>
        <div class="badge-bk"><i class="fas fa-user-shield"></i> Portal Guru BK</div>
    </div>
    <div class="card-body">
        <?php if ($error): ?>
        <div class="error"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div>
        <?php endif; ?>

        <form method="POST" id="loginForm" onsubmit="return false">
            <input type="hidden" name="password" id="pwHidden">
            <div class="pin-label"><i class="fas fa-hashtag" style="color:#1e3a8a"></i> Masukkan PIN</div>
            <div class="pin-dots" id="pinDots">
                <span class="pin-dot" id="pd0"></span>
                <span class="pin-dot" id="pd1"></span>
                <span class="pin-dot" id="pd2"></span>
                <span class="pin-dot" id="pd3"></span>
            </div>
            <div class="pin-pad">
                <?php foreach(['1','2','3','4','5','6','7','8','9','','0','del'] as $k): ?>
                <?php if ($k === ''): ?>
                    <button type="button" class="pin-btn empty"></button>
                <?php elseif ($k === 'del'): ?>
                    <button type="button" class="pin-btn del" onclick="pinDel()"><i class="fas fa-backspace"></i></button>
                <?php else: ?>
                    <button type="button" class="pin-btn" onclick="pinEnter('<?= $k ?>')"><?= $k ?></button>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </form>
        <div class="hint"><i class="fas fa-info-circle"></i> PIN default: <strong>1234</strong></div>

    </div>
</div>
<script>
var bkPin='';var PIN_LEN=4;
function pinEnter(d){
    if(bkPin.length>=PIN_LEN)return;
    bkPin+=d;updateDots();
    if(bkPin.length===PIN_LEN){
        setTimeout(function(){
            document.getElementById('pwHidden').value=bkPin;
            document.getElementById('loginForm').submit();
        },180);
    }
}
function pinDel(){if(!bkPin.length)return;bkPin=bkPin.slice(0,-1);updateDots();}
function updateDots(){
    for(var i=0;i<PIN_LEN;i++){
        var d=document.getElementById('pd'+i);
        if(d)d.className='pin-dot'+(i<bkPin.length?' filled':'');
    }
}
<?php if ($error): ?>
document.getElementById('pinDots').classList.add('shake');
setTimeout(function(){document.getElementById('pinDots').classList.remove('shake');},500);
<?php endif; ?>
document.addEventListener('keydown',function(e){
    if(e.key>='0'&&e.key<='9')pinEnter(e.key);
    if(e.key==='Backspace'){e.preventDefault();pinDel();}
});
</script>
<?php include 'includes/bottom_nav_mobile.php'; ?>
</body>
</html>
