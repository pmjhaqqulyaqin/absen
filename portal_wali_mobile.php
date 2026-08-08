<?php
require_once '../includes/config.php';
cek_wali();

$wali       = $_SESSION['wali_nama']  ?? 'Wali Kelas';
$kelas      = $_SESSION['wali_kelas'] ?? '';
$foto_wali  = $_SESSION['wali_foto']  ?? '';
$pengaturan = get_pengaturan();
$sekolah    = $pengaturan['nama_sekolah'] ?? 'MA NW TOYA';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no">
<title>Portal Wali – <?= htmlspecialchars($kelas) ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* ─── RESET ─────────────────────────────── */
*{box-sizing:border-box;margin:0;padding:0;-webkit-tap-highlight-color:transparent}
:root{
  --P:#0D47A1;--PL:#1565C0;--PM:#1976D2;--A:#00ACC1;--AD:#00838F;
  --bg:#EEF2F7;--card:#fff;--txt:#1A2340;--mut:#607D8B;
  --nav:64px;--hdr:76px;--r:14px;
  --c-hadir:#1B5E20;--bg-hadir:#E8F5E9;
  --c-terlambat:#BF360C;--bg-terlambat:#FBE9E7;
  --c-alpa:#B71C1C;--bg-alpa:#FFEBEE;
  --c-sakit:#01579B;--bg-sakit:#E1F5FE;
  --c-izin:#4A148C;--bg-izin:#F3E5F5;
  --c-bolos:#E65100;--bg-bolos:#FFF3E0;
}
html,body{height:100%;background:var(--bg);color:var(--txt);font-family:-apple-system,'Segoe UI',Roboto,sans-serif;overflow-x:hidden;max-width:480px;margin:0 auto}

