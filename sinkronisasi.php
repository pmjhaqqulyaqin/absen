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

$conn->query("ALTER TABLE sync_log MODIFY COLUMN tipe ENUM('kelas','siswa','kelas_siswa','absensi_push','absensi_pull') NOT NULL");
$conn->query("ALTER TABLE kelas ADD COLUMN IF NOT EXISTS ext_id VARCHAR(36) DEFAULT NULL");
$conn->query("ALTER TABLE siswa ADD COLUMN IF NOT EXISTS ext_id VARCHAR(36) DEFAULT NULL");

// Ambil log terakhir
$logs = $conn->query("SELECT * FROM sync_log ORDER BY created_at DESC LIMIT 20")->fetch_all(MYSQLI_ASSOC);

// Hitung data lokal
$total_siswa_lokal = $conn->query("SELECT COUNT(*) c FROM siswa WHERE aktif=1")->fetch_assoc()['c'];
$total_kelas_lokal = $conn->query("SELECT COUNT(*) c FROM kelas")->fetch_assoc()['c'];
$total_absensi_hari_ini = $conn->query("SELECT COUNT(*) c FROM absensi WHERE tanggal = CURDATE()")->fetch_assoc()['c'];
$total_siswa_synced = $conn->query("SELECT COUNT(*) c FROM siswa WHERE ext_id IS NOT NULL")->fetch_assoc()['c'];
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
                        <div style="font-size:.8rem;color:rgba(255,255,255,.7)">Preview data dulu, pilih yang akan disimpan</div>
                    </div>
                    <div style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap">
                        <select id="syncMode" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);padding:8px 12px;border-radius:8px;font-size:.82rem;cursor:pointer">
                            <option value="auto" style="color:#333">🔄 Auto</option>
                            <option value="full" style="color:#333">📦 Full Sync</option>
                            <option value="incremental" style="color:#333">⚡ Incremental</option>
                        </select>
                        <button onclick="fetchPreview()" id="btnSyncKelasSiswa" style="background:#fff;color:#4f46e5;border:none;padding:10px 24px;border-radius:10px;font-weight:700;cursor:pointer;font-size:.9rem;transition:all .2s">
                            <i class="fas fa-search"></i> Sinkronkan Sekarang
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
                    <i class="fas fa-info-circle"></i> Data akan ditampilkan sebagai <strong>preview</strong> terlebih dahulu. Anda bisa memilih item mana yang akan disimpan.
                </div>
                <div id="resultKelasSiswa" style="min-height:60px;display:flex;align-items:center;justify-content:center;color:var(--text-secondary)">
                    <div style="text-align:center">
                        <i class="fas fa-info-circle" style="font-size:2rem;opacity:.3;margin-bottom:.5rem;display:block"></i>
                        Klik tombol di atas untuk menarik data dari MandaApp
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
                    <button disabled style="background:rgba(100,116,139,.3);color:rgba(255,255,255,.4);border:none;padding:10px 20px;border-radius:10px;font-weight:600;font-size:.85rem;cursor:not-allowed">
                        <i class="fas fa-upload"></i> Kirim ke MandaApp <span style="font-size:.7rem;background:rgba(245,158,11,.2);color:#fbbf24;padding:2px 6px;border-radius:4px;margin-left:4px">Soon</span>
                    </button>
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

