<?php
require_once '../includes/config.php';
require_once '../includes/xlsx_writer.php';
cek_login();

$bulan = (int)($_GET['bulan'] ?? date('n'));
$tahun = (int)($_GET['tahun'] ?? date('Y'));
$kelas = sanitize($_GET['kelas'] ?? '');
$pengaturan = get_pengaturan();

$where_kelas = $kelas ? "AND s.kelas='$kelas'" : '';
$data = $conn->query("SELECT s.nis, s.nama, s.kelas,
    SUM(a.status='Hadir') as hadir,
    SUM(a.status='Terlambat') as terlambat,
    SUM(a.status='Alpa') as alpa,
    SUM(a.status='Sakit') as sakit,
    SUM(a.status='Izin') as izin,
    SUM(a.status='Bolos') as bolos,
    COUNT(a.id) as total_hari
    FROM siswa s
    LEFT JOIN absensi a ON a.siswa_id=s.id AND MONTH(a.tanggal)=$bulan AND YEAR(a.tanggal)=$tahun
    WHERE 1=1 $where_kelas GROUP BY s.id ORDER BY s.kelas, s.nama");

$bln_names = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$nama_sekolah = strtoupper($pengaturan['nama_sekolah'] ?? 'NAMA SEKOLAH');
$alamat       = $pengaturan['alamat'] ?? '';
$kepala       = $pengaturan['kepala_sekolah'] ?? '';
$nip          = $pengaturan['nip_kepala'] ?? '';
$lastCol      = 'K'; // 11 kolom

$rows_data = [];
while ($row = $data->fetch_assoc()) $rows_data[] = $row;
$total_siswa = count($rows_data);

$xlsx = new SimpleXLSX();
$xlsx->setColWidth(1, 5);  $xlsx->setColWidth(2, 14); $xlsx->setColWidth(3, 28);
$xlsx->setColWidth(4, 10); $xlsx->setColWidth(5, 9);  $xlsx->setColWidth(6, 11);
$xlsx->setColWidth(7, 9);  $xlsx->setColWidth(8, 9);  $xlsx->setColWidth(9, 9);
$xlsx->setColWidth(10, 9); $xlsx->setColWidth(11, 11);

// === KOP SEKOLAH ===
$xlsx->addRow([[$nama_sekolah, SimpleXLSX::S_KOP_NAMA],'','','','','','','','','','']);
$xlsx->mergeCells('A1:'.$lastCol.'1');

$xlsx->addRow([[$alamat, SimpleXLSX::S_SUBTITLE],'','','','','','','','','','']);
$xlsx->mergeCells('A2:'.$lastCol.'2');

$xlsx->addEmptyRow(); // baris 3

$judul = 'REKAP ABSENSI BULANAN — '.$bln_names[$bulan].' '.$tahun.($kelas?' — KELAS '.$kelas:'');
$xlsx->addRow([[$judul, SimpleXLSX::S_TITLE],'','','','','','','','','','']);
$xlsx->mergeCells('A4:'.$lastCol.'4');

$xlsx->addEmptyRow(); // baris 5

// === HEADER TABEL ===
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
    ['% Hadir',   SimpleXLSX::S_HEADER],
]);

$no = 1;
$sum = ['hadir'=>0,'terlambat'=>0,'alpa'=>0,'sakit'=>0,'izin'=>0,'bolos'=>0,'total'=>0];
foreach ($rows_data as $row) {
    $total = (int)$row['total_hari'];
    $pct   = $total > 0 ? round(($row['hadir'] + $row['terlambat']) / $total * 100, 1) : 0;
    $pctStyle = $pct >= 80 ? SimpleXLSX::S_HADIR : ($pct >= 60 ? SimpleXLSX::S_TERLAMBAT : SimpleXLSX::S_ALPA);
    foreach(['hadir','terlambat','alpa','sakit','izin','bolos'] as $k) $sum[$k] += (int)$row[$k];
    $sum['total'] += $total;

    $xlsx->addRow([
        [$no++,               SimpleXLSX::S_CENTER],
        [$row['nis'],         SimpleXLSX::S_CENTER],
        [$row['nama'],        SimpleXLSX::S_BORDER],
        [$row['kelas'],       SimpleXLSX::S_CENTER],
        [(int)$row['hadir'],     SimpleXLSX::S_HADIR],
        [(int)$row['terlambat'], SimpleXLSX::S_TERLAMBAT],
        [(int)$row['alpa'],      SimpleXLSX::S_ALPA],
        [(int)$row['sakit'],     SimpleXLSX::S_NUMBER],
        [(int)$row['izin'],      SimpleXLSX::S_NUMBER],
        [(int)$row['bolos'],     SimpleXLSX::S_ALPA],
        [$pct.'%',            $pctStyle],
    ]);
}

// Baris total
$xlsx->addEmptyRow();
$tot_pct = $sum['total'] > 0 ? round(($sum['hadir']+$sum['terlambat'])/$sum['total']*100,1) : 0;
$xlsx->addRow([
    ['TOTAL',          SimpleXLSX::S_BOLD],
    ['',               SimpleXLSX::S_BOLD],
    [$total_siswa.' siswa', SimpleXLSX::S_BOLD],
    ['',               SimpleXLSX::S_BOLD],
    [$sum['hadir'],    SimpleXLSX::S_HADIR],
    [$sum['terlambat'],SimpleXLSX::S_TERLAMBAT],
    [$sum['alpa'],     SimpleXLSX::S_ALPA],
    [$sum['sakit'],    SimpleXLSX::S_NUMBER],
    [$sum['izin'],     SimpleXLSX::S_NUMBER],
    [$sum['bolos'],    SimpleXLSX::S_ALPA],
    [$tot_pct.'%',     SimpleXLSX::S_BOLD],
]);

// === TANDA TANGAN ===
$xlsx->addEmptyRow();
$xlsx->addEmptyRow();
$xlsx->addEmptyRow();

// Baris "Mengetahui" di kolom I-K
$xlsx->addRow([
    ['','','','','','','','',
     ['Mengetahui,',  SimpleXLSX::S_CENTER],
     ['',SimpleXLSX::S_NORMAL],
     ['',SimpleXLSX::S_NORMAL],
    ]
]);
$xlsx->addRow([
    ['','','','','','','','',
     ['Kepala Sekolah', SimpleXLSX::S_CENTER],
     ['',SimpleXLSX::S_NORMAL],
     ['',SimpleXLSX::S_NORMAL],
    ]
]);
$xlsx->addEmptyRow();
$xlsx->addEmptyRow();
$xlsx->addEmptyRow();
$xlsx->addRow([
    ['','','','','','','','',
     [$kepala ?: '(_______________________)', SimpleXLSX::S_BOLD],
     ['',SimpleXLSX::S_NORMAL],
     ['',SimpleXLSX::S_NORMAL],
    ]
]);
if ($nip) {
    $xlsx->addRow([
        ['','','','','','','','',
         ['NIP. '.$nip, SimpleXLSX::S_CENTER],
         ['',SimpleXLSX::S_NORMAL],
         ['',SimpleXLSX::S_NORMAL],
        ]
    ]);
}

$filename = 'Rekap_Bulanan_'.$bln_names[$bulan].'_'.$tahun.($kelas?'_'.$kelas:'').'.xlsx';
$xlsx->download($filename);
