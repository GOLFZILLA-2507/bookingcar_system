<?php
require_once '../config/connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage_booking.php');
    exit;
}

$car_id = $_POST['car_id'];

// อ่านสถานะปัจจุบัน
$stmt = $conn->prepare("SELECT status FROM cars WHERE id = :id");
$stmt->execute([':id' => $car_id]);
$current = $stmt->fetchColumn();

// สลับสถานะ
$new = $current === 'รถคันนี้มีผู้ใช้งานแล้ว' ? 'ว่าง' : 'รถคันนี้มีผู้ใช้งานแล้ว';

$stmt2 = $conn->prepare("UPDATE cars SET status = :new WHERE id = :id");
$stmt2->execute([':new' => $new, ':id' => $car_id]);

header('Location: manage_cars.php');
exit;
