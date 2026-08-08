// ==========================================
// SISTEM ABSENSI - MAIN JS
// ==========================================

// --- CLOCK ---
function updateClock() {
    const el = document.getElementById('realtimeClock');
    if (!el) return;
    const now = new Date();
    const hari = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    const bulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'];
    const h = String(now.getHours()).padStart(2,'0');
    const m = String(now.getMinutes()).padStart(2,'0');
    const s = String(now.getSeconds()).padStart(2,'0');
    el.innerHTML = `<i class="fas fa-clock"></i> ${hari[now.getDay()]}, ${now.getDate()} ${bulan[now.getMonth()]} ${now.getFullYear()} &nbsp;|&nbsp; ${h}:${m}:${s}`;
}
setInterval(updateClock, 1000);
updateClock();

// --- SIDEBAR ---
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const main = document.getElementById('mainContent');
    if (window.innerWidth <= 768) {
        sidebar.classList.toggle('open');
    } else {
        const isCollapsed = sidebar.style.transform === 'translateX(-100%)';
        sidebar.style.transform = isCollapsed ? '' : 'translateX(-100%)';
        main.style.marginLeft = isCollapsed ? '' : '0';
    }
}

// Close sidebar on outside click (mobile)
document.addEventListener('click', function(e) {
    if (window.innerWidth <= 768) {
        const sidebar = document.getElementById('sidebar');
        const toggle = document.querySelector('.sidebar-toggle');
        if (sidebar && !sidebar.contains(e.target) && !toggle.contains(e.target)) {
            sidebar.classList.remove('open');
        }
    }
});

// --- TOAST ---
function showToast(message, type = 'info', duration = 3000) {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    const icons = { success: '✅', error: '❌', warning: '⚠️', info: 'ℹ️' };
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `<span style="font-size:1.2rem">${icons[type]||'ℹ️'}</span> <span>${message}</span>`;
    container.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
        toast.style.transition = 'all .3s';
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

// --- MODAL ---
function openModal(id) {
    document.getElementById(id).classList.add('show');
    document.body.style.overflow = 'hidden';
}
function closeModal(id) {
    document.getElementById(id).classList.remove('show');
    document.body.style.overflow = '';
}

// --- CONFIRM DELETE ---
function confirmDelete(url, msg = 'Yakin ingin menghapus data ini?') {
    if (confirm(msg)) {
        window.location.href = url;
    }
}

// --- TABLE SEARCH ---
function tableSearch(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    if (!input || !table) return;
    input.addEventListener('keyup', function() {
        const filter = this.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(filter) ? '' : 'none';
        });
    });
}

// --- AJAX helper ---
async function ajaxPost(url, data) {
    const resp = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(data)
    });
    return resp.json();
}

// --- BARCODE via ZXing (CDN) ---
let scannerActive = false;
let codeReader = null;

async function startCameraScanner(videoElem, onDecode) {
    if (!window.ZXing) {
        showToast('Library barcode belum dimuat', 'error');
        return;
    }
    const hints = new Map();
    const formats = [
        ZXing.BarcodeFormat.CODE_128,
        ZXing.BarcodeFormat.CODE_39,
        ZXing.BarcodeFormat.EAN_13,
        ZXing.BarcodeFormat.QR_CODE,
    ];
    hints.set(ZXing.DecodeHintType.POSSIBLE_FORMATS, formats);
    codeReader = new ZXing.BrowserMultiFormatReader(hints);
    try {
        const devices = await ZXing.BrowserMultiFormatReader.listVideoInputDevices();
        const deviceId = devices.length > 0 ? devices[0].deviceId : undefined;
        codeReader.decodeFromVideoDevice(deviceId, videoElem, (result, err) => {
            if (result) {
                onDecode(result.getText());
            }
        });
        scannerActive = true;
    } catch(e) {
        showToast('Tidak bisa akses kamera: ' + e.message, 'error');
    }
}

function stopScanner() {
    if (codeReader) {
        codeReader.reset();
        scannerActive = false;
    }
}

// USB Scanner: Listen on input field
function initUSBScanner(inputId, callback) {
    const input = document.getElementById(inputId);
    if (!input) return;
    let lastKeyTime = Date.now();
    input.addEventListener('input', function() {
        const now = Date.now();
        if (now - lastKeyTime < 50 && this.value.length > 5) {
            // Likely from barcode scanner (fast input)
            setTimeout(() => {
                if (this.value.trim()) {
                    callback(this.value.trim());
                    this.value = '';
                }
            }, 100);
        }
        lastKeyTime = now;
    });
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && this.value.trim()) {
            callback(this.value.trim());
            this.value = '';
            e.preventDefault();
        }
    });
}

// Auto-init search boxes
document.addEventListener('DOMContentLoaded', function() {
    // Auto table search
    if (document.getElementById('searchInput') && document.getElementById('mainTable')) {
        tableSearch('searchInput', 'mainTable');
    }
    // Modal close on overlay click
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('show');
                document.body.style.overflow = '';
            }
        });
    });
});

// Format time
function padZero(n) { return String(n).padStart(2,'0'); }
