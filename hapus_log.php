<?php
require_once 'includes/config.php';
cek_login();

$msg = '';
$kelas_list = get_kelas_list();

// =============================================
// PROSES HAPUS
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_log'])) {
    $dari   = sanitize($_POST['dari']);
    $sampai = sanitize($_POST['sampai']);
    $kelas  = sanitize($_POST['kelas'] ?? '');
    $konfirmasi = $_POST['konfirmasi'] ?? '';

    if (strtolower($konfirmasi) !== 'hapus') {
        $msg = 'danger:Kata konfirmasi salah! Ketik kata HAPUS untuk melanjutkan.';
    } else {
        $where_hapus = "tanggal BETWEEN '$dari' AND '$sampai'";
        if ($kelas) $where_hapus .= " AND kelas='$kelas'";

        // Auto backup ke rekap_bulanan
        $months = $conn->query("SELECT DISTINCT MONTH(tanggal) as m, YEAR(tanggal) as y 
            FROM absensi WHERE $where_hapus")->fetch_all(MYSQLI_ASSOC);
        foreach ($months as $mp) {
            backup_rekap_bulanan($mp['m'], $mp['y']);
        }

        // Hitung jumlah sebelum hapus
        $count = $conn->query("SELECT COUNT(*) as c FROM absensi WHERE $where_hapus")->fetch_assoc()['c'];

        // HAPUS
        $conn->query("DELETE FROM absensi WHERE $where_hapus");

        $msg = "success:Berhasil menghapus <strong>$count log absensi</strong>. Data telah dibackup ke rekap bulanan.";
    }
}

// =============================================
// AJAX: Preview jumlah data yang akan dihapus
// =============================================
if (isset($_GET['preview'])) {
    $dari   = sanitize($_GET['dari']);
    $sampai = sanitize($_GET['sampai']);
    $kelas  = sanitize($_GET['kelas'] ?? '');
    $where  = "tanggal BETWEEN '$dari' AND '$sampai'";
    if ($kelas) $where .= " AND kelas='$kelas'";

    $count  = $conn->query("SELECT COUNT(*) as c FROM absensi WHERE $where")->fetch_assoc()['c'];
    $stats  = $conn->query("SELECT status, COUNT(*) as t FROM absensi WHERE $where GROUP BY status")->fetch_all(MYSQLI_ASSOC);
    $days   = $conn->query("SELECT COUNT(DISTINCT tanggal) as d FROM absensi WHERE $where")->fetch_assoc()['d'];
    $siswa  = $conn->query("SELECT COUNT(DISTINCT siswa_id) as s FROM absensi WHERE $where")->fetch_assoc()['s'];
    header('Content-Type: application/json');
    echo json_encode(['count'=>$count,'stats'=>$stats,'days'=>$days,'siswa'=>$siswa]);
    exit;
}

// =============================================
// DATA RINGKASAN
// =============================================
$oldest    = $conn->query("SELECT MIN(tanggal) as v FROM absensi")->fetch_assoc()['v'];
$newest    = $conn->query("SELECT MAX(tanggal) as v FROM absensi")->fetch_assoc()['v'];
$total_log = $conn->query("SELECT COUNT(*) as c FROM absensi")->fetch_assoc()['c'];
$total_hari= $conn->query("SELECT COUNT(DISTINCT tanggal) as c FROM absensi")->fetch_assoc()['c'];
$total_siswa_log = $conn->query("SELECT COUNT(DISTINCT siswa_id) as c FROM absensi")->fetch_assoc()['c'];

