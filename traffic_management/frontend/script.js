
const API_URL = "../backend/get_incidents.php";
const RESOLVE_API_URL = "../backend/resolve_incident.php";
const STATUS_API_URL = "../backend/update_incident_status.php";

const CURRENT_USER_ROLE = (
    document.body.dataset.userRole || "reporter"
).toLowerCase();

let allIncidents = [];

// ======================================
// NOTIFICATION SYSTEM
// ======================================

function setupNotificationStyles() {
    if (document.getElementById("urbanflow-notification-styles")) return;

    const style = document.createElement("style");
    style.id = "urbanflow-notification-styles";

    style.textContent = `
        .urbanflow-toast {
            position: fixed;
            top: 24px;
            right: 24px;
            z-index: 99999;
            width: min(390px, calc(100% - 32px));
            padding: 18px 20px;
            border-radius: 16px;
            background: #183b32;
            color: #fff;
            box-shadow: 0 16px 45px rgba(0,0,0,.18);
            display: flex;
            align-items: flex-start;
            gap: 13px;
            opacity: 0;
            transform: translateY(-12px);
            transition: opacity .25s ease, transform .25s ease;
            pointer-events: none;
            font-family: 'DM Sans', sans-serif;
        }

        .urbanflow-toast.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .urbanflow-toast.error {
            background: #702e2e;
        }

        .urbanflow-toast-icon {
            width: 30px;
            height: 30px;
            min-width: 30px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: rgba(255,255,255,.15);
            font-weight: 800;
        }

        .urbanflow-toast strong {
            display: block;
            margin-bottom: 4px;
            font-size: 14px;
        }

        .urbanflow-toast p {
            margin: 0;
            color: rgba(255,255,255,.8);
            font-size: 12px;
            line-height: 1.6;
        }
    `;

    document.head.appendChild(style);
}

function showNotification(title, message, isError = false) {
    setupNotificationStyles();

    let toast = document.getElementById("urbanflow-toast");

    if (!toast) {
        toast = document.createElement("div");
        toast.id = "urbanflow-toast";
        toast.className = "urbanflow-toast";
        toast.setAttribute("role", "status");
        toast.setAttribute("aria-live", "polite");
        document.body.appendChild(toast);
    }

    toast.className = "urbanflow-toast" + (isError ? " error" : "");
    toast.innerHTML = "";

    const icon = document.createElement("span");
    icon.className = "urbanflow-toast-icon";
    icon.textContent = isError ? "!" : "✓";

    const content = document.createElement("div");

    const heading = document.createElement("strong");
    heading.textContent = title;

    const description = document.createElement("p");
    description.textContent = message;

    content.append(heading, description);
    toast.append(icon, content);

    requestAnimationFrame(() => {
        toast.classList.add("visible");
    });

    clearTimeout(toast.hideTimer);

    toast.hideTimer = setTimeout(() => {
        toast.classList.remove("visible");
    }, 5000);
}

// ======================================
// SAFE ELEMENT HELPERS
// ======================================

function createTextElement(tag, className, value) {
    const element = document.createElement(tag);

    if (className) {
        element.className = className;
    }

    element.textContent = value ?? "";

    return element;
}

function createButton(text, className, onClick) {
    const button = document.createElement("button");

    button.type = "button";
    button.className = className;
    button.textContent = text;
    button.addEventListener("click", onClick);

    return button;
}

function isIncidentManager() {
    return ["admin", "officer"].includes(CURRENT_USER_ROLE);
}

// ======================================
// LOAD INCIDENTS FROM DATABASE
// ======================================

async function loadIncidents() {
    const incidentList = document.getElementById("incidentList");
    const resolvedList = document.getElementById("resolvedIncidentList");
    const message = document.getElementById("incident-message");

    if (!incidentList) return;

    incidentList.innerHTML = "";
    incidentList.appendChild(
        createTextElement("p", "empty-state", "Loading incidents...")
    );

    if (resolvedList) {
        resolvedList.innerHTML = "";
        resolvedList.appendChild(
            createTextElement("p", "empty-state", "Loading resolved cases...")
        );
    }

    try {
        const response = await fetch(API_URL, {
            method: "GET",
            headers: { "Accept": "application/json" },
            cache: "no-store"
        });

        const responseText = await response.text();

        let data;

        try {
            data = JSON.parse(responseText);
        } catch {
            throw new Error(
                "The incidents backend did not return valid JSON. Check the PHP endpoint."
            );
        }

        if (!response.ok || data.success !== true) {
            throw new Error(data.message || "Could not retrieve incidents.");
        }

        allIncidents = Array.isArray(data.incidents)
            ? data.incidents
            : [];

        renderIncidents();

        if (message) {
            message.textContent =
                allIncidents.length + " incident(s) recorded in UrbanFlow.";
        }

    } catch (error) {
        console.error("Incident loading error:", error);

        incidentList.innerHTML = "";
        incidentList.appendChild(
            createTextElement(
                "p",
                "empty-state",
                "Could not load incidents: " + error.message
            )
        );

        if (resolvedList) {
            resolvedList.innerHTML = "";
            resolvedList.appendChild(
                createTextElement(
                    "p",
                    "empty-state",
                    "The resolved archive could not be loaded."
                )
            );
        }

        if (message) {
            message.textContent = "Unable to load incident records.";
        }

        showNotification("Loading failed", error.message, true);
    }
}

