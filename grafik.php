<?php
require_once 'includes/config.php';
cek_login();

// 30-day data
$labels = [];
$hadir_data = [];
$terlambat_data = [];
$alpa_data = [];

for ($i = 29; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $labels[] = date('d/m', strtotime($d));
    $r = $conn->query("SELECT 
        SUM(status='Hadir') as hadir,
        SUM(status='Terlambat') as terlambat,
        SUM(status='Alpa') as alpa
        FROM absensi WHERE tanggal='$d'")->fetch_assoc();
    $hadir_data[]     = (int)($r['hadir'] ?? 0);
    $terlambat_data[] = (int)($r['terlambat'] ?? 0);
    $alpa_data[]      = (int)($r['alpa'] ?? 0);
}

// Weekly averages
$weekly_data = [];
for ($w = 0; $w < 4; $w++) {
    $start = date('Y-m-d', strtotime("-" . (($w+1)*7-1) . " days"));
    $end   = date('Y-m-d', strtotime("-" . ($w*7) . " days"));
    $r = $conn->query("SELECT SUM(status IN ('Hadir','Terlambat')) as hadir, SUM(status='Alpa') as alpa FROM absensi WHERE tanggal BETWEEN '$start' AND '$end'")->fetch_assoc();
    $weekly_data[] = ['label' => "Minggu ke-" . (4-$w), 'hadir' => $r['hadir'] ?? 0, 'alpa' => $r['alpa'] ?? 0];
}

include 'includes/header.php';
?>

<div class="page-header">
    <div class="page-title"><i class="fas fa-chart-line"></i> Grafik Kehadiran</div>
    <div class="page-subtitle">Analisis visual 30 hari terakhir</div>
</div>

<!-- Main Line Chart -->
<div class="card mb-3">
    <div class="card-header"><i class="fas fa-chart-line" style="color:var(--primary)"></i> Tren Kehadiran 30 Hari Terakhir</div>
    <div class="card-body">
        <div class="chart-container" style="height:320px">
            <canvas id="lineChart"></canvas>
        </div>
    </div>
</div>

<div class="grid-2">
    <!-- Bar Chart -->
    <div class="card">
        <div class="card-header"><i class="fas fa-chart-bar" style="color:var(--warning)"></i> Hadir vs Terlambat (30 hari)</div>
        <div class="card-body">
            <div class="chart-container" style="height:250px">
                <canvas id="barChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Weekly Summary -->
    <div class="card">
        <div class="card-header"><i class="fas fa-calendar-week" style="color:var(--success)"></i> Ringkasan Mingguan</div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Periode</th>
                        <th>Hadir</th>
                        <th>Alpa</th>
                        <th>Rasio</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($weekly_data as $w): 
                    $total = $w['hadir'] + $w['alpa'];
                    $pct = $total > 0 ? round($w['hadir']/$total*100) : 0;
                    ?>
                    <tr>
                        <td><?= $w['label'] ?></td>
                        <td><strong style="color:var(--success)"><?= $w['hadir'] ?></strong></td>
                        <td><strong style="color:var(--danger)"><?= $w['alpa'] ?></strong></td>
                        <td>
                            <div style="display:flex;align-items:center;gap:8px">
                                <div style="flex:1;height:8px;background:#e2e8f0;border-radius:4px">
                                    <div style="width:<?= $pct ?>%;height:100%;background:#16a34a;border-radius:4px"></div>
                                </div>
                                <span style="font-size:.8rem;font-weight:600"><?= $pct ?>%</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const labels = <?= json_encode($labels) ?>;
const hadirData    = <?= json_encode($hadir_data) ?>;
const terlambatData = <?= json_encode($terlambat_data) ?>;
const alpaData     = <?= json_encode($alpa_data) ?>;

// Line Chart
new Chart(document.getElementById('lineChart').getContext('2d'), {
    type: 'line',
    data: {
        labels,
        datasets: [
            {
                label: 'Hadir',
                data: hadirData,
                borderColor: '#16a34a',
                backgroundColor: 'rgba(22,163,74,.1)',
                fill: true,
                tension: .4,
                pointRadius: 3,
            },
            {
                label: 'Terlambat',
                data: terlambatData,
                borderColor: '#d97706',
                backgroundColor: 'rgba(217,119,6,.1)',
                fill: false,
                tension: .4,
                pointRadius: 3,
                borderDash: [5,5],
            },
            {
                label: 'Alpa',
                data: alpaData,
                borderColor: '#dc2626',
                backgroundColor: 'rgba(220,38,38,.05)',
                fill: true,
                tension: .4,
                pointRadius: 3,
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom' } },
        scales: {
            x: { ticks: { maxRotation: 45, font: { size: 10 } } },
            y: { beginAtZero: true, ticks: { stepSize: 1 } }
        }
    }
});

// Bar Chart
new Chart(document.getElementById('barChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: labels.slice(-14),
        datasets: [
            { label: 'Hadir', data: hadirData.slice(-14), backgroundColor: 'rgba(22,163,74,.7)', borderRadius: 4 },
            { label: 'Terlambat', data: terlambatData.slice(-14), backgroundColor: 'rgba(217,119,6,.7)', borderRadius: 4 }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom' } },
        scales: { y: { beginAtZero: true } }
    }
});
</script>

<?php include 'includes/footer.php'; ?>
