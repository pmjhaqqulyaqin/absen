<?php
require_once 'includes/config.php';
cek_login();

// ============================================================
// BACKUP DATABASE - Download SQL
// ============================================================
if (isset($_GET['action']) && $_GET['action'] === 'download') {
    $db_host = DB_HOST;
    $db_user = DB_USER;
    $db_pass = DB_PASS;
    $db_name = DB_NAME;

    $filename = 'backup_' . $db_name . '_' . date('Ymd_His') . '.sql';

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache');

    $conn2 = new mysqli($db_host, $db_user, $db_pass, $db_name);
    $conn2->set_charset('utf8mb4');

    $output = "-- ============================================================\n";
    $output .= "-- BACKUP DATABASE: $db_name\n";
    $output .= "-- Tanggal: " . date('Y-m-d H:i:s') . " WITA\n";
    $output .= "-- Server: " . $db_host . "\n";
    $output .= "-- ============================================================\n\n";
    $output .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    $output .= "START TRANSACTION;\n";
    $output .= "SET time_zone = \"+08:00\";\n";
    $output .= "SET NAMES utf8mb4;\n\n";

    // Ambil semua tabel
    $tables = [];
    $result = $conn2->query("SHOW TABLES");
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }

    foreach ($tables as $table) {
        // Struktur tabel
        $output .= "-- --------------------------------------------------------\n";
        $output .= "-- Struktur tabel: `$table`\n";
        $output .= "-- --------------------------------------------------------\n\n";
        $output .= "DROP TABLE IF EXISTS `$table`;\n";

        $create = $conn2->query("SHOW CREATE TABLE `$table`")->fetch_row();
        $output .= $create[1] . ";\n\n";

        // Data tabel
        $rows = $conn2->query("SELECT * FROM `$table`");
        if ($rows->num_rows > 0) {
            $output .= "-- Data untuk tabel `$table`\n";

            // Kolom
            $fields = [];
            $field_info = $conn2->query("SHOW COLUMNS FROM `$table`");
            while ($f = $field_info->fetch_assoc()) {
                $fields[] = '`' . $f['Field'] . '`';
            }
            $fields_str = implode(', ', $fields);

            // Baris data
            $values_list = [];
            while ($row = $rows->fetch_row()) {
                $vals = [];
                foreach ($row as $val) {
                    if ($val === null) {
                        $vals[] = 'NULL';
                    } else {
                        $vals[] = "'" . $conn2->real_escape_string($val) . "'";
                    }
                }
                $values_list[] = '(' . implode(', ', $vals) . ')';

                // Flush tiap 500 baris
                if (count($values_list) >= 500) {
                    $output .= "INSERT INTO `$table` ($fields_str) VALUES\n" . implode(",\n", $values_list) . ";\n";
                    echo $output;
                    $output = '';
                    $values_list = [];
                }
            }
            if (!empty($values_list)) {
                $output .= "INSERT INTO `$table` ($fields_str) VALUES\n" . implode(",\n", $values_list) . ";\n";
            }
            $output .= "\n";
        }
    }

    $output .= "COMMIT;\n";
    $output .= "-- ============================================================\n";
    $output .= "-- END OF BACKUP\n";
    $output .= "-- ============================================================\n";

    echo $output;
    $conn2->close();
    exit;
}

