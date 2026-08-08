-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Aug 08, 2026 at 05:16 PM
-- Server version: 10.11.17-MariaDB-cll-lve
-- PHP Version: 8.4.24

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `login276_absensi`
--

-- --------------------------------------------------------

--
-- Table structure for table `absensi`
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `tahun_ajaran` varchar(20) NOT NULL DEFAULT '',
  `semester` varchar(10) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `absensi`
--

INSERT INTO `absensi` (`id`, `siswa_id`, `nis`, `nama`, `kelas`, `tanggal`, `jam_masuk`, `jam_pulang`, `status`, `keterangan`, `metode`, `created_at`, `updated_at`, `tahun_ajaran`, `semester`) VALUES
(3592, 226, '2027010', 'M. ARSANI HIDAYATULLAH', 'XI-1', '2026-08-08', '16:16:53', NULL, 'Terlambat', '', 'Manual', '2026-08-08 09:16:53', '2026-08-08 09:16:53', '2026/2027', 'Ganjil'),
(3593, 227, '2027011', 'M. RIKI GAZALI', 'XI-1', '2026-08-08', '16:16:53', NULL, 'Terlambat', '', 'Manual', '2026-08-08 09:16:53', '2026-08-08 09:16:53', '2026/2027', 'Ganjil'),
(3594, 228, '2027012', 'M.WIJDAN GARDA SOFA', 'XI-1', '2026-08-08', '16:16:53', NULL, 'Terlambat', '', 'Manual', '2026-08-08 09:16:53', '2026-08-08 09:16:53', '2026/2027', 'Ganjil'),
(3595, 229, '2027013', 'MHD. AFDHAL AL-GIFARI', 'XI-1', '2026-08-08', '16:16:53', NULL, 'Terlambat', '', 'Manual', '2026-08-08 09:16:53', '2026-08-08 09:16:53', '2026/2027', 'Ganjil'),
(3596, 230, '2027014', 'MUH.LUTFI AKBAR', 'XI-1', '2026-08-08', '16:16:53', NULL, 'Terlambat', '', 'Manual', '2026-08-08 09:16:53', '2026-08-08 09:16:53', '2026/2027', 'Ganjil'),
(3597, 231, '2027015', 'MUHAMMAD DENDI APRIZI', 'XI-1', '2026-08-08', '16:16:53', NULL, 'Terlambat', '', 'Manual', '2026-08-08 09:16:53', '2026-08-08 09:16:53', '2026/2027', 'Ganjil'),
(3598, 232, '2027016', 'MUHAMMAD RIZKI ROMDANI', 'XI-1', '2026-08-08', '16:16:53', NULL, 'Terlambat', '', 'Manual', '2026-08-08 09:16:53', '2026-08-08 09:16:53', '2026/2027', 'Ganjil'),
(3599, 233, '2027017', 'NIA ANDRIANI', 'XI-1', '2026-08-08', '16:16:53', NULL, 'Terlambat', '', 'Manual', '2026-08-08 09:16:53', '2026-08-08 09:16:53', '2026/2027', 'Ganjil'),
(3600, 234, '2027018', 'PARIDI WAJDI', 'XI-1', '2026-08-08', '16:16:53', NULL, 'Terlambat', '', 'Manual', '2026-08-08 09:16:53', '2026-08-08 09:16:53', '2026/2027', 'Ganjil'),
(3601, 235, '2027019', 'RANDIKA PRATAMA', 'XI-1', '2026-08-08', '16:16:53', NULL, 'Terlambat', '', 'Manual', '2026-08-08 09:16:53', '2026-08-08 09:16:53', '2026/2027', 'Ganjil'),
(3602, 236, '2027020', 'RIZKI ADITYA', 'XI-1', '2026-08-08', '16:16:53', NULL, 'Terlambat', '', 'Manual', '2026-08-08 09:16:53', '2026-08-08 09:16:53', '2026/2027', 'Ganjil'),
(3603, 237, '2027021', 'RYAN YUDISTIA SUHENDRA', 'XI-1', '2026-08-08', '16:16:53', NULL, 'Terlambat', '', 'Manual', '2026-08-08 09:16:53', '2026-08-08 09:16:53', '2026/2027', 'Ganjil'),
(3604, 238, '2027022', 'SUSILAWATI', 'XI-1', '2026-08-08', '16:16:53', NULL, 'Terlambat', '', 'Manual', '2026-08-08 09:16:53', '2026-08-08 09:16:53', '2026/2027', 'Ganjil'),
(3605, 239, '202723', 'AHMAD HASAN ZARKASI', 'X-1', '2026-08-08', '16:28:52', NULL, 'Terlambat', '', 'Manual', '2026-08-08 09:28:52', '2026-08-08 09:28:52', '2026/2027', 'Ganjil'),
(3606, 240, '202724', 'AHSANTA KHALQI', 'X-1', '2026-08-08', '16:28:52', NULL, 'Terlambat', '', 'Manual', '2026-08-08 09:28:52', '2026-08-08 09:28:52', '2026/2027', 'Ganjil'),
(3607, 243, '202727', 'ALWADIN', 'X-1', '2026-08-08', '16:28:52', NULL, 'Terlambat', '', 'Manual', '2026-08-08 09:28:52', '2026-08-08 09:28:52', '2026/2027', 'Ganjil'),
(3608, 241, '202725', 'ASMINI', 'X-1', '2026-08-08', '16:28:52', NULL, 'Terlambat', '', 'Manual', '2026-08-08 09:28:52', '2026-08-08 09:28:52', '2026/2027', 'Ganjil'),
(3609, 244, '202728', 'B. HURUL INEL AOLIA', 'X-1', '2026-08-08', '16:28:52', NULL, 'Terlambat', '', 'Manual', '2026-08-08 09:28:52', '2026-08-08 09:28:52', '2026/2027', 'Ganjil'),
(3610, 242, '202726', 'BAYU RIZIK KHATTOBI', 'X-1', '2026-08-08', '16:28:52', NULL, 'Terlambat', '', 'Manual', '2026-08-08 09:28:52', '2026-08-08 09:28:52', '2026/2027', 'Ganjil'),
(3611, 245, '202729', 'HAJAR USWATUMMINA', 'X-1', '2026-08-08', '16:28:52', NULL, 'Terlambat', '', 'Manual', '2026-08-08 09:28:52', '2026-08-08 09:28:52', '2026/2027', 'Ganjil'),
(3612, 246, '202730', 'M. AFRIYANDI', 'X-1', '2026-08-08', '16:28:52', NULL, 'Terlambat', '', 'Manual', '2026-08-08 09:28:52', '2026-08-08 09:28:52', '2026/2027', 'Ganjil'),
(3613, 247, '202731', 'M. AZRIA LUTFI', 'X-1', '2026-08-08', '16:28:52', NULL, 'Terlambat', '', 'Manual', '2026-08-08 09:28:52', '2026-08-08 09:28:52', '2026/2027', 'Ganjil'),
(3614, 258, '202742', 'M. RHAQIB', 'X-1', '2026-08-08', '16:28:52', NULL, 'Terlambat', '', 'Manual', '2026-08-08 09:28:52', '2026-08-08 09:28:52', '2026/2027', 'Ganjil'),
(3615, 248, '202732', 'MAZIATUL JANNAH', 'X-1', '2026-08-08', '16:28:52', NULL, 'Terlambat', '', 'Manual', '2026-08-08 09:28:52', '2026-08-08 09:28:52', '2026/2027', 'Ganjil'),
(3616, 249, '202733', 'MERLI AOLIANA', 'X-1', '2026-08-08', '16:28:52', NULL, 'Terlambat', '', 'Manual', '2026-08-08 09:28:52', '2026-08-08 09:28:52', '2026/2027', 'Ganjil'),
(3617, 250, '202734', 'METRI SIVIANI', 'X-1', '2026-08-08', '16:28:52', NULL, 'Terlambat', '', 'Manual', '2026-08-08 09:28:52', '2026-08-08 09:28:52', '2026/2027', 'Ganjil'),
(3618, 251, '202735', 'MIZAN', 'X-1', '2026-08-08', '16:28:52', NULL, 'Terlambat', '', 'Manual', '2026-08-08 09:28:52', '2026-08-08 09:28:52', '2026/2027', 'Ganjil'),
(3619, 252, '202736', 'MUHAMAD EVANDI', 'X-1', '2026-08-08', '16:28:52', NULL, 'Terlambat', '', 'Manual', '2026-08-08 09:28:52', '2026-08-08 09:28:52', '2026/2027', 'Ganjil'),
(3620, 253, '202737', 'MUHAMMAD NAOVAL', 'X-1', '2026-08-08', '16:28:52', NULL, 'Terlambat', '', 'Manual', '2026-08-08 09:28:52', '2026-08-08 09:28:52', '2026/2027', 'Ganjil'),
(3621, 254, '202738', 'MUSLIMATUSSOKRAH', 'X-1', '2026-08-08', '16:28:52', NULL, 'Terlambat', '', 'Manual', '2026-08-08 09:28:52', '2026-08-08 09:28:52', '2026/2027', 'Ganjil'),
(3622, 255, '202739', 'NURHALISA', 'X-1', '2026-08-08', '16:28:52', NULL, 'Terlambat', '', 'Manual', '2026-08-08 09:28:52', '2026-08-08 09:28:52', '2026/2027', 'Ganjil'),
(3623, 257, '202741', 'ROSIDATUL AULIA', 'X-1', '2026-08-08', '16:28:52', NULL, 'Terlambat', '', 'Manual', '2026-08-08 09:28:52', '2026-08-08 09:28:52', '2026/2027', 'Ganjil'),
(3624, 256, '202740', 'YULIANA AYU JASMIN', 'X-1', '2026-08-08', '16:28:52', NULL, 'Terlambat', '', 'Manual', '2026-08-08 09:28:52', '2026-08-08 09:28:52', '2026/2027', 'Ganjil'),
(3625, 259, '202743', 'AHMAD RIFKI', 'X-2', '2026-08-08', '16:28:59', NULL, 'Terlambat', '', 'Manual', '2026-08-08 09:28:59', '2026-08-08 09:28:59', '2026/2027', 'Ganjil'),
(3626, 260, '202744', 'ASDIANA MALASARI', 'X-2', '2026-08-08', '16:28:59', NULL, 'Terlambat', '', 'Manual', '2026-08-08 09:28:59', '2026-08-08 09:28:59', '2026/2027', 'Ganjil'),
(3627, 261, '202745', 'BAIQ SHELVIA ENDRIYATI', 'X-2', '2026-08-08', '16:28:59', NULL, 'Terlambat', '', 'Manual', '2026-08-08 09:28:59', '2026-08-08 09:28:59', '2026/2027', 'Ganjil'),
(3628, 262, '202746', 'ELIYAN NOPIANA', 'X-2', '2026-08-08', '16:28:59', NULL, 'Terlambat', '', 'Manual', '2026-08-08 09:28:59', '2026-08-08 09:28:59', '2026/2027', 'Ganjil'),
(3629, 263, '202747', 'GHEA DEVINA NADIANTI', 'X-2', '2026-08-08', '16:28:59', NULL, 'Terlambat', '', 'Manual', '2026-08-08 09:28:59', '2026-08-08 09:28:59', '2026/2027', 'Ganjil'),
(3630, 264, '202748', 'HIKKIRISWANDI MAHMUZ', 'X-2', '2026-08-08', '16:28:59', NULL, 'Terlambat', '', 'Manual', '2026-08-08 09:28:59', '2026-08-08 09:28:59', '2026/2027', 'Ganjil'),
(3631, 265, '202749', 'LALU JUNANDI KHARIRI', 'X-2', '2026-08-08', '16:28:59', NULL, 'Terlambat', '', 'Manual', '2026-08-08 09:28:59', '2026-08-08 09:28:59', '2026/2027', 'Ganjil'),
(3632, 266, '202750', 'MIFTAHUL JANNAH', 'X-2', '2026-08-08', '16:28:59', NULL, 'Terlambat', '', 'Manual', '2026-08-08 09:28:59', '2026-08-08 09:28:59', '2026/2027', 'Ganjil'),
(3633, 267, '202751', 'MUHAMMAD ALPIAN', 'X-2', '2026-08-08', '16:28:59', NULL, 'Terlambat', '', 'Manual', '2026-08-08 09:28:59', '2026-08-08 09:28:59', '2026/2027', 'Ganjil'),
(3634, 268, '202752', 'NIA RAHMAYANTI', 'X-2', '2026-08-08', '16:28:59', NULL, 'Terlambat', '', 'Manual', '2026-08-08 09:28:59', '2026-08-08 09:28:59', '2026/2027', 'Ganjil'),
(3635, 269, '202753', 'PITRA RAMDANI', 'X-2', '2026-08-08', '16:28:59', NULL, 'Terlambat', '', 'Manual', '2026-08-08 09:28:59', '2026-08-08 09:28:59', '2026/2027', 'Ganjil'),
(3636, 270, '202754', 'RAMDAN HAQIQI', 'X-2', '2026-08-08', '16:28:59', NULL, 'Terlambat', '', 'Manual', '2026-08-08 09:28:59', '2026-08-08 09:28:59', '2026/2027', 'Ganjil'),
(3637, 271, '202755', 'SABHATUSSAADAH S', 'X-2', '2026-08-08', '16:28:59', NULL, 'Terlambat', '', 'Manual', '2026-08-08 09:28:59', '2026-08-08 09:28:59', '2026/2027', 'Ganjil'),
(3638, 272, '202756', 'SELVIANA PUTRI', 'X-2', '2026-08-08', '16:28:59', NULL, 'Terlambat', '', 'Manual', '2026-08-08 09:28:59', '2026-08-08 09:28:59', '2026/2027', 'Ganjil'),
(3639, 273, '202757', 'SISKA PRATIWI RAMDANI', 'X-2', '2026-08-08', '16:28:59', NULL, 'Terlambat', '', 'Manual', '2026-08-08 09:28:59', '2026-08-08 09:28:59', '2026/2027', 'Ganjil'),
(3640, 274, '202758', 'SURYA DAEN', 'X-2', '2026-08-08', '16:28:59', NULL, 'Terlambat', '', 'Manual', '2026-08-08 09:28:59', '2026-08-08 09:28:59', '2026/2027', 'Ganjil'),
(3641, 275, '202759', 'ULPIA SAPITRI', 'X-2', '2026-08-08', '16:28:59', NULL, 'Terlambat', '', 'Manual', '2026-08-08 09:28:59', '2026-08-08 09:28:59', '2026/2027', 'Ganjil'),
(3642, 276, '202760', 'WIDYA RAHAYU', 'X-2', '2026-08-08', '16:28:59', NULL, 'Terlambat', '', 'Manual', '2026-08-08 09:28:59', '2026-08-08 09:28:59', '2026/2027', 'Ganjil'),
(3643, 277, '202761', 'ZUHRATUL AIBAH', 'X-2', '2026-08-08', '16:28:59', NULL, 'Terlambat', '', 'Manual', '2026-08-08 09:28:59', '2026-08-08 09:28:59', '2026/2027', 'Ganjil');

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `kepsek_pin` varchar(255) DEFAULT NULL,
  `pin` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`, `nama`, `created_at`, `kepsek_pin`, `pin`) VALUES
(1, 'admin', '$2a$12$s3Bi53QPa.yGlIMn1CJTqev9xde2QhfFjbHOv6VOnUEADwHHq5qIu', 'Administrator', '2026-02-23 03:01:13', '$2y$10$/11dqN6c5924/PsYJVXbC.kSQ9wgMX1c113gOBpA3EW6rhlUTfXHW', '$2y$10$8YvF7b9j40Z0c8VwcnutOe5FkPL.Yry8O1Iv/PPvpxwfIe5ePeOKO');

-- --------------------------------------------------------

--
-- Table structure for table `beranda_foto`
--

CREATE TABLE `beranda_foto` (
  `id` int(11) NOT NULL,
  `judul` varchar(200) DEFAULT '',
  `deskripsi` text DEFAULT NULL,
  `file_foto` varchar(255) NOT NULL,
  `urutan` int(11) DEFAULT 0,
  `aktif` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `beranda_info`
--

CREATE TABLE `beranda_info` (
  `id` int(11) NOT NULL,
  `judul` varchar(200) NOT NULL,
  `isi` text NOT NULL,
  `ikon` varchar(50) DEFAULT 'fa-info-circle',
  `warna` varchar(20) DEFAULT '#3b82f6',
  `urutan` int(11) DEFAULT 0,
  `aktif` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `buku_kejadian`
--

CREATE TABLE `buku_kejadian` (
  `id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `nis` varchar(30) DEFAULT '',
  `nama_siswa` varchar(100) DEFAULT '',
  `kelas` varchar(30) DEFAULT '',
  `guru_bk_id` int(11) DEFAULT 0,
  `nama_guru` varchar(100) DEFAULT '',
  `tanggal` date NOT NULL,
  `hari` varchar(20) DEFAULT '',
  `uraian_kejadian` text DEFAULT NULL,
  `poin` int(11) DEFAULT 0,
  `tanggapan_siswa` text DEFAULT NULL,
  `arahan_guru_wali` text DEFAULT NULL,
  `tindak_lanjut` text DEFAULT NULL,
  `ttd` varchar(255) DEFAULT '',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `tahun_ajaran` varchar(20) NOT NULL DEFAULT '',
  `semester` varchar(10) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `catatan`
