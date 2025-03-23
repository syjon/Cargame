<?php
session_start();
require '../config.php';

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "❌ Musisz być zalogowany!"]);
    exit();
}

if (!isset($_GET['job_id'])) {
    echo json_encode(["status" => "error", "message" => "❌ Nie wybrano pracy!"]);
    exit();
}

$userId = $_SESSION['user_id'];
$jobId = intval($_GET['job_id']);

// **🔍 Sprawdzenie, czy użytkownik już pracuje**
$stmt = $conn->prepare("
    SELECT * FROM user_jobs 
    WHERE user_id = :user_id 
    AND completed = 0
");
$stmt->bindParam(":user_id", $userId);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    echo json_encode(["status" => "error", "message" => "❌ Już jesteś w trakcie pracy!"]);
    exit();
}

// Pobranie czasu trwania pracy
$stmt = $conn->prepare("SELECT duration FROM jobs WHERE id = :job_id");
$stmt->bindParam(":job_id", $jobId);
$stmt->execute();
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    echo json_encode(["status" => "error", "message" => "❌ Nie znaleziono pracy!"]);
    exit();
}

$duration = $job['duration']; // Czas trwania pracy w minutach
$startTime = date("Y-m-d H:i:s");
$endTime = date("Y-m-d H:i:s", strtotime("+$duration minutes")); // Dodanie minut do aktualnego czasu

// **📌 Wstawienie pracy do user_jobs**
$stmt = $conn->prepare("INSERT INTO user_jobs (user_id, job_id, start_time, end_time, completed) VALUES (:user_id, :job_id, :start_time, :end_time, 0)");
$stmt->bindParam(":user_id", $userId);
$stmt->bindParam(":job_id", $jobId);
$stmt->bindParam(":start_time", $startTime);
$stmt->bindParam(":end_time", $endTime);
$stmt->execute();

ob_clean(); // Usunięcie ewentualnych ukrytych błędów
echo json_encode(["status" => "success", "message" => "✅ Praca rozpoczęta!", "end_time" => $endTime]);
exit();
