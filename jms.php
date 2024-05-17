<?php
header('Content-Type: application/json');

// Menyertakan file koneksi
require 'koneksi.php';

// Fungsi untuk menambah data
function tambahData($id_user, $sekolah, $status) {
    global $conn;
    $sql = "INSERT INTO tb_jms (id_user, sekolah, status) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $id_user, $sekolah, $status);
    return $stmt->execute();
}

// Fungsi untuk membaca semua data
function semuaData() {
    global $conn;
    $sql = "SELECT * FROM tb_jms";
    $result = $conn->query($sql);
    $data = array();
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    return $data;
}

function laporanUser($id) {
    global $conn;
    $sql = "SELECT * FROM tb_jms WHERE id_user=$id";
    $result = $conn->query($sql);
    $data = array();
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    return $data;
}

// Fungsi untuk mengubah data
function ubahData($id_jms, $id_user, $sekolah, $status) {
    global $conn;
    $sql = "UPDATE tb_jms SET id_user = ?, sekolah = ?, status = ?, created_date = CURRENT_TIMESTAMP WHERE id_jms = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issi", $id_user, $sekolah, $status, $id_jms);
    return $stmt->execute();
}

// Fungsi untuk menghapus data
function hapusData($id_jms) {
    global $conn;
    $sql = "DELETE FROM tb_jms WHERE id_jms = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_jms);
    return $stmt->execute();
}

// Menentukan metode permintaan dan memanggil fungsi yang sesuai
$request_method = $_SERVER['REQUEST_METHOD'];

switch ($request_method) {
    case 'GET':
        if (isset($_GET["id_jms"])) {
            $id = $_GET["id_jms"];
            if (hapusData($id)) {
                echo json_encode(array("sukses" => true, "status" => 200, "pesan" => "Data berhasil dihapus"));
            } else {
                echo json_encode(array("sukses" => false, "status" => 500, "pesan" => "Gagal menghapus data"));
            }
        } else if(isset($_GET['id'])){
            $id = $_GET["id"];
            if (laporanUser($id)) {
                echo json_encode(array("sukses" => true, "status" => 200, "pesan" => "Data berhasil didapat", "data"=>laporanUser($id)));
            } else {
                echo json_encode(array("sukses" => false, "status" => 500, "pesan" => "Data tidak ditemukan", "data"=>null));
            }
        } else {
            echo json_encode(array("sukses" => true, "status" => 200, "pesan" => "Berhasil mendapatkan semua data", "data" => semuaData()));
        }
        break;
    
    case 'POST':
        $id_user = $_POST["id_user"];
        $sekolah = $_POST["sekolah"];
        $status = $_POST["status"];
        if (isset($_POST["id_jms"])) {
            $id = $_POST["id_jms"];
            if (ubahData($id, $id_user, $sekolah, $status)) {
                echo json_encode(array("sukses" => true, "status" => 200, "pesan" => "Data berhasil diubah"));
            } else {
                echo json_encode(array("sukses" => false, "status" => 500, "pesan" => "Gagal mengubah data"));
            }
        } else {
            if (tambahData($id_user, $sekolah, $status)) {
                echo json_encode(array("sukses" => true, "status" => 200, "pesan" => "Data berhasil ditambahkan"));
            } else {
                echo json_encode(array("sukses" => false, "status" => 500, "pesan" => "Gagal menambahkan data"));
            }
        }
        break;
    
    default:
        echo json_encode(array("sukses" => false, "status" => 400, "pesan" => "Metode permintaan tidak dikenali"));
        break;
}

// Menutup koneksi
$conn->close();
?>