--

CREATE TABLE `catatan` (
  `id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `wali_id` int(11) DEFAULT NULL,
  `tipe` enum('Informasi','Peringatan','Urgent','Apresiasi') DEFAULT 'Informasi',
  `judul` varchar(200) NOT NULL,
  `isi` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `tahun_ajaran` varchar(20) NOT NULL DEFAULT '',
  `semester` varchar(10) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `catatan_bk`
--

CREATE TABLE `catatan_bk` (
  `id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `nis` varchar(30) DEFAULT '',
  `nama_siswa` varchar(100) DEFAULT '',
  `kelas` varchar(30) DEFAULT '',
  `guru_bk_id` int(11) DEFAULT 0,
  `nama_guru` varchar(100) DEFAULT '',
  `tipe` enum('Informasi','Peringatan','Urgent','Apresiasi') DEFAULT 'Informasi',
  `judul` varchar(200) DEFAULT '',
  `isi` text NOT NULL,
  `balasan` text DEFAULT NULL,
  `dibalas_at` datetime DEFAULT NULL,
  `balasan_dibaca` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `tahun_ajaran` varchar(20) NOT NULL DEFAULT '',
  `semester` varchar(10) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `disiplin_pin`
--

CREATE TABLE `disiplin_pin` (
  `id` int(11) NOT NULL,
  `pin` varchar(255) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `disiplin_pin`
--

INSERT INTO `disiplin_pin` (`id`, `pin`, `updated_at`) VALUES
(1, '$2y$10$iky1vine5k0RMXjGIB6jN.JC0xXa3CubRDNoI/atcaBf3sny9yrBK', '2026-05-18 05:29:10');

-- --------------------------------------------------------

--
-- Table structure for table `guru_bk`
--

CREATE TABLE `guru_bk` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `nip` varchar(30) DEFAULT '',
  `pin` varchar(255) NOT NULL COMMENT 'bcrypt hash',
  `foto` varchar(100) DEFAULT '',
  `aktif` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `pin_plain` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `guru_bk`
--

INSERT INTO `guru_bk` (`id`, `nama`, `nip`, `pin`, `foto`, `aktif`, `created_at`, `pin_plain`) VALUES
(19, 'FATHURRAHMAN.S.Pd', '-', '$2y$10$vgof/QIlJU6ZYYcONQL7v.EXTcgjsBhClVBqgzJHeAntO8KyOchMa', '', 1, '2026-08-06 07:24:54', '4321');

-- --------------------------------------------------------

--
-- Table structure for table `kelas`
--

CREATE TABLE `kelas` (
  `id` int(11) NOT NULL,
  `nama_kelas` varchar(30) NOT NULL,
  `tingkat` varchar(10) DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kelas`
--

INSERT INTO `kelas` (`id`, `nama_kelas`, `tingkat`, `created_at`) VALUES
(1, 'X-2', 'X', '2026-08-08 07:21:21'),
(37, 'XI-1', 'XI', '2026-08-08 08:39:46'),
(40, 'X-1', 'X', '2026-08-08 09:20:29');

-- --------------------------------------------------------

--
-- Table structure for table `kunjungan_rumah`
--

CREATE TABLE `kunjungan_rumah` (
  `id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `nis` varchar(30) DEFAULT '',
  `nama_siswa` varchar(100) DEFAULT '',
  `kelas` varchar(30) DEFAULT '',
  `guru_bk_id` int(11) DEFAULT 0,
  `nama_guru` varchar(100) DEFAULT '',
  `tanggal_kunjungan` date NOT NULL,
  `nama_ortu` varchar(100) DEFAULT '',
  `kasus` text DEFAULT NULL,
  `penyelesaian` enum('Belum Ditindaklanjuti','Dalam Proses','Selesai') DEFAULT 'Belum Ditindaklanjuti',
  `keterangan` varchar(255) DEFAULT '',
  `foto` varchar(255) DEFAULT '',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `tahun_ajaran` varchar(20) NOT NULL DEFAULT '',
  `semester` varchar(10) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pelanggaran`
--

CREATE TABLE `pelanggaran` (
  `id` int(11) NOT NULL,
  `siswa_id` int(11) DEFAULT 0,
  `nis` varchar(30) DEFAULT '',
  `nama` varchar(100) DEFAULT '',
  `kelas` varchar(30) DEFAULT '',
  `tanggal` date NOT NULL,
  `jenis_id` int(11) DEFAULT 0,
  `jenis_nama` varchar(100) DEFAULT '',
  `keterangan` varchar(255) DEFAULT '',
  `input_oleh` varchar(100) DEFAULT 'Disiplin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `poin` int(11) DEFAULT 3,
  `topik_id` int(11) DEFAULT 0,
  `sub_id` int(11) DEFAULT 0,
  `tahun_ajaran` varchar(20) NOT NULL DEFAULT '',
  `semester` varchar(10) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pelanggaran`
--

INSERT INTO `pelanggaran` (`id`, `siswa_id`, `nis`, `nama`, `kelas`, `tanggal`, `jenis_id`, `jenis_nama`, `keterangan`, `input_oleh`, `created_at`, `poin`, `topik_id`, `sub_id`, `tahun_ajaran`, `semester`) VALUES
(1, 98, '202607', 'AHMAD YANI', 'X-1', '2026-04-23', 1, 'TERLAMBAT', '', 'Petugas Disiplin', '2026-04-23 05:37:31', 3, 0, 0, '2026/2027', 'Ganjil'),
(2, 99, '202608', 'AHMAD YAZID NABIL', 'X-1', '2026-04-23', 2, 'MEROKOK', '', 'Petugas Disiplin', '2026-04-23 05:37:34', 3, 0, 0, '2026/2027', 'Ganjil'),
(3, 100, '202609', 'ARGA JAOZAN JIWARI', 'X-1', '2026-04-23', 3, 'KELUAR TIDAK IZIN', '', 'Petugas Disiplin', '2026-04-23 05:37:35', 3, 0, 0, '2026/2027', 'Ganjil'),
(4, 101, '202610', 'DWI FITRIYANI HUKMI', 'X-1', '2026-04-23', 5, 'TIDAK PIKET', '', 'Petugas Disiplin', '2026-04-23 05:37:37', 3, 0, 0, '2026/2027', 'Ganjil'),
(5, 102, '202611', 'MUH. HIPZUL MUBARROK', 'X-1', '2026-04-23', 1, 'TERLAMBAT', '', 'Petugas Disiplin', '2026-04-23 05:37:40', 3, 0, 0, '2026/2027', 'Ganjil'),
(6, 99, '202608', 'AHMAD YAZID NABIL', 'X-1', '2026-04-30', 2, 'MEROKOK', '', 'Petugas Disiplin', '2026-04-30 02:10:54', 3, 0, 0, '2026/2027', 'Ganjil'),
(7, 98, '202607', 'AHMAD YANI', 'X-1', '2026-04-30', 2, 'MEROKOK', '', 'Petugas Disiplin', '2026-04-30 02:10:57', 3, 0, 0, '2026/2027', 'Ganjil'),
(8, 98, '202607', 'AHMAD YANI', 'X-1', '2026-07-01', 1, 'TERLAMBAT', '', 'Petugas Disiplin', '2026-07-01 05:33:11', 3, 0, 0, '2026/2027', 'Ganjil'),
(9, 98, '202607', 'AHMAD YANI', 'X-1', '2026-07-01', 2, 'MEROKOK', '', 'Petugas Disiplin', '2026-07-01 05:33:14', 3, 0, 0, '2026/2027', 'Ganjil');

-- --------------------------------------------------------

--
-- Table structure for table `pelanggaran_jenis`
--

CREATE TABLE `pelanggaran_jenis` (
  `id` int(11) NOT NULL,
  `topik_id` int(11) DEFAULT 0,
  `sub_id` int(11) DEFAULT 0,
  `nama` varchar(100) NOT NULL,
  `kode` varchar(10) DEFAULT '',
  `keterangan` varchar(255) DEFAULT '',
  `aktif` tinyint(1) DEFAULT 1,
  `urutan` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `poin` int(11) DEFAULT 3
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pelanggaran_jenis`
--

INSERT INTO `pelanggaran_jenis` (`id`, `topik_id`, `sub_id`, `nama`, `kode`, `keterangan`, `aktif`, `urutan`, `created_at`, `poin`) VALUES
(9, 1, 1, 'Membuat atau terlibat keributan/kegaduhan di dalam atau luar lingkungan madrasah', '', '', 1, 1, '2026-08-06 16:01:27', 10),
(10, 1, 1, 'Mengotori (mencorat-coret) dinding, meja, dan peralatan milik sekolah lainnya', 'FSK', '', 1, 2, '2026-08-06 16:02:30', 10),
(11, 1, 1, 'Merusak, mencuri barang milik sekolah/guru/karyawan/teman', 'MERUSAK, M', '', 1, 3, '2026-08-06 16:25:56', 5);

-- --------------------------------------------------------

--
-- Table structure for table `pelanggaran_sub`
--

CREATE TABLE `pelanggaran_sub` (
  `id` int(11) NOT NULL,
  `topik_id` int(11) NOT NULL DEFAULT 0,
  `nama` varchar(150) NOT NULL,
  `kode` varchar(10) DEFAULT '',
  `urutan` int(11) DEFAULT 0,
  `aktif` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pelanggaran_sub`
--

INSERT INTO `pelanggaran_sub` (`id`, `topik_id`, `nama`, `kode`, `urutan`, `aktif`, `created_at`) VALUES
(1, 1, 'KEPRIBADIAN', 'KTR', 1, 1, '2026-08-06 16:00:52');

-- --------------------------------------------------------

--
-- Table structure for table `pelanggaran_topik`
--

CREATE TABLE `pelanggaran_topik` (
  `id` int(11) NOT NULL,
  `nama` varchar(150) NOT NULL,
  `urutan` int(11) DEFAULT 0,
  `aktif` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pelanggaran_topik`
--

INSERT INTO `pelanggaran_topik` (`id`, `nama`, `urutan`, `aktif`, `created_at`) VALUES
(1, 'KETERTIBAN', 1, 1, '2026-08-06 16:00:19');

-- --------------------------------------------------------

--
-- Table structure for table `pengaturan`
--

CREATE TABLE `pengaturan` (
  `id` int(11) NOT NULL,
  `nama_sekolah` varchar(200) DEFAULT 'Nama Sekolah',
  `alamat` text DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `kepala_sekolah` varchar(200) DEFAULT NULL,
  `nip_kepala` varchar(50) DEFAULT NULL,
  `jam_masuk` time DEFAULT '07:00:00',
  `jam_terlambat` time DEFAULT '07:30:00',
  `jam_pulang` time DEFAULT '14:00:00',
  `token_wa` varchar(200) DEFAULT '',
  `pin_admin` varchar(255) DEFAULT NULL COMMENT 'PIN hashed untuk login Admin',
  `pin_kepsek` varchar(255) DEFAULT NULL COMMENT 'PIN hashed untuk Portal Kepala Sekolah'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengaturan`
--

INSERT INTO `pengaturan` (`id`, `nama_sekolah`, `alamat`, `logo`, `kepala_sekolah`, `nip_kepala`, `jam_masuk`, `jam_terlambat`, `jam_pulang`, `token_wa`, `pin_admin`, `pin_kepsek`) VALUES
(1, 'MAN 2 LOMBOK TIMUR', 'Jln Beririjarak Kecamatan Wanasaba', 'logo_1785989026.png', 'MEHRAM.S.Pd', '', '06:30:00', '07:50:00', '10:00:00', '', '$2y$10$QMKNUT3Gs82T.9KN539Cr.4bMAP2nNYYrLHgYXeQ/4ULUSF07l2/S', '$2y$10$txvix/qeGNW4rY81eVj.YulKLuJLgeE1zW/xzXDbA8PLvt6dPvRNG');

-- --------------------------------------------------------

--
-- Table structure for table `pengaturan_poin_absen`
--

CREATE TABLE `pengaturan_poin_absen` (
  `id` int(11) NOT NULL,
  `poin_alpa` int(11) DEFAULT 5,
  `poin_terlambat` int(11) DEFAULT 2,
  `keterangan` varchar(255) DEFAULT '',
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengaturan_poin_absen`
--

INSERT INTO `pengaturan_poin_absen` (`id`, `poin_alpa`, `poin_terlambat`, `keterangan`, `updated_at`) VALUES
(1, 3, 2, 'Setiap 1x Alpa = 5 Poin, Setiap 1x Terlambat = 2 Poin', '2026-08-07 03:31:13');

-- --------------------------------------------------------

--
-- Table structure for table `periode_ajaran`
--

CREATE TABLE `periode_ajaran` (
  `id` int(11) NOT NULL,
  `tahun_ajaran` varchar(20) NOT NULL,
  `semester` varchar(10) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `tanggal_mulai` date DEFAULT NULL,
  `tanggal_selesai` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `periode_ajaran`
--

INSERT INTO `periode_ajaran` (`id`, `tahun_ajaran`, `semester`, `created_at`, `tanggal_mulai`, `tanggal_selesai`) VALUES
(1, '2026/2027', 'Ganjil', '2026-08-08 05:35:43', '2026-07-01', '2026-11-30'),
(2, '2026/2027', 'Genap', '2026-08-08 05:57:21', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `periode_aktif`
--

CREATE TABLE `periode_aktif` (
  `id` int(11) NOT NULL,
  `periode_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `periode_aktif`
--

INSERT INTO `periode_aktif` (`id`, `periode_id`) VALUES
(1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `pesan_wali`
--

CREATE TABLE `pesan_wali` (
  `id` int(11) NOT NULL,
  `wali_id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `pengirim` enum('wali','siswa') NOT NULL DEFAULT 'wali',
  `pesan` text NOT NULL,
  `dibaca` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `point_perbaikan`
--

CREATE TABLE `point_perbaikan` (
  `id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `nis` varchar(30) DEFAULT '',
  `nama_siswa` varchar(100) DEFAULT '',
  `kelas` varchar(30) DEFAULT '',
  `kategori` enum('TERLAMBAT','ALPA','PELANGGARAN','KUNJUNGAN') NOT NULL,
  `jumlah` int(11) DEFAULT 1,
  `keterangan` varchar(255) DEFAULT '',
  `tanggal` date NOT NULL,
  `guru_bk_id` int(11) DEFAULT 0,
  `nama_guru` varchar(100) DEFAULT '',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `tahun_ajaran` varchar(20) NOT NULL DEFAULT '',
  `semester` varchar(10) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `point_perbaikan`
--

INSERT INTO `point_perbaikan` (`id`, `siswa_id`, `nis`, `nama_siswa`, `kelas`, `kategori`, `jumlah`, `keterangan`, `tanggal`, `guru_bk_id`, `nama_guru`, `created_at`, `tahun_ajaran`, `semester`) VALUES
(5, 155, '202601', 'ABDUL AZIZ', 'X-1', 'TERLAMBAT', 1, 'AMAN', '2026-08-06', 19, 'FATHURRAHMAN.S.Pd', '2026-08-06 15:16:26', '2026/2027', 'Ganjil'),
(6, 155, '202601', 'ABDUL AZIZ', 'X-1', 'TERLAMBAT', 1, '', '2026-08-07', 19, 'FATHURRAHMAN.S.Pd', '2026-08-07 01:58:19', '2026/2027', 'Ganjil'),
(7, 155, '202601', 'ABDUL AZIZ', 'X-1', 'PELANGGARAN', 1, '', '2026-08-07', 19, 'FATHURRAHMAN.S.Pd', '2026-08-07 02:05:08', '2026/2027', 'Ganjil'),
(8, 155, '202601', 'ABDUL AZIZ', 'X-1', 'PELANGGARAN', 8, '', '2026-08-07', 19, 'FATHURRAHMAN.S.Pd', '2026-08-07 02:08:50', '2026/2027', 'Ganjil'),
(9, 155, '202601', 'ABDUL AZIZ', 'X-1', 'PELANGGARAN', 1, '', '2026-08-07', 19, 'FATHURRAHMAN.S.Pd', '2026-08-07 02:13:19', '2026/2027', 'Ganjil'),
(10, 155, '202601', 'ABDUL AZIZ', 'X-1', 'TERLAMBAT', 2, '', '2026-08-07', 19, 'FATHURRAHMAN.S.Pd', '2026-08-07 02:14:04', '2026/2027', 'Ganjil'),
(11, 155, '202601', 'ABDUL AZIZ', 'X-1', 'TERLAMBAT', 1, '', '2026-08-07', 19, 'FATHURRAHMAN.S.Pd', '2026-08-07 02:23:56', '2026/2027', 'Ganjil'),
(12, 155, '202601', 'ABDUL AZIZ', 'X-1', 'TERLAMBAT', 5, '', '2026-08-07', 19, 'FATHURRAHMAN.S.Pd', '2026-08-07 02:24:05', '2026/2027', 'Ganjil'),
(13, 156, '202602', 'AHMAD FIRDAUS', 'X-1', 'TERLAMBAT', 1, '', '2026-08-07', 19, 'FATHURRAHMAN.S.Pd', '2026-08-07 03:21:28', '2026/2027', 'Ganjil');

-- --------------------------------------------------------

--
-- Table structure for table `rekap_bulanan`
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `tahun_ajaran` varchar(20) NOT NULL DEFAULT '',
  `semester` varchar(10) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rekap_bulanan`
--

INSERT INTO `rekap_bulanan` (`id`, `siswa_id`, `nis`, `nama`, `kelas`, `bulan`, `tahun`, `hadir`, `terlambat`, `alpa`, `sakit`, `izin`, `bolos`, `total_hari`, `created_at`, `tahun_ajaran`, `semester`) VALUES
(256, 155, '202601', 'ABDUL AZIZ', 'X-1', 8, 2026, 0, 1, 0, 0, 0, 0, 1, '2026-08-07 00:05:16', '2026/2027', 'Ganjil'),
(257, 156, '202602', 'AHMAD FIRDAUS', 'X-1', 8, 2026, 0, 1, 0, 0, 0, 0, 1, '2026-08-07 00:05:16', '2026/2027', 'Ganjil'),
(258, 157, '202603', 'AHMAD ZABANDI MAHFUZ', 'X-1', 8, 2026, 0, 1, 0, 0, 0, 0, 1, '2026-08-07 00:05:16', '2026/2027', 'Ganjil'),
(259, 158, '202604', 'AHMAD ZHAMHARIL', 'X-1', 8, 2026, 0, 1, 0, 0, 0, 0, 1, '2026-08-07 00:05:16', '2026/2027', 'Ganjil'),
(260, 159, '202605', 'ALMI MARDIATUN AENI', 'X-1', 8, 2026, 0, 1, 0, 0, 0, 0, 1, '2026-08-07 00:05:16', '2026/2027', 'Ganjil'),
(261, 160, '202606', 'BAIQ NAJITA PUTRI', 'X-1', 8, 2026, 0, 1, 0, 0, 0, 0, 1, '2026-08-07 00:05:16', '2026/2027', 'Ganjil'),
(262, 161, '202607', 'BILAL NAJWAN', 'X-1', 8, 2026, 0, 1, 0, 0, 0, 0, 1, '2026-08-07 00:05:16', '2026/2027', 'Ganjil'),
(263, 162, '202608', 'BQ. NADA ASHABUL JANNAH', 'X-1', 8, 2026, 0, 1, 0, 0, 0, 0, 1, '2026-08-07 00:05:16', '2026/2027', 'Ganjil'),
(264, 163, '202609', 'BQ. NAELIN NAZIROH', 'X-1', 8, 2026, 0, 1, 0, 0, 0, 0, 1, '2026-08-07 00:05:16', '2026/2027', 'Ganjil'),
(265, 164, '202610', 'ELSA AULIA', 'X-1', 8, 2026, 0, 1, 0, 0, 0, 0, 1, '2026-08-07 00:05:16', '2026/2027', 'Ganjil'),
(266, 165, '202611', 'ENAN JAYADI', 'X-1', 8, 2026, 0, 1, 0, 0, 0, 0, 1, '2026-08-07 00:05:16', '2026/2027', 'Ganjil'),
(267, 166, '202612', 'FEBY CITRA WULANDARI', 'X-1', 8, 2026, 0, 1, 0, 0, 0, 0, 1, '2026-08-07 00:05:16', '2026/2027', 'Ganjil'),
(268, 167, '202613', 'HANDAYANI', 'X-1', 8, 2026, 0, 1, 0, 0, 0, 0, 1, '2026-08-07 00:05:16', '2026/2027', 'Ganjil'),
(269, 168, '202614', 'HIPZUL MUBIN ARRIZKI', 'X-1', 8, 2026, 0, 1, 0, 0, 0, 0, 1, '2026-08-07 00:05:16', '2026/2027', 'Ganjil'),
(270, 169, '202615', 'INDRI MULYANA MANAN', 'X-1', 8, 2026, 0, 1, 0, 0, 0, 0, 1, '2026-08-07 00:05:16', '2026/2027', 'Ganjil'),
(271, 170, '202616', 'IZZATUL KAMALIAH', 'X-1', 8, 2026, 0, 1, 0, 0, 0, 0, 1, '2026-08-07 00:05:16', '2026/2027', 'Ganjil'),
(272, 171, '202617', 'JUNIATUN HUSNA', 'X-1', 8, 2026, 0, 1, 0, 0, 0, 0, 1, '2026-08-07 00:05:16', '2026/2027', 'Ganjil'),
(273, 172, '202618', 'LALU ARADEA IFRA ARDANA', 'X-1', 8, 2026, 0, 1, 0, 0, 0, 0, 1, '2026-08-07 00:05:16', '2026/2027', 'Ganjil'),
(274, 173, '202619', 'LALU MUHAMMAD ILHAM HADIWIJAYA', 'X-1', 8, 2026, 0, 1, 0, 0, 0, 0, 1, '2026-08-07 00:05:16', '2026/2027', 'Ganjil'),
(275, 174, '202620', 'M.ROMI NAJWAN HIDAYAT', 'X-1', 8, 2026, 0, 1, 0, 0, 0, 0, 1, '2026-08-07 00:05:16', '2026/2027', 'Ganjil'),
(276, 175, '202621', 'MUHAMMAD AFIQ HARIRI', 'X-1', 8, 2026, 0, 1, 0, 0, 0, 0, 1, '2026-08-07 00:05:16', '2026/2027', 'Ganjil'),
(277, 176, '202622', 'MUHAMMAD AZIZUDDIN', 'X-1', 8, 2026, 0, 1, 0, 0, 0, 0, 1, '2026-08-07 00:05:16', '2026/2027', 'Ganjil'),
(278, 177, '202623', 'MUHAMMAD FERDIAN HIDAYAT', 'X-1', 8, 2026, 0, 1, 0, 0, 0, 0, 1, '2026-08-07 00:05:16', '2026/2027', 'Ganjil'),
(279, 178, '202624', 'MUHAMMAD HASRUL', 'X-1', 8, 2026, 0, 1, 0, 0, 0, 0, 1, '2026-08-07 00:05:16', '2026/2027', 'Ganjil'),
(280, 179, '202625', 'MUHAMMAD RENDRA AL QARDAWI', 'X-1', 8, 2026, 0, 1, 0, 0, 0, 0, 1, '2026-08-07 00:05:16', '2026/2027', 'Ganjil'),
(281, 180, '202626', 'NAELATUN NISA', 'X-1', 8, 2026, 0, 1, 0, 0, 0, 0, 1, '2026-08-07 00:05:16', '2026/2027', 'Ganjil'),
(282, 181, '202627', 'NETI WARDANI', 'X-1', 8, 2026, 0, 1, 0, 0, 0, 0, 1, '2026-08-07 00:05:16', '2026/2027', 'Ganjil'),
(283, 182, '202628', 'SABRI', 'X-1', 8, 2026, 0, 1, 0, 0, 0, 0, 1, '2026-08-07 00:05:16', '2026/2027', 'Ganjil'),
(284, 183, '202629', 'SATRIA EFENDY', 'X-1', 8, 2026, 0, 1, 0, 0, 0, 0, 1, '2026-08-07 00:05:16', '2026/2027', 'Ganjil'),
(285, 184, '202630', 'SUSILAWATI', 'X-1', 8, 2026, 0, 1, 0, 0, 0, 0, 1, '2026-08-07 00:05:16', '2026/2027', 'Ganjil'),
(286, 185, '202631', 'ULFI ALDIANSAH', 'X-1', 8, 2026, 0, 1, 0, 0, 0, 0, 1, '2026-08-07 00:05:16', '2026/2027', 'Ganjil');

-- --------------------------------------------------------

--
-- Table structure for table `riwayat_pindah_kelas`
--

CREATE TABLE `riwayat_pindah_kelas` (
  `id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `nis` varchar(20) DEFAULT '',
  `nama` varchar(100) DEFAULT '',
  `kelas_asal` varchar(20) DEFAULT '',
  `kelas_tujuan` varchar(20) DEFAULT '',
  `admin_nama` varchar(100) DEFAULT '',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `riwayat_pindah_kelas`
--

INSERT INTO `riwayat_pindah_kelas` (`id`, `siswa_id`, `nis`, `nama`, `kelas_asal`, `kelas_tujuan`, `admin_nama`, `created_at`) VALUES
(1, 155, '202601', 'ABDUL AZIZ', 'X-1', 'X-2', 'Administrator', '2026-08-08 07:18:10'),
(2, 156, '202602', 'AHMAD FIRDAUS', 'X-1', 'X-2', 'Administrator', '2026-08-08 07:18:10'),
(3, 157, '202603', 'AHMAD ZABANDI MAHFUZ', 'X-1', 'X-2', 'Administrator', '2026-08-08 07:18:10'),
(4, 158, '202604', 'AHMAD ZHAMHARIL', 'X-1', 'X-2', 'Administrator', '2026-08-08 07:18:10'),
(5, 159, '202605', 'ALMI MARDIATUN AENI', 'X-1', 'X-2', 'Administrator', '2026-08-08 07:18:10'),
(6, 160, '202606', 'BAIQ NAJITA PUTRI', 'X-1', 'X-2', 'Administrator', '2026-08-08 07:18:10'),
(7, 161, '202607', 'BILAL NAJWAN', 'X-1', 'X-2', 'Administrator', '2026-08-08 07:18:10'),
(8, 162, '202608', 'BQ. NADA ASHABUL JANNAH', 'X-1', 'X-2', 'Administrator', '2026-08-08 07:18:10'),
(9, 163, '202609', 'BQ. NAELIN NAZIROH', 'X-1', 'X-2', 'Administrator', '2026-08-08 07:18:10'),
(10, 164, '202610', 'ELSA AULIA', 'X-1', 'X-2', 'Administrator', '2026-08-08 07:18:10'),
(11, 165, '202611', 'ENAN JAYADI', 'X-1', 'X-2', 'Administrator', '2026-08-08 07:18:10'),
(12, 166, '202612', 'FEBY CITRA WULANDARI', 'X-1', 'X-2', 'Administrator', '2026-08-08 07:18:10'),
(13, 167, '202613', 'HANDAYANI', 'X-1', 'X-2', 'Administrator', '2026-08-08 07:18:10'),
(14, 168, '202614', 'HIPZUL MUBIN ARRIZKI', 'X-1', 'X-2', 'Administrator', '2026-08-08 07:18:10'),
(15, 169, '202615', 'INDRI MULYANA MANAN', 'X-1', 'X-2', 'Administrator', '2026-08-08 07:18:10'),
(16, 170, '202616', 'IZZATUL KAMALIAH', 'X-1', 'X-2', 'Administrator', '2026-08-08 07:18:10'),
(17, 171, '202617', 'JUNIATUN HUSNA', 'X-1', 'X-2', 'Administrator', '2026-08-08 07:18:10'),
(18, 172, '202618', 'LALU ARADEA IFRA ARDANA', 'X-1', 'X-2', 'Administrator', '2026-08-08 07:18:10'),
(19, 173, '202619', 'LALU MUHAMMAD ILHAM HADIWIJAYA', 'X-1', 'X-2', 'Administrator', '2026-08-08 07:18:10'),
(20, 174, '202620', 'M.ROMI NAJWAN HIDAYAT', 'X-1', 'X-2', 'Administrator', '2026-08-08 07:18:10'),
(21, 175, '202621', 'MUHAMMAD AFIQ HARIRI', 'X-1', 'X-2', 'Administrator', '2026-08-08 07:18:10'),
(22, 176, '202622', 'MUHAMMAD AZIZUDDIN', 'X-1', 'X-2', 'Administrator', '2026-08-08 07:18:10'),
(23, 177, '202623', 'MUHAMMAD FERDIAN HIDAYAT', 'X-1', 'X-2', 'Administrator', '2026-08-08 07:18:10'),
(24, 178, '202624', 'MUHAMMAD HASRUL', 'X-1', 'X-2', 'Administrator', '2026-08-08 07:18:10'),
(25, 179, '202625', 'MUHAMMAD RENDRA AL QARDAWI', 'X-1', 'X-2', 'Administrator', '2026-08-08 07:18:10'),
(26, 180, '202626', 'NAELATUN NISA', 'X-1', 'X-2', 'Administrator', '2026-08-08 07:18:10'),
(27, 181, '202627', 'NETI WARDANI', 'X-1', 'X-2', 'Administrator', '2026-08-08 07:18:10'),
(28, 182, '202628', 'SABRI', 'X-1', 'X-2', 'Administrator', '2026-08-08 07:18:10'),
(29, 183, '202629', 'SATRIA EFENDY', 'X-1', 'X-2', 'Administrator', '2026-08-08 07:18:10'),
(30, 184, '202630', 'SUSILAWATI', 'X-1', 'X-2', 'Administrator', '2026-08-08 07:18:10'),
(31, 185, '202631', 'ULFI ALDIANSAH', 'X-1', 'X-2', 'Administrator', '2026-08-08 07:18:10'),
(32, 155, '202601', 'ABDUL AZIZ', 'X-2', 'XI-1', 'Administrator', '2026-08-08 08:41:07'),
(33, 186, '202632', 'ABDUL RASYID SULAIMAN', 'X-2', 'XI-1', 'Administrator', '2026-08-08 08:41:07'),
(34, 187, '202633', 'ABIB ALIUDIN', 'X-2', 'XI-1', 'Administrator', '2026-08-08 08:41:07'),
(35, 188, '202634', 'AGUS RAHMAN', 'X-2', 'XI-1', 'Administrator', '2026-08-08 08:41:07'),
(36, 156, '202602', 'AHMAD FIRDAUS', 'X-2', 'XI-1', 'Administrator', '2026-08-08 08:41:07'),
(37, 157, '202603', 'AHMAD ZABANDI MAHFUZ', 'X-2', 'XI-1', 'Administrator', '2026-08-08 08:41:07'),
(38, 158, '202604', 'AHMAD ZHAMHARIL', 'X-2', 'XI-1', 'Administrator', '2026-08-08 08:41:07'),
(39, 159, '202605', 'ALMI MARDIATUN AENI', 'X-2', 'XI-1', 'Administrator', '2026-08-08 08:41:07'),
(40, 216, '202662', 'AQILA IZZA IFKAOZIZ', 'X-2', 'XI-1', 'Administrator', '2026-08-08 08:41:07'),
(41, 189, '202635', 'AUDIATUS SYIFA', 'X-2', 'XI-1', 'Administrator', '2026-08-08 08:41:07'),
(42, 160, '202606', 'BAIQ NAJITA PUTRI', 'X-2', 'XI-1', 'Administrator', '2026-08-08 08:41:07'),
(43, 161, '202607', 'BILAL NAJWAN', 'X-2', 'XI-1', 'Administrator', '2026-08-08 08:41:07'),
(44, 162, '202608', 'BQ. NADA ASHABUL JANNAH', 'X-2', 'XI-1', 'Administrator', '2026-08-08 08:41:07'),
(45, 163, '202609', 'BQ. NAELIN NAZIROH', 'X-2', 'XI-1', 'Administrator', '2026-08-08 08:41:07'),
(46, 190, '202636', 'DESI AGUSTINA RAMADANIA', 'X-2', 'XI-1', 'Administrator', '2026-08-08 08:41:07'),
(47, 164, '202610', 'ELSA AULIA', 'X-2', 'XI-1', 'Administrator', '2026-08-08 08:41:07'),
(48, 191, '202637', 'ELSA DWI ARYANTI', 'X-2', 'XI-1', 'Administrator', '2026-08-08 08:41:07'),
(49, 165, '202611', 'ENAN JAYADI', 'X-2', 'XI-1', 'Administrator', '2026-08-08 08:41:07'),
(50, 166, '202612', 'FEBY CITRA WULANDARI', 'X-2', 'XI-1', 'Administrator', '2026-08-08 08:41:07'),
(51, 192, '202638', 'HAMDAN ZAKY', 'X-2', 'XI-1', 'Administrator', '2026-08-08 08:41:07'),
(52, 167, '202613', 'HANDAYANI', 'X-2', 'XI-1', 'Administrator', '2026-08-08 08:41:07'),
(53, 168, '202614', 'HIPZUL MUBIN ARRIZKI', 'X-2', 'XI-1', 'Administrator', '2026-08-08 08:41:07'),
(54, 169, '202615', 'INDRI MULYANA MANAN', 'X-2', 'XI-1', 'Administrator', '2026-08-08 08:41:07'),
(55, 193, '202639', 'IWAN RASIDI', 'X-2', 'XI-1', 'Administrator', '2026-08-08 08:41:07'),
(56, 194, '202640', 'IZATUL AINI', 'X-2', 'XI-1', 'Administrator', '2026-08-08 08:41:07'),
(57, 170, '202616', 'IZZATUL KAMALIAH', 'X-2', 'XI-1', 'Administrator', '2026-08-08 08:41:07'),
(58, 195, '202641', 'JELI ARSELA', 'X-2', 'XI-1', 'Administrator', '2026-08-08 08:41:07'),
(59, 171, '202617', 'JUNIATUN HUSNA', 'X-2', 'XI-1', 'Administrator', '2026-08-08 08:41:07'),
(60, 196, '202642', 'KAMARUDDIN', 'X-2', 'XI-1', 'Administrator', '2026-08-08 08:41:07'),
(61, 172, '202618', 'LALU ARADEA IFRA ARDANA', 'X-2', 'XI-1', 'Administrator', '2026-08-08 08:41:07'),
(62, 197, '202643', 'LALU MUHAMMAD AZANI ABDILLAH', 'X-2', 'XI-1', 'Administrator', '2026-08-08 08:41:07');

-- --------------------------------------------------------

--
-- Table structure for table `siswa`
--

CREATE TABLE `siswa` (
  `id` int(11) NOT NULL,
  `nis` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `kelas` varchar(20) NOT NULL,
  `aktif` tinyint(1) DEFAULT 1,
  `foto` varchar(255) DEFAULT NULL,
  `barcode_img` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `no_wa_ortu` varchar(20) DEFAULT '',
  `password_plain` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `siswa`
--

INSERT INTO `siswa` (`id`, `nis`, `nama`, `kelas`, `aktif`, `foto`, `barcode_img`, `password`, `created_at`, `no_wa_ortu`, `password_plain`) VALUES
(226, '2027010', 'M. ARSANI HIDAYATULLAH', 'XI-1', 1, NULL, NULL, '$2y$10$u0TOR/WvIYd1wigEANTeL.fMvP8QKzT7jxK3360RyZbfU7QmnGeC.', '2026-08-08 09:16:41', '', NULL),
(227, '2027011', 'M. RIKI GAZALI', 'XI-1', 1, NULL, NULL, '$2y$10$aaJ31VgUnoYElARyP0oS8uNoZwCZCN0jfnDeGY3yweJB6ZzuX94GG', '2026-08-08 09:16:41', '', NULL),
(228, '2027012', 'M.WIJDAN GARDA SOFA', 'XI-1', 1, NULL, NULL, '$2y$10$.lu6BYw1lDXEwUsg4psqteUVD0UpWcmhtEGifrschJeQVzkOPr/Xu', '2026-08-08 09:16:42', '', NULL),
(229, '2027013', 'MHD. AFDHAL AL-GIFARI', 'XI-1', 1, NULL, NULL, '$2y$10$zbRXd5IjStK5UrVJMurnIuZeVxrn32k8r4u.3vRwyQk3BxLs65gUm', '2026-08-08 09:16:42', '', NULL),
(230, '2027014', 'MUH.LUTFI AKBAR', 'XI-1', 1, NULL, NULL, '$2y$10$ZIyQyC5XuASYB/H7uUhs1.b8dHt825xU4Qb4PUdJ3lPG30VhT0Voa', '2026-08-08 09:16:42', '', NULL),
(231, '2027015', 'MUHAMMAD DENDI APRIZI', 'XI-1', 1, NULL, NULL, '$2y$10$.nSeSJbaN/gp/4EmkrlqcOMWcuXpbwHqJzXuQIj2YL3TGl8N1D9sK', '2026-08-08 09:16:42', '', NULL),
(232, '2027016', 'MUHAMMAD RIZKI ROMDANI', 'XI-1', 1, NULL, NULL, '$2y$10$xcfE36oFaIVOygLZvSXd/uSHs.ur71B9tLUwV3eqh0/fFxHF5uTgi', '2026-08-08 09:16:42', '', NULL),
(233, '2027017', 'NIA ANDRIANI', 'XI-1', 1, NULL, NULL, '$2y$10$qzPZixbj9CUpjd1WrETIbeS26MiZkoFO2yeKKiogDxavLEaZ73wQ2', '2026-08-08 09:16:42', '', NULL),
(234, '2027018', 'PARIDI WAJDI', 'XI-1', 1, NULL, NULL, '$2y$10$6l1Y7s3TUndlxLufQf1HyugZqeGoxvTjq.jhkiQXm9cOSrwifElgi', '2026-08-08 09:16:42', '', NULL),
(235, '2027019', 'RANDIKA PRATAMA', 'XI-1', 1, NULL, NULL, '$2y$10$RXGd120wjfRbvBFiYQU98.ye7i0BZEyKxlXuIRyIvsTxdHjMmCGru', '2026-08-08 09:16:42', '', NULL),
(236, '2027020', 'RIZKI ADITYA', 'XI-1', 1, NULL, NULL, '$2y$10$KNR5i.GPo194RvRG1Sk0LeF5TMauTgzx3qvPfoapDqGBmqJzzhF3u', '2026-08-08 09:16:42', '', NULL),
(237, '2027021', 'RYAN YUDISTIA SUHENDRA', 'XI-1', 1, NULL, NULL, '$2y$10$A3aQWxj6r8MiiAse.zKFIe3dNzdRJy3WgTgUbM/8/oi7AQrKsLz22', '2026-08-08 09:16:42', '', NULL),
(238, '2027022', 'SUSILAWATI', 'XI-1', 1, NULL, NULL, '$2y$10$kEFfLDvgCEFP5E.cOYAtL.UNSQ5Gd.x1XQYI9eKk99fyrm0y0fmc6', '2026-08-08 09:16:42', '', NULL),
(239, '202723', 'AHMAD HASAN ZARKASI', 'X-1', 1, NULL, NULL, '$2y$10$5gLR/CHB3SIptt9CPVGQou8nNoItF44bXC7NyD6PHPh4yZdSY8e7O', '2026-08-08 09:24:48', '', '202723'),
(240, '202724', 'AHSANTA KHALQI', 'X-1', 1, NULL, NULL, '$2y$10$mwbKhAWWQBSvS7n71qAXw.fKEnnihFbxNw8ZTfO98FHuToC6UkmAO', '2026-08-08 09:24:48', '', NULL),
(241, '202725', 'ASMINI', 'X-1', 1, NULL, NULL, '$2y$10$JB1BqgjDn5qYh7.w2TVpbex57rNkBWt49dVRs8AbQplsmVoLnow0O', '2026-08-08 09:24:48', '', NULL),
(242, '202726', 'BAYU RIZIK KHATTOBI', 'X-1', 1, NULL, NULL, '$2y$10$QbmkYSqGOAG8Vuhktv2MfuK0wEptRX87f/0vW19jFRSkLyLS03Faq', '2026-08-08 09:24:48', '', NULL),
(243, '202727', 'ALWADIN', 'X-1', 1, NULL, NULL, '$2y$10$q31FWlzMDt4NP4D8fScmf.oQD6DKThaalvtz0E8Li5orHR75FvtEi', '2026-08-08 09:24:48', '', NULL),
(244, '202728', 'B. HURUL INEL AOLIA', 'X-1', 1, NULL, NULL, '$2y$10$e3HVBPtKGtPUCq8NwZyjceNdrHPx7GV7e2sK93G2kcIo0ZfbPh9S.', '2026-08-08 09:24:48', '', NULL),
(245, '202729', 'HAJAR USWATUMMINA', 'X-1', 1, NULL, NULL, '$2y$10$VAXOU1yyK7Rah45efUvhtOcg5LckgB1bIr6COen1S84ybNh5H54.6', '2026-08-08 09:24:48', '', NULL),
(246, '202730', 'M. AFRIYANDI', 'X-1', 1, NULL, NULL, '$2y$10$ckTAJ52oG06ijbkJVUscl.UTDAofBs9Fqx/jI.tTRA8Aldhefypwi', '2026-08-08 09:24:48', '', NULL),
(247, '202731', 'M. AZRIA LUTFI', 'X-1', 1, NULL, NULL, '$2y$10$a/0RtU0.NpiaAf5HOFnExuCku4hfq/j3T/cmIw3AKBuF3o./3OIYG', '2026-08-08 09:24:48', '', NULL),
(248, '202732', 'MAZIATUL JANNAH', 'X-1', 1, NULL, NULL, '$2y$10$l9BqBsGnj2/MUfEA4I9At.YMim8bdZ8qXDR3lHSLAfS7utSuykvTC', '2026-08-08 09:24:48', '', NULL),
(249, '202733', 'MERLI AOLIANA', 'X-1', 1, NULL, NULL, '$2y$10$SCWb6nyd8.OQ7PAztM1EsOmVC5HXfZaIRVwXye.jye2yemyVw54qO', '2026-08-08 09:24:49', '', NULL),
(250, '202734', 'METRI SIVIANI', 'X-1', 1, NULL, NULL, '$2y$10$MxqzLZH3wRPNjN3KFAY6OOh8L5aC4HfvEeaKa0ua/QuqhLykawNvS', '2026-08-08 09:24:49', '', NULL),
(251, '202735', 'MIZAN', 'X-1', 1, NULL, NULL, '$2y$10$ZEwybbl/oWn.pttud/xOIupOWdQKv6gsfZs0JTI.WnrdP6Td4B8D.', '2026-08-08 09:24:49', '', NULL),
(252, '202736', 'MUHAMAD EVANDI', 'X-1', 1, NULL, NULL, '$2y$10$Qg.6XpYrLA.Tn6nHESSbR.ky2HMWIUPM.BolbWRa6ncsDqtVeyT..', '2026-08-08 09:24:49', '', NULL),
(253, '202737', 'MUHAMMAD NAOVAL', 'X-1', 1, NULL, NULL, '$2y$10$mXahCtK2uWWoEetlMtKvouuauX0J6MrZzXGFYZJKK8nEA0dFy3N/q', '2026-08-08 09:24:49', '', NULL),
(254, '202738', 'MUSLIMATUSSOKRAH', 'X-1', 1, NULL, NULL, '$2y$10$o9bmGob6Mimq1Z1wgS7HNe214xEnIyIx7m7YhFKqNWPGY2xeOyxo2', '2026-08-08 09:24:49', '', NULL),
(255, '202739', 'NURHALISA', 'X-1', 1, NULL, NULL, '$2y$10$f4tALhDFGhRrBIYg/dqMxOeyeMJFjqv6/NhNN4JSI501JH1GqtKvu', '2026-08-08 09:24:49', '', NULL),
(256, '202740', 'YULIANA AYU JASMIN', 'X-1', 1, NULL, NULL, '$2y$10$0QeUAlXdX9dqHe0dkUOdBebuYsUAyakcJ32yDYLO9F4H1cBb9Tk2C', '2026-08-08 09:24:49', '', NULL),
(257, '202741', 'ROSIDATUL AULIA', 'X-1', 1, NULL, NULL, '$2y$10$LILhkLfKwuuB8FYzrDxhVemIh9njpbSXoqgtHsjUOS6iWxGPmpHkq', '2026-08-08 09:24:49', '', NULL),
(258, '202742', 'M. RHAQIB', 'X-1', 1, NULL, NULL, '$2y$10$63ATS82L0xTxUJ2CJxOTmu4iAlZ8cCJgYnugnJHb5bNHJqvAz.ae6', '2026-08-08 09:24:49', '', NULL),
(259, '202743', 'AHMAD RIFKI', 'X-2', 1, NULL, NULL, '$2y$10$M5NT3L6Ve9FsKVcwXak9yePyeAPm5bvO5t3vLPJc5XSCvFsRiUzHS', '2026-08-08 09:28:11', '', NULL),
(260, '202744', 'ASDIANA MALASARI', 'X-2', 1, NULL, NULL, '$2y$10$/MaYcKN3lS.emaHo.BfJ2.qGLubvH3/WlDFtSO1NCQ0NO4vyPKZOe', '2026-08-08 09:28:11', '', NULL),
(261, '202745', 'BAIQ SHELVIA ENDRIYATI', 'X-2', 1, NULL, NULL, '$2y$10$7HOUDthz4c5nvIHZKfydyOifPMQG3Ejr8E8BCiHbUz5qT9w4zKyFe', '2026-08-08 09:28:11', '', NULL),
(262, '202746', 'ELIYAN NOPIANA', 'X-2', 1, NULL, NULL, '$2y$10$9LVBjX5d0K5I9AJsrGqq6O.NgDbysx7H3iHdvJ5a9Q4njuO59N.IC', '2026-08-08 09:28:11', '', NULL),
(263, '202747', 'GHEA DEVINA NADIANTI', 'X-2', 1, NULL, NULL, '$2y$10$pbngXTyAmesT19DQ.vdFC.qgSU7x9xLSxA57TVpfx0hM.j2GjbNR.', '2026-08-08 09:28:11', '', NULL),
(264, '202748', 'HIKKIRISWANDI MAHMUZ', 'X-2', 1, NULL, NULL, '$2y$10$nAPLdf4w47UhP7ouJf7R9uGp4Ven/iHN.DNEJ4LV3MHr9uuhZUI3S', '2026-08-08 09:28:11', '', NULL),
(265, '202749', 'LALU JUNANDI KHARIRI', 'X-2', 1, NULL, NULL, '$2y$10$8P17PsEwnyHGFbsi.csSjuSX.b8KW1oRGLCh2zM6e76Q3/M7e5.QS', '2026-08-08 09:28:11', '', NULL),
(266, '202750', 'MIFTAHUL JANNAH', 'X-2', 1, NULL, NULL, '$2y$10$7ris0Hw7/rD5.A/Nfiwz7.EbUuihS.mhe9E4iQBbOnWIY8TjgWVgG', '2026-08-08 09:28:11', '', NULL),
(267, '202751', 'MUHAMMAD ALPIAN', 'X-2', 1, NULL, NULL, '$2y$10$wLPekerTdO4v1JI/o6v/KuN3K6AY0HdD6Gt8t4nI/RPgA0RYHoqY6', '2026-08-08 09:28:12', '', NULL),
(268, '202752', 'NIA RAHMAYANTI', 'X-2', 1, NULL, NULL, '$2y$10$WB9poc20IJbXdKj5Iz3NdudShp6Yo4C0IdeWWjAUkI.vtQTRZgdTO', '2026-08-08 09:28:12', '', NULL),
(269, '202753', 'PITRA RAMDANI', 'X-2', 1, NULL, NULL, '$2y$10$Jh8G/JmE22H3juuZodSc5eAnuFLZrr9yaqUOQO7.5Svvfk4hJKUPS', '2026-08-08 09:28:12', '', NULL),
(270, '202754', 'RAMDAN HAQIQI', 'X-2', 1, NULL, NULL, '$2y$10$2V9g/QSmaL0TYV7iK12QSuQvToPyefy7MBJLUUBLdbtnsv6dGRecy', '2026-08-08 09:28:12', '', NULL),
(271, '202755', 'SABHATUSSAADAH S', 'X-2', 1, NULL, NULL, '$2y$10$wl6TaDDWqlYc6rvHnmTLr.6jvXLYlWs6wajfeEfeEgG9F.QAy1phy', '2026-08-08 09:28:12', '', NULL),
(272, '202756', 'SELVIANA PUTRI', 'X-2', 1, NULL, NULL, '$2y$10$eIcoQH1QHwiU/lMLcWSegeUAxc48zPT6ogE/SMkwT5aiuChxhtR5u', '2026-08-08 09:28:12', '', NULL),
(273, '202757', 'SISKA PRATIWI RAMDANI', 'X-2', 1, NULL, NULL, '$2y$10$5j9JXI9YdjPoZN/xo45LNuK1z5V5wxh4Bsoqp9ccRMC2mfrf2wnMG', '2026-08-08 09:28:12', '', NULL),
(274, '202758', 'SURYA DAEN', 'X-2', 1, NULL, NULL, '$2y$10$efq7ipdLKNv7/KAMlytSEuI6.mTCZVkEHMosKW7ZqGxy9253Dh/li', '2026-08-08 09:28:12', '', NULL),
(275, '202759', 'ULPIA SAPITRI', 'X-2', 1, NULL, NULL, '$2y$10$ydpop/LOO9CSOHL144COU.iuQuf0sc5PjyoxoG4t.KCZ86EBRvUyK', '2026-08-08 09:28:12', '', NULL),
(276, '202760', 'WIDYA RAHAYU', 'X-2', 1, NULL, NULL, '$2y$10$8fr6nTE6ysxYKnAgEdevJ.3/SnpsBrfgPeUk/BxtdZSEoGjXdjMpy', '2026-08-08 09:28:12', '', NULL),
(277, '202761', 'ZUHRATUL AIBAH', 'X-2', 1, NULL, NULL, '$2y$10$HQhR8burXW86lP8A1kEW8uuNWk9LUCEQ8w7LNd4yAQDhivgAGaT0i', '2026-08-08 09:28:12', '', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tingkat`
--

CREATE TABLE `tingkat` (
  `id` int(11) NOT NULL,
  `nama_tingkat` varchar(20) NOT NULL,
  `urutan` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `keterangan` varchar(100) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tingkat`
--

INSERT INTO `tingkat` (`id`, `nama_tingkat`, `urutan`, `created_at`, `keterangan`) VALUES
(1, 'X', 1, '2026-08-08 07:44:30', ''),
(2, 'XI', 2, '2026-08-08 08:39:13', '');

-- --------------------------------------------------------

--
-- Table structure for table `wali`
--

CREATE TABLE `wali` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `jabatan` varchar(100) DEFAULT 'Wali Kelas',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `kelas_wali` varchar(30) DEFAULT '',
  `no_hp` varchar(20) DEFAULT '',
  `no_wa` varchar(20) DEFAULT '',
  `foto` varchar(200) DEFAULT '',
  `pin` varchar(255) DEFAULT NULL COMMENT 'PIN hashed untuk login portal',
  `is_bk` tinyint(1) DEFAULT 0 COMMENT '1 = Guru BK, 0 = Wali Kelas',
  `nip` varchar(50) DEFAULT NULL,
  `foto_wali` varchar(255) DEFAULT '',
  `pin_plain` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wali`
--

INSERT INTO `wali` (`id`, `username`, `password`, `nama`, `jabatan`, `created_at`, `kelas_wali`, `no_hp`, `no_wa`, `foto`, `pin`, `is_bk`, `nip`, `foto_wali`, `pin_plain`) VALUES
(14, 'wali_egi_866e', '$2y$10$hy.uUBzxygMeam2Uz5t3nucNulzI.IIGE/DfNZgcpkdGMgJLiIkBG', 'egi', 'Wali Kelas', '2026-08-08 09:32:55', 'X-1', '', '', '', '$2y$10$A8QLKVsij74y0Ctr.D8C3OJsewXGjkc3hp3OSmOyS3kq2nmMxTMAu', 0, NULL, '', '4444');

-- --------------------------------------------------------

--
-- Table structure for table `wali_siswa`
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
-- Indexes for table `absensi`
--
ALTER TABLE `absensi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_siswa_tanggal` (`siswa_id`,`tanggal`),
  ADD KEY `idx_tanggal` (`tanggal`),
  ADD KEY `idx_kelas` (`kelas`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_periode` (`tahun_ajaran`,`semester`);

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `beranda_foto`
--
ALTER TABLE `beranda_foto`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `beranda_info`
--
ALTER TABLE `beranda_info`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `buku_kejadian`
--
ALTER TABLE `buku_kejadian`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_siswa` (`siswa_id`),
  ADD KEY `idx_periode` (`tahun_ajaran`,`semester`);

--
-- Indexes for table `catatan`
--
ALTER TABLE `catatan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_siswa` (`siswa_id`),
  ADD KEY `idx_periode` (`tahun_ajaran`,`semester`);

--
-- Indexes for table `catatan_bk`
--
ALTER TABLE `catatan_bk`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_siswa` (`siswa_id`),
  ADD KEY `idx_periode` (`tahun_ajaran`,`semester`);

--
-- Indexes for table `disiplin_pin`
--
ALTER TABLE `disiplin_pin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `guru_bk`
--
ALTER TABLE `guru_bk`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `kelas`
--
ALTER TABLE `kelas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nama_kelas` (`nama_kelas`);

--
-- Indexes for table `kunjungan_rumah`
--
ALTER TABLE `kunjungan_rumah`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_siswa` (`siswa_id`),
  ADD KEY `idx_periode` (`tahun_ajaran`,`semester`);

--
-- Indexes for table `pelanggaran`
--
ALTER TABLE `pelanggaran`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_periode` (`tahun_ajaran`,`semester`);

--
-- Indexes for table `pelanggaran_jenis`
--
ALTER TABLE `pelanggaran_jenis`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pelanggaran_sub`
--
ALTER TABLE `pelanggaran_sub`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pelanggaran_topik`
--
ALTER TABLE `pelanggaran_topik`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pengaturan`
--
ALTER TABLE `pengaturan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pengaturan_poin_absen`
--
ALTER TABLE `pengaturan_poin_absen`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `periode_ajaran`
--
ALTER TABLE `periode_ajaran`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_periode` (`tahun_ajaran`,`semester`);

--
-- Indexes for table `periode_aktif`
--
ALTER TABLE `periode_aktif`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pesan_wali`
--
ALTER TABLE `pesan_wali`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ws` (`wali_id`,`siswa_id`),
  ADD KEY `idx_siswa` (`siswa_id`);

--
-- Indexes for table `point_perbaikan`
--
ALTER TABLE `point_perbaikan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_siswa` (`siswa_id`),
  ADD KEY `idx_periode` (`tahun_ajaran`,`semester`);

--
-- Indexes for table `rekap_bulanan`
--
ALTER TABLE `rekap_bulanan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_rekap` (`siswa_id`,`bulan`,`tahun`),
  ADD KEY `idx_periode` (`tahun_ajaran`,`semester`);

--
-- Indexes for table `riwayat_pindah_kelas`
--
ALTER TABLE `riwayat_pindah_kelas`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `siswa`
--
ALTER TABLE `siswa`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nis` (`nis`),
  ADD KEY `idx_nis` (`nis`),
  ADD KEY `idx_kelas` (`kelas`);

--
-- Indexes for table `tingkat`
--
ALTER TABLE `tingkat`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nama_tingkat` (`nama_tingkat`);

--
-- Indexes for table `wali`
--
ALTER TABLE `wali`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `wali_siswa`
--
ALTER TABLE `wali_siswa`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_wali_siswa` (`wali_id`,`siswa_id`),
  ADD KEY `idx_wali` (`wali_id`),
  ADD KEY `idx_siswa` (`siswa_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `absensi`
--
ALTER TABLE `absensi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3644;

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `beranda_foto`
--
ALTER TABLE `beranda_foto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `beranda_info`
--
ALTER TABLE `beranda_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `buku_kejadian`
--
ALTER TABLE `buku_kejadian`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `catatan`
--
ALTER TABLE `catatan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `catatan_bk`
--
ALTER TABLE `catatan_bk`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `disiplin_pin`
--
ALTER TABLE `disiplin_pin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `guru_bk`
--
ALTER TABLE `guru_bk`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `kelas`
--
ALTER TABLE `kelas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `kunjungan_rumah`
--
ALTER TABLE `kunjungan_rumah`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pelanggaran`
--
ALTER TABLE `pelanggaran`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `pelanggaran_jenis`
--
ALTER TABLE `pelanggaran_jenis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `pelanggaran_sub`
--
ALTER TABLE `pelanggaran_sub`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pelanggaran_topik`
--
ALTER TABLE `pelanggaran_topik`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pengaturan`
--
ALTER TABLE `pengaturan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pengaturan_poin_absen`
--
ALTER TABLE `pengaturan_poin_absen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `periode_ajaran`
--
ALTER TABLE `periode_ajaran`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `pesan_wali`
--
ALTER TABLE `pesan_wali`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `point_perbaikan`
--
ALTER TABLE `point_perbaikan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `rekap_bulanan`
--
ALTER TABLE `rekap_bulanan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=349;

--
-- AUTO_INCREMENT for table `riwayat_pindah_kelas`
--
ALTER TABLE `riwayat_pindah_kelas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `siswa`
--
ALTER TABLE `siswa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=278;

--
-- AUTO_INCREMENT for table `tingkat`
--
ALTER TABLE `tingkat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `wali`
--
ALTER TABLE `wali`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `wali_siswa`
--
ALTER TABLE `wali_siswa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=506;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
