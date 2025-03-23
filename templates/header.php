<?php include __DIR__ . '/../config.php'; ?>
<?php
session_start(); // Startujemy sesję, aby wiedzieć, czy użytkownik jest zalogowany
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?></title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<header>
    <h1>Street Racer Online 🏁</h1>
    <nav>
        <ul>
            <li><a href="index.php">🏠 Strona główna</a></li>
            <li><a href="races.php">🏎️ Wyścigi</a></li>
            <li><a href="garage.php">🚗 Garaż</a></li>
            <li><a href="shop.php">🛒 Sklep</a></li>
            <li><a href="profile.php">👤 Profil</a></li>

            <?php if (isset($_SESSION['user_id'])): ?>
                <!-- Jeśli użytkownik jest zalogowany -->
                <li><a href="dashboard.php">🎮 Panel gracza</a></li>
                <li><a href="logout.php">🚪 Wyloguj</a></li>
            <?php else: ?>
                <!-- Jeśli użytkownik NIE jest zalogowany -->
                <li><a href="login.php">🔑 Logowanie</a></li>
                <li><a href="register.php">📝 Rejestracja</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>
