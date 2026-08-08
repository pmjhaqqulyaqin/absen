<?php
require_once 'includes/config.php';
require_once 'includes/xlsx_writer.php';
cek_wali();

$wid  = $_SESSION['wali_id'];
$wali = $conn->query("SELECT * FROM wali WHERE id=$wid")->fetch_assoc();
$pengaturan = get_pengaturan();

if (isset($_GET['logout'])) { session_destroy(); header('Location: '.BASE_URL.'portal_login.php?role=wali'); exit; }

// ── Buat tabel pesan_wali jika belum ada ──────────────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS pesan_wali (
    id INT AUTO_INCREMENT PRIMARY KEY,
    wali_id INT NOT NULL,
    siswa_id INT NOT NULL,
    pengirim ENUM('wali','siswa') NOT NULL DEFAULT 'wali',
    pesan TEXT NOT NULL,
    dibaca TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ws (wali_id, siswa_id),
    INDEX idx_siswa (siswa_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── Page routing ──────────────────────────────────────────────────────
$page    = $_GET['page']    ?? 'dashboard';
$sid_url = isset($_GET['sid']) ? (int)$_GET['sid'] : 0;
$bulan   = (int)($_GET['bulan'] ?? date('n'));
$tahun   = (int)($_GET['tahun'] ?? date('Y'));

// ── Data anak didik (HARUS didefinisikan sebelum handler AJAX di bawah,
//    karena handler tersebut memakai $anak_ids untuk validasi kepemilikan) ──
// Akun "Wali" di sistem ini bisa berupa 2 jenis:
//   1) WALI KELAS  -> punya $wali['kelas_wali'] terisi (mis. "X-1"), melihat SEMUA siswa di kelas itu.
//   2) WALI MURID/ORTU -> $wali['kelas_wali'] kosong, siswa yang dilihat ditentukan lewat tabel wali_siswa.
// Sebelumnya kode SELALU pakai tabel wali_siswa meski akunnya wali kelas, sehingga
// Data Siswa/Rekap Absensi/Rekap Pelanggaran/Absen Manual/Edit Absensi/Kunjungan selalu kosong
// untuk akun wali kelas (hanya "Laporan Rekap Harian" yang sudah benar pakai kelas_wali).
$kelas_wali_login = trim($wali['kelas_wali'] ?? '');
if ($kelas_wali_login !== '') {
    $kelas_wali_esc = $conn->real_escape_string($kelas_wali_login);
    $anak_res = $conn->query("SELECT * FROM siswa WHERE kelas='$kelas_wali_esc' ORDER BY nama");
} else {
    $anak_res = $conn->query("SELECT s.* FROM siswa s
        JOIN wali_siswa ws ON ws.siswa_id=s.id
        WHERE ws.wali_id=$wid ORDER BY s.nama");
}
$anak_list = [];
while ($r = $anak_res->fetch_assoc()) $anak_list[] = $r;
$anak_ids = array_column($anak_list, 'id');
$anak_ids_str = $anak_ids ? implode(',', $anak_ids) : '0';

// ── Validasi akses ke siswa tertentu ─────────────────────────────────
$siswa_ok = $sid_url && in_array($sid_url, $anak_ids);

// ── EDIT ABSENSI: Simpan satu record ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wali_edit_single'])) {
    header('Content-Type: application/json');
    $absen_id   = (int)($_POST['absen_id'] ?? 0);
    $new_status = trim($_POST['new_status'] ?? '');
    $new_ket    = $conn->real_escape_string(trim($_POST['new_keterangan'] ?? ''));
    $valid_st   = ['Hadir','Terlambat','Alpa','Sakit','Izin','Bolos'];
    // Pastikan siswa ini milik wali yang login
    $ok = false;
    if ($absen_id && in_array($new_status, $valid_st)) {
        $ab = $conn->query("SELECT a.siswa_id FROM absensi a WHERE a.id=$absen_id")->fetch_assoc();
        if ($ab && in_array((int)$ab['siswa_id'], $anak_ids)) {
            $ex = $conn->query("SELECT jam_masuk FROM absensi WHERE id=$absen_id")->fetch_assoc();
            $jam_final = ($ex && $ex['jam_masuk'] && in_array($new_status,['Hadir','Terlambat'])) ? "'{$ex['jam_masuk']}'" : "NULL";
            if (in_array($new_status,['Alpa','Sakit','Izin','Bolos'])) $jam_final = "NULL";
            $conn->query("UPDATE absensi SET status='$new_status', keterangan='$new_ket', jam_masuk=$jam_final, metode='Manual', updated_at=NOW() WHERE id=$absen_id");
            $ok = true;
        }
    }
    echo json_encode(['ok'=>$ok]);
    exit;
}

// ── EDIT ABSENSI: Hapus record ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wali_hapus_absen'])) {
    header('Content-Type: application/json');
    $absen_id = (int)($_POST['absen_id'] ?? 0);
    $ok = false;
    if ($absen_id) {
        $ab = $conn->query("SELECT siswa_id FROM absensi WHERE id=$absen_id")->fetch_assoc();
        if ($ab && in_array((int)$ab['siswa_id'], $anak_ids)) {
            $conn->query("DELETE FROM absensi WHERE id=$absen_id");
            $ok = true;
        }
    }
    echo json_encode(['ok'=>$ok]);
    exit;
}

// ── KIRIM PESAN (AJAX) ────────────────────────────────────────────────
if (isset($_POST['ajax_kirim_pesan'])) {
    header('Content-Type: application/json');
    $sid  = (int)($_POST['siswa_id'] ?? 0);
    $pesan = trim($_POST['pesan'] ?? '');
    if ($sid && in_array($sid, $anak_ids) && $pesan !== '') {
        $ps = $conn->real_escape_string($pesan);
        $conn->query("INSERT INTO pesan_wali (wali_id,siswa_id,pengirim,pesan) VALUES ($wid,$sid,'wali','$ps')");
        echo json_encode(['ok'=>true,'waktu'=>date('H:i'),'pesan'=>htmlspecialchars($pesan)]);
    } else {
        echo json_encode(['ok'=>false]);
    }
    exit;
}

// ── ABSEN MANUAL (AJAX) ──────────────────────────────────────────────
if (isset($_POST['ajax_absen_manual'])) {
    header('Content-Type: application/json');
    $sid      = (int)($_POST['siswa_id'] ?? 0);
    $status   = $_POST['status'] ?? '';
    $tanggal  = $_POST['tanggal'] ?? date('Y-m-d');
    $ket      = $conn->real_escape_string(trim($_POST['keterangan'] ?? ''));
    $valid_st = ['Hadir','Terlambat','Alpa','Sakit','Izin','Bolos'];
    // Validasi tanggal tidak lebih dari hari ini
    if ($tanggal > date('Y-m-d')) { echo json_encode(['ok'=>false,'msg'=>'Tanggal tidak boleh melebihi hari ini']); exit; }
    if ($sid && in_array($sid, $anak_ids) && in_array($status, $valid_st)) {
        $s_info = $conn->query("SELECT nis, nama, kelas FROM siswa WHERE id=$sid")->fetch_assoc();
        $existing = $conn->query("SELECT id FROM absensi WHERE siswa_id=$sid AND tanggal='$tanggal'" . periode_where($conn))->fetch_assoc();
        $jam = date('H:i:s');
        if ($existing) {
            $conn->query("UPDATE absensi SET status='$status', jam_masuk='$jam', keterangan='$ket', metode='Manual' WHERE id={$existing['id']}");
        } else {
            $nis   = $conn->real_escape_string($s_info['nis']);
            $nama  = $conn->real_escape_string($s_info['nama']);
            $kelas = $conn->real_escape_string($s_info['kelas']);
            list($ptaW,$psemW) = periode_values($conn);
            $conn->query("INSERT INTO absensi (siswa_id, nis, nama, kelas, tanggal, jam_masuk, status, keterangan, metode, tahun_ajaran, semester) VALUES ($sid,'$nis','$nama','$kelas','$tanggal','$jam','$status','$ket','Manual',$ptaW,$psemW)");
        }
        echo json_encode(['ok'=>true,'status'=>$status,'msg'=>'Absensi berhasil disimpan']);
    } else {
        echo json_encode(['ok'=>false,'msg'=>'Data tidak valid']);
    }
    exit;
}

// ── AMBIL PESAN (AJAX) ────────────────────────────────────────────────
if (isset($_GET['ajax_pesan']) && isset($_GET['sid'])) {
    header('Content-Type: application/json');
    $sid  = (int)$_GET['sid'];
    $last = (int)($_GET['last_id'] ?? 0);
    if (!in_array($sid, $anak_ids)) { echo json_encode(['messages'=>[]]); exit; }
    $conn->query("UPDATE pesan_wali SET dibaca=1 WHERE siswa_id=$sid AND wali_id=$wid AND pengirim='siswa'");
    $rows = [];
    $res  = $conn->query("SELECT * FROM pesan_wali WHERE wali_id=$wid AND siswa_id=$sid AND id>$last ORDER BY id ASC LIMIT 50");
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    echo json_encode(['messages'=>$rows]);
    exit;
}

