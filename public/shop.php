<?php
session_start();
require '../config.php';

if (!isset($_SESSION['user_id'])) {
    echo "<p>Musisz być zalogowany, aby robić zakupy! 🛒</p>";
    exit();
}

// Pobieramy klasę pojazdu gracza
$stmt = $conn->prepare("
    SELECT c.class_id 
    FROM user_cars uc 
    JOIN cars c ON uc.car_id = c.id 
    WHERE uc.user_id = :user_id 
    LIMIT 1
");
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$userCar = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$userCar) {
    echo "<p>Musisz mieć pojazd, aby kupować części! 🚗</p>";
    exit();
}

// Pobieramy części pasujące do klasy pojazdu
$stmt = $conn->prepare("
    SELECT * FROM tuning_parts 
    WHERE class_id = :class_id
");
$stmt->bindParam(':class_id', $userCar['class_id']);
$stmt->execute();
$partsForSale = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>🛠️ Sklep tuningowy</h2>";

if (count($partsForSale) > 0) {
    foreach ($partsForSale as $part) {
        echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 10px; border-radius: 5px;'>
            <p><b>{$part['name']}</b> - Cena: <b>{$part['price']} zł</b></p>
            <p>⚙️ Moc: <b>+{$part['power']}</b> | 🏁 V-MAX: <b>+{$part['max_speed']} km/h</b></p>
            <p>⏱️ 0-100: <b>-{$part['acceleration']} s</b> | ⚖️ Waga: <b>-{$part['weight']} kg</b></p>
            <button onclick='buyPart({$part['id']})'>Kup</button>
        </div>";
    }
} else {
    echo "<p>Brak dostępnych części do tuningu dla Twojego pojazdu! 🔧</p>";
}
?>

<script>
function buyPart(partId) {
    fetch('buy_part.php?part=' + partId)
        .then(response => response.text())
        .then(data => alert(data));
}
</script>
