-- ===================================================
-- DATABASE SCHEME: Sistem Informasi Perpustakaan (sipus_db)
-- RPL Tugas Kuliah - Database Script
-- ===================================================

CREATE DATABASE IF NOT EXISTS `sipus_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `sipus_db`;

-- ---------------------------------------------------
-- 1. TABEL users (Penyimpanan akun login & peran)
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `role` ENUM('admin', 'pustakawan', 'anggota') NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------
-- 2. TABEL members (Detail profil & status anggota)
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `members` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `member_code` VARCHAR(20) NOT NULL UNIQUE,
    `phone` VARCHAR(20),
    `address` TEXT,
    `status` ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_members_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------
-- 3. TABEL books (Informasi utama katalog buku)
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `books` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `isbn` VARCHAR(20) NOT NULL UNIQUE,
    `title` VARCHAR(200) NOT NULL,
    `author` VARCHAR(100) NOT NULL,
    `publisher` VARCHAR(100) NOT NULL,
    `publication_year` INT NOT NULL,
    `category` VARCHAR(50) NOT NULL,
    `total_stock` INT NOT NULL DEFAULT 0,
    `available_stock` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------
-- 4. TABEL book_copies (Salinan/eksemplar fisik buku)
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `book_copies` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `book_id` INT NOT NULL,
    `copy_code` VARCHAR(30) NOT NULL UNIQUE,
    `status` ENUM('tersedia', 'dipinjam', 'hilang') NOT NULL DEFAULT 'tersedia',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_copies_book` FOREIGN KEY (`book_id`) REFERENCES `books`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------
-- 5. TABEL loans (Transaksi peminjaman buku)
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `loans` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `member_id` INT NOT NULL,
    `book_id` INT NOT NULL,
    `copy_id` INT NOT NULL,
    `loan_date` DATE NOT NULL,
    `due_date` DATE NOT NULL,
    `return_date` DATE NULL,
    `status` ENUM('dipinjam', 'dikembalikan') NOT NULL DEFAULT 'dipinjam',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_loans_member` FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_loans_book` FOREIGN KEY (`book_id`) REFERENCES `books`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_loans_copy` FOREIGN KEY (`copy_id`) REFERENCES `book_copies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------
-- 6. TABEL fines (Catatan denda keterlambatan)
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `fines` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `loan_id` INT NOT NULL UNIQUE,
    `late_days` INT NOT NULL DEFAULT 0,
    `fine_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `status` ENUM('belum_dibayar', 'lunas') NOT NULL DEFAULT 'belum_dibayar',
    `paid_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_fines_loan` FOREIGN KEY (`loan_id`) REFERENCES `loans`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ===================================================
-- DATA AWAL (SEED DATA)
-- ===================================================

-- 1. Seed Akun Pengguna (Password terenkripsi password_hash())
-- Password admin: admin123
-- Password pustakawan: pustakawan123
-- Password anggota1: anggota123
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `role`) VALUES
(1, 'admin', '$2y$10$T5yHHCQaywP5otf1f0MN8.nk21JkVaQ2s.UGNY1rJNVeqnDyYkHpu', 'Administrator Utama', 'admin'),
(2, 'pustakawan', '$2y$10$bKISJfe7BsSAhv8/aPRcFOtGO6tPslXSC0Fpz/rwWq7eIcxPL5gcO', 'Budi Santoso (Pustakawan)', 'pustakawan'),
(3, 'anggota1', '$2y$10$rgJMW22PmsS9GO1oWZlj4e8oPub/RJfBiwNlvWH4dh9yX9Sd6AnZq', 'Siti Rahmawati (Anggota)', 'anggota');

-- 2. Seed Data Detail Anggota
INSERT INTO `members` (`id`, `user_id`, `member_code`, `phone`, `address`, `status`) VALUES
(1, 3, 'ANG001', '081234567890', 'Jl. Merdeka No. 45, Jakarta', 'aktif');

-- 3. Seed Data 5 Buku Utama
INSERT INTO `books` (`id`, `isbn`, `title`, `author`, `publisher`, `publication_year`, `category`, `total_stock`, `available_stock`) VALUES
(1, '978-602-0001', 'Pemrograman Web PHP Native & MySQL', 'Rian Hidayat', 'Informatika Press', 2023, 'Pemrograman', 3, 3),
(2, '978-602-0002', 'Rekayasa Perangkat Lunak Sederhana', 'Dr. Eng. Ahmad Subagyo', 'Tekno Media', 2022, 'Rekayasa Perangkat Lunak', 2, 2),
(3, '978-602-0003', 'Menguasai Database MySQL dari Nol', 'Dewi Lestari', 'Penerbit Cerdas', 2021, 'Database', 4, 4),
(4, '978-602-0004', 'Analisis dan Perancangan Sistem Informasi', 'Bambang Pratama', 'Mitra Wacana', 2023, 'Sistem Informasi', 2, 2),
(5, '978-602-0005', 'Panduan Praktis Bootstrap 5 untuk Pemula', 'Eko Kurniawan', 'Media Komputer', 2024, 'Web Design', 3, 3);

-- 4. Seed Data Eksemplar / Salinan Buku (book_copies)
INSERT INTO `book_copies` (`book_id`, `copy_code`, `status`) VALUES
-- Salinan untuk Buku 1 (3 eksemplar)
(1, 'BK001-01', 'tersedia'),
(1, 'BK001-02', 'tersedia'),
(1, 'BK001-03', 'tersedia'),
-- Salinan untuk Buku 2 (2 eksemplar)
(2, 'BK002-01', 'tersedia'),
(2, 'BK002-02', 'tersedia'),
-- Salinan untuk Buku 3 (4 eksemplar)
(3, 'BK003-01', 'tersedia'),
(3, 'BK003-02', 'tersedia'),
(3, 'BK003-03', 'tersedia'),
(3, 'BK003-04', 'tersedia'),
-- Salinan untuk Buku 4 (2 eksemplar)
(4, 'BK004-01', 'tersedia'),
(4, 'BK004-02', 'tersedia'),
-- Salinan untuk Buku 5 (3 eksemplar)
(5, 'BK005-01', 'tersedia'),
(5, 'BK005-02', 'tersedia'),
(5, 'BK005-03', 'tersedia');
