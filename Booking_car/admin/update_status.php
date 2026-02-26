<?php
require_once '../config/connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage_booking.php');
    exit;
}

$booking_id = $_POST['booking_id'] ?? null;
$car_id     = $_POST['car_id']     ?? null;
$action     = $_POST['action']     ?? null;

// ถ้าขาด booking_id หรือ action ไม่ถูกต้อง → error
if (!$booking_id || !in_array($action, ['approve','reject'])) {
    die('ข้อมูลไม่ครบถ้วน');
}

// กรณี approve ต้องเลือกรถด้วย
if ($action === 'approve' && !$car_id) {
    die('ข้อมูลไม่ครบถ้วน: กรุณาเลือกรถก่อนอนุมัติ');
}

$status = $action === 'approve' ? 'อนุมัติแล้ว' : 'ปฏิเสธแล้ว';

if ($action === 'approve') {
    // อัปเดต travel_bookings พร้อม car_id
    $stmt = $conn->prepare("
        UPDATE travel_bookings
        SET car_id        = :car_id,
            booking_status= :status
        WHERE id = :booking_id
    ");
    $stmt->execute([
        ':car_id'      => $car_id,
        ':status'      => $status,
        ':booking_id'  => $booking_id,
    ]);

    // อัปเดต cars.status เป็นมีผู้ใช้งานแล้ว
    $stmt2 = $conn->prepare("
        UPDATE cars
        SET status = 'รถคันนี้มีผู้ใช้งานแล้ว'
        WHERE id = :id
    ");
    $stmt2->execute([':id' => $car_id]);
}
else { // reject
    // อัปเดตแค่ booking_status โดยไม่แตะ car_id
    $stmt = $conn->prepare("
        UPDATE travel_bookings
        SET booking_status = :status
        WHERE id = :booking_id
    ");
    $stmt->execute([
        ':status'     => $status,
        ':booking_id' => $booking_id,
    ]);
}

header('Location: manage_booking.php');
exit;
