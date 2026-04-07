<?php
session_start();
require_once '../config/connect.php';

/* ================= AJAX SEARCH ================= */
if (isset($_GET['action']) && $_GET['action'] === 'search_employee') {

    header('Content-Type: application/json; charset=utf-8');

    $q = trim($_GET['q'] ?? '');

    if ($q === '') {
        echo json_encode([]);
        exit;
    }

    $stmt = $conn->prepare("
        SELECT TOP 10 fullname, position, department
        FROM Employee
        WHERE active = 1 AND fullname LIKE :q
    ");
    $stmt->execute(['q'=>"%$q%"]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

/* ================= INSERT ================= */
if($_SERVER['REQUEST_METHOD']=='POST'){

    $employee_id = $_SESSION['EmployeeID'];

    // ดึง user
    $u = $conn->prepare("SELECT fullname FROM Employee WHERE EmployeeID=:id");
    $u->execute(['id'=>$employee_id]);
    $userData = $u->fetch();

    $full_name = $userData['fullname'];

    $destination = !empty($_POST['destination']) ? implode(", ", $_POST['destination']) : '';
    $companions = !empty($_POST['companions']) ? implode(", ", $_POST['companions']) : '';
    $start_date = $_POST['start_date'] . ' ' . $_POST['start_time'];
    $end_date   = $_POST['end_date'] . ' ' . $_POST['end_time'];
    $detail = $_POST['additional_details'];

    $file_name = null;

    if(!empty($_FILES['booking_file']['name'])){
        $new = time()."_".$_FILES['booking_file']['name'];
        move_uploaded_file($_FILES['booking_file']['tmp_name'],"../uploads/".$new);
        $file_name = $new;
    }

        $sql = "INSERT INTO travel_bookings
        (full_name, employee_id, companions, destination, start_date, end_date, additional_details, booking_status, approver1_status, approver2_status, file_name, created_at)
        VALUES
        (:full_name, :employee_id, :companions, :destination, :start_date, :end_date, :detail, 'รออนุมัติ','รออนุมัติ','รออนุมัติ', :file_name, GETDATE())";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        'full_name'=>$full_name,
        'employee_id'=>$employee_id,
        'companions'=>$companions,
        'destination'=>$destination,
        'start_date'=>$start_date,
        'end_date'=>$end_date,
        'detail'=>$detail,
        'file_name'=>$file_name
    ]);

    header("Location:index.php?success=1");
    exit;
}

/* ================= LOAD DATA ================= */
include 'partials/header.php';
include 'partials/sidebar.php';

// ===== ผู้ login =====
$stmt = $conn->prepare("
    SELECT fullname, position, department
    FROM Employee
    WHERE EmployeeID = :id
");
$stmt->execute(['id' => $_SESSION['EmployeeID']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// ===== ดึงโครงการ =====
$stmtProject = $conn->query("
    SELECT TOP (1000) project_id, project_name
    FROM IT_projects
    WHERE project_name IS NOT NULL
    ORDER BY project_name
");
$projects = $stmtProject->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<div class="content">
    <h2 class="text-center">ฟอร์มจองรถ</h2>

    <form method="post" enctype="multipart/form-data" onsubmit="return confirmSubmit()" class="p-4 border rounded bg-light">

        <!-- USER -->
        <div class="row mb-3">
            <div class="col-md-4">
                <label>ชื่อ</label>
                <input type="text" class="form-control"
                       value="<?= htmlspecialchars($user['fullname']) ?>" readonly>
            </div>
            <div class="col-md-4">
                <label>ตำแหน่ง</label>
                <input type="text" class="form-control"
                       value="<?= htmlspecialchars($user['position']) ?>" readonly>
            </div>
            <div class="col-md-4">
                <label>แผนก</label>
                <input type="text" class="form-control"
                       value="<?= htmlspecialchars($user['department']) ?>" readonly>
            </div>
        </div>

        <!-- ผู้ร่วมเดินทาง -->
        <div class="mb-3">
            <label>ผู้ร่วมเดินทาง</label>

            <div id="companions-group">
                <div class="input-group mb-2 position-relative">
                    <input type="text" class="form-control companion-search" placeholder="พิมพ์ชื่อ...">
                    <input type="hidden" name="companions[]" class="companion-value">
                    <button type="button" class="btn btn-outline-primary add-companion">+</button>

                    <div class="list-group companion-result position-absolute w-100"
                         style="z-index:1000; top:100%;"></div>
                </div>
            </div>
        </div>

        <!-- สถานที่ -->
        <div class="mb-3">
            <label>สถานที่</label>

            <div id="destination-group">
                <div class="input-group mb-2">
                    <select name="destination[]" class="form-control" required>
                        <option value="">-- เลือกโครงการ --</option>
                        <?php foreach($projects as $p): ?>
                            <option value="<?= htmlspecialchars($p['project_name']) ?>">
                                <?= htmlspecialchars($p['project_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="btn btn-outline-primary add-destination">+</button>
                </div>
            </div>
        </div>

        <!-- วันที่ -->
        <div class="row mb-3">
            <div class="col-md-6">
                <label>วันที่เริ่ม</label>
                <input type="text" id="start_date" name="start_date" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label>เวลาเริ่ม</label>
                <input type="text" id="start_time" name="start_time" class="form-control" required>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label>วันที่สิ้นสุด</label>
                <input type="text" id="end_date" name="end_date" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label>เวลาสิ้นสุด</label>
                <input type="text" id="end_time" name="end_time" class="form-control" required>
            </div>
        </div>

        <!-- จำนวนวัน -->
        <div class="mb-3">
            <label>จำนวนวัน</label>
            <input type="text" id="total_days" class="form-control" readonly>
        </div>

        <!-- ไฟล์ -->
        <div class="mb-3">
            <label>แนบไฟล์</label>
            <input type="file" name="booking_file" class="form-control">
        </div>

        <!-- รายละเอียด -->
        <div class="mb-3">
            <label>รายละเอียด</label>
            <textarea name="additional_details" class="form-control" required></textarea>
        </div>

        <button class="btn btn-success w-100">บันทึก</button>
    </form>
</div>

<!-- ===================== MODAL ===================== -->

<div class="modal fade" id="confirmModal">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">

      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">ยืนยันการจอง</h5>
      </div>

      <div class="modal-body">
        <p><b>สถานที่:</b><br> <span id="cf_destination"></span></p>
        <p><b>ผู้ร่วมเดินทาง:</b><br> <span id="cf_companions"></span></p>
        <p><b>วันเวลา:</b><br> <span id="cf_datetime"></span></p>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
        <button class="btn btn-success" id="confirmSubmitBtn">ยืนยัน</button>
      </div>

    </div>
  </div>
</div>

<div class="modal fade" id="alertModal">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content text-center p-3">
        <h5 class="text-danger">แจ้งเตือน</h5>
        <p id="alertText"></p>
        <button class="btn btn-danger" data-bs-dismiss="modal">ปิด</button>
    </div>
  </div>
</div>

<div class="modal fade" id="successModal">
  <div class="modal-dialog modal-sm">
    <div class="modal-content text-center p-3">
        <h5 class="text-success">บันทึกสำเร็จ</h5>
        <p>...</p>
    </div>
  </div>
</div>



<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>

// ===== date =====
flatpickr("#start_date",{dateFormat:"Y-m-d"});
flatpickr("#end_date",{dateFormat:"Y-m-d"});
flatpickr("#start_time",{enableTime:true,noCalendar:true,dateFormat:"H:i"});
flatpickr("#end_time",{enableTime:true,noCalendar:true,dateFormat:"H:i"});

// ===== คำนวณวัน =====
function calculateTotalDays(){
    let s = $('#start_date').val();
    let e = $('#end_date').val();
    if(s && e){
        let diff = Math.ceil((new Date(e)-new Date(s))/(1000*60*60*24))+1;
        $('#total_days').val(diff+' วัน');
    }
}
$('#start_date,#end_date').on('change',calculateTotalDays);

// ================= REALTIME SEARCH (แก้ครบ) =================
$(document).on('keyup', '.companion-search', function () {

    let input = $(this);
    let keyword = input.val().trim();
    let resultBox = input.closest('.input-group').find('.companion-result');

    if (keyword.length < 1) {
        resultBox.empty();
        return;
    }

    $.ajax({
        url: "index.php",
        method: "GET",
        dataType: "json",
        data: {
            action: "search_employee",
            q: keyword
        },
        success: function (data) {

            let html = "";

            if (!data || data.length === 0) {
                html = '<div class="list-group-item text-muted">ไม่พบข้อมูล</div>';
            } else {
                data.forEach(function (row) {
                    let label = row.fullname + " - " + row.position + " (" + row.department + ")";
                    html += `<a href="#" class="list-group-item list-group-item-action" data-value="${label}">${label}</a>`;
                });
            }

            resultBox.html(html);
        }
    });

});

// ===== เลือกค่า =====
$(document).on('click', '.companion-result a', function (e) {
    e.preventDefault();

    let val = $(this).data('value');
    let group = $(this).closest('.input-group');

    group.find('.companion-search').val(val);
    group.find('.companion-value').val(val);
    group.find('.companion-result').empty();
});

// ===== เพิ่มผู้ร่วมเดินทาง =====
$(document).on('click','.add-companion',function(){
    $('#companions-group').append(`
        <div class="input-group mb-2 position-relative">
            <input type="text" class="form-control companion-search" placeholder="พิมพ์ชื่อ...">
            <input type="hidden" name="companions[]" class="companion-value">
            <button type="button" class="btn btn-danger remove-companion">-</button>
            <div class="list-group companion-result position-absolute w-100" style="z-index:1000; top:100%;"></div>
        </div>
    `);
});

// ===== ลบ =====
$(document).on('click','.remove-companion',function(){
    $(this).closest('.input-group').remove();
});

// ===== เพิ่มสถานที่ =====
$(document).on('click','.add-destination',function(){

    // ดึง option จาก PHP (สำคัญ)
    let options = `<?php foreach($projects as $p): ?>
        <option value="<?= htmlspecialchars($p['project_name']) ?>">
            <?= htmlspecialchars($p['project_name']) ?>
        </option>
    <?php endforeach; ?>`;

    $('#destination-group').append(`
        <div class="input-group mb-2">
            <select name="destination[]" class="form-control" required>
                <option value="">-- เลือกโครงการ --</option>
                ${options}
            </select>
            <button type="button" class="btn btn-danger remove-destination">-</button>
        </div>
    `);
});

// ===== ลบสถานที่ =====
$(document).on('click','.remove-destination',function(){
    $(this).closest('.input-group').remove();
});

// ===== CONFIRM =====
function confirmSubmit(){

    try {

        let dest = [];
        $('select[name="destination[]"]').each(function(){
            if($(this).val()) dest.push($(this).val());
        });

        let comp = [];
        $('.companion-value').each(function(){
            if($(this).val()) comp.push($(this).val());
        });

        let start = $('#start_date').val();
        let end = $('#end_date').val();

        // ===== VALIDATION =====

        if(!start || !end){
            alert("กรุณาเลือกวันที่ให้ครบ");
            return false;
        }

        let s = new Date(start);
        let e = new Date(end);

        if(e < s){
            showAlert("วันที่สิ้นสุดห้ามน้อยกว่าวันที่เริ่ม");
            return false;
        }

        let diff = Math.ceil((e - s)/(1000*60*60*24)) + 1;

        if(diff > 30){
            showAlert("จองได้ไม่เกิน 30 วัน");
            return false;
        }

        // ===== FORMAT =====
        let destText = dest.map((d,i)=> `${i+1}. ${d}`).join('<br>');
        let compText = comp.map((c,i)=> `${i+1}. ${c}`).join('<br>');

        let dt = start + " ถึง " + end;

        $('#cf_destination').html(destText || '-');
        $('#cf_companions').html(compText || '-');
        $('#cf_datetime').html(dt + "<br>จำนวนวัน: " + diff + " วัน");

        let modal = new bootstrap.Modal(document.getElementById('confirmModal'));
        modal.show();

        return false;

    } catch(err){
        console.error(err);
        alert("เกิด error: " + err.message);
        return false;
    }
}

function showAlert(msg){
    $('#alertText').text(msg);
    new bootstrap.Modal(document.getElementById('alertModal')).show();
}

// ===== submit จริง =====
$('#confirmSubmitBtn').click(function(){

    // ปิด modal
    let modalEl = document.getElementById('confirmModal');
    let modal = bootstrap.Modal.getInstance(modalEl);
    if(modal) modal.hide();

    // ล้าง backdrop
    $('.modal-backdrop').remove();
    $('body').removeClass('modal-open');
    $('body').css('padding-right','');

    // 🔥 สำคัญ: ปิด onsubmit ก่อน
    let form = document.querySelector('form');
    form.onsubmit = null;

    // submit จริง
    setTimeout(()=>{
        form.submit();
    },200);

});

$(document).ready(function(){

    const urlParams = new URLSearchParams(window.location.search);

    if(urlParams.get('success') === '1'){

        let modal = new bootstrap.Modal(document.getElementById('successModal'));
        modal.show();

        // ดีเลย์แล้วไปหน้า preview
        setTimeout(()=>{
            window.location.href = "previewbook.php";
        },1500);
    }

});

</script>