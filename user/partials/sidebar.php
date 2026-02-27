

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">🚗 ระบบจองรถออนไลน์</a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminSidebar">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="adminSidebar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">

                <li class="nav-item">
                    <a class="nav-link" href="index.php">🏠 หน้าหลัก</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="previewbook.php">🚗 การจองรถของฉัน</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="preview_approved.php">📋 สถานะรถโดยรวม</a>
                </li>

                <li class="nav-item">
    <a class="nav-link" href="approval_dashboard.php">📝 อนุมัติรายการ</a>
</li>

            </ul>

            <span class="navbar-text">
                <?= htmlspecialchars($_SESSION['fullname']) ?>  
                | <a href="../index.php" class="text-white text-decoration-underline">ออกจากระบบ</a>
            </span>

        </div>
    </div>
</nav>

<div class="container mt-4">