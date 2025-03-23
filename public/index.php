<?php
session_start();
require '../config.php'; // Połączenie z bazą
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Street Racer Online</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php

$userLevel = 0; // Domyślny poziom
$xpNeeded = 100; // Punkty XP wymagane na pierwszy poziom

if (!empty($_SESSION['user_id'])) {
    // Pobieramy XP gracza
    $stmt = $conn->prepare("SELECT experience FROM users WHERE id = :user_id");
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $xp = $user['experience'] ?? 0; // Jeśli brak XP, to 0

    // Obliczamy poziom gracza
    while ($xp >= $xpNeeded) {
        $xp -= $xpNeeded;
        $xpNeeded = ceil($xpNeeded * 1.27);
        $userLevel++;
    }
}
?>

<nav>
    <ul>
        <?php if (!empty($_SESSION['user_id'])): ?>
            <li><a href="#" id="homeButton">🏠 Strona główna</a></li>

            <?php if ($userLevel >= 5): ?> <!-- Wyświetlamy dopiero od 5 poziomu -->
                <li><a href="#" id="racesButton">🏎️ Wyścigi</a></li>
            <?php else: ?>
                <li><a href="#" id="racesButton" style="display: none;">🏎️ Wyścigi</a></li>
            <?php endif; ?>

            <li><a href="#" id="garageButton">🚗 Garaż</a></li>
            <li><a href="#" id="shopButton">🛒 Sklep</a></li>
            <li><a href="#" id="commissionButton">🔄 Komis</a></li>
            <li><a href="#" id="dealerButton">🚘 Salon</a></li>
            <li><a href="#" id="workshopButton">🔧 Warsztat</a></li>
            <li><a href="#" id="fuelStationButton">⛽ Stacja paliw</a></li>
            <li><a href="#" id="jobButton">🛠️ Praca</a></li>
            <li><a href="#" id="profileButton">🧑 Profil</a></li>
            <li><a href="logout.php">🚪 Wyloguj się</a></li>
        <?php else: ?>
            <li>
                <form id="loginForm" method="post">
                    <input type="text" name="login" placeholder="Login / E-mail" required>
                    <input type="password" name="password" placeholder="Hasło" required>
                    <button type="submit">🔑 Zaloguj się</button>
                    <button type="button" id="registerButton">📄 Rejestracja</button>
                </form>
                <p id="loginMessage"></p>
            </li>
        <?php endif; ?>
    </ul>
</nav>


<!-- Główna treść strony -->
<div id="main-content">
    <h2>🏎️ Witaj w grze Street Racer Online!</h2>
    <p>Wejdź do świata nielegalnych wyścigów i zostań najlepszym kierowcą!</p>
</div>


<!-- Okno modalne rejestracji -->
<div id="registerModal" class="modal">
    <div class="modal-content">
        <span id="closeRegister">&times;</span>
        <h2>📄 Rejestracja</h2>
        <form id="registerForm">
            <label>📛 Login:</label>
            <input type="text" name="login" required><br>
            
            <label>📧 E-mail:</label>
            <input type="email" name="email" required><br>
            
            <label>🔒 Hasło:</label>
            <input type="password" name="password" required><br>
            
            <button type="submit">📝 Zarejestruj się</button>
        </form>
        <p id="registerMessage"></p>
    </div>
</div>

<<script src="/assets/script.js"></script>


</body>
</html>
