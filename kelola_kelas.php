<?php
require_once 'includes/config.php';
cek_login();

$msg        = '';
$kelas_list = get_kelas_list();   // acuan rombel/kelas yang SUDAH ADA (tidak boleh input bebas)
$tingkat_list = get_tingkat_list(); // dari menu Kelola Tingkat

// ── PROSES PINDAH / NAIK KELAS ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pindah_kelas'])) {
    $ids          = $_POST['siswa_ids'] ?? [];
    $kelas_asal   = sanitize($_POST['kelas_asal'] ?? '');
    $kelas_tujuan = sanitize($_POST['kelas_tujuan'] ?? '');

    if (!$kelas_tujuan) {
        $msg = 'danger:Pilih kelas tujuan terlebih dahulu';
    } elseif (!in_array($kelas_tujuan, $kelas_list, true)) {
        // Pastikan tetap mengacu ke rombel/kelas yang sudah ada
        $msg = 'danger:Kelas tujuan tidak valid. Gunakan rombel yang sudah terdaftar (kelola di menu Kelola Siswa &raquo; Tambah Kelas)';
    } elseif ($kelas_tujuan === $kelas_asal) {
        $msg = 'danger:Kelas tujuan harus berbeda dari kelas asal';
    } elseif (empty($ids)) {
        $msg = 'danger:Pilih minimal 1 siswa yang akan dipindah / dinaikkan kelasnya';
    } else {
        // Jaring pengaman: pastikan kelas ASAL tetap terdaftar di tabel master
        // `kelas`, walau nanti SELURUH siswanya dipindah keluar (supaya kelas
        // asal tidak hilang dari daftar/dropdown di manapun, termasuk saat
        // Edit Siswa).
        if ($kelas_asal) {
            $ka_e = $conn->real_escape_string($kelas_asal);
            $conn->query("INSERT IGNORE INTO kelas (nama_kelas) VALUES ('$ka_e')");
        }

        $count      = 0;
        $today_pk   = date('Y-m-d');
        foreach ($ids as $sid) {
            $sid = (int)$sid;
            if (!$sid) continue;
            $s = $conn->query("SELECT id,nis,nama,kelas FROM siswa WHERE id=$sid")->fetch_assoc();
            if (!$s) continue;

            $conn->query("UPDATE siswa SET kelas='$kelas_tujuan' WHERE id=$sid");

            // Sinkronkan absensi HARI INI yang sudah terlanjur tercatat di kelas lama
            // supaya ikut pindah ke kelas tujuan (biar Edit Absensi / Input Absensi
            // tidak menampilkan data ganda/tertinggal di kelas asal).
            $conn->query("UPDATE absensi SET kelas='$kelas_tujuan'
                          WHERE siswa_id=$sid AND tanggal='$today_pk'" . periode_where($conn));

            $count++;
        }
        $msg = "success:$count siswa berhasil dipindahkan ke kelas $kelas_tujuan";
    }
}

// ── Kelas asal & kelas tujuan yang sedang dipilih ──
$kelas_asal_view   = sanitize($_GET['kelas_asal']   ?? ($_POST['kelas_asal']   ?? ''));
$kelas_tujuan_view = sanitize($_GET['kelas_tujuan'] ?? ($_POST['kelas_tujuan'] ?? ''));

// Jaring pengaman: kelas tujuan tidak boleh sama dengan kelas asal
// (mis. saat parameter lama masih terbawa di URL setelah ganti kelas asal)
if ($kelas_tujuan_view !== '' && $kelas_tujuan_view === $kelas_asal_view) {
    $kelas_tujuan_view = '';
}

$siswa_asal = [];
if ($kelas_asal_view) {
    $ka  = $conn->real_escape_string($kelas_asal_view);
    $res = $conn->query("SELECT * FROM siswa WHERE kelas='$ka' ORDER BY nama");
    while ($row = $res->fetch_assoc()) $siswa_asal[] = $row;
}

// Jumlah siswa per kelas (untuk pill kelas, sama seperti di Kelola Siswa)
$jumlah_per_kelas = [];
foreach ($kelas_list as $k) {
    $k_safe = $conn->real_escape_string($k);
    $jumlah_per_kelas[$k] = (int)$conn->query("SELECT COUNT(*) c FROM siswa WHERE kelas='$k_safe'")->fetch_assoc()['c'];
}

