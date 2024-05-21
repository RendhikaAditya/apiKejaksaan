<?php
include 'koneksi.php'; 
$request_method = $_SERVER["REQUEST_METHOD"];

function getUser($id) {
    global $conn;
    $sql = "SELECT * FROM tb_user WHERE id_user = $id";
    $result = $conn->query($sql);
    return $result->fetch_assoc();
}

function getAllUsers() {
    global $conn;
    $sql = "SELECT * FROM tb_user";
    $result = $conn->query($sql);
    $users = array();
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    return $users;
}

function loginUser($email, $password) {
    global $conn;

    // Ambil data user berdasarkan email
    $sql = "SELECT * FROM tb_user WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result->num_rows == 1) {
        // Ambil baris data pengguna dari hasil query
        $user = $result->fetch_assoc();

        // Verifikasi password
        if (password_verify($password, $user['password'])) {
            // Jika password cocok, kembalikan data pengguna
            return $user;
        } else {
            // Jika password tidak cocok, kembalikan null
            return null;
        }
    } else {
        // Jika tidak ada pengguna dengan email yang diberikan, kembalikan null
        return null;
    }
}

function createUser($nama, $email, $no_telpon, $ktp_base64, $alamat, $password, $level) {
    global $conn;

    // Generate the file name for the KTP PDF
    $ktp_file_name = "pdf/".generateRandomFileName('ktp_', '.pdf');
    $filehandler = fopen($ktp_file_name, 'wb');
    if (!$filehandler) {
        echo 'Failed to open file for writing';
        return false;
    }
    
    try {
        // Decode the base64 content
        $decoded_content = base64_decode($ktp_base64);
        if ($decoded_content === false) {
            echo 'Failed to decode base64 content';
            return false;
        }
        
        // Write the base64 decoded PDF content to the file
        $bytes_written = fwrite($filehandler, $decoded_content);
        if ($bytes_written === false) {
            echo 'Failed to write to file';
            return false;
        }
    } catch (Exception $e) {
        echo 'Error in fwrite(): ',  $e->getMessage(), "\n";
        fclose($filehandler);
        return false;
    }
    fclose($filehandler);

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Insert the user data into the database
    $sql = "INSERT INTO tb_user (nama, email, no_telpon, ktp, alamat, password, level)
            VALUES ('$nama', '$email', '$no_telpon', '$ktp_file_name', '$alamat', '$hashed_password', '$level')";

    return $conn->query($sql);
}



function updateUser($id, $nama, $email, $no_telpon, $ktp_base64, $alamat, $password, $level) {
    global $conn;

    // Get the old KTP file path
    $sql = "SELECT ktp, password FROM tb_user WHERE id_user = $id";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $old_ktp_file = $row['ktp'];
        $old_password = $row['password'];

        // Use the old KTP file name if it exists, else generate a new one
        $new_ktp_file_name = !empty($old_ktp_file) ? $old_ktp_file : "pdf/".generateRandomFileName('ktp_', '.pdf');

        // Write the base64 decoded PDF content to the file
        $filehandler = fopen($new_ktp_file_name, 'wb');
        if (!$filehandler) {
            echo 'Failed to open file for writing';
            return false;
        }
        try {
            // Decode and write the base64 content to the file
            $decoded_content = base64_decode($ktp_base64);
            if ($decoded_content === false) {
                echo 'Failed to decode base64 content';
                fclose($filehandler);
                return false;
            }
            $bytes_written = fwrite($filehandler, $decoded_content);
            if ($bytes_written === false) {
                echo 'Failed to write to file';
                fclose($filehandler);
                return false;
            }
        } catch (Exception $e) {
            echo 'Error in fwrite(): ',  $e->getMessage(), "\n";
            fclose($filehandler);
            return false;
        }
        fclose($filehandler);

        // Check if password is provided and not empty
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $password_update = ", password='$hashed_password'";
        } else {
            $password_update = "";
        }

        // Construct the SQL query
        $sql = "UPDATE tb_user SET 
                nama='$nama', 
                email='$email', 
                no_telpon='$no_telpon', 
                ktp='$new_ktp_file_name', 
                alamat='$alamat' 
                $password_update, 
                level='$level' 
                WHERE id_user=$id";

        if ($conn->query($sql)) {
            // Return the new or existing file name
            return $new_ktp_file_name;
        } else {
            echo 'Failed to update user in the database';
            return false;
        }
    } else {
        echo 'User not found';
        return false;
    }
}