<!-- ===================== MODAL PREVIEW ===================== -->
<div id="syncModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.7);backdrop-filter:blur(4px);overflow-y:auto;padding:1rem">
    <div style="max-width:960px;margin:1rem auto;background:var(--card-bg);border-radius:16px;border:1px solid rgba(255,255,255,.1);overflow:hidden;animation:modalSlide .3s ease">
        <!-- Modal Header -->
        <div style="background:linear-gradient(135deg,#6366f1,#4f46e5);padding:1.2rem 1.5rem;display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;z-index:10">
            <div>
                <div style="font-size:1.1rem;font-weight:700;color:#fff"><i class="fas fa-eye"></i> Preview Sinkronisasi</div>
                <div id="modalSubtitle" style="font-size:.78rem;color:rgba(255,255,255,.7)">Review data sebelum menyimpan</div>
            </div>
            <button onclick="closeModal()" style="background:rgba(255,255,255,.15);border:none;color:#fff;width:36px;height:36px;border-radius:8px;cursor:pointer;font-size:1.1rem">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Toolbar -->
        <div style="padding:.8rem 1.5rem;background:rgba(99,102,241,.05);border-bottom:1px solid rgba(255,255,255,.06);display:flex;gap:.4rem;flex-wrap:wrap;align-items:center">
            <button onclick="selectByStatus('baru')" class="toolbar-btn" style="background:rgba(34,197,94,.15);color:#22c55e"><i class="fas fa-check"></i> Centang Baru</button>
            <button onclick="selectByStatus('update')" class="toolbar-btn" style="background:rgba(59,130,246,.15);color:#3b82f6"><i class="fas fa-check"></i> Centang Update</button>
            <button onclick="selectAll(true)" class="toolbar-btn" style="background:rgba(255,255,255,.08);color:var(--text-secondary)"><i class="fas fa-check-double"></i> Centang Semua</button>
            <button onclick="selectAll(false)" class="toolbar-btn" style="background:rgba(255,255,255,.08);color:var(--text-secondary)"><i class="fas fa-times"></i> Hapus Centang</button>
            <div style="flex:1"></div>
            <div id="selectionCounter" style="font-size:.8rem;color:#a5b4fc;font-weight:600"></div>
        </div>

        <!-- Modal Body -->
        <div style="padding:1.5rem;max-height:60vh;overflow-y:auto" id="modalBody">
            <!-- Diisi oleh JS -->
        </div>

        <!-- Modal Footer -->
        <div style="padding:1rem 1.5rem;background:rgba(0,0,0,.2);border-top:1px solid rgba(255,255,255,.06);display:flex;justify-content:space-between;align-items:center;position:sticky;bottom:0;z-index:10">
            <button onclick="closeModal()" style="background:rgba(255,255,255,.08);color:var(--text-secondary);border:none;padding:10px 24px;border-radius:10px;cursor:pointer;font-size:.85rem;font-weight:600">
                <i class="fas fa-times"></i> Batal
            </button>
            <button onclick="confirmSync()" id="btnConfirmSync" style="background:linear-gradient(135deg,#10b981,#059669);color:#fff;border:none;padding:10px 28px;border-radius:10px;cursor:pointer;font-size:.9rem;font-weight:700;transition:all .2s">
                <i class="fas fa-save"></i> <span id="btnConfirmText">Simpan yang Dipilih</span>
            </button>
        </div>
    </div>
</div>

