<?php
// api/guru.php — COMPLETE: semua fungsi dari Admin, PortalGuru, dll
error_reporting(0);
ini_set('display_errors', 0);
require_once __DIR__ . '/../config/db.php';

$action = getInput()['action'] ?? $_GET['action'] ?? '';

switch ($action) {
  // ── DATA GURU ──────────────────────────────────────────────
  case 'getDataGuru':         case 'getDataGuruFull':    getDataGuru();         break;
  case 'simpanGuru':          case 'simpanGuruFull':     simpanGuru();          break;
  case 'uploadTtdGuru':                                  uploadTtdGuru();       break;
  case 'getJadwalHariIni':    getJadwalHariIni();        break;
  case 'kirimWaGuru':         kirimWaGuru();             break;
  case 'kirimWaBroadcast':    kirimWaBroadcast();        break;
  case 'getWaPengaturan':     getWaPengaturan();         break;
  case 'simpanWaPengaturan':  simpanWaPengaturan();      break;
  case 'hapusGuru':           case 'hapusGuruFull':      hapusGuru();           break;
  case 'hapusGuruMassal':     hapusGuruMassal();         break;
  // ── AKUN GURU ──────────────────────────────────────────────
  case 'getAkunGuru':         getAkunGuru();      break;
  case 'simpanAkunGuru':      simpanAkunGuru();   break;
  case 'hapusAkunGuru':       hapusAkunGuru();    break;
  // ── PRESENSI ───────────────────────────────────────────────
  case 'simpanPresensi':      case 'simpanPresensiMengajar':
  case 'simpanPresensiPortalGuru':                simpanPresensi();      break;
  case 'hapusPresensiPortalGuru':                 hapusPresensi();       break;
  case 'hapusMateriPortalGuru':                   hapusMateri();         break;
  case 'simpanFotoPresensiGuru':                  simpanFotoPresensi();  break;
  case 'getPresensiGuru':     case 'getPresensiPortalGuru':
                                                  getPresensiGuru();     break;
  case 'getPresensiMengajarAdmin':               getPresensiAdmin();     break;
  case 'hapusPresensiAdminMassal':               hapusPresensiAdminMassal(); break;
  // ── Jurnal Hari Ini (Admin mengisi presensi harian guru) ──
  // PATCH: tambah alias nama action yang dipakai frontend Presensi Piket
  // (getJurnalHariIniAdmin / presensiAdminJurnal / hapusJurnalHariIniAdmin)
  // supaya tersambung ke fungsi backend yang sudah ada (tanpa mengubah fungsi).
  case 'getJurnalHariIni':    case 'getJurnalHariIniAdmin':  getJurnalHariIni();   break;
  case 'simpanJurnalAdmin':   case 'presensiAdminJurnal':    simpanJurnalAdmin();  break;
  case 'hapusJurnalHariIniAdmin':                            hapusPresensi();      break;
  // ── REKAP ──────────────────────────────────────────────────
  case 'getRekapMengajar':    getRekapMengajar();   break;
  case 'getRekapKehadiran':   getRekapKehadiran();  break;
  case 'getLaporanAbsensiSiswa': getLaporanAbsensi(); break;
  // ── JADWAL ─────────────────────────────────────────────────
  case 'getJadwal':           case 'getJadwalMengajar':  getJadwal();    break;
  case 'getJadwalGuru':       case 'getJadwalGuruAll':   getJadwalGuru();break;
  case 'simpanJadwal':        simpanJadwal();  break;
  case 'hapusJadwal':         case 'hapusJadwalMengajar': hapusJadwal(); break;
  case 'hapusJadwalMassal':   hapusJadwalMassal(); break;
  case 'hapusCascadeManual':  hapusCascadeManual(); break;
  case 'getWaktuJam':         apiGetWaktuJam();    break;
  case 'simpanWaktuJam':      apiSimpanWaktuJam(); break;
  case 'getRiwayatMengajarKelas': getRiwayatKelas(); break;
  // ── ROMBEL ─────────────────────────────────────────────────
  case 'getRombel':           case 'getDataRombel':  getRombel();   break;
  case 'simpanRombel':        simpanRombel();  break;
  case 'hapusRombel':         hapusRombel();   break;
  case 'hapusRombelMassal':   hapusRombelMassal(); break;
  // ── MAPEL ──────────────────────────────────────────────────
  case 'getMapel':            case 'getDataMapel':   getMapel();    break;
  case 'simpanMapel':         simpanMapel();   break;
  case 'hapusMapel':          hapusMapel();    break;
  case 'hapusMapelMassal':    hapusMapelMassal(); break;
  // ── TINGKAT ────────────────────────────────────────────────
  case 'getDataTingkat':      getDataTingkat();    break;
  case 'simpanTingkat':       simpanTingkat();     break;
  case 'hapusTingkat':        hapusTingkat();      break;
  case 'hapusTingkatMassal':  hapusTingkatMassal(); break;
  // ── TAHUN PELAJARAN & SEMESTER ─────────────────────────────
  case 'getTahunPelajaran':   getTahunPelajaran(); break;
  case 'simpanTahunPelajaran':simpanTahunPelajaran(); break;
  case 'hapusTahunPelajaran': hapusTahunPelajaran(); break;
  case 'setSemesterAktif':    setSemesterAktif(); break;
  case 'getSemesterAktif':    getSemesterAktif(); break;
  case 'nonAktifkanSemester': nonAktifkanSemester(); break;
  // ── WALI KELAS ─────────────────────────────────────────────
  case 'getWaliKelasList':    case 'getDataWaliKelas': getWaliList(); break;
  case 'simpanWaliKelas':     simpanWaliKelas(); break;
  case 'hapusWaliKelas':      hapusWaliKelas();  break;
  case 'syncRombelWali':      syncRombelWali(); break;
  // ── SISWA ──────────────────────────────────────────────────
  case 'getDataSiswa':        getDataSiswa();   break;
  case 'simpanSiswa':         simpanSiswa();    break;
  case 'hapusSiswa':          hapusSiswa();     break;
  case 'hapusSiswaMassal':    hapusSiswaMassal(); break;
  case 'importDataSiswa':     importSiswa();    break;
  case 'getFilterOptionsAbsensi': getFilterAbsensi(); break;
  // ── ABSENSI SISWA ──────────────────────────────────────────
  case 'getAbsensiSiswaPortal':   getAbsensiSiswaPortal();   break;
  case 'simpanAbsensiSiswaPortal':simpanAbsensiSiswaPortal(); break;
  case 'hapusAbsensiSiswaPortal': hapusAbsensiSiswa();        break;
  case 'hapusAbsensiSiswaById':   hapusAbsensiSiswaById();    break;
  case 'hapusAbsensiSiswaByGuru':  hapusAbsensiSiswaByGuru();  break;
  // ── PIKET ──────────────────────────────────────────────────
  case 'simpanPiketGuru':     simpanPiket();   break;
  case 'hapusPiketGuru':      hapusPiket();    break;
  case 'getPiketHariIni':     getPiketHariIni();  break;
  case 'getPiketRekap':       getPiketRekap();    break;
  case 'cekJadwalPiket':      cekJadwalPiket();       break;
  case 'getJadwalPiket':      getJadwalPiket();       break;
  case 'simpanJadwalPiket':   simpanJadwalPiket();    break;
  case 'hapusJadwalPiket':    hapusJadwalPiket();     break;
  // ── GURU DATA (portal) ─────────────────────────────────────
  case 'getGuruDataById':     getGuruById();   break;
  case 'getMapelKelasGuruMandiri': getMapelKelasGuruMandiri(); break;
  case 'simpanCPTPMandiri':        simpanCPTPMandiri();        break;
  case 'getServerTime':       getServerTime(); break;
  case 'getAllDataBatch':      getAllDataBatch(); break;
  // ── PENGATURAN & PASSWORD ───────────────────────────────────
  case 'gantiPasswordAdmin':  gantiPassword(); break;
  case 'debugBatch':
    // Diagnostic — cek apa yang gagal di getAllDataBatch
    $pdo2 = getDB(); $info = [];
    foreach (['data_guru','data_siswa','rombel','wali_kelas','jadwal_mengajar','mata_pelajaran','pengaturan','pengaturan_sekolah','sessions'] as $t) {
      try { $info[$t] = (int)$pdo2->query("SELECT COUNT(*) FROM $t")->fetchColumn(); }
      catch(Exception $e) { $info[$t] = 'ERROR: '.$e->getMessage(); }
    }
    // Check sessions column
    try { $cols = $pdo2->query("SHOW COLUMNS FROM sessions")->fetchAll(PDO::FETCH_COLUMN); $info['sessions_cols'] = $cols; } catch(Exception $e) { $info['sessions_cols'] = $e->getMessage(); }
    jsonOut(['success'=>true,'tables'=>$info,'php'=>PHP_VERSION]);
    break;
  // ── PORTAL BK: CATATAN BK (sumber data: data_siswa milik Admin) ─
  case 'getSiswaCatatanBK':        getSiswaCatatanBK();        break;
  case 'kirimLangsungCatatanBK':   kirimLangsungCatatanBK();   break;
  case 'simpanCatatanBK':          simpanCatatanBK();          break;
  case 'editCatatanLangsungBK':    editCatatanLangsungBK();    break;
  case 'hapusCatatanLangsungBK':   hapusCatatanLangsungBK();   break;
  case 'getJumlahBalasanBaruBK':   getJumlahBalasanBaruBK();   break;
  case 'tandaiBalasanDibacaBK':    tandaiBalasanDibacaBK();    break;
  // ── PORTAL BK: KUNJUNGAN RUMAH SISWA ─────────────────────────
  case 'getKunjunganBK':           getKunjunganBK();           break;
  case 'simpanKunjunganBK':        simpanKunjunganBK();        break;
  case 'hapusKunjunganBK':         hapusKunjunganBK();         break;
  // ── PORTAL BK: BUKU KEJADIAN (format sesuai jurnal manual) ───
  case 'getBukuKejadian':          getBukuKejadian();          break;
  case 'simpanBukuKejadian':       simpanBukuKejadian();       break;
  case 'editBukuKejadian':         editBukuKejadian();         break;
  case 'hapusBukuKejadian':        hapusBukuKejadian();        break;
  // ── PORTAL BK: RINGKASAN / RIWAYAT SISWA (gabungan semua sumber) ─
  case 'getRingkasanSiswaBK':      getRingkasanSiswaBK();      break;
  case 'getRiwayatAktivitasSiswa': getRiwayatAktivitasSiswa(); break;
  case 'getRekapRiwayatSiswaBK':   getRekapRiwayatSiswaBK();   break;
  default: jsonErr('Action tidak dikenal: ' . $action);
}

// ════════════════════════════════════════════════════════════════
// DATA GURU
// ════════════════════════════════════════════════════════════════
function getDataGuru() {
  $rows = getDB()->query("SELECT * FROM data_guru ORDER BY nama ASC")->fetchAll();
  jsonOut(toCamel($rows));
}

// ── Upload Tanda Tangan Guru ────────────────────────────────────────────────
function uploadTtdGuru() {
  $d   = getInput();
  $id  = trim($d['id'] ?? '');
  $b64 = $d['base64'] ?? '';
  if (!$id)  jsonErr('ID guru tidak valid');
  if (!$b64) jsonErr('Data gambar kosong');

  if (!preg_match('/^data:(image\/[a-z]+);base64,/', $b64, $m)) jsonErr('Format gambar tidak valid');
  $ext  = str_replace('image/', '', $m[1]);
  $data = base64_decode(preg_replace('/^data:image\/[a-z]+;base64,/', '', $b64));
  if (!$data) jsonErr('Gagal decode gambar');

  $fn  = 'ttd_' . preg_replace('/[^a-zA-Z0-9_\-]/', '', $id) . '_' . time() . '.' . $ext;
  if (!file_put_contents(UPLOAD_DIR . $fn, $data)) jsonErr('Gagal menyimpan file TTD');
  $url = UPLOAD_URL . $fn;

  $pdo = getDB();
  try { $pdo->exec("ALTER TABLE data_guru ADD COLUMN IF NOT EXISTS ttd_url TEXT DEFAULT NULL"); } catch (Exception $e) {}
  $pdo->prepare("UPDATE data_guru SET ttd_url=? WHERE id=?")->execute([$url, $id]);

  jsonOut(['success' => true, 'ttdUrl' => $url, 'msg' => 'Tanda tangan berhasil diunggah!']);
}

function simpanGuru() {
  $d   = getInput();
  $pdo = getDB();
  $id  = trim($d['id'] ?? '');
  if (!$id) $id = 'G' . time();

  $nama  = $d['nama'] ?? '';
  $jbt   = $d['jabatan'] ?? '';
  $jbtF  = $d['jabFungsi'] ?? $d['jabatanFungsional'] ?? $d['jabatan_fungsional'] ?? '';
  $tugas = $d['tugas'] ?? $d['tugasTambahan'] ?? $d['tugas_tambahan'] ?? '';
  $m1    = $d['mapel1'] ?? $d['mapel_1'] ?? $d['MAPEL_1'] ?? '';
  $m2    = $d['mapel2'] ?? $d['mapel_2'] ?? '';
  $m3    = $d['mapel3'] ?? $d['mapel_3'] ?? '';
  $m4    = $d['mapel4'] ?? $d['mapel_4'] ?? '';
  $kls   = is_array($d['kelas'] ?? null) ? implode(',', $d['kelas']) : ($d['kelas'] ?? '');
  $alm   = $d['alamat'] ?? '';
  $noWa  = preg_replace('/\D/', '', $d['noWa'] ?? $d['no_wa'] ?? ''); // strip non-digit
  // Status: pertahankan nilai asli dari form (AKTIF/Aktif/NON-AKTIF/Non-Aktif semua valid)
  $rawSts = trim($d['status'] ?? 'Aktif');
  // Normalisasi ke nilai ENUM yang diterima database
  $stsMap = [
    'aktif'     => 'Aktif',    'AKTIF'     => 'Aktif',    'Aktif'    => 'Aktif',
    'non-aktif' => 'Non-Aktif','NON-AKTIF' => 'Non-Aktif','Non-Aktif'=> 'Non-Aktif',
    'nonaktif'  => 'Non-Aktif','NONAKTIF'  => 'Non-Aktif',
  ];
  $sts = $stsMap[$rawSts] ?? $rawSts;
  $pw    = $d['password'] ?? null;
  $pin   = trim($d['pin'] ?? '');

  // Migrasi kolom no_wa, ttd_url & pin jika belum ada
  try { $pdo->exec("ALTER TABLE data_guru ADD COLUMN IF NOT EXISTS no_wa VARCHAR(20) DEFAULT ''"); } catch (Exception $e) {}
  try { $pdo->exec("ALTER TABLE data_guru ADD COLUMN IF NOT EXISTS ttd_url TEXT DEFAULT NULL"); } catch (Exception $e) {}
  try { $pdo->exec("ALTER TABLE data_guru ADD COLUMN IF NOT EXISTS pin VARCHAR(20) DEFAULT NULL"); } catch (Exception $e) {}

  $pdo->prepare(
    "INSERT INTO data_guru (id,nama,jabatan,jabatan_fungsional,tugas_tambahan,mapel_1,mapel_2,mapel_3,mapel_4,kelas,alamat,no_wa,status,password)
     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)
     ON DUPLICATE KEY UPDATE nama=?,jabatan=?,jabatan_fungsional=?,tugas_tambahan=?,mapel_1=?,mapel_2=?,mapel_3=?,mapel_4=?,kelas=?,alamat=?,no_wa=?,status=?" .
    ($pw !== null ? ",password=?" : "")
  )->execute(array_filter(array_merge(
    [$id,$nama,$jbt,$jbtF,$tugas,$m1,$m2,$m3,$m4,$kls,$alm,$noWa,$sts,$pw],
    [$nama,$jbt,$jbtF,$tugas,$m1,$m2,$m3,$m4,$kls,$alm,$noWa,$sts],
    $pw !== null ? [$pw] : []
  ), function($v){ return $v !== null; }));

  // ── PIN Login Guru: opsional, isi di sini otomatis tersinkron ke Kelola PIN ──
  $pinMsg = '';
  if ($pin !== '') {
    if (!ctype_digit($pin) || strlen($pin) < 4) {
      $pinMsg = ' (PIN diabaikan: harus angka, minimal 4 digit)';
    } else {
      try { $pdo->prepare("UPDATE data_guru SET pin=?, password=? WHERE id=?")->execute([$pin, $pin, $id]); } catch (Exception $e) {}
      try {
        $col = $pdo->query("SHOW COLUMNS FROM akun_guru LIKE 'pin'")->fetch();
        if (!$col) $pdo->exec("ALTER TABLE akun_guru ADD COLUMN pin VARCHAR(20) DEFAULT NULL");
      } catch (Exception $e) {}
      try { $pdo->prepare("UPDATE akun_guru SET password=?, pin=? WHERE nip=? OR username=?")->execute([$pin, $pin, $id, $id]); } catch (Exception $e) {}
      $pinMsg = ' & PIN Login tersimpan';
    }
  }

  jsonOut(['success'=>true,'id'=>$id,'msg'=>'Data guru disimpan!'.$pinMsg]);
}

function hapusGuru() {
  $d  = getInput();
  $id = $d['id'] ?? '';
  if (!$id) jsonErr('ID tidak valid');
  $pdo = getDB();

  // ── Cascade delete semua data yang terkait guru ini ──────────
  $tables = [
    // tabel                     kolom id_guru
    ['honor_guru',               'id_guru'],
    ['data_presensi',            'id_guru'],
    ['absensi_siswa',            'id_guru'],
    ['piket_guru',               'id_guru'],
    ['jadwal_mengajar',          'guru_id'],
    ['akun_guru',                'nip'],
  ];

  $deleted = [];
  foreach ($tables as $t) {
    try {
      $stmt = $pdo->prepare("DELETE FROM {$t[0]} WHERE {$t[1]}=?");
      $stmt->execute([$id]);
      $n = $stmt->rowCount();
      if ($n > 0) $deleted[] = $t[0] . ': ' . $n;
    } catch (Exception $e) {
      // Tabel tidak ada atau kolom berbeda — lewati saja
    }
  }

  // Hapus guru dari data_guru
  $pdo->prepare("DELETE FROM data_guru WHERE id=?")->execute([$id]);

  jsonOut([
    'success' => true,
    'msg'     => 'Guru berhasil dihapus beserta semua data terkait.',
    'detail'  => $deleted,
  ]);
}

// ════════════════════════════════════════════════════════════════

// ════════════════════════════════════════════════════════════════
// HAPUS GURU MASSAL
// ════════════════════════════════════════════════════════════════
function hapusGuruMassal() {
  $d   = getInput();
  $ids = $d['ids'] ?? [];
  if (is_string($ids)) $ids = json_decode($ids, true) ?: [];
  if (!is_array($ids) || !count($ids)) {
    jsonOut(['success' => false, 'msg' => 'Tidak ada ID yang dipilih']);
    return;
  }
  $ids = array_values(array_filter(array_map('strval', $ids)));
  if (!count($ids)) { jsonOut(['success' => false, 'msg' => 'ID tidak valid']); return; }

  $pdo = getDB();
  $ph  = implode(',', array_fill(0, count($ids), '?'));

  // Cascade delete semua data terkait guru-guru yang dipilih
  $tables = [
    ['honor_guru',      'id_guru'],
    ['data_presensi',   'id_guru'],
    ['absensi_siswa',   'id_guru'],
    ['piket_guru',      'id_guru'],
    ['jadwal_mengajar', 'guru_id'],
    ['akun_guru',       'nip'],
  ];

  $totalDeleted = [];
  foreach ($tables as $t) {
    try {
      $stmt = $pdo->prepare("DELETE FROM {$t[0]} WHERE {$t[1]} IN ($ph)");
      $stmt->execute($ids);
      $n = $stmt->rowCount();
      if ($n > 0) $totalDeleted[] = $t[0] . ': ' . $n;
    } catch (Exception $e) {
      // Tabel tidak ada atau kolom berbeda — lewati
    }
  }

  // Hapus semua guru dari data_guru
  $stmt = $pdo->prepare("DELETE FROM data_guru WHERE id IN ($ph)");
  $stmt->execute($ids);
  $hapusGuru = $stmt->rowCount();

  jsonOut([
    'success' => true,
    'msg'     => $hapusGuru . ' guru berhasil dihapus beserta semua data terkait.',
    'detail'  => $totalDeleted,
    'count'   => $hapusGuru,
  ]);
}

// AKUN GURU
// ════════════════════════════════════════════════════════════════
function getAkunGuru() {
  $rows = getDB()->query("SELECT ag.*, dg.nama FROM akun_guru ag LEFT JOIN data_guru dg ON ag.nip=dg.id ORDER BY dg.nama")->fetchAll();
  jsonOut($rows);
}

function simpanAkunGuru() {
  $d = getInput();
  $pdo = getDB();
  $nip = $d['nip'] ?? '';
  $usr = strtolower(trim($d['username'] ?? ''));
  $pw  = $d['password'] ?? '';
  $id  = $d['id'] ?? null;
  if (!$nip || !$usr) jsonErr('NIP dan username wajib!');

  if ($id) {
    if ($pw) $pdo->prepare("UPDATE akun_guru SET username=?,password=? WHERE id=?")->execute([$usr, password_hash($pw, PASSWORD_DEFAULT), $id]);
    else     $pdo->prepare("UPDATE akun_guru SET username=? WHERE id=?")->execute([$usr, $id]);
  } else {
    if (!$pw) jsonErr('Password wajib untuk akun baru!');
    $pdo->prepare("INSERT INTO akun_guru (nip,username,password) VALUES (?,?,?)")->execute([$nip,$usr,password_hash($pw,PASSWORD_DEFAULT)]);
  }
  jsonOut(['success'=>true,'msg'=>'Akun disimpan!']);
}

