-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Apr 02, 2026 at 04:39 AM
-- Server version: 8.0.30
-- PHP Version: 8.2.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `e-recruitment`
--

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `application_id` int NOT NULL,
  `job_id` int NOT NULL,
  `user_id` int NOT NULL,
  `no_telepon` varchar(20) NOT NULL,
  `id_jenjang_pendidikan` int DEFAULT NULL,
  `id_jurusan_pendidikan` int DEFAULT NULL,
  `cv` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL,
  `reason` text,
  `interview_date` datetime DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `applied_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`application_id`, `job_id`, `user_id`, `no_telepon`, `id_jenjang_pendidikan`, `id_jurusan_pendidikan`, `cv`, `status`, `reason`, `interview_date`, `start_date`, `applied_at`, `updated_at`) VALUES
(6, 3, 2, '0812345678910', 3, 3, 'cv_2_1763881705.pdf', 'diterima bekerja', 'test', '2025-11-11 15:33:00', '2025-11-26', '2025-11-25 07:24:36', '2025-11-25 07:46:09'),
(7, 3, 8, '08131278923178', 3, 3, 'cv_8_1763985384.pdf', 'ditolak tes & wawancara', 'gak tau', '2025-12-04 07:55:00', NULL, '2025-11-25 11:55:09', '2025-11-26 00:55:50'),
(8, 3, 9, '0831279132789', 3, 3, 'cv_9_1764117960.pdf', 'lolos administrasi', 'oke kamu diterima di sini yh', NULL, NULL, '2025-11-26 00:47:55', '2025-11-27 11:40:49'),
(9, 4, 8, '08131278923178', 3, 3, 'cv_8_1763985384.pdf', 'diterima bekerja', 'aa', '2025-11-27 08:35:00', '2025-11-28', '2025-11-26 01:34:18', '2025-11-26 01:36:35'),
(10, 4, 2, '0812345678910', 3, 3, 'cv_2_1763881705.pdf', 'diterima bekerja', '', '2026-02-27 10:11:00', '2026-02-27', '2026-02-26 03:09:54', '2026-02-26 03:12:15');

-- --------------------------------------------------------

--
-- Table structure for table `content_categories`
--

CREATE TABLE `content_categories` (
  `category_id` int NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `category_description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `galeri`
--

