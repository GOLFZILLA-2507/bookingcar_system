<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        
        <a class="navbar-brand" href="index.php">ระบบจองรถออนไลน์</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminSidebar">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="adminSidebar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="index.php">🏠 หน้าหลัก</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_booking.php">📋 จัดการการจอง</a></li>
                <li class="nav-item"><a class="nav-link" href="admin_add_cars.php">🚗 เพิ่มรถ</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_cars.php"><i class="bi bi-car-front-fill"></i> ⌨ จัดการสถานะรถ</a></li>
                <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="viewDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    📋 มุมมอง
                </a>
                <ul class="dropdown-menu" aria-labelledby="viewDropdown">
                    <li><a class="dropdown-item" href="bookings_view.php">มุมมองแบบตาราง</a></li>
                    <li><a class="dropdown-item" href="calendar.php">มุมมองปฏิทิน</a></li>
                </ul>
                </li>

                
            </ul>
            <span class="navbar-text">
            <?= htmlspecialchars($_SESSION['fullname']) ?> | <a href="../index.php" class="text-white text-decoration-underline">ออกจากระบบ</a>
            </span>
        </div>
    </div>
</nav>

<div class="container mt-4">
