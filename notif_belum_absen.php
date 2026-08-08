<?php
require_once 'includes/config.php';
cek_login();

// Pastikan kolom no_wa_ortu ada
$conn->query("ALTER TABLE siswa ADD COLUMN IF NOT EXISTS no_wa_ortu VARCHAR(20) DEFAULT ''");

$today      = date('Y-m-d');
$kelas      = sanitize($_GET['kelas'] ?? '');
$kelas_list = get_kelas_list();

$where = "aktif=1 AND id NOT IN (SELECT siswa_id FROM absensi WHERE tanggal='$today'" . periode_where($conn) . ")";
if ($kelas) $where .= " AND kelas='$kelas'";

$data  = $conn->query("SELECT * FROM siswa WHERE $where ORDER BY kelas, nama");
$rows  = [];
while ($r = $data->fetch_assoc()) $rows[] = $r;
$total      = count($rows);
$ada_wa     = count(array_filter($rows, fn($r) => !empty($r['no_wa_ortu'])));
$tanpa_wa   = $total - $ada_wa;

$pengaturan = get_pengaturan();
$tgl_fmt    = format_tanggal($today);
$jam_sekarang = date('H:i');

include 'includes/header.php';
?>

<div class="page-header d-flex align-center" style="flex-wrap:wrap;gap:10px;">
  <div>
    <div class="page-title"><i class="fab fa-whatsapp" style="color:#22c55e;"></i> Notifikasi WA — Belum Absen</div>
    <div class="page-subtitle"><?= $tgl_fmt ?> · Pukul <?= $jam_sekarang ?> WIB</div>
  </div>
  <div style="margin-left:auto;display:flex;gap:8px;flex-wrap:wrap;">
    <a href="belum_absen.php<?= $kelas?"?kelas=$kelas":'' ?>" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Kembali</a>
  </div>
</div>

<!-- Statistik -->
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:18px;">
  <div class="card" style="text-align:center;padding:16px;">
    <div style="font-size:2rem;font-weight:900;color:#dc2626;"><?= $total ?></div>
    <div style="font-size:.78rem;color:#64748b;font-weight:700;">Belum Absen</div>
  </div>
  <div class="card" style="text-align:center;padding:16px;">
    <div style="font-size:2rem;font-weight:900;color:#16a34a;"><?= $ada_wa ?></div>
    <div style="font-size:.78rem;color:#64748b;font-weight:700;">Ada No WA Ortu</div>
  </div>
  <div class="card" style="text-align:center;padding:16px;">
    <div style="font-size:2rem;font-weight:900;color:#94a3b8;"><?= $tanpa_wa ?></div>
    <div style="font-size:.78rem;color:#64748b;font-weight:700;">Tanpa No WA</div>
  </div>
</div>

<!-- Filter kelas -->
<div class="card mb-3">
  <div class="card-body">
    <form method="GET" class="filter-bar" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
      <select name="kelas" class="form-select" style="width:160px;">
        <option value="">Semua Kelas</option>
        <?php foreach ($kelas_list as $k): ?>
          <option value="<?= $k ?>" <?= $kelas==$k?'selected':'' ?>><?= $k ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Filter</button>
    </form>
  </div>
</div>

<!-- Template Pesan -->
<div class="card mb-3">
  <div class="card-body">
    <label style="font-size:.78rem;font-weight:700;color:#475569;display:block;margin-bottom:6px;">
      ✏️ TEMPLATE PESAN WA
      <small style="font-weight:400;text-transform:none;"> ({{nama}} {{kelas}} {{tanggal}} {{jam}})</small>
    </label>
    <textarea id="waTemplate" rows="5" style="width:100%;padding:10px;border:1.5px solid #e2e8f0;border-radius:8px;font-family:inherit;font-size:.85rem;resize:vertical;">Assalamu'alaikum Bapak/Ibu Orang Tua,

⚠️ *PERINGATAN BELUM ABSEN*
📌 Nama  : {{nama}}
🏫 Kelas : {{kelas}}
📅 Tgl   : {{tanggal}}
🕐 Jam   : {{jam}} WIB

Putra/putri Anda *BELUM TERCATAT* hadir di sekolah hari ini.

Mohon konfirmasi atau segera hubungi sekolah.
Terima kasih 🙏
<?= htmlspecialchars($pengaturan['nama_sekolah']??'Sekolah') ?></textarea>
  </div>
</div>

<!-- Tombol aksi -->
<div class="card mb-3">
  <div class="card-body" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
    <button class="btn btn-success" onclick="bukaSemuaWa()" style="background:#22c55e;">
      <i class="fab fa-whatsapp"></i> Buka Semua WA Sekaligus (<?= $ada_wa ?> ortu)
    </button>
    <button class="btn btn-primary" onclick="selectAll(true)">
      <i class="fas fa-check-square"></i> Pilih Semua
    </button>
    <button class="btn btn-secondary" onclick="selectAll(false)">
      <i class="fas fa-square"></i> Batal Pilih
    </button>
    <span id="selCount" style="font-size:.82rem;color:#64748b;font-weight:600;">0 dipilih</span>
  </div>
  <div id="hasilKirim" style="display:none;padding:10px 16px;border-top:1px solid #e2e8f0;font-size:.85rem;font-weight:700;"></div>
</div>

