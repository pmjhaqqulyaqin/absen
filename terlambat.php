<?php
require_once 'includes/config.php';
cek_login();

$today   = date('Y-m-d');
$tanggal = sanitize($_GET['tanggal'] ?? $today);
$kelas   = sanitize($_GET['kelas']   ?? '');
$kelas_list = get_kelas_list();

$where = "a.tanggal='$tanggal' AND a.status='Terlambat'";
if ($kelas) $where .= " AND a.kelas='$kelas'";

$data = $conn->query("SELECT a.*, s.foto FROM absensi a LEFT JOIN siswa s ON s.id=a.siswa_id WHERE $where ORDER BY a.jam_masuk");

include 'includes/header.php';
?>

<div class="page-header">
    <div class="page-title"><i class="fas fa-clock" style="color:var(--warning)"></i> Siswa Terlambat</div>
    <div class="page-subtitle"><?= format_tanggal($tanggal) ?></div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="filter-bar">
            <input type="date" name="tanggal" class="form-control" value="<?= $tanggal ?>">
            <select name="kelas" class="form-select">
                <option value="">Semua Kelas</option>
                <?php foreach ($kelas_list as $k): ?>
                    <option value="<?= $k ?>" <?= $kelas==$k?'selected':'' ?>><?= $k ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Cari</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="fas fa-clock" style="color:var(--warning)"></i> Daftar Terlambat
        <span class="badge badge-terlambat ms-auto"><?= $data->num_rows ?> siswa</span>
    </div>
    <div class="table-container">
        <table id="mainTable">
            <thead>
                <tr><th>#</th><th>Foto</th><th>NIS</th><th>Nama</th><th>Kelas</th><th>Jam Masuk</th><th>Keterlambatan</th></tr>
            </thead>
            <tbody>
                <?php 
                $pengaturan = get_pengaturan();
                $jam_masuk  = strtotime($pengaturan['jam_masuk']);
                if ($data->num_rows === 0): ?>
                <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text-muted)">
                    Tidak ada siswa terlambat
                </td></tr>
                <?php else: $no=0; while ($row=$data->fetch_assoc()): $no++; 
                $telat = '';
                if ($row['jam_masuk']) {
                    $diff = strtotime($row['jam_masuk']) - $jam_masuk;
                    if ($diff > 0) {
                        $menit = floor($diff/60);
                        $jam   = floor($menit/60);
                        $telat = $jam > 0 ? "{$jam}j " . ($menit%60) . "m" : "{$menit} menit";
                    }
                }
                ?>
                <tr>
                    <td><?= $no ?></td>
                    <td>
                        <?php if (!empty($row['foto']) && file_exists('uploads/foto/'.$row['foto'])): ?>
                            <img src="<?= BASE_URL ?>uploads/foto/<?= $row['foto'] ?>" class="student-photo">
                        <?php else: ?>
                            <div class="student-avatar"><?= strtoupper(substr($row['nama'],0,1)) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= $row['nis'] ?></td>
                    <td><?= htmlspecialchars($row['nama']) ?></td>
                    <td><?= $row['kelas'] ?></td>
                    <td><strong><?= $row['jam_masuk'] ? date('H:i', strtotime($row['jam_masuk'])) : '-' ?></strong></td>
                    <td>
                        <?php if ($telat): ?>
                            <span style="color:var(--danger);font-weight:600">⏰ Terlambat <?= $telat ?></span>
                        <?php else: ?>-<?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
