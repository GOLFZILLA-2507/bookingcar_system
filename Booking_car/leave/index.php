<?php
// ตรวจสอบการเข้าสู่ระบบ
session_start();
if (!isset($_SESSION['EmployeeID'])) {
    header('Location: ../login.php'); // ถ้าผู้ใช้ไม่ได้เข้าสู่ระบบ จะถูกส่งไปหน้า login
    exit();
}

require_once '../config/connect.php';

// ดึงข้อมูลพนักงานที่เข้าสู่ระบบ
$EmployeeID = $_SESSION['EmployeeID'];
$stmt = $conn->prepare("SELECT * FROM Employee WHERE EmployeeID = :EmployeeID");
$stmt->bindParam(':EmployeeID', $EmployeeID);
$stmt->execute();
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

// ดึงข้อมูลการลาจากตาราง annual_leave_2568
$stmt_leave = $conn->prepare("SELECT * FROM annual_leave_2568 WHERE EmployeeID = :EmployeeID");
$stmt_leave->bindParam(':EmployeeID', $EmployeeID);
$stmt_leave->execute();
$leave_data = $stmt_leave->fetch(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบลาออนไลน์</title>
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
                    <a class="nav-link active" href="index.php">หน้าแรก</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="request_leave.php">ขอลา</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="track_leave.php">ติดตามสถานะการลา</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../logout.php">ออกจากระบบ</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Main Content -->
<div class="container">
    <h1>ยินดีต้อนรับ, <?= htmlspecialchars($employee['fullname']) ?>!</h1>
    <p>ข้อมูลส่วนตัวของคุณ:</p>
    <ul>
        <li>ตำแหน่ง: <?= htmlspecialchars($employee['position']) ?></li>
        <li>แผนก: <?= htmlspecialchars($employee['department']) ?></li>
        <li>ประจำ : <?= htmlspecialchars($employee['site']) ?></li>

    <h3>ข้อมูลการลาของคุณ:</h3>
    <p>จำนวนวันลาพักร้อนที่เหลือ: <?= $leave_data['holiday_day'] - $leave_data['holiday_day_used'] ?> วัน</p>
    <p>จำนวนวันที่ลาป่วยที่เหลือ: <?= $leave_data['sick_day'] - $leave_data['sick_day_used'] ?> วัน</p>
    <p>จำนวนวันที่ลากิจที่เหลือ: <?= $leave_data['business_day'] - $leave_data['business_day_used'] ?> วัน</p>

    <div class="mt-3">
        <a href="request_leave.php" class="btn btn-primary">ขอลา</a>
        <a href="track_leave.php" class="btn btn-info">ติดตามสถานะการลา</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
