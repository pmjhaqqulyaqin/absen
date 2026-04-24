<?php
require_once 'includes/config.php';
cek_login();

$today   = date('Y-m-d');
$stats   = get_stats_hari_ini();
// Tambah stat pulang
$stats['pulang'] = $conn->query("SELECT COUNT(*) c FROM absensi WHERE tanggal='$today' AND jam_pulang IS NOT NULL")->fetch_assoc()['c'];
$pengaturan = get_pengaturan();

// 7-day chart data
$chart_labels = [];
$chart_hadir  = [];
$chart_terlambat = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('d/m', strtotime($d));
    $r = $conn->query("SELECT 
        SUM(status IN ('Hadir','Terlambat')) as hadir,
        SUM(status='Terlambat') as terlambat
        FROM absensi WHERE tanggal='$d'")->fetch_assoc();
    $chart_hadir[]     = (int)($r['hadir'] ?? 0);
    $chart_terlambat[] = (int)($r['terlambat'] ?? 0);
}

// Recent log
$recent = $conn->query("SELECT a.*, s.foto FROM absensi a LEFT JOIN siswa s ON s.id=a.siswa_id WHERE a.tanggal='$today' ORDER BY a.updated_at DESC LIMIT 10");

include 'includes/header.php';
?>

<div class="page-header">
    <div class="page-title">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </div>
    <div class="page-subtitle"><?= format_tanggal($today) ?></div>
</div>

<!-- STATS GRID -->
<div class="stats-grid">
    <div class="stat-card total">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div><div class="stat-value"><?= $stats['total_siswa'] ?></div><div class="stat-label">Total Siswa</div></div>
    </div>
    <div class="stat-card belum">
        <div class="stat-icon"><i class="fas fa-user-times"></i></div>
        <div><div class="stat-value"><?= $stats['belum_absen'] ?></div><div class="stat-label">Belum Absen</div></div>
    </div>
    <div class="stat-card hadir">
        <div class="stat-icon"><i class="fas fa-user-check"></i></div>
        <div><div class="stat-value"><?= $stats['Hadir'] ?></div><div class="stat-label">Hadir</div></div>
    </div>
    <div class="stat-card pulang" style="background:white">
        <div class="stat-icon" style="background:#f5f3ff;color:#7c3aed"><i class="fas fa-home"></i></div>
        <div><div class="stat-value" style="color:#7c3aed"><?= $stats['pulang'] ?></div><div class="stat-label" style="color:#9ca3af">Sudah Pulang</div></div>
    </div>
    <div class="stat-card terlambat">
        <div class="stat-icon"><i class="fas fa-clock"></i></div>
        <div><div class="stat-value"><?= $stats['Terlambat'] ?></div><div class="stat-label">Terlambat</div></div>
    </div>
    <div class="stat-card alpa">
        <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
        <div><div class="stat-value"><?= $stats['Alpa'] ?></div><div class="stat-label">Alpa</div></div>
    </div>
    <div class="stat-card sakit">
        <div class="stat-icon"><i class="fas fa-heartbeat"></i></div>
        <div><div class="stat-value"><?= $stats['Sakit'] ?></div><div class="stat-label">Sakit</div></div>
    </div>
    <div class="stat-card izin">
        <div class="stat-icon"><i class="fas fa-clipboard-list"></i></div>
        <div><div class="stat-value"><?= $stats['Izin'] ?></div><div class="stat-label">Izin</div></div>
    </div>
    <div class="stat-card bolos">
        <div class="stat-icon"><i class="fas fa-ban"></i></div>
        <div><div class="stat-value"><?= $stats['Bolos'] ?></div><div class="stat-label">Bolos</div></div>
    </div>
</div>

<!-- CHARTS + LOG -->
<div class="grid-2">
    <!-- Chart 7 Hari -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-chart-bar" style="color:var(--primary)"></i>
            Kehadiran 7 Hari Terakhir
        </div>
        <div class="card-body">
            <div class="chart-container" style="height:220px">
                <canvas id="chart7hari"></canvas>
            </div>
        </div>
    </div>

    <!-- Log Real-time -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-history" style="color:var(--success)"></i>
            Log Absensi Hari Ini
            <span class="badge" style="background:#f0fdf4;color:#15803d;margin-left:auto"><?= $stats['sudah_absen'] ?> siswa</span>
        </div>
        <div class="log-feed">
            <?php if ($recent->num_rows === 0): ?>
                <div style="padding:40px;text-align:center;color:var(--text-muted)">
                    <i class="fas fa-inbox fa-2x" style="opacity:.3"></i>
                    <p style="margin-top:12px">Belum ada absensi hari ini</p>
                </div>
            <?php else:
                while ($log = $recent->fetch_assoc()): ?>
                <div class="log-item">
                    <?php if (!empty($log['foto']) && file_exists('uploads/foto/'.$log['foto'])): ?>
                        <img src="<?= BASE_URL ?>uploads/foto/<?= $log['foto'] ?>" class="student-photo" alt="">
                    <?php else: ?>
                        <div class="log-avatar"><?= strtoupper(substr($log['nama'],0,1)) ?></div>
                    <?php endif; ?>
                    <div class="log-info">
                        <div class="log-name"><?= htmlspecialchars($log['nama']) ?></div>
                        <div class="log-detail"><?= $log['nis'] ?> | <?= $log['kelas'] ?></div>
                    </div>
                    <div>
                        <?= get_status_badge($log['status']) ?>
                        <div class="log-time"><?= $log['jam_masuk'] ? date('H:i', strtotime($log['jam_masuk'])) : '-' ?></div>
                    </div>
                </div>
            <?php endwhile; endif; ?>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card mt-3">
    <div class="card-header"><i class="fas fa-bolt" style="color:var(--warning)"></i> Aksi Cepat</div>
    <div class="card-body">
        <div style="display:flex;gap:12px;flex-wrap:wrap">
            <a href="scan.php" class="btn btn-primary"><i class="fas fa-qrcode"></i> Scan QR</a>
            <a href="manual.php" class="btn btn-success"><i class="fas fa-edit"></i> Input Absensi</a>
            <a href="belum_absen.php" class="btn btn-warning"><i class="fas fa-user-times"></i> Belum Absen (<?= $stats['belum_absen'] ?>)</a>
            <a href="siswa.php" class="btn btn-info"><i class="fas fa-users"></i> Kelola Siswa</a>
            <a href="wali.php" class="btn btn-secondary"><i class="fas fa-chalkboard-teacher"></i> Kelola Wali</a>
            <a href="catatan.php" class="btn btn-outline"><i class="fas fa-sticky-note"></i> Catatan</a>
            <a href="portal_login.php?role=siswa" class="btn btn-outline" target="_blank"><i class="fas fa-user-graduate"></i> Portal Siswa</a>
            <a href="portal_login.php?role=wali" class="btn btn-outline" target="_blank"><i class="fas fa-chalkboard-teacher"></i> Portal Wali</a>
        </div>
    </div>
