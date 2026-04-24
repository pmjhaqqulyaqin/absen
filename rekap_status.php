<?php
require_once 'includes/config.php';
cek_login();

// Overall stats
$overall = $conn->query("SELECT status, COUNT(*) as total FROM absensi GROUP BY status")->fetch_all(MYSQLI_ASSOC);
$total_all = array_sum(array_column($overall, 'total'));
$stat_map  = array_column($overall, 'total', 'status');

// Per kelas
$per_kelas = $conn->query("SELECT kelas, status, COUNT(*) as total FROM absensi GROUP BY kelas, status ORDER BY kelas")->fetch_all(MYSQLI_ASSOC);
$kelas_data = [];
foreach ($per_kelas as $row) {
    $kelas_data[$row['kelas']][$row['status']] = $row['total'];
}

include 'includes/header.php';
?>

<div class="page-header">
    <div class="page-title"><i class="fas fa-chart-pie"></i> Rekap Status Keseluruhan</div>
    <div class="page-subtitle">Statistik total semua data absensi</div>
</div>

<!-- Stats -->
<div class="stats-grid mb-3">
    <?php
    $cfgs = [
        'Hadir' =>['icon'=>'user-check','class'=>'hadir'],
        'Terlambat'=>['icon'=>'clock','class'=>'terlambat'],
        'Alpa' =>['icon'=>'times-circle','class'=>'alpa'],
        'Sakit'=>['icon'=>'heartbeat','class'=>'sakit'],
        'Izin' =>['icon'=>'clipboard-list','class'=>'izin'],
        'Bolos'=>['icon'=>'ban','class'=>'bolos'],
    ];
    foreach ($cfgs as $st => $c):
    $val = $stat_map[$st] ?? 0;
    $pct = $total_all > 0 ? round($val/$total_all*100,1) : 0;
    ?>
    <div class="stat-card <?= $c['class'] ?>">
        <div class="stat-icon"><i class="fas fa-<?= $c['icon'] ?>"></i></div>
        <div>
            <div class="stat-value"><?= $val ?></div>
            <div class="stat-label"><?= $st ?> (<?= $pct ?>%)</div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="grid-2">
    <!-- Pie Chart -->
    <div class="card">
        <div class="card-header"><i class="fas fa-chart-pie" style="color:var(--primary)"></i> Grafik Pie Status</div>
        <div class="card-body">
            <div class="chart-container" style="height:280px">
                <canvas id="pieChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Rekap Per Kelas -->
    <div class="card">
        <div class="card-header"><i class="fas fa-school" style="color:var(--success)"></i> Rekap Per Kelas</div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Kelas</th>
                        <th style="color:#15803d">H</th>
                        <th style="color:#854d0e">T</th>
                        <th style="color:#991b1b">A</th>
                        <th style="color:#1e40af">S</th>
                        <th style="color:#5b21b6">I</th>
                        <th style="color:#9a3412">B</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($kelas_data as $kls => $sts): ?>
                    <tr>
                        <td><strong><?= $kls ?></strong></td>
                        <td><?= $sts['Hadir'] ?? 0 ?></td>
                        <td><?= $sts['Terlambat'] ?? 0 ?></td>
                        <td><?= $sts['Alpa'] ?? 0 ?></td>
                        <td><?= $sts['Sakit'] ?? 0 ?></td>
                        <td><?= $sts['Izin'] ?? 0 ?></td>
                        <td><?= $sts['Bolos'] ?? 0 ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
new Chart(document.getElementById('pieChart').getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: ['Hadir','Terlambat','Alpa','Sakit','Izin','Bolos'],
        datasets: [{
            data: [<?= implode(',', [
                $stat_map['Hadir']??0,
                $stat_map['Terlambat']??0,
                $stat_map['Alpa']??0,
                $stat_map['Sakit']??0,
                $stat_map['Izin']??0,
                $stat_map['Bolos']??0
            ]) ?>],
            backgroundColor: ['#16a34a','#d97706','#dc2626','#0891b2','#7c3aed','#ea580c'],
            borderWidth: 0,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom' },
            tooltip: {
                callbacks: {
                    label: function(c) {
                        const total = c.dataset.data.reduce((a,b)=>a+b,0);
                        const pct = total > 0 ? (c.raw/total*100).toFixed(1) : 0;
                        return ` ${c.label}: ${c.raw} (${pct}%)`;
                    }
                }
            }
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>
