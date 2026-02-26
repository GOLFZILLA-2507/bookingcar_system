<?php
session_start();
require_once '../config/connect.php';

if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM cars WHERE id = ?");
    $stmt->execute([$id]);
}

header("Location: admin_add_cars.php");
exit();
?>
