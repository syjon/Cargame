<?php
session_start();
require '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "❌ Musisz być zalogowany!"]);
    exit();
}

$userId = $_SESSION['user_id'];

// Pobierz aktualnie trwający wyścig
$stmt = $conn->prepare("
    SELECT user_races.*, races.reward_min, races.reward_max, races.experience, UNIX_TIMESTAMP(user_races.end_time) AS end_time_unix 
    FROM user_races 
    JOIN races ON user_races.race_id = races.id 
    WHERE user_races.user_id = :user_id AND user_races.completed = 0 
    ORDER BY user_races.id DESC LIMIT 1
");
$stmt->bindParam(':user_id', $userId);
$stmt->execute();
$race = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$race) {
    echo json_encode(["status" => "error", "message" => "❌ Brak aktywnego wyścigu do zakończenia!"]);
    exit();
}

// Sprawdź, czy wyścig się zakończył
$currentTime = time();
if ($currentTime < $race['end_time_unix']) {
    $remaining = $race['end_time_unix'] - $currentTime;
    echo json_encode(["status" => "error", "message" => "⏳ Wyścig jeszcze trwa! Pozostało: {$remaining} sekund."]);
    exit();
}

// Wylosuj nagrodę i doświadczenie
$reward = rand($race['reward_min'], $race['reward_max']);
$experience = $race['experience'];

// Aktualizacja użytkownika – dodanie nagrody i XP
$stmt = $conn->prepare("UPDATE users SET money = money + :reward, experience = experience + :xp WHERE id = :user_id");
$stmt->bindParam(':reward', $reward);
$stmt->bindParam(':xp', $experience);
$stmt->bindParam(':user_id', $userId);
$stmt->execute();

// Oznacz wyścig jako zakończony
$stmt = $conn->prepare("UPDATE user_races SET completed = 1 WHERE id = :id");
$stmt->bindParam(':id', $race['id']);
$stmt->execute();

// Sukces
echo json_encode([
    "status" => "success",
    "message" => "🏁 Gratulacje! Otrzymałeś {$reward}$ i {$experience} XP!"
]);
exit();
