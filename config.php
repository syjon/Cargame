<?php
// Plik konfiguracyjny gry

// Dane do połączenia z bazą danych
define("DB_HOST", "localhost");
define("DB_USER", "root");  // Domyślny użytkownik MySQL/MariaDB
define("DB_PASS", "0db17e3f97M`");  // Hasło do bazy danych
define("DB_NAME", "cargame"); // Nazwa bazy danych

// Podstawowa konfiguracja strony
define("SITE_NAME", "Street Racer Online");
define("BASE_URL", "http://192.168.100.64"); // Adres Twojego serwera

// Połączenie z bazą danych
try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("❌ Błąd połączenia z bazą danych: " . $e->getMessage());
}
?>
