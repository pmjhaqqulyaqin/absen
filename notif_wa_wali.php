<?php
require_once 'includes/config.php';
cek_login();

// ── Pastikan KEDUA kolom no_hp dan no_wa ada ─────────────────────────
$conn->query("ALTER TABLE wali ADD COLUMN IF NOT EXISTS no_hp VARCHAR(20) DEFAULT ''");
$conn->query("ALTER TABLE wali ADD COLUMN IF NOT EXISTS no_wa VARCHAR(20) DEFAULT ''");

$pengaturan  = get_pengaturan();
$hari_indo   = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
$bulan_indo  = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$hari_ini    = $hari_indo[date('w')];
$tgl_ini     = date('j').' '.$bulan_indo[(int)date('n')].' '.date('Y');

// Jadwal absen default Senin-Sabtu
$jadwal_hari = ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];

// ── Ambil semua wali – gabung no_hp dan no_wa ─────────────────────────
$wali_all = $conn->query("SELECT * FROM wali ORDER BY nama");
$wali_arr = [];
while ($w = $wali_all->fetch_assoc()) {
    // Gunakan no_hp jika ada, fallback ke no_wa
    $w['_nomor'] = !empty($w['no_hp']) ? $w['no_hp'] : ($w['no_wa'] ?? '');
    $wali_arr[] = $w;
}
$ada_wa = count(array_filter($wali_arr, fn($w) => !empty($w['_nomor'])));

$template_default = "🔔 *PENGINGAT REKAP ABSENSI SISWA*\n\nYth. Bapak/Ibu {{nama}},\n\nJangan lupa download & cetak rekap kehadiran dan poin anak-anak kita per bulan sebagai bahan laporan Bapak/Ibu mengenai perkembangan anak-anak kita selama di Madrasah. Atas perhatiannya terima kasih.\n\nKLIK LINK : https://loginku.xyz/index.php\nPIN : {{pin}}\n\nSemoga kegiatan hari ini berjalan lancar 🙏\nWassalamu'alaikum w.wb";

include 'includes/header.php';
?>

<div class="page-header d-flex align-center">
    <div>
        <div class="page-title"><i class="fab fa-whatsapp" style="color:#25d366"></i> Notifikasi WA — Wali Kelas</div>
        <div class="page-subtitle">Kirim pengingat WhatsApp ke wali kelas secara manual · <?= $tgl_ini ?></div>
    </div>
</div>

<!-- STATUS -->
<div class="card" style="margin-bottom:16px;border-left:4px solid #25d366">
    <div class="card-body" style="padding:14px 20px;display:flex;align-items:center;gap:12px">
        <i class="fab fa-whatsapp fa-2x" style="color:#25d366"></i>
        <div style="flex:1">
            <div style="font-weight:700;color:#166534">✅ Mode WA Manual — Klik tombol WA untuk membuka WhatsApp langsung</div>
            <div style="font-size:.8rem;color:#64748b;margin-top:2px">
                Total wali: <strong><?= count($wali_arr) ?></strong> &nbsp;|&nbsp;
                Ada nomor WA: <strong style="color:#16a34a"><?= $ada_wa ?></strong> &nbsp;|&nbsp;
                Tanpa nomor WA: <strong style="color:#dc2626"><?= count($wali_arr)-$ada_wa ?></strong>
            </div>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start">

