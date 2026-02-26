<?php
require_once '../config/connect.php';
require_once '../config/checklogin.php';
include 'partials/header.php';
include 'partials/sidebar.php';

// ตรวจสอบสิทธิ์ admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// ดึงรายการจองเฉพาะที่อนุมัติแล้ว
$stmt = $conn->prepare(
    "SELECT id, full_name, destination, start_date, start_time, end_date, end_time
     FROM travel_bookings
     WHERE booking_status = 'อนุมัติแล้ว'"
);
$stmt->execute();
$events = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // รวมวันที่และเวลาเป็น ISO8601
    $start = $row['start_date'] . 'T' . substr($row['start_time'], 0, 5);
    $end   = $row['end_date']   . 'T' . substr($row['end_time'], 0, 5);

    $events[] = [
        'id'    => $row['id'],
        'title' => $row['full_name'] . ' - ' . $row['destination'], // ชื่อและปลายทาง
        'start' => $start,
        'end'   => $end,
        'color' => '#28a745' // สีเขียวสำหรับอีเวนต์ที่อนุมัติ
    ];
}
?>

<h1 class="mb-4">แดชบอร์ดผู้ดูแลระบบ</h1>

<div class="row">
    <!-- จองทั้งหมด -->
    <div class="col-md-3 mb-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5 class="card-title">จองทั้งหมด</h5>
                <p class="card-text fs-4">
                    <?php
                    $stmt = $conn->query("SELECT COUNT(*) FROM travel_bookings");
                    echo $stmt->fetchColumn();
                    ?>
                </p>
            </div>
        </div>
    </div>

    <!-- รออนุมัติ -->
    <div class="col-md-3 mb-3">
        <div class="card bg-warning text-dark">
            <div class="card-body">
                <h5 class="card-title">รออนุมัติ</h5>
                <p class="card-text fs-4">
                    <?php
                    $stmt = $conn->query("SELECT COUNT(*) FROM travel_bookings WHERE booking_status = 'รออนุมัติ'");
                    echo $stmt->fetchColumn();
                    ?>
                </p>
            </div>
        </div>
    </div>

    <!-- อนุมัติแล้ว -->
    <div class="col-md-3 mb-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5 class="card-title">อนุมัติแล้ว</h5>
                <p class="card-text fs-4">
                    <?php
                    $stmt = $conn->query("SELECT COUNT(*) FROM travel_bookings WHERE booking_status = 'อนุมัติแล้ว'");
                    echo $stmt->fetchColumn();
                    ?>
                </p>
            </div>
        </div>
    </div>

    <!-- ปฏิเสธแล้ว -->
    <div class="col-md-3 mb-3">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <h5 class="card-title">ปฏิเสธแล้ว</h5>
                <p class="card-text fs-4">
                    <?php
                    $stmt = $conn->query("SELECT COUNT(*) FROM travel_bookings WHERE booking_status = 'ปฏิเสธแล้ว'");
                    echo $stmt->fetchColumn();
                    ?>
                </p>
            </div>
        </div>
    </div>
</div>


<div class="content">
  <h1 class="mb-4">ปฏิทินการจองรถ </h1>
  <!-- ปรับขนาดปฏิทินให้เต็มพื้นที่ -->
  <div id="calendar" style="width:100%; height:calc(100vh - 200px);"></div>
</div>
</div>

<!-- FullCalendar CSS & JS -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
      initialView: 'dayGridMonth',
      headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay'
      },
      locale: 'th',
      displayEventTime: false, // ไม่แสดงเวลาในอีเวนต์
      events: <?php echo json_encode($events, JSON_HEX_TAG); ?>,
      eventClick: function(info) {
        window.location = 'manage_booking.php?id=' + info.event.id;
      }
    });
    calendar.render();
  });
</script>

<?php include 'partials/footer.php'; ?>
