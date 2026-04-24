# 🌐 PANDUAN LENGKAP HOSTING — Sistem Absensi Sekolah

---

## ✅ JAWABAN SINGKAT: BISA HOSTING!

File zip ini **bisa langsung di-hosting** di shared hosting biasa (Niagahoster, Rumahweb, IDwebhost, Hostinger, dll).

**Syarat hosting minimal:**
| Kebutuhan | Detail |
|---|---|
| PHP | Versi 7.4 atau lebih baru ✅ |
| Database | MySQL / MariaDB ✅ |
| Ekstensi PHP | `mysqli` (standar, sudah tersedia di semua hosting) |
| Library tambahan | ❌ Tidak ada (tidak pakai Composer/vendor) |
| Node.js / Python | ❌ Tidak dibutuhkan |

---

## 📋 LANGKAH INSTALASI DI HOSTING (cPanel)

### LANGKAH 1 — Upload File
1. Login ke **cPanel** hosting Anda
2. Buka **File Manager**
3. Masuk ke folder `public_html`
4. Klik **Upload** → pilih file `absensi_fixed.zip`
5. Setelah upload, klik kanan zip → **Extract**
6. Hasil ekstrak: folder `absensi_fixed/` muncul di dalam `public_html`
7. *(Opsional)* Rename folder ke `absensi` supaya URL lebih rapi

---

### LANGKAH 2 — Buat Database MySQL
1. Di cPanel → klik **MySQL Databases**
2. Di bagian "Create New Database": isi nama misal `absensi` → klik **Create Database**
3. Catat nama lengkap database (format: `namauser_absensi`)
4. Di bagian "MySQL Users": buat user baru, isi username & password → **Create User**
5. Di bagian "Add User To Database": pilih user & database → **Add** → centang **ALL PRIVILEGES** → **Make Changes**

---

### LANGKAH 3 — Import Database SQL
1. Di cPanel → buka **phpMyAdmin**
2. Pilih database yang baru dibuat (klik di panel kiri)
3. Klik tab **Import**
4. Klik **Choose File** → pilih file `install.sql` (ada di dalam folder absensi)
5. Klik **Go** / **Import**
6. Tunggu sampai muncul pesan sukses hijau ✅

---

### LANGKAH 4 — Edit File Konfigurasi ⚠️ WAJIB!

Buka **File Manager** cPanel → masuk ke folder absensi → buka file:
```
includes/config.php
```

Edit bagian ini:
```php
define('DB_HOST', 'localhost');                          // Biarkan localhost
define('DB_USER', 'namauser_usermysql');                // Sesuaikan!
define('DB_PASS', 'passwordmysqlAnda');                 // Sesuaikan!
define('DB_NAME', 'namauser_absensi');                  // Sesuaikan!
define('BASE_URL', '/absensi/');                        // Jika folder namanya "absensi"
// Jika diletakkan di ROOT (public_html langsung), ganti jadi:
// define('BASE_URL', '/');
```

> ⚠️ **Format DB_USER dan DB_NAME di shared hosting biasanya:**
> `cPanelUsername_namayang_anda_buat`
> Contoh: `sekolah_absensi` atau `budi123_dbabsen`

---

### LANGKAH 5 — Akses & Login
Buka browser → `https://domainanda.com/absensi`

**Login Admin:**
- Username: `admin`
- Password: `password`

**Portal Siswa & Wali:**
→ `https://domainanda.com/absensi/portal_login.php`

> ⚠️ **Segera ganti password admin** setelah login pertama!
> Masuk ke menu **Pengaturan** → Ganti Password

---

## ⚠️ CATATAN PENTING

### Fitur Scan Kamera (QR Code via HP)
Fitur scan menggunakan kamera HP **membutuhkan HTTPS**.
- Hosting berbayar biasanya sudah include SSL gratis (Let's Encrypt)
- Aktifkan SSL di cPanel → **SSL/TLS** atau **Let's Encrypt**
- Setelah SSL aktif, update `BASE_URL` ke `https://`

### Jika Pakai Hosting Gratis (000webhost, InfinityFree, dll)
- Fitur kamera **mungkin tidak bisa** karena tidak ada HTTPS
- Upload file dan database tetap bisa
- Akses absensi manual dan rekap tetap bisa

---

## 🗂️ STRUKTUR FOLDER SETELAH UPLOAD

```
public_html/
└── absensi/
    ├── includes/
    │   └── config.php  ← EDIT FILE INI
    ├── uploads/
    │   ├── foto/
    │   └── logo/
    ├── assets/
    ├── ajax/
    ├── install.sql     ← Import ke phpMyAdmin
    ├── dashboard.php
    ├── portal_login.php
    └── ... (file lainnya)
```

---

## 🆚 PERBEDAAN XAMPP vs HOSTING

| | XAMPP (Lokal) | Hosting |
|---|---|---|
| DB_USER | `root` | `namauser_dbuser` |
| DB_PASS | *(kosong)* | Password yang Anda buat |
| DB_NAME | `absensi_sekolah` | `namauser_absensi` |
| BASE_URL | `/absensi/` | `/absensi/` atau `/` |
| HTTPS | Tidak perlu | Direkomendasikan |

---

## 🔧 TROUBLESHOOTING

**❌ "Koneksi Database Gagal"**
→ Cek DB_USER, DB_PASS, DB_NAME di `config.php` sudah benar

**❌ Halaman kosong / error 500**
→ PHP version kurang dari 7.4. Ganti versi PHP di cPanel → **PHP Selector** atau **MultiPHP Manager**

**❌ Login admin tidak bisa / redirect terus**
→ BASE_URL salah. Sesuaikan dengan nama folder yang Anda pakai

**❌ Upload foto gagal**
→ Folder `uploads/foto/` belum ada permission write. Di File Manager, klik kanan folder `uploads` → **Permissions** → ubah ke `755`

**❌ Scan kamera tidak muncul**
→ Aktifkan SSL/HTTPS di hosting, lalu akses lewat `https://`
