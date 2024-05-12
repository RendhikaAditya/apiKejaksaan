<?php
// Informasi conn database
$host = "localhost"; // Lokasi database (biasanya localhost)
$username = "root"; // Username database
$password = ""; // Password database (kosongkan jika tidak ada)
$database = "db_kejaksaan"; // Nama database

// Membuat koneksi
$conn = new mysqli($host, $username, $password, $database);

// Memeriksa koneksi
if ($conn->connect_error) {
    die("conn gagal: " . $conn->connect_error);
}

// Jika conn berhasil
// echo "conn berhasil terhubung ke database '$database'.";
?>
