<?php
session_start();
require '../config.php';

if (!isset($_SESSION['user_id'])) {
    echo "<p>Musisz być zalogowany, aby pracować! 🏢</p>";
    exit();
}

$userId = $_SESSION['user_id'];

// **🔄 Automatyczna aktualizacja zakończonych prac i wypłata nagrody**
$stmt = $conn->prepare("SELECT user_jobs.*, jobs.name, jobs.reward_min, jobs.reward_max, jobs.experience 
    FROM user_jobs 
    JOIN jobs ON user_jobs.job_id = jobs.id 
    WHERE user_jobs.user_id = :user_id 
    AND user_jobs.completed = 0 
    AND user_jobs.end_time <= NOW()");
$stmt->bindParam(":user_id", $userId);
$stmt->execute();
$completedJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($completedJobs as $job) {
    $reward = rand($job['reward_min'], $job['reward_max']);
    $experience = $job['experience'];
    
    $stmt = $conn->prepare("UPDATE users SET money = money + :reward, experience = experience + :xp WHERE id = :user_id");
    $stmt->bindParam(":reward", $reward);
    $stmt->bindParam(":xp", $experience);
    $stmt->bindParam(":user_id", $userId);
    $stmt->execute();
    
    $stmt = $conn->prepare("UPDATE user_jobs SET completed = 1 WHERE id = :job_id");
    $stmt->bindParam(":job_id", $job['id']);
    $stmt->execute();

    echo "<p>✅ Otrzymałeś <b>{$reward} zł</b> oraz <b>{$experience} XP</b> za zakończoną pracę <b>{$job['name']}</b>!</p>";
}

// **🔍 Sprawdzenie, czy użytkownik nadal jest w trakcie pracy**
$stmt = $conn->prepare("SELECT user_jobs.*, jobs.name, UNIX_TIMESTAMP(user_jobs.end_time) AS end_timestamp 
    FROM user_jobs 
    JOIN jobs ON user_jobs.job_id = jobs.id 
    WHERE user_jobs.user_id = :user_id 
    AND user_jobs.completed = 0");
$stmt->bindParam(":user_id", $userId);
$stmt->execute();
$activeJob = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<h2>💼 Wybierz pracę</h2>";

if ($activeJob) {
    $currentTime = time();
    $endTimestamp = strtotime($activeJob['end_time']); // Konwersja na UNIX timestamp
    $remainingTime = max(0, $endTime - $currentTime);

    // **Dodanie obsługi minut i sekund, aby uniknąć błędów**
    $days = floor($remainingTime / 86400);
    $hours = floor(($remainingTime % 86400) / 3600);
    $minutes = floor(($remainingTime % 3600) / 60);
    $seconds = $remainingTime % 60;

    $formattedTime = ($days > 0 ? $days . "d " : "") . 
                     ($hours > 0 ? $hours . "h " : "") . 
                     ($minutes > 0 ? $minutes . "m " : "") . 
                     $seconds . "s";

    echo "<h3>⏳ Jesteś w trakcie pracy: <b>{$activeJob['name']}</b></h3>";
    echo "<div class='timer' id='timer_job' data-end='{$endTimestamp}'>Ładowanie...</div>";
    echo "<button onclick='cancelJob()'>❌ Przerwij pracę</button>";

    echo "<script>
        document.addEventListener('DOMContentLoaded', function () {
            let countdownElement = document.getElementById('countdown');
            let endTime = parseInt(countdownElement.getAttribute('data-end'), 10);
            if (!countdownElement || isNaN(endTime)) return;

            function updateCountdown() {
                let currentTime = Math.floor(Date.now() / 1000);
                let remainingSeconds = endTime - currentTime;

                if (remainingSeconds > 0) {
                    let days = Math.floor(remainingSeconds / 86400);
                    let hours = Math.floor((remainingSeconds % 86400) / 3600);
                    let minutes = Math.floor((remainingSeconds % 3600) / 60);
                    let seconds = remainingSeconds % 60;
                    
                    let timeText = (days > 0 ? days + 'd ' : '') +
                                   (hours > 0 ? hours + 'h ' : '') +
                                   (minutes > 0 ? minutes + 'm ' : '') +
                                   seconds + 's';

                    countdownElement.innerText = timeText;
                    setTimeout(updateCountdown, 1000);
                } else {
                    countdownElement.innerText = '✅ Praca zakończona! Odśwież stronę, aby odebrać wynagrodzenie.';
                }
            }
            updateCountdown();
        });
    </script>";
    exit();
}

// **🏆 Pobranie dostępnych prac**
function formatDuration($minutes) {
    $days = floor($minutes / 1440);
    $hours = floor(($minutes % 1440) / 60);
    $mins = $minutes % 60;

    $formatted = "";
    if ($days > 0) $formatted .= "{$days}d ";
    if ($hours > 0) $formatted .= "{$hours}h ";
    if ($mins > 0 || $formatted === "") $formatted .= "{$mins}m";
    return trim($formatted);
}

$stmt = $conn->prepare("SELECT * FROM jobs ORDER BY duration ASC");
$stmt->execute();
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($jobs) > 0) {
    echo "<form id='jobForm'>";
    echo "<select name='job_id'>";
    foreach ($jobs as $job) {
        $formattedDuration = formatDuration($job['duration']);
        echo "<option value='{$job['id']}'>
            {$job['name']} (🕒 {$formattedDuration}, 💰 {$job['reward_min']} - {$job['reward_max']} zł, XP: {$job['experience']})
        </option>";
    }
    echo "</select>";
    echo "<button type='button' onclick='startJob()'>🚀 Rozpocznij</button>";
    echo "</form>";
} else {
    echo "<p>🚫 Brak dostępnych prac.</p>";
}
?>

<script>
function startJob() {
    let jobSelect = document.querySelector("select[name='job_id']");
    if (!jobSelect) {
        alert("❌ Wystąpił problem! Wybierz pracę.");
        return;
    }
    
    let jobId = jobSelect.value;
    fetch('job_start.php', {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "job_id=" + encodeURIComponent(jobId)
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
        if (data.status === "success") {
            location.reload();
        }
    })
    .catch(error => {
        console.error("❌ Błąd:", error);
        alert("❌ Wystąpił problem z rozpoczęciem pracy!");
    });
}

function cancelJob() {
    fetch('job_cancel.php', { method: "POST" })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
        if (data.status === "success") {
            location.reload();
        }
    })
    .catch(error => {
        console.error("❌ Błąd anulowania pracy:", error);
        alert("❌ Nie udało się anulować pracy!");
    });
}
</script>