/* ─── HEADER ─────────────────────────────── */
.hdr{
  background:linear-gradient(135deg,var(--P) 0%,var(--PM) 55%,var(--AD) 100%);
  color:#fff;padding:12px 16px 14px;
  position:sticky;top:0;z-index:100;
  box-shadow:0 3px 20px rgba(13,71,161,.35);
}
.hdr-row{display:flex;align-items:center;gap:10px}
.wali-avatar{
  width:44px;height:44px;border-radius:50%;
  border:2.5px solid rgba(255,255,255,.5);
  object-fit:cover;background:rgba(255,255,255,.2);
  display:flex;align-items:center;justify-content:center;
  font-size:16px;font-weight:700;flex-shrink:0;overflow:hidden;
}
.wali-avatar img{width:100%;height:100%;object-fit:cover}
.hdr-info{flex:1;min-width:0}
.hdr-school{font-size:12px;opacity:.8;letter-spacing:.3px}
.hdr-name{font-size:15px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.hdr-kelas{font-size:11px;opacity:.75;margin-top:1px}
.clock-box{
  background:rgba(255,255,255,.18);border-radius:8px;
  padding:6px 10px;text-align:center;flex-shrink:0;
}
.clock-time{font-size:15px;font-weight:700;letter-spacing:.5px;line-height:1}
.clock-date{font-size:9.5px;opacity:.85;margin-top:2px;line-height:1}

/* Progress */
.prog-wrap{background:rgba(255,255,255,.2);border-radius:4px;height:4px;margin-top:10px;overflow:hidden}
.prog-fill{height:100%;background:#fff;border-radius:4px;transition:width .6s ease}
.prog-label{display:flex;justify-content:space-between;font-size:10px;opacity:.8;margin-top:4px}

/* ─── TABS ─────────────────────────────── */
.tab-bar{
  display:flex;background:#fff;
  border-bottom:1px solid #E0E8F0;
  position:sticky;top:var(--hdr);z-index:90;
  box-shadow:0 2px 8px rgba(0,0,0,.06);
}
.tab-btn{
  flex:1;padding:11px 4px 9px;border:none;background:none;
  font-size:11px;font-weight:600;color:var(--mut);cursor:pointer;
  display:flex;flex-direction:column;align-items:center;gap:3px;
  border-bottom:3px solid transparent;transition:all .2s;
}
.tab-btn i{font-size:16px}
.tab-btn.act{color:var(--PL);border-bottom-color:var(--PL)}

/* ─── MAIN ─────────────────────────────── */
.main{padding:12px 12px 80px}
.page{display:none}.page.act{display:block}

/* ─── STAT GRID ─────────────────────────── */
.stat-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px}
.stat-card{background:var(--card);border-radius:var(--r);padding:14px;
  display:flex;align-items:center;gap:10px;
  box-shadow:0 1px 8px rgba(21,101,192,.09)}
.stat-ico{width:42px;height:42px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0}
.stat-n{font-size:26px;font-weight:800;line-height:1}
.stat-l{font-size:11px;color:var(--mut);font-weight:500;margin-top:2px}
.s-hadir .stat-ico{background:var(--bg-hadir)} .s-hadir .stat-n{color:var(--c-hadir)}
.s-alpa  .stat-ico{background:var(--bg-alpa)}  .s-alpa  .stat-n{color:var(--c-alpa)}
.s-belum .stat-ico{background:#ECEFF1}          .s-belum .stat-n{color:#546E7A}
.s-total .stat-ico{background:#E3F2FD}          .s-total .stat-n{color:var(--PL)}

/* ─── SECTION TITLE ─────────────────────── */
.sec-ttl{font-size:11.5px;font-weight:700;color:var(--mut);letter-spacing:.8px;text-transform:uppercase;margin:14px 2px 8px}

/* ─── BERANDA STUDENT CARD ──────────────── */
.b-card{background:var(--card);border-radius:var(--r);padding:11px 13px;
  display:flex;align-items:center;gap:11px;margin-bottom:8px;
  box-shadow:0 1px 8px rgba(21,101,192,.08);transition:transform .15s}
.b-card:active{transform:scale(.98)}
.avatar{width:40px;height:40px;border-radius:50%;display:flex;align-items:center;
  justify-content:center;font-size:13px;font-weight:700;color:#fff;flex-shrink:0}
.b-info{flex:1;min-width:0}
.b-name{font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.b-meta{font-size:10.5px;color:var(--mut);margin-top:2px}
.bdg{font-size:10.5px;font-weight:700;padding:3px 9px;border-radius:20px;white-space:nowrap;flex-shrink:0}
.bdg-hadir    {background:var(--bg-hadir);   color:var(--c-hadir)}
.bdg-terlambat{background:var(--bg-terlambat);color:var(--c-terlambat)}
.bdg-alpa     {background:var(--bg-alpa);    color:var(--c-alpa)}
.bdg-sakit    {background:var(--bg-sakit);   color:var(--c-sakit)}
.bdg-izin     {background:var(--bg-izin);    color:var(--c-izin)}
.bdg-bolos    {background:var(--bg-bolos);   color:var(--c-bolos)}
.bdg-belum    {background:#ECEFF1;           color:#546E7A}

/* ─── TOOLBAR ───────────────────────────── */
.toolbar{background:var(--card);border-radius:var(--r);padding:12px;margin-bottom:12px;
  box-shadow:0 1px 8px rgba(21,101,192,.09)}
.toolbar-top{display:flex;align-items:center;gap:8px;margin-bottom:10px}
.toolbar-top label{font-size:12px;font-weight:600;color:var(--mut);white-space:nowrap}
input[type=date]{
  flex:1;border:1.5px solid #D0DCF0;border-radius:9px;
  padding:7px 10px;font-size:13px;color:var(--txt);
  background:var(--bg);outline:none;min-width:0;
}
input[type=date]:focus{border-color:var(--PL)}
.btn-tampil{
  background:var(--PL);color:#fff;border:none;border-radius:9px;
  padding:7px 14px;font-size:12px;font-weight:700;cursor:pointer;
  display:flex;align-items:center;gap:5px;flex-shrink:0;
}
.bulk-row{display:flex;flex-wrap:wrap;gap:7px}
.bulk-btn{
  border:none;border-radius:20px;padding:6px 12px;
  font-size:11px;font-weight:700;cursor:pointer;
  display:flex;align-items:center;gap:5px;transition:all .15s;
}
.bulk-btn:active{transform:scale(.96)}
.bulk-hadir   {background:var(--bg-hadir);   color:var(--c-hadir)}
.bulk-terlambat{background:var(--bg-terlambat);color:var(--c-terlambat)}
.bulk-alpa    {background:var(--bg-alpa);    color:var(--c-alpa)}

/* ─── ABSEN MANUAL CARD ─────────────────── */
.m-card{background:var(--card);border-radius:var(--r);margin-bottom:10px;
  box-shadow:0 1px 8px rgba(21,101,192,.08);overflow:hidden}
.m-top{padding:11px 13px;display:flex;align-items:center;gap:10px;border-bottom:1px solid #F0F4F8}
.m-num{width:24px;height:24px;border-radius:50%;background:var(--PL);color:#fff;
  font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.m-info{flex:1;min-width:0}
.m-name{font-size:13.5px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.m-nis{font-size:10.5px;color:var(--mut);margin-top:2px}
.m-cur-badge{font-size:10px;font-weight:700;padding:3px 8px;border-radius:20px;flex-shrink:0;white-space:nowrap}

/* Status buttons */
.m-btns{display:grid;grid-template-columns:repeat(3,1fr);gap:1px;background:#F0F4F8}
.s-btn{
  border:none;padding:10px 4px;font-size:11px;font-weight:700;cursor:pointer;
  background:var(--card);display:flex;flex-direction:column;align-items:center;gap:4px;
  transition:all .15s;position:relative;
}
.s-btn:active{opacity:.7}
.s-btn .s-ico{font-size:16px}
.s-btn.sel-hadir    {background:var(--c-hadir);   color:#fff}
.s-btn.sel-terlambat{background:var(--c-terlambat);color:#fff}
.s-btn.sel-alpa     {background:var(--c-alpa);    color:#fff}
.s-btn.sel-sakit    {background:var(--c-sakit);   color:#fff}
.s-btn.sel-izin     {background:var(--c-izin);    color:#fff}
.s-btn.sel-bolos    {background:var(--c-bolos);   color:#fff}
.s-btn.hadir   {color:var(--c-hadir)}
.s-btn.terlambat{color:var(--c-terlambat)}
.s-btn.alpa    {color:var(--c-alpa)}
.s-btn.sakit   {color:var(--c-sakit)}
.s-btn.izin    {color:var(--c-izin)}
.s-btn.bolos   {color:var(--c-bolos)}
.s-btn .ket-input{display:none;width:100%;font-size:10px;border:none;background:transparent;text-align:center;color:inherit;outline:none;margin-top:2px}

/* Keterangan inline */
.m-ket{padding:8px 13px;border-top:1px solid #F0F4F8;display:none}
.m-ket.show{display:block}
.m-ket input{
  width:100%;border:1.5px solid #D0DCF0;border-radius:8px;
  padding:7px 10px;font-size:12px;color:var(--txt);background:var(--bg);outline:none;
}
.m-ket input:focus{border-color:var(--PL)}

/* ─── SAVE BAR ───────────────────────────── */
.save-bar{
  position:fixed;bottom:0;left:50%;transform:translateX(-50%);
  width:100%;max-width:480px;
  background:var(--card);border-top:1px solid #E0E8F0;
  padding:10px 14px;z-index:200;
  box-shadow:0 -4px 20px rgba(13,71,161,.15);
}
.save-btn{
  width:100%;background:linear-gradient(135deg,var(--PL),var(--AD));
  color:#fff;border:none;border-radius:12px;padding:14px;
  font-size:15px;font-weight:700;cursor:pointer;
  display:flex;align-items:center;justify-content:center;gap:8px;
  box-shadow:0 4px 18px rgba(21,101,192,.35);transition:all .2s;
}
.save-btn:active{transform:scale(.98);opacity:.9}
.save-btn.loading{opacity:.7;pointer-events:none}

/* spinner */
.spin{display:inline-block;width:16px;height:16px;border:2.5px solid rgba(255,255,255,.4);
  border-top-color:#fff;border-radius:50%;animation:spin .7s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}

/* ─── TOAST ──────────────────────────────── */
#toastWrap{position:fixed;bottom:80px;left:50%;transform:translateX(-50%);
  width:90%;max-width:440px;z-index:999;pointer-events:none;
  display:flex;flex-direction:column;gap:8px}
.toast{
  background:#1A2340;color:#fff;border-radius:12px;
  padding:12px 16px;font-size:13px;font-weight:500;
  display:flex;align-items:center;gap:10px;
  box-shadow:0 4px 20px rgba(0,0,0,.25);
  animation:toastIn .3s ease;pointer-events:auto;
}
.toast.ok {background:#1B5E20} .toast.err{background:#B71C1C}
.toast.warn{background:#E65100}
@keyframes toastIn{from{opacity:0;transform:translateY(18px)}to{opacity:1;transform:translateY(0)}}

/* ─── REKAP PAGE ─────────────────────────── */
.filter-bar{background:var(--card);border-radius:var(--r);padding:12px;
  margin-bottom:12px;box-shadow:0 1px 8px rgba(21,101,192,.09)}
.filter-bar select{
  flex:1;border:1.5px solid #D0DCF0;border-radius:9px;
  padding:7px 10px;font-size:13px;color:var(--txt);
  background:var(--bg);outline:none;appearance:none;
}
.filter-row{display:flex;gap:8px;align-items:center}
.rekap-table-wrap{background:var(--card);border-radius:var(--r);
  box-shadow:0 1px 8px rgba(21,101,192,.09);overflow:hidden}
.rekap-table{width:100%;border-collapse:collapse;font-size:12px}
.rekap-table th{background:var(--P);color:#fff;padding:10px 8px;text-align:left;font-size:11.5px}
.rekap-table td{padding:10px 8px;border-bottom:1px solid #F0F4F8;vertical-align:middle}
.rekap-table tr:last-child td{border-bottom:none}
.rekap-table tr:nth-child(even) td{background:#F8FAFC}

/* ─── EMPTY STATE ────────────────────────── */
.empty{text-align:center;padding:48px 20px;color:var(--mut)}
.empty i{font-size:40px;display:block;margin-bottom:12px;opacity:.5}
.empty p{font-size:14px}

/* ─── LOGOUT LINK ────────────────────────── */
.logout-btn{display:flex;align-items:center;justify-content:center;gap:8px;
  background:none;border:1.5px solid #EF9A9A;border-radius:10px;
  color:#B71C1C;font-size:13px;font-weight:600;padding:12px;cursor:pointer;
  margin-top:16px;width:100%;transition:all .2s}
.logout-btn:active{background:#FFEBEE}
</style>
</head>
<body>

<!-- ════ HEADER ════ -->
<div class="hdr" id="topHdr">
  <div class="hdr-row">
    <div class="wali-avatar" id="waliAva">
      <?php if($foto_wali && file_exists("uploads/foto/$foto_wali")): ?>
        <img src="uploads/foto/<?= htmlspecialchars($foto_wali) ?>" alt="foto">
      <?php else: ?>
        <?= strtoupper(substr($wali,0,2)) ?>
      <?php endif; ?>
    </div>
    <div class="hdr-info">
      <div class="hdr-school"><?= htmlspecialchars($sekolah) ?></div>
      <div class="hdr-name"><?= htmlspecialchars($wali) ?></div>
      <div class="hdr-kelas">Wali Kelas <?= htmlspecialchars($kelas) ?></div>
    </div>
    <div class="clock-box">
      <div class="clock-time" id="clockTime">--:--</div>
      <div class="clock-date" id="clockDate">---</div>
    </div>
  </div>
  <div class="prog-wrap"><div class="prog-fill" id="progFill" style="width:0%"></div></div>
  <div class="prog-label">
    <span id="progText">Memuat data...</span>
    <span id="progPct">0%</span>
  </div>
</div>

<!-- ════ TAB BAR ════ -->
<div class="tab-bar">
  <button class="tab-btn act" id="tab-beranda" onclick="gotoTab('beranda')">
    <i class="fas fa-home"></i>Beranda
  </button>
  <button class="tab-btn" id="tab-manual" onclick="gotoTab('manual')">
    <i class="fas fa-pen-square"></i>Absen Manual
  </button>
  <button class="tab-btn" id="tab-edit" onclick="gotoTab('edit')">
    <i class="fas fa-edit"></i>Edit Absen
  </button>
  <button class="tab-btn" id="tab-rekap" onclick="gotoTab('rekap')">
    <i class="fas fa-chart-bar"></i>Rekap
  </button>
</div>

<!-- ════ MAIN CONTENT ════ -->
<div class="main">

  <!-- ══ BERANDA ══ -->
  <div class="page act" id="pg-beranda">
    <div class="stat-grid">
      <div class="stat-card s-hadir">
        <div class="stat-ico">✅</div>
        <div><div class="stat-n" id="st-hadir">-</div><div class="stat-l">Hadir</div></div>
      </div>
      <div class="stat-card s-alpa">
        <div class="stat-ico">❌</div>
        <div><div class="stat-n" id="st-tdkhadir">-</div><div class="stat-l">Tidak Hadir</div></div>
      </div>
      <div class="stat-card s-belum">
        <div class="stat-ico">🕐</div>
        <div><div class="stat-n" id="st-belum">-</div><div class="stat-l">Belum Absen</div></div>
      </div>
      <div class="stat-card s-total">
        <div class="stat-ico">👥</div>
        <div><div class="stat-n" id="st-total">-</div><div class="stat-l">Total Siswa</div></div>
      </div>
    </div>
    <div class="sec-ttl">Status Absensi Siswa Hari Ini</div>
    <div id="beranda-list"><div class="empty"><i class="fas fa-spinner fa-spin"></i><p>Memuat data siswa...</p></div></div>
  </div>

  <!-- ══ ABSEN MANUAL ══ -->
  <div class="page" id="pg-manual">
    <div class="toolbar">
      <div class="toolbar-top">
        <label><i class="fas fa-calendar-alt"></i></label>
        <input type="date" id="tgl-manual" value="<?= date('Y-m-d') ?>">
        <button class="btn-tampil" onclick="loadManual()"><i class="fas fa-sync-alt"></i> Tampil</button>
      </div>
      <div class="bulk-row">
        <button class="bulk-btn bulk-hadir"    onclick="bulkSet('Hadir')">    ✅ Semua Hadir</button>
        <button class="bulk-btn bulk-terlambat" onclick="bulkSet('Terlambat')">⏰ Semua Terlambat</button>
        <button class="bulk-btn bulk-alpa"     onclick="bulkSet('Alpa')">     ❌ Semua Alpa</button>
      </div>
    </div>
    <div id="manual-list"><div class="empty"><i class="fas fa-spinner fa-spin"></i><p>Memuat...</p></div></div>
  </div>

  <!-- ══ EDIT ABSEN ══ -->
  <div class="page" id="pg-edit">
    <div class="toolbar">
      <div class="toolbar-top">
        <label><i class="fas fa-calendar-alt"></i></label>
        <input type="date" id="tgl-edit" value="<?= date('Y-m-d') ?>">
        <button class="btn-tampil" onclick="loadEdit()"><i class="fas fa-sync-alt"></i> Tampil</button>
      </div>
    </div>
    <div id="edit-list"><div class="empty"><i class="fas fa-list"></i><p>Pilih tanggal lalu tekan Tampil</p></div></div>
  </div>

  <!-- ══ REKAP ══ -->
  <div class="page" id="pg-rekap">
    <div class="filter-bar">
      <div class="filter-row" style="margin-bottom:10px">
        <label style="font-size:12px;font-weight:600;color:var(--mut);white-space:nowrap;margin-right:8px">📅 Dari</label>
        <input type="date" id="rek-dari" value="<?= date('Y-m-01') ?>">
      </div>
      <div class="filter-row" style="margin-bottom:10px">
        <label style="font-size:12px;font-weight:600;color:var(--mut);white-space:nowrap;margin-right:8px">📅 Sampai</label>
        <input type="date" id="rek-sampai" value="<?= date('Y-m-d') ?>">
      </div>
      <button class="btn-tampil" style="width:100%;justify-content:center;padding:9px" onclick="loadRekap()">
        <i class="fas fa-search"></i> Tampilkan Rekap
      </button>
    </div>
    <div id="rekap-content"><div class="empty"><i class="fas fa-chart-bar"></i><p>Pilih rentang tanggal dan tekan Tampilkan</p></div></div>
    <button class="logout-btn" onclick="location.href='portal_wali_logout.php'">
      <i class="fas fa-sign-out-alt"></i> Keluar / Logout
    </button>
  </div>

</div><!-- /main -->

<!-- ════ SAVE BAR ════ -->
<div class="save-bar" id="saveBar" style="display:none">
  <button class="save-btn" id="saveBtn" onclick="simpanSemua()">
    <i class="fas fa-save"></i> Simpan Semua Absensi
  </button>
</div>

<div id="toastWrap"></div>

<!-- ════ SCRIPT ════ -->
<script>
// ── CONFIG ──────────────────────────────────────────────
const BASE   = ''; // kosong = relative path
const API_SISWA = BASE + 'ajax/portal_wali_siswa.php';
const API_ABSEN = BASE + 'ajax/portal_wali_absen.php';

// ── STATE ────────────────────────────────────────────────
let SISWA    = [];
let absenState = {};   // { id: { status, keterangan } }
let editState  = {};
let curTab   = 'beranda';

// ── AVATAR COLORS ─────────────────────────────────────────
const AVCOL = ['#0D47A1','#00838F','#1B5E20','#4A148C','#B71C1C','#BF360C','#004D40','#283593','#37474F','#6A1B9A'];
function avcol(i){ return AVCOL[i % AVCOL.length]; }
function initials(n){ const p=n.trim().split(' '); return p.length>=2?(p[0][0]+p[1][0]).toUpperCase():n.slice(0,2).toUpperCase(); }

// Status definitions
const STATUSES = [
  { key:'Hadir',     ico:'✅', lbl:'Hadir',      cls:'hadir'     },
  { key:'Terlambat', ico:'⏰', lbl:'Terlambat',  cls:'terlambat' },
  { key:'Alpa',      ico:'❌', lbl:'Alpa',       cls:'alpa'      },
  { key:'Sakit',     ico:'🤒', lbl:'Sakit',      cls:'sakit'     },
  { key:'Izin',      ico:'📋', lbl:'Izin',       cls:'izin'      },
  { key:'Bolos',     ico:'🚫', lbl:'Bolos',      cls:'bolos'     },
];

// ── CLOCK ────────────────────────────────────────────────
function tick(){
  const n=new Date(),p=x=>String(x).padStart(2,'0');
  const HARI=['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
  const BLN=['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'];
  document.getElementById('clockTime').textContent=`${p(n.getHours())}:${p(n.getMinutes())}:${p(n.getSeconds())}`;
  document.getElementById('clockDate').textContent=`${HARI[n.getDay()]} ${n.getDate()} ${BLN[n.getMonth()]}`;
}
setInterval(tick,1000); tick();

// ── TABS ─────────────────────────────────────────────────
function gotoTab(t){
  document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('act'));
  document.querySelectorAll('.page').forEach(p=>p.classList.remove('act'));
  document.getElementById('tab-'+t).classList.add('act');
  document.getElementById('pg-'+t).classList.add('act');
  curTab = t;
  const showSave = (t==='manual'||t==='edit');
  document.getElementById('saveBar').style.display = showSave ? 'block' : 'none';
  if(t==='beranda') loadBeranda();
  if(t==='manual')  loadManual();
  if(t==='edit')    loadEdit();
  window.scrollTo(0,0);
}

// ── API HELPERS ───────────────────────────────────────────
async function getSiswa(tgl){
  const r=await fetch(`${API_SISWA}?tanggal=${tgl}`);
  return r.json();
}
async function postAbsen(data){
  const fd=new FormData();
  Object.entries(data).forEach(([k,v])=>fd.append(k,v));
  const r=await fetch(API_ABSEN,{method:'POST',body:fd});
  return r.json();
}

// ── PROGRESS ─────────────────────────────────────────────
function updateProgress(siswaArr){
  const total = siswaArr.length;
  const done  = siswaArr.filter(s=>s.status && s.status!=='').length;
  const belum = total - done;
  const pct   = total ? Math.round(done/total*100) : 0;
  document.getElementById('progFill').style.width = pct+'%';
  document.getElementById('progPct').textContent  = pct+'%';
  document.getElementById('progText').textContent  = `${done}/${total} tercatat · ${belum} belum`;
  document.getElementById('st-hadir').textContent   = siswaArr.filter(s=>s.status==='Hadir').length;
  document.getElementById('st-tdkhadir').textContent= siswaArr.filter(s=>['Terlambat','Alpa','Sakit','Izin','Bolos'].includes(s.status)).length;
  document.getElementById('st-belum').textContent   = belum;
  document.getElementById('st-total').textContent   = total;
}

// ── BADGE HTML ────────────────────────────────────────────
function bdgHtml(s){
  if(!s) return '<span class="bdg bdg-belum">Belum</span>';
  const cls=s.toLowerCase();
  return `<span class="bdg bdg-${cls}">${s}</span>`;
}

// ── BERANDA ───────────────────────────────────────────────
async function loadBeranda(){
  const tgl = new Date().toISOString().slice(0,10);
  try{
    SISWA = await getSiswa(tgl);
    updateProgress(SISWA);
    const list = document.getElementById('beranda-list');
    if(!SISWA.length){ list.innerHTML='<div class="empty"><i class="fas fa-users-slash"></i><p>Tidak ada data siswa</p></div>'; return; }
    list.innerHTML = SISWA.map((s,i)=>`
      <div class="b-card">
        <div class="avatar" style="background:${avcol(i)}">${initials(s.nama)}</div>
        <div class="b-info">
          <div class="b-name">${s.nama}</div>
          <div class="b-meta">${s.nis}${s.jam_masuk?' · '+s.jam_masuk.slice(0,5):''}</div>
        </div>
        ${bdgHtml(s.status)}
      </div>
    `).join('');
  }catch(e){ console.error(e); toast('Gagal memuat data','err'); }
}

// ── ABSEN MANUAL ─────────────────────────────────────────
async function loadManual(){
  const tgl = document.getElementById('tgl-manual').value;
  const el  = document.getElementById('manual-list');
  el.innerHTML='<div class="empty"><i class="fas fa-spinner fa-spin"></i><p>Memuat...</p></div>';
  try{
    SISWA = await getSiswa(tgl);
    updateProgress(SISWA);
    absenState = {};
    SISWA.forEach(s=>{ absenState[s.id]={ status:s.status||null, keterangan:s.keterangan||'' }; });
    renderManual();
  }catch(e){ el.innerHTML='<div class="empty"><i class="fas fa-exclamation-circle"></i><p>Gagal memuat data</p></div>'; }
}

function renderManual(){
  const el=document.getElementById('manual-list');
  if(!SISWA.length){ el.innerHTML='<div class="empty"><i class="fas fa-users-slash"></i><p>Tidak ada siswa</p></div>'; return; }
  el.innerHTML = SISWA.map((s,i)=>{
    const cur = absenState[s.id]?.status;
    const ket = absenState[s.id]?.keterangan||'';
    const needKet = ['Sakit','Izin','Bolos'].includes(cur);
    return `
    <div class="m-card" id="mc-${s.id}">
      <div class="m-top">
        <div class="m-num">${i+1}</div>
        <div class="avatar" style="background:${avcol(i)};width:36px;height:36px;font-size:12px">${initials(s.nama)}</div>
        <div class="m-info">
          <div class="m-name">${s.nama}</div>
          <div class="m-nis">${s.nis}</div>
        </div>
        <span class="m-cur-badge ${cur?'bdg bdg-'+cur.toLowerCase():'bdg bdg-belum'}">${cur||'Belum'}</span>
      </div>
      <div class="m-btns">
        ${STATUSES.map(st=>`
          <button class="s-btn ${st.cls} ${cur===st.key?'sel-'+st.cls:''}"
            onclick="setSiswaStatus(${s.id},'${st.key}')">
            <span class="s-ico">${st.ico}</span>
            <span>${st.lbl}</span>
          </button>
        `).join('')}
      </div>
      <div class="m-ket ${needKet?'show':''}" id="ket-${s.id}">
        <input type="text" placeholder="Keterangan (opsional)..."
          value="${ket}" oninput="setKet(${s.id},this.value)"
          style="font-size:12px">
      </div>
    </div>`;
  }).join('');
}

function setSiswaStatus(id, status){
  if(!absenState[id]) absenState[id]={ status:null, keterangan:'' };
  absenState[id].status = status;
  // Update badge
  const card = document.getElementById('mc-'+id);
  if(!card) return;
  const badge = card.querySelector('.m-cur-badge');
  badge.className = `m-cur-badge bdg bdg-${status.toLowerCase()}`;
  badge.textContent = status;
  // Update buttons
  card.querySelectorAll('.s-btn').forEach(b=>{
    const bcls = b.classList[1];
    STATUSES.forEach(st=>{ if(st.cls===bcls){ b.className=`s-btn ${st.cls}${status===st.key?' sel-'+st.cls:''}`; } });
  });
  // Toggle keterangan
  const ketEl = document.getElementById('ket-'+id);
  if(['Sakit','Izin','Bolos'].includes(status)){ ketEl.classList.add('show'); ketEl.querySelector('input').focus(); }
  else{ ketEl.classList.remove('show'); }
}
function setKet(id,v){ if(absenState[id]) absenState[id].keterangan=v; }

function bulkSet(status){
  SISWA.forEach(s=>{ absenState[s.id]={ status, keterangan:'' }; });
  renderManual();
  toast(`Semua siswa ditandai ${status}`, 'ok');
}

// ── EDIT ABSEN ───────────────────────────────────────────
async function loadEdit(){
  const tgl = document.getElementById('tgl-edit').value;
  const el  = document.getElementById('edit-list');
  el.innerHTML='<div class="empty"><i class="fas fa-spinner fa-spin"></i><p>Memuat...</p></div>';
  try{
    const data = await getSiswa(tgl);
    editState={};
    data.forEach(s=>{ editState[s.id]={ status:s.status||null, keterangan:s.keterangan||'' }; });
    SISWA = data;
    if(!data.length){ el.innerHTML='<div class="empty"><i class="fas fa-users-slash"></i><p>Tidak ada siswa</p></div>'; return; }
    el.innerHTML = data.map((s,i)=>{
      const cur = editState[s.id]?.status;
      return `
      <div class="m-card" id="ec-${s.id}">
        <div class="m-top">
          <div class="m-num">${i+1}</div>
          <div class="avatar" style="background:${avcol(i)};width:36px;height:36px;font-size:12px">${initials(s.nama)}</div>
          <div class="m-info">
            <div class="m-name">${s.nama}</div>
            <div class="m-nis">${s.nis}${s.jam_masuk?' · '+s.jam_masuk.slice(0,5):''}</div>
          </div>
          <span class="m-cur-badge ${cur?'bdg bdg-'+cur.toLowerCase():'bdg bdg-belum'}" id="ebd-${s.id}">${cur||'Belum'}</span>
        </div>
        <div class="m-btns">
          ${STATUSES.map(st=>`
            <button class="s-btn ${st.cls} ${cur===st.key?'sel-'+st.cls:''}"
              onclick="setEditStatus(${s.id},'${st.key}')">
              <span class="s-ico">${st.ico}</span>
              <span>${st.lbl}</span>
            </button>
          `).join('')}
        </div>
      </div>`;
    }).join('');
  }catch(e){ el.innerHTML='<div class="empty"><i class="fas fa-exclamation-circle"></i><p>Gagal memuat data</p></div>'; }
}

function setEditStatus(id, status){
  if(!editState[id]) editState[id]={ status:null, keterangan:'' };
  editState[id].status = status;
  const card = document.getElementById('ec-'+id);
  if(!card) return;
  const badge = document.getElementById('ebd-'+id);
  badge.className = `m-cur-badge bdg bdg-${status.toLowerCase()}`;
  badge.textContent = status;
  card.querySelectorAll('.s-btn').forEach(b=>{
    const bcls = b.classList[1];
    STATUSES.forEach(st=>{ if(st.cls===bcls){ b.className=`s-btn ${st.cls}${status===st.key?' sel-'+st.cls:''}`; } });
  });
}

// ── SIMPAN SEMUA ─────────────────────────────────────────
async function simpanSemua(){
  const btn = document.getElementById('saveBtn');
  const state  = curTab==='manual' ? absenState : editState;
  const tgl    = curTab==='manual'
    ? document.getElementById('tgl-manual').value
    : document.getElementById('tgl-edit').value;

  const toSave = SISWA.filter(s=> state[s.id]?.status);
  if(!toSave.length){ toast('Belum ada status yang dipilih','warn'); return; }

  btn.classList.add('loading');
  btn.innerHTML='<span class="spin"></span> Menyimpan...';

  let ok=0, fail=0;
  for(const s of toSave){
    const st  = state[s.id];
    const jam = (st.status==='Hadir'||st.status==='Terlambat')
      ? new Date().toTimeString().slice(0,5) : '';
    try{
      const res = await postAbsen({
        aksi   : curTab==='edit' ? 'edit' : 'single',
        id_siswa: s.id,
        status : st.status,
        tanggal: tgl,
        jam_masuk: jam,
        keterangan: st.keterangan||''
      });
      if(res.success) ok++; else fail++;
    }catch(e){ fail++; }
  }

  btn.classList.remove('loading');
  btn.innerHTML='<i class="fas fa-save"></i> Simpan Semua Absensi';

  if(ok>0)   toast(`✅ ${ok} siswa berhasil disimpan`, 'ok');
  if(fail>0) toast(`❌ ${fail} siswa gagal disimpan`, 'err');
  if(curTab==='beranda'||ok>0) loadBeranda();
}

// ── REKAP ────────────────────────────────────────────────
async function loadRekap(){
  const dari   = document.getElementById('rek-dari').value;
  const sampai = document.getElementById('rek-sampai').value;
  const el     = document.getElementById('rekap-content');
  el.innerHTML ='<div class="empty"><i class="fas fa-spinner fa-spin"></i><p>Memuat rekap...</p></div>';
  try{
    const data = await getSiswa(sampai); // ambil data hari ini sebagai sampel
    if(!data.length){ el.innerHTML='<div class="empty"><i class="fas fa-chart-bar"></i><p>Tidak ada data</p></div>'; return; }
    // Tabel ringkasan
    el.innerHTML = `
      <div class="rekap-table-wrap">
        <table class="rekap-table">
          <thead><tr>
            <th>No</th><th>Nama Siswa</th><th>Hadir</th><th>Tlbt</th><th>Alpa</th><th>Sakit</th><th>Izin</th>
          </tr></thead>
          <tbody>
            ${data.map((s,i)=>`<tr>
              <td>${i+1}</td>
              <td><b>${s.nama}</b><br><span style="color:var(--mut);font-size:10px">${s.nis}</span></td>
              <td style="color:var(--c-hadir);font-weight:700">-</td>
              <td style="color:var(--c-terlambat)">-</td>
              <td style="color:var(--c-alpa)">-</td>
              <td style="color:var(--c-sakit)">-</td>
              <td style="color:var(--c-izin)">-</td>
            </tr>`).join('')}
          </tbody>
        </table>
      </div>
      <p style="font-size:11px;color:var(--mut);text-align:center;margin-top:10px">
        <i class="fas fa-info-circle"></i> Rekap detail tersedia di Laporan Rekap portal utama
      </p>`;
  }catch(e){ el.innerHTML='<div class="empty"><i class="fas fa-exclamation-circle"></i><p>Gagal memuat rekap</p></div>'; }
}

// ── TOAST ─────────────────────────────────────────────────
function toast(msg, type='info'){
  const w=document.getElementById('toastWrap');
  const el=document.createElement('div');
  el.className='toast '+(type==='ok'?'ok':type==='err'?'err':type==='warn'?'warn':'');
  el.textContent=msg;
  w.appendChild(el);
  setTimeout(()=>{ el.style.transition='opacity .3s'; el.style.opacity='0'; setTimeout(()=>el.remove(),300); },3000);
}

// ── INIT ─────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded',()=>{
  loadBeranda();
});
</script>
</body>
</html>
