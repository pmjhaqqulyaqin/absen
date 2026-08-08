<?php
require_once '../includes/config.php';
cek_login();
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

if ($action === 'switch') {
    $id = (int)($_POST['id'] ?? 0);
    $cek = $conn->query("SELECT id FROM periode_ajaran WHERE id=$id")->fetch_assoc();
    if (!$cek) {
        echo json_encode(['success' => false, 'msg' => 'Periode tidak ditemukan']);
        exit;
    }
    $conn->query("UPDATE periode_aktif SET periode_id=$id WHERE id=1");
    unset($_SESSION['periode_aktif'], $_SESSION['periode_aktif_ts']); // paksa refresh cache
    echo json_encode(['success' => true, 'msg' => 'Periode aktif berhasil diganti']);
    exit;
}

if ($action === 'tambah') {
    $tahun    = trim($_POST['tahun_ajaran'] ?? '');
    $semester = trim($_POST['semester'] ?? '');
    $tglMulai = trim($_POST['tanggal_mulai'] ?? '');
    $tglSelesai = trim($_POST['tanggal_selesai'] ?? '');

    if (!preg_match('/^\d{4}\/\d{4}$/', $tahun)) {
        echo json_encode(['success' => false, 'msg' => 'Format tahun ajaran harus seperti 2027/2028']);
        exit;
    }
    if (!in_array($semester, ['Ganjil', 'Genap'])) {
        echo json_encode(['success' => false, 'msg' => 'Semester tidak valid']);
        exit;
    }
    // Validasi format tanggal (YYYY-MM-DD dari <input type="date">)
    $validTgl = function($t) {
        if ($t === '') return true; // boleh dikosongkan, diisi belakangan
        $d = DateTime::createFromFormat('Y-m-d', $t);
        return $d && $d->format('Y-m-d') === $t;
    };
    if (!$validTgl($tglMulai) || !$validTgl($tglSelesai)) {
        echo json_encode(['success' => false, 'msg' => 'Format tanggal tidak valid']);
        exit;
    }
    if ($tglMulai !== '' && $tglSelesai !== '' && $tglMulai > $tglSelesai) {
        echo json_encode(['success' => false, 'msg' => 'Tanggal mulai tidak boleh setelah tanggal selesai']);
        exit;
    }

    $tahunEsc = $conn->real_escape_string($tahun);
    $semEsc   = $conn->real_escape_string($semester);
    $tglMulaiSql   = $tglMulai   !== '' ? "'".$conn->real_escape_string($tglMulai)."'"   : 'NULL';
    $tglSelesaiSql = $tglSelesai !== '' ? "'".$conn->real_escape_string($tglSelesai)."'" : 'NULL';

    $cek = $conn->query("SELECT id FROM periode_ajaran WHERE tahun_ajaran='$tahunEsc' AND semester='$semEsc'")->fetch_assoc();
    if ($cek) {
        $id = $cek['id'];
        // Periode sudah ada: perbarui tanggalnya kalau dikirim, lalu aktifkan
        $conn->query("UPDATE periode_ajaran SET tanggal_mulai=$tglMulaiSql, tanggal_selesai=$tglSelesaiSql WHERE id=$id");
        $conn->query("UPDATE periode_aktif SET periode_id=$id WHERE id=1");
        unset($_SESSION['periode_aktif'], $_SESSION['periode_aktif_ts']);
        echo json_encode(['success' => true, 'msg' => 'Periode sudah ada, tanggal diperbarui & langsung diaktifkan']);
        exit;
    }

    $ok = $conn->query("INSERT INTO periode_ajaran (tahun_ajaran, semester, tanggal_mulai, tanggal_selesai) VALUES ('$tahunEsc','$semEsc',$tglMulaiSql,$tglSelesaiSql)");
    if (!$ok) {
        echo json_encode(['success' => false, 'msg' => 'Gagal menambah periode: ' . $conn->error]);
        exit;
    }
    $newId = $conn->insert_id;
    $conn->query("UPDATE periode_aktif SET periode_id=$newId WHERE id=1");
    unset($_SESSION['periode_aktif'], $_SESSION['periode_aktif_ts']);
    echo json_encode(['success' => true, 'msg' => "Tahun ajaran $tahun - $semester ditambahkan & diaktifkan. Data absensi/pelanggaran akan mulai kosong untuk periode ini."]);
    exit;
}

if ($action === 'update_tanggal') {
    $id = (int)($_POST['id'] ?? 0);
    $tglMulai   = trim($_POST['tanggal_mulai'] ?? '');
    $tglSelesai = trim($_POST['tanggal_selesai'] ?? '');

    $cek = $conn->query("SELECT id FROM periode_ajaran WHERE id=$id")->fetch_assoc();
    if (!$cek) {
        echo json_encode(['success' => false, 'msg' => 'Periode tidak ditemukan']);
        exit;
    }
    $validTgl = function($t) {
        if ($t === '') return true;
        $d = DateTime::createFromFormat('Y-m-d', $t);
        return $d && $d->format('Y-m-d') === $t;
    };
    if (!$validTgl($tglMulai) || !$validTgl($tglSelesai)) {
        echo json_encode(['success' => false, 'msg' => 'Format tanggal tidak valid']);
        exit;
    }
    if ($tglMulai !== '' && $tglSelesai !== '' && $tglMulai > $tglSelesai) {
        echo json_encode(['success' => false, 'msg' => 'Tanggal mulai tidak boleh setelah tanggal selesai']);
        exit;
    }
    $tglMulaiSql   = $tglMulai   !== '' ? "'".$conn->real_escape_string($tglMulai)."'"   : 'NULL';
    $tglSelesaiSql = $tglSelesai !== '' ? "'".$conn->real_escape_string($tglSelesai)."'" : 'NULL';

    $ok = $conn->query("UPDATE periode_ajaran SET tanggal_mulai=$tglMulaiSql, tanggal_selesai=$tglSelesaiSql WHERE id=$id");
    if (!$ok) {
        echo json_encode(['success' => false, 'msg' => 'Gagal menyimpan tanggal: ' . $conn->error]);
        exit;
    }
    unset($_SESSION['periode_aktif'], $_SESSION['periode_aktif_ts']);
    echo json_encode(['success' => true, 'msg' => 'Tanggal periode berhasil disimpan']);
    exit;
}

echo json_encode(['success' => false, 'msg' => 'Aksi tidak dikenal']);
