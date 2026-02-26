<?php
// manage_booking.php
require_once '../config/connect.php';
require_once '../config/checklogin.php';
include 'partials/header.php';
include 'partials/sidebar.php';
?>

<h1 class="mb-4">จัดการการจองรถ</h1>
<div class="row mb-3">
  <div class="col-md-4">
    <input type="text" id="searchInput" class="form-control" placeholder="ค้นหา ชื่อ / ปลายทาง / สถานะ...">
  </div>
  <div class="col-md-3">
    <select id="statusFilter" class="form-select">
      <option value="">-- สถานะทั้งหมด --</option>
      <option value="รออนุมัติ">รออนุมัติ</option>
      <option value="อนุมัติแล้ว">อนุมัติแล้ว</option>
      <option value="ปฏิเสธแล้ว">ปฏิเสธแล้ว</option>
    </select>
  </div>
</div>

<?php
// ดึงข้อมูลรถและ map พร้อมสถานะ
$cars = $conn->query("SELECT id, car_name, license_plate, car_type, status FROM cars")->fetchAll(PDO::FETCH_ASSOC);
$carMap = [];
foreach ($cars as $c) {
    $carMap[$c['id']] = "{$c['car_name']} ({$c['license_plate']}) / {$c['car_type']}";
}
// ดึงข้อมูลการจอง
$bookings = $conn->query("SELECT * FROM travel_bookings
  ORDER BY
    CASE booking_status
      WHEN 'รออนุมัติ' THEN 0
      WHEN 'อนุมัติแล้ว' THEN 1
      WHEN 'ปฏิเสธแล้ว' THEN 2
      ELSE 3 END,
    start_date DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

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
        <th>จัดการ</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($bookings as $i => $b): ?>
      <?php
        $start_dt = strtotime("{$b['start_date']} {$b['start_time']}");
        $end_dt   = strtotime("{$b['end_date']} {$b['end_time']}");
        $days     = floor(($end_dt - $start_dt)/(60*60*24)) + 1;
        switch ($b['booking_status']) {
          case 'อนุมัติแล้ว': $rowClass='table-success'; break;
          case 'รออนุมัติ':   $rowClass='table-warning'; break;
          case 'ปฏิเสธแล้ว': $rowClass='table-danger';  break;
          default:            $rowClass='';             break;
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
        <td><?= $statusBadge ?></td>
        <td>
          <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#manageModal<?= $b['id'] ?>">จัดการ</button>

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
                    <dt class="col-sm-3">วันที่เดินทาง</dt><dd class="col-sm-9"><?= date('d/m/Y H:i', $start_dt) ?> - <?= date('d/m/Y H:i', $end_dt) ?></dd>
                    <dt class="col-sm-3">จำนวนวัน</dt><dd class="col-sm-9"><?= $days ?></dd>
                    <dt class="col-sm-3">รายละเอียด</dt><dd class="col-sm-9"><?= nl2br(htmlspecialchars($b['additional_details'])) ?></dd>
                    <dt class="col-sm-3">รถที่ได้</dt><dd class="col-sm-9"><?= htmlspecialchars($carMap[$b['car_id']] ?? 'ยังไม่ได้จัดสรร') ?></dd>
                    <dt class="col-sm-3">ผู้ร่วมเดินทาง</dt><dd class="col-sm-9"><?= htmlspecialchars($b['companions']) ?></dd>
                    <dt class="col-sm-3">สถานะ</dt><dd class="col-sm-9"><?= $statusBadge ?></dd>
                  </dl>
                  <form method="POST" action="update_status.php" class="mt-3">
                    <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                    <div class="row g-2 align-items-end">
                      <div class="col-md-8">
                        <label class="form-label">เลือกรถ</label>
                        <select name="car_id" class="form-select">
                          <option value="">เลือกรถ</option>
                          <?php foreach ($cars as $c):
                            $disabled = $c['status'] === 'รถคันนี้มีผู้ใช้งานแล้ว' ? 'disabled' : '';
                          ?>
                          <option value="<?= $c['id'] ?>"
                                  <?= $b['car_id'] == $c['id'] ? 'selected' : '' ?> <?= $disabled ?> >
                            <?= htmlspecialchars($c['car_name']) ?> (<?= htmlspecialchars($c['license_plate']) ?>)
                            <?= $disabled ? ' - ไม่ว่าง' : '' ?>
                          </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="col-md-4 text-end">
                        <button name="action" value="approve" class="btn btn-success me-1" onclick="return confirm('คุณต้องการอนุมัติรายการนี้?')">อนุมัติ</button>
                        <button name="action" value="reject" class="btn btn-danger" onclick="return confirm('คุณต้องการปฏิเสธรายการนี้?')">ปฏิเสธ</button>
                    </div>
                  </form>
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

  // ช่องค้นหาทั่วไป
  $('#searchInput').on('keyup', function() {
    table.search(this.value).draw();
  });

  // ตัวกรองสถานะ ใช้คอลัมน์ที่ 5 (index เริ่มจาก 0)
  $('#statusFilter').on('change', function() {
    table.column(5).search(this.value).draw();
  });
});
</script>

