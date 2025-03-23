<?php
session_start();
require '../config.php';

if (!isset($_SESSION['user_id'])) {
    echo "<p>Musisz być zalogowany, aby zobaczyć swój garaż! 🚗</p>";
    exit();
}

// Pobieranie aktywnego auta użytkownika
$stmt = $conn->prepare("SELECT active_car_id FROM users WHERE id = :user_id");
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$activeCar = $stmt->fetch(PDO::FETCH_ASSOC);
$activeCarId = $activeCar['active_car_id'] ?? null;

// Pobranie samochodów użytkownika
$stmt = $conn->prepare("
    SELECT uc.id AS user_car_id, c.car_name, c.power, c.acceleration, c.max_speed, c.weight, 
           uc.fuel_level, c.fuel_capacity
    FROM user_cars uc
    JOIN cars c ON uc.car_id = c.id
    WHERE uc.user_id = :user_id
");
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$cars = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Twój Garaż</title>
    <style>
        .garage-container {
            text-align: center;
            margin: auto;
            width: 60%;
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.2);
        }

        .garage-title {
            font-size: 24px;
            font-weight: bold;
            background-color: #346beb;
            color: white;
            padding: 10px;
            border-radius: 5px;
            display: inline-block;
        }

        .car-box {
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .car-name {
            font-size: 20px;
            font-weight: bold;
            color: #222;
        }

        .car-attributes {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
        }

        .attribute {
            background: #c5d1ff;
            padding: 8px;
            border-radius: 5px;
            font-weight: bold;
        }

        .fuel-container {
            display: flex;
            align-items: center;
            justify-content: center;
            background: #c5d1ff;
            padding: 10px;
            border-radius: 5px;
            font-weight: bold;
            margin: auto;
            width: 250px;
            position: relative;
        }

        .fuel-bar {
            width: 100%;
            height: 15px;
            background-color: #ddd;
            border-radius: 5px;
            overflow: hidden;
            position: absolute;
            left: 0;
        }

        .fuel-fill {
            height: 100%;
            background-color: #4CAF50;
            transition: width 0.5s ease-in-out;
        }

        .fuel-text {
            position: relative;
            z-index: 2;
            font-weight: bold;
            color: black;
        }

        .select-car-btn {
            background-color: orange;
            color: white;
            padding: 10px;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            font-weight: bold;
            transition: 0.3s;
        }

        .select-car-btn.active {
            background-color: green;
        }
    </style>
</head>
<body>

<div class="garage-container">
    <h2 class="garage-title">🚙 Twój garaż</h2>

    <?php if (count($cars) > 0): ?>
        <?php foreach ($cars as $car): ?>
            <div class="car-box">
                <p class="car-name"><b><?php echo htmlspecialchars($car['car_name']); ?></b></p>
                <div class="car-attributes">
                    <span class="attribute">⚙️ Moc: <?php echo $car['power']; ?> KM</span>
                    <span class="attribute">⏱️ 0-100: <?php echo $car['acceleration']; ?> s</span>
                    <span class="attribute">🏁 V-MAX: <?php echo $car['max_speed']; ?> km/h</span>
                    <span class="attribute">⚖️ Waga: <?php echo $car['weight']; ?> kg</span>
                </div>

                <div class="fuel-container">
                    <div class="fuel-bar">
                        <div class="fuel-fill" style="width: <?php echo ($car['fuel_level'] / $car['fuel_capacity']) * 100; ?>%;"></div>
                    </div>
                    <span class="fuel-text">⛽ <?php echo $car['fuel_level']; ?>L / <?php echo $car['fuel_capacity']; ?>L</span>
                </div>

                <button id="car-btn-<?php echo $car['user_car_id']; ?>" 
                        class="select-car-btn <?php echo ($car['user_car_id'] == $activeCarId) ? 'active' : ''; ?>" 
                        onclick="selectCar(<?php echo $car['user_car_id']; ?>)">
                    🚗 Wybierz
                </button>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Nie posiadasz jeszcze żadnych pojazdów! 🏎️</p>
    <?php endif; ?>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const buttons = document.querySelectorAll(".select-car-btn");

    function updateActiveButton(carId) {
        buttons.forEach(btn => btn.classList.remove("active"));
        const activeButton = document.getElementById("car-btn-" + carId);
        if (activeButton) {
            activeButton.classList.add("active");
        }
    }

    buttons.forEach(button => {
        button.addEventListener("click", function() {
            const carId = this.getAttribute("onclick").match(/\d+/)[0];

            // ✅ Natychmiastowa zmiana koloru po kliknięciu
            updateActiveButton(carId);

            fetch("set_active_car.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "car_id=" + carId
            })
            .then(response => response.text())
            .then(data => {
                if (data.includes("success")) {
                    updateActiveButton(carId); // ✅ Od razu zmienia kolor
                } else {
                    alert("Błąd: " + data);
                }
            })
            .catch(error => console.error("Błąd:", error));
        });
    });
});
</script>

</body>
</html>
