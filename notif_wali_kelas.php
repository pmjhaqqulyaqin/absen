<?php
require_once 'includes/config.php';
cek_wali();

$wid  = $_SESSION['wali_id'];
$wali = $conn->query("SELECT * FROM wali WHERE id=$wid")->fetch_assoc();
$pengaturan = get_pengaturan();
$kelas_wali = $wali['kelas_wali'] ?? '';

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: '.BASE_URL.'portal_login.php?role=wali');
    exit;
}

$msg  = '';
$today = date('Y-m-d');

// ── SIMPAN ABSENSI ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_absen_wali'])) {
    $tgl     = sanitize($_POST['tanggal']);
    $ids     = $_POST['siswa_id']   ?? [];
    $stats   = $_POST['status']     ?? [];
    $kets    = $_POST['keterangan'] ?? [];
    $now     = date('H:i:s');
    $jam_terlambat = $pengaturan['jam_terlambat'] ?? '07:30:00';
    $count   = 0;
    foreach ($ids as $i => $sid) {
        $sid = (int)$sid;
        $st  = sanitize($stats[$i] ?? 'Hadir');
        $ket = sanitize($kets[$i]  ?? '');
        $s   = $conn->query("SELECT * FROM siswa WHERE id=$sid AND kelas='$kelas_wali' LIMIT 1")->fetch_assoc();
        if (!$s) continue;
        if ($st === 'Hadir' && $tgl === $today && $now > $jam_terlambat) $st = 'Terlambat';
        $jam_sql = in_array($st, ['Hadir','Terlambat']) ? "'$now'" : "NULL";
        $conn->query("INSERT INTO absensi (siswa_id,nis,nama,kelas,tanggal,jam_masuk,status,keterangan,metode)
            VALUES ($sid,'{$s['nis']}','{$s['nama']}','$kelas_wali','$tgl',$jam_sql,'$st','$ket','Manual-Wali')
            ON DUPLICATE KEY UPDATE status='$st',keterangan='$ket',metode='Manual-Wali'");
        $count++;
    }
    $msg = "success:$count absensi berhasil disimpan";
}

// ── TAMBAH CATATAN ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_catatan'])) {
    $sid  = (int)$_POST['siswa_id'];
    $tipe = sanitize($_POST['tipe']);
    $jdl  = sanitize($_POST['judul']);
    $isi  = sanitize($_POST['isi']);
    $conn->query("INSERT INTO catatan (siswa_id,wali_id,tipe,judul,isi) VALUES ($sid,$wid,'$tipe','$jdl','$isi')");
    $msg = 'success:Catatan berhasil disimpan';
}
if (isset($_GET['del_cat'])) {
    $cid = (int)$_GET['del_cat'];
    $conn->query("DELETE FROM catatan WHERE id=$cid AND wali_id=$wid");
    $msg = 'success:Catatan dihapus';
}

// ── MODE ──────────────────────────────────────────────────────────────────
$mode     = $_GET['mode']  ?? '';
$view_sid = (int)($_GET['siswa'] ?? 0);

