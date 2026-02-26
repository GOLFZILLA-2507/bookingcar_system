<?php
// ตรวจสอบการเข้าสู่ระบบ
session_start();
if (!isset($_SESSION['EmployeeID'])) {
    header('Location: ./login.php');
    exit();
}

require_once '../config/connect.php';

// ดึงข้อมูลพนักงาน
$EmployeeID = $_SESSION['EmployeeID'];
$stmt = $conn->prepare("SELECT * FROM Employee WHERE EmployeeID = :EmployeeID");
$stmt->bindParam(':EmployeeID', $EmployeeID, PDO::PARAM_INT);
$stmt->execute();
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

// การดำเนินการเมื่อผู้ใช้ส่งคำขอลา
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leave_type = filter_input(INPUT_POST, 'leave_type', FILTER_SANITIZE_STRING);
    $type_selector = filter_input(INPUT_POST, 'type_selector', FILTER_SANITIZE_STRING);
    $detail = filter_input(INPUT_POST, 'detail', FILTER_SANITIZE_STRING);

    if (!$leave_type || !$detail || !$type_selector) {
        $error = "กรุณากรอกข้อมูลให้ครบถ้วน!";
    } else {
        try {
            $conn->beginTransaction();
            if ($type_selector === 'date') {
                $start_date_leave = filter_input(INPUT_POST, 'start_date_leave', FILTER_SANITIZE_STRING);
                $end_date_leave = filter_input(INPUT_POST, 'end_date_leave', FILTER_SANITIZE_STRING);
                if ($start_date_leave && $end_date_leave) {
                    $start_datetime = new DateTime($start_date_leave);
                    $end_datetime = new DateTime($end_date_leave);
                    echo $end_datetime;
                    if ($end_datetime < $start_datetime) {
                        throw new Exception("วันที่สิ้นสุดต้องไม่เร็วกว่าวันที่เริ่มต้น");
                    }
                   // $leave_day = $start_datetime->diff($end_datetime)->days + 1;
                    $stmt_leave = $conn->prepare("
                        INSERT INTO leave_requests (leave_type, EmployeeID, submit_leave, start_date_leave, end_date_leave, detail, status)
                        VALUES (:leave_type, :EmployeeID, GETDATE(), :start_date_leave, :end_date_leave, :detail, 0)
                    ");
                    $stmt_leave->execute([
                        ':leave_type' => $leave_type,
                        ':EmployeeID' => $EmployeeID,
                        ':start_date_leave' => $start_date_leave,
                        ':end_date_leave' => $end_date_leave,
                        ':detail' => $detail,
                    ]);
                }
            } elseif ($type_selector === 'time') {
                $start_time = filter_input(INPUT_POST, 'start_time', FILTER_SANITIZE_STRING);
                $end_time = filter_input(INPUT_POST, 'end_time', FILTER_SANITIZE_STRING);

                if ($start_time && $end_time) {
                    $start_datetime = new DateTime($start_time);
                    $end_datetime = new DateTime($end_time);

                    if ($end_datetime < $start_datetime) {
                        throw new Exception("เวลาสิ้นสุดต้องไม่เร็วกว่าเวลาเริ่มต้น");
                    }

                    $leave_hour = $start_datetime->diff($end_datetime)->h;
                    $stmt_leave = $conn->prepare("
                        INSERT INTO leave_requests (leave_type, EmployeeID, submit_leave, leave_day, leave_hour, detail, start_time, end_time, status)
                        VALUES (:leave_type, :EmployeeID, GETDATE(), 0, :leave_hour, :detail, :start_time, :end_time, 0)
                    ");
                    $stmt_leave->execute([
                        ':leave_type' => $leave_type,
                        ':EmployeeID' => $EmployeeID,
                        ':leave_hour' => $leave_hour,
                        ':detail' => $detail,
                        ':start_time' => $start_time,
                        ':end_time' => $end_time,
                    ]);
                }
            }

            $conn->commit();
            $success = "คำขอลาถูกส่งเรียบร้อยแล้ว!";
        } catch (Exception $e) {
            $conn->rollBack();
            $error = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ขอลา</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">ระบบลาออนไลน์</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">หน้าแรก</a></li>
                <li class="nav-item"><a class="nav-link active" href="request_leave.php">ขอลา</a></li>
                <li class="nav-item"><a class="nav-link" href="track_leave.php">ติดตามสถานะการลา</a></li>
                <li class="nav-item"><a class="nav-link" href="logout.php">ออกจากระบบ</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container my-5">
    <h1>ขอลา</h1>
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php elseif (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" novalidate>
        <div class="mb-3">
            <label for="leave_type" class="form-label">ประเภทการลา</label>
            <select class="form-select" id="leave_type" name="leave_type" required>
                <option value="ลาป่วย">ลาป่วย</option>
                <option value="ลากิจ">ลากิจ</option>
                <option value="พักร้อน">พักร้อน</option>
                <option value="ลาอื่นๆ">ลาอื่นๆ</option>
            </select>
        </div>

        <div class="row g-3">
    <div class="col-md-6">
        <label for="type_selector" class="form-label">เลือกประเภทการลา</label>
        <select class="form-select" id="type_selector" name="type_selector" required>
            <option value="date">ลาเต็มวัน</option>
            <option value="time">ลาช่วงเวลา</option>
        </select>
    </div>
</div>

<div id="date_inputs" class="row g-3 mt-3">
    <div class="col-md-4">
        <label for="start_date_leave" class="form-label">วันที่เริ่มลา</label>
        <input type="text" class="form-control datepicker" id="start_date_leave" name="start_date_leave" required>
    </div>
    <div class="col-md-4">
        <label for="end_date_leave" class="form-label">วันที่สิ้นสุดลา</label>
        <input type="text" class="form-control datepicker" id="end_date_leave" name="end_date_leave" required>
    </div>
    <div class="col-md-4">
        <label for="total_days" class="form-label">จำนวนวันที่ลา</label>
        <input type="text" class="form-control" id="total_days" name="total_days" readonly>
    </div>
</div>



<div id="time_inputs" class="row g-3 mt-3 d-none">
<div class="col-md-3">
        <label for="start_date_leave" class="form-label">วันที่ลา</label>
        <input type="text" class="form-control datepicker" id="start_date_leave" name="start_date_leave" required>
    </div>
    <div class="col-md-3">
        <label for="start_time" class="form-label">เวลาเริ่มลา</label>
        <input type="time" class="form-control" id="start_time" name="start_time" required>
    </div>
    <div class="col-md-3">
        <label for="end_time" class="form-label">เวลาสิ้นสุดลา</label>
        <input type="time" class="form-control" id="end_time" name="end_time" required>
    </div>
    <div class="col-md-3">
        <label for="total_days" class="form-label">จำนวนเวลาที่ลา</label>
        <input type="text" class="form-control" id="total_time" name="total_time" readonly>
    </div>
</div>

<script>
    // คำนวณจำนวนวัน
    const startDateInput = document.getElementById('start_date_leave');
    const endDateInput = document.getElementById('end_date_leave');
    const totalDaysInput = document.getElementById('total_days');

    function calculateTotalDays() {
        const startDate = new Date(startDateInput.value);
        const endDate = new Date(endDateInput.value);

        if (startDate && endDate && endDate >= startDate) {
            const diffTime = Math.abs(endDate - startDate);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1; // รวมวันแรก
            totalDaysInput.value = diffDays;
        } else {
            totalDaysInput.value = ''; // ล้างค่าเมื่อข้อมูลไม่สมบูรณ์
        }
    }

    startDateInput.addEventListener('change', calculateTotalDays);
    endDateInput.addEventListener('change', calculateTotalDays);

    // คำนวณจำนวนชั่วโมง
    const startTimeInput = document.getElementById('start_time');
    const endTimeInput = document.getElementById('end_time');
    const totalTimeInput = document.getElementById('total_time');

    function calculateTotalHours() {
        const startTime = startTimeInput.value;
        const endTime = endTimeInput.value;

        if (startTime && endTime) {
            const start = new Date(`1970-01-01T${startTime}:00`);
            const end = new Date(`1970-01-01T${endTime}:00`);

            if (end >= start) {
                const diffTime = (end - start) / (1000 * 60 * 60); // แปลงเป็นชั่วโมง
                totalTimeInput.value = diffTime.toFixed(2); // แสดงทศนิยม 2 ตำแหน่ง
            } else {
                totalTimeInput.value = 'เวลาสิ้นสุดไม่ถูกต้อง';
            }
        } else {
            totalTimeInput.value = ''; // ล้างค่าเมื่อข้อมูลไม่สมบูรณ์
        }
    }

    startTimeInput.addEventListener('change', calculateTotalHours);
    endTimeInput.addEventListener('change', calculateTotalHours);
</script>

<script>
    const typeSelector = document.getElementById('type_selector');
    const dateInputs = document.getElementById('date_inputs');
    const timeInputs = document.getElementById('time_inputs');

    // เปลี่ยนการแสดงผลตามตัวเลือก
    typeSelector.addEventListener('change', function () {
    if (this.value === 'date') {
        dateInputs.classList.remove('d-none');
        timeInputs.classList.add('d-none');
        startDateInput.required = true;
        endDateInput.required = true;
        startTimeInput.required = false;
        endTimeInput.required = false;
    } else if (this.value === 'time') {
        dateInputs.classList.add('d-none');
        timeInputs.classList.remove('d-none');
        startDateInput.required = false;
        endDateInput.required = false;
        startTimeInput.required = true;
        endTimeInput.required = true;
    }
});


    // กำหนด Flatpickr สำหรับอินพุตวันที่
    flatpickr(".datepicker", {
        locale: "th",
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "j F Y",
    });
</script>

        <div class="mb-3 mt-3">
            <label for="detail" class="form-label">เหตุผลการลา</label>
            <textarea class="form-control" id="detail" name="detail" rows="3" required></textarea>
        </div>

        <button type="submit" class="btn btn-primary">ส่งคำขอลา</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    flatpickr(".datepicker", {
        locale: "th",
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "j F Y",
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