// ── AJAX: Riwayat / profil siswa (dipakai modal Data Siswa, senada Portal BK) ──
// Catatan: TIDAK ada input Point Perbaikan di sini — fitur itu tetap khusus milik Portal BK.
if (isset($_GET['ajax']) && $_GET['ajax'] === 'riwayat') {
    header('Content-Type: application/json');
    $sid = (int)($_GET['id'] ?? 0);
    if (!$sid || !in_array($sid, $anak_ids)) { echo json_encode(['ok'=>false,'msg'=>'Siswa tidak ditemukan / bukan wali kelasnya']); exit; }
    $sw  = $conn->query("SELECT * FROM siswa WHERE id=$sid")->fetch_assoc();
    if (!$sw) { echo json_encode(['ok'=>false,'msg'=>'Siswa tidak ditemukan']); exit; }

    $abs = $conn->query("SELECT status,COUNT(*) c FROM absensi WHERE siswa_id=$sid" . periode_where($conn) . " GROUP BY status")->fetch_all(MYSQLI_ASSOC);
    $absMap = array_column($abs,'c','status');
    $jmlPel = (int)$conn->query("SELECT COUNT(*) c FROM pelanggaran WHERE siswa_id=$sid" . periode_where($conn))->fetch_assoc()['c'];
    $jmlKnj = (int)$conn->query("SELECT COUNT(*) c FROM kunjungan_rumah WHERE siswa_id=$sid" . periode_where($conn))->fetch_assoc()['c'];
    $poinPel = (int)($conn->query("SELECT COALESCE(SUM(COALESCE(poin,3)),0) s FROM pelanggaran WHERE siswa_id=$sid" . periode_where($conn))->fetch_assoc()['s'] ?? 0);
    $poinBk  = (int)($conn->query("SELECT COALESCE(SUM(poin),0) s FROM buku_kejadian WHERE siswa_id=$sid" . periode_where($conn))->fetch_assoc()['s'] ?? 0);

    // Poin per-kejadian Alpa & Terlambat (pengaturan yang sama dipakai Portal BK)
    $conn->query("CREATE TABLE IF NOT EXISTS pengaturan_poin_absen (
        id             INT AUTO_INCREMENT PRIMARY KEY,
        poin_alpa      INT(11) DEFAULT 5,
        poin_terlambat INT(11) DEFAULT 2,
        keterangan     VARCHAR(255) DEFAULT ''
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $poinCfgRow = $conn->query("SELECT * FROM pengaturan_poin_absen LIMIT 1")->fetch_assoc();
    $poinAlpaCfg      = (int)($poinCfgRow['poin_alpa'] ?? 5);
    $poinTerlambatCfg = (int)($poinCfgRow['poin_terlambat'] ?? 2);

    // Point perbaikan (diinput oleh BK) — hanya dibaca di sini untuk menandai status "Sudah Diperbaiki"
    $perbaikan = ['TERLAMBAT'=>0,'ALPA'=>0,'PELANGGARAN'=>0,'KUNJUNGAN'=>0];
    $resPerb = $conn->query("SELECT kategori, SUM(jumlah) total FROM point_perbaikan WHERE siswa_id=$sid" . periode_where($conn) . " GROUP BY kategori");
    if ($resPerb) { while ($r=$resPerb->fetch_assoc()) $perbaikan[$r['kategori']] = (int)$r['total']; }

    $timeline = [];
    $q3 = $conn->query("SELECT id, tanggal tgl, 'Pelanggaran Disiplin' tipe, UPPER(jenis_nama) judul, CONCAT('+',COALESCE(poin,3),' Poin') ket FROM pelanggaran WHERE siswa_id=$sid" . periode_where($conn) . "");
    while ($x=$q3->fetch_assoc()) $timeline[] = $x;
    $q5 = $conn->query("SELECT id, tanggal tgl, 'Terlambat' tipe, 'TERLAMBAT' judul, CONCAT('+',$poinTerlambatCfg,' Poin') ket FROM absensi WHERE siswa_id=$sid AND status='Terlambat'" . periode_where($conn) . "");
    while ($x=$q5->fetch_assoc()) $timeline[] = $x;
    $q6 = $conn->query("SELECT id, tanggal tgl, 'Alpa' tipe, 'ALPA' judul, CONCAT('+',$poinAlpaCfg,' Poin') ket FROM absensi WHERE siswa_id=$sid AND status='Alpa'" . periode_where($conn) . "");
    while ($x=$q6->fetch_assoc()) $timeline[] = $x;
    usort($timeline, fn($a,$b)=>strtotime($b['tgl'])<=>strtotime($a['tgl']));

    $poinPelNet       = max(0, $poinPel - $perbaikan['PELANGGARAN']);
    $poinTerlambatTot = (int)($absMap['Terlambat'] ?? 0) * $poinTerlambatCfg;
    $poinAlpaTot      = (int)($absMap['Alpa'] ?? 0) * $poinAlpaCfg;
    $poinTerlambatNet = max(0, $poinTerlambatTot - $perbaikan['TERLAMBAT']);
    $poinAlpaNet      = max(0, $poinAlpaTot - $perbaikan['ALPA']);
    $totalPoin        = max(0, $poinPelNet + $poinBk + $poinTerlambatNet + $poinAlpaNet - $perbaikan['KUNJUNGAN']);

    // Tandai entri yang sudah "tertutup" oleh point perbaikan yang diinput BK (dihitung dari yang paling lama)
    $sisaT = $perbaikan['TERLAMBAT']; $sisaA = $perbaikan['ALPA']; $sisaP = $perbaikan['PELANGGARAN'];
    $asc = $timeline; usort($asc, fn($a,$b)=>strtotime($a['tgl'])<=>strtotime($b['tgl']));
    $sudahMap = [];
    foreach ($asc as $t) {
        $poinEntry = (int)preg_replace('/[^0-9]/','',$t['ket'] ?? '');
        $key = $t['tipe'].'#'.$t['id'];
        if ($t['tipe']==='Terlambat' && $sisaT>0) { $sudahMap[$key]=true; $sisaT -= $poinEntry; }
        elseif ($t['tipe']==='Alpa' && $sisaA>0) { $sudahMap[$key]=true; $sisaA -= $poinEntry; }
        elseif ($t['tipe']==='Pelanggaran Disiplin' && $sisaP>0) { $sudahMap[$key]=true; $sisaP -= $poinEntry; }
    }
    foreach ($timeline as &$t) { $t['_sudah'] = isset($sudahMap[$t['tipe'].'#'.$t['id']]); }
    unset($t);

    echo json_encode([
        'ok'=>true,
        'siswa'=>$sw,
        'stat'=>[
            'Hadir'=>(int)($absMap['Hadir']??0),
            'Terlambat'=>(int)($absMap['Terlambat']??0),
            'Alpa'=>(int)($absMap['Alpa']??0),
            'Izin'=>(int)($absMap['Izin']??0),'Sakit'=>(int)($absMap['Sakit']??0),
            'Pelanggaran'=>$jmlPel,'Kunjungan'=>$jmlKnj,'TotalPoin'=>$totalPoin,
        ],
        'timeline'=>$timeline,
    ]);
    exit;
}

// ── Kunjungan Rumah (Wali Kelas): tambah / hapus — hanya untuk siswa yang di-assign ke wali ini ──
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['tambah_kunjungan_wali'])) {
    $sid = (int)($_POST['siswa_id'] ?? 0);
    if ($sid && in_array($sid, $anak_ids)) {
        $sw = $conn->query("SELECT * FROM siswa WHERE id=$sid")->fetch_assoc();
        if ($sw) {
            $tgl   = $conn->real_escape_string($_POST['tanggal_kunjungan'] ?? date('Y-m-d'));
            $ortu  = $conn->real_escape_string(trim($_POST['nama_ortu'] ?? ''));
            $kasus = $conn->real_escape_string(trim($_POST['kasus'] ?? ''));
            $sel   = in_array($_POST['penyelesaian']??'', ['Belum Ditindaklanjuti','Dalam Proses','Selesai']) ? $_POST['penyelesaian'] : 'Belum Ditindaklanjuti';
            $ket   = $conn->real_escape_string(trim($_POST['keterangan'] ?? ''));
            $foto  = '';
            if (!empty($_FILES['foto']['name']) && $_FILES['foto']['error']===UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
                if (in_array($ext,['jpg','jpeg','png','gif','webp']) && $_FILES['foto']['size'] < 3*1024*1024) {
                    if (!is_dir(__DIR__.'/uploads/kunjungan')) @mkdir(__DIR__.'/uploads/kunjungan', 0755, true);
                    $foto = 'kj_'.time().'_'.rand(100,999).'.'.$ext;
                    move_uploaded_file($_FILES['foto']['tmp_name'], __DIR__.'/uploads/kunjungan/'.$foto);
                }
            }
            $namaWaliE = $conn->real_escape_string($wali['nama'] ?? '');
            $conn->query("INSERT INTO kunjungan_rumah (siswa_id,nis,nama_siswa,kelas,nama_guru,tanggal_kunjungan,nama_ortu,kasus,penyelesaian,keterangan,foto)
                VALUES ($sid,'".$conn->real_escape_string($sw['nis'])."','".$conn->real_escape_string($sw['nama'])."','".$conn->real_escape_string($sw['kelas'])."','$namaWaliE','$tgl','$ortu','$kasus','$sel','$ket','$foto')");
        }
    }
    header('Location: portal_wali.php?page=kunjungan'); exit;
}
if (isset($_GET['hapus_kunjungan_wali'])) {
    $kid = (int)$_GET['hapus_kunjungan_wali'];
    $kr = $conn->query("SELECT siswa_id FROM kunjungan_rumah WHERE id=$kid")->fetch_assoc();
    if ($kr && in_array((int)$kr['siswa_id'], $anak_ids)) {
        $conn->query("DELETE FROM kunjungan_rumah WHERE id=$kid");
    }
    header('Location: portal_wali.php?page=kunjungan'); exit;
}

// ── STATISTIK DASHBOARD ───────────────────────────────────────────────
$today      = date('Y-m-d');
$nama_bulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$stat_today = ['Hadir'=>0,'Terlambat'=>0,'Alpa'=>0,'Sakit'=>0,'Izin'=>0,'Bolos'=>0];
if ($anak_ids) {
    $sr = $conn->query("SELECT status, COUNT(*) c FROM absensi WHERE tanggal='$today' AND siswa_id IN ($anak_ids_str)" . periode_where($conn) . " GROUP BY status");
    while ($r = $sr->fetch_assoc()) $stat_today[$r['status']] = (int)$r['c'];
}
$total_siswa = count($anak_list);

// ── REKAP KALENDER ───────────────────────────────────────────────────
$jumlah_hari = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);
$absensi_map = [];
if ($page === 'rekap' && $anak_ids) {
    $filter_sid = $siswa_ok ? "AND siswa_id=$sid_url" : "AND siswa_id IN ($anak_ids_str)";
    $ar = $conn->query("SELECT siswa_id, DAY(tanggal) tgl, status FROM absensi
        WHERE MONTH(tanggal)=$bulan AND YEAR(tanggal)=$tahun $filter_sid" . periode_where($conn) . "");
    while ($r = $ar->fetch_assoc()) $absensi_map[$r['siswa_id']][$r['tgl']] = $r['status'];
}

$status_kode = [
    'Hadir'     => ['H','#16a34a','#dcfce7'],
    'Terlambat' => ['T','#d97706','#fef3c7'],
    'Alpa'      => ['A','#dc2626','#fee2e2'],
    'Sakit'     => ['S','#2563eb','#dbeafe'],
    'Izin'      => ['I','#7c3aed','#ede9fe'],
    'Bolos'     => ['B','#9a3412','#ffedd5'],
];

// ── Detail siswa untuk halaman chat ──────────────────────────────────
$detail_siswa = null;
if ($siswa_ok) {
    $detail_siswa = $conn->query("SELECT * FROM siswa WHERE id=$sid_url")->fetch_assoc();
}

// ── Helper: Hitung Rekap Pelanggaran (Terlambat + Alpa + Pelanggaran Disiplin) ─────
// Sumber & rumus poin SAMA PERSIS dengan Riwayat Siswa (menu Data Siswa) & Portal BK.
// Wali hanya melihat siswa yang jadi anak didiknya ($anak_ids), tidak perlu filter kelas
// karena satu wali kelas memang hanya mengurus satu kelas saja.
function hitung_rekap_pelanggaran_wali(mysqli $conn, int $bulan, int $tahun, array $siswa_ids): array {
    $poinCfgRow       = $conn->query("SELECT * FROM pengaturan_poin_absen LIMIT 1")->fetch_assoc();
    $poinAlpaCfg      = (int)($poinCfgRow['poin_alpa'] ?? 5);
    $poinTerlambatCfg = (int)($poinCfgRow['poin_terlambat'] ?? 2);

    $ids = $siswa_ids ? implode(',', array_map('intval', $siswa_ids)) : '0';
    $agg = [];
    $tambah = function($sid, $nis, $nama, $kelas, $label, $point, $jumlah) use (&$agg) {
        $sid = (int)$sid;
        if (!isset($agg[$sid])) $agg[$sid] = ['nis'=>$nis,'nama'=>$nama,'kelas'=>$kelas,'items'=>[],'total'=>0];
        $agg[$sid]['items'][] = ['label'=>$label,'point'=>(int)$point,'jumlah'=>(int)$jumlah];
        $agg[$sid]['total'] += (int)$point * (int)$jumlah;
    };

    if (!$siswa_ids) return $agg;

    $res = $conn->query("SELECT siswa_id,nis,nama,kelas,COUNT(*) jumlah FROM absensi
        WHERE status='Terlambat' AND MONTH(tanggal)=$bulan AND YEAR(tanggal)=$tahun AND siswa_id IN ($ids)" . periode_where($conn) . " GROUP BY siswa_id");
    while ($r = $res->fetch_assoc()) $tambah($r['siswa_id'],$r['nis'],$r['nama'],$r['kelas'],'Terlambat',$poinTerlambatCfg,$r['jumlah']);

    $res = $conn->query("SELECT siswa_id,nis,nama,kelas,COUNT(*) jumlah FROM absensi
        WHERE status='Alpa' AND MONTH(tanggal)=$bulan AND YEAR(tanggal)=$tahun AND siswa_id IN ($ids)" . periode_where($conn) . " GROUP BY siswa_id");
    while ($r = $res->fetch_assoc()) $tambah($r['siswa_id'],$r['nis'],$r['nama'],$r['kelas'],'Alpa',$poinAlpaCfg,$r['jumlah']);

    $res = $conn->query("SELECT siswa_id,nis,nama,kelas,jenis_nama,COALESCE(poin,3) poin,COUNT(*) jumlah FROM pelanggaran
        WHERE MONTH(tanggal)=$bulan AND YEAR(tanggal)=$tahun AND siswa_id IN ($ids)" . periode_where($conn) . " GROUP BY siswa_id,jenis_nama,poin");
    while ($r = $res->fetch_assoc()) $tambah($r['siswa_id'],$r['nis'],$r['nama'],$r['kelas'], $r['jenis_nama'] ?: 'Pelanggaran', $r['poin'], $r['jumlah']);

    // Tambahkan juga siswa (anak didik wali) yang TIDAK memiliki pelanggaran (poin nol)
    // agar tetap tampil di rekap, bukan hanya siswa yang melanggar saja.
    $resAll = $conn->query("SELECT id,nis,nama,kelas FROM siswa WHERE id IN ($ids)");
    while ($r = $resAll->fetch_assoc()) {
        $sid = (int)$r['id'];
        if (!isset($agg[$sid])) $agg[$sid] = ['nis'=>$r['nis'],'nama'=>$r['nama'],'kelas'=>$r['kelas'],'items'=>[],'total'=>0];
    }

    uasort($agg, fn($a,$b) => strcmp($a['nama'], $b['nama']));
    return $agg;
}

// ── Export Rekap Pelanggaran (Excel asli .xlsx) ──
if ($page === 'rekap_pelanggaran' && isset($_GET['export'])) {
    $bulan_e = (int)($_GET['bulan'] ?? date('n'));
    $tahun_e = (int)($_GET['tahun'] ?? date('Y'));
    $agg_e   = hitung_rekap_pelanggaran_wali($conn, $bulan_e, $tahun_e, $anak_ids);

    $nama_sekolah_e = strtoupper($pengaturan['nama_sekolah'] ?? 'NAMA SEKOLAH');
    $alamat_e       = $pengaturan['alamat'] ?? '';
    $kepala_e       = $pengaturan['kepala_sekolah'] ?? '';
    $nip_e          = $pengaturan['nip_kepala'] ?? '';
    $lastCol_e      = 'H';
    $kelas_e_lbl    = trim($wali['kelas_wali'] ?? '');

    $xlsx = new SimpleXLSX();
    $xlsx->setColWidth(1, 5); $xlsx->setColWidth(2, 14); $xlsx->setColWidth(3, 26); $xlsx->setColWidth(4, 9);
    $xlsx->setColWidth(5, 32); $xlsx->setColWidth(6, 8); $xlsx->setColWidth(7, 10); $xlsx->setColWidth(8, 9);

    $xlsx->addRow([[$nama_sekolah_e, SimpleXLSX::S_KOP_NAMA],'','','','','','','']);
    $xlsx->mergeCells('A1:'.$lastCol_e.'1');
    $xlsx->addRow([[$alamat_e, SimpleXLSX::S_SUBTITLE],'','','','','','','']);
    $xlsx->mergeCells('A2:'.$lastCol_e.'2');
    $xlsx->addEmptyRow();
    $judul_e = 'REKAP PELANGGARAN SISWA — '.$nama_bulan[$bulan_e].' '.$tahun_e.($kelas_e_lbl?' — KELAS '.$kelas_e_lbl:'');
    $xlsx->addRow([[$judul_e, SimpleXLSX::S_TITLE],'','','','','','','']);
    $xlsx->mergeCells('A4:'.$lastCol_e.'4');
    $xlsx->addEmptyRow();

    $xlsx->addRow([
        ['No',SimpleXLSX::S_HEADER],['NIS',SimpleXLSX::S_HEADER],['Nama',SimpleXLSX::S_HEADER],['Kelas',SimpleXLSX::S_HEADER],
        ['Pelanggaran',SimpleXLSX::S_HEADER],['Point',SimpleXLSX::S_HEADER],['Jumlah',SimpleXLSX::S_HEADER],['Total',SimpleXLSX::S_HEADER],
    ]);

    $no_e = 0;
    foreach ($agg_e as $row) {
        $no_e++;
        if (!$row['items']) {
            $xlsx->addRow([
                [$no_e,SimpleXLSX::S_CENTER],[$row['nis'],SimpleXLSX::S_CENTER],[$row['nama'],SimpleXLSX::S_BORDER],[$row['kelas'],SimpleXLSX::S_CENTER],
                ['Tidak ada pelanggaran',SimpleXLSX::S_BORDER],[0,SimpleXLSX::S_CENTER],[0,SimpleXLSX::S_CENTER],[0,SimpleXLSX::S_BOLD],
            ]);
            continue;
        }
        $firstItem = true;
        foreach ($row['items'] as $it) {
            $xlsx->addRow([
                [$firstItem ? $no_e : '', SimpleXLSX::S_CENTER],
                [$firstItem ? $row['nis'] : '', SimpleXLSX::S_CENTER],
                [$firstItem ? $row['nama'] : '', SimpleXLSX::S_BORDER],
                [$firstItem ? $row['kelas'] : '', SimpleXLSX::S_CENTER],
                [$it['label'], SimpleXLSX::S_BORDER],
                [$it['point'], SimpleXLSX::S_CENTER],
                [$it['jumlah'], SimpleXLSX::S_CENTER],
                [$firstItem ? $row['total'] : '', SimpleXLSX::S_BOLD],
            ]);
            $firstItem = false;
        }
    }

    $xlsx->addEmptyRow(); $xlsx->addEmptyRow(); $xlsx->addEmptyRow();
    $xlsx->addRow(['','','','','','',['Mengetahui,',SimpleXLSX::S_CENTER],'']);
    $xlsx->addRow(['','','','','','',['Wali Kelas',SimpleXLSX::S_CENTER],'']);
    $xlsx->addEmptyRow(); $xlsx->addEmptyRow(); $xlsx->addEmptyRow();
    $xlsx->addRow(['','','','','','',[$kepala_e ?: '(_______________________)',SimpleXLSX::S_BOLD],'']);
    if ($nip_e) $xlsx->addRow(['','','','','','',['NIP. '.$nip_e,SimpleXLSX::S_CENTER],'']);

    $xlsx->download('Rekap_Pelanggaran_'.$nama_bulan[$bulan_e].'_'.$tahun_e.($kelas_e_lbl?'_'.$kelas_e_lbl:'').'.xlsx');
    exit;
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Portal Wali – <?= htmlspecialchars($wali['nama']) ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',system-ui,sans-serif;background:#f1f5f9;display:flex;min-height:100vh;font-size:14px}

/* ── SIDEBAR ─────────────────────────────────── */
.sidebar{width:220px;background:linear-gradient(180deg,#4f46e5 0%,#3730a3 100%);color:white;display:flex;flex-direction:column;flex-shrink:0;min-height:100vh}
.sidebar-logo{padding:20px 18px 14px;border-bottom:1px solid rgba(255,255,255,.15)}
.sidebar-logo .title{font-weight:800;font-size:1rem;display:flex;align-items:center;gap:8px}
.sidebar-logo .sub{font-size:.75rem;opacity:.7;margin-top:3px}
.nav-section{padding:14px 12px 4px;font-size:.65rem;font-weight:700;letter-spacing:1.5px;opacity:.5;text-transform:uppercase}
.nav-item{display:flex;align-items:center;gap:10px;padding:10px 18px;color:rgba(255,255,255,.8);text-decoration:none;transition:.15s;font-size:.875rem;border-radius:8px;margin:2px 8px}
.nav-item:hover{background:rgba(255,255,255,.12);color:white}
.nav-item.active{background:rgba(255,255,255,.2);color:white;font-weight:600}
.nav-item i{width:18px;text-align:center;font-size:.9rem}
.sidebar-footer{margin-top:auto;padding:14px;border-top:1px solid rgba(255,255,255,.12)}

/* ── MAIN ────────────────────────────────────── */
.main{flex:1;display:flex;flex-direction:column;min-height:100vh;overflow:hidden}
.topbar{background:white;padding:12px 24px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05)}
.topbar .page-name{font-weight:700;font-size:1rem;color:#1e293b}
.topbar .time{font-size:.8rem;color:#64748b}
.content{padding:24px;flex:1;overflow-y:auto}

/* ── CARDS ───────────────────────────────────── */
.stat-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:14px;margin-bottom:24px}
.stat-card{background:white;border-radius:12px;padding:16px;box-shadow:0 2px 8px rgba(0,0,0,.06);border-top:4px solid var(--c)}
.stat-card .val{font-size:2rem;font-weight:800;color:var(--c)}
.stat-card .lbl{font-size:.75rem;color:#64748b;margin-top:4px;font-weight:600}
.card{background:white;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.06);overflow:hidden;margin-bottom:20px}
.card-header{padding:14px 20px;font-weight:700;font-size:.9rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:8px;color:#1e293b}
.card-body{padding:20px}

/* ── TABLE ───────────────────────────────────── */
.tbl-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse;font-size:.82rem}
th{background:#1e293b;color:white;padding:8px 6px;text-align:center;white-space:nowrap;font-size:.75rem}
th.sticky-no{position:sticky;left:0;z-index:3;background:#1e293b}
th.sticky-nis{position:sticky;left:30px;z-index:3;background:#1e293b}
th.sticky-nama{position:sticky;left:80px;z-index:3;background:#1e293b}
td{padding:5px 6px;border-bottom:1px solid #f1f5f9;vertical-align:middle}
td.sticky-no{position:sticky;left:0;z-index:1;background:white}
td.sticky-nis{position:sticky;left:30px;z-index:1;background:white}
td.sticky-nama{position:sticky;left:80px;z-index:1;background:white;font-weight:600;white-space:nowrap}
tr:nth-child(even) td{background:#f8fafc}
tr:nth-child(even) td.sticky-no,
tr:nth-child(even) td.sticky-nis,
tr:nth-child(even) td.sticky-nama{background:#f8fafc}
.st-box{display:inline-block;width:22px;height:22px;line-height:22px;border-radius:4px;font-weight:800;font-size:.72rem;text-align:center}
.weekend{background:#f1f5f9 !important}

/* ── LEGEND ──────────────────────────────────── */
.legend{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:16px}
.legend-item{display:flex;align-items:center;gap:6px;font-size:.8rem;font-weight:600}
.legend-dot{width:26px;height:22px;border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:800}

/* ── SUMMARY COL ─────────────────────────────── */
.sum-h{background:#f0fdf4;color:#166534;font-weight:800}
.sum-t{background:#fffbeb;color:#854d0e;font-weight:800}
.sum-a{background:#fef2f2;color:#991b1b;font-weight:800}
.sum-s{background:#eff6ff;color:#1e40af;font-weight:800}
.sum-i{background:#f5f3ff;color:#5b21b6;font-weight:800}
.sum-b{background:#fff7ed;color:#9a3412;font-weight:800}

/* ── SISWA LIST ──────────────────────────────── */
.siswa-list{display:flex;flex-direction:column;gap:8px}
.siswa-row{display:flex;align-items:center;gap:14px;padding:12px 16px;border-radius:10px;cursor:pointer;text-decoration:none;color:#1e293b;background:#f8fafc;transition:.15s;border:1px solid #e2e8f0}
.siswa-row:hover{background:#eef2ff;border-color:#c7d2fe}
.avatar{width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:white;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:1.1rem;flex-shrink:0}

/* ── CHAT ────────────────────────────────────── */
.chat-wrap{display:flex;flex-direction:column;height:calc(100vh - 200px)}
.chat-messages{flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:10px;background:#f8fafc;border-radius:12px;margin-bottom:12px}
.bubble{max-width:70%;padding:10px 14px;border-radius:14px;font-size:.875rem;line-height:1.5;position:relative}
.bubble.sent{align-self:flex-end;background:#4f46e5;color:white;border-bottom-right-radius:4px}
.bubble.recv{align-self:flex-start;background:white;color:#1e293b;border-bottom-left-radius:4px;box-shadow:0 1px 4px rgba(0,0,0,.08)}
.bubble .time{font-size:.65rem;opacity:.6;margin-top:4px;text-align:right}
.chat-input-wrap{display:flex;gap:10px}
.chat-input{flex:1;border:2px solid #e2e8f0;border-radius:10px;padding:10px 14px;outline:none;font-size:.875rem;resize:none;font-family:inherit;transition:.2s}
.chat-input:focus{border-color:#6366f1}
.btn{padding:9px 18px;border-radius:8px;border:none;cursor:pointer;font-size:.875rem;font-weight:600;transition:.15s;display:inline-flex;align-items:center;gap:6px}
.btn-primary{background:#4f46e5;color:white}
.btn-primary:hover{background:#4338ca}
.btn-sm{padding:5px 10px;font-size:.78rem;border-radius:6px}
.btn-secondary{background:#e2e8f0;color:#475569}
.alert{padding:10px 16px;border-radius:8px;margin-bottom:14px;font-size:.875rem}
.alert-success{background:#dcfce7;color:#166534}
.alert-error{background:#fee2e2;color:#991b1b}
.progress-bar{height:6px;background:#e2e8f0;border-radius:3px;overflow:hidden}
.progress-inner{height:100%;border-radius:3px}
.badge-pill{display:inline-block;padding:2px 10px;border-radius:20px;font-size:.72rem;font-weight:700}
.unread-dot{width:8px;height:8px;background:#ef4444;border-radius:50%;display:inline-block;margin-left:4px;flex-shrink:0}

/* ══════════ Tambahan gaya senada Portal BK (Data Siswa / Rekap Pelanggaran / Kunjungan Rumah) ══════════ */
.filter-row{display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap}
.filter-row label{font-size:.75rem;font-weight:600;color:#64748b;display:block;margin-bottom:4px}
.filter-row input,.filter-row select,.filter-row textarea{padding:7px 11px;border:1px solid #e2e8f0;border-radius:8px;font-size:.85rem;outline:none;background:white;min-width:120px;font-family:inherit}
.filter-row input:focus,.filter-row select:focus{border-color:#4f46e5}
.btn-filter{padding:8px 18px;background:#4f46e5;color:white;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:.85rem;white-space:nowrap;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
.btn-filter:hover{background:#4338ca}
.btn-soft{padding:8px 16px;background:#eef2ff;color:#4338ca;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:.85rem;white-space:nowrap;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
.btn-green{padding:8px 16px;background:#16a34a;color:white;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:.85rem;white-space:nowrap;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
.btn-red{padding:6px 10px;background:#fee2e2;color:#dc2626;border:none;border-radius:6px;font-weight:700;cursor:pointer;font-size:.78rem;text-decoration:none;display:inline-block}

.badge{display:inline-block;padding:2px 10px;border-radius:20px;font-size:.74rem;font-weight:700}
.b-hadir{background:#dcfce7;color:#15803d}
.b-terlambat{background:#fef9c3;color:#854d0e}
.b-alpa{background:#fee2e2;color:#991b1b}
.b-sakit{background:#dbeafe;color:#1e40af}
.b-izin{background:#ede9fe;color:#5b21b6}
.b-bolos{background:#ffedd5;color:#9a3412}
.b-belum{background:#fee2e2;color:#991b1b}
.b-proses{background:#fef3c7;color:#92400e}
.b-selesai{background:#dcfce7;color:#15803d}
.b-pelanggaran{background:#fee2e2;color:#991b1b}
.b-sudah{background:#dcfce7;color:#15803d}

/* ── MODAL (Riwayat Siswa / Tambah Kunjungan) ─────── */
.modal-overlay{display:none;position:fixed;inset:0;background:linear-gradient(135deg,rgba(79,70,229,.5) 0%,rgba(55,48,163,.5) 100%);backdrop-filter:blur(4px);z-index:1000;align-items:flex-start;justify-content:center;padding:32px 16px;overflow-y:auto}
.modal-overlay.show{display:flex}
.modal-box{background:white;border-radius:16px;width:100%;max-width:580px;box-shadow:0 24px 60px rgba(79,70,229,.25),0 8px 24px rgba(0,0,0,.12);max-height:90vh;display:flex;flex-direction:column;border-top:4px solid #4f46e5}
.modal-box.wide{max-width:820px}
.modal-head{padding:16px 20px;background:linear-gradient(90deg,#eef2ff,#f5f3ff);border-bottom:1px solid #e2e8f0;display:flex;align-items:center;gap:10px;font-weight:700;color:#4338ca;border-radius:12px 12px 0 0}
.modal-head .x{margin-left:auto;cursor:pointer;color:#94a3b8;background:none;border:none;font-size:1.2rem;line-height:1}
.modal-head .x:hover{color:#1e293b}
.modal-body{padding:18px 20px;overflow-y:auto;flex:1;background:#fafbff}
.modal-body label{font-size:.78rem;font-weight:700;color:#4338ca;display:block;margin-bottom:5px;margin-top:14px;letter-spacing:.01em}
.modal-body label:first-child{margin-top:0}
.modal-body input,.modal-body select,.modal-body textarea{width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:.85rem;outline:none;font-family:inherit;background:#ffffff;color:#1e293b}
.modal-body input:focus,.modal-body select:focus,.modal-body textarea:focus{border-color:#4f46e5;box-shadow:0 0 0 3px rgba(79,70,229,.1)}
.modal-body input[type="file"]{background:#f8fafc;padding:7px 10px;cursor:pointer}
.modal-foot{padding:14px 20px;background:linear-gradient(90deg,#eef2ff,#f5f3ff);border-top:1px solid #e2e8f0;display:flex;justify-content:flex-end;gap:10px;border-radius:0 0 16px 16px}

.tl-item{padding:10px 0;border-bottom:1px solid #f1f5f9}
.tl-item:last-child{border-bottom:none}
.tl-date{font-size:.72rem;color:#94a3b8;font-weight:600}
.tl-title{font-weight:700;font-size:.85rem;color:#1e293b;margin-top:2px}
.tl-ket{font-size:.78rem;color:#64748b;margin-top:2px}
.rw-tabs{display:flex;gap:6px;margin-bottom:10px;border-bottom:1px solid #f1f5f9}
.rw-tab{background:none;border:none;cursor:pointer;padding:7px 4px;font-size:.75rem;font-weight:700;color:#94a3b8;position:relative;top:1px;border-bottom:2px solid transparent;display:flex;align-items:center;gap:5px}
.rw-tab .cnt{background:#f1f5f9;color:#64748b;border-radius:20px;padding:1px 7px;font-size:.65rem;font-weight:800}
.rw-tab.active{color:#4f46e5;border-bottom-color:#4f46e5}
.rw-tab.active .cnt{background:#e0e7ff;color:#4338ca}

.tab-toggle{display:inline-flex;background:#f1f5f9;border-radius:8px;padding:3px;gap:2px}
.tab-toggle button{padding:6px 16px;border:none;background:transparent;border-radius:6px;font-weight:700;font-size:.8rem;cursor:pointer;color:#64748b}
.tab-toggle button.active{background:#4f46e5;color:white}
</style>
</head>
<body>

<!-- ══════════════════ SIDEBAR ══════════════════ -->
<div class="sidebar">
    <div class="sidebar-logo">
        <?php
        $foto_wali_file = $wali['foto_wali'] ?? '';
        $foto_wali_path = 'uploads/foto_wali/' . $foto_wali_file;
        ?>
        <?php if ($foto_wali_file && file_exists(__DIR__ . '/' . $foto_wali_path)): ?>
        <div style="display:flex;flex-direction:column;align-items:center;padding:8px 0 12px">
            <img src="<?= BASE_URL . $foto_wali_path ?>?t=<?= filemtime(__DIR__.'/'.$foto_wali_path) ?>"
                 style="width:160px;height:180px;border-radius:12px;object-fit:cover;border:3px solid rgba(255,255,255,.5);box-shadow:0 6px 20px rgba(0,0,0,.35);margin-bottom:12px">
            <div style="text-align:center;padding:0 8px">
                <div style="font-weight:800;font-size:1rem;line-height:1.3;color:white"><?= htmlspecialchars($wali['nama']) ?></div>
                <div style="font-size:.75rem;opacity:.65;margin-top:4px;color:white"><?= htmlspecialchars($wali['jabatan'] ?? 'Wali Kelas') ?></div>
            </div>
        </div>
        <?php else: ?>
        <div class="title"><i class="fas fa-chalkboard-teacher"></i> Portal Wali</div>
        <?php endif; ?>
        <div class="sub">
            <?php if (!empty($wali['kelas_wali'])): ?>
            Kelas <?= htmlspecialchars($wali['kelas_wali']) ?>
            <?php else: ?>
            <?= htmlspecialchars($wali['nama']) ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="nav-section">Utama</div>
    <a href="portal_wali.php?page=dashboard" class="nav-item <?= $page==='dashboard'?'active':'' ?>">
        <i class="fas fa-home"></i> Dashboard
    </a>
    <div class="nav-section">Laporan</div>
    <a href="portal_wali.php?page=rekap" class="nav-item <?= $page==='rekap'?'active':'' ?>">
        <i class="fas fa-calendar-alt"></i> Rekap Absensi
    </a>
    <a href="portal_wali.php?page=laporan_rekap" class="nav-item <?= $page==='laporan_rekap'?'active':'' ?>"
       style="<?= $page==='laporan_rekap'?'':'border-left:3px solid #f59e0b;background:rgba(245,158,11,.08)' ?>">
        <i class="fas fa-clipboard-list" style="color:#f59e0b"></i> Laporan Rekap Harian
    </a>
    <a href="portal_wali.php?page=rekap_pelanggaran" class="nav-item <?= $page==='rekap_pelanggaran'?'active':'' ?>">
        <i class="fas fa-chart-bar"></i> Rekap Pelanggaran
    </a>

    <div class="nav-section">Manajemen</div>
    <a href="portal_wali.php?page=absen" class="nav-item <?= $page==='absen'?'active':'' ?>">
        <i class="fas fa-clipboard-check"></i> Absen Manual
    </a>
    <a href="portal_wali.php?page=edit_absensi" class="nav-item <?= $page==='edit_absensi'?'active':'' ?>">
        <i class="fas fa-pen-square"></i> Edit Absensi
    </a>
    <a href="portal_wali.php?page=siswa" class="nav-item <?= $page==='siswa'?'active':'' ?>">
        <i class="fas fa-users"></i> Data Siswa
    </a>
    <a href="portal_wali.php?page=kunjungan" class="nav-item <?= $page==='kunjungan'?'active':'' ?>">
        <i class="fas fa-house-user"></i> Kunjungan Rumah
    </a>

    <div class="nav-section">Komunikasi</div>
    <a href="portal_wali.php?page=chat" class="nav-item <?= $page==='chat'?'active':'' ?>">
        <i class="fas fa-comments"></i> Chat Siswa
    </a>

    <div class="sidebar-footer">
        <a href="?logout=1" style="color:rgba(255,255,255,.7);text-decoration:none;font-size:.8rem;display:flex;align-items:center;gap:6px">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<!-- ══════════════════ MAIN ══════════════════════ -->
<div class="main">
    <div class="topbar">
        <div class="page-name">
            <?php
            $page_titles = ['dashboard'=>'Dashboard','rekap'=>'Rekap Absensi','laporan_rekap'=>'Laporan Rekap Harian','siswa'=>'Data Siswa','chat'=>'Chat Siswa','absen'=>'Absen Manual','edit_absensi'=>'Edit Absensi','rekap_pelanggaran'=>'Rekap Pelanggaran','kunjungan'=>'Kunjungan Rumah Siswa'];
            echo '<i class="fas fa-'.(['dashboard'=>'home','rekap'=>'calendar-alt','laporan_rekap'=>'clipboard-list','siswa'=>'users','chat'=>'comments','absen'=>'clipboard-check','edit_absensi'=>'pen-square','rekap_pelanggaran'=>'chart-bar','kunjungan'=>'house-user'][$page] ?? 'home').'"></i>&nbsp; ';
            echo $page_titles[$page] ?? 'Portal Wali';
            ?>
        </div>
        <div class="time">
            <i class="fas fa-clock"></i>
            <span id="jam">--:--:--</span> &nbsp;|&nbsp;
            <?= htmlspecialchars($pengaturan['nama_sekolah'] ?? '') ?>
            &nbsp;<span style="font-weight:700;color:#4f46e5"><?= htmlspecialchars($wali['nama']) ?></span>
        </div>
    </div>

    <div class="content">

<?php // ═══════════════════════════════ DASHBOARD ═══════════════════════════════
if ($page === 'dashboard'): ?>

    <!-- Stat cards -->
    <div class="stat-grid">
        <div class="stat-card" style="--c:#4f46e5">
            <div class="val"><?= $total_siswa ?></div>
            <div class="lbl"><i class="fas fa-users"></i> Total Siswa</div>
        </div>
        <div class="stat-card" style="--c:#16a34a">
            <div class="val"><?= $stat_today['Hadir'] ?></div>
            <div class="lbl"><i class="fas fa-check-circle"></i> Hadir</div>
        </div>
        <div class="stat-card" style="--c:#d97706">
            <div class="val"><?= $stat_today['Terlambat'] ?></div>
            <div class="lbl"><i class="fas fa-clock"></i> Terlambat</div>
        </div>
        <div class="stat-card" style="--c:#dc2626">
            <div class="val"><?= $stat_today['Alpa'] ?></div>
            <div class="lbl"><i class="fas fa-times-circle"></i> Alpa</div>
        </div>
        <div class="stat-card" style="--c:#2563eb">
            <div class="val"><?= $stat_today['Sakit'] ?></div>
            <div class="lbl"><i class="fas fa-hospital"></i> Sakit</div>
        </div>
        <div class="stat-card" style="--c:#7c3aed">
            <div class="val"><?= $stat_today['Izin'] ?></div>
            <div class="lbl"><i class="fas fa-file-alt"></i> Izin</div>
        </div>
        <div class="stat-card" style="--c:#9a3412">
            <div class="val"><?= $stat_today['Bolos'] ?></div>
            <div class="lbl"><i class="fas fa-ban"></i> Bolos</div>
        </div>
    </div>

    <!-- Absensi hari ini -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-calendar-day" style="color:#4f46e5"></i>
            Absensi Hari Ini &mdash; <?= format_tanggal($today) ?>
            <span style="margin-left:auto;font-size:.75rem;color:#64748b;font-weight:400"><?= $total_siswa ?> siswa</span>
        </div>
        <div class="tbl-wrap">
            <table>
                <thead>
                    <tr>
                        <th class="sticky-no" style="min-width:32px">No</th>
                        <th style="text-align:left;min-width:180px;padding-left:10px">Nama Siswa</th>
                        <th style="min-width:34px;background:#166534" title="Hadir">H</th>
                        <th style="min-width:34px;background:#854d0e" title="Terlambat">T</th>
                        <th style="min-width:34px;background:#991b1b" title="Alpa">A</th>
                        <th style="min-width:34px;background:#1e40af" title="Sakit">S</th>
                        <th style="min-width:34px;background:#5b21b6" title="Izin">I</th>
                        <th style="min-width:34px;background:#9a3412" title="Bolos">B</th>
                        <th style="min-width:100px">Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$anak_list): ?>
                    <tr><td colspan="9" style="text-align:center;padding:40px;color:#94a3b8">Belum ada siswa yang di-assign ke Anda.</td></tr>
                <?php else: $no=0; foreach($anak_list as $s):
                    $no++;
                    $ab = $conn->query("SELECT status FROM absensi WHERE siswa_id={$s['id']} AND tanggal='$today'" . periode_where($conn))->fetch_assoc();
                    $st = $ab['status'] ?? null;
                ?>
                    <tr>
                        <td class="sticky-no" style="text-align:center;font-weight:600"><?= $no ?></td>
                        <td style="padding-left:10px"><?= htmlspecialchars($s['nama']) ?> <small style="color:#94a3b8"><?= $s['kelas'] ?></small></td>
                        <?php foreach(['Hadir','Terlambat','Alpa','Sakit','Izin','Bolos'] as $ss):
                            $k = $status_kode[$ss];
                        ?>
                        <td style="text-align:center">
                            <?php if ($st === $ss): ?>
                            <span class="st-box" style="background:<?= $k[2] ?>;color:<?= $k[1] ?>"><?= $k[0] ?></span>
                            <?php else: ?><span style="color:#e2e8f0">·</span><?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                        <td style="text-align:center">
                            <?php if ($st): [$kd,$w,$bg] = $status_kode[$st];
                                echo "<span class='badge-pill' style='background:$bg;color:$w'>$st</span>";
                            else: echo "<span class='badge-pill' style='background:#f1f5f9;color:#94a3b8'>Belum absen</span>";
                            endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <!-- Keterangan -->
        <div style="padding:10px 16px;background:#f8fafc;border-top:1px solid #f1f5f9;display:flex;flex-wrap:wrap;gap:10px;font-size:.75rem">
            <strong style="color:#64748b">Keterangan:</strong>
            <?php foreach(['H'=>['#16a34a','#dcfce7','Hadir'],'T'=>['#d97706','#fef3c7','Terlambat'],'A'=>['#dc2626','#fee2e2','Alpa'],'S'=>['#2563eb','#dbeafe','Sakit'],'I'=>['#7c3aed','#ede9fe','Izin'],'B'=>['#9a3412','#ffedd5','Bolos']] as $kd=>$v): ?>
            <span style="display:flex;align-items:center;gap:5px;font-weight:600">
                <span style="width:22px;height:20px;border-radius:4px;background:<?= $v[1] ?>;color:<?= $v[0] ?>;display:inline-flex;align-items:center;justify-content:center;font-weight:800"><?= $kd ?></span>
                = <?= $v[2] ?>
            </span>
            <?php endforeach; ?>
        </div>
    </div>

<?php // ═══════════════════════════════ REKAP ═══════════════════════════════
elseif ($page === 'rekap'): ?>

    <style>
    @media print {
        @page { size: landscape; margin: 8mm; }
        .rekap-kalender-print-header { display:block !important; }
        .tbl-wrap table { font-size: 7pt; }
        .tbl-wrap th, .tbl-wrap td { padding: 2px !important; }
    }
    .rekap-kalender-print-header { display:none; }
    </style>

    <!-- Header khusus saat cetak (sekolah tidak terlihat di layar biasa, sidebar sudah cukup) -->
    <div class="rekap-kalender-print-header" style="text-align:center;border-bottom:2px solid #000;padding-bottom:8px;margin-bottom:14px;font-family:Arial,sans-serif">
        <div style="font-size:14pt;font-weight:bold"><?= htmlspecialchars($pengaturan['nama_sekolah'] ?? '') ?></div>
        <div style="font-size:10pt">REKAP KEHADIRAN SISWA — <?= $nama_bulan[$bulan] ?> <?= $tahun ?><?= $kelas_wali_login ? ' — Kelas '.htmlspecialchars($kelas_wali_login) : '' ?></div>
        <div style="font-size:8.5pt;color:#444">Dicetak: <?= date('j/n/Y, H.i.s') ?></div>
    </div>

    <!-- Filter -->
    <form method="GET" class="no-print" style="display:flex;gap:10px;align-items:flex-end;margin-bottom:20px;flex-wrap:wrap">
        <input type="hidden" name="page" value="rekap">
        <div>
            <div style="font-size:.75rem;font-weight:600;color:#64748b;margin-bottom:4px">Bulan</div>
            <select name="bulan" style="padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;background:white;outline:none;font-size:.875rem">
                <?php for($m=1;$m<=12;$m++): ?>
                <option value="<?= $m ?>" <?= $bulan==$m?'selected':'' ?>><?= $nama_bulan[$m] ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div>
            <div style="font-size:.75rem;font-weight:600;color:#64748b;margin-bottom:4px">Tahun</div>
            <select name="tahun" style="padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;background:white;outline:none;font-size:.875rem">
                <?php for($y=date('Y');$y>=date('Y')-3;$y--): ?>
                <option value="<?= $y ?>" <?= $tahun==$y?'selected':'' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <?php if (count($anak_list)>1): ?>
        <div>
            <div style="font-size:.75rem;font-weight:600;color:#64748b;margin-bottom:4px">Siswa</div>
            <select name="sid" style="padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;background:white;outline:none;font-size:.875rem">
                <option value="">Semua Siswa</option>
                <?php foreach($anak_list as $s): ?>
                <option value="<?= $s['id'] ?>" <?= $sid_url==$s['id']?'selected':'' ?>><?= htmlspecialchars($s['nama']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Tampilkan</button>
        <button type="button" class="btn btn-primary" style="background:#475569" onclick="window.print()"><i class="fas fa-print"></i> Cetak</button>
    </form>

    <!-- Keterangan -->
    <div class="legend no-print" style="margin-bottom:16px">
        <span style="font-size:.8rem;font-weight:700;color:#64748b;align-self:center">Keterangan:</span>
        <?php foreach(['H'=>['#16a34a','#dcfce7','Hadir'],'T'=>['#d97706','#fef3c7','Terlambat'],'A'=>['#dc2626','#fee2e2','Alpa'],'S'=>['#2563eb','#dbeafe','Sakit'],'I'=>['#7c3aed','#ede9fe','Izin'],'B'=>['#9a3412','#ffedd5','Bolos']] as $kd=>$v): ?>
        <div class="legend-item">
            <span class="legend-dot" style="background:<?= $v[1] ?>;color:<?= $v[0] ?>"><?= $kd ?></span>
            <span style="color:#475569">=&nbsp;<?= $v[2] ?></span>
        </div>
        <?php endforeach; ?>
        <div class="legend-item"><span style="color:#94a3b8;font-size:.75rem">(<span style="color:#374151">—</span> = Libur/Weekend)</span></div>
    </div>

    <!-- Rekap Tabel -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-calendar-alt" style="color:#4f46e5"></i>
            Daftar Hadir <?= $nama_bulan[$bulan] ?> <?= $tahun ?>
            <span style="margin-left:auto;font-size:.75rem;font-weight:400;color:#64748b"><?= count($anak_list) ?> siswa</span>
        </div>
        <div class="tbl-wrap">
        <?php
        $show_list = ($siswa_ok && $sid_url) ? array_filter($anak_list, fn($s)=>$s['id']==$sid_url) : $anak_list;
        ?>
        <table>
            <thead>
                <tr>
                    <th class="sticky-no" style="min-width:30px">#</th>
                    <th class="sticky-nis" style="min-width:50px;text-align:left">NIS</th>
                    <th class="sticky-nama" style="min-width:160px;text-align:left;padding-left:10px">NAMA</th>
                    <?php for($d=1;$d<=$jumlah_hari;$d++):
                        $ts = mktime(0,0,0,$bulan,$d,$tahun);
                        $hn = date('N',$ts);
                        $iswk = $hn >= 6;
                        $hari_s = ['','Sen','Sel','Rab','Kam','Jum','Sab','Min'][$hn];
                    ?>
                    <th style="min-width:30px;<?= $iswk?'background:#374151':'' ?>" title="<?= $hari_s ?> <?= $d ?>">
                        <div style="font-size:.6rem;font-weight:400;opacity:.7"><?= $hari_s ?></div>
                        <div style="font-size:.78rem"><?= $d ?></div>
                    </th>
                    <?php endfor; ?>
                    <th style="min-width:28px;background:#166534;font-size:.8rem" title="Hadir">H</th>
                    <th style="min-width:28px;background:#854d0e;font-size:.8rem" title="Terlambat">T</th>
                    <th style="min-width:28px;background:#991b1b;font-size:.8rem" title="Alpa">A</th>
                    <th style="min-width:28px;background:#1e40af;font-size:.8rem" title="Sakit">S</th>
                    <th style="min-width:28px;background:#5b21b6;font-size:.8rem" title="Izin">I</th>
                    <th style="min-width:28px;background:#9a3412;font-size:.8rem" title="Bolos">B</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$anak_list): ?>
                <tr><td colspan="<?= 3+$jumlah_hari+6 ?>" style="text-align:center;padding:40px;color:#94a3b8">Belum ada siswa.</td></tr>
            <?php else: $no=0; foreach($show_list as $s):
                $no++; $sid2=$s['id'];
                $tot=['H'=>0,'T'=>0,'A'=>0,'S'=>0,'I'=>0,'B'=>0];
            ?>
                <tr>
                    <td class="sticky-no" style="text-align:center;font-weight:600"><?= $no ?></td>
                    <td class="sticky-nis" style="font-family:monospace;font-size:.75rem"><?= $s['nis'] ?></td>
                    <td class="sticky-nama" style="padding-left:10px"><?= htmlspecialchars($s['nama']) ?></td>
                    <?php for($d=1;$d<=$jumlah_hari;$d++):
                        $ts = mktime(0,0,0,$bulan,$d,$tahun);
                        $iswk = date('N',$ts) >= 6;
                        $st = $absensi_map[$sid2][$d] ?? null;
                        if ($st && isset($status_kode[$st])) {
                            [$kd2] = $status_kode[$st];
                            if (isset($tot[$kd2])) $tot[$kd2]++;
                        }
                    ?>
                    <td style="text-align:center;<?= $iswk?'background:#f1f5f9':'' ?>">
                        <?php if ($st && isset($status_kode[$st])): [$kd2,$w,$bg]=$status_kode[$st]; ?>
                        <span class="st-box" style="background:<?= $bg ?>;color:<?= $w ?>"><?= $kd2 ?></span>
                        <?php elseif($iswk): ?><span style="color:#cbd5e1;font-size:.75rem">—</span>
                        <?php else: ?><span style="color:#e2e8f0;font-size:.7rem">·</span>
                        <?php endif; ?>
                    </td>
                    <?php endfor; ?>
                    <td style="text-align:center" class="sum-h"><?= $tot['H'] ?></td>
                    <td style="text-align:center" class="sum-t"><?= $tot['T'] ?></td>
                    <td style="text-align:center" class="sum-a"><?= $tot['A'] ?></td>
                    <td style="text-align:center" class="sum-s"><?= $tot['S'] ?></td>
                    <td style="text-align:center" class="sum-i"><?= $tot['I'] ?></td>
                    <td style="text-align:center" class="sum-b"><?= $tot['B'] ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>
    </div>

<?php // ═══════════════════════════════ DATA SISWA (gaya senada Portal BK) ═══════════════════════════════
elseif ($page === 'siswa'): ?>

<div class="card">
    <div class="card-header"><i class="fas fa-users" style="color:#4f46e5"></i> Data Siswa
        <span style="margin-left:auto;font-size:.75rem;font-weight:400;color:#64748b"><?= count($anak_list) ?> siswa</span>
    </div>
    <div class="tbl-wrap">
        <table>
            <thead><tr><th style="width:35px">#</th><th class="th-left">NIS</th><th class="th-left">Nama</th><th>Kelas</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php if (!$anak_list): ?>
            <tr><td colspan="5" style="text-align:center;padding:40px;color:#94a3b8">
                <i class="fas fa-user-slash" style="font-size:1.6rem;display:block;margin-bottom:8px;color:#cbd5e1"></i>
                Belum ada siswa yang di-assign ke Anda. Hubungi admin untuk menambahkan siswa.
            </td></tr>
            <?php else: $no=0; foreach ($anak_list as $s): $no++; ?>
            <tr>
                <td style="text-align:center"><?= $no ?></td>
                <td style="font-family:monospace"><?= htmlspecialchars($s['nis']) ?></td>
                <td style="font-weight:600"><?= htmlspecialchars($s['nama']) ?></td>
                <td style="text-align:center"><?= htmlspecialchars($s['kelas']) ?></td>
                <td style="text-align:center">
                    <button class="btn-soft" onclick="bukaRiwayat(<?= $s['id'] ?>)"><i class="fas fa-list"></i> Riwayat</button>
                </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Riwayat Siswa (senada Portal BK, TANPA panel Input Point Perbaikan — itu khusus Portal BK) -->
<div class="modal-overlay" id="modalRiwayat">
    <div class="modal-box wide" style="max-width:720px">
        <div class="modal-head">
            <i class="fas fa-user"></i> <span id="rwNama">Memuat...</span>
            <div style="margin-left:auto;display:flex;gap:8px;align-items:center">
                <button class="btn-soft no-print" onclick="cetakRekapSiswaWali()" style="font-size:.78rem;padding:6px 12px"><i class="fas fa-print"></i> Cetak Riwayat</button>
                <button class="x" onclick="document.getElementById('modalRiwayat').classList.remove('show')">&times;</button>
            </div>
        </div>
        <!-- Stats mini -->
        <div id="rwStats" style="display:grid;grid-template-columns:repeat(8,1fr);gap:6px;padding:12px 18px;border-bottom:1px solid #f1f5f9;background:#f8fafc"></div>
        <!-- Riwayat timeline -->
        <div style="padding:16px 18px">
            <div style="font-weight:700;font-size:.82rem;color:#1e293b;margin-bottom:12px;display:flex;justify-content:space-between;align-items:center">
                <span><i class="fas fa-history" style="color:#4f46e5;margin-right:5px"></i>Riwayat Pelanggaran, Alpa &amp; Terlambat</span>
                <span id="rwCount" style="font-size:.72rem;color:#94a3b8;font-weight:400"></span>
            </div>
            <div class="rw-tabs">
                <button type="button" class="rw-tab active" data-rwtab="baru" onclick="rwSetTab('baru')">Belum Diperbaiki <span class="cnt" id="rwTabCntBaru">0</span></button>
                <button type="button" class="rw-tab" data-rwtab="diperbaiki" onclick="rwSetTab('diperbaiki')">Sudah Diperbaiki <span class="cnt" id="rwTabCntDiperbaiki">0</span></button>
            </div>
            <div id="rwBody" style="max-height:420px;overflow-y:auto;padding-right:4px">
                <div style="text-align:center;padding:30px;color:#94a3b8"><i class="fas fa-spinner fa-spin"></i> Memuat data...</div>
            </div>
        </div>
    </div>
</div>
<script>
// ── Modal Riwayat Siswa (Data Siswa) — senada Portal BK, tanpa Input Point Perbaikan ──
var rwCurrentSiswaId = null;
var rwTimelineData   = [];
var rwCurrentTab      = 'baru';
var rwBadgeMap = {'Pelanggaran Disiplin':'b-pelanggaran','Terlambat':'b-terlambat','Alpa':'b-alpa'};
var rwCurrentSiswa   = null;
var rwCurrentStat    = null;
var rwNamaSek        = <?= json_encode($pengaturan['nama_sekolah'] ?? 'Sekolah') ?>;
var rwWaliNama        = <?= json_encode($wali['nama'] ?? 'Wali Kelas') ?>;

function bukaRiwayat(id){
    rwCurrentSiswaId = id;
    rwTimelineData   = [];
    rwCurrentSiswa   = null;
    rwCurrentStat    = null;
    rwSetTab('baru', true);
    var modal = document.getElementById('modalRiwayat');
    modal.classList.add('show');
    document.getElementById('rwNama').textContent = 'Memuat...';
    document.getElementById('rwBody').innerHTML = '<div style="text-align:center;padding:30px;color:#94a3b8"><i class="fas fa-spinner fa-spin"></i> Memuat data...</div>';
    document.getElementById('rwStats').innerHTML = '';
    document.getElementById('rwCount').textContent = '';

    fetch('portal_wali.php?ajax=riwayat&id='+id)
        .then(r=>r.json())
        .then(function(d){
            if(!d.ok){ document.getElementById('rwBody').innerHTML='<div style="padding:20px;color:#dc2626">'+(d.msg||'Error')+'</div>'; return; }
            document.getElementById('rwNama').textContent = d.siswa.nama;
            rwCurrentSiswa = d.siswa;
            rwCurrentStat  = d.stat;

            var statColors = {Hadir:'#16a34a',Terlambat:'#d97706',Alpa:'#dc2626',Izin:'#7c3aed',Sakit:'#2563eb',Pelanggaran:'#dc2626',Kunjungan:'#16a34a',TotalPoin:'#4f46e5'};
            var statLabels = {Hadir:'Hadir',Terlambat:'Terlambat',Alpa:'Alpa',Izin:'Izin',Sakit:'Sakit',Pelanggaran:'Pelanggaran Disiplin',Kunjungan:'Kunjungan',TotalPoin:'Total Poin'};
            var sHtml = '';
            Object.keys(statLabels).forEach(function(k){
                sHtml += '<div style="background:#f8fafc;border-radius:8px;padding:6px;text-align:center;border:1px solid #f1f5f9">'
                    + '<div style="font-size:1.2rem;font-weight:800;color:'+statColors[k]+'">'+d.stat[k]+'</div>'
                    + '<div style="font-size:.6rem;color:#64748b;font-weight:600;text-transform:uppercase">'+statLabels[k]+'</div>'
                    + '</div>';
            });
            document.getElementById('rwStats').innerHTML = sHtml;

            document.getElementById('rwCount').textContent = d.timeline.length+' aktifitas tercatat';
            rwTimelineData = d.timeline;
            rwRenderTimeline();
        })
        .catch(function(){ document.getElementById('rwBody').innerHTML = '<div style="padding:20px;color:#dc2626">Gagal memuat data</div>'; });
}

function rwSetTab(tab, silent){
    rwCurrentTab = tab;
    document.querySelectorAll('.rw-tab').forEach(function(btn){
        btn.classList.toggle('active', btn.getAttribute('data-rwtab')===tab);
    });
    if (!silent) rwRenderTimeline();
}

function rwRenderTimeline(){
    var data = rwTimelineData || [];
    var total = data.length;
    var baru  = data.filter(function(t){ return !t._sudah; }).length;
    var fixed = data.filter(function(t){ return t._sudah; }).length;
    var cBaru  = document.getElementById('rwTabCntBaru');  if (cBaru)  cBaru.textContent  = baru;
    var cFixed = document.getElementById('rwTabCntDiperbaiki'); if (cFixed) cFixed.textContent = fixed;
    document.getElementById('rwCount').textContent = total+' aktifitas tercatat';

    var list = rwCurrentTab === 'diperbaiki'
        ? data.filter(function(t){ return t._sudah; })
        : data.filter(function(t){ return !t._sudah; });

    var html = '';
    if (total===0) {
        html = '<div style="color:#94a3b8;padding:20px 0;text-align:center;font-size:.82rem">Belum ada riwayat aktifitas</div>';
    } else if (list.length===0) {
        var emptyMsg = rwCurrentTab==='baru' ? 'Tidak ada yang belum diperbaiki' : 'Belum ada yang diperbaiki';
        html = '<div style="color:#94a3b8;padding:20px 0;text-align:center;font-size:.82rem">'+emptyMsg+'</div>';
    } else {
        list.forEach(function(t){
            var cls = rwBadgeMap[t.tipe] || '';
            html += '<div class="tl-item">';
            html += '<div class="tl-date">'+escRwH(t.tgl)+' &nbsp; <span class="badge '+cls+'">'+escRwH(t.tipe)+'</span>'
                + (t._sudah ? ' <span class="badge b-sudah"><i class="fas fa-check"></i> Sudah Diperbaiki</span>' : '')
                + '</div>';
            html += '<div class="tl-title">'+(t.judul ? escRwH(t.judul) : '')+'</div>';
            if (t.ket) html += '<div class="tl-ket">'+escRwH(t.ket)+'</div>';
            html += '</div>';
        });
    }
    document.getElementById('rwBody').innerHTML = html;
}

function escRwH(s) {
    return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function cetakRekapSiswaWali() {
    if (!rwCurrentSiswa || !rwCurrentStat) { return; }
    var s = rwCurrentSiswa, sv = rwCurrentStat;
    document.getElementById('pr-namaSek').textContent  = rwNamaSek;
    document.getElementById('pr-namaGuru').textContent = rwWaliNama;
    document.getElementById('pr-nama').textContent      = s.nama;
    document.getElementById('pr-nis').textContent       = s.nis;
    document.getElementById('pr-kelas').textContent     = s.kelas;
    document.getElementById('pr-identitas').textContent = s.kelas + ' / ' + s.nis;
    document.getElementById('pr-terlambat').textContent   = sv.Terlambat||0;
    document.getElementById('pr-alpa').textContent        = sv.Alpa||0;
    document.getElementById('pr-izin').textContent        = sv.Izin||0;
    document.getElementById('pr-sakit').textContent       = sv.Sakit||0;
    document.getElementById('pr-pelanggaran').textContent = sv.Pelanggaran||0;
    document.getElementById('pr-kunjungan').textContent   = sv.Kunjungan||0;
    document.getElementById('pr-totalPoin').textContent   = sv.TotalPoin||0;
    document.getElementById('pr-totalPerbaikan').textContent = sv.TotalPoin||0;
    var now = new Date();
    var bln = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    document.getElementById('pr-tglCetak').textContent = now.getDate()+' '+bln[now.getMonth()+1]+' '+now.getFullYear();
    window.print();
}

document.querySelectorAll('.modal-overlay').forEach(function(ov){
    ov.addEventListener('click', function(e){ if (e.target === ov) ov.classList.remove('show'); });
});
</script>

<!-- ═══ PRINT AREA: REKAP RIWAYAT SISWA (khusus tampil saat cetak) ═══ -->
<style>
@media print {
    body { background: white !important; }
    .sidebar, .topbar, .no-print, .modal-overlay, .card { display: none !important; }
    .print-rekap-siswa { display: block !important; }
}
.print-rekap-siswa { display: none; font-family: Arial, sans-serif; font-size: 11pt; color: #000; }
.print-header-sekolah { text-align: center; border-bottom: 2px solid #000; padding-bottom: 8px; margin-bottom: 16px; }
.print-header-sekolah .nama-sek { font-size: 14pt; font-weight: bold; }
.print-header-sekolah .sub-sek  { font-size: 10pt; }
.print-rekap-title { text-align: center; font-weight: bold; font-size: 13pt; margin-bottom: 16px; text-decoration: underline; }
.print-identitas-wrap { display: flex; gap: 20px; align-items: flex-start; margin-bottom: 14px; }
.print-identitas-tbl { flex: 1; border-collapse: collapse; font-size: 10.5pt; }
.print-identitas-tbl td { padding: 3px 6px; }
.print-identitas-tbl td:first-child { width: 130px; font-weight: 600; }
.print-rekap-tbl { width: 100%; border-collapse: collapse; font-size: 9.5pt; margin-top: 12px; }
.print-rekap-tbl th { border: 1px solid #000; padding: 5px 4px; text-align: center; background: #dbeafe; font-weight: bold; font-size: 9pt; }
.print-rekap-tbl td { border: 1px solid #000; padding: 4px 4px; text-align: center; min-height: 20px; }
.print-ttd-wrap { display: flex; justify-content: flex-end; margin-top: 24px; }
.print-ttd-box { text-align: center; width: 200px; }
.print-ttd-line { border-bottom: 1px solid #000; margin: 40px 20px 4px; }
</style>
<div class="print-rekap-siswa" id="printRekapSiswaWali">
    <div class="print-header-sekolah">
        <div class="nama-sek" id="pr-namaSek">SEKOLAH</div>
        <div class="sub-sek">Portal Wali Kelas</div>
    </div>
    <div class="print-rekap-title">REKAP RIWAYAT SISWA</div>
    <div class="print-identitas-wrap">
        <table class="print-identitas-tbl">
            <tr><td>IDENTITAS</td><td>:</td><td id="pr-identitas">—</td></tr>
            <tr><td>NAMA</td><td>:</td><td id="pr-nama">—</td></tr>
            <tr><td>NISN / NIS</td><td>:</td><td id="pr-nis">—</td></tr>
            <tr><td>KELAS</td><td>:</td><td id="pr-kelas">—</td></tr>
            <tr><td>TOTAL POIN</td><td>:</td><td id="pr-totalPerbaikan">0</td></tr>
        </table>
    </div>
    <table class="print-rekap-tbl">
        <thead>
            <tr>
                <th>TERLAMBAT</th><th>ALPA</th><th>IZIN</th><th>SAKIT</th>
                <th>PELANGGARAN</th><th>KUNJUNGAN</th><th>TOTAL POINT</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td id="pr-terlambat">0</td>
                <td id="pr-alpa">0</td>
                <td id="pr-izin">0</td>
                <td id="pr-sakit">0</td>
                <td id="pr-pelanggaran">0</td>
                <td id="pr-kunjungan">0</td>
                <td id="pr-totalPoin">0</td>
            </tr>
        </tbody>
    </table>
    <div class="print-ttd-wrap">
        <div class="print-ttd-box">
            <div id="pr-tglCetak" style="margin-bottom:4px"></div>
            Wali Kelas
            <div class="print-ttd-line"></div>
            <div id="pr-namaGuru" style="margin-top:4px;font-weight:bold"></div>
        </div>
    </div>
</div>
<!-- ═══ END PRINT AREA ═══ -->

<?php // ═══════════════════════════════ ABSEN MANUAL ═══════════════════════════════
elseif ($page === 'absen'):
    $absen_tgl = $_GET['absen_tgl'] ?? date('Y-m-d');
    if ($absen_tgl > date('Y-m-d')) $absen_tgl = date('Y-m-d');
    // Ambil status absensi hari terpilih untuk setiap siswa
    $absen_today_map = [];
    if ($anak_ids) {
        $ar2 = $conn->query("SELECT siswa_id, status, keterangan FROM absensi WHERE tanggal='$absen_tgl' AND siswa_id IN ($anak_ids_str)" . periode_where($conn));
        while ($r = $ar2->fetch_assoc()) $absen_today_map[$r['siswa_id']] = $r;
    }
?>
    <!-- Filter tanggal -->
    <form method="GET" style="display:flex;gap:10px;align-items:flex-end;margin-bottom:20px;flex-wrap:wrap">
        <input type="hidden" name="page" value="absen">
        <div>
            <div style="font-size:.75rem;font-weight:600;color:#64748b;margin-bottom:4px">Tanggal Absensi</div>
            <input type="date" name="absen_tgl" value="<?= $absen_tgl ?>" max="<?= date('Y-m-d') ?>"
                   style="padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;background:white;outline:none;font-size:.875rem">
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Tampilkan</button>
    </form>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-clipboard-check" style="color:#4f46e5"></i>
            Absen Manual — <?= format_tanggal($absen_tgl) ?>
            <span style="margin-left:auto;font-size:.75rem;color:#64748b;font-weight:400"><?= count($anak_list) ?> siswa</span>
        </div>
        <div id="absen-alert" style="display:none;padding:10px 20px;font-size:.85rem;border-radius:0;font-weight:600"></div>

        <?php if (!$anak_list): ?>
        <div style="text-align:center;padding:40px;color:#94a3b8">Belum ada siswa yang di-assign ke Anda.</div>
        <?php else: ?>

        <!-- ── Toolbar: Pilih Semua + Aksi Massal ── -->
        <div style="padding:12px 16px;background:#f8fafc;border-bottom:1px solid #e2e8f0;display:flex;gap:10px;align-items:center;flex-wrap:wrap">
            <!-- Checkbox Pilih Semua -->
            <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:.85rem;font-weight:700;color:#475569;user-select:none">
                <input type="checkbox" id="chkAll" onchange="toggleCheckAll(this.checked)"
                       style="width:16px;height:16px;cursor:pointer;accent-color:#4f46e5">
                Pilih Semua
            </label>
            <span style="color:#e2e8f0">|</span>

            <!-- Dropdown set status untuk yang diceklis -->
            <div style="display:flex;align-items:center;gap:6px">
                <span style="font-size:.8rem;font-weight:600;color:#64748b">Set status terpilih:</span>
                <select id="bulkStatus" style="padding:6px 10px;border:1px solid #e2e8f0;border-radius:8px;font-size:.82rem;background:white;outline:none;font-weight:600">
                    <option value="">-- Pilih Status --</option>
                    <option value="Hadir"     style="color:#16a34a">✅ Hadir</option>
                    <option value="Terlambat" style="color:#d97706">⏰ Terlambat</option>
                    <option value="Alpa"      style="color:#dc2626">❌ Alpa</option>
                    <option value="Sakit"     style="color:#2563eb">🏥 Sakit</option>
                    <option value="Izin"      style="color:#7c3aed">📄 Izin</option>
                    <option value="Bolos"     style="color:#9a3412">🚫 Bolos</option>
                </select>
                <button type="button" onclick="terapkanBulk()"
                        style="padding:6px 14px;border-radius:8px;border:none;background:#4f46e5;color:white;font-size:.8rem;font-weight:700;cursor:pointer">
                    <i class="fas fa-check"></i> Terapkan
                </button>
            </div>
            <span style="color:#e2e8f0">|</span>

            <!-- Shortcut cepat: Set Semua (6 status, senada Input Absensi Admin) -->
            <span style="font-size:.8rem;font-weight:600;color:#64748b">Set semua:</span>
            <?php foreach(['Hadir'=>['#16a34a','✅'],'Terlambat'=>['#d97706','⏰'],'Sakit'=>['#2563eb','🏥'],'Izin'=>['#7c3aed','📄'],'Alpa'=>['#dc2626','❌'],'Bolos'=>['#9a3412','🚫']] as $qs=>[$qc,$qi]): ?>
            <button type="button" onclick="pilihSemuaStatus('<?= $qs ?>','<?= $absen_tgl ?>')"
                    style="padding:5px 12px;border-radius:20px;border:2px solid <?= $qc ?>;background:white;color:<?= $qc ?>;font-size:.75rem;font-weight:700;cursor:pointer">
                <?= $qi ?> <?= $qs ?>
            </button>
            <?php endforeach; ?>

            <span id="selCount" style="margin-left:auto;font-size:.8rem;font-weight:700;color:#4f46e5;background:#eef2ff;padding:4px 10px;border-radius:20px;display:none">
                0 dipilih
            </span>
        </div>

        <!-- ── Tabel Siswa ── -->
        <div style="padding:0 16px 16px">
            <div style="overflow-x:auto;margin-top:12px">
            <table style="width:100%;border-collapse:collapse;font-size:.875rem">
                <thead>
                    <tr>
                        <th style="background:#1e293b;color:white;padding:10px 8px;text-align:center;width:36px">
                            <input type="checkbox" id="chkAllTh" onchange="toggleCheckAll(this.checked)"
                                   style="width:15px;height:15px;cursor:pointer;accent-color:#6366f1">
                        </th>
                        <th style="background:#1e293b;color:white;padding:10px 8px;text-align:center;width:36px">No</th>
                        <th style="background:#1e293b;color:white;padding:10px 8px;text-align:center;width:80px">NIS</th>
                        <th style="background:#1e293b;color:white;padding:10px 8px;text-align:left;min-width:180px">Nama Siswa</th>
                        <th style="background:#1e293b;color:white;padding:10px 8px;text-align:center;min-width:360px">Status Kehadiran</th>
                        <th style="background:#1e293b;color:white;padding:10px 8px;text-align:center;min-width:100px">Keterangan</th>
                        <th style="background:#1e293b;color:white;padding:10px 8px;text-align:center;width:80px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php $no=0; foreach($anak_list as $s):
                    $no++;
                    $cur = $absen_today_map[$s['id']] ?? null;
                    $cur_st  = $cur['status'] ?? '';
                    $cur_ket = $cur['keterangan'] ?? '';
                    $sid2 = $s['id'];
                ?>
                <tr id="row-<?= $sid2 ?>" style="<?= $no%2==0?'background:#f8fafc':'' ?>">
                    <td style="text-align:center;padding:8px">
                        <input type="checkbox" class="chk-siswa" data-sid="<?= $sid2 ?>"
                               onchange="updateSelCount()"
                               style="width:16px;height:16px;cursor:pointer;accent-color:#4f46e5">
                    </td>
                    <td style="text-align:center;font-weight:600;padding:10px 8px"><?= $no ?></td>
                    <td style="text-align:center;padding:10px 8px;font-family:monospace;font-size:.78rem;color:#64748b"><?= htmlspecialchars($s['nis']) ?></td>
                    <td style="padding:10px 8px">
                        <div style="font-weight:700"><?= htmlspecialchars($s['nama']) ?></div>
                        <div style="font-size:.73rem;color:#94a3b8"><?= $s['kelas'] ?></div>
                    </td>
                    <td style="padding:8px;text-align:center">
                        <div style="display:flex;gap:6px;flex-wrap:wrap;justify-content:center">
                        <?php
                        $st_colors = [
                            'Hadir'     => ['#16a34a','#dcfce7'],
                            'Terlambat' => ['#d97706','#fef3c7'],
                            'Alpa'      => ['#dc2626','#fee2e2'],
                            'Sakit'     => ['#2563eb','#dbeafe'],
                            'Izin'      => ['#7c3aed','#ede9fe'],
                            'Bolos'     => ['#9a3412','#ffedd5'],
                        ];
                        foreach($st_colors as $st=>[$c,$bg]):
                            $active = ($cur_st === $st);
                        ?>
                        <button type="button"
                            onclick="pilihStatus(<?= $sid2 ?>, '<?= $st ?>')"
                            id="btn-<?= $sid2 ?>-<?= $st ?>"
                            style="padding:5px 12px;border-radius:20px;border:2px solid <?= $c ?>;
                                background:<?= $active ? $c : 'white' ?>;
                                color:<?= $active ? 'white' : $c ?>;
                                font-size:.76rem;font-weight:700;cursor:pointer;transition:.15s">
                            <?= $st ?>
                        </button>
                        <?php endforeach; ?>
                        </div>
                    </td>
                    <td style="padding:8px;text-align:center">
                        <input type="text" id="ket-<?= $sid2 ?>" value="<?= htmlspecialchars($cur_ket) ?>"
                               placeholder="Opsional"
                               style="width:90%;padding:5px 8px;border:1px solid #e2e8f0;border-radius:6px;font-size:.78rem;outline:none">
                    </td>
                    <td style="padding:8px;text-align:center">
                        <button type="button" onclick="simpanAbsen(<?= $sid2 ?>, '<?= $absen_tgl ?>')"
                                id="save-<?= $sid2 ?>"
                                style="padding:6px 14px;border-radius:8px;border:none;background:#4f46e5;color:white;font-size:.78rem;font-weight:700;cursor:pointer">
                            <i class="fas fa-save"></i> Simpan
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>

            <!-- Tombol Simpan Semua -->
            <div style="margin-top:16px;display:flex;gap:10px;align-items:center;flex-wrap:wrap">
                <button type="button" onclick="simpanSemua('<?= $absen_tgl ?>')"
                        style="padding:10px 24px;border-radius:8px;border:none;background:#16a34a;color:white;font-weight:700;cursor:pointer;font-size:.875rem">
                    <i class="fas fa-check-double"></i> Simpan Semua
                </button>
                <button type="button" onclick="simpanTerpilih('<?= $absen_tgl ?>')"
                        id="btnSimpanTerpilih"
                        style="padding:10px 18px;border-radius:8px;border:2px solid #4f46e5;color:#4f46e5;background:white;font-weight:700;cursor:pointer;font-size:.8rem;display:none">
                    <i class="fas fa-save"></i> Simpan Terpilih
                </button>
                <span style="font-size:.8rem;color:#64748b"><i class="fas fa-info-circle"></i> Centang siswa → set status → Terapkan → Simpan Terpilih, atau Simpan Semua sekaligus.</span>
            </div>
        </div>
        <?php endif; ?>
    </div>

<script>
// State status yang dipilih per siswa
var selectedStatus = {};
<?php foreach($anak_list as $s): $sid2=$s['id']; $cur_st = $absen_today_map[$sid2]['status'] ?? ''; ?>
selectedStatus[<?= $sid2 ?>] = '<?= $cur_st ?>';
<?php endforeach; ?>

var statusColors = {
    'Hadir':     ['#16a34a','#dcfce7'],
    'Terlambat': ['#d97706','#fef3c7'],
    'Alpa':      ['#dc2626','#fee2e2'],
    'Sakit':     ['#2563eb','#dbeafe'],
    'Izin':      ['#7c3aed','#ede9fe'],
    'Bolos':     ['#9a3412','#ffedd5'],
};

function pilihStatus(sid, status) {
    selectedStatus[sid] = status;
    // Reset semua tombol untuk siswa ini
    ['Hadir','Terlambat','Alpa','Sakit','Izin','Bolos'].forEach(function(st) {
        var btn = document.getElementById('btn-'+sid+'-'+st);
        if (!btn) return;
        var c = statusColors[st][0], bg = statusColors[st][1];
        if (st === status) {
            btn.style.background = c; btn.style.color = 'white';
        } else {
            btn.style.background = 'white'; btn.style.color = c;
        }
    });
}

function pilihSemuaStatus(status, tgl) {
    var sids = [<?= implode(',', $anak_ids) ?>];
    sids.forEach(function(sid){ pilihStatus(sid, status); });
}

// ── Checkbox functions ────────────────────────────────────────
function toggleCheckAll(checked) {
    document.querySelectorAll('.chk-siswa').forEach(function(c){ c.checked = checked; });
    // Sync both checkboxes
    var ca = document.getElementById('chkAll');
    var ct = document.getElementById('chkAllTh');
    if (ca) ca.checked = checked;
    if (ct) ct.checked = checked;
    updateSelCount();
}

function updateSelCount() {
    var n = document.querySelectorAll('.chk-siswa:checked').length;
    var el = document.getElementById('selCount');
    var btnT = document.getElementById('btnSimpanTerpilih');
    if (el)  { el.textContent = n + ' dipilih'; el.style.display = n>0?'inline-block':'none'; }
    if (btnT){ btnT.style.display = n>0?'inline-flex':'none'; }
    // Sync header checkboxes
    var total = document.querySelectorAll('.chk-siswa').length;
    var ca = document.getElementById('chkAll');
    var ct = document.getElementById('chkAllTh');
    if (ca) ca.checked = (n === total && total > 0);
    if (ct) ct.checked = (n === total && total > 0);
}

function terapkanBulk() {
    var status = document.getElementById('bulkStatus').value;
    if (!status) { showAlert('Pilih status terlebih dahulu di dropdown!', 'danger'); return; }
    var checked = document.querySelectorAll('.chk-siswa:checked');
    if (checked.length === 0) { showAlert('Centang minimal 1 siswa terlebih dahulu!', 'danger'); return; }
    checked.forEach(function(c){ pilihStatus(parseInt(c.getAttribute('data-sid')), status); });
    showAlert(checked.length + ' siswa ditandai sebagai ' + status + '. Klik Simpan Terpilih untuk menyimpan.', 'success');
}

function simpanTerpilih(tgl) {
    var checked = document.querySelectorAll('.chk-siswa:checked');
    if (checked.length === 0) { showAlert('Centang minimal 1 siswa!', 'danger'); return; }
    var sids = Array.from(checked).map(function(c){ return parseInt(c.getAttribute('data-sid')); });
    var allSet = true;
    sids.forEach(function(sid){ if (!selectedStatus[sid]) allSet = false; });
    if (!allSet) { showAlert('Pastikan semua siswa yang dipilih sudah ada statusnya!', 'danger'); return; }
    var pending = sids.length;
    sids.forEach(function(sid){
        var ket = document.getElementById('ket-'+sid)?.value || '';
        var btn = document.getElementById('save-'+sid);
        btn.disabled=true; btn.innerHTML='<i class="fas fa-spinner fa-spin"></i>';
        var fd = new FormData();
        fd.append('ajax_absen_manual','1');
        fd.append('siswa_id', sid);
        fd.append('status', selectedStatus[sid]);
        fd.append('tanggal', tgl);
        fd.append('keterangan', ket);
        fetch('portal_wali.php', {method:'POST',body:fd})
            .then(function(r){return r.json();})
            .then(function(d){
                if (d.ok) { btn.innerHTML='<i class="fas fa-check"></i> Tersimpan'; btn.style.background='#16a34a'; }
                else { btn.innerHTML='<i class="fas fa-save"></i> Simpan'; btn.disabled=false; }
                pending--;
                if (pending===0) showAlert(sids.length + ' absensi berhasil disimpan!', 'success');
            });
    });
}

function simpanAbsen(sid, tgl) {
    var status = selectedStatus[sid] || '';
    if (!status) { showAlert('Pilih status untuk siswa ini terlebih dahulu!', 'danger'); return; }
    var ket = document.getElementById('ket-'+sid)?.value || '';
    var btn = document.getElementById('save-'+sid);
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    var fd = new FormData();
    fd.append('ajax_absen_manual','1');
    fd.append('siswa_id', sid);
    fd.append('status', status);
    fd.append('tanggal', tgl);
    fd.append('keterangan', ket);
    fetch('portal_wali.php', {method:'POST',body:fd})
        .then(function(r){return r.json();})
        .then(function(d){
            if (d.ok) {
                btn.innerHTML = '<i class="fas fa-check"></i> Tersimpan';
                btn.style.background = '#16a34a';
                showAlert('Absensi '+status+' berhasil disimpan!', 'success');
            } else {
                btn.innerHTML = '<i class="fas fa-save"></i> Simpan';
                btn.disabled = false;
                showAlert(d.msg || 'Gagal menyimpan', 'danger');
            }
        })
        .catch(function(){ btn.innerHTML='<i class="fas fa-save"></i> Simpan'; btn.disabled=false; });
}

function simpanSemua(tgl) {
    var sids = [<?= implode(',', $anak_ids) ?>];
    var all_ok = true;
    sids.forEach(function(sid){
        if (!selectedStatus[sid]) { all_ok = false; }
    });
    if (!all_ok) { showAlert('Harap pilih status untuk semua siswa terlebih dahulu!', 'danger'); return; }
    var pending = sids.length;
    sids.forEach(function(sid){
        var ket = document.getElementById('ket-'+sid)?.value || '';
        var fd = new FormData();
        fd.append('ajax_absen_manual','1');
        fd.append('siswa_id', sid);
        fd.append('status', selectedStatus[sid]);
        fd.append('tanggal', tgl);
        fd.append('keterangan', ket);
        var btn = document.getElementById('save-'+sid);
        btn.disabled=true; btn.innerHTML='<i class="fas fa-spinner fa-spin"></i>';
        fetch('portal_wali.php', {method:'POST',body:fd})
            .then(function(r){return r.json();})
            .then(function(d){
                if (d.ok) { btn.innerHTML='<i class="fas fa-check"></i> Tersimpan'; btn.style.background='#16a34a'; }
                else { btn.innerHTML='<i class="fas fa-save"></i> Simpan'; btn.disabled=false; }
                pending--;
                if (pending===0) showAlert('Semua absensi berhasil disimpan!', 'success');
            });
    });
}

function showAlert(msg, type) {
    var el = document.getElementById('absen-alert');
    el.style.display='block';
    el.style.background = type==='success'?'#dcfce7':'#fee2e2';
    el.style.color = type==='success'?'#166534':'#991b1b';
    el.innerHTML = '<i class="fas fa-'+(type==='success'?'check-circle':'exclamation-circle')+'"></i> '+msg;
    setTimeout(function(){ el.style.display='none'; }, 4000);
}
</script>

<?php // ═══════════════════════════ EDIT ABSENSI ═══════════════════════════
elseif ($page === 'edit_absensi'):
    $today_ea  = date('Y-m-d');
    $tgl_ea    = isset($_GET['tanggal']) ? sanitize($_GET['tanggal']) : $today_ea;
    if ($tgl_ea > $today_ea) $tgl_ea = $today_ea;

    // Ambil kelas unik dari anak didik
    $kelas_anak = [];
    foreach ($anak_list as $s) {
        if (!in_array($s['kelas'], $kelas_anak)) $kelas_anak[] = $s['kelas'];
    }
    sort($kelas_anak);
    $filter_kelas_ea = isset($_GET['kelas']) ? sanitize($_GET['kelas']) : '';
    // Validasi kelas hanya boleh milik anak didik wali ini
    if ($filter_kelas_ea && !in_array($filter_kelas_ea, $kelas_anak)) $filter_kelas_ea = '';

    // Ambil data absensi
    $ea_list = [];
    if ($anak_ids) {
        $kelas_cond = $filter_kelas_ea ? "AND a.kelas='$filter_kelas_ea'" : '';
        $res_ea = $conn->query("
            SELECT a.*, s.foto
            FROM absensi a
            JOIN siswa s ON a.siswa_id = s.id
            WHERE a.tanggal='$tgl_ea' AND a.siswa_id IN ($anak_ids_str) $kelas_cond" . periode_where($conn, 'a.') . "
            ORDER BY a.kelas, a.nama
        ");
        while ($r = $res_ea->fetch_assoc()) $ea_list[] = $r;
    }

    $ea_stats = ['Hadir'=>0,'Terlambat'=>0,'Sakit'=>0,'Izin'=>0,'Alpa'=>0,'Bolos'=>0];
    foreach ($ea_list as $a) { if (isset($ea_stats[$a['status']])) $ea_stats[$a['status']]++; }
?>
<style>
.ea-stat-mini{display:inline-flex;align-items:center;gap:6px;padding:5px 13px;border-radius:20px;font-size:.8rem;font-weight:700;margin:2px}
.ea-sm-hadir{background:#dcfce7;color:#15803d}.ea-sm-terlambat{background:#fef9c3;color:#854d0e}
.ea-sm-alpa{background:#fee2e2;color:#991b1b}.ea-sm-sakit{background:#dbeafe;color:#1e40af}
.ea-sm-izin{background:#ede9fe;color:#5b21b6}.ea-sm-bolos{background:#ffedd5;color:#9a3412}
#eaModal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center}
#eaModal.show{display:flex}
.ea-modal-box{background:#fff;border-radius:16px;padding:26px 28px;min-width:320px;max-width:400px;width:92%;box-shadow:0 20px 60px rgba(0,0,0,.25)}
.ea-status-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:14px}
.ea-st-opt{border:2px solid #e2e8f0;border-radius:10px;padding:10px 6px;text-align:center;cursor:pointer;font-size:.8rem;font-weight:600;color:#475569;background:#fff;transition:.15s}
.ea-st-opt:hover{transform:scale(1.04)}
.ea-st-opt.sel{color:#fff;border-color:transparent}
.ea-st-hadir{--c:#16a34a}.ea-st-terlambat{--c:#d97706}.ea-st-sakit{--c:#0891b2}
.ea-st-izin{--c:#7c3aed}.ea-st-alpa{--c:#64748b}.ea-st-bolos{--c:#dc2626}
.ea-st-opt.sel{background:var(--c);border-color:var(--c)}
.ea-edit-btn{background:#eff6ff;color:#2563eb;border:none;padding:5px 12px;border-radius:7px;font-size:.77rem;font-weight:600;cursor:pointer}
.ea-edit-btn:hover{background:#dbeafe}
#eaTable tbody tr{cursor:pointer;transition:background .12s}
#eaTable tbody tr:hover{background:#eff6ff !important}
</style>

<!-- Filter -->
<div class="card" style="margin-bottom:16px">
    <div class="card-body">
        <form method="GET" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
            <input type="hidden" name="page" value="edit_absensi">
            <div>
                <div style="font-size:.75rem;font-weight:600;color:#64748b;margin-bottom:4px">Tanggal</div>
                <input type="date" name="tanggal" value="<?= $tgl_ea ?>" max="<?= $today_ea ?>"
                       style="padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:.875rem;outline:none">
            </div>
            <div>
                <div style="font-size:.75rem;font-weight:600;color:#64748b;margin-bottom:4px">Kelas</div>
                <select name="kelas" style="padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:.875rem;background:white;outline:none">
                    <option value="">-- Semua Kelas --</option>
                    <?php foreach ($kelas_anak as $kn): ?>
                    <option value="<?= $kn ?>" <?= $filter_kelas_ea===$kn?'selected':'' ?>><?= htmlspecialchars($kn) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" style="padding:8px 18px;background:#4f46e5;color:white;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:.875rem">
                <i class="fas fa-search"></i> Tampilkan
            </button>
        </form>
    </div>
</div>

<?php if (!empty($ea_list)): ?>
<!-- Statistik mini -->
<div style="margin-bottom:12px;display:flex;flex-wrap:wrap;gap:4px;align-items:center">
    <span style="font-size:.8rem;color:#64748b;margin-right:4px"><i class="fas fa-chart-bar"></i> Rekap:</span>
    <?php
    $ea_stat_icons = ['Hadir'=>'✅','Terlambat'=>'⏰','Sakit'=>'🏥','Izin'=>'📋','Alpa'=>'❌','Bolos'=>'🚫'];
    $ea_stat_cls   = ['Hadir'=>'ea-sm-hadir','Terlambat'=>'ea-sm-terlambat','Sakit'=>'ea-sm-sakit','Izin'=>'ea-sm-izin','Alpa'=>'ea-sm-alpa','Bolos'=>'ea-sm-bolos'];
    foreach ($ea_stats as $st => $jml): if ($jml > 0): ?>
    <span class="ea-stat-mini <?= $ea_stat_cls[$st] ?>"><?= $ea_stat_icons[$st] ?> <?= $st ?>: <?= $jml ?></span>
    <?php endif; endforeach; ?>
    <span style="margin-left:8px;font-size:.8rem;color:#64748b">Total: <?= count($ea_list) ?> siswa</span>
</div>

<!-- Tabel -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-pen-square" style="color:#4f46e5"></i>
        <?php
        $hari_ea = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
        $nama_hari_ea = $hari_ea[date('l', strtotime($tgl_ea))] ?? '';
        echo $nama_hari_ea . ', ' . date('d', strtotime($tgl_ea)) . ' ' . ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'][(int)date('m', strtotime($tgl_ea))] . ' ' . date('Y', strtotime($tgl_ea));
        ?>
        <?php if ($filter_kelas_ea): ?> &mdash; Kelas <strong><?= $filter_kelas_ea ?></strong><?php endif; ?>
        <div style="margin-left:auto">
            <input type="text" id="eaSearch" placeholder="🔍 Cari nama..." oninput="eaSearchFilter()"
                   style="padding:6px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:.8rem;outline:none;width:180px">
        </div>
    </div>
    <div style="overflow-x:auto">
        <table id="eaTable" style="width:100%;border-collapse:collapse;font-size:.82rem">
            <thead>
                <tr>
                    <th style="background:#1e293b;color:white;padding:8px 6px;text-align:center;width:4%">#</th>
                    <th style="background:#1e293b;color:white;padding:8px 6px;text-align:left;width:28%">Nama Siswa</th>
                    <th style="background:#1e293b;color:white;padding:8px 6px;text-align:center;width:10%">Kelas</th>
                    <th style="background:#1e293b;color:white;padding:8px 6px;text-align:center;width:12%">Jam Masuk</th>
                    <th style="background:#1e293b;color:white;padding:8px 6px;text-align:center;width:14%">Status</th>
                    <th style="background:#1e293b;color:white;padding:8px 6px;text-align:left;width:22%">Keterangan</th>
                    <th style="background:#1e293b;color:white;padding:8px 6px;text-align:center;width:10%">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $ea_bg = ['Hadir'=>'#f0fdf4','Terlambat'=>'#fffbeb','Alpa'=>'#f8fafc','Sakit'=>'#eff6ff','Izin'=>'#f5f3ff','Bolos'=>'#fff7ed'];
            $ea_badge = [
                'Hadir'     => ['background:#dcfce7;color:#15803d'],
                'Terlambat' => ['background:#fef9c3;color:#854d0e'],
                'Alpa'      => ['background:#fee2e2;color:#991b1b'],
                'Sakit'     => ['background:#dbeafe;color:#1e40af'],
                'Izin'      => ['background:#ede9fe;color:#5b21b6'],
                'Bolos'     => ['background:#ffedd5;color:#9a3412'],
            ];
            foreach ($ea_list as $i => $a):
                $bg = $ea_bg[$a['status']] ?? '';
                $bd = $ea_badge[$a['status']][0] ?? 'background:#f1f5f9;color:#64748b';
            ?>
            <tr onclick="eaOpenEdit(<?= htmlspecialchars(json_encode($a), ENT_QUOTES) ?>)" style="background:<?= $bg ?>">
                <td style="text-align:center;padding:8px 4px"><?= $i+1 ?></td>
                <td style="padding:8px 6px">
                    <div style="display:flex;align-items:center;gap:8px">
                        <?php if (!empty($a['foto']) && file_exists('uploads/foto/'.$a['foto'])): ?>
                            <img src="<?= BASE_URL ?>uploads/foto/<?= $a['foto'] ?>" style="width:30px;height:30px;border-radius:50%;object-fit:cover;flex-shrink:0">
                        <?php else: ?>
                            <div style="width:30px;height:30px;border-radius:50%;background:#4f46e5;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.75rem;flex-shrink:0">
                                <?= strtoupper(substr($a['nama'],0,1)) ?>
                            </div>
                        <?php endif; ?>
                        <div>
                            <div style="font-weight:600"><?= htmlspecialchars($a['nama']) ?></div>
                            <div style="font-size:.72rem;color:#94a3b8"><?= $a['nis'] ?></div>
                        </div>
                    </div>
                </td>
                <td style="text-align:center;padding:8px 4px">
                    <span style="background:#eef2ff;color:#4338ca;padding:2px 8px;border-radius:12px;font-size:.75rem;font-weight:600"><?= $a['kelas'] ?></span>
                </td>
                <td style="text-align:center;padding:8px 4px;color:#64748b;font-size:.82rem">
                    <?= $a['jam_masuk'] ? date('H:i', strtotime($a['jam_masuk'])) : '<span style="color:#cbd5e1">—</span>' ?>
                </td>
                <td style="text-align:center;padding:8px 4px">
                    <span style="<?= $bd ?>;padding:3px 10px;border-radius:20px;font-size:.78rem;font-weight:700"><?= $a['status'] ?></span>
                </td>
                <td style="padding:8px 6px;font-size:.8rem;color:#475569"><?= htmlspecialchars($a['keterangan'] ?? '') ?: '<span style="color:#cbd5e1">—</span>' ?></td>
                <td style="text-align:center;padding:8px 4px">
                    <button class="ea-edit-btn" onclick="event.stopPropagation();eaOpenEdit(<?= htmlspecialchars(json_encode($a), ENT_QUOTES) ?>)">
                        <i class="fas fa-pen"></i> Edit
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php elseif (isset($_GET['tanggal'])): ?>
<div style="background:white;border-radius:12px;padding:40px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,.06)">
    <i class="fas fa-info-circle" style="font-size:2rem;color:#94a3b8;margin-bottom:12px;display:block"></i>
    <div style="color:#64748b;font-weight:600">Tidak ada data absensi pada tanggal ini<?= $filter_kelas_ea ? ' untuk kelas '.$filter_kelas_ea : '' ?>.</div>
</div>
<?php else: ?>
<div class="card"><div class="card-body" style="text-align:center;padding:60px">
    <i class="fas fa-pen-square fa-3x" style="color:#cbd5e1;margin-bottom:16px;display:block"></i>
    <div style="font-weight:700;font-size:1rem;color:#64748b;margin-bottom:8px">Pilih Tanggal</div>
    <div style="color:#94a3b8;font-size:.875rem">Pilih tanggal di atas untuk melihat dan mengedit data absensi siswa Anda</div>
</div></div>
<?php endif; ?>

<!-- Modal Edit Absensi -->
<div id="eaModal">
    <div class="ea-modal-box">
        <h3 id="eaModalNama" style="margin:0 0 4px;font-size:1rem;color:#1e293b">—</h3>
        <div id="eaModalSub" style="font-size:.78rem;color:#64748b;margin-bottom:18px">NIS • Kelas</div>

        <div style="font-size:.8rem;font-weight:600;color:#374151;margin-bottom:8px">Ubah Status:</div>
        <div class="ea-status-grid">
            <?php foreach([
                'Hadir'     => ['✅','ea-st-hadir'],
                'Terlambat' => ['⏰','ea-st-terlambat'],
                'Sakit'     => ['🏥','ea-st-sakit'],
                'Izin'      => ['📋','ea-st-izin'],
                'Alpa'      => ['❌','ea-st-alpa'],
                'Bolos'     => ['🚫','ea-st-bolos'],
            ] as $st => [$ico,$cls]): ?>
            <div class="ea-st-opt <?= $cls ?>" data-status="<?= $st ?>" onclick="eaPilihStatus('<?= $st ?>')">
                <?= $ico ?><br><?= $st ?>
            </div>
            <?php endforeach; ?>
        </div>

        <div style="font-size:.8rem;font-weight:600;color:#374151;margin-bottom:5px">Keterangan:</div>
        <input type="text" id="eaModalKet" placeholder="Opsional..."
               style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:.875rem;outline:none;margin-bottom:4px">

        <div style="display:flex;gap:8px;margin-top:16px">
            <button onclick="eaCloseModal()" style="background:#f1f5f9;color:#475569;border:none;padding:10px 16px;border-radius:8px;font-weight:600;cursor:pointer;font-size:.875rem">Batal</button>
            <button onclick="eaHapusAbsen()" style="background:#fee2e2;color:#991b1b;border:none;padding:10px 14px;border-radius:8px;font-weight:600;cursor:pointer;font-size:.875rem" title="Hapus — reset ke Belum Absen">
                <i class="fas fa-trash"></i>
            </button>
            <button onclick="eaSimpan()" style="flex:1;background:#4f46e5;color:white;border:none;padding:10px;border-radius:8px;font-weight:700;cursor:pointer;font-size:.875rem">
                <i class="fas fa-save"></i> Simpan
            </button>
        </div>
        <p style="font-size:.7rem;color:#94a3b8;margin-top:10px;text-align:center"><i class="fas fa-info-circle"></i> Hapus = siswa kembali ke Belum Absen</p>
    </div>
</div>

<script>
var eaCurrentId   = null;
var eaSelStatus   = null;
var eaCurrentRow  = null;
var eaKetDefaults = {Hadir:'',Terlambat:'Terlambat',Sakit:'Sakit',Izin:'Izin',Alpa:'Alpa',Bolos:'Bolos'};

function eaOpenEdit(data) {
    eaCurrentId  = data.id;
    eaSelStatus  = data.status;
    document.getElementById('eaModalNama').textContent = data.nama;
    document.getElementById('eaModalSub').textContent  = 'NIS: '+data.nis+' • Kelas: '+data.kelas+(data.jam_masuk?' • Jam: '+data.jam_masuk.substring(0,5):'');
    document.getElementById('eaModalKet').value = data.keterangan || '';
    document.querySelectorAll('.ea-st-opt').forEach(function(el){
        el.classList.toggle('sel', el.dataset.status === data.status);
    });
    document.getElementById('eaModal').classList.add('show');
}

function eaPilihStatus(st) {
    eaSelStatus = st;
    document.querySelectorAll('.ea-st-opt').forEach(function(el){
        el.classList.toggle('sel', el.dataset.status === st);
    });
    var ket = document.getElementById('eaModalKet');
    var defVals = Object.values(eaKetDefaults);
    if (!ket.value || defVals.indexOf(ket.value) !== -1) ket.value = eaKetDefaults[st] || '';
}

function eaCloseModal() {
    document.getElementById('eaModal').classList.remove('show');
    eaCurrentId = null; eaSelStatus = null;
}

function eaSimpan() {
    if (!eaCurrentId || !eaSelStatus) return;
    var ket = document.getElementById('eaModalKet').value;
    var fd = new FormData();
    fd.append('wali_edit_single','1');
    fd.append('absen_id', eaCurrentId);
    fd.append('new_status', eaSelStatus);
    fd.append('new_keterangan', ket);
    fetch('portal_wali.php', {method:'POST',body:fd})
        .then(function(r){return r.json();})
        .then(function(d){
            if (d.ok) {
                eaCloseModal();
                eaShowToast('Absensi berhasil diperbarui!','success');
                setTimeout(function(){ location.reload(); }, 1200);
            } else {
                eaShowToast('Gagal menyimpan perubahan.','error');
            }
        });
}

function eaHapusAbsen() {
    if (!eaCurrentId) return;
    if (!confirm('Hapus absensi ini? Siswa akan kembali ke daftar Belum Absen.')) return;
    var fd = new FormData();
    fd.append('wali_hapus_absen','1');
    fd.append('absen_id', eaCurrentId);
    fetch('portal_wali.php', {method:'POST',body:fd})
        .then(function(r){return r.json();})
        .then(function(d){
            if (d.ok) {
                eaCloseModal();
                eaShowToast('Absensi berhasil dihapus!','success');
                setTimeout(function(){ location.reload(); }, 1200);
            } else {
                eaShowToast('Gagal menghapus.','error');
            }
        });
}

document.getElementById('eaModal').addEventListener('click', function(e){
    if (e.target === this) eaCloseModal();
});

function eaSearchFilter() {
    var q = document.getElementById('eaSearch').value.toLowerCase();
    document.querySelectorAll('#eaTable tbody tr').forEach(function(tr){
        tr.style.display = tr.textContent.toLowerCase().indexOf(q) !== -1 ? '' : 'none';
    });
}

function eaShowToast(msg, type) {
    var el = document.createElement('div');
    el.textContent = msg;
    el.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:99999;padding:12px 20px;border-radius:10px;font-size:.875rem;font-weight:600;box-shadow:0 4px 20px rgba(0,0,0,.2);color:white;background:'+(type==='success'?'#10b981':'#ef4444');
    document.body.appendChild(el);
    setTimeout(function(){ el.style.transition='opacity .3s'; el.style.opacity='0'; setTimeout(function(){ el.remove(); },300); }, 3000);
}
</script>

<?php // ═══════════════════════════════ CHAT ═══════════════════════════════
elseif ($page === 'chat'): ?>

<?php if (!$siswa_ok || !$detail_siswa): ?>
    <!-- Daftar siswa untuk chat -->
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:12px">
    <?php if (!$anak_list): ?>
        <div style="grid-column:1/-1;text-align:center;padding:60px;color:#94a3b8">Belum ada siswa yang di-assign ke Anda.</div>
    <?php else: foreach($anak_list as $s):
        $unread = (int)$conn->query("SELECT COUNT(*) c FROM pesan_wali WHERE wali_id=$wid AND siswa_id={$s['id']} AND pengirim='siswa' AND dibaca=0")->fetch_assoc()['c'];
        $last_msg = $conn->query("SELECT pesan,created_at FROM pesan_wali WHERE wali_id=$wid AND siswa_id={$s['id']} ORDER BY id DESC LIMIT 1")->fetch_assoc();
    ?>
    <a href="portal_wali.php?page=chat&sid=<?= $s['id'] ?>" class="siswa-row" style="flex-direction:row">
        <div class="avatar" style="width:48px;height:48px;font-size:1.1rem;position:relative">
            <?= strtoupper(substr($s['nama'],0,1)) ?>
            <?php if ($unread>0): ?><span style="position:absolute;top:-4px;right:-4px;background:#ef4444;color:white;font-size:.6rem;width:18px;height:18px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700"><?= $unread ?></span><?php endif; ?>
        </div>
        <div style="flex:1;min-width:0">
            <div style="font-weight:700;display:flex;align-items:center;gap:6px">
                <?= htmlspecialchars($s['nama']) ?>
                <?php if ($unread>0): ?><span class="unread-dot"></span><?php endif; ?>
            </div>
            <div style="font-size:.75rem;color:#64748b">Kelas <?= $s['kelas'] ?></div>
            <?php if ($last_msg): ?>
            <div style="font-size:.75rem;color:#94a3b8;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:200px;margin-top:2px">
                <?= htmlspecialchars(mb_strimwidth($last_msg['pesan'],0,40,'...')) ?>
            </div>
            <?php endif; ?>
        </div>
        <i class="fas fa-chevron-right" style="color:#cbd5e1"></i>
    </a>
    <?php endforeach; endif; ?>
    </div>

<?php else:
    // Chat dengan siswa tertentu
    $conn->query("UPDATE pesan_wali SET dibaca=1 WHERE siswa_id=$sid_url AND wali_id=$wid AND pengirim='siswa'");
    $messages = $conn->query("SELECT * FROM pesan_wali WHERE wali_id=$wid AND siswa_id=$sid_url ORDER BY id ASC LIMIT 100");
    $last_id  = 0;
    $msgs_arr = [];
    while ($m = $messages->fetch_assoc()) { $msgs_arr[] = $m; $last_id = $m['id']; }
?>
    <div style="display:flex;flex-direction:column;height:calc(100vh - 130px)">
        <!-- Header chat -->
        <div style="display:flex;align-items:center;gap:12px;padding:14px 20px;background:white;border-radius:12px;margin-bottom:14px;box-shadow:0 2px 8px rgba(0,0,0,.06)">
            <a href="portal_wali.php?page=chat" style="color:#64748b;text-decoration:none;font-size:1.1rem"><i class="fas fa-arrow-left"></i></a>
            <div class="avatar" style="width:42px;height:42px;font-size:1rem"><?= strtoupper(substr($detail_siswa['nama'],0,1)) ?></div>
            <div>
                <div style="font-weight:700"><?= htmlspecialchars($detail_siswa['nama']) ?></div>
                <div style="font-size:.75rem;color:#64748b">Kelas <?= $detail_siswa['kelas'] ?> &bull; NIS <?= $detail_siswa['nis'] ?></div>
            </div>
        </div>

        <!-- Pesan -->
        <div id="chatBox" class="chat-messages" style="flex:1">
            <?php if (!$msgs_arr): ?>
            <div style="text-align:center;color:#94a3b8;font-size:.875rem;margin:auto">Belum ada pesan. Mulai percakapan!</div>
            <?php else: foreach($msgs_arr as $m): ?>
            <div class="bubble <?= $m['pengirim']==='wali'?'sent':'recv' ?>">
                <?= nl2br(htmlspecialchars($m['pesan'])) ?>
                <div class="time"><?= date('H:i', strtotime($m['created_at'])) ?></div>
            </div>
            <?php endforeach; endif; ?>
        </div>

        <!-- Input pesan -->
        <div class="chat-input-wrap">
            <textarea id="inputPesan" class="chat-input" rows="2" placeholder="Ketik pesan..." onkeydown="handleKey(event)"></textarea>
            <button class="btn btn-primary" onclick="kirimPesan()" id="btnKirim">
                <i class="fas fa-paper-plane"></i> Kirim
            </button>
        </div>
    </div>

<script>
var lastId = <?= $last_id ?>;
var waliSid = <?= $sid_url ?>;

function scrollBottom() {
    var box = document.getElementById('chatBox');
    if (box) box.scrollTop = box.scrollHeight;
}
scrollBottom();

function handleKey(e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); kirimPesan(); }
}

function kirimPesan() {
    var inp  = document.getElementById('inputPesan');
    var pesan = inp.value.trim();
    if (!pesan) return;
    inp.value = '';
    inp.disabled = true;
    var fd = new FormData();
    fd.append('ajax_kirim_pesan','1');
    fd.append('siswa_id', waliSid);
    fd.append('pesan', pesan);
    fetch('portal_wali.php', {method:'POST',body:fd})
        .then(function(r){return r.json();})
        .then(function(d){
            if (d.ok) {
                var box = document.getElementById('chatBox');
                var div = document.createElement('div');
                div.className = 'bubble sent';
                div.innerHTML = pesan.replace(/\n/g,'<br>') + '<div class="time">' + d.waktu + '</div>';
                box.appendChild(div);
                scrollBottom();
                // Hapus placeholder kosong
                var empty = box.querySelector('[style*="text-align:center"]');
                if (empty) empty.remove();
            }
        })
        .finally(function(){ inp.disabled=false; inp.focus(); });
}

// Poll pesan baru setiap 5 detik
setInterval(function(){
    fetch('portal_wali.php?ajax_pesan=1&sid='+waliSid+'&last_id='+lastId)
        .then(function(r){return r.json();})
        .then(function(d){
            if (d.messages && d.messages.length) {
                var box = document.getElementById('chatBox');
                d.messages.forEach(function(m){
                    if (m.pengirim === 'siswa') {
                        var div = document.createElement('div');
                        div.className = 'bubble recv';
                        div.innerHTML = m.pesan.replace(/\n/g,'<br>') + '<div class="time">' + m.created_at.substr(11,5) + '</div>';
                        box.appendChild(div);
                    }
                    lastId = Math.max(lastId, parseInt(m.id));
                });
                scrollBottom();
            }
        });
}, 5000);
</script>

<?php endif; ?>

<?php // ═══════════════════════════════ REKAP PELANGGARAN (gaya senada Portal BK) ═══════════════════════════════
elseif ($page === 'rekap_pelanggaran'):
    $bulan_p = (int)($_GET['bulan'] ?? date('n'));
    $tahun_p = (int)($_GET['tahun'] ?? date('Y'));
    // Rekap Pelanggaran = Terlambat + Alpa + Pelanggaran Disiplin, poin per-kejadian
    // diambil dari sumber yang SAMA dengan Riwayat Siswa (menu Data Siswa) & Portal BK.
    // Tidak perlu filter kelas — wali kelas otomatis hanya melihat kelasnya sendiri ($anak_ids).
    $agg_p = hitung_rekap_pelanggaran_wali($conn, $bulan_p, $tahun_p, $anak_ids);
?>
<div class="card no-print">
    <div class="card-body">
        <form method="GET" class="filter-row">
            <input type="hidden" name="page" value="rekap_pelanggaran">
            <div>
                <label>Bulan</label>
                <select name="bulan">
                    <?php for ($m=1;$m<=12;$m++): ?>
                    <option value="<?= $m ?>" <?= $bulan_p==$m?'selected':'' ?>><?= $nama_bulan[$m] ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label>Tahun</label>
                <input type="number" name="tahun" value="<?= $tahun_p ?>" style="width:100px">
            </div>
            <div><label>&nbsp;</label><button type="submit" class="btn-filter"><i class="fas fa-search"></i> Tampilkan</button></div>
            <div><label>&nbsp;</label>
                <a class="btn-green" href="portal_wali.php?page=rekap_pelanggaran&export=1&bulan=<?= $bulan_p ?>&tahun=<?= $tahun_p ?>">
                    <i class="fas fa-file-excel"></i> Export Excel
                </a>
            </div>
            <div><label>&nbsp;</label>
                <button type="button" class="btn-filter" style="background:#475569" onclick="window.print()">
                    <i class="fas fa-print"></i> Cetak
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="fas fa-list-ol" style="color:#4f46e5"></i> Rekap Pelanggaran Siswa
        <span style="margin-left:auto;font-size:.75rem;font-weight:400;color:#64748b"><?= $nama_bulan[$bulan_p] ?> <?= $tahun_p ?> — Kelas <?= htmlspecialchars($kelas_wali_login ?: '-') ?></span>
    </div>
    <div class="tbl-wrap">
        <table>
            <thead><tr>
                <th style="width:35px">#</th><th class="th-left">NIS</th><th class="th-left">NAMA</th><th>KELAS</th>
                <th class="th-left">PELANGGARAN</th><th style="width:70px">POINT</th><th style="width:90px">JUMLAH</th><th style="width:80px">TOTAL</th>
            </tr></thead>
            <tbody>
            <?php if (!$agg_p): ?>
            <tr><td colspan="8" style="text-align:center;padding:40px;color:#94a3b8">
                <i class="fas fa-circle-check" style="font-size:1.6rem;display:block;margin-bottom:8px;color:#16a34a"></i>
                Tidak ada pelanggaran
            </td></tr>
            <?php else: $no=0; foreach ($agg_p as $row): $no++; ?>
            <tr>
                <td style="text-align:center;vertical-align:middle"><?= $no ?></td>
                <td style="font-family:monospace;vertical-align:middle"><?= htmlspecialchars($row['nis']) ?></td>
                <td style="font-weight:600;vertical-align:middle"><?= htmlspecialchars($row['nama']) ?></td>
                <td style="text-align:center;vertical-align:middle"><?= htmlspecialchars($row['kelas']) ?></td>
                <td style="padding:6px 10px">
                    <?php if (!$row['items']): ?>
                    <span style="color:#94a3b8;font-style:italic">Tidak ada pelanggaran</span>
                    <?php else: foreach ($row['items'] as $it): ?>
                    <div style="padding:4px 0;border-bottom:1px dashed #f1f5f9"><?= htmlspecialchars($it['label']) ?></div>
                    <?php endforeach; endif; ?>
                </td>
                <td style="text-align:center;padding:6px 4px">
                    <?php foreach ($row['items'] as $it): ?>
                    <div style="padding:4px 0;border-bottom:1px dashed #f1f5f9"><?= $it['point'] ?></div>
                    <?php endforeach; ?>
                </td>
                <td style="text-align:center;padding:6px 4px">
                    <?php foreach ($row['items'] as $it): ?>
                    <div style="padding:4px 0;border-bottom:1px dashed #f1f5f9"><?= $it['jumlah'] ?></div>
                    <?php endforeach; ?>
                </td>
                <td style="text-align:center;vertical-align:middle;font-weight:800;color:#4f46e5;font-size:1rem"><?= $row['total'] ?></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php // ═══════════════════════════════ KUNJUNGAN RUMAH SISWA (gaya senada Portal BK) ═══════════════════════════════
elseif ($page === 'kunjungan'):
    $list_kw = [];
    if ($anak_ids) {
        $res_kw = $conn->query("SELECT * FROM kunjungan_rumah WHERE siswa_id IN ($anak_ids_str)" . periode_where($conn) . " ORDER BY tanggal_kunjungan DESC, id DESC");
        while ($r=$res_kw->fetch_assoc()) $list_kw[] = $r;
    }
?>
<div class="card">
    <div class="card-header">
        <i class="fas fa-house-user" style="color:#4f46e5"></i> Kunjungan Rumah Siswa
        <div style="margin-left:auto;display:flex;gap:8px">
            <button class="btn-filter" onclick="document.getElementById('modalKunjunganWali').classList.add('show')">
                <i class="fas fa-plus"></i> Tambah Kunjungan
            </button>
        </div>
    </div>
    <div class="tbl-wrap">
        <table>
            <thead><tr>
                <th style="width:35px">No</th><th>Tanggal</th><th class="th-left">Nama Siswa</th><th>Kelas</th>
                <th class="th-left">Orang Tua</th><th>Penyelesaian</th><th class="th-left">Keterangan</th><th>Foto</th><th>Aksi</th>
            </tr></thead>
            <tbody>
            <?php if (!$list_kw): ?>
            <tr><td colspan="9" style="text-align:center;padding:30px;color:#94a3b8">Belum ada data kunjungan</td></tr>
            <?php else: $no=0; foreach ($list_kw as $r): $no++;
                $selcls = ['Belum Ditindaklanjuti'=>'b-belum','Dalam Proses'=>'b-proses','Selesai'=>'b-selesai'];
            ?>
            <tr>
                <td style="text-align:center"><?= $no ?></td>
                <td style="text-align:center;white-space:nowrap"><?= $r['tanggal_kunjungan'] ?></td>
                <td style="font-weight:600"><?= htmlspecialchars($r['nama_siswa']) ?></td>
                <td style="text-align:center"><?= htmlspecialchars($r['kelas']) ?></td>
                <td><?= htmlspecialchars($r['nama_ortu'] ?: '-') ?></td>
                <td style="text-align:center"><span class="badge <?= $selcls[$r['penyelesaian']] ?? '' ?>"><?= $r['penyelesaian'] ?></span></td>
                <td><?= htmlspecialchars($r['keterangan'] ?: '-') ?></td>
                <td style="text-align:center">
                    <?php if ($r['foto'] && file_exists(__DIR__.'/uploads/kunjungan/'.$r['foto'])): ?>
                    <a href="<?= BASE_URL ?>uploads/kunjungan/<?= $r['foto'] ?>" target="_blank"><i class="fas fa-image" style="color:#4f46e5"></i></a>
                    <?php else: ?><span style="color:#cbd5e1">—</span><?php endif; ?>
                </td>
                <td style="text-align:center">
                    <a href="portal_wali.php?page=kunjungan&hapus_kunjungan_wali=<?= $r['id'] ?>" onclick="return confirm('Hapus data kunjungan ini?')" class="btn-red"><i class="fas fa-trash"></i></a>
                </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Kunjungan (Wali Kelas) -->
<div class="modal-overlay" id="modalKunjunganWali">
    <div class="modal-box">
        <div class="modal-head"><i class="fas fa-house-user"></i> Kunjungan Rumah Siswa <button class="x" onclick="document.getElementById('modalKunjunganWali').classList.remove('show')">&times;</button></div>
        <form method="POST" enctype="multipart/form-data">
        <div class="modal-body">
            <label>Siswa</label>
            <select name="siswa_id" required>
                <option value="">-- Pilih Siswa --</option>
                <?php foreach ($anak_list as $s): ?>
                <option value="<?= $s['id'] ?>">[<?= htmlspecialchars($s['kelas']) ?>] <?= htmlspecialchars($s['nama']) ?></option>
                <?php endforeach; ?>
            </select>

            <label>Tanggal Kunjungan</label>
            <input type="date" name="tanggal_kunjungan" value="<?= $today ?>" required>

            <label>Nama Orang Tua / Wali</label>
            <input type="text" name="nama_ortu" placeholder="Nama orang tua / wali siswa...">

            <label>Kasus</label>
            <textarea name="kasus" rows="3" placeholder="Uraikan kasus / permasalahan siswa..."></textarea>

            <label>Penyelesaian</label>
            <select name="penyelesaian">
                <option>Belum Ditindaklanjuti</option>
                <option>Dalam Proses</option>
                <option>Selesai</option>
            </select>

            <label>Keterangan <span style="font-size:.72rem;font-weight:400;color:#64748b">(opsional)</span></label>
            <input type="text" name="keterangan" placeholder="Keterangan tambahan...">

            <label>Foto Bukti Kunjungan</label>
            <input type="file" name="foto" accept="image/*">
        </div>
        <div class="modal-foot">
            <button type="button" class="btn-soft" onclick="document.getElementById('modalKunjunganWali').classList.remove('show')">Batal</button>
            <button type="submit" name="tambah_kunjungan_wali" value="1" class="btn-filter"><i class="fas fa-save"></i> Simpan</button>
        </div>
        </form>
    </div>
</div>
<script>
document.querySelectorAll('.modal-overlay').forEach(function(ov){
    ov.addEventListener('click', function(e){ if (e.target === ov) ov.classList.remove('show'); });
});
</script>

<?php endif; ?>

<?php
// ============================================================
// === PAGE: LAPORAN REKAP HARIAN (Wali Kelas)             ===
// ============================================================
if ($page === 'laporan_rekap'):
    $lr_nb = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $lr_nh = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];

    $lr_tgl = (int)($_GET['lr_tgl'] ?? date('j'));
    $lr_bln = (int)($_GET['lr_bln'] ?? date('n'));
    $lr_thn = (int)($_GET['lr_thn'] ?? date('Y'));
    $lr_max = cal_days_in_month(CAL_GREGORIAN, $lr_bln, $lr_thn);
    if ($lr_tgl < 1 || $lr_tgl > $lr_max) $lr_tgl = 1;
    $lr_date      = sprintf('%04d-%02d-%02d', $lr_thn, $lr_bln, $lr_tgl);
    $lr_hari_nama = $lr_nh[date('w', strtotime($lr_date))];

    // Wali hanya melihat kelas sendiri
    $lr_kelas_wali = $wali['kelas_wali'] ?? '';

    // Kelas yang bisa dilihat wali (kelasnya sendiri + semua kelas jika kelas_wali kosong)
    $lr_kelas_list = [];
    if ($lr_kelas_wali) {
        $lr_kelas_list = [$lr_kelas_wali];
    } else {
        $kq = $conn->query("SELECT DISTINCT kelas FROM siswa ORDER BY kelas");
        while ($kr = $kq->fetch_assoc()) $lr_kelas_list[] = $kr['kelas'];
    }

    $lr_rekap_kelas = [];
    $lr_grand = ['siswa'=>0,'Hadir'=>0,'Terlambat'=>0,'Alpa'=>0,'Sakit'=>0,'Izin'=>0,'Bolos'=>0];
    foreach ($lr_kelas_list as $k) {
        $k_esc = $conn->real_escape_string($k);
        $total = (int)$conn->query("SELECT COUNT(*) c FROM siswa WHERE kelas='$k_esc'")->fetch_assoc()['c'];
        $sq    = $conn->query("SELECT status, COUNT(*) total FROM absensi WHERE tanggal='$lr_date' AND kelas='$k_esc'" . periode_where($conn) . " GROUP BY status");
        $s2    = ['Hadir'=>0,'Terlambat'=>0,'Alpa'=>0,'Sakit'=>0,'Izin'=>0,'Bolos'=>0];
        while ($r = $sq->fetch_assoc()) if (isset($s2[$r['status']])) $s2[$r['status']] = (int)$r['total'];
        $pct = $total > 0 ? round(($s2['Hadir']+$s2['Terlambat'])/$total*100,1) : 0;
        $lr_rekap_kelas[] = ['kelas'=>$k,'siswa'=>$total,'Hadir'=>$s2['Hadir'],'Terlambat'=>$s2['Terlambat'],
            'Alpa'=>$s2['Alpa'],'Sakit'=>$s2['Sakit'],'Izin'=>$s2['Izin'],'Bolos'=>$s2['Bolos'],'pct'=>$pct];
        $lr_grand['siswa'] += $total;
        foreach (['Hadir','Terlambat','Alpa','Sakit','Izin','Bolos'] as $st) $lr_grand[$st] += $s2[$st];
    }
    $lr_grand_pct = $lr_grand['siswa'] > 0 ? round(($lr_grand['Hadir']+$lr_grand['Terlambat'])/$lr_grand['siswa']*100,1) : 0;

    $lr_filter_status = isset($_GET['lr_status']) ? $conn->real_escape_string(htmlspecialchars(strip_tags(trim($_GET['lr_status'])))) : '';
    $lr_valid_status  = ['Alpa','Sakit','Izin','Bolos','Terlambat','Hadir'];
    if (!in_array($lr_filter_status, $lr_valid_status)) $lr_filter_status = '';
    $lr_kelas_esc = $conn->real_escape_string($lr_kelas_wali);
    $lr_kelas_sql = $lr_kelas_wali ? "AND a.kelas='$lr_kelas_esc'" : '';
    $lr_status_sql = $lr_filter_status ? "AND a.status='$lr_filter_status'" : "AND a.status IN ('Alpa','Sakit','Izin','Bolos','Terlambat','Hadir')";
    $lr_semua_q   = $conn->query("SELECT a.nis, a.nama, a.kelas, a.status, a.keterangan FROM absensi a WHERE a.tanggal='$lr_date' $lr_kelas_sql $lr_status_sql" . periode_where($conn, 'a.') . " ORDER BY a.status, a.kelas, a.nama");
    $lr_rekap_semua = $lr_semua_q ? $lr_semua_q->fetch_all(MYSQLI_ASSOC) : [];

    // Export Excel
    if (isset($_GET['lr_export_excel'])) {
        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        header("Content-Disposition: attachment; filename=\"LaporanRekap_{$lr_date}".($lr_kelas_wali?"_Kelas{$lr_kelas_wali}":"")."".($lr_filter_status?"_{$lr_filter_status}":"_Semua").".xls\"");
        header("Cache-Control: max-age=0");
        echo "\xEF\xBB\xBF<table border='1'>";
        echo "<tr><th colspan='6' style='text-align:center;font-size:14pt;font-weight:bold'>".htmlspecialchars($pengaturan['nama_sekolah'])."</th></tr>";
        echo "<tr><th colspan='6' style='text-align:center'>LAPORAN REKAP HARIAN — ".strtoupper($lr_hari_nama).", {$lr_tgl} ".$lr_nb[$lr_bln]." {$lr_thn}".($lr_kelas_wali?" — KELAS: $lr_kelas_wali":"")."</th></tr>";
        echo "<tr><th>NO</th><th>NIS</th><th>NAMA SISWA</th><th>KELAS</th><th>STATUS</th><th>KETERANGAN</th></tr>";
        foreach ($lr_rekap_semua as $i => $row)
            echo "<tr><td>".($i+1)."</td><td>{$row['nis']}</td><td>{$row['nama']}</td><td>{$row['kelas']}</td><td>{$row['status']}</td><td>".($row['keterangan']??'')."</td></tr>";
        echo "</table>";
        exit;
    }
?>
<style>
.lr-stats-w{display:grid;grid-template-columns:repeat(auto-fit,minmax(100px,1fr));gap:10px;margin-bottom:20px}
.lr-stat-w{background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:12px;text-align:center}
.lr-stat-w .num{font-size:1.6rem;font-weight:900;line-height:1}
.lr-stat-w .lbl{font-size:.67rem;color:#94a3b8;margin-top:4px;font-weight:600;text-transform:uppercase;letter-spacing:.5px}
.lr-filter-w{background:#fffbeb;border:1px solid #fde68a;border-radius:12px;padding:14px;margin-bottom:18px}
.lr-filter-w label{font-size:.72rem;font-weight:700;color:#92400e;text-transform:uppercase;letter-spacing:.4px;display:block;margin-bottom:4px}
.lr-filter-w select,.lr-filter-w button{padding:8px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:.84rem;background:white;cursor:pointer}
.lr-filter-w .btn-go{background:linear-gradient(135deg,#d97706,#b45309);color:white;border:none;font-weight:700;display:inline-flex;align-items:center;gap:5px}
.lr-filter-w .btn-excel{background:linear-gradient(135deg,#16a34a,#15803d);color:white;border:none;font-weight:700;display:inline-flex;align-items:center;gap:5px}
.lr-tbl{width:100%;border-collapse:collapse;font-size:.82rem}
.lr-tbl thead th{background:#1e3a8a;color:white;padding:10px 12px;text-align:center}
.lr-tbl thead th:first-child,.lr-tbl thead th:nth-child(3){text-align:left}
.lr-tbl tbody tr:hover{background:#eff6ff}
.lr-tbl tbody td{padding:9px 12px;border-top:1px solid #f1f5f9;text-align:center;vertical-align:middle}
.lr-tbl tbody td:first-child,.lr-tbl tbody td:nth-child(3){text-align:left}
.lr-tbl tfoot td{background:#1e3a8a;color:white;padding:10px 12px;font-weight:800}
.lr-badge{display:inline-block;padding:3px 10px;border-radius:6px;font-weight:700;font-size:.78rem}
.lr-hadir{background:#dcfce7;color:#15803d}.lr-terlambat{background:#fef3c7;color:#b45309}
.lr-alpa{background:#fee2e2;color:#dc2626}.lr-sakit{background:#dbeafe;color:#1d4ed8}
.lr-izin{background:#ede9fe;color:#6d28d9}.lr-bolos{background:#ffedd5;color:#9a3412}
.lr-pbar{width:70px;height:6px;background:#e2e8f0;border-radius:3px;display:inline-block;vertical-align:middle;margin-right:5px;overflow:hidden}
.lr-pfill{height:100%;border-radius:3px;background:#15803d}
@media print{.lr-no-print-w{display:none!important}.sidebar,.top-bar{display:none!important}.main-content{margin-left:0!important}}
@media print{.no-print,.sidebar,.topbar,.modal-overlay{display:none!important}.main{margin-left:0!important}.content{padding:0!important}}
</style>

<div style="margin-bottom:18px">
    <div style="font-size:1.1rem;font-weight:800;color:#1e40af;display:flex;align-items:center;gap:8px;margin-bottom:4px">
        <i class="fas fa-clipboard-list" style="color:#f59e0b"></i>
        Laporan Rekap Harian
    </div>
    <div style="font-size:.85rem;color:#64748b">
        <?= $lr_hari_nama ?>, <?= $lr_tgl ?> <?= $lr_nb[$lr_bln] ?> <?= $lr_thn ?>
        <?php if($lr_kelas_wali): ?> &nbsp;|&nbsp; <strong>Kelas <?= htmlspecialchars($lr_kelas_wali) ?></strong><?php endif; ?>
    </div>
</div>

<!-- Filter -->
<div class="lr-filter-w lr-no-print-w">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
        <input type="hidden" name="page" value="laporan_rekap">
        <div>
            <label><i class="fas fa-calendar-day"></i> Tanggal</label>
            <select name="lr_tgl">
                <?php for($d=1;$d<=31;$d++): ?>
                <option value="<?= $d ?>" <?= $lr_tgl==$d?'selected':'' ?>><?= str_pad($d,2,'0',STR_PAD_LEFT) ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div>
            <label><i class="fas fa-calendar-alt"></i> Bulan</label>
            <select name="lr_bln">
                <?php for($m=1;$m<=12;$m++): ?>
                <option value="<?= $m ?>" <?= $lr_bln==$m?'selected':'' ?>><?= $lr_nb[$m] ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div>
            <label><i class="fas fa-calendar"></i> Tahun</label>
            <select name="lr_thn">
                <?php for($y=date('Y')+1;$y>=date('Y')-3;$y--): ?>
                <option value="<?= $y ?>" <?= $lr_thn==$y?'selected':'' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div>
            <label><i class="fas fa-filter"></i> Status</label>
            <select name="lr_status">
                <option value="">Semua Status</option>
                <?php foreach(['Hadir','Terlambat','Alpa','Sakit','Izin','Bolos'] as $opt): ?>
                <option value="<?= $opt ?>" <?= $lr_filter_status===$opt?'selected':'' ?>><?= $opt ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="display:flex;gap:8px">
            <button type="submit" class="btn-go"><i class="fas fa-search"></i> Tampilkan</button>
            <button type="submit" name="lr_export_excel" value="1" class="btn-excel"><i class="fas fa-file-excel"></i> Excel</button>
            <button type="button" onclick="window.print()" style="padding:8px 12px;background:#1d4ed8;color:white;border:none;border-radius:8px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:5px;font-size:.84rem">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </form>
</div>

<!-- Stats Ringkasan -->
<div class="lr-stats-w">
    <div class="lr-stat-w"><div class="num" style="color:#3b82f6"><?= $lr_grand['siswa'] ?></div><div class="lbl">Total Siswa</div></div>
    <div class="lr-stat-w"><div class="num" style="color:#16a34a"><?= $lr_grand['Hadir'] ?></div><div class="lbl">Hadir</div></div>
    <div class="lr-stat-w"><div class="num" style="color:#b45309"><?= $lr_grand['Terlambat'] ?></div><div class="lbl">Terlambat</div></div>
    <div class="lr-stat-w"><div class="num" style="color:#dc2626"><?= $lr_grand['Alpa'] ?></div><div class="lbl">Alpa</div></div>
    <div class="lr-stat-w"><div class="num" style="color:#1d4ed8"><?= $lr_grand['Sakit'] ?></div><div class="lbl">Sakit</div></div>
    <div class="lr-stat-w"><div class="num" style="color:#6d28d9"><?= $lr_grand['Izin'] ?></div><div class="lbl">Izin</div></div>
    <div class="lr-stat-w"><div class="num" style="color:#9a3412"><?= $lr_grand['Bolos'] ?></div><div class="lbl">Bolos</div></div>
    <div class="lr-stat-w"><div class="num" style="color:#16a34a"><?= $lr_grand_pct ?>%</div><div class="lbl">% Hadir</div></div>
</div>

<!-- Tabel Rekap Per Kelas -->
<div style="font-weight:700;font-size:.85rem;color:#1e40af;margin-bottom:8px;display:flex;align-items:center;gap:6px">
    <i class="fas fa-table"></i> Rekapitulasi Per Kelas
</div>
<div style="overflow-x:auto;border-radius:12px;border:1px solid #e2e8f0;margin-bottom:22px">
    <table class="lr-tbl">
        <thead>
            <tr>
                <th style="text-align:left;width:40px">No</th>
                <th style="text-align:left">Kelas</th>
                <th>Siswa</th>
                <th style="background:#15803d">Hadir</th>
                <th style="background:#b45309">Terlambat</th>
                <th style="background:#dc2626">Alpa</th>
                <th style="background:#1d4ed8">Sakit</th>
                <th style="background:#6d28d9">Izin</th>
                <th style="background:#9a3412">Bolos</th>
                <th>% Hadir</th>
            </tr>
        </thead>
        <tbody>
        <?php if(empty($lr_rekap_kelas)): ?>
        <tr><td colspan="10" style="text-align:center;padding:28px;color:#94a3b8">
            <i class="fas fa-inbox" style="font-size:1.5rem;display:block;margin-bottom:8px"></i>Tidak ada data
        </td></tr>
        <?php else: ?>
        <?php foreach($lr_rekap_kelas as $i => $r):
            $pc = $r['pct']>=90?'#16a34a':($r['pct']>=75?'#d97706':'#dc2626');
        ?>
        <tr>
            <td style="color:#64748b;font-weight:600"><?= $i+1 ?></td>
            <td style="font-weight:700"><i class="fas fa-door-open" style="color:#2563eb;font-size:.78rem;margin-right:4px"></i><?= htmlspecialchars($r['kelas']) ?></td>
            <td><?= $r['siswa'] ?></td>
            <td><span class="lr-badge lr-hadir"><?= $r['Hadir'] ?></span></td>
            <td><span class="lr-badge lr-terlambat"><?= $r['Terlambat'] ?></span></td>
            <td><span class="lr-badge lr-alpa"><?= $r['Alpa'] ?></span></td>
            <td><span class="lr-badge lr-sakit"><?= $r['Sakit'] ?></span></td>
            <td><span class="lr-badge lr-izin"><?= $r['Izin'] ?></span></td>
            <td><span class="lr-badge lr-bolos"><?= $r['Bolos'] ?></span></td>
            <td>
                <div class="lr-pbar"><div class="lr-pfill" style="width:<?= min($r['pct'],100) ?>%;background:<?= $pc ?>"></div></div>
                <span style="font-weight:800;font-size:.82rem;color:<?= $pc ?>"><?= $r['pct'] ?>%</span>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" style="text-align:right">TOTAL</td>
                <td><?= $lr_grand['siswa'] ?></td>
                <td><?= $lr_grand['Hadir'] ?></td>
                <td><?= $lr_grand['Terlambat'] ?></td>
                <td><?= $lr_grand['Alpa'] ?></td>
                <td><?= $lr_grand['Sakit'] ?></td>
                <td><?= $lr_grand['Izin'] ?></td>
                <td><?= $lr_grand['Bolos'] ?></td>
                <td style="color:#86efac"><?= $lr_grand_pct ?>%</td>
            </tr>
        </tfoot>
    </table>
</div>

<!-- Tabel Detail Siswa -->
<div style="font-weight:700;font-size:.85rem;color:#1e40af;margin-bottom:8px;display:flex;align-items:center;gap:6px">
    <i class="fas fa-layer-group"></i> Rekap Detail Siswa
    <?php if($lr_filter_status): ?>
    <span style="background:#fef3c7;color:#92400e;padding:2px 8px;border-radius:6px;font-size:.72rem;margin-left:6px">Status: <?= $lr_filter_status ?></span>
    <?php endif; ?>
</div>
<div style="overflow-x:auto;border-radius:12px;border:1px solid #e2e8f0">
    <table class="lr-tbl">
        <thead>
            <tr>
                <th style="text-align:center;width:44px">No</th>
                <th style="text-align:left">NIS</th>
                <th style="text-align:left">Nama Siswa</th>
                <th>Kelas</th>
                <th>Status</th>
                <th style="text-align:left">Keterangan</th>
            </tr>
        </thead>
        <tbody>
        <?php if(empty($lr_rekap_semua)): ?>
        <tr><td colspan="6" style="text-align:center;padding:28px;color:#94a3b8">
            <i class="fas fa-inbox" style="font-size:1.5rem;display:block;margin-bottom:8px"></i>Tidak ada data
        </td></tr>
        <?php else:
        $lr_bg_map = ['Alpa'=>'#fff5f5','Sakit'=>'#eff6ff','Izin'=>'#f5f3ff','Bolos'=>'#fff7ed','Hadir'=>'#f0fdf4','Terlambat'=>'#fffbeb'];
        $lr_bm = ['Hadir'=>'lr-hadir','Terlambat'=>'lr-terlambat','Alpa'=>'lr-alpa','Sakit'=>'lr-sakit','Izin'=>'lr-izin','Bolos'=>'lr-bolos'];
        $lr_prev = '';
        foreach ($lr_rekap_semua as $ri => $rrow):
            if ($rrow['status'] !== $lr_prev && $lr_prev !== ''):?>
        <tr><td colspan="6" style="padding:0;height:4px;background:#f1f5f9"></td></tr>
        <?php endif; $lr_prev = $rrow['status']; ?>
        <tr style="background:<?= $lr_bg_map[$rrow['status']] ?? '#fff' ?>;border-top:1px solid #f8fafc">
            <td style="text-align:center;color:#94a3b8;font-weight:600"><?= $ri+1 ?></td>
            <td style="color:#64748b;font-size:.78rem"><?= htmlspecialchars($rrow['nis']) ?></td>
            <td style="font-weight:700"><?= htmlspecialchars($rrow['nama']) ?></td>
            <td><span style="background:#e2e8f0;color:#334155;padding:2px 8px;border-radius:6px;font-size:.78rem;font-weight:600"><?= htmlspecialchars($rrow['kelas']) ?></span></td>
            <td><span class="lr-badge <?= $lr_bm[$rrow['status']] ?? '' ?>"><?= $rrow['status'] ?></span></td>
            <td style="color:#64748b;font-size:.8rem"><?= htmlspecialchars($rrow['keterangan'] ?? '-') ?></td>
        </tr>
        <?php endforeach; ?>
        <tr style="background:#1e3a8a;color:white;font-weight:800">
            <td colspan="2" style="text-align:right">TOTAL</td>
            <td colspan="4"><?= count($lr_rekap_semua) ?> siswa<?= $lr_filter_status?" — Status: <strong>{$lr_filter_status}</strong>":" — Semua Status" ?></td>
        </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Tanda Tangan (print) -->
<div style="margin-top:40px;display:flex;justify-content:flex-end;padding-right:30px">
    <div style="text-align:center;min-width:200px">
        <div><?= htmlspecialchars($pengaturan['nama_sekolah'] ?? '') ?>, <?= $lr_tgl.' '.$lr_nb[$lr_bln].' '.$lr_thn ?></div>
        <div style="margin-top:4px">Wali Kelas<?= $lr_kelas_wali?" {$lr_kelas_wali}":"" ?>,</div>
        <div style="margin-top:65px;font-weight:700;text-decoration:underline"><?= htmlspecialchars($wali['nama']) ?></div>
    </div>
</div>

<?php endif; // end laporan_rekap ?>
</div><!-- /main -->

<script>
// Jam realtime
function updateJam() {
    var now = new Date();
    var h = String(now.getHours()).padStart(2,'0');
    var m = String(now.getMinutes()).padStart(2,'0');
    var s = String(now.getSeconds()).padStart(2,'0');
    var el = document.getElementById('jam');
    if (el) el.textContent = h+':'+m+':'+s;
}
updateJam(); setInterval(updateJam, 1000);
</script>
</body>
</html>