<!-- KOLOM KIRI: Template + Kirim Semua -->
<div>
    <!-- Template Pesan -->
    <div class="card" style="margin-bottom:16px">
        <div class="card-header">
            <i class="fas fa-comment-alt" style="color:#4f46e5"></i> Template Pesan
            <small style="font-weight:400;color:#64748b;margin-left:8px">
                Variabel: <code>{{nama}}</code> <code>{{pin}}</code>
            </small>
        </div>
        <div class="card-body">
            <textarea id="templatePesan" rows="11" style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:12px;font-size:.875rem;font-family:inherit;outline:none;resize:vertical;transition:.2s"
                onfocus="this.style.borderColor='#6366f1'"
                onblur="this.style.borderColor='#e2e8f0'"><?= htmlspecialchars($template_default) ?></textarea>
        </div>
    </div>

    <!-- Kirim ke Semua -->
    <div class="card">
        <div class="card-header"><i class="fas fa-paper-plane" style="color:#25d366"></i> Kirim ke Semua Wali</div>
        <div class="card-body">
            <div style="padding:12px;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;font-size:.82rem;color:#92400e;margin-bottom:14px">
                <i class="fas fa-info-circle"></i> <strong>Cara penggunaan:</strong>
                <ol style="margin:8px 0 0 18px;line-height:2">
                    <li>Edit template pesan jika diperlukan</li>
                    <li>Klik tombol <strong style="color:#25d366">WA</strong> per wali, atau gunakan tombol bawah</li>
                    <li>WhatsApp terbuka → pesan terisi otomatis → klik Kirim</li>
                </ol>
            </div>
            <?php if ($ada_wa > 0): ?>
            <button type="button" onclick="bukaSemuaWA()"
                    style="width:100%;padding:12px;border-radius:8px;border:none;background:#25d366;color:white;font-weight:700;cursor:pointer;font-size:.9rem;display:flex;align-items:center;justify-content:center;gap:8px">
                <i class="fab fa-whatsapp fa-lg"></i> Buka Semua WA Sekaligus (<?= $ada_wa ?> wali)
            </button>
            <div style="font-size:.73rem;color:#94a3b8;margin-top:6px;text-align:center">
                <i class="fas fa-exclamation-triangle"></i> Jika browser memblokir pop-up, izinkan atau gunakan tombol per-wali di kanan.
            </div>
            <?php else: ?>
            <div style="text-align:center;padding:16px;color:#94a3b8;font-size:.85rem">
                <i class="fas fa-exclamation-circle"></i> Belum ada wali dengan nomor WA.<br>
                <a href="wali.php" style="color:#4f46e5">Tambahkan nomor WA wali</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- KOLOM KANAN: Daftar Wali -->
<div>
    <div class="card">
        <div class="card-header">
            <i class="fas fa-users" style="color:#4f46e5"></i> Daftar Wali & Kirim Individual
            <a href="wali.php" style="margin-left:auto;font-size:.75rem;font-weight:400;color:#4f46e5;text-decoration:none">
                <i class="fas fa-edit"></i> Edit nomor WA
            </a>
        </div>
        <?php if (!$wali_arr): ?>
        <div style="padding:40px;text-align:center;color:#94a3b8">
            <i class="fas fa-users fa-2x" style="display:block;margin-bottom:12px;opacity:.3"></i>
            Belum ada wali kelas terdaftar.<br>
            <a href="wali.php" style="color:#4f46e5;font-size:.85rem">+ Tambah Wali</a>
        </div>
        <?php else: ?>
        <div style="overflow-y:auto;max-height:600px">
        <?php foreach($wali_arr as $w):
            $nomor   = $w['_nomor'];
            $has_wa  = !empty($nomor);
            $no_bersih = preg_replace('/[^0-9]/', '', $nomor);
            if ($no_bersih && substr($no_bersih, 0, 1) === '0') $no_bersih = '62'.substr($no_bersih, 1);
            if ($no_bersih && substr($no_bersih, 0, 2) !== '62') $no_bersih = '62'.$no_bersih;
        ?>
        <div style="display:flex;align-items:center;gap:12px;padding:12px 16px;border-bottom:1px solid #f1f5f9;transition:.15s"
             onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='white'">
            <div style="width:42px;height:42px;border-radius:50%;
                background:<?= $has_wa?'linear-gradient(135deg,#25d366,#128c7e)':'#e2e8f0' ?>;
                color:<?= $has_wa?'white':'#94a3b8' ?>;
                display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.95rem;flex-shrink:0">
                <?= strtoupper(mb_substr($w['nama'],0,1)) ?>
            </div>
            <div style="flex:1;min-width:0">
                <div style="font-weight:700;font-size:.875rem;margin-bottom:2px"><?= htmlspecialchars($w['nama']) ?></div>
                <div style="font-size:.75rem;color:#64748b">
                    <?php if ($has_wa): ?>
                    <i class="fab fa-whatsapp" style="color:#25d366"></i>
                    <span style="font-family:monospace"><?= htmlspecialchars($nomor) ?></span>
                    <?php else: ?>
                    <span style="color:#dc2626"><i class="fas fa-exclamation-circle"></i> Belum ada nomor WA</span>
                    <?php endif; ?>
                    &nbsp;·&nbsp; Kelas: <strong><?= htmlspecialchars($w['kelas_wali'] ?? '-') ?></strong>
                </div>
            </div>
            <?php if ($has_wa): ?>
            <button type="button"
                class="btn-wa-individual"
                data-hp="<?= htmlspecialchars($no_bersih) ?>"
                data-nama="<?= htmlspecialchars($w['nama']) ?>"
                data-pin="<?= htmlspecialchars($w['pin_plain'] ?? '') ?>"
                style="flex-shrink:0;padding:8px 16px;border-radius:8px;border:none;
                    background:#25d366;color:white;font-weight:700;cursor:pointer;
                    font-size:.8rem;display:flex;align-items:center;gap:6px;
                    box-shadow:0 2px 6px rgba(37,211,102,.3)"
                title="Kirim WA ke <?= htmlspecialchars($w['nama']) ?>">
                <i class="fab fa-whatsapp"></i> WA
            </button>
            <?php else: ?>
            <a href="wali.php?show=edit&edit_id=<?= $w['id'] ?>"
               style="flex-shrink:0;padding:7px 12px;border-radius:8px;border:1px solid #e2e8f0;
                   color:#64748b;font-size:.78rem;text-decoration:none;font-weight:600">
                <i class="fas fa-edit"></i> Isi No
            </a>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

