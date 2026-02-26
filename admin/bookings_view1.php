<?php
// weekly_bookings_view.php (SQL Server compatible)
require_once '../config/connect.php';
require_once '../config/checklogin.php';
include 'partials/header.php';
include 'partials/sidebar.php';

// รับค่าค้นหา
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// สร้าง SQL และ binding ตามว่ามีการค้นหาหรือไม่
if ($search !== '') {
    $sql =
        "SELECT *,
           DATEDIFF(DAY, start_date, end_date) + 1 AS total_days,
           DATEPART(YEAR, start_date) AS year,
           DATEPART(ISO_WEEK, start_date) AS week_number
         FROM travel_bookings
         WHERE full_name LIKE ? OR destination LIKE ?
         ORDER BY start_date ASC";
    $stmt = $conn->prepare($sql);
    $search_param = "%$search%";
    $stmt->execute([$search_param, $search_param]);
} else {
    $sql =
        "SELECT *,
           DATEDIFF(DAY, start_date, end_date) + 1 AS total_days,
           DATEPART(YEAR, start_date) AS year,
           DATEPART(ISO_WEEK, start_date) AS week_number
         FROM travel_bookings
         ORDER BY start_date ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
}

$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// จัดกลุ่มตามปีและสัปดาห์
$grouped = [];
foreach ($bookings as $b) {
    $key = $b['year'] . '-W' . str_pad($b['week_number'], 2, '0', STR_PAD_LEFT);
    if (!isset($grouped[$key])) {
        $dt = new DateTime();
        $dt->setISODate($b['year'], $b['week_number']);
        $monday = $dt->format('Y-m-d');
        $dt->modify('+6 days');
        $sunday = $dt->format('Y-m-d');
        $grouped[$key] = [
            'label' => "$monday ถึง $sunday",
            'items' => []
        ];
    }
    $grouped[$key]['items'][] = $b;
}
?>
<div class="container py-4">
  <h2 class="mb-4">มุมมองการจองตามรายสัปดาห์</h2>
<div class="row mb-3">
  <div class="col-md-8">
    <input type="text" id="searchInput" class="form-control" placeholder="ค้นหา ชื่อ / ปลายทาง / สถานะ...">
  </div>
  <div class="col-md-4">
    <select id="statusFilter" class="form-select">
      <option value="">-- สถานะทั้งหมด --</option>
      <option value="รออนุมัติ">รออนุมัติ</option>
      <option value="อนุมัติแล้ว">อนุมัติแล้ว</option>
      <option value="ปฏิเสธแล้ว">ปฏิเสธแล้ว</option>
    </select>
  </div>
</div>


  <?php if (empty($grouped)): ?>
    <div class="alert alert-info">ไม่พบข้อมูลการจอง</div>
  <?php endif; ?>

  <?php foreach ($grouped as $weekKey => $weekData): ?>
    <h4 class="mt-4">สัปดาห์ <?= htmlspecialchars($weekKey) ?> (<?= htmlspecialchars($weekData['label']) ?>)</h4>
    <div class="table-responsive">
      <table class="table table-bordered align-middle">
        <thead class="table-primary text-white">
          <tr>
            <th>#</th>
            <th>ผู้จอง</th>
            <th>ปลายทาง</th>
            <th>ช่วงวันและเวลา</th>
            <th>จำนวนวัน</th>
            <th>สถานะ</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($weekData['items'] as $idx => $b): ?>
            <tr>
              <td><?= $idx + 1 ?></td>
              <td><?= htmlspecialchars($b['full_name']) ?></td>
              <td><?= htmlspecialchars($b['destination']) ?></td>
              <td>
                <?= date('d/m/Y', strtotime($b['start_date'])) . ' ' . date('H:i', strtotime($b['start_time'])) ?> ถึง <?= date('d/m/Y', strtotime($b['end_date'])) . ' ' . date('H:i', strtotime($b['end_time'])) ?>
              </td>
              <td><?= $b['total_days'] ?> วัน</td>
              <td>
                <?php
                  switch ($b['booking_status']) {
                    case 'อนุมัติแล้ว':
                      echo '<span class="badge bg-success">อนุมัติแล้ว</span>'; break;
                    case 'รออนุมัติ':
                      echo '<span class="badge bg-warning text-dark">รออนุมัติ</span>'; break;
                    case 'ปฏิเสธแล้ว':
                      echo '<span class="badge bg-danger">ปฏิเสธแล้ว</span>'; break;
                    default:
                      echo '<span class="badge bg-secondary">'.htmlspecialchars($b['booking_status']).'</span>';
                  }
                ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endforeach; ?>
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
      paginate: { first: 'หน้าแรก', last: 'หน้าสุดท้าย', next: 'ถัดไป', previous: 'ก่อนหน้า' }
    }
  });

  $('#searchInput').on('keyup', function(){ table.search(this.value).draw(); });
  $('#statusFilter').on('change', function(){ table.column(5).search(this.value).draw(); });
});
</script>
