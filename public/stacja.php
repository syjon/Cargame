<?php
session_start();
require '../config.php'; // Połączenie z bazą

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Musisz być zalogowany, aby zatankować paliwo! ⛽"]);
    exit();
}

$user_id = $_SESSION['user_id'];

// 📌 Pobranie aktywnego auta użytkownika
$stmt = $conn->prepare("SELECT active_car_id FROM users WHERE id = :user_id");
$stmt->bindParam(":user_id", $user_id);
$stmt->execute();
$activeCar = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$activeCar || !$activeCar['active_car_id']) {
    echo json_encode(["status" => "error", "message" => "Nie wybrałeś samochodu przed tankowaniem! ⛽"]);
    exit();
}

$car_id = $activeCar['active_car_id'];

// 📌 Pobranie informacji o baku i pieniądzach
$stmt = $conn->prepare("
    SELECT uc.id AS user_car_id, uc.fuel_level, c.fuel_capacity, u.money 
    FROM user_cars uc 
    JOIN cars c ON uc.car_id = c.id 
    JOIN users u ON uc.user_id = u.id
    WHERE uc.id = :car_id AND uc.user_id = :user_id
");
$stmt->bindParam(':car_id', $car_id);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$car = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$car) {
    echo json_encode(["status" => "error", "message" => "Błąd! Nie znaleziono auta."]);
    exit();
}

// 📌 Obsługa tankowania (jeśli POST)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['amount'])) {
    $amount = intval($_POST['amount']);

    // Sprawdzenie czy auto może pomieścić więcej paliwa
    if ($car['fuel_level'] + $amount > $car['fuel_capacity']) {
        echo json_encode(["status" => "error", "message" => "Bak jest pełny!"]);
        exit();
    }

    // Sprawdzenie czy użytkownik ma wystarczającą ilość pieniędzy
    $fuel_price_per_liter = 6;
    $total_cost = $amount * $fuel_price_per_liter;

    if ($car['money'] < $total_cost) {
        echo json_encode(["status" => "error", "message" => "Za mało pieniędzy na tankowanie!"]);
        exit();
    }

    // 📌 Aktualizacja poziomu paliwa i salda użytkownika
    $new_fuel = $car['fuel_level'] + $amount;
    $new_balance = $car['money'] - $total_cost;

    $conn->beginTransaction();
    
    $stmt = $conn->prepare("UPDATE user_cars SET fuel_level = :fuel WHERE id = :car_id");
    $stmt->bindParam(':fuel', $new_fuel);
    $stmt->bindParam(':car_id', $car['user_car_id']);
    $stmt->execute();

    $stmt = $conn->prepare("UPDATE users SET money = :money WHERE id = :user_id");
    $stmt->bindParam(':money', $new_balance);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();

    $conn->commit();

    echo json_encode([
        "status" => "success",
        "message" => "✅ Tankowanie zakończone!",
        "new_fuel" => $new_fuel,
        "fuel_capacity" => $car['fuel_capacity'],
        "new_balance" => number_format($new_balance, 2, ',', ' ')
    ]);
    exit();
}

?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>⛽ Stacja Paliw</title>
    <style>
        .station-container {
            text-align: center;
            margin: auto;
            width: 50%;
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.2);
        }
        
        .fuel-status {
            font-size: 18px;
            font-weight: bold;
            background: rgba(255, 215, 0, 0.3);
            padding: 10px;
            border-radius: 5px;
            display: inline-block;
            margin-bottom: 10px;
        }

        .fuel-bar-container {
            width: 100%;
            height: 20px;
            background-color: #ddd;
            border-radius: 5px;
            overflow: hidden;
            border: 1px solid #aaa;
            margin-bottom: 10px;
        }

        .fuel-bar {
            height: 100%;
            background-color: #4CAF50;
            text-align: center;
            line-height: 20px;
            color: white;
            font-weight: bold;
            width: <?php echo floor(($car['fuel_level'] / $car['fuel_capacity']) * 100); ?>%;
            transition: width 0.5s ease-in-out;
        }

        .btn {
            padding: 10px 20px;
            font-size: 16px;
            font-weight: bold;
            color: white;
            background-color: #28a745;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .btn:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>

<div class="station-container">
    <h2>⛽ Stacja Paliw</h2>
    <p class="fuel-status">🛢 Aktualny poziom paliwa: <span id="fuelStatus"><?php echo $car['fuel_level']; ?>/<?php echo $car['fuel_capacity']; ?>L</span></p>
    
    <div class="fuel-bar-container">
        <div class="fuel-bar"></div>
    </div>
	
	<p>💰 Aktualna cena wahy 6zł/L</span></b></p>
    <p>💰 Twój szmal: <b><span id="userMoney"><?php echo number_format($car['money'], 2, ',', ' '); ?> zł</span></b></p>
    
    
    
    <button id="refuelButton" class="btn">⛽ Zatankuj</button>
</div>

<script src="script.js"></script>
</body>
</html>


</body>
</html>
