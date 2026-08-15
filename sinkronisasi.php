<?php
require_once 'includes/config.php';
require_once 'includes/sync_config.php';
cek_login();

// Pastikan tabel sync_log ada
$conn->query("CREATE TABLE IF NOT EXISTS sync_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipe ENUM('kelas','siswa','kelas_siswa','absensi_push','absensi_pull') NOT NULL,
    total_baru INT DEFAULT 0,
    total_update INT DEFAULT 0,
    total_skip INT DEFAULT 0,
    total_nonaktif INT DEFAULT 0,
    detail TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Tambah enum value kelas_siswa jika belum ada
$conn->query("ALTER TABLE sync_log MODIFY COLUMN tipe ENUM('kelas','siswa','kelas_siswa','absensi_push','absensi_pull') NOT NULL");

// Pastikan kolom ext_id ada di tabel kelas dan siswa
$conn->query("ALTER TABLE kelas ADD COLUMN IF NOT EXISTS ext_id VARCHAR(36) DEFAULT NULL");
$conn->query("ALTER TABLE siswa ADD COLUMN IF NOT EXISTS ext_id VARCHAR(36) DEFAULT NULL");

// Ambil log terakhir
$logs = $conn->query("SELECT * FROM sync_log ORDER BY created_at DESC LIMIT 20")->fetch_all(MYSQLI_ASSOC);

// Hitung data lokal
$total_siswa_lokal = $conn->query("SELECT COUNT(*) c FROM siswa WHERE aktif=1")->fetch_assoc()['c'];
$total_kelas_lokal = $conn->query("SELECT COUNT(*) c FROM kelas")->fetch_assoc()['c'];
$total_absensi_hari_ini = $conn->query("SELECT COUNT(*) c FROM absensi WHERE tanggal = CURDATE()")->fetch_assoc()['c'];
$total_siswa_synced = $conn->query("SELECT COUNT(*) c FROM siswa WHERE ext_id IS NOT NULL")->fetch_assoc()['c'];

// Ambil waktu sync terakhir
$last_sync_kelas_siswa = sync_get_last_sync($conn, 'kelas_siswa');

include 'includes/header.php';
?>

<div class="main-content">
    <div class="page-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem">
        <div>
            <div class="page-title"><i class="fas fa-sync-alt" style="color:#10b981"></i> Sinkronisasi Data</div>
            <div class="page-subtitle">Sinkronkan data dari <strong>MandaApp</strong> (mandualotim.sch.id) via Integration API</div>
        </div>
        <div>
            <button class="btn btn-sm" onclick="cekKoneksi()" id="btnPing" style="background:linear-gradient(135deg,#10b981,#059669);color:#fff;border:none;padding:8px 16px;border-radius:8px;cursor:pointer;font-size:.85rem">
                <i class="fas fa-wifi"></i> Cek Koneksi API
            </button>
        </div>
    </div>

    <!-- Status Koneksi -->
    <div id="connStatus" style="display:none;margin-bottom:1rem"></div>

    <!-- Overview Cards -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-bottom:1.5rem">
        <div style="background:var(--card-bg);border-radius:12px;padding:1.2rem;border:1px solid rgba(255,255,255,.06)">
            <div style="display:flex;align-items:center;gap:.75rem">
                <div style="width:40px;height:40px;border-radius:10px;background:rgba(99,102,241,.15);display:flex;align-items:center;justify-content:center">
                    <i class="fas fa-school" style="color:#818cf8;font-size:1.1rem"></i>
                </div>
                <div>
                    <div style="font-size:1.5rem;font-weight:700;color:#818cf8"><?= $total_kelas_lokal ?></div>
                    <div style="font-size:.75rem;color:var(--text-secondary)">Kelas Lokal</div>
                </div>
            </div>
        </div>
        <div style="background:var(--card-bg);border-radius:12px;padding:1.2rem;border:1px solid rgba(255,255,255,.06)">
            <div style="display:flex;align-items:center;gap:.75rem">
                <div style="width:40px;height:40px;border-radius:10px;background:rgba(34,197,94,.15);display:flex;align-items:center;justify-content:center">
                    <i class="fas fa-users" style="color:#22c55e;font-size:1.1rem"></i>
                </div>
                <div>
                    <div style="font-size:1.5rem;font-weight:700;color:#22c55e"><?= $total_siswa_lokal ?></div>
                    <div style="font-size:.75rem;color:var(--text-secondary)">Siswa Aktif Lokal</div>
                </div>
            </div>
        </div>
        <div style="background:var(--card-bg);border-radius:12px;padding:1.2rem;border:1px solid rgba(255,255,255,.06)">
            <div style="display:flex;align-items:center;gap:.75rem">
                <div style="width:40px;height:40px;border-radius:10px;background:rgba(59,130,246,.15);display:flex;align-items:center;justify-content:center">
                    <i class="fas fa-link" style="color:#3b82f6;font-size:1.1rem"></i>
                </div>
                <div>
                    <div style="font-size:1.5rem;font-weight:700;color:#3b82f6"><?= $total_siswa_synced ?></div>
                    <div style="font-size:.75rem;color:var(--text-secondary)">Siswa Tersinkron</div>
                </div>
            </div>
        </div>
        <div style="background:var(--card-bg);border-radius:12px;padding:1.2rem;border:1px solid rgba(255,255,255,.06)">
            <div style="display:flex;align-items:center;gap:.75rem">
                <div style="width:40px;height:40px;border-radius:10px;background:rgba(245,158,11,.15);display:flex;align-items:center;justify-content:center">
                    <i class="fas fa-clipboard-check" style="color:#f59e0b;font-size:1.1rem"></i>
                </div>
                <div>
                    <div style="font-size:1.5rem;font-weight:700;color:#f59e0b"><?= $total_absensi_hari_ini ?></div>
                    <div style="font-size:.75rem;color:var(--text-secondary)">Absensi Hari Ini</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div style="display:flex;gap:.5rem;margin-bottom:1.5rem;flex-wrap:wrap">
        <button class="tab-btn active" onclick="switchTab('kelassiswa')" id="tabKelassiswa"
            style="padding:10px 20px;border-radius:10px;border:1px solid rgba(255,255,255,.1);cursor:pointer;font-size:.85rem;font-weight:600;transition:all .3s">
            <i class="fas fa-school"></i> Sinkron Kelas & Siswa
        </button>
        <button class="tab-btn" onclick="switchTab('absensi')" id="tabAbsensi"
            style="padding:10px 20px;border-radius:10px;border:1px solid rgba(255,255,255,.1);cursor:pointer;font-size:.85rem;font-weight:600;transition:all .3s">
            <i class="fas fa-clipboard-list"></i> Sinkron Absensi
        </button>
        <button class="tab-btn" onclick="switchTab('log')" id="tabLog"
            style="padding:10px 20px;border-radius:10px;border:1px solid rgba(255,255,255,.1);cursor:pointer;font-size:.85rem;font-weight:600;transition:all .3s">
            <i class="fas fa-history"></i> Riwayat Sync
        </button>
    </div>

    <!-- ===================== TAB: KELAS & SISWA ===================== -->
    <div class="sync-tab" id="panelKelassiswa">
        <div class="card" style="border-radius:14px;overflow:hidden">
            <div style="background:linear-gradient(135deg,#6366f1,#4f46e5);padding:1.2rem 1.5rem">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.75rem">
                    <div>
                        <div style="font-size:1.1rem;font-weight:700;color:#fff"><i class="fas fa-school"></i> <i class="fas fa-users" style="margin-left:4px"></i> Sinkronisasi Kelas & Siswa</div>
                        <div style="font-size:.8rem;color:rgba(255,255,255,.7)">Tarik data kelas dan siswa aktif dari MandaApp dalam satu langkah</div>
                    </div>
                    <div style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap">
                        <select id="syncMode" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);padding:8px 12px;border-radius:8px;font-size:.82rem;cursor:pointer">
                            <option value="auto" style="color:#333">🔄 Auto (Incremental jika pernah sync)</option>
                            <option value="full" style="color:#333">📦 Full Sync (Tarik semua data)</option>
                            <option value="incremental" style="color:#333">⚡ Incremental (Hanya perubahan)</option>
                        </select>
                        <button onclick="syncKelasSiswa()" id="btnSyncKelasSiswa" style="background:#fff;color:#4f46e5;border:none;padding:10px 24px;border-radius:10px;font-weight:700;cursor:pointer;font-size:.9rem;transition:all .2s">
                            <i class="fas fa-download"></i> Sinkronkan Sekarang
                        </button>
                    </div>
                </div>
                <?php if ($last_sync_kelas_siswa): ?>
                <div style="margin-top:.6rem;font-size:.75rem;color:rgba(255,255,255,.6)">
                    <i class="fas fa-clock"></i> Terakhir sync: <?= date('d/m/Y H:i', strtotime($last_sync_kelas_siswa)) ?>
                </div>
                <?php endif; ?>
            </div>
            <div style="padding:1.5rem">
                <div style="background:rgba(99,102,241,.08);border:1px solid rgba(99,102,241,.2);border-radius:10px;padding:.8rem 1rem;margin-bottom:1rem;font-size:.8rem;color:#a5b4fc">
                    <i class="fas fa-info-circle"></i> Endpoint: <code style="background:rgba(99,102,241,.15);padding:2px 6px;border-radius:4px;font-size:.75rem">/v1/classes-students</code> — 
                    Kelas dan siswa diambil dalam satu panggilan API. Mode <strong>Incremental</strong> hanya menarik data yang berubah sejak sync terakhir.
                </div>
                <div id="resultKelasSiswa" style="min-height:60px;display:flex;align-items:center;justify-content:center;color:var(--text-secondary)">
                    <div style="text-align:center">
                        <i class="fas fa-info-circle" style="font-size:2rem;opacity:.3;margin-bottom:.5rem;display:block"></i>
                        Klik tombol di atas untuk memulai sinkronisasi kelas & siswa
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================== TAB: ABSENSI ===================== -->
    <div class="sync-tab" id="panelAbsensi" style="display:none">
        <div class="card" style="border-radius:14px;overflow:hidden">
            <div style="background:linear-gradient(135deg,#f59e0b,#d97706);padding:1.2rem 1.5rem">
                <div style="font-size:1.1rem;font-weight:700;color:#fff"><i class="fas fa-clipboard-list"></i> Sinkronisasi Absensi</div>
                <div style="font-size:.8rem;color:rgba(255,255,255,.7)">Tarik data presensi dari MandaApp berdasarkan tanggal</div>
            </div>
            <div style="padding:1.5rem">
                <div style="display:flex;gap:1rem;align-items:end;flex-wrap:wrap;margin-bottom:1.5rem">
                    <div>
                        <label style="font-size:.8rem;color:var(--text-secondary);display:block;margin-bottom:4px">Tarik data sejak tanggal</label>
                        <input type="date" id="syncTanggal" value="<?= date('Y-m-d') ?>" 
                            style="background:var(--input-bg);border:1px solid rgba(255,255,255,.1);color:#fff;padding:10px 14px;border-radius:8px;font-size:.9rem">
                    </div>
                    <button onclick="syncAbsensiPull()" id="btnPull" style="background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;border:none;padding:10px 20px;border-radius:10px;font-weight:600;cursor:pointer;font-size:.85rem">
                        <i class="fas fa-download"></i> Tarik dari MandaApp
                    </button>
                    <button disabled style="background:rgba(100,116,139,.3);color:rgba(255,255,255,.4);border:none;padding:10px 20px;border-radius:10px;font-weight:600;font-size:.85rem;cursor:not-allowed" title="Integration API belum mendukung push">
                        <i class="fas fa-upload"></i> Kirim ke MandaApp <span style="font-size:.7rem;background:rgba(245,158,11,.2);color:#fbbf24;padding:2px 6px;border-radius:4px;margin-left:4px">Soon</span>
                    </button>
                </div>
                <div style="background:rgba(59,130,246,.08);border:1px solid rgba(59,130,246,.2);border-radius:10px;padding:.8rem 1rem;margin-bottom:1rem;font-size:.8rem;color:#93c5fd">
                    <i class="fas fa-info-circle"></i> Endpoint: <code style="background:rgba(59,130,246,.15);padding:2px 6px;border-radius:4px;font-size:.75rem">/v1/attendances</code> — 
                    Data presensi yang di-update setelah tanggal yang dipilih akan ditarik. Siswa dicocokkan via ext_id atau NIS.
                </div>
                <div id="resultAbsensi" style="min-height:60px;display:flex;align-items:center;justify-content:center;color:var(--text-secondary)">
                    <div style="text-align:center">
                        <i class="fas fa-info-circle" style="font-size:2rem;opacity:.3;margin-bottom:.5rem;display:block"></i>
                        Pilih tanggal lalu klik Tarik dari MandaApp
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================== TAB: LOG ===================== -->
    <div class="sync-tab" id="panelLog" style="display:none">
        <div class="card" style="border-radius:14px;overflow:hidden">
            <div style="background:linear-gradient(135deg,#64748b,#475569);padding:1.2rem 1.5rem">
                <div style="font-size:1.1rem;font-weight:700;color:#fff"><i class="fas fa-history"></i> Riwayat Sinkronisasi</div>
                <div style="font-size:.8rem;color:rgba(255,255,255,.7)">20 log sinkronisasi terakhir</div>
            </div>
            <div style="padding:1rem;overflow-x:auto">
                <?php if (empty($logs)): ?>
                    <div style="text-align:center;padding:2rem;color:var(--text-secondary)">
                        <i class="fas fa-inbox" style="font-size:2.5rem;opacity:.3;display:block;margin-bottom:.5rem"></i>
                        Belum ada riwayat sinkronisasi
                    </div>
                <?php else: ?>
                    <table style="width:100%;border-collapse:collapse;font-size:.8rem">
                        <thead>
                            <tr style="border-bottom:1px solid rgba(255,255,255,.1)">
                                <th style="padding:10px;text-align:left;color:var(--text-secondary)">Waktu</th>
                                <th style="padding:10px;text-align:left;color:var(--text-secondary)">Tipe</th>
                                <th style="padding:10px;text-align:center;color:#22c55e">Baru</th>
                                <th style="padding:10px;text-align:center;color:#3b82f6">Update</th>
                                <th style="padding:10px;text-align:center;color:#94a3b8">Skip</th>
                                <th style="padding:10px;text-align:center;color:#ef4444">Nonaktif</th>
                                <th style="padding:10px;text-align:left;color:var(--text-secondary)">Detail</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr style="border-bottom:1px solid rgba(255,255,255,.05)">
                                <td style="padding:8px 10px;white-space:nowrap"><?= date('d/m/Y H:i', strtotime($log['created_at'])) ?></td>
                                <td style="padding:8px 10px">
                                    <?php
                                    $tipeMap = [
                                        'kelas' => ['🏫 Kelas', '#818cf8'],
                                        'siswa' => ['👨‍🎓 Siswa', '#22c55e'],
                                        'kelas_siswa' => ['🔄 Kelas & Siswa', '#8b5cf6'],
                                        'absensi_push' => ['📤 Push Absensi', '#f59e0b'],
                                        'absensi_pull' => ['📥 Pull Absensi', '#3b82f6'],
                                    ];
                                    $t = $tipeMap[$log['tipe']] ?? ['❓ Unknown', '#888'];
                                    ?>
                                    <span style="background:<?= $t[1] ?>22;color:<?= $t[1] ?>;padding:3px 10px;border-radius:6px;font-size:.75rem;font-weight:600"><?= $t[0] ?></span>
                                </td>
                                <td style="padding:8px 10px;text-align:center;color:#22c55e;font-weight:600"><?= $log['total_baru'] ?></td>
                                <td style="padding:8px 10px;text-align:center;color:#3b82f6;font-weight:600"><?= $log['total_update'] ?></td>
                                <td style="padding:8px 10px;text-align:center;color:#94a3b8"><?= $log['total_skip'] ?></td>
                                <td style="padding:8px 10px;text-align:center;color:#ef4444"><?= $log['total_nonaktif'] ?></td>
                                <td style="padding:8px 10px;font-size:.75rem;color:var(--text-secondary);max-width:250px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= htmlspecialchars($log['detail']) ?>"><?= htmlspecialchars($log['detail']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.tab-btn { background:var(--card-bg); color:var(--text-secondary); }
.tab-btn.active { background:linear-gradient(135deg,#10b981,#059669); color:#fff; border-color:#10b981; }
.tab-btn:hover:not(.active) { background:rgba(16,185,129,.15); color:#6ee7b7; }

.sync-result-card {
    background:rgba(16,185,129,.08); border:1px solid rgba(16,185,129,.2);
    border-radius:12px; padding:1.2rem; margin-top:.5rem;
}
.sync-stat { display:inline-flex; align-items:center; gap:6px; padding:6px 14px; border-radius:8px; font-size:.85rem; font-weight:600; margin:4px; }

@keyframes syncSpin { from { transform:rotate(0deg); } to { transform:rotate(360deg); } }
.syncing i.fa-sync-alt, .syncing i.fa-spinner { animation: syncSpin 1s linear infinite; }
</style>

<script>
function switchTab(tab) {
    document.querySelectorAll('.sync-tab').forEach(p => p.style.display = 'none');
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    const panelId = 'panel' + tab.charAt(0).toUpperCase() + tab.slice(1);
    document.getElementById(panelId).style.display = 'block';
    const tabId = 'tab' + tab.charAt(0).toUpperCase() + tab.slice(1);
    document.getElementById(tabId).classList.add('active');
}

function setLoading(btn, loading) {
    if (loading) {
        btn.disabled = true;
        btn.dataset.original = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner" style="animation:syncSpin 1s linear infinite"></i> Memproses...';
        btn.style.opacity = '.7';
    } else {
        btn.disabled = false;
        btn.innerHTML = btn.dataset.original;
        btn.style.opacity = '1';
    }
}

function resultKelasSiswaHTML(data) {
    // Bagian kelas
    let kelasStats = `
        <div style="margin-bottom:1rem">
            <div style="font-size:.85rem;font-weight:700;color:#818cf8;margin-bottom:.5rem"><i class="fas fa-school"></i> Kelas</div>
            <span class="sync-stat" style="background:rgba(34,197,94,.15);color:#22c55e"><i class="fas fa-plus-circle"></i> ${data.kelas_baru} Baru</span>
            <span class="sync-stat" style="background:rgba(59,130,246,.15);color:#3b82f6"><i class="fas fa-pen"></i> ${data.kelas_update} Update</span>
            <span class="sync-stat" style="background:rgba(148,163,184,.15);color:#94a3b8"><i class="fas fa-forward"></i> ${data.kelas_skip} Skip</span>
            <div style="font-size:.78rem;color:var(--text-secondary);margin-top:.3rem">Total dari MandaApp: <strong style="color:#fff">${data.kelas_total_api}</strong> kelas</div>
        </div>`;
    
    // Bagian siswa
    let siswaStats = `
        <div style="margin-bottom:.5rem">
            <div style="font-size:.85rem;font-weight:700;color:#22c55e;margin-bottom:.5rem"><i class="fas fa-users"></i> Siswa</div>
            <span class="sync-stat" style="background:rgba(34,197,94,.15);color:#22c55e"><i class="fas fa-plus-circle"></i> ${data.siswa_baru} Baru</span>
            <span class="sync-stat" style="background:rgba(59,130,246,.15);color:#3b82f6"><i class="fas fa-pen"></i> ${data.siswa_update} Update</span>
            <span class="sync-stat" style="background:rgba(148,163,184,.15);color:#94a3b8"><i class="fas fa-forward"></i> ${data.siswa_skip} Skip</span>`;
    if (data.siswa_nonaktif > 0) {
        siswaStats += `<span class="sync-stat" style="background:rgba(239,68,68,.15);color:#ef4444"><i class="fas fa-user-minus"></i> ${data.siswa_nonaktif} Nonaktif</span>`;
    }
    siswaStats += `<div style="font-size:.78rem;color:var(--text-secondary);margin-top:.3rem">Total dari MandaApp: <strong style="color:#fff">${data.siswa_total_api}</strong> siswa</div>
        </div>`;

    // Mode info
    let modeLabel = data.mode === 'full' ? '📦 Full Sync' : '⚡ Incremental';
    let modeInfo = `<div style="font-size:.78rem;color:#a5b4fc;margin-top:.5rem"><i class="fas fa-info-circle"></i> Mode: ${modeLabel}`;
    if (data.last_sync_used) {
        modeInfo += ` (sejak ${data.last_sync_used})`;
    }
    modeInfo += `</div>`;

    // Kelas badges
    let kelasHtml = '';
    if (data.kelas_names && data.kelas_names.length > 0) {
        const badges = data.kelas_names.map(k => 
            `<span style="display:inline-block;background:rgba(99,102,241,.15);color:#818cf8;padding:3px 10px;border-radius:6px;font-size:.72rem;font-weight:600;margin:2px">${k}</span>`
        ).join('');
        kelasHtml = `<div style="margin-top:.75rem;background:rgba(99,102,241,.05);border:1px solid rgba(99,102,241,.15);border-radius:8px;padding:.6rem .8rem">
            <div style="font-size:.75rem;color:#a5b4fc;margin-bottom:.4rem"><i class="fas fa-school"></i> <strong>Kelas tersinkron (${data.kelas_names.length}):</strong></div>
            <div>${badges}</div>
        </div>`;
    }

    // Errors
    let errorsHtml = '';
    if (data.errors && data.errors.length > 0) {
        errorsHtml = `<div style="margin-top:.75rem;background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);border-radius:8px;padding:.6rem .8rem;font-size:.75rem;color:#fca5a5;max-height:120px;overflow-y:auto">
            <strong>⚠️ Catatan:</strong><br>${data.errors.slice(0, 10).join('<br>')}
            ${data.errors.length > 10 ? '<br>...dan ' + (data.errors.length - 10) + ' lainnya' : ''}
        </div>`;
    }

    return `<div class="sync-result-card">
        <div style="font-size:1rem;font-weight:700;color:#10b981;margin-bottom:.75rem">
            <i class="fas fa-check-circle"></i> ${data.message}
        </div>
        ${kelasStats}
        <div style="border-top:1px solid rgba(255,255,255,.06);margin:.75rem 0"></div>
        ${siswaStats}
        ${modeInfo}
        ${kelasHtml}
        ${errorsHtml}
    </div>`;
}

function resultAbsensiHTML(data) {
    let stats = '';
    if (data.baru !== undefined) stats += `<span class="sync-stat" style="background:rgba(34,197,94,.15);color:#22c55e"><i class="fas fa-plus-circle"></i> ${data.baru} Baru</span>`;
    if (data.update !== undefined) stats += `<span class="sync-stat" style="background:rgba(59,130,246,.15);color:#3b82f6"><i class="fas fa-pen"></i> ${data.update} Update</span>`;
    if (data.skip !== undefined) stats += `<span class="sync-stat" style="background:rgba(148,163,184,.15);color:#94a3b8"><i class="fas fa-forward"></i> ${data.skip} Skip</span>`;

    let total = data.total_mandaapp !== undefined ? `<div style="font-size:.8rem;color:var(--text-secondary);margin-top:.5rem">Total dari MandaApp: <strong style="color:#fff">${data.total_mandaapp}</strong></div>` : '';

    let errorsHtml = '';
    if (data.errors && data.errors.length > 0) {
        errorsHtml = `<div style="margin-top:.75rem;background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);border-radius:8px;padding:.6rem .8rem;font-size:.75rem;color:#fca5a5;max-height:120px;overflow-y:auto">
            <strong>⚠️ Catatan:</strong><br>${data.errors.slice(0, 10).join('<br>')}
        </div>`;
    }

    return `<div class="sync-result-card">
        <div style="font-size:1rem;font-weight:700;color:#10b981;margin-bottom:.5rem">
            <i class="fas fa-check-circle"></i> ${data.message}
        </div>
        <div>${stats}</div>
        ${total}
        ${errorsHtml}
    </div>`;
}

function errorHTML(msg) {
    return `<div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);border-radius:12px;padding:1.2rem;color:#fca5a5">
        <i class="fas fa-exclamation-triangle" style="color:#ef4444"></i> <strong>Gagal:</strong> ${msg}
    </div>`;
}

// ═══════════════════════════════════════════════
// CEK KONEKSI
// ═══════════════════════════════════════════════
async function cekKoneksi() {
    const btn = document.getElementById('btnPing');
    setLoading(btn, true);
    try {
        const res = await fetch('ajax/sync_ping.php');
        const data = await res.json();
        const el = document.getElementById('connStatus');
        el.style.display = 'block';
        if (data.success) {
            el.innerHTML = `<div style="background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.3);border-radius:10px;padding:.8rem 1rem;color:#6ee7b7;font-size:.85rem">
                <i class="fas fa-check-circle" style="color:#10b981"></i> <strong>Koneksi berhasil!</strong> MandaApp Integration API aktif.
                <span style="color:rgba(255,255,255,.5);margin-left:.5rem">Timestamp: ${data.timestamp || '-'}</span>
            </div>`;
        } else {
            el.innerHTML = errorHTML(data.message || 'Koneksi gagal');
        }
    } catch (e) {
        document.getElementById('connStatus').style.display = 'block';
        document.getElementById('connStatus').innerHTML = errorHTML(e.message);
    }
    setLoading(btn, false);
}

// ═══════════════════════════════════════════════
// SINKRON KELAS & SISWA (GABUNGAN)
// ═══════════════════════════════════════════════
async function syncKelasSiswa() {
    const btn = document.getElementById('btnSyncKelasSiswa');
    const mode = document.getElementById('syncMode').value;
    setLoading(btn, true);
    try {
        const res = await fetch('ajax/sync_classes_students.php?mode=' + mode);
        const data = await res.json();
        document.getElementById('resultKelasSiswa').innerHTML = data.success 
            ? resultKelasSiswaHTML(data) 
            : errorHTML(data.message);
    } catch (e) {
        document.getElementById('resultKelasSiswa').innerHTML = errorHTML(e.message);
    }
    setLoading(btn, false);
}

// ═══════════════════════════════════════════════
// SINKRON ABSENSI
// ═══════════════════════════════════════════════
async function syncAbsensiPull() {
    const btn = document.getElementById('btnPull');
    const tanggal = document.getElementById('syncTanggal').value;
    if (!tanggal) { alert('Pilih tanggal!'); return; }
    setLoading(btn, true);
    try {
        const res = await fetch('ajax/sync_absensi.php?action=pull&tanggal=' + tanggal);
        const data = await res.json();
        document.getElementById('resultAbsensi').innerHTML = data.success 
            ? resultAbsensiHTML(data) 
            : errorHTML(data.message);
    } catch (e) {
        document.getElementById('resultAbsensi').innerHTML = errorHTML(e.message);
    }
    setLoading(btn, false);
}
</script>

<?php include 'includes/footer.php'; ?>
