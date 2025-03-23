<?php
session_start();
require '../config.php';

if (!isset($_SESSION['user_id'])) {
    die("❌ Musisz być zalogowany, aby zobaczyć wyścigi!");
}

$userId = $_SESSION['user_id'];

// Sprawdzenie czy gracz jest w trakcie wyścigu
$stmt = $conn->prepare("SELECT user_races.*, races.name, UNIX_TIMESTAMP(end_time) as end_timestamp 
                        FROM user_races 
                        JOIN races ON user_races.race_id = races.id
                        WHERE user_races.user_id = :user_id AND user_races.completed = 0
                        ORDER BY user_races.id DESC LIMIT 1");
$stmt->bindParam(":user_id", $userId);
$stmt->execute();
$activeRace = $stmt->fetch(PDO::FETCH_ASSOC);

// Obsługa AJAX
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) ob_start();

if ($activeRace) {
    $now = time();
    $end = $activeRace['end_timestamp'];
    $remaining = $end - $now;
    $isFinished = $remaining <= 0;

    echo "<h2>🏎️ Aktualny wyścig: <b>{$activeRace['name']}</b></h2>";
    echo "<div class='timer' id='race_timer' data-end='{$end}'>Ładowanie...</div>";

    if ($isFinished) {
        echo "<button id='finishRaceButton'>🎁 Odbierz nagrodę</button>";
    }

    echo "
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let el = document.getElementById('race_timer');
            let endTime = parseInt(el.getAttribute('data-end'), 10);
            console.log('End time:', endTime);

            function updateRaceTimer() {
                let now = Math.floor(Date.now() / 1000);
                let remaining = endTime - now;
                console.log('Remaining time:', remaining);

                if (remaining > 0) {
                    let min = Math.floor(remaining / 60);
                    let sec = remaining % 60;
                    el.innerText = '⏳ Pozostały czas: ' + min + 'm ' + sec + 's';
                    setTimeout(updateRaceTimer, 1000);
                } else {
                    el.innerText = '✅ Wyścig zakończony! Odbierz nagrodę.';
                    if (!document.getElementById('finishRaceButton')) {
                        const btn = document.createElement('button');
                        btn.id = 'finishRaceButton';
                        btn.textContent = '🎁 Odbierz nagrodę';
                        btn.addEventListener('click', finishRace);
                        el.parentElement.appendChild(btn);
                    }
                }
            }

            updateRaceTimer();
        });
    </script>
    ";
} else {
    // Jeśli nie ma aktywnego wyścigu — wyświetl wybór
    $stmt = $conn->prepare("SELECT active_car_id, experience FROM users WHERE id = :user_id");
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !$user['active_car_id']) {
        die("❌ Brak wybranego auta!");
    }

    $activeCarId = $user['active_car_id'];
    $experience = $user['experience'];

    $stmt = $conn->prepare("SELECT fuel_level FROM user_cars WHERE id = :car_id AND user_id = :user_id");
    $stmt->bindParam(':car_id', $activeCarId);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    $car = $stmt->fetch(PDO::FETCH_ASSOC);

    function calculateLevel($xp) {
        $level = 0;
        $xpNeeded = 100;
        while ($xp >= $xpNeeded) {
            $xp -= $xpNeeded;
            $xpNeeded = ceil($xpNeeded * 1.27);
            $level++;
        }
        return $level;
    }

    $currentLevel = calculateLevel($experience);

    $stmt = $conn->prepare("SELECT * FROM races ORDER BY required_xp ASC");
    $stmt->execute();
    $races = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <h2>🏁 Wybierz wyścig</h2>
    <p>Twój poziom paliwa: <b id="fuelStatus"><?= $car['fuel_level'] ?> L ⛽</b></p>

    <?php if (count($races) > 0): ?>
        <form id="raceForm">
            <select name="race_id" id="raceSelect">
                <?php foreach ($races as $race): ?>
                    <option value="<?= $race['id'] ?>">
                        <?= htmlspecialchars($race['name']) ?> (⛽ <?= $race['fuel_cost'] ?>L)
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" id="raceStartButton">🚗 Start</button>
        </form>
        <div id="raceMessage"></div>
    <?php else: ?>
        <p>🚦 Nie masz jeszcze odblokowanych wyścigów!</p>
    <?php endif;
}

if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    $output = ob_get_clean();
    echo '<div id="main-content">' . $output . '</div>';
    exit;
}
?>