// ======================================
// UPDATE INCIDENT COUNTERS
// ======================================

function updateIncidentCounters() {
    const active = allIncidents.filter(
        incident => incident.status !== "resolved"
    );

    const reported = active.filter(
        incident => incident.status === "reported"
    );

    const investigating = active.filter(
        incident => incident.status === "investigating"
    );

    const critical = active.filter(
        incident => String(incident.severity).toLowerCase() === "critical"
    );

    const resolved = allIncidents.filter(
        incident => incident.status === "resolved"
    );

    const counters = {
        activeIncidentCount: active.length,
        reportedIncidentCount: reported.length,
        investigatingIncidentCount: investigating.length,
        criticalIncidentCount: critical.length,
        resolvedIncidentCount: resolved.length
    };

    Object.entries(counters).forEach(([id, value]) => {
        const element = document.getElementById(id);

        if (element) {
            element.textContent = value.toLocaleString("en-KE");
        }
    });
}

// ======================================
// RENDER ACTIVE AND RESOLVED INCIDENTS
// ======================================

function renderIncidents() {
    updateIncidentCounters();
    renderActiveIncidents();
    renderResolvedIncidents();
}

// ======================================
// FILTER ACTIVE INCIDENTS
// ======================================

function renderActiveIncidents() {
    const incidentList = document.getElementById("incidentList");

    if (!incidentList) return;

    incidentList.innerHTML = "";

    const searchValue = (
        document.getElementById("searchInput")?.value || ""
    ).trim().toLowerCase();

    const statusValue = (
        document.getElementById("statusFilter")?.value || "all"
    ).toLowerCase();

    const severityValue = (
        document.getElementById("severityFilter")?.value || "all"
    ).toLowerCase();

    const typeValue = (
        document.getElementById("typeFilter")?.value || "all"
    ).toLowerCase();

    const activeIncidents = allIncidents.filter(incident => {
        if (incident.status === "resolved") return false;

        const searchableText = [
            incident.id,
            incident.title,
            incident.location,
            incident.type,
            incident.severity,
            incident.description,
            incident.status
        ].join(" ").toLowerCase();

        const matchesSearch = searchableText.includes(searchValue);

        const matchesStatus =
            statusValue === "all" ||
            String(incident.status).toLowerCase() === statusValue;

        const matchesSeverity =
            severityValue === "all" ||
            String(incident.severity).toLowerCase() === severityValue;

        const matchesType =
            typeValue === "all" ||
            String(incident.type).toLowerCase() === typeValue;

        return matchesSearch &&
            matchesStatus &&
            matchesSeverity &&
            matchesType;
    });

    if (activeIncidents.length === 0) {
        incidentList.appendChild(
            createTextElement(
                "p",
                "empty-state",
                allIncidents.some(item => item.status !== "resolved")
                    ? "No incidents match your search or filters."
                    : "There are currently no active incidents."
            )
        );

        return;
    }

    activeIncidents.forEach(incident => {
        incidentList.appendChild(createIncidentCard(incident, false));
    });
}

// ======================================
// FILTER RESOLVED INCIDENTS
// ======================================

function renderResolvedIncidents() {
    const resolvedList = document.getElementById("resolvedIncidentList");

    if (!resolvedList) return;

    resolvedList.innerHTML = "";

    const searchValue = (
        document.getElementById("resolvedSearchInput")?.value || ""
    ).trim().toLowerCase();

    const resolvedIncidents = allIncidents.filter(incident => {
        if (incident.status !== "resolved") return false;

        const searchableText = [
            incident.id,
            incident.title,
            incident.location,
            incident.type,
            incident.severity,
            incident.description,
            incident.resolved_by_name,
            incident.resolution_notes
        ].join(" ").toLowerCase();

        return searchableText.includes(searchValue);
    });

    if (resolvedIncidents.length === 0) {
        resolvedList.appendChild(
            createTextElement(
                "p",
                "empty-state",
                allIncidents.some(item => item.status === "resolved")
                    ? "No resolved incidents match your search."
                    : "No incidents have been resolved yet."
            )
        );

        return;
    }

    resolvedIncidents.forEach(incident => {
        resolvedList.appendChild(createIncidentCard(incident, true));
    });
}

