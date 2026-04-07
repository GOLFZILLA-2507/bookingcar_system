<?php
session_start();
require_once '../config/connect.php';
include 'partials/header.php';
include 'partials/sidebar.php';

// ================= USER =================
$employee_id = $_SESSION['EmployeeID'];

$stmtUser = $conn->prepare("SELECT fullname FROM Employee WHERE EmployeeID = :id");
$stmtUser->execute(['id'=>$employee_id]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);

if(!$user){
    die("ไม่พบผู้ใช้");
}

// ================= BOOKING =================
$stmt = $conn->prepare("
    SELECT *
    FROM travel_bookings
    WHERE employee_id = :employee_id
    ORDER BY start_date DESC
");
$stmt->execute(['employee_id'=>$employee_id]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content">
<div class="container">
<h2 class="text-center mb-4">📋 รายการจองของคุณ</h2>

<?php if(count($bookings)>0): ?>

<!-- FILTER -->
<div class="row mb-3">
    <div class="col-md-3">
        <input type="text" id="searchInput" class="form-control" placeholder="🔍 ค้นหา...">
    </div>

    <div class="col-md-3">
        <select id="statusFilter" class="form-select">
            <option value="">สถานะทั้งหมด</option>
            <option value="รออนุมัติ">รออนุมัติ</option>
            <option value="อนุมัติแล้ว">อนุมัติแล้ว</option>
            <option value="ปฏิเสธแล้ว">ปฏิเสธแล้ว</option>
        </select>
    </div>

    <div class="col-md-3">
        <input type="text" id="projectFilter" class="form-control" placeholder="🏗️ กรองโครงการ">
    </div>
</div>

<!-- TABLE -->
<div class="table-responsive shadow rounded">
<table class="table table-hover align-middle text-center" id="bookingTable">

<thead class="bg-primary text-white">
<tr>
<th>#</th>
<th>ผู้จอง</th>
<th>โครงการ</th>
<th>ช่วงเวลา</th>
<th>วัน</th>
<th>สถานะ</th>
<th>จัดการ</th>
</tr>
</thead>

<tbody>
<?php foreach($bookings as $i=>$b): 

$start = strtotime($b['start_date']);
$end   = strtotime($b['end_date']);
$days  = floor(($end-$start)/(60*60*24))+1;

// สีสถานะ
switch($b['booking_status']){
    case 'อนุมัติแล้ว':
        $badge = '<span class="badge bg-success px-3">✔ อนุมัติ</span>';
        break;
    case 'รออนุมัติ':
        $badge = '<span class="badge bg-warning text-dark px-3">⏳ รออนุมัติ</span>';
        break;
    case 'ปฏิเสธแล้ว':
        $badge = '<span class="badge bg-danger px-3">✖ ปฏิเสธ</span>';
        break;
    default:
        $badge = '<span class="badge bg-secondary">'.$b['booking_status'].'</span>';
}
?>

<tr>
<td><?= $i+1 ?></td>
<td><?= htmlspecialchars($b['full_name']) ?></td>
<td><?= htmlspecialchars($b['destination']) ?></td>
<td>
<?= date('d/m/Y H:i',$start) ?><br>
→ <?= date('d/m/Y H:i',$end) ?>
</td>
<td><b><?= $days ?></b></td>
<td><?= $badge ?></td>
<td>
<button class="btn btn-sm btn-primary"
data-bs-toggle="modal"
data-bs-target="#modal<?= $b['id'] ?>">
รายละเอียด
</button>
</td>
</tr>

<!-- MODAL -->
<div class="modal fade" id="modal<?= $b['id'] ?>">
<div class="modal-dialog modal-lg modal-dialog-centered">
<div class="modal-content">

<div class="modal-header bg-primary text-white">
<h5>📌 รายละเอียดการจอง</h5>
<button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">

<?php
// แยกโครงการ
$destArr = explode(',', $b['destination']);
$destHtml = '';
foreach($destArr as $k=>$d){
    $destHtml .= ($k+1).'. '.trim($d).'<br>';
}

// แยกผู้ร่วมเดินทาง
$compArr = explode(',', $b['companions']);
$compHtml = '';
foreach($compArr as $k=>$c){
    if(trim($c)!==''){
        $compHtml .= ($k+1).'. '.trim($c).'<br>';
    }
}
?>

<p><b>ผู้จอง:</b> <?= $b['full_name'] ?></p>
<p><b>โครงการ:</b><br><?= $destHtml ?: '-' ?></p>
<p><b>ผู้ร่วมเดินทาง:</b><br><?= $compHtml ?: '-' ?></p>

<p><b>เวลา:</b><br>
<?= date('d/m/Y H:i',$start) ?> - <?= date('d/m/Y H:i',$end) ?>
</p>

<p><b>จำนวนวัน:</b> <?= $days ?> วัน</p>

<p><b>รายละเอียด:</b><br>
<?= nl2br(htmlspecialchars($b['additional_details'] ?? '-')) ?>
</p>

<p><b>รถ:</b>
<?= htmlspecialchars($b['car_name'] ?? 'ยังไม่ได้จัด') ?>
</p>

<p><b>สถานะ:</b> <?= $badge ?></p>

</div>

<div class="modal-footer">
<button class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
</div>

</div>
</div>
</div>

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

<!-- DATATABLE -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<script>
$(function(){

let table = $('#bookingTable').DataTable({
    dom:'lrtip',
    pageLength:10
});

// search
$('#searchInput').keyup(function(){
    table.search(this.value).draw();
});

// filter status
$('#statusFilter').change(function(){
    table.column(5).search(this.value).draw();
});

// filter project
$('#projectFilter').keyup(function(){
    table.column(2).search(this.value).draw();
});

});
</script>