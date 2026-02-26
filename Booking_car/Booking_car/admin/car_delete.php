<?php
session_start();
require_once '../config/connect.php';

if (isset($_POST['delete_id'])) {
    $id = $_POST['delete_id']; 
    $stmt = $conn->prepare("DELETE FROM cars WHERE id = ?");
    $stmt->execute([$id]);
}

header("Location: admin_add_cars.php"); // หรือชื่อไฟล์หน้าแสดงรายการรถที่ถูกต้อง
exit();
?>