// ======================================
// CREATE INCIDENT CARD
// ======================================

function createIncidentCard(incident, isResolved) {
    const card = document.createElement("article");

    card.className = "incident-item";
    card.dataset.incidentId = incident.id;

    const statusValue = String(incident.status || "reported").toLowerCase();
    const severityValue = String(incident.severity || "medium").toUpperCase();

    const title = createTextElement(
        "h3",
        "incident-card-title",
        incident.title || "Untitled incident"
    );

    const reference = createTextElement(
        "p",
        "incident-reference",
        "Incident #" + incident.id
    );

    const location = createTextElement(
        "p",
        "incident-detail",
        "Location: " + (incident.location || "Not provided")
    );

    const type = createTextElement(
        "p",
        "incident-detail",
        "Type: " + (incident.type || "Not specified")
    );

    const severity = createTextElement(
        "p",
        "incident-detail",
        "Severity: " + severityValue
    );

    const description = createTextElement(
        "p",
        "incident-description",
        incident.description || "No description provided."
    );

    const status = createTextElement(
        "span",
        "incident-status status-" + (isResolved ? "resolved" : statusValue),
        isResolved ? "RESOLVED" : statusValue.toUpperCase()
    );

    const reportedDate = createTextElement(
        "p",
        "incident-date",
        "Reported: " + formatDate(incident.created_at)
    );

    card.append(
        reference,
        title,
        status,
        location,
        type,
        severity,
        description,
        reportedDate
    );

    if (isResolved) {
        card.appendChild(
            createTextElement(
                "p",
                "incident-resolution-date",
                "Resolved: " + formatDate(incident.resolved_at)
            )
        );

        card.appendChild(
            createTextElement(
                "p",
                "incident-resolved-by",
                "Resolved by: " + (incident.resolved_by_name || "System user")
            )
        );

        card.appendChild(
            createTextElement(
                "p",
                "incident-resolution-notes",
                "Resolution notes: " +
                    (incident.resolution_notes || "No notes provided.")
            )
        );
    }

    if (!isResolved && isIncidentManager()) {
        const actions = document.createElement("div");
        actions.className = "incident-card-actions";

        if (statusValue === "reported") {
            const investigateButton = createButton(
                "Start investigation ↗",
                "investigate-incident-button",
                function () {
                    updateIncidentStatus(
                        incident.id,
                        investigateButton
                    );
                }
            );

            actions.appendChild(investigateButton);
        }

        const resolveButton = createButton(
            "Mark as resolved ✓",
            "resolve-incident-button",
            function () {
                resolveIncident(incident.id, resolveButton);
            }
        );

        actions.appendChild(resolveButton);
        card.appendChild(actions);
    }

    return card;
}

// ======================================
// FORMAT DATE
// ======================================

function formatDate(dateValue) {
    if (!dateValue) return "Not recorded";

    const date = new Date(
        String(dateValue).replace(" ", "T")
    );

    if (Number.isNaN(date.getTime())) {
        return String(dateValue);
    }

    return date.toLocaleString("en-KE", {
        dateStyle: "medium",
        timeStyle: "short"
    });
}

// ======================================
// CHANGE STATUS TO INVESTIGATING
// ======================================

async function updateIncidentStatus(incidentId, button) {
    const confirmed = confirm(
        "Start investigating incident #" + incidentId + "?"
    );

    if (!confirmed) return;

    button.disabled = true;
    button.textContent = "Updating status...";

    try {
        const formData = new FormData();

        formData.append("incident_id", incidentId);

        const response = await fetch(STATUS_API_URL, {
            method: "POST",
            body: formData,
            headers: {
                "Accept": "application/json"
            }
        });

        const responseText = await response.text();

        let data;

        try {
            data = JSON.parse(responseText);
        } catch {
            throw new Error("The server returned an invalid response.");
        }

        if (!response.ok || data.success !== true) {
            throw new Error(
                data.message || "Could not update incident status."
            );
        }

        showNotification(
            "Investigation started",
            data.message || "The incident is now being investigated."
        );

        await loadIncidents();

        if (typeof loadAnalytics === "function") {
            loadAnalytics();
        }

    } catch (error) {
        console.error("Status update error:", error);

        showNotification(
            "Status update failed",
            error.message,
            true
        );

        button.disabled = false;
        button.textContent = "Start investigation ↗";
    }
}

// ======================================
// RESOLVE INCIDENT
// ======================================

