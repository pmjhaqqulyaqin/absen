<?php
require_once 'includes/config.php';
require_once 'includes/sync_config.php';
cek_login();

// Pastikan tabel sync_log ada
$conn->query("CREATE TABLE IF NOT EXISTS sync_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipe ENUM('kelas','siswa','absensi_push','absensi_pull') NOT NULL,
    total_baru INT DEFAULT 0,
    total_update INT DEFAULT 0,
    total_skip INT DEFAULT 0,
    total_nonaktif INT DEFAULT 0,
    detail TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Pastikan kolom ext_id ada di tabel kelas dan siswa (jika database hasil import lama)
$conn->query("ALTER TABLE kelas ADD COLUMN IF NOT EXISTS ext_id VARCHAR(36) DEFAULT NULL");
$conn->query("ALTER TABLE siswa ADD COLUMN IF NOT EXISTS ext_id VARCHAR(36) DEFAULT NULL");

// Ambil log terakhir
$logs = $conn->query("SELECT * FROM sync_log ORDER BY created_at DESC LIMIT 20")->fetch_all(MYSQLI_ASSOC);

// Hitung data lokal
$total_siswa_lokal = $conn->query("SELECT COUNT(*) c FROM siswa WHERE aktif=1")->fetch_assoc()['c'];
$total_kelas_lokal = $conn->query("SELECT COUNT(*) c FROM kelas")->fetch_assoc()['c'];
$total_absensi_hari_ini = $conn->query("SELECT COUNT(*) c FROM absensi WHERE tanggal = CURDATE()")->fetch_assoc()['c'];
$total_siswa_synced = $conn->query("SELECT COUNT(*) c FROM siswa WHERE ext_id IS NOT NULL")->fetch_assoc()['c'];

include 'includes/header.php';
?>

<div class="main-content">
    <div class="page-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem">
        <div>
            <div class="page-title"><i class="fas fa-sync-alt" style="color:#10b981"></i> Sinkronisasi Data</div>
            <div class="page-subtitle">Sinkronkan data kelas, siswa, dan absensi dengan <strong>mandualotim.sch.id</strong></div>
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
        <button class="tab-btn active" onclick="switchTab('kelas')" id="tabKelas"
            style="padding:10px 20px;border-radius:10px;border:1px solid rgba(255,255,255,.1);cursor:pointer;font-size:.85rem;font-weight:600;transition:all .3s">
            <i class="fas fa-school"></i> Sinkron Kelas
        </button>
        <button class="tab-btn" onclick="switchTab('siswa')" id="tabSiswa"
            style="padding:10px 20px;border-radius:10px;border:1px solid rgba(255,255,255,.1);cursor:pointer;font-size:.85rem;font-weight:600;transition:all .3s">
            <i class="fas fa-users"></i> Sinkron Siswa
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

    <!-- ===================== TAB: KELAS ===================== -->
    <div class="sync-tab" id="panelKelas">
        <div class="card" style="border-radius:14px;overflow:hidden">
            <div style="background:linear-gradient(135deg,#6366f1,#4f46e5);padding:1.2rem 1.5rem;display:flex;justify-content:space-between;align-items:center">
                <div>
                    <div style="font-size:1.1rem;font-weight:700;color:#fff"><i class="fas fa-school"></i> Sinkronisasi Kelas</div>
                    <div style="font-size:.8rem;color:rgba(255,255,255,.7)">Tarik data kelas <strong>dari jadwal KBM</strong> mandualotim.sch.id (hanya kelas yang ada di jadwal)</div>
                </div>
                <button onclick="syncKelas()" id="btnSyncKelas" style="background:#fff;color:#4f46e5;border:none;padding:10px 24px;border-radius:10px;font-weight:700;cursor:pointer;font-size:.9rem;transition:all .2s">
                    <i class="fas fa-download"></i> Sinkronkan Sekarang
                </button>
            </div>
            <div style="padding:1.5rem">
                <div id="resultKelas" style="min-height:60px;display:flex;align-items:center;justify-content:center;color:var(--text-secondary)">
                    <div style="text-align:center">
                        <i class="fas fa-info-circle" style="font-size:2rem;opacity:.3;margin-bottom:.5rem;display:block"></i>
                        Klik tombol di atas untuk memulai sinkronisasi kelas
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================== TAB: SISWA ===================== -->
    <div class="sync-tab" id="panelSiswa" style="display:none">
        <div class="card" style="border-radius:14px;overflow:hidden">
            <div style="background:linear-gradient(135deg,#22c55e,#16a34a);padding:1.2rem 1.5rem;display:flex;justify-content:space-between;align-items:center">
                <div>
                    <div style="font-size:1.1rem;font-weight:700;color:#fff"><i class="fas fa-users"></i> Sinkronisasi Siswa</div>
                    <div style="font-size:.8rem;color:rgba(255,255,255,.7)">Tarik data siswa aktif dari mandualotim.sch.id</div>
                </div>
                <button onclick="syncSiswa()" id="btnSyncSiswa" style="background:#fff;color:#16a34a;border:none;padding:10px 24px;border-radius:10px;font-weight:700;cursor:pointer;font-size:.9rem;transition:all .2s">
                    <i class="fas fa-download"></i> Sinkronkan Sekarang
                </button>
            </div>
            <div style="padding:1.5rem">
                <div style="background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.3);border-radius:10px;padding:.8rem 1rem;margin-bottom:1rem;font-size:.8rem;color:#fbbf24">
                    <i class="fas fa-lightbulb"></i> <strong>Tips:</strong> Sinkronkan kelas terlebih dahulu. Siswa hanya akan diambil dari kelas yang sudah tersinkron (kelas jadwal KBM).
                </div>
                <div id="resultSiswa" style="min-height:60px;display:flex;align-items:center;justify-content:center;color:var(--text-secondary)">
                    <div style="text-align:center">
                        <i class="fas fa-info-circle" style="font-size:2rem;opacity:.3;margin-bottom:.5rem;display:block"></i>
                        Klik tombol di atas untuk memulai sinkronisasi siswa
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
                <div style="font-size:.8rem;color:rgba(255,255,255,.7)">Tarik atau kirim data absensi per tanggal</div>
            </div>
            <div style="padding:1.5rem">
                <div style="display:flex;gap:1rem;align-items:end;flex-wrap:wrap;margin-bottom:1.5rem">
                    <div>
                        <label style="font-size:.8rem;color:var(--text-secondary);display:block;margin-bottom:4px">Pilih Tanggal</label>
                        <input type="date" id="syncTanggal" value="<?= date('Y-m-d') ?>" 
                            style="background:var(--input-bg);border:1px solid rgba(255,255,255,.1);color:#fff;padding:10px 14px;border-radius:8px;font-size:.9rem">
                    </div>
                    <button onclick="syncAbsensiPull()" id="btnPull" style="background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;border:none;padding:10px 20px;border-radius:10px;font-weight:600;cursor:pointer;font-size:.85rem">
                        <i class="fas fa-download"></i> Tarik dari Mandaapp
                    </button>
                    <button onclick="syncAbsensiPush()" id="btnPush" style="background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;border:none;padding:10px 20px;border-radius:10px;font-weight:600;cursor:pointer;font-size:.85rem">
                        <i class="fas fa-upload"></i> Kirim ke Mandaapp
                    </button>
                </div>
                <div id="resultAbsensi" style="min-height:60px;display:flex;align-items:center;justify-content:center;color:var(--text-secondary)">
                    <div style="text-align:center">
                        <i class="fas fa-info-circle" style="font-size:2rem;opacity:.3;margin-bottom:.5rem;display:block"></i>
                        Pilih tanggal lalu klik Tarik atau Kirim
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

function resultHTML(data) {
    let stats = '';
    if (data.baru !== undefined) stats += `<span class="sync-stat" style="background:rgba(34,197,94,.15);color:#22c55e"><i class="fas fa-plus-circle"></i> ${data.baru} Baru</span>`;
    if (data.update !== undefined) stats += `<span class="sync-stat" style="background:rgba(59,130,246,.15);color:#3b82f6"><i class="fas fa-pen"></i> ${data.update} Update</span>`;
    if (data.skip !== undefined) stats += `<span class="sync-stat" style="background:rgba(148,163,184,.15);color:#94a3b8"><i class="fas fa-forward"></i> ${data.skip} Skip</span>`;
    if (data.nonaktif !== undefined && data.nonaktif > 0) stats += `<span class="sync-stat" style="background:rgba(239,68,68,.15);color:#ef4444"><i class="fas fa-user-minus"></i> ${data.nonaktif} Nonaktif</span>`;
    if (data.inserted !== undefined) stats += `<span class="sync-stat" style="background:rgba(34,197,94,.15);color:#22c55e"><i class="fas fa-plus-circle"></i> ${data.inserted} Inserted</span>`;
    if (data.updated !== undefined) stats += `<span class="sync-stat" style="background:rgba(59,130,246,.15);color:#3b82f6"><i class="fas fa-pen"></i> ${data.updated} Updated</span>`;
    if (data.skipped !== undefined) stats += `<span class="sync-stat" style="background:rgba(148,163,184,.15);color:#94a3b8"><i class="fas fa-forward"></i> ${data.skipped} Skipped</span>`;

    let total = data.total_mandaapp !== undefined ? `<div style="font-size:.8rem;color:var(--text-secondary);margin-top:.5rem">Total data mandaapp: <strong style="color:#fff">${data.total_mandaapp}</strong></div>` : '';
    if (data.total_dikirim !== undefined) total += `<div style="font-size:.8rem;color:var(--text-secondary)">Total dikirim: <strong style="color:#fff">${data.total_dikirim}</strong></div>`;
    if (data.source === 'scheduled') total += `<div style="font-size:.8rem;color:#818cf8;margin-top:.25rem"><i class="fas fa-filter"></i> Filtered: hanya kelas dari jadwal KBM</div>`;
    if (data.source === 'filtered') total += `<div style="font-size:.8rem;color:#818cf8;margin-top:.25rem"><i class="fas fa-filter"></i> Filtered: hanya siswa dari kelas jadwal</div>`;
    if (data.total_kelas_aktif !== undefined) total += `<div style="font-size:.8rem;color:var(--text-secondary)">Kelas aktif: <strong style="color:#fff">${data.total_kelas_aktif} kelas</strong></div>`;
    if (data.skip_kelas !== undefined && data.skip_kelas > 0) total += `<div style="font-size:.8rem;color:#fbbf24"><i class="fas fa-filter"></i> ${data.skip_kelas} siswa di-skip (kelas bukan jadwal)</div>`;
    if (data.kelas_non_jadwal !== undefined && data.kelas_non_jadwal > 0) total += `<div style="font-size:.8rem;color:#fbbf24"><i class="fas fa-info-circle"></i> ${data.kelas_non_jadwal} kelas lokal bukan dari jadwal</div>`;

    let errorsHtml = '';
    if (data.errors && data.errors.length > 0) {
        errorsHtml = `<div style="margin-top:.75rem;background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);border-radius:8px;padding:.6rem .8rem;font-size:.75rem;color:#fca5a5;max-height:120px;overflow-y:auto">
            <strong>⚠️ Catatan:</strong><br>${data.errors.slice(0, 10).join('<br>')}
            ${data.errors.length > 10 ? '<br>...dan ' + (data.errors.length - 10) + ' lainnya' : ''}
        </div>`;
    }

    let kelasHtml = '';
    if (data.kelas_aktif && data.kelas_aktif.length > 0) {
        const badges = data.kelas_aktif.map(k => 
            `<span style="display:inline-block;background:rgba(99,102,241,.15);color:#818cf8;padding:3px 10px;border-radius:6px;font-size:.72rem;font-weight:600;margin:2px">${k}</span>`
        ).join('');
        kelasHtml = `<div style="margin-top:.75rem;background:rgba(99,102,241,.05);border:1px solid rgba(99,102,241,.15);border-radius:8px;padding:.6rem .8rem">
            <div style="font-size:.75rem;color:#a5b4fc;margin-bottom:.4rem"><i class="fas fa-school"></i> <strong>Kelas tersinkron (${data.kelas_aktif.length}):</strong></div>
            <div>${badges}</div>
        </div>`;
    }

    return `<div class="sync-result-card">
        <div style="font-size:1rem;font-weight:700;color:#10b981;margin-bottom:.5rem">
            <i class="fas fa-check-circle"></i> ${data.message}
        </div>
        <div>${stats}</div>
        ${total}
        ${errorsHtml}
        ${kelasHtml}
    </div>`;
}

function errorHTML(msg) {
    return `<div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);border-radius:12px;padding:1.2rem;color:#fca5a5">
        <i class="fas fa-exclamation-triangle" style="color:#ef4444"></i> <strong>Gagal:</strong> ${msg}
    </div>`;
}

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
                <i class="fas fa-check-circle" style="color:#10b981"></i> <strong>Koneksi berhasil!</strong> API mandaapp aktif. Timestamp: ${data.timestamp || '-'}
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

async function syncKelas() {
    const btn = document.getElementById('btnSyncKelas');
    setLoading(btn, true);
    try {
        const res = await fetch('ajax/sync_kelas.php');
        const data = await res.json();
        document.getElementById('resultKelas').innerHTML = data.success ? resultHTML(data) : errorHTML(data.message);
    } catch (e) {
        document.getElementById('resultKelas').innerHTML = errorHTML(e.message);
    }
    setLoading(btn, false);
}

async function syncSiswa() {
    const btn = document.getElementById('btnSyncSiswa');
    setLoading(btn, true);
    try {
        const res = await fetch('ajax/sync_siswa.php');
        const data = await res.json();
        document.getElementById('resultSiswa').innerHTML = data.success ? resultHTML(data) : errorHTML(data.message);
    } catch (e) {
        document.getElementById('resultSiswa').innerHTML = errorHTML(e.message);
    }
    setLoading(btn, false);
}

async function syncAbsensiPull() {
    const btn = document.getElementById('btnPull');
    const tanggal = document.getElementById('syncTanggal').value;
    if (!tanggal) { alert('Pilih tanggal!'); return; }
    setLoading(btn, true);
    try {
        const res = await fetch('ajax/sync_absensi.php?action=pull&tanggal=' + tanggal);
        const data = await res.json();
        document.getElementById('resultAbsensi').innerHTML = data.success ? resultHTML(data) : errorHTML(data.message);
    } catch (e) {
        document.getElementById('resultAbsensi').innerHTML = errorHTML(e.message);
    }
    setLoading(btn, false);
}

async function syncAbsensiPush() {
    const btn = document.getElementById('btnPush');
    const tanggal = document.getElementById('syncTanggal').value;
    if (!tanggal) { alert('Pilih tanggal!'); return; }
    if (!confirm('Kirim data absensi tanggal ' + tanggal + ' ke mandaapp?')) return;
    setLoading(btn, true);
    try {
        const res = await fetch('ajax/sync_absensi.php?action=push&tanggal=' + tanggal);
        const data = await res.json();
        document.getElementById('resultAbsensi').innerHTML = data.success ? resultHTML(data) : errorHTML(data.message);
    } catch (e) {
        document.getElementById('resultAbsensi').innerHTML = errorHTML(e.message);
    }
    setLoading(btn, false);
}
</script>

<?php include 'includes/footer.php'; ?>
