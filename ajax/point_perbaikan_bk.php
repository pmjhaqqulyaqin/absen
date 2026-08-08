<?php
/**
 * ajax/point_perbaikan_bk.php
 * AJAX handler untuk fitur Point Perbaikan di Portal BK.
 * Auto-create tabel point_perbaikan jika belum ada.
 *
 * PERUBAHAN:
 * - Menambahkan KUNJUNGAN ke dalam kategori valid ($valid_kat)
 * - Perbaikan: saat simpan, poin perbaikan otomatis mengurangi poin pelanggaran sesuai kategori
 */
require_once '../includes/config.php';
header('Content-Type: application/json');

// Guard: hanya bisa diakses oleh session admin atau BK
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['bk_id'])) {
    echo json_encode(['ok' => false, 'msg' => 'Unauthorized']);
    exit;
}

// Kompatibilitas: jika masuk lewat admin, set bk_id dari admin
if (!isset($_SESSION['bk_id']) && isset($_SESSION['admin_id'])) {
    $_SESSION['bk_id']   = $_SESSION['admin_id'];
    $_SESSION['bk_nama'] = $_SESSION['admin_nama'] ?? 'Admin';
}

// ── Auto-create tabel point_perbaikan ─────────────────────────────────
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

// ── Auto-create tabel pengaturan_poin_absen ───────────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS pengaturan_poin_absen (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    poin_alpa       INT(11) DEFAULT 5,
    poin_terlambat  INT(11) DEFAULT 2,
    keterangan      VARCHAR(255) DEFAULT '',
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
// Insert default jika belum ada
$cekPoin = $conn->query("SELECT COUNT(*) as c FROM pengaturan_poin_absen")->fetch_assoc();
if ((int)$cekPoin['c'] === 0) {
    $conn->query("INSERT INTO pengaturan_poin_absen (poin_alpa, poin_terlambat) VALUES (5, 2)");
}

$action = $_REQUEST['action'] ?? '';

// ── GET: ambil daftar point perbaikan milik satu siswa ─────────────────
if ($action === 'get') {
    $siswa_id = (int)($_GET['siswa_id'] ?? 0);
    $out = [];
    if ($siswa_id) {
        $res = $conn->query(
            "SELECT * FROM point_perbaikan WHERE siswa_id=$siswa_id" . periode_where($conn) . " ORDER BY tanggal DESC, id DESC"
        );
        while ($r = $res->fetch_assoc()) $out[] = $r;
    }
    // Hitung total per kategori
    $total = ['TERLAMBAT' => 0, 'ALPA' => 0, 'PELANGGARAN' => 0, 'KUNJUNGAN' => 0];
    foreach ($out as $r) {
        $total[$r['kategori']] = ($total[$r['kategori']] ?? 0) + (int)$r['jumlah'];
    }
    echo json_encode(['ok' => true, 'data' => $out, 'total' => $total]);
    exit;
}

// ── SIMPAN: catat point perbaikan baru ────────────────────────────────
if ($action === 'simpan' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $siswa_id = (int)($_POST['siswa_id'] ?? 0);
    $kategori = strtoupper(trim($_POST['kategori'] ?? ''));
    $jumlah   = max(1, (int)($_POST['jumlah'] ?? 1));
    $ket      = $conn->real_escape_string(trim($_POST['keterangan'] ?? ''));
    $tgl      = $conn->real_escape_string($_POST['tanggal'] ?? date('Y-m-d'));
    $guru_id  = (int)($_SESSION['bk_id'] ?? $_SESSION['admin_id'] ?? 0);
    $guru_nm  = $conn->real_escape_string($_SESSION['bk_nama'] ?? $_SESSION['admin_nama'] ?? '');

    // KUNJUNGAN sekarang ditambahkan ke kategori valid
    $valid_kat = ['TERLAMBAT', 'ALPA', 'PELANGGARAN', 'KUNJUNGAN'];
    if (!$siswa_id || !in_array($kategori, $valid_kat)) {
        echo json_encode(['ok' => false, 'msg' => 'Data tidak lengkap atau kategori tidak valid: ' . $kategori]);
        exit;
    }

    // Ambil data siswa
    $s = $conn->query("SELECT nis, nama, kelas FROM siswa WHERE id=$siswa_id AND aktif=1")->fetch_assoc();
    if (!$s) {
        echo json_encode(['ok' => false, 'msg' => 'Data siswa tidak ditemukan']);
        exit;
    }
    $nis   = $conn->real_escape_string($s['nis']);
    $nama  = $conn->real_escape_string($s['nama']);
    $kelas = $conn->real_escape_string($s['kelas']);

    list($ptaPb, $psemPb) = periode_values($conn);
    $conn->query("INSERT INTO point_perbaikan
        (siswa_id,nis,nama_siswa,kelas,kategori,jumlah,keterangan,tanggal,guru_bk_id,nama_guru,tahun_ajaran,semester)
        VALUES ($siswa_id,'$nis','$nama','$kelas','$kategori',$jumlah,'$ket','$tgl',$guru_id,'$guru_nm',$ptaPb,$psemPb)");

    if ($conn->error) {
        echo json_encode(['ok' => false, 'msg' => 'Gagal menyimpan: ' . $conn->error]);
        exit;
    }

    // Hitung sisa poin setelah perbaikan (untuk info di response)
    $poinCfg = $conn->query("SELECT * FROM pengaturan_poin_absen LIMIT 1")->fetch_assoc();
    $poinPerAlpa       = (int)($poinCfg['poin_alpa'] ?? 5);
    $poinPerTerlambat  = (int)($poinCfg['poin_terlambat'] ?? 2);

    // Hitung total perbaikan untuk kategori ini
    $totalPerbaikan = (int)$conn->query(
        "SELECT COALESCE(SUM(jumlah),0) as s FROM point_perbaikan WHERE siswa_id=$siswa_id AND kategori='$kategori'" . periode_where($conn)
    )->fetch_assoc()['s'];

    echo json_encode([
        'ok'              => true,
        'msg'             => "Point perbaikan $kategori (+$jumlah) berhasil disimpan!",
        'id'              => $conn->insert_id,
        'total_perbaikan' => $totalPerbaikan,
    ]);
    exit;
}

// ── HAPUS: hapus satu record point perbaikan ──────────────────────────
if ($action === 'hapus' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
        $conn->query("DELETE FROM point_perbaikan WHERE id=$id");
        echo json_encode(['ok' => true, 'msg' => 'Point perbaikan dihapus']);
    } else {
        echo json_encode(['ok' => false, 'msg' => 'ID tidak valid']);
    }
    exit;
}

echo json_encode(['ok' => false, 'msg' => 'Action tidak dikenal: ' . htmlspecialchars($action)]);