async function resolveIncident(incidentId, button) {
    const notes = prompt(
        "Enter the resolution notes for incident #" + incidentId + ":"
    );

    if (notes === null) return;

    if (notes.trim() === "") {
        showNotification(
            "Resolution notes required",
            "Please explain what action was taken.",
            true
        );
        return;
    }

    if (notes.trim().length > 10000) {
        showNotification(
            "Notes too long",
            "Your resolution notes must be shorter.",
            true
        );
        return;
    }

    const confirmed = confirm(
        "Are you sure you want to mark incident #" +
        incidentId +
        " as resolved?"
    );

    if (!confirmed) return;

    button.disabled = true;
    button.textContent = "Saving resolution...";

    try {
        const formData = new FormData();

        formData.append("incident_id", incidentId);
        formData.append("resolution_notes", notes.trim());

        const response = await fetch(RESOLVE_API_URL, {
            method: "POST",
            body: formData,
            headers: {
                "Accept": "application/json"
            }
        });

        const responseText = await response.text();

        let data;

        try {
            data = JSON.parse(responseText);
        } catch {
            throw new Error("The server returned an invalid response.");
        }

        if (!response.ok || data.success !== true) {
            throw new Error(
                data.message || "Could not resolve incident."
            );
        }

        showNotification(
            "Incident resolved",
            data.message ||
                "The incident has been moved to Resolved Cases."
        );

        await loadIncidents();

        if (typeof loadAnalytics === "function") {
            loadAnalytics();
        }

    } catch (error) {
        console.error("Resolution error:", error);

        showNotification(
            "Resolution failed",
            error.message,
            true
        );

        button.disabled = false;
        button.textContent = "Mark as resolved ✓";
    }
}

// ======================================
// FILTER EVENT LISTENERS
// ======================================

function setupIncidentFilters() {
    const activeSearch = document.getElementById("searchInput");
    const resolvedSearch = document.getElementById("resolvedSearchInput");

    const statusFilter = document.getElementById("statusFilter");
    const severityFilter = document.getElementById("severityFilter");
    const typeFilter = document.getElementById("typeFilter");

    if (activeSearch) {
        activeSearch.addEventListener("input", renderActiveIncidents);
    }

    if (resolvedSearch) {
        resolvedSearch.addEventListener("input", renderResolvedIncidents);
    }

    [statusFilter, severityFilter, typeFilter].forEach(filter => {
        if (filter) {
            filter.addEventListener("change", renderActiveIncidents);
        }
    });
}

// ======================================
// REPORT INCIDENT FORM
// NORMAL FORM SUBMISSION — NO AJAX
// ======================================

function setupReportForm() {
    const reportForm = document.getElementById("incidentForm");

    if (!reportForm) return;

    reportForm.addEventListener("submit", function (event) {
        const title = reportForm.querySelector('[name="title"]');
        const type = reportForm.querySelector('[name="type"]');
        const location = reportForm.querySelector('[name="location"]');
        const severity = reportForm.querySelector('[name="severity"]');
        const description = reportForm.querySelector('[name="description"]');

        if (
            !title ||
            !type ||
            !location ||
            !severity ||
            !description ||
            title.value.trim() === "" ||
            type.value.trim() === "" ||
            location.value.trim() === "" ||
            severity.value.trim() === "" ||
            description.value.trim().length < 10
        ) {
            event.preventDefault();

            showNotification(
                "Incomplete report",
                "Complete all fields. Your description must contain at least 10 characters.",
                true
            );

            return;
        }

        const confirmed = confirm(
            "Are you sure you want to submit this incident report?"
        );

        if (!confirmed) {
            event.preventDefault();
            return;
        }

        const submitButton = reportForm.querySelector(
            'button[type="submit"]'
        );

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = "Submitting report...";
        }

        // Normal browser submission to report.php.
        // report.php redirects back to the dashboard.
    });
}

// ======================================
// REPORT SUCCESS / ERROR MESSAGE
// ======================================

function showReportResult() {
    const params = new URLSearchParams(window.location.search);

    if (params.get("report") === "success") {
        showNotification(
            "Report submitted",
            "Your traffic incident has been recorded successfully."
        );

        window.history.replaceState(
            {},
            document.title,
            window.location.pathname
        );
    }

    if (params.get("report") === "error") {
        showNotification(
            "Report not submitted",
            "Something went wrong while saving your report.",
            true
        );

        window.history.replaceState(
            {},
            document.title,
            window.location.pathname
        );
    }
}

// ======================================
// BACK TO TOP
// ======================================

function setupBackToTop() {
    const backToTop = document.getElementById("backToTop");

    if (!backToTop) return;

    backToTop.addEventListener("click", function (event) {
        event.preventDefault();

        window.scrollTo({
            top: 0,
            left: 0,
            behavior: "smooth"
        });
    });
}

// ======================================
// START URBANFLOW
// ======================================

document.addEventListener("DOMContentLoaded", function () {
    setupNotificationStyles();

    setupIncidentFilters();

    setupReportForm();

    setupBackToTop();

    showReportResult();

    loadIncidents();
});