</div>

<!-- Hapus Siswa dengan Centang -->
<div class="card mt-3">
    <div class="card-header" style="background:#fee2e2;color:#991b1b">
        <i class="fas fa-trash-alt"></i> Hapus Data Siswa
        <span style="font-size:.8rem;color:#b91c1c;margin-left:8px">Pilih siswa lalu klik Hapus</span>
    </div>
    <div class="card-body">
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:12px">
            <select id="filterKelasHapus" class="form-select" style="width:auto" onchange="loadListSiswa()">
                <option value="">-- Pilih Kelas --</option>
                <?php
                $klsList = $conn->query("SELECT DISTINCT kelas FROM siswa ORDER BY kelas");
                while ($k = $klsList->fetch_assoc()):
                ?><option value="<?= $k['kelas'] ?>"><?= $k['kelas'] ?></option>
                <?php endwhile; ?>
            </select>
            <button onclick="pilihSemuaHapus()" class="btn btn-outline" style="font-size:.85rem">☑ Pilih Semua</button>
            <button onclick="hapusSiswaSelected()" class="btn" style="background:#dc2626;color:white;font-size:.85rem"><i class="fas fa-trash"></i> Hapus Terpilih</button>
        </div>
        <div id="alertHapusSiswa"></div>
        <div id="listSiswaHapus" style="max-height:250px;overflow-y:auto;border:1px solid #fecaca;border-radius:8px;padding:8px;background:#fff5f5">
            <div style="text-align:center;color:#9ca3af;padding:20px">Pilih kelas untuk menampilkan daftar siswa</div>
        </div>
    </div>
</div>

<script>
function loadListSiswa() {
    const kelas = document.getElementById('filterKelasHapus').value;
    if (!kelas) return;
    const container = document.getElementById('listSiswaHapus');
    container.innerHTML = '<div style="text-align:center;padding:16px"><i class="fas fa-spinner fa-spin"></i></div>';
    fetch('ajax/get_siswa_list.php?kelas=' + encodeURIComponent(kelas))
    .then(r => r.json())
    .then(data => {
        if (!data.length) { container.innerHTML = '<div style="text-align:center;color:#9ca3af;padding:16px">Tidak ada siswa</div>'; return; }
        container.innerHTML = data.map(s => `
            <div style="display:flex;align-items:center;gap:10px;padding:6px 4px;border-bottom:1px solid #fecaca">
                <input type="checkbox" class="checkHapusSiswa" value="${s.id}" style="width:16px;height:16px">
                <strong>${s.nama}</strong>
                <span style="color:#9ca3af;font-size:.82rem">${s.nis}</span>
                <span style="margin-left:auto;font-size:.8rem;background:#eff6ff;color:#1d4ed8;padding:2px 8px;border-radius:20px">${s.kelas}</span>
            </div>
        `).join('');
    })
    .catch(() => { container.innerHTML = '<div style="color:#dc2626;padding:12px">Gagal memuat data</div>'; });
}

function pilihSemuaHapus() {
    document.querySelectorAll('.checkHapusSiswa').forEach(cb => cb.checked = true);
}

function hapusSiswaSelected() {
    const checked = [...document.querySelectorAll('.checkHapusSiswa:checked')].map(cb => cb.value);
    if (!checked.length) { showToast('Pilih minimal 1 siswa', 'warning'); return; }
    if (!confirm(`Yakin hapus ${checked.length} siswa? Data absensinya ikut terhapus!`)) return;
    fetch('ajax/hapus_siswa.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ids: checked})
    })
    .then(r => r.json())
    .then(res => {
        const el = document.getElementById('alertHapusSiswa');
        el.innerHTML = `<div class="alert alert-${res.success?'success':'danger'}">${res.message}</div>`;
        if (res.success) { loadListSiswa(); setTimeout(() => location.reload(), 1500); }
    });
}
</script>

<script>
const ctx = document.getElementById('chart7hari').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [
            {
                label: 'Hadir',
                data: <?= json_encode($chart_hadir) ?>,
                backgroundColor: 'rgba(22,163,74,.7)',
                borderRadius: 6,
            },
            {
                label: 'Terlambat',
                data: <?= json_encode($chart_terlambat) ?>,
                backgroundColor: 'rgba(217,119,6,.7)',
                borderRadius: 6,
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom' } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});
</script>

<?php include 'includes/footer.php'; ?>
