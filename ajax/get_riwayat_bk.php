<?php
/**
 * ajax/get_riwayat_bk.php
 * Mengambil riwayat aktifitas siswa untuk Portal BK:
 * - Pelanggaran Disiplin (dari tabel pelanggaran)
 * - Buku Kejadian (dari tabel buku_kejadian) — beserta poin
 * - Kunjungan Rumah (dari tabel kunjungan_rumah)
 *
 * Response JSON:
 * {
 *   ok: true,
 *   data: [ { tipe, tanggal, judul, isi, keterangan, poin }, ... ],
 *   stats: { hadir, terlambat, alpa, izin, sakit, bolos,
 *            jml_pelanggaran, poin_pelanggaran, poin_buku,
 *            jml_kunjungan, total_poin }
 * }
 */
require_once '../includes/config.php';
header('Content-Type: application/json');

// Guard
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['bk_id'])) {
    echo json_encode(['ok' => false, 'msg' => 'Unauthorized']);
    exit;
}

$siswa_id = (int)($_GET['siswa_id'] ?? 0);
if (!$siswa_id) {
    echo json_encode(['ok' => false, 'msg' => 'siswa_id required']);
    exit;
}

// ── Auto-create tabel jika belum ada ──────────────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS pelanggaran (
    id INT AUTO_INCREMENT PRIMARY KEY, siswa_id INT NOT NULL, nis VARCHAR(20) DEFAULT '',
    nama VARCHAR(100) DEFAULT '', kelas VARCHAR(20) DEFAULT '',
    jenis_id INT DEFAULT 0, nama_jenis VARCHAR(255) DEFAULT '',
    poin INT DEFAULT 0, keterangan TEXT DEFAULT NULL,
    tanggal DATE NOT NULL, admin_id INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_siswa (siswa_id), INDEX idx_tanggal (tanggal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS buku_kejadian (
    id INT AUTO_INCREMENT PRIMARY KEY, siswa_id INT NOT NULL, nis VARCHAR(20) DEFAULT '',
    nama VARCHAR(100) DEFAULT '', kelas VARCHAR(20) DEFAULT '',
    judul VARCHAR(255) NOT NULL, isi TEXT DEFAULT NULL,
    poin INT DEFAULT 0, tindak_lanjut TEXT DEFAULT NULL,
    tanggal DATE NOT NULL, admin_id INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_siswa (siswa_id), INDEX idx_tanggal (tanggal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS kunjungan_rumah (
    id INT AUTO_INCREMENT PRIMARY KEY, siswa_id INT NOT NULL, nis VARCHAR(20) DEFAULT '',
    nama VARCHAR(100) DEFAULT '', kelas VARCHAR(20) DEFAULT '',
    tujuan TEXT DEFAULT NULL, hasil TEXT DEFAULT NULL,
    tanggal DATE NOT NULL, admin_id INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_siswa (siswa_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS pengaturan_poin_absen (
    id INT AUTO_INCREMENT PRIMARY KEY, poin_alpa INT DEFAULT 5, poin_terlambat INT DEFAULT 2,
    keterangan VARCHAR(255) DEFAULT '', updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$cekPoin = $conn->query("SELECT COUNT(*) as c FROM pengaturan_poin_absen")->fetch_assoc();
if ((int)$cekPoin['c'] === 0) {
    $conn->query("INSERT INTO pengaturan_poin_absen (poin_alpa, poin_terlambat) VALUES (5, 2)");
}

$conn->query("CREATE TABLE IF NOT EXISTS point_perbaikan (
    id INT AUTO_INCREMENT PRIMARY KEY, siswa_id INT NOT NULL, nis VARCHAR(30) DEFAULT '',
    nama_siswa VARCHAR(100) DEFAULT '', kelas VARCHAR(30) DEFAULT '',
    kategori ENUM('TERLAMBAT','ALPA','PELANGGARAN','KUNJUNGAN') NOT NULL,
    jumlah INT DEFAULT 1, keterangan VARCHAR(255) DEFAULT '', tanggal DATE NOT NULL,
    guru_bk_id INT DEFAULT 0, nama_guru VARCHAR(100) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX idx_siswa (siswa_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── Ambil pengaturan poin ──────────────────────────────────────────
$poin_cfg      = $conn->query("SELECT * FROM pengaturan_poin_absen LIMIT 1")->fetch_assoc();
$POIN_ALPA     = (int)($poin_cfg['poin_alpa'] ?? 5);
$POIN_TERLAMBAT = (int)($poin_cfg['poin_terlambat'] ?? 2);

// ── 1. Pelanggaran Disiplin ────────────────────────────────────────
$items = [];
$res = $conn->query("SELECT 'pelanggaran' as tipe, tanggal, nama_jenis as judul, keterangan, poin, '' as isi
    FROM pelanggaran WHERE siswa_id=$siswa_id" . periode_where($conn) . "");
while ($r = $res->fetch_assoc()) $items[] = $r;

// ── 2. Buku Kejadian (tampilkan poin) ─────────────────────────────
$res = $conn->query("SELECT 'buku' as tipe, tanggal, judul, isi, tindak_lanjut as keterangan, poin
    FROM buku_kejadian WHERE siswa_id=$siswa_id" . periode_where($conn) . "");
while ($r = $res->fetch_assoc()) $items[] = $r;

// ── 3. Kunjungan Rumah ─────────────────────────────────────────────
$res = $conn->query("SELECT 'kunjungan' as tipe, tanggal, tujuan as judul, hasil as isi, '' as keterangan, 0 as poin
    FROM kunjungan_rumah WHERE siswa_id=$siswa_id" . periode_where($conn) . "");
while ($r = $res->fetch_assoc()) $items[] = $r;

// Urutkan berdasarkan tanggal DESC
usort($items, function($a, $b) { return strcmp($b['tanggal'], $a['tanggal']); });

// ── Stats Absensi ──────────────────────────────────────────────────
$abs = $conn->query("SELECT
    COALESCE(SUM(status='Hadir'),0)     as hadir,
    COALESCE(SUM(status='Terlambat'),0) as terlambat,
    COALESCE(SUM(status='Alpa'),0)      as alpa,
    COALESCE(SUM(status='Izin'),0)      as izin,
    COALESCE(SUM(status='Sakit'),0)     as sakit,
    COALESCE(SUM(status='Bolos'),0)     as bolos
    FROM absensi WHERE siswa_id=$siswa_id" . periode_where($conn))->fetch_assoc();

$hadir     = (int)($abs['hadir']     ?? 0);
$terlambat = (int)($abs['terlambat'] ?? 0);
$alpa      = (int)($abs['alpa']      ?? 0);
$izin      = (int)($abs['izin']      ?? 0);
$sakit     = (int)($abs['sakit']     ?? 0);
$bolos     = (int)($abs['bolos']     ?? 0);

// ── Stats Pelanggaran ──────────────────────────────────────────────
$rp = $conn->query("SELECT COUNT(*) as cnt, COALESCE(SUM(poin),0) as total FROM pelanggaran WHERE siswa_id=$siswa_id" . periode_where($conn))->fetch_assoc();
$jml_pelanggaran  = (int)($rp['cnt']   ?? 0);
$poin_pelanggaran = (int)($rp['total'] ?? 0);

// ── Stats Buku Kejadian ────────────────────────────────────────────
$poin_buku = (int)$conn->query("SELECT COALESCE(SUM(poin),0) as s FROM buku_kejadian WHERE siswa_id=$siswa_id" . periode_where($conn))->fetch_assoc()['s'];

// ── Stats Kunjungan ────────────────────────────────────────────────
$jml_kunjungan = (int)$conn->query("SELECT COUNT(*) as c FROM kunjungan_rumah WHERE siswa_id=$siswa_id" . periode_where($conn))->fetch_assoc()['c'];

// ── Hitung Perbaikan ───────────────────────────────────────────────
$resPb = $conn->query("SELECT kategori, COALESCE(SUM(jumlah),0) as s FROM point_perbaikan WHERE siswa_id=$siswa_id" . periode_where($conn) . " GROUP BY kategori");
$pb = ['TERLAMBAT'=>0,'ALPA'=>0,'PELANGGARAN'=>0,'KUNJUNGAN'=>0];
while ($rp2 = $resPb->fetch_assoc()) $pb[$rp2['kategori']] = (int)$rp2['s'];

// ── Hitung Total Poin ──────────────────────────────────────────────
// Poin dari alpa & terlambat (berdasarkan pengaturan)
$poin_dari_alpa      = $alpa      * $POIN_ALPA;
$poin_dari_terlambat = $terlambat * $POIN_TERLAMBAT;

// Kurangi dengan perbaikan per kategori
$poin_terlambat_net = max(0, $poin_dari_terlambat - ($pb['TERLAMBAT'] * $POIN_TERLAMBAT));
$poin_alpa_net      = max(0, $poin_dari_alpa      - ($pb['ALPA']      * $POIN_ALPA));
$poin_pel_net       = max(0, $poin_pelanggaran    - $pb['PELANGGARAN']);
$poin_kunjungan_red = $pb['KUNJUNGAN']; // Kunjungan mengurangi total langsung

$total_poin = max(0, $poin_terlambat_net + $poin_alpa_net + $poin_pel_net + $poin_buku - $poin_kunjungan_red);

echo json_encode([
    'ok'   => true,
    'data' => $items,
    'stats' => [
        'hadir'             => $hadir,
        'terlambat'         => $terlambat,
        'alpa'              => $alpa,
        'izin'              => $izin,
        'sakit'             => $sakit,
        'bolos'             => $bolos,
        'jml_pelanggaran'   => $jml_pelanggaran,
        'poin_pelanggaran'  => $poin_pelanggaran,
        'poin_buku'         => $poin_buku,
        'jml_kunjungan'     => $jml_kunjungan,
        'poin_dari_alpa'    => $poin_dari_alpa,
        'poin_dari_terlambat' => $poin_dari_terlambat,
        'total_poin'        => $total_poin,
    ],
    'poin_cfg' => [
        'poin_alpa'      => $POIN_ALPA,
        'poin_terlambat' => $POIN_TERLAMBAT,
    ],
]);
