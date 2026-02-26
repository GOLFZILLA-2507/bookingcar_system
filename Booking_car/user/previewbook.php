<?php
session_start();
require_once '../config/connect.php';
include 'partials/header.php';
include 'partials/sidebar.php';

$employee_id = $_SESSION['EmployeeID'];

$stmt = $conn->prepare(
    "SELECT id, full_name, destination, companions, start_date, start_time, end_date, end_time, booking_status, additional_details, car_id
     FROM travel_bookings
     WHERE employee_id = :employee_id
     ORDER BY start_date DESC"
);
$stmt->bindParam(':employee_id', $employee_id);
$stmt->execute();
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cars = [];
$carMap = [];
$car_stmt = $conn->query("SELECT id, car_name, license_plate, status FROM cars");
if ($car_stmt) {
    $cars = $car_stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cars as $car) {
        $carMap[$car['id']] = $car['car_name'] . ' (' . $car['license_plate'] . ')';
    }
}
?>

<div class="content">
  <div class="container">
    <h2 class="text-center mb-4">รายการจองของคุณ</h2>

    <?php if (count($bookings) > 0): ?>
    <div class="mb-3 row">
      <div class="col-md-4">
        <input type="text" id="searchInput" class="form-control" placeholder="ค้นหา...">
      </div>
      <div class="col-md-4">
        <select id="statusFilter" class="form-select">
          <option value="">ทั้งหมด</option>
          <option value="รออนุมัติ">รออนุมัติ</option>
          <option value="อนุมัติแล้ว">อนุมัติแล้ว</option>
          <option value="ปฏิเสธแล้ว">ปฏิเสธแล้ว</option>
        </select>
      </div>
    </div>

    <div class="table-responsive border rounded shadow-sm">
      <table class="table table-bordered table-hover align-middle mb-0" id="bookingTable">
        <thead class="table-primary text-white">
          <tr>
            <th>#</th>
            <th>ผู้จอง</th>
            <th>ปลายทาง</th>
            <th>วันที่จอง</th>
            <th>จำนวนวัน</th>
            <th>สถานะ</th>
            <th>เพิ่มเติม</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($bookings as $i => $b): ?>
          <?php
            $start_dt = strtotime("{$b['start_date']} {$b['start_time']}");
            $end_dt = strtotime("{$b['end_date']} {$b['end_time']}");
            $days = floor(($end_dt - $start_dt)/(60*60*24)) + 1;

            switch ($b['booking_status']) {
              case 'อนุมัติแล้ว': $rowClass = 'table-success'; break;
              case 'รออนุมัติ':   $rowClass = 'table-warning'; break;
              case 'ปฏิเสธแล้ว': $rowClass = 'table-danger';  break;
              default:            $rowClass = '';              break;
            }

            switch ($b['booking_status']) {
              case 'อนุมัติแล้ว':
                $statusBadge = '<span class="badge bg-success">อนุมัติแล้ว</span>'; break;
              case 'รออนุมัติ':
                $statusBadge = '<span class="badge bg-warning text-dark">รออนุมัติ</span>'; break;
              case 'ปฏิเสธแล้ว':
                $statusBadge = '<span class="badge bg-danger">ปฏิเสธแล้ว</span>'; break;
              default:
                $statusBadge = '<span class="badge bg-secondary">'.htmlspecialchars($b['booking_status']).'</span>';
            }
          ?>
          <tr class="<?= $rowClass ?>">
            <td><?= $i+1 ?></td>
            <td><?= htmlspecialchars($b['full_name']) ?></td>
            <td><?= htmlspecialchars($b['destination']) ?></td>
            <td><?= date('d/m/Y H:i', $start_dt) ?> - <?= date('d/m/Y H:i', $end_dt) ?></td>
            <td><?= $days ?></td>
            <td><?= strip_tags($statusBadge) ?></td> <!-- ใช้ strip_tags เพื่อให้ JS filter ได้ -->
            <td>
              <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#manageModal<?= $b['id'] ?>">รายละเอียด</button>

              <!-- Modal -->
              <div class="modal fade" id="manageModal<?= $b['id'] ?>" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                  <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                      <h5 class="modal-title">Booking #<?= $b['id'] ?></h5>
                      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                      <dl class="row">
                        <dt class="col-sm-3">ผู้จอง</dt><dd class="col-sm-9"><?= htmlspecialchars($b['full_name']) ?></dd>
                        <dt class="col-sm-3">ปลายทาง</dt><dd class="col-sm-9"><?= htmlspecialchars($b['destination']) ?></dd>
                        <dt class="col-sm-3">เริ่มต้น</dt><dd class="col-sm-9"><?= date('d/m/Y H:i', $start_dt) ?></dd>
                        <dt class="col-sm-3">สิ้นสุด</dt><dd class="col-sm-9"><?= date('d/m/Y H:i', $end_dt) ?></dd>
                        <dt class="col-sm-3">จำนวนวัน</dt><dd class="col-sm-9"><?= $days ?></dd>
                        <dt class="col-sm-3">รายละเอียด</dt><dd class="col-sm-9"><?= nl2br(htmlspecialchars($b['additional_details'] ?? '-')) ?></dd>
                        <dt class="col-sm-3">รถที่ได้</dt><dd class="col-sm-9"><?= htmlspecialchars($carMap[$b['car_id']] ?? 'ยังไม่ได้จัดสรร') ?></dd>
                        <dt class="col-sm-3">ผู้ร่วมเดินทาง</dt><dd class="col-sm-9"><?= htmlspecialchars($b['companions']) ?></dd>
                        <dt class="col-sm-3">สถานะ</dt><dd class="col-sm-9"><?= $statusBadge ?></dd>
                      </dl>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                    </div>
                  </div>
                </div>
              </div>
              <!-- End Modal -->
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php else: ?>
      <div class="alert alert-info text-center mt-4">
        ยังไม่มีรายการจอง
      </div>
    <?php endif; ?>
  </div>
</div>

<?php include 'partials/footer.php'; ?>

<!-- scripts -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
$(function(){
  var table = $('#bookingTable').DataTable({
    dom: 'lrtip',
    pageLength: 10,
    lengthChange: false,
    autoWidth: false,
    scrollX: true,
    language: {
      zeroRecords: 'ไม่พบข้อมูล',
      info: 'แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ',
      paginate: {
        first: 'หน้าแรก',
        last: 'หน้าสุดท้าย',
        next: 'ถัดไป',
        previous: 'ก่อนหน้า'
      }
    }
  });

  $('#searchInput').on('keyup', function(){
    table.search(this.value).draw();
  });

  $('#statusFilter').on('change', function(){
    const val = this.value;
    if (val) {
      table.column(5).search(val).draw(); // คอลัมน์สถานะคือ index 5
    } else {
      table.column(5).search('').draw(); // รีเซ็ต
    }
  });
});
</script>