<!-- Tabel siswa belum absen -->
<div class="card">
  <div class="table-responsive">
    <table class="table" id="tblBelumAbsen">
      <thead>
        <tr>
          <th width="40"><input type="checkbox" id="chkAll" onclick="selectAll(this.checked)"></th>
          <th>#</th>
          <th>Nama Siswa</th>
          <th>Kelas</th>
          <th>No WA Ortu</th>
          <th>Kirim WA</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="6" style="text-align:center;padding:30px;color:#94a3b8;">
            🎉 Semua siswa sudah absen hari ini!
          </td></tr>
        <?php else: ?>
          <?php foreach ($rows as $i => $s): ?>
            <?php
              $noWa    = $s['no_wa_ortu'] ?? '';
              $nm      = $s['nama'];
              $kls     = $s['kelas'];
            ?>
            <tr>
              <td>
                <input type="checkbox" class="siChk" data-idx="<?= $i ?>"
                       <?= $noWa ? '' : 'disabled' ?>>
              </td>
              <td style="color:#64748b;"><?= $i+1 ?></td>
              <td style="font-weight:700;"><?= htmlspecialchars($nm) ?></td>
              <td><span class="badge" style="background:#f1f5f9;color:#475569;"><?= $kls ?></span></td>
              <td style="font-size:.8rem;color:<?= $noWa?'#475569':'#cbd5e1' ?>;">
                <?= $noWa ?: '<i>Belum diisi</i>' ?>
              </td>
              <td>
                <?php if ($noWa): ?>
                  <button class="btn btn-success btn-sm" onclick="bukaWaSatu(<?= $i ?>)"
                          style="background:#22c55e;font-size:.75rem;padding:4px 10px;">
                    <i class="fab fa-whatsapp"></i> Kirim
                  </button>
                <?php else: ?>
                  <a href="siswa.php" style="font-size:.72rem;color:#3b82f6;">+ Isi nomor</a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Data siswa untuk JS -->
<script>
var SISWA_DATA = <?= json_encode(array_map(function($s) {
    return [
        'nama'    => $s['nama'],
        'kelas'   => $s['kelas'],
        'no_wa'   => $s['no_wa_ortu'] ?? '',
    ];
}, $rows), JSON_UNESCAPED_UNICODE) ?>;

var TGL_FMT = "<?= $tgl_fmt ?>";
var JAM_NOW = "<?= $jam_sekarang ?>";

function getTemplate() {
    return document.getElementById('waTemplate').value.trim();
}

function buatPesan(idx) {
    var s   = SISWA_DATA[idx];
    var tmpl = getTemplate();
    return tmpl
        .replace(/{{nama}}/g,    s.nama)
        .replace(/{{kelas}}/g,   s.kelas)
        .replace(/{{tanggal}}/g, TGL_FMT)
        .replace(/{{jam}}/g,     JAM_NOW);
}

function buatLink(idx) {
    var s   = SISWA_DATA[idx];
    if (!s.no_wa) return null;
    var nomor = s.no_wa.replace(/\D/g,'');
    if (nomor.charAt(0)==='0') nomor='62'+nomor.substring(1);
    if (nomor.substring(0,2)!=='62') nomor='62'+nomor;
    return 'https://wa.me/'+nomor+'?text='+encodeURIComponent(buatPesan(idx));
}

function bukaWaSatu(idx) {
    var link = buatLink(idx);
    if (link) window.open(link, '_blank');
}

function bukaSemuaWa() {
    var checked = document.querySelectorAll('.siChk:checked');
    if (!checked.length) {
        // Jika tidak ada yang dicentang, buka semua yang ada nomor
        var semua = SISWA_DATA.map(function(s,i){return s.no_wa?i:null;}).filter(function(i){return i!==null;});
        if (!semua.length) { alert('Tidak ada siswa dengan nomor WA.'); return; }
        if (!confirm('Buka '+semua.length+' tab WA sekaligus?\n\nPastikan popup tidak diblokir.\n\nLanjutkan?')) return;
        semua.forEach(function(idx, i) {
            setTimeout(function(){ window.open(buatLink(idx),'_blank'); }, i*600);
        });
        var hasil = document.getElementById('hasilKirim');
        hasil.style.display=''; hasil.style.color='#16a34a';
        setTimeout(function(){ hasil.innerHTML = '✓ '+semua.length+' tab WA dibuka!'; }, semua.length*600+300);
        return;
    }
    if (!confirm('Buka '+checked.length+' tab WA?\n\nLanjutkan?')) return;
    Array.from(checked).forEach(function(cb, i) {
        var idx = parseInt(cb.dataset.idx);
        setTimeout(function(){ window.open(buatLink(idx),'_blank'); }, i*600);
    });
    var hasil = document.getElementById('hasilKirim');
    hasil.style.display=''; hasil.style.color='#16a34a';
    setTimeout(function(){ hasil.innerHTML='✓ '+checked.length+' tab WA dibuka!'; }, checked.length*600+300);
}

function selectAll(v) {
    document.querySelectorAll('.siChk:not([disabled])').forEach(function(c){ c.checked=v; });
    document.getElementById('chkAll').checked = v;
    updateCount();
}

function updateCount() {
    var n = document.querySelectorAll('.siChk:checked').length;
    document.getElementById('selCount').textContent = n + ' dipilih';
}

document.querySelectorAll('.siChk').forEach(function(c){
    c.addEventListener('change', updateCount);
});
</script>

<?php include 'includes/footer.php'; ?>
