<?php
require_once '../includes/config.php';
require_once '../includes/xlsx_writer.php';
// Izinkan akses oleh admin atau kepsek
if (!isset($_SESSION["admin_id"]) && !isset($_SESSION["kepsek_id"])) {
    header("Location: ../portal_kepsek_login.php"); exit;
}

$bulan = (int)($_GET['bulan'] ?? date('n'));
$tahun = (int)($_GET['tahun'] ?? date('Y'));
$kelas = sanitize($_GET['kelas'] ?? '');
$pengaturan = get_pengaturan();

$bln_names   = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$nama_sekolah = strtoupper($pengaturan['nama_sekolah'] ?? 'NAMA SEKOLAH');
$alamat       = $pengaturan['alamat'] ?? '';
$kepala       = $pengaturan['kepala_sekolah'] ?? '';
$nip          = $pengaturan['nip_kepala'] ?? '';

$where_kelas = $kelas ? "AND s.kelas='$kelas'" : '';
$data = $conn->query("SELECT s.nis, s.nama, s.kelas,
    SUM(a.status='Hadir')     as hadir,
    SUM(a.status='Terlambat') as terlambat,
    SUM(a.status='Alpa')      as alpa,
    SUM(a.status='Sakit')     as sakit,
    SUM(a.status='Izin')      as izin,
    SUM(a.status='Bolos')     as bolos,
    COUNT(a.id)               as total_hari
    FROM siswa s
    LEFT JOIN absensi a ON a.siswa_id=s.id
        AND MONTH(a.tanggal)=$bulan AND YEAR(a.tanggal)=$tahun
    WHERE 1=1 $where_kelas
    GROUP BY s.id ORDER BY s.kelas, s.nama");

$rows_data = [];
while ($row = $data->fetch_assoc()) $rows_data[] = $row;
$total_siswa = count($rows_data);

// Jumlah hari kerja (Senin-Jumat) bulan ini
$hari_kerja = 0;
$jml_hari   = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);
for ($d = 1; $d <= $jml_hari; $d++) {
    $dn = date('N', mktime(0,0,0,$bulan,$d,$tahun));
    if ($dn < 6) $hari_kerja++;
}

// 12 kolom: No,NIS,Nama,Kelas,H,T,A,S,I,B,Total,% Hadir
$lastCol = 'L';

$xlsx = new SimpleXLSX();
$xlsx->setColWidth(1, 5);   // No
$xlsx->setColWidth(2, 15);  // NIS
$xlsx->setColWidth(3, 30);  // Nama
$xlsx->setColWidth(4, 10);  // Kelas
$xlsx->setColWidth(5, 9);   // Hadir
$xlsx->setColWidth(6, 11);  // Terlambat
$xlsx->setColWidth(7, 9);   // Alpa
$xlsx->setColWidth(8, 9);   // Sakit
$xlsx->setColWidth(9, 9);   // Izin
$xlsx->setColWidth(10, 9);  // Bolos
$xlsx->setColWidth(11, 9);  // Total
$xlsx->setColWidth(12, 11); // % Hadir

// ── KOP ─────────────────────────────────────────────────────
$xlsx->addRow([[$nama_sekolah, SimpleXLSX::S_KOP_NAMA],'','','','','','','','','','','']);
$xlsx->mergeCells("A1:{$lastCol}1");
$xlsx->addRow([[$alamat, SimpleXLSX::S_SUBTITLE],'','','','','','','','','','','']);
$xlsx->mergeCells("A2:{$lastCol}2");
$xlsx->addEmptyRow();

$judul = 'REKAP ABSENSI BULANAN — '.$bln_names[$bulan].' '.$tahun.($kelas?' — KELAS '.$kelas:'');
$xlsx->addRow([[$judul, SimpleXLSX::S_TITLE],'','','','','','','','','','','']);
$xlsx->mergeCells("A4:{$lastCol}4");
$xlsx->addEmptyRow();

// ── HEADER ──────────────────────────────────────────────────
$xlsx->addRow([
    ['No',        SimpleXLSX::S_HEADER],
    ['NIS',       SimpleXLSX::S_HEADER],
    ['Nama',      SimpleXLSX::S_HEADER],
    ['Kelas',     SimpleXLSX::S_HEADER],
    ['Hadir',     SimpleXLSX::S_HEADER],
    ['Terlambat', SimpleXLSX::S_HEADER],
    ['Alpa',      SimpleXLSX::S_HEADER],
    ['Sakit',     SimpleXLSX::S_HEADER],
    ['Izin',      SimpleXLSX::S_HEADER],
    ['Bolos',     SimpleXLSX::S_HEADER],
    ['Total',     SimpleXLSX::S_HEADER],
    ['% Hadir',   SimpleXLSX::S_HEADER],
]);

