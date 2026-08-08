-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 23 Feb 2026 pada 07.10
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `absensi_sekolah`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `absensi`
--

CREATE TABLE `absensi` (
  `id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `nis` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `kelas` varchar(20) NOT NULL,
  `tanggal` date NOT NULL,
  `jam_masuk` time DEFAULT NULL,
  `jam_pulang` time DEFAULT NULL,
  `status` enum('Hadir','Terlambat','Alpa','Sakit','Izin','Bolos') DEFAULT 'Alpa',
  `keterangan` text DEFAULT NULL,
  `metode` enum('QR','Manual','Sistem') DEFAULT 'Manual',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`, `nama`, `created_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', '2026-02-23 03:01:13');

-- --------------------------------------------------------

--
-- Struktur dari tabel `catatan`
--

CREATE TABLE `catatan` (
  `id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `wali_id` int(11) DEFAULT NULL,
  `tipe` enum('Informasi','Peringatan','Urgent','Apresiasi') DEFAULT 'Informasi',
  `judul` varchar(200) NOT NULL,
  `isi` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengaturan`
--

CREATE TABLE `pengaturan` (
  `id` int(11) NOT NULL,
  `nama_sekolah` varchar(200) DEFAULT 'Nama Sekolah',
  `alamat` text DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `jam_masuk` time DEFAULT '07:00:00',
  `jam_terlambat` time DEFAULT '07:30:00',
  `jam_pulang` time DEFAULT '14:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pengaturan`
--

INSERT INTO `pengaturan` (`id`, `nama_sekolah`, `alamat`, `logo`, `jam_masuk`, `jam_terlambat`, `jam_pulang`) VALUES
(1, 'MAN 2 LOMBOK TIMUR', 'Jln Beririjarak Kecamatan Wanasaba', 'logo_1771815929.png', '06:30:00', '07:30:00', '13:00:00');

-- --------------------------------------------------------

--
-- Struktur dari tabel `rekap_bulanan`
--

CREATE TABLE `rekap_bulanan` (
  `id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `nis` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `kelas` varchar(20) NOT NULL,
  `bulan` int(2) NOT NULL,
  `tahun` int(4) NOT NULL,
  `hadir` int(3) DEFAULT 0,
  `terlambat` int(3) DEFAULT 0,
  `alpa` int(3) DEFAULT 0,
  `sakit` int(3) DEFAULT 0,
  `izin` int(3) DEFAULT 0,
  `bolos` int(3) DEFAULT 0,
  `total_hari` int(3) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `rekap_bulanan`
--

INSERT INTO `rekap_bulanan` (`id`, `siswa_id`, `nis`, `nama`, `kelas`, `bulan`, `tahun`, `hadir`, `terlambat`, `alpa`, `sakit`, `izin`, `bolos`, `total_hari`, `created_at`) VALUES
(1, 49, '45678', 'AHMAD HASAN ZARKASYI', 'X-A', 2, 2026, 1, 0, 0, 0, 0, 0, 1, '2026-02-23 06:09:26'),
(2, 50, '45679', 'AHMAD ZIYADUL KHAIR', 'X-A', 2, 2026, 1, 0, 0, 0, 0, 0, 1, '2026-02-23 06:09:26'),
(3, 51, '45680', 'ASHRAFI HILAL RAMDHAN', 'X-A', 2, 2026, 1, 0, 0, 0, 0, 0, 1, '2026-02-23 06:09:26'),
(4, 52, '45681', 'BAIQ NIKMATUS SHOLIHAH', 'X-A', 2, 2026, 1, 0, 0, 0, 0, 0, 1, '2026-02-23 06:09:26'),
(5, 53, '45682', 'BAIQ TITI MARGI UTAMI', 'X-A', 2, 2026, 1, 0, 0, 0, 0, 0, 1, '2026-02-23 06:09:26'),
(6, 54, '45683', 'BAIQ ZULYANA NUR AZIZI', 'X-A', 2, 2026, 1, 0, 0, 0, 0, 0, 1, '2026-02-23 06:09:26'),
(7, 55, '45684', 'DWI PRATIWI', 'X-A', 2, 2026, 0, 0, 1, 0, 0, 0, 1, '2026-02-23 06:09:26'),
(8, 56, '45685', 'ETY JULAENI RAHMANI', 'X-A', 2, 2026, 0, 0, 1, 0, 0, 0, 1, '2026-02-23 06:09:26'),
(9, 57, '45686', 'GHEA DEVINA NADIANTI', 'X-A', 2, 2026, 0, 0, 1, 0, 0, 0, 1, '2026-02-23 06:09:26'),
(10, 58, '45687', 'HASANATUN UMMAH', 'X-A', 2, 2026, 0, 0, 1, 0, 0, 0, 1, '2026-02-23 06:09:26'),
(11, 59, '45688', 'LALU ADE KURNIA JUNIAWAN', 'X-A', 2, 2026, 1, 0, 0, 0, 0, 0, 1, '2026-02-23 06:09:26'),
(12, 60, '45689', 'MUHAMMAD HAZIZURRAHMAN', 'X-A', 2, 2026, 1, 0, 0, 0, 0, 0, 1, '2026-02-23 06:09:26'),
(13, 61, '45690', 'MUHAMMAD ZUHRI HAQI', 'X-A', 2, 2026, 1, 0, 0, 0, 0, 0, 1, '2026-02-23 06:09:26'),
(14, 62, '45691', 'MULIANI SASFIRA', 'X-A', 2, 2026, 1, 0, 0, 0, 0, 0, 1, '2026-02-23 06:09:26'),
(15, 63, '45692', 'NADYA RAHAYU', 'X-A', 2, 2026, 1, 0, 0, 0, 0, 0, 1, '2026-02-23 06:09:26'),
(16, 64, '45693', 'NESAWATUL AUNI', 'X-A', 2, 2026, 1, 0, 0, 0, 0, 0, 1, '2026-02-23 06:09:26'),
(17, 65, '45694', 'RENDI DARMAWI', 'X-A', 2, 2026, 1, 0, 0, 0, 0, 0, 1, '2026-02-23 06:09:26'),
(18, 66, '45695', 'SISKA PRATIWI RAMADANI', 'X-A', 2, 2026, 1, 0, 0, 0, 0, 0, 1, '2026-02-23 06:09:26'),
(19, 67, '45696', 'TITIN NADIA AZURO', 'X-A', 2, 2026, 1, 0, 0, 0, 0, 0, 1, '2026-02-23 06:09:26'),
(20, 68, '45697', 'VIDA AMINATUL', 'X-A', 2, 2026, 1, 0, 0, 0, 0, 0, 1, '2026-02-23 06:09:26'),
(21, 69, '45698', 'YULIANA AYU JASMIN', 'X-A', 2, 2026, 1, 0, 0, 0, 0, 0, 1, '2026-02-23 06:09:26'),
(22, 70, '45699', 'ZARA&#039;A TAZKIA', 'X-A', 2, 2026, 1, 0, 0, 0, 0, 0, 1, '2026-02-23 06:09:26'),
(23, 71, '45706', 'ZULPIYAH', 'X-A', 2, 2026, 1, 0, 0, 0, 0, 0, 1, '2026-02-23 06:09:26'),
(24, 72, '55678', 'ANDRIAWAN HADI', 'X-B', 2, 2026, 1, 0, 0, 0, 0, 0, 1, '2026-02-23 06:09:26'),
(25, 73, '55679', 'ASMINI', 'X-B', 2, 2026, 1, 0, 0, 0, 0, 0, 1, '2026-02-23 06:09:26'),
(26, 74, '55680', 'BAIQ SHELVIA ENDRIYATI', 'X-B', 2, 2026, 1, 0, 0, 0, 0, 0, 1, '2026-02-23 06:09:26'),
(27, 75, '55681', 'KHAERINA AMELIA', 'X-B', 2, 2026, 1, 0, 0, 0, 0, 0, 1, '2026-02-23 06:09:26'),
(28, 76, '55682', 'LALU JUNANDI HARIRI', 'X-B', 2, 2026, 1, 0, 0, 0, 0, 0, 1, '2026-02-23 06:09:26'),
(29, 77, '55683', 'LALU UMAMURRIJA', 'X-B', 2, 2026, 1, 0, 0, 0, 0, 0, 1, '2026-02-23 06:09:26'),
(30, 78, '55684', 'LARA SASMITA', 'X-B', 2, 2026, 1, 0, 0, 0, 0, 0, 1, '2026-02-23 06:09:26'),
(31, 79, '55685', 'LIZATUL AINI', 'X-B', 2, 2026, 1, 0, 0, 0, 0, 0, 1, '2026-02-23 06:09:26'),
(32, 80, '55686', 'M. AGUS KURNIAWAN', 'X-B', 2, 2026, 1, 0, 0, 0, 0, 0, 1, '2026-02-23 06:09:26'),
(33, 81, '55687', 'M. WIJDAN GARDA SOFA', 'X-B', 2, 2026, 1, 0, 0, 0, 0, 0, 1, '2026-02-23 06:09:26'),
(34, 82, '55688', 'MHD. AFDHAL ALGIFARI', 'X-B', 2, 2026, 1, 0, 0, 0, 0, 0, 1, '2026-02-23 06:09:26'),
(35, 83, '55689', 'MIFTAHUL JANNAH', 'X-B', 2, 2026, 0, 1, 0, 0, 0, 0, 1, '2026-02-23 06:09:26'),
(36, 84, '55690', 'MUHAMMAD ALI ABDULLAH', 'X-B', 2, 2026, 1, 0, 0, 0, 0, 0, 1, '2026-02-23 06:09:26'),
(37, 85, '55691', 'MUHAMMAD DENDI APRIZI', 'X-B', 2, 2026, 0, 1, 0, 0, 0, 0, 1, '2026-02-23 06:09:26'),
(38, 86, '55692', 'NAUFAL FIRDAUS', 'X-B', 2, 2026, 0, 1, 0, 0, 0, 0, 1, '2026-02-23 06:09:26'),
(39, 87, '55693', 'NIA RAHMAYANTI', 'X-B', 2, 2026, 0, 1, 0, 0, 0, 0, 1, '2026-02-23 06:09:26'),
(40, 88, '55694', 'RAMDAN HAQIQI', 'X-B', 2, 2026, 1, 0, 0, 0, 0, 0, 1, '2026-02-23 06:09:26'),
(41, 89, '55695', 'RESTU NURMAN', 'X-B', 2, 2026, 1, 0, 0, 0, 0, 0, 1, '2026-02-23 06:09:26'),
(42, 90, '55696', 'TIKA ASTURI', 'X-B', 2, 2026, 1, 0, 0, 0, 0, 0, 1, '2026-02-23 06:09:26'),
(43, 91, '55697', 'ULFIA SAPITRI', 'X-B', 2, 2026, 1, 0, 0, 0, 0, 0, 1, '2026-02-23 06:09:26');

-- --------------------------------------------------------

--
-- Struktur dari tabel `siswa`
--

CREATE TABLE `siswa` (
  `id` int(11) NOT NULL,
  `nis` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `kelas` varchar(20) NOT NULL,
  `aktif` tinyint(1) DEFAULT 1,
  `foto` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `siswa`
--

INSERT INTO `siswa` (`id`, `nis`, `nama`, `kelas`, `aktif`, `foto`, `password`, `created_at`) VALUES
(49, '45678', 'AHMAD HASAN ZARKASYI', 'X-A', 1, NULL, '$2y$10$sA088zv5kYQMz0jvtwNcluf4JUDyTUuNhXBTxkOyRZLSDQp/wou02', '2026-02-23 05:07:32'),
(50, '45679', 'AHMAD ZIYADUL KHAIR', 'X-A', 1, NULL, '$2y$10$9SIM0SSYFEriU5jIfitNyecGL5x4CmYmbEc5s6CyS4aX95veAJONq', '2026-02-23 05:07:33'),
(51, '45680', 'ASHRAFI HILAL RAMDHAN', 'X-A', 1, NULL, '$2y$10$xC8l.99nvqaM7apYus6zMO.roVk3Y6OxHRm5tsPoCHNlPbzNCn8OW', '2026-02-23 05:07:33'),
(52, '45681', 'BAIQ NIKMATUS SHOLIHAH', 'X-A', 1, NULL, '$2y$10$PTsiYjiuEZIPHImiVh35ceqCmpHEsvCDkc2e4/qvi4YAbl5eDVuFi', '2026-02-23 05:07:33'),
(53, '45682', 'BAIQ TITI MARGI UTAMI', 'X-A', 1, NULL, '$2y$10$wLoC.SilOMGDI4KU3Iaqh.E/c4Xf5behGfDq/LR8.yZTdz.G6.RC2', '2026-02-23 05:07:33'),
(54, '45683', 'BAIQ ZULYANA NUR AZIZI', 'X-A', 1, NULL, '$2y$10$OSrMnCSC.6RCT422Y.UwNeL.2qZ8NNeFN4ac8hxzYwn.T/upcR2J6', '2026-02-23 05:07:33'),
(55, '45684', 'DWI PRATIWI', 'X-A', 1, NULL, '$2y$10$WXhGZYaksJhhanFyPFpfiO3OIruNUBXtqhBdyvSNWhlQnHsulkG4y', '2026-02-23 05:07:33'),
(56, '45685', 'ETY JULAENI RAHMANI', 'X-A', 1, NULL, '$2y$10$0j0HsYStR8eUxVmBNXCMGuAZXRsU6OJ7jYLmI0aKEEstDz7G06oiq', '2026-02-23 05:07:33'),
(57, '45686', 'GHEA DEVINA NADIANTI', 'X-A', 1, NULL, '$2y$10$zYNYgMivMPODI2w43rydoOd5paqEnYN24Wx8oA98l/XSP0aM5pScu', '2026-02-23 05:07:33'),
(58, '45687', 'HASANATUN UMMAH', 'X-A', 1, NULL, '$2y$10$PnUvbA6j6OtkxQnwCCPgIeQ5ybnwOVF2bJ2VmKh1iZhC/M97abL..', '2026-02-23 05:07:33'),
(59, '45688', 'LALU ADE KURNIA JUNIAWAN', 'X-A', 1, NULL, '$2y$10$JgzT2xp2lEY215CxhsgJvuCwL.CJn9/0.86fZzRmNmahUFvJNHiRy', '2026-02-23 05:07:33'),
(60, '45689', 'MUHAMMAD HAZIZURRAHMAN', 'X-A', 1, NULL, '$2y$10$tejbMNo0erIckW/SY0nk4.kaPKEbxy55DXTt52N1c7AGVbqz9qFs6', '2026-02-23 05:07:33'),
(61, '45690', 'MUHAMMAD ZUHRI HAQI', 'X-A', 1, NULL, '$2y$10$T.cEa/UM0XgFRyTIcQOHIOlH4IApnIpGMV2/mAj0H7ogvB9/JrTAG', '2026-02-23 05:07:33'),
(62, '45691', 'MULIANI SASFIRA', 'X-A', 1, NULL, '$2y$10$XDPv1fPaOrq62fHqj14.HuXSAZbMSXO2quKIu8Xg3Mm3XGABTx9oi', '2026-02-23 05:07:33'),
(63, '45692', 'NADYA RAHAYU', 'X-A', 1, NULL, '$2y$10$LTS8yPQ5wUTpvSDI9ylLs.6p7pNlyp0m6lmsgTi.4dsGOy3NvKqeC', '2026-02-23 05:07:33'),
(64, '45693', 'NESAWATUL AUNI', 'X-A', 1, NULL, '$2y$10$CmGnf6xGf/HC4TwlrvMRXuIQAB5iStS/axfiT2CFc0zbQHmackJ7C', '2026-02-23 05:07:34'),
(65, '45694', 'RENDI DARMAWI', 'X-A', 1, NULL, '$2y$10$EFCAvP1nIhkz7z..EJv0deLIrOivehpOdZzME50x3KcZZh7JHbnxG', '2026-02-23 05:07:34'),
(66, '45695', 'SISKA PRATIWI RAMADANI', 'X-A', 1, NULL, '$2y$10$eGP41vS3Ms67TKPRtvnaGuTqUa7YlzKDUJbg.fJYwariXGEB8eRSO', '2026-02-23 05:07:34'),
(67, '45696', 'TITIN NADIA AZURO', 'X-A', 1, NULL, '$2y$10$d8ra0huprYXlXQH7xoZ7KuDH4jdIYca3p4uG5zlKpUKrdfU3znxWq', '2026-02-23 05:07:34'),
(68, '45697', 'VIDA AMINATUL', 'X-A', 1, NULL, '$2y$10$N7066aHgYMvq4U7DSIRHiuhIdSgDpTz3FPvAc/kK9hICQJw4XwJIy', '2026-02-23 05:07:34'),
(69, '45698', 'YULIANA AYU JASMIN', 'X-A', 1, NULL, '$2y$10$BnvaAy7OU9FYiz7hF/P6a.5e2oC.ohaOjHaU3NEix8.GP6CH5XEPO', '2026-02-23 05:07:34'),
(70, '45699', 'ZARA&#039;A TAZKIA', 'X-A', 1, NULL, '$2y$10$fzcClKijjGx077mdP6Y5G.OWTVjUthSanXMqK1WjRFcsj0GErV1i2', '2026-02-23 05:07:34'),
(71, '45706', 'ZULPIYAH', 'X-A', 1, NULL, '$2y$10$J1ItmsZfMAg9grDYDiRRC.m/5K.7/6c0yTKlUMH74l6yWvSWnz3cm', '2026-02-23 05:07:34'),
(72, '55678', 'ANDRIAWAN HADI', 'X-B', 1, NULL, '$2y$10$QJUHIo6GhF02r8zEd5UbV.hAKSFMtELgjVAXjd96TwOp/q4DOUN82', '2026-02-23 05:09:01'),
(73, '55679', 'ASMINI', 'X-B', 1, NULL, '$2y$10$IbsQAKdeMVKi2UqipjP2ouUjYsEFTI57AqGhJv1o8Ra/rwPKCQvuO', '2026-02-23 05:09:01'),
(74, '55680', 'BAIQ SHELVIA ENDRIYATI', 'X-B', 1, NULL, '$2y$10$wZz0MbILjvi.Zjru6f7s2.cWdokyZ9RmF50Wi1I6/YwhdSvgmNqTi', '2026-02-23 05:09:01'),
(75, '55681', 'KHAERINA AMELIA', 'X-B', 1, NULL, '$2y$10$o3.NsLze7EnGmOQRARNYZ.SqA2khGkf1FTOyWSAtdUHR4qLfV0VZe', '2026-02-23 05:09:02'),
(76, '55682', 'LALU JUNANDI HARIRI', 'X-B', 1, NULL, '$2y$10$PDw1T5WSixLi6mw1FZPTweuI/ZO65YGqVAJu3LjSC4CjFF7yQZ.wy', '2026-02-23 05:09:02'),
(77, '55683', 'LALU UMAMURRIJA', 'X-B', 1, NULL, '$2y$10$QkjBad4k3hqWrQNzmWBVTO4nfCk4VwrIX/86IkHVn5Lqv7c1PZDYu', '2026-02-23 05:09:02'),
(78, '55684', 'LARA SASMITA', 'X-B', 1, NULL, '$2y$10$tZlgHcD/aD7XP4y.u2obDuhrY64z.5XYVDIqoK7XqIkDnu4xnVXcW', '2026-02-23 05:09:02'),
(79, '55685', 'LIZATUL AINI', 'X-B', 1, NULL, '$2y$10$t.bAdr2vg0uT/KKqzRKtuOGy.KYZmioxjCftXXowN7ynvz1Xsox1G', '2026-02-23 05:09:02'),
(80, '55686', 'M. AGUS KURNIAWAN', 'X-B', 1, NULL, '$2y$10$Kp0/6Kcq.KfSAEkZ4zEWEOS9X8Um2qutjqVyZglaJ/4WoQMn6CzbK', '2026-02-23 05:09:02'),
(81, '55687', 'M. WIJDAN GARDA SOFA', 'X-B', 1, NULL, '$2y$10$v0Xvfcl9BfK8WNhGeyuA8.f6.2499NmAt1Jcl30FVJfJOUiYUl.Ky', '2026-02-23 05:09:02'),
(82, '55688', 'MHD. AFDHAL ALGIFARI', 'X-B', 1, NULL, '$2y$10$6s0wXHDfcA6R4g4cQvL3juVjZip99s9B/WA612MNO4xHoMYJC1OYW', '2026-02-23 05:09:02'),
(83, '55689', 'MIFTAHUL JANNAH', 'X-B', 1, NULL, '$2y$10$SG.5YhJ72zcvUg.oZlF1VeAERzc8MnYxLrhZ3jJp7v7UEor/.ZS6i', '2026-02-23 05:09:03'),
(84, '55690', 'MUHAMMAD ALI ABDULLAH', 'X-B', 1, NULL, '$2y$10$9zTAak/UKiWylOaejMCQ0OxUuPHkFA9HkyTXNcbhZ4PPaR6mBdDSW', '2026-02-23 05:09:03'),
(85, '55691', 'MUHAMMAD DENDI APRIZI', 'X-B', 1, NULL, '$2y$10$4DK8KJyu/34TTmOG8EReKusLf5TZS/zVaCp5wUixPCw6DlSz8mmye', '2026-02-23 05:09:03'),
(86, '55692', 'NAUFAL FIRDAUS', 'X-B', 1, NULL, '$2y$10$sSsbE59BXburDf/Ipu9T0uaOrQBezYVDZtj4nHjhL0UnPDeGk/zcS', '2026-02-23 05:09:03'),
(87, '55693', 'NIA RAHMAYANTI', 'X-B', 1, NULL, '$2y$10$T5bUd6OzbMfW2CTe9IOXiukg4mD9mAzNP//9VLWGHn027unwPUNpu', '2026-02-23 05:09:03'),
(88, '55694', 'RAMDAN HAQIQI', 'X-B', 1, NULL, '$2y$10$IKmwiQNVWPxHWdyQs8NqjunMcAk6YQGRx9BawjrfJh3HMV5zNnMUG', '2026-02-23 05:09:03'),
(89, '55695', 'RESTU NURMAN', 'X-B', 1, NULL, '$2y$10$A.bwHYQQi2P0rHEkRS1pB.qVZzHirxoYNjpuagx6lmPzoF31aMugO', '2026-02-23 05:09:03'),
(90, '55696', 'TIKA ASTURI', 'X-B', 1, NULL, '$2y$10$p5Ihis8XpCEFTcOFBUIYXuJdzD02HA4c/2npzeI9aKxEHUXRbZ7VK', '2026-02-23 05:09:03'),
(91, '55697', 'ULFIA SAPITRI', 'X-B', 1, NULL, '$2y$10$UtchfF7cM6t/oNPQjViR7uybeHH6YlW7WGGzCv/5z9Bsr6OqabyyW', '2026-02-23 05:09:03');

-- --------------------------------------------------------

--
-- Struktur dari tabel `wali`
--

CREATE TABLE `wali` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `jabatan` varchar(100) DEFAULT 'Wali Kelas',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `wali`
--

INSERT INTO `wali` (`id`, `username`, `password`, `nama`, `jabatan`, `created_at`) VALUES
(3, 'wali_xd', '$2y$10$0t1zTwNW1F/of1kHr0K7COnv50OEsLeZKJNNsR9kM4/3L.OMG5z8G', 'egi', 'Wali Kelas', '2026-02-23 04:48:00');

-- --------------------------------------------------------

--
-- Struktur dari tabel `wali_siswa`
--

CREATE TABLE `wali_siswa` (
  `id` int(11) NOT NULL,
  `wali_id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `absensi`
--
ALTER TABLE `absensi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_siswa_tanggal` (`siswa_id`,`tanggal`),
  ADD KEY `idx_tanggal` (`tanggal`),
  ADD KEY `idx_kelas` (`kelas`),
  ADD KEY `idx_status` (`status`);

--
-- Indeks untuk tabel `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `catatan`
--
ALTER TABLE `catatan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_siswa` (`siswa_id`);

--
-- Indeks untuk tabel `pengaturan`
--
ALTER TABLE `pengaturan`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `rekap_bulanan`
--
ALTER TABLE `rekap_bulanan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_rekap` (`siswa_id`,`bulan`,`tahun`);

--
-- Indeks untuk tabel `siswa`
--
ALTER TABLE `siswa`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nis` (`nis`),
  ADD KEY `idx_nis` (`nis`),
  ADD KEY `idx_kelas` (`kelas`);

--
-- Indeks untuk tabel `wali`
--
ALTER TABLE `wali`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indeks untuk tabel `wali_siswa`
--
ALTER TABLE `wali_siswa`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_wali_siswa` (`wali_id`,`siswa_id`),
  ADD KEY `idx_wali` (`wali_id`),
  ADD KEY `idx_siswa` (`siswa_id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `absensi`
--
ALTER TABLE `absensi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT untuk tabel `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `catatan`
--
ALTER TABLE `catatan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `pengaturan`
--
ALTER TABLE `pengaturan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `rekap_bulanan`
--
ALTER TABLE `rekap_bulanan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT untuk tabel `siswa`
--
ALTER TABLE `siswa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=92;

--
-- AUTO_INCREMENT untuk tabel `wali`
--
ALTER TABLE `wali`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `wali_siswa`
--
ALTER TABLE `wali_siswa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
