
// ======================================
// URBANFLOW AUTOMATIC SESSION TIMEOUT
// ======================================

const SESSION_LIMIT = 15 * 60 * 1000;
const WARNING_TIME = 14 * 60 * 1000;

const ACTIVITY_URL = "../backend/session_activity.php";
const LOGOUT_URL = "../backend/logout.php?timeout=1";

let lastActivity = Date.now();
let lastServerUpdate = 0;
let warningVisible = false;
let warningBox = null;
let countdownInterval = null;

// Create the warning window.
function createWarningBox() {
    if (warningBox) return;

    warningBox = document.createElement("div");

    warningBox.id = "sessionWarning";

    warningBox.style.cssText = `
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.65);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 99999;
        padding: 20px;
    `;

    warningBox.innerHTML = `
        <div style="
            background: #ffffff;
            color: #183b32;
            padding: 30px;
            border-radius: 14px;
            max-width: 420px;
            width: 100%;
            text-align: center;
            box-shadow: 0 10px 35px rgba(0,0,0,0.25);
        ">
            <h2 style="margin-top: 0;">Session Timeout</h2>

            <p>
                You have been inactive for some time.
                For security, you will be logged out soon.
            </p>

            <h1 id="sessionCountdown"
                style="font-size: 42px; margin: 20px 0;">
                60
            </h1>

            <p>seconds remaining</p>

            <button id="continueSession"
                style="
                    background: #285b49;
                    color: white;
                    border: none;
                    padding: 13px 25px;
                    border-radius: 7px;
                    cursor: pointer;
                    font-size: 15px;
                ">
                Continue Session
            </button>
        </div>
    `;

    document.body.appendChild(warningBox);

    document.getElementById("continueSession")
        .addEventListener("click", function () {
            recordActivity(true);
        });
}

// Tell PHP that the user is active.
async function updateServerActivity() {
    try {
        const response = await fetch(ACTIVITY_URL, {
            method: "POST",
            credentials: "same-origin",
            cache: "no-store"
        });

        if (response.status === 401) {
            window.location.href = LOGOUT_URL;
            return;
        }

        if (!response.ok) {
            console.error("Could not refresh session.");
            return;
        }

        const data = await response.json();

        if (!data.success) {
            window.location.href = LOGOUT_URL;
            return;
        }

        lastServerUpdate = Date.now();

    } catch (error) {
        console.error("Session activity error:", error);
    }
}

// Record user activity.
function recordActivity(forceUpdate = false) {
    lastActivity = Date.now();

    // Close the warning if it is showing.
    if (warningVisible) {
        warningVisible = false;

        warningBox.style.display = "none";

        clearInterval(countdownInterval);
    }

    // Update the server at most once every 30 seconds.
    if (
        forceUpdate ||
        Date.now() - lastServerUpdate >= 30000
    ) {
        updateServerActivity();
    }
}

// Display the 60-second warning.
function showWarning() {
    if (warningVisible) return;

    warningVisible = true;

    createWarningBox();

    warningBox.style.display = "flex";

    let remaining = Math.ceil(
        (SESSION_LIMIT - (Date.now() - lastActivity)) / 1000
    );

    const countdown = document.getElementById(
        "sessionCountdown"
    );

    countdown.textContent = remaining;

    countdownInterval = setInterval(function () {

        const elapsed = Date.now() - lastActivity;

        remaining = Math.ceil(
            (SESSION_LIMIT - elapsed) / 1000
        );

        countdown.textContent = Math.max(remaining, 0);

        if (elapsed >= SESSION_LIMIT) {
            clearInterval(countdownInterval);

            window.location.href = LOGOUT_URL;
        }

    }, 1000);
}

// Watch for user activity.
[
    "mousemove",
    "mousedown",
    "keydown",
    "scroll",
    "touchstart",
    "click"
].forEach(function (eventName) {

    document.addEventListener(
        eventName,
        function () {
            recordActivity();
        },
        { passive: true }
    );

});

// Check inactivity every second.
setInterval(function () {

    const inactiveTime = Date.now() - lastActivity;

    if (inactiveTime >= SESSION_LIMIT) {
        window.location.href = LOGOUT_URL;
        return;
    }

    if (inactiveTime >= WARNING_TIME) {
        showWarning();
    }

}, 1000);

// Initial server check.
updateServerActivity();
