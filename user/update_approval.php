<?php
session_start();
require_once '../config/connect.php';

// ================= ตรวจสอบสิทธิ์ =================
if (!isset($_SESSION['EmployeeID'])) {
    die("Unauthorized");
}

$emp = $_SESSION['EmployeeID'];
$booking_id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;

if (!$booking_id || !in_array($action, ['approve', 'reject'])) {
    die("Invalid request");
}

try {

    // ใช้ Transaction ป้องกันข้อมูลเสีย
    $conn->beginTransaction();

    // ================= ตรวจสอบว่าผู้ใช้นี้มีสิทธิ์อนุมัติรายการนี้จริง =================
    $stmt = $conn->prepare("
        SELECT id, status 
        FROM booking_approval_logs
        WHERE booking_id = :bid
        AND approver_id = :emp
    ");
    $stmt->execute([
        'bid' => $booking_id,
        'emp' => $emp
    ]);

    $log = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$log) {
        throw new Exception("คุณไม่มีสิทธิ์อนุมัติรายการนี้");
    }

    // ================= ป้องกันกดซ้ำ =================
    if ($log['status'] !== 'pending') {
        throw new Exception("รายการนี้ถูกดำเนินการไปแล้ว");
    }

    // ================= UPDATE LOG =================
    $newStatus = ($action == 'approve') ? 'approved' : 'rejected';

    $stmt = $conn->prepare("
        UPDATE booking_approval_logs
        SET status = :status,
            action_date = GETDATE()
        WHERE id = :id
    ");
    $stmt->execute([
        'status' => $newStatus,
        'id' => $log['id']
    ]);

    // ================= ถ้า Reject → จบทันที =================
    if ($newStatus == 'rejected') {

        $conn->prepare("
            UPDATE travel_bookings
            SET booking_status = 'ปฏิเสธแล้ว'
            WHERE id = :bid
        ")->execute(['bid' => $booking_id]);

        $conn->commit();

        header("Location: approval_dashboard.php");
        exit();
    }

    // ================= ถ้า Approve =================

    // ตรวจว่ามีใคร approve แล้วกี่คน
    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM booking_approval_logs
        WHERE booking_id = :bid
        AND status = 'approved'
    ");
    $stmt->execute(['bid' => $booking_id]);

    $approvedCount = $stmt->fetchColumn();

    // ===== เงื่อนไขหลักของระบบคุณ =====
    // ✔ แค่มี 1 คน approve = ผ่านเลย
    if ($approvedCount >= 1) {

        $conn->prepare("
            UPDATE travel_bookings
            SET booking_status = 'อนุมัติแล้ว'
            WHERE id = :bid
        ")->execute(['bid' => $booking_id]);

        // (Optional) ปิด log อื่นให้หมด
        $conn->prepare("
            UPDATE booking_approval_logs
            SET status = 'approved'
            WHERE booking_id = :bid
            AND status = 'pending'
        ")->execute(['bid' => $booking_id]);
    }

    $conn->commit();

    // ป้องกัน refresh ซ้ำ
    header("Location: approval_dashboard.php");
    exit();

} catch (Exception $e) {

    $conn->rollBack();

    echo "<div style='padding:20px;color:red'>";
    echo "เกิดข้อผิดพลาด: " . $e->getMessage();
    echo "</div>";
}
?>