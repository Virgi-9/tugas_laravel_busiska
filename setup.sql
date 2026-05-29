-- ============================================================
-- AutoPro Workshop - Database Schema
-- Jalankan file ini sekali di phpMyAdmin atau MySQL CLI
-- ============================================================

CREATE DATABASE IF NOT EXISTS autopro_workshop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE autopro_workshop;

-- Users
CREATE TABLE IF NOT EXISTS users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    nama       VARCHAR(100) NOT NULL,
    email      VARCHAR(100) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    telepon    VARCHAR(20)  DEFAULT NULL,
    role       ENUM('User','Mekanik','Admin','Super Admin') NOT NULL DEFAULT 'User',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Layanan / Jasa Servis
CREATE TABLE IF NOT EXISTS layanan (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nama_servis VARCHAR(100) NOT NULL,
    deskripsi   TEXT         DEFAULT NULL,
    harga       DECIMAL(12,2) NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sparepart
CREATE TABLE IF NOT EXISTS spareparts (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nama_barang VARCHAR(100)  NOT NULL,
    kategori    VARCHAR(50)   DEFAULT 'Umum',
    gambar      VARCHAR(255)  DEFAULT NULL,
    stok        INT           NOT NULL DEFAULT 0,
    harga       DECIMAL(12,2) NOT NULL,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Bookings
CREATE TABLE IF NOT EXISTS bookings (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    user_id           INT NOT NULL,
    layanan_id        INT NOT NULL,
    sparepart_id      INT DEFAULT NULL,
    nama_kendaraan    VARCHAR(100) DEFAULT NULL,
    plat_nomor        VARCHAR(20)  DEFAULT NULL,
    keluhan           TEXT         DEFAULT NULL,
    tanggal           DATE NOT NULL,
    jam               TIME NOT NULL,
    status_pengerjaan ENUM('Menunggu Antrian','Sedang Diproses','Selesai') DEFAULT 'Menunggu Antrian',
    catatan_mekanik   TEXT DEFAULT NULL,
    total_harga       DECIMAL(12,2) DEFAULT 0,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)      REFERENCES users(id)      ON DELETE CASCADE,
    FOREIGN KEY (layanan_id)   REFERENCES layanan(id)    ON DELETE RESTRICT,
    FOREIGN KEY (sparepart_id) REFERENCES spareparts(id) ON DELETE SET NULL
);

-- Transactions
CREATE TABLE IF NOT EXISTS transactions (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    booking_id        INT NOT NULL UNIQUE,
    status_pembayaran ENUM('Belum Bayar','Lunas') DEFAULT 'Belum Bayar',
    metode_bayar      VARCHAR(50) DEFAULT NULL,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);

-- ============================================================
-- Seed Data
-- ============================================================

-- Default Super Admin (password: admin123)
INSERT IGNORE INTO users (nama, email, password, role) VALUES
('Super Admin', 'admin@autopro.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Admin');

-- Default Admin (password: admin123)
INSERT IGNORE INTO users (nama, email, password, role) VALUES
('Admin Bengkel', 'admin2@autopro.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin');

-- Layanan default
INSERT IGNORE INTO layanan (nama_servis, deskripsi, harga) VALUES
('Ganti Oli Mesin',    'Penggantian oli mesin standar termasuk filter oli',          75000),
('Tune Up Ringan',     'Pembersihan karburator, busi, dan filter udara',             150000),
('Servis Rem',         'Pengecekan dan penyetelan sistem pengereman',                120000),
('Ganti Ban',          'Pelepasan dan pemasangan ban baru (per ban)',                 50000),
('Servis AC',          'Pembersihan evaporator dan pengecekan freon',               200000),
('Overhaul Mesin',     'Pembongkaran dan perbaikan menyeluruh komponen mesin',      850000);

-- Sparepart default
INSERT IGNORE INTO spareparts (nama_barang, kategori, stok, harga) VALUES
('Oli Mesin 1L Shell Helix',   'Universal',    50, 85000),
('Filter Oli',                 'Universal',    30, 35000),
('Busi NGK Standard',          'Universal',    40, 25000),
('Filter Udara Matic',         'Motor Matic',  25, 45000),
('Kampas Rem Depan Matic',     'Motor Matic',  20, 120000),
('Kampas Rem Belakang Matic',  'Motor Matic',  20, 95000),
('V-Belt Motor Matic',         'Motor Matic',  15, 65000),
('Kampas Rem Depan Manual',    'Motor Manual', 20, 85000),
('Rantai Motor Manual',        'Motor Manual', 15, 75000),
('Kampas Rem Depan Sport',     'Motor Sport',  18, 130000),
('Busi Iridium Sport',         'Motor Sport',  25, 55000);
