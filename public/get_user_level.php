<?php
session_start();
require '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Nie zalogowano"]);
    exit();
}

$stmt = $conn->prepare("SELECT experience FROM users WHERE id = :user_id");
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$xp = $user['experience'] ?? 0;
$xpNeeded = 100;
$userLevel = 0;

while ($xp >= $xpNeeded) {
    $xp -= $xpNeeded;
    $xpNeeded = ceil($xpNeeded * 1.27);
    $userLevel++;
}

echo json_encode(["status" => "success", "level" => $userLevel]);
?>
