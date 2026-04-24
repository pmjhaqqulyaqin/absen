<?php
require_once 'includes/config.php';
cek_login();
$pengaturan = get_pengaturan();
include 'includes/header.php';
?>

<div class="page-header">
    <div class="page-title"><i class="fas fa-qrcode"></i> Scan QR / Barcode</div>
    <div class="page-subtitle">3 Metode: Kamera HP/PC, Webcam, USB Scanner</div>
</div>

<!-- PERINGATAN HTTP -->
<?php
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
$isLocal = in_array($_SERVER['SERVER_NAME'] ?? $_SERVER['HTTP_HOST'] ?? '', ['localhost','127.0.0.1','::1']);
if (!$isHttps && !$isLocal):
?>
<div style="background:#fef3c7;border:1px solid #f59e0b;color:#92400e;margin-bottom:16px;padding:14px 18px;border-radius:10px;display:flex;gap:12px;align-items:flex-start">
    <i class="fas fa-exclamation-triangle" style="margin-top:2px;color:#f59e0b;font-size:1.2rem;flex-shrink:0"></i>
    <div>
        <strong>⚠️ Kamera HP memerlukan HTTPS!</strong><br>
        Anda mengakses via HTTP. Kamera tidak bisa diakses tanpa HTTPS.<br>
        <span style="font-size:.85rem">Gunakan <strong>USB Scanner</strong> atau <strong>Input NIS Manual</strong>. Webcam PC di localhost tetap bisa digunakan.</span>
    </div>
</div>
<?php endif; ?>

