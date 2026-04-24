<?php
require_once 'includes/config.php';

// Harus login sebagai kepsek
if (!isset($_SESSION['kepsek_id'])) { header('Location: portal_kepsek_login.php'); exit; }

$kepsek_id = $_SESSION['kepsek_id'];
$msg = '';
$msg_type = '';

// Cek kolom kepsek_pin ada
$kepsek_pin_col = false;
$chk = $conn->query("SHOW COLUMNS FROM admin LIKE 'kepsek_pin'");
if ($chk && $chk->num_rows > 0) $kepsek_pin_col = true;

// Proses ganti PIN
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pin_baru    = $_POST['pin_baru'] ?? '';
    $pin_konfirm = $_POST['pin_konfirm'] ?? '';

    if (strlen($pin_baru) !== 4 || !ctype_digit($pin_baru)) {
        $msg = 'PIN harus 4 digit angka!'; $msg_type = 'danger';
    } elseif ($pin_baru !== $pin_konfirm) {
        $msg = 'Konfirmasi PIN tidak cocok!'; $msg_type = 'danger';
    } else {
        if (!$kepsek_pin_col) {
            $conn->query("ALTER TABLE admin ADD COLUMN kepsek_pin VARCHAR(255) NULL DEFAULT NULL");
            $kepsek_pin_col = true;
        }
        $hashed = password_hash($pin_baru, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE admin SET kepsek_pin=? WHERE id=?");
        $stmt->bind_param("si", $hashed, $kepsek_id);
        $stmt->execute(); $stmt->close();
        $msg = 'PIN Kepala Sekolah berhasil disimpan!';
        $msg_type = 'success';
    }
}

