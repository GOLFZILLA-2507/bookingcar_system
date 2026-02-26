<?php
require_once '../config/connect.php';
require_once '../config/checklogin.php';
include 'partials/header.php';
include 'partials/sidebar.php';

function thaiMonth($monthNumber) {
  $months = [
    1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
    5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
    9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
  ];
  return $months[$monthNumber] ?? '';
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_status = isset($_GET['status']) ? trim($_GET['status']) : '';
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

$startDate = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
$endDate = date("Y-m-t", strtotime($startDate));

$whereParts = ["start_date BETWEEN ? AND ?"];
$params = [$startDate, $endDate];

if ($search !== '') {
    $whereParts[] = "(full_name LIKE ? OR destination LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filter_status !== '') {
    $whereParts[] = "booking_status = ?";
    $params[] = $filter_status;
}

$whereClause = 'WHERE ' . implode(' AND ', $whereParts);

$sql = "SELECT *, DATEDIFF(DAY, start_date, end_date) + 1 AS total_days, DATEPART(ISO_WEEK, start_date) AS week_number FROM travel_bookings $whereClause ORDER BY start_date ASC";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูลรถทั้งหมดมา map ไว้สำหรับแสดงใน modal
$cars = $conn->query("SELECT id, car_name, license_plate, car_type FROM cars")->fetchAll(PDO::FETCH_ASSOC);
$carMap = [];
foreach ($cars as $c) {
    $carMap[$c['id']] = "{$c['car_name']} ({$c['license_plate']}) / {$c['car_type']}";
}

$grouped = [];
foreach ($bookings as $b) {
    $key = $b['week_number'];
    if (!isset($grouped[$key])) {
        $dt = new DateTime($b['start_date']);
        $dt->setISODate($year, $b['week_number']);
        $monday = $dt->format('d/m/Y');
        $dt->modify('+6 days');
        $sunday = $dt->format('d/m/Y');
        $grouped[$key] = [
            'label' => "$monday ถึง $sunday",
            'items' => []
        ];
    }
    $grouped[$key]['items'][] = $b;
}
?>
<div class="container py-4">
  <h2 class="mb-4">รายการจองเดือน <?= thaiMonth($month) ?> <?= $year ?></h2>
  <form class="row g-3 mb-3" method="get">
    <input type="hidden" name="month" value="<?= $month ?>">
    <input type="hidden" name="year" value="<?= $year ?>">
    <div class="col-md-4">
      <input type="text" name="search" class="form-control" placeholder="ค้นหา ชื่อ / ปลายทาง..." value="<?= htmlspecialchars($search) ?>">
    </div>
    <div class="col-md-4">
      <select name="status" class="form-select">
        <option value="">-- สถานะทั้งหมด --</option>
        <option value="รออนุมัติ" <?= $filter_status === 'รออนุมัติ' ? 'selected' : '' ?>>รออนุมัติ</option>
        <option value="อนุมัติแล้ว" <?= $filter_status === 'อนุมัติแล้ว' ? 'selected' : '' ?>>อนุมัติแล้ว</option>
        <option value="ปฏิเสธแล้ว" <?= $filter_status === 'ปฏิเสธแล้ว' ? 'selected' : '' ?>>ปฏิเสธแล้ว</option>
      </select>
    </div>
    <div class="col-md-2">
      <button type="submit" class="btn btn-primary w-100">ค้นหา</button>
    </div>
    <div class="col-md-2 d-flex justify-content-between">
      <a href="?month=<?= $month - 1 <= 0 ? 12 : $month - 1 ?>&year=<?= $month - 1 <= 0 ? $year - 1 : $year ?>" class="btn btn-outline-secondary">&laquo; ก่อนหน้า</a>
      <a href="?month=<?= $month + 1 > 12 ? 1 : $month + 1 ?>&year=<?= $month + 1 > 12 ? $year + 1 : $year ?>" class="btn btn-outline-secondary">ถัดไป &raquo;</a>
    </div>
  </form>

  <?php if (empty($grouped)): ?>
    <div class="alert alert-info">ไม่พบข้อมูลการจองในเดือนนี้</div>
  <?php else: ?>
    <?php foreach ($grouped as $week => $data): ?>
      <h4 class="mt-4">สัปดาห์ที่ <?= $week ?> (<?= $data['label'] ?>)</h4>
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
              <th>เพิ่มเติม</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($data['items'] as $idx => $b): ?>
              <tr>
                <td><?= $idx + 1 ?></td>
                <td><?= htmlspecialchars($b['full_name']) ?></td>
                <td><?= htmlspecialchars($b['destination']) ?></td>
                <td><?= date('d/m/Y H:i', strtotime($b['start_date'] . ' ' . $b['start_time'])) ?> - <?= date('d/m/Y H:i', strtotime($b['end_date'] . ' ' . $b['end_time'])) ?></td>
                <td><?= $b['total_days'] ?> วัน</td>
                <td>
                  <?php
                    switch ($b['booking_status']) {
                      case 'อนุมัติแล้ว': echo '<span class="badge bg-success">อนุมัติแล้ว</span>'; break;
                      case 'รออนุมัติ': echo '<span class="badge bg-warning text-dark">รออนุมัติ</span>'; break;
                      case 'ปฏิเสธแล้ว': echo '<span class="badge bg-danger">ปฏิเสธแล้ว</span>'; break;
                      default: echo '<span class="badge bg-secondary">' . htmlspecialchars($b['booking_status']) . '</span>';
                    }
                  ?>
                </td>
                <td><button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#detailModal<?= $b['id'] ?>">ดูรายละเอียด</button></td>
              </tr>
              <div class="modal fade" id="detailModal<?= $b['id'] ?>" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                  <div class="modal-content">
                    <div class="modal-header bg-info text-white">
                      <h5 class="modal-title">รายละเอียด Booking #<?= $b['id'] ?></h5>
                      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                      <dl class="row">
                        <dt class="col-sm-3">ผู้จอง</dt><dd class="col-sm-9"><?= htmlspecialchars($b['full_name']) ?></dd>
                        <dt class="col-sm-3">ปลายทาง</dt><dd class="col-sm-9"><?= htmlspecialchars($b['destination']) ?></dd>
                        <dt class="col-sm-3">วันที่เดินทาง</dt>
                        <dd class="col-sm-9"><?= date('d/m/Y H:i', strtotime($b['start_date'] . ' ' . $b['start_time'])) ?> - <?= date('d/m/Y H:i', strtotime($b['end_date'] . ' ' . $b['end_time'])) ?></dd>
                        <dt class="col-sm-3">จำนวนวัน</dt><dd class="col-sm-9"><?= $b['total_days'] ?> วัน</dd>
                        <dt class="col-sm-3">รายละเอียด</dt><dd class="col-sm-9"><?= nl2br(htmlspecialchars($b['additional_details'] ?? '-')) ?></dd>
                        <dt class="col-sm-3">รถที่ได้</dt>
                        <dd class="col-sm-9">
                          <?php
                            if (!empty($b['car_id']) && isset($carMap[$b['car_id']])) {
                                echo htmlspecialchars($carMap[$b['car_id']]);
                            } elseif ($b['booking_status'] === 'อนุมัติแล้ว') {
                                echo '<span class="text-danger">[ ยังไม่มีข้อมูลรถในระบบ ]</span>';
                            } else {
                                echo 'ยังไม่ได้จัดสรร';
                            }
                          ?>
                        </dd>
                        <dt class="col-sm-3">ผู้ร่วมเดินทาง</dt><dd class="col-sm-9"><?= htmlspecialchars($b['companions']) ?></dd>
                        <dt class="col-sm-3">สถานะ</dt>
                        <dd class="col-sm-9">
                          <?php
                            switch ($b['booking_status']) {
                              case 'อนุมัติแล้ว': echo '<span class="badge bg-success">อนุมัติแล้ว</span>'; break;
                              case 'รออนุมัติ': echo '<span class="badge bg-warning text-dark">รออนุมัติ</span>'; break;
                              case 'ปฏิเสธแล้ว': echo '<span class="badge bg-danger">ปฏิเสธแล้ว</span>'; break;
                              default: echo '<span class="badge bg-secondary">' . htmlspecialchars($b['booking_status']) . '</span>';
                            }
                          ?>
                        </dd>
                      </dl>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
<?php include 'partials/footer.php'; ?>