// Log per bulan untuk tabel
$per_bulan = $conn->query("SELECT YEAR(tanggal) as tahun, MONTH(tanggal) as bulan,
    COUNT(*) as total,
    SUM(status='Hadir') as hadir,
    SUM(status='Terlambat') as terlambat,
    SUM(status='Alpa') as alpa,
    SUM(status='Sakit') as sakit,
    SUM(status='Izin') as izin,
    SUM(status='Bolos') as bolos,
    COUNT(DISTINCT tanggal) as hari,
    COUNT(DISTINCT siswa_id) as siswa
    FROM absensi GROUP BY YEAR(tanggal), MONTH(tanggal) ORDER BY tahun DESC, bulan DESC");

$bln_names = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

include 'includes/header.php';
?>

<div class="page-header">
    <div class="page-title"><i class="fas fa-trash-alt" style="color:var(--danger)"></i> Hapus Log Absensi</div>
    <div class="page-subtitle">Backup otomatis ke rekap bulanan sebelum data dihapus</div>
</div>

<?php if ($msg): list($t,$tx)=explode(':',$msg,2); ?>
<div class="alert alert-<?= $t ?>" style="display:flex;gap:10px;align-items:center">
    <i class="fas fa-<?= $t==='success'?'check-circle':'exclamation-circle' ?>" style="font-size:1.3rem;flex-shrink:0"></i>
    <span><?= $tx ?></span>
</div>
<?php endif; ?>

<!-- STATISTIK LOG -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:14px;margin-bottom:20px">
    <div class="card" style="padding:18px;text-align:center;border-top:4px solid var(--primary)">
        <div style="font-size:1.8rem;font-weight:800;color:var(--primary)"><?= number_format($total_log) ?></div>
        <div style="font-size:.8rem;color:var(--text-muted);margin-top:4px"><i class="fas fa-database"></i> Total Log</div>
    </div>
    <div class="card" style="padding:18px;text-align:center;border-top:4px solid #10b981">
        <div style="font-size:1.8rem;font-weight:800;color:#10b981"><?= $total_hari ?></div>
        <div style="font-size:.8rem;color:var(--text-muted);margin-top:4px"><i class="fas fa-calendar-alt"></i> Hari Tercatat</div>
    </div>
    <div class="card" style="padding:18px;text-align:center;border-top:4px solid #f59e0b">
        <div style="font-size:1.8rem;font-weight:800;color:#f59e0b"><?= $total_siswa_log ?></div>
        <div style="font-size:.8rem;color:var(--text-muted);margin-top:4px"><i class="fas fa-users"></i> Siswa Terlibat</div>
    </div>
    <div class="card" style="padding:18px;text-align:center;border-top:4px solid #64748b">
        <div style="font-size:1rem;font-weight:700;color:#64748b"><?= $oldest ?? '-' ?></div>
        <div style="font-size:.8rem;color:var(--text-muted);margin-top:4px"><i class="fas fa-hourglass-start"></i> Data Tertua</div>
    </div>
    <div class="card" style="padding:18px;text-align:center;border-top:4px solid #6366f1">
        <div style="font-size:1rem;font-weight:700;color:#6366f1"><?= $newest ?? '-' ?></div>
        <div style="font-size:.8rem;color:var(--text-muted);margin-top:4px"><i class="fas fa-hourglass-end"></i> Data Terbaru</div>
    </div>
</div>

<!-- TABEL LOG PER BULAN -->
<div class="card mb-3">
    <div class="card-header">
        <i class="fas fa-calendar-alt" style="color:var(--primary)"></i> Log Absensi Per Bulan
        <span style="font-size:.8rem;color:var(--text-muted);font-weight:400;margin-left:8px">Klik baris untuk langsung isi form hapus</span>
    </div>
    <div class="table-container" style="max-height:380px;overflow-y:auto">
        <table>
            <thead style="position:sticky;top:0;z-index:1">
                <tr>
                    <th>Bulan</th>
                    <th>Hari</th>
                    <th>Siswa</th>
                    <th style="color:#16a34a">Hadir</th>
                    <th style="color:#d97706">Terlambat</th>
                    <th style="color:#dc2626">Alpa</th>
                    <th style="color:#0891b2">Sakit</th>
                    <th style="color:#7c3aed">Izin</th>
                    <th style="color:#dc2626">Bolos</th>
                    <th>Total</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($per_bulan->num_rows === 0): ?>
                <tr><td colspan="11" style="text-align:center;padding:40px;color:var(--text-muted)">
                    <i class="fas fa-inbox fa-2x" style="opacity:.3"></i><br><br>Belum ada data log
                </td></tr>
            <?php else: while ($r = $per_bulan->fetch_assoc()):
                // Tanggal awal dan akhir bulan
                $tgl_awal  = $r['tahun'].'-'.str_pad($r['bulan'],2,'0',STR_PAD_LEFT).'-01';
                $tgl_akhir = date('Y-m-t', strtotime($tgl_awal));
            ?>
                <tr style="cursor:pointer;transition:.15s" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background=''"
                    onclick="isiForm('<?= $tgl_awal ?>','<?= $tgl_akhir ?>')">
                    <td><strong><?= $bln_names[$r['bulan']] ?> <?= $r['tahun'] ?></strong></td>
                    <td><?= $r['hari'] ?> hari</td>
                    <td><?= $r['siswa'] ?> siswa</td>
                    <td><span style="color:#16a34a;font-weight:600"><?= $r['hadir'] ?></span></td>
                    <td><span style="color:#d97706;font-weight:600"><?= $r['terlambat'] ?></span></td>
                    <td><span style="color:#dc2626;font-weight:600"><?= $r['alpa'] ?></span></td>
                    <td><span style="color:#0891b2"><?= $r['sakit'] ?></span></td>
                    <td><span style="color:#7c3aed"><?= $r['izin'] ?></span></td>
                    <td><span style="color:#dc2626"><?= $r['bolos'] ?></span></td>
                    <td><strong><?= $r['total'] ?></strong></td>
                    <td>
                        <button type="button" onclick="event.stopPropagation();isiForm('<?= $tgl_awal ?>','<?= $tgl_akhir ?>')"
                            class="btn btn-sm btn-danger" style="font-size:.75rem;padding:4px 10px">
                            <i class="fas fa-trash"></i> Hapus
                        </button>
                    </td>
                </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- FORM HAPUS -->
<div class="card" id="formCard">
    <div class="card-header" style="background:linear-gradient(135deg,#fef2f2,#fff)">
        <i class="fas fa-trash-alt" style="color:var(--danger)"></i>
        <span style="color:var(--danger);font-weight:700">Form Hapus Log</span>
    </div>
    <div class="card-body">

        <div class="alert" style="background:#fef3c7;border:1px solid #f59e0b;color:#92400e;display:flex;gap:12px;align-items:flex-start;margin-bottom:20px">
            <i class="fas fa-exclamation-triangle" style="font-size:1.3rem;color:#f59e0b;flex-shrink:0;margin-top:2px"></i>
            <div>
                <strong>Perhatian!</strong> Data log yang dihapus <u>tidak bisa dipulihkan</u>.<br>
                Sistem akan <strong>otomatis backup ke Rekap Bulanan</strong> sebelum menghapus.
                Pastikan sudah export Excel jika butuh data detail.
            </div>
        </div>

        <form method="POST" id="formHapus" onsubmit="return validasiForm()">
            <input type="hidden" name="hapus_log" value="1">

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-calendar"></i> Dari Tanggal <span style="color:red">*</span></label>
                    <input type="date" name="dari" id="inputDari" class="form-control" required
                        value="<?= $oldest ?? date('Y-m-01') ?>" max="<?= date('Y-m-d') ?>"
                        onchange="updatePreview()">
                </div>
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-calendar-check"></i> Sampai Tanggal <span style="color:red">*</span></label>
                    <input type="date" name="sampai" id="inputSampai" class="form-control" required
                        value="<?= $oldest ? date('Y-m-t', strtotime($oldest)) : date('Y-m-t') ?>" max="<?= date('Y-m-d') ?>"
                        onchange="updatePreview()">
                </div>
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-school"></i> Filter Kelas</label>
                    <select name="kelas" id="inputKelas" class="form-select" onchange="updatePreview()">
                        <option value="">Semua Kelas</option>
                        <?php foreach ($kelas_list as $k): ?>
                            <option value="<?= $k ?>"><?= $k ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- PREVIEW AREA -->
            <div id="previewArea" style="background:#f8fafc;border:1px solid var(--border);border-radius:10px;padding:16px;margin-bottom:16px;min-height:80px">
                <div id="previewLoading" style="text-align:center;color:var(--text-muted)">
                    <i class="fas fa-spinner fa-spin"></i> Menghitung data...
                </div>
                <div id="previewResult" style="display:none">
                    <div style="font-weight:700;font-size:.9rem;color:#dc2626;margin-bottom:10px">
                        <i class="fas fa-exclamation-circle"></i> Data yang akan dihapus:
                    </div>
                    <div style="display:flex;gap:16px;flex-wrap:wrap">
                        <div style="text-align:center;background:white;padding:10px 18px;border-radius:8px;border:1px solid var(--border)">
                            <div id="pvTotal" style="font-size:1.5rem;font-weight:800;color:#dc2626">-</div>
                            <div style="font-size:.75rem;color:var(--text-muted)">Total Log</div>
                        </div>
                        <div style="text-align:center;background:white;padding:10px 18px;border-radius:8px;border:1px solid var(--border)">
                            <div id="pvHari" style="font-size:1.5rem;font-weight:800;color:#7c3aed">-</div>
                            <div style="font-size:.75rem;color:var(--text-muted)">Hari</div>
                        </div>
                        <div style="text-align:center;background:white;padding:10px 18px;border-radius:8px;border:1px solid var(--border)">
                            <div id="pvSiswa" style="font-size:1.5rem;font-weight:800;color:#0891b2">-</div>
                            <div style="font-size:.75rem;color:var(--text-muted)">Siswa</div>
                        </div>
                        <div id="pvStats" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center"></div>
                    </div>
                </div>
                <div id="previewEmpty" style="display:none;text-align:center;color:var(--text-muted)">
                    <i class="fas fa-check-circle" style="color:#16a34a;font-size:1.5rem"></i><br>
                    <span style="font-size:.9rem;margin-top:6px;display:block">Tidak ada data pada rentang yang dipilih</span>
                </div>
            </div>

            <!-- KONFIRMASI KETIK -->
            <div class="form-group" id="konfirmasiGroup" style="display:none">
                <label class="form-label" style="color:#dc2626;font-weight:700">
                    <i class="fas fa-keyboard"></i> Ketik <code style="background:#fee2e2;padding:2px 8px;border-radius:4px;font-size:1rem;letter-spacing:2px">HAPUS</code> untuk konfirmasi
                </label>
                <input type="text" name="konfirmasi" id="inputKonfirmasi" class="form-control"
                    placeholder="Ketik: HAPUS"
                    style="font-size:1.1rem;font-weight:700;letter-spacing:3px;max-width:200px;border:2px solid #fca5a5"
                    oninput="cekKonfirmasi(this.value)" autocomplete="off">
                <div id="konfirmasiHint" style="font-size:.8rem;color:#94a3b8;margin-top:4px"></div>
            </div>

            <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-top:8px">
                <button type="submit" id="btnHapus" class="btn btn-danger btn-lg" disabled
                    style="opacity:.5;cursor:not-allowed">
                    <i class="fas fa-trash-alt"></i> Hapus Log Sekarang
                </button>
                <button type="button" class="btn btn-outline" onclick="resetForm()">
                    <i class="fas fa-undo"></i> Reset
                </button>
                <div id="backupInfo" style="font-size:.8rem;color:#16a34a;display:none">
                    <i class="fas fa-shield-alt"></i> Data akan dibackup otomatis ke Rekap Bulanan
                </div>
            </div>
        </form>
    </div>
</div>

<!-- REKAP BULANAN TERSIMPAN -->
<div class="card mt-3">
    <div class="card-header" style="display:flex;align-items:center;gap:8px">
        <i class="fas fa-archive" style="color:#16a34a"></i> Rekap Bulanan Tersimpan (Backup)
        <span style="margin-left:auto;font-size:.78rem;font-weight:400;color:#64748b">Klik Download untuk export CSV atau Hapus untuk menghapus data bulan tertentu</span>
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Bulan</th><th>Tahun</th><th>Siswa</th>
                    <th>Hadir</th><th>Terlambat</th><th>Alpa</th><th>Sakit</th><th>Izin</th><th>Bolos</th>
                    <th style="min-width:160px">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php
            // Handle hapus backup via POST
            if (isset($_POST['hapus_backup'])) {
                $hb = (int)$_POST['hapus_bulan'];
                $hy = (int)$_POST['hapus_tahun'];
                $conn->query("DELETE FROM rekap_bulanan WHERE bulan=$hb AND tahun=$hy");
                echo "<script>location.href=location.href;</script>";
                exit;
            }

            // Handle download CSV backup
            if (isset($_GET['download_backup'])) {
                $db = (int)$_GET['bulan_dl'];
                $dy = (int)$_GET['tahun_dl'];
                $rows = $conn->query("SELECT nis, nama, kelas, hadir, terlambat, alpa, sakit, izin, bolos, total_hari FROM rekap_bulanan WHERE bulan=$db AND tahun=$dy ORDER BY kelas, nama");
                header('Content-Type: text/csv; charset=UTF-8');
                header("Content-Disposition: attachment; filename=rekap_{$bln_names[$db]}_{$dy}.csv");
                header('Pragma: no-cache');
                echo "\xEF\xBB\xBF"; // BOM UTF-8
                echo "NIS,Nama,Kelas,Hadir,Terlambat,Alpa,Sakit,Izin,Bolos,Total Hari\n";
                while ($row = $rows->fetch_assoc()) {
                    echo implode(',', array_map(fn($v) => '"'.str_replace('"','""',$v).'"', $row))."\n";
                }
                exit;
            }

            $rekap = $conn->query("SELECT bulan, tahun,
                COUNT(*) as siswa,
                SUM(hadir) as hadir, SUM(terlambat) as terlambat,
                SUM(alpa) as alpa, SUM(sakit) as sakit,
                SUM(izin) as izin, SUM(bolos) as bolos
                FROM rekap_bulanan GROUP BY bulan, tahun ORDER BY tahun DESC, bulan DESC");
            if ($rekap->num_rows === 0): ?>
                <tr><td colspan="10" style="text-align:center;padding:30px;color:var(--text-muted)">
                    Belum ada rekap bulanan tersimpan
                </td></tr>
            <?php else: while ($r = $rekap->fetch_assoc()): ?>
                <tr>
                    <td><strong><?= $bln_names[$r['bulan']] ?></strong></td>
                    <td><?= $r['tahun'] ?></td>
                    <td><?= $r['siswa'] ?> siswa</td>
                    <td><span style="color:#16a34a;font-weight:600"><?= $r['hadir'] ?></span></td>
                    <td><span style="color:#d97706;font-weight:600"><?= $r['terlambat'] ?></span></td>
                    <td><span style="color:#dc2626;font-weight:600"><?= $r['alpa'] ?></span></td>
                    <td><span style="color:#0891b2"><?= $r['sakit'] ?></span></td>
                    <td><span style="color:#7c3aed"><?= $r['izin'] ?></span></td>
                    <td><span style="color:#dc2626"><?= $r['bolos'] ?></span></td>
                    <td style="white-space:nowrap">
                        <!-- Download CSV -->
                        <a href="hapus_log.php?download_backup=1&bulan_dl=<?= $r['bulan'] ?>&tahun_dl=<?= $r['tahun'] ?>"
                           style="display:inline-flex;align-items:center;gap:4px;padding:5px 10px;border-radius:6px;background:#16a34a;color:white;font-size:.75rem;font-weight:700;text-decoration:none;margin-right:4px"
                           title="Download CSV <?= $bln_names[$r['bulan']].' '.$r['tahun'] ?>">
                            <i class="fas fa-download"></i> CSV
                        </a>
                        <!-- Hapus -->
                        <form method="POST" style="display:inline" onsubmit="return confirm('Hapus backup rekap <?= $bln_names[$r['bulan']].' '.$r['tahun'] ?>?\nData tidak bisa dipulihkan!')">
                            <input type="hidden" name="hapus_backup" value="1">
                            <input type="hidden" name="hapus_bulan" value="<?= $r['bulan'] ?>">
                            <input type="hidden" name="hapus_tahun" value="<?= $r['tahun'] ?>">
                            <button type="submit" style="display:inline-flex;align-items:center;gap:4px;padding:5px 10px;border-radius:6px;background:#dc2626;color:white;font-size:.75rem;font-weight:700;border:none;cursor:pointer"
                                    title="Hapus backup <?= $bln_names[$r['bulan']].' '.$r['tahun'] ?>">
                                <i class="fas fa-trash"></i> Hapus
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
let previewTimer = null;
let hasData = false;

// Isi form dari klik tabel
function isiForm(dari, sampai, kelas) {
    document.getElementById('inputDari').value   = dari;
    document.getElementById('inputSampai').value = sampai;
    if (kelas) document.getElementById('inputKelas').value = kelas;
    document.getElementById('formCard').scrollIntoView({ behavior:'smooth', block:'center' });
    updatePreview();
}

// Update preview saat form berubah
function updatePreview() {
    clearTimeout(previewTimer);
    const dari   = document.getElementById('inputDari').value;
    const sampai = document.getElementById('inputSampai').value;
    const kelas  = document.getElementById('inputKelas').value;

    if (!dari || !sampai) return;

    document.getElementById('previewLoading').style.display = 'block';
    document.getElementById('previewResult').style.display  = 'none';
    document.getElementById('previewEmpty').style.display   = 'none';

    previewTimer = setTimeout(async () => {
        try {
            const params = new URLSearchParams({preview:1, dari, sampai, kelas});
            const resp = await fetch('hapus_log.php?' + params);
            const data = await resp.json();

            document.getElementById('previewLoading').style.display = 'none';

            if (data.count === 0) {
                hasData = false;
                document.getElementById('previewEmpty').style.display = 'block';
                document.getElementById('konfirmasiGroup').style.display = 'none';
                document.getElementById('backupInfo').style.display = 'none';
                document.getElementById('btnHapus').disabled = true;
                document.getElementById('btnHapus').style.opacity = '.5';
                document.getElementById('btnHapus').style.cursor = 'not-allowed';
                return;
            }

            hasData = true;
            document.getElementById('pvTotal').textContent = data.count.toLocaleString();
            document.getElementById('pvHari').textContent  = data.days;
            document.getElementById('pvSiswa').textContent = data.siswa;

            // Stats badge
            const statusColors = {
                'Hadir':'#16a34a','Terlambat':'#d97706','Alpa':'#dc2626',
                'Sakit':'#0891b2','Izin':'#7c3aed','Bolos':'#ef4444'
            };
            let statsHtml = '';
            data.stats.forEach(s => {
                const c = statusColors[s.status] || '#64748b';
                statsHtml += `<span style="background:${c}20;color:${c};border:1px solid ${c}40;
                    padding:6px 12px;border-radius:20px;font-size:.8rem;font-weight:700">
                    ${s.status}: ${s.t}</span>`;
            });
            document.getElementById('pvStats').innerHTML = statsHtml;
            document.getElementById('previewResult').style.display = 'block';

            // Tampilkan form konfirmasi
            document.getElementById('konfirmasiGroup').style.display = 'block';
            document.getElementById('backupInfo').style.display = '';
            document.getElementById('inputKonfirmasi').value = '';
            cekKonfirmasi('');
        } catch(e) {
            document.getElementById('previewLoading').style.display = 'none';
        }
    }, 500);
}

function cekKonfirmasi(val) {
    const btn  = document.getElementById('btnHapus');
    const hint = document.getElementById('konfirmasiHint');
    if (val.toUpperCase() === 'HAPUS' && hasData) {
        btn.disabled = false;
        btn.style.opacity = '1';
        btn.style.cursor  = 'pointer';
        hint.textContent  = '✅ Konfirmasi diterima, tombol hapus aktif';
        hint.style.color  = '#16a34a';
    } else {
        btn.disabled = true;
        btn.style.opacity = '.5';
        btn.style.cursor  = 'not-allowed';
        if (val.length > 0) {
            hint.textContent = '❌ Kata salah. Ketik: HAPUS (huruf kapital)';
            hint.style.color = '#dc2626';
        } else {
            hint.textContent = '';
        }
    }
}

function validasiForm() {
    const dari   = document.getElementById('inputDari').value;
    const sampai = document.getElementById('inputSampai').value;
    if (dari > sampai) {
        alert('Tanggal "Dari" tidak boleh lebih besar dari "Sampai"!');
        return false;
    }
    const total = document.getElementById('pvTotal').textContent;
    return confirm(`⚠️ KONFIRMASI AKHIR\n\nAnda akan menghapus ${total} log absensi\nPeriode: ${dari} s/d ${sampai}\n\nData akan dibackup ke Rekap Bulanan.\nProses ini TIDAK BISA DIBATALKAN!\n\nLanjutkan?`);
}

function resetForm() {
    document.getElementById('inputKonfirmasi').value = '';
    cekKonfirmasi('');
    document.getElementById('previewLoading').style.display = 'block';
    document.getElementById('previewResult').style.display  = 'none';
    document.getElementById('previewEmpty').style.display   = 'none';
    document.getElementById('konfirmasiGroup').style.display= 'none';
    document.getElementById('backupInfo').style.display     = 'none';
    hasData = false;
}

// Load preview saat halaman pertama kali dibuka
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('inputDari').value) updatePreview();
});
</script>

<?php include 'includes/footer.php'; ?>
