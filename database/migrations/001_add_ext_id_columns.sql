-- ============================================================
-- Migrasi: Tambah kolom ext_id untuk fitur sinkronisasi
-- Tanggal: 2026-08-09
-- ============================================================

-- Tambah kolom ext_id di tabel siswa (untuk link ke mandaapp)
ALTER TABLE `siswa` ADD COLUMN IF NOT EXISTS `ext_id` VARCHAR(36) DEFAULT NULL;

-- Tambah kolom ext_id di tabel kelas (untuk link ke mandaapp)
ALTER TABLE `kelas` ADD COLUMN IF NOT EXISTS `ext_id` VARCHAR(36) DEFAULT NULL;

-- Tambah kolom aktif di tabel siswa jika belum ada
ALTER TABLE `siswa` ADD COLUMN IF NOT EXISTS `aktif` TINYINT(1) DEFAULT 1;

-- Index untuk performa query sync
-- (IF NOT EXISTS tidak didukung di semua versi MariaDB untuk index,
--  jadi kita gunakan cara aman: abaikan error jika sudah ada)
ALTER TABLE `siswa` ADD UNIQUE INDEX `idx_siswa_ext_id` (`ext_id`);
ALTER TABLE `kelas` ADD UNIQUE INDEX `idx_kelas_ext_id` (`ext_id`);