</div><!-- /grid -->

<script>
// ── Data wali untuk JS ────────────────────────────────────────
var WALI_LIST = [
<?php foreach($wali_arr as $w):
    $nomor = $w['_nomor'];
    if (empty($nomor)) continue;
    $no_bersih = preg_replace('/[^0-9]/', '', $nomor);
    if ($no_bersih && substr($no_bersih,0,1)==='0') $no_bersih = '62'.substr($no_bersih,1);
    if ($no_bersih && substr($no_bersih,0,2)!=='62') $no_bersih = '62'.$no_bersih;
?>
    {nama: <?= json_encode($w['nama']) ?>, hp: '<?= $no_bersih ?>', pin: <?= json_encode($w['pin_plain'] ?? '') ?>},
<?php endforeach; ?>
];

// ── Buat Pesan ──────────────────────────────────────────────
function buatPesan(namaWali, pinWali) {
    return document.getElementById('templatePesan').value
        .replace(/\{\{nama\}\}/g, namaWali)
        .replace(/\{\{pin\}\}/g, pinWali || '-');
}

// ── Kirim WA Individual via data attributes ───────────────────
document.addEventListener('click', function(e) {
    var btn = e.target.closest('.btn-wa-individual');
    if (!btn) return;
    var hp   = btn.getAttribute('data-hp');
    var nama = btn.getAttribute('data-nama');
    var pin  = btn.getAttribute('data-pin');
    var url  = 'https://wa.me/' + hp + '?text=' + encodeURIComponent(buatPesan(nama, pin));
    window.open(url, '_blank');
});

function bukaWA(no_hp, nama, pin) {
    var url = 'https://wa.me/' + no_hp + '?text=' + encodeURIComponent(buatPesan(nama, pin));
    window.open(url, '_blank');
}

function bukaSemuaWA() {
    if (WALI_LIST.length === 0) { alert('Tidak ada wali dengan nomor WA.'); return; }
    if (!confirm('Buka ' + WALI_LIST.length + ' tab WhatsApp sekaligus?\n\nPastikan browser mengizinkan pop-up dari halaman ini.')) return;
    WALI_LIST.forEach(function(w, i) {
        setTimeout(function() {
            var url = 'https://wa.me/' + w.hp + '?text=' + encodeURIComponent(buatPesan(w.nama, w.pin));
            window.open(url, '_blank');
        }, i * 700);
    });
}
</script>

<?php include 'includes/footer.php'; ?>
