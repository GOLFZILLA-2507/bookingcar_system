<?php
// ตรวจสอบการเข้าสู่ระบบ
session_start();
if (!isset($_SESSION['EmployeeID'])) {
    header('Location: login.php'); // ถ้าผู้ใช้ไม่ได้เข้าสู่ระบบ จะถูกส่งไปหน้า login
    exit();
}

require_once '../config/connect.php';

// ดึงข้อมูลพนักงานที่เข้าสู่ระบบ
$EmployeeID = $_SESSION['EmployeeID'];
$stmt = $conn->prepare("SELECT * FROM Employee WHERE EmployeeID = :EmployeeID");
$stmt->bindParam(':EmployeeID', $EmployeeID);
$stmt->execute();
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

// ดึงข้อมูลการขอลาของพนักงาน
$stmt_leaves = $conn->prepare("SELECT * FROM leave_requests WHERE EmployeeID = :EmployeeID ORDER BY submit_leave DESC");
$stmt_leaves->bindParam(':EmployeeID', $EmployeeID);
$stmt_leaves->execute();
$leaves = $stmt_leaves->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ติดตามสถานะการลา</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container {
            margin-top: 50px;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">ระบบลาออนไลน์</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">หน้าแรก</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="request_leave.php">ขอลา</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="track_leave.php">ติดตามสถานะการลา</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">ออกจากระบบ</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Main Content -->
<div class="container">
    <h1>ติดตามสถานะการลา</h1>

    <!-- Display Leave Requests -->
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>#</th>
                <th>ประเภทการลา</th>
                <th>วันที่ขอลา</th>
                <th>วันที่เริ่มลา</th>
                <th>วันที่สิ้นสุดลา</th>
                <th>สถานะ</th>
                <th>รายละเอียด</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($leaves): ?>
                <?php foreach ($leaves as $index => $leave): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= htmlspecialchars($leave['leave_type']) ?></td>
                        <td><?= htmlspecialchars($leave['submit_leave']) ?></td>
                        <td><?= htmlspecialchars($leave['start_date_leave']) ?></td>
                        <td><?= htmlspecialchars($leave['end_date_leave']) ?></td>
                        <td>
                            <?php
                            // แสดงสถานะการอนุมัติ
                            if ($leave['status'] == 0) {
                                echo "รออนุมัติ";
                            } elseif ($leave['status'] == 1) {
                                echo "อนุมัติแล้ว";
                            } else {
                                echo "ปฏิเสธ";
                            }
                            ?>
                        </td>
                        <td><?= htmlspecialchars($leave['detail']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="text-center">ยังไม่มีคำขอลา</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
