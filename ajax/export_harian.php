<?php
require_once '../includes/config.php';
require_once '../includes/xlsx_writer.php';
cek_login();

$tanggal = sanitize($_GET['tanggal'] ?? date('Y-m-d'));
$kelas   = sanitize($_GET['kelas'] ?? '');
$pengaturan = get_pengaturan();

$where = "tanggal='$tanggal'";
if ($kelas) $where .= " AND kelas='$kelas'";

$data = $conn->query("SELECT nis, nama, kelas, jam_masuk, jam_pulang, status, keterangan, metode 
    FROM absensi WHERE $where ORDER BY kelas, nama");

$hari       = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
$bln_names  = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$ts         = strtotime($tanggal);
$tgl_format = $hari[date('w',$ts)].', '.date('d',$ts).' '.$bln_names[(int)date('n',$ts)].' '.date('Y',$ts);

$nama_sekolah = strtoupper($pengaturan['nama_sekolah'] ?? 'NAMA SEKOLAH');
$alamat       = $pengaturan['alamat'] ?? '';
$kepala       = $pengaturan['kepala_sekolah'] ?? '';
$nip          = $pengaturan['nip_kepala'] ?? '';
$lastCol      = 'I'; // 9 kolom

$stats = ['Hadir'=>0,'Terlambat'=>0,'Alpa'=>0,'Sakit'=>0,'Izin'=>0,'Bolos'=>0];
$rows_data = [];
while ($row = $data->fetch_assoc()) {
    $rows_data[] = $row;
    if (isset($stats[$row['status']])) $stats[$row['status']]++;
}
$total = count($rows_data);

$xlsx = new SimpleXLSX();
$xlsx->setColWidth(1, 5);  $xlsx->setColWidth(2, 14); $xlsx->setColWidth(3, 28);
$xlsx->setColWidth(4, 10); $xlsx->setColWidth(5, 12); $xlsx->setColWidth(6, 12);
$xlsx->setColWidth(7, 13); $xlsx->setColWidth(8, 25); $xlsx->setColWidth(9, 10);

// === KOP ===
$xlsx->addRow([[$nama_sekolah, SimpleXLSX::S_KOP_NAMA],'','','','','','','','']);
$xlsx->mergeCells('A1:'.$lastCol.'1');

$xlsx->addRow([[$alamat, SimpleXLSX::S_SUBTITLE],'','','','','','','','']);
$xlsx->mergeCells('A2:'.$lastCol.'2');

$xlsx->addEmptyRow(); // 3

$judul = 'REKAP ABSENSI HARIAN'.($kelas?' — KELAS '.$kelas:'');
$xlsx->addRow([[$judul, SimpleXLSX::S_TITLE],'','','','','','','','']);
$xlsx->mergeCells('A4:'.$lastCol.'4');

$xlsx->addRow([['Tanggal: '.$tgl_format, SimpleXLSX::S_SUBTITLE],'','','','','','','','']);
$xlsx->mergeCells('A5:'.$lastCol.'5');

$xlsx->addEmptyRow(); // 6

// === HEADER TABEL ===
$xlsx->addRow([
    ['No',         SimpleXLSX::S_HEADER],
    ['NIS',        SimpleXLSX::S_HEADER],
    ['Nama',       SimpleXLSX::S_HEADER],
    ['Kelas',      SimpleXLSX::S_HEADER],
    ['Jam Masuk',  SimpleXLSX::S_HEADER],
    ['Jam Pulang', SimpleXLSX::S_HEADER],
    ['Status',     SimpleXLSX::S_HEADER],
    ['Keterangan', SimpleXLSX::S_HEADER],
    ['Metode',     SimpleXLSX::S_HEADER],
]);

$no = 1;
foreach ($rows_data as $row) {
    $ss = [
        'Hadir'     => SimpleXLSX::S_HADIR,
        'Terlambat' => SimpleXLSX::S_TERLAMBAT,
        'Alpa'      => SimpleXLSX::S_ALPA,
        'Bolos'     => SimpleXLSX::S_ALPA,
    ][$row['status']] ?? SimpleXLSX::S_CENTER;

    $xlsx->addRow([
        [$no++,           SimpleXLSX::S_CENTER],
        [$row['nis'],     SimpleXLSX::S_CENTER],
        [$row['nama'],    SimpleXLSX::S_BORDER],
        [$row['kelas'],   SimpleXLSX::S_CENTER],
        [$row['jam_masuk']  ? date('H:i',strtotime($row['jam_masuk']))  : '-', SimpleXLSX::S_CENTER],
        [$row['jam_pulang'] ? date('H:i',strtotime($row['jam_pulang'])) : '-', SimpleXLSX::S_CENTER],
        [$row['status'],     $ss],
        [$row['keterangan'] ?? '', SimpleXLSX::S_BORDER],
        [$row['metode'],     SimpleXLSX::S_CENTER],
    ]);
}

// === RINGKASAN ===
$xlsx->addEmptyRow();
$xlsx->addRow([
    ['RINGKASAN',               SimpleXLSX::S_BOLD],
    ['Total: '.$total,          SimpleXLSX::S_BOLD],
    ['Hadir: '.$stats['Hadir'], SimpleXLSX::S_HADIR],
    ['Terlambat: '.$stats['Terlambat'], SimpleXLSX::S_TERLAMBAT],
    ['Alpa: '.$stats['Alpa'],   SimpleXLSX::S_ALPA],
    ['Sakit: '.$stats['Sakit'], SimpleXLSX::S_CENTER],
    ['Izin: '.$stats['Izin'],   SimpleXLSX::S_CENTER],
    ['Bolos: '.$stats['Bolos'], SimpleXLSX::S_ALPA],
    ['',                        SimpleXLSX::S_NORMAL],
]);

// === TANDA TANGAN ===
$xlsx->addEmptyRow();
$xlsx->addEmptyRow();
$xlsx->addEmptyRow();

$xlsx->addRow([
    ['','','','','','',
     ['Mengetahui,', SimpleXLSX::S_CENTER],
     ['',SimpleXLSX::S_NORMAL],
     ['',SimpleXLSX::S_NORMAL],
    ]
]);
$xlsx->addRow([
    ['','','','','','',
     ['Kepala Sekolah', SimpleXLSX::S_CENTER],
     ['',SimpleXLSX::S_NORMAL],
     ['',SimpleXLSX::S_NORMAL],
    ]
]);
$xlsx->addEmptyRow();
$xlsx->addEmptyRow();
$xlsx->addEmptyRow();
$xlsx->addRow([
    ['','','','','','',
     [$kepala ?: '(_______________________)', SimpleXLSX::S_BOLD],
     ['',SimpleXLSX::S_NORMAL],
     ['',SimpleXLSX::S_NORMAL],
    ]
]);
if ($nip) {
    $xlsx->addRow([
        ['','','','','','',
         ['NIP. '.$nip, SimpleXLSX::S_CENTER],
         ['',SimpleXLSX::S_NORMAL],
         ['',SimpleXLSX::S_NORMAL],
        ]
    ]);
}

$filename = 'Rekap_Harian_'.($kelas?$kelas.'_':'').str_replace('-','_',$tanggal).'.xlsx';
$xlsx->download($filename);