<div class="grid-2">
    <div class="card">
        <div class="card-header"><i class="fas fa-camera"></i> Scanner</div>
        <div class="card-body">
            <div style="display:flex;gap:8px;margin-bottom:14px">
                <button id="btnMasuk" onclick="setJenis('masuk')" style="flex:1;padding:10px;border:2px solid #16a34a;border-radius:10px;background:#16a34a;color:white;font-weight:700;cursor:pointer;font-size:.95rem">
                    🟢 Absen Masuk
                </button>
                <button id="btnPulang" onclick="setJenis('pulang')" style="flex:1;padding:10px;border:2px solid #e2e8f0;border-radius:10px;background:white;color:#64748b;font-weight:700;cursor:pointer;font-size:.95rem">
                    🔴 Absen Pulang
                </button>
            </div>
            <div id="infoJenis" style="text-align:center;font-size:.85rem;color:#16a34a;margin-bottom:10px;font-weight:600">Mode aktif: ABSEN MASUK</div>

            <div class="scan-tabs">
                <button class="scan-tab active" onclick="switchTab('camera',this)"><i class="fas fa-mobile-alt"></i> Kamera HP/PC</button>
                <button class="scan-tab" onclick="switchTab('usb',this)"><i class="fas fa-usb"></i> USB Scanner</button>
            </div>

            <!-- Camera Tab -->
            <div id="tab-camera">
                <div id="libStatus" style="text-align:center;padding:7px;font-size:.8rem;color:#64748b;background:#f8fafc;border-radius:6px;margin-bottom:8px">
                    <i class="fas fa-spinner fa-spin"></i> Memuat library scanner...
                </div>
                <div style="position:relative;background:#111;border-radius:10px;overflow:hidden">
                    <div id="scanPlaceholder" style="height:300px;display:flex;flex-direction:column;align-items:center;justify-content:center;color:rgba(255,255,255,.5)">
                        <i class="fas fa-camera fa-3x" style="margin-bottom:12px"></i>
                        <p style="margin:0">Klik <strong style="color:white">Mulai Scan</strong></p>
                    </div>
                    <video id="cameraVideo" autoplay playsinline muted style="width:100%;display:none;max-height:300px;object-fit:cover"></video>
                    <canvas id="scanCanvas" style="display:none"></canvas>
                    <div id="scanOverlay" style="display:none;position:absolute;top:0;left:0;width:100%;height:100%;pointer-events:none">
                        <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:65%;max-width:260px;height:65%;max-height:200px;border:3px solid #22c55e;border-radius:12px;box-shadow:0 0 0 9999px rgba(0,0,0,.45)">
                            <div style="position:absolute;top:0;left:0;width:100%;height:3px;background:linear-gradient(90deg,transparent,#22c55e,transparent);animation:scanline 1.5s ease-in-out infinite"></div>
                        </div>
                        <div style="position:absolute;bottom:10px;width:100%;text-align:center;color:white;font-size:.8rem;font-weight:600;text-shadow:0 1px 3px rgba(0,0,0,.8)">Arahkan QR/Barcode ke kotak hijau</div>
                    </div>
                    <div id="detectFlash" style="display:none;position:absolute;top:0;left:0;width:100%;height:100%;background:rgba(34,197,94,.4);pointer-events:none"></div>
                    <!-- Senter Torch -->
                    <button id="torchBtn" onclick="toggleTorch()" style="display:none;position:absolute;top:10px;right:10px;background:rgba(0,0,0,0.6);border:1px solid rgba(255,255,255,0.2);color:white;width:44px;height:44px;border-radius:50%;cursor:pointer;z-index:10;"><i class="fas fa-lightbulb"></i></button>
                </div>
                <div style="display:flex;gap:8px;margin-top:10px;flex-wrap:wrap">
                    <button class="btn btn-success" id="startBtn" onclick="startScan()" style="flex:1">
                        <i class="fas fa-play"></i> Mulai Scan
                    </button>
                    <button class="btn btn-danger" id="stopBtn" onclick="stopScan()" style="flex:1;display:none">
                        <i class="fas fa-stop"></i> Stop
                    </button>
                    <select id="cameraSelect" class="form-select" style="flex:1;min-width:140px">
                        <option>Memuat kamera...</option>
                    </select>
                </div>
                <div id="debugInfo" style="margin-top:6px;font-size:.73rem;color:#94a3b8;min-height:16px;text-align:center"></div>
            </div>

            <!-- USB Tab -->
            <div id="tab-usb" style="display:none">
                <div style="padding:28px;text-align:center;background:#f8fafc;border-radius:10px;border:2px dashed var(--border)">
                    <i class="fas fa-qrcode fa-3x" style="color:var(--primary);margin-bottom:14px"></i>
                    <p style="margin-bottom:14px;color:var(--text-muted)">Hubungkan USB QR/Barcode Scanner, klik field lalu scan kartu siswa.</p>
                    <input type="text" id="usbInput" class="form-control" placeholder="🎯 Klik di sini, lalu scan..." style="text-align:center;font-size:1.1rem;font-weight:700;letter-spacing:2px;max-width:300px;margin:0 auto;display:block">
                    <p style="margin-top:10px;font-size:.8rem;color:var(--text-muted)"><i class="fas fa-info-circle"></i> Scanner otomatis submit setelah baca kode</p>
                </div>
            </div>

            <!-- Manual -->
            <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--border)">
                <label class="form-label"><i class="fas fa-keyboard"></i> Input NIS Manual</label>
                <div style="display:flex;gap:8px">
                    <input type="text" id="manualNis" class="form-control" placeholder="Ketik NIS lalu Enter">
                    <button class="btn btn-primary" onclick="processNIS(document.getElementById('manualNis').value.trim())"><i class="fas fa-search"></i></button>
                </div>
            </div>
        </div>
    </div>

    <div>
        <div class="card mb-2" id="resultCard" style="display:none">
            <div class="card-body" id="resultContent" style="text-align:center;padding:28px"></div>
        </div>
        <div class="card mb-2">
            <div class="card-body" style="display:flex;justify-content:space-between;padding:14px 20px;flex-wrap:wrap;gap:8px">
                <div style="text-align:center"><div style="font-size:.75rem;color:var(--text-muted)">Masuk</div><div style="font-weight:700;color:var(--success)"><?= date('H:i',strtotime($pengaturan['jam_masuk'])) ?></div></div>
                <div style="text-align:center"><div style="font-size:.75rem;color:var(--text-muted)">Terlambat</div><div style="font-weight:700;color:var(--warning)"><?= date('H:i',strtotime($pengaturan['jam_terlambat'])) ?></div></div>
                <div style="text-align:center"><div style="font-size:.75rem;color:var(--text-muted)">Pulang</div><div style="font-weight:700;color:var(--primary)"><?= date('H:i',strtotime($pengaturan['jam_pulang'])) ?></div></div>
                <div style="text-align:center"><div style="font-size:.75rem;color:var(--text-muted)">Sekarang</div><div style="font-weight:700" id="liveClock2"><?= date('H:i:s') ?></div></div>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><i class="fas fa-list" style="color:var(--primary)"></i> Log Absensi Hari Ini <span class="badge badge-hadir ms-auto" id="logCount">0</span></div>
            <div id="logFeed" style="min-height:200px;max-height:400px;overflow-y:auto">
                <div style="padding:30px;text-align:center;color:var(--text-muted)" id="logEmpty">
                    <i class="fas fa-inbox fa-2x" style="opacity:.3"></i><p style="margin-top:10px">Belum ada scan hari ini</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes scanline { 0%{top:0} 50%{top:calc(100% - 3px)} 100%{top:0} }
