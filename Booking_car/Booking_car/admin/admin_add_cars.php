<?php
// manage_cars.php
require_once '../config/connect.php';
require_once '../config/checklogin.php';
include 'partials/header.php';
include 'partials/sidebar.php';

// ดึงข้อมูลรถทั้งหมด
$cars = $conn->query("SELECT * FROM cars ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="container py-4">
  <h2 class="mb-4">🚗 จัดการข้อมูลรถ</h2>

  <!-- ปุ่มเปิด Add Modal -->
  <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addCarModal">
    ➕ เพิ่มรถ
  </button>

  <div class="table-responsive">
    <table class="table table-bordered align-middle">
      <thead class="table-primary text-white">
        <tr>
          <th>#</th><th>ชื่อรถ</th><th>ทะเบียน</th><th>ประเภท</th><th>สถานะ</th><th>จัดการ</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($cars as $i => $car):
          $status = $car['status'] ?? 'ว่าง';
          $statusHtml = $status === 'รถคันนี้มีผู้ใช้งานแล้ว'
            ? '<span class="badge bg-danger">รถคันนี้มีผู้ใช้งานแล้ว</span>'
            : '<span class="badge bg-success">ว่าง</span>';
        ?>
        <tr>
          <td><?= $i+1 ?></td>
          <td><?= htmlspecialchars($car['car_name']) ?></td>
          <td><?= htmlspecialchars($car['license_plate']) ?></td>
          <td><?= htmlspecialchars($car['car_type']) ?></td>
          <td><?= $statusHtml ?></td>
          <td>
            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editCarModal<?= $car['id'] ?>">
              แก้ไข
            </button>
            <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteCarModal<?= $car['id'] ?>">
              ลบ
            </button>
          </td>
        </tr>

        <!-- Edit Modal -->
        <div class="modal fade" id="editCarModal<?= $car['id'] ?>" tabindex="-1">
          <div class="modal-dialog">
            <div class="modal-content">
              <form method="POST" action="car_update.php"
                    onsubmit="return confirm('คุณต้องการบันทึกการแก้ไขรถคันนี้?');">
                <div class="modal-header">
                  <h5 class="modal-title">แก้ไขข้อมูลรถ</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                  <input type="hidden" name="car_id" value="<?= $car['id'] ?>">
                  <div class="mb-3">
                    <label>ชื่อรถ</label>
                    <input type="text" name="car_name" class="form-control"
                           value="<?= htmlspecialchars($car['car_name']) ?>" required>
                  </div>
                  <div class="mb-3">
                    <label>ทะเบียนรถ</label>
                    <input type="text" name="license_plate" class="form-control"
                           value="<?= htmlspecialchars($car['license_plate']) ?>" required>
                  </div>
                  <div class="mb-3">
                    <label>ประเภทรถ</label>
                    <input type="text" name="car_type" class="form-control"
                           value="<?= htmlspecialchars($car['car_type']) ?>">
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="submit" class="btn btn-success">บันทึก</button>
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- Delete Modal -->
        <div class="modal fade" id="deleteCarModal<?= $car['id'] ?>" tabindex="-1">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <form method="POST" action="car_delete.php"
                    onsubmit="return confirm('คุณต้องการลบรถคันนี้จริงหรือไม่?');">
                <div class="modal-header">
                  <h5 class="modal-title">ยืนยันการลบ</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                  <input type="hidden" name="delete_id" value="<?= $car['id'] ?>">
                  คุณต้องการลบรถ <strong><?= htmlspecialchars($car['car_name']) ?></strong> ใช่หรือไม่?
                </div>
                <div class="modal-footer">
                  <button type="submit" class="btn btn-danger">ยืนยันลบ</button>
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Car Modal -->
<div class="modal fade" id="addCarModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="car_add.php"
            onsubmit="return confirm('คุณต้องการบันทึกรถคันใหม่หรือไม่?');">
        <div class="modal-header">
          <h5 class="modal-title">➕ เพิ่มรถ</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label>ชื่อรถ</label>
            <input type="text" name="car_name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>ทะเบียนรถ</label>
            <input type="text" name="license_plate" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>ประเภทรถ</label>
            <input type="text" name="car_type" class="form-control">
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">บันทึก</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include 'partials/footer.php'; ?>