// ── DATA ─────────────────────────────────────────────────────
$no  = 1;
$sum = ['hadir'=>0,'terlambat'=>0,'alpa'=>0,'sakit'=>0,'izin'=>0,'bolos'=>0,'total'=>0];

foreach ($rows_data as $row) {
    $total = (int)$row['total_hari'];
    $hadir = (int)$row['hadir'] + (int)$row['terlambat'];
    $pct   = $total > 0 ? round($hadir / $total * 100, 1) : 0;
    $pctStyle = $pct >= 80 ? SimpleXLSX::S_HADIR : ($pct >= 60 ? SimpleXLSX::S_TERLAMBAT : SimpleXLSX::S_ALPA);

    foreach (['hadir','terlambat','alpa','sakit','izin','bolos'] as $k) $sum[$k] += (int)$row[$k];
    $sum['total'] += $total;

    $xlsx->addRow([
        [$no++,                   SimpleXLSX::S_CENTER],
        [$row['nis'],             SimpleXLSX::S_CENTER],
        [$row['nama'],            SimpleXLSX::S_BORDER],
        [$row['kelas'],           SimpleXLSX::S_CENTER],
        [(int)$row['hadir'],      SimpleXLSX::S_HADIR],
        [(int)$row['terlambat'],  SimpleXLSX::S_TERLAMBAT],
        [(int)$row['alpa'],       SimpleXLSX::S_ALPA],
        [(int)$row['sakit'],      SimpleXLSX::S_NUMBER],
        [(int)$row['izin'],       SimpleXLSX::S_NUMBER],
        [(int)$row['bolos'],      SimpleXLSX::S_ALPA],
        [$total,                  SimpleXLSX::S_NUMBER],
        [$pct.'%',                $pctStyle],
    ]);
}

// ── TOTAL ────────────────────────────────────────────────────
$xlsx->addEmptyRow();
$tot_pct  = $sum['total']>0 ? round(($sum['hadir']+$sum['terlambat'])/$sum['total']*100,1) : 0;
$xlsx->addRow([
    ['TOTAL',                 SimpleXLSX::S_BOLD],
    ['',                      SimpleXLSX::S_BOLD],
    [$total_siswa.' siswa',   SimpleXLSX::S_BOLD],
    ['',                      SimpleXLSX::S_BOLD],
    [$sum['hadir'],           SimpleXLSX::S_HADIR],
    [$sum['terlambat'],       SimpleXLSX::S_TERLAMBAT],
    [$sum['alpa'],            SimpleXLSX::S_ALPA],
    [$sum['sakit'],           SimpleXLSX::S_NUMBER],
    [$sum['izin'],            SimpleXLSX::S_NUMBER],
    [$sum['bolos'],           SimpleXLSX::S_ALPA],
    [$sum['total'],           SimpleXLSX::S_NUMBER],
    [$tot_pct.'%',            SimpleXLSX::S_BOLD],
]);

// ── KETERANGAN ───────────────────────────────────────────────
$xlsx->addEmptyRow();
$xlsx->addRow([
    ['Keterangan: H=Hadir  T=Terlambat  A=Alpa  S=Sakit  I=Izin  B=Bolos', SimpleXLSX::S_NORMAL],
    '','','','','','','','','','',''
]);
$xlsx->mergeCells("A".($total_siswa+11).":".$lastCol.($total_siswa+11));

// ── TANDA TANGAN ─────────────────────────────────────────────
$xlsx->addEmptyRow();$xlsx->addEmptyRow();$xlsx->addEmptyRow();
$ttd_row = $total_siswa + 15;
$xlsx->addRow(['','','','','','','','','',
    ['Mengetahui,', SimpleXLSX::S_CENTER],'','']);
$xlsx->addRow(['','','','','','','','','',
    ['Kepala Sekolah', SimpleXLSX::S_CENTER],'','']);
$xlsx->addEmptyRow();$xlsx->addEmptyRow();$xlsx->addEmptyRow();
$xlsx->addRow(['','','','','','','','','',
    [$kepala ?: '(_______________________)', SimpleXLSX::S_BOLD],'','']);
if ($nip) {
    $xlsx->addRow(['','','','','','','','','',
        ['NIP. '.$nip, SimpleXLSX::S_CENTER],'','']);
}

$filename = 'Rekap_Persentase_'.$bln_names[$bulan].'_'.$tahun.($kelas?'_Kelas_'.str_replace(' ','-',$kelas):'').'.xlsx';
$xlsx->download($filename);
