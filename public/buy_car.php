<?php
session_start();
require '../config.php';

// Sprawdź, czy użytkownik jest zalogowany
if (!isset($_SESSION['user_id'])) {
    echo "❌ Musisz być zalogowany!";
    exit();
}

// Sprawdź, ile samochodów ma użytkownik
$stmt = $conn->prepare("SELECT COUNT(*) FROM user_cars WHERE user_id = :user_id");
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$carCount = $stmt->fetchColumn();

if ($carCount < 2) {
    // Znajdź pierwszy wolny slot (1 lub 2)
    $slot = ($carCount == 0) ? 1 : 2;

    // Pobierz ID auta z formularza (np. użytkownik wybiera auto z salonu)
    $car_id = $_POST['car_id'] ?? null;
    if (!$car_id) {
        echo "❌ Nie wybrano auta!";
        exit();
    }

    // Dodaj samochód do garażu w wolnym slocie
    $stmt = $conn->prepare("INSERT INTO user_cars (user_id, car_id, slot) VALUES (:user_id, :car_id, :slot)");
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->bindParam(':car_id', $car_id);
    $stmt->bindParam(':slot', $slot);
    $stmt->execute();

    echo "✅ Auto dodane do garażu w slocie $slot!";
} else {
    echo "❌ Masz już dwa auta w garażu!";
}
$stmt = $conn->prepare("INSERT INTO user_cars (user_id, car_id, fuel_level) 
                        VALUES (:user_id, :car_id, 10)");
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->bindParam(':car_id', $carId);
$stmt->execute();

?>
