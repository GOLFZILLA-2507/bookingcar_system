<?php
session_start();
require_once '../config/connect.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $car_name = $_POST['car_name'];
    $license_plate = $_POST['license_plate'];
    $car_type = $_POST['car_type'];

    $stmt = $conn->prepare("INSERT INTO cars (car_name, license_plate, car_type) VALUES (?, ?, ?)");
    $stmt->execute([$car_name, $license_plate, $car_type]);

    $_SESSION['success'] = true;
    header("Location: admin_add_cars.php");
    exit();
}
?>