CREATE TABLE `galeri` (
  `galeri_id` int NOT NULL,
  `judul` varchar(200) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `category_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `galeri`
--

INSERT INTO `galeri` (`galeri_id`, `judul`, `created_at`, `category_id`) VALUES
(1, 'Acara Kemerdekaan Indonesia', '2025-08-26 02:21:27', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `galeri_foto`
--

CREATE TABLE `galeri_foto` (
  `foto_id` int NOT NULL,
  `galeri_id` int DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `galeri_foto`
--

INSERT INTO `galeri_foto` (`foto_id`, `galeri_id`, `foto`) VALUES
(1, 1, 'uploads/galeri/1756174887_3.jpeg'),
(2, 1, 'uploads/galeri/1756174887_2.jpeg'),
(3, 1, 'uploads/galeri/1756174887_1.jpeg');

-- --------------------------------------------------------

--
-- Table structure for table `hrd_notes`
--

CREATE TABLE `hrd_notes` (
  `note_id` int NOT NULL,
  `candidate_user_id` int NOT NULL COMMENT 'user_id pelamar — catatan mengikuti kandidat, bukan lamaran',
  `application_id` int NOT NULL,
  `hrd_user_id` int NOT NULL,
  `note` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jenjang_pendidikan`
--

CREATE TABLE `jenjang_pendidikan` (
  `id_jenjang` int NOT NULL,
  `nama_jenjang` varchar(50) NOT NULL,
  `kode_jenjang` varchar(10) NOT NULL,
  `punya_jurusan` tinyint(1) DEFAULT '0' COMMENT '0 = tidak ada jurusan, 1 = ada jurusan',
  `status` tinyint(1) DEFAULT '1',
  `dibuat_pada` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `jenjang_pendidikan`
--

INSERT INTO `jenjang_pendidikan` (`id_jenjang`, `nama_jenjang`, `kode_jenjang`, `punya_jurusan`, `status`, `dibuat_pada`) VALUES
(1, 'SMA', 'SMA', 0, 1, '2025-11-23 02:14:47'),
(2, 'SMK', 'SMK', 1, 1, '2025-11-23 02:14:47'),
(3, 'S1', 'S1', 1, 1, '2025-11-23 02:14:47');

-- --------------------------------------------------------

--
-- Table structure for table `jurusan_pendidikan`
--

CREATE TABLE `jurusan_pendidikan` (
  `id_jurusan` int NOT NULL,
  `id_jenjang` int NOT NULL,
  `nama_jurusan` varchar(100) NOT NULL,
  `status` tinyint(1) DEFAULT '1',
  `dibuat_pada` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `jurusan_pendidikan`
--

INSERT INTO `jurusan_pendidikan` (`id_jurusan`, `id_jenjang`, `nama_jurusan`, `status`, `dibuat_pada`) VALUES
(1, 2, 'Rekayasa Perangkat Lunak', 1, '2025-11-23 02:14:47'),
(2, 2, 'Desain Komunikasi Visual', 1, '2025-11-23 02:14:47'),
(3, 3, 'Teknik Informatika', 1, '2025-11-23 02:14:47'),
(4, 3, 'Desain Komunikasi Visual', 1, '2025-11-23 02:14:47');

-- --------------------------------------------------------

--
-- Table structure for table `kegiatan`
--

CREATE TABLE `kegiatan` (
  `kegiatan_id` int NOT NULL,
  `judul` varchar(200) NOT NULL,
  `deskripsi` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `category_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `kegiatan`
--

INSERT INTO `kegiatan` (`kegiatan_id`, `judul`, `deskripsi`, `created_at`, `category_id`) VALUES
(1, 'Kegiatan Outing dan Family Gathering', 'PT. Waindo SpecTerra membuat kegiatan yang dapat mengajak seluruh karyawan dan keluarga, yang tentunya melibatkan suami atau istri serta anak-anak mereka dalam suasana yang penuh keakraban. Rekreasi seluruh karyawan dan keluarganya ini dikemas dalam acara family day. Family day diselenggarakan perusahaan sebagai salah satu bentuk penghargaan perusahaan terhadap karyawan dan keluarganya atas kerja keras dan dukungan yang telah diberikan. Family day ini bertujuan untuk mempererat hubungan antara karyawan dan keluarganya, serta untuk meningkatkan kinerja para karyawan.\r\n\r\nTujuan :\r\n1.Untuk mempererat hubungan dan menghilangkan kepenatan selama bekerja\r\n2.Untuk mengembalikan optimalisasi kinerja karyawan\r\n3.Agar karyawan dan keluarga bisa dapat saling mengenal satu sama lain dan memperkokoh tali silaturahmi\r\n4.Untuk mewujudkan rasa kebersamaan dan kerukunan antar keluarga', '2025-08-21 09:19:16', NULL),
(2, 'Halal Bihalal Virtual saat Pandemi COVID-19', 'Pada Hari Raya Idul Fitri walaupun Tangan Tak Bisa Berjabat dan Tidak Bisa Betatap muka langsung tidak melunturkan semangat untuk saling bermaafan dan kembali fitri,Waindo tetap melaksanakan Halal Bi Halal secara virtual dengan mendengarkan tauziyah yang sangat bermanfaat saat pandemi oleh Ustadzah Bunda Yati', '2025-08-27 03:35:06', NULL),
(3, 'Kegiatan Pembagian Sembako Saat Pandemi Covid-19', 'Program ini dilakukan PT Waindo SpecTerra untuk support dan mengembalikan semangat karyawan dan keluarganya menghadapi Pandemi Covid 19 untuk pembagian sembako bagi yang berkeluarga dan ada voucher belanja bagi yang masih belum menikah. pembagian sembako, diantarkan langsung ke rumah masing - masing dengan menggunakan fasilitas mobil operasional kantor.', '2025-08-29 01:21:53', NULL),
(4, 'Kegiatan Berbagi Saat Ramadhan dan Santunan Anak Yatim', 'Program ini memang biasa dilakukan PT Waindo SpecTerra setiap tahunnya untuk membagikan makanan berbuka untuk anak-anak yatim piatu dan untuk masjid di lokasi tinggal para karyawan atau karyawan membagikan makanan untuk berbuka ke anak jalanan dengan cara membagikan dari dalam mobil karena situasi Covid19.\r\n', '2025-08-29 01:24:26', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `kegiatan_foto`
--

CREATE TABLE `kegiatan_foto` (
  `foto_id` int NOT NULL,
  `kegiatan_id` int DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `kegiatan_foto`
--

INSERT INTO `kegiatan_foto` (`foto_id`, `kegiatan_id`, `foto`) VALUES
(4, 1, 'uploads/kegiatan/1756260233_3__1_.jpeg'),
(5, 1, 'uploads/kegiatan/1756260233_2__1_.jpeg'),
(6, 1, 'uploads/kegiatan/1756260233_1__1_.jpeg'),
(7, 2, 'uploads/kegiatan/1756265706_6.jpeg'),
(9, 2, 'uploads/kegiatan/1756265895_1__2_.jpeg'),
(10, 3, 'uploads/kegiatan/1756430513_3__2_.jpeg'),
(11, 3, 'uploads/kegiatan/1756430513_2__2_.jpeg'),
(12, 3, 'uploads/kegiatan/1756430513_1__3_.jpeg'),
(13, 4, 'uploads/kegiatan/1756430666_3.jpg'),
(14, 4, 'uploads/kegiatan/1756430666_2.jpg'),
(15, 4, 'uploads/kegiatan/1756430666_1__4_.jpeg');

-- --------------------------------------------------------

--
-- Table structure for table `live_streaming`
--

CREATE TABLE `live_streaming` (
  `streaming_id` int NOT NULL,
  `judul` varchar(200) NOT NULL,
  `tipe` enum('youtube','mp4') NOT NULL,
  `url` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `category_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `live_streaming`
--

INSERT INTO `live_streaming` (`streaming_id`, `judul`, `tipe`, `url`, `created_at`, `category_id`) VALUES
(1, 'Live Streaming #1', 'mp4', 'uploads/live/1756174758_1.mp4', '2025-08-26 02:19:18', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `log_aktivitas`
--

CREATE TABLE `log_aktivitas` (
  `log_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `log_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `log_aktivitas`
--

INSERT INTO `log_aktivitas` (`log_id`, `user_id`, `action`, `log_time`) VALUES
(1, 1, 'Login', '2025-08-19 04:10:41'),
(2, 1, 'Logout', '2025-08-19 04:12:20'),
(3, 1, 'Login', '2025-08-19 04:13:24'),
(4, 1, 'Logout', '2025-08-19 04:22:58'),
(5, 2, 'Login', '2025-08-19 04:24:16'),
(6, 2, 'Logout', '2025-08-19 06:14:57'),
(7, 1, 'Login', '2025-08-19 06:20:22'),
(8, 1, 'Logout', '2025-08-19 07:09:14'),
(9, 1, 'Login', '2025-08-19 07:15:49'),
(10, 1, 'Logout', '2025-08-19 07:26:14'),
(11, 1, 'Login', '2025-08-19 07:26:29'),
(12, 1, 'Logout', '2025-08-19 07:33:27'),
(13, 1, 'Login', '2025-08-19 07:33:42'),
(14, 1, 'Logout', '2025-08-19 08:23:16'),
(15, 2, 'Login', '2025-08-19 08:23:49'),
(16, 2, 'Logout', '2025-08-19 08:25:53'),
(17, 2, 'Login', '2025-08-19 08:56:07'),
(18, 1, 'Login', '2025-08-20 01:15:41'),
(19, 1, 'Logout', '2025-08-20 01:16:43'),
(20, 2, 'Login', '2025-08-20 01:17:02'),
(21, 2, 'Logout', '2025-08-20 01:17:08'),
(22, 3, 'Login', '2025-08-20 01:18:09'),
(23, 3, 'Logout', '2025-08-20 01:52:17'),
(24, 4, 'Login', '2025-08-20 01:52:52'),
(25, 4, 'Logout', '2025-08-20 02:18:27'),
(26, 1, 'Login', '2025-08-20 04:19:24'),
(27, 1, 'Logout', '2025-08-20 06:05:00'),
(28, 3, 'Login', '2025-08-20 06:06:09'),
(29, 3, 'Logout', '2025-08-20 06:46:39'),
(30, 2, 'Login', '2025-08-20 06:46:48'),
(31, 2, 'Buka halaman Bergabung', '2025-08-20 06:57:54'),
(32, 2, 'Buka halaman Bergabung', '2025-08-20 06:57:55'),
(33, 2, 'Buka halaman Bergabung', '2025-08-20 06:57:56'),
(34, 2, 'Buka halaman Bergabung', '2025-08-20 06:57:56'),
(35, 2, 'Buka halaman Bergabung', '2025-08-20 06:58:09'),
(36, 2, 'Logout', '2025-08-20 06:58:51'),
(37, 3, 'Login', '2025-08-20 06:59:05'),
(38, 3, 'HRD: tambah lowongan #1 - Programmer Frontend', '2025-08-20 07:01:47'),
(39, 3, 'Buka halaman Bergabung', '2025-08-20 07:02:05'),
(40, 3, 'Logout', '2025-08-20 07:02:23'),
(41, 2, 'Login', '2025-08-20 07:02:36'),
(42, 2, 'Kirim lamaran (job #1)', '2025-08-20 07:03:00'),
(43, 2, 'Buka halaman Bergabung', '2025-08-20 07:06:06'),
(44, 2, 'Buka halaman Bergabung', '2025-08-20 07:09:30'),
(45, 2, 'Update profil', '2025-08-20 07:12:11'),
(46, 2, 'Update profil', '2025-08-20 07:12:15'),
(47, 2, 'Update profil', '2025-08-20 07:12:19'),
(48, 2, 'Logout', '2025-08-20 07:12:25'),
(49, 3, 'Login', '2025-08-20 07:12:35'),
(50, 3, 'HRD: buka detail application #1 => seleksi administrasi', '2025-08-20 07:12:44'),
(51, 3, 'HRD: terima administrasi application #1 (Programmer Frontend)', '2025-08-20 07:13:34'),
(52, 3, 'HRD: buka detail application #1 => seleksi administrasi', '2025-08-20 07:15:02'),
(53, 3, 'HRD: terima administrasi application #1 (Programmer Frontend)', '2025-08-20 07:15:13'),
(54, 3, 'HRD: buka detail application #1 => seleksi administrasi', '2025-08-20 07:28:43'),
(55, 3, 'HRD: terima administrasi application #1 (Programmer Frontend)', '2025-08-20 07:28:59'),
(56, 3, 'HRD: terima bekerja application #1 (Programmer Frontend)', '2025-08-20 07:34:32'),
(57, 3, 'HRD: terima bekerja application #1 (Programmer Frontend)', '2025-08-20 07:37:40'),
(58, 3, 'HRD: buka detail application #1 => seleksi administrasi', '2025-08-20 07:38:14'),
(59, 3, 'HRD: terima administrasi application #1 (Programmer Frontend)', '2025-08-20 07:38:22'),
(60, 3, 'HRD: terima administrasi application #1 (Programmer Frontend)', '2025-08-20 07:40:32'),
(61, 3, 'HRD: terima administrasi application #1 (Programmer Frontend)', '2025-08-20 07:41:15'),
(62, 3, 'HRD: terima administrasi application #1 (Programmer Frontend)', '2025-08-20 07:45:06'),
(63, 3, 'Logout', '2025-08-20 07:45:35'),
(64, 3, 'Login', '2025-08-20 07:45:45'),
(65, 3, 'HRD: terima bekerja application #1 (Programmer Frontend)', '2025-08-20 07:45:56'),
(66, 3, 'HRD: terima bekerja application #1 (Programmer Frontend)', '2025-08-20 07:47:18'),
(67, 3, 'Logout', '2025-08-20 07:48:46'),
(68, 3, 'Login', '2025-08-20 07:49:06'),
(69, 3, 'HRD: buka detail application #1 => seleksi administrasi', '2025-08-20 07:49:37'),
(70, 3, 'HRD: terima administrasi application #1 (Programmer Frontend)', '2025-08-20 07:49:48'),
(71, 3, 'HRD: terima administrasi application #1 (Programmer Frontend)', '2025-08-20 07:53:25'),
(72, 3, 'HRD: terima administrasi application #1 (Programmer Frontend)', '2025-08-20 07:58:06'),
(73, 1, 'Login', '2025-08-21 04:31:21'),
(74, 1, 'Logout', '2025-08-21 04:32:55'),
(75, 3, 'Login', '2025-08-21 04:33:11'),
(76, 3, 'HRD: terima bekerja application #1 (Programmer Frontend)', '2025-08-21 04:54:52'),
(77, 3, 'Logout', '2025-08-21 06:35:52'),
(78, 2, 'Login', '2025-08-21 06:36:07'),
(79, 2, 'Buka halaman Bergabung', '2025-08-21 06:52:16'),
(80, 2, 'Buka halaman Bergabung', '2025-08-21 06:56:12'),
(81, 2, 'Logout', '2025-08-21 07:31:47'),
(82, 4, 'Login', '2025-08-21 07:59:53'),
(83, 4, 'Konten: tambah kegiatan #1 (Kegiatan test 123)', '2025-08-21 09:19:16'),
(84, 4, 'Buka halaman Bergabung', '2025-08-21 09:20:22'),
(85, 1, 'Login', '2025-08-22 00:50:54'),
(86, 1, 'Logout', '2025-08-22 03:58:08'),
(87, 1, 'Logout', '2025-08-22 04:35:02'),
(88, 1, 'Login', '2025-08-25 01:30:58'),
(89, 1, 'Menambah user baru', '2025-08-25 01:34:19'),
(90, 1, 'Mengubah role user', '2025-08-25 01:35:00'),
(91, 1, 'Mengubah role user', '2025-08-25 01:35:31'),
(92, 1, 'Logout', '2025-08-25 01:37:12'),
(93, 6, 'Login', '2025-08-25 01:37:29'),
(94, 6, 'Logout', '2025-08-25 01:38:06'),
(95, 2, 'Login', '2025-08-25 01:38:14'),
(96, 2, 'Logout', '2025-08-25 01:38:21'),
(97, 7, 'Login', '2025-08-25 01:49:06'),
(98, 7, 'Logout', '2025-08-25 03:46:45'),
(99, 1, 'Login', '2025-08-25 03:46:54'),
(100, 1, 'Logout', '2025-08-25 03:49:16'),
(101, 3, 'Login', '2025-08-25 03:49:29'),
(102, 3, 'Logout', '2025-08-25 03:55:06'),
(103, 2, 'Login', '2025-08-25 03:55:21'),
(104, 2, 'Logout', '2025-08-25 03:56:00'),
(105, 7, 'Login', '2025-08-25 03:56:07'),
(106, 7, 'Logout', '2025-08-25 03:58:05'),
(107, 4, 'Login', '2025-08-25 03:58:20'),
(108, 4, 'Logout', '2025-08-25 04:04:18'),
(109, 1, 'Login', '2025-08-25 04:04:25'),
(110, 1, 'Logout', '2025-08-25 04:10:30'),
(111, 2, 'Login', '2025-08-25 04:10:38'),
(112, 2, 'Logout', '2025-08-25 04:15:59'),
(113, 4, 'Login', '2025-08-25 04:16:07'),
(114, 4, 'Logout', '2025-08-25 04:17:09'),
(115, 3, 'Login', '2025-08-25 04:17:18'),
(116, 3, 'Logout', '2025-08-25 09:27:16'),
(117, 4, 'Login', '2025-08-25 09:27:32'),
(118, 4, 'Login', '2025-08-26 01:46:20'),
(119, 4, 'Konten: tambah webinar #1 (Webinar Waindo Series #1 GIS Enterprise & Dashboard Operation, CSRT, Airbone LiDAR dan Aplikasi Pemanfaatannya)', '2025-08-26 02:09:03'),
(120, 4, 'Logout', '2025-08-26 02:13:34'),
(121, 4, 'Login', '2025-08-26 02:13:44'),
(122, 4, 'Konten: tambah live_streaming #1 (mp4)', '2025-08-26 02:19:18'),
(123, 4, 'Konten: tambah galeri #1 (Acara Kemerdekaan Indonesia)', '2025-08-26 02:21:27'),
(124, 4, 'Logout', '2025-08-26 02:44:29'),
(125, 1, 'Login', '2025-08-26 02:44:35'),
(126, 1, 'Logout', '2025-08-26 02:46:09'),
(127, 4, 'Login', '2025-08-26 02:46:17'),
(128, 4, 'Logout', '2025-08-26 03:51:50'),
(129, 1, 'Login', '2025-08-26 04:04:47'),
(130, 1, 'Logout', '2025-08-26 04:07:21'),
(131, 4, 'Login', '2025-08-26 04:07:32'),
(132, 4, 'Logout', '2025-08-26 04:23:43'),
(133, 3, 'Login', '2025-08-26 04:23:54'),
(134, 3, 'HRD: buka detail application #1 => seleksi administrasi', '2025-08-26 04:45:06'),
(135, 3, 'Logout', '2025-08-26 07:29:57'),
(136, 3, 'Login', '2025-08-26 07:30:05'),
(137, 3, 'Logout', '2025-08-26 07:30:41'),
(138, 3, 'Login', '2025-08-26 07:30:51'),
(139, 3, 'Logout', '2025-08-26 07:31:19'),
(140, 3, 'Login', '2025-08-26 07:31:29'),
(141, 4, 'Login', '2025-08-27 01:54:15'),
(142, 4, 'Konten: edit kegiatan #1 (Kegiatan Outing dan Family Gathering)', '2025-08-27 02:03:53'),
(143, 4, 'Konten: edit webinar #1 (Webinar Waindo Series #1 GIS Enterprise & Dashboard Operation, CSRT, Airbone LiDAR dan Aplikasi Pemanfaatannyaa)', '2025-08-27 02:18:21'),
(144, 4, 'Konten: edit webinar #1 (Webinar Waindo Series #1 GIS Enterprise & Dashboard Operation, CSRT, Airbone LiDAR dan Aplikasi Pemanfaatannya)', '2025-08-27 02:18:29'),
(145, 4, 'Konten: edit webinar #1 (Webinar Waindo Series #1 GIS Enterprise & Dashboard Operation, CSRT, Airbone LiDAR dan Aplikasi Pemanfaatannya)', '2025-08-27 02:19:17'),
(146, 4, 'Konten: edit webinar #1 (Webinar Waindo Series #1 GIS Enterprise & Dashboard Operation, CSRT, Airbone LiDAR dan Aplikasi Pemanfaatannya)', '2025-08-27 02:19:45'),
(147, 4, 'Konten: edit webinar #1 (Webinar Waindo Series #1 GIS Enterprise & Dashboard Operation, CSRT, Airbone LiDAR dan Aplikasi Pemanfaatannya)', '2025-08-27 02:19:47'),
(148, 4, 'Konten: edit webinar #1 (Webinar Waindo Series #1 GIS Enterprise & Dashboard Operation, CSRT, Airbone LiDAR dan Aplikasi Pemanfaatannya)', '2025-08-27 02:19:50'),
(149, 4, 'Konten: tambah kegiatan #2 (Halal Bihalal Virtual saat Pandemi COVID-19)', '2025-08-27 03:35:06'),
(150, 4, 'Konten: tambah webinar #2 (Webinar 2 *Webinar Waindo Series #2 Pembuatan Peta 3D Menggunakan ArcGIS PRO)', '2025-08-27 03:36:38'),
(151, 4, 'Konten: edit kegiatan #2 (Halal Bihalal Virtual saat Pandemi COVID-19)', '2025-08-27 03:38:15'),
(152, 4, 'Konten: tambah galeri #2 (Acara test)', '2025-08-27 03:39:56'),
(153, 4, 'Konten: hapus galeri #2', '2025-08-27 03:40:33'),
(154, 4, 'Logout', '2025-08-27 03:44:14'),
(155, 1, 'Login', '2025-08-27 03:44:21'),
(156, 1, 'Logout', '2025-08-27 07:03:30'),
(157, 2, 'Login', '2025-08-27 07:03:40'),
(158, 4, 'Login', '2025-08-29 01:18:52'),
(159, 4, 'Konten: edit webinar #2 (Webinar Waindo Series #2 Pembuatan Peta 3D Menggunakan ArcGIS PRO)', '2025-08-29 01:19:20'),
(160, 4, 'Konten: tambah webinar #3 (Webinar Waindo Series #3 Technology Updates Low Cost GNSS for Surveying dan Monitoring)', '2025-08-29 01:20:18'),
(161, 4, 'Konten: tambah kegiatan #3 (Kegiatan Pembagian Sembako Saat Pandemi Covid-19)', '2025-08-29 01:21:53'),
(162, 4, 'Konten: tambah kegiatan #4 (Kegiatan Berbagi Saat Ramadhan dan Santunan Anak Yatim)', '2025-08-29 01:24:26'),
(163, 1, 'Login', '2025-09-04 03:20:43'),
(164, 1, 'Logout', '2025-09-04 03:21:43'),
(165, 2, 'Login', '2025-09-04 03:22:12'),
(166, 2, 'Logout', '2025-09-04 03:28:00'),
(167, 3, 'Login', '2025-09-04 03:29:45'),
(168, 3, 'Logout', '2025-09-04 03:36:48'),
(169, 4, 'Login', '2025-09-04 03:36:58'),
(170, 4, 'Logout', '2025-09-04 07:56:36'),
(171, 1, 'Login', '2025-09-10 01:19:39'),
(172, 1, 'Mengedit data user', '2025-09-10 01:20:31'),
(173, 1, 'Mengedit data user', '2025-09-10 01:21:09'),
(174, 1, 'Logout', '2025-09-10 01:39:18'),
(175, 3, 'Login', '2025-09-10 01:39:30'),
(176, 3, 'HRD: ubah status lowongan #1 -> closed', '2025-09-10 01:40:47'),
(177, 3, 'HRD: ubah status lowongan #1 -> open', '2025-09-10 01:40:54'),
(178, 3, 'HRD: ubah status lowongan #1 -> closed', '2025-09-10 01:40:59'),
(179, 3, 'HRD: ubah status lowongan #1 -> open', '2025-09-10 01:41:15'),
(180, 3, 'Logout', '2025-09-10 02:10:48'),
(181, 2, 'Login', '2025-09-10 02:10:56'),
(182, 2, 'Logout', '2025-09-10 02:19:13'),
(183, 2, 'Login', '2025-09-10 02:19:26'),
(184, 2, 'Logout', '2025-09-10 02:47:38'),
(185, 2, 'Login', '2025-09-10 08:22:57'),
(186, 2, 'Login', '2025-09-11 07:04:51'),
(187, 2, 'Logout', '2025-09-11 07:05:04'),
(188, 2, 'Login', '2025-09-11 07:05:13'),
(189, 2, 'Logout', '2025-09-11 07:08:15'),
(190, 1, 'Login', '2025-09-11 07:08:25'),
(191, 1, 'Logout', '2025-09-11 07:08:51'),
(192, 2, 'Login', '2025-09-11 07:09:05'),
(193, 2, 'Logout', '2025-09-11 07:09:10'),
(194, 4, 'Login', '2025-09-11 07:09:18'),
(195, 4, 'Logout', '2025-09-11 07:11:41'),
(196, 3, 'Login', '2025-09-11 07:11:55'),
(197, 3, 'Logout', '2025-09-11 07:25:07'),
(198, 1, 'Login', '2025-09-12 01:25:58'),
(199, 1, 'Logout', '2025-09-12 01:33:41'),
(200, 1, 'Login', '2025-09-12 03:07:07'),
(201, 1, 'Logout', '2025-09-12 03:10:33'),
(202, 2, 'Login', '2025-09-12 03:10:47'),
(203, 2, 'Logout', '2025-09-12 03:17:48'),
(204, 1, 'Login', '2025-09-12 03:18:05'),
(205, 1, 'Logout', '2025-09-12 03:53:57'),
(206, 3, 'Login', '2025-09-12 03:55:56'),
(207, 3, 'HRD: ubah status lowongan #1 -> closed', '2025-09-12 03:57:33'),
(208, 3, 'Logout', '2025-09-12 03:59:11'),
(209, 7, 'Login', '2025-09-12 03:59:18'),
(210, 7, 'Logout', '2025-09-12 03:59:51'),
(211, 7, 'Login', '2025-09-12 03:59:59'),
(212, 7, 'Logout', '2025-09-12 04:02:05'),
(213, 3, 'Login', '2025-09-12 04:02:22'),
(214, 3, 'HRD: terima administrasi application #1 (Programmer Frontend)', '2025-09-12 04:20:54'),
(215, 3, 'HRD: terima administrasi application #1 (Programmer Frontend)', '2025-09-12 04:21:13'),
(216, 3, 'HRD: set interview application #1', '2025-09-12 04:21:50'),
(217, 3, 'Logout', '2025-09-12 04:26:55'),
(218, 1, 'Login', '2025-09-12 04:27:14'),
(219, 1, 'Logout', '2025-09-12 04:29:45'),
(220, 4, 'Login', '2025-09-12 04:29:52'),
(221, 4, 'Logout', '2025-09-12 06:12:08'),
(222, 2, 'Login', '2025-09-12 06:12:24'),
(223, 3, 'Login', '2025-09-13 03:23:43'),
(224, 3, 'HRD: ubah status lowongan #1 -> open', '2025-09-13 03:25:05'),
(225, 3, 'Logout', '2025-09-13 03:25:30'),
(226, 2, 'Login', '2025-09-13 03:26:05'),
(227, 4, 'Login', '2025-09-15 01:47:28'),
(228, 4, 'Logout', '2025-09-15 01:50:19'),
(229, 3, 'Login', '2025-09-15 02:38:55'),
(230, 3, 'HRD: tambah lowongan #2 - Programmer Backend', '2025-09-15 02:52:56'),
(231, 3, 'HRD: edit lowongan #1 - Programmer Frontend', '2025-09-15 02:53:16'),
(232, 3, 'HRD: ubah status lowongan #2 -> closed', '2025-09-15 02:53:18'),
(233, 3, 'Logout', '2025-09-15 03:01:21'),
(234, 2, 'Login', '2025-09-15 03:01:30'),
(235, 2, 'Logout', '2025-09-15 03:56:56'),
(236, 3, 'Login', '2025-09-15 04:05:33'),
(237, 3, 'Logout', '2025-09-15 04:05:52'),
(238, 3, 'Login', '2025-09-15 04:06:25'),
(239, 3, 'HRD: edit lowongan #1 - Programmer Frontend', '2025-09-15 04:07:52'),
(240, 3, 'Logout', '2025-09-15 04:15:05'),
(241, 4, 'Login', '2025-09-15 04:15:24'),
(242, 4, 'Logout', '2025-09-15 07:26:28'),
(243, 4, 'Login', '2025-09-15 07:26:37'),
(244, 4, 'Logout', '2025-09-15 08:44:24'),
(245, 3, 'Login', '2025-09-16 02:00:22'),
(246, 3, 'Logout', '2025-09-16 02:07:18'),
(247, 7, 'Login', '2025-09-16 02:08:30'),
(248, 7, 'Logout', '2025-09-16 05:54:12'),
(249, 1, 'Login', '2025-09-16 05:57:35'),
(250, 1, 'Logout', '2025-09-16 06:55:12'),
(251, 3, 'Login', '2025-09-16 06:55:22'),
(252, 3, 'Login', '2025-09-17 02:26:34'),
(253, 3, 'HRD: tambah popup gambar #1 - Lowongan Terbaru Di PT Waindo', '2025-09-17 02:31:27'),
(254, 3, 'HRD: toggle popup gambar #1 -> aktif', '2025-09-17 02:31:34'),
(255, 3, 'HRD: toggle popup gambar #1 -> nonaktif', '2025-09-17 02:33:35'),
(256, 3, 'HRD: toggle popup gambar #1 -> aktif', '2025-09-17 02:33:38'),
(257, 3, 'HRD: toggle popup gambar #1 -> nonaktif', '2025-09-17 02:36:54'),
(258, 3, 'Logout', '2025-09-17 02:37:47'),
(259, 1, 'Login', '2025-09-17 02:37:54'),
(260, 1, 'Logout', '2025-09-17 02:38:45'),
(261, 3, 'Login', '2025-09-17 02:38:51'),
(262, 3, 'HRD: toggle popup gambar #1 -> aktif', '2025-09-17 02:38:59'),
(263, 3, 'Logout', '2025-09-17 02:39:13'),
(264, 3, 'Login', '2025-09-17 02:47:30'),
(265, 3, 'HRD: tambah popup gambar #2 - Lowongan geospasial', '2025-09-17 06:21:11'),
(266, 3, 'HRD: toggle popup gambar #2 -> aktif', '2025-09-17 06:21:15'),
(267, 3, 'HRD: toggle popup gambar #1 -> aktif', '2025-09-17 06:21:22'),
(268, 3, 'HRD: toggle popup gambar #2 -> aktif', '2025-09-17 06:21:37'),
(269, 3, 'HRD: toggle popup gambar #1 -> aktif', '2025-09-17 08:35:13'),
(270, 3, 'HRD: toggle popup gambar #2 -> aktif', '2025-09-17 08:35:16'),
(271, 3, 'Logout', '2025-09-17 08:38:52'),
(272, 3, 'Login', '2025-09-17 08:39:08'),
(273, 3, 'HRD: toggle popup gambar #1 -> aktif', '2025-09-17 08:40:17'),
(274, 3, 'Login', '2025-09-18 07:32:03'),
(275, 1, 'Login', '2025-09-22 02:45:44'),
(276, 1, 'Logout', '2025-09-22 04:07:41'),
(277, 3, 'Login', '2025-09-22 04:07:52'),
(278, 3, 'Logout', '2025-09-22 04:20:49'),
(279, 4, 'Login', '2025-09-22 04:21:58'),
(280, 4, 'Logout', '2025-09-22 04:24:36'),
(281, 4, 'Login', '2025-09-22 04:26:22'),
(282, 4, 'Login', '2025-09-23 01:47:46'),
(283, 4, 'Konten: tambah produk - Coastal Zone Management', '2025-09-23 04:17:40'),
(284, 4, 'Konten: tambah produk - Forest & Plantation Inventory', '2025-09-23 04:35:41'),
(285, 4, 'Konten: tambah produk - Natural Resources Accounting', '2025-09-23 04:48:21'),
(286, 4, 'Konten: tambah produk - Environment Monitoring', '2025-09-23 04:49:07'),
(287, 4, 'Konten: tambah produk - Maxar', '2025-09-23 04:50:04'),
(288, 4, 'Konten: tambah produk - Planetscope', '2025-09-23 04:51:31'),
(289, 4, 'Konten: tambah produk - Radarsat', '2025-09-23 04:51:58'),
(290, 4, 'Konten: tambah produk - Scanned Map', '2025-09-23 06:45:53'),
(291, 1, 'Login', '2025-09-26 13:55:00'),
(292, 2, 'Login', '2025-09-30 08:06:26'),
(293, 2, 'Logout', '2025-09-30 08:45:20'),
(294, 4, 'Login', '2025-10-01 03:07:48'),
(295, 4, 'Konten: tambah produk - Vector Map', '2025-10-01 04:06:04'),
(296, 4, 'Konten: tambah produk - Data Converter', '2025-10-01 04:06:57'),
(297, 4, 'Konten: tambah produk - POI Data', '2025-10-01 04:07:33'),
(298, 4, 'Konten: tambah produk - Web, Desktop, Mobile Application', '2025-10-01 04:08:28'),
(299, 4, 'Konten: tambah produk - GPS Tracking System', '2025-10-01 04:09:11'),
(300, 2, 'Login', '2025-10-02 04:08:44'),
(301, 2, 'Logout', '2025-10-02 07:16:54'),
(302, 3, 'Login', '2025-10-02 07:17:02'),
(303, 4, 'Login', '2025-10-03 01:36:36'),
(304, 4, 'Logout', '2025-10-03 04:16:13'),
(305, 1, 'Login', '2025-10-03 04:16:21'),
(306, 2, 'Login', '2025-10-04 08:26:14'),
(307, 2, 'Logout', '2025-10-04 08:28:19'),
(308, 1, 'Login', '2025-10-04 08:28:27'),
(309, 1, 'Logout', '2025-10-04 08:29:36'),
(310, 3, 'Login', '2025-10-04 08:29:42'),
(311, 3, 'Logout', '2025-10-04 08:30:33'),
(312, 4, 'Login', '2025-10-04 08:30:40'),
(313, 4, 'Logout', '2025-10-04 09:17:11'),
(314, 1, 'Login', '2025-10-06 02:46:13'),
(315, 1, 'Logout', '2025-10-06 02:46:18'),
(316, 1, 'Login', '2025-10-07 02:41:59'),
(317, 1, 'Logout', '2025-10-07 02:43:07'),
(318, 3, 'Login', '2025-10-07 02:43:18'),
(319, 3, 'Logout', '2025-10-07 02:47:45'),
(320, 2, 'Login', '2025-10-07 02:48:08'),
(321, 2, 'Logout', '2025-10-07 02:48:39'),
(322, 4, 'Login', '2025-10-07 02:48:58'),
(323, 4, 'Logout', '2025-10-07 02:50:02'),
(324, 4, 'Login', '2025-10-07 04:11:22'),
(325, 4, 'Konten: edit produk - Satellite Image Services and Remote Sensing', '2025-10-07 04:13:22'),
(326, 4, 'Logout', '2025-10-07 04:13:34'),
(327, 2, 'Login', '2025-10-07 06:41:06'),
(328, 2, 'Logout', '2025-10-07 07:36:23'),
(329, 2, 'Login', '2025-10-07 08:19:49'),
(330, NULL, 'membuat User baru', '2025-10-08 02:10:42'),
(331, 8, 'Login', '2025-10-08 02:10:59'),
(332, 8, 'Logout', '2025-10-08 05:55:12'),
(333, 3, 'Login', '2025-10-08 05:55:21'),
(334, 3, 'Logout', '2025-10-08 07:38:56'),
(335, 1, 'Login', '2025-10-08 07:39:04'),
(336, 1, 'Logout', '2025-10-08 07:43:17'),
(337, 1, 'Login', '2025-10-08 07:43:23'),
(338, 2, 'Login', '2025-10-13 04:31:18'),
(339, 2, 'Logout', '2025-10-13 04:31:55'),
(340, 1, 'Login', '2025-10-14 01:14:01'),
(341, 3, 'Login', '2025-10-14 06:02:26'),
(342, 3, 'HRD: ubah status lowongan #2 -> closed', '2025-10-14 06:27:52'),
(343, 3, 'HRD: ubah status lowongan #2 -> open', '2025-10-14 06:27:54'),
(344, 3, 'Logout', '2025-10-14 07:05:42'),
(345, 2, 'Login', '2025-10-14 07:05:48'),
(346, 2, 'Logout', '2025-10-14 07:14:18'),
(347, 8, 'Login', '2025-10-14 07:14:55'),
(348, 8, 'Logout', '2025-10-14 07:25:33'),
(349, 4, 'Login', '2025-10-15 01:20:24'),
(350, 1, 'Login', '2025-10-17 02:33:30'),
(351, 1, 'Logout', '2025-10-17 02:33:49'),
(352, 1, 'Login', '2025-10-17 02:42:02'),
(353, 1, 'Logout', '2025-10-17 02:43:23'),
(354, 2, 'Login', '2025-10-17 02:49:16'),
(355, 2, 'Logout', '2025-10-17 02:49:35'),
(356, 8, 'Login', '2025-10-17 02:49:49'),
(357, 8, 'Logout', '2025-10-17 03:10:03'),
(358, 2, 'Login', '2025-10-20 02:57:49'),
(359, 2, 'Logout', '2025-10-20 02:59:35'),
(360, 3, 'Login', '2025-10-20 02:59:43'),
(361, 3, 'Logout', '2025-10-20 03:03:50'),
(362, 1, 'Login', '2025-10-20 03:03:56'),
(363, 1, 'Logout', '2025-10-20 03:07:55'),
(364, 2, 'Login', '2025-10-20 03:12:10'),
(365, 2, 'Logout', '2025-10-20 03:24:37'),
(366, 8, 'Login', '2025-10-20 03:24:46'),
(367, 8, 'Logout', '2025-10-20 03:29:42'),
(368, 3, 'Login', '2025-10-20 03:29:50'),
(369, 3, 'Login', '2025-10-22 03:34:12'),
(370, 3, 'Logout', '2025-10-22 03:38:43'),
(371, 1, 'Login', '2025-10-22 03:38:48'),
(372, 1, 'Login', '2025-10-23 01:10:33'),
(373, 1, 'Logout', '2025-10-23 01:10:42'),
(374, 1, 'Login', '2025-10-23 01:11:00'),
(375, 1, 'Logout', '2025-10-23 01:13:53'),
(376, 3, 'Login', '2025-10-23 01:14:31'),
(377, 3, 'Logout', '2025-10-23 01:16:23'),
(378, 2, 'Login', '2025-10-23 01:16:39'),
(379, 2, 'Logout', '2025-10-23 01:18:08'),
(380, 4, 'Login', '2025-10-23 01:18:20'),
(381, 4, 'Logout', '2025-10-23 01:22:26'),
(382, 1, 'Login', '2025-10-23 06:44:35'),
(383, 1, 'Logout', '2025-10-23 06:50:59'),
(384, 3, 'Login', '2025-10-23 06:51:10'),
(385, 3, 'Logout', '2025-10-23 06:58:57'),
(386, 8, 'Login', '2025-10-23 07:01:10'),
(387, 8, 'Logout', '2025-10-23 07:02:58'),
(388, 4, 'Login', '2025-10-23 07:03:05'),
(389, 4, 'Logout', '2025-10-23 09:48:23'),
(390, 2, 'Login', '2025-10-23 09:48:33'),
(391, 2, 'Login', '2025-10-28 08:41:50'),
(392, 2, 'Login', '2025-11-22 02:29:51'),
(393, 2, 'Logout', '2025-11-22 02:34:04'),
(394, 3, 'Login', '2025-11-22 02:34:17'),
(395, 2, 'Login', '2025-11-22 13:19:49'),
(396, 2, 'Logout', '2025-11-22 14:15:05'),
(397, 1, 'Login', '2025-11-22 14:15:13'),
(398, 1, 'Login', '2025-11-23 02:26:09'),
(399, 1, 'Admin: edit jenjang pendidikan \'SMA\' - tanpa jurusan → dengan jurusan', '2025-11-23 02:37:26'),
(400, 1, 'Admin: edit jenjang pendidikan \'SMA\' - dengan jurusan → tanpa jurusan', '2025-11-23 02:37:36'),
(401, 1, 'Admin: edit jenjang pendidikan \'SMA\' - tanpa jurusan → dengan jurusan', '2025-11-23 03:02:20'),
(402, 1, 'Admin: edit jenjang pendidikan \'SMA\' - dengan jurusan → tanpa jurusan', '2025-11-23 03:02:25'),
(403, 1, 'Logout', '2025-11-23 04:52:53'),
(404, 2, 'Login', '2025-11-23 04:53:36'),
(405, 2, 'Update profil: No. Telepon: \'belum diisi\' → \'0812345678910\', Jenjang Pendidikan: \'belum diisi\' → \'S1\', Jurusan: \'belum diisi\' → \'Teknik Informatika\', CV: Upload CV baru \'cv_2_1763881705.pdf\'', '2025-11-23 07:08:25'),
(406, 2, 'Logout', '2025-11-23 07:24:10'),
(407, 3, 'Login', '2025-11-23 07:24:19'),
(408, 2, 'Login', '2025-11-24 02:09:09'),
(409, 2, 'Logout', '2025-11-24 02:10:57'),
(410, 3, 'Login', '2025-11-24 02:11:04'),
(411, 3, 'Login', '2025-11-24 11:31:41'),
(412, 3, 'HRD: tambah lowongan #3 - Divisi IT', '2025-11-24 11:55:18'),
(413, 3, 'Logout', '2025-11-24 11:55:24'),
(414, 2, 'Login', '2025-11-24 11:55:31'),
(415, 2, 'Logout', '2025-11-24 11:55:42'),
(416, 8, 'Login', '2025-11-24 11:55:51'),
(417, 8, 'Update profil: No. Telepon: \'belum diisi\' → \'08131278923178\', Jenjang Pendidikan: \'belum diisi\' → \'SMK\', Jurusan: \'belum diisi\' → \'Desain Komunikasi Visual\', CV: Upload CV baru \'cv_8_1763985384.pdf\'', '2025-11-24 11:56:24'),
(418, 8, 'Logout', '2025-11-24 11:56:48'),
(419, 2, 'Login', '2025-11-24 12:09:10'),
(420, 2, 'Kirim lamaran (job #3)', '2025-11-24 12:09:31'),
(421, 2, 'Logout', '2025-11-24 12:21:55'),
(422, 3, 'Login', '2025-11-24 12:22:01'),
(423, 3, 'Logout', '2025-11-24 13:16:59'),
(424, 1, 'Login', '2025-11-24 13:17:05'),
(425, 1, 'Logout', '2025-11-24 13:23:30'),
(426, 3, 'Login', '2025-11-24 13:23:36'),
(427, 3, 'HRD: terima administrasi application #5 (Divisi IT)', '2025-11-24 13:24:19'),
(428, 3, 'HRD: set interview application #5', '2025-11-24 13:24:46'),
(429, 3, 'HRD: terima bekerja application #5 (Divisi IT)', '2025-11-24 13:25:18'),
(430, 3, 'Logout', '2025-11-24 13:42:24'),
(431, 1, 'Login', '2025-11-24 13:42:31'),
(432, 2, 'Login', '2025-11-25 07:23:05'),
(433, 2, 'Kirim lamaran (job #3)', '2025-11-25 07:24:36'),
(434, 2, 'Logout', '2025-11-25 07:24:47'),
(435, 3, 'Login', '2025-11-25 07:25:02'),
(436, 3, 'HRD: terima administrasi application #6 (Divisi IT)', '2025-11-25 07:31:23'),
(437, 3, 'HRD: set interview application #6', '2025-11-25 07:32:36'),
(438, 3, 'Logout', '2025-11-25 07:35:02'),
(439, 1, 'Login', '2025-11-25 07:35:18'),
(440, 1, 'Logout', '2025-11-25 07:36:51'),
(441, 4, 'Login', '2025-11-25 07:37:04'),
(442, 4, 'Logout', '2025-11-25 07:40:34'),
(443, 3, 'Login', '2025-11-25 07:40:57'),
(444, 3, 'Logout', '2025-11-25 07:41:06'),
(445, 1, 'Login', '2025-11-25 07:41:14'),
(446, 1, 'Logout', '2025-11-25 07:42:50'),
(447, 3, 'Login', '2025-11-25 07:43:25'),
(448, 3, 'HRD: terima bekerja application #6 (Divisi IT)', '2025-11-25 07:46:14'),
(449, 3, 'Logout', '2025-11-25 07:46:18'),
(450, 1, 'Login', '2025-11-25 07:46:28'),
(451, 1, 'Logout', '2025-11-25 07:46:45'),
(452, 3, 'Login', '2025-11-25 07:46:52'),
(453, 3, 'Logout', '2025-11-25 07:48:29'),
(454, 1, 'Login', '2025-11-25 07:48:35'),
(455, 1, 'Login', '2025-11-25 11:36:06'),
(456, 1, 'Logout', '2025-11-25 11:36:11'),
(457, 3, 'Login', '2025-11-25 11:36:27'),
(458, 3, 'Logout', '2025-11-25 11:54:19'),
(459, 8, 'Login', '2025-11-25 11:54:43'),
(460, 8, 'Update profil: Jenjang Pendidikan: \'SMK\' → \'S1\', Jurusan: \'Desain Komunikasi Visual\' → \'Teknik Informatika\'', '2025-11-25 11:54:58'),
(461, 8, 'Kirim lamaran (job #3)', '2025-11-25 11:55:09'),
(462, 8, 'Logout', '2025-11-25 11:55:17'),
(463, 3, 'Login', '2025-11-25 11:55:24'),
(464, 3, 'HRD: terima administrasi application #7 (Divisi IT)', '2025-11-25 12:19:31'),
(465, 3, 'HRD: terima administrasi application #7 (Divisi IT)', '2025-11-25 12:19:34'),
(466, NULL, 'membuat User baru', '2025-11-26 00:45:02'),
(467, 9, 'Login', '2025-11-26 00:45:12'),
(468, 9, 'Update profil: No. Telepon: \'belum diisi\' → \'0831279132789\', Jenjang Pendidikan: \'belum diisi\' → \'S1\', Jurusan: \'belum diisi\' → \'Teknik Informatika\', CV: Upload CV baru \'cv_9_1764117960.pdf\'', '2025-11-26 00:46:00'),
(469, 9, 'Kirim lamaran (job #3)', '2025-11-26 00:47:56'),
(470, 9, 'Logout', '2025-11-26 00:48:10'),
(471, 3, 'Login', '2025-11-26 00:48:17'),
(472, 3, 'HRD: tolak administrasi application #8 (Divisi IT)', '2025-11-26 00:51:09'),
(473, 3, 'HRD: tolak kandidat application #7 (Divisi IT)', '2025-11-26 00:51:46'),
(474, 3, 'HRD: tolak kandidat application #7 (Divisi IT)', '2025-11-26 00:53:06'),
(475, 3, 'HRD: set interview application #7', '2025-11-26 00:55:37'),
(476, 3, 'HRD: tolak setelah interview application #7 (Divisi IT)', '2025-11-26 00:55:55'),
(477, 3, 'Logout', '2025-11-26 01:07:21'),
(478, 8, 'Login', '2025-11-26 01:07:29'),
(479, 8, 'Logout', '2025-11-26 01:31:34'),
(480, 3, 'Login', '2025-11-26 01:31:53'),
(481, 3, 'HRD: tambah lowongan #4 - Divisi Software', '2025-11-26 01:33:14'),
(482, 3, 'Logout', '2025-11-26 01:33:19'),
(483, 8, 'Login', '2025-11-26 01:33:30'),
(484, 8, 'Kirim lamaran (job #4)', '2025-11-26 01:34:18'),
(485, 8, 'Logout', '2025-11-26 01:34:21'),
(486, 3, 'Login', '2025-11-26 01:34:29'),
(487, 3, 'HRD: terima administrasi application #9 (Divisi Software)', '2025-11-26 01:34:54'),
(488, 3, 'HRD: set interview application #9', '2025-11-26 01:35:18'),
(489, 3, 'Logout', '2025-11-26 01:35:25'),
(490, 8, 'Login', '2025-11-26 01:35:37'),
(491, 8, 'Logout', '2025-11-26 01:36:07'),
(492, 3, 'Login', '2025-11-26 01:36:14'),
(493, 3, 'HRD: terima bekerja application #9 (Divisi Software)', '2025-11-26 01:36:43'),
(494, 3, 'Logout', '2025-11-26 01:36:54'),
(495, 8, 'Login', '2025-11-26 01:37:04'),
(496, 8, 'Logout', '2025-11-26 01:37:36'),
(497, 1, 'Login', '2025-11-26 01:37:46'),
(498, 1, 'Logout', '2025-11-26 03:12:20'),
(499, 3, 'Login', '2025-11-26 03:12:28'),
(500, 3, 'Logout', '2025-11-26 03:12:43'),
(501, 8, 'Login', '2025-11-26 03:12:59'),
(502, 8, 'Logout', '2025-11-26 03:14:10'),
(503, 1, 'Login', '2025-11-27 06:12:01'),
(504, 1, 'Logout', '2025-11-27 07:38:14'),
(505, 2, 'Login', '2025-11-27 07:41:37'),
(506, 2, 'Logout', '2025-11-27 07:45:40'),
(507, 3, 'Login', '2025-11-27 07:45:45'),
(508, 3, 'Login', '2025-11-27 11:40:09'),
(509, 3, 'HRD: terima administrasi application #8 (Divisi IT)', '2025-11-27 11:40:52'),
(510, 3, 'Logout', '2025-11-27 11:43:02'),
(511, 1, 'Login', '2025-11-27 11:43:08'),
(512, 1, 'Login', '2026-02-26 03:07:21'),
(513, 1, 'Logout', '2026-02-26 03:08:22'),
(514, 2, 'Login', '2026-02-26 03:08:45'),
(515, 2, 'Kirim lamaran (job #4)', '2026-02-26 03:09:54'),
(516, 2, 'Logout', '2026-02-26 03:10:01'),
(517, 3, 'Login', '2026-02-26 03:10:10'),
(518, 3, 'HRD: terima administrasi application #10 (Divisi Software)', '2026-02-26 03:10:54'),
(519, 3, 'HRD: set interview application #10', '2026-02-26 03:11:41'),
(520, 3, 'HRD: terima bekerja application #10 (Divisi Software)', '2026-02-26 03:12:19'),
(521, 3, 'Logout', '2026-02-26 03:12:44'),
(522, 1, 'Login', '2026-02-26 03:12:51'),
(523, 3, 'Login', '2026-03-30 06:50:08'),
(524, 3, 'Logout', '2026-03-30 06:57:03'),
(525, 3, 'Login', '2026-03-30 06:57:10'),
(526, 3, 'Logout', '2026-03-30 07:09:10'),
(527, 1, 'Login', '2026-03-30 07:09:17'),
(528, NULL, 'membuat User baru', '2026-03-31 04:33:36'),
(529, NULL, 'membuat User baru', '2026-03-31 04:34:31'),
(530, 10, 'Login', '2026-03-31 04:34:42'),
(531, 10, 'Update profil: No. Telepon: \'belum diisi\' → \'08798465132\', Jenjang Pendidikan: \'belum diisi\' → \'S1\', Jurusan: \'belum diisi\' → \'Teknik Informatika\', CV: Upload CV baru \'cv_10_1774931728.pdf\'', '2026-03-31 04:35:28'),
(532, 3, 'Login', '2026-03-31 07:03:22'),
(533, 3, 'Logout', '2026-03-31 07:04:44'),
(534, 10, 'Login', '2026-03-31 07:08:20'),
(535, 10, 'Kirim lamaran (job #3)', '2026-03-31 07:09:12'),
(536, 10, 'Logout', '2026-03-31 07:09:39'),
(537, 3, 'Login', '2026-03-31 07:11:49'),
(538, 3, 'HRD: terima administrasi application #11 (Divisi IT)', '2026-03-31 07:12:37'),
(539, 3, 'HRD: set interview application #11', '2026-03-31 07:13:18'),
(540, 3, 'HRD: terima bekerja application #11 (Divisi IT)', '2026-03-31 07:15:09'),
(541, 3, 'Logout', '2026-03-31 07:15:32'),
(542, 1, 'Login', '2026-03-31 07:15:39'),
(543, 10, 'Login', '2026-04-01 02:08:57'),
(544, 10, 'Logout', '2026-04-01 04:34:07'),
(545, 3, 'Login', '2026-04-01 04:34:13'),
(546, 3, 'Logout', '2026-04-01 04:48:39'),
(547, 3, 'Login', '2026-04-01 04:48:52'),
(548, 3, 'Logout', '2026-04-01 04:48:59'),
(549, 10, 'Login', '2026-04-01 04:49:05'),
(550, 10, 'Logout', '2026-04-01 04:55:47'),
(551, 3, 'Login', '2026-04-01 04:56:07'),
(552, 3, 'HRD: edit lowongan #4 - Divisi Software', '2026-04-01 04:56:30'),
(553, 3, 'Logout', '2026-04-01 04:56:36'),
(554, 10, 'Login', '2026-04-01 04:56:43'),
(555, 10, 'Login', '2026-04-01 06:44:42'),
(556, 10, 'Kirim lamaran (job #4)', '2026-04-01 07:53:22'),
(557, 10, 'Logout', '2026-04-01 07:53:43'),
(558, 3, 'Login', '2026-04-01 07:53:50'),
(559, 3, 'Logout', '2026-04-01 07:54:52'),
(560, 10, 'Login', '2026-04-01 13:54:24'),
(561, 10, 'Kirim lamaran (job #4)', '2026-04-01 14:02:05'),
(562, 3, 'Login', '2026-04-02 03:27:51'),
(563, 3, 'Logout', '2026-04-02 03:45:32'),
(564, 1, 'Login', '2026-04-02 03:45:43'),
(565, 1, 'Logout', '2026-04-02 03:46:31'),
(566, 10, 'Login', '2026-04-02 03:46:44'),
(567, 10, 'Kirim lamaran (job #4)', '2026-04-02 03:46:50'),
(568, 10, 'Logout', '2026-04-02 03:46:56'),
(569, 6, 'Login', '2026-04-02 03:47:04'),
(570, 6, 'Logout', '2026-04-02 03:47:22'),
(571, 3, 'Login', '2026-04-02 03:47:31'),
(572, 3, 'HRD: tambah catatan internal application #14', '2026-04-02 03:47:56'),
(573, 3, 'HRD: terima administrasi application #14 (Divisi Software)', '2026-04-02 03:48:31'),
(574, 3, 'Logout', '2026-04-02 03:49:26'),
(575, 10, 'Login', '2026-04-02 03:49:41'),
(576, 10, 'Logout', '2026-04-02 03:50:23'),
(577, 3, 'Login', '2026-04-02 03:50:29'),
(578, 3, 'HRD: set interview application #14', '2026-04-02 03:50:43'),
(579, 3, 'HRD: tolak setelah interview application #14 (Divisi Software)', '2026-04-02 03:50:51'),
(580, 3, 'Logout', '2026-04-02 03:50:58'),
(581, 10, 'Login', '2026-04-02 03:51:09'),
(582, 10, 'Kirim lamaran (job #4)', '2026-04-02 04:34:53'),
(583, 10, 'Logout', '2026-04-02 04:34:54'),
(584, 3, 'Login', '2026-04-02 04:34:59'),
(585, 3, 'HRD: tambah catatan untuk kandidat #10 (lamaran #15)', '2026-04-02 04:35:36'),
(586, 3, 'HRD: tolak administrasi application #15 (Divisi Software)', '2026-04-02 04:35:54'),
(587, 3, 'Logout', '2026-04-02 04:36:03'),
(588, 10, 'Login', '2026-04-02 04:36:14'),
(589, 10, 'Kirim lamaran (job #3)', '2026-04-02 04:36:28'),
(590, 10, 'Logout', '2026-04-02 04:36:30'),
(591, 3, 'Login', '2026-04-02 04:36:38'),
(592, 3, 'Logout', '2026-04-02 04:37:07'),
(593, 10, 'Login', '2026-04-02 04:37:20');

-- --------------------------------------------------------

--
-- Table structure for table `lowongan`
--

CREATE TABLE `lowongan` (
  `job_id` int NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `requirements` text,
  `location` varchar(100) DEFAULT NULL,
  `salary_range` varchar(50) DEFAULT NULL,
  `status` enum('open','closed') DEFAULT 'open',
  `posted_by` int DEFAULT NULL,
  `req_jenjang_pendidikan` int DEFAULT NULL,
  `req_jurusan_pendidikan` int DEFAULT NULL,
  `posted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deadline` datetime DEFAULT NULL COMMENT 'Batas waktu pendaftaran, NULL = tidak terbatas',
  `quota` int UNSIGNED DEFAULT NULL COMMENT 'Kuota pendaftar, NULL = tidak terbatas',
  `close_reason` text COMMENT 'Alasan penutupan otomatis oleh sistem',
  `hapus` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `lowongan`
--

INSERT INTO `lowongan` (`job_id`, `title`, `description`, `requirements`, `location`, `salary_range`, `status`, `posted_by`, `req_jenjang_pendidikan`, `req_jurusan_pendidikan`, `posted_at`, `updated_at`, `deadline`, `quota`, `close_reason`, `hapus`) VALUES
(3, 'Divisi IT', 'Bekerja pada divisi IT', '1. Bekerja On-Site\r\n2. Berpengalaman dalam mengurus project\r\n3. Bersedia mengikuti jam kerja yang tertera', 'PT Waindo Specterra', '10.000.000 - 15.000.000', 'open', 3, 3, 3, '2025-11-24 11:55:18', NULL, NULL, NULL, NULL, 0),
(4, 'Divisi Software', 'aaa', 'aaaa', 'PT Waindo Specterra', '10.000.000 - 15.000.000', 'open', 3, 3, 3, '2025-11-26 01:33:14', '2026-04-01 04:56:30', '2026-04-16 11:56:00', 10, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `popup_images`
--

CREATE TABLE `popup_images` (
  `popup_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `image_filename` varchar(255) NOT NULL,
  `orientation` enum('vertical','horizontal') DEFAULT 'vertical',
  `is_active` tinyint(1) DEFAULT '0',
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `popup_images`
--

INSERT INTO `popup_images` (`popup_id`, `title`, `image_filename`, `orientation`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Lowongan Terbaru Di PT Waindo', 'popup_vertical_68ca1d7f08849.png', 'vertical', 1, 3, '2025-09-17 02:31:27', '2025-09-17 08:40:17'),
(2, 'Lowongan geospasial', 'popup_horizontal_68ca5357b1b93.jpg', 'horizontal', 1, 3, '2025-09-17 06:21:11', '2025-09-17 08:35:16');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text,
  `image` varchar(255) DEFAULT NULL,
  `category_id` int NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `name`, `description`, `image`, `category_id`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Satellite Image Services and Remote Sensing', 'Satellite Image \\\"Services and Remote Sensing\\\" menyediakan layanan citra satelit resolusi tinggi yang dirancang untuk mendukung berbagai kebutuhan analisis spasial dan pemantauan lingkungan. Dengan teknologi penginderaan jauh terkini, layanan ini memungkinkan akuisisi data multispektral dan radar secara cepat dan akurat. Cocok untuk keperluan pemetaan lahan, deteksi perubahan tutupan wilayah, perencanaan tata ruang, hingga pemantauan sumber daya alam secara berkelanjutan. Dilengkapi dengan sistem pengolahan data berbasis cloud untuk efisiensi akses, penyimpanan, dan analisis citra satelit dalam skala besar.', 'assets/remotesesing.jpg', 1, 'active', 4, '2025-09-22 08:55:33', '2025-10-07 04:13:22'),
(2, 'Geographic Information System', 'Sistem informasi geografis yang komprehensif untuk pengolahan, analisis, serta visualisasi data spasial. GIS ini membantu dalam pengambilan keputusan berbasis lokasi, pemetaan interaktif, hingga integrasi data multi-sumber sehingga dapat digunakan oleh berbagai sektor, mulai dari tata ruang, lingkungan, hingga infrastruktur.', 'assets/Geograpic-Information-System.jpg', 1, 'active', 4, '2025-09-22 08:55:33', '2025-09-22 08:55:33'),
(3, 'ArcGIS For Desktop', 'Perangkat lunak GIS desktop yang menjadi standar industri dalam analisis geospasial. Menyediakan berbagai tools untuk pemetaan, manajemen data spasial, hingga analisis tingkat lanjut, sehingga sangat ideal digunakan oleh pemerintah, perusahaan, maupun akademisi.', 'assets/arcgis_destop.jpg', 2, 'active', 4, '2025-09-22 08:55:33', '2025-09-22 08:55:33'),
(4, 'ArcGIS Enterprise', 'Platform GIS berbasis enterprise yang dirancang untuk organisasi besar. Mendukung integrasi data spasial lintas divisi, memungkinkan kolaborasi antar pengguna, serta menyediakan kontrol penuh terhadap keamanan dan distribusi informasi geospasial.', 'assets/arcgisportal.jpg', 2, 'active', 4, '2025-09-22 08:55:33', '2025-09-22 08:55:33'),
(5, 'Coastal Zone Management', 'Sistem manajemen zona pesisir yang dirancang untuk monitoring, perlindungan, serta pengelolaan lingkungan pantai. Membantu dalam konservasi ekosistem pesisir, mitigasi abrasi, hingga perencanaan tata ruang wilayah pesisir.', 'assets/coastal zone.jpg', 3, 'active', 4, '2025-09-23 04:17:40', '2025-09-23 04:18:43'),
(6, 'Forest & Plantation Inventory', 'Sistem inventarisasi hutan dan perkebunan untuk memantau luas, jenis vegetasi, serta potensi sumber daya kehutanan. Dapat digunakan untuk pengelolaan hutan lestari, perencanaan produksi, maupun konservasi keanekaragaman hayati.', 'assets/products/product_68d2239d23d42.jpg', 3, 'active', 4, '2025-09-23 04:35:41', '2025-09-23 04:35:41'),
(7, 'Natural Resources Accounting', 'Sistem akuntansi sumber daya alam yang digunakan untuk menghitung nilai ekonomi dari hutan, tambang, air, hingga energi. Mendukung perencanaan pembangunan berkelanjutan dengan data kuantitatif dan komprehensif.', 'assets/products/product_68d22695803fe.jpg', 3, 'active', 4, '2025-09-23 04:48:21', '2025-09-23 04:48:21'),
(8, 'Environment Monitoring', 'Sistem pemantauan lingkungan yang fokus pada kualitas air, udara, dan tanah. Memberikan data real-time yang penting bagi pengendalian pencemaran, kesehatan lingkungan, hingga perumusan kebijakan berbasis data.', 'assets/products/product_68d226c30f6de.jpg', 3, 'active', 4, '2025-09-23 04:49:07', '2025-09-23 04:49:07'),
(9, 'Maxar', 'Penyedia citra satelit resolusi tinggi dengan cakupan global. Data dari Maxar mendukung berbagai aplikasi, mulai dari pemetaan kota, pertanian presisi, hingga pemantauan bencana alam.', 'assets/products/product_68d226fce46c8.jpeg', 4, 'active', 4, '2025-09-23 04:50:04', '2025-09-23 04:50:04'),
(10, 'Planetscope', 'Citra satelit harian yang memudahkan monitoring perubahan lahan, pertanian, hingga lingkungan. Dengan pembaruan data yang cepat, Planetscope sangat berguna untuk mendeteksi dinamika penggunaan lahan secara periodik.', 'assets/products/product_68d2275339a30.png', 4, 'active', 4, '2025-09-23 04:51:31', '2025-09-23 04:51:31'),
(11, 'Radarsat', 'Data radar satelit yang mampu menembus awan dan digunakan untuk monitoring cuaca, pemetaan wilayah terpencil, serta analisis perubahan permukaan bumi. Cocok untuk wilayah tropis dengan tutupan awan tinggi.', 'assets/products/product_68d2276e0f2db.jpg', 4, 'active', 4, '2025-09-23 04:51:58', '2025-09-23 04:51:58'),
(12, 'Scanned Map', 'Peta digital hasil scanning dari peta cetak yang bernilai historis. Memberikan referensi penting untuk analisis perubahan lahan, penelitian sejarah, serta pelestarian data spasial lama.', 'assets/products/product_68d24221a5068.jpg', 4, 'active', 4, '2025-09-23 06:45:53', '2025-09-23 06:45:53'),
(13, 'Vector Map', 'Data vektor berkualitas tinggi untuk analisis spasial dan pembuatan peta tematik. Fleksibel digunakan di berbagai software GIS dan dapat dikustomisasi sesuai kebutuhan analisis', 'assets/products/product_68dca8aca1a78.jpg', 4, 'active', 4, '2025-10-01 04:06:04', '2025-10-01 04:06:04'),
(14, 'Data Converter', 'Tools konversi data geospasial antar format yang memudahkan interoperabilitas antar sistem. Mendukung berbagai format standar sehingga mempermudah integrasi lintas platform.', 'assets/products/product_68dca8e1b94d6.jpg', 4, 'active', 4, '2025-10-01 04:06:57', '2025-10-01 04:06:57'),
(15, 'POI Data', 'Database Points of Interest yang berisi lokasi penting seperti fasilitas umum, transportasi, hingga destinasi wisata. Sangat berguna untuk navigasi, analisis lokasi bisnis, serta perencanaan transportasi.', 'assets/products/product_68dca905bdd25.png', 4, 'active', 4, '2025-10-01 04:07:33', '2025-10-01 04:07:33'),
(16, 'Web, Desktop, Mobile Application', 'Layanan pengembangan aplikasi GIS lintas platform, baik untuk web, desktop, maupun mobile. Aplikasi dapat disesuaikan dengan kebutuhan, mulai dari sistem monitoring, pemetaan interaktif, hingga dashboard analitik.', 'assets/products/product_68dca93ceec07.jpg', 5, 'active', 4, '2025-10-01 04:08:28', '2025-10-01 04:08:28'),
(17, 'GPS Tracking System', 'Sistem pelacakan GPS yang memungkinkan monitoring kendaraan, aset, maupun personel secara real-time. Cocok digunakan untuk manajemen armada, keamanan aset, hingga pengelolaan logistik yang efisien.', 'assets/products/product_68dca967d10f4.jpeg', 5, 'active', 4, '2025-10-01 04:09:11', '2025-10-01 04:09:11');

-- --------------------------------------------------------

--
-- Table structure for table `product_categories`
--

CREATE TABLE `product_categories` (
  `category_id` int NOT NULL,
  `category_key` varchar(50) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `category_description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `product_categories`
--

INSERT INTO `product_categories` (`category_id`, `category_key`, `category_name`, `category_description`, `created_at`, `updated_at`) VALUES
(1, 'geomatic-applications', 'Geomatic Applications', 'Solusi aplikasi geomatika untuk survei, pemetaan, dan analisis geospasial', '2025-09-22 08:55:33', '2025-09-22 08:55:33'),
(2, 'software-provider', 'Software Provider', 'Software dan platform GIS terdepan untuk analisis geospasial dan pemetaan', '2025-09-22 08:55:33', '2025-09-22 08:55:33'),
(3, 'enrm', 'Environment & Natural Resources Management', 'Solusi manajemen lingkungan dan sumber daya alam untuk pembangunan berkelanjutan', '2025-09-22 08:55:33', '2025-09-22 08:55:33'),
(4, 'gis-data-provider', 'GIS Data Provider', 'Penyedia data geospasial dan citra satelit untuk berbagai kebutuhan pemetaan', '2025-09-22 08:55:33', '2025-09-22 08:55:33'),
(5, 'gis-information-technology', 'GIS & Information Technology', 'Aplikasi dan sistem teknologi informasi geografis untuk berbagai platform', '2025-09-22 08:55:33', '2025-09-22 08:55:33');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `service_id` int NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text,
  `features` text,
  `image` varchar(255) DEFAULT NULL,
  `category` enum('foto-dan-lidar','survey','tematik','training','software') NOT NULL,
  `order_position` int DEFAULT '0',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`service_id`, `title`, `description`, `features`, `image`, `category`, `order_position`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Pengambilan & Pengolahan Data LIDAR', 'Layanan pengambilan dan pengolahan data LIDAR dengan teknologi terdepan untuk menghasilkan model digital yang akurat.', 'Data Raw LIDAR|Pengolahan dan Pengklasifikasian Point Clouds LIDAR|Digital Surface Model (DSM)|Digital Terrain Model (DTM)|LIDAR Intensity Images', 'assets/Lidar.png', 'foto-dan-lidar', 1, 'active', 4, '2025-09-23 02:35:21', '2025-09-23 02:35:21'),
(2, 'Pengambilan & Pengolahan Foto Udara Digital', 'Layanan pengambilan dan pengolahan foto udara digital menggunakan teknologi drone dan pesawat terbang.', 'Triangulasi Udara|Stereomodel|Mosaik Orthophoto', 'assets/Data-Lidar.png', 'foto-dan-lidar', 2, 'active', 4, '2025-09-23 02:35:21', '2025-09-23 02:35:21'),
(3, 'Survey – Hydrografi dan Terestrial dengan GPS dan 3D Mobile System', 'Layanan survey komprehensif menggunakan teknologi GPS dan sistem mobile 3D untuk berbagai kebutuhan pemetaan.', 'Survey karakteristik perairan, danau dan sungai|Survey Topografi|Survey Toponimi Wilayah|Road Survey', 'assets/LandSurvey.png', 'survey', 1, 'active', 4, '2025-09-23 02:35:21', '2025-09-23 02:35:21'),
(4, 'Pemetaan Penutup Lahan', 'Sasaran dari kegiatan Pemetaan Penutup Lahan adalah menghasilkan informasi geospasial tematik yang akurat dan terkini.', 'Informasi Geospasial Tematik Penutup Lahan skala 1 : 50.000 dalam format NLP dan seamless (Region, provinsi, kabupaten)|Buku Deskripsi Analisis Pembaruan Peta Penutup Lahan|Metadata Pembaruan Peta Penutup Lahan', 'assets/tematik1.png', 'tematik', 1, 'active', 4, '2025-09-23 02:35:21', '2025-09-23 02:35:21'),
(5, 'Layout Peta Penutup Lahan Provinsi', 'Proses digitasi dan interpolasi 3D untuk menghasilkan peta penutup lahan yang komprehensif dengan analisis ketinggian.', 'Hasil digitasi data penutup lahan dilakukan interpolasi 3D|Data yang digunakan untuk proses penutup lahan yaitu data DSM (Digital Surface Model) dan DTM (Digital Terrain Model)|Setelah dilakukan proses interpolasi akan menghasilkan data ketinggian sesuai vertek di data penutup lahan 2D|Setelah data sudah terisi semua nilai x,y dan z maka melakukan analisis 3D menggunakan extension 3D analyst dengan metode Interpolate shape|Konversi data vektor 2D ke data 3D dengan metode pengambilan ketinggian (Z) dari data DEMNAS dan DTM', 'assets/tematik2.png', 'tematik', 2, 'active', 4, '2025-09-23 02:35:21', '2025-09-23 02:35:21'),
(6, 'Training Dispotrud', 'Program pelatihan komprehensif untuk penggunaan teknologi GIS dan aplikasi mobile dalam pengumpulan data lapangan.', 'Menggunakan Mobile Application untuk collecting data lapangan|Pembuatan Database Dengan Menggunakan Arcgis Pro(Proses Digitasi, Editing dan Attribut dan pembuatan Geodatabase)|Visualisasi 3D Menggunakan Arcgis Pro', 'assets/Dispotrud.jpeg', 'training', 1, 'active', 4, '2025-09-23 02:35:21', '2025-09-23 02:35:21'),
(7, 'DDS Application', 'Aplikasi DDS yang dikembangkan khusus untuk kebutuhan manajemen data dan informasi perusahaan.', '', 'assets/ddsaplication.jpeg', 'software', 1, 'active', 4, '2025-09-23 02:35:21', '2025-09-23 02:35:21'),
(8, 'SIPETA Application', 'Sistem Informasi Peta (SIPETA) untuk manajemen dan visualisasi data geospasial secara terintegrasi.', '', 'assets/sipeta.jpeg', 'software', 2, 'active', 4, '2025-09-23 02:35:21', '2025-09-23 02:35:21'),
(9, 'WebGIS & Mobile Apps', 'Pengembangan aplikasi WebGIS dan Mobile Apps untuk akses data geospasial yang mudah dan cepat.', '', 'assets/webgis.jpeg', 'software', 3, 'active', 4, '2025-09-23 02:35:21', '2025-09-23 02:35:21');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `no_telepon` varchar(20) DEFAULT NULL,
  `id_jenjang_pendidikan` int DEFAULT NULL,
  `id_jurusan_pendidikan` int DEFAULT NULL,
  `cv_filename` varchar(255) DEFAULT NULL,
  `role` enum('admin','pelamar','hrd','konten') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'pelamar',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `hapus` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `full_name`, `no_telepon`, `id_jenjang_pendidikan`, `id_jurusan_pendidikan`, `cv_filename`, `role`, `status`, `created_at`, `hapus`) VALUES
(1, 'admin01', '0192023a7bbd73250516f069df18b500', 'admin@gmail.com', 'Admin', NULL, NULL, NULL, NULL, 'admin', 'active', '2025-08-19 03:54:54', 0),
(2, 'pelamar01', '9c5fa085ce256c7c598f6710584ab25d', 'rekishiilucy123@gmail.com', 'Budi Santoso', '0812345678910', 3, 3, 'cv_2_1763881705.pdf', 'pelamar', 'active', '2025-08-19 03:54:54', 0),
(3, 'hrd01', '5c2e4a2563f9f4427955422fe1402762', 'siti@gmail.com', 'Siti', NULL, NULL, NULL, NULL, 'hrd', 'active', '2025-08-19 03:54:54', 0),
(4, 'konten01', '26ed30f28908645239254ff4f88c1b75', 'rian@gmail.com', 'Rian', NULL, NULL, NULL, NULL, 'konten', 'active', '2025-08-19 03:54:54', 0),
(6, 'agus01', '01c3c766ce47082b1b130daedd347ffd', 'agus123@gmail.com', 'Agus Agus', NULL, NULL, NULL, NULL, 'hrd', 'active', '2025-08-25 01:34:19', 0),
(7, 'rekis', 'ef14d8aeff3c7255004a18508133b8ad', 'weioewhifewhuifwhui@gmail.com', 'rekishii lucy', NULL, NULL, NULL, NULL, 'hrd', 'active', '2025-08-25 01:48:43', 0),
(8, 'pelamar02', 'cc03e747a6afbbcbf8be7668acfebee5', 'ewqodijqoijdqijodwqiojwdjioqjidqjodoi@gmail.com', 'test123', '08131278923178', 3, 3, 'cv_8_1763985384.pdf', 'pelamar', 'active', '2025-10-08 02:10:42', 0),
(9, 'pelamar03', 'ee53d4213c897ad632bb8d824762f918', 'qjiodjioqdwjioqwd@gmail.com', 'Test321', '0831279132789', 3, 3, 'cv_9_1764117960.pdf', 'pelamar', 'active', '2025-11-26 00:45:02', 0),
(10, 'pelamar10', '03256b6f995b425e1a10755acaab5777', 'rekislucy993@gmail.com', 'test123', '08798465132', 3, 3, 'cv_10_1774931728.pdf', 'pelamar', 'active', '2026-03-31 04:34:31', 0);

-- --------------------------------------------------------

--
-- Table structure for table `webinar`
--

CREATE TABLE `webinar` (
  `webinar_id` int NOT NULL,
  `judul` varchar(200) NOT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `category_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `webinar`
--

INSERT INTO `webinar` (`webinar_id`, `judul`, `gambar`, `created_at`, `category_id`) VALUES
(1, 'Webinar Waindo Series #1 GIS Enterprise & Dashboard Operation, CSRT, Airbone LiDAR dan Aplikasi Pemanfaatannya', 'uploads/webinar/1756174143_webinar1.jpeg', '2025-08-26 02:09:03', NULL),
(2, 'Webinar Waindo Series #2 Pembuatan Peta 3D Menggunakan ArcGIS PRO', 'uploads/webinar/1756265798_webinar2.jpeg', '2025-08-27 03:36:38', NULL),
(3, 'Webinar Waindo Series #3 Technology Updates Low Cost GNSS for Surveying dan Monitoring', 'uploads/webinar/1756430418_webinar3.jpeg', '2025-08-29 01:20:18', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`application_id`),
  ADD KEY `relasi_user` (`user_id`),
  ADD KEY `relasi_job` (`job_id`),
  ADD KEY `fk_applications_jenjang` (`id_jenjang_pendidikan`),
  ADD KEY `fk_applications_jurusan` (`id_jurusan_pendidikan`);

--
-- Indexes for table `content_categories`
--
ALTER TABLE `content_categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `galeri`
--
ALTER TABLE `galeri`
  ADD PRIMARY KEY (`galeri_id`),
  ADD KEY `fk_galeri_category` (`category_id`);

--
-- Indexes for table `galeri_foto`
--
ALTER TABLE `galeri_foto`
  ADD PRIMARY KEY (`foto_id`),
  ADD KEY `galeri_id` (`galeri_id`);

--
-- Indexes for table `hrd_notes`
--
ALTER TABLE `hrd_notes`
  ADD PRIMARY KEY (`note_id`),
  ADD KEY `hrd_user_id` (`hrd_user_id`),
  ADD KEY `hrd_notes_ibfk_1` (`application_id`),
  ADD KEY `candidate_user_id` (`candidate_user_id`);

--
-- Indexes for table `jenjang_pendidikan`
--
ALTER TABLE `jenjang_pendidikan`
  ADD PRIMARY KEY (`id_jenjang`),
  ADD UNIQUE KEY `kode_jenjang` (`kode_jenjang`);

--
-- Indexes for table `jurusan_pendidikan`
--
ALTER TABLE `jurusan_pendidikan`
  ADD PRIMARY KEY (`id_jurusan`),
  ADD KEY `id_jenjang` (`id_jenjang`);

--
-- Indexes for table `kegiatan`
--
ALTER TABLE `kegiatan`
  ADD PRIMARY KEY (`kegiatan_id`),
  ADD KEY `fk_kegiatan_category` (`category_id`);

--
-- Indexes for table `kegiatan_foto`
--
ALTER TABLE `kegiatan_foto`
  ADD PRIMARY KEY (`foto_id`),
  ADD KEY `kegiatan_id` (`kegiatan_id`);

--
-- Indexes for table `live_streaming`
--
ALTER TABLE `live_streaming`
  ADD PRIMARY KEY (`streaming_id`),
  ADD KEY `fk_live_streaming_category` (`category_id`);

--
-- Indexes for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `lowongan`
--
ALTER TABLE `lowongan`
  ADD PRIMARY KEY (`job_id`),
  ADD KEY `posted_by` (`posted_by`),
  ADD KEY `fk_lowongan_jenjang` (`req_jenjang_pendidikan`),
  ADD KEY `fk_lowongan_jurusan` (`req_jurusan_pendidikan`);

--
-- Indexes for table `popup_images`
--
ALTER TABLE `popup_images`
  ADD PRIMARY KEY (`popup_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_key` (`category_key`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`service_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username_2` (`username`),
  ADD KEY `fk_users_jenjang` (`id_jenjang_pendidikan`),
  ADD KEY `fk_users_jurusan` (`id_jurusan_pendidikan`);

--
-- Indexes for table `webinar`
--
ALTER TABLE `webinar`
  ADD PRIMARY KEY (`webinar_id`),
  ADD KEY `fk_webinar_category` (`category_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `application_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `content_categories`
--
ALTER TABLE `content_categories`
  MODIFY `category_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `galeri`
--
ALTER TABLE `galeri`
  MODIFY `galeri_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `galeri_foto`
--
ALTER TABLE `galeri_foto`
  MODIFY `foto_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `hrd_notes`
--
ALTER TABLE `hrd_notes`
  MODIFY `note_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `jenjang_pendidikan`
--
ALTER TABLE `jenjang_pendidikan`
  MODIFY `id_jenjang` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `jurusan_pendidikan`
--
ALTER TABLE `jurusan_pendidikan`
  MODIFY `id_jurusan` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `kegiatan`
--
ALTER TABLE `kegiatan`
  MODIFY `kegiatan_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `kegiatan_foto`
--
ALTER TABLE `kegiatan_foto`
  MODIFY `foto_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `live_streaming`
--
ALTER TABLE `live_streaming`
  MODIFY `streaming_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  MODIFY `log_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=594;

--
-- AUTO_INCREMENT for table `lowongan`
--
ALTER TABLE `lowongan`
  MODIFY `job_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `popup_images`
--
ALTER TABLE `popup_images`
  MODIFY `popup_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `product_categories`
--
ALTER TABLE `product_categories`
  MODIFY `category_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `service_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `webinar`
--
ALTER TABLE `webinar`
  MODIFY `webinar_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `fk_applications_jenjang` FOREIGN KEY (`id_jenjang_pendidikan`) REFERENCES `jenjang_pendidikan` (`id_jenjang`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_applications_jurusan` FOREIGN KEY (`id_jurusan_pendidikan`) REFERENCES `jurusan_pendidikan` (`id_jurusan`) ON DELETE SET NULL,
  ADD CONSTRAINT `relasi_job` FOREIGN KEY (`job_id`) REFERENCES `lowongan` (`job_id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  ADD CONSTRAINT `relasi_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE RESTRICT;

--
-- Constraints for table `galeri`
--
ALTER TABLE `galeri`
  ADD CONSTRAINT `fk_galeri_category` FOREIGN KEY (`category_id`) REFERENCES `content_categories` (`category_id`) ON DELETE SET NULL;

--
-- Constraints for table `galeri_foto`
--
ALTER TABLE `galeri_foto`
  ADD CONSTRAINT `galeri_foto_ibfk_1` FOREIGN KEY (`galeri_id`) REFERENCES `galeri` (`galeri_id`) ON DELETE CASCADE;

--
-- Constraints for table `hrd_notes`
--
ALTER TABLE `hrd_notes`
  ADD CONSTRAINT `hrd_notes_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`application_id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  ADD CONSTRAINT `hrd_notes_ibfk_2` FOREIGN KEY (`hrd_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `hrd_notes_ibfk_candidate` FOREIGN KEY (`candidate_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `jurusan_pendidikan`
--
ALTER TABLE `jurusan_pendidikan`
  ADD CONSTRAINT `jurusan_pendidikan_ibfk_1` FOREIGN KEY (`id_jenjang`) REFERENCES `jenjang_pendidikan` (`id_jenjang`) ON DELETE CASCADE;

--
-- Constraints for table `kegiatan`
--
ALTER TABLE `kegiatan`
  ADD CONSTRAINT `fk_kegiatan_category` FOREIGN KEY (`category_id`) REFERENCES `content_categories` (`category_id`) ON DELETE SET NULL;

--
-- Constraints for table `kegiatan_foto`
--
ALTER TABLE `kegiatan_foto`
  ADD CONSTRAINT `kegiatan_foto_ibfk_1` FOREIGN KEY (`kegiatan_id`) REFERENCES `kegiatan` (`kegiatan_id`) ON DELETE CASCADE;

--
-- Constraints for table `live_streaming`
--
ALTER TABLE `live_streaming`
  ADD CONSTRAINT `fk_live_streaming_category` FOREIGN KEY (`category_id`) REFERENCES `content_categories` (`category_id`) ON DELETE SET NULL;

--
-- Constraints for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD CONSTRAINT `log_aktivitas_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `lowongan`
--
ALTER TABLE `lowongan`
  ADD CONSTRAINT `fk_lowongan_jenjang` FOREIGN KEY (`req_jenjang_pendidikan`) REFERENCES `jenjang_pendidikan` (`id_jenjang`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_lowongan_jurusan` FOREIGN KEY (`req_jurusan_pendidikan`) REFERENCES `jurusan_pendidikan` (`id_jurusan`) ON DELETE SET NULL,
  ADD CONSTRAINT `lowongan_ibfk_1` FOREIGN KEY (`posted_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `popup_images`
--
ALTER TABLE `popup_images`
  ADD CONSTRAINT `popup_images_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`category_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `services_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_jenjang` FOREIGN KEY (`id_jenjang_pendidikan`) REFERENCES `jenjang_pendidikan` (`id_jenjang`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_users_jurusan` FOREIGN KEY (`id_jurusan_pendidikan`) REFERENCES `jurusan_pendidikan` (`id_jurusan`) ON DELETE SET NULL;

--
-- Constraints for table `webinar`
--
ALTER TABLE `webinar`
  ADD CONSTRAINT `fk_webinar_category` FOREIGN KEY (`category_id`) REFERENCES `content_categories` (`category_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
