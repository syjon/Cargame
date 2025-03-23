<?php
session_start();
require '../config.php'; // Sprawdź czy ścieżka jest poprawna

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Nie jesteś zalogowany!"]);
    exit();
}

if (!isset($_POST['amount']) || !is_numeric($_POST['amount']) || $_POST['amount'] < 1) {
    echo json_encode(["status" => "error", "message" => "Nieprawidłowa ilość paliwa!"]);
    exit();
}

$amount = intval($_POST['amount']);
$user_id = $_SESSION['user_id'];
$fuel_price_per_liter = 6; // Koszt za litr
$total_cost = $amount * $fuel_price_per_liter;

$stmt = $conn->prepare("
    SELECT uc.id AS user_car_id, uc.fuel_level, c.fuel_capacity, u.money 
    FROM user_cars uc 
    JOIN cars c ON uc.car_id = c.id 
    JOIN users u ON uc.user_id = u.id
    WHERE u.active_car_id = uc.id AND uc.user_id = :user_id
    LIMIT 1
");
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$car = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$car) {
    echo json_encode(["status" => "error", "message" => "Musisz wybrać samochód przed tankowaniem!"]);
    exit();
}

// Sprawdzenie, czy paliwo nie przekracza pojemności baku
if ($car['fuel_level'] + $amount > $car['fuel_capacity']) {
    echo json_encode(["status" => "error", "message" => "Nie możesz zatankować więcej niż pojemność baku!"]);
    exit();
}

// Sprawdzenie, czy gracz ma wystarczającą ilość pieniędzy
if ($car['money'] < $total_cost) {
    echo json_encode(["status" => "error", "message" => "Nie masz wystarczającej ilości pieniędzy!"]);
    exit();
}

// Aktualizacja paliwa w aucie
$new_fuel = $car['fuel_level'] + $amount;
$stmt = $conn->prepare("UPDATE user_cars SET fuel_level = :fuel WHERE id = :user_car_id");
$stmt->bindParam(':fuel', $new_fuel);
$stmt->bindParam(':user_car_id', $car['user_car_id']);
$stmt->execute();

// Aktualizacja pieniędzy gracza
$new_balance = $car['money'] - $total_cost;
$stmt = $conn->prepare("UPDATE users SET money = :money WHERE id = :user_id");
$stmt->bindParam(':money', $new_balance);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();

echo json_encode([
    "status" => "success",
    "message" => "Tankowanie zakończone!",
    "new_fuel" => $new_fuel,
    "new_balance" => number_format($new_balance, 2, ',', ' ')
]);
exit();
