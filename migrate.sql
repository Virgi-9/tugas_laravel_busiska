-- ============================================================
-- AutoPro Workshop - Migration Script
-- Compatible: MySQL 5.7+
-- Jalankan di phpMyAdmin > tab SQL
-- ============================================================

USE autopro_workshop;

-- ── layanan: tambah kolom deskripsi ──────────────────────────
ALTER TABLE layanan
    ADD COLUMN deskripsi TEXT DEFAULT NULL AFTER nama_servis;

-- ── spareparts: tambah kolom kategori ────────────────────────
ALTER TABLE spareparts
    ADD COLUMN kategori VARCHAR(50) NOT NULL DEFAULT 'Umum' AFTER nama_barang;

-- ── spareparts: tambah kolom gambar ──────────────────────────
ALTER TABLE spareparts
    ADD COLUMN gambar VARCHAR(255) DEFAULT NULL AFTER kategori;

-- ── bookings: tambah kolom detail kendaraan & keluhan ────────
ALTER TABLE bookings
    ADD COLUMN nama_kendaraan  VARCHAR(100) DEFAULT NULL AFTER sparepart_id,
    ADD COLUMN plat_nomor      VARCHAR(20)  DEFAULT NULL AFTER nama_kendaraan,
    ADD COLUMN keluhan         TEXT         DEFAULT NULL AFTER plat_nomor,
    ADD COLUMN catatan_mekanik TEXT         DEFAULT NULL AFTER status_pengerjaan;

-- ── transactions: tambah kolom metode_bayar ──────────────────
ALTER TABLE transactions
    ADD COLUMN metode_bayar VARCHAR(50) DEFAULT NULL AFTER status_pembayaran;

SELECT 'Migration selesai!' AS status;
