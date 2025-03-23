<?php
session_start();
require '../config.php'; // Połączenie z bazą

if (!isset($_SESSION['login'])) {
    header("Location: index.php");
    exit();
}

// Pobranie danych użytkownika z bazy
$stmt = $conn->prepare("SELECT login, registration_date, money, experience FROM users WHERE login = :login");
$stmt->bindParam(':login', $_SESSION['login']);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "Błąd: Nie znaleziono użytkownika!";
    exit();
}

// Konwersja daty rejestracji na format bez godziny
$registrationDate = date("Y-m-d", strtotime($user['registration_date']));

// Aktualne XP użytkownika
$xp = $user['experience'];

// Obliczanie poziomu i wymaganego XP do następnego poziomu
$level = 1;
$xpNeeded = 100; // Pierwszy poziom wymaga 100 XP

while ($xp >= $xpNeeded) {
    $xp -= $xpNeeded;
    $xpNeeded = ceil($xpNeeded * 1.27); // 27% więcej na kolejny poziom
    $level++;
}

// XP brakujące do następnego poziomu
$xpLeft = $xpNeeded - $xp;

// Pasek postępu w %
$xpProgress = ($xpNeeded > 0) ? floor(($xp / $xpNeeded) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Profil - Street Racer Online</title>
    <style>
        .profile-container {
            text-align: center;
            font-family: Arial, sans-serif;
            padding: 20px;
            color: #333;
        }

        .xp-bar-container {
            width: 300px;
            height: 20px;
            background-color: #ddd;
            border-radius: 5px;
            overflow: hidden;
            border: 1px solid #aaa;
            margin: 10px auto;
            position: relative;
        }

        .xp-bar {
            height: 100%;
            background-color: #4CAF50;
            text-align: center;
            line-height: 20px;
            color: white;
            font-weight: bold;
            width: <?php echo $xpProgress; ?>%;
            transition: width 0.5s ease-in-out;
        }
    </style>
</head>
<body>

<div class="profile-container">
    <h2>Witaj, <?php echo htmlspecialchars($user['login']); ?>! 🏆</h2>
    <p>📅 Data rejestracji: <?php echo $registrationDate; ?></p>
    <p>💰 Twoje saldo: <?php echo floor($user['money']); ?> zł</p>

    <h3>📈 Doświadczenie</h3>
    <p>🏅 Poziom: <?php echo $level; ?></p>
    <div class="xp-bar-container">
        <div class="xp-bar"><?php echo $xpProgress; ?>%</div>
    </div>
    <p>🔼 XP do następnego poziomu: <?php echo $xpLeft; ?></p>
</div>

</body>
</html>