include 'includes/header.php';
if ($msg) { list($t,$tx)=explode(':',$msg,2); echo "<div class='alert alert-$t'><i class='fas fa-check-circle'></i> $tx</div>"; }
?>

<div class="page-header d-flex align-center">
    <div>
        <div class="page-title"><i class="fas fa-people-arrows"></i> Pindah Kelas</div>
        <div class="page-subtitle">Naik kelas / pindah rombel &mdash; data siswa tetap mengacu ke Tingkat &amp; Rombel yang sudah dibuat</div>
    </div>
    <div class="ms-auto" style="display:flex;gap:8px">
        <a href="tingkat.php" class="btn btn-outline"><i class="fas fa-layer-group"></i> Kelola Tingkat</a>
        <a href="kelas.php" class="btn btn-outline"><i class="fas fa-chalkboard"></i> Kelola Rombel</a>
        <a href="siswa.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Kembali ke Kelola Siswa</a>
    </div>
</div>

<?php if (empty($tingkat_list) || empty($kelas_list)): ?>
<div class="card mb-3" style="border-left:4px solid var(--warning)">
    <div class="card-body" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
        <i class="fas fa-exclamation-triangle" style="color:var(--warning);font-size:1.2rem"></i>
        <div style="font-size:.85rem">
            <?php if (empty($tingkat_list)): ?>
                Belum ada <strong>Tingkat</strong>. Buat dulu di menu <a href="tingkat.php">Kelola Tingkat</a>,
                baru lanjut buat <strong>Rombel</strong> di menu <a href="kelas.php">Kelola Rombel</a>.
            <?php else: ?>
                Belum ada <strong>Rombel</strong>. Buat dulu di menu <a href="kelas.php">Kelola Rombel</a>
                sebelum memindahkan siswa antar kelas.
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ── STEP 1: PILIH KELAS ASAL & KELAS TUJUAN ──────────────── -->
<div class="card mb-3">
    <div class="card-header">
        <i class="fas fa-door-open" style="color:var(--primary)"></i>
        1. Pilih Kelas Asal &amp; Kelas Tujuan
    </div>
    <div class="card-body">
        <form method="GET" id="filterAsalForm" class="filter-bar" style="align-items:center">
            <div style="display:flex;flex-direction:column;gap:4px">
                <label class="form-label" style="margin:0">Dari Kelas</label>
                <select name="kelas_asal" class="form-select" style="width:auto;min-width:220px"
                        onchange="onAsalChange(this)">
                    <option value="">-- Pilih Kelas Asal --</option>
                    <?php foreach ($kelas_list as $k): ?>
                        <option value="<?= htmlspecialchars($k) ?>" <?= $kelas_asal_view===$k?'selected':'' ?>>
                            KELAS <?= htmlspecialchars($k) ?> (<?= $jumlah_per_kelas[$k] ?> siswa)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <i class="fas fa-long-arrow-alt-right" style="color:var(--text-muted);font-size:1.2rem;margin:0 8px;align-self:flex-end;transform:translateY(-10px)"></i>

            <div style="display:flex;flex-direction:column;gap:4px">
                <label class="form-label" style="margin:0">Menuju Kelas (KE)</label>
                <select name="kelas_tujuan" class="form-select" style="width:auto;min-width:220px"
                        onchange="document.getElementById('filterAsalForm').submit()">
                    <option value="">-- Pilih Kelas Tujuan --</option>
                    <?php foreach ($kelas_list as $k): if ($k===$kelas_asal_view) continue; ?>
                        <option value="<?= htmlspecialchars($k) ?>" <?= $kelas_tujuan_view===$k?'selected':'' ?>>
                            KELAS <?= htmlspecialchars($k) ?> (<?= $jumlah_per_kelas[$k] ?> siswa)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>

        <?php if ($kelas_asal_view && !empty($siswa_asal)): ?>
        <div style="display:inline-block;margin-top:4px">
            <button type="button" id="btnPindahTop" class="btn btn-primary" disabled
                    onclick="konfirmasiPindah()"
                    style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:2px;height:38px;margin-top:22px;opacity:.5;cursor:not-allowed">
                <span><i class="fas fa-people-arrows"></i> Pindahkan Siswa Terpilih (<span id="jumlahPilihTop">0</span>)</span>
            </button>
            <div style="font-size:.75rem;color:var(--text-muted);margin-top:4px">
                Centang siswa di tabel bawah, lalu klik tombol ini
            </div>
        </div>
        <?php endif; ?>


        <div style="margin-top:16px">
            <div style="font-size:.78rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;margin-bottom:8px">
                Pilihan Cepat &mdash; Kelas Asal
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:10px">
                <?php foreach ($kelas_list as $k): $active = $kelas_asal_view===$k; ?>
                <a href="?kelas_asal=<?= urlencode($k) ?>"
                   style="display:inline-flex;align-items:center;gap:7px;padding:9px 18px;border-radius:999px;
                          font-size:.85rem;font-weight:700;text-decoration:none;color:#fff;white-space:nowrap;
                          background:#16a34a;box-shadow:0 2px 6px rgba(0,0,0,.15);<?= $active ? 'outline:3px solid #14532d;outline-offset:2px;' : '' ?>">
                    <i class="fas fa-door-open" style="font-size:.85rem"></i>
                    KELAS <?= htmlspecialchars($k) ?> (<?= $jumlah_per_kelas[$k] ?> siswa)
                </a>
                <?php endforeach; ?>
                <?php if (empty($kelas_list)): ?>
                    <span style="color:var(--text-muted)">Belum ada data siswa/kelas.</span>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($kelas_asal_view && $kelas_tujuan_view): ?>
        <div style="margin-top:16px;display:flex;align-items:center;gap:10px;font-weight:700">
            <span class="badge" style="background:#fef2f2;color:#991b1b;font-size:.9rem;padding:8px 16px">
                KELAS <?= htmlspecialchars($kelas_asal_view) ?>
            </span>
            <i class="fas fa-arrow-right" style="color:var(--text-muted)"></i>
            <span class="badge" style="background:#f0fdf4;color:#15803d;font-size:.9rem;padding:8px 16px">
                KELAS <?= htmlspecialchars($kelas_tujuan_view) ?>
            </span>
        </div>
        <?php elseif ($kelas_asal_view): ?>
        <div style="margin-top:16px;color:var(--warning);font-size:.85rem">
            <i class="fas fa-exclamation-triangle"></i> Pilih juga <strong>Menuju Kelas (KE)</strong> di atas untuk melanjutkan.
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($kelas_asal_view): ?>
<!-- ── STEP 2: PILIH SISWA YANG DIPINDAH ────────────────────── -->
<form method="POST" id="formPindahKelas">
<input type="hidden" name="pindah_kelas" value="1">
<input type="hidden" name="kelas_asal" value="<?= htmlspecialchars($kelas_asal_view) ?>">
<input type="hidden" name="kelas_tujuan" value="<?= htmlspecialchars($kelas_tujuan_view) ?>">

