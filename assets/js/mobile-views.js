/* ============================================
   MOBILE VIEW SWITCHING & LOGIC
   ============================================ */

var currentView = 'beranda';
var scannerMoved = false;

function switchView(viewName) {
    // Stop scan jika pindah dari view scan
    if (currentView === 'scan' && viewName !== 'scan') {
        if (typeof stopScan === 'function') stopScan();
    }

    currentView = viewName;

    // Simpan ke localStorage agar persistent saat kembali dari halaman lain
    try { localStorage.setItem('absensi_view', viewName); } catch(e) {}

    // Hide semua view
    document.querySelectorAll('.view').forEach(function(v) { v.classList.remove('active'); });

    // Show target view
    var target = document.getElementById('view-' + viewName);
    if (target) target.classList.add('active');

    // Update bottom nav active state
    document.querySelectorAll('.bnav-item').forEach(function(b) { b.classList.remove('active'); });
    var activeBtn = document.querySelector('[data-view="' + viewName + '"]');
    if (activeBtn) activeBtn.classList.add('active');

    // Refresh data jika diperlukan
    if (viewName === 'rekap' || viewName === 'beranda') {
        refreshMobileStats();
    }
    if (viewName === 'riwayat') {
        loadMobileLog();
    }

    // Scroll ke atas
    window.scrollTo(0, 0);
}

// === BERANDA: Donut Chart via CSS conic-gradient ===
function updateDonutChart() {
    var hadirEl = document.getElementById('s-hadir');
    var terlambatEl = document.getElementById('s-terlambat');
    var belumEl = document.getElementById('s-alpa');
    var totalEl = document.getElementById('s-total');

    var hadir = hadirEl ? parseInt(hadirEl.textContent) || 0 : 0;
    var terlambat = terlambatEl ? parseInt(terlambatEl.textContent) || 0 : 0;
    var belum = belumEl ? parseInt(belumEl.textContent) || 0 : 0;
    var total = totalEl ? parseInt(totalEl.textContent) || 1 : 1;
    if (total === 0) total = 1;

    var pHadir = (hadir / total) * 100;
    var pTerlambat = (terlambat / total) * 100;
    var pBelum = (belum / total) * 100;

    var ring = document.getElementById('donutRing');
    if (ring) {
        var a1 = pHadir;
        var a2 = a1 + pTerlambat;
        var a3 = a2 + pBelum;
        ring.style.background = 'conic-gradient(' +
            '#4ade80 0% ' + a1 + '%, ' +
            '#fb923c ' + a1 + '% ' + a2 + '%, ' +
            '#f87171 ' + a2 + '% ' + a3 + '%, ' +
            'rgba(255,255,255,0.08) ' + a3 + '% 100%)';
    }

    var pctEl = document.getElementById('donutPct');
    if (pctEl) pctEl.textContent = Math.round(pHadir) + '%';

    // Update stat cards di beranda
    var mh = document.getElementById('m-hadir'); if (mh) mh.textContent = hadir;
    var mt = document.getElementById('m-terlambat'); if (mt) mt.textContent = terlambat;
    var mb = document.getElementById('m-belum'); if (mb) mb.textContent = belum;
    var mto = document.getElementById('m-total'); if (mto) mto.textContent = total;
}

// === REKAP: Update bar chart ===
function updateRekapBars() {
    var totalEl = document.getElementById('s-total');
    var total = totalEl ? parseInt(totalEl.textContent) || 1 : 1;
    if (total === 0) total = 1;

    var keys = ['hadir','terlambat','belum','sakit','izin','bolos'];
    var ids = {'hadir':'s-hadir','terlambat':'s-terlambat','belum':'s-alpa','sakit':'s-sakit','izin':'s-izin','bolos':'s-bolos'};
    
    keys.forEach(function(key) {
        var srcEl = document.getElementById(ids[key]);
        var val = srcEl ? parseInt(srcEl.textContent) || 0 : 0;
        var bar = document.getElementById('bar-' + key);
        var num = document.getElementById('barnum-' + key);
        if (bar) {
            var pct = Math.round((val / total) * 100);
            bar.style.width = Math.max(pct, 2) + '%';
        }
        if (num) num.textContent = val;
        
        var el = document.getElementById('r-' + key);
        if (el) el.textContent = val;
    });
    var rt = document.getElementById('r-total');
    if (rt) rt.textContent = total;
}

// === Refresh stats ===
function refreshMobileStats() {
    if (typeof BASE === 'undefined') return;
    fetch(BASE + 'ajax/get_stats.php')
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d) {
                var map = {'s-hadir':'Hadir','s-terlambat':'Terlambat','s-alpa':'belum_absen','s-sakit':'Sakit','s-izin':'Izin','s-bolos':'Bolos'};
                for (var id in map) {
                    var el = document.getElementById(id);
                    if (el) el.textContent = d[map[id]] || 0;
                }
                updateDonutChart();
                updateRekapBars();
            }
        })
        .catch(function() {});
}

// === RIWAYAT: Load full log ===
var allLogData = [];

