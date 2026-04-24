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

$bln_names    = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$hari_singkat = ['','Sen','Sel','Rab','Kam','Jum','Sab','Min'];
$nama_sekolah = strtoupper($pengaturan['nama_sekolah'] ?? 'NAMA SEKOLAH');
$alamat       = $pengaturan['alamat'] ?? '';
$kepala       = $pengaturan['kepala_sekolah'] ?? '';
$nip          = $pengaturan['nip_kepala'] ?? '';
$jml_hari     = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);

// Ambil data siswa
$where_kelas = $kelas ? "AND kelas='$kelas'" : '';
$siswa_list  = $conn->query("SELECT id, nis, nama, kelas FROM siswa WHERE 1=1 $where_kelas ORDER BY kelas, nama");
$siswa_arr   = [];
while ($s = $siswa_list->fetch_assoc()) $siswa_arr[] = $s;
$siswa_ids   = array_column($siswa_arr, 'id');

// Ambil semua absensi bulan ini
$absensi_map = [];
if ($siswa_ids) {
    $ids_str = implode(',', $siswa_ids);
    $ab_res  = $conn->query("SELECT siswa_id, DAY(tanggal) as tgl, status
        FROM absensi WHERE MONTH(tanggal)=$bulan AND YEAR(tanggal)=$tahun
        AND siswa_id IN ($ids_str)");
    while ($a = $ab_res->fetch_assoc()) {
        $absensi_map[$a['siswa_id']][$a['tgl']] = $a['status'];
    }
}

// Status → kode + style
$st_map = [
    'Hadir'     => ['H', SimpleXLSX::S_HADIR],
    'Terlambat' => ['T', SimpleXLSX::S_TERLAMBAT],
    'Alpa'      => ['A', SimpleXLSX::S_ALPA],
    'Sakit'     => ['S', SimpleXLSX::S_NUMBER],
    'Izin'      => ['I', SimpleXLSX::S_NUMBER],
    'Bolos'     => ['B', SimpleXLSX::S_ALPA],
];

// Kolom: No(1), NIS(2), Nama(3), Kelas(4), hari1..N, H, T, A, S, I, B
$total_col   = 4 + $jml_hari + 6;
$col_letters = [];
for ($c = 1; $c <= $total_col; $c++) {
    $col = '';
    $n   = $c;
    while ($n > 0) {
        $n--;
        $col = chr(65 + ($n % 26)) . $col;
        $n   = (int)($n / 26);
    }
    $col_letters[$c] = $col;
}
$lastCol = $col_letters[$total_col];

$xlsx = new SimpleXLSX();

// Lebar kolom
$xlsx->setColWidth(1, 5);   // No
$xlsx->setColWidth(2, 14);  // NIS
$xlsx->setColWidth(3, 28);  // Nama
$xlsx->setColWidth(4, 9);   // Kelas
for ($d = 1; $d <= $jml_hari; $d++) {
    $xlsx->setColWidth(4 + $d, 4); // Setiap hari
}
foreach ([1,2,3,4,5,6] as $i) {
    $xlsx->setColWidth(4 + $jml_hari + $i, 5); // H,T,A,S,I,B
}

// ── KOP ─────────────────────────────────────────────────────
$empty12 = array_fill(0, $total_col - 1, '');
$xlsx->addRow(array_merge([[$nama_sekolah, SimpleXLSX::S_KOP_NAMA]], $empty12));
$xlsx->mergeCells("A1:{$lastCol}1");

$xlsx->addRow(array_merge([[$alamat, SimpleXLSX::S_SUBTITLE]], $empty12));
$xlsx->mergeCells("A2:{$lastCol}2");
$xlsx->addEmptyRow();

$judul = 'DAFTAR HADIR SISWA — '.$bln_names[$bulan].' '.$tahun.($kelas?' — KELAS '.$kelas:'');
$xlsx->addRow(array_merge([[$judul, SimpleXLSX::S_TITLE]], $empty12));
$xlsx->mergeCells("A4:{$lastCol}4");
$xlsx->addEmptyRow();

// ── HEADER BARIS 1: label hari ──────────────────────────────
$hdr1 = [
    ['No',    SimpleXLSX::S_HEADER],
    ['NIS',   SimpleXLSX::S_HEADER],
    ['Nama',  SimpleXLSX::S_HEADER],
    ['Kelas', SimpleXLSX::S_HEADER],
];
for ($d = 1; $d <= $jml_hari; $d++) {
    $dn       = date('N', mktime(0,0,0,$bulan,$d,$tahun));
    $isWeekend = $dn >= 6;
    $hdr1[]   = [$hari_singkat[$dn]."\n".$d, $isWeekend ? SimpleXLSX::S_ALPA : SimpleXLSX::S_HEADER];
}
foreach (['H','T','A','S','I','B'] as $lbl) {
    $styleMap = ['H'=>SimpleXLSX::S_HADIR,'T'=>SimpleXLSX::S_TERLAMBAT,'A'=>SimpleXLSX::S_ALPA,
                 'S'=>SimpleXLSX::S_NUMBER,'I'=>SimpleXLSX::S_NUMBER,'B'=>SimpleXLSX::S_ALPA];
    $hdr1[] = [$lbl, $styleMap[$lbl]];
}
$xlsx->addRow($hdr1);

// ── DATA SISWA ───────────────────────────────────────────────
$no = 1;
$totals_col = array_fill(0, 6, 0); // H,T,A,S,I,B totals per column

foreach ($siswa_arr as $s) {
    $sid   = $s['id'];
    $tot   = ['H'=>0,'T'=>0,'A'=>0,'S'=>0,'I'=>0,'B'=>0];
    $row   = [
        [$no++,         SimpleXLSX::S_CENTER],
        [$s['nis'],     SimpleXLSX::S_CENTER],
        [$s['nama'],    SimpleXLSX::S_BORDER],
        [$s['kelas'],   SimpleXLSX::S_CENTER],
    ];

    for ($d = 1; $d <= $jml_hari; $d++) {
        $dn        = date('N', mktime(0,0,0,$bulan,$d,$tahun));
        $isWeekend = $dn >= 6;
        $st        = $absensi_map[$sid][$d] ?? null;

        if ($st && isset($st_map[$st])) {
            [$kd, $style] = $st_map[$st];
            $row[] = [$kd, $style];
            $tot[$kd]++;
        } elseif ($isWeekend) {
            $row[] = ['-', SimpleXLSX::S_CENTER];
        } else {
            $row[] = ['', SimpleXLSX::S_BORDER];
        }
    }

    // Kolom total H,T,A,S,I,B
    foreach (['H','T','A','S','I','B'] as $i => $k) {
        $styleMap = ['H'=>SimpleXLSX::S_HADIR,'T'=>SimpleXLSX::S_TERLAMBAT,'A'=>SimpleXLSX::S_ALPA,
                     'S'=>SimpleXLSX::S_NUMBER,'I'=>SimpleXLSX::S_NUMBER,'B'=>SimpleXLSX::S_ALPA];
        $row[] = [$tot[$k] > 0 ? $tot[$k] : '', $styleMap[$k]];
        $totals_col[$i] += $tot[$k];
    }

    $xlsx->addRow($row);
}

// ── BARIS TOTAL ──────────────────────────────────────────────
$xlsx->addEmptyRow();
$tot_row = [
    ['TOTAL',               SimpleXLSX::S_BOLD],
    ['',                    SimpleXLSX::S_BOLD],
    [count($siswa_arr).' siswa', SimpleXLSX::S_BOLD],
    ['',                    SimpleXLSX::S_BOLD],
];
for ($d = 1; $d <= $jml_hari; $d++) {
    $tot_row[] = ['', SimpleXLSX::S_BORDER];
}
foreach (['H','T','A','S','I','B'] as $i => $k) {
    $styleMap = ['H'=>SimpleXLSX::S_HADIR,'T'=>SimpleXLSX::S_TERLAMBAT,'A'=>SimpleXLSX::S_ALPA,
                 'S'=>SimpleXLSX::S_NUMBER,'I'=>SimpleXLSX::S_NUMBER,'B'=>SimpleXLSX::S_ALPA];
    $tot_row[] = [$totals_col[$i], $styleMap[$k]];
}
$xlsx->addRow($tot_row);

// ── KETERANGAN ───────────────────────────────────────────────
$xlsx->addEmptyRow();
$ket_row = array_fill(0, $total_col, '');
$ket_row[0] = ['Keterangan: H=Hadir  T=Terlambat  A=Alpa  S=Sakit  I=Izin  B=Bolos  (-=Libur/Sabtu-Minggu)', SimpleXLSX::S_NORMAL];
$xlsx->addRow($ket_row);

// ── TANDA TANGAN ─────────────────────────────────────────────
$xlsx->addEmptyRow();$xlsx->addEmptyRow();$xlsx->addEmptyRow();
$ttd_base = array_fill(0, $total_col, '');

$ttd1 = $ttd_base; $ttd1[$total_col - 3] = ['Mengetahui,', SimpleXLSX::S_CENTER];
$xlsx->addRow($ttd1);
$ttd2 = $ttd_base; $ttd2[$total_col - 3] = ['Kepala Sekolah', SimpleXLSX::S_CENTER];
$xlsx->addRow($ttd2);
$xlsx->addEmptyRow();$xlsx->addEmptyRow();$xlsx->addEmptyRow();
$ttd3 = $ttd_base; $ttd3[$total_col - 3] = [$kepala ?: '(_______________________)', SimpleXLSX::S_BOLD];
$xlsx->addRow($ttd3);
if ($nip) {
    $ttd4 = $ttd_base; $ttd4[$total_col - 3] = ['NIP. '.$nip, SimpleXLSX::S_CENTER];
    $xlsx->addRow($ttd4);
}

$filename = 'Rekap_Kalender_'.$bln_names[$bulan].'_'.$tahun.($kelas?'_Kelas_'.str_replace(' ','-',$kelas):'').'.xlsx';
$xlsx->download($filename);
