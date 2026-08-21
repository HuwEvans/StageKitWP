/**
 * StageKitWP Theme - Real-time Countdown Engine
 */
document.addEventListener('DOMContentLoaded', function () {
    const timerElement = document.getElementById('stagekitwp-timer');
    
    if (!timerElement) return;

    const targetTimeString = timerElement.getAttribute('data-time');
    if (!targetTimeString) return;

    // Convert PHP MySQL standard string (YYYY-MM-DD HH:MM:SS) safely for cross-browser JS parsing
    const targetDate = new Date(targetTimeString.replace(/-/g, '/')).getTime();

    if (isNaN(targetDate)) {
        timerElement.textContent = "Invalid Date Format";
        return;
    }

    const countdownInterval = setInterval(function () {
        const now = new Date().getTime();
        const difference = targetDate - now;

        // If the curtain time has already passed
        if (difference < 0) {
            clearInterval(countdownInterval);
            timerElement.textContent = "Show is in progress or ended!";
            return;
        }

        // Time calculations for days, hours, minutes, and seconds
        const days = Math.floor(difference / (1000 * 60 * 60 * 24));
        const hours = Math.floor((difference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((difference % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((difference % (1000 * 60)) / 1000);

        // Format output display string gracefully
        let displayString = "";
        if (days > 0) displayString += days + "d ";
        displayString += hours.toString().padStart(2, '0') + "h " + 
                         minutes.toString().padStart(2, '0') + "m " + 
                         seconds.toString().padStart(2, '0') + "s";

        timerElement.textContent = displayString;
    }, 1000);
});