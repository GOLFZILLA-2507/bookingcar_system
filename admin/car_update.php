<?php
session_start();
require_once '../config/connect.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = $_POST['car_id'];
    $car_name = $_POST['car_name'];
    $license_plate = $_POST['license_plate'];
    $car_type = $_POST['car_type'];

    $stmt = $conn->prepare("UPDATE cars SET car_name = ?, license_plate = ?, car_type = ? WHERE id = ?");
    $stmt->execute([$car_name, $license_plate, $car_type, $id]);

    header("Location: admin_add_cars.php?update_success=1");
    exit();
}
?>
