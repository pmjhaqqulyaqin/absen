# PANDUAN INSTALASI PATCH PERBAIKAN ABSENSI v2
================================================

## Apa yang Diperbaiki?

### ✅ 1. Kamera Scan (scan.php)
- **Fix kamera hitam** - Menggunakan library ZXing (lebih stabil dari library lama)
- Otomatis minta izin kamera di browser
- Pilihan kamera (jika ada beberapa kamera / HP + webcam)
- Prioritas kamera belakang (untuk HP)
- Overlay area scan berbentuk kotak hijau
- Suara beep saat scan berhasil/gagal
- Input manual sebagai backup jika scan tidak bisa

### ✅ 2. Absen Pulang di Halaman Scan (scan.php)
- Toggle switch **Absen Masuk / Absen Pulang** di bagian atas
- Log tabel menampilkan kolom **Jam Pulang**
- Validasi: tidak bisa absen pulang jika belum absen masuk
- Validasi: tidak bisa absen pulang dua kali

### ✅ 3. Absen Pulang di Input Manual (manual.php)
- Toggle **Absen Masuk / Absen Pulang**
- Tampilkan status jam masuk dan jam pulang per siswa
- Checklist per siswa untuk simpan massal

### ✅ 4. Dashboard (dashboard.php)
- Kartu baru **"Sudah Pulang"** (ungu) di baris statistik
- Section **Hapus Data Siswa** dengan checkbox centang
  - Filter per kelas
  - Pilih semua / hapus pilihan
  - Konfirmasi sebelum hapus
  - Hapus siswa + data absensinya sekaligus

---

## Cara Install

### Step 1: Backup dulu!
```bash
cp -r absensi_v2/ absensi_v2_backup/
```

### Step 2: Jalankan SQL migrasi
Di phpMyAdmin, buka tab **SQL** dan jalankan isi file:
```
migrasi_jam_pulang.sql
```

### Step 3: Replace file-file berikut
Salin file dari folder patch ini ke folder absensi_v2 Anda:

| File Patch | Tujuan | Keterangan |
|-----------|--------|-----------|
| `scan.php` | `/absensi_v2/scan.php` | REPLACE (ganti total) |
| `dashboard.php` | `/absensi_v2/dashboard.php` | REPLACE (ganti total) |
| `manual.php` | `/absensi_v2/manual.php` | REPLACE (ganti total) |
| `ajax/absen_scan.php` | `/absensi_v2/ajax/absen_scan.php` | REPLACE |
| `ajax/absen_manual_cepat.php` | `/absensi_v2/ajax/absen_manual_cepat.php` | REPLACE |
| `ajax/get_siswa_absen.php` | `/absensi_v2/ajax/get_siswa_absen.php` | FILE BARU |
| `ajax/hapus_siswa.php` | `/absensi_v2/ajax/hapus_siswa.php` | FILE BARU |

### Step 4: Test kamera
- Buka `scan.php` di browser
- Klik **"Mulai Kamera"**
- Jika minta izin → klik **"Izinkan"**
- **Penting**: Harus pakai HTTPS atau localhost (kamera tidak jalan di HTTP biasa)

---

## Syarat Kamera Bisa Jalan

| Kondisi | Kamera Bisa? |
|---------|-------------|
| localhost (XAMPP lokal) | ✅ Ya |
| https://domain.com | ✅ Ya |
| http://192.168.x.x (LAN) | ❌ Tidak (HTTP biasa) |
| http://domain.com | ❌ Tidak |

**Solusi untuk LAN (HP scan di jaringan lokal):**
- Gunakan XAMPP dengan SSL, atau
- Pakai ngrok untuk tunnel HTTPS, atau
- Input manual sebagai alternatif

---

## Troubleshooting

**Kamera tetap hitam setelah klik Mulai:**
1. Cek apakah browser minta izin kamera (ada popup?) → klik Izinkan
2. Cek apakah kamera dipakai aplikasi lain (Zoom, Teams, dll)
3. Coba pilih kamera lain di dropdown
4. Pastikan pakai HTTPS atau localhost

**Scan tidak terdeteksi:**
- Pastikan barcode/QR cukup terang dan jelas
- Jarak optimal: 15-30 cm dari kamera
- Gunakan input manual jika barcode rusak/tidak bisa scan

**Absen pulang tidak tersimpan:**
- Pastikan siswa sudah absen masuk dulu
- Cek kolom `jam_pulang` sudah ada di tabel (jalankan SQL migrasi)
