<?php
session_start();
require '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "❌ Musisz być zalogowany!"]);
    exit();
}

$userId = $_SESSION['user_id'];

// Sprawdzenie, czy już trwa wyścig
$stmt = $conn->prepare("SELECT * FROM user_races WHERE user_id = :user_id AND completed = 0 AND end_time > NOW()");
$stmt->bindParam(":user_id", $userId);
$stmt->execute();
if ($stmt->rowCount() > 0) {
    echo json_encode(["status" => "error", "message" => "⏳ Trwa już wyścig! Poczekaj na zakończenie."]);
    exit();
}

if (!isset($_POST['race_id']) || !is_numeric($_POST['race_id'])) {
    echo json_encode(["status" => "error", "message" => "❌ Nie wybrano wyścigu!"]);
    exit();
}

$raceId = intval($_POST['race_id']);

// Pobranie danych wyścigu
$stmt = $conn->prepare("SELECT * FROM races WHERE id = :race_id");
$stmt->bindParam(":race_id", $raceId);
$stmt->execute();
$race = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$race) {
    echo json_encode(["status" => "error", "message" => "❌ Nie znaleziono wyścigu!"]);
    exit();
}

// Pobranie aktywnego auta i paliwa
$stmt = $conn->prepare("SELECT active_car_id FROM users WHERE id = :user_id");
$stmt->bindParam(":user_id", $userId);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !$user['active_car_id']) {
    echo json_encode(["status" => "error", "message" => "❌ Nie masz wybranego auta!"]);
    exit();
}

$activeCarId = $user['active_car_id'];

$stmt = $conn->prepare("SELECT fuel_level FROM user_cars WHERE id = :car_id AND user_id = :user_id");
$stmt->bindParam(":car_id", $activeCarId);
$stmt->bindParam(":user_id", $userId);
$stmt->execute();
$car = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$car) {
    echo json_encode(["status" => "error", "message" => "❌ Brak informacji o paliwie auta!"]);
    exit();
}

if ($car['fuel_level'] < $race['fuel_cost']) {
    echo json_encode(["status" => "error", "message" => "❌ Masz za mało paliwa na ten wyścig!"]);
    exit();
}

// Odejmij paliwo
$newFuel = $car['fuel_level'] - $race['fuel_cost'];
$stmt = $conn->prepare("UPDATE user_cars SET fuel_level = :fuel WHERE id = :car_id");
$stmt->bindParam(":fuel", $newFuel);
$stmt->bindParam(":car_id", $activeCarId);
$stmt->execute();

// Oblicz czas zakończenia
$startTime = date("Y-m-d H:i:s");
$endTime = date("Y-m-d H:i:s", strtotime("+{$race['duration']} seconds"));

// Zapisz wyścig
$stmt = $conn->prepare("INSERT INTO user_races (user_id, race_id, start_time, end_time) VALUES (:user_id, :race_id, :start_time, :end_time)");
$stmt->bindParam(":user_id", $userId);
$stmt->bindParam(":race_id", $raceId);
$stmt->bindParam(":start_time", $startTime);
$stmt->bindParam(":end_time", $endTime);
$stmt->execute();

echo json_encode([
    "status" => "success",
    "message" => "🏁 Wyścig rozpoczęty! Trwa {$race['duration']} sekund. Pozostałe paliwo: {$newFuel} L",
    "new_fuel" => $newFuel
]);
exit();
