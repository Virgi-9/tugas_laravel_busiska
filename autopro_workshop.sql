-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 29, 2026 at 03:39 PM
-- Server version: 8.0.30
-- PHP Version: 8.3.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `autopro_workshop`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `layanan_id` int NOT NULL,
  `sparepart_id` int DEFAULT NULL,
  `nama_kendaraan` varchar(100) DEFAULT NULL,
  `plat_nomor` varchar(20) DEFAULT NULL,
  `keluhan` text,
  `tanggal` date NOT NULL,
  `jam` time NOT NULL,
  `status_pengerjaan` enum('Menunggu Antrian','Sedang Diproses','Selesai') DEFAULT 'Menunggu Antrian',
  `catatan_mekanik` text,
  `total_harga` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `layanan_id`, `sparepart_id`, `nama_kendaraan`, `plat_nomor`, `keluhan`, `tanggal`, `jam`, `status_pengerjaan`, `catatan_mekanik`, `total_harga`, `created_at`) VALUES
(1, 6, 1, 1, 'Honda Beat', 'B 1234 TEK', 'motor mau modip', '2026-05-29', '15:04:00', 'Sedang Diproses', '', '245000.00', '2026-05-28 16:48:33');

-- --------------------------------------------------------

--
-- Table structure for table `layanan`
--

CREATE TABLE `layanan` (
  `id` int NOT NULL,
  `nama_servis` varchar(100) NOT NULL,
  `deskripsi` text,
  `harga` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `layanan`
--

INSERT INTO `layanan` (`id`, `nama_servis`, `deskripsi`, `harga`) VALUES
(1, 'Servis Rutin Berkala', NULL, '150000.00'),
(2, 'Tune Up Machine Heavy', NULL, '250000.00'),
(3, 'Overhaul Transmisi', NULL, '750000.00');

-- --------------------------------------------------------

--
-- Table structure for table `spareparts`
--

CREATE TABLE `spareparts` (
  `id` int NOT NULL,
  `nama_barang` varchar(100) NOT NULL,
  `kategori` varchar(50) NOT NULL DEFAULT 'Umum',
  `gambar` varchar(255) DEFAULT NULL,
  `stok` int NOT NULL DEFAULT '0',
  `harga` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `spareparts`
--

INSERT INTO `spareparts` (`id`, `nama_barang`, `kategori`, `gambar`, `stok`, `harga`) VALUES
(1, 'Oli Sintetis Mesin 1L', 'Motor Matic', NULL, 49, '95000.00'),
(2, 'Kampas Rem Depan', 'Motor Matic', NULL, 20, '120000.00'),
(3, 'Busi Iridium', 'Universal', NULL, 100, '45000.00');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int NOT NULL,
  `booking_id` int NOT NULL,
  `tgl_bayar` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status_pembayaran` enum('Belum Bayar','Lunas') DEFAULT 'Belum Bayar',
  `metode_bayar` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `booking_id`, `tgl_bayar`, `status_pembayaran`, `metode_bayar`) VALUES
(1, 1, '2026-05-28 16:48:33', 'Lunas', 'Tunai');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('User','Mekanik','Admin','Super Admin') DEFAULT 'User',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Super Admin AutoPro', 'superadmin@autopro.com', '$2y$10$vN918g8f75AEP3A01rFfgeJ67M9ZpXg2/XhV0v0uGfSj9FwU0fU2.', 'Super Admin', '2026-05-21 14:06:53'),
(2, 'Admin Workshop', 'admin@autopro.com', '$2y$10$vN918g8f75AEP3A01rFfgeJ67M9ZpXg2/XhV0v0uGfSj9FwU0fU2.', 'Admin', '2026-05-21 14:06:53'),
(3, 'Mekanik Budi', 'budi@autopro.com', '$2y$10$vN918g8f75AEP3A01rFfgeJ67M9ZpXg2/XhV0v0uGfSj9FwU0fU2.', 'Mekanik', '2026-05-21 14:06:53'),
(4, 'Pelanggan Setia', 'user@gmail.com', '$2y$10$vN918g8f75AEP3A01rFfgeJ67M9ZpXg2/XhV0v0uGfSj9FwU0fU2.', 'User', '2026-05-21 14:06:53'),
(5, 'Alif Virgi Aryaguna', 'alifvirgi@gmail.com', '$2y$10$CJckK2vlNlfmVjI7fqi12.MNSpIN863V6/RmnH7pjrcuT0uKJCVCq', 'Super Admin', '2026-05-21 14:59:31'),
(6, 'asoy', 'asoy@gmail.com', '$2y$10$QJaNglYhtxvyVl/PAMQVEubZYCmnxekTSBkrGPO9lTMf2bwSvsnYa', 'User', '2026-05-28 16:38:42'),
(7, 'mekanik asoy', 'mekanikasoy@gmail.com', '$2y$10$ZX6H78YgButF8ZPnc675CeHPSOvh1yJqkJt5QvjTar1FjW9kXZxv.', 'Mekanik', '2026-05-28 17:06:03'),
(8, 'admin new', 'admin12@gmail.com', '$2y$10$Ra5QGPr3wqkjczYdxLZjZOHgebTzshK325ktPDJrQnYAByyY.2d0q', 'Admin', '2026-05-28 17:12:32');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `layanan_id` (`layanan_id`),
  ADD KEY `sparepart_id` (`sparepart_id`);

--
-- Indexes for table `layanan`
--
ALTER TABLE `layanan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `spareparts`
--
ALTER TABLE `spareparts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `layanan`
--
ALTER TABLE `layanan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `spareparts`
--
ALTER TABLE `spareparts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`layanan_id`) REFERENCES `layanan` (`id`),
  ADD CONSTRAINT `bookings_ibfk_3` FOREIGN KEY (`sparepart_id`) REFERENCES `spareparts` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