function loadMobileLog() {
    if (typeof BASE === 'undefined') return;
    fetch(BASE + 'ajax/get_log.php')
        .then(function(r) { return r.json(); })
        .then(function(d) {
            allLogData = d || [];
            renderMobileLog('all');
        })
        .catch(function() {});
}

function renderMobileLog(filter) {
    var list = document.getElementById('mobileLogList');
    if (!list) return;

    document.querySelectorAll('.v-filter-chip').forEach(function(c) {
        c.classList.toggle('active', c.dataset.filter === filter);
    });

    var filtered = allLogData;
    if (filter !== 'all') {
        filtered = allLogData.filter(function(r) { return r.status === filter; });
    }

    var countEl = document.getElementById('riwayatCount');
    if (countEl) countEl.textContent = filtered.length;

    if (filtered.length === 0) {
        list.innerHTML = '<div style="padding:40px;text-align:center;color:#475569"><i class="fas fa-inbox fa-2x" style="opacity:.3;display:block;margin-bottom:10px"></i>Tidak ada data</div>';
        return;
    }

    list.innerHTML = filtered.map(function(row) {
        var st = row.status || 'Hadir';
        var bc = { Hadir: 'badge-hadir', Terlambat: 'badge-terlambat', Pulang: 'badge-pulang' }[st] || 'badge-hadir';
        var jam = row.jam_masuk ? row.jam_masuk.slice(0, 5) : '-';
        return '<div class="log-item">' +
            '<div class="log-avatar">' + row.nama.charAt(0).toUpperCase() + '</div>' +
            '<div style="flex:1;min-width:0"><div class="log-name">' + row.nama + '</div><div class="log-detail">' + row.nis + ' | ' + row.kelas + '</div></div>' +
            '<div><span class="log-badge ' + bc + '">' + st + '</span><div class="log-time">' + jam + '</div></div>' +
            '</div>';
    }).join('');
}

function filterRiwayat(filter) {
    renderMobileLog(filter);
}

// === BERANDA: Recent log ===
function updateRecentLog() {
    var feed = document.getElementById('recentLogFeed');
    if (!feed) return;
    var items = document.querySelectorAll('#logFeed .log-item');
    if (items.length === 0) {
        feed.innerHTML = '<div style="padding:16px;text-align:center;color:#475569;font-size:.8rem"><i class="fas fa-inbox" style="opacity:.3"></i> Belum ada scan</div>';
        return;
    }
    feed.innerHTML = '';
    var count = Math.min(items.length, 3);
    for (var i = 0; i < count; i++) {
        feed.innerHTML += items[i].outerHTML;
    }
}

// === Mobile Clock ===
function updateMobileClock() {
    var el = document.getElementById('mobileClock');
    if (el) {
        var n = new Date();
        el.textContent = String(n.getHours()).padStart(2, '0') + ':' + String(n.getMinutes()).padStart(2, '0') + ':' + String(n.getSeconds()).padStart(2, '0');
    }
}

// === Greeting ===
function getGreeting() {
    var h = new Date().getHours();
    if (h < 10) return '🌅 Selamat Pagi';
    if (h < 15) return '☀️ Selamat Siang';
    if (h < 18) return '🌤️ Selamat Sore';
    return '🌙 Selamat Malam';
}

// === Move Scanner ke Mobile View ===
function moveScannerToMobile() {
    if (scannerMoved) return;
    // Cari .grid-scan di dalam .desktop-content
    var gridScan = document.querySelector('.desktop-content .grid-scan');
    var container = document.getElementById('mobileScanContainer');
    console.log('[MobileViews] moveScannerToMobile: gridScan=', !!gridScan, ', container=', !!container);
    if (gridScan && container) {
        container.innerHTML = '';
        container.appendChild(gridScan);
        // Paksa display karena mungkin parent .main punya display:none
        gridScan.style.display = 'block';
        gridScan.style.maxWidth = '100%';
        gridScan.style.padding = '0';
        scannerMoved = true;
        console.log('[MobileViews] Scanner berhasil dipindahkan!');
        // Re-init kamera setelah pindah
        if (typeof loadCameras === 'function') {
            setTimeout(loadCameras, 500);
        }
    } else {
        console.warn('[MobileViews] Gagal pindahkan scanner! gridScan:', gridScan, 'container:', container);
    }
}

// Init mobile views
function initMobileViews() {
    if (window.innerWidth > 768) return;
    console.log('[MobileViews] Initializing...');

    // Pindahkan scanner ke mobileScanContainer
    moveScannerToMobile();

    var greetEl = document.getElementById('mobileGreeting');
    if (greetEl) greetEl.textContent = getGreeting();

    setInterval(updateMobileClock, 1000);
    updateMobileClock();

    updateDonutChart();
    updateRekapBars();

    setTimeout(function() {
        updateRecentLog();
        loadMobileLog();
    }, 1500);

    // Cek localStorage untuk view persistence
    var savedView = null;
    try { savedView = localStorage.getItem('absensi_view'); } catch(e) {}
    
    if (savedView && document.getElementById('view-' + savedView)) {
        switchView(savedView);
    } else {
        switchView('beranda');
    }
}

// Run on load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMobileViews);
} else {
    initMobileViews();
}
