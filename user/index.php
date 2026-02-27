<?php
session_start();
require_once '../config/connect.php';

// ================= AJAX SEARCH =================
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
        ORDER BY fullname
    ");

    $stmt->execute(['q' => "%$q%"]);

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

include 'partials/header.php';
include 'partials/sidebar.php';

// ===== ดึงข้อมูลผู้ login =====
$stmt = $conn->prepare("
    SELECT fullname, position, department
    FROM Employee
    WHERE EmployeeID = :id
");
$stmt->execute(['id' => $_SESSION['EmployeeID']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<div class="content">
    <h2 class="text-center">ฟอร์มจองรถ</h2>

    <form action="submit_booking.php" method="post" enctype="multipart/form-data"
          class="p-4 border rounded bg-light" onsubmit="return confirmSubmit()">

        <!-- ===================== แถวที่ 1: ชื่อ + ตำแหน่ง + แผนก ===================== -->
        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label">ชื่อ-นามสกุล</label>
                <input type="text" name="full_name" class="form-control"
                       value="<?= htmlspecialchars($user['fullname']) ?>" readonly>
            </div>

            <div class="col-md-4">
                <label class="form-label">ตำแหน่ง</label>
                <input type="text" name="position" class="form-control"
                       value="<?= htmlspecialchars($user['position']) ?>" readonly>
            </div>

            <div class="col-md-4">
                <label class="form-label">แผนก</label>
                <input type="text" name="department" class="form-control"
                       value="<?= htmlspecialchars($user['department']) ?>" readonly>
            </div>
        </div>

        <!-- ===================== แถวที่ 2: ผู้ร่วมเดินทาง (Realtime Search) ===================== -->
        <div class="col-md-12">
            <label class="form-label">ผู้ร่วมเดินทาง (ค้นหาพนักงาน)</label>

            <div id="companions-group">   <!-- 👈 เพิ่มบรรทัดนี้ -->
                <div class="input-group mb-2 position-relative">
                    <input type="text" class="form-control companion-search" placeholder="พิมพ์ชื่อ...">
                    <input type="hidden" name="companions[]" class="companion-value">
                    <button type="button" class="btn btn-outline-primary add-companion">+</button>

                    <div class="list-group companion-result position-absolute w-100"
                        style="z-index:1000; top:100%;"></div>
                </div>
            </div>   <!-- 👈 ปิด wrapper -->
        </div>

        <!-- ===================== สถานที่ ===================== -->
        <div class="row mb-3">
            <div class="col-md-12">
                <label class="form-label">สถานที่ที่จะเดินทางไป</label>
                <div id="destination-group">
                    <div class="input-group mb-2">
                        <input type="text" name="destination[]" class="form-control" placeholder="ระบุสถานที่เดินทาง" required>
                        <button type="button" class="btn btn-outline-primary add-destination">+</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===================== วันที่/เวลา ===================== -->
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">วันที่เริ่มเดินทาง</label>
                <input type="text" id="start_date" name="start_date" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">เวลาที่เริ่มเดินทาง</label>
                <input type="text" id="start_time" name="start_time" class="form-control" required>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">สิ้นสุดวันที่</label>
                <input type="text" id="end_date" name="end_date" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">ถึงเวลา</label>
                <input type="text" id="end_time" name="end_time" class="form-control" required>
            </div>
        </div>

        <!-- จำนวนวัน -->
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">จำนวนวันที่จอง</label>
                <input type="text" id="total_days" class="form-control" readonly>
            </div>
        </div>

        <!-- อัปโหลดไฟล์ -->
        <div class="row mb-3">
            <div class="col-md-12">
                <label class="form-label">แนบเอกสาร (ถ้ามี)</label>
                <input type="file" name="booking_file" class="form-control"
                       accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                <small class="text-muted">รองรับไฟล์ PDF, รูปภาพ, Word (ไม่บังคับ)</small>
            </div>
        </div>

        <!-- รายละเอียด -->
        <div class="row mb-3">
            <div class="col-md-12">
                <label class="form-label">รายละเอียดเพิ่มเติม</label>
                <textarea name="additional_details" class="form-control" rows="4" required></textarea>
            </div>
        </div>

        <button type="submit" class="btn btn-success w-100">บันทึกข้อมูล</button>
    </form>
</div>

<!-- ===================== JS ===================== -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
function confirmSubmit(){ return confirm("คุณต้องการบันทึกข้อมูลใช่หรือไม่?"); }

flatpickr("#start_date",{locale:"th",dateFormat:"Y-m-d"});
flatpickr("#end_date",{locale:"th",dateFormat:"Y-m-d"});
flatpickr("#start_time",{enableTime:true,noCalendar:true,dateFormat:"H:i",time_24hr:true});
flatpickr("#end_time",{enableTime:true,noCalendar:true,dateFormat:"H:i",time_24hr:true});

function calculateTotalDays(){
    const sd=document.getElementById('start_date').value;
    const ed=document.getElementById('end_date').value;
    if(sd && ed){
        const start=new Date(sd);
        const end=new Date(ed);
        const diff=Math.ceil((end-start)/(1000*60*60*24))+1;
        document.getElementById('total_days').value=diff+' วัน';
    }
}
document.getElementById('start_date').addEventListener('change',calculateTotalDays);
document.getElementById('end_date').addEventListener('change',calculateTotalDays);

// ===== REALTIME SEARCH =====
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

                    html += `
                        <a href="#" class="list-group-item list-group-item-action"
                           data-value="${label}">
                           ${label}
                        </a>
                    `;
                });
            }

            resultBox.html(html);
        }
    });

}); // ✅ ปิด function สำคัญมาก

// ===== CLICK SELECT =====
$(document).on('click', '.companion-result a', function (e) {
    e.preventDefault();

    let val = $(this).data('value');
    let group = $(this).closest('.input-group');

    group.find('.companion-search').val(val);
    group.find('.companion-value').val(val);
    group.find('.companion-result').empty();
});

// เพิ่ม/ลบช่องผู้ร่วมเดินทาง
$(document).on('click','.add-companion',function(){
    $('#companions-group').append(`
        <div class="input-group mb-2 position-relative">
            <input type="text" class="form-control companion-search" placeholder="พิมพ์ชื่อเพื่อค้นหา...">
            <input type="hidden" name="companions[]" class="companion-value">
            <button type="button" class="btn btn-outline-danger remove-companion">–</button>
            <div class="list-group companion-result position-absolute w-100" style="z-index:1000; top:100%;"></div>
        </div>
    `);
});
$(document).on('click','.remove-companion',function(){
    $(this).closest('.input-group').remove();
});

// add/remove destination
$(document).on('click','.add-destination',function(){
    $('#destination-group').append(`
        <div class="input-group mb-2">
            <input type="text" name="destination[]" class="form-control" placeholder="ระบุสถานที่เดินทาง" required>
            <button type="button" class="btn btn-outline-danger remove-destination">–</button>
        </div>
    `);
});
$(document).on('click','.remove-destination',function(){
    $(this).closest('.input-group').remove();
});
</script>

<?php include 'partials/footer.php'; ?>