<div class="card mb-3">
    <div class="card-header" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
        <i class="fas fa-users" style="color:var(--primary)"></i>
        2. Data Siswa Kelas <?= htmlspecialchars($kelas_asal_view) ?>
        <span class="badge" style="background:#eff6ff;color:var(--primary)"><?= count($siswa_asal) ?> siswa</span>
        <div style="margin-left:auto;display:flex;align-items:center;gap:10px">
            <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:.85rem;color:var(--text-muted);margin:0;font-weight:500">
                <input type="checkbox" id="checkAll" onchange="toggleAll(this)"
                       style="width:16px;height:16px;cursor:pointer;accent-color:var(--primary)">
                Pilih Semua
            </label>
            <span class="badge" style="background:#f0fdf4;color:#15803d">
                Terpilih: <span id="jumlahPilih">0</span>
            </span>
        </div>
    </div>

    <div class="table-container">
        <table>
            <thead><tr>
                <th style="width:44px;text-align:center">✓</th>
                <th>#</th><th>NIS</th><th>Nama</th><th>Kelas Saat Ini</th>
            </tr></thead>
            <tbody>
                <?php if (empty($siswa_asal)): ?>
                <tr><td colspan="5" style="text-align:center;padding:48px;color:var(--text-muted)">
                    <i class="fas fa-users-slash fa-2x" style="opacity:.3;display:block;margin-bottom:8px"></i>
                    Tidak ada siswa di kelas ini
                </td></tr>
                <?php else: $no=0; foreach ($siswa_asal as $s): $no++; ?>
                <tr>
                    <td style="text-align:center">
                        <input type="checkbox" name="siswa_ids[]" value="<?= $s['id'] ?>"
                               class="checkSiswa" onchange="updateHapusBtn()"
                               style="width:16px;height:16px;cursor:pointer;accent-color:var(--primary)">
                    </td>
                    <td><?= $no ?></td>
                    <td><code><?= htmlspecialchars($s['nis']) ?></code></td>
                    <td><?= htmlspecialchars($s['nama']) ?></td>
                    <td><span class="badge" style="background:#eff6ff;color:var(--primary)"><?= htmlspecialchars($s['kelas']) ?></span></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ── STEP 3: KONFIRMASI & AKSI ─────────────────────────────── -->
