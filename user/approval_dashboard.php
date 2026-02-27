<?php
session_start();
require_once '../config/connect.php';
include 'partials/header.php';
include 'partials/sidebar.php';

// ตรวจสอบ login
$emp = $_SESSION['EmployeeID'] ?? '';
if(!$emp){
    die("กรุณา login ก่อน");
}

// ===== ดึงรายการรออนุมัติ =====
$stmt = $conn->prepare("
SELECT 
    tb.id,
    tb.full_name,
    tb.destination,
    tb.start_date,
    tb.end_date,
    ISNULL(l.approver_level, 0) AS approver_level,
    l.status
FROM travel_bookings tb
INNER JOIN booking_approval_logs l
    ON tb.id = l.booking_id
WHERE l.approver_id = :emp
AND l.status = 'pending'
ORDER BY tb.start_date ASC
");

$stmt->execute(['emp'=>$emp]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <h3>📋 รายการรออนุมัติ</h3>

    <?php if(empty($rows)): ?>
        <div class="alert alert-info">ไม่มีรายการรออนุมัติ</div>
    <?php else: ?>

    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>ผู้จอง</th>
                <th>ปลายทาง</th>
                <th>วันที่</th>
                <th>ระดับ</th>
                <th>จัดการ</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($rows as $i=>$r): ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td><?= htmlspecialchars($r['full_name']) ?></td>
                <td><?= htmlspecialchars($r['destination']) ?></td>
                <td><?= $r['start_date'] ?> - <?= $r['end_date'] ?></td>

                <td>
                    <?php if($r['approver_level']==1): ?>
                        <span class="badge bg-primary">หัวหน้า</span>
                    <?php elseif($r['approver_level']==2): ?>
                        <span class="badge bg-warning text-dark">ผู้จัดการ</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">ไม่ระบุ</span>
                    <?php endif; ?>
                </td>

                <td>
                    <a href="update_approval.php?action=approve&id=<?= $r['id'] ?>" 
                       class="btn btn-success btn-sm">✔ อนุมัติ</a>

                    <a href="update_approval.php?action=reject&id=<?= $r['id'] ?>" 
                       class="btn btn-danger btn-sm">✖ ปฏิเสธ</a>
                </td>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>

    <?php endif; ?>
</div>

<?php include 'partials/footer.php'; ?>