<script>
    function loadPersentase() {
        document.getElementById('contentArea').innerHTML =
            '<div class="card">'
          + '<div class="card-header"><h2>📈 Persentase Kehadiran</h2></div>'
          + '<div class="card-body">'
          + '<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:15px;margin-bottom:20px;">'
          + '<div class="form-group"><label>Filter Periode:</label>'
          + '<input type="month" id="periodePersen" class="form-control" value="' + new Date().toISOString().substr(0,7) + '"></div>'
          + '<div class="form-group"><label>&nbsp;</label>'
          + '<button onclick="hitungPersentaseKehadiran()" class="btn btn-primary btn-block">📊 Hitung Persentase</button></div>'
          + '<div class="form-group"><label>&nbsp;</label>'
          + '<button onclick="exportToExcel(\'Absensi\')" class="btn btn-success btn-block">📥 Export Excel</button></div>'
          + '</div>'
          + '<div id="persentaseTable">'
          + '<div class="alert alert-info">Klik <strong>Hitung Persentase</strong> untuk melihat data.</div>'
          + '</div>'
          + '</div></div>'

          + '<div class="card mt-20">'
          + '<div class="card-header"><h2>📖 Keterangan Predikat</h2></div>'
          + '<div class="card-body">'
          + '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:15px;">'
          + '<div class="stat-card" style="border-left:4px solid #27ae60;"><div class="stat-info"><h3>95% – 100%</h3><p>🏆 Amat Baik</p></div></div>'
          + '<div class="stat-card" style="border-left:4px solid #3498db;"><div class="stat-info"><h3>75% – 94%</h3><p>👍 Baik</p></div></div>'
          + '<div class="stat-card" style="border-left:4px solid #f39c12;"><div class="stat-info"><h3>65% – 74%</h3><p>😐 Cukup</p></div></div>'
          + '<div class="stat-card" style="border-left:4px solid #e74c3c;"><div class="stat-info"><h3>&lt; 65%</h3><p>⚠️ Kurang</p></div></div>'
          + '</div></div></div>';

        // Auto hitung saat halaman dibuka
        hitungPersentaseKehadiran();
    }

    function hitungPersentaseKehadiran() {
        var periode = document.getElementById('periodePersen').value;
        var container = document.getElementById('persentaseTable');
        container.innerHTML = '<div class="loading"><div class="spinner"></div><p>Menghitung persentase...</p></div>';

        google.script.run
            .withSuccessHandler(function(data) {
                displayPersentaseTable(data, periode);
            })
            .withFailureHandler(function(err) {
                container.innerHTML =
                    '<div class="alert alert-error">❌ Gagal menghitung: ' + (err.message || 'Terjadi kesalahan') + '<br><br>'
                  + '<button onclick="hitungPersentaseKehadiran()" class="btn btn-primary">🔄 Coba Lagi</button></div>';
            })
            .hitungPersentase(periode);
    }

    function displayPersentaseTable(data, periode) {
        var container = document.getElementById('persentaseTable');

        if (!data || data.length === 0) {
            container.innerHTML = '<div class="alert alert-info">Tidak ada data untuk periode ' + formatMonthYear(periode) + '.</div>';
            return;
        }

        // Urutkan dari persentase tertinggi
        data.sort(function(a, b) { return parseFloat(b.persentase) - parseFloat(a.persentase); });

        var html = '<div class="alert alert-info"><strong>Periode:</strong> ' + formatMonthYear(periode) + '</div>'
                 + '<div class="table-container"><table>'
                 + '<thead><tr>'
                 + '<th>Peringkat</th><th>Nama Guru</th><th>Jabatan</th>'
                 + '<th>Hadir</th><th>Terlambat</th><th>Pulang Cepat</th>'
                 + '<th>Izin</th><th>Sakit</th><th>Piket</th>'
                 + '<th>Persentase</th><th>Predikat</th>'
                 + '</tr></thead><tbody>';

        data.forEach(function(item, index) {
            var pct   = parseFloat(item.persentase || 0);
            var pColor = getPersentaseColor(pct);
            var prdColor = getPredikatColor(item.predikat);

            html += '<tr>'
                  + '<td><strong>#' + (index + 1) + '</strong></td>'
                  + '<td><strong>' + (item.nama    || '-') + '</strong></td>'
                  + '<td>' + (item.jabatan  || '-') + '</td>'
                  + '<td style="text-align:center;">' + (item.hadir      || 0) + '</td>'
                  + '<td style="text-align:center;color:#f39c12;font-weight:bold;">' + (item.terlambat  || 0) + '</td>'
                  + '<td style="text-align:center;">' + (item.pulangCepat|| 0) + '</td>'
                  + '<td style="text-align:center;">' + (item.izin       || 0) + '</td>'
                  + '<td style="text-align:center;color:#e74c3c;">' + (item.sakit     || 0) + '</td>'
                  + '<td style="text-align:center;color:#27ae60;">' + (item.piket     || 0) + '</td>'
                  + '<td style="min-width:160px;">'
                  +   '<div style="display:flex;align-items:center;gap:8px;">'
                  +     '<div style="flex:1;background:#ecf0f1;border-radius:10px;height:18px;overflow:hidden;">'
                  +       '<div style="width:' + Math.min(pct,100) + '%;background:' + pColor + ';height:100%;border-radius:10px;transition:width 0.4s;"></div>'
                  +     '</div>'
                  +     '<strong style="color:' + pColor + ';white-space:nowrap;">' + pct + '%</strong>'
                  +   '</div>'
                  + '</td>'
                  + '<td><span class="badge" style="background:' + prdColor + ';color:white;font-weight:bold;">' + (item.predikat || '-') + '</span></td>'
                  + '</tr>';
        });

        html += '</tbody></table></div>';

        // Ringkasan
        var cAmatBaik = data.filter(function(d){ return d.predikat === 'Amat Baik'; }).length;
        var cBaik     = data.filter(function(d){ return d.predikat === 'Baik'; }).length;
        var cCukup    = data.filter(function(d){ return d.predikat === 'Cukup'; }).length;
        var cKurang   = data.filter(function(d){ return d.predikat === 'Kurang'; }).length;
        var avg       = data.length > 0
                      ? (data.reduce(function(s,d){ return s + parseFloat(d.persentase||0); }, 0) / data.length).toFixed(1)
                      : 0;

        html += '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:15px;margin-top:20px;">'
              + statCard('#27ae60', '🏆', cAmatBaik, 'Amat Baik')
              + statCard('#3498db', '👍', cBaik,     'Baik')
              + statCard('#f39c12', '😐', cCukup,    'Cukup')
              + statCard('#e74c3c', '⚠️', cKurang,   'Kurang')
              + statCard('#9b59b6', '📊', avg + '%', 'Rata-rata')
              + '</div>';

        container.innerHTML = html;
    }

    function statCard(color, icon, value, label) {
        return '<div class="stat-card" style="border-left:4px solid ' + color + ';">'
             + '<div class="stat-icon">' + icon + '</div>'
             + '<div class="stat-info"><h3>' + value + '</h3><p>' + label + '</p></div>'
             + '</div>';
    }

    function getPredikatColor(predikat) {
        switch (predikat) {
            case 'Amat Baik': return '#27ae60';
            case 'Baik':      return '#3498db';
            case 'Cukup':     return '#f39c12';
            case 'Kurang':    return '#e74c3c';
            default:          return '#95a5a6';
        }
    }

    function getPersentaseColor(persen) {
        if (persen >= 95) return '#27ae60';
        if (persen >= 75) return '#3498db';
        if (persen >= 65) return '#f39c12';
        return '#e74c3c';
    }

    function formatMonthYear(monthString) {
        if (!monthString || monthString === 'Semua') return 'Semua Periode';
        var parts = monthString.split('-');
        if (parts.length !== 2) return monthString;
        var months = ['Januari','Februari','Maret','April','Mei','Juni',
                      'Juli','Agustus','September','Oktober','November','Desember'];
        return months[parseInt(parts[1]) - 1] + ' ' + parts[0];
    }
</script>
