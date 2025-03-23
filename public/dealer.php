<?php
session_start();
if (!isset($_SESSION['login'])) {
    echo "<p>Musisz być zalogowany, aby przeglądać salon.</p>";
    exit;
}
?>

<h2>🚘 Salon samochodowy</h2>
<p>Tutaj możesz kupić nowe samochody.</p>
