<?php
session_start();
require_once '../config/connect.php';
include 'partials/header.php';
include 'partials/sidebar.php';
?>
<!-- เนื้อหาหลัก -->

<!-- Flatpickr CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<div class="content">
    <h2 class="text-center">ฟอร์มจองรถ</h2>

    <form action="submit_booking.php" method="post" class="p-4 border rounded bg-light" onsubmit="return confirmSubmit()">

        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label">ชื่อ-นามสกุล</label>
                <input type="text" name="full_name" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">ตำแหน่ง</label>
                <select name="position" class="form-select" required>
                    <option value="">-- เลือกตำแหน่ง --</option>
                    <option value="ผู้จัดการ">ผู้จัดการ</option>
                    <option value="พนักงาน">พนักงาน</option>
                    <option value="เจ้าหน้าที่">เจ้าหน้าที่</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">แผนก</label>
                <select name="department" class="form-select" required>
                    <option value="">-- เลือกแผนก --</option>
                    <option value="การตลาด">การตลาด</option>
                    <option value="บัญชี">บัญชี</option>
                    <option value="ไอที">ไอที</option>
                </select>
            </div>
        </div>

        <!-- แถวที่ 2: ผู้ร่วมเดินทาง, สถานที่ที่จะเดินทางไป -->
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">สถานที่ที่จะเดินทางไป</label>
                <div id="destination-group">
                    <div class="input-group mb-2">
                        <input type="text" name="destination[]" class="form-control" placeholder="ระบุสถานที่เดินทาง" required>
                        <button type="button" class="btn btn-outline-primary add-destination">+</button>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">ผู้ร่วมเดินทาง</label>
                <div id="companions-group">
                    <div class="input-group mb-2">
                        <input type="text" name="companions[]" class="form-control" placeholder="ชื่อผู้ร่วมเดินทาง">
                        <button type="button" class="btn btn-outline-primary add-companion">+</button>
                    </div>
                </div>
            </div>
        </div>

           <!-- แถววันที่และเวลา -->
           <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">วันที่เริ่มเดินทาง</label>
                <input type="text" id="start_date" name="start_date" class="form-control" placeholder="เลือกวันที่" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">เวลาที่เริ่มเดินทาง</label>
                <input type="text" id="start_time" name="start_time" class="form-control" placeholder="เลือกเวลา" required>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">สิ้นสุดวันที่</label>
                <input type="text" id="end_date" name="end_date" class="form-control" placeholder="เลือกวันที่" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">ถึงเวลา</label>
                <input type="text" id="end_time" name="end_time" class="form-control" placeholder="เลือกเวลา" required>
            </div>
        </div>

        <!-- จำนวนวันจอง -->
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">จำนวนวันที่จอง</label>
                <input type="text" id="total_days" class="form-control" readonly>
            </div>
        </div>

        <!-- รายละเอียดเพิ่มเติม -->
        <div class="row mb-3">
            <div class="col-md-12">
                <label class="form-label">รายละเอียดเพิ่มเติม</label>
                <textarea name="additional_details" class="form-control" rows="4" required></textarea>
            </div>
        </div>

        <button type="submit" class="btn btn-success w-100">บันทึกข้อมูล</button>
    </form>
</div>

<!-- Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    // ยืนยันก่อนบันทึก
    function confirmSubmit() {
        return confirm("คุณต้องการบันทึกข้อมูลใช่หรือไม่?");
    }

    // Flatpickr: ตั้งวันที่
    flatpickr("#start_date", {
        locale: "th",         // ถ้ามีไฟล์ locale ของไทย
        dateFormat: "Y-m-d",
    });
    flatpickr("#end_date", {
        locale: "th",
        dateFormat: "Y-m-d",
    });

    // Flatpickr: ตั้งเวลาแบบ 24 ชั่วโมง
    flatpickr("#start_time", {
        enableTime: true,
        noCalendar: true,
        dateFormat: "H:i",
        time_24hr: true
    });
    flatpickr("#end_time", {
        enableTime: true,
        noCalendar: true,
        dateFormat: "H:i",
        time_24hr: true
    });

    // คำนวณจำนวนวัน (ใช้ Date.parse)
    function calculateTotalDays() {
        const sd = document.getElementById('start_date').value;
        const ed = document.getElementById('end_date').value;
        if (sd && ed) {
            const start = new Date(sd);
            const end = new Date(ed);
            const diff = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;
            document.getElementById('total_days').value = diff + ' วัน';
        }
    }
    document.getElementById('start_date').addEventListener('change', calculateTotalDays);
    document.getElementById('end_date').addEventListener('change', calculateTotalDays);

    // ฟังก์ชันเพิ่มช่องพักอื่นๆ, เหมือนเดิม...
    $(document).on('click', '.add-companion', function () {
        $('#companions-group').append(`
            <div class="input-group mb-2">
                <input type="text" name="companions[]" class="form-control" placeholder="ชื่อผู้ร่วมเดินทาง">
                <button type="button" class="btn btn-outline-danger remove-companion">–</button>
            </div>
        `);
    });
    $(document).on('click', '.remove-companion', function () {
        $(this).closest('.input-group').remove();
    });
    $(document).on('click', '.add-destination', function () {
        $('#destination-group').append(`
            <div class="input-group mb-2">
                <input type="text" name="destination[]" class="form-control" placeholder="ระบุสถานที่เดินทาง" required>
                <button type="button" class="btn btn-outline-danger remove-destination">–</button>
            </div>
        `);
    });
    $(document).on('click', '.remove-destination', function () {
        $(this).closest('.input-group').remove();
    });
</script>

<?php include 'partials/footer.php'; ?>