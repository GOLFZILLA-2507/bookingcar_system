<?php
session_start();
require_once '../config/connect.php'; // เชื่อมต่อฐานข้อมูล

try {
    // เชื่อมต่อ PDO
    $conn = new PDO("sqlsrv:Server=$serverName;Database=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // ดึงข้อมูลจากฟอร์ม
        $full_name = $_POST['full_name'];
        $position = $_POST['position'];
        $department = $_POST['department'];
        $destination = implode(", ", $_POST['destination']);
        $companions = implode(", ", $_POST['companions']);
        $start_date = $_POST['start_date'];
        $start_time = $_POST['start_time'];
        $end_date = $_POST['end_date'];
        $end_time = $_POST['end_time'];
        $additional_details = $_POST['additional_details'];

        // ดึง employee_id จาก session
        if (isset($_SESSION['EmployeeID'])) {
            $employee_id = $_SESSION['EmployeeID'];
        } else {
            throw new Exception("ไม่ได้เข้าสู่ระบบ กรุณา login ก่อนทำการจองรถ");
        }

        // เตรียม SQL
        $sql = "INSERT INTO travel_bookings 
            (employee_id, full_name, position, department, destination, companions, start_date, start_time, end_date, end_time, additional_details) 
        VALUES 
            (:employee_id, :full_name, :position, :department, :destination, :companions, :start_date, :start_time, :end_date, :end_time, :additional_details)";

        $stmt = $conn->prepare($sql);
        // ...
        $stmt->bindParam(':destination', $destinations);

        // bind parameters
        $stmt->bindParam(':employee_id', $employee_id);
        $stmt->bindParam(':full_name', $full_name);
        $stmt->bindParam(':position', $position);
        $stmt->bindParam(':department', $department);
        $stmt->bindParam(':destination', $destination);
        $stmt->bindParam(':companions', $companions);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':start_time', $start_time);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->bindParam(':end_time', $end_time);
        $stmt->bindParam(':additional_details', $additional_details);

        $stmt->execute();

        echo "<script>alert('บันทึกข้อมูลจองรถสำเร็จ!'); window.location.href = 'previewbook.php';</script>";
        exit();
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

$conn = null;
?>
