<?php
session_start();
require_once '../config/connect.php'; // ใช้ $conn จากไฟล์นี้เลย

try {

    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        // ===== รับค่าจากฟอร์ม =====
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

        // ===== session user =====
        if (!isset($_SESSION['EmployeeID'])) {
            throw new Exception("ไม่ได้เข้าสู่ระบบ กรุณา login ก่อนทำการจองรถ");
        }
        $employee_id = $_SESSION['EmployeeID'];

        // ===== อัปโหลดไฟล์ (ถ้ามี) =====
        $file_name = null;
        $file_path = null;

        if (isset($_FILES['booking_file']) && $_FILES['booking_file']['error'] == 0) {

            $allowed_ext = ['pdf','jpg','jpeg','png','doc','docx'];
            $ext = strtolower(pathinfo($_FILES['booking_file']['name'], PATHINFO_EXTENSION));

            if (in_array($ext, $allowed_ext)) {

                $file_name = $_FILES['booking_file']['name'];
                $new_name = time() . "_" . preg_replace('/\s+/', '_', $file_name);

                $upload_dir = "../uploads/booking_files/";
                $target = $upload_dir . $new_name;

                if (move_uploaded_file($_FILES['booking_file']['tmp_name'], $target)) {
                    $file_path = $new_name;
                }
            }
        }

        // ===== INSERT booking =====
        $sql = "INSERT INTO travel_bookings 
        (employee_id, full_name, position, department, destination, companions, start_date, start_time, end_date, end_time, additional_details, file_name, file_path)
        VALUES 
        (:employee_id, :full_name, :position, :department, :destination, :companions, :start_date, :start_time, :end_date, :end_time, :additional_details, :file_name, :file_path)";

        $stmt = $conn->prepare($sql);

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
        $stmt->bindParam(':file_name', $file_name);
        $stmt->bindParam(':file_path', $file_path);

        $stmt->execute();

        // ===== STEP: สร้าง approval log =====
        $booking_id = $conn->lastInsertId();

        // หา approver ของ user นี้
        $stmt = $conn->prepare("
            SELECT approver1_id, approver2_id
            FROM booking_approvers
            WHERE employee_id = :emp
        ");
        $stmt->execute(['emp'=>$employee_id]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);

        if($app){

            // approver 1
            if(!empty($app['approver1_id'])){
                $conn->prepare("
                    INSERT INTO booking_approval_logs
                    (booking_id, approver_id, approver_level, status)
                    VALUES (:bid, :aid, 1, 'pending')
                ")->execute([
                    'bid'=>$booking_id,
                    'aid'=>$app['approver1_id']
                ]);
            }

            // approver 2
            if(!empty($app['approver2_id'])){
                $conn->prepare("
                    INSERT INTO booking_approval_logs
                    (booking_id, approver_id, approver_level, status)
                    VALUES (:bid, :aid, 2, 'pending')
                ")->execute([
                    'bid'=>$booking_id,
                    'aid'=>$app['approver2_id']
                ]);
            }
        }

        $_SESSION['success'] = "บันทึกข้อมูลสำเร็จ";
        header("Location: previewbook.php");
        exit();
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

$conn = null;
?>