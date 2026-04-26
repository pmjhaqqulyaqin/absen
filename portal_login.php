<?php
require_once 'includes/config.php';

if (isset($_SESSION['siswa_id'])) { header('Location: '.BASE_URL.'portal_siswa.php'); exit; }
if (isset($_SESSION['wali_id']))   { header('Location: '.BASE_URL.'portal_wali.php'); exit; }

$role  = $_GET['role'] ?? 'siswa';
$error = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $role = $_POST['role'] ?? 'siswa';
    $user = trim($_POST['username'] ?? '');
    $pw   = $_POST['password'] ?? '';

    // LOGIN SISWA
    if ($role === 'siswa') {
        $stmt = $conn->prepare("SELECT * FROM siswa WHERE nis = ? LIMIT 1");
        $stmt->bind_param("s", $user);
        $stmt->execute();
        $s = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $login_ok = false;
        if ($s) {
            if (!empty($s['password'])) {
                $login_ok = password_verify($pw, $s['password']);
                if (!$login_ok && $pw === $s['nis']) {
                    $login_ok = true;
                    $h = password_hash($pw, PASSWORD_DEFAULT);
                    $conn->query("UPDATE siswa SET password='$h' WHERE id={$s['id']}");
                }
            } else {
                $login_ok = ($pw === $s['nis']);
                if ($login_ok) {
                    $h = password_hash($pw, PASSWORD_DEFAULT);
                    $conn->query("UPDATE siswa SET password='$h' WHERE id={$s['id']}");
                }
            }
        }
        if ($login_ok) {
            $_SESSION['siswa_id']    = $s['id'];
            $_SESSION['siswa_nis']   = $s['nis'];
            $_SESSION['siswa_nama']  = $s['nama'];
            $_SESSION['siswa_kelas'] = $s['kelas'];
            header('Location: '.BASE_URL.'portal_siswa.php'); exit;
        }
        $error = 'NIS atau password salah! (Password default = NIS Anda)';

    // LOGIN WALI (PIN)
    } else {
        $all_wali = $conn->query("SELECT * FROM wali");
        $found_wali = null;
        while ($row = $all_wali->fetch_assoc()) {
            if (!empty($row['pin']) && password_verify($pw, $row['pin'])) {
                $found_wali = $row; break;
            }
            if (!$found_wali && password_verify($pw, $row['password'])) {
                $found_wali = $row; break;
            }
        }
        if ($found_wali) {
            $_SESSION['wali_id']   = $found_wali['id'];
            $_SESSION['wali_nama'] = $found_wali['nama'];
            $_SESSION['wali_user'] = $found_wali['username'];
            header('Location: '.BASE_URL.'portal_wali.php'); exit;
        }
        $error = 'PIN atau Password salah!';
    }
}

$pengaturan = get_pengaturan();

