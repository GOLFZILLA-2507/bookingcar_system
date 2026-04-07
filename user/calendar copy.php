<?php
session_start();
require_once '../config/connect.php';
include 'partials/header.php';
include 'partials/sidebar.php';

// ================= USER =================
$employee_id = $_SESSION['EmployeeID'] ?? null;

// ================= DASHBOARD =================
$stmtMy = $conn->prepare("
    SELECT COUNT(*) total,
    SUM(CASE WHEN booking_status='รออนุมัติ' THEN 1 ELSE 0 END) pending,
    SUM(CASE WHEN booking_status='อนุมัติแล้ว' THEN 1 ELSE 0 END) approve
    FROM travel_bookings
    WHERE employee_id = :id
");
$stmtMy->execute(['id'=>$employee_id]);
$my = $stmtMy->fetch(PDO::FETCH_ASSOC);

// ================= ALL BOOKINGS =================
$stmt = $conn->query("SELECT * FROM travel_bookings");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$events = [];

foreach($rows as $r){

    switch($r['booking_status']){
        case 'อนุมัติแล้ว': $color='#16a34a'; break;
        case 'รออนุมัติ': $color='#facc15'; break;
        case 'ปฏิเสธแล้ว': $color='#dc2626'; break;
        default: $color='#6b7280';
    }

    $events[] = [
        'id'=>$r['id'],
        'title'=>$r['destination'] ?? 'ไม่มีข้อมูล',
        'start'=>$r['start_date'],
        'end'=>$r['end_date'],
        'color'=>$color,
        'extendedProps'=>$r
    ];
}
?>

<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">

<style>
.content{
    background: linear-gradient(135deg,#eef2ff,#f8fafc);
}
.card-box{
    border-radius:15px;
    padding:20px;
    color:white;
    text-align:center;
}
.bg-blue{background:#2563eb;}
.bg-yellow{background:#facc15;color:black;}
.bg-green{background:#16a34a;}

#calendar{
    background:white;
    padding:20px;
    border-radius:15px;
    box-shadow:0 10px 30px rgba(0,0,0,0.1);
}
</style>

<div class="content">
<div class="container py-4">

<h2 class="text-center mb-4">📊 Dashboard การจองรถ</h2>

<div class="row mb-4">
<div class="col-md-4">
<div class="card-box bg-blue">
📌 การจองของฉัน
<h2><?= $my['total'] ?? 0 ?></h2>
</div>
</div>

<div class="col-md-4">
<div class="card-box bg-yellow">
⏳ รออนุมัติ
<h2><?= $my['pending'] ?? 0 ?></h2>
</div>
</div>

<div class="col-md-4">
<div class="card-box bg-green">
✔ อนุมัติแล้ว
<h2><?= $my['approve'] ?? 0 ?></h2>
</div>
</div>
</div>

<div class="text-center mb-3">
<a href="index.php" class="btn btn-primary">➕ จองรถ</a>
</div>

<div class="text-center mb-3">
<span class="badge bg-success">อนุมัติ</span>
<span class="badge bg-warning text-dark">รออนุมัติ</span>
<span class="badge bg-danger">ปฏิเสธ</span>
</div>

<div id="calendar"></div>

</div>
</div>

<!-- MODAL -->
<div class="modal fade" id="eventModal">
<div class="modal-dialog modal-lg modal-dialog-centered">
<div class="modal-content">

<div class="modal-header bg-primary text-white">
<h5>📌 รายละเอียดการจอง</h5>
<button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body" id="modalBody"></div>

</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function(){

// ===== ฟังก์ชัน format เวลา (ไทย + ไม่มี .000)
function formatThaiDate(dt){
    let d = new Date(dt);

    let day = d.getDate();
    let month = d.toLocaleString('th-TH',{month:'short'});
    let year = d.getFullYear()+543;

    let hour = String(d.getHours()).padStart(2,'0');
    let min = String(d.getMinutes()).padStart(2,'0');

    return `${day} ${month} ${year} ${hour}:${min}`;
}

let calendar = new FullCalendar.Calendar(document.getElementById('calendar'),{

    initialView:'dayGridMonth',

    headerToolbar:{
        left:'prev,next today',
        center:'title',
        right:'dayGridMonth,timeGridWeek,timeGridDay'
    },

    buttonText:{
        today:'วันนี้',
        month:'เดือน',
        week:'สัปดาห์',
        day:'วัน'
    },

    locale:'th',

    height:650,

    events: <?= json_encode($events) ?>,

    eventClick:function(info){

        let d = info.event.extendedProps;

        let dest = d.destination ? d.destination.split(',') : [];
        let comp = d.companions ? d.companions.split(',') : [];

        let destHtml = dest.length ? '' : '-';
        dest.forEach((x,i)=>{
            destHtml += (i+1)+'. '+x+'<br>';
        });

        let compHtml = comp.length ? '' : '-';
        comp.forEach((x,i)=>{
            if(x.trim()!=''){
                compHtml += (i+1)+'. '+x+'<br>';
            }
        });

        let s = new Date(d.start_date);
        let e = new Date(d.end_date);
        let days = Math.ceil((e-s)/(1000*60*60*24))+1;

        document.getElementById('modalBody').innerHTML = `
        <p><b>👤 ชื่อผู้จอง:</b> ${d.full_name ?? '-'}</p>

        <p><b>🏗️ สถานที่เดินทาง:</b><br>${destHtml}</p>

        <p><b>👥 ผู้ร่วมเดินทาง (${comp.length} คน):</b><br>${compHtml}</p>

        <p><b>📅 จำนวนวันทั้งหมด:</b> ${days} วัน</p>

        <p><b>🕒 เวลา:</b><br>
        ${formatThaiDate(d.start_date)} ถึง ${formatThaiDate(d.end_date)}
        </p>

        <p><b>🚗 รถที่ใช้:</b> ${d.car_name ?? 'Admin ยังไม่จัดสรร'}</p>

        <p><b>📌 สถานะ:</b> ${d.booking_status}</p>
        `;

        new bootstrap.Modal(document.getElementById('eventModal')).show();
    }

});

calendar.render();

});
</script>

<?php include 'partials/footer.php'; ?>