$tipe_colors = ['Informasi'=>'#3b82f6','Peringatan'=>'#f59e0b','Urgent'=>'#ef4444','Apresiasi'=>'#10b981'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Portal Wali – <?= htmlspecialchars($wali['nama']) ?></title>
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
body{background:linear-gradient(135deg,#0f172a,#064e3b);min-height:100vh;padding:20px;}
.wrap{max-width:1020px;margin:0 auto;}
.topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;color:rgba(255,255,255,.85);}
.siswa-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:14px;}
.siswa-item{background:#fff;border-radius:14px;padding:18px;cursor:pointer;transition:.2s;box-shadow:0 4px 16px rgba(0,0,0,.15);}
.siswa-item:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.2);}
.sBtn{border:2px solid #e2e8f0;border-radius:8px;padding:6px 10px;font-size:.8rem;font-weight:700;cursor:pointer;background:#fff;transition:.15s;}
.sBtn.aH{background:#dcfce7;border-color:#16a34a;color:#15803d;}
.sBtn.aT{background:#fef3c7;border-color:#d97706;color:#92400e;}
.sBtn.aA{background:#fee2e2;border-color:#dc2626;color:#b91c1c;}
.sBtn.aS{background:#dbeafe;border-color:#2563eb;color:#1e40af;}
.sBtn.aI{background:#ede9fe;border-color:#7c3aed;color:#5b21b6;}
.sBtn.aB{background:#ffedd5;border-color:#ea580c;color:#9a3412;}
</style>
</head>
<body>
<div class="wrap">

<!-- TOPBAR -->
<div class="topbar">
    <div>
        <div style="font-size:.78rem;opacity:.65"><i class="fas fa-school"></i> <?= htmlspecialchars($pengaturan['nama_sekolah']) ?></div>
        <div style="font-weight:700;font-size:1.05rem"><i class="fas fa-chalkboard-teacher"></i> <?= htmlspecialchars($wali['nama']) ?>
        <?php if ($kelas_wali): ?>
        <span style="background:rgba(255,255,255,.15);border-radius:20px;padding:2px 10px;font-size:.75rem;margin-left:6px"><?= $kelas_wali ?></span>
        <?php endif; ?></div>
    </div>
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <?php if ($kelas_wali): ?>
        <a href="portal_wali.php?mode=absen" class="btn btn-sm" style="background:#16a34a;color:#fff">
            <i class="fas fa-clipboard-check"></i> Input Absensi
        </a>
        <?php endif; ?>
        <a href="?logout=1" class="btn btn-sm" style="background:rgba(255,255,255,.15);color:#fff">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<?php if ($msg): [$t,$tx]=explode(':',$msg,2); ?>
<div class="alert alert-<?= $t ?>" style="margin-bottom:14px"><i class="fas fa-check-circle"></i> <?= $tx ?></div>
<?php endif; ?>

<?php
/* ═══════════════════════════════════════════
   MODE ABSEN MANUAL
═══════════════════════════════════════════ */
if ($mode === 'absen'):
    if (!$kelas_wali): ?>
    <div class="card" style="text-align:center;padding:40px;color:#64748b">
        Kelas Anda belum diatur. Hubungi admin.
    </div>
    <?php else:
    $tgl_absen = sanitize($_GET['tgl'] ?? $today);
    // Ambil semua siswa di kelas wali
    $res = $conn->query("SELECT s.*, a.status cur_status, a.keterangan cur_ket, a.metode cur_metode
        FROM siswa s
        LEFT JOIN absensi a ON a.siswa_id=s.id AND a.tanggal='$tgl_absen'
        WHERE s.kelas='$kelas_wali' AND s.aktif=1 ORDER BY s.nama");
    $siswa_list = [];
    while ($r = $res->fetch_assoc()) $siswa_list[] = $r;
    ?>

    <div style="display:flex;gap:8px;align-items:center;margin-bottom:14px;flex-wrap:wrap">
        <a href="portal_wali.php" class="btn btn-sm" style="background:rgba(255,255,255,.15);color:#fff;border-color:rgba(255,255,255,.3)">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
        <h3 style="color:#fff;margin:0"><i class="fas fa-clipboard-check"></i> Input Absensi – Kelas <?= $kelas_wali ?></h3>
    </div>

    <!-- Pilih Tanggal -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
                <input type="hidden" name="mode" value="absen">
                <div>
                    <label class="form-label">Tanggal</label>
                    <input type="date" name="tgl" class="form-control" value="<?= $tgl_absen ?>" max="<?= $today ?>">
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Tampilkan</button>
            </form>
        </div>
    </div>

    <?php if (empty($siswa_list)): ?>
    <div class="card" style="text-align:center;padding:40px;color:#64748b">
        Belum ada siswa di kelas <?= $kelas_wali ?>. Hubungi admin.
    </div>
    <?php else: ?>

    <!-- Legenda -->
    <div class="card mb-3" style="padding:12px 16px">
        <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;font-size:.78rem">
            <b style="color:#475569">Status:</b>
            <span class="sBtn aH">✅ Hadir</span>
            <span class="sBtn aT">⏰ Terlambat</span>
            <span class="sBtn aA">❌ Alpa</span>
            <span class="sBtn aS">🏥 Sakit</span>
            <span class="sBtn aI">📋 Izin</span>
            <span class="sBtn aB">🚫 Bolos</span>
        </div>
    </div>

    <form method="POST">
        <input type="hidden" name="save_absen_wali" value="1">
        <input type="hidden" name="tanggal" value="<?= $tgl_absen ?>">
    <div class="card">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
            <span><i class="fas fa-users" style="color:var(--primary)"></i>
            <?= count($siswa_list) ?> Siswa – <?= format_tanggal($tgl_absen) ?></span>
            <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-save"></i> Simpan</button>
        </div>
        <div class="table-container">
            <table style="font-size:.84rem">
                <thead>
                    <tr><th>#</th><th>Nama Siswa</th><th style="text-align:center">Status</th><th>Keterangan</th></tr>
                </thead>
                <tbody>
                <?php
                $kodeMap = ['Hadir'=>'H','Terlambat'=>'T','Alpa'=>'A','Sakit'=>'S','Izin'=>'I','Bolos'=>'B'];
                foreach ($siswa_list as $i => $s):
                    $cur  = $s['cur_status'] ?: 'Hadir';
                    $kode = $kodeMap[$cur] ?? 'H';
                    $ket  = $s['cur_ket'] ?? '';
                    $met  = $s['cur_metode'] ?? '';
                ?>
                <tr style="<?= $i%2?'background:#f8fafc':'' ?>">
                    <td style="color:#64748b"><?= $i+1 ?></td>
                    <td>
                        <input type="hidden" name="siswa_id[]" value="<?= $s['id'] ?>">
                        <input type="hidden" name="status[]"   id="st<?= $i ?>" value="<?= $cur ?>">
                        <b><?= htmlspecialchars($s['nama']) ?></b>
                        <?php if ($met): ?><br><small style="color:#94a3b8"><?= $met ?></small><?php endif; ?>
                    </td>
                    <td>
                        <div style="display:flex;gap:3px;justify-content:center;flex-wrap:wrap">
                        <?php foreach (['H'=>['Hadir','✅'],'T'=>['Terlambat','⏰'],'A'=>['Alpa','❌'],'S'=>['Sakit','🏥'],'I'=>['Izin','📋'],'B'=>['Bolos','🚫']] as $k=>[$lbl,$ico]): ?>
                        <button type="button" id="b<?= $i ?>_<?= $k ?>"
                            class="sBtn <?= $kode===$k?'a'.$k:'' ?>"
                            onclick="setSt(<?= $i ?>,'<?= $k ?>','<?= $lbl ?>')"
                            title="<?= $lbl ?>">
                            <?= $ico ?>
                        </button>
                        <?php endforeach; ?>
                        </div>
                    </td>
                    <td>
                        <input type="text" name="keterangan[]" class="form-control"
                            value="<?= htmlspecialchars($ket) ?>"
                            placeholder="Keterangan..." style="min-width:120px;font-size:.82rem">
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div style="padding:12px 16px;border-top:1px solid #e2e8f0;display:flex;justify-content:flex-end">
            <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Simpan <?= count($siswa_list) ?> Absensi</button>
        </div>
    </div>
    </form>
    <script>
    var LBL = {H:'Hadir',T:'Terlambat',A:'Alpa',S:'Sakit',I:'Izin',B:'Bolos'};
    function setSt(i,k,l){
        document.getElementById('st'+i).value=LBL[k];
        ['H','T','A','S','I','B'].forEach(function(x){
            var b=document.getElementById('b'+i+'_'+x);
            if(b) b.className='sBtn'+(x===k?' a'+x:'');
        });
    }
    </script>
    <?php endif; endif;

/* ═══════════════════════════════════════════
   MODE DETAIL SISWA
═══════════════════════════════════════════ */
elseif ($view_sid):
    // Validasi: siswa harus di kelas wali ini
    $det = $conn->query("SELECT * FROM siswa WHERE id=$view_sid AND kelas='$kelas_wali' LIMIT 1")->fetch_assoc();
    if (!$det): ?>
    <div class="card" style="text-align:center;padding:40px;color:#64748b">Siswa tidak ditemukan.</div>
    <?php else:
    $st_res = $conn->query("SELECT status,COUNT(*) t FROM absensi WHERE siswa_id=$view_sid GROUP BY status")->fetch_all(MYSQLI_ASSOC);
    $st = ['Hadir'=>0,'Terlambat'=>0,'Alpa'=>0,'Sakit'=>0,'Izin'=>0,'Bolos'=>0,'total'=>0];
    foreach ($st_res as $r){ $st[$r['status']]=$r['t']; $st['total']+=$r['t']; }
    $pct = $st['total']>0 ? round(($st['Hadir']+$st['Terlambat'])/$st['total']*100,1) : 0;
    $absen_det = $conn->query("SELECT * FROM absensi WHERE siswa_id=$view_sid ORDER BY tanggal DESC LIMIT 60");
    $cat_det   = $conn->query("SELECT c.*,COALESCE(w.nama,'Admin') as dari FROM catatan c LEFT JOIN wali w ON w.id=c.wali_id WHERE c.siswa_id=$view_sid ORDER BY c.created_at DESC");
    ?>
    <div style="margin-bottom:14px">
        <a href="portal_wali.php" class="btn btn-sm" style="background:rgba(255,255,255,.15);color:#fff;border-color:rgba(255,255,255,.3)">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card mb-3" style="padding:22px">
        <div style="display:flex;align-items:center;gap:18px;flex-wrap:wrap">
            <?php if (!empty($det['foto'])&&file_exists('uploads/foto/'.$det['foto'])): ?>
                <img src="<?= BASE_URL ?>uploads/foto/<?= $det['foto'] ?>" style="width:75px;height:75px;border-radius:50%;object-fit:cover;border:3px solid var(--primary)">
            <?php else: ?>
                <div style="width:75px;height:75px;border-radius:50%;background:var(--primary);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.8rem;font-weight:700"><?= strtoupper(substr($det['nama'],0,1)) ?></div>
            <?php endif; ?>
            <div>
                <h2><?= htmlspecialchars($det['nama']) ?></h2>
                <span class="badge" style="background:#eff6ff;color:var(--primary)">NIS: <?= $det['nis'] ?></span>
                <span class="badge" style="background:#f0fdf4;color:#15803d;margin-left:4px">Kelas: <?= $det['kelas'] ?></span>
            </div>
            <div style="margin-left:auto;text-align:center">
                <div style="font-size:2rem;font-weight:800;color:<?= $pct>=80?'#16a34a':($pct>=60?'#d97706':'#dc2626') ?>"><?= $pct ?>%</div>
                <div style="font-size:.73rem;color:#64748b">Kehadiran</div>
            </div>
        </div>
        <div style="display:grid;grid-template-columns:repeat(6,1fr);gap:8px;margin-top:18px;text-align:center">
            <?php foreach (['Hadir'=>'#16a34a','Terlambat'=>'#d97706','Sakit'=>'#0891b2','Izin'=>'#7c3aed','Alpa'=>'#64748b','Bolos'=>'#dc2626'] as $ss=>$c): ?>
            <div style="padding:10px;background:#f8fafc;border-radius:8px;border-top:3px solid <?= $c ?>">
                <div style="font-weight:800;font-size:1.1rem;color:<?= $c ?>"><?= $st[$ss] ?></div>
                <div style="font-size:.68rem;color:#64748b"><?= $ss ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Tambah catatan -->
    <div class="card mb-3">
        <div class="card-header"><i class="fas fa-sticky-note" style="color:var(--warning)"></i> Tambah Catatan</div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="siswa_id" value="<?= $view_sid ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Tipe</label>
                        <select name="tipe" class="form-select">
                            <option>Informasi</option><option>Peringatan</option><option>Urgent</option><option>Apresiasi</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Judul *</label>
                        <input type="text" name="judul" class="form-control" required placeholder="Judul catatan">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Isi *</label>
                    <textarea name="isi" class="form-control" rows="3" required></textarea>
                </div>
                <button type="submit" name="save_catatan" class="btn btn-success"><i class="fas fa-save"></i> Simpan</button>
            </form>
        </div>
    </div>

    <?php if ($cat_det->num_rows>0): ?>
    <div class="card mb-3">
        <div class="card-header"><i class="fas fa-comments"></i> Catatan</div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:10px">
        <?php while ($c=$cat_det->fetch_assoc()): $clr=$tipe_colors[$c['tipe']]??'#64748b'; ?>
            <div style="border-left:4px solid <?= $clr ?>;padding:10px 14px;background:#f8fafc;border-radius:8px;display:flex;gap:10px">
                <div style="flex:1">
                    <span style="background:<?= $clr ?>;color:#fff;padding:2px 8px;border-radius:20px;font-size:.7rem;font-weight:600"><?= $c['tipe'] ?></span>
                    <strong style="margin-left:6px"><?= htmlspecialchars($c['judul']) ?></strong>
                    <p style="margin:4px 0 0;font-size:.83rem;color:#475569"><?= nl2br(htmlspecialchars($c['isi'])) ?></p>
                    <small style="color:#94a3b8">dari <?= $c['dari'] ?> · <?= date('d/m/Y H:i',strtotime($c['created_at'])) ?></small>
                </div>
                <?php if ($c['wali_id']==$wid): ?>
                <a href="?siswa=<?= $view_sid ?>&del_cat=<?= $c['id'] ?>" onclick="return confirm('Hapus?')" style="color:#dc2626"><i class="fas fa-trash"></i></a>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header"><i class="fas fa-calendar-alt" style="color:var(--primary)"></i> Riwayat Absensi</div>
        <div class="table-container">
            <table><thead><tr><th>No</th><th>Hari</th><th>Tanggal</th><th>Status</th><th>Jam</th></tr></thead>
            <tbody>
            <?php if ($absen_det->num_rows===0): ?>
            <tr><td colspan="5" style="text-align:center;padding:30px;color:#94a3b8">Belum ada data</td></tr>
            <?php else: $n=0; while ($r=$absen_det->fetch_assoc()): $n++; ?>
            <tr>
                <td><?= $n ?></td>
                <td><?= ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'][date('w',strtotime($r['tanggal']))] ?></td>
                <td><?= date('d/m/Y',strtotime($r['tanggal'])) ?></td>
                <td><?= get_status_badge($r['status']) ?></td>
                <td><?= $r['jam_masuk'] ? date('H:i',strtotime($r['jam_masuk'])) : '-' ?></td>
            </tr>
            <?php endwhile; endif; ?>
            </tbody></table>
        </div>
    </div>
    <?php endif;

/* ═══════════════════════════════════════════
   DAFTAR SISWA (HOME)
═══════════════════════════════════════════ */
else:
    $siswa_all = $kelas_wali
        ? $conn->query("SELECT * FROM siswa WHERE kelas='$kelas_wali' AND aktif=1 ORDER BY nama")
        : null;
?>
    <h3 style="color:#fff;margin-bottom:14px">
        <i class="fas fa-users"></i> Siswa Kelas <?= $kelas_wali ?: '–' ?>
    </h3>

    <?php if (!$kelas_wali): ?>
    <div class="card" style="text-align:center;padding:50px;color:#64748b">
        <i class="fas fa-exclamation-circle fa-2x" style="opacity:.4;display:block;margin-bottom:10px"></i>
        Kelas belum diatur. Hubungi admin untuk mengatur kelas Anda.
    </div>
    <?php elseif ($siswa_all->num_rows===0): ?>
    <div class="card" style="text-align:center;padding:50px;color:#64748b">
        <i class="fas fa-users fa-2x" style="opacity:.3;display:block;margin-bottom:10px"></i>
        Belum ada siswa di kelas <?= $kelas_wali ?>.
    </div>
    <?php else: ?>
    <div class="siswa-grid">
    <?php while ($s=$siswa_all->fetch_assoc()):
        $ab_today = $conn->query("SELECT status FROM absensi WHERE siswa_id={$s['id']} AND tanggal='$today'")->fetch_assoc();
        $alpa     = $conn->query("SELECT COUNT(*) c FROM absensi WHERE siswa_id={$s['id']} AND status='Alpa'")->fetch_assoc()['c'];
        $total_ab = $conn->query("SELECT COUNT(*) c FROM absensi WHERE siswa_id={$s['id']}")->fetch_assoc()['c'];
        $pct2     = $total_ab>0 ? round(($total_ab-$alpa)/$total_ab*100) : 100;
    ?>
    <a href="?siswa=<?= $s['id'] ?>" style="text-decoration:none">
    <div class="siswa-item">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
            <?php if (!empty($s['foto'])&&file_exists('uploads/foto/'.$s['foto'])): ?>
            <img src="<?= BASE_URL ?>uploads/foto/<?= $s['foto'] ?>" style="width:44px;height:44px;border-radius:50%;object-fit:cover">
            <?php else: ?>
            <div style="width:44px;height:44px;border-radius:50%;background:var(--primary);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:1rem"><?= strtoupper(substr($s['nama'],0,1)) ?></div>
            <?php endif; ?>
            <div style="flex:1;min-width:0">
                <div style="font-weight:700;font-size:.88rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($s['nama']) ?></div>
                <div style="font-size:.72rem;color:#64748b"><?= $s['nis'] ?></div>
            </div>
            <?php if ($alpa>=3): ?>
            <span style="background:#fef3c7;color:#92400e;border-radius:50%;width:20px;height:20px;display:inline-flex;align-items:center;justify-content:center;font-size:.7rem">⚠️</span>
            <?php endif; ?>
        </div>
        <div style="font-size:.76rem;margin-bottom:8px">
            Hari ini: <?= $ab_today ? get_status_badge($ab_today['status']) : '<span class="badge" style="background:#f1f5f9;color:#64748b">Belum absen</span>' ?>
        </div>
        <div style="height:5px;background:#e2e8f0;border-radius:3px">
            <div style="width:<?= $pct2 ?>%;height:100%;background:<?= $pct2>=80?'#16a34a':($pct2>=60?'#d97706':'#dc2626') ?>;border-radius:3px"></div>
        </div>
        <div style="font-size:.7rem;color:#64748b;margin-top:4px"><?= $pct2 ?>% kehadiran</div>
    </div>
    </a>
    <?php endwhile; ?>
    </div>
    <?php endif; endif; ?>

</div>
</body>
</html>