// Label & warna per role
$cfg = [
    'siswa' => [
        'title'    => 'Portal Siswa',
        'icon'     => 'fa-user-graduate',
        'gradient' => 'linear-gradient(135deg,#6366f1,#4f46e5)',
        'btn_bg'   => 'linear-gradient(135deg,#6366f1,#4f46e5)',
    ],
    'wali' => [
        'title'    => 'Portal Wali Kelas',
        'icon'     => 'fa-chalkboard-teacher',
        'gradient' => 'linear-gradient(135deg,#7c3aed,#6d28d9)',
        'btn_bg'   => 'linear-gradient(135deg,#7c3aed,#6d28d9)',
    ],
];
$c = $cfg[$role] ?? $cfg['siswa'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $c['title'] ?> – <?= htmlspecialchars($pengaturan['nama_sekolah'] ?? '') ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Segoe UI',system-ui,sans-serif;background:<?= $c['gradient'] ?>;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
    .card{background:white;border-radius:24px;width:100%;max-width:420px;box-shadow:0 30px 80px rgba(0,0,0,.3);overflow:hidden}
    .card-top{background:<?= $c['gradient'] ?>;padding:32px 28px;text-align:center;color:white}
    .card-top .logo{width:72px;height:72px;border-radius:50%;object-fit:cover;border:3px solid rgba(255,255,255,.4);margin-bottom:14px}
    .card-top .logo-icon{width:72px;height:72px;border-radius:50%;background:rgba(255,255,255,.15);display:inline-flex;align-items:center;justify-content:center;font-size:2rem;margin-bottom:14px;border:3px solid rgba(255,255,255,.3)}
    .card-top h1{font-size:1.1rem;font-weight:800;margin-bottom:4px}
    .card-top p{font-size:.8rem;opacity:.75}
    .badge-role{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.15);border-radius:20px;padding:5px 14px;font-size:.78rem;font-weight:700;margin-top:10px;border:1px solid rgba(255,255,255,.25)}
    .card-body{padding:32px 28px}
    .error{background:#fef2f2;color:#991b1b;border:1px solid #fecaca;border-radius:10px;padding:10px 14px;font-size:.85rem;font-weight:600;margin-bottom:18px;display:flex;align-items:center;gap:8px}
    .form-group{margin-bottom:16px}
    .form-label{display:block;font-size:.82rem;font-weight:700;color:#374151;margin-bottom:6px}
    .form-control{width:100%;padding:11px 14px;border:2px solid #e2e8f0;border-radius:10px;font-size:.9rem;outline:none;transition:.2s}
    .form-control:focus{border-color:#6366f1}
    .pw-wrap{position:relative}
    .pw-wrap .form-control{padding-right:40px}
    .pw-toggle{position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#64748b}
    .btn-submit{width:100%;padding:13px;border:none;border-radius:12px;background:<?= $c['btn_bg'] ?>;color:white;font-size:1rem;font-weight:800;cursor:pointer;transition:.2s;margin-top:6px}
    .btn-submit:hover{opacity:.9;transform:translateY(-1px)}
    .hint{text-align:center;font-size:.76rem;color:#94a3b8;margin-top:12px}
    .back-link{display:block;text-align:center;margin-top:16px;color:#64748b;font-size:.8rem;text-decoration:none}
    .back-link:hover{color:#1e3a8a}
    /* PIN */
    .pin-label{font-size:.82rem;font-weight:700;color:#374151;margin-bottom:10px;display:flex;align-items:center;gap:6px}
    .pin-dots{display:flex;justify-content:center;gap:14px;margin-bottom:20px;padding:16px;background:#f8fafc;border-radius:14px;border:2px solid #e2e8f0}
    .pin-dots.shake{animation:shake .4s}
    @keyframes shake{0%,100%{transform:translateX(0)}20%,60%{transform:translateX(-6px)}40%,80%{transform:translateX(6px)}}
    .pin-dot{width:18px;height:18px;border-radius:50%;border:2.5px solid #cbd5e1;background:transparent;transition:.2s}
    .pin-dot.filled{background:#7c3aed;border-color:#7c3aed;transform:scale(1.1)}
    .pin-pad{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;max-width:240px;margin:0 auto}
    .pin-btn{padding:15px 10px;border:2px solid #e2e8f0;border-radius:12px;background:white;font-size:1.3rem;font-weight:700;color:#1e293b;cursor:pointer;transition:.15s;display:flex;align-items:center;justify-content:center;-webkit-tap-highlight-color:transparent}
    .pin-btn:hover{background:#f1f5f9;border-color:#cbd5e1}
    .pin-btn:active{transform:scale(.93);background:#e2e8f0}
    .pin-btn.del{color:#ef4444;font-size:1.1rem}
    .pin-btn.empty{border:none;background:transparent;cursor:default;pointer-events:none}
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
            <div class="logo-icon"><i class="fas <?= $c['icon'] ?>"></i></div>
        <?php endif; ?>
        <h1><?= htmlspecialchars($pengaturan['nama_sekolah'] ?? 'Sistem Absensi') ?></h1>
        <p>Sistem Absensi Digital</p>
        <div class="badge-role"><i class="fas <?= $c['icon'] ?>"></i> <?= $c['title'] ?></div>
    </div>
    <div class="card-body">
        <?php if ($error): ?>
        <div class="error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" id="loginForm" onsubmit="return doSubmit()">
            <input type="hidden" name="role" value="<?= $role ?>">
            <input type="hidden" name="username" id="usernameHidden">
            <input type="hidden" name="password" id="pwHidden">

            <?php if ($role === 'siswa'): ?>
            <!-- SISWA: NIS + Password -->
            <div class="form-group">
                <label class="form-label"><i class="fas fa-id-card"></i> NIS</label>
                <input type="text" id="nisInput" class="form-control" placeholder="Nomor Induk Siswa" autocomplete="off">
                <small style="color:#94a3b8;font-size:.75rem;margin-top:4px;display:block">Password default = NIS Anda</small>
            </div>
            <div class="form-group">
                <label class="form-label"><i class="fas fa-lock"></i> Password</label>
                <div class="pw-wrap">
                    <input type="password" id="pwInput" class="form-control" placeholder="Password">
                    <button type="button" onclick="togglePw()" class="pw-toggle"><i class="fas fa-eye" id="eyeIcon"></i></button>
                </div>
            </div>
            <button type="submit" class="btn-submit"><i class="fas fa-sign-in-alt"></i> Masuk sebagai Siswa</button>

            <?php else: ?>
            <!-- WALI: PIN -->
            <div class="pin-label"><i class="fas fa-hashtag" style="color:#7c3aed"></i> Masukkan PIN 4 Digit</div>
            <div class="pin-dots" id="pinDots">
                <span class="pin-dot" id="wd0"></span>
                <span class="pin-dot" id="wd1"></span>
                <span class="pin-dot" id="wd2"></span>
                <span class="pin-dot" id="wd3"></span>
            </div>
            <div class="pin-pad">
                <?php foreach(['1','2','3','4','5','6','7','8','9','','0','del'] as $k): ?>
                <?php if($k===''):?><button type="button" class="pin-btn empty"></button>
                <?php elseif($k==='del'):?><button type="button" class="pin-btn del" onclick="pinDel()"><i class="fas fa-backspace"></i></button>
                <?php else:?><button type="button" class="pin-btn" onclick="pinEnter('<?=$k?>')"><?=$k?></button>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <div class="hint"><i class="fas fa-info-circle"></i> PIN default: <strong>1234</strong></div>
            <?php if ($error): ?>
            <script>
            document.addEventListener('DOMContentLoaded',function(){
                document.getElementById('pinDots').classList.add('shake');
                setTimeout(function(){document.getElementById('pinDots').classList.remove('shake');},500);
            });
            </script>
            <?php endif; ?>
            <?php endif; ?>
        </form>


    </div>
</div>
<script>
var waliPin=''; var PIN_LEN=4;
function pinEnter(d){
    if(waliPin.length>=PIN_LEN)return;
    waliPin+=d; updateDots();
    if(waliPin.length===PIN_LEN){
        setTimeout(function(){
            document.getElementById('pwHidden').value=waliPin;
            document.getElementById('loginForm').submit();
        },180);
    }
}
function pinDel(){if(!waliPin.length)return;waliPin=waliPin.slice(0,-1);updateDots();}
function updateDots(){
    for(var i=0;i<PIN_LEN;i++){
        var d=document.getElementById('wd'+i);
        if(d)d.className='pin-dot'+(i<waliPin.length?' filled':'');
    }
}
function doSubmit(){
    var role='<?= $role ?>';
    if(role==='siswa'){
        document.getElementById('usernameHidden').value=document.getElementById('nisInput').value;
        document.getElementById('pwHidden').value=document.getElementById('pwInput').value;
        return true;
    }
    return false;
}
function togglePw(){
    var p=document.getElementById('pwInput'),i=document.getElementById('eyeIcon');
    p.type=p.type==='password'?'text':'password';
    i.className=p.type==='password'?'fas fa-eye':'fas fa-eye-slash';
}
document.addEventListener('keydown',function(e){
    if('<?= $role ?>'!=='wali')return;
    if(document.activeElement&&document.activeElement.tagName==='INPUT')return;
    if(e.key>='0'&&e.key<='9')pinEnter(e.key);
    if(e.key==='Backspace'){e.preventDefault();pinDel();}
});
</script>
<?php include 'includes/bottom_nav_mobile.php'; ?>
</body>
</html>
