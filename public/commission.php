<?php
session_start();
if (!isset($_SESSION['login'])) {
    echo "<p>Musisz być zalogowany, aby przeglądać giełdę.</p>";
    exit;
}
?>

<div id="main-content">
    <h2>📈 Komis samochodowy</h2>
    <p>Tutaj możesz kupować używane fury.</p>
</div>