.scan-tabs{display:flex;gap:6px;margin-bottom:12px}
.scan-tab{flex:1;padding:9px;border:2px solid var(--border);border-radius:8px;background:white;color:#64748b;font-weight:600;cursor:pointer;font-size:.85rem;transition:.2s}
.scan-tab.active{background:var(--primary);border-color:var(--primary);color:white}
.log-item{display:flex;align-items:center;gap:12px;padding:10px 16px;border-bottom:1px solid var(--border)}
.log-avatar{width:36px;height:36px;border-radius:50%;background:var(--primary);color:white;display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0}
.log-info{flex:1;min-width:0}
.log-name{font-weight:600;font-size:.9rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.log-detail{font-size:.75rem;color:var(--text-muted)}
.log-time{font-size:.75rem;color:var(--text-muted);text-align:right;white-space:nowrap}
</style>

<!-- jsQR: sangat andal untuk QR Code -->
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
<!-- ZXing: untuk barcode CODE128/EAN -->
<script src="https://unpkg.com/@zxing/library@0.19.1/umd/index.min.js"></script>

<script>
var videoStream=null, scanInterval=null, scanning=false;
var jenisAbsen='masuk', lastScanned='', lastScannedAt=0;
var audioCtx=null, zxingDecoder=null;
var jsQRReady=false, zxingReady=false;
var wakeLock = null; // Wake Lock API
var videoTrack = null; // Untuk kontrol Senter

// --- Check library ---
var libCheck = setInterval(function(){
    jsQRReady  = (typeof jsQR === 'function');
    zxingReady = (typeof ZXing !== 'undefined');
    var el = document.getElementById('libStatus');
    if (jsQRReady && zxingReady) {
        clearInterval(libCheck);
        el.innerHTML = '<i class="fas fa-check-circle" style="color:#16a34a"></i> Scanner siap: <b>jsQR</b> (QR Code) + <b>ZXing</b> (Barcode)';
        el.style.background='#f0fdf4'; el.style.color='#15803d';
        // Init ZXing decoder
        try {
            var hints = new Map();
            hints.set(ZXing.DecodeHintType.POSSIBLE_FORMATS,[
                ZXing.BarcodeFormat.CODE_128, ZXing.BarcodeFormat.CODE_39,
                ZXing.BarcodeFormat.EAN_13,   ZXing.BarcodeFormat.EAN_8,
                ZXing.BarcodeFormat.QR_CODE,  ZXing.BarcodeFormat.DATA_MATRIX
            ]);
            hints.set(ZXing.DecodeHintType.TRY_HARDER, true);
            zxingDecoder = new ZXing.BrowserMultiFormatReader(hints);
        } catch(e){}
    } else if (jsQRReady) {
        el.innerHTML = '<i class="fas fa-check-circle" style="color:#f59e0b"></i> jsQR siap. ZXing memuat...';
    }
}, 400);

// --- Audio ---
document.addEventListener('click', function(){
    if(!audioCtx) audioCtx=new(window.AudioContext||window.webkitAudioContext)();
}, false);
function beep(freq,dur,vol){
    try{
        if(!audioCtx) audioCtx=new(window.AudioContext||window.webkitAudioContext)();
        if(audioCtx.state==='suspended') audioCtx.resume();
        var o=audioCtx.createOscillator(), g=audioCtx.createGain();
        o.connect(g); g.connect(audioCtx.destination);
        o.type='square'; o.frequency.value=freq||880;
        g.gain.setValueAtTime(vol||0.5, audioCtx.currentTime);
        g.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime+(dur||150)/1000);
        o.start(); o.stop(audioCtx.currentTime+(dur||150)/1000);
    }catch(e){}
}

// --- Clock ---
setInterval(function(){
    var el=document.getElementById('liveClock2');
    if(el) el.textContent=new Date().toTimeString().slice(0,8);
},1000);

// --- Jenis absen ---
function setJenis(j){
    jenisAbsen=j;
    var bM=document.getElementById('btnMasuk'), bP=document.getElementById('btnPulang'), inf=document.getElementById('infoJenis');
    if(j==='masuk'){
        bM.style.cssText='flex:1;padding:10px;border:2px solid #16a34a;border-radius:10px;background:#16a34a;color:white;font-weight:700;cursor:pointer;font-size:.95rem';
        bP.style.cssText='flex:1;padding:10px;border:2px solid #e2e8f0;border-radius:10px;background:white;color:#64748b;font-weight:700;cursor:pointer;font-size:.95rem';
        inf.style.color='#16a34a'; inf.textContent='Mode aktif: ABSEN MASUK';
    } else {
        bP.style.cssText='flex:1;padding:10px;border:2px solid #dc2626;border-radius:10px;background:#dc2626;color:white;font-weight:700;cursor:pointer;font-size:.95rem';
        bM.style.cssText='flex:1;padding:10px;border:2px solid #e2e8f0;border-radius:10px;background:white;color:#64748b;font-weight:700;cursor:pointer;font-size:.95rem';
        inf.style.color='#dc2626'; inf.textContent='Mode aktif: ABSEN PULANG';
    }
}

// --- Tab switch ---
function switchTab(tab,btn){
    document.querySelectorAll('.scan-tab').forEach(function(b){b.classList.remove('active');});
    btn.classList.add('active');
    document.getElementById('tab-camera').style.display = tab==='camera'?'':'none';
    document.getElementById('tab-usb').style.display    = tab==='usb'?'':'none';
    if(tab==='usb'){ stopScan(); setTimeout(function(){document.getElementById('usbInput').focus();},150); }
}

// --- Load cameras ---
async function loadCameras(){
    var sel=document.getElementById('cameraSelect');
    if(!navigator.mediaDevices||!navigator.mediaDevices.getUserMedia){
        sel.innerHTML='<option>Butuh HTTPS untuk kamera</option>'; return;
    }
    try{
        var tmp=await navigator.mediaDevices.getUserMedia({video:true});
        tmp.getTracks().forEach(function(t){t.stop();});
        var devs=await navigator.mediaDevices.enumerateDevices();
        var cams=devs.filter(function(d){return d.kind==='videoinput';});
        if(!cams.length){ sel.innerHTML='<option>Tidak ada kamera</option>'; return; }
        sel.innerHTML=cams.map(function(c,i){
            return '<option value="'+c.deviceId+'">'+(c.label||('Kamera '+(i+1)))+'</option>';
        }).join('');
        // Pilih kamera belakang otomatis
        var back=cams.find(function(c){return /back|rear|belakang|environment/i.test(c.label);});
        if(back) sel.value=back.deviceId;
    }catch(e){
        sel.innerHTML='<option>Izin kamera ditolak</option>';
        dbg('❌ '+e.message);
    }
}
loadCameras();

// --- Start Scan ---
async function startScan(){
    if(!navigator.mediaDevices||!navigator.mediaDevices.getUserMedia){
        showToast('Kamera tidak bisa diakses. Perlu HTTPS!','error'); return;
    }
    var did=document.getElementById('cameraSelect').value;
    var constraints={video: did
        ? {deviceId:{exact:did}, width:{ideal:1280}, height:{ideal:720}, advanced:[{focusMode:"continuous"}]}
        : {facingMode:{ideal:'environment'}, width:{ideal:1280}, height:{ideal:720}, advanced:[{focusMode:"continuous"}]}
    };
    try{
        videoStream=await navigator.mediaDevices.getUserMedia(constraints);
        var video=document.getElementById('cameraVideo');
        video.srcObject=videoStream;
        await video.play();

        // Setup Senter
        videoTrack = videoStream.getVideoTracks()[0];
        if (videoTrack && videoTrack.getCapabilities) {
            const capabilities = videoTrack.getCapabilities();
            if (capabilities.torch) {
                document.getElementById('torchBtn').style.display = 'block';
            }
        }
        
        // Wake Lock Request (Layar tidak mati)
        try {
            if ('wakeLock' in navigator) {
                wakeLock = await navigator.wakeLock.request('screen');
            }
        } catch (err) {}

        document.getElementById('scanPlaceholder').style.display='none';
        video.style.display='block';
        document.getElementById('scanOverlay').style.display='block';
        document.getElementById('startBtn').style.display='none';
        document.getElementById('stopBtn').style.display='';
        scanning=true;
        dbg('📷 Kamera aktif — mendeteksi QR/Barcode...');

        // Update daftar kamera
        try{
            var devs=await navigator.mediaDevices.enumerateDevices();
            var cams=devs.filter(function(d){return d.kind==='videoinput';});
            var sel=document.getElementById('cameraSelect');
            sel.innerHTML=cams.map(function(c,i){
                return '<option value="'+c.deviceId+'" '+(c.deviceId===did?'selected':'')+'>'+(c.label||('Kamera '+(i+1)))+'</option>';
            }).join('');
        }catch(e){}

        startCanvasLoop(video);
    }catch(e){
        var msg='Error: '+e.message;
        if(e.name==='NotAllowedError') msg='Izin kamera ditolak.';
        if(e.name==='NotFoundError')   msg='Tidak ada kamera.';
        if(e.name==='NotReadableError') msg='Kamera digunakan aplikasi lain.';
        showToast(msg,'error'); dbg('❌ '+msg);
    }
}

// --- Canvas Scan Loop (inti deteksi) ---
function startCanvasLoop(video){
    var canvas=document.getElementById('scanCanvas');
    var ctx=canvas.getContext('2d',{willReadFrequently:true});
    var frame=0;

    scanInterval=setInterval(function(){
        if(!scanning||video.readyState<2||video.videoWidth===0) return;
        if(canvas.width!==video.videoWidth){ canvas.width=video.videoWidth; canvas.height=video.videoHeight; }

        ctx.drawImage(video,0,0,canvas.width,canvas.height);
        frame++;

        // Crop area tengah 70%
        var cw=Math.floor(canvas.width*0.7), ch=Math.floor(canvas.height*0.7);
        var cx=Math.floor((canvas.width-cw)/2), cy=Math.floor((canvas.height-ch)/2);
        var imgData;
        try{ imgData=ctx.getImageData(cx,cy,cw,ch); }catch(e){ return; }

        // == METODE 1: jsQR (paling andal untuk QR Code) ==
        if(typeof jsQR==='function'){
            var qr=jsQR(imgData.data,imgData.width,imgData.height,{inversionAttempts:'dontInvert'});
            if(qr&&qr.data){ onDetected(qr.data,'jsQR'); return; }
            // Coba juga full frame (untuk QR kecil)
            if(frame%2===0){
                try{
                    var fd=ctx.getImageData(0,0,canvas.width,canvas.height);
                    var qr2=jsQR(fd.data,fd.width,fd.height,{inversionAttempts:'attemptBoth'});
                    if(qr2&&qr2.data){ onDetected(qr2.data,'jsQR-full'); return; }
                }catch(e){}
            }
        }

        // == METODE 2: ZXing canvas (untuk Barcode CODE128/EAN) ==
        if(zxingDecoder&&frame%3===0){
            try{
                var lum=new ZXing.HTMLCanvasElementLuminanceSource(canvas);
                var bmp=new ZXing.BinaryBitmap(new ZXing.HybridBinarizer(lum));
                var res=zxingDecoder.decode(bmp);
                if(res&&res.getText()){ onDetected(res.getText(),'ZXing'); return; }
            }catch(e){}
        }

        if(frame%90===0) dbg('📷 '+frame+' frame diproses. Arahkan ke kotak hijau.');
    }, 80); // ~12fps
}

function onDetected(code,src){
    code=code.trim();
    if(!code) return;
    var now=Date.now();
    if(code===lastScanned&&now-lastScannedAt<2000) return;
    lastScanned=code; lastScannedAt=now;
    dbg('✅ ['+src+']: '+code);
    var f=document.getElementById('detectFlash');
    f.style.display='block'; setTimeout(function(){f.style.display='none';},300);
    processNIS(code);
}

function stopScan(){
    scanning=false;
    if(scanInterval){clearInterval(scanInterval);scanInterval=null;}
    if(videoStream){videoStream.getTracks().forEach(function(t){t.stop();}); videoStream=null;}
    videoTrack = null;
    document.getElementById('torchBtn').style.display = 'none';
    if(wakeLock !== null) { wakeLock.release().then(()=>wakeLock=null); }
    
    var v=document.getElementById('cameraVideo');
    v.srcObject=null; v.style.display='none';
    document.getElementById('scanPlaceholder').style.display='flex';
    document.getElementById('scanOverlay').style.display='none';
    document.getElementById('startBtn').style.display='';
    document.getElementById('stopBtn').style.display='none';
    dbg('');
}

// Fitur Senter
var torchState = false;
function toggleTorch() {
    if (videoTrack) {
        torchState = !torchState;
        videoTrack.applyConstraints({
            advanced: [{torch: torchState}]
        }).then(() => {
            var tb = document.getElementById('torchBtn');
            if(torchState) { tb.style.background = '#eab308'; tb.style.color = 'black'; }
            else { tb.style.background = 'rgba(0,0,0,0.6)'; tb.style.color = 'white'; }
        }).catch(e => dbg('Senter error: '+e.message));
    }
}

// --- USB Scanner ---
document.getElementById('usbInput').addEventListener('keydown',function(e){
    if(e.key==='Enter'){var v=this.value.trim();if(v){processNIS(v);this.value='';}}
});

// --- Manual NIS ---
document.getElementById('manualNis').addEventListener('keydown',function(e){
    if(e.key==='Enter'){processNIS(this.value.trim());this.value='';}
});

// --- Process NIS ---
async function processNIS(nis){
    if(!nis||nis.length<3) return;
    try{
        var resp=await fetch('ajax/absen_scan.php',{
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:'nis='+encodeURIComponent(nis)+'&jenis='+encodeURIComponent(jenisAbsen)
        });
        var data=await resp.json();
        showResult(data);
        if(data.success){ addToLog(data); beep(880,150); if(navigator.vibrate) navigator.vibrate([80,40,80]); }
        else { beep(300,400); if(navigator.vibrate) navigator.vibrate(400); }
    }catch(e){ showToast('Error koneksi','error'); beep(300,400); }
}

function dbg(msg){ var el=document.getElementById('debugInfo'); if(el) el.textContent=msg; }

function showResult(data){
    var card=document.getElementById('resultCard'), content=document.getElementById('resultContent');
    card.style.display='';
    if(!data.success){
        content.innerHTML='<div style="color:var(--danger)"><i class="fas fa-times-circle fa-3x"></i><h3 style="margin-top:12px">'+data.message+'</h3></div>';
        return;
    }
    var colors={Hadir:'#16a34a',Terlambat:'#d97706',Pulang:'#0891b2'};
    var icons={Hadir:'✅',Terlambat:'⏰',Pulang:'🏠'};
    var c=colors[data.status]||'#16a34a';
    content.innerHTML='<div style="color:'+c+'"><div style="font-size:3rem">'+(icons[data.status]||'✅')+'</div>'
        +'<h2 style="margin:10px 0 4px;color:#1e293b">'+data.nama+'</h2>'
        +'<p style="color:var(--text-muted);margin:0">'+data.nis+' | Kelas '+data.kelas+'</p>'
        +'<div style="font-size:1.5rem;font-weight:800;margin-top:10px;color:'+c+'">'+data.status+'</div>'
        +'<div style="font-size:1rem;color:var(--text-muted);margin-top:4px">'+data.jam+'</div></div>';
    showToast(data.nama+' — '+data.status, data.status==='Terlambat'?'warning':'success');
}

function addToLog(data){
    var feed=document.getElementById('logFeed');
    var emp=document.getElementById('logEmpty'); if(emp) emp.remove();
    var item=document.createElement('div'); item.className='log-item';
    item.innerHTML='<div class="log-avatar">'+data.nama.charAt(0).toUpperCase()+'</div>'
        +'<div class="log-info"><div class="log-name">'+data.nama+'</div><div class="log-detail">'+data.nis+' | '+data.kelas+'</div></div>'
        +'<div>'+badge(data.status)+'<div class="log-time">'+data.jam+'</div></div>';
    feed.insertBefore(item,feed.firstChild);
    var cnt=document.getElementById('logCount'); cnt.textContent=parseInt(cnt.textContent||0)+1;
}

function badge(s){
    var m={Hadir:'badge-hadir',Terlambat:'badge-terlambat',Pulang:'badge-izin',Alpa:'badge-alpa'};
    return '<span class="badge '+(m[s]||'')+'">'+s+'</span>';
}

async function loadTodayLog(){
    try{
        var resp=await fetch('ajax/get_log.php'); var data=await resp.json();
        if(data.length>0){
            document.getElementById('logEmpty')?.remove();
            data.forEach(function(d){
                var feed=document.getElementById('logFeed');
                var item=document.createElement('div'); item.className='log-item';
                item.innerHTML='<div class="log-avatar">'+d.nama.charAt(0).toUpperCase()+'</div>'
                    +'<div class="log-info"><div class="log-name">'+d.nama+'</div><div class="log-detail">'+d.nis+' | '+d.kelas+'</div></div>'
                    +'<div>'+badge(d.status)+'<div class="log-time">'+(d.jam_masuk?d.jam_masuk.slice(0,5):'-')+'</div></div>';
                feed.appendChild(item);
            });
            document.getElementById('logCount').textContent=data.length;
        }
    }catch(e){}
}
loadTodayLog();
window.addEventListener('beforeunload',stopScan);
</script>

<?php include 'includes/footer.php'; ?>