function deleteFile($filePath) {
    if (file_exists($filePath)) {
        unlink($filePath);
    }
}
function generateRandomFileName($prefix = '', $suffix = '') {
    $datePart = date("YmdHis");
    $randomFileName = $prefix . $datePart . $suffix;
    return $randomFileName;
}


function deleteUser($id) {
    global $conn;
    $sql = "DELETE FROM tb_user WHERE id_user=$id";
    return $conn->query($sql);
}

switch ($request_method) {
    case 'GET':
        if (isset($_GET["id_user"])) {
            $id = intval($_GET["id_user"]);
            $user = getUser($id);
            if ($user) {
                echo json_encode(array("sukses" => true, "status" => 200, "pesan" => "Data berhasil ditemukan", "data" => $user));
            } else {
                echo json_encode(array("sukses" => false, "status" => 404, "pesan" => "Data tidak ditemukan"));
            }
        } else {
            $users = getAllUsers();
            echo json_encode(array("sukses" => true, "status" => 200, "pesan" => "Berhasil mendapatkan semua data", "data" => $users));
        }
        break;

        case 'POST':
            // Kasus POST untuk menambahkan atau memperbarui data pengguna
            if(isset($_POST["action"]) && $_POST["action"] == "login") {
                // Proses login
                $email = $_POST["email"];
                $password = $_POST["password"];
                $user = loginUser($email, $password);
                if($user) {
                    echo json_encode(array("sukses" => true, "status" => 200, "pesan" => "Login berhasil", "data" => $user));
                } else {
                    echo json_encode(array("sukses" => false, "status" => 401, "pesan" => "Login gagal. Email atau password salah", "data"=> $user));
                }
            } else {
                // Proses penambahan atau pembaruan data pengguna
                $nama = $_POST["nama"];
                $email = $_POST["email"];
                $no_telpon = $_POST["no_telpon"];
                $ktp = $_POST["ktp"];
                $alamat = $_POST["alamat"];
                $password = $_POST["password"];
                $level = $_POST["level"];
    
                if (isset($_GET["id_user"])) {
                    $id = $_GET["id_user"];
                    if (updateUser($id, $nama, $email, $no_telpon, $ktp, $alamat, $password, $level)) {
                        echo json_encode(array("sukses" => true, "status" => 200, "pesan" => "Berhasil merubah data"));
                    } else {
                        echo json_encode(array("sukses" => false, "status" => 500, "pesan" => "Gagal mengubah data"));
                    }
                } else {
                    if (createUser($nama, $email, $no_telpon, $ktp, $alamat, $password, $level)) {
                        echo json_encode(array("sukses" => true, "status" => 200, "pesan" => "Data berhasil ditambahkan"));
                    } else {
                        echo json_encode(array("sukses" => false, "status" => 500, "pesan" => "Gagal menambahkan data"));
                    }
                }
            }
            break;
        $nama = $_POST["nama"];
        $email = $_POST["email"];
        $no_telpon = $_POST["no_telpon"];
        $ktp = $_POST["ktp"];
        $alamat = $_POST["alamat"];
        $password = $_POST["password"];
        $level = $_POST["level"];
        
        if (isset($_POST["id_user"])) {
            $id = $_POST["id_user"];
            if (updateUser($id, $nama, $email, $no_telpon, $ktp, $alamat, $password, $level)) {
                echo json_encode(array("sukses" => true, "status" => 200, "pesan" => "Data berhasil diubah"));
            } else {
                echo json_encode(array("sukses" => false, "status" => 500, "pesan" => "Gagal mengubah data"));
            }
        } else {
            if (createUser($nama, $email, $no_telpon, $ktp, $alamat, $password, $level)) {
                echo json_encode(array("sukses" => true, "status" => 200, "pesan" => "Data berhasil ditambahkan"));
            } else {
                echo json_encode(array("sukses" => false, "status" => 500, "pesan" => "Gagal menambahkan data"));
            }
        }
        break;

    case 'DELETE':
        if (isset($_GET["id_user"])) {
            $id = intval($_GET["id_user"]);
            if (deleteUser($id)) {
                echo json_encode(array("sukses" => true, "status" => 200, "pesan" => "Data berhasil dihapus"));
            } else {
                echo json_encode(array("sukses" => false, "status" => 500, "pesan" => "Gagal menghapus data"));
            }
        } else {
            echo json_encode(array("sukses" => false, "status" => 400, "pesan" => "ID user tidak ditemukan"));
        }
        break;

    default:
        echo json_encode(array("sukses" => false, "status" => 400, "pesan" => "Method tidak dikenal"));
        break;
}
?>

