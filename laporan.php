<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Origin: *');
include 'koneksi.php';

function generateRandomFileName($prefix = '', $suffix = '') {
    $datePart = date("YmdHis");
    $randomFileName = $prefix . $datePart  . $suffix;
    return $randomFileName;
}

function tambahLaporan($id_user, $laporan_text, $laporan_pdf, $status, $tipe_laporan) {
    global $conn;
    
    $outputfile = "pdf/".generateRandomFileName(str_replace(' ', '', $tipe_laporan).'_', '.pdf') ;
    $filehandler = fopen($outputfile, 'wb' ); 
    try {
        // kode fwrite() Anda di sini
        fwrite($filehandler, base64_decode($laporan_pdf));

    } catch (Exception $e) {
        echo 'Kesalahan fwrite(): ',  $e->getMessage(), "\n";
    }
    fclose($filehandler);
    
    
    $sql = "INSERT INTO tb_laporan (id_user, laporan_text, laporan_pdf, status, tipe_laporan) VALUES ('$id_user', '$laporan_text', '$outputfile', '$status', '$tipe_laporan')";

    if (mysqli_query($conn, $sql)) {
        return true;
    } else {
        return false;
    }
}

function semuaLaporan() {
    global $conn;
    $sql = "SELECT * FROM tb_laporan";
    $result = $conn->query($sql);
    $laporan = array();
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $laporan[] = $row;
        }
    }
    return $laporan;
}

function ubahLaporan($id, $laporan_text, $laporan_pdf, $status, $tipe_laporan) {
    global $conn;
    
    // Cek apakah file PDF baru diunggah
    if (!empty($laporan_pdf)) {
        $outputfile = "pdf/".generateRandomFileName(str_replace(' ', '', $tipe_laporan).'_', '.pdf') ;
        $filehandler = fopen($outputfile, 'wb' ); 
        fwrite($filehandler, base64_decode($laporan_pdf));
        fclose($filehandler);
        $laporan_pdf = $outputfile; // Set nilai $laporan_pdf dengan path file yang baru diunggah
    }

    $sql = "UPDATE tb_laporan SET laporan_text='$laporan_text', status='$status', tipe_laporan='$tipe_laporan'";
    
    // Jika ada file PDF yang diunggah, tambahkan kolom laporan_pdf ke pernyataan SQL
    if (!empty($laporan_pdf)) {
        $sql .= ", laporan_pdf='$laporan_pdf'";
    }

    $sql .= " WHERE id_laporan=$id";

    if ($conn->query($sql) === TRUE) {
        return true;
    } else {
        return false;
    }
}


function hapusLaporan($id) {
    global $conn;
    $sql = "DELETE FROM tb_laporan WHERE id_laporan=$id";
    if ($conn->query($sql) === TRUE) {
        return true;
    } else {
        return false;
    }
}

$request_method = $_SERVER["REQUEST_METHOD"];

switch($request_method) {
    case 'GET':
        if (isset($_GET["id_laporan"])) {
            $id = $_GET["id_laporan"];
            if (hapusLaporan($id)) {
                echo json_encode(array("sukses" => true, "status" => 200, "pesan" => "Data berhasil Dihapus"));
            } else {
                echo json_encode(array("sukses" => false, "status" => 500, "pesan" => "Gagal Menghapus Data"));
            }
        } else {
            echo json_encode(array("sukses" => true, "status" => 200, "pesan" => "Berhasil Mendapatkan Semua Laporan", "data" => semuaLaporan()));
        }
        break;
    case 'POST':
        $id_user = $_POST["id_user"];
        $laporan_text = $_POST["laporan_text"];
        $laporan_pdf = $_POST["laporan_pdf"];
        $status = $_POST["status"];
        $tipe_laporan = $_POST["tipe_laporan"];
        if (isset($_POST["id_laporan"])) {
            $id = $_POST["id_laporan"];
            if (ubahLaporan($id, $laporan_text, $laporan_pdf, $status, $tipe_laporan)) {
                echo json_encode(array("sukses" => true, "status" => 200, "pesan" => "Data berhasil Diubah"));
            } else {
                echo json_encode(array("sukses" => false, "status" => 500, "pesan" => "Gagal Mengubah Data"));
            }
        } else {
            if (tambahLaporan($id_user, $laporan_text, $laporan_pdf, $status, $tipe_laporan)) {
                echo json_encode(array("sukses" => true, "status" => 200, "pesan" => "Data berhasil Ditambahkan"));
            } else {
                echo json_encode(array("sukses" => false, "status" => 500, "pesan" => "Gagal Menambahkan Data"));
            }
        }
        break;
    default:
        echo json_encode(array("sukses" => true, "status" => 400, "pesan" => "Method Tidak di kenal"));
        break;
}

$conn->close();
?>
