<?php
// ----------------------------------------
// CẤU HÌNH CHO LOCALHOST (Laragon)
// ----------------------------------------
$host = "localhost";
$user = "root";
$pass = "";         // Laragon mặc định không có pass
$db   = "mtp_nd";   // Tên DB chữ thường như bạn thấy trong Laragon

// Tạo kết nối
$conn = new mysqli($host, $user, $pass, $db);

// Kiểm tra lỗi
if ($conn->connect_error) {
    die("Lỗi kết nối database: " . $conn->connect_error);
}

// Nếu chạy ngon lành thì dòng dưới sẽ hiện ra (bỏ comment để test)
// echo "Kết nối thành công!";
?>