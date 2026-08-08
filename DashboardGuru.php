<!DOCTYPE html>
<html>
<head><base target="_top"></head>
<body>
<script>
    // =============================================
    // DASHBOARD GURU - FIXED VERSION
    // =============================================
    function loadDashboardGuru() {
        var guruNama = sessionStorage.getItem('guruNama') || 'Guru';

        document.getElementById('contentArea').innerHTML =
            '<div style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;padding:30px;border-radius:15px;margin-bottom:30px;">'
          +   '<h1 style="margin:0 0 10px 0;">👋 Selamat Datang, ' + guruNama + '!</h1>'
          +   '<p style="margin:0;opacity:0.9;">Dashboard Guru - Sistem Absensi</p>'
          + '</div>'
          + '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:20px;margin-bottom:30px;">'
          +   '<div class="stat-card" onclick="navTo(\'mengajar\')" style="cursor:pointer;">'
          +     '<div class="stat-icon" style="font-size:48px;">📚</div>'
          +     '<div class="stat-info"><h3>Input Jam Mengajar</h3><p>Catat kehadiran mengajar Anda</p></div>'
          +   '</div>'
          +   '<div class="stat-card" onclick="navTo(\'piket\')" style="cursor:pointer;">'
          +     '<div class="stat-icon" style="font-size:48px;">🛡️</div>'
          +     '<div class="stat-info"><h3>Input Piket</h3><p>Catat tugas piket Anda</p></div>'
          +   '</div>'
          +   '<div class="stat-card" onclick="navTo(\'riwayat\')" style="cursor:pointer;">'
          +     '<div class="stat-icon" style="font-size:48px;">📊</div>'
          +     '<div class="stat-info"><h3>Riwayat</h3><p>Lihat riwayat kehadiran</p></div>'
          +   '</div>'
          + '</div>'
          + '<div class="card">'
          +   '<div class="card-header"><h2>📋 Aktivitas Hari Ini</h2></div>'
          +   '<div class="card-body">'
          +     '<div id="aktivitasHariIni"><div class="loading"><div class="spinner"></div><p>Memuat data...</p></div></div>'
          +   '</div>'
          + '</div>';

        loadAktivitasHariIni();
    }

    function loadAktivitasHariIni() {
        var guruId = sessionStorage.getItem('guruId');
        google.script.run
            .withSuccessHandler(function(result) {
                // FIXED: cek null/undefined sebelum akses .jamMengajar
                if (result && result.jamMengajar !== undefined) {
                    displayAktivitasHariIni(result.jamMengajar, result.piket);
                } else {
                    displayAktivitasHariIni([], { hasPiket: false });
                }
            })
            .withFailureHandler(function(err) {
                var el = document.getElementById('aktivitasHariIni');
                if (el) el.innerHTML = '<div class="alert alert-error">❌ Gagal memuat aktivitas: ' + (err.message || 'Terjadi kesalahan') + '</div>';
            })
            .getAktivitasGuruHariIni(guruId);
    }

    function displayAktivitasHariIni(jamMengajar, piket) {
        var el = document.getElementById('aktivitasHariIni');
        if (!el) return;

        // FIXED: jaga null/undefined
        var jm = jamMengajar || [];
        var pk = piket || { hasPiket: false };

        var html = '<div style="display:grid;gap:20px;">';

        // -- Jam Mengajar --
        html += '<div><h3 style="margin-bottom:15px;color:#2c3e50;">📚 Jam Mengajar Hari Ini (' + jm.length + ' jam)</h3>';
        if (jm.length > 0) {
            html += '<div class="table-container"><table><thead><tr><th>Jam Ke</th><th>Kelas</th><th>Mapel</th><th>Waktu Input</th></tr></thead><tbody>';
            jm.forEach(function(item) {
                html += '<tr>'
                      + '<td><strong>Jam ' + (item.jamKe || '-') + '</strong></td>'
                      + '<td>' + (item.kelas || '-') + '</td>'
                      + '<td>' + (item.mapel || '-') + '</td>'
                      + '<td>' + (item.waktu || '-') + '</td>'
                      + '</tr>';
            });
            html += '</tbody></table></div>';
        } else {
            html += '<div class="alert alert-info">Belum ada jam mengajar yang diinput hari ini.</div>';
        }
        html += '</div>';

        // -- Piket --
        html += '<div><h3 style="margin-bottom:15px;color:#2c3e50;">🛡️ Status Piket Hari Ini</h3>';
        if (pk.hasPiket) {
            html += '<div class="alert alert-success">'
                  + '✅ <strong>Piket sudah diinput</strong><br>'
                  + 'Waktu: ' + (pk.waktu || '-') + '<br>'
                  + (pk.keterangan ? 'Keterangan: ' + pk.keterangan : '')
                  + '</div>';
        } else {
            html += '<div class="alert alert-warning">'
                  + '⚠️ Piket belum diinput hari ini.'
                  + '<button onclick="navTo(\'piket\')" class="btn btn-primary" style="margin-left:10px;padding:6px 14px;">Input Piket</button>'
                  + '</div>';
        }
        html += '</div></div>';

        el.innerHTML = html;
    }

    // =============================================
    // INPUT JAM MENGAJAR - FIXED
    // =============================================
    function showInputJamMengajar() {
        var guruNama = sessionStorage.getItem('guruNama') || 'Guru';

        document.getElementById('contentArea').innerHTML =
            '<div class="card">'
          + '<div class="card-header"><h2>📚 Input Jam Mengajar</h2></div>'
          + '<div class="card-body">'
          + '<form id="formJamMengajar" onsubmit="submitJamMengajar(event)">'
          + '<div class="form-group"><label>Nama Guru</label>'
          + '<input type="text" value="' + guruNama + '" readonly class="form-control"></div>'
          + '<div class="form-group"><label>Kelas <span style="color:red;">*</span></label>'
          + '<select id="kelasGuru" required class="form-control">'
          + '<option value="">-- Memuat daftar kelas... --</option>'
          + '</select></div>'
          + '<div class="form-group"><label>Mata Pelajaran <span style="color:red;">*</span></label>'
          + '<input type="text" id="mapelGuru" required class="form-control" placeholder="Contoh: Matematika"></div>'
          + '<div class="form-group"><label>Jam Ke <span style="color:red;">*</span></label>'
          + '<select id="jamKeGuru" required class="form-control">'
          + '<option value="">-- Pilih Jam --</option>'
          + [1,2,3,4,5,6,7,8,9,10].map(function(n){ return '<option value="'+n+'">Jam '+n+'</option>'; }).join('')
          + '</select></div>'
          + '<div style="display:flex;gap:10px;margin-top:20px;">'
          + '<button type="submit" class="btn btn-primary">💾 Simpan</button>'
          + '<button type="button" onclick="navTo(\'dashboard\')" class="btn btn-secondary">❌ Batal</button>'
          + '</div></form></div></div>';

        // FIXED: Load kelas dari server, dengan fallback
        google.script.run
            .withSuccessHandler(function(list) {
                var sel = document.getElementById('kelasGuru');
                if (!sel) return;
                var fallback = ['X-1','X-2','X-3','X-4','X-5',
                                'XI-IPA-1','XI-IPA-2','XI-IPA-3','XI-IPS-1','XI-IPS-2','XI-IPS-3',
                                'XII-IPA-1','XII-IPA-2','XII-IPA-3','XII-IPS-1','XII-IPS-2','XII-IPS-3'];
                var kelas = (list && list.length > 0) ? list : fallback;
                sel.innerHTML = '<option value="">-- Pilih Kelas --</option>';
                kelas.forEach(function(k) {
                    var o = document.createElement('option');
                    o.value = k; o.textContent = k;
                    sel.appendChild(o);
                });
            })
            .withFailureHandler(function() {
                // Jika gagal load server, pakai hardcoded fallback
                var sel = document.getElementById('kelasGuru');
                if (!sel) return;
                var fallback = ['X-1','X-2','X-3','X-4','X-5',
                                'XI-IPA-1','XI-IPA-2','XI-IPA-3','XI-IPS-1','XI-IPS-2','XI-IPS-3',
                                'XII-IPA-1','XII-IPA-2','XII-IPA-3','XII-IPS-1','XII-IPS-2','XII-IPS-3'];
                sel.innerHTML = '<option value="">-- Pilih Kelas --</option>';
                fallback.forEach(function(k) {
                    var o = document.createElement('option');
                    o.value = k; o.textContent = k;
                    sel.appendChild(o);
                });
            })
            .getKelasList();
    }

    function submitJamMengajar(event) {
        event.preventDefault();

        var kelas = document.getElementById('kelasGuru') ? document.getElementById('kelasGuru').value : '';
        if (!kelas) { alert('❌ Pilih kelas terlebih dahulu!'); return; }

        var data = {
            guruId: sessionStorage.getItem('guruId'),
            nama:   sessionStorage.getItem('guruNama'),
            kelas:  kelas,
            mapel:  document.getElementById('mapelGuru').value,
            jamKe:  document.getElementById('jamKeGuru').value
        };

        var btn = event.target.querySelector('button[type=submit]');
        if (btn) { btn.disabled = true; btn.textContent = '⏳ Menyimpan...'; }

        google.script.run
            .withSuccessHandler(function(result) {
                if (btn) { btn.disabled = false; btn.textContent = '💾 Simpan'; }
                if (result && result.success) {
                    alert('✅ ' + (result.message || 'Jam mengajar berhasil disimpan'));
                    setTimeout(function() { navTo('dashboard'); }, 800);
                } else {
                    alert('❌ ' + ((result && result.message) || 'Gagal menyimpan'));
                }
            })
            .withFailureHandler(function(err) {
                if (btn) { btn.disabled = false; btn.textContent = '💾 Simpan'; }
                alert('❌ Error: ' + (err.message || err));
            })
            .saveJamMengajarGuru(data);
    }

    // =============================================
    // INPUT PIKET - FIXED
    // =============================================
    function showInputPiket() {
        var guruNama = sessionStorage.getItem('guruNama') || 'Guru';

        document.getElementById('contentArea').innerHTML =
            '<div class="card">'
          + '<div class="card-header"><h2>🛡️ Input Piket</h2></div>'
          + '<div class="card-body">'
          + '<form id="formPiket" onsubmit="submitPiket(event)">'
          + '<div class="form-group"><label>Nama Guru</label>'
          + '<input type="text" value="' + guruNama + '" readonly class="form-control"></div>'
          + '<div class="form-group"><label>Keterangan (Opsional)</label>'
          + '<textarea id="keteranganPiket" class="form-control" rows="3" placeholder="Keterangan tambahan..."></textarea></div>'
          + '<div style="display:flex;gap:10px;margin-top:20px;">'
          + '<button type="submit" class="btn btn-primary">💾 Simpan Piket</button>'
          + '<button type="button" onclick="navTo(\'dashboard\')" class="btn btn-secondary">❌ Batal</button>'
          + '</div></form></div></div>';
    }

    function submitPiket(event) {
        event.preventDefault();

        var data = {
            guruId:     sessionStorage.getItem('guruId'),
            nama:       sessionStorage.getItem('guruNama'),
            keterangan: document.getElementById('keteranganPiket').value
        };

        var btn = event.target.querySelector('button[type=submit]');
        if (btn) { btn.disabled = true; btn.textContent = '⏳ Menyimpan...'; }

        google.script.run
            .withSuccessHandler(function(result) {
                if (btn) { btn.disabled = false; btn.textContent = '💾 Simpan Piket'; }
                if (result && result.success) {
                    alert('✅ ' + (result.message || 'Piket berhasil disimpan'));
                    setTimeout(function() { navTo('dashboard'); }, 800);
                } else {
                    alert('❌ ' + ((result && result.message) || 'Gagal menyimpan'));
                }
            })
            .withFailureHandler(function(err) {
                if (btn) { btn.disabled = false; btn.textContent = '💾 Simpan Piket'; }
                alert('❌ Error: ' + (err.message || err));
            })
            .saveAbsensiPiketGuru(data);
    }

    // =============================================
    // RIWAYAT GURU - FIXED (bukan placeholder lagi)
    // =============================================
    function showRiwayatGuru() {
        var guruId   = sessionStorage.getItem('guruId');
        var guruNama = sessionStorage.getItem('guruNama') || 'Guru';

        document.getElementById('contentArea').innerHTML =
            '<div class="card">'
          + '<div class="card-header"><h2>📊 Riwayat Kehadiran - ' + guruNama + '</h2></div>'
          + '<div class="card-body">'
          + '<div id="riwayatContent"><div class="loading"><div class="spinner"></div><p>Memuat riwayat...</p></div></div>'
          + '</div></div>';

        google.script.run
            .withSuccessHandler(function(result) { displayRiwayatGuru(result); })
            .withFailureHandler(function(err) {
                var el = document.getElementById('riwayatContent');
                if (el) el.innerHTML = '<div class="alert alert-error">❌ Gagal memuat riwayat: ' + (err.message || 'Terjadi kesalahan') + '</div>';
            })
            .getRiwayatGuru(guruId);
    }

    function displayRiwayatGuru(data) {
        var el = document.getElementById('riwayatContent');
        if (!el) return;

        // FIXED: bukan placeholder lagi, tampilkan data nyata
        if (!data) {
            el.innerHTML = '<div class="alert alert-info">ℹ️ Belum ada data riwayat.</div>';
            return;
        }

        var html = '<h3 style="margin-bottom:12px;color:#2c3e50;">📚 Riwayat Jam Mengajar</h3>';
        if (data.jamMengajar && data.jamMengajar.length > 0) {
            html += '<div class="table-container"><table>'
                  + '<thead><tr><th>Tanggal</th><th>Kelas</th><th>Mapel</th><th>Jam Ke</th><th>Waktu</th></tr></thead>'
                  + '<tbody>';
            data.jamMengajar.forEach(function(item) {
                html += '<tr>'
                      + '<td>' + (item.tanggal || '-') + '</td>'
                      + '<td>' + (item.kelas   || '-') + '</td>'
                      + '<td>' + (item.mapel   || '-') + '</td>'
                      + '<td>Jam ' + (item.jamKe || '-') + '</td>'
                      + '<td>' + (item.waktu   || '-') + '</td>'
                      + '</tr>';
            });
            html += '</tbody></table></div>';
        } else {
            html += '<div class="alert alert-info">Belum ada data jam mengajar.</div>';
        }

        html += '<br><h3 style="margin-bottom:12px;color:#2c3e50;">🛡️ Riwayat Piket</h3>';
        if (data.piket && data.piket.length > 0) {
            html += '<div class="table-container"><table>'
                  + '<thead><tr><th>Tanggal</th><th>Waktu</th><th>Keterangan</th></tr></thead>'
                  + '<tbody>';
            data.piket.forEach(function(item) {
                html += '<tr>'
                      + '<td>' + (item.tanggal    || '-') + '</td>'
                      + '<td>' + (item.waktu      || '-') + '</td>'
                      + '<td>' + (item.keterangan || '-') + '</td>'
                      + '</tr>';
            });
            html += '</tbody></table></div>';
        } else {
            html += '<div class="alert alert-info">Belum ada data piket.</div>';
        }

        el.innerHTML = html;
    }
</script>
</body>
</html>
