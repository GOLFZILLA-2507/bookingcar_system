<?php
require_once '../config/connect.php';
require_once '../config/checklogin.php';
include 'partials/header.php';
include 'partials/sidebar.php';
?>

<h1 class="mb-4">จัดการสถานะรถ</h1>

<table class="table table-bordered">
  <thead>
    <tr>
      <th>#</th>
      <th>ชื่อรถ</th>
      <th>ทะเบียน</th>
      <th>ประเภท</th>
      <th>สถานะ</th>
      <th>จัดการ</th>
    </tr>
  </thead>
  <tbody>
    <?php
    $cars = $conn->query("SELECT * FROM cars")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cars as $i => $c):
      $statusText = htmlspecialchars($c['status']);
      if ($c['status'] === 'รถคันนี้มีผู้ใช้งานแล้ว') {
        // สถานะถูกใช้งานแล้ว → แดง
        $badge = "<span class=\"badge bg-danger\">{$statusText}</span>";
      } else {
        // สถานะอื่นทั้งหมด → เขียว
        $badge = "<span class=\"badge bg-success\">{$statusText}</span>";
      }
    ?>
    <tr>
      <td><?= $i + 1 ?></td>
      <td><?= htmlspecialchars($c['car_name']) ?></td>
      <td><?= htmlspecialchars($c['license_plate']) ?></td>
      <td><?= htmlspecialchars($c['car_type']) ?></td>
      <td><?= $badge ?></td>
      <td>
        <form method="POST" action="toggle_car_status.php" onsubmit="return confirm('ยืนยันเปลี่ยนสถานะ?')">
          <input type="hidden" name="car_id" value="<?= $c['id'] ?>">
          <?php if ($c['status'] === 'รถคันนี้มีผู้ใช้งานแล้ว'): ?>
            <button class="btn btn-sm btn-outline-success">ตั้งเป็นว่าง</button>
          <?php else: ?>
            <button class="btn btn-sm btn-outline-danger">ตั้งเป็นมีผู้ใช้งานแล้ว</button>
          <?php endif; ?>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php include 'partials/footer.php'; ?>
