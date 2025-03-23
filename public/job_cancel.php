<?php
session_start();
require '../config.php';

if (!isset($_SESSION['user_id'])) {
    echo "❌ Musisz być zalogowany!";
    exit();
}

$userId = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM user_jobs WHERE user_id = :user_id AND completed = 0");
$stmt->bindParam(":user_id", $userId);
$stmt->execute();
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    echo "❌ Nie masz aktywnej pracy!";
    exit();
}

// Gracz dostaje tylko 30% wynagrodzenia
$reward = rand($job['reward_min'], $job['reward_max']) * 0.3;
$stmt = $conn->prepare("UPDATE users SET money = money + :reward WHERE id = :user_id");
$stmt->bindParam(":reward", $reward);
$stmt->bindParam(":user_id", $userId);
$stmt->execute();

// Oznaczenie pracy jako zakończonej
$stmt = $conn->prepare("UPDATE user_jobs SET completed = 1 WHERE id = :id");
$stmt->bindParam(":id", $job['id']);
$stmt->execute();

echo "❌ Przerwałeś pracę i otrzymałeś {$reward} zł.";
?>
