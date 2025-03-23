<?php
session_start();
require '../config.php';

if (!isset($_SESSION['login']) || !isset($_GET['part'])) {
    echo "❌ Błąd zakupu!";
    exit();
}

// Pobieramy dane pojazdu gracza
$stmt = $conn->prepare("SELECT * FROM user_cars WHERE user = :login");
$stmt->bindParam(':login', $_SESSION['login']);
$stmt->execute();
$userCar = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$userCar) {
    echo "❌ Nie masz pojazdu!";
    exit();
}

// Pobieramy część
$stmt = $conn->prepare("SELECT * FROM tuning_parts WHERE id = :part_id");
$stmt->bindParam(':part_id', $_GET['part']);
$stmt->execute();
$part = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$part) {
    echo "❌ Nie znaleziono części!";
    exit();
}

// Sprawdzenie, czy część pasuje do klasy pojazdu
if ($part['class_id'] != $userCar['class_id']) {
    echo "❌ Ta część nie pasuje do Twojego pojazdu!";
    exit();
}

// Pobranie środków użytkownika
$stmt = $conn->prepare("SELECT money FROM users WHERE login = :login");
$stmt->bindParam(':login', $_SESSION['login']);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user['money'] < $part['price']) {
    echo "❌ Masz za mało pieniędzy!";
    exit();
}

// Odejmuje pieniądze i aktualizuje statystyki pojazdu
$conn->beginTransaction();
$stmt = $conn->prepare("UPDATE users SET money = money - :price WHERE login = :login");
$stmt->execute(['price' => $part['price'], 'login' => $_SESSION['login']]);

$stmt = $conn->prepare("UPDATE user_cars SET power = power + :power, speed = speed + :speed, acceleration = acceleration + :acceleration, max_speed = max_speed + :max_speed, weight = weight + :weight WHERE user = :login");
$stmt->execute([
    'power' => $part['power'],
    'speed' => $part['speed'],
    'acceleration' => $part['acceleration'],
    'max_speed' => $part['max_speed'],
    'weight' => $part['weight'],
    'login' => $_SESSION['login']
]);

$conn->commit();

echo "✅ Zakupiono: {$part['name']}!";
?>
