<?php
// wa_absen.php — Handler WA Notifikasi Absensi Siswa
// Gunakan token dari sistem Jurnal KBM (wa_pengaturan)
// atau isi manual $WA_TOKEN di bawah
require_once __DIR__ . '/includes/config.php';

// ── Ambil token dari database wa_pengaturan (jika pakai DB yang sama) ──
// Jika DB berbeda, isi manual di bawah:
define('WA_TOKEN',   '');   // ← isi token Fonnte jika DB berbeda
define('WA_USERKEY', '');   // ← isi userkey Zenziva jika pakai Zenziva
define('WA_PASSKEY', '');   // ← isi passkey Zenziva jika pakai Zenziva
define('WA_PROVIDER','fonnte'); // 'fonnte' atau 'zenziva'

// ════════════════════════════════════════════════════════════════
// FUNGSI KIRIM WA
// ════════════════════════════════════════════════════════════════
function wa_kirim($nomor, $pesan) {
    $nomor = preg_replace('/\D/', '', $nomor);
    if (substr($nomor,0,1)==='0') $nomor = '62'.substr($nomor,1);
    if (substr($nomor,0,2)!=='62') $nomor = '62'.$nomor;

    if (WA_PROVIDER === 'zenziva') {
        $ch = curl_init('https://console.zenziva.net/warouter/api/sendWA/');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => ['userkey'=>WA_USERKEY,'passkey'=>WA_PASSKEY,'to'=>$nomor,'message'=>$pesan],
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
    } else {
        // Fonnte
        $ch = curl_init('https://api.fonnte.com/send');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => ['target'=>$nomor,'message'=>$pesan,'countryCode'=>'62'],
            CURLOPT_HTTPHEADER     => ['Authorization: '.WA_TOKEN],
            CURLOPT_TIMEOUT        => 10,
        ]);
    }
    $res = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($res, true);
    if (WA_PROVIDER === 'zenziva') return ($data['status']??'') === '100';
    return ($data['status']??false) === true;
}

// ════════════════════════════════════════════════════════════════
// FUNGSI UTAMA
// ════════════════════════════════════════════════════════════════

/**
 * Kirim notif HADIR ke ortu setelah scan QR
 */
function wa_notif_hadir($siswa_id, $nama, $kelas, $nis, $status, $jam) {
    global $conn;
    $no_wa = wa_get_no_ortu($conn, $siswa_id, $nis);
    if (!$no_wa) return false;

    $tgl   = format_tanggal(date('Y-m-d'));
    $emoji = $status === 'Terlambat' ? '⏰' : '✅';
    $pesan = "Assalamu'alaikum,\n\n"
           . "$emoji *NOTIFIKASI KEHADIRAN SISWA*\n\n"
           . "📌 Nama  : $nama\n"
           . "🏫 Kelas : $kelas\n"
           . "📅 Tgl   : $tgl\n"
           . "🕐 Jam   : $jam\n"
           . "📋 Status: *$status*\n\n"
           . "Putra/putri Anda telah tiba di sekolah.\n"
           . "Terima kasih 🙏";

    return wa_kirim($no_wa, $pesan);
}

/**
 * Kirim notif BELUM ABSEN (pengingat)
 */
function wa_notif_belum_absen($siswa_id, $nama, $kelas, $nis) {
    global $conn;
    $no_wa = wa_get_no_ortu($conn, $siswa_id, $nis);
    if (!$no_wa) return false;

    $tgl   = format_tanggal(date('Y-m-d'));
    $jam   = date('H:i');
    $pesan = "Assalamu'alaikum Bapak/Ibu,\n\n"
           . "⚠️ *PERINGATAN BELUM ABSEN*\n\n"
           . "📌 Nama  : $nama\n"
           . "🏫 Kelas : $kelas\n"
           . "📅 Tgl   : $tgl\n"
           . "🕐 Dikirim: $jam WIB\n\n"
           . "Putra/putri Anda *BELUM TERCATAT* hadir di sekolah hari ini.\n\n"
           . "Mohon konfirmasi atau segera hubungi sekolah.\n"
           . "Terima kasih 🙏";

    return wa_kirim($no_wa, $pesan);
}

/**
 * Ambil nomor WA ortu dari tabel siswa
 */
function wa_get_no_ortu($conn, $siswa_id, $nis = '') {
    // Cek kolom no_wa_ortu di tabel siswa
    $res = $conn->query("SELECT no_wa_ortu FROM siswa WHERE id=$siswa_id LIMIT 1");
    if ($res) {
        $row = $res->fetch_assoc();
        if (!empty($row['no_wa_ortu'])) return $row['no_wa_ortu'];
    }
    return null;
}

// ════════════════════════════════════════════════════════════════
// API ENDPOINT (dipanggil via AJAX atau cron)
// ════════════════════════════════════════════════════════════════
if (isset($_POST['action']) || isset($_GET['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    if ($action === 'notif_hadir') {
        $sid    = (int)($_POST['siswa_id'] ?? 0);
        $nama   = $_POST['nama']   ?? '';
        $kelas  = $_POST['kelas']  ?? '';
        $nis    = $_POST['nis']    ?? '';
        $status = $_POST['status'] ?? 'Hadir';
        $jam    = $_POST['jam']    ?? date('H:i');
        $ok = wa_notif_hadir($sid, $nama, $kelas, $nis, $status, $jam);
        echo json_encode(['success'=>$ok]);
        exit;
    }

    if ($action === 'notif_belum_massal') {
        cek_login();
        $kelas  = sanitize($_POST['kelas'] ?? '');
        $today  = date('Y-m-d');
        $where  = "id NOT IN (SELECT siswa_id FROM absensi WHERE tanggal='$today'" . periode_where($conn) . ") AND aktif=1";
        if ($kelas) $where .= " AND kelas='$kelas'";
        $list = $conn->query("SELECT id,nis,nama,kelas,no_wa_ortu FROM siswa WHERE $where ORDER BY kelas,nama");
        $terkirim=0; $skip=0; $gagal=0; $detail=[];
        while ($s = $list->fetch_assoc()) {
            if (empty($s['no_wa_ortu'])) { $skip++; continue; }
            $ok = wa_notif_belum_absen($s['id'], $s['nama'], $s['kelas'], $s['nis']);
            if ($ok) $terkirim++;
            else { $gagal++; $detail[]=$s['nama']; }
            usleep(300000);
        }
        echo json_encode(['success'=>true,'terkirim'=>$terkirim,'skip'=>$skip,'gagal'=>$gagal,'detail'=>$detail]);
        exit;
    }

    if ($action === 'test') {
        cek_login();
        $nomor = $_POST['nomor'] ?? '';
        $ok = wa_kirim($nomor, 'Test notifikasi dari Sistem Absensi '.date('H:i d/m/Y').' ✅');
        echo json_encode(['success'=>$ok]);
        exit;
    }

    echo json_encode(['success'=>false,'msg'=>'Action tidak dikenal']);
    exit;
}
?>
