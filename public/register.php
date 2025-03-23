<?php
session_start();
require('../config.php'); // Połączenie z bazą

header('Content-Type: application/json'); // ✅ Wysyłamy JSON do JavaScript

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login = trim($_POST['login']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    try {
        // Sprawdzamy, czy login lub email już istnieje
        $stmt = $conn->prepare("SELECT id FROM users WHERE login = :login OR email = :email");
        $stmt->execute(['login' => $login, 'email' => $email]);

        if ($stmt->rowCount() > 0) { 
            echo json_encode(["status" => "error", "message" => "❌ Login lub e-mail już istnieje!"]);
            exit;
        } else {
            // Wstawiamy nowego użytkownika
            $stmt = $conn->prepare("INSERT INTO users (login, email, password, registration_date, balance) 
                                    VALUES (:login, :email, :password, NOW(), 1000.00)");
            $result = $stmt->execute([
                'login' => $login,
                'email' => $email,
                'password' => $password
            ]);

            if ($result) {
                echo json_encode(["status" => "success", "message" => "✅ Konto utworzone! Możesz się teraz zalogować."]);
            } else {
                echo json_encode(["status" => "error", "message" => "❌ Wystąpił błąd podczas rejestracji."]);
            }
            exit;
        }
    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => "❌ Błąd rejestracji: " . $e->getMessage()]);
        exit;
    }
}
?>
