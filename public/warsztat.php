<?php
session_start();
require '../config.php';

if (!isset($_SESSION['login'])) {
    echo "Musisz być zalogowany, aby korzystać z warsztatu!";
    exit();
}

echo "<h2>🔧 Warsztat</h2>";
echo "<p>Tutaj możesz naprawiać i tuningować swoje auto!</p>";
echo "<ul>
        <li><button id='repairCar'>🔧 Napraw auto</button></li>
        <li><button id='upgradeCar'>🚀 Ulepsz auto</button></li>
      </ul>";

?>
