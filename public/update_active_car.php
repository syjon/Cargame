<?php
session_start();
require '../config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Nie jesteś zalogowany!"]);
    exit();
}

if (!isset($_POST['car_id'])) {
    echo json_encode(["status" => "error", "message" => "Nie wybrano pojazdu!"]);
    exit();
}

$car_id = intval($_POST['car_id']);
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("UPDATE users SET active_car_id = :car_id WHERE id = :user_id");
$stmt->bindParam(':car_id', $car_id);
$stmt->bindParam(':user_id', $user_id);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Auto ustawione!", "car_id" => $car_id]);
} else {
    echo json_encode(["status" => "error", "message" => "Błąd zapisu do bazy!"]);
}
exit();