<?php if (!empty($siswa_asal)): ?>
<div class="card">
    <div class="card-header">
        <i class="fas fa-flag-checkered" style="color:var(--primary)"></i>
        3. Pindahkan / Naikkan ke Kelas
    </div>
    <div class="card-body">
        <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
            <div style="display:flex;align-items:center;gap:10px;font-weight:700">
                <span class="badge" style="background:#fef2f2;color:#991b1b;font-size:.9rem;padding:8px 16px">
                    KELAS <?= htmlspecialchars($kelas_asal_view) ?>
                </span>
                <i class="fas fa-arrow-right" style="color:var(--text-muted)"></i>
                <span class="badge" style="background:<?= $kelas_tujuan_view ? '#f0fdf4' : '#f1f5f9' ?>;color:<?= $kelas_tujuan_view ? '#15803d' : '#94a3b8' ?>;font-size:.9rem;padding:8px 16px">
                    <?= $kelas_tujuan_view ? 'KELAS '.htmlspecialchars($kelas_tujuan_view) : '-- Pilih Kelas Tujuan --' ?>
                </span>
            </div>

            <button type="button" id="btnPindahKelas" onclick="konfirmasiPindah()"
                    class="btn btn-primary" disabled style="opacity:.5;cursor:not-allowed">
                <i class="fas fa-people-arrows"></i> Pindahkan Siswa Terpilih (<span id="jumlahPilih2">0</span>)
            </button>
        </div>
        <small style="display:block;margin-top:10px;color:var(--text-muted)">
            <i class="fas fa-info-circle"></i>
            Ingin ganti tujuan? Ubah pilihan di langkah 1 di atas.
        </small>
    </div>
</div>
<?php endif; ?>
</form>
<?php endif; ?>

<script>
function toggleAll(master) {
    document.querySelectorAll('.checkSiswa').forEach(cb => cb.checked = master.checked);
    updateHapusBtn();
}

function onAsalChange(sel) {
    const tujuanSel = document.querySelector('#filterAsalForm select[name="kelas_tujuan"]');
    if (tujuanSel && tujuanSel.value === sel.value) tujuanSel.value = '';
    document.getElementById('filterAsalForm').submit();
}

function updateHapusBtn() {
    const checked = document.querySelectorAll('.checkSiswa:checked');
    const total   = document.querySelectorAll('.checkSiswa').length;
    const btn     = document.getElementById('btnPindahKelas');
    const btnTop  = document.getElementById('btnPindahTop');
    const master  = document.getElementById('checkAll');

    document.getElementById('jumlahPilih').textContent  = checked.length;
    const j2 = document.getElementById('jumlahPilih2');
    if (j2) j2.textContent = checked.length;
    const jTop = document.getElementById('jumlahPilihTop');
    if (jTop) jTop.textContent = checked.length;

    [btn, btnTop].forEach(b => {
        if (!b) return;
        b.disabled = checked.length === 0;
        b.style.opacity = checked.length === 0 ? '.5' : '1';
        b.style.cursor  = checked.length === 0 ? 'not-allowed' : 'pointer';
    });

    if (master) {
        master.indeterminate = checked.length > 0 && checked.length < total;
        master.checked       = total > 0 && checked.length === total;
    }
}

function konfirmasiPindah() {
    const n = document.querySelectorAll('.checkSiswa:checked').length;
    if (!n) { alert('Pilih minimal 1 siswa terlebih dahulu'); return; }
    const tujuanInput = document.querySelector('#formPindahKelas input[name="kelas_tujuan"]');
    const tujuan = tujuanInput ? tujuanInput.value : '';
    if (!tujuan) { alert('Pilih kelas tujuan terlebih dahulu di langkah 1'); return; }
    if (!confirm(`Pindahkan ${n} siswa terpilih ke kelas ${tujuan}?`)) return;
    document.getElementById('formPindahKelas').submit();
}
</script>

<?php include 'includes/footer.php'; ?>
