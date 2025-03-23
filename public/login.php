<?php
session_start();
require '../config.php'; // Połączenie z bazą

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login = trim($_POST['login']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT id, login, password FROM users WHERE login = :login OR email = :login");
    $stmt->bindParam(":login", $login);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id']; // ✅ Poprawne zapisanie `user_id`
        $_SESSION['login'] = $user['login'];
        
        echo json_encode(["status" => "success", "message" => "✅ Zalogowano! Przekierowanie..."]);
        exit();
    } else {
        echo json_encode(["status" => "error", "message" => "❌ Nieprawidłowy login lub hasło!"]);
        exit();
    }
}
?>