// Ambil data kepsek
$kepsek = $conn->query("SELECT * FROM admin WHERE id=$kepsek_id")->fetch_assoc();
$pengaturan = get_pengaturan();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola PIN - Portal Kepala Sekolah</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    *{margin:0;padding:0;box-sizing:border-box}
    body{font-family:'Segoe UI',sans-serif;background:linear-gradient(135deg,#1e1b4b,#312e81,#1e1b4b);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
    .wrap{width:100%;max-width:420px}
    .header{text-align:center;margin-bottom:24px}
    .icon-wrap{width:72px;height:72px;background:linear-gradient(135deg,#7c3aed,#5b21b6);border-radius:18px;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;font-size:1.8rem;color:white;box-shadow:0 8px 24px rgba(124,58,237,.4)}
    .header h1{color:white;font-size:1.2rem;font-weight:800;margin-bottom:4px}
    .header p{color:#a5b4fc;font-size:.82rem}
    .card{background:rgba(255,255,255,.07);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,.12);border-radius:20px;padding:30px}
    .status-badge{display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border-radius:20px;font-size:.8rem;font-weight:700;margin-bottom:22px}
    .badge-set{background:rgba(52,211,153,.15);border:1px solid rgba(52,211,153,.3);color:#6ee7b7}
    .badge-unset{background:rgba(251,191,36,.12);border:1px solid rgba(251,191,36,.25);color:#fcd34d}
    .section-title{color:#c4b5fd;font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;margin-bottom:14px;display:flex;align-items:center;gap:6px}
    /* PIN Input */
    .pin-dots-row{display:flex;justify-content:center;gap:14px;margin-bottom:20px;padding:16px;background:rgba(255,255,255,.05);border-radius:14px;border:2px solid rgba(255,255,255,.1)}
    .pin-dot{width:22px;height:22px;border-radius:50%;border:2.5px solid rgba(255,255,255,.25);background:transparent;transition:.2s}
    .pin-dot.filled{background:#7c3aed;border-color:#a78bfa;transform:scale(1.1);box-shadow:0 0 10px rgba(124,58,237,.5)}
    .pin-pad{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;max-width:260px;margin:0 auto 8px}
    .pin-btn{padding:14px 10px;border:2px solid rgba(255,255,255,.12);border-radius:12px;background:rgba(255,255,255,.05);font-size:1.25rem;font-weight:700;color:white;cursor:pointer;transition:.15s;display:flex;align-items:center;justify-content:center}
    .pin-btn:hover{background:rgba(124,58,237,.3);border-color:#7c3aed;transform:translateY(-1px)}
    .pin-btn:active{transform:scale(.95)}
    .pin-btn.del{color:#f87171;font-size:1rem}
    .pin-btn.empty{border:none;background:transparent;cursor:default;pointer-events:none}
    .step-label{text-align:center;font-size:.8rem;color:#a5b4fc;margin-bottom:12px;min-height:20px}
    .btn-submit{width:100%;padding:13px;background:linear-gradient(135deg,#7c3aed,#5b21b6);color:white;border:none;border-radius:12px;font-size:.95rem;font-weight:700;cursor:pointer;transition:.2s;margin-top:6px;display:flex;align-items:center;justify-content:center;gap:8px;opacity:.4;pointer-events:none}
    .btn-submit.ready{opacity:1;pointer-events:auto}
    .btn-submit:hover.ready{opacity:.9;transform:translateY(-1px)}
    .alert{border-radius:12px;padding:12px 16px;font-size:.85rem;margin-bottom:18px;display:flex;align-items:center;gap:8px}
    .alert-success{background:rgba(52,211,153,.12);border:1px solid rgba(52,211,153,.25);color:#6ee7b7}
    .alert-danger{background:rgba(248,113,113,.12);border:1px solid rgba(248,113,113,.25);color:#fca5a5}
    .back-link{text-align:center;margin-top:20px}
    .back-link a{color:#a5b4fc;font-size:.83rem;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
    .back-link a:hover{color:white}
    </style>
</head>
<body>
<div class="wrap">
    <div class="header">
        <div class="icon-wrap"><i class="fas fa-key"></i></div>
        <h1>Kelola PIN Saya</h1>
        <p>Atur PIN untuk login Portal Kepala Sekolah</p>
    </div>

    <div class="card">
        <?php if ($msg): ?>
        <div class="alert alert-<?= $msg_type ?>">
            <i class="fas fa-<?= $msg_type==='success'?'check-circle':'exclamation-circle' ?>"></i>
            <?= htmlspecialchars($msg) ?>
        </div>
        <?php endif; ?>

        <div style="text-align:center;margin-bottom:20px">
            <?php if (!empty($kepsek['kepsek_pin'])): ?>
                <span class="status-badge badge-set"><i class="fas fa-check-circle"></i> PIN Kepsek sudah diatur</span>
            <?php else: ?>
                <span class="status-badge badge-unset"><i class="fas fa-exclamation-triangle"></i> Belum ada PIN Kepsek — pakai password lama</span>
            <?php endif; ?>
        </div>

        <form method="POST" id="pinForm">
            <input type="hidden" name="pin_baru" id="pinBaru">
            <input type="hidden" name="pin_konfirm" id="pinKonfirm">

            <div class="section-title"><i class="fas fa-hashtag"></i> <?= $step===2?'Ulangi PIN Baru':'Masukkan PIN Baru' ?></div>

            <div class="step-label" id="stepLabel">Langkah 1: Masukkan PIN baru (4 digit)</div>
            <div class="pin-dots-row" id="pinDisplay">
                <span class="pin-dot" id="d0"></span>
                <span class="pin-dot" id="d1"></span>
                <span class="pin-dot" id="d2"></span>
                <span class="pin-dot" id="d3"></span>
            </div>
            <div class="pin-pad">
                <button type="button" class="pin-btn" onclick="enter('1')">1</button>
                <button type="button" class="pin-btn" onclick="enter('2')">2</button>
                <button type="button" class="pin-btn" onclick="enter('3')">3</button>
                <button type="button" class="pin-btn" onclick="enter('4')">4</button>
                <button type="button" class="pin-btn" onclick="enter('5')">5</button>
                <button type="button" class="pin-btn" onclick="enter('6')">6</button>
                <button type="button" class="pin-btn" onclick="enter('7')">7</button>
                <button type="button" class="pin-btn" onclick="enter('8')">8</button>
                <button type="button" class="pin-btn" onclick="enter('9')">9</button>
                <button type="button" class="pin-btn empty"></button>
                <button type="button" class="pin-btn" onclick="enter('0')">0</button>
                <button type="button" class="pin-btn del" onclick="del()"><i class="fas fa-backspace"></i></button>
            </div>

            <button type="submit" class="btn-submit" id="btnSimpan">
                <i class="fas fa-save"></i> Simpan PIN
            </button>
        </form>
    </div>

    <div class="back-link">
        <a href="portal_kepsek.php"><i class="fas fa-arrow-left"></i> Kembali ke Portal</a>
    </div>
</div>
<script>
var step = 1; // 1=input PIN baru, 2=konfirmasi
var pin1 = '';
var pin2 = '';
var current = '';
var PIN_LEN = 4;

function enter(d) {
    if (current.length >= PIN_LEN) return;
    current += d;
    updateDots();
    if (current.length === PIN_LEN) {
        setTimeout(function(){
            if (step === 1) {
                pin1 = current;
                current = '';
                step = 2;
                updateDots();
                document.getElementById('stepLabel').textContent = 'Langkah 2: Ulangi PIN untuk konfirmasi';
                // flash dots
                var disp = document.getElementById('pinDisplay');
                disp.style.borderColor = '#7c3aed';
                setTimeout(function(){ disp.style.borderColor = ''; }, 600);
            } else {
                pin2 = current;
                if (pin1 === pin2) {
                    document.getElementById('pinBaru').value = pin1;
                    document.getElementById('pinKonfirm').value = pin2;
                    document.getElementById('btnSimpan').classList.add('ready');
                    document.getElementById('stepLabel').textContent = '✅ PIN cocok! Klik Simpan PIN.';
                } else {
                    // Tidak cocok — reset
                    current = '';
                    pin1 = '';
                    pin2 = '';
                    step = 1;
                    updateDots();
                    document.getElementById('stepLabel').textContent = '❌ PIN tidak cocok, ulangi dari awal.';
                    var disp = document.getElementById('pinDisplay');
                    disp.style.borderColor = '#f87171';
                    setTimeout(function(){ disp.style.borderColor=''; document.getElementById('stepLabel').textContent='Langkah 1: Masukkan PIN baru (4 digit)'; }, 1200);
                }
            }
        }, 150);
    }
}

function del() {
    if (!current.length) return;
    current = current.slice(0,-1);
    updateDots();
}

function updateDots() {
    for (var i=0; i<PIN_LEN; i++) {
        var d = document.getElementById('d'+i);
        d.className = 'pin-dot' + (i < current.length ? ' filled' : '');
    }
}

document.getElementById('pinForm').addEventListener('submit', function(e){
    var p1 = document.getElementById('pinBaru').value;
    var p2 = document.getElementById('pinKonfirm').value;
    if (!p1 || !p2 || p1 !== p2) { e.preventDefault(); return false; }
});

document.addEventListener('keydown', function(e){
    if (e.key>='0'&&e.key<='9') enter(e.key);
    if (e.key==='Backspace') { e.preventDefault(); del(); }
});
</script>
</body>
</html>
