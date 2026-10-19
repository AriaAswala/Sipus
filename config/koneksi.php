<?php
/**
 * File: config/koneksi.php
 * Lokasi: sipus/config/koneksi.php
 * Deskripsi: Mengatur koneksi database MySQL menggunakan MySQLi (Gaya Prosedural)
 */

// Konfigurasi Database (Mendukung Docker Compose & Server Lokal)
$host     = getenv('DB_HOST') ?: 'db';           // Nama service database di docker-compose
$user     = getenv('DB_USER') ?: 'sipus_user';   // User database
$pass     = getenv('DB_PASS') ?: 'sipus_password'; // Password database
$database = getenv('DB_NAME') ?: 'sipus_db';     // Nama database
$port     = getenv('DB_PORT') ?: 3306;           // Port MySQL

// Membuat koneksi ke database menggunakan mysqli (Gaya Prosedural)
$koneksi = mysqli_connect($host, $user, $pass, $database, $port);

// Periksa apakah koneksi database berhasil
if (!$koneksi) {
    // Tampilkan pesan error jika koneksi gagal
    die("Peringatan: Koneksi ke Database Gagal! Error: " . mysqli_connect_error());
}

// Mengatur charset menjadi utf8mb4
mysqli_set_charset($koneksi, "utf8mb4");
