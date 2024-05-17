<?php
header('Content-Type: application/json');

// Menyertakan file koneksi
require 'koneksi.php';

// Memeriksa apakah metode permintaan adalah POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Mengambil data dari permintaan POST
    $id_user = isset($_POST['id_user']) ? intval($_POST['id_user']) : 0;
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;

    // Validasi input
    if ($id_user > 0 && $rating > 0) {
        // Periksa apakah id_user sudah memberikan rating sebelumnya
        $sql_check = "SELECT * FROM tb_rating WHERE id_user = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("i", $id_user);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            // Jika sudah ada, update rating
            $sql_update = "UPDATE tb_rating SET rating = ?, created_date = CURRENT_TIMESTAMP WHERE id_user = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("ii", $rating, $id_user);

            if ($stmt_update->execute()) {
                echo json_encode(array("sukses" => true, "status" => 200, "pesan" => "Rating berhasil diperbarui"));
            } else {
                echo json_encode(array("sukses" => false, "status" => 500, "pesan" => "Terjadi kesalahan saat memperbarui rating"));
            }

            $stmt_update->close();
        } else {
            // Jika belum ada, tambahkan rating baru
            $sql_insert = "INSERT INTO tb_rating (id_user, rating) VALUES (?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("ii", $id_user, $rating);

            if ($stmt_insert->execute()) {
                echo json_encode(array("sukses" => true, "status" => 200, "pesan" => "Data berhasil ditambahkan"));
            } else {
                echo json_encode(array("sukses" => false, "status" => 500, "pesan" => "Terjadi kesalahan saat menambahkan data"));
            }

            $stmt_insert->close();
        }

        $stmt_check->close();
    } else {
        echo json_encode(array("sukses" => false, "status" => 400, "pesan" => "Data tidak valid"));
    }
} else {
    echo json_encode(array("sukses" => false, "status" => 405, "pesan" => "Metode permintaan tidak diizinkan"));
}

// Menutup koneksi
$conn->close();
?>