<style>
.tab-btn { background:var(--card-bg); color:var(--text-secondary); }
.tab-btn.active { background:linear-gradient(135deg,#10b981,#059669); color:#fff; border-color:#10b981; }
.tab-btn:hover:not(.active) { background:rgba(16,185,129,.15); color:#6ee7b7; }
.sync-result-card { background:rgba(16,185,129,.08); border:1px solid rgba(16,185,129,.2); border-radius:12px; padding:1.2rem; margin-top:.5rem; }
.sync-stat { display:inline-flex; align-items:center; gap:6px; padding:6px 14px; border-radius:8px; font-size:.85rem; font-weight:600; margin:4px; }

@keyframes syncSpin { from { transform:rotate(0deg); } to { transform:rotate(360deg); } }
@keyframes modalSlide { from { opacity:0; transform:translateY(-20px); } to { opacity:1; transform:translateY(0); } }

.toolbar-btn { border:none; padding:6px 12px; border-radius:6px; cursor:pointer; font-size:.75rem; font-weight:600; transition:all .2s; }
.toolbar-btn:hover { filter:brightness(1.2); }

.preview-table { width:100%; border-collapse:collapse; font-size:.8rem; }
.preview-table th { padding:8px 10px; text-align:left; color:var(--text-secondary); border-bottom:1px solid rgba(255,255,255,.1); position:sticky; top:0; background:var(--card-bg); }
.preview-table td { padding:6px 10px; border-bottom:1px solid rgba(255,255,255,.04); }
.preview-table tr:hover { background:rgba(255,255,255,.03); }
.preview-table input[type="checkbox"] { width:16px; height:16px; cursor:pointer; accent-color:#10b981; }

.status-badge { padding:2px 8px; border-radius:5px; font-size:.7rem; font-weight:700; text-transform:uppercase; }
.status-baru { background:rgba(34,197,94,.15); color:#22c55e; }
.status-update { background:rgba(59,130,246,.15); color:#3b82f6; }
.status-sama { background:rgba(148,163,184,.1); color:#64748b; }
.status-nonaktif { background:rgba(239,68,68,.15); color:#ef4444; }

.section-header { font-size:.9rem; font-weight:700; margin:1rem 0 .5rem; padding:.5rem .75rem; border-radius:8px; display:flex; align-items:center; gap:.5rem; }
</style>

<script>
// ═══════════════════════════════════════════════
// DATA STORE
// ═══════════════════════════════════════════════
let previewData = null;

// ═══════════════════════════════════════════════
// TAB SWITCHING
// ═══════════════════════════════════════════════
function switchTab(tab) {
    document.querySelectorAll('.sync-tab').forEach(p => p.style.display = 'none');
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('panel' + tab.charAt(0).toUpperCase() + tab.slice(1)).style.display = 'block';
    document.getElementById('tab' + tab.charAt(0).toUpperCase() + tab.slice(1)).classList.add('active');
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
                <span style="color:rgba(255,255,255,.5);margin-left:.5rem">${data.timestamp || ''}</span>
            </div>`;
        } else {
            el.innerHTML = errorHTML(data.message || 'Koneksi gagal');
        }
    } catch (e) {
        document.getElementById('connStatus').innerHTML = errorHTML(e.message);
        document.getElementById('connStatus').style.display = 'block';
    }
    setLoading(btn, false);
}

// ═══════════════════════════════════════════════
// FETCH PREVIEW (TANPA SIMPAN)
// ═══════════════════════════════════════════════
async function fetchPreview() {
    const btn = document.getElementById('btnSyncKelasSiswa');
    const mode = document.getElementById('syncMode').value;
    setLoading(btn, true);
    try {
        const res = await fetch('ajax/sync_preview.php?mode=' + mode);
        const data = await res.json();
        if (!data.success) {
            document.getElementById('resultKelasSiswa').innerHTML = errorHTML(data.message);
            setLoading(btn, false);
            return;
        }
        previewData = data;
        showModal(data);
    } catch (e) {
        document.getElementById('resultKelasSiswa').innerHTML = errorHTML(e.message);
    }
    setLoading(btn, false);
}

// ═══════════════════════════════════════════════
// MODAL — RENDER PREVIEW
// ═══════════════════════════════════════════════
function showModal(data) {
    const s = data.summary;
    document.getElementById('modalSubtitle').textContent = 
        `${data.classes.length} kelas, ${data.students.length} siswa dari MandaApp` +
        (data.mode === 'incremental' ? ` (incremental)` : ` (full sync)`);

    let html = '';

    // === SECTION: KELAS ===
    html += `<div class="section-header" style="background:rgba(99,102,241,.1);color:#a5b4fc">
        <i class="fas fa-school"></i> Kelas
        <span style="font-size:.72rem;font-weight:400;color:#818cf8;margin-left:auto">
            🟢 ${s.kelas.baru} baru · 🔵 ${s.kelas.update} update · ⚪ ${s.kelas.sama} sama
        </span>
    </div>`;

    if (data.classes.length === 0) {
        html += `<div style="text-align:center;padding:1rem;color:#64748b;font-size:.82rem">Tidak ada data kelas</div>`;
    } else {
        html += `<table class="preview-table"><thead><tr>
            <th style="width:30px"><input type="checkbox" onchange="toggleSection('kelas', this.checked)" checked></th>
            <th>Status</th><th>Nama Kelas (MandaApp)</th><th>Nama Lokal</th>
        </tr></thead><tbody>`;
        data.classes.forEach((c, i) => {
            const checked = c.status !== 'sama' ? 'checked' : '';
            html += `<tr>
                <td><input type="checkbox" class="cb-kelas" data-idx="${i}" ${checked} onchange="updateCounter()"></td>
                <td><span class="status-badge status-${c.status}">${c.status}</span></td>
                <td style="font-weight:600">${esc(c.name)}</td>
                <td style="color:var(--text-secondary)">${c.local_name || '<span style="color:#22c55e;font-style:italic">— baru —</span>'}</td>
            </tr>`;
        });
        html += `</tbody></table>`;
    }

    // === SECTION: SISWA ===
    html += `<div class="section-header" style="background:rgba(34,197,94,.1);color:#6ee7b7;margin-top:1.5rem">
        <i class="fas fa-users"></i> Siswa
        <span style="font-size:.72rem;font-weight:400;color:#22c55e;margin-left:auto">
            🟢 ${s.siswa.baru} baru · 🔵 ${s.siswa.update} update · ⚪ ${s.siswa.sama} sama
        </span>
    </div>`;

    if (data.students.length === 0) {
        html += `<div style="text-align:center;padding:1rem;color:#64748b;font-size:.82rem">Tidak ada data siswa</div>`;
    } else {
        html += `<table class="preview-table"><thead><tr>
            <th style="width:30px"><input type="checkbox" onchange="toggleSection('siswa', this.checked)" checked></th>
            <th>Status</th><th>NIS</th><th>Nama (MandaApp)</th><th>Kelas</th><th>Lokal</th>
        </tr></thead><tbody>`;
        data.students.forEach((s, i) => {
            const checked = s.status !== 'sama' ? 'checked' : '';
            let localInfo = '';
            if (s.local_name) {
                const diffs = [];
                if (s.local_name !== s.name) diffs.push(s.local_name);
                if (s.local_class && s.local_class !== s.class) diffs.push(s.local_class);
                localInfo = diffs.length > 0 ? diffs.join(' · ') : '<span style="color:#64748b">sama</span>';
            } else {
                localInfo = '<span style="color:#22c55e;font-style:italic">— baru —</span>';
            }
            html += `<tr>
                <td><input type="checkbox" class="cb-siswa" data-idx="${i}" ${checked} onchange="updateCounter()"></td>
                <td><span class="status-badge status-${s.status}">${s.status}</span></td>
                <td style="font-family:monospace;font-size:.78rem">${esc(s.nis)}</td>
                <td style="font-weight:600">${esc(s.name)}</td>
                <td><span style="background:rgba(99,102,241,.1);color:#818cf8;padding:2px 8px;border-radius:4px;font-size:.72rem">${esc(s.class)}</span></td>
                <td style="color:var(--text-secondary);font-size:.78rem">${localInfo}</td>
            </tr>`;
        });
        html += `</tbody></table>`;
    }

    // === SECTION: NONAKTIF ===
    if (data.students_nonaktif && data.students_nonaktif.length > 0) {
        html += `<div class="section-header" style="background:rgba(239,68,68,.1);color:#fca5a5;margin-top:1.5rem">
            <i class="fas fa-user-minus"></i> Siswa akan Dinonaktifkan (${data.students_nonaktif.length})
            <span style="font-size:.72rem;font-weight:400;margin-left:auto">Ada di lokal tapi tidak di MandaApp</span>
        </div>`;
        html += `<table class="preview-table"><thead><tr>
            <th style="width:30px"><input type="checkbox" onchange="toggleSection('nonaktif', this.checked)"></th>
            <th>NIS</th><th>Nama</th><th>Kelas</th>
        </tr></thead><tbody>`;
        data.students_nonaktif.forEach((n, i) => {
            html += `<tr>
                <td><input type="checkbox" class="cb-nonaktif" data-idx="${i}" onchange="updateCounter()"></td>
                <td style="font-family:monospace;font-size:.78rem">${esc(n.nis)}</td>
                <td>${esc(n.name)}</td>
                <td>${esc(n.class)}</td>
            </tr>`;
        });
        html += `</tbody></table>`;
    }

    document.getElementById('modalBody').innerHTML = html;
    document.getElementById('syncModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
    updateCounter();
}

function closeModal() {
    document.getElementById('syncModal').style.display = 'none';
    document.body.style.overflow = '';
}

function esc(s) {
    if (!s) return '';
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

// ═══════════════════════════════════════════════
// SELECTION HELPERS
// ═══════════════════════════════════════════════
function toggleSection(section, checked) {
    document.querySelectorAll(`.cb-${section}`).forEach(cb => cb.checked = checked);
    updateCounter();
}

function selectByStatus(status) {
    if (!previewData) return;
    document.querySelectorAll('.cb-kelas').forEach(cb => {
        const c = previewData.classes[cb.dataset.idx];
        if (c.status === status) cb.checked = true;
    });
    document.querySelectorAll('.cb-siswa').forEach(cb => {
        const s = previewData.students[cb.dataset.idx];
        if (s.status === status) cb.checked = true;
    });
    updateCounter();
}

function selectAll(checked) {
    document.querySelectorAll('.cb-kelas, .cb-siswa, .cb-nonaktif').forEach(cb => cb.checked = checked);
    updateCounter();
}

function updateCounter() {
    const kelas = document.querySelectorAll('.cb-kelas:checked').length;
    const siswa = document.querySelectorAll('.cb-siswa:checked').length;
    const nonaktif = document.querySelectorAll('.cb-nonaktif:checked').length;
    const total = kelas + siswa + nonaktif;
    
    let parts = [];
    if (kelas > 0) parts.push(`${kelas} kelas`);
    if (siswa > 0) parts.push(`${siswa} siswa`);
    if (nonaktif > 0) parts.push(`${nonaktif} nonaktif`);
    
    document.getElementById('selectionCounter').textContent = total > 0 ? `✅ ${parts.join(' + ')} dipilih` : 'Belum ada yang dipilih';
    document.getElementById('btnConfirmText').textContent = total > 0 ? `Simpan ${total} Item Terpilih` : 'Tidak ada yang dipilih';
    document.getElementById('btnConfirmSync').disabled = total === 0;
    document.getElementById('btnConfirmSync').style.opacity = total === 0 ? '.5' : '1';
}

// ═══════════════════════════════════════════════
// CONFIRM — SIMPAN YANG DIPILIH
// ═══════════════════════════════════════════════
async function confirmSync() {
    if (!previewData) return;
    
    const btn = document.getElementById('btnConfirmSync');
    setLoading(btn, true);

    // Kumpulkan item terpilih
    const selectedClasses = [];
    document.querySelectorAll('.cb-kelas:checked').forEach(cb => {
        const c = previewData.classes[cb.dataset.idx];
        selectedClasses.push({ ext_id: c.ext_id, name: c.name });
    });

    const selectedStudents = [];
    document.querySelectorAll('.cb-siswa:checked').forEach(cb => {
        const s = previewData.students[cb.dataset.idx];
        selectedStudents.push({ ext_id: s.ext_id, nis: s.nis, name: s.name, class: s.class });
    });

    const nonaktifIds = [];
    document.querySelectorAll('.cb-nonaktif:checked').forEach(cb => {
        const n = previewData.students_nonaktif[cb.dataset.idx];
        nonaktifIds.push(n.id);
    });

    try {
        const res = await fetch('ajax/sync_confirm.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                classes: selectedClasses,
                students: selectedStudents,
                nonaktif_ids: nonaktifIds,
            })
        });
        const data = await res.json();

        closeModal();

        if (data.success) {
            let stats = '';
            if (data.kelas_baru > 0) stats += `<span class="sync-stat" style="background:rgba(34,197,94,.15);color:#22c55e"><i class="fas fa-plus-circle"></i> ${data.kelas_baru} Kelas Baru</span>`;
            if (data.kelas_update > 0) stats += `<span class="sync-stat" style="background:rgba(59,130,246,.15);color:#3b82f6"><i class="fas fa-pen"></i> ${data.kelas_update} Kelas Update</span>`;
            if (data.siswa_baru > 0) stats += `<span class="sync-stat" style="background:rgba(34,197,94,.15);color:#22c55e"><i class="fas fa-user-plus"></i> ${data.siswa_baru} Siswa Baru</span>`;
            if (data.siswa_update > 0) stats += `<span class="sync-stat" style="background:rgba(59,130,246,.15);color:#3b82f6"><i class="fas fa-user-edit"></i> ${data.siswa_update} Siswa Update</span>`;
            if (data.siswa_nonaktif > 0) stats += `<span class="sync-stat" style="background:rgba(239,68,68,.15);color:#ef4444"><i class="fas fa-user-minus"></i> ${data.siswa_nonaktif} Dinonaktifkan</span>`;

            let errorsHtml = '';
            if (data.errors && data.errors.length > 0) {
                errorsHtml = `<div style="margin-top:.75rem;background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);border-radius:8px;padding:.6rem .8rem;font-size:.75rem;color:#fca5a5">
                    <strong>⚠️ Catatan:</strong><br>${data.errors.slice(0, 5).join('<br>')}
                </div>`;
            }

            document.getElementById('resultKelasSiswa').innerHTML = `<div class="sync-result-card">
                <div style="font-size:1rem;font-weight:700;color:#10b981;margin-bottom:.5rem">
                    <i class="fas fa-check-circle"></i> ${data.message}
                </div>
                <div>${stats}</div>
                ${errorsHtml}
            </div>`;
        } else {
            document.getElementById('resultKelasSiswa').innerHTML = errorHTML(data.message);
        }
    } catch (e) {
        closeModal();
        document.getElementById('resultKelasSiswa').innerHTML = errorHTML(e.message);
    }
    setLoading(btn, false);
}

// ═══════════════════════════════════════════════
// ABSENSI PULL
// ═══════════════════════════════════════════════
async function syncAbsensiPull() {
    const btn = document.getElementById('btnPull');
    const tanggal = document.getElementById('syncTanggal').value;
    if (!tanggal) { alert('Pilih tanggal!'); return; }
    setLoading(btn, true);
    try {
        const res = await fetch('ajax/sync_absensi.php?action=pull&tanggal=' + tanggal);
        const data = await res.json();
        if (data.success) {
            let stats = '';
            if (data.baru !== undefined) stats += `<span class="sync-stat" style="background:rgba(34,197,94,.15);color:#22c55e"><i class="fas fa-plus-circle"></i> ${data.baru} Baru</span>`;
            if (data.update !== undefined) stats += `<span class="sync-stat" style="background:rgba(59,130,246,.15);color:#3b82f6"><i class="fas fa-pen"></i> ${data.update} Update</span>`;
            if (data.skip !== undefined) stats += `<span class="sync-stat" style="background:rgba(148,163,184,.15);color:#94a3b8"><i class="fas fa-forward"></i> ${data.skip} Skip</span>`;
            document.getElementById('resultAbsensi').innerHTML = `<div class="sync-result-card">
                <div style="font-size:1rem;font-weight:700;color:#10b981;margin-bottom:.5rem"><i class="fas fa-check-circle"></i> ${data.message}</div>
                <div>${stats}</div>
                <div style="font-size:.8rem;color:var(--text-secondary);margin-top:.5rem">Total dari MandaApp: <strong style="color:#fff">${data.total_mandaapp}</strong></div>
            </div>`;
        } else {
            document.getElementById('resultAbsensi').innerHTML = errorHTML(data.message);
        }
    } catch (e) {
        document.getElementById('resultAbsensi').innerHTML = errorHTML(e.message);
    }
    setLoading(btn, false);
}

// Close modal on Escape
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
// Close modal on backdrop click
document.getElementById('syncModal').addEventListener('click', e => { if (e.target === e.currentTarget) closeModal(); });
</script>

<?php include 'includes/footer.php'; ?>
