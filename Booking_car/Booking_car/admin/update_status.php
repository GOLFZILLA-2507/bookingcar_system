<?php
require_once '../config/connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage_booking.php');
    exit;
}

$booking_id = $_POST['booking_id'] ?? null;
$car_id     = $_POST['car_id']     ?? null;
$action     = $_POST['action']     ?? null;

// ตรวจสอบความถูกต้อง
if (!$booking_id || !in_array($action, ['approve', 'reject'])) {
    die('ข้อมูลไม่ครบถ้วนหรือไม่ถูกต้อง');
}

// กรณีอนุมัติ แต่ไม่ได้เลือกรถ
if ($action === 'approve' && !$car_id) {
    die('กรุณาเลือกรถก่อนอนุมัติ');
}

$status = $action === 'approve' ? 'อนุมัติแล้ว' : 'ปฏิเสธแล้ว';

if ($action === 'approve') {
    // ดึงข้อมูลรถ
    $stmtCar = $conn->prepare("SELECT car_name, license_plate, car_type FROM cars WHERE id = :id");
    $stmtCar->execute([':id' => $car_id]);
    $car = $stmtCar->fetch(PDO::FETCH_ASSOC);

    if (!$car) {
        die('ไม่พบข้อมูลรถ');
    }

    // อัปเดต travel_bookings ด้วยข้อมูลรถ
    $stmt = $conn->prepare("
        UPDATE travel_bookings 
        SET 
            car_id = :car_id,
            car_name = :car_name,
            license_plate = :license_plate,
            car_type = :car_type,
            booking_status = :status 
        WHERE id = :booking_id
    ");
    $stmt->execute([
        ':car_id'        => $car_id,
        ':car_name'      => $car['car_name'],
        ':license_plate' => $car['license_plate'],
        ':car_type'      => $car['car_type'],
        ':status'        => $status,
        ':booking_id'    => $booking_id,
    ]);

    // อัปเดตสถานะรถ
    $stmt2 = $conn->prepare("
        UPDATE cars 
        SET status = 'รถคันนี้มีผู้ใช้งานแล้ว' 
        WHERE id = :id
    ");
    $stmt2->execute([':id' => $car_id]);

} else {
    // ปฏิเสธ: อัปเดตสถานะอย่างเดียว
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

// กลับไปหน้า manage
header('Location: manage_booking.php');
exit;
