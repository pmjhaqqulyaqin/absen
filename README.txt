# SISTEM ABSENSI SEKOLAH DIGITAL
## Panduan Instalasi XAMPP

---

## 🔧 PERSYARATAN SISTEM
- XAMPP (Apache + MySQL + PHP 7.4+)
- Browser modern (Chrome/Firefox/Edge)
- USB Barcode Scanner (opsional)
- Webcam/Kamera HP (opsional)

---

## 📦 CARA INSTALASI XAMPP (Lokal)

### Langkah 1: Copy Files
```
Salin folder `absensi` ke:
C:\xampp\htdocs\absensi\
```

### Langkah 2: Buat Database
1. Buka browser → http://localhost/phpmyadmin
2. Klik "New" (buat database baru)
3. Nama database: `absensi_sekolah`
4. Klik "Create"
5. Klik tab "Import"
6. Pilih file `install.sql`
7. Klik "Go"

### Langkah 3: Konfigurasi
Edit file `includes/config.php`:
```php
define('DB_HOST', 'localhost');  // Biasanya localhost
define('DB_USER', 'root');       // Username MySQL
define('DB_PASS', '');           // Password MySQL (kosong di XAMPP default)
define('DB_NAME', 'absensi_sekolah');
define('BASE_URL', '/absensi/'); // Sesuaikan jika nama folder berbeda
```

### Langkah 4: Akses Aplikasi
Buka browser → http://localhost/absensi

### Langkah 5: Login
- Username: `admin`
- Password: `password`

⚠️ **GANTI PASSWORD SEGERA** setelah login pertama!

---

## 🌐 CARA HOSTING (cPanel/Shared Hosting)

### Langkah 1: Upload Files
1. Login ke cPanel → File Manager
2. Masuk ke folder `public_html` (atau `www`)
3. Buat folder baru: `absensi`
4. Upload semua file ke dalam folder tersebut

### Langkah 2: Buat Database
1. Di cPanel → MySQL Databases
2. Buat database baru (catat nama lengkapnya)
3. Buat user MySQL baru
4. Tambahkan user ke database (ALL PRIVILEGES)

### Langkah 3: Import SQL
1. Di cPanel → phpMyAdmin
2. Pilih database yang baru dibuat
3. Import file `install.sql`

### Langkah 4: Edit Config
Edit `includes/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'cpanelusername_dbuser');  // Format: namauser_namadbuser
define('DB_PASS', 'passworddbuser');
define('DB_NAME', 'cpanelusername_absensi_sekolah');
define('BASE_URL', '/absensi/');  // atau '/' jika di root
```

### Langkah 5: Akses
Buka: https://domainanda.com/absensi

---

## 📱 FITUR SCAN BARCODE

### Metode 1: Kamera HP/Tablet
1. Buka URL sistem di browser HP
2. Pergi ke menu "Scan Barcode"
3. Klik "Mulai Scan"
4. Izinkan akses kamera
5. Arahkan kamera ke barcode kartu siswa

**Note:** Memerlukan HTTPS untuk akses kamera dari HP. Di localhost gunakan Chrome.

### Metode 2: Webcam PC/Laptop
1. Buka browser di PC
2. Pergi ke "Scan Barcode"
3. Pilih kamera yang tersedia
4. Klik "Mulai Scan"

### Metode 3: USB Barcode Scanner
1. Hubungkan USB scanner ke komputer
2. Buka tab "USB Scanner"
3. Klik pada field input
4. Scan barcode → otomatis submit

---

## 📊 GENERATE BARCODE SISWA

1. Pergi ke "Data Siswa"
2. Klik "Generate Barcode"
3. Pilih format (CODE-128 atau QR Code)
4. Klik "Cetak" untuk mencetak kartu

---

## 📁 STRUKTUR FOLDER

```
absensi/
├── index.php          → Redirect ke login
├── login.php          → Halaman login
├── dashboard.php      → Dashboard utama
├── scan.php           → Halaman scan barcode
├── manual.php         → Absen manual
├── siswa.php          → Kelola data siswa
├── barcode_generate.php → Generate barcode
├── rekap_harian.php   → Rekap harian
├── rekap_bulanan.php  → Rekap bulanan
├── belum_absen.php    → Siswa belum absen
├── terlambat.php      → Siswa terlambat
├── rekap_status.php   → Rekap status
├── grafik.php         → Grafik analitik
├── hapus_log.php      → Hapus log + backup
├── pengaturan.php     → Pengaturan sekolah
├── pengaturan_waktu.php → Pengaturan waktu
├── logout.php
├── install.sql        → File database
├── includes/
│   ├── config.php     ← EDIT INI!
│   ├── header.php
│   └── footer.php
├── assets/
│   ├── css/style.css
│   └── js/app.js
├── ajax/              → API endpoints
└── uploads/           → Foto & logo
    ├── foto/
    ├── logo/
    └── excel/
```

---

## 🔐 KEAMANAN

- Ganti password default admin segera
- Aktifkan HTTPS untuk akses kamera
- Folder uploads dilindungi dari eksekusi PHP
- Session-based authentication
- SQL injection prevention (prepared statements)

---

## 📞 TROUBLESHOOTING

**Q: Database tidak bisa connect?**
A: Pastikan XAMPP running, cek config.php DB_HOST/USER/PASS/NAME

**Q: Kamera tidak bisa diakses di HP?**
A: Butuh HTTPS. Gunakan ngrok atau hosting dengan SSL untuk akses dari HP.

**Q: Barcode tidak terdeteksi?**
A: Pastikan barcode jelas dan cukup cahaya. Gunakan USB scanner untuk lebih akurat.

**Q: Import Excel gagal?**
A: Sistem menggunakan CSV. Buka Excel → Save As → CSV (Comma delimited). Pastikan kolom: NIS, Nama, Kelas

---

## 📧 KUSTOMISASI

Untuk menambah kelas baru, cukup tambahkan siswa dengan nama kelas baru saat tambah/import data siswa.

Format kelas bebas: X-A, XI-IPA-1, XII-MIPA-2, dll.
