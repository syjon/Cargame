document.addEventListener("DOMContentLoaded", function () {
    console.log("✅ Skrypt załadowany!");

    // 🔐 Logowanie i rejestracja
    function handleFormSubmit(formId, url, messageId) {
        const form = document.getElementById(formId);
        if (!form) return;

        form.addEventListener("submit", function (e) {
            e.preventDefault();
            const formData = new FormData(form);

            fetch(url, { method: "POST", body: formData })
                .then(response => response.json())
                .then(data => {
                    console.log("📡 Odpowiedź serwera:", data);
                    document.getElementById(messageId).innerText = data.message;
                    if (data.status === "success") setTimeout(() => location.reload(), 1000);
                })
                .catch(error => console.error("❌ Błąd:", error));
        });
    }

    handleFormSubmit("loginForm", "login.php", "loginMessage");
    handleFormSubmit("registerForm", "register.php", "registerMessage");

    // 📄 Dynamiczne ładowanie stron
    const pageButtons = {
        homeButton: "index.php",
        racesButton: "races.php",
        garageButton: "garage.php",
        shopButton: "shop.php",
        commissionButton: "commission.php",
        dealerButton: "dealer.php",
        workshopButton: "warsztat.php",
        fuelStationButton: "stacja.php",
        jobButton: "job.php",
        profileButton: "dashboard.php"
    };

    Object.keys(pageButtons).forEach(buttonId => {
        const button = document.getElementById(buttonId);
        if (button) button.addEventListener("click", e => {
            e.preventDefault();
            loadPage(pageButtons[buttonId]);
        });
    });

    function loadPage(url) {
        const contentContainer = document.getElementById("main-content") || document.createElement("div");
        contentContainer.id = "main-content";
        document.body.appendChild(contentContainer);

        if (window.location.pathname.includes(url)) return;

        fetch(url + "?ajax=1")
            .then(response => response.ok ? response.text() : Promise.reject("Błąd"))
            .then(html => {
                const tempContainer = document.createElement("div");
                tempContainer.innerHTML = html;
                const newContent = tempContainer.querySelector("#main-content") || tempContainer;
                contentContainer.innerHTML = newContent.innerHTML;

                setupGarageButtons();
				setupRaceFeatures();
                setupRaceForm(); // 🏁 Dodana obsługa formularza wyścigu
            })
            .catch(error => console.error("❌ Błąd ładowania strony:", error));
    }

    // 🚗 Wybór auta
    function setupGarageButtons() {
        window.selectCar = function (carId) {
            fetch("update_active_car.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `car_id=${encodeURIComponent(carId)}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === "success") {
                        alert("✅ Wybrano auto!");
                        document.querySelectorAll(".select-car-btn").forEach(btn => btn.classList.remove("selected"));
                        document.querySelector(`[data-car-id='${carId}']`)?.classList.add("selected");
                    } else {
                        alert("❌ Błąd: " + data.message);
                    }
                })
                .catch(error => console.error("❌ Błąd wyboru auta:", error));
        };
    }

    // ⛽ Tankowanie
    window.refuelCar = function () {
        let fuelAmount = prompt("Ile litrów paliwa chcesz zatankować?");
        
        if (!fuelAmount || isNaN(fuelAmount) || fuelAmount <= 0) {
            alert("❌ Nieprawidłowa ilość paliwa.");
            return;
        }

        fetch("refuel.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "amount=" + encodeURIComponent(fuelAmount)
        })
        .then(response => response.json())
        .then(data => {
            console.log("📡 Odpowiedź serwera:", data);
            if (data.status === "success") {
                document.getElementById("fuelStatus").innerText = data.new_fuel + " L";
                alert("✅ Zatankowano " + fuelAmount + "L!");
            } else {
                alert("❌ Błąd: " + data.message);
            }
        })
        .catch(error => {
            console.error("❌ Błąd tankowania:", error);
            alert("❌ Wystąpił błąd podczas tankowania!");
        });
    };

    document.addEventListener("click", function (event) {
        if (event.target && event.target.id === "refuelButton") {
            console.log("⛽ Kliknięto przycisk Zatankuj");
            refuelCar();
        }
    });

    // 📌 Ukrywanie wyścigów poniżej 5 lvl
    function checkLevelAndShowRaces() {
        fetch("get_user_level.php")
            .then(response => response.json())
            .then(data => {
                document.getElementById("racesButton").style.display = data.level < 5 ? "none" : "inline";
            })
            .catch(error => console.error("❌ Błąd pobierania poziomu:", error));
    }
    checkLevelAndShowRaces();

    // 🏁 Obsługa formularza wyścigu po załadowaniu strony
    function setupRaceForm() {
        const raceForm = document.getElementById("raceForm");

        if (!raceForm) {
            console.warn("❌ Formularz wyścigu NIE został znaleziony!");
            return;
        }

        console.log("✅ Formularz wyścigu znaleziony!");
        raceForm.addEventListener("submit", function (e) {
            e.preventDefault();
            console.log("🏁 Kliknięto START wyścigu!");

            let raceSelect = document.getElementById("raceSelect");
            let raceId = raceSelect?.value;

            if (!raceId) {
                alert("❌ Wybierz wyścig!");
                return;
            }

            fetch("race_start.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "race_id=" + encodeURIComponent(raceId)
            })
            .then(response => response.json())
            .then(data => {
                console.log("📡 Odpowiedź serwera:", data);
                alert(data.message);
                if (data.status === "success") {
                    const fuelStatus = document.getElementById("fuelStatus");
                    if (fuelStatus) fuelStatus.innerText = "Pozostałe paliwo: " + data.new_fuel + " L";
                }
            })
            .catch(error => {
                console.error("❌ Błąd:", error);
                alert("❌ Wystąpił problem z rozpoczęciem wyścigu!");
            });
        });
    }
	window.startJob = function () {
    const jobSelect = document.querySelector("select[name='job_id']");
    if (!jobSelect) {
        alert("❌ Nie wybrano pracy!");
        return;
    }

    const jobId = jobSelect.value;

    fetch(`job_start.php?job_id=${jobId}`)
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            if (data.status === "success") {
                location.reload();
            }
        })
        .catch(error => {
            console.error("❌ Błąd rozpoczęcia pracy:", error);
            alert("❌ Wystąpił problem z rozpoczęciem pracy!");
        });
};
	//Kończenie pracy