// ============================================================
// RESTORE DATABASE - Upload SQL
// ============================================================
$restore_msg = '';
$restore_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['sql_file'])) {
    $file = $_FILES['sql_file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $restore_msg = '❌ Gagal upload file. Error code: ' . $file['error'];
        $restore_type = 'error';
    } elseif (pathinfo($file['name'], PATHINFO_EXTENSION) !== 'sql') {
        $restore_msg = '❌ File harus berformat .SQL!';
        $restore_type = 'error';
    } elseif ($file['size'] > 50 * 1024 * 1024) {
        $restore_msg = '❌ File terlalu besar! Maksimal 50MB.';
        $restore_type = 'error';
    } else {
        $sql_content = file_get_contents($file['tmp_name']);

        if (empty($sql_content)) {
            $restore_msg = '❌ File SQL kosong!';
            $restore_type = 'error';
        } else {
            // Split SQL per statement
            $conn->query("SET FOREIGN_KEY_CHECKS = 0");
            $conn->query("SET SQL_MODE = ''");

            // Pisah query dengan delimiter ;
            $statements = array_filter(
                array_map('trim', explode(";\n", $sql_content)),
                fn($s) => !empty($s) && !preg_match('/^--/', $s) && !preg_match('/^\/\*/', $s)
            );

            $success = 0;
            $errors = [];

            foreach ($statements as $stmt) {
                $stmt = trim($stmt);
                if (empty($stmt)) continue;
                // Skip comment-only lines
                if (preg_match('/^(--|\/\*|SET NAMES|START TRANSACTION|COMMIT)/', $stmt)) {
                    $conn->query($stmt);
                    continue;
                }
                if ($conn->query($stmt) === false) {
                    if (!empty($conn->error)) {
                        $errors[] = substr($stmt, 0, 80) . '... → ' . $conn->error;
                    }
                } else {
                    $success++;
                }
            }

            $conn->query("SET FOREIGN_KEY_CHECKS = 1");

            if (count($errors) === 0) {
                $restore_msg = "✅ Database berhasil direstore! ($success query dijalankan)";
                $restore_type = 'success';
            } else {
                $restore_msg = "⚠️ Selesai dengan " . count($errors) . " error dari $success query sukses.<br><small>" . implode('<br>', array_slice($errors, 0, 5)) . "</small>";
                $restore_type = 'warning';
            }
        }
    }
}

