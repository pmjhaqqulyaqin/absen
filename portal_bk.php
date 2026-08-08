<?php
require_once 'includes/config.php';
require_once 'includes/xlsx_writer.php';

// Auth
if (!isset($_SESSION['bk_id'])) { header('Location: '.BASE_URL.'portal_bk_login.php'); exit; }
if (isset($_GET['logout']))     { header('Location: '.BASE_URL.'portal_bk_logout.php'); exit; }

$bk_id   = (int)$_SESSION['bk_id'];
$bk_nama = $_SESSION['bk_nama'] ?? 'Guru BK';

$pengaturan = get_pengaturan();

// Data pengaturan untuk print header (dipakai semua dokumen cetak)
$nm_sek = htmlspecialchars($pengaturan['nama_sekolah'] ?? '');
$hari_cetak_arr = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
$nama_bulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$tgl_cetak = $hari_cetak_arr[date('w')].', '.date('j').' '.$nama_bulan[(int)date('n')].' '.date('Y');

// ════════════════════════════════════════════════════════════════════
// AUTO-MIGRASI TABEL BK (aman dijalankan berkali-kali)
// ════════════════════════════════════════════════════════════════════
$conn->query("CREATE TABLE IF NOT EXISTS catatan_bk (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    siswa_id       INT(11)      NOT NULL,
    nis            VARCHAR(30)  DEFAULT '',
    nama_siswa     VARCHAR(100) DEFAULT '',
    kelas          VARCHAR(30)  DEFAULT '',
    guru_bk_id     INT(11)      DEFAULT 0,
    nama_guru      VARCHAR(100) DEFAULT '',
    tipe           ENUM('Informasi','Peringatan','Urgent','Apresiasi') DEFAULT 'Informasi',
    judul          VARCHAR(200) DEFAULT '',
    isi            TEXT         NOT NULL,
    balasan        TEXT         DEFAULT NULL,
    dibalas_at     DATETIME     DEFAULT NULL,
    balasan_dibaca TINYINT(1)   DEFAULT 1,
    created_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_siswa (siswa_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS kunjungan_rumah (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    siswa_id          INT(11)      NOT NULL,
    nis               VARCHAR(30)  DEFAULT '',
    nama_siswa        VARCHAR(100) DEFAULT '',
    kelas             VARCHAR(30)  DEFAULT '',
    guru_bk_id        INT(11)      DEFAULT 0,
    nama_guru         VARCHAR(100) DEFAULT '',
    tanggal_kunjungan DATE         NOT NULL,
    nama_ortu         VARCHAR(100) DEFAULT '',
    kasus             TEXT,
    penyelesaian      ENUM('Belum Ditindaklanjuti','Dalam Proses','Selesai') DEFAULT 'Belum Ditindaklanjuti',
    keterangan        VARCHAR(255) DEFAULT '',
    foto              VARCHAR(255) DEFAULT '',
    created_at        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_siswa (siswa_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS buku_kejadian (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    siswa_id         INT(11)      NOT NULL,
    nis              VARCHAR(30)  DEFAULT '',
    nama_siswa       VARCHAR(100) DEFAULT '',
    kelas            VARCHAR(30)  DEFAULT '',
    guru_bk_id       INT(11)      DEFAULT 0,
    nama_guru        VARCHAR(100) DEFAULT '',
    tanggal          DATE         NOT NULL,
    hari             VARCHAR(20)  DEFAULT '',
    uraian_kejadian  TEXT,
    poin             INT(11)      DEFAULT 0,
    tanggapan_siswa  TEXT,
    arahan_guru_wali TEXT,
    tindak_lanjut    TEXT,
    ttd              VARCHAR(255) DEFAULT '',
    created_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_siswa (siswa_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Jaga-jaga: tabel pelanggaran mungkin belum ada jika menu Disiplin belum pernah dibuka
$conn->query("CREATE TABLE IF NOT EXISTS pelanggaran_jenis (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nama        VARCHAR(100) NOT NULL,
    kode        VARCHAR(10)  DEFAULT '',
    keterangan  VARCHAR(255) DEFAULT '',
    aktif       TINYINT(1)   DEFAULT 1,
    urutan      INT          DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$conn->query("CREATE TABLE IF NOT EXISTS pelanggaran (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    siswa_id    INT          DEFAULT 0,
    nis         VARCHAR(30)  DEFAULT '',
    nama        VARCHAR(100) DEFAULT '',
    kelas       VARCHAR(30)  DEFAULT '',
    tanggal     DATE         NOT NULL,
    jenis_id    INT          DEFAULT 0,
    jenis_nama  VARCHAR(100) DEFAULT '',
    keterangan  VARCHAR(255) DEFAULT '',
    input_oleh  VARCHAR(100) DEFAULT 'Disiplin',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
// Migrasi kolom poin (dipakai untuk total poin siswa di menu Data Siswa)
foreach ([['pelanggaran_jenis','poin','INT DEFAULT 3'],['pelanggaran','poin','INT DEFAULT 3']] as [$tbl,$col,$def]) {
    $c = $conn->query("SHOW COLUMNS FROM `$tbl` LIKE '$col'");
    if ($c && $c->num_rows === 0) $conn->query("ALTER TABLE `$tbl` ADD COLUMN `$col` $def");
}
// Direktori upload
foreach (['uploads/kunjungan'] as $d) { if (!is_dir($d)) @mkdir($d, 0755, true); }
// ── Auto-create tabel point_perbaikan (point pengurangan) ─────────────────
$conn->query("CREATE TABLE IF NOT EXISTS point_perbaikan (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    siswa_id      INT(11)      NOT NULL,
    nis           VARCHAR(30)  DEFAULT '',
    nama_siswa    VARCHAR(100) DEFAULT '',
    kelas         VARCHAR(30)  DEFAULT '',
    kategori      ENUM('TERLAMBAT','ALPA','PELANGGARAN','KUNJUNGAN') NOT NULL,
    jumlah        INT(11)      DEFAULT 1,
    keterangan    VARCHAR(255) DEFAULT '',
    tanggal       DATE         NOT NULL,
    guru_bk_id    INT(11)      DEFAULT 0,
    nama_guru     VARCHAR(100) DEFAULT '',
    created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_siswa (siswa_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");



$page = $_GET['page'] ?? 'rekap_pelanggaran';

// Ambil semua kelas
$kelas_list = [];
$kr = $conn->query("SELECT DISTINCT kelas FROM siswa ORDER BY kelas");
while ($r = $kr->fetch_assoc()) $kelas_list[] = $r['kelas'];

// ── Helper: Render kelas pill buttons ─────────────────────────────────────
function render_kelas_pills(array $kelas_list, string $page, string $kelas_active, string $extra_params = ''): void {
    if (empty($kelas_list)) return;
    echo '<div class="kelas-pills-wrap no-print">';
    echo '<div class="kelas-pills-title"><i class="fas fa-th-list"></i> Pilih Kelas</div>';
    echo '<div class="kelas-pills">';
    foreach ($kelas_list as $k) {
        $active = ($kelas_active === $k) ? ' active' : '';
        $href   = 'portal_bk.php?page=' . $page . '&kelas=' . urlencode($k) . $extra_params;
        echo '<a href="' . htmlspecialchars($href) . '" class="kelas-pill' . $active . '">';
        echo '<i class="fas fa-check-circle"></i> ' . htmlspecialchars($k);
        echo ' <span class="pill-status">(Lengkap)</span>';
        echo '</a>';
    }
    echo '</div></div>';
}

// ── Helper: Hitung Rekap Pelanggaran (Terlambat + Alpa + Pelanggaran Disiplin) ─────
// Sumber & rumus poin SAMA PERSIS dengan yang dipakai di Riwayat Siswa (menu Data Siswa):
// - Terlambat & Alpa diambil dari tabel absensi, poin per-kejadian dari pengaturan_poin_absen.
// - Pelanggaran Disiplin diambil dari tabel pelanggaran, poin per-kejadian dari kolom poin masing-masing jenis.
// $siswa_ids: null = tidak dibatasi siswa tertentu (hanya dibatasi kelas jika $kelas_filter diisi).
function hitung_rekap_pelanggaran(mysqli $conn, int $bulan, int $tahun, string $kelas_filter = '', ?array $siswa_ids = null): array {
    $poinCfgRow       = $conn->query("SELECT * FROM pengaturan_poin_absen LIMIT 1")->fetch_assoc();
    $poinAlpaCfg      = (int)($poinCfgRow['poin_alpa'] ?? 5);
    $poinTerlambatCfg = (int)($poinCfgRow['poin_terlambat'] ?? 2);

    $condKelas = $kelas_filter !== '' ? " AND kelas='".$conn->real_escape_string($kelas_filter)."'" : '';
    $condSiswa = '';
    if (is_array($siswa_ids)) {
        $ids = $siswa_ids ? implode(',', array_map('intval', $siswa_ids)) : '0';
        $condSiswa = " AND siswa_id IN ($ids)";
    }

    $agg = [];
    $tambah = function($sid, $nis, $nama, $kelas, $label, $point, $jumlah) use (&$agg) {
        $sid = (int)$sid;
        if (!isset($agg[$sid])) $agg[$sid] = ['nis'=>$nis,'nama'=>$nama,'kelas'=>$kelas,'items'=>[],'total'=>0];
        $agg[$sid]['items'][] = ['label'=>$label,'point'=>(int)$point,'jumlah'=>(int)$jumlah];
        $agg[$sid]['total'] += (int)$point * (int)$jumlah;
    };

    // 1) Terlambat
    $res = $conn->query("SELECT siswa_id,nis,nama,kelas,COUNT(*) jumlah FROM absensi
        WHERE status='Terlambat' AND MONTH(tanggal)=$bulan AND YEAR(tanggal)=$tahun $condKelas $condSiswa" . periode_where($conn) . " GROUP BY siswa_id");
    while ($r = $res->fetch_assoc()) $tambah($r['siswa_id'],$r['nis'],$r['nama'],$r['kelas'],'Terlambat',$poinTerlambatCfg,$r['jumlah']);

    // 2) Alpa
    $res = $conn->query("SELECT siswa_id,nis,nama,kelas,COUNT(*) jumlah FROM absensi
        WHERE status='Alpa' AND MONTH(tanggal)=$bulan AND YEAR(tanggal)=$tahun $condKelas $condSiswa" . periode_where($conn) . " GROUP BY siswa_id");
    while ($r = $res->fetch_assoc()) $tambah($r['siswa_id'],$r['nis'],$r['nama'],$r['kelas'],'Alpa',$poinAlpaCfg,$r['jumlah']);

    // 3) Pelanggaran Disiplin (dikelompokkan per jenis pelanggaran, tiap jenis punya poinnya sendiri)
    $res = $conn->query("SELECT siswa_id,nis,nama,kelas,jenis_nama,COALESCE(poin,3) poin,COUNT(*) jumlah FROM pelanggaran
        WHERE MONTH(tanggal)=$bulan AND YEAR(tanggal)=$tahun $condKelas $condSiswa" . periode_where($conn) . " GROUP BY siswa_id,jenis_nama,poin");
    while ($r = $res->fetch_assoc()) $tambah($r['siswa_id'],$r['nis'],$r['nama'],$r['kelas'], $r['jenis_nama'] ?: 'Pelanggaran', $r['poin'], $r['jumlah']);

    // 4) Tambahkan juga siswa yang TIDAK memiliki pelanggaran (poin nol) agar tetap tampil di rekap,
    //    bukan hanya siswa yang melanggar saja. Filter kelas / siswa_ids yang sama tetap dipakai.
    $condKelasS = $kelas_filter !== '' ? " WHERE kelas='".$conn->real_escape_string($kelas_filter)."'" : '';
    if (is_array($siswa_ids)) {
        $idsS = $siswa_ids ? implode(',', array_map('intval', $siswa_ids)) : '0';
        $condKelasS = $condKelasS === '' ? " WHERE id IN ($idsS)" : $condKelasS." AND id IN ($idsS)";
    }
    $resAll = $conn->query("SELECT id,nis,nama,kelas FROM siswa$condKelasS");
    while ($r = $resAll->fetch_assoc()) {
        $sid = (int)$r['id'];
        if (!isset($agg[$sid])) $agg[$sid] = ['nis'=>$r['nis'],'nama'=>$r['nama'],'kelas'=>$r['kelas'],'items'=>[],'total'=>0];
    }

    uasort($agg, fn($a,$b) => strcmp($a['nama'], $b['nama']));
    return $agg;
}

$nama_bulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$today = date('Y-m-d');

// Foto & data BK — ambil dari tabel sesuai sumber login
$bk_source = $_SESSION['bk_source'] ?? 'guru_bk';
if ($bk_source === 'wali') {
    $bk_row = $conn->query("SELECT * FROM wali WHERE id=$bk_id")->fetch_assoc();
    $foto_bk = $bk_row['foto'] ?? '';
    $foto_dir = 'uploads/foto_wali/';
} else {
    $r = $conn->query("SELECT * FROM guru_bk WHERE id=$bk_id");
    $bk_row  = $r ? $r->fetch_assoc() : [];
    $foto_bk = $bk_row['foto'] ?? '';
    $foto_dir = 'uploads/foto_bk/';
}

// ── Status badge helper ────────────────────────────────────────────────────
$status_kode = [
    'Hadir'     => ['H','#16a34a','#dcfce7'],
    'Terlambat' => ['T','#d97706','#fef3c7'],
    'Alpa'      => ['A','#dc2626','#fee2e2'],
    'Sakit'     => ['S','#2563eb','#dbeafe'],
    'Izin'      => ['I','#7c3aed','#ede9fe'],
    'Bolos'     => ['B','#9a3412','#ffedd5'],
];

// ════════════════════════════════════════════════════════════════════
// AJAX: Riwayat / profil siswa (dipakai modal Data Siswa)
// ════════════════════════════════════════════════════════════════════
if (isset($_GET['ajax']) && $_GET['ajax'] === 'riwayat') {
    header('Content-Type: application/json');
    $sid = (int)($_GET['id'] ?? 0);
    $sw  = $conn->query("SELECT * FROM siswa WHERE id=$sid")->fetch_assoc();
    if (!$sw) { echo json_encode(['ok'=>false,'msg'=>'Siswa tidak ditemukan']); exit; }

    $abs = $conn->query("SELECT status,COUNT(*) c FROM absensi WHERE siswa_id=$sid" . periode_where($conn) . " GROUP BY status")->fetch_all(MYSQLI_ASSOC);
    $absMap = array_column($abs,'c','status');
    $jmlPel = (int)$conn->query("SELECT COUNT(*) c FROM pelanggaran WHERE siswa_id=$sid" . periode_where($conn))->fetch_assoc()['c'];
    $jmlKnj = (int)$conn->query("SELECT COUNT(*) c FROM kunjungan_rumah WHERE siswa_id=$sid" . periode_where($conn))->fetch_assoc()['c'];
    $poinPel = (int)($conn->query("SELECT COALESCE(SUM(COALESCE(poin,3)),0) s FROM pelanggaran WHERE siswa_id=$sid" . periode_where($conn))->fetch_assoc()['s'] ?? 0);
    $poinBk  = (int)($conn->query("SELECT COALESCE(SUM(poin),0) s FROM buku_kejadian WHERE siswa_id=$sid" . periode_where($conn))->fetch_assoc()['s'] ?? 0);

    // Poin per-kejadian untuk Alpa & Terlambat — diambil dari pengaturan di menu
    // Kelola Pelanggaran → Penentuan Point Alpa & Terlambat (tabel pengaturan_poin_absen).
    $conn->query("CREATE TABLE IF NOT EXISTS pengaturan_poin_absen (
        id             INT AUTO_INCREMENT PRIMARY KEY,
        poin_alpa      INT(11) DEFAULT 5,
        poin_terlambat INT(11) DEFAULT 2,
        keterangan     VARCHAR(255) DEFAULT ''
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $poinCfgRow = $conn->query("SELECT * FROM pengaturan_poin_absen LIMIT 1")->fetch_assoc();
    $poinAlpaCfg      = (int)($poinCfgRow['poin_alpa'] ?? 5);
    $poinTerlambatCfg = (int)($poinCfgRow['poin_terlambat'] ?? 2);

    // Point perbaikan per kategori
    $perbaikan = ['TERLAMBAT'=>0,'ALPA'=>0,'PELANGGARAN'=>0,'KUNJUNGAN'=>0];
    $resPerb = $conn->query("SELECT kategori, SUM(jumlah) total FROM point_perbaikan WHERE siswa_id=$sid" . periode_where($conn) . " GROUP BY kategori");
    if ($resPerb) { while ($r=$resPerb->fetch_assoc()) $perbaikan[$r['kategori']] = (int)$r['total']; }
    $perbaikanLog = [];
    $resLog = $conn->query("SELECT * FROM point_perbaikan WHERE siswa_id=$sid" . periode_where($conn) . " ORDER BY tanggal DESC, id DESC LIMIT 50");
    if ($resLog) { while ($r=$resLog->fetch_assoc()) $perbaikanLog[] = $r; }

    $timeline = [];
    // Catatan BK, Kunjungan Rumah & Buku Kejadian: query dipertahankan (tidak dihapus)
    // tapi TIDAK dimasukkan ke $timeline — sesuai permintaan: Riwayat gabungan
    // hanya menampilkan Pelanggaran Disiplin, Terlambat, dan Alpa saja.
    $q1 = $conn->query("SELECT created_at tgl, 'Catatan BK' tipe, 'Catatan BK' judul, isi ket FROM catatan_bk WHERE siswa_id=$sid" . periode_where($conn) . "");
    while ($x=$q1->fetch_assoc()) { /* tidak ditampilkan di Riwayat gabungan */ }
    $q2 = $conn->query("SELECT tanggal_kunjungan tgl, 'Kunjungan Rumah' tipe, UPPER(COALESCE(keterangan,kasus,'')) judul, penyelesaian ket FROM kunjungan_rumah WHERE siswa_id=$sid" . periode_where($conn) . "");
    while ($x=$q2->fetch_assoc()) { /* tidak ditampilkan di Riwayat gabungan */ }
    $q3 = $conn->query("SELECT id, tanggal tgl, 'Pelanggaran Disiplin' tipe, UPPER(jenis_nama) judul, CONCAT('+',COALESCE(poin,3),' Poin') ket, 'pelanggaran' src FROM pelanggaran WHERE siswa_id=$sid" . periode_where($conn) . "");
    while ($x=$q3->fetch_assoc()) $timeline[] = $x;
    $q4 = $conn->query("SELECT tanggal tgl, 'Buku Kejadian' tipe, UPPER(uraian_kejadian) judul, CONCAT('+',COALESCE(poin,0),' Poin',IF(tanggapan_siswa IS NOT NULL AND tanggapan_siswa<>'',CONCAT(' — ',tanggapan_siswa),'')) ket FROM buku_kejadian WHERE siswa_id=$sid" . periode_where($conn) . "");
    while ($x=$q4->fetch_assoc()) { /* tidak ditampilkan di Riwayat gabungan */ }
    // Riwayat Terlambat & Alpa digabung dari tabel absensi ke menu yang sama.
    // Poin per kejadian diambil dari pengaturan_poin_absen (Kelola Pelanggaran → Penentuan Point Alpa & Terlambat).
    $q5 = $conn->query("SELECT id, tanggal tgl, 'Terlambat' tipe, 'TERLAMBAT' judul, CONCAT('+',$poinTerlambatCfg,' Poin') ket, 'absensi' src FROM absensi WHERE siswa_id=$sid AND status='Terlambat'" . periode_where($conn) . "");
    while ($x=$q5->fetch_assoc()) $timeline[] = $x;
    $q6 = $conn->query("SELECT id, tanggal tgl, 'Alpa' tipe, 'ALPA' judul, CONCAT('+',$poinAlpaCfg,' Poin') ket, 'absensi' src FROM absensi WHERE siswa_id=$sid AND status='Alpa'" . periode_where($conn) . "");
    while ($x=$q6->fetch_assoc()) $timeline[] = $x;
    usort($timeline, fn($a,$b)=>strtotime($b['tgl'])<=>strtotime($a['tgl']));

    // ── Total Poin = Poin Pelanggaran Disiplin (dikurangi perbaikan kategori Pelanggaran)
    //               + Poin Buku Kejadian
    //               + Poin Terlambat (jumlah kejadian × poin per kejadian, dikurangi perbaikan kategori Terlambat)
    //               + Poin Alpa (jumlah kejadian × poin per kejadian, dikurangi perbaikan kategori Alpa)
    //               − Point Perbaikan kategori Kunjungan (mengurangi total langsung)
    $poinPelNet       = max(0, $poinPel - $perbaikan['PELANGGARAN']);
    $poinTerlambatTot = (int)($absMap['Terlambat'] ?? 0) * $poinTerlambatCfg;
    $poinAlpaTot      = (int)($absMap['Alpa'] ?? 0) * $poinAlpaCfg;
    $poinTerlambatNet = max(0, $poinTerlambatTot - $perbaikan['TERLAMBAT']);
    $poinAlpaNet      = max(0, $poinAlpaTot - $perbaikan['ALPA']);
    $totalPoin        = max(0, $poinPelNet + $poinBk + $poinTerlambatNet + $poinAlpaNet - $perbaikan['KUNJUNGAN']);

    echo json_encode([
        'ok'=>true,
        'siswa'=>$sw,
        'stat'=>[
            'Hadir'=>(int)($absMap['Hadir']??0),
            // Terlambat/Alpa di sini SELALU angka mentah dari tabel absensi (data admin),
            // supaya absen manual baru langsung kehitung. Pengurangan oleh Point Perbaikan
            // hanya ditampilkan sebagai "Sisa" di panel Input Point Perbaikan (lihat JS bukaRiwayat).
            'Terlambat'=>(int)($absMap['Terlambat']??0),
            'Alpa'=>(int)($absMap['Alpa']??0),
            'Izin'=>(int)($absMap['Izin']??0),'Sakit'=>(int)($absMap['Sakit']??0),
            'Pelanggaran'=>$jmlPel,'Kunjungan'=>$jmlKnj,'TotalPoin'=>$totalPoin,
        ],
        'poinCfg'=>['alpa'=>$poinAlpaCfg,'terlambat'=>$poinTerlambatCfg],
        'perbaikan'=>$perbaikan,
        'perbaikanLog'=>$perbaikanLog,
        'timeline'=>$timeline,
    ]);
    exit;
}

// ── AJAX: Simpan point perbaikan ─────────────────────────────────────────────
if (isset($_GET['ajax']) && $_GET['ajax'] === 'simpan_perbaikan') {
    header('Content-Type: application/json');
    $sid     = (int)($_POST['siswa_id'] ?? 0);
    $kat     = strtoupper(trim($_POST['kategori'] ?? ''));
    $jumlah  = max(1,(int)($_POST['jumlah'] ?? 1));
    $ket     = $conn->real_escape_string(trim($_POST['keterangan'] ?? ''));
    $tgl     = $conn->real_escape_string($_POST['tanggal'] ?? date('Y-m-d'));
    $valid   = ['TERLAMBAT','ALPA','PELANGGARAN','KUNJUNGAN'];
    if (!$sid || !in_array($kat,$valid)) { echo json_encode(['ok'=>false,'msg'=>'Data tidak lengkap']); exit; }
    $sw = $conn->query("SELECT nis,nama,kelas FROM siswa WHERE id=$sid")->fetch_assoc();
    if (!$sw) { echo json_encode(['ok'=>false,'msg'=>'Siswa tidak ditemukan']); exit; }
    $nis=$conn->real_escape_string($sw['nis']); $nm=$conn->real_escape_string($sw['nama']); $kls=$conn->real_escape_string($sw['kelas']);
    $nmg=$conn->real_escape_string($bk_nama);
    list($ptaBk,$psemBk) = periode_values($conn);
    $conn->query("INSERT INTO point_perbaikan (siswa_id,nis,nama_siswa,kelas,kategori,jumlah,keterangan,tanggal,guru_bk_id,nama_guru,tahun_ajaran,semester)
        VALUES ($sid,'$nis','$nm','$kls','$kat',$jumlah,'$ket','$tgl',$bk_id,'$nmg',$ptaBk,$psemBk)");
    if ($conn->error) { echo json_encode(['ok'=>false,'msg'=>'Gagal: '.$conn->error]); exit; }
    echo json_encode(['ok'=>true,'msg'=>"Point perbaikan $kat (+$jumlah) berhasil disimpan!",'id'=>$conn->insert_id]);
    exit;
}

// ── AJAX: Hapus point perbaikan ──────────────────────────────────────────────
if (isset($_GET['ajax']) && $_GET['ajax'] === 'hapus_perbaikan') {
    header('Content-Type: application/json');
    $id = (int)($_POST['id'] ?? 0);
    if ($id) { $conn->query("DELETE FROM point_perbaikan WHERE id=$id"); echo json_encode(['ok'=>true]); }
    else echo json_encode(['ok'=>false,'msg'=>'ID tidak valid']);
    exit;
}

// ── AJAX: Hapus log riwayat (entri Pelanggaran Disiplin / Terlambat / Alpa langsung dari tabel asal) ──
if (isset($_GET['ajax']) && $_GET['ajax'] === 'hapus_log_riwayat') {
    header('Content-Type: application/json');
    $id  = (int)($_POST['id'] ?? 0);
    $src = $_POST['src'] ?? '';
    $allowedSrc = ['pelanggaran','absensi'];
    if (!$id || !in_array($src, $allowedSrc, true)) {
        echo json_encode(['ok'=>false,'msg'=>'Data tidak valid']); exit;
    }
    $table = $src === 'pelanggaran' ? 'pelanggaran' : 'absensi';
    $conn->query("DELETE FROM `$table` WHERE id=$id");
    if ($conn->affected_rows > 0) echo json_encode(['ok'=>true]);
    else echo json_encode(['ok'=>false,'msg'=>'Data tidak ditemukan atau sudah dihapus']);
    exit;
}

// ════════════════════════════════════════════════════════════════════
// POST / GET HANDLERS
// ════════════════════════════════════════════════════════════════════

// ── Catatan BK: kirim / edit ──
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['kirim_catatan'])) {
    $sid = (int)$_POST['siswa_id'];
    $isi = trim($_POST['isi'] ?? '');
    $sw  = $conn->query("SELECT * FROM siswa WHERE id=$sid")->fetch_assoc();
    if ($sw && $isi !== '') {
        $isiE = $conn->real_escape_string($isi);
        $existing = $conn->query("SELECT id FROM catatan_bk WHERE siswa_id=$sid" . periode_where($conn) . " ORDER BY id DESC LIMIT 1")->fetch_assoc();
        if ($existing) {
            $conn->query("UPDATE catatan_bk SET isi='$isiE', nama_guru='".$conn->real_escape_string($bk_nama)."', guru_bk_id=$bk_id, balasan=NULL, dibalas_at=NULL WHERE id={$existing['id']}");
        } else {
            list($ptaCat,$psemCat) = periode_values($conn);
            $conn->query("INSERT INTO catatan_bk (siswa_id,nis,nama_siswa,kelas,guru_bk_id,nama_guru,isi,tahun_ajaran,semester)
                VALUES ($sid,'".$conn->real_escape_string($sw['nis'])."','".$conn->real_escape_string($sw['nama'])."','".$conn->real_escape_string($sw['kelas'])."',$bk_id,'".$conn->real_escape_string($bk_nama)."','$isiE',$ptaCat,$psemCat)");
        }
    }
    header('Location: portal_bk.php?page=catatan_bk'.(!empty($_POST['kelas'])?'&kelas='.urlencode($_POST['kelas']):'')); exit;
}
if (isset($_GET['hapus_catatan'])) {
    $conn->query("DELETE FROM catatan_bk WHERE id=".(int)$_GET['hapus_catatan']);
    header('Location: portal_bk.php?page=catatan_bk'); exit;
}

// ── Kunjungan Rumah: tambah / hapus ──
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['tambah_kunjungan'])) {
    $sid = (int)$_POST['siswa_id'];
    $sw  = $conn->query("SELECT * FROM siswa WHERE id=$sid")->fetch_assoc();
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
                $foto = 'kj_'.time().'_'.rand(100,999).'.'.$ext;
                move_uploaded_file($_FILES['foto']['tmp_name'], __DIR__.'/uploads/kunjungan/'.$foto);
            }
        }
        list($ptaKj,$psemKj) = periode_values($conn);
        $conn->query("INSERT INTO kunjungan_rumah (siswa_id,nis,nama_siswa,kelas,guru_bk_id,nama_guru,tanggal_kunjungan,nama_ortu,kasus,penyelesaian,keterangan,foto,tahun_ajaran,semester)
            VALUES ($sid,'".$conn->real_escape_string($sw['nis'])."','".$conn->real_escape_string($sw['nama'])."','".$conn->real_escape_string($sw['kelas'])."',$bk_id,'".$conn->real_escape_string($bk_nama)."','$tgl','$ortu','$kasus','$sel','$ket','$foto',$ptaKj,$psemKj)");
    }
    header('Location: portal_bk.php?page=kunjungan'); exit;
}
if (isset($_GET['hapus_kunjungan'])) {
    $conn->query("DELETE FROM kunjungan_rumah WHERE id=".(int)$_GET['hapus_kunjungan']);
    header('Location: portal_bk.php?page=kunjungan'); exit;
}

// ── Buku Kejadian: tambah / edit / hapus ──
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['simpan_kejadian'])) {
    $kid = (int)($_POST['kejadian_id'] ?? 0);
    $sid = (int)$_POST['siswa_id'];
    $sw  = $conn->query("SELECT * FROM siswa WHERE id=$sid")->fetch_assoc();
    if ($sw) {
        $tgl  = $conn->real_escape_string($_POST['tanggal'] ?? date('Y-m-d'));
        $hari = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'][date('w',strtotime($tgl))];
        $uraian = $conn->real_escape_string(trim($_POST['uraian_kejadian'] ?? ''));
        $poin   = (int)($_POST['poin'] ?? 0);
        $tgp    = $conn->real_escape_string(trim($_POST['tanggapan_siswa'] ?? ''));
        $arahan = $conn->real_escape_string(trim($_POST['arahan_guru_wali'] ?? ''));
        $tl     = $conn->real_escape_string(trim($_POST['tindak_lanjut'] ?? ''));
        // Handle TTD upload
        $ttd = '';
        if (!empty($_FILES['ttd']['name']) && $_FILES['ttd']['error']===UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['ttd']['name'], PATHINFO_EXTENSION));
            if (in_array($ext,['jpg','jpeg','png','gif','webp']) && $_FILES['ttd']['size'] < 3*1024*1024) {
                if (!is_dir(__DIR__.'/uploads/kunjungan')) @mkdir(__DIR__.'/uploads/kunjungan', 0755, true);
                $ttd = 'ttd_'.$sid.'_'.time().'.'.$ext;
                move_uploaded_file($_FILES['ttd']['tmp_name'], __DIR__.'/uploads/kunjungan/'.$ttd);
            }
        }
        if ($kid) {
            $ttdSql = $ttd ? ",ttd='$ttd'" : '';
            $conn->query("UPDATE buku_kejadian SET siswa_id=$sid,nis='".$conn->real_escape_string($sw['nis'])."',nama_siswa='".$conn->real_escape_string($sw['nama'])."',kelas='".$conn->real_escape_string($sw['kelas'])."',
                tanggal='$tgl',hari='$hari',uraian_kejadian='$uraian',poin=$poin,tanggapan_siswa='$tgp',arahan_guru_wali='$arahan',tindak_lanjut='$tl'$ttdSql WHERE id=$kid");
        } else {
            list($ptaBuk,$psemBuk) = periode_values($conn);
            $conn->query("INSERT INTO buku_kejadian (siswa_id,nis,nama_siswa,kelas,guru_bk_id,nama_guru,tanggal,hari,uraian_kejadian,poin,tanggapan_siswa,arahan_guru_wali,tindak_lanjut,ttd,tahun_ajaran,semester)
                VALUES ($sid,'".$conn->real_escape_string($sw['nis'])."','".$conn->real_escape_string($sw['nama'])."','".$conn->real_escape_string($sw['kelas'])."',$bk_id,'".$conn->real_escape_string($bk_nama)."','$tgl','$hari','$uraian',$poin,'$tgp','$arahan','$tl','$ttd',$ptaBuk,$psemBuk)");
        }
    }
    header('Location: portal_bk.php?page=buku_kejadian&kelas='.urlencode($_POST['kelas_active'] ?? '')); exit;
}
if (isset($_GET['hapus_kejadian'])) {
    $conn->query("DELETE FROM buku_kejadian WHERE id=".(int)$_GET['hapus_kejadian']);
    header('Location: portal_bk.php?page=buku_kejadian'); exit;
}

// ── Export Rekap Pelanggaran (Excel asli .xlsx) ──
if ($page === 'rekap_pelanggaran' && isset($_GET['export'])) {
    $bulan_e = (int)($_GET['bulan'] ?? date('n'));
    $tahun_e = (int)($_GET['tahun'] ?? date('Y'));
    $kelas_e = sanitize($_GET['kelas'] ?? '');
    $agg_e   = hitung_rekap_pelanggaran($conn, $bulan_e, $tahun_e, $kelas_e, null);

    $nama_sekolah_e = strtoupper($pengaturan['nama_sekolah'] ?? 'NAMA SEKOLAH');
    $alamat_e       = $pengaturan['alamat'] ?? '';
    $kepala_e       = $pengaturan['kepala_sekolah'] ?? '';
    $nip_e          = $pengaturan['nip_kepala'] ?? '';
    $lastCol_e      = 'H';

    $xlsx = new SimpleXLSX();
    $xlsx->setColWidth(1, 5); $xlsx->setColWidth(2, 14); $xlsx->setColWidth(3, 26); $xlsx->setColWidth(4, 9);
    $xlsx->setColWidth(5, 32); $xlsx->setColWidth(6, 8); $xlsx->setColWidth(7, 10); $xlsx->setColWidth(8, 9);

    $xlsx->addRow([[$nama_sekolah_e, SimpleXLSX::S_KOP_NAMA],'','','','','','','']);
    $xlsx->mergeCells('A1:'.$lastCol_e.'1');
    $xlsx->addRow([[$alamat_e, SimpleXLSX::S_SUBTITLE],'','','','','','','']);
    $xlsx->mergeCells('A2:'.$lastCol_e.'2');
    $xlsx->addEmptyRow();
    $judul_e = 'REKAP PELANGGARAN SISWA — '.$nama_bulan[$bulan_e].' '.$tahun_e.($kelas_e?' — KELAS '.$kelas_e:' — SEMUA KELAS');
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
    $xlsx->addRow(['','','','','','',['Kepala Sekolah',SimpleXLSX::S_CENTER],'']);
    $xlsx->addEmptyRow(); $xlsx->addEmptyRow(); $xlsx->addEmptyRow();
    $xlsx->addRow(['','','','','','',[$kepala_e ?: '(_______________________)',SimpleXLSX::S_BOLD],'']);
    if ($nip_e) $xlsx->addRow(['','','','','','',['NIP. '.$nip_e,SimpleXLSX::S_CENTER],'']);

    $xlsx->download('Rekap_Pelanggaran_'.$nama_bulan[$bulan_e].'_'.$tahun_e.($kelas_e?'_'.$kelas_e:'').'.xlsx');
    exit;
}

// ── Export Rekap Absensi Bulanan (Excel asli .xlsx) ──
if ($page === 'rekap_absensi' && isset($_GET['export'])) {
    $bulan_e = (int)($_GET['bulan'] ?? date('n'));
    $tahun_e = (int)($_GET['tahun'] ?? date('Y'));
    $kelas_e = sanitize($_GET['kelas'] ?? '');
    $cond_e  = $kelas_e ? "AND s.kelas='$kelas_e'" : '';
    $res_e = $conn->query("SELECT s.nis,s.nama,s.kelas,
            SUM(a.status='Hadir') Hadir, SUM(a.status='Terlambat') Terlambat, SUM(a.status='Alpa') Alpa,
            SUM(a.status='Sakit') Sakit, SUM(a.status='Izin') Izin, SUM(a.status='Bolos') Bolos
        FROM siswa s
        LEFT JOIN absensi a ON a.siswa_id=s.id AND MONTH(a.tanggal)=$bulan_e AND YEAR(a.tanggal)=$tahun_e
        WHERE 1=1 $cond_e GROUP BY s.id ORDER BY s.kelas,s.nama");

    $nama_sekolah_e = strtoupper($pengaturan['nama_sekolah'] ?? 'NAMA SEKOLAH');
    $alamat_e       = $pengaturan['alamat'] ?? '';
    $kepala_e       = $pengaturan['kepala_sekolah'] ?? '';
    $nip_e          = $pengaturan['nip_kepala'] ?? '';
    $lastCol_e      = 'J';

    $xlsx = new SimpleXLSX();
    $xlsx->setColWidth(1,14); $xlsx->setColWidth(2,26); $xlsx->setColWidth(3,9); $xlsx->setColWidth(4,9);
    $xlsx->setColWidth(5,11); $xlsx->setColWidth(6,8); $xlsx->setColWidth(7,8); $xlsx->setColWidth(8,8);
    $xlsx->setColWidth(9,8); $xlsx->setColWidth(10,11);

    $xlsx->addRow([[$nama_sekolah_e, SimpleXLSX::S_KOP_NAMA],'','','','','','','','','']);
    $xlsx->mergeCells('A1:'.$lastCol_e.'1');
    $xlsx->addRow([[$alamat_e, SimpleXLSX::S_SUBTITLE],'','','','','','','','','']);
    $xlsx->mergeCells('A2:'.$lastCol_e.'2');
    $xlsx->addEmptyRow();
    $judul_e = 'REKAP ABSENSI BULANAN — '.$nama_bulan[$bulan_e].' '.$tahun_e.($kelas_e?' — KELAS '.$kelas_e:' — SEMUA KELAS');
    $xlsx->addRow([[$judul_e, SimpleXLSX::S_TITLE],'','','','','','','','','']);
    $xlsx->mergeCells('A4:'.$lastCol_e.'4');
    $xlsx->addEmptyRow();

    $xlsx->addRow([
        ['NIS',SimpleXLSX::S_HEADER],['Nama',SimpleXLSX::S_HEADER],['Kelas',SimpleXLSX::S_HEADER],
        ['Hadir',SimpleXLSX::S_HEADER],['Terlambat',SimpleXLSX::S_HEADER],['Alpa',SimpleXLSX::S_HEADER],
        ['Sakit',SimpleXLSX::S_HEADER],['Izin',SimpleXLSX::S_HEADER],['Bolos',SimpleXLSX::S_HEADER],['% Kehadiran',SimpleXLSX::S_HEADER],
    ]);

    $sum_e = ['Hadir'=>0,'Terlambat'=>0,'Alpa'=>0,'Sakit'=>0,'Izin'=>0,'Bolos'=>0];
    while ($r = $res_e->fetch_assoc()) {
        $tot = $r['Hadir']+$r['Terlambat']+$r['Alpa']+$r['Sakit']+$r['Izin']+$r['Bolos'];
        $pct = $tot>0 ? round(($r['Hadir']+$r['Terlambat'])/$tot*100,1) : 0;
        $pctStyle = $pct>=80 ? SimpleXLSX::S_HADIR : ($pct>=60 ? SimpleXLSX::S_TERLAMBAT : SimpleXLSX::S_ALPA);
        foreach (['Hadir','Terlambat','Alpa','Sakit','Izin','Bolos'] as $k) $sum_e[$k] += (int)$r[$k];
        $xlsx->addRow([
            [$r['nis'],SimpleXLSX::S_CENTER],[$r['nama'],SimpleXLSX::S_BORDER],[$r['kelas'],SimpleXLSX::S_CENTER],
            [(int)$r['Hadir'],SimpleXLSX::S_HADIR],[(int)$r['Terlambat'],SimpleXLSX::S_TERLAMBAT],[(int)$r['Alpa'],SimpleXLSX::S_ALPA],
            [(int)$r['Sakit'],SimpleXLSX::S_NUMBER],[(int)$r['Izin'],SimpleXLSX::S_NUMBER],[(int)$r['Bolos'],SimpleXLSX::S_ALPA],
            [$pct.'%',$pctStyle],
        ]);
    }

    $xlsx->addEmptyRow(); $xlsx->addEmptyRow(); $xlsx->addEmptyRow();
    $xlsx->addRow(['','','','','','','','',['Mengetahui,',SimpleXLSX::S_CENTER],'']);
    $xlsx->addRow(['','','','','','','','',['Kepala Sekolah',SimpleXLSX::S_CENTER],'']);
    $xlsx->addEmptyRow(); $xlsx->addEmptyRow(); $xlsx->addEmptyRow();
    $xlsx->addRow(['','','','','','','','',[$kepala_e ?: '(_______________________)',SimpleXLSX::S_BOLD],'']);
    if ($nip_e) $xlsx->addRow(['','','','','','','','',['NIP. '.$nip_e,SimpleXLSX::S_CENTER],'']);

    $xlsx->download('Rekap_Absensi_'.$nama_bulan[$bulan_e].'_'.$tahun_e.($kelas_e?'_'.$kelas_e:'').'.xlsx');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Portal Guru BK – <?= htmlspecialchars($pengaturan['nama_sekolah'] ?? '') ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',system-ui,sans-serif;background:linear-gradient(135deg,#e0f2fe 0%,#f0fdf4 40%,#ede9fe 100%);background-attachment:fixed;display:flex;min-height:100vh;font-size:14px}

/* ── SIDEBAR ──────────────────────────────────────── */
.sidebar{width:220px;background:linear-gradient(180deg,#1e3a8a 0%,#0e7490 100%);color:white;display:flex;flex-direction:column;flex-shrink:0;min-height:100vh}
.sidebar-logo{padding:20px 16px 14px;border-bottom:1px solid rgba(255,255,255,.15);text-align:center}
.sidebar-logo img.school-logo{height:48px;margin-bottom:8px}
.sidebar-logo .school-name{font-weight:800;font-size:.88rem;line-height:1.3;color:white}
.sidebar-logo .school-sub{font-size:.7rem;opacity:.65;margin-top:3px}
.bk-profile{padding:14px 16px;border-bottom:1px solid rgba(255,255,255,.12);display:flex;align-items:center;gap:10px}
.bk-avatar{width:40px;height:40px;border-radius:50%;background:rgba(255,255,255,.2);border:2px solid rgba(255,255,255,.4);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:1rem;flex-shrink:0;overflow:hidden}
.bk-avatar img{width:100%;height:100%;object-fit:cover}
.bk-info .name{font-weight:700;font-size:.82rem;color:white}
.bk-info .role{font-size:.7rem;opacity:.65;margin-top:2px}
.nav-section{padding:12px 14px 3px;font-size:.63rem;font-weight:700;letter-spacing:1.5px;opacity:.5;text-transform:uppercase}
.nav-item{display:flex;align-items:center;gap:9px;padding:9px 16px;color:rgba(255,255,255,.8);text-decoration:none;transition:.15s;font-size:.85rem;border-radius:8px;margin:2px 8px}
.nav-item:hover{background:rgba(255,255,255,.12);color:white}
.nav-item.active{background:rgba(255,255,255,.2);color:white;font-weight:600}
.nav-item i{width:16px;text-align:center;font-size:.88rem}
.sidebar-footer{margin-top:auto;padding:12px 14px;border-top:1px solid rgba(255,255,255,.12)}

/* ── MAIN ─────────────────────────────────────────── */
.main{flex:1;display:flex;flex-direction:column;min-height:100vh;overflow:hidden}
.topbar{background:white;padding:11px 22px;display:flex;align-items:center;gap:10px;border-bottom:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05)}
.topbar .page-name{font-weight:700;font-size:.95rem;color:#1e293b}
.topbar .topbar-right{margin-left:auto;display:flex;align-items:center;gap:12px;font-size:.8rem;color:#64748b}
.content{padding:20px;flex:1;overflow-y:auto}

/* ── STAT CARDS ───────────────────────────────────── */
.stat-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:12px;margin-bottom:20px}
.stat-card{background:white;border-radius:12px;padding:14px 16px;box-shadow:0 2px 8px rgba(0,0,0,.06);border-top:4px solid var(--c)}
.stat-card .val{font-size:1.9rem;font-weight:800;color:var(--c)}
.stat-card .lbl{font-size:.72rem;color:#64748b;margin-top:3px;font-weight:600}

/* ── CARDS ────────────────────────────────────────── */
.card{background:white;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.06);overflow:hidden;margin-bottom:18px}
.card-header{padding:13px 18px;font-weight:700;font-size:.88rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:8px;color:#1e293b;flex-wrap:wrap}
.card-body{padding:18px}

/* ── FILTER ───────────────────────────────────────── */
.filter-row{display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap}
.filter-row label{font-size:.75rem;font-weight:600;color:#64748b;display:block;margin-bottom:4px}
.filter-row input,.filter-row select,.filter-row textarea{padding:7px 11px;border:1px solid #e2e8f0;border-radius:8px;font-size:.85rem;outline:none;background:white;min-width:120px;font-family:inherit}
.filter-row input:focus,.filter-row select:focus{border-color:#0e7490}
.btn-filter{padding:8px 18px;background:#0e7490;color:white;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:.85rem;white-space:nowrap}
.btn-filter:hover{background:#0c6678}
.btn-soft{padding:8px 16px;background:#eef2ff;color:#4338ca;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:.85rem;white-space:nowrap;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
.btn-green{padding:8px 16px;background:#16a34a;color:white;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:.85rem;white-space:nowrap;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
.btn-red{padding:6px 10px;background:#fee2e2;color:#dc2626;border:none;border-radius:6px;font-weight:700;cursor:pointer;font-size:.78rem}
.btn-icon{width:30px;height:30px;border-radius:7px;border:none;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;font-size:.78rem}

/* ── TABS (Periode Bulanan/Mingguan dsb) ───────────── */
.tab-toggle{display:inline-flex;background:#f1f5f9;border-radius:8px;padding:3px;gap:2px}
.tab-toggle button{padding:6px 16px;border:none;background:transparent;border-radius:6px;font-weight:700;font-size:.8rem;cursor:pointer;color:#64748b}
.tab-toggle button.active{background:#0e7490;color:white}

/* ── LEGENDA ──────────────────────────────────────── */
.legenda{display:flex;flex-wrap:wrap;gap:10px;align-items:center;padding:10px 16px;background:#f8fafc;border-bottom:1px solid #f1f5f9}
.leg-item{display:inline-flex;align-items:center;gap:6px;font-size:.78rem;font-weight:600}
.leg-box{width:22px;height:20px;border-radius:4px;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.72rem}

/* ── TABLE ────────────────────────────────────────── */
.tbl-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch}
table{width:100%;border-collapse:collapse;font-size:.78rem}
th{background:#1e293b;color:white;padding:7px 5px;text-align:center;white-space:nowrap;font-size:.72rem}
th.th-left{text-align:left}
th.sticky{position:sticky;z-index:3;background:#1e293b}
td{padding:5px 5px;border-bottom:1px solid #f1f5f9;vertical-align:middle}
td.sticky{position:sticky;z-index:1;background:white}
tr:nth-child(even) td{background:#f8fafc}
tr:nth-child(even) td.sticky{background:#f8fafc}
.st-box{display:inline-block;width:20px;height:20px;line-height:20px;border-radius:4px;font-weight:800;font-size:.68rem;text-align:center}
.weekend{background:#f1f5f9 !important}
.sum-cell{text-align:center;font-weight:700;padding:5px 4px}

/* ── STATUS BADGE ─────────────────────────────────── */
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
.b-catatan{background:#cffafe;color:#0e7490}
.b-kunjungan{background:#dcfce7;color:#15803d}
.b-pelanggaran{background:#fee2e2;color:#991b1b}
.b-kejadian{background:#ede9fe;color:#6d28d9}

/* ── MODAL ────────────────────────────────────────── */
.modal-overlay{display:none;position:fixed;inset:0;background:linear-gradient(135deg,rgba(14,116,144,.55) 0%,rgba(30,58,138,.55) 100%);backdrop-filter:blur(4px);z-index:1000;align-items:flex-start;justify-content:center;padding:32px 16px;overflow-y:auto}
.modal-overlay.show{display:flex}
.modal-box{background:white;border-radius:16px;width:100%;max-width:580px;box-shadow:0 24px 60px rgba(14,116,144,.25),0 8px 24px rgba(0,0,0,.12);max-height:90vh;display:flex;flex-direction:column;border-top:4px solid #0e7490}
.modal-box.wide{max-width:820px}
.modal-head{padding:16px 20px;background:linear-gradient(90deg,#f0fdfa,#f0f9ff);border-bottom:1px solid #e2e8f0;display:flex;align-items:center;gap:10px;font-weight:700;color:#0e7490;border-radius:12px 12px 0 0}
.modal-head .x{margin-left:auto;cursor:pointer;color:#94a3b8;background:none;border:none;font-size:1.2rem;line-height:1}
.modal-head .x:hover{color:#1e293b}
.modal-body{padding:18px 20px;overflow-y:auto;flex:1;background:#fafcff}
.modal-body label{font-size:.78rem;font-weight:700;color:#0e7490;display:block;margin-bottom:5px;margin-top:14px;letter-spacing:.01em}
.modal-body label:first-child{margin-top:0}
.modal-body input,.modal-body select,.modal-body textarea{width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:.85rem;outline:none;font-family:inherit;background:#ffffff;color:#1e293b}
.modal-body input:focus,.modal-body select:focus,.modal-body textarea:focus{border-color:#0e7490;box-shadow:0 0 0 3px rgba(14,116,144,.1)}
.modal-body input[readonly]{background:#f0f9ff;color:#0e7490;font-weight:600;border-color:#bae6fd}
.modal-body input[type="file"]{background:#f8fafc;padding:7px 10px;cursor:pointer}
.modal-foot{padding:14px 20px;background:linear-gradient(90deg,#f0fdfa,#f0f9ff);border-top:1px solid #e2e8f0;display:flex;justify-content:flex-end;gap:10px;border-radius:0 0 16px 16px}
.stat-mini-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:14px}
.stat-mini{background:#f8fafc;border-radius:10px;padding:10px;text-align:center}
.stat-mini .v{font-size:1.3rem;font-weight:800;color:#1e293b}
.stat-mini .l{font-size:.65rem;color:#64748b;font-weight:700;text-transform:uppercase}
.tl-item{padding:10px 0;border-bottom:1px solid #f1f5f9}
.tl-item:last-child{border-bottom:none}
.tl-date{font-size:.72rem;color:#94a3b8;font-weight:600}
.tl-title{font-weight:700;font-size:.85rem;color:#1e293b;margin-top:2px}
.tl-ket{font-size:.78rem;color:#64748b;margin-top:2px}
.rw-tabs{display:flex;gap:6px;margin-bottom:10px;border-bottom:1px solid #f1f5f9}
.rw-tab{background:none;border:none;cursor:pointer;padding:7px 4px;font-size:.75rem;font-weight:700;color:#94a3b8;position:relative;top:1px;border-bottom:2px solid transparent;display:flex;align-items:center;gap:5px}
.rw-tab .cnt{background:#f1f5f9;color:#64748b;border-radius:20px;padding:1px 7px;font-size:.65rem;font-weight:800}
.rw-tab.active{color:#0e7490;border-bottom-color:#0e7490}
.rw-tab.active .cnt{background:#cffafe;color:#0e7490}

/* ── HAMBURGER & OVERLAY ──────────────────────────── */
.hamburger{display:none;flex-direction:column;justify-content:center;gap:5px;width:34px;height:34px;cursor:pointer;padding:4px;border:none;background:transparent;flex-shrink:0}
.hamburger span{display:block;width:22px;height:2px;background:#1e293b;border-radius:2px;transition:.25s}
.hamburger.open span:nth-child(1){transform:translateY(7px) rotate(45deg)}
.hamburger.open span:nth-child(2){opacity:0}
.hamburger.open span:nth-child(3){transform:translateY(-7px) rotate(-45deg)}
.sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:998}
.sidebar-overlay.show{display:block}

/* ── KELAS PILLS ──────────────────────────────────── */
.kelas-pills-wrap{margin-bottom:18px}
.kelas-pills-title{font-size:.72rem;font-weight:700;color:#64748b;letter-spacing:.05em;text-transform:uppercase;margin-bottom:8px}
.kelas-pills{display:flex;flex-wrap:wrap;gap:7px}
.kelas-pill{display:inline-flex;align-items:center;gap:5px;padding:7px 15px;border-radius:24px;font-size:.82rem;font-weight:700;cursor:pointer;text-decoration:none;background:#dcfce7;color:#15803d;border:2px solid #86efac;transition:.15s;white-space:nowrap}
.kelas-pill:hover{background:#bbf7d0;border-color:#4ade80;color:#15803d}
.kelas-pill.active{background:#16a34a;color:white;border-color:#15803d;box-shadow:0 2px 8px rgba(22,163,74,.35)}
.kelas-pill .pill-status{font-size:.72rem;font-weight:400;opacity:.85}

/* ── PRINT ────────────────────────────────────────── */
.print-header{display:none}
.cetak-dokumen{display:none}
@media print{
    .no-print,.sidebar,.topbar,.hamburger,.sidebar-overlay,.modal-overlay,
    .kelas-pills-wrap,.card,.cetak-hide{display:none!important}
    .main{width:100%;display:block}
    .content{padding:0}
    body{background:white;font-size:11pt}
    .cetak-dokumen{display:block!important}

    /* ── Layout dokumen cetak ── */
    .cdk-wrap{font-family:Arial,sans-serif;font-size:10pt;color:#000}
    .cdk-judul{font-size:15pt;font-weight:900;margin-bottom:2px}
    .cdk-sub{font-size:10pt;margin-bottom:14px;border-bottom:2px solid #000;padding-bottom:6px}
    .cdk-tbl{width:100%;border-collapse:collapse;font-size:9pt}
    .cdk-tbl th{border:1px solid #000;padding:5px 6px;background:#e8e8e8;font-weight:700;text-align:center;vertical-align:middle}
    .cdk-tbl td{border:1px solid #000;padding:5px 6px;vertical-align:top}
    .cdk-tbl .tc{text-align:center;vertical-align:middle}
    .cdk-ttd{display:flex;justify-content:space-between;margin-top:36px;font-size:10pt}
    .cdk-ttd-box{text-align:center;width:220px}
    .cdk-ttd-line{margin-top:54px;border-top:1px solid #000}
}

/* ── MOBILE ───────────────────────────────────────── */
@media(max-width:768px){
    body{flex-direction:column}
    .sidebar{position:fixed;left:-240px;top:0;bottom:0;width:240px;z-index:999;transition:left .28s;overflow-y:auto}
    .sidebar.open{left:0}
    .hamburger{display:flex}
    .main{width:100%}
    .topbar{padding:9px 12px}
    .topbar .topbar-right .school-txt{display:none}
    .content{padding:12px}
    .stat-grid{grid-template-columns:repeat(2,1fr);gap:8px}
    .stat-card .val{font-size:1.5rem}
    .filter-row{flex-direction:column}
    .filter-row input,.filter-row select,.btn-filter{width:100%}
    .stat-mini-grid{grid-template-columns:repeat(2,1fr)}
}
@media(max-width:400px){
    .stat-grid{grid-template-columns:repeat(2,1fr)}
    .topbar .topbar-right{display:none}
}

/* ── PRINT: Rekap Riwayat Siswa ───────────────────────────────────────── */
@media print {
    /* CATATAN: JANGAN tambahkan .content di sini — .content adalah induk dari
       semua dokumen cetak lain (.cetak-dokumen: Kunjungan, Buku Kejadian,
       Rekap Pelanggaran, Rekap Absensi). Menyembunyikan .content membuat
       SEMUA dokumen cetak tersebut ikut tersembunyi walau diberi display:block!important,
       karena display:none pada elemen induk selalu menyembunyikan seluruh anaknya. */
    body { background: white !important; }
    .print-rekap-siswa { display: block !important; }
}
.print-rekap-siswa {
    display: none;
    font-family: Arial, sans-serif;
    font-size: 11pt;
    color: #000;
}
.print-header-sekolah {
    text-align: center;
    border-bottom: 2px solid #000;
    padding-bottom: 8px;
    margin-bottom: 16px;
}
.print-header-sekolah .nama-sek { font-size: 14pt; font-weight: bold; }
.print-header-sekolah .sub-sek  { font-size: 10pt; }
.print-rekap-title { text-align: center; font-weight: bold; font-size: 13pt; margin-bottom: 16px; text-decoration: underline; }
.print-identitas-wrap { display: flex; gap: 20px; align-items: flex-start; margin-bottom: 14px; }
.print-foto-box { width: 90px; height: 110px; border: 1px solid #999; display: flex; align-items: center; justify-content: center; font-size: 9pt; color: #666; text-align: center; flex-shrink: 0; font-weight: bold; }
.print-identitas-tbl { flex: 1; border-collapse: collapse; font-size: 10.5pt; }
.print-identitas-tbl td { padding: 3px 6px; }
.print-identitas-tbl td:first-child { width: 130px; font-weight: 600; }
.print-rekap-tbl { width: 100%; border-collapse: collapse; font-size: 9.5pt; margin-top: 12px; }
.print-rekap-tbl th { border: 1px solid #000; padding: 5px 4px; text-align: center; background: #dbeafe; font-weight: bold; font-size: 9pt; }
.print-rekap-tbl td { border: 1px solid #000; padding: 4px 4px; text-align: center; min-height: 20px; }
.print-rekap-tbl td.td-left { text-align: left; padding-left: 8px; }
.print-ttd-wrap { display: flex; justify-content: flex-end; margin-top: 24px; }
.print-ttd-box { text-align: center; width: 200px; }
.print-ttd-line { border-bottom: 1px solid #000; margin: 40px 20px 4px; }
</style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- ═══════════ SIDEBAR ═══════════ -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <?php if (!empty($pengaturan['logo']) && file_exists('uploads/logo/'.$pengaturan['logo'])): ?>
        <img src="<?= BASE_URL ?>uploads/logo/<?= $pengaturan['logo'] ?>" class="school-logo">
        <?php else: ?>
        <div style="font-size:1.8rem;margin-bottom:8px">🏫</div>
        <?php endif; ?>
        <div class="school-name"><?= htmlspecialchars($pengaturan['nama_sekolah'] ?? 'Portal BK') ?></div>
        <div class="school-sub">Sistem Monitoring Siswa Digital</div>
    </div>

    <div class="bk-profile">
        <div class="bk-avatar">
            <?php if ($foto_bk && file_exists($foto_dir.$foto_bk)): ?>
                <img src="<?= BASE_URL ?><?= $foto_dir ?><?= $foto_bk ?>">
            <?php else: ?>
                <?= strtoupper(substr($bk_nama,0,1)) ?>
            <?php endif; ?>
        </div>
        <div class="bk-info">
            <div class="name"><?= htmlspecialchars($bk_nama) ?></div>
            <div class="role"><i class="fas fa-user-shield" style="font-size:.65rem"></i> Guru BK</div>
        </div>
    </div>

    <div class="nav-section">Menu BK</div>
    <?php
    $nav_items = [
        'rekap_pelanggaran' => ['chart-bar',       'Rekap Pelanggaran'],
        'rekap_absensi'     => ['calendar-check',  'Rekap Absensi'],
        'catatan_bk'        => ['comment-medical', 'Catatan BK'],
        'kunjungan'         => ['house-user',      'Kunjungan'],
        'data_siswa'        => ['user-graduate',   'Data Siswa'],
        'buku_kejadian'     => ['book',             'Buku Kejadian'],
    ];
    foreach ($nav_items as $key=>[$ico,$label]): ?>
    <a href="portal_bk.php?page=<?= $key ?>" class="nav-item <?= $page===$key?'active':'' ?>">
        <i class="fas fa-<?= $ico ?>"></i> <?= $label ?>
    </a>
    <?php endforeach; ?>

    <div class="sidebar-footer">
        <a href="?logout=1" style="color:rgba(255,255,255,.7);text-decoration:none;font-size:.8rem;display:flex;align-items:center;gap:6px">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<!-- ═══════════ MAIN ═══════════ -->
<div class="main">
    <div class="topbar no-print">
        <button class="hamburger" id="hamburgerBtn" onclick="toggleSidebar()"><span></span><span></span><span></span></button>
        <div class="page-name">
            <?php
            $icons  = array_map(fn($v)=>$v[0], $nav_items);
            $titles = array_map(fn($v)=>$v[1], $nav_items);
            echo '<i class="fas fa-'.($icons[$page]??'home').'"></i> '.($titles[$page]??'Portal BK');
            ?>
        </div>
        <div class="topbar-right">
            <span class="school-txt"><?= htmlspecialchars($pengaturan['nama_sekolah'] ?? '') ?> &nbsp;|&nbsp;</span>
            <i class="fas fa-clock"></i> <span id="jam">--:--:--</span>
        </div>
    </div>

    <div class="content">

<?php /* ═══════════════════════ 1. REKAP PELANGGARAN ═══════════════════════ */
if ($page === 'rekap_pelanggaran'):
    // Kelas kosong ('') = Semua Kelas — Portal BK mengawasi semua kelas, jadi wajib bisa pilih "Semua Kelas".
    $kelas_p = sanitize($_GET['kelas'] ?? '');
    $bulan_p = (int)($_GET['bulan'] ?? date('n'));
    $tahun_p = (int)($_GET['tahun'] ?? date('Y'));

    // Rekap Pelanggaran = Terlambat + Alpa + Pelanggaran Disiplin, poin per-kejadian
    // diambil dari sumber yang SAMA dengan Riwayat Siswa (menu Data Siswa).
    $agg_p = hitung_rekap_pelanggaran($conn, $bulan_p, $tahun_p, $kelas_p, null);
?>
<!-- ═══ DOKUMEN CETAK: REKAP PELANGGARAN (hanya tampil saat print) ═══ -->
<div class="cetak-dokumen" id="cdk-rekap-pelanggaran">
<div class="cdk-wrap">
    <div class="cdk-judul">REKAP PELANGGARAN SISWA</div>
    <div class="cdk-sub"><?= $nm_sek ?> &mdash; <?= $nama_bulan[$bulan_p] ?> <?= $tahun_p ?><?= $kelas_p ? ' — Kelas '.htmlspecialchars($kelas_p) : ' — Semua Kelas' ?> &mdash; Dicetak: <?= date('j/n/Y, H.i.s') ?></div>
    <table class="cdk-tbl">
        <thead>
            <tr>
                <th width="28">No</th><th width="80">NIS</th><th>Nama</th><th width="55">Kelas</th>
                <th>Pelanggaran</th><th width="45">Point</th><th width="55">Jumlah</th><th width="50">Total</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!$agg_p): ?>
            <tr><td colspan="8" style="text-align:center;padding:16px">Tidak ada pelanggaran</td></tr>
        <?php else: $no_pc=0; foreach ($agg_p as $row_pc): $no_pc++; ?>
            <?php if (!$row_pc['items']): ?>
            <tr>
                <td class="tc"><?= $no_pc ?></td>
                <td class="tc"><?= htmlspecialchars($row_pc['nis']) ?></td>
                <td><?= htmlspecialchars($row_pc['nama']) ?></td>
                <td class="tc"><?= htmlspecialchars($row_pc['kelas']) ?></td>
                <td colspan="3" style="font-style:italic;color:#555">Tidak ada pelanggaran</td>
                <td class="tc">0</td>
            </tr>
            <?php else: $firstRow=true; foreach ($row_pc['items'] as $it_pc): ?>
            <tr>
                <td class="tc"><?= $firstRow ? $no_pc : '' ?></td>
                <td class="tc"><?= $firstRow ? htmlspecialchars($row_pc['nis']) : '' ?></td>
                <td><?= $firstRow ? htmlspecialchars($row_pc['nama']) : '' ?></td>
                <td class="tc"><?= $firstRow ? htmlspecialchars($row_pc['kelas']) : '' ?></td>
                <td><?= htmlspecialchars($it_pc['label']) ?></td>
                <td class="tc"><?= $it_pc['point'] ?></td>
                <td class="tc"><?= $it_pc['jumlah'] ?></td>
                <td class="tc" style="font-weight:700"><?= $firstRow ? $row_pc['total'] : '' ?></td>
            </tr>
            <?php $firstRow=false; endforeach; endif; ?>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    <div class="cdk-ttd">
        <div class="cdk-ttd-box">
            Mengetahui,<br>Kepala Sekolah
            <div class="cdk-ttd-line"><?= htmlspecialchars($pengaturan['kepala_sekolah'] ?? '') ?></div>
        </div>
        <div class="cdk-ttd-box">
            <?= $tgl_cetak ?><br>Guru BK
            <div class="cdk-ttd-line"><?= htmlspecialchars($bk_nama) ?></div>
        </div>
    </div>
</div>
</div>
<!-- ═══ AKHIR DOKUMEN CETAK REKAP PELANGGARAN ═══ -->

<div class="card no-print">
    <div class="card-body">
        <form method="GET" class="filter-row">
            <input type="hidden" name="page" value="rekap_pelanggaran">
            <div>
                <label>Kelas</label>
                <select name="kelas">
                    <option value="" <?= $kelas_p===''?'selected':'' ?>>-- Semua Kelas --</option>
                    <?php foreach ($kelas_list as $k): ?>
                    <option value="<?= $k ?>" <?= $kelas_p===$k?'selected':'' ?>><?= htmlspecialchars($k) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
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
                <a class="btn-green" href="portal_bk.php?page=rekap_pelanggaran&export=1&bulan=<?= $bulan_p ?>&tahun=<?= $tahun_p ?>&kelas=<?= urlencode($kelas_p) ?>">
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
    <div class="card-header"><i class="fas fa-list-ol" style="color:#0e7490"></i> Rekap Pelanggaran Siswa
        <span style="margin-left:auto;font-size:.75rem;font-weight:400;color:#64748b"><?= $nama_bulan[$bulan_p] ?> <?= $tahun_p ?><?= $kelas_p ? ' — Kelas '.htmlspecialchars($kelas_p) : ' — Semua Kelas' ?></span>
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
                <td style="text-align:center;vertical-align:middle;font-weight:800;color:#0e7490;font-size:1rem"><?= $row['total'] ?></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php /* ═══════════════════════ 2. REKAP ABSENSI ═══════════════════════ */
elseif ($page === 'rekap_absensi'):
    $periode_a = $_GET['periode'] ?? 'bulanan';
    $kelas_a   = sanitize($_GET['kelas'] ?? '');
    $bulan_a   = (int)($_GET['bulan'] ?? date('n'));
    $tahun_a   = (int)($_GET['tahun'] ?? date('Y'));
    $tgl_awal_a= sanitize($_GET['tgl_awal'] ?? date('Y-m-d', strtotime('monday this week')));
    $tgl_akhir_a = date('Y-m-d', strtotime($tgl_awal_a.' +6 days'));
    // Auto tampil jika kelas dipilih via pill button
    $sudah_tampil = isset($_GET['tampil']) || !empty($kelas_a);

    $rekap_a = [];
    if ($sudah_tampil) {
        $cond_a = $kelas_a ? "AND s.kelas='$kelas_a'" : '';
        if ($periode_a === 'mingguan') {
            $dateCond = "a.tanggal BETWEEN '$tgl_awal_a' AND '$tgl_akhir_a'";
        } else {
            $dateCond = "MONTH(a.tanggal)=$bulan_a AND YEAR(a.tanggal)=$tahun_a";
        }
        $res_a = $conn->query("SELECT s.id,s.nis,s.nama,s.kelas,
                SUM(a.status='Hadir') Hadir, SUM(a.status='Terlambat') Terlambat, SUM(a.status='Alpa') Alpa,
                SUM(a.status='Sakit') Sakit, SUM(a.status='Izin') Izin, SUM(a.status='Bolos') Bolos
            FROM siswa s
            LEFT JOIN absensi a ON a.siswa_id=s.id AND $dateCond
            WHERE 1=1 $cond_a GROUP BY s.id ORDER BY s.kelas,s.nama");
        while ($r=$res_a->fetch_assoc()) $rekap_a[] = $r;
    }
?>
<!-- ═══ DOKUMEN CETAK: REKAP ABSENSI (hanya tampil saat print) ═══ -->
<div class="cetak-dokumen" id="cdk-rekap-absensi">
<div class="cdk-wrap">
    <div class="cdk-judul">REKAP KEHADIRAN <?= $periode_a==='mingguan'?'MINGGUAN':'BULANAN' ?> SISWA</div>
    <div class="cdk-sub">
        <?= $nm_sek ?> &mdash;
        <?php if ($periode_a==='mingguan'): ?>
            <?= date('j/n/Y',strtotime($tgl_awal_a)) ?> s/d <?= date('j/n/Y',strtotime($tgl_akhir_a)) ?>
        <?php else: ?>
            <?= $nama_bulan[$bulan_a] ?> <?= $tahun_a ?>
        <?php endif; ?>
        <?= $kelas_a ? ' — Kelas '.htmlspecialchars($kelas_a) : ' — Semua Kelas' ?> &mdash; Dicetak: <?= date('j/n/Y, H.i.s') ?>
    </div>
    <table class="cdk-tbl">
        <thead>
            <tr>
                <th width="28">No</th><th width="80">NIS</th><th>Nama</th><th width="55">Kelas</th>
                <th width="45">Hadir</th><th width="55">Terlmb</th><th width="40">Alpa</th>
                <th width="40">Sakit</th><th width="40">Izin</th><th width="40">Bolos</th><th width="55">% Hadir</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!$sudah_tampil || !$rekap_a): ?>
            <tr><td colspan="11" style="text-align:center;padding:16px">Tidak ada data</td></tr>
        <?php else: $no_ac=0; foreach ($rekap_a as $r_ac): $no_ac++;
            $tot_ac = $r_ac['Hadir']+$r_ac['Terlambat']+$r_ac['Alpa']+$r_ac['Sakit']+$r_ac['Izin']+$r_ac['Bolos'];
            $pct_ac = $tot_ac>0 ? round(($r_ac['Hadir']+$r_ac['Terlambat'])/$tot_ac*100,1) : 0;
        ?>
            <tr>
                <td class="tc"><?= $no_ac ?></td>
                <td class="tc"><?= htmlspecialchars($r_ac['nis']) ?></td>
                <td><?= htmlspecialchars($r_ac['nama']) ?></td>
                <td class="tc"><?= htmlspecialchars($r_ac['kelas']) ?></td>
                <td class="tc"><?= $r_ac['Hadir'] ?></td>
                <td class="tc"><?= $r_ac['Terlambat'] ?></td>
                <td class="tc"><?= $r_ac['Alpa'] ?></td>
                <td class="tc"><?= $r_ac['Sakit'] ?></td>
                <td class="tc"><?= $r_ac['Izin'] ?></td>
                <td class="tc"><?= $r_ac['Bolos'] ?></td>
                <td class="tc" style="font-weight:700"><?= $pct_ac ?>%</td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    <div class="cdk-ttd">
        <div class="cdk-ttd-box">
            Mengetahui,<br>Kepala Sekolah
            <div class="cdk-ttd-line"><?= htmlspecialchars($pengaturan['kepala_sekolah'] ?? '') ?></div>
        </div>
        <div class="cdk-ttd-box">
            <?= $tgl_cetak ?><br>Guru BK
            <div class="cdk-ttd-line"><?= htmlspecialchars($bk_nama) ?></div>
        </div>
    </div>
</div>
</div>
<!-- ═══ AKHIR DOKUMEN CETAK REKAP ABSENSI ═══ -->

<?php render_kelas_pills($kelas_list, 'rekap_absensi', $kelas_a, '&bulan='.$bulan_a.'&tahun='.$tahun_a.'&periode='.$periode_a.'&tampil=1'); ?>
<div class="card no-print">
    <div class="card-body">
        <div style="margin-bottom:14px">
            <label style="font-size:.75rem;font-weight:600;color:#64748b;display:block;margin-bottom:6px">Periode</label>
            <div class="tab-toggle">
                <button type="button" class="periode-btn <?= $periode_a==='bulanan'?'active':'' ?>" data-p="bulanan">Bulanan</button>
                <button type="button" class="periode-btn <?= $periode_a==='mingguan'?'active':'' ?>" data-p="mingguan">Mingguan</button>
            </div>
        </div>
        <form method="GET" class="filter-row">
            <input type="hidden" name="page" value="rekap_absensi">
            <input type="hidden" name="periode" id="periodeInput" value="<?= $periode_a ?>">
            <input type="hidden" name="tampil" value="1">
            <div class="blok-bulanan" style="<?= $periode_a!=='bulanan'?'display:none':'' ?>">
                <label>Bulan</label>
                <select name="bulan">
                    <?php for ($m=1;$m<=12;$m++): ?>
                    <option value="<?= $m ?>" <?= $bulan_a==$m?'selected':'' ?>><?= $nama_bulan[$m] ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="blok-bulanan" style="<?= $periode_a!=='bulanan'?'display:none':'' ?>">
                <label>Tahun</label>
                <input type="number" name="tahun" value="<?= $tahun_a ?>" style="width:100px">
            </div>
            <div class="blok-mingguan" style="<?= $periode_a!=='mingguan'?'display:none':'' ?>">
                <label>Mulai Minggu (Senin)</label>
                <input type="date" name="tgl_awal" value="<?= $tgl_awal_a ?>">
            </div>
            <div>
                <label>Kelas</label>
                <select name="kelas">
                    <option value="">-- Semua Kelas --</option>
                    <?php foreach ($kelas_list as $k): ?>
                    <option value="<?= $k ?>" <?= $kelas_a===$k?'selected':'' ?>><?= htmlspecialchars($k) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><label>&nbsp;</label><button type="submit" class="btn-filter"><i class="fas fa-search"></i> Tampilkan</button></div>
            <?php if ($periode_a==='bulanan'): ?>
            <div><label>&nbsp;</label>
                <a class="btn-green" href="portal_bk.php?page=rekap_absensi&export=1&bulan=<?= $bulan_a ?>&tahun=<?= $tahun_a ?>&kelas=<?= urlencode($kelas_a) ?>">
                    <i class="fas fa-file-excel"></i> Export Bulanan
                </a>
            </div>
            <?php endif; ?>
            <?php if ($sudah_tampil): ?>
            <div><label>&nbsp;</label>
                <button type="button" class="btn-filter" style="background:#475569" onclick="window.print()">
                    <i class="fas fa-print"></i> Cetak
                </button>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="fas fa-calendar-check" style="color:#0e7490"></i> Rekap Kehadiran <?= $periode_a==='mingguan'?'Mingguan':'Bulanan' ?> Siswa</div>
    <div class="tbl-wrap">
        <table>
            <thead><tr>
                <th style="width:35px">#</th><th class="th-left">NIS</th><th class="th-left">NAMA</th><th>KELAS</th>
                <th style="background:#166534">HADIR</th><th style="background:#854d0e">TERLAMBAT</th><th style="background:#991b1b">ALPA</th>
                <th style="background:#1e40af">SAKIT</th><th style="background:#5b21b6">IZIN</th><th style="background:#9a3412">BOLOS</th>
                <th style="background:#1e3a8a">% KEHADIRAN</th>
            </tr></thead>
            <tbody>
            <?php if (!$sudah_tampil): ?>
            <tr><td colspan="11" style="text-align:center;padding:40px;color:#94a3b8">Pilih filter dan klik Tampilkan</td></tr>
            <?php elseif (empty($rekap_a)): ?>
            <tr><td colspan="11" style="text-align:center;padding:40px;color:#94a3b8">Tidak ada data</td></tr>
            <?php else: $no=0; foreach ($rekap_a as $r): $no++;
                $tot=$r['Hadir']+$r['Terlambat']+$r['Alpa']+$r['Sakit']+$r['Izin']+$r['Bolos'];
                $pct = $tot>0 ? round(($r['Hadir']+$r['Terlambat'])/$tot*100,1) : 0;
            ?>
            <tr>
                <td style="text-align:center"><?= $no ?></td>
                <td style="font-family:monospace"><?= htmlspecialchars($r['nis']) ?></td>
                <td style="font-weight:600"><?= htmlspecialchars($r['nama']) ?></td>
                <td style="text-align:center"><?= htmlspecialchars($r['kelas']) ?></td>
                <td class="sum-cell" style="color:#166534"><?= $r['Hadir'] ?></td>
                <td class="sum-cell" style="color:#854d0e"><?= $r['Terlambat'] ?></td>
                <td class="sum-cell" style="color:#991b1b"><?= $r['Alpa'] ?></td>
                <td class="sum-cell" style="color:#1e40af"><?= $r['Sakit'] ?></td>
                <td class="sum-cell" style="color:#5b21b6"><?= $r['Izin'] ?></td>
                <td class="sum-cell" style="color:#9a3412"><?= $r['Bolos'] ?></td>
                <td class="sum-cell" style="color:<?= $pct>=90?'#16a34a':($pct>=75?'#d97706':'#dc2626') ?>"><?= $pct ?>%</td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php /* ═══════════════════════ 3. CATATAN BK ═══════════════════════ */
elseif ($page === 'catatan_bk'):
    $q_c     = sanitize($_GET['q'] ?? '');
    $kelas_c = sanitize($_GET['kelas'] ?? ($kelas_list[0] ?? ''));
    $cond_c  = "1=1";
    if ($q_c)     $cond_c .= " AND s.nama LIKE '%$q_c%'";
    if ($kelas_c) $cond_c .= " AND s.kelas='$kelas_c'";
    $siswa_c = $conn->query("SELECT s.*, cb.id cid, cb.isi, cb.balasan
        FROM siswa s
        LEFT JOIN catatan_bk cb ON cb.id = (SELECT id FROM catatan_bk WHERE siswa_id=s.id" . periode_where($conn) . " ORDER BY id DESC LIMIT 1)
        WHERE $cond_c ORDER BY s.kelas, s.nama");
?>
<?php render_kelas_pills($kelas_list, 'catatan_bk', $kelas_c); ?>
<div class="card">
    <div class="card-header"><i class="fas fa-comment-medical" style="color:#0e7490"></i> Catatan Bimbingan</div>
    <div class="card-body" style="padding-bottom:0">
        <form method="GET" class="filter-row" style="margin-bottom:16px">
            <input type="hidden" name="page" value="catatan_bk">
            <div style="flex:1;min-width:180px">
                <label>Siswa / Nama</label>
                <input type="text" name="q" value="<?= htmlspecialchars($q_c) ?>" placeholder="Cari nama siswa...">
            </div>
            <div>
                <label>Kelas</label>
                <select name="kelas">
                    <option value="">-- Semua Kelas --</option>
                    <?php foreach ($kelas_list as $k): ?>
                    <option value="<?= $k ?>" <?= $kelas_c===$k?'selected':'' ?>><?= htmlspecialchars($k) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><button type="submit" class="btn-filter"><i class="fas fa-search"></i> Cari</button></div>
        </form>
    </div>
    <div class="tbl-wrap">
        <table>
            <thead><tr><th style="width:35px">No</th><th class="th-left">Nama Siswa</th><th class="th-left" style="min-width:260px">Catatan</th><th style="width:90px">Kirim</th><th class="th-left">Balasan Siswa</th></tr></thead>
            <tbody>
            <?php if ($siswa_c->num_rows===0): ?>
            <tr><td colspan="5" style="text-align:center;padding:30px;color:#94a3b8">Tidak ada siswa</td></tr>
            <?php else: $no=0; while ($s=$siswa_c->fetch_assoc()): $no++; ?>
            <tr>
                <td style="text-align:center"><?= $no ?></td>
                <td style="font-weight:700"><?= htmlspecialchars($s['nama']) ?></td>
                <td>
                    <form method="POST" style="display:flex;gap:6px;align-items:center">
                        <input type="hidden" name="siswa_id" value="<?= $s['id'] ?>">
                        <textarea name="isi" rows="1" placeholder="Ketik catatan untuk <?= htmlspecialchars($s['nama']) ?>..." style="flex:1;resize:vertical"><?= htmlspecialchars($s['isi'] ?? '') ?></textarea>
                        <button type="submit" name="kirim_catatan" value="1" style="background:#0e7490;color:white;border:none;border-radius:7px;padding:8px 12px;cursor:pointer;font-size:.78rem;font-weight:700;white-space:nowrap">
                            <i class="fas fa-paper-plane"></i> <?= $s['cid'] ? 'Update' : 'Kirim' ?>
                        </button>
                    </form>
                </td>
                <td style="text-align:center">
                    <?php if ($s['cid']): ?>
                    <span style="color:#16a34a;font-weight:700;font-size:.78rem"><i class="fas fa-check"></i> Terkirim</span>
                    <div style="margin-top:4px">
                        <a href="portal_bk.php?page=catatan_bk&hapus_catatan=<?= $s['cid'] ?>" onclick="return confirm('Hapus catatan ini?')" class="btn-red" style="text-decoration:none;display:inline-block"><i class="fas fa-trash"></i></a>
                    </div>
                    <?php else: ?>
                    <span style="color:#cbd5e1;font-size:.78rem">—</span>
                    <?php endif; ?>
                </td>
                <td style="color:<?= $s['balasan']?'#1e293b':'#94a3b8' ?>"><?= $s['balasan'] ? htmlspecialchars($s['balasan']) : 'Belum dibalas' ?></td>
            </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php /* ═══════════════════════ 4. KUNJUNGAN RUMAH ═══════════════════════ */
elseif ($page === 'kunjungan'):
    $q_k     = sanitize($_GET['q'] ?? '');
    $kelas_k = sanitize($_GET['kelas'] ?? '');
    $cond_k  = "1=1";
    if ($q_k)     $cond_k .= " AND (nama_siswa LIKE '%$q_k%' OR nama_ortu LIKE '%$q_k%')";
    if ($kelas_k) $cond_k .= " AND kelas='$kelas_k'";
    $cond_k .= periode_where($conn);
    $list_k = $conn->query("SELECT * FROM kunjungan_rumah WHERE $cond_k ORDER BY tanggal_kunjungan DESC");
    $siswa_opt = $conn->query("SELECT id,nis,nama,kelas FROM siswa ORDER BY nama");
    $siswa_arr = [];
    while ($s=$siswa_opt->fetch_assoc()) $siswa_arr[] = $s;
?>
<!-- ═══ DOKUMEN CETAK: KUNJUNGAN RUMAH (hanya tampil saat print) ═══ -->
<?php
// Kumpulkan semua data kunjungan untuk dokumen cetak (tanpa limit)
$rows_k_cetak = [];
$lk_cetak = $conn->query("SELECT * FROM kunjungan_rumah WHERE $cond_k ORDER BY tanggal_kunjungan DESC"); // $cond_k sudah termasuk filter periode
while ($r=$lk_cetak->fetch_assoc()) $rows_k_cetak[] = $r;
?>
<div class="cetak-dokumen" id="cdk-kunjungan">
<div class="cdk-wrap">
    <div class="cdk-judul">BUKU KUNJUNGAN RUMAH</div>
    <div class="cdk-sub"><?= $nm_sek ?> &mdash; Dicetak: <?= date('j/n/Y, H.i.s') ?></div>
    <table class="cdk-tbl">
        <thead>
            <tr>
                <th width="28">No</th>
                <th width="90">Tanggal</th>
                <th>Nama Siswa</th>
                <th width="60">Kelas</th>
                <th width="130">Nama Orang Tua/Wali</th>
                <th>Kasus / Permasalahan</th>
                <th width="130">Penyelesaian</th>
                <th width="140">Keterangan</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($rows_k_cetak)): ?>
            <tr><td colspan="8" style="text-align:center;padding:16px">Tidak ada data</td></tr>
        <?php else: $no=0; foreach ($rows_k_cetak as $r): $no++; ?>
            <tr>
                <td class="tc"><?= $no ?></td>
                <td class="tc"><?= $r['tanggal_kunjungan'] ?></td>
                <td><strong><?= htmlspecialchars($r['nama_siswa']) ?></strong></td>
                <td class="tc"><?= htmlspecialchars($r['kelas']) ?></td>
                <td><?= htmlspecialchars($r['nama_ortu'] ?: '-') ?></td>
                <td><?= htmlspecialchars($r['kasus'] ?: '-') ?></td>
                <td class="tc"><?= htmlspecialchars($r['penyelesaian']) ?></td>
                <td><?= htmlspecialchars($r['keterangan'] ?: '-') ?></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    <div class="cdk-ttd">
        <div class="cdk-ttd-box">
            Mengetahui,<br>Kepala Sekolah
            <div class="cdk-ttd-line"><?= htmlspecialchars($pengaturan['kepala_sekolah'] ?? '') ?></div>
        </div>
        <div class="cdk-ttd-box">
            <?= $tgl_cetak ?><br>Guru BK
            <div class="cdk-ttd-line"><?= htmlspecialchars($bk_nama) ?></div>
        </div>
    </div>
</div>
</div>
<!-- ═══ AKHIR DOKUMEN CETAK KUNJUNGAN ═══ -->

<div class="card">
    <div class="card-header">
        <i class="fas fa-house-user" style="color:#0e7490"></i> Kunjungan Rumah Siswa
        <div style="margin-left:auto;display:flex;gap:8px">
            <button onclick="window.print()" class="btn-soft no-print"><i class="fas fa-print"></i> Cetak</button>
            <button class="btn-filter no-print" onclick="document.getElementById('modalKunjungan').classList.add('show')">
                <i class="fas fa-plus"></i> Tambah Kunjungan
            </button>
        </div>
    </div>
    <div class="card-body no-print" style="padding-bottom:0">
        <form method="GET" class="filter-row">
            <input type="hidden" name="page" value="kunjungan">
            <div style="flex:1;min-width:180px">
                <label>Siswa / Nama</label>
                <input type="text" name="q" value="<?= htmlspecialchars($q_k) ?>" placeholder="Cari nama siswa / ortu...">
            </div>
            <div>
                <label>Kelas</label>
                <select name="kelas">
                    <option value="">-- Semua Kelas --</option>
                    <?php foreach ($kelas_list as $k): ?>
                    <option value="<?= $k ?>" <?= $kelas_k===$k?'selected':'' ?>><?= htmlspecialchars($k) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><button type="submit" class="btn-filter"><i class="fas fa-search"></i> Cari</button></div>
        </form>
    </div>
    <div class="tbl-wrap" style="margin-top:14px">
        <table>
            <thead><tr>
                <th style="width:35px">No</th><th>Tanggal</th><th class="th-left">Nama Siswa</th><th>Kelas</th>
                <th class="th-left">Orang Tua</th><th>Penyelesaian</th><th class="th-left">Keterangan</th><th>Foto</th><th class="no-print">Aksi</th>
            </tr></thead>
            <tbody>
            <?php if ($list_k->num_rows===0): ?>
            <tr><td colspan="9" style="text-align:center;padding:30px;color:#94a3b8">Belum ada data kunjungan</td></tr>
            <?php else: $no=0; while ($r=$list_k->fetch_assoc()): $no++;
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
                    <?php if ($r['foto'] && file_exists('uploads/kunjungan/'.$r['foto'])): ?>
                    <a href="<?= BASE_URL ?>uploads/kunjungan/<?= $r['foto'] ?>" target="_blank"><i class="fas fa-image" style="color:#0e7490"></i></a>
                    <?php else: ?><span style="color:#cbd5e1">—</span><?php endif; ?>
                </td>
                <td class="no-print" style="text-align:center">
                    <a href="portal_bk.php?page=kunjungan&hapus_kunjungan=<?= $r['id'] ?>" onclick="return confirm('Hapus data kunjungan ini?')" class="btn-red" style="text-decoration:none;display:inline-block"><i class="fas fa-trash"></i></a>
                </td>
            </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Kunjungan -->
<div class="modal-overlay" id="modalKunjungan">
    <div class="modal-box">
        <div class="modal-head"><i class="fas fa-house-user"></i> Kunjungan Rumah Siswa <button class="x" onclick="document.getElementById('modalKunjungan').classList.remove('show')">&times;</button></div>
        <form method="POST" enctype="multipart/form-data">
        <div class="modal-body">
            <label>Filter Kelas</label>
            <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:4px" id="kKelasPills">
                <button type="button" class="kelas-pill active" data-kelas="" onclick="filterSiswaKunjungan(this)">Semua</button>
                <?php foreach ($kelas_list as $k): ?>
                <button type="button" class="kelas-pill" data-kelas="<?= htmlspecialchars($k) ?>" onclick="filterSiswaKunjungan(this)"><?= htmlspecialchars($k) ?></button>
                <?php endforeach; ?>
            </div>

            <label>Siswa</label>
            <select name="siswa_id" id="kSiswaId" required>
                <option value="">-- Pilih Siswa --</option>
                <?php foreach ($siswa_arr as $s): ?>
                <option value="<?= $s['id'] ?>" data-kelas="<?= htmlspecialchars($s['kelas']) ?>">[<?= htmlspecialchars($s['kelas']) ?>] <?= htmlspecialchars($s['nama']) ?></option>
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
            <button type="button" class="btn-soft" onclick="document.getElementById('modalKunjungan').classList.remove('show')">Batal</button>
            <button type="submit" name="tambah_kunjungan" value="1" class="btn-filter"><i class="fas fa-save"></i> Simpan</button>
        </div>
        </form>
    </div>
</div>
<script>
function filterSiswaKunjungan(btn){
    document.querySelectorAll('#kKelasPills .kelas-pill').forEach(function(b){ b.classList.remove('active'); });
    btn.classList.add('active');
    var kelas = btn.dataset.kelas;
    var sel = document.getElementById('kSiswaId');
    sel.value = '';
    Array.from(sel.options).forEach(function(opt){
        if (!opt.value){ opt.style.display=''; return; }
        opt.style.display = (!kelas || opt.dataset.kelas === kelas) ? '' : 'none';
    });
}
</script>

<?php /* ═══════════════════════ 5. DATA SISWA ═══════════════════════ */
elseif ($page === 'data_siswa'):
    $kelas_d = sanitize($_GET['kelas'] ?? ($kelas_list[0] ?? ''));
    $cond_d  = $kelas_d ? "WHERE kelas='$kelas_d'" : '';
    $list_d  = $conn->query("SELECT * FROM siswa $cond_d ORDER BY nama");
?>
<?php render_kelas_pills($kelas_list, 'data_siswa', $kelas_d); ?>
<div class="card">
    <div class="card-header"><i class="fas fa-user-graduate" style="color:#0e7490"></i> Data Siswa</div>
    <div class="card-body" style="padding-bottom:0">
        <form method="GET" class="filter-row">
            <input type="hidden" name="page" value="data_siswa">
            <div>
                <label>Kelas</label>
                <select name="kelas" onchange="this.form.submit()">
                    <option value="">-- Semua Kelas --</option>
                    <?php foreach ($kelas_list as $k): ?>
                    <option value="<?= $k ?>" <?= $kelas_d===$k?'selected':'' ?>><?= htmlspecialchars($k) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
    <div class="tbl-wrap" style="margin-top:14px">
        <table>
            <thead><tr><th style="width:35px">#</th><th class="th-left">NIS</th><th class="th-left">Nama</th><th>Kelas</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php if ($list_d->num_rows===0): ?>
            <tr><td colspan="5" style="text-align:center;padding:30px;color:#94a3b8">Tidak ada siswa</td></tr>
            <?php else: $no=0; while ($s=$list_d->fetch_assoc()): $no++; ?>
            <tr>
                <td style="text-align:center"><?= $no ?></td>
                <td style="font-family:monospace"><?= htmlspecialchars($s['nis']) ?></td>
                <td style="font-weight:600"><?= htmlspecialchars($s['nama']) ?></td>
                <td style="text-align:center"><?= htmlspecialchars($s['kelas']) ?></td>
                <td style="text-align:center">
                    <button class="btn-soft" onclick="bukaRiwayat(<?= $s['id'] ?>)"><i class="fas fa-list"></i> Riwayat</button>
                </td>
            </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Riwayat Siswa -->
<div class="modal-overlay" id="modalRiwayat">
    <div class="modal-box wide" style="max-width:900px">
        <div class="modal-head">
            <i class="fas fa-user"></i> <span id="rwNama">Memuat...</span>
            <div style="margin-left:auto;display:flex;gap:8px;align-items:center">
                <button class="btn-soft no-print" onclick="cetakRekapSiswa()" style="font-size:.78rem;padding:6px 12px"><i class="fas fa-print"></i> Cetak Rekap</button>
                <button class="x" onclick="document.getElementById('modalRiwayat').classList.remove('show')">&times;</button>
            </div>
        </div>
        <!-- Stats mini -->
        <div id="rwStats" style="display:grid;grid-template-columns:repeat(8,1fr);gap:6px;padding:12px 18px;border-bottom:1px solid #f1f5f9;background:#f8fafc"></div>
        <!-- Two-column body -->
        <div style="display:grid;grid-template-columns:1fr 300px;min-height:0">
            <!-- LEFT: Riwayat timeline -->
            <div style="padding:16px 18px;border-right:1px solid #f1f5f9">
                <div style="font-weight:700;font-size:.82rem;color:#1e293b;margin-bottom:12px;display:flex;justify-content:space-between;align-items:center">
                    <span><i class="fas fa-history" style="color:#0e7490;margin-right:5px"></i>Riwayat Pelanggaran, Alpa &amp; Terlambat</span>
                    <span id="rwCount" style="font-size:.72rem;color:#94a3b8;font-weight:400"></span>
                </div>
                <div class="rw-tabs">
                    <button type="button" class="rw-tab active" data-rwtab="baru" onclick="rwSetTab('baru')">Pelanggaran Baru <span class="cnt" id="rwTabCntBaru">0</span></button>
                    <button type="button" class="rw-tab" data-rwtab="diperbaiki" onclick="rwSetTab('diperbaiki')">Sudah Diperbaiki <span class="cnt" id="rwTabCntDiperbaiki">0</span></button>
                </div>
                <div id="rwBody" style="max-height:380px;overflow-y:auto;padding-right:4px">
                    <div style="text-align:center;padding:30px;color:#94a3b8"><i class="fas fa-spinner fa-spin"></i> Memuat data...</div>
                </div>
            </div>
            <!-- RIGHT: Point Perbaikan -->
            <div style="padding:16px 18px;background:linear-gradient(180deg,#faf5ff,#fff);border-left:1px solid #ede9fe">
                <div style="font-weight:800;font-size:.82rem;color:#7c3aed;margin-bottom:12px;display:flex;align-items:center;gap:6px">
                    <i class="fas fa-arrow-trend-up"></i> Input Point Perbaikan
                </div>
                <!-- Ringkasan per kategori -->
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:6px;margin-bottom:14px" id="rwPerbSummary">
                    <div style="background:#f5f3ff;border-radius:8px;padding:8px;text-align:center;border:1px solid #ede9fe">
                        <div style="font-size:.6rem;font-weight:700;color:#64748b;text-transform:uppercase">Sisa Terlambat</div>
                        <div style="font-size:1rem;font-weight:900;color:#7c3aed" id="rwpv-TERLAMBAT">0</div>
                    </div>
                    <div style="background:#f5f3ff;border-radius:8px;padding:8px;text-align:center;border:1px solid #ede9fe">
                        <div style="font-size:.6rem;font-weight:700;color:#64748b;text-transform:uppercase">Sisa Alpa</div>
                        <div style="font-size:1rem;font-weight:900;color:#7c3aed" id="rwpv-ALPA">0</div>
                    </div>
                    <div style="background:#f5f3ff;border-radius:8px;padding:8px;text-align:center;border:1px solid #ede9fe">
                        <div style="font-size:.6rem;font-weight:700;color:#64748b;text-transform:uppercase">P. Disiplin</div>
                        <div style="font-size:1rem;font-weight:900;color:#7c3aed" id="rwpv-PELANGGARAN">0</div>
                    </div>
                    <div style="background:#f5f3ff;border-radius:8px;padding:8px;text-align:center;border:1px solid #ede9fe">
                        <div style="font-size:.6rem;font-weight:700;color:#64748b;text-transform:uppercase">Kunjungan</div>
                        <div style="font-size:1rem;font-weight:900;color:#7c3aed" id="rwpv-KUNJUNGAN">0</div>
                    </div>
                </div>
                <!-- Form perbaikan -->
                <div style="background:#f5f3ff;border:1px solid #ede9fe;border-radius:10px;padding:12px;margin-bottom:12px">
                    <div style="margin-bottom:8px">
                        <label style="font-size:.68rem;font-weight:700;color:#64748b;display:block;margin-bottom:4px;text-transform:uppercase">Kategori Perbaikan</label>
                        <select id="rwfKategori" style="width:100%;padding:7px 10px;border:1px solid #ddd6fe;border-radius:7px;font-size:.83rem;background:#fff;color:#1e293b;outline:none">
                            <option value="">-- Pilih Kategori Perbaikan --</option>
                            <option value="TERLAMBAT">1. Terlambat</option>
                            <option value="ALPA">2. Alpa</option>
                            <option value="PELANGGARAN">3. Pelanggaran Disiplin</option>
                            <option value="KUNJUNGAN">4. Kunjungan</option>
                        </select>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:8px">
                        <div>
                            <label style="font-size:.68rem;font-weight:700;color:#64748b;display:block;margin-bottom:4px;text-transform:uppercase">Jumlah</label>
                            <input type="number" id="rwfJumlah" min="1" max="100" value="1" style="width:100%;padding:7px 10px;border:1px solid #ddd6fe;border-radius:7px;font-size:.83rem;background:#fff;color:#1e293b;outline:none">
                        </div>
                        <div>
                            <label style="font-size:.68rem;font-weight:700;color:#64748b;display:block;margin-bottom:4px;text-transform:uppercase">Tanggal</label>
                            <input type="date" id="rwfTanggal" value="<?= date('Y-m-d') ?>" style="width:100%;padding:7px 10px;border:1px solid #ddd6fe;border-radius:7px;font-size:.83rem;background:#fff;color:#1e293b;outline:none">
                        </div>
                    </div>
                    <div style="margin-bottom:10px">
                        <label style="font-size:.68rem;font-weight:700;color:#64748b;display:block;margin-bottom:4px;text-transform:uppercase">Keterangan (opsional)</label>
                        <input type="text" id="rwfKeterangan" placeholder="Misal: sudah membuat surat pernyataan..." style="width:100%;padding:7px 10px;border:1px solid #ddd6fe;border-radius:7px;font-size:.83rem;background:#fff;color:#1e293b;outline:none">
                    </div>
                    <button onclick="rwSimpanPerbaikan()" style="width:100%;padding:9px;background:linear-gradient(135deg,#7c3aed,#5b21b6);border:none;border-radius:8px;color:white;font-weight:700;cursor:pointer;font-size:.82rem;display:flex;align-items:center;justify-content:center;gap:6px">
                        <i class="fas fa-save"></i> Simpan Point Perbaikan
                    </button>
                </div>
                <!-- Log riwayat perbaikan -->
                <div style="font-size:.65rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px">Riwayat Perbaikan</div>
                <div id="rwPerbLog" style="max-height:150px;overflow-y:auto">
                    <div style="text-align:center;color:#94a3b8;font-size:.75rem;padding:10px">Memuat...</div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php /* ═══════════════════════ 6. BUKU KEJADIAN ═══════════════════════ */
elseif ($page === 'buku_kejadian'):
    $q_bk     = sanitize($_GET['q'] ?? '');
    $kelas_bk = sanitize($_GET['kelas'] ?? ($kelas_list[0] ?? ''));
    $cond_bk  = "1=1";
    if ($q_bk)     $cond_bk .= " AND nama_siswa LIKE '%$q_bk%'";
    if ($kelas_bk) $cond_bk .= " AND kelas='$kelas_bk'";
    $cond_bk .= periode_where($conn);
    $list_bk = $conn->query("SELECT * FROM buku_kejadian WHERE $cond_bk ORDER BY tanggal DESC");
    $rows_bk = [];
    while ($r=$list_bk->fetch_assoc()) $rows_bk[] = $r;
    $siswa_opt2 = $conn->query("SELECT id,nis,nama,kelas FROM siswa ORDER BY nama");
    $siswa_arr2 = [];
    while ($s=$siswa_opt2->fetch_assoc()) $siswa_arr2[] = $s;
    $edit_bk = null;
    if (isset($_GET['edit'])) $edit_bk = $conn->query("SELECT * FROM buku_kejadian WHERE id=".(int)$_GET['edit'])->fetch_assoc();
?>
<?php
// Data pengaturan untuk print header
$nm_sek = htmlspecialchars($pengaturan['nama_sekolah'] ?? '');
$hari_cetak_arr = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
$tgl_cetak = $hari_cetak_arr[date('w')].', '.date('j').' '.$nama_bulan[(int)date('n')].' '.date('Y');
?>

<!-- ═══ DOKUMEN CETAK: BUKU KEJADIAN (hanya tampil saat print) ═══ -->
<div class="cetak-dokumen" id="cdk-buku-kejadian">
<div class="cdk-wrap">
    <div class="cdk-judul">BUKU KEJADIAN</div>
    <div class="cdk-sub"><?= $nm_sek ?> &mdash; Dicetak: <?= date('j/n/Y, H.i.s') ?></div>
    <table class="cdk-tbl">
        <thead>
            <tr>
                <th width="28">No</th>
                <th width="90">Hari/Tanggal</th>
                <th>Nama Siswa</th>
                <th width="60">Kelas</th>
                <th>Uraian Kejadian</th>
                <th width="36">Poin</th>
                <th width="140">Tanggapan Siswa</th>
                <th width="110">Arahan Guru Wali</th>
                <th width="130">Tindak Lanjut</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($rows_bk)): ?>
            <tr><td colspan="9" style="text-align:center;padding:16px">Tidak ada data</td></tr>
        <?php else: $no=0; foreach ($rows_bk as $r): $no++; ?>
            <tr>
                <td class="tc"><?= $no ?></td>
                <td class="tc"><?= $r['hari'] ?><br><?= $r['tanggal'] ?></td>
                <td><strong><?= htmlspecialchars($r['nama_siswa']) ?></strong></td>
                <td class="tc"><?= htmlspecialchars($r['kelas']) ?></td>
                <td><?= htmlspecialchars($r['uraian_kejadian']) ?></td>
                <td class="tc"><?= $r['poin'] ?></td>
                <td><?= htmlspecialchars($r['tanggapan_siswa'] ?: '') ?></td>
                <td><?= htmlspecialchars($r['arahan_guru_wali'] ?: '') ?></td>
                <td><?= htmlspecialchars($r['tindak_lanjut'] ?: '') ?></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    <div class="cdk-ttd">
        <div class="cdk-ttd-box">
            Mengetahui,<br>Kepala Sekolah
            <div class="cdk-ttd-line"><?= htmlspecialchars($pengaturan['kepala_sekolah'] ?? '') ?></div>
        </div>
        <div class="cdk-ttd-box">
            <?= $tgl_cetak ?><br>Guru BK
            <div class="cdk-ttd-line"><?= htmlspecialchars($bk_nama) ?></div>
        </div>
    </div>
</div>
</div>
<!-- ═══ AKHIR DOKUMEN CETAK BUKU KEJADIAN ═══ -->

<?php render_kelas_pills($kelas_list, 'buku_kejadian', $kelas_bk); ?>
<div class="card">
    <div class="card-header no-print">
        <i class="fas fa-book" style="color:#0e7490"></i> Buku Kejadian
        <?php if ($kelas_bk): ?><span style="font-size:.78rem;font-weight:400;margin-left:4px;color:#64748b">— Kelas <strong><?= htmlspecialchars($kelas_bk) ?></strong></span><?php endif; ?>
        <div style="margin-left:auto;display:flex;gap:8px">
            <button onclick="window.print()" class="btn-soft"><i class="fas fa-print"></i> Cetak</button>
            <button class="btn-filter" onclick="bukaModalKejadian()"><i class="fas fa-plus"></i> Tambah Kejadian</button>
        </div>
    </div>
    <div class="card-body no-print" style="padding-bottom:0">
        <form method="GET" class="filter-row">
            <input type="hidden" name="page" value="buku_kejadian">
            <div style="flex:1;min-width:180px"><label>Siswa / Nama</label><input type="text" name="q" value="<?= htmlspecialchars($q_bk) ?>" placeholder="Cari nama siswa..."></div>
            <div>
                <label>Kelas</label>
                <select name="kelas">
                    <option value="">-- Semua Kelas --</option>
                    <?php foreach ($kelas_list as $k): ?>
                    <option value="<?= $k ?>" <?= $kelas_bk===$k?'selected':'' ?>><?= htmlspecialchars($k) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><button type="submit" class="btn-filter"><i class="fas fa-search"></i> Cari</button></div>
        </form>
    </div>
    <div class="tbl-wrap" style="margin-top:14px">
        <table>
            <thead><tr>
                <th style="width:32px">No</th><th>Hari/Tanggal</th><th class="th-left">Nama Siswa</th><th>Kelas</th>
                <th class="th-left">Uraian Kejadian</th><th>Poin</th><th class="th-left">Tanggapan Siswa</th>
                <th class="th-left">Arahan Guru Wali</th><th class="th-left">Tindak Lanjut</th><th class="no-print">Aksi</th>
            </tr></thead>
            <tbody>
            <?php if (empty($rows_bk)): ?>
            <tr><td colspan="10" style="text-align:center;padding:30px;color:#94a3b8">Belum ada data</td></tr>
            <?php else: $no=0; foreach ($rows_bk as $r): $no++; ?>
            <tr>
                <td style="text-align:center"><?= $no ?></td>
                <td style="text-align:center;white-space:nowrap"><?= $r['hari'] ?><br><span style="font-size:.72rem;color:#64748b"><?= $r['tanggal'] ?></span></td>
                <td style="font-weight:600"><?= htmlspecialchars($r['nama_siswa']) ?></td>
                <td style="text-align:center"><?= htmlspecialchars($r['kelas']) ?></td>
                <td style="font-size:.8rem"><?= htmlspecialchars($r['uraian_kejadian']) ?></td>
                <td style="text-align:center"><?= $r['poin'] ?></td>
                <td style="font-size:.78rem"><?= htmlspecialchars($r['tanggapan_siswa'] ?: '-') ?></td>
                <td style="font-size:.78rem"><?= htmlspecialchars($r['arahan_guru_wali'] ?: '-') ?></td>
                <td style="font-size:.78rem"><?= htmlspecialchars($r['tindak_lanjut'] ?: '-') ?></td>
                <td class="no-print" style="text-align:center;white-space:nowrap">
                    <a href="portal_bk.php?page=buku_kejadian&edit=<?= $r['id'] ?>&kelas=<?= urlencode($kelas_bk) ?>" class="btn-soft" style="padding:5px 8px;text-decoration:none"><i class="fas fa-pen"></i></a>
                    <a href="portal_bk.php?page=buku_kejadian&hapus_kejadian=<?= $r['id'] ?>&kelas=<?= urlencode($kelas_bk) ?>" onclick="return confirm('Hapus catatan kejadian ini?')" class="btn-red" style="text-decoration:none;display:inline-block"><i class="fas fa-trash"></i></a>
                </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah/Edit Kejadian -->
<div class="modal-overlay <?= $edit_bk?'show':'' ?>" id="modalKejadian">
    <div class="modal-box">
        <div class="modal-head"><i class="fas fa-book"></i> <?= $edit_bk?'Edit':'Tambah' ?> Buku Kejadian <button class="x" onclick="document.getElementById('modalKejadian').classList.remove('show')">&times;</button></div>
        <form method="POST" enctype="multipart/form-data">
        <div class="modal-body">
            <input type="hidden" name="kejadian_id" value="<?= $edit_bk['id'] ?? '' ?>">
            <input type="hidden" name="kelas_active" value="<?= htmlspecialchars($kelas_bk) ?>">

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                <div>
                    <label>Tanggal</label>
                    <input type="date" name="tanggal" id="bkTanggal" value="<?= $edit_bk['tanggal'] ?? $today ?>" required onchange="isiHariBk(this.value)">
                </div>
                <div>
                    <label>Hari</label>
                    <input type="text" id="bkHari" readonly value="<?= $edit_bk ? $edit_bk['hari'] : date('l') ?>" style="background:#f8fafc;color:#475569;font-weight:600">
                </div>
            </div>

            <label>Kelas</label>
            <select id="bkKelasFilter" onchange="filterSiswaKejadian()">
                <option value="">-- Pilih Kelas --</option>
                <?php foreach ($kelas_list as $k): ?>
                <option value="<?= htmlspecialchars($k) ?>" <?= (!empty($edit_bk) && $edit_bk['kelas']===$k)?'selected':'' ?><?= (!$edit_bk && $kelas_bk===$k)?'selected':'' ?>><?= htmlspecialchars($k) ?></option>
                <?php endforeach; ?>
            </select>

            <label>Nama Siswa</label>
            <select name="siswa_id" id="bkSiswaId" required>
                <?php if (!empty($edit_bk)): ?>
                <option value="<?= $edit_bk['siswa_id'] ?>" selected><?= htmlspecialchars($edit_bk['nama_siswa']) ?></option>
                <?php else: ?>
                <option value="">-- Pilih Siswa --</option>
                <?php endif; ?>
            </select>

            <label>Uraian Kejadian</label>
            <textarea name="uraian_kejadian" rows="3" placeholder="Uraikan kejadian yang terjadi..." required><?= htmlspecialchars($edit_bk['uraian_kejadian'] ?? '') ?></textarea>

            <label>Poin Pelanggaran / Kejadian</label>
            <input type="number" name="poin" value="<?= $edit_bk['poin'] ?? 0 ?>" min="0">

            <label>Tanggapan Siswa</label>
            <textarea name="tanggapan_siswa" rows="2" placeholder="Tanggapan siswa terkait kejadian..."><?= htmlspecialchars($edit_bk['tanggapan_siswa'] ?? '') ?></textarea>

            <label>Arahan Guru Wali</label>
            <textarea name="arahan_guru_wali" rows="2" placeholder="Arahan dari guru wali kelas..."><?= htmlspecialchars($edit_bk['arahan_guru_wali'] ?? '') ?></textarea>

            <label>Tindak Lanjut</label>
            <textarea name="tindak_lanjut" rows="2" placeholder="Tindak lanjut yang dilakukan..."><?= htmlspecialchars($edit_bk['tindak_lanjut'] ?? '') ?></textarea>

            <label>TTD <span style="font-size:.72rem;font-weight:400;color:#64748b">(opsional)</span></label>
            <input type="file" name="ttd" accept="image/*">
            <?php if (!empty($edit_bk['ttd']) && file_exists('uploads/kunjungan/'.$edit_bk['ttd'])): ?>
            <div style="margin-top:6px"><img src="<?= BASE_URL ?>uploads/kunjungan/<?= $edit_bk['ttd'] ?>" style="height:48px;border-radius:6px;border:1px solid #e2e8f0"></div>
            <?php endif; ?>
        </div>
        <div class="modal-foot">
            <button type="button" class="btn-soft" onclick="document.getElementById('modalKejadian').classList.remove('show')">Batal</button>
            <button type="submit" name="simpan_kejadian" value="1" class="btn-filter"><i class="fas fa-save"></i> Simpan</button>
        </div>
        </form>
    </div>
</div>
<script>
var siswaDataKejadian = <?= json_encode($siswa_arr2) ?>;
var hariId = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
function isiHariBk(tgl){
    if(!tgl) return;
    var d = new Date(tgl+'T00:00:00');
    document.getElementById('bkHari').value = hariId[d.getDay()];
}
function bukaModalKejadian(){
    var m = document.getElementById('modalKejadian');
    m.classList.add('show');
    // Set kelas aktif saat ini
    var sel = document.getElementById('bkKelasFilter');
    if (sel.value) filterSiswaKejadian();
}
function filterSiswaKejadian(selectedId){
    var kelas = document.getElementById('bkKelasFilter').value;
    var sel = document.getElementById('bkSiswaId');
    sel.innerHTML = '<option value="">-- Pilih Siswa --</option>';
    siswaDataKejadian.filter(function(s){ return !kelas || s.kelas === kelas; }).forEach(function(s){
        var opt = document.createElement('option');
        opt.value = s.id;
        opt.textContent = (kelas ? '' : '['+s.kelas+'] ') + s.nama;
        if (selectedId && String(s.id) === String(selectedId)) opt.selected = true;
        sel.appendChild(opt);
    });
}
// Init saat load
(function(){
    var tgl = document.getElementById('bkTanggal');
    if(tgl && tgl.value) isiHariBk(tgl.value);
    filterSiswaKejadian(<?= !empty($edit_bk) ? (int)$edit_bk['siswa_id'] : 'null' ?>);
})();
</script>

<?php endif; ?>
</div><!-- /content -->
</div><!-- /main -->

<script>
// Jam realtime
function updateJam(){
    var n=new Date();
    var h=String(n.getHours()).padStart(2,'0');
    var m=String(n.getMinutes()).padStart(2,'0');
    var s=String(n.getSeconds()).padStart(2,'0');
    var el=document.getElementById('jam');
    if(el) el.textContent=h+':'+m+':'+s;
}
updateJam(); setInterval(updateJam,1000);

// Hamburger
function toggleSidebar(){
    var sb=document.getElementById('sidebar');
    var ov=document.getElementById('sidebarOverlay');
    var btn=document.getElementById('hamburgerBtn');
    var open=sb.classList.toggle('open');
    ov.classList.toggle('show',open);
    btn.classList.toggle('open',open);
    document.body.style.overflow=open?'hidden':'';
}
function closeSidebar(){
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('show');
    document.getElementById('hamburgerBtn').classList.remove('open');
    document.body.style.overflow='';
}
document.querySelectorAll('.nav-item').forEach(function(el){
    el.addEventListener('click',function(){ if(window.innerWidth<=768) closeSidebar(); });
});
window.addEventListener('resize',function(){ if(window.innerWidth>768) closeSidebar(); });

// Search filter tabel
function srFilter(tblId, q){
    q=q.toLowerCase();
    var rows=document.getElementById(tblId).querySelectorAll('tbody tr');
    rows.forEach(function(r){
        r.style.display=r.textContent.toLowerCase().includes(q)?'':'none';
    });
}

// Toggle Periode Rekap Absensi (Bulanan/Mingguan)
document.querySelectorAll('.periode-btn').forEach(function(btn){
    btn.addEventListener('click', function(){
        document.querySelectorAll('.periode-btn').forEach(b=>b.classList.remove('active'));
        this.classList.add('active');
        var p = this.dataset.p;
        document.getElementById('periodeInput').value = p;
        document.querySelectorAll('.blok-bulanan').forEach(el=>el.style.display = p==='bulanan' ? '' : 'none');
        document.querySelectorAll('.blok-mingguan').forEach(el=>el.style.display = p==='mingguan' ? '' : 'none');
    });
});

// ── STATE untuk modal riwayat ──────────────────────────────────────────────
var rwCurrentSiswaId = null;
var rwCurrentSiswa   = null;
var rwCurrentStat    = null;
var rwNamaSek        = <?= json_encode($pengaturan['nama_sekolah'] ?? 'Sekolah') ?>;
var rwBkNama         = <?= json_encode($bk_nama) ?>;
var rwTimelineData   = [];   // hasil timeline yang sudah diberi flag _sudah, disimpan supaya tab bisa filter tanpa fetch ulang
var rwCurrentTab      = 'baru'; // baru | diperbaiki

// Modal riwayat siswa (Data Siswa) — buka & muat data
function bukaRiwayat(id, keepTab){
    rwCurrentSiswaId = id;
    rwCurrentSiswa   = null;
    rwTimelineData   = [];
    if (!keepTab) { rwSetTab('baru', true); }
    var modal = document.getElementById('modalRiwayat');
    modal.classList.add('show');
    document.getElementById('rwNama').textContent = 'Memuat...';
    document.getElementById('rwBody').innerHTML = '<div style="text-align:center;padding:30px;color:#94a3b8"><i class="fas fa-spinner fa-spin"></i> Memuat data...</div>';
    document.getElementById('rwStats').innerHTML = '';
    document.getElementById('rwCount').textContent = '';
    document.getElementById('rwPerbLog').innerHTML = '<div style="text-align:center;color:#94a3b8;font-size:.75rem;padding:10px">Memuat...</div>';
    ['TERLAMBAT','ALPA','PELANGGARAN','KUNJUNGAN'].forEach(function(k){
        var el = document.getElementById('rwpv-'+k); if(el) el.textContent='0';
    });

    fetch('portal_bk.php?ajax=riwayat&id='+id)
        .then(r=>r.json())
        .then(function(d){
            if(!d.ok){ document.getElementById('rwBody').innerHTML='<div style="padding:20px;color:#dc2626">'+(d.msg||'Error')+'</div>'; return; }
            rwCurrentSiswa = d.siswa;
            rwCurrentStat  = d.stat;
            document.getElementById('rwNama').textContent = d.siswa.nama;

            // Stats bar
            var statColors = {Hadir:'#16a34a',Terlambat:'#d97706',Alpa:'#dc2626',Izin:'#7c3aed',Sakit:'#2563eb',Pelanggaran:'#dc2626',Kunjungan:'#16a34a',TotalPoin:'#0e7490'};
            var statLabels = {Hadir:'Hadir',Terlambat:'Terlambat',Alpa:'Alpa',Izin:'Izin',Sakit:'Sakit',Pelanggaran:'Pelanggaran Disiplin',Kunjungan:'Kunjungan',TotalPoin:'Total Poin'};
            var statPoinCfg = d.poinCfg || {alpa:0, terlambat:0};
            // Poin per kejadian untuk Terlambat & Alpa (dari Kelola Pelanggaran → Penentuan Point Alpa & Terlambat),
            // ditampilkan sebagai sub-label kecil di kartu statistik supaya langsung kelihatan berapa poin per kejadiannya.
            var statPoinSub = {Terlambat:statPoinCfg.terlambat, Alpa:statPoinCfg.alpa};
            var sHtml = '';
            Object.keys(statLabels).forEach(function(k){
                var poinSub = statPoinSub.hasOwnProperty(k)
                    ? '<div style="font-size:.62rem;color:#94a3b8;font-weight:700;margin-top:1px">+'+statPoinSub[k]+' Poin/kejadian</div>'
                    : '';
                sHtml += '<div style="background:#f8fafc;border-radius:8px;padding:6px;text-align:center;border:1px solid #f1f5f9">'
                    + '<div style="font-size:1.2rem;font-weight:800;color:'+statColors[k]+'">'+d.stat[k]+'</div>'
                    + '<div style="font-size:.6rem;color:#64748b;font-weight:600;text-transform:uppercase">'+statLabels[k]+'</div>'
                    + poinSub
                    + '</div>';
            });
            document.getElementById('rwStats').innerHTML = sHtml;

            // Point perbaikan chips
            // "Sisa Terlambat/Alpa" sekarang dalam satuan POIN (bukan jumlah kejadian):
            // total poin = jumlah kejadian absensi × poin per kejadian (dari pengaturan Kelola
            // Pelanggaran → Penentuan Point Alpa & Terlambat), dikurangi total Point Perbaikan (poin)
            // yang sudah diinput untuk kategori itu — supaya makin sering input perbaikan, sisa poinnya BERKURANG.
            // Stat card di atas (rwStats) tetap murni angka mentah dari absensi (tidak dikurangi),
            // supaya absen manual baru dari admin selalu otomatis kehitung.
            var perb    = d.perbaikan || {};
            var poinCfg = d.poinCfg || {alpa:0, terlambat:0};
            var poinTerlambatTotal = (d.stat.Terlambat||0) * (poinCfg.terlambat||0);
            var poinAlpaTotal      = (d.stat.Alpa||0) * (poinCfg.alpa||0);
            var elT = document.getElementById('rwpv-TERLAMBAT'); if (elT) elT.textContent = Math.max(0, poinTerlambatTotal - (perb['TERLAMBAT']||0));
            var elA = document.getElementById('rwpv-ALPA');      if (elA) elA.textContent = Math.max(0, poinAlpaTotal - (perb['ALPA']||0));
            var elP = document.getElementById('rwpv-PELANGGARAN'); if (elP) elP.textContent = perb['PELANGGARAN']||0;
            var elK = document.getElementById('rwpv-KUNJUNGAN');    if (elK) elK.textContent = perb['KUNJUNGAN']||0;

            // Perbaikan log
            rwRenderPerbLog(d.perbaikanLog||[]);

            // Riwayat timeline
            var badgeMap = {'Pelanggaran Disiplin':'b-pelanggaran','Terlambat':'b-terlambat','Alpa':'b-alpa'};
            document.getElementById('rwCount').textContent = d.timeline.length+' aktifitas tercatat';
            // Tandai entri yang sudah "tertutup" oleh point perbaikan (dihitung dari yang paling lama)
            // sebagai "Sudah Diperbaiki". Entri yang belum tertutup tetap tampil apa adanya (belum diperbaiki).
            // Terlambat & Alpa sekarang dihitung poin-weighted, sama seperti Pelanggaran Disiplin:
            // setiap entri punya nilai poin sendiri (dari ket, mis. "+2 Poin") dan sisa poin perbaikan
            // dikurangi berdasarkan nilai poin entri tsb, bukan sekadar dihitung per-1 kejadian.
            var sisaT = perb['TERLAMBAT']||0, sisaA = perb['ALPA']||0, sisaP = perb['PELANGGARAN']||0;
            var asc = d.timeline.slice().sort(function(a,b){ return new Date(a.tgl) - new Date(b.tgl); });
            asc.forEach(function(t){
                if (t.tipe==='Terlambat' && sisaT>0) {
                    var poinEntry = parseInt((t.ket||'').replace(/[^0-9]/g,''))||0;
                    t._sudah = true; sisaT -= poinEntry;
                }
                else if (t.tipe==='Alpa' && sisaA>0) {
                    var poinEntry = parseInt((t.ket||'').replace(/[^0-9]/g,''))||0;
                    t._sudah = true; sisaA -= poinEntry;
                }
                else if (t.tipe==='Pelanggaran Disiplin' && sisaP>0) {
                    var poinEntry = parseInt((t.ket||'').replace(/[^0-9]/g,''))||0;
                    t._sudah = true; sisaP -= poinEntry;
                }
            });
            rwTimelineData = d.timeline;
            rwBadgeMap = badgeMap;
            rwRenderTimeline();
        })
        .catch(function(){ document.getElementById('rwBody').innerHTML = '<div style="padding:20px;color:#dc2626">Gagal memuat data</div>'; });
}

var rwBadgeMap = {};

// Ganti tab aktif (Semua / Pelanggaran Baru / Sudah Diperbaiki) lalu render ulang list dari data yang sudah dimuat.
function rwSetTab(tab, silent){
    rwCurrentTab = tab;
    document.querySelectorAll('.rw-tab').forEach(function(btn){
        btn.classList.toggle('active', btn.getAttribute('data-rwtab')===tab);
    });
    if (!silent) rwRenderTimeline();
}

// Render ulang daftar riwayat sesuai tab yang sedang dipilih, tanpa fetch ulang ke server.
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
        var emptyMsg = rwCurrentTab==='baru' ? 'Tidak ada pelanggaran baru' : 'Belum ada yang diperbaiki';
        html = '<div style="color:#94a3b8;padding:20px 0;text-align:center;font-size:.82rem">'+emptyMsg+'</div>';
    } else {
        list.forEach(function(t){
            var cls = rwBadgeMap[t.tipe] || '';
            var canHapus = t.id && t.src;
            html += '<div class="tl-item" style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px">';
            html += '<div style="min-width:0;flex:1">';
            html += '<div class="tl-date">'+escRwH(t.tgl)+' &nbsp; <span class="badge '+cls+'">'+escRwH(t.tipe)+'</span>'
                + (t._sudah ? ' <span class="badge" style="background:#dcfce7;color:#15803d"><i class="fas fa-check"></i> Sudah Diperbaiki</span>' : '')
                + '</div>';
            html += '<div class="tl-title">'+(t.judul ? escRwH(t.judul) : '')+'</div>';
            if (t.ket) html += '<div class="tl-ket">'+escRwH(t.ket)+'</div>';
            html += '</div>';
            if (canHapus) {
                html += '<button onclick="rwHapusLog(\''+t.src+'\','+t.id+')" title="Hapus log ini" '
                    + 'style="flex-shrink:0;background:#fef2f2;border:1px solid #fecaca;color:#dc2626;border-radius:7px;padding:5px 8px;cursor:pointer;font-size:.7rem;display:flex;align-items:center;gap:4px">'
                    + '<i class="fas fa-trash"></i> Hapus Log</button>';
            }
            html += '</div>';
        });
    }
    document.getElementById('rwBody').innerHTML = html;
}

// Hapus satu entri log riwayat (Pelanggaran Disiplin / Terlambat / Alpa) langsung dari tabel asal.
function rwHapusLog(src, id){
    if (!confirm('Hapus log ini secara permanen? Tindakan ini tidak bisa dibatalkan.')) return;
    var fd = new FormData();
    fd.append('id', id);
    fd.append('src', src);
    fetch('portal_bk.php?ajax=hapus_log_riwayat', {method:'POST', body:fd})
        .then(r=>r.json())
        .then(function(d){
            if (d.ok) { rwToast('Log berhasil dihapus','info'); bukaRiwayat(rwCurrentSiswaId, true); }
            else { rwToast(d.msg||'Gagal menghapus log','error'); }
        })
        .catch(function(){ rwToast('Gagal: koneksi error','error'); });
}

function rwRenderPerbLog(data) {
    var el = document.getElementById('rwPerbLog');
    if (!data || !data.length) {
        el.innerHTML = '<div style="text-align:center;color:#94a3b8;font-size:.73rem;padding:10px">Belum ada point perbaikan dicatat</div>';
        return;
    }
    var html = '';
    data.forEach(function(p){
        html += '<div style="display:flex;align-items:center;gap:5px;padding:5px 6px;border-radius:7px;background:#f5f3ff;margin-bottom:5px;font-size:.72rem">'
            + '<span style="background:#ddd6fe;color:#5b21b6;padding:1px 7px;border-radius:5px;font-weight:700;font-size:.62rem;text-transform:uppercase;white-space:nowrap">'+escRwH(p.kategori)+'</span>'
            + '<div style="flex:1;color:#64748b;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="'+escRwH(p.keterangan||'')+'">'+escRwH(p.keterangan||'—')+'</div>'
            + '<span style="font-weight:800;color:#7c3aed;font-size:.75rem;white-space:nowrap">-'+p.jumlah+'</span>'
            + '<button onclick="rwHapusPerbaikan('+p.id+')" style="background:none;border:none;color:#94a3b8;cursor:pointer;font-size:.7rem;padding:2px" title="Hapus"><i class="fas fa-times"></i></button>'
            + '</div>';
    });
    el.innerHTML = html;
}

function rwSimpanPerbaikan() {
    if (!rwCurrentSiswaId) return;
    var kat = document.getElementById('rwfKategori').value;
    var jml = document.getElementById('rwfJumlah').value;
    var tgl = document.getElementById('rwfTanggal').value;
    var ket = document.getElementById('rwfKeterangan').value;
    if (!kat) { rwToast('Pilih kategori terlebih dahulu','info'); return; }
    if (!jml || jml < 1) { rwToast('Jumlah minimal 1','info'); return; }
    var fd = new FormData();
    fd.append('siswa_id',  rwCurrentSiswaId);
    fd.append('kategori',  kat);
    fd.append('jumlah',    jml);
    fd.append('tanggal',   tgl);
    fd.append('keterangan', ket);
    fetch('portal_bk.php?ajax=simpan_perbaikan', {method:'POST', body:fd})
        .then(r=>r.json())
        .then(function(d){
            if (d.ok) {
                rwToast(d.msg,'success');
                document.getElementById('rwfKategori').value='';
                document.getElementById('rwfKeterangan').value='';
                document.getElementById('rwfJumlah').value='1';
                // Reload riwayat (tetap di tab yang sedang aktif, mis. langsung lihat "Sudah Diperbaiki")
                bukaRiwayat(rwCurrentSiswaId, true);
            } else { rwToast(d.msg||'Gagal menyimpan','error'); }
        })
        .catch(function(){ rwToast('Gagal: koneksi error','error'); });
}

function rwHapusPerbaikan(id) {
    if (!confirm('Hapus catatan point perbaikan ini?')) return;
    var fd = new FormData(); fd.append('id', id);
    fetch('portal_bk.php?ajax=hapus_perbaikan', {method:'POST', body:fd})
        .then(r=>r.json())
        .then(function(d){
            if (d.ok) { rwToast('Point perbaikan dihapus','info'); bukaRiwayat(rwCurrentSiswaId, true); }
        });
}

function cetakRekapSiswa() {
    if (!rwCurrentSiswa || !rwCurrentStat) { rwToast('Data siswa belum dimuat','info'); return; }
    var s = rwCurrentSiswa, sv = rwCurrentStat;
    document.getElementById('pr-nama').textContent  = s.nama;
    document.getElementById('pr-kelas').textContent = s.kelas;
    document.getElementById('pr-hadir').textContent      = sv.Hadir||0;
    document.getElementById('pr-terlambat').textContent  = sv.Terlambat||0;
    document.getElementById('pr-pelanggaran').textContent= sv.Pelanggaran||0;
    document.getElementById('pr-poin').textContent       = sv.TotalPoin||0;
    var fotoBox = document.getElementById('pr-fotoBox');
    if (s.foto) { fotoBox.innerHTML = '<img src="'+s.foto+'" style="width:100%;height:100%;object-fit:cover;border-radius:12px">'; }
    else { fotoBox.textContent = (s.nama||'?').charAt(0).toUpperCase(); }
    window.print();
}

function escRwH(s) {
    return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function rwToast(msg, type) {
    var el = document.createElement('div');
    el.style.cssText = 'position:fixed;bottom:20px;right:20px;z-index:99999;padding:10px 16px;border-radius:8px;font-size:.84rem;font-weight:600;color:white;box-shadow:0 4px 16px rgba(0,0,0,.25)';
    el.style.background = type==='error'?'#dc2626':type==='info'?'#0e7490':'#16a34a';
    el.textContent = msg;
    document.body.appendChild(el);
    setTimeout(function(){ el.style.opacity='0'; setTimeout(function(){ el.remove(); },300); }, 2500);
}

// Klik di luar modal-box menutup modal
document.querySelectorAll('.modal-overlay').forEach(function(ov){
    ov.addEventListener('click', function(e){ if (e.target === ov) ov.classList.remove('show'); });
});
</script>

<!-- ═══ PRINT AREA: RIWAYAT SISWA ══════════════════════════════════ -->
<div class="print-rekap-siswa" id="printRekapSiswa">
    <div class="print-rekap-title">RIWAYAT SISWA</div>
    <div class="print-identitas-wrap">
        <div class="print-foto-box" id="pr-fotoBox">?</div>
        <table class="print-identitas-tbl">
            <tr><td>NAMA</td><td>:</td><td id="pr-nama">—</td></tr>
            <tr><td>KELAS</td><td>:</td><td id="pr-kelas">—</td></tr>
            <tr><td>HADIR</td><td>:</td><td id="pr-hadir">0</td></tr>
            <tr><td>TERLAMBAT</td><td>:</td><td id="pr-terlambat">0</td></tr>
            <tr><td>PELANGGARAN</td><td>:</td><td id="pr-pelanggaran">0</td></tr>
            <tr><td>POINT</td><td>:</td><td id="pr-poin">0</td></tr>
            <tr><td>CATATAN</td><td>:</td><td>&nbsp;</td></tr>
        </table>
    </div>
</div>
<!-- ═══ END PRINT AREA ══════════════════════════════════════════════════ -->
</body>
</html>