window.finishRace = function () {
    fetch("race_finish.php")
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            if (data.status === "success") {
                location.reload();
            }
        })
        .catch(err => {
            console.error("❌ Błąd:", err);
            alert("❌ Nie udało się odebrać nagrody.");
        });
};
// ⏱️ Timer wyścigu
function updateTimer() {
    let timerElement = document.getElementById("race_timer");

    if (!timerElement) {
        setTimeout(updateTimer, 1000);
        return;
    }

    let endTime = parseInt(timerElement.getAttribute("data-end"), 10);
    if (isNaN(endTime)) {
        console.error("❌ Nieprawidłowa wartość data-end:", timerElement.getAttribute("data-end"));
        return;
    }

    function refreshTimer() {
        let now = Math.floor(Date.now() / 1000);
        let remainingTime = endTime - now;

        if (remainingTime > 0) {
            let hours = Math.floor(remainingTime / 3600);
            let minutes = Math.floor((remainingTime % 3600) / 60);
            let seconds = remainingTime % 60;

            let timeString = (hours > 0 ? hours + "h " : "") +
                             (minutes > 0 ? minutes + "m " : "") +
                             seconds + "s";

            timerElement.innerText = "⏳ Pozostały czas: " + timeString;
            setTimeout(refreshTimer, 1000);
        } else {
            timerElement.innerText = "✅ Wyścig zakończony!";
        }
    }

    refreshTimer();
}

// Uruchomienie timera po załadowaniu dokumentu
document.addEventListener('DOMContentLoaded', updateTimer);
    // ⏱️ Timer pracy
    function updateTimer() {
        let timerElement = document.getElementById("timer_job");

        if (!timerElement) {
            setTimeout(updateTimer, 1000);
            return;
        }

        let endTime = parseInt(timerElement.getAttribute("data-end"), 10);
        if (isNaN(endTime)) {
            console.error("❌ Nieprawidłowa wartość data-end:", timerElement.getAttribute("data-end"));
            return;
        }

        function refreshTimer() {
            let now = Math.floor(Date.now() / 1000);
            let remainingTime = endTime - now;

            if (remainingTime > 0) {
                let hours = Math.floor(remainingTime / 3600);
                let minutes = Math.floor((remainingTime % 3600) / 60);
                let seconds = remainingTime % 60;

                let timeString = (hours > 0 ? hours + "h " : "") +
                                 (minutes > 0 ? minutes + "m " : "") +
                                 seconds + "s";

                timerElement.innerText = "⏳ Pozostały czas: " + timeString;
                setTimeout(refreshTimer, 1000);
            } else {
                timerElement.innerText = "✅ Praca zakończona!";
            }
        }

        refreshTimer();
    }
// 🎁 Odbieranie nagrody za wyścig
window.finishRace = function () {
    fetch("race_finish.php")
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            if (data.status === "success") {
                location.reload();
            }
        })
        .catch(error => {
            console.error("❌ Błąd:", error);
            alert("❌ Nie udało się odebrać nagrody!");
        });
};
function setupRaceFeatures() {
    const raceForm = document.getElementById("raceForm");
    const finishBtn = document.getElementById("finishRaceButton");

    // 🏁 Obsługa startu wyścigu
    if (raceForm) {
        console.log("✅ Formularz wyścigu znaleziony!");
        raceForm.addEventListener("submit", function (e) {
            e.preventDefault();
            console.log("🏁 Kliknięto START wyścigu!");

            let raceSelect = document.getElementById("raceSelect");
            let raceId = raceSelect?.value;

            if (!raceId) {
                alert("❌ Wybierz wyścig!");
                return;
            }

            fetch("race_start.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "race_id=" + encodeURIComponent(raceId)
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
                alert("❌ Wystąpił problem z rozpoczęciem wyścigu!");
            });
        });
    } else {
        console.warn("❌ Formularz wyścigu NIE został znaleziony!");
    }

    // 🎁 Obsługa odbioru nagrody
    if (finishBtn) {
        finishBtn.addEventListener("click", function () {
            console.log("🎁 Kliknięto odbierz nagrodę!");

            fetch("race_finish.php")
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    if (data.status === "success") {
                        location.reload();
                    }
                })
                .catch(err => {
                    console.error("❌ Błąd:", err);
                    alert("❌ Nie udało się odebrać nagrody.");
                });
        });
    }
}

    setTimeout(updateTimer, 500);
	function setupRaceButton() {
    const finishBtn = document.getElementById("finishRaceButton");
    if (finishBtn) {
        finishBtn.addEventListener("click", finishRace);
    }
}

});
