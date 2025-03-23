<?php
session_start();
require '../config.php';

if (!isset($_SESSION['user_id'])) {
    echo "❌ Musisz być zalogowany!";
    exit();
}

if (!isset($_POST['car_id'])) {
    echo "❌ Brak ID pojazdu!";
    exit();
}

$carId = intval($_POST['car_id']);

// Sprawdzenie, czy użytkownik ma ten samochód
$stmt = $conn->prepare("SELECT car_id FROM user_cars WHERE user_id = :user_id AND car_id = :car_id");
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->bindParam(':car_id', $carId);
$stmt->execute();
$car = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$car) {
    echo "❌ Ten pojazd nie należy do Ciebie!";
    exit();
}

// Aktualizujemy aktywne auto w bazie danych
$stmt = $conn->prepare("UPDATE users SET active_car_id = :car_id WHERE id = :user_id");
$stmt->bindParam(':car_id', $carId);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();

echo "✅ Pojazd wybrany!";
