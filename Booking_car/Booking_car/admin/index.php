<?php
require_once '../config/connect.php';
require_once '../config/checklogin.php';
include 'partials/header.php';
include 'partials/sidebar.php';
?>

<h1 class="mb-4">แดชบอร์ดผู้ดูแลระบบ</h1>

<div class="row">
    <!-- จองทั้งหมด -->
    <div class="col-md-3 mb-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5 class="card-title">จองทั้งหมด</h5>
                <p class="card-text fs-4">
                    <?php
                    $stmt = $conn->query("SELECT COUNT(*) FROM travel_bookings");
                    echo $stmt->fetchColumn();
                    ?>
                </p>
            </div>
        </div>
    </div>

    <!-- รออนุมัติ -->
    <div class="col-md-3 mb-3">
        <div class="card bg-warning text-dark">
            <div class="card-body">
                <h5 class="card-title">รออนุมัติ</h5>
                <p class="card-text fs-4">
                    <?php
                    $stmt = $conn->query("SELECT COUNT(*) FROM travel_bookings WHERE booking_status = 'รออนุมัติ'");
                    echo $stmt->fetchColumn();
                    ?>
                </p>
            </div>
        </div>
    </div>

    <!-- อนุมัติแล้ว -->
    <div class="col-md-3 mb-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5 class="card-title">อนุมัติแล้ว</h5>
                <p class="card-text fs-4">
                    <?php
                    $stmt = $conn->query("SELECT COUNT(*) FROM travel_bookings WHERE booking_status = 'อนุมัติแล้ว'");
                    echo $stmt->fetchColumn();
                    ?>
                </p>
            </div>
        </div>
    </div>

    <!-- ปฏิเสธแล้ว -->
    <div class="col-md-3 mb-3">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <h5 class="card-title">ปฏิเสธแล้ว</h5>
                <p class="card-text fs-4">
                    <?php
                    $stmt = $conn->query("SELECT COUNT(*) FROM travel_bookings WHERE booking_status = 'ปฏิเสธแล้ว'");
                    echo $stmt->fetchColumn();
                    ?>
                </p>
            </div>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>