// Hitung jumlah tabel & ukuran DB saat ini
$db_stats = $conn->query("SELECT 
    COUNT(*) as total_tabel,
    ROUND(SUM(data_length + index_length) / 1024, 1) as ukuran_kb
    FROM information_schema.tables 
    WHERE table_schema = '" . DB_NAME . "'")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup & Restore Database</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .backup-container { max-width: 720px; margin: 30px auto; padding: 0 20px; }
        .backup-card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 24px rgba(0,0,0,.08);
            padding: 30px;
            margin-bottom: 22px;
        }
        .backup-card h2 { margin: 0 0 6px; font-size: 1.2rem; color: #1e293b; }
        .backup-card p  { margin: 0 0 20px; color: #64748b; font-size: .9rem; }
        .card-icon {
            width: 52px; height: 52px; border-radius: 12px;
            display:flex; align-items:center; justify-content:center;
            font-size: 1.4rem; margin-bottom: 16px;
        }
        .icon-blue  { background: #eff6ff; color: #2563eb; }
        .icon-green { background: #f0fdf4; color: #16a34a; }
        .icon-red   { background: #fef2f2; color: #dc2626; }

        .btn-backup {
            display: inline-flex; align-items: center; gap: 10px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff; border: none; padding: 13px 28px;
            border-radius: 10px; font-size: 1rem; font-weight: 600;
            cursor: pointer; text-decoration: none;
            transition: all .2s; box-shadow: 0 4px 12px rgba(37,99,235,.3);
        }
        .btn-backup:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(37,99,235,.4); }

        .btn-restore {
            display: inline-flex; align-items: center; gap: 10px;
            background: linear-gradient(135deg, #16a34a, #15803d);
            color: #fff; border: none; padding: 13px 28px;
            border-radius: 10px; font-size: 1rem; font-weight: 600;
            cursor: pointer; transition: all .2s;
            box-shadow: 0 4px 12px rgba(22,163,74,.3);
        }
        .btn-restore:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(22,163,74,.4); }

        .file-drop {
            border: 2px dashed #cbd5e1; border-radius: 10px;
            padding: 32px; text-align: center; cursor: pointer;
            transition: all .2s; margin-bottom: 16px; background: #f8fafc;
        }
        .file-drop:hover, .file-drop.drag { border-color: #16a34a; background: #f0fdf4; }
        .file-drop i { font-size: 2rem; color: #94a3b8; margin-bottom: 8px; display: block; }
        .file-drop span { color: #64748b; font-size: .9rem; }
        .file-drop strong { color: #16a34a; }
        #sql_file { display: none; }
        #file-name { margin-top: 8px; font-size: .85rem; color: #16a34a; font-weight: 600; }

        .alert { padding: 14px 18px; border-radius: 10px; margin-bottom: 18px; font-size: .9rem; }
        .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; }
        .alert-error   { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }
        .alert-warning  { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; }

        .db-stats {
            display: flex; gap: 16px; margin-bottom: 22px;
        }
        .stat-box {
            flex: 1; background: #f8fafc; border-radius: 10px;
            padding: 14px 18px; border: 1px solid #e2e8f0;
        }
        .stat-box .label { font-size: .75rem; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em; }
        .stat-box .value { font-size: 1.4rem; font-weight: 700; color: #1e293b; margin-top: 2px; }

        .warning-box {
            background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px;
            padding: 12px 16px; margin-bottom: 18px; font-size: .85rem; color: #92400e;
        }
        .back-btn {
            display: inline-flex; align-items: center; gap: 6px;
            color: #64748b; text-decoration: none; font-size: .9rem; margin-bottom: 20px;
        }
        .back-btn:hover { color: #1e293b; }

        .confirm-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,.5); z-index: 9999;
            align-items: center; justify-content: center;
        }
        .confirm-overlay.show { display: flex; }
        .confirm-box {
            background: #fff; border-radius: 16px;
            padding: 32px; max-width: 420px; width: 90%; text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,.2);
        }
        .confirm-box .icon-warn { font-size: 3rem; color: #f59e0b; margin-bottom: 12px; }
        .confirm-box h3 { margin: 0 0 8px; color: #1e293b; }
        .confirm-box p  { color: #64748b; font-size: .9rem; margin-bottom: 24px; }
        .confirm-btns { display: flex; gap: 12px; justify-content: center; }
        .btn-cancel {
            padding: 10px 24px; border-radius: 8px; border: 1px solid #e2e8f0;
            background: #fff; cursor: pointer; font-size: .95rem; color: #64748b;
        }
        .btn-confirm {
            padding: 10px 24px; border-radius: 8px; border: none;
            background: #dc2626; color: #fff; cursor: pointer; font-size: .95rem; font-weight: 600;
        }
        #loadingRestore {
            display: none; text-align: center; padding: 20px;
            color: #16a34a; font-weight: 600;
        }
        #loadingRestore i { animation: spin 1s linear infinite; margin-right: 8px; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body style="background:#f1f5f9; min-height:100vh; padding: 20px;">

<!-- Confirm Overlay -->
<div class="confirm-overlay" id="confirmOverlay">
    <div class="confirm-box">
        <div class="icon-warn"><i class="fas fa-exclamation-triangle"></i></div>
        <h3>Yakin Restore Database?</h3>
        <p>Proses ini akan <strong>menghapus semua data lama</strong> dan menggantinya dengan data dari file SQL yang Anda pilih. Pastikan file SQL benar!</p>
        <div class="confirm-btns">
            <button class="btn-cancel" onclick="closeConfirm()">Batal</button>
            <button class="btn-confirm" onclick="doRestore()">Ya, Restore Sekarang</button>
        </div>
    </div>
</div>

<div class="backup-container">
    <a href="<?= BASE_URL ?>dashboard.php" class="back-btn">
        <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
    </a>

    <h1 style="font-size:1.5rem; color:#1e293b; margin:0 0 4px;">
        <i class="fas fa-database" style="color:#2563eb"></i> Backup & Restore Database
    </h1>
    <p style="color:#64748b; margin:0 0 24px; font-size:.9rem;">
        Kelola cadangan database sistem absensi Anda
    </p>

    <!-- Info DB -->
    <div class="db-stats">
        <div class="stat-box">
            <div class="label">Database</div>
            <div class="value" style="font-size:1rem;"><?= DB_NAME ?></div>
        </div>
        <div class="stat-box">
            <div class="label">Jumlah Tabel</div>
            <div class="value"><?= $db_stats['total_tabel'] ?></div>
        </div>
        <div class="stat-box">
            <div class="label">Ukuran DB</div>
            <div class="value"><?= $db_stats['ukuran_kb'] ?> KB</div>
        </div>
        <div class="stat-box">
            <div class="label">Waktu</div>
            <div class="value" style="font-size:.95rem;"><?= date('d/m/Y H:i') ?></div>
        </div>
    </div>

    <!-- Alert restore -->
    <?php if ($restore_msg): ?>
        <div class="alert alert-<?= $restore_type ?>">
            <?= $restore_msg ?>
        </div>
    <?php endif; ?>

    <!-- CARD BACKUP -->
    <div class="backup-card">
        <div class="card-icon icon-blue"><i class="fas fa-download"></i></div>
        <h2>⬇️ Backup Database</h2>
        <p>Download seluruh database sebagai file .SQL. Simpan file ini sebagai cadangan. Kapanpun dibutuhkan, bisa direstore kembali.</p>
        <a href="backup_db.php?action=download" class="btn-backup">
            <i class="fas fa-download"></i> Download Backup SQL Sekarang
        </a>
    </div>

    <!-- CARD RESTORE -->
    <div class="backup-card">
        <div class="card-icon icon-green"><i class="fas fa-upload"></i></div>
        <h2>⬆️ Restore Database</h2>
        <p>Upload file .SQL untuk memulihkan database. Cocok digunakan saat database rusak atau ingin pindah ke versi baru.</p>

        <div class="warning-box">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Peringatan:</strong> Restore akan <strong>menghapus data saat ini</strong> dan menggantinya dengan isi file SQL. Pastikan sudah backup dulu!
        </div>

        <form id="restoreForm" method="POST" enctype="multipart/form-data">
            <div class="file-drop" id="fileDrop" onclick="document.getElementById('sql_file').click()">
                <i class="fas fa-file-code"></i>
                <span>Klik untuk pilih file, atau <strong>drag & drop</strong> di sini</span><br>
                <span style="font-size:.8rem; color:#94a3b8;">Format: .SQL | Maks: 50MB</span>
                <div id="file-name"></div>
            </div>
            <input type="file" name="sql_file" id="sql_file" accept=".sql" onchange="fileSelected(this)">

            <div id="loadingRestore">
                <i class="fas fa-spinner"></i> Sedang merestore database, harap tunggu...
            </div>

            <button type="button" class="btn-restore" id="btnRestore" style="display:none" onclick="showConfirm()">
                <i class="fas fa-upload"></i> Restore Database Sekarang
            </button>
        </form>
    </div>

    <!-- Cara pakai -->
    <div class="backup-card" style="background:#f8fafc; box-shadow:none; border:1px solid #e2e8f0;">
        <h2 style="font-size:1rem;">📖 Cara Penggunaan</h2>
        <ol style="color:#475569; font-size:.88rem; line-height:1.9; margin:10px 0 0 16px; padding:0;">
            <li><strong>Backup rutin</strong> — klik "Download Backup" minimal 1x seminggu</li>
            <li><strong>Simpan file SQL</strong> di tempat aman (Google Drive / flashdisk)</li>
            <li><strong>Kalau website rusak</strong> — buka halaman ini, upload file SQL backup</li>
            <li><strong>Setelah restore</strong> — cek dashboard untuk memastikan data kembali normal</li>
        </ol>
    </div>
</div>

<script>
// Drag & Drop
const drop = document.getElementById('fileDrop');
drop.addEventListener('dragover', e => { e.preventDefault(); drop.classList.add('drag'); });
drop.addEventListener('dragleave', () => drop.classList.remove('drag'));
drop.addEventListener('drop', e => {
    e.preventDefault();
    drop.classList.remove('drag');
    const file = e.dataTransfer.files[0];
    if (file) {
        document.getElementById('sql_file').files = e.dataTransfer.files;
        fileSelected({ files: [file] });
    }
});

function fileSelected(input) {
    const file = input.files[0];
    if (file) {
        document.getElementById('file-name').textContent = '📄 ' + file.name + ' (' + (file.size/1024).toFixed(1) + ' KB)';
        document.getElementById('btnRestore').style.display = 'inline-flex';
    }
}

function showConfirm() {
    document.getElementById('confirmOverlay').classList.add('show');
}
function closeConfirm() {
    document.getElementById('confirmOverlay').classList.remove('show');
}
function doRestore() {
    closeConfirm();
    document.getElementById('btnRestore').style.display = 'none';
    document.getElementById('loadingRestore').style.display = 'block';
    document.getElementById('restoreForm').submit();
}
</script>
</body>
</html>