function hapusAkunGuru() {
  $id = getInput()['id'] ?? '';
  getDB()->prepare("DELETE FROM akun_guru WHERE id=?")->execute([$id]);
  jsonOut(['success'=>true]);
}

// ════════════════════════════════════════════════════════════════
// PRESENSI
// ════════════════════════════════════════════════════════════════
function simpanPresensi() {
  $d   = getInput();
  $pdo = getDB();
  $id  = 'PR' . time() . rand(100,999);
  $nip = $d['nip'] ?? $d['idGuru'] ?? $d['id_guru'] ?? '';
  $nm  = $d['nama'] ?? $d['namaGuru'] ?? $d['nama_guru'] ?? '';
  $kls = $d['kelas'] ?? '';
  $jk  = (int)($d['jamKe'] ?? $d['jam_ke'] ?? 0);
  $foto= $d['fotoUrl'] ?? $d['foto_url'] ?? '';
  $tgl = $d['tanggal'] ?? date('Y-m-d');
  $now = date('H:i:s');

  // Normalisasi status agar sesuai ENUM database
  $rawSts = $d['status'] ?? 'Mengajar';
  $stsMap = ['Hadir'=>'Hadir','Tdk Hadir'=>'Tidak Hadir','Tidak Hadir'=>'Tidak Hadir',
             'Tdk Mengajar'=>'Mengajar','Mengajar'=>'Mengajar','Izin'=>'Izin','Sakit'=>'Sakit'];
  $sts = $stsMap[$rawSts] ?? 'Mengajar';

  $mp  = $d['mapel'] ?? '';
  $mat = $d['materi'] ?? '';
  // FIX: kolom "kegiatan" (TP) sebelumnya TIDAK PERNAH dibaca/disimpan sama
  // sekali (tidak ada di query INSERT/UPDATE) — akibatnya TP yang dipilih/
  // ditulis guru selalu hilang dan tidak pernah tersimpan ke database.
  $keg = $d['kegiatan'] ?? '';
  // CP/TP mentah (nilai dropdown Capaian Pembelajaran / Tujuan Pembelajaran
  // yang dipilih guru) disimpan terpisah dari materi/kegiatan supaya tetap
  // ikut tersimpan ke Batasan Pembelajaran walau materi/kegiatan diedit manual.
  $cp  = $d['cp'] ?? '';
  $tp  = $d['tp'] ?? '';
  // FIX: bedakan "tidak dikirim" vs "dikirim" agar materi/kegiatan/jam yang
  // sudah benar tersimpan tidak ditimpa balik jadi kosong/1-jam oleh request
  // lain (mis. Simpan Materi vs Simpan Kegiatan) yang tidak mengirim semua field.
  $jksSent = isset($d['jamKeSampai']) ? $d['jamKeSampai'] : ($d['jam_ke_sampai'] ?? null);
  $jksProvided = ($jksSent !== null && $jksSent !== '');
  $jks = $jksProvided ? (int)$jksSent : $jk;
  if ($jks < $jk) $jks = $jk;

  // Migrasi otomatis: tambah kolom waktu, jam_ke_sampai, kegiatan, cp, tp jika belum ada
  try { $pdo->exec("ALTER TABLE data_presensi ADD COLUMN IF NOT EXISTS waktu TIME DEFAULT NULL"); } catch(Exception $e) {}
  try { $pdo->exec("ALTER TABLE data_presensi ADD COLUMN IF NOT EXISTS jam_ke_sampai INT DEFAULT NULL"); } catch(Exception $e) {}
  try { $pdo->exec("ALTER TABLE data_presensi ADD COLUMN IF NOT EXISTS kegiatan TEXT DEFAULT NULL"); } catch(Exception $e) {}
  // FIX: kolom kegiatan/materi/cp/tp bisa sudah ada dari skema lama dengan
  // tipe VARCHAR pendek (mis. VARCHAR(255)) -> menyebabkan error SQLSTATE[22001]
  // "Data too long for column" saat teks kegiatan/materi panjang disimpan.
  // ADD COLUMN IF NOT EXISTS di atas TIDAK mengubah tipe kolom yang sudah ada,
  // jadi kita paksa ubah tipenya ke TEXT (idempotent, aman dipanggil berulang).
  try { $pdo->exec("ALTER TABLE data_presensi MODIFY COLUMN kegiatan TEXT DEFAULT NULL"); } catch(Exception $e) {}
  try { $pdo->exec("ALTER TABLE data_presensi MODIFY COLUMN materi TEXT DEFAULT NULL"); } catch(Exception $e) {}
  try { $pdo->exec("ALTER TABLE data_presensi MODIFY COLUMN cp TEXT DEFAULT NULL"); } catch(Exception $e) {}
  try { $pdo->exec("ALTER TABLE data_presensi MODIFY COLUMN tp TEXT DEFAULT NULL"); } catch(Exception $e) {}
  try { $pdo->exec("ALTER TABLE data_presensi ADD COLUMN IF NOT EXISTS cp TEXT DEFAULT NULL"); } catch(Exception $e) {}
  try { $pdo->exec("ALTER TABLE data_presensi ADD COLUMN IF NOT EXISTS tp TEXT DEFAULT NULL"); } catch(Exception $e) {}

  // Cek duplikat — jika sudah ada, UPDATE saja
  $cek = $pdo->prepare("SELECT id, materi, kegiatan, cp, tp, jam_ke_sampai FROM data_presensi WHERE id_guru=? AND tanggal=? AND jam_ke=? AND kelas=?");
  $cek->execute([$nip, $tgl, $jk, $kls]);
  $existing = $cek->fetch();
  if ($existing) {
    // FIX: jangan timpa materi/kegiatan/cp/tp/jam dengan nilai kosong/1-jam
    // jika request ini hanya mengirim salah satu field — pertahankan nilai
    // yang sudah tersimpan untuk field yang tidak dikirim di request ini.
    $finalMat = ($mat !== '') ? $mat : ($existing['materi']   ?? '');
    $finalKeg = ($keg !== '') ? $keg : ($existing['kegiatan'] ?? '');
    $finalCp  = ($cp  !== '') ? $cp  : ($existing['cp']       ?? '');
    $finalTp  = ($tp  !== '') ? $tp  : ($existing['tp']       ?? '');
    $finalJks = $jksProvided ? $jks : (int)($existing['jam_ke_sampai'] ?? $jk);
    if ($finalJks < $jk) $finalJks = $jk;
    // Update status, materi, kegiatan, cp, tp, dan jam_ke_sampai jika sudah ada
    $pdo->prepare("UPDATE data_presensi SET status=?, materi=?, kegiatan=?, cp=?, tp=?, jam_ke_sampai=? WHERE id_guru=? AND tanggal=? AND jam_ke=? AND kelas=?")
        ->execute([$sts, $finalMat, $finalKeg, $finalCp, $finalTp, $finalJks, $nip, $tgl, $jk, $kls]);
    jsonOut(['success'=>true,'msg'=>'Presensi diperbarui!','waktu'=>$now,'id'=>$existing['id']]);
  }

  $pdo->prepare("INSERT INTO data_presensi (id,tanggal,id_guru,nama_guru,kelas,jam_ke,jam_ke_sampai,status,mapel,materi,kegiatan,cp,tp,foto_url,waktu) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
      ->execute([$id,$tgl,$nip,$nm,$kls,$jk,$jks,$sts,$mp,$mat,$keg,$cp,$tp,$foto,$now]);
  jsonOut(['success'=>true,'msg'=>'Presensi berhasil disimpan!','id'=>$id,'waktu'=>$now]);
}

function simpanFotoPresensi() {
  $d   = getInput();
  $nip = $d['nip'] ?? '';
  $b64 = $d['base64'] ?? '';
  $kls = $d['kelas'] ?? '';
  $jk  = $d['jamKe'] ?? 0;
  if (!$b64 || !$nip) jsonErr('Data tidak lengkap');

  if (!preg_match('/^data:(image\/[a-z]+);base64,/', $b64, $m)) jsonErr('Format foto tidak valid');
  $ext  = $m[1] === 'image/png' ? 'png' : 'jpg';
  $data = base64_decode(preg_replace('/^data:image\/[a-z]+;base64,/', '', $b64));
  if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
  $fn  = 'presensi_' . $nip . '_' . time() . '.' . $ext;
  file_put_contents(UPLOAD_DIR . $fn, $data);
  $url = UPLOAD_URL . $fn;

  // Update foto di presensi terakhir hari ini
  $pdo = getDB();
  $today = date('Y-m-d');
  $pdo->prepare("UPDATE data_presensi SET foto_url=? WHERE id_guru=? AND tanggal=? AND jam_ke=? ORDER BY id DESC LIMIT 1")
      ->execute([$url, $nip, $today, $jk]);
  jsonOut(['success'=>true,'fotoUrl'=>$url]);
}

function getPresensiGuru() {
  $d   = getInput();
  $nip = $d['nip'] ?? '';
  $bln = (int)($d['bulan'] ?? date('n'));
  $thn = (int)($d['tahun'] ?? date('Y'));
  $pdo = getDB();

  // Jika dipanggil dari portal (getPresensiPortalGuru) → return map keyed kelas_jamKe untuk hari ini
  if (!isset($d['bulan']) && !isset($d['tahun'])) {
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT kelas, jam_ke, status, materi, foto_url, waktu FROM data_presensi WHERE id_guru=? AND tanggal=? ORDER BY jam_ke ASC");
    $stmt->execute([$nip, $today]);
    $map = [];
    foreach ($stmt->fetchAll() as $r) {
      $key = $r['kelas'] . '_' . $r['jam_ke'];
      $map[$key] = [
        'status' => $r['status'],
        'materi' => $r['materi'] ?? '',
        'foto'   => $r['foto_url'] ?? '',
        'waktu'  => $r['waktu'] ? substr($r['waktu'],0,5) : '',
      ];
    }
    jsonOut($map);
  }

  $stmt = $pdo->prepare("SELECT * FROM data_presensi WHERE id_guru=? AND MONTH(tanggal)=? AND YEAR(tanggal)=? ORDER BY tanggal DESC, jam_ke ASC");
  $stmt->execute([$nip,$bln,$thn]);
  jsonOut(toCamel($stmt->fetchAll()));
}

function getPresensiAdmin() {
  $d   = getInput();
  $tgl = $d['tanggal'] ?? date('Y-m-d');
  $pdo = getDB();

  // Migrasi otomatis: tambah kolom waktu jika belum ada
  try { $pdo->exec("ALTER TABLE data_presensi ADD COLUMN IF NOT EXISTS waktu TIME DEFAULT NULL"); } catch(Exception $e) {}
  try { $pdo->exec("ALTER TABLE jadwal_mengajar ADD COLUMN IF NOT EXISTS jam_ke_sampai INT DEFAULT NULL"); } catch(Exception $e) {}

  // Map tanggal ke nama hari Indonesia untuk filter jadwal_mengajar
  $hariMap = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa',
              'Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
  $hariIndo = $hariMap[date('l', strtotime($tgl))] ?? '';

  // Ambil SEMUA jadwal hari ini + LEFT JOIN presensi (muncul meski belum presensi)
  $stmt = $pdo->prepare(
    "SELECT j.guru_id AS id_guru, j.nama_guru,
            j.kelas, j.jam_ke,
            COALESCE(j.jam_ke_sampai, j.jam_ke) AS jam_ke_sampai,
            j.mapel,
            COALESCE(j.jam_mulai,'')  AS jam_mulai,
            COALESCE(j.jam_selesai,'') AS jam_selesai,
            ? AS tanggal,
            COALESCE(dp.id,'')       AS id,
            COALESCE(dp.status,'')   AS status,
            COALESCE(dp.materi,'')   AS materi,
            COALESCE(dp.kegiatan,'') AS kegiatan,
            COALESCE(dp.foto_url,'') AS foto_url,
            IF(dp.waktu IS NOT NULL, LEFT(dp.waktu,5),
               IF(dp.created_at IS NOT NULL, LEFT(TIME(dp.created_at),5),'')) AS waktu
     FROM jadwal_mengajar j
     LEFT JOIN data_presensi dp
           ON dp.id_guru = j.guru_id
          AND dp.kelas   = j.kelas
          AND dp.jam_ke  = j.jam_ke
          AND dp.tanggal = ?
     WHERE j.hari = ?
     ORDER BY j.kelas, j.jam_ke"
  );
  $stmt->execute([$tgl, $tgl, $hariIndo]);
  $rows = toCamel($stmt->fetchAll(PDO::FETCH_ASSOC));
  foreach ($rows as &$r) {
    $r['guruId']      = $r['idGuru']      ?? $r['guruId']      ?? '';
    $r['foto']        = $r['fotoUrl']     ?? '';
    $r['jamKeSampai'] = $r['jamKeSampai'] ?? $r['jamKe']       ?? '';
  }
  unset($r);
  jsonOut($rows);
}

// ════════════════════════════════════════════════════════════════
// REKAP
// ════════════════════════════════════════════════════════════════
function getRekapMengajar() {
  $d   = getInput();
  $bln = (int)($d['bulan'] ?? date('n'));
  $thn = (int)($d['tahun'] ?? date('Y'));
  $pdo = getDB();
  // Migrasi otomatis: tambah kolom waktu jika belum ada
  try { $pdo->exec("ALTER TABLE data_presensi ADD COLUMN IF NOT EXISTS waktu TIME DEFAULT NULL"); } catch(Exception $e) {}
  // JOIN jadwal untuk ambil jam_mulai & jam_selesai
  $stmt = $pdo->prepare(
    "SELECT dp.id, dp.tanggal, dp.id_guru, dp.nama_guru, dp.kelas, dp.jam_ke,
             dp.status, dp.mapel, dp.materi, dp.foto_url,
             COALESCE(dp.waktu, TIME(dp.created_at)) AS waktu,
             COALESCE(j.jam_mulai,'') AS jam_mulai,
             COALESCE(j.jam_selesai,'') AS jam_selesai,
             DAYNAME(dp.tanggal) AS hari_en
      FROM data_presensi dp
      LEFT JOIN data_guru dg ON dp.id_guru = dg.id
      LEFT JOIN jadwal_mengajar j ON j.guru_id=dp.id_guru AND j.kelas=dp.kelas AND j.jam_ke=dp.jam_ke
      WHERE MONTH(dp.tanggal)=? AND YEAR(dp.tanggal)=?
        AND (dp.tanpa_jadwal IS NULL OR dp.tanpa_jadwal = 0)
      ORDER BY dp.tanggal DESC, dp.nama_guru, dp.jam_ke");
  $stmt->execute([$bln,$thn]);
  $rows = toCamel($stmt->fetchAll());

  // Map hari Inggris → Indonesia
  $hariMap = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu',
               'Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
  foreach ($rows as &$r) {
    $r['hari']     = $hariMap[$r['hari_en'] ?? ''] ?? ($r['hari_en'] ?? '');  // FIX: hari_en bukan hariEn
    $r['guruId']   = $r['idGuru'] ?? $r['guruId'] ?? '';
    $r['namaGuru'] = $r['namaGuru'] ?? '';
    $r['jamMulai'] = $r['jamMulai'] ?? '';
    $r['foto']     = $r['fotoUrl'] ?? '';
    unset($r['hari_en']);
  }
  unset($r);
  jsonOut(['detail' => $rows, 'total' => count($rows)]);
}

function getRekapKehadiran() {
  $d   = getInput();
  $bln = (int)($d['bulan'] ?? date('n'));
  $thn = (int)($d['tahun'] ?? date('Y'));
  $mulai   = sprintf('%04d-%02d-01', $thn, $bln);
  $selesai = date('Y-m-t', strtotime($mulai));
  $pdo = getDB();

  // PENTING: 'Hadir' di DB = guru benar-benar mengajar. 'Mengajar' di DB = Tdk Mengajar di UI.
  $stmt = $pdo->prepare("SELECT id_guru, nama_guru, COUNT(*) as total_mengajar, COUNT(DISTINCT tanggal) as hari_hadir FROM data_presensi WHERE tanggal BETWEEN ? AND ? AND status = 'Hadir' AND (tanpa_jadwal IS NULL OR tanpa_jadwal = 0) GROUP BY id_guru, nama_guru ORDER BY nama_guru");
  $stmt->execute([$mulai,$selesai]);
  jsonOut(toCamel($stmt->fetchAll()));
}

function getLaporanAbsensi() {
  $d   = getInput();
  $kls   = trim($d['kelas']  ?? '');
  $guruId= trim($d['guruId'] ?? $d['guru_id'] ?? '');
  $mapel = trim($d['mapel']  ?? '');
  $bln   = (int)($d['bulan'] ?? date('n'));
  $thn   = (int)($d['tahun'] ?? date('Y'));
  $mulai   = sprintf('%04d-%02d-01', $thn, $bln);
  $selesai = date('Y-m-t', strtotime($mulai));
  $pdo = getDB();

  $where  = "WHERE DATE(a.tanggal) BETWEEN ? AND ?";
  $params = [$mulai, $selesai];
  if ($kls && $kls !== 'SEMUA')
    { $where .= " AND UPPER(TRIM(a.kelas))=UPPER(?)"; $params[] = $kls; }
  if ($guruId && $guruId !== 'SEMUA')
    { $where .= " AND (UPPER(TRIM(a.id_guru))=UPPER(?) OR UPPER(TRIM(a.nama_guru))=UPPER(?))"; $params[] = $guruId; $params[] = $guruId; }
  if ($mapel && $mapel !== 'SEMUA')
    { $where .= " AND UPPER(TRIM(a.mapel))=UPPER(?)"; $params[] = $mapel; }

  // Query sederhana, dedup dilakukan di PHP
  $stmt = $pdo->prepare(
    "SELECT a.id, a.tanggal, a.id_guru, a.nama_guru, a.kelas, a.jam_ke, a.mapel,
            a.nis, COALESCE(a.nama_siswa,'') AS nama_siswa, a.status,
            DAYNAME(a.tanggal) AS hari_en
     FROM absensi_siswa a
     $where
     ORDER BY a.tanggal, a.kelas, COALESCE(a.nama_siswa,''), a.id DESC");
  $stmt->execute($params);
  $allRows = $stmt->fetchAll();

  // Dedup di PHP: ambil record terbaru (id terbesar) per kombinasi unik
  $seen = [];
  $rawRows = [];
  foreach ($allRows as $r) {
    $key = ($r['nis']??'').'|'.($r['kelas']??'').'|'.($r['jam_ke']??'').'|'.substr($r['tanggal']??'',0,10).'|'.strtoupper($r['mapel']??'').'|'.strtoupper($r['id_guru']??'');
    if (!isset($seen[$key])) {
      $seen[$key] = true;
      $rawRows[] = $r;
    }
  }

  $hariMap = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu',
              'Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
  $rows = [];
  foreach ($rawRows as $r) {
    $rows[] = [
      'id'        => $r['id'],
      'tanggal'   => $r['tanggal'] ? substr($r['tanggal'],0,10) : '',
      'hari'      => $hariMap[$r['hari_en'] ?? ''] ?? '',
      'kelas'     => $r['kelas'] ?? '',
      'jamKe'     => $r['jam_ke'] ?? '',
      'mapel'     => $r['mapel'] ?? '',
      'nis'       => $r['nis'] ?? '',
      'namaSiswa' => $r['nama_siswa'] ?? '',
      'status'    => $r['status'] ?? '',
      'idGuru'    => $r['id_guru'] ?? '',
      'namaGuru'  => $r['nama_guru'] ?? '',
    ];
  }

  // Debug info jika kosong
  $debugInfo = null;
  if (!$rows) {
    $cntStmt = $pdo->prepare("SELECT COUNT(*) FROM absensi_siswa WHERE DATE(tanggal) BETWEEN ? AND ?");
    $cntStmt->execute([$mulai, $selesai]);
    $totalBulan = (int)$cntStmt->fetchColumn();
    $debugInfo = ['totalBulan' => $totalBulan,
      'petunjuk' => $totalBulan===0
        ? 'Belum ada data absensi bulan ini.'
        : 'Ada '.$totalBulan.' data bulan ini tapi tidak cocok filter.',
      'filter' => ['kelas'=>$kls,'guruId'=>$guruId,'mapel'=>$mapel,'bulan'=>$bln,'tahun'=>$thn]];
  }

  // Rekap per siswa
  $rekap = [];
  foreach ($rows as $r) {
    $siswaKey = ($r['kelas']??'').'|'.($r['nis']??'').'|'.($r['namaSiswa']??'');
    if (!isset($rekap[$siswaKey])) {
      $rekap[$siswaKey] = ['kelas'=>$r['kelas'],'nis'=>$r['nis'],'namaSiswa'=>$r['namaSiswa'],
                           'hadir'=>0,'alpa'=>0,'sakit'=>0,'izin'=>0,'total'=>0];
    }
    $st = strtolower($r['status'] ?? '');
    if ($st==='hdr'||$st==='hadir')     $rekap[$siswaKey]['hadir']++;
    elseif ($st==='alp'||$st==='alpa')  $rekap[$siswaKey]['alpa']++;
    elseif ($st==='skt'||$st==='sakit') $rekap[$siswaKey]['sakit']++;
    elseif ($st==='iz' ||$st==='izin')  $rekap[$siswaKey]['izin']++;
    $rekap[$siswaKey]['total']++;
  }

  jsonOut(['rows'=>$rows,'summary'=>array_values($rekap),'total'=>count($rows),'debug'=>$debugInfo]);
}

// ════════════════════════════════════════════════════════════════
// JADWAL
// ════════════════════════════════════════════════════════════════
function getJadwal() {
  $d   = getInput();
  $kls = $d['kelas'] ?? '';
  $pdo = getDB();
  try { $pdo->exec("ALTER TABLE jadwal_mengajar ADD COLUMN IF NOT EXISTS jam_ke_sampai INT DEFAULT NULL"); } catch (Exception $e) {}

  // ── Ambil semester aktif untuk filter ──────────────────────
  $aktif_tp  = '';
  $aktif_sem = '';
  try {
    $a = $pdo->query("SELECT tahun_pelajaran, semester FROM semester_aktif WHERE id=1 LIMIT 1")->fetch();
    if ($a) { $aktif_tp = trim($a['tahun_pelajaran']); $aktif_sem = trim($a['semester']); }
  } catch (Exception $e) {}

  $sql = "SELECT j.*,
            COALESCE(j.jam_ke_sampai, j.jam_ke) AS jam_ke_sampai,
            COALESCE(NULLIF(r.wali_kelas,''), w.nama_wali, '') AS wali_kelas_merged,
            COALESCE(r.jumlah_siswa, 0) AS jumlah_siswa_merged
          FROM jadwal_mengajar j
          LEFT JOIN rombel r ON UPPER(TRIM(j.kelas))=UPPER(TRIM(r.nama_rombel))
          LEFT JOIN wali_kelas w ON UPPER(TRIM(w.kelas))=UPPER(TRIM(j.kelas))";

  // Bangun kondisi WHERE dengan filter semester aktif
  $where = [];
  $params = [];
  if ($kls)       { $where[] = "j.kelas=?";            $params[] = $kls; }
  if ($aktif_tp)  { $where[] = "j.tahun_pelajaran=?";  $params[] = $aktif_tp; }
  if ($aktif_sem) { $where[] = "j.semester=?";          $params[] = $aktif_sem; }

  $order = " ORDER BY FIELD(j.hari,'Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'), j.jam_ke";
  if ($where) {
    $s = $pdo->prepare($sql . " WHERE " . implode(" AND ", $where) . $order);
    $s->execute($params);
  } else {
    $s = $pdo->query($sql . $order);
  }
  $rows = $s->fetchAll();
  $result = toCamel($rows);
  foreach ($result as &$r) {
    $r['waliKelas']   = $r['wali_kelas_merged'] ?? $r['waliKelas'] ?? '';
    $r['jumlahSiswa'] = (int)($r['jumlah_siswa_merged'] ?? $r['jumlahSiswa'] ?? 0);
    unset($r['wali_kelas_merged'], $r['jumlah_siswa_merged']);
  }
  unset($r);
  jsonOut($result);
}

function getJadwalGuru() {
  $d   = getInput();
  $nip = $d['nip'] ?? '';
  $hari= $d['hari'] ?? '';
  $pdo = getDB();

  // ── Ambil semester aktif untuk filter ──────────────────────
  $aktif_tp = ''; $aktif_sem = '';
  try {
    $a = $pdo->query("SELECT tahun_pelajaran, semester FROM semester_aktif WHERE id=1 LIMIT 1")->fetch();
    if ($a) { $aktif_tp = trim($a['tahun_pelajaran']); $aktif_sem = trim($a['semester']); }
  } catch (Exception $e) {}

  $baseSql = "SELECT j.*, COALESCE(NULLIF(r.wali_kelas,''), w.nama_wali, '') AS wali_kelas_merged,
              COALESCE(r.jumlah_siswa, 0) AS jumlah_siswa_merged
       FROM jadwal_mengajar j
       LEFT JOIN rombel r ON UPPER(TRIM(j.kelas))=UPPER(TRIM(r.nama_rombel))
       LEFT JOIN wali_kelas w ON UPPER(TRIM(w.kelas))=UPPER(TRIM(j.kelas))";

  if ($hari) {
    $where = "WHERE j.guru_id=? AND j.hari=?"; $params = [$nip, $hari];
    if ($aktif_tp)  { $where .= " AND j.tahun_pelajaran=?"; $params[] = $aktif_tp; }
    if ($aktif_sem) { $where .= " AND j.semester=?";         $params[] = $aktif_sem; }
    $s = $pdo->prepare($baseSql . " " . $where . " ORDER BY j.jam_ke");
    $s->execute($params);
    $rows = toCamel($s->fetchAll());
    foreach ($rows as &$r) {
      $r['waliKelas']   = $r['wali_kelas_merged'] ?? '';
      $r['jumlahSiswa'] = (int)($r['jumlah_siswa_merged'] ?? 0);
      unset($r['wali_kelas_merged'], $r['jumlah_siswa_merged']);
    }
    unset($r);
    jsonOut($rows);
  } else {
    // getJadwalGuruAll — kembalikan data dikelompokkan per hari
    $where = "WHERE j.guru_id=?"; $params = [$nip];
    if ($aktif_tp)  { $where .= " AND j.tahun_pelajaran=?"; $params[] = $aktif_tp; }
    if ($aktif_sem) { $where .= " AND j.semester=?";         $params[] = $aktif_sem; }
    $s = $pdo->prepare($baseSql . " " . $where . " ORDER BY FIELD(j.hari,'Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'), j.jam_ke");
    $s->execute($params);
    $rows = toCamel($s->fetchAll());
    $grouped = [];
    foreach ($rows as $r) {
      $r['waliKelas']   = $r['wali_kelas_merged'] ?? '';
      $r['jumlahSiswa'] = (int)($r['jumlah_siswa_merged'] ?? 0);
      unset($r['wali_kelas_merged'], $r['jumlah_siswa_merged']);
      $h = $r['hari'] ?? 'Senin';
      if (!isset($grouped[$h])) $grouped[$h] = [];
      $grouped[$h][] = $r;
    }
    jsonOut($grouped);
  }
}

function simpanJadwal() {
  $d   = getInput();
  $pdo = getDB();
  // Migrasi kolom (aman kalau sudah ada)
  try { $pdo->exec("ALTER TABLE jadwal_mengajar ADD COLUMN IF NOT EXISTS jam_ke_sampai INT DEFAULT NULL"); } catch (Exception $e) {}
  try { $pdo->exec("ALTER TABLE jadwal_mengajar ADD COLUMN IF NOT EXISTS tahun_pelajaran VARCHAR(20) DEFAULT ''"); } catch (Exception $e) {}
  try { $pdo->exec("ALTER TABLE jadwal_mengajar ADD COLUMN IF NOT EXISTS semester VARCHAR(10) DEFAULT ''"); } catch (Exception $e) {}

  $id  = trim($d['id'] ?? '');
  if (!$id) $id = 'JD' . time() . rand(10,99);

  $jamKe       = (int)($d['jamKe']??$d['jam_ke']??1);
  $jamKeSampai = isset($d['jamKeSampai']) && $d['jamKeSampai'] ? (int)$d['jamKeSampai'] : $jamKe;
  if ($jamKeSampai < $jamKe) $jamKeSampai = $jamKe;

  // ── Ambil semester aktif sebagai DEFAULT bila tidak dikirim dari form ──
  $tp  = trim($d['tahunPelajaran'] ?? $d['tahun_pelajaran'] ?? '');
  $sem = trim($d['semester'] ?? '');
  if (!$tp || !$sem) {
    try {
      $a = $pdo->query("SELECT tahun_pelajaran, semester FROM semester_aktif WHERE id=1")->fetch();
      if ($a) {
        if (!$tp)  $tp  = $a['tahun_pelajaran'] ?? '';
        if (!$sem) $sem = $a['semester'] ?? 'Ganjil';
      }
    } catch (Exception $e) {}
  }

  $pdo->prepare(
    "INSERT INTO jadwal_mengajar (id,guru_id,nama_guru,hari,jam_ke,jam_ke_sampai,kelas,mapel,jam_mulai,jam_selesai,tahun_pelajaran,semester)
     VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
     ON DUPLICATE KEY UPDATE guru_id=?,nama_guru=?,hari=?,jam_ke=?,jam_ke_sampai=?,kelas=?,mapel=?,jam_mulai=?,jam_selesai=?,tahun_pelajaran=?,semester=?"
  )->execute([
    $id,$d['guruId']??$d['guru_id']??'',$d['namaGuru']??$d['nama_guru']??'',$d['hari']??'Senin',
    $jamKe,$jamKeSampai,$d['kelas']??'',$d['mapel']??'',$d['jamMulai']??null,$d['jamSelesai']??null,$tp,$sem,
    $d['guruId']??$d['guru_id']??'',$d['namaGuru']??$d['nama_guru']??'',$d['hari']??'Senin',
    $jamKe,$jamKeSampai,$d['kelas']??'',$d['mapel']??'',$d['jamMulai']??null,$d['jamSelesai']??null,$tp,$sem,
  ]);
  jsonOut(['success'=>true,'id'=>$id,'msg'=>'Jadwal berhasil disimpan!','tahunPelajaran'=>$tp,'semester'=>$sem]);
}

function hapusJadwal() {
  $id  = getInput()['id'] ?? '';
  if (!$id) jsonOut(['success'=>false,'msg'=>'ID jadwal tidak ditemukan']);
  $pdo = getDB();

  // Ambil detail jadwal sebelum dihapus (untuk cari data yg akan dihapus)
  $j = $pdo->prepare("SELECT guru_id, kelas, jam_ke, hari FROM jadwal_mengajar WHERE id=?");
  $j->execute([$id]);
  $jadwal = $j->fetch(PDO::FETCH_ASSOC);

  $hapusPresensi  = 0;
  $hapusAbsensi   = 0;

  if ($jadwal) {
    $guruId = $jadwal['guru_id'];
    $kelas  = $jadwal['kelas'];
    $jamKe  = (int)$jadwal['jam_ke'];
    $hari   = $jadwal['hari'];

    // Hapus data_presensi yang cocok: guru + kelas + jam_ke + hari
    // (DAYNAME disesuaikan dengan nama hari Indonesia)
    $hariMap = ['Senin'=>'Monday','Selasa'=>'Tuesday','Rabu'=>'Wednesday',
                'Kamis'=>'Thursday','Jumat'=>'Friday','Sabtu'=>'Saturday','Minggu'=>'Sunday'];
    $hariEn  = $hariMap[$hari] ?? $hari;

    $delPres = $pdo->prepare(
      "DELETE FROM data_presensi
       WHERE id_guru=? AND kelas=? AND jam_ke=?
         AND DAYNAME(tanggal)=?"
    );
    $delPres->execute([$guruId, $kelas, $jamKe, $hariEn]);
    $hapusPresensi = $delPres->rowCount();

    // Hapus absensi_siswa yang cocok: guru + kelas + jam_ke + hari
    $delAbs = $pdo->prepare(
      "DELETE FROM absensi_siswa
       WHERE id_guru=? AND kelas=? AND jam_ke=?
         AND DAYNAME(tanggal)=?"
    );
    $delAbs->execute([$guruId, $kelas, $jamKe, $hariEn]);
    $hapusAbsensi = $delAbs->rowCount();
  }

  // Hapus jadwal
  $pdo->prepare("DELETE FROM jadwal_mengajar WHERE id=?")->execute([$id]);

  jsonOut([
    'success'        => true,
    'msg'            => 'Jadwal dihapus. Presensi terhapus: '.$hapusPresensi.', Absensi siswa terhapus: '.$hapusAbsensi,
    'hapusPresensi'  => $hapusPresensi,
    'hapusAbsensi'   => $hapusAbsensi,
  ]);
}

function hapusJadwalMassal() {
  $d   = getInput();
  $ids = $d['ids'] ?? [];
  if (is_string($ids)) $ids = json_decode($ids, true) ?: [];
  if (!is_array($ids) || !count($ids)) jsonOut(['success'=>false,'msg'=>'Tidak ada ID yang dipilih']);
  $ids = array_values(array_filter(array_map('strval', $ids)));

  $pdo = getDB();

  $hariMap = ['Senin'=>'Monday','Selasa'=>'Tuesday','Rabu'=>'Wednesday',
              'Kamis'=>'Thursday','Jumat'=>'Friday','Sabtu'=>'Saturday','Minggu'=>'Sunday'];

  // Ambil semua detail jadwal yang akan dihapus
  $ph      = implode(',', array_fill(0, count($ids), '?'));
  $jStmt   = $pdo->prepare("SELECT guru_id, kelas, jam_ke, hari FROM jadwal_mengajar WHERE id IN ($ph)");
  $jStmt->execute($ids);
  $jadwals = $jStmt->fetchAll(PDO::FETCH_ASSOC);

  $hapusPresensi = 0;
  $hapusAbsensi  = 0;

  foreach ($jadwals as $j) {
    $guruId = $j['guru_id'];
    $kelas  = $j['kelas'];
    $jamKe  = (int)$j['jam_ke'];
    $hariEn = $hariMap[$j['hari']] ?? $j['hari'];

    $delPres = $pdo->prepare(
      "DELETE FROM data_presensi
       WHERE id_guru=? AND kelas=? AND jam_ke=?
         AND DAYNAME(tanggal)=?"
    );
    $delPres->execute([$guruId, $kelas, $jamKe, $hariEn]);
    $hapusPresensi += $delPres->rowCount();

    $delAbs = $pdo->prepare(
      "DELETE FROM absensi_siswa
       WHERE id_guru=? AND kelas=? AND jam_ke=?
         AND DAYNAME(tanggal)=?"
    );
    $delAbs->execute([$guruId, $kelas, $jamKe, $hariEn]);
    $hapusAbsensi += $delAbs->rowCount();
  }

  // Hapus semua jadwal yang dipilih
  $pdo->prepare("DELETE FROM jadwal_mengajar WHERE id IN ($ph)")->execute($ids);

  jsonOut([
    'success'       => true,
    'msg'           => 'Berhasil menghapus '.count($ids).' jadwal. Presensi terhapus: '.$hapusPresensi.', Absensi siswa terhapus: '.$hapusAbsensi,
    'hapusPresensi' => $hapusPresensi,
    'hapusAbsensi'  => $hapusAbsensi,
  ]);
}

// ════════════════════════════════════════════════════════════════
// HAPUS CASCADE MANUAL — bersihkan presensi & absensi lama
// yang jadwalnya sudah tidak ada
// ════════════════════════════════════════════════════════════════
function hapusCascadeManual() {
  $pdo = getDB();
  $d   = getInput();
  $mode = $d['mode'] ?? 'preview'; // preview | hapus

  // Cari data_presensi yang guru+kelas+jam_ke+hari tidak ada di jadwal_mengajar
  $hariMap = ['Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu',
              'Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu','Sunday'=>'Minggu'];

  // Preview: hitung berapa baris presensi & absensi yang "orphan" (jadwal sudah dihapus)
  $stmtPres = $pdo->query(
    "SELECT dp.id, dp.id_guru, dp.kelas, dp.jam_ke, DAYNAME(dp.tanggal) AS hari_en, dp.tanggal
     FROM data_presensi dp
     WHERE NOT EXISTS (
       SELECT 1 FROM jadwal_mengajar j
       WHERE j.guru_id = dp.id_guru
         AND j.kelas   = dp.kelas
         AND j.jam_ke  = dp.jam_ke
     )"
  );
  $orphanPres = $stmtPres->fetchAll(PDO::FETCH_ASSOC);

  $stmtAbs = $pdo->query(
    "SELECT a.id, a.id_guru, a.kelas, a.jam_ke
     FROM absensi_siswa a
     WHERE NOT EXISTS (
       SELECT 1 FROM jadwal_mengajar j
       WHERE j.guru_id = a.id_guru
         AND j.kelas   = a.kelas
         AND j.jam_ke  = a.jam_ke
     )"
  );
  $orphanAbs = $stmtAbs->fetchAll(PDO::FETCH_ASSOC);

  if ($mode === 'hapus') {
    // Hapus semua orphan presensi
    if (count($orphanPres) > 0) {
      $ids = array_column($orphanPres, 'id');
      $ph  = implode(',', array_fill(0, count($ids), '?'));
      $pdo->prepare("DELETE FROM data_presensi WHERE id IN ($ph)")->execute($ids);
    }
    // Hapus semua orphan absensi siswa
    if (count($orphanAbs) > 0) {
      $ids = array_column($orphanAbs, 'id');
      $ph  = implode(',', array_fill(0, count($ids), '?'));
      $pdo->prepare("DELETE FROM absensi_siswa WHERE id IN ($ph)")->execute($ids);
    }
    jsonOut([
      'success'       => true,
      'msg'           => 'Selesai! Presensi terhapus: '.count($orphanPres).', Absensi siswa terhapus: '.count($orphanAbs),
      'hapusPresensi' => count($orphanPres),
      'hapusAbsensi'  => count($orphanAbs),
    ]);
  } else {
    jsonOut([
      'success'         => true,
      'orphanPresensi'  => count($orphanPres),
      'orphanAbsensi'   => count($orphanAbs),
      'msg'             => 'Preview: '.count($orphanPres).' presensi & '.count($orphanAbs).' absensi siswa tidak punya jadwal',
    ]);
  }
}

// ════════════════════════════════════════════════════════════════
// GET JADWAL HARI INI (untuk kirim WA pengingat jadwal)
// ════════════════════════════════════════════════════════════════
function getJadwalHariIni() {
  $d    = getInput();
  $pdo  = getDB();

  // Hari bisa dikirim dari JS, atau otomatis dari server
  $hariMap = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa',
              'Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
  $hari = $d['hari'] ?? ($hariMap[date('l')] ?? '');

  if (!$hari) jsonOut(['success'=>false, 'msg'=>'Hari tidak valid', 'data'=>[]]);

  // Migrasi kolom no_wa agar aman sebelum query
  try { $pdo->exec("ALTER TABLE data_guru ADD COLUMN IF NOT EXISTS no_wa VARCHAR(20) DEFAULT ''"); } catch (Exception $e) {}

  $stmt = $pdo->prepare(
    "SELECT j.id, j.guru_id, j.nama_guru, j.kelas, j.jam_ke,
            COALESCE(j.jam_ke_sampai, j.jam_ke) AS jam_ke_sampai,
            j.mapel,
            COALESCE(j.jam_mulai,'')   AS jam_mulai,
            COALESCE(j.jam_selesai,'') AS jam_selesai,
            COALESCE(dg.no_wa,'')      AS no_wa
     FROM jadwal_mengajar j
     LEFT JOIN data_guru dg ON dg.id = j.guru_id
     WHERE j.hari = ?
     ORDER BY j.nama_guru ASC, j.jam_ke ASC"
  );
  $stmt->execute([$hari]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  jsonOut(['success'=>true, 'data'=>$rows, 'hari'=>$hari, 'total'=>count($rows)]);
}

function getRiwayatKelas() {
  $d   = getInput();
  $kls = $d['kelas'] ?? '';
  $jk  = $d['jamKe'] ?? 0;
  $nip = $d['nip'] ?? '';
  $pdo = getDB();
  $stmt= $pdo->prepare("SELECT * FROM data_presensi WHERE kelas=? AND jam_ke=? AND id_guru=? ORDER BY tanggal DESC LIMIT 10");
  $stmt->execute([$kls,$jk,$nip]);
  jsonOut($stmt->fetchAll());
}

// ════════════════════════════════════════════════════════════════
// ROMBEL
// ════════════════════════════════════════════════════════════════
function getRombel() {
  $pdo = getDB();
  $rows = $pdo->query(
    "SELECT r.*, COALESCE(NULLIF(r.wali_kelas,''), w.nama_wali, '') AS wali_kelas_merged
     FROM rombel r
     LEFT JOIN wali_kelas w ON UPPER(TRIM(w.kelas))=UPPER(TRIM(r.nama_rombel))
     ORDER BY r.tingkat, r.nama_rombel"
  )->fetchAll();
  $result = toCamel($rows);
  foreach ($result as &$rb) {
    $rb['wali_kelas'] = $rb['wali_kelas_merged'] ?? $rb['wali_kelas'] ?? '';
    $rb['waliKelas']  = $rb['wali_kelas'];
    unset($rb['wali_kelas_merged']);
  }
  unset($rb);
  jsonOut($result);
}

function simpanRombel() {
  $d   = getInput();
  $pdo = getDB();
  $id  = $d['id'] ?? null;
  $nm  = trim($d['namaRombel'] ?? $d['nm'] ?? $d['nama'] ?? '');
  $tk  = trim($d['tingkat'] ?? '');
  $jm  = (int)($d['jumlah'] ?? $d['jumlahSiswa'] ?? 0);
  $wl  = trim($d['waliKelas'] ?? '');
  if (!$nm) jsonOut(['success'=>false,'msg'=>'Nama rombel wajib!']);
  try {
    if ($id) {
      $pdo->prepare("UPDATE rombel SET nama_rombel=?,tingkat=?,jumlah_siswa=?,wali_kelas=? WHERE id=?")->execute([$nm,$tk,$jm,$wl,$id]);
    } else {
      $pdo->prepare("INSERT INTO rombel (nama_rombel,tingkat,jumlah_siswa,wali_kelas) VALUES (?,?,?,?)")->execute([$nm,$tk,$jm,$wl]);
      $id = $pdo->lastInsertId();
    }
    jsonOut(['success'=>true,'id'=>$id,'msg'=>'Rombel disimpan!']);
  } catch (Exception $e) {
    jsonOut(['success'=>false,'msg'=>'DB Error: '.$e->getMessage()]);
  }
}

function hapusRombel() {
  $d  = getInput();
  $id = $d['id'] ?? null;
  $nm = $d['nm'] ?? $d['nama'] ?? null;
  $pdo= getDB();
  if ($id) $pdo->prepare("DELETE FROM rombel WHERE id=?")->execute([$id]);
  elseif ($nm) $pdo->prepare("DELETE FROM rombel WHERE nama_rombel=?")->execute([$nm]);
  jsonOut(['success'=>true]);
}

// ════════════════════════════════════════════════════════════════
// MAPEL
// ════════════════════════════════════════════════════════════════
function getMapel() {
  $rows = getDB()->query("SELECT id, nama_mapel AS nama, keterangan FROM mata_pelajaran ORDER BY nama_mapel")->fetchAll();
  jsonOut($rows);
}

function simpanMapel() {
  $d   = getInput();
  $nm  = $d['nama'] ?? '';
  $kt  = $d['keterangan'] ?? '';
  $id  = $d['id'] ?? null;
  if (!$nm) jsonErr('Nama mapel wajib!');
  $pdo = getDB();
  if ($id) {
    $pdo->prepare("UPDATE mata_pelajaran SET nama_mapel=?,keterangan=? WHERE id=?")->execute([$nm,$kt,$id]);
  } else {
    $pdo->prepare("INSERT INTO mata_pelajaran (nama_mapel,keterangan) VALUES (?,?) ON DUPLICATE KEY UPDATE keterangan=?")->execute([$nm,$kt,$kt]);
  }
  jsonOut(['success'=>true,'msg'=>'Mapel disimpan!']);
}

function hapusMapel() {
  $d  = getInput();
  $id = $d['id'] ?? null;
  $nm = $d['nama'] ?? null;
  $pdo= getDB();
  if ($id) $pdo->prepare("DELETE FROM mata_pelajaran WHERE id=?")->execute([$id]);
  elseif ($nm) $pdo->prepare("DELETE FROM mata_pelajaran WHERE nama_mapel=?")->execute([$nm]);
  jsonOut(['success'=>true]);
}

function hapusMapelMassal() {
  $d    = getInput();
  $ids  = $d['ids'] ?? [];
  $nms  = $d['namas'] ?? [];
  $pdo  = getDB();
  $count = 0;
  if (!empty($ids) && is_array($ids)) {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("DELETE FROM mata_pelajaran WHERE id IN ($placeholders)");
    $stmt->execute(array_map('intval', $ids));
    $count = $stmt->rowCount();
  } elseif (!empty($nms) && is_array($nms)) {
    $placeholders = implode(',', array_fill(0, count($nms), '?'));
    $stmt = $pdo->prepare("DELETE FROM mata_pelajaran WHERE nama_mapel IN ($placeholders)");
    $stmt->execute($nms);
    $count = $stmt->rowCount();
  }
  jsonOut(['success'=>true,'msg'=>"$count mapel berhasil dihapus!",'count'=>$count]);
}

// ════════════════════════════════════════════════════════════════
// TINGKAT
// ════════════════════════════════════════════════════════════════
function ensureTingkatTable() {
  try {
    getDB()->exec("CREATE TABLE IF NOT EXISTS tingkat (
      id INT PRIMARY KEY AUTO_INCREMENT,
      nama VARCHAR(20) NOT NULL,
      keterangan VARCHAR(100) DEFAULT '',
      urutan INT DEFAULT 99,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      UNIQUE KEY uk_nama_tingkat (nama)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  } catch (Exception $e) { /* abaikan jika sudah ada */ }
}

function getDataTingkat() {
  // Pastikan tidak ada output lain mengkontaminasi JSON (mencegah "muter loading")
  while (ob_get_level() > 0) { ob_end_clean(); }
  ob_start();
  try {
    ensureTingkatTable();
    $pdo  = getDB();
    $rows = $pdo->query("SELECT id, nama, keterangan, urutan FROM tingkat ORDER BY urutan ASC, nama ASC")
                ->fetchAll(PDO::FETCH_ASSOC);
    jsonOut(['success'=>true, 'data'=>$rows]);
  } catch (Throwable $e) {
    // Coba buat tabel lagi lalu query ulang
    try {
      $pdo2 = getDB();
      $pdo2->exec("CREATE TABLE IF NOT EXISTS tingkat (
        id INT PRIMARY KEY AUTO_INCREMENT,
        nama VARCHAR(20) NOT NULL,
        keterangan VARCHAR(100) DEFAULT '',
        urutan INT DEFAULT 99,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_nama_tingkat (nama)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
      $rows2 = $pdo2->query("SELECT id, nama, keterangan, urutan FROM tingkat ORDER BY urutan ASC, nama ASC")
                    ->fetchAll(PDO::FETCH_ASSOC);
      jsonOut(['success'=>true, 'data'=>$rows2]);
    } catch (Throwable $e2) {
      jsonOut(['success'=>true, 'data'=>[], 'note'=>'Tabel tingkat baru/empty: '.$e2->getMessage()]);
    }
  }
}

function simpanTingkat() {
  try {
    ensureTingkatTable();
    $d   = getInput();
    $id  = isset($d['id']) && $d['id'] ? (int)$d['id'] : null;
    $nm  = strtoupper(trim($d['nama'] ?? ''));
    $kt  = trim($d['keterangan'] ?? '');
    if (!$nm) jsonOut(['success'=>false,'msg'=>'Nama tingkat wajib diisi!']);
    $pdo = getDB();
    if ($id) {
      // Cek duplikat nama (selain id ini sendiri)
      $cek = $pdo->prepare("SELECT id FROM tingkat WHERE nama=? AND id!=?");
      $cek->execute([$nm, $id]);
      if ($cek->fetch()) jsonOut(['success'=>false,'msg'=>"Tingkat '$nm' sudah ada!"]);
      $pdo->prepare("UPDATE tingkat SET nama=?,keterangan=? WHERE id=?")->execute([$nm,$kt,$id]);
      jsonOut(['success'=>true,'msg'=>"Tingkat '$nm' berhasil diperbarui!"]);
    } else {
      $cek = $pdo->prepare("SELECT id FROM tingkat WHERE nama=?");
      $cek->execute([$nm]);
      if ($cek->fetch()) jsonOut(['success'=>false,'msg'=>"Tingkat '$nm' sudah ada!"]);
      $pdo->prepare("INSERT INTO tingkat (nama,keterangan) VALUES (?,?)")->execute([$nm,$kt]);
      jsonOut(['success'=>true,'msg'=>"Tingkat '$nm' berhasil ditambahkan!"]);
    }
  } catch (Exception $e) {
    jsonOut(['success'=>false,'msg'=>'Gagal simpan: '.$e->getMessage()]);
  }
}

function hapusTingkat() {
  try {
    ensureTingkatTable();
    $d  = getInput();
    $id = isset($d['id']) && $d['id'] ? (int)$d['id'] : null;
    $nm = trim($d['nama'] ?? '');
    $pdo= getDB();
    if ($id) $pdo->prepare("DELETE FROM tingkat WHERE id=?")->execute([$id]);
    elseif ($nm) $pdo->prepare("DELETE FROM tingkat WHERE nama=?")->execute([$nm]);
    jsonOut(['success'=>true,'msg'=>'Tingkat berhasil dihapus!']);
  } catch (Exception $e) {
    jsonOut(['success'=>false,'msg'=>'Gagal hapus: '.$e->getMessage()]);
  }
}

function hapusTingkatMassal() {
  try {
    ensureTingkatTable();
    $d    = getInput();
    $ids  = $d['ids'] ?? [];
    $nms  = $d['namas'] ?? [];
    $pdo  = getDB();
    $count= 0;
    if (!empty($ids) && is_array($ids)) {
      $ph   = implode(',', array_fill(0, count($ids), '?'));
      $stmt = $pdo->prepare("DELETE FROM tingkat WHERE id IN ($ph)");
      $stmt->execute(array_map('intval', $ids));
      $count= $stmt->rowCount();
    } elseif (!empty($nms) && is_array($nms)) {
      $ph   = implode(',', array_fill(0, count($nms), '?'));
      $stmt = $pdo->prepare("DELETE FROM tingkat WHERE nama IN ($ph)");
      $stmt->execute($nms);
      $count= $stmt->rowCount();
    }
    jsonOut(['success'=>true,'msg'=>"$count tingkat berhasil dihapus!"]);
  } catch (Exception $e) {
    jsonOut(['success'=>false,'msg'=>'Gagal: '.$e->getMessage()]);
  }
}

function hapusRombelMassal() {
  $d    = getInput();
  $ids  = $d['ids'] ?? [];
  $nms  = $d['namas'] ?? [];
  $pdo  = getDB();
  $count = 0;
  if (!empty($ids) && is_array($ids)) {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("DELETE FROM rombel WHERE id IN ($placeholders)");
    $stmt->execute(array_map('intval', $ids));
    $count = $stmt->rowCount();
  } elseif (!empty($nms) && is_array($nms)) {
    $placeholders = implode(',', array_fill(0, count($nms), '?'));
    $stmt = $pdo->prepare("DELETE FROM rombel WHERE nama_rombel IN ($placeholders)");
    $stmt->execute($nms);
    $count = $stmt->rowCount();
  }
  jsonOut(['success'=>true,'msg'=>"$count rombel berhasil dihapus!",'count'=>$count]);
}

// ════════════════════════════════════════════════════════════════
// TAHUN PELAJARAN & SEMESTER
// ════════════════════════════════════════════════════════════════
function ensureTahunPelajaranTable() {
  try {
    $pdo = getDB();
    $pdo->exec("CREATE TABLE IF NOT EXISTS tahun_pelajaran (
      id INT PRIMARY KEY AUTO_INCREMENT,
      nama VARCHAR(20) NOT NULL,
      tanggal_mulai DATE,
      tanggal_selesai DATE,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      UNIQUE KEY uk_nama (nama)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS semester_aktif (
      id INT PRIMARY KEY DEFAULT 1,
      tahun_pelajaran VARCHAR(20) NOT NULL DEFAULT '',
      semester VARCHAR(10) NOT NULL DEFAULT 'Ganjil',
      label VARCHAR(150) NOT NULL DEFAULT '',
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("INSERT IGNORE INTO semester_aktif (id,tahun_pelajaran,semester,label) VALUES (1,'','Ganjil','')");
  } catch (Exception $e) {
    // Jika tabel sudah ada, abaikan
  }
}

function getTahunPelajaran() {
  try {
    ensureTahunPelajaranTable();
    $pdo  = getDB();
    $rows = $pdo->query("SELECT * FROM tahun_pelajaran ORDER BY nama DESC")->fetchAll();
    $aktif= $pdo->query("SELECT * FROM semester_aktif WHERE id=1")->fetch();
    if (!$aktif) $aktif = ['tahun_pelajaran'=>'','semester'=>'Ganjil','label'=>''];
    jsonOut(['success'=>true,'data'=>$rows,'aktif'=>$aktif]);
  } catch (Exception $e) {
    jsonOut(['success'=>false,'msg'=>'Error: '.$e->getMessage(),'data'=>[],'aktif'=>['tahun_pelajaran'=>'','semester'=>'Ganjil','label'=>'']]);
  }
}

function simpanTahunPelajaran() {
  try {
    ensureTahunPelajaranTable();
    $d    = getInput();
    $id   = isset($d['id']) && $d['id'] ? (int)$d['id'] : null;
    $nama = trim($d['nama'] ?? '');
    $mulai= isset($d['tanggal_mulai']) && $d['tanggal_mulai'] ? $d['tanggal_mulai'] : null;
    $sls  = isset($d['tanggal_selesai']) && $d['tanggal_selesai'] ? $d['tanggal_selesai'] : null;
    if (!$nama) jsonOut(['success'=>false,'msg'=>'Nama tahun pelajaran wajib diisi!']);
    if (!preg_match('/^\d{4}[\/]\d{4}$/', $nama))
      jsonOut(['success'=>false,'msg'=>'Format harus YYYY/YYYY contoh: 2025/2026']);
    $pdo = getDB();
    if ($id) {
      $pdo->prepare("UPDATE tahun_pelajaran SET nama=?,tanggal_mulai=?,tanggal_selesai=? WHERE id=?")
          ->execute([$nama, $mulai, $sls, $id]);
      jsonOut(['success'=>true,'msg'=>'Tahun pelajaran diperbarui!']);
    } else {
      // Cek duplikat
      $cek = $pdo->prepare("SELECT id FROM tahun_pelajaran WHERE nama=?");
      $cek->execute([$nama]);
      if ($cek->fetch()) jsonOut(['success'=>false,'msg'=>'Tahun pelajaran '.$nama.' sudah ada!']);
      $pdo->prepare("INSERT INTO tahun_pelajaran (nama,tanggal_mulai,tanggal_selesai) VALUES (?,?,?)")
          ->execute([$nama, $mulai, $sls]);
      jsonOut(['success'=>true,'msg'=>'Tahun pelajaran '.$nama.' berhasil ditambahkan!']);
    }
  } catch (Exception $e) {
    jsonOut(['success'=>false,'msg'=>'Gagal simpan: '.$e->getMessage()]);
  }
}

function hapusTahunPelajaran() {
  try {
    ensureTahunPelajaranTable();
    $d  = getInput();
    $id = isset($d['id']) && $d['id'] ? (int)$d['id'] : null;
    $nm = trim($d['nama'] ?? '');
    $pdo= getDB();
    $nama_hapus = '';
    if ($id) {
      $r = $pdo->prepare("SELECT nama FROM tahun_pelajaran WHERE id=?");
      $r->execute([$id]);
      $row = $r->fetch();
      $nama_hapus = $row ? $row['nama'] : '';
      $pdo->prepare("DELETE FROM tahun_pelajaran WHERE id=?")->execute([$id]);
    } elseif ($nm) {
      $nama_hapus = $nm;
      $pdo->prepare("DELETE FROM tahun_pelajaran WHERE nama=?")->execute([$nm]);
    }
    // Reset semester aktif jika yang dihapus adalah yang sedang aktif
    if ($nama_hapus) {
      $aktif = $pdo->query("SELECT tahun_pelajaran FROM semester_aktif WHERE id=1")->fetchColumn();
      if ($aktif === $nama_hapus) {
        $pdo->exec("UPDATE semester_aktif SET tahun_pelajaran='',semester='Ganjil',label='' WHERE id=1");
      }
    }
    jsonOut(['success'=>true,'msg'=>'Tahun pelajaran dihapus!']);
  } catch (Exception $e) {
    jsonOut(['success'=>false,'msg'=>'Gagal hapus: '.$e->getMessage()]);
  }
}

function setSemesterAktif() {
  try {
    ensureTahunPelajaranTable();
    $d     = getInput();
    $tahun = trim($d['tahun_pelajaran'] ?? '');
    $sem   = trim($d['semester'] ?? 'Ganjil');
    if (!$tahun) jsonOut(['success'=>false,'msg'=>'Pilih tahun pelajaran terlebih dahulu!']);
    if (!in_array($sem, ['Ganjil','Genap'])) jsonOut(['success'=>false,'msg'=>'Semester tidak valid!']);
    $pdo = getDB();
    // Cek tahun ada
    $cek = $pdo->prepare("SELECT id FROM tahun_pelajaran WHERE nama=?");
    $cek->execute([$tahun]);
    if (!$cek->fetch()) jsonOut(['success'=>false,'msg'=>'Tahun pelajaran tidak ditemukan!']);
    $label = "Semester $sem Tahun Pelajaran $tahun";
    // Cek baris ada di semester_aktif
    $ada = $pdo->query("SELECT COUNT(*) FROM semester_aktif WHERE id=1")->fetchColumn();
    if ($ada) {
      $pdo->prepare("UPDATE semester_aktif SET tahun_pelajaran=?,semester=?,label=? WHERE id=1")
          ->execute([$tahun,$sem,$label]);
    } else {
      $pdo->prepare("INSERT INTO semester_aktif (id,tahun_pelajaran,semester,label) VALUES (1,?,?,?)")
          ->execute([$tahun,$sem,$label]);
    }
    jsonOut(['success'=>true,'msg'=>"$label berhasil diaktifkan!",'label'=>$label]);
  } catch (Exception $e) {
    jsonOut(['success'=>false,'msg'=>'Gagal aktifkan: '.$e->getMessage()]);
  }
}

function nonAktifkanSemester() {
  try {
    ensureTahunPelajaranTable();
    $pdo = getDB();
    $pdo->exec("UPDATE semester_aktif SET tahun_pelajaran='',semester='Ganjil',label='' WHERE id=1");
    jsonOut(['success'=>true,'msg'=>'Semester dinonaktifkan. Tidak ada semester yang berlaku.']);
  } catch (Exception $e) {
    jsonOut(['success'=>false,'msg'=>'Gagal: '.$e->getMessage()]);
  }
}

function getSemesterAktif() {
  try {
    ensureTahunPelajaranTable();
    $pdo   = getDB();
    $aktif = $pdo->query("SELECT sa.*, tp.tanggal_mulai, tp.tanggal_selesai
                            FROM semester_aktif sa
                            LEFT JOIN tahun_pelajaran tp ON tp.nama = sa.tahun_pelajaran
                           WHERE sa.id=1 LIMIT 1")->fetch();
    if (!$aktif) $aktif = ['tahun_pelajaran'=>'','semester'=>'Ganjil','label'=>'','tanggal_mulai'=>null,'tanggal_selesai'=>null];

    // ── Pastikan tanggal_mulai selalu sinkron dengan tahun_pelajaran + semester ──
    // Untuk Ganjil → pakai tahun pertama (Juli tahun pertama bila kosong)
    // Untuk Genap → pakai tahun kedua  (Januari tahun kedua bila kosong / masih tahun pertama)
    if (!empty($aktif['tahun_pelajaran'])) {
      $parts = explode('/', $aktif['tahun_pelajaran']);
      if (count($parts) === 2 && ctype_digit($parts[0]) && ctype_digit($parts[1])) {
        $sem = strtolower(trim($aktif['semester'] ?? 'Ganjil'));
        if ($sem === 'genap') {
          if (empty($aktif['tanggal_mulai']) || substr($aktif['tanggal_mulai'],0,4) === $parts[0]) {
            $aktif['tanggal_mulai'] = $parts[1] . '-01-01';
          }
        } else { // Ganjil (default)
          if (empty($aktif['tanggal_mulai'])) {
            $aktif['tanggal_mulai'] = $parts[0] . '-07-01';
          }
        }
      }
    }

    jsonOut(['success'=>true,'aktif'=>$aktif]);
  } catch (Exception $e) {
    jsonOut(['success'=>true,'aktif'=>['tahun_pelajaran'=>'','semester'=>'Ganjil','label'=>'','tanggal_mulai'=>null,'tanggal_selesai'=>null]]);
  }
}

// ════════════════════════════════════════════════════════════════
// WALI KELAS
// ════════════════════════════════════════════════════════════════
function getWaliList() {
  jsonOut(toCamel(getDB()->query("SELECT id, username, password, nama_wali, kelas FROM wali_kelas ORDER BY kelas")->fetchAll()));
}

function simpanWaliKelas() {
  $d   = getInput();
  $pdo = getDB();
  $id  = $d['id'] ?? null;
  $usr = strtolower(trim($d['username'] ?? ''));
  $pw  = $d['password'] ?? '';
  $nm  = $d['namaWali'] ?? $d['nama_wali'] ?? '';
  $kls = $d['kelas'] ?? '';
  $isNew = $d['isNew'] ?? (!$id);
  if (!$usr) jsonErr('Username wajib!');

  if (!$isNew && $id) {
    // Update berdasarkan id atau username
    if (is_numeric($id)) {
      if ($pw) $pdo->prepare("UPDATE wali_kelas SET username=?,password=?,nama_wali=?,kelas=? WHERE id=?")->execute([$usr,$pw,$nm,$kls,$id]);
      else     $pdo->prepare("UPDATE wali_kelas SET username=?,nama_wali=?,kelas=? WHERE id=?")->execute([$usr,$nm,$kls,$id]);
    } else {
      if ($pw) $pdo->prepare("UPDATE wali_kelas SET password=?,nama_wali=?,kelas=? WHERE username=?")->execute([$pw,$nm,$kls,$id]);
      else     $pdo->prepare("UPDATE wali_kelas SET nama_wali=?,kelas=? WHERE username=?")->execute([$nm,$kls,$id]);
    }
  } else {
    if (!$pw) $pw = '333'; // default
    $pdo->prepare("INSERT INTO wali_kelas (username,password,nama_wali,kelas) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE password=?,nama_wali=?,kelas=?")->execute([$usr,$pw,$nm,$kls,$pw,$nm,$kls]);
    $id = $pdo->lastInsertId() ?: $id;
  }
  // ── Sync nama wali ke tabel rombel ──
  if ($kls) {
    $pdo->prepare("UPDATE rombel SET wali_kelas=? WHERE UPPER(TRIM(nama_rombel))=UPPER(TRIM(?))")->execute([$nm, $kls]);
  }
  jsonOut(['success'=>true,'id'=>$id,'msg'=>'Wali kelas disimpan!']);
}

function hapusWaliKelas() {
  $d  = getInput();
  $id = $d['id'] ?? null;
  $us = $d['username'] ?? null;
  $pdo= getDB();
  // Ambil kelas sebelum hapus, agar bisa clear rombel.wali_kelas
  $klsRow = null;
  if ($id) {
    $r = $pdo->prepare("SELECT kelas FROM wali_kelas WHERE id=?");
    $r->execute([$id]); $klsRow = $r->fetchColumn();
    $pdo->prepare("DELETE FROM wali_kelas WHERE id=?")->execute([$id]);
  } elseif ($us) {
    $r = $pdo->prepare("SELECT kelas FROM wali_kelas WHERE username=?");
    $r->execute([$us]); $klsRow = $r->fetchColumn();
    $pdo->prepare("DELETE FROM wali_kelas WHERE username=?")->execute([$us]);
  }
  // Kosongkan wali_kelas di rombel jika ditemukan
  if ($klsRow) {
    $pdo->prepare("UPDATE rombel SET wali_kelas='' WHERE UPPER(TRIM(nama_rombel))=UPPER(TRIM(?))")->execute([$klsRow]);
  }
  jsonOut(['success'=>true]);
}

function syncRombelWali() {
  $d   = getInput();
  $us  = $d['username'] ?? '';
  $kls = $d['kelas'] ?? '';
  $pdo = getDB();
  // Cari nama_wali berdasarkan username atau kelas
  if ($us) {
    $r = $pdo->prepare("SELECT nama_wali, kelas FROM wali_kelas WHERE username=? LIMIT 1");
    $r->execute([$us]);
    $row = $r->fetch();
    if ($row) {
      $pdo->prepare("UPDATE rombel SET wali_kelas=? WHERE UPPER(TRIM(nama_rombel))=UPPER(TRIM(?))")
          ->execute([$row['nama_wali'], $row['kelas']]);
    }
  } elseif ($kls) {
    $r = $pdo->prepare("SELECT nama_wali FROM wali_kelas WHERE UPPER(TRIM(kelas))=UPPER(TRIM(?)) LIMIT 1");
    $r->execute([$kls]);
    $nm = $r->fetchColumn();
    $pdo->prepare("UPDATE rombel SET wali_kelas=? WHERE UPPER(TRIM(nama_rombel))=UPPER(TRIM(?))")
        ->execute([$nm ?: '', $kls]);
  } else {
    // Sync semua wali ke rombel
    $all = $pdo->query("SELECT nama_wali, kelas FROM wali_kelas")->fetchAll();
    foreach ($all as $w) {
      $pdo->prepare("UPDATE rombel SET wali_kelas=? WHERE UPPER(TRIM(nama_rombel))=UPPER(TRIM(?))")
          ->execute([$w['nama_wali'], $w['kelas']]);
    }
  }
  jsonOut(['success'=>true,'msg'=>'Sync berhasil!']);
}


// ════════════════════════════════════════════════════════════════
function getDataSiswa() {
  $d   = getInput();
  $kls = trim($d['kelas'] ?? '');
  $pdo = getDB();
  if ($kls) {
    $s = $pdo->prepare("SELECT * FROM data_siswa WHERE UPPER(TRIM(kelas))=UPPER(?) ORDER BY nama");
    $s->execute([$kls]);
  } else {
    $s = $pdo->query("SELECT * FROM data_siswa ORDER BY kelas, nama");
  }
  jsonOut(toCamel($s->fetchAll()));
}

function simpanSiswa() {
  $d   = getInput();
  $pdo = getDB();
  $id  = trim($d['id'] ?? ('S' . time() . rand(10,99)));
  $nis = trim($d['nis'] ?? '');
  $nm  = trim($d['nama'] ?? '');
  $kls = trim($d['kelas'] ?? '');

  if (!$nm) jsonOut(['success'=>false,'msg'=>'Nama siswa wajib!']);
  if (!$kls) jsonOut(['success'=>false,'msg'=>'Kelas wajib!']);
  if (!$nis) $nis = '-'; // default jika NIS kosong (NOT NULL di skema)

  try {
    // Cek apakah id sudah ada
    $cek = $pdo->prepare("SELECT id FROM data_siswa WHERE id=?");
    $cek->execute([$id]);
    if ($cek->fetch()) {
      // Update
      $pdo->prepare("UPDATE data_siswa SET nis=?,nama=?,kelas=? WHERE id=?")->execute([$nis,$nm,$kls,$id]);
    } else {
      // Insert baru
      $pdo->prepare("INSERT INTO data_siswa (id,nis,nama,kelas) VALUES (?,?,?,?)")->execute([$id,$nis,$nm,$kls]);
    }
    jsonOut(['success'=>true,'id'=>$id,'msg'=>'Siswa disimpan!']);
  } catch (Exception $e) {
    jsonOut(['success'=>false,'msg'=>'DB Error: '.$e->getMessage()]);
  }
}

function hapusSiswa() {
  $id = getInput()['id'] ?? '';
  getDB()->prepare("DELETE FROM data_siswa WHERE id=?")->execute([$id]);
  jsonOut(['success'=>true]);
}

function hapusSiswaMassal() {
  $d   = getInput();
  $ids = $d['ids'] ?? [];
  if (is_string($ids)) $ids = json_decode($ids, true) ?: [];
  if (!is_array($ids) || !count($ids)) jsonOut(['success'=>false,'msg'=>'Tidak ada ID yang dipilih']);
  $ids = array_values(array_filter(array_map('strval', $ids)));
  $ph  = implode(',', array_fill(0, count($ids), '?'));
  getDB()->prepare("DELETE FROM data_siswa WHERE id IN ($ph)")->execute($ids);
  jsonOut(['success'=>true, 'msg'=>'Berhasil menghapus '.count($ids).' siswa']);
}

function importSiswa() {
  $rows = getInput()['rows'] ?? [];
  $pdo  = getDB();
  $ok   = 0;
  foreach ($rows as $r) {
    $id  = 'S' . time() . rand(10,99);
    $nis = $r['nis'] ?? '';
    $nm  = $r['nama'] ?? '';
    $kls = $r['kelas'] ?? '';
    if (!$nm) continue;
    $pdo->prepare("INSERT INTO data_siswa (id,nis,nama,kelas) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE nama=?,kelas=?")->execute([$id,$nis,$nm,$kls,$nm,$kls]);
    $ok++;
  }
  jsonOut(['success'=>true,'msg'=>"$ok siswa diimport!"]);
}

function getFilterAbsensi() {
  $pdo = getDB();

  // Kelas dari absensi_siswa, fallback ke data_siswa
  $kelas = $pdo->query("SELECT DISTINCT TRIM(kelas) as kelas FROM absensi_siswa WHERE kelas IS NOT NULL AND kelas<>'' ORDER BY kelas")->fetchAll(PDO::FETCH_COLUMN);
  if (!$kelas) $kelas = $pdo->query("SELECT DISTINCT kelas FROM data_siswa ORDER BY kelas")->fetchAll(PDO::FETCH_COLUMN);

  // Guru: hanya yang terdaftar di data_guru (JOIN) agar tidak muncul nama lama/tidak valid
  $guruRaw = $pdo->query(
    "SELECT DISTINCT TRIM(a.id_guru) as id_guru, TRIM(dg.nama) as nama_guru
      FROM absensi_siswa a
      INNER JOIN data_guru dg ON TRIM(a.id_guru) = TRIM(dg.id)
      WHERE a.id_guru IS NOT NULL AND a.id_guru<>''
      ORDER BY dg.nama"
  )->fetchAll();
  $guru = array_map(function($r){ return ['id'=>$r['id_guru'],'nama'=>$r['nama_guru']]; }, $guruRaw);

  // Mapel semua
  $mapel = $pdo->query("SELECT DISTINCT TRIM(mapel) as mapel FROM absensi_siswa WHERE mapel IS NOT NULL AND mapel<>'' ORDER BY mapel")->fetchAll(PDO::FETCH_COLUMN);

  // Mapel per guru (untuk cascading filter) - hanya guru aktif di data_guru
  $mapelByGuruRaw = $pdo->query(
    "SELECT DISTINCT TRIM(a.id_guru) as id_guru, TRIM(a.mapel) as mapel
      FROM absensi_siswa a
      INNER JOIN data_guru dg ON TRIM(a.id_guru) = TRIM(dg.id)
      WHERE a.id_guru IS NOT NULL AND a.mapel IS NOT NULL AND a.mapel<>''
      ORDER BY a.mapel"
  )->fetchAll();
  $mapelByGuru = [];
  foreach ($mapelByGuruRaw as $r) {
    $gid = $r['id_guru'];
    if (!isset($mapelByGuru[$gid])) $mapelByGuru[$gid] = [];
    if (!in_array($r['mapel'], $mapelByGuru[$gid])) $mapelByGuru[$gid][] = $r['mapel'];
  }

  jsonOut(['kelas' => $kelas, 'guru' => $guru, 'mapel' => $mapel, 'mapelByGuru' => $mapelByGuru]);
}

// ════════════════════════════════════════════════════════════════
// ABSENSI SISWA
// ════════════════════════════════════════════════════════════════
function getAbsensiSiswaPortal() {
  $d   = getInput();
  $kls = trim(strtoupper($d['kelas'] ?? ''));
  $jk  = (int)($d['jamKe'] ?? 0);
  $pdo = getDB();

  // Ambil daftar siswa kelas ini
  $sSiswa = $pdo->prepare("SELECT id, nis, nama FROM data_siswa WHERE UPPER(TRIM(kelas))=? ORDER BY nama");
  $sSiswa->execute([$kls]);
  $siswa  = $sSiswa->fetchAll();

  // Cek absensi hari ini jam ini
  $today   = date('Y-m-d');
  $sAbsen  = $pdo->prepare("SELECT nis, status FROM absensi_siswa WHERE UPPER(TRIM(kelas))=? AND DATE(tanggal)=? AND jam_ke=?");
  $sAbsen->execute([$kls, $today, $jk]);
  $sudah   = [];
  foreach ($sAbsen->fetchAll() as $r) $sudah[$r['nis']] = $r['status'];

  jsonOut(['siswa' => $siswa, 'sudahAbsen' => $sudah, 'total' => count($siswa)]);
}

function simpanAbsensiSiswaPortal() {
  $d   = getInput();
  $pdo = getDB();
  $nip = $d['nip'] ?? '';
  $kls = $d['kelas'] ?? '';
  $jk  = (int)($d['jamKe'] ?? 0);
  $mp  = $d['mapel'] ?? '';
  $rows= $d['payload'] ?? [];
  if (!$rows || !is_array($rows)) jsonOut(['success'=>false,'msg'=>'Tidak ada data absensi']);

  // Cari nama guru
  $nmGuru = $pdo->prepare("SELECT nama FROM data_guru WHERE id=? LIMIT 1");
  $nmGuru->execute([$nip]);
  $nmG = $nmGuru->fetchColumn() ?: $nip;

  $today = date('Y-m-d');
  $now   = date('Y-m-d H:i:s');
  $ok    = 0;
  foreach ($rows as $r) {
    $nis = trim($r['nis'] ?? '');
    $nm  = $r['nama'] ?? '';
    $sts = strtoupper(trim($r['status'] ?? 'HDR'));
    if (!$nis) continue;

    // HAPUS duplikat dulu (ambil yg terbaru, hapus sisanya)
    $cek = $pdo->prepare(
      "SELECT id FROM absensi_siswa WHERE nis=? AND UPPER(TRIM(kelas))=UPPER(?) AND jam_ke=? AND DATE(tanggal)=? ORDER BY created_at DESC");
    $cek->execute([$nis, $kls, $jk, $today]);
    $ids = $cek->fetchAll(PDO::FETCH_COLUMN);

    if (count($ids) > 0) {
      // Update yang pertama (terbaru), hapus sisanya
      $pdo->prepare("UPDATE absensi_siswa SET status=?,id_guru=?,nama_guru=?,mapel=? WHERE id=?")
          ->execute([$sts, $nip, $nmG, $mp, $ids[0]]);
      if (count($ids) > 1) {
        $dupIds = array_slice($ids, 1);
        $ph = implode(',', array_fill(0, count($dupIds), '?'));
        $pdo->prepare("DELETE FROM absensi_siswa WHERE id IN ($ph)")->execute($dupIds);
      }
    } else {
      $id = 'AB' . time() . rand(100,999) . $ok;
      $pdo->prepare("INSERT INTO absensi_siswa (id,tanggal,id_guru,nama_guru,kelas,jam_ke,mapel,nis,nama_siswa,status) VALUES (?,?,?,?,?,?,?,?,?,?)")
          ->execute([$id,$now,$nip,$nmG,$kls,$jk,$mp,$nis,$nm,$sts]);
    }
    $ok++;
  }
  jsonOut(['success'=>true,'msg'=>"$ok absensi tersimpan!"]);
}

function hapusPresensi() {
  $d   = getInput();
  $nip = $d['nip']   ?? '';
  $kls = $d['kelas'] ?? '';
  $jk  = (int)($d['jamKe'] ?? 0);
  $tgl = $d['tanggal'] ?? date('Y-m-d');
  if (!$nip || !$kls || !$jk) jsonErr('Data tidak lengkap');
  $pdo = getDB();
  // Hapus presensi guru
  $pdo->prepare("DELETE FROM data_presensi WHERE id_guru=? AND tanggal=? AND kelas=? AND jam_ke=?")
      ->execute([$nip, $tgl, $kls, $jk]);
  // Hapus absensi siswa untuk sesi yang sama (kelas+jam+tanggal)
  $pdo->prepare("DELETE FROM absensi_siswa WHERE UPPER(TRIM(kelas))=UPPER(?) AND jam_ke=? AND DATE(tanggal)=?")
      ->execute([$kls, $jk, $tgl]);
  jsonOut(['success'=>true, 'msg'=>'Presensi dan absensi siswa berhasil dihapus!']);
}

function hapusPresensiAdminMassal() {
  $d   = getInput();
  $ids = $d['ids'] ?? [];
  if (is_string($ids)) $ids = json_decode($ids, true) ?: [];
  if (!is_array($ids)) $ids = [];
  $ids = array_values(array_filter(array_map('strval', $ids)));
  if (!count($ids)) jsonOut(['success'=>false,'msg'=>'Tidak ada data dipilih']);
  $ph = implode(',', array_fill(0, count($ids), '?'));
  getDB()->prepare("DELETE FROM data_presensi WHERE id IN ($ph)")->execute($ids);
  jsonOut(['success'=>true,'msg'=>'Berhasil menghapus '.count($ids).' presensi. Data dapat diisi ulang.']);
}

// ════════════════════════════════════════════════════════════════
// JURNAL HARI INI — Admin mengisi presensi harian untuk guru
// ════════════════════════════════════════════════════════════════
function getJurnalHariIni() {
  $d   = getInput();
  $tgl = $d['tanggal'] ?? date('Y-m-d');
  $pdo = getDB();

  // Migrasi otomatis
  try { $pdo->exec("ALTER TABLE data_presensi ADD COLUMN IF NOT EXISTS waktu TIME DEFAULT NULL"); } catch(Exception $e) {}
  try { $pdo->exec("ALTER TABLE jadwal_mengajar ADD COLUMN IF NOT EXISTS jam_ke_sampai INT DEFAULT NULL"); } catch(Exception $e) {}
  try { $pdo->exec("ALTER TABLE data_presensi ADD COLUMN IF NOT EXISTS dikerjakan_admin TINYINT(1) DEFAULT 0"); } catch(Exception $e) {}
  try { $pdo->exec("ALTER TABLE data_presensi ADD COLUMN IF NOT EXISTS cp TEXT DEFAULT NULL"); } catch(Exception $e) {}
  try { $pdo->exec("ALTER TABLE data_presensi ADD COLUMN IF NOT EXISTS tp TEXT DEFAULT NULL"); } catch(Exception $e) {}

  $hariMap = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa',
              'Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
  $hariIndo = $hariMap[date('l', strtotime($tgl))] ?? '';

  // Ambil semua jadwal hari ini beserta status presensinya
  $stmt = $pdo->prepare(
    "SELECT j.guru_id AS id_guru, j.nama_guru,
            j.kelas, j.jam_ke,
            COALESCE(j.jam_ke_sampai, j.jam_ke) AS jam_ke_sampai,
            j.mapel,
            COALESCE(j.jam_mulai,'')  AS jam_mulai,
            COALESCE(j.jam_selesai,'') AS jam_selesai,
            ? AS tanggal,
            COALESCE(dp.id,'')              AS id_presensi,
            COALESCE(dp.status,'')          AS status,
            COALESCE(dp.materi,'')          AS materi,
            COALESCE(dp.kegiatan,'')        AS kegiatan,
            COALESCE(dp.cp,'')              AS cp,
            COALESCE(dp.tp,'')              AS tp,
            COALESCE(dp.dikerjakan_admin,0) AS dikerjakan_admin
     FROM jadwal_mengajar j
     LEFT JOIN data_presensi dp
           ON dp.id_guru = j.guru_id
          AND dp.kelas   = j.kelas
          AND dp.jam_ke  = j.jam_ke
          AND dp.tanggal = ?
     WHERE j.hari = ?
     ORDER BY j.kelas, j.jam_ke"
  );
  $stmt->execute([$tgl, $tgl, $hariIndo]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $result = [];
  foreach ($rows as $r) {
    $sudahPresensi = ($r['id_presensi'] !== null && $r['id_presensi'] !== '');
    $result[] = [
      'guruId'          => $r['id_guru'],
      'id_guru'         => $r['id_guru'],
      'namaGuru'        => $r['nama_guru'],
      'nama_guru'       => $r['nama_guru'],
      'kelas'           => $r['kelas'],
      'jamKe'           => $r['jam_ke'],
      'jam_ke'          => $r['jam_ke'],
      'jamKeSampai'     => $r['jam_ke_sampai'],
      'jam_ke_sampai'   => $r['jam_ke_sampai'],
      'mapel'           => $r['mapel'],
      'jamMulai'        => $r['jam_mulai'],
      'jamSelesai'      => $r['jam_selesai'],
      'tanggal'         => $r['tanggal'],
      'idPresensi'      => $r['id_presensi'],
      'status'          => $r['status'],
      'materi'          => $r['materi'],
      'kegiatan'        => $r['kegiatan'],
      'cp'              => $r['cp'],
      'tp'              => $r['tp'],
      'dikerjakan_admin'=> $r['dikerjakan_admin'],
      // [FIX] Field ini sebelumnya TIDAK dikirim sama sekali, padahal frontend
      // (Jurnal Hari Ini) memakainya untuk menentukan badge "Sudah/Belum
      // Presensi" & tombol Hadir/Edit/Hapus — akibatnya SEMUA baris selalu
      // tampil "Belum Presensi" walau sesi itu sudah dipresensi guru/admin.
      'sudahPresensi'   => $sudahPresensi,
      'diisiOleh'       => $sudahPresensi ? (((int)($r['dikerjakan_admin'] ?? 0) === 1) ? 'admin' : 'guru') : '',
    ];
  }
  jsonOut(['success'=>true, 'data'=>$result, 'hari'=>$hariIndo, 'tanggal'=>$tgl]);
}

function simpanJurnalAdmin() {
  $d   = getInput();
  $pdo = getDB();

  $nip  = trim($d['id_guru'] ?? $d['nip'] ?? '');
  $nm   = trim($d['nama_guru'] ?? $d['nama'] ?? '');
  $kls  = trim($d['kelas'] ?? '');
  $jk   = (int)($d['jam_ke'] ?? $d['jamKe'] ?? 0);
  $jks  = (int)($d['jam_ke_sampai'] ?? $d['jamKeSampai'] ?? $jk);
  $mp   = trim($d['mapel'] ?? '');
  $mat  = trim($d['materi'] ?? '');
  $keg  = trim($d['kegiatan'] ?? '');
  $cp   = trim($d['cp'] ?? '');
  $tp   = trim($d['tp'] ?? '');
  $tgl  = $d['tanggal'] ?? date('Y-m-d');
  $now  = date('H:i:s');
  if ($jks < $jk) $jks = $jk;

  if (!$nip || !$kls || !$jk) jsonErr('Data tidak lengkap (id_guru, kelas, jam_ke wajib diisi)');

  // Migrasi kolom
  try { $pdo->exec("ALTER TABLE data_presensi ADD COLUMN IF NOT EXISTS waktu TIME DEFAULT NULL"); } catch(Exception $e) {}
  try { $pdo->exec("ALTER TABLE data_presensi ADD COLUMN IF NOT EXISTS jam_ke_sampai INT DEFAULT NULL"); } catch(Exception $e) {}
  try { $pdo->exec("ALTER TABLE data_presensi ADD COLUMN IF NOT EXISTS kegiatan TEXT DEFAULT NULL"); } catch(Exception $e) {}
  // FIX: kolom kegiatan/materi/cp/tp bisa sudah ada dari skema lama dengan
  // tipe VARCHAR pendek (mis. VARCHAR(255)) -> menyebabkan error SQLSTATE[22001]
  // "Data too long for column" saat teks kegiatan/materi panjang disimpan.
  // ADD COLUMN IF NOT EXISTS di atas TIDAK mengubah tipe kolom yang sudah ada,
  // jadi kita paksa ubah tipenya ke TEXT (idempotent, aman dipanggil berulang).
  try { $pdo->exec("ALTER TABLE data_presensi MODIFY COLUMN kegiatan TEXT DEFAULT NULL"); } catch(Exception $e) {}
  try { $pdo->exec("ALTER TABLE data_presensi MODIFY COLUMN materi TEXT DEFAULT NULL"); } catch(Exception $e) {}
  try { $pdo->exec("ALTER TABLE data_presensi MODIFY COLUMN cp TEXT DEFAULT NULL"); } catch(Exception $e) {}
  try { $pdo->exec("ALTER TABLE data_presensi MODIFY COLUMN tp TEXT DEFAULT NULL"); } catch(Exception $e) {}
  try { $pdo->exec("ALTER TABLE data_presensi ADD COLUMN IF NOT EXISTS dikerjakan_admin TINYINT(1) DEFAULT 0"); } catch(Exception $e) {}
  try { $pdo->exec("ALTER TABLE data_presensi ADD COLUMN IF NOT EXISTS cp TEXT DEFAULT NULL"); } catch(Exception $e) {}
  try { $pdo->exec("ALTER TABLE data_presensi ADD COLUMN IF NOT EXISTS tp TEXT DEFAULT NULL"); } catch(Exception $e) {}

  // Cek apakah sudah ada presensi untuk slot ini
  $cek = $pdo->prepare("SELECT id, materi, kegiatan, cp, tp, jam_ke_sampai FROM data_presensi WHERE id_guru=? AND tanggal=? AND jam_ke=? AND kelas=?");
  $cek->execute([$nip, $tgl, $jk, $kls]);
  $existing = $cek->fetch(PDO::FETCH_ASSOC);

  if ($existing) {
    // UPDATE: gabungkan materi/kegiatan/cp/tp yang sudah ada dengan yang baru
    // (jika admin baru mengisi jurnal). CP/TP disimpan terpisah dari materi/
    // kegiatan supaya tetap ikut tersimpan meski hanya salah satu dropdown
    // (CP saja atau TP saja) yang diklik oleh admin.
    $finalMat = ($mat !== '') ? $mat : ($existing['materi'] ?? '');
    $finalKeg = ($keg !== '') ? $keg : ($existing['kegiatan'] ?? '');
    $finalCp  = ($cp  !== '') ? $cp  : ($existing['cp']  ?? '');
    $finalTp  = ($tp  !== '') ? $tp  : ($existing['tp']  ?? '');
    $finalJks = ($jks > $jk) ? $jks : (int)($existing['jam_ke_sampai'] ?? $jk);
    $pdo->prepare("UPDATE data_presensi SET status='Hadir', materi=?, kegiatan=?, cp=?, tp=?, jam_ke_sampai=?, dikerjakan_admin=1 WHERE id_guru=? AND tanggal=? AND jam_ke=? AND kelas=?")
        ->execute([$finalMat, $finalKeg, $finalCp, $finalTp, $finalJks, $nip, $tgl, $jk, $kls]);
    jsonOut(['success'=>true,'msg'=>'Jurnal berhasil diperbarui! (Hadir – diisi Admin)','id'=>$existing['id']]);
  }

  $id = 'PA'.time().rand(100,999);
  $pdo->prepare("INSERT INTO data_presensi (id,tanggal,id_guru,nama_guru,kelas,jam_ke,jam_ke_sampai,status,mapel,materi,kegiatan,cp,tp,foto_url,waktu,dikerjakan_admin) VALUES (?,?,?,?,?,?,?,'Hadir',?,?,?,?,?,?,?,1)")
      ->execute([$id,$tgl,$nip,$nm,$kls,$jk,$jks,$mp,$mat,$keg,$cp,$tp,'',$now]);
  jsonOut(['success'=>true,'msg'=>'Jurnal berhasil disimpan! (Hadir – diisi Admin)','id'=>$id]);
}

function hapusMateri() {
  $d   = getInput();
  $nip = $d['nip']   ?? '';
  $kls = $d['kelas'] ?? '';
  $jk  = (int)($d['jamKe'] ?? 0);
  $tgl = $d['tanggal'] ?? date('Y-m-d');
  if (!$nip || !$kls || !$jk) jsonErr('Data tidak lengkap');
  $pdo = getDB();
  $pdo->prepare("UPDATE data_presensi SET materi='' WHERE id_guru=? AND tanggal=? AND kelas=? AND jam_ke=?")
      ->execute([$nip, $tgl, $kls, $jk]);
  jsonOut(['success'=>true, 'msg'=>'Materi berhasil dihapus!']);
}

function hapusAbsensiSiswaById() {
  $id = trim(getInput()['id'] ?? '');
  if (!$id) jsonErr('ID tidak ada');
  getDB()->prepare("DELETE FROM absensi_siswa WHERE id=?")->execute([$id]);
  jsonOut(['success'=>true,'msg'=>'Data absensi dihapus!']);
}

function hapusAbsensiSiswaByGuru() {
  $guruId = trim(getInput()['guruId'] ?? getInput()['nip'] ?? '');
  if (!$guruId) jsonErr('ID guru tidak ada');
  $pdo = getDB();
  $stmt = $pdo->prepare("DELETE FROM absensi_siswa WHERE TRIM(id_guru)=?");
  $stmt->execute([$guruId]);
  $deleted = $stmt->rowCount();
  jsonOut(['success'=>true,'msg'=>$deleted.' data absensi berhasil dihapus!','deleted'=>$deleted]);
}

function hapusAbsensiSiswa() {
  $d   = getInput();
  $nip = $d['nip']   ?? '';
  $kls = $d['kelas'] ?? '';
  $jk  = (int)($d['jamKe'] ?? 0);
  $tgl = $d['tanggal'] ?? date('Y-m-d');
  if (!$kls || !$jk) jsonErr('Data tidak lengkap');
  $pdo = getDB();
  $pdo->prepare("DELETE FROM absensi_siswa WHERE kelas=? AND DATE(tanggal)=? AND jam_ke=?")
      ->execute([$kls, $tgl, $jk]);
  jsonOut(['success'=>true, 'msg'=>'Absensi siswa berhasil dihapus!']);
}

function hapusPiket() {
  $d   = getInput();
  $nip = $d['nip']     ?? '';
  $tgl = $d['tanggal'] ?? date('Y-m-d');
  if (!$nip) jsonErr('NIP tidak ada');
  getDB()->prepare("DELETE FROM piket_guru WHERE id_guru=? AND tanggal=?")
         ->execute([$nip, $tgl]);
  jsonOut(['success'=>true, 'msg'=>'Data piket berhasil dihapus!']);
}

// ════════════════════════════════════════════════════════════════
// PIKET
// ════════════════════════════════════════════════════════════════
function simpanPiket() {
  $d      = getInput();
  $nip    = $d['nip']     ?? '';
  $nama   = $d['nama']    ?? '';
  $laporan= $d['laporan'] ?? '';
  $foto   = $d['foto']    ?? '';
  $pdo    = getDB();

  // Pastikan kolom ada (migrasi otomatis)
  try {
    $pdo->exec("ALTER TABLE piket_guru ADD COLUMN IF NOT EXISTS laporan TEXT");
    $pdo->exec("ALTER TABLE piket_guru ADD COLUMN IF NOT EXISTS foto_url TEXT");
  } catch(Exception $e) {}

  // Cek apakah sudah ada piket hari ini
  $cek = $pdo->prepare("SELECT id FROM piket_guru WHERE id_guru=? AND tanggal=?");
  $cek->execute([$nip, date('Y-m-d')]);
  $existing = $cek->fetch();

  if ($existing) {
    // Update laporan & foto jika sudah ada
    $pdo->prepare("UPDATE piket_guru SET laporan=?, foto_url=? WHERE id_guru=? AND tanggal=?")
        ->execute([$laporan, $foto, $nip, date('Y-m-d')]);
    jsonOut(['success'=>true, 'msg'=>'Laporan piket diperbarui!', 'updated'=>true]);
  }

  // Simpan foto ke file jika base64
  $fotoUrl = '';
  if ($foto && strpos($foto, 'data:image') === 0) {
    if (preg_match('/^data:(image\/[a-z]+);base64,/', $foto, $m)) {
      $ext  = $m[1] === 'image/png' ? 'png' : 'jpg';
      $data = base64_decode(preg_replace('/^data:image\/[a-z]+;base64,/', '', $foto));
      if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
      $fn  = 'piket_' . $nip . '_' . time() . '.' . $ext;
      file_put_contents(UPLOAD_DIR . $fn, $data);
      $fotoUrl = UPLOAD_URL . $fn;
    }
  }

  $id = 'PK' . time();
  $pdo->prepare("INSERT INTO piket_guru (id,tanggal,id_guru,nama_guru,waktu,laporan,foto_url) VALUES (?,?,?,?,?,?,?)")
      ->execute([$id, date('Y-m-d'), $nip, $nama, date('H:i:s'), $laporan, $fotoUrl]);
  jsonOut(['success'=>true, 'msg'=>'Piket berhasil dicatat!', 'fotoUrl'=>$fotoUrl]);
}

// Cek apakah guru dijadwalkan piket pada hari tertentu
// FIX: dulu ada fallback "atau tugas_tambahan mengandung piket" — ini menyebabkan
// form/tombol Piket muncul dan bisa diisi guru meskipun BELUM ada jadwal piket resmi
// yang dibuatkan admin, sehingga honor piket bisa terbayar tanpa jadwal. Sekarang
// HANYA berdasarkan tabel jadwal_piket (harus cocok hari-nya juga).
function cekJadwalPiket() {
  $d    = getInput();
  $pdo  = getDB();
  $nip  = $d['idGuru'] ?? $d['id_guru'] ?? $d['nip'] ?? '';
  $hari = $d['hari'] ?? '';

  if (!$nip) jsonOut(['success'=>false,'adaPiket'=>false]);

  $pdo->exec("CREATE TABLE IF NOT EXISTS jadwal_piket (
    id VARCHAR(50) PRIMARY KEY,
    guru_id VARCHAR(50) NOT NULL,
    nama_guru VARCHAR(200),
    hari ENUM('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu') NOT NULL,
    INDEX idx_piket_guru (guru_id),
    INDEX idx_piket_hari (hari)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

  $stmt = $pdo->prepare("SELECT id FROM jadwal_piket WHERE guru_id=?" . ($hari ? " AND hari=?" : ""));
  $params = $hari ? [$nip, $hari] : [$nip];
  $stmt->execute($params);
  $row = $stmt->fetch();

  jsonOut(['success'=>true, 'adaPiket'=> $row ? true : false, 'sumber'=>'jadwal_piket']);
}

function _ensureJadwalPiketTable($pdo) {
  $pdo->exec("CREATE TABLE IF NOT EXISTS jadwal_piket (
    id VARCHAR(50) PRIMARY KEY,
    guru_id VARCHAR(50) NOT NULL,
    nama_guru VARCHAR(200),
    hari ENUM('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu') NOT NULL,
    INDEX idx_jp_guru (guru_id),
    INDEX idx_jp_hari (hari)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function getJadwalPiket() {
  $pdo = getDB();
  _ensureJadwalPiketTable($pdo);
  $rows = $pdo->query("SELECT jp.*, dg.nama AS nama_guru_db FROM jadwal_piket jp LEFT JOIN data_guru dg ON dg.id=jp.guru_id ORDER BY FIELD(jp.hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'), jp.nama_guru")->fetchAll(PDO::FETCH_ASSOC);
  // Gunakan nama dari data_guru jika ada
  foreach ($rows as &$r) {
    if (!empty($r['nama_guru_db'])) $r['nama_guru'] = $r['nama_guru_db'];
    unset($r['nama_guru_db']);
  }
  jsonOut(['success'=>true,'data'=>$rows]);
}

function simpanJadwalPiket() {
  $d    = getInput();
  $pdo  = getDB();
  _ensureJadwalPiketTable($pdo);
  $guruId   = trim($d['guruId'] ?? $d['guru_id'] ?? '');
  $namaGuru = trim($d['namaGuru'] ?? $d['nama_guru'] ?? '');
  $hari     = trim($d['hari'] ?? '');
  if (!$guruId || !$hari) jsonOut(['success'=>false,'msg'=>'guruId dan hari wajib diisi']);
  // Cek duplikat
  $cek = $pdo->prepare("SELECT id FROM jadwal_piket WHERE guru_id=? AND hari=?");
  $cek->execute([$guruId,$hari]);
  if ($cek->fetch()) jsonOut(['success'=>false,'msg'=>'Guru ini sudah dijadwalkan piket hari '.$hari]);
  $id = 'JP'.time().rand(10,99);
  $pdo->prepare("INSERT INTO jadwal_piket (id,guru_id,nama_guru,hari) VALUES (?,?,?,?)")->execute([$id,$guruId,$namaGuru,$hari]);
  jsonOut(['success'=>true,'msg'=>'Jadwal piket berhasil disimpan!','id'=>$id]);
}

function hapusJadwalPiket() {
  $d   = getInput();
  $pdo = getDB();
  $id  = $d['id'] ?? '';
  if (!$id) jsonOut(['success'=>false,'msg'=>'ID tidak ditemukan']);
  _ensureJadwalPiketTable($pdo);
  $pdo->prepare("DELETE FROM jadwal_piket WHERE id=?")->execute([$id]);
  jsonOut(['success'=>true,'msg'=>'Jadwal piket dihapus!']);
}

function getPiketHariIni() {
  $d   = getInput();
  $tgl = $d['tanggal'] ?? date('Y-m-d');
  $pdo = getDB();
  $stmt = $pdo->prepare(
    "SELECT p.id, p.tanggal, p.id_guru, p.nama_guru, p.waktu,
             COALESCE(p.laporan,'') AS laporan, COALESCE(p.foto_url,'') AS foto_url,
             DAYNAME(p.tanggal) AS hari_en,
             dg.jabatan, dg.jabatan_fungsional
      FROM piket_guru p
      LEFT JOIN data_guru dg ON p.id_guru = dg.id
      WHERE p.tanggal = ?
      ORDER BY p.waktu ASC");
  $stmt->execute([$tgl]);
  $rows = $pdo->query("SELECT 1")->fetchColumn(); // dummy to keep pdo active
  $stmt->execute([$tgl]);
  $rows = $stmt->fetchAll();
  $hariMap = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa',
               'Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
  $result = [];
  foreach ($rows as $r) {
    $result[] = [
      'id'       => $r['id'],
      'tanggal'  => $r['tanggal'],
      'hari'     => $hariMap[$r['hari_en']] ?? $r['hari_en'],
      'idGuru'   => $r['id_guru'],
      'namaGuru' => $r['nama_guru'],
      'waktu'    => $r['waktu'] ? substr($r['waktu'],0,5) : '-',
      'jabatan'  => $r['jabatan'] ?? 'Guru',
      'laporan'  => $r['laporan'] ?? '',
      'foto'     => $r['foto_url'] ?? '',
    ];
  }
  jsonOut($result);
}

function getPiketRekap() {
  $d   = getInput();
  $bln = (int)($d['bulan'] ?? date('n'));
  $thn = (int)($d['tahun'] ?? date('Y'));
  $mulai   = sprintf('%04d-%02d-01', $thn, $bln);
  $selesai = date('Y-m-t', strtotime($mulai));
  $pdo = getDB();
  $stmt = $pdo->prepare(
    "SELECT p.id, p.tanggal, p.id_guru, p.nama_guru, p.waktu,
             COALESCE(p.laporan,'') AS laporan, COALESCE(p.foto_url,'') AS foto_url,
             DAYNAME(p.tanggal) AS hari_en
      FROM piket_guru p
      WHERE p.tanggal BETWEEN ? AND ?
      ORDER BY p.tanggal DESC, p.waktu ASC");
  $stmt->execute([$mulai, $selesai]);
  $rows = $stmt->fetchAll();
  $hariMap = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa',
               'Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
  $result = [];
  foreach ($rows as $r) {
    $result[] = [
      'id'       => $r['id'],
      'tanggal'  => $r['tanggal'],
      'hari'     => $hariMap[$r['hari_en']] ?? $r['hari_en'],
      'idGuru'   => $r['id_guru'],
      'namaGuru' => $r['nama_guru'],
      'waktu'    => $r['waktu'] ? substr($r['waktu'],0,5) : '-',
    ];
  }
  jsonOut(['detail' => $result, 'total' => count($result)]);
}

// ════════════════════════════════════════════════════════════════
// GURU DATA (Portal)
// ════════════════════════════════════════════════════════════════
function getGuruById() {
  $nip = getInput()['nip'] ?? '';
  $row = getDB()->prepare("SELECT * FROM data_guru WHERE id=? LIMIT 1");
  $row->execute([$nip]);
  $guru = $row->fetch();
  if (!$guru) jsonOut(['success'=>false,'msg'=>'Guru tidak ditemukan']);

  // Mapel list (array string) — JS renderProfile baca d.mapels
  $mapels = array_values(array_filter([
    $guru['mapel_1'] ?? '', $guru['mapel_2'] ?? '',
    $guru['mapel_3'] ?? '', $guru['mapel_4'] ?? ''
  ]));

  // Kelas list
  $kelas = array_values(array_filter(array_map('trim', explode(',', $guru['kelas'] ?? ''))));

  jsonOut([
    'success'          => true,
    'nama'             => $guru['nama'],
    'jabatan'          => $guru['jabatan'] ?? 'Guru',
    'jabFungsi'        => $guru['jabatan_fungsional'] ?? '',
    'tugas'            => $guru['tugas_tambahan'] ?? '',
    'mapels'           => $mapels,
    'kelasList'        => $kelas,
    'status'           => $guru['status'] ?? '',
    'alamat'           => $guru['alamat'] ?? '',
    'no_wa'            => $guru['no_wa'] ?? '',
    'noWa'             => $guru['no_wa'] ?? '',
    'foto'             => $guru['foto_url'] ?? '',
  ]);
}

// ════════════════════════════════════════════════════════════════
// CP / TP / MATERI / KEGIATAN MANDIRI — tanpa perlu jadwal dibuat
// dulu. Guru bisa isi langsung berdasarkan mapel yang sudah dibagi
// per-guru di data guru (mapel_1..mapel_4) × kelas yang diampu.
// ════════════════════════════════════════════════════════════════
function getMapelKelasGuruMandiri() {
  $d   = getInput();
  $nip = $d['nip'] ?? $d['id_guru'] ?? '';
  $tgl = $d['tanggal'] ?? date('Y-m-d');
  if (!$nip) jsonOut(['success'=>false,'msg'=>'NIP guru wajib diisi']);
  $pdo = getDB();

  $row = $pdo->prepare("SELECT * FROM data_guru WHERE id=? LIMIT 1");
  $row->execute([$nip]);
  $guru = $row->fetch();
  if (!$guru) jsonOut(['success'=>false,'msg'=>'Guru tidak ditemukan']);

  $mapels = array_values(array_filter([
    $guru['mapel_1'] ?? '', $guru['mapel_2'] ?? '',
    $guru['mapel_3'] ?? '', $guru['mapel_4'] ?? ''
  ]));
  $kelas = array_values(array_filter(array_map('trim', explode(',', $guru['kelas'] ?? ''))));

  // Migrasi aman: kolom penanda entri "tanpa jadwal"
  try { $pdo->exec("ALTER TABLE data_presensi ADD COLUMN IF NOT EXISTS tanpa_jadwal TINYINT(1) DEFAULT 0"); } catch(Exception $e) {}
  try { $pdo->exec("ALTER TABLE data_presensi ADD COLUMN IF NOT EXISTS kegiatan TEXT DEFAULT NULL"); } catch(Exception $e) {}
  // FIX: kolom kegiatan/materi/cp/tp bisa sudah ada dari skema lama dengan
  // tipe VARCHAR pendek (mis. VARCHAR(255)) -> menyebabkan error SQLSTATE[22001]
  // "Data too long for column" saat teks kegiatan/materi panjang disimpan.
  // ADD COLUMN IF NOT EXISTS di atas TIDAK mengubah tipe kolom yang sudah ada,
  // jadi kita paksa ubah tipenya ke TEXT (idempotent, aman dipanggil berulang).
  try { $pdo->exec("ALTER TABLE data_presensi MODIFY COLUMN kegiatan TEXT DEFAULT NULL"); } catch(Exception $e) {}
  try { $pdo->exec("ALTER TABLE data_presensi MODIFY COLUMN materi TEXT DEFAULT NULL"); } catch(Exception $e) {}
  try { $pdo->exec("ALTER TABLE data_presensi MODIFY COLUMN cp TEXT DEFAULT NULL"); } catch(Exception $e) {}
  try { $pdo->exec("ALTER TABLE data_presensi MODIFY COLUMN tp TEXT DEFAULT NULL"); } catch(Exception $e) {}
  try { $pdo->exec("ALTER TABLE data_presensi ADD COLUMN IF NOT EXISTS cp TEXT DEFAULT NULL"); } catch(Exception $e) {}
  try { $pdo->exec("ALTER TABLE data_presensi ADD COLUMN IF NOT EXISTS tp TEXT DEFAULT NULL"); } catch(Exception $e) {}
  try { $pdo->exec("ALTER TABLE data_presensi ADD COLUMN IF NOT EXISTS waktu TIME DEFAULT NULL"); } catch(Exception $e) {}

  // Entri mandiri yang sudah pernah diisi guru ini utk tanggal terpilih,
  // supaya kartu mapel×kelas langsung tampil terisi (bukan kosong lagi).
  $existing = [];
  $stE = $pdo->prepare("SELECT kelas, mapel, materi, kegiatan, cp, tp, waktu FROM data_presensi WHERE id_guru=? AND tanggal=? AND tanpa_jadwal=1");
  $stE->execute([$nip, $tgl]);
  foreach ($stE->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $key = $r['kelas'] . '|' . $r['mapel'];
    $existing[$key] = [
      'materi'   => $r['materi']   ?? '',
      'kegiatan' => $r['kegiatan'] ?? '',
      'cp'       => $r['cp']       ?? '',
      'tp'       => $r['tp']       ?? '',
      'waktu'    => $r['waktu'] ? substr($r['waktu'],0,5) : '',
    ];
  }

  jsonOut([
    'success'   => true,
    'mapels'    => $mapels,
    'kelasList' => $kelas,
    'tanggal'   => $tgl,
    'existing'  => $existing,
  ]);
}

function simpanCPTPMandiri() {
  $d   = getInput();
  $pdo = getDB();
  $nip = $d['nip'] ?? $d['idGuru'] ?? $d['id_guru'] ?? '';
  $nm  = $d['nama'] ?? $d['namaGuru'] ?? $d['nama_guru'] ?? '';
  $kls = trim($d['kelas'] ?? '');
  $mp  = trim($d['mapel'] ?? '');
  $tgl = $d['tanggal'] ?? date('Y-m-d');
  $now = date('H:i:s');

  if (!$nip || !$kls || !$mp) jsonOut(['success'=>false,'msg'=>'Guru, kelas, dan mapel wajib diisi']);

  $mat = trim($d['materi']   ?? '');
  $keg = trim($d['kegiatan'] ?? '');
  $cp  = trim($d['cp']       ?? '');
  $tp  = trim($d['tp']       ?? '');
  if ($mat === '' && $keg === '' && $cp === '' && $tp === '') {
    jsonOut(['success'=>false,'msg'=>'Isi minimal salah satu: CP, TP, Materi, atau Kegiatan']);
  }

  // Migrasi aman
  try { $pdo->exec("ALTER TABLE data_presensi ADD COLUMN IF NOT EXISTS tanpa_jadwal TINYINT(1) DEFAULT 0"); } catch(Exception $e) {}
  try { $pdo->exec("ALTER TABLE data_presensi ADD COLUMN IF NOT EXISTS waktu TIME DEFAULT NULL"); } catch(Exception $e) {}
  try { $pdo->exec("ALTER TABLE data_presensi ADD COLUMN IF NOT EXISTS jam_ke_sampai INT DEFAULT NULL"); } catch(Exception $e) {}
  try { $pdo->exec("ALTER TABLE data_presensi ADD COLUMN IF NOT EXISTS kegiatan TEXT DEFAULT NULL"); } catch(Exception $e) {}
  // FIX: kolom kegiatan/materi/cp/tp bisa sudah ada dari skema lama dengan
  // tipe VARCHAR pendek (mis. VARCHAR(255)) -> menyebabkan error SQLSTATE[22001]
  // "Data too long for column" saat teks kegiatan/materi panjang disimpan.
  // ADD COLUMN IF NOT EXISTS di atas TIDAK mengubah tipe kolom yang sudah ada,
  // jadi kita paksa ubah tipenya ke TEXT (idempotent, aman dipanggil berulang).
  try { $pdo->exec("ALTER TABLE data_presensi MODIFY COLUMN kegiatan TEXT DEFAULT NULL"); } catch(Exception $e) {}
  try { $pdo->exec("ALTER TABLE data_presensi MODIFY COLUMN materi TEXT DEFAULT NULL"); } catch(Exception $e) {}
  try { $pdo->exec("ALTER TABLE data_presensi MODIFY COLUMN cp TEXT DEFAULT NULL"); } catch(Exception $e) {}
  try { $pdo->exec("ALTER TABLE data_presensi MODIFY COLUMN tp TEXT DEFAULT NULL"); } catch(Exception $e) {}
  try { $pdo->exec("ALTER TABLE data_presensi ADD COLUMN IF NOT EXISTS cp TEXT DEFAULT NULL"); } catch(Exception $e) {}
  try { $pdo->exec("ALTER TABLE data_presensi ADD COLUMN IF NOT EXISTS tp TEXT DEFAULT NULL"); } catch(Exception $e) {}

  // jam_ke dikunci ke 0 sebagai penanda entri "tanpa jadwal" (bukan sesi jam
  // pelajaran nyata) — dibedakan dari presensi asli lewat kolom tanpa_jadwal.
  $cek = $pdo->prepare("SELECT id FROM data_presensi WHERE id_guru=? AND tanggal=? AND kelas=? AND mapel=? AND tanpa_jadwal=1");
  $cek->execute([$nip, $tgl, $kls, $mp]);
  $existing = $cek->fetch();

  if ($existing) {
    $pdo->prepare("UPDATE data_presensi SET materi=?, kegiatan=?, cp=?, tp=?, waktu=?, nama_guru=COALESCE(NULLIF(?,''),nama_guru) WHERE id=?")
        ->execute([$mat, $keg, $cp, $tp, $now, $nm, $existing['id']]);
    jsonOut(['success'=>true,'msg'=>'CP/TP/Materi/Kegiatan berhasil diperbarui!','waktu'=>$now,'id'=>$existing['id']]);
  }

  $id = 'PRM' . time() . rand(100,999);
  $pdo->prepare("INSERT INTO data_presensi (id,tanggal,id_guru,nama_guru,kelas,jam_ke,jam_ke_sampai,status,mapel,materi,kegiatan,cp,tp,waktu,tanpa_jadwal) VALUES (?,?,?,?,?,0,0,'Mengajar',?,?,?,?,?,?,1)")
      ->execute([$id,$tgl,$nip,$nm,$kls,$mp,$mat,$keg,$cp,$tp,$now]);
  jsonOut(['success'=>true,'msg'=>'CP/TP/Materi/Kegiatan berhasil disimpan!','id'=>$id,'waktu'=>$now]);
}

function getServerTime() {
  $days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
  $bln  = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
  jsonOut([
    'timestamp'  => time() * 1000,
    'tanggal'    => date('Y-m-d'),
    'jam'        => date('H:i:s'),
    'hari'       => $days[date('w')],
    'hariTanggal'=> $days[date('w')] . ', ' . date('j') . ' ' . $bln[(int)date('n')] . ' ' . date('Y'),
  ]);
}

function getAllDataBatch() {
  // Setiap query dibungkus try-catch agar satu tabel bermasalah
  // tidak menghentikan seluruh batch response
  $pdo = getDB();

  $guru = [];
  try { $guru = toCamel($pdo->query("SELECT * FROM data_guru ORDER BY nama")->fetchAll()); } catch(Exception $e) {}

  $rombel = [];
  try {
    $rombel = toCamel($pdo->query(
      "SELECT r.*, COALESCE(NULLIF(r.wali_kelas,''), w.nama_wali, '') AS wali_kelas_sync
       FROM rombel r
       LEFT JOIN wali_kelas w ON UPPER(TRIM(w.kelas))=UPPER(TRIM(r.nama_rombel))
       ORDER BY r.tingkat, r.nama_rombel"
    )->fetchAll());
    foreach ($rombel as &$rb) {
      $rb['wali_kelas'] = $rb['wali_kelas_sync'] ?? $rb['wali_kelas'] ?? '';
      $rb['waliKelas']  = $rb['wali_kelas'];
      unset($rb['wali_kelas_sync']);
    }
    unset($rb);
  } catch(Exception $e) {}

  $mapel = [];
  try { $mapel = $pdo->query("SELECT id, nama_mapel AS nama, keterangan FROM mata_pelajaran ORDER BY nama_mapel")->fetchAll(); } catch(Exception $e) {}

  $wali = [];
  try { $wali = toCamel($pdo->query("SELECT id, username, nama_wali, kelas FROM wali_kelas ORDER BY kelas")->fetchAll()); } catch(Exception $e) {}

  $jadwal = [];
  try {
    $jadwalRaw = $pdo->query(
      "SELECT j.*,
              COALESCE(NULLIF(r.wali_kelas,''), w.nama_wali, '') AS wali_kelas_merged,
              COALESCE(r.jumlah_siswa, 0) AS jumlah_siswa_merged
       FROM jadwal_mengajar j
       LEFT JOIN rombel r ON UPPER(TRIM(j.kelas))=UPPER(TRIM(r.nama_rombel))
       LEFT JOIN wali_kelas w ON UPPER(TRIM(w.kelas))=UPPER(TRIM(j.kelas))
       ORDER BY FIELD(j.hari,'Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'), j.jam_ke"
    )->fetchAll();
    $jadwal = toCamel($jadwalRaw);
    foreach ($jadwal as &$jrow) {
      $jrow['waliKelas']   = $jrow['wali_kelas_merged'] ?? $jrow['waliKelas'] ?? '';
      $jrow['jumlahSiswa'] = (int)($jrow['jumlah_siswa_merged'] ?? $jrow['jumlahSiswa'] ?? 0);
      unset($jrow['wali_kelas_merged'], $jrow['jumlah_siswa_merged']);
    }
    unset($jrow);
  } catch(Exception $e) {}

  $pengaturan = ['namaSekolah'=>'MAN 2 LOMBOK TIMUR','nama'=>'','alamat'=>'','telepon'=>'','email'=>'','logoUrl'=>'','logo'=>''];
  foreach (['pengaturan','pengaturan_sekolah'] as $tbl) {
    try {
      $peng = $pdo->query("SELECT * FROM $tbl LIMIT 1")->fetch();
      if ($peng) {
        $pengaturan['namaSekolah'] = $peng['nama_sekolah'] ?? $pengaturan['namaSekolah'];
        $pengaturan['nama']        = $peng['nama_sekolah'] ?? '';
        $pengaturan['alamat']      = $peng['alamat']   ?? '';
        $pengaturan['telepon']     = $peng['telepon']  ?? '';
        $pengaturan['email']       = $peng['email']    ?? '';
        $pengaturan['logoUrl']     = $peng['logo_url'] ?? '';
        $pengaturan['logo']        = $peng['logo_url'] ?? '';
        break;
      }
    } catch(Exception $e) {}
  }

  $today    = date('Y-m-d');
  $hariNama = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'][(int)date('w')];

  $totalGuru   = 0; try { $totalGuru   = (int)$pdo->query("SELECT COUNT(*) FROM data_guru WHERE UPPER(status)='AKTIF' OR status IS NULL OR status=''")->fetchColumn(); } catch(Exception $e) {}
  $totalSiswa  = 0; try { $totalSiswa  = (int)$pdo->query("SELECT COUNT(*) FROM data_siswa")->fetchColumn(); } catch(Exception $e) {}
  $totalKelas  = 0; try { $totalKelas  = (int)$pdo->query("SELECT COUNT(*) FROM rombel")->fetchColumn(); } catch(Exception $e) {}
  $totalJadwal = 0; try { $totalJadwal = (int)$pdo->query("SELECT COUNT(*) FROM jadwal_mengajar")->fetchColumn(); } catch(Exception $e) {}
  $hadirHariIni= 0; try {
    $sh = $pdo->prepare("SELECT COUNT(DISTINCT id_guru) FROM data_presensi WHERE tanggal=? AND status = 'Hadir'");
    $sh->execute([$today]); $hadirHariIni = (int)$sh->fetchColumn();
  } catch(Exception $e) {}

  $jadwalHariIni = [];
  try {
    // Ambil semester aktif & tanggal range
    $saRow = null; $tpMulai = null; $tpSelesai = null;
    try {
      $saRow = $pdo->query("SELECT sa.tahun_pelajaran, sa.semester, tp.tanggal_mulai, tp.tanggal_selesai
                             FROM semester_aktif sa
                             LEFT JOIN tahun_pelajaran tp ON tp.nama = sa.tahun_pelajaran
                             WHERE sa.id = 1 LIMIT 1")->fetch();
      if ($saRow && !empty($saRow['tahun_pelajaran'])) {
        $tpMulai   = $saRow['tanggal_mulai']  ?? null;
        $tpSelesai = $saRow['tanggal_selesai'] ?? null;
      }
    } catch (Exception $e) { $saRow = null; }

    $jdWhere  = "j.hari = ?";
    $jdParams = [$hariNama];
    if ($saRow && !empty($saRow['tahun_pelajaran'])) {
      $jdWhere  .= " AND (j.tahun_pelajaran = '' OR j.tahun_pelajaran IS NULL OR j.tahun_pelajaran = ?)";
      $jdParams[] = $saRow['tahun_pelajaran'];
    }
    $sjh = $pdo->prepare("SELECT j.*, dg.nama AS namaGuru, j.jam_ke AS jamKe FROM jadwal_mengajar j LEFT JOIN data_guru dg ON j.guru_id=dg.id WHERE $jdWhere ORDER BY j.jam_ke");
    $sjh->execute($jdParams);
    $jadwalHariIni = toCamel($sjh->fetchAll());
  } catch(Exception $e) {}

  jsonOut([
    'guru'       => $guru,
    'rombel'     => $rombel,
    'mapel'      => $mapel,
    'wali'       => $wali,
    'jadwal'     => $jadwal,
    'pengaturan' => $pengaturan,
    'db' => [
      'guru'          => $totalGuru,
      'siswa'         => $totalSiswa,
      'kelas'         => $totalKelas,
      'jadwal'        => $totalJadwal,
      'hadirHariIni'  => $hadirHariIni,
      'jadwalHariIni' => $jadwalHariIni,
    ],
  ]);
}

// ════════════════════════════════════════════════════════════════
// PENGATURAN & PASSWORD
// ════════════════════════════════════════════════════════════════
function gantiPassword() {
  $d   = getInput();
  $pdo = getDB();
  $usr = $d['username'] ?? 'admin';
  $lama= $d['passwordLama'] ?? '';
  $baru= $d['passwordBaru'] ?? '';
  $stmt= $pdo->prepare("SELECT password FROM admin WHERE username=?");
  $stmt->execute([$usr]); $row = $stmt->fetch();
  if (!$row) jsonOut(['success'=>false,'msg'=>'User tidak ditemukan']);
  if (!password_verify($lama, $row['password']) && $row['password'] !== $lama)
    jsonOut(['success'=>false,'msg'=>'Password lama salah!']);
  $pdo->prepare("UPDATE admin SET password=? WHERE username=?")->execute([password_hash($baru,PASSWORD_DEFAULT),$usr]);
  jsonOut(['success'=>true,'msg'=>'Password berhasil diganti!']);
}

// ════════════════════════════════════════════════════════════════
// ════════════════════════════════════════════════════════════════
// WAKTU JAM PELAJARAN (jam 1 – 10)
// ════════════════════════════════════════════════════════════════
function apiGetWaktuJam() {
  $pdo = getDB();
  try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS waktu_jam_sekolah (
      jam_ke      INT         NOT NULL PRIMARY KEY,
      jam_mulai   VARCHAR(5)  NOT NULL DEFAULT '07:00',
      jam_selesai VARCHAR(5)  NOT NULL DEFAULT '07:45',
      keterangan  VARCHAR(100) DEFAULT ''
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    // Seed default jika kosong
    $cnt = (int)$pdo->query("SELECT COUNT(*) FROM waktu_jam_sekolah")->fetchColumn();
    if ($cnt === 0) {
      $ins = $pdo->prepare("INSERT IGNORE INTO waktu_jam_sekolah (jam_ke,jam_mulai,jam_selesai,keterangan) VALUES (?,?,?,?)");
      foreach ([
        [1,'07:00','07:45','Jam 1'],[2,'07:45','08:30','Jam 2'],
        [3,'08:30','09:15','Jam 3'],[4,'09:15','10:00','Jam 4'],
        [5,'10:15','11:00','Jam 5'],[6,'11:00','11:45','Jam 6'],
        [7,'12:30','13:15','Jam 7'],[8,'13:15','14:00','Jam 8'],
        [9,'14:00','14:45','Jam 9'],[10,'14:45','15:30','Jam 10'],
      ] as $r) $ins->execute($r);
    }
  } catch(Exception $e) {}
  $rows = $pdo->query("SELECT jam_ke, jam_mulai, jam_selesai, keterangan FROM waktu_jam_sekolah ORDER BY jam_ke ASC")->fetchAll(PDO::FETCH_ASSOC);
  $map = [];
  foreach ($rows as $r) {
    $map[(int)$r['jam_ke']] = [
      'jamMulai'   => $r['jam_mulai'],
      'jamSelesai' => $r['jam_selesai'],
      'keterangan' => $r['keterangan'],
    ];
  }
  jsonOut(['success' => true, 'data' => $rows, 'map' => $map]);
}

function apiSimpanWaktuJam() {
  $d    = getInput();
  $rows = $d['rows'] ?? [];
  if (!is_array($rows) || !count($rows)) {
    jsonOut(['success' => false, 'message' => 'Data kosong']); return;
  }
  $pdo  = getDB();
  try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS waktu_jam_sekolah (
      jam_ke      INT         NOT NULL PRIMARY KEY,
      jam_mulai   VARCHAR(5)  NOT NULL DEFAULT '07:00',
      jam_selesai VARCHAR(5)  NOT NULL DEFAULT '07:45',
      keterangan  VARCHAR(100) DEFAULT ''
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  } catch(Exception $e) {}
  $stmt = $pdo->prepare(
    "INSERT INTO waktu_jam_sekolah (jam_ke,jam_mulai,jam_selesai,keterangan) VALUES (?,?,?,?)
     ON DUPLICATE KEY UPDATE jam_mulai=VALUES(jam_mulai),jam_selesai=VALUES(jam_selesai),keterangan=VALUES(keterangan)"
  );
  $saved = 0;
  foreach ($rows as $r) {
    $jk  = (int)($r['jamKe']     ?? $r['jam_ke']     ?? 0);
    $mul = trim($r['jamMulai']   ?? $r['jam_mulai']  ?? '');
    $sel = trim($r['jamSelesai'] ?? $r['jam_selesai']?? '');
    $ket = trim($r['keterangan'] ?? '');
    if ($jk < 1 || $jk > 10) continue;
    if (!preg_match('/^\d{2}:\d{2}$/', $mul) || !preg_match('/^\d{2}:\d{2}$/', $sel)) continue;
    $stmt->execute([$jk, $mul, $sel, $ket]);
    $saved++;
  }
  jsonOut(['success' => true, 'message' => "$saved waktu jam berhasil disimpan!"]);
}

// HELPER: Konversi snake_case → camelCase untuk semua rows
// ════════════════════════════════════════════════════════════════
function toCamel($rows) {
  if (!$rows) return [];
  return array_map(function($r) { return rowCamel($r); }, $rows);
}

function rowCamel($r) {
  if (!$r) return $r;
  $out = [];
  foreach ($r as $k => $v) {
    // Langsung tambah alias camelCase
    $out[$k] = $v; // tetap simpan original juga
  }
  // Alias spesifik yang dipakai frontend
  $aliases = [
    // Rombel
    'nama_rombel'         => 'nama',
    'wali_kelas'          => 'waliKelas',
    'jumlah_siswa'        => 'jumlahSiswa',
    // Wali
    'nama_wali'           => 'namaWali',
    // Guru
    'jabatan_fungsional'  => 'jabFungsi',
    'tugas_tambahan'      => 'tugas',
    'mapel_1'             => 'mapel1',
    'mapel_2'             => 'mapel2',
    'mapel_3'             => 'mapel3',
    'mapel_4'             => 'mapel4',
    'foto_url'            => 'foto',
    'nama_mapel'          => 'nama',
    // Presensi / Jadwal
    'id_guru'             => 'guruId',
    'nama_guru'           => 'namaGuru',
    'jam_ke'              => 'jamKe',
    'jam_ke_sampai'       => 'jamKeSampai',
    'jam_mulai'           => 'jamMulai',
    'jam_selesai'         => 'jamSelesai',
    'nama_siswa'          => 'namaSiswa',
    'nama_lengkap'        => 'namaLengkap',
    'guru_id'             => 'guruId',
    // Jadwal
    'total_mengajar'      => 'totalMengajar',
    'hari_hadir'          => 'hariHadir',
    'total_hari'          => 'totalHari',
    // Absensi rekap
    'jumlah_hadir'        => 'hadir',
    'jumlah_alpa'         => 'alpa',
    'jumlah_sakit'        => 'sakit',
    'jumlah_izin'         => 'izin',
  ];
  foreach ($aliases as $snake => $camel) {
    if (array_key_exists($snake, $r)) $out[$camel] = $r[$snake];
  }
  return $out;
}

// ════════════════════════════════════════════════════════════════
// PORTAL BK — CATATAN BK, KUNJUNGAN RUMAH, BUKU KEJADIAN
// (ditambahkan supaya PortalBK.html punya backend; data siswa selalu
//  diambil dari tabel data_siswa milik Admin — bukan tabel terpisah)
// ════════════════════════════════════════════════════════════════

function ensureBkTables($pdo) {
  // Catatan BK: catatan guru BK ke siswa + balasan siswa (mengikuti pola
  // chat pesan_wali pada sistem lama: 1 baris = 1 catatan, field balasan
  // diisi belakangan saat siswa membalas dari Portal Siswa)
  $pdo->exec("CREATE TABLE IF NOT EXISTS catatan_bk (
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

  // Kunjungan Rumah Siswa
  $pdo->exec("CREATE TABLE IF NOT EXISTS kunjungan_rumah (
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

  // Buku Kejadian — kolomnya mengikuti format jurnal manual:
  // Hari/Tanggal, Nama Siswa, Kelas, Uraian Kejadian, Poin,
  // Tanggapan Siswa, Arahan Guru Wali, Tindak Lanjut
  $pdo->exec("CREATE TABLE IF NOT EXISTS buku_kejadian (
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
}

// Decode gambar base64 (data:image/...;base64,....) dan simpan ke UPLOAD_DIR.
// Mengikuti pola persis yang sudah dipakai uploadTtdGuru() di atas.
function simpanGambarBase64BK($b64, $prefix) {
  if (!$b64 || !preg_match('/^data:(image\/[a-zA-Z]+);base64,/', $b64, $m)) return '';
  $ext  = str_replace('image/', '', $m[1]);
  $data = base64_decode(preg_replace('/^data:image\/[a-zA-Z]+;base64,/', '', $b64));
  if (!$data) return '';
  $fn = $prefix . '_' . time() . rand(100, 999) . '.' . $ext;
  if (!@file_put_contents(UPLOAD_DIR . $fn, $data)) return '';
  return UPLOAD_URL . $fn;
}

// ── CATATAN BK ─────────────────────────────────────────────────

// Daftar semua siswa di 1 kelas (sumber: data_siswa milik Admin), digabung
// dengan catatan_bk TERBARU milik masing-masing siswa (kalau ada).
function getSiswaCatatanBK() {
  $d     = getInput();
  $kelas = trim($d['kelas'] ?? '');
  $cari  = trim($d['cari']  ?? '');
  $pdo   = getDB();
  ensureBkTables($pdo);

  if (!$kelas) jsonOut(['success' => true, 'data' => []]);

  $where  = "WHERE UPPER(TRIM(s.kelas)) = UPPER(?)";
  $params = [$kelas];
  if ($cari !== '') { $where .= " AND s.nama LIKE ?"; $params[] = '%' . $cari . '%'; }

  $sql = "SELECT s.id AS siswaId, s.nis, s.nama, s.kelas,
                 c.id AS catatanId, c.isi, c.balasan, c.dibalas_at
          FROM data_siswa s
          LEFT JOIN catatan_bk c ON c.id = (
              SELECT id FROM catatan_bk WHERE siswa_id = s.id ORDER BY id DESC LIMIT 1
          )
          $where ORDER BY s.nama";
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);

  $out = [];
  foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $out[] = [
      'siswaId'   => $r['siswaId'],
      'nama'      => $r['nama'],
      'kelas'     => $r['kelas'],
      'nis'       => $r['nis'],
      'terkirim'  => $r['catatanId'] ? 1 : 0,
      'catatanId' => $r['catatanId'],
      'isi'       => $r['isi'] ?? '',
      'balasan'   => $r['balasan'] ?? null,
      'dibalasAt' => $r['dibalas_at'] ?? null,
    ];
  }
  jsonOut(['success' => true, 'data' => $out]);
}

// Kirim cepat per-baris (tab Catatan BK, tombol "Kirim" di setiap siswa)
function kirimLangsungCatatanBK() {
  $d        = getInput();
  $pdo      = getDB();
  ensureBkTables($pdo);
  $siswaId  = (int)($d['siswa_id'] ?? 0);
  $nama     = trim($d['nama_siswa'] ?? '');
  $kelas    = trim($d['kelas'] ?? '');
  $nis      = trim($d['nis'] ?? '');
  $isi      = trim($d['isi'] ?? '');
  $guruBkId = (int)($d['guru_bk_id'] ?? 0);
  $namaGuru = trim($d['nama_guru'] ?? '');

  if (!$siswaId) jsonErr('Siswa tidak valid!');
  if (!$isi)     jsonErr('Isi catatan wajib diisi!');

  $pdo->prepare("INSERT INTO catatan_bk (siswa_id,nis,nama_siswa,kelas,guru_bk_id,nama_guru,tipe,judul,isi)
                  VALUES (?,?,?,?,?,?,'Informasi','Catatan BK',?)")
      ->execute([$siswaId, $nis, $nama, $kelas, $guruBkId, $namaGuru, $isi]);

  jsonOut(['success' => true, 'msg' => 'Catatan berhasil dikirim ke Portal Siswa!', 'id' => $pdo->lastInsertId()]);
}

// Simpan dari modal "Tambah Catatan" (punya tipe & judul)
function simpanCatatanBK() {
  $d        = getInput();
  $pdo      = getDB();
  ensureBkTables($pdo);
  $siswaId  = (int)($d['siswa_id'] ?? 0);
  $nama     = trim($d['nama_siswa'] ?? '');
  $kelas    = trim($d['kelas'] ?? '');
  $nis      = trim($d['nis'] ?? '');
  $tipe     = trim($d['tipe'] ?? 'Informasi');
  $judul    = trim($d['judul'] ?? '');
  $isi      = trim($d['isi'] ?? '');
  $guruBkId = (int)($d['guru_bk_id'] ?? 0);
  $namaGuru = trim($d['nama_guru'] ?? '');

  if (!$siswaId) jsonErr('Pilih siswa terlebih dahulu!');
  if (!$judul)   jsonErr('Judul catatan wajib diisi!');
  if (!$isi)     jsonErr('Isi catatan wajib diisi!');
  if (!in_array($tipe, ['Informasi', 'Peringatan', 'Urgent', 'Apresiasi'])) $tipe = 'Informasi';

  $pdo->prepare("INSERT INTO catatan_bk (siswa_id,nis,nama_siswa,kelas,guru_bk_id,nama_guru,tipe,judul,isi)
                  VALUES (?,?,?,?,?,?,?,?,?)")
      ->execute([$siswaId, $nis, $nama, $kelas, $guruBkId, $namaGuru, $tipe, $judul, $isi]);

  jsonOut(['success' => true, 'msg' => 'Catatan berhasil disimpan!', 'id' => $pdo->lastInsertId()]);
}

function editCatatanLangsungBK() {
  $d   = getInput();
  $id  = (int)($d['id'] ?? 0);
  $isi = trim($d['isi'] ?? '');
  if (!$id)  jsonErr('ID tidak valid!');
  if (!$isi) jsonErr('Isi catatan tidak boleh kosong!');
  getDB()->prepare("UPDATE catatan_bk SET isi=? WHERE id=?")->execute([$isi, $id]);
  jsonOut(['success' => true, 'msg' => 'Catatan berhasil diperbarui!']);
}

function hapusCatatanLangsungBK() {
  $d  = getInput();
  $id = (int)($d['id'] ?? 0);
  if (!$id) jsonErr('ID tidak valid!');
  getDB()->prepare("DELETE FROM catatan_bk WHERE id=?")->execute([$id]);
  jsonOut(['success' => true, 'msg' => 'Catatan berhasil dihapus!']);
}

// Badge jumlah balasan siswa yang belum dibaca guru BK
function getJumlahBalasanBaruBK() {
  $pdo = getDB();
  ensureBkTables($pdo);
  $n = (int)$pdo->query("SELECT COUNT(*) FROM catatan_bk
                          WHERE balasan IS NOT NULL AND balasan<>'' AND balasan_dibaca=0")->fetchColumn();
  jsonOut(['success' => true, 'jumlah' => $n]);
}

function tandaiBalasanDibacaBK() {
  $pdo = getDB();
  ensureBkTables($pdo);
  $pdo->exec("UPDATE catatan_bk SET balasan_dibaca=1 WHERE balasan IS NOT NULL AND balasan<>''");
  jsonOut(['success' => true]);
}

// ── KUNJUNGAN RUMAH SISWA ──────────────────────────────────────

function getKunjunganBK() {
  $d     = getInput();
  $pdo   = getDB();
  ensureBkTables($pdo);
  $kelas = trim($d['kelas'] ?? '');
  $cari  = trim($d['cari']  ?? '');

  $where = []; $params = [];
  if ($kelas) { $where[] = "UPPER(TRIM(kelas))=UPPER(?)"; $params[] = $kelas; }
  if ($cari)  { $where[] = "(nama_siswa LIKE ? OR nama_ortu LIKE ?)"; $params[] = "%$cari%"; $params[] = "%$cari%"; }

  $sql = "SELECT * FROM kunjungan_rumah";
  if ($where) $sql .= " WHERE " . implode(' AND ', $where);
  $sql .= " ORDER BY tanggal_kunjungan DESC, id DESC";

  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);

  $out = [];
  foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $out[] = [
      'id'                => $r['id'],
      'tanggal_kunjungan' => $r['tanggal_kunjungan'],
      'nama_siswa'        => $r['nama_siswa'],
      'kelas'             => $r['kelas'],
      'nama_ortu'         => $r['nama_ortu'],
      'kasus'             => $r['kasus'],
      'penyelesaian'      => $r['penyelesaian'],
      'keterangan'        => $r['keterangan'],
      'foto_url'          => $r['foto'] ?: null,
    ];
  }
  jsonOut(['success' => true, 'data' => $out]);
}

function simpanKunjunganBK() {
  $d        = getInput();
  $pdo      = getDB();
  ensureBkTables($pdo);
  $siswaId  = (int)($d['siswa_id'] ?? 0);
  $nama     = trim($d['nama_siswa'] ?? '');
  $kelas    = trim($d['kelas'] ?? '');
  $nis      = trim($d['nis'] ?? '');
  $tgl      = trim($d['tanggal_kunjungan'] ?? '');
  $ortu     = trim($d['nama_ortu'] ?? '');
  $kasus    = trim($d['kasus'] ?? '');
  $sel      = trim($d['penyelesaian'] ?? 'Belum Ditindaklanjuti');
  $ket      = trim($d['keterangan'] ?? '');
  $guruBkId = (int)($d['guru_bk_id'] ?? 0);
  $namaGuru = trim($d['nama_guru'] ?? '');

  if (!$siswaId) jsonErr('Pilih siswa terlebih dahulu!');
  if (!$tgl)     jsonErr('Tanggal kunjungan wajib diisi!');
  if (!$kasus)   jsonErr('Kasus wajib diisi!');
  if (!in_array($sel, ['Belum Ditindaklanjuti', 'Dalam Proses', 'Selesai'])) $sel = 'Belum Ditindaklanjuti';

  $fotoUrl = simpanGambarBase64BK($d['foto_base64'] ?? '', 'kunjungan_' . $siswaId);

  $pdo->prepare("INSERT INTO kunjungan_rumah
                  (siswa_id,nis,nama_siswa,kelas,guru_bk_id,nama_guru,tanggal_kunjungan,nama_ortu,kasus,penyelesaian,keterangan,foto)
                  VALUES (?,?,?,?,?,?,?,?,?,?,?,?)")
      ->execute([$siswaId, $nis, $nama, $kelas, $guruBkId, $namaGuru, $tgl, $ortu, $kasus, $sel, $ket, $fotoUrl]);

  jsonOut(['success' => true, 'msg' => 'Kunjungan berhasil disimpan!', 'id' => $pdo->lastInsertId()]);
}

function hapusKunjunganBK() {
  $d  = getInput();
  $id = (int)($d['id'] ?? 0);
  if (!$id) jsonErr('ID tidak valid!');
  getDB()->prepare("DELETE FROM kunjungan_rumah WHERE id=?")->execute([$id]);
  jsonOut(['success' => true, 'msg' => 'Data kunjungan berhasil dihapus!']);
}

// ── BUKU KEJADIAN (format sesuai jurnal manual) ────────────────

function getBukuKejadian() {
  $d     = getInput();
  $pdo   = getDB();
  ensureBkTables($pdo);
  $kelas = trim($d['kelas'] ?? '');
  $cari  = trim($d['cari']  ?? '');

  $where = []; $params = [];
  if ($kelas) { $where[] = "UPPER(TRIM(kelas))=UPPER(?)"; $params[] = $kelas; }
  if ($cari)  { $where[] = "nama_siswa LIKE ?"; $params[] = "%$cari%"; }

  $sql = "SELECT * FROM buku_kejadian";
  if ($where) $sql .= " WHERE " . implode(' AND ', $where);
  $sql .= " ORDER BY tanggal DESC, id DESC";

  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);

  $out = [];
  foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $out[] = [
      'id'              => $r['id'],
      'siswa_id'        => $r['siswa_id'],
      'tanggal'         => $r['tanggal'],
      'hari'            => $r['hari'],
      'namaSiswa'       => $r['nama_siswa'],
      'kelas'           => $r['kelas'],
      'uraianKejadian'  => $r['uraian_kejadian'],
      'poin'            => (int)$r['poin'],
      'tanggapanSiswa'  => $r['tanggapan_siswa'],
      'arahanGuruWali'  => $r['arahan_guru_wali'],
      'tindakLanjut'    => $r['tindak_lanjut'],
      'ttdUrl'          => $r['ttd'] ?: null,
    ];
  }
  jsonOut(['success' => true, 'data' => $out]);
}

function hariIndoDariTanggal($tgl) {
  $hariArr = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', "Jum'at", 'Sabtu'];
  $ts = strtotime($tgl);
  return $ts ? $hariArr[(int)date('w', $ts)] : '';
}

function simpanBukuKejadian() {
  $d         = getInput();
  $pdo       = getDB();
  ensureBkTables($pdo);
  $siswaId   = (int)($d['siswa_id'] ?? 0);
  $nama      = trim($d['nama_siswa'] ?? '');
  $kelas     = trim($d['kelas'] ?? '');
  $nis       = trim($d['nis'] ?? '');
  $tgl       = trim($d['tanggal'] ?? '');
  $uraian    = trim($d['uraian_kejadian'] ?? '');
  $poin      = (int)($d['poin'] ?? 0);
  $tanggapan = trim($d['tanggapan_siswa'] ?? '');
  $arahan    = trim($d['arahan_guru_wali'] ?? '');
  $tindak    = trim($d['tindak_lanjut'] ?? '');
  $guruBkId  = (int)($d['guru_bk_id'] ?? 0);
  $namaGuru  = trim($d['nama_guru'] ?? '');

  if (!$siswaId) jsonErr('Pilih siswa terlebih dahulu!');
  if (!$tgl)     jsonErr('Tanggal kejadian wajib diisi!');
  if (!$uraian)  jsonErr('Uraian kejadian wajib diisi!');

  $hari   = hariIndoDariTanggal($tgl);
  $ttdUrl = simpanGambarBase64BK($d['ttd_base64'] ?? '', 'ttd_kejadian_' . $siswaId);

  $pdo->prepare("INSERT INTO buku_kejadian
                  (siswa_id,nis,nama_siswa,kelas,guru_bk_id,nama_guru,tanggal,hari,uraian_kejadian,poin,tanggapan_siswa,arahan_guru_wali,tindak_lanjut,ttd)
                  VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
      ->execute([$siswaId, $nis, $nama, $kelas, $guruBkId, $namaGuru, $tgl, $hari, $uraian, $poin, $tanggapan, $arahan, $tindak, $ttdUrl]);

  jsonOut(['success' => true, 'msg' => 'Catatan kejadian berhasil disimpan!', 'id' => $pdo->lastInsertId()]);
}

function editBukuKejadian() {
  $d         = getInput();
  $pdo       = getDB();
  ensureBkTables($pdo);
  $id        = (int)($d['id'] ?? 0);
  if (!$id) jsonErr('ID tidak valid!');
  $siswaId   = (int)($d['siswa_id'] ?? 0);
  $nama      = trim($d['nama_siswa'] ?? '');
  $kelas     = trim($d['kelas'] ?? '');
  $nis       = trim($d['nis'] ?? '');
  $tgl       = trim($d['tanggal'] ?? '');
  $uraian    = trim($d['uraian_kejadian'] ?? '');
  $poin      = (int)($d['poin'] ?? 0);
  $tanggapan = trim($d['tanggapan_siswa'] ?? '');
  $arahan    = trim($d['arahan_guru_wali'] ?? '');
  $tindak    = trim($d['tindak_lanjut'] ?? '');
  $ttdLama   = trim($d['ttd_url_lama'] ?? '');

  if (!$tgl)    jsonErr('Tanggal kejadian wajib diisi!');
  if (!$uraian) jsonErr('Uraian kejadian wajib diisi!');

  $hari = hariIndoDariTanggal($tgl);
  $baru = simpanGambarBase64BK($d['ttd_base64'] ?? '', 'ttd_kejadian_' . $id);
  $ttdUrl = $baru ?: $ttdLama;

  $pdo->prepare("UPDATE buku_kejadian SET
                  siswa_id=?,nis=?,nama_siswa=?,kelas=?,tanggal=?,hari=?,uraian_kejadian=?,poin=?,
                  tanggapan_siswa=?,arahan_guru_wali=?,tindak_lanjut=?,ttd=? WHERE id=?")
      ->execute([$siswaId, $nis, $nama, $kelas, $tgl, $hari, $uraian, $poin, $tanggapan, $arahan, $tindak, $ttdUrl, $id]);

  jsonOut(['success' => true, 'msg' => 'Catatan kejadian berhasil diperbarui!']);
}

function hapusBukuKejadian() {
  $d  = getInput();
  $id = (int)($d['id'] ?? 0);
  if (!$id) jsonErr('ID tidak valid!');
  getDB()->prepare("DELETE FROM buku_kejadian WHERE id=?")->execute([$id]);
  jsonOut(['success' => true, 'msg' => 'Catatan kejadian berhasil dihapus!']);
}

// ── RINGKASAN / RIWAYAT SISWA (gabungan Pelanggaran + Catatan BK +
//    Kunjungan Rumah + Buku Kejadian) — dipakai tab Data Siswa ────

// Hitung Hadir/Terlambat/Alpa/Izin/Sakit dari tabel absensi_siswa (per NIS).
// Catatan: tabel ini berisi absensi PER JAM PELAJARAN (diisi guru mapel),
// bukan absensi harian QR (api/absen_siswa_qr.php). Kalau sekolah Anda
// memakai absen QR harian sebagai acuan utama, kirimkan file itu supaya
// angka Hadir/Terlambat/dst di sini bisa disinkronkan ke sumber yang sama.
function hitungAbsensiSiswaBK($pdo, $nis) {
  $hasil = ['hadir' => 0, 'terlambat' => 0, 'alpa' => 0, 'izin' => 0, 'sakit' => 0];
  if (!$nis) return $hasil;
  try {
    $stmt = $pdo->prepare("SELECT status, COUNT(*) c FROM absensi_siswa WHERE nis=? GROUP BY status");
    $stmt->execute([$nis]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $a) {
      $st = strtoupper(trim($a['status']));
      if (in_array($st, ['HDR', 'HADIR', 'H']))          $hasil['hadir']     += (int)$a['c'];
      elseif (in_array($st, ['TLT', 'TERLAMBAT', 'T']))  $hasil['terlambat'] += (int)$a['c'];
      elseif (in_array($st, ['ALP', 'ALPA', 'A']))       $hasil['alpa']      += (int)$a['c'];
      elseif (in_array($st, ['IZN', 'IZIN', 'I']))       $hasil['izin']      += (int)$a['c'];
      elseif (in_array($st, ['SKT', 'SAKIT', 'S']))      $hasil['sakit']     += (int)$a['c'];
    }
  } catch (Exception $e) {}
  return $hasil;
}

function getRingkasanSiswaBK() {
  $d       = getInput();
  $pdo     = getDB();
  ensureBkTables($pdo);
  $siswaId = (int)($d['siswa_id'] ?? 0);
  if (!$siswaId) jsonErr('Siswa tidak valid!');

  $s = $pdo->prepare("SELECT id, nis, nama, kelas FROM data_siswa WHERE id=?");
  $s->execute([$siswaId]);
  $row = $s->fetch(PDO::FETCH_ASSOC);
  if (!$row) jsonErr('Data siswa tidak ditemukan!');

  $abs = hitungAbsensiSiswaBK($pdo, $row['nis']);

  $stP = $pdo->prepare("SELECT COUNT(*) FROM pelanggaran WHERE siswa_id=?");
  $stP->execute([$siswaId]);
  $pelanggaran = (int)$stP->fetchColumn();

  $stK = $pdo->prepare("SELECT COUNT(*) FROM kunjungan_rumah WHERE siswa_id=?");
  $stK->execute([$siswaId]);
  $kunjungan = (int)$stK->fetchColumn();

  jsonOut(['success' => true, 'data' => [
    'nis' => $row['nis'], 'nama' => $row['nama'], 'kelas' => $row['kelas'],
    'hadir' => $abs['hadir'], 'terlambat' => $abs['terlambat'], 'alpa' => $abs['alpa'],
    'izin' => $abs['izin'], 'sakit' => $abs['sakit'],
    'pelanggaran' => $pelanggaran, 'kunjungan' => $kunjungan,
  ]]);
}

function getRiwayatAktivitasSiswa() {
  $d       = getInput();
  $pdo     = getDB();
  ensureBkTables($pdo);
  $siswaId = (int)($d['siswa_id'] ?? 0);
  if (!$siswaId) jsonErr('Siswa tidak valid!');

  $items = [];
  $totalPoin = 0;

  // Pelanggaran — sumber data: tabel Admin (pelanggaran + pelanggaran_jenis)
  $stP = $pdo->prepare("SELECT p.tanggal, p.jenis_nama, p.keterangan, COALESCE(pj.poin,1) AS poin
                         FROM pelanggaran p LEFT JOIN pelanggaran_jenis pj ON pj.id=p.jenis_id
                         WHERE p.siswa_id=?");
  $stP->execute([$siswaId]);
  foreach ($stP->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $totalPoin += (int)$r['poin'];
    $items[] = ['tanggal' => $r['tanggal'], 'jenis' => 'Pelanggaran', 'judul' => $r['jenis_nama'],
                'keterangan' => $r['keterangan'], 'poin' => (int)$r['poin']];
  }

  // Catatan BK
  $stC = $pdo->prepare("SELECT created_at, judul, isi FROM catatan_bk WHERE siswa_id=?");
  $stC->execute([$siswaId]);
  foreach ($stC->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $items[] = ['tanggal' => substr($r['created_at'], 0, 10), 'jenis' => 'Catatan BK',
                'judul' => $r['judul'] ?: 'Catatan BK', 'keterangan' => $r['isi'], 'poin' => 0];
  }

  // Kunjungan Rumah
  $stK = $pdo->prepare("SELECT tanggal_kunjungan, kasus, penyelesaian FROM kunjungan_rumah WHERE siswa_id=?");
  $stK->execute([$siswaId]);
  foreach ($stK->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $items[] = ['tanggal' => $r['tanggal_kunjungan'], 'jenis' => 'Kunjungan Rumah',
                'judul' => $r['kasus'], 'keterangan' => $r['penyelesaian'], 'poin' => 0];
  }

  // Buku Kejadian
  $stJ = $pdo->prepare("SELECT tanggal, uraian_kejadian, poin, tindak_lanjut FROM buku_kejadian WHERE siswa_id=?");
  $stJ->execute([$siswaId]);
  foreach ($stJ->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $totalPoin += (int)$r['poin'];
    $items[] = ['tanggal' => $r['tanggal'], 'jenis' => 'Buku Kejadian', 'judul' => $r['uraian_kejadian'],
                'keterangan' => $r['tindak_lanjut'], 'poin' => (int)$r['poin']];
  }

  usort($items, function ($a, $b) { return strcmp($b['tanggal'] ?? '', $a['tanggal'] ?? ''); });

  jsonOut(['success' => true, 'data' => $items, 'totalAktivitas' => count($items), 'totalPoin' => $totalPoin]);
}

// Tabel rekap per kelas (tab Data Siswa) — gabungan semua sumber di atas
function getRekapRiwayatSiswaBK() {
  $d     = getInput();
  $pdo   = getDB();
  ensureBkTables($pdo);
  $kelas = trim($d['kelas'] ?? '');
  if (!$kelas) jsonOut(['success' => true, 'data' => []]);

  $stmt = $pdo->prepare("SELECT id, nis, nama, kelas FROM data_siswa WHERE UPPER(TRIM(kelas))=UPPER(?) ORDER BY nama");
  $stmt->execute([$kelas]);
  $siswaList = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $out = [];
  foreach ($siswaList as $s) {
    $abs = hitungAbsensiSiswaBK($pdo, $s['nis']);

    $stP = $pdo->prepare("SELECT COUNT(*) c, COALESCE(SUM(pj.poin),0) poin
                           FROM pelanggaran p LEFT JOIN pelanggaran_jenis pj ON pj.id=p.jenis_id
                           WHERE p.siswa_id=?");
    $stP->execute([$s['id']]);
    $pRow = $stP->fetch(PDO::FETCH_ASSOC);
    $pelanggaran = (int)($pRow['c'] ?? 0);
    $poinPelanggaran = (int)($pRow['poin'] ?? 0);

    $stK = $pdo->prepare("SELECT COUNT(*) FROM kunjungan_rumah WHERE siswa_id=?");
    $stK->execute([$s['id']]);
    $kunjungan = (int)$stK->fetchColumn();

    $stJ = $pdo->prepare("SELECT COALESCE(SUM(poin),0) FROM buku_kejadian WHERE siswa_id=?");
    $stJ->execute([$s['id']]);
    $poinKejadian = (int)$stJ->fetchColumn();

    $out[] = [
      'siswaId' => $s['id'], 'nis' => $s['nis'], 'nama' => $s['nama'], 'kelas' => $s['kelas'],
      'hadir' => $abs['hadir'], 'terlambat' => $abs['terlambat'], 'alpa' => $abs['alpa'],
      'izin' => $abs['izin'], 'sakit' => $abs['sakit'],
      'pelanggaran' => $pelanggaran, 'kunjungan' => $kunjungan,
      'totalPoin' => $poinPelanggaran + $poinKejadian,
    ];
  }
  jsonOut(['success' => true, 'data' => $out]);
}
