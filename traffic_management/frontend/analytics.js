
// ======================================
// URBANFLOW ANALYTICS
// PREMIUM DASHBOARD VERSION
// ======================================

const ANALYTICS_URL = "../backend/get_analytics.php";

async function loadAnalytics() {

    const container = document.getElementById("analyticsContent");

    if (!container) return;

    // Loading animation
    container.innerHTML = `
        <div class="analytics-loading">
            <span class="analytics-loader"></span>
            <p>Preparing your UrbanFlow insights...</p>
        </div>
    `;

    try {

        const response = await fetch(ANALYTICS_URL, {
            method: "GET",
            credentials: "same-origin",
            cache: "no-store"
        });

        if (response.status === 401) {
            window.location.href = "../backend/login.php?timeout=1";
            return;
        }

        const data = await response.json();

        if (!response.ok || data.success !== true) {
            throw new Error(
                data.message || "Analytics could not be loaded."
            );
        }

        const summary = data.summary || {};

        // Read statistics safely
        const total = Number(summary.total) || 0;
        const active = Number(summary.active) || 0;
        const resolved = Number(summary.resolved) || 0;
        const critical = Number(summary.critical) || 0;
        const high = Number(summary.high_severity) || 0;

        // ======================================
        // STATISTIC CARDS
        // ======================================

        const cards = [

            {
                label: "Total incidents",
                value: total,
                icon: "▦",
                tone: "total",
                note: "All reported incidents"
            },

            {
                label: "Active incidents",
                value: active,
                icon: "◷",
                tone: "active",
                note: "Awaiting resolution"
            },

            {
                label: "Resolved incidents",
                value: resolved,
                icon: "✓",
                tone: "resolved",
                note: "Successfully addressed"
            },

            {
                label: "Critical incidents",
                value: critical,
                icon: "!",
                tone: "critical",
                note: "Require urgent attention"
            },

            {
                label: "High severity",
                value: high,
                icon: "▲",
                tone: "high",
                note: "High-priority incidents"
            }

        ];

        // ======================================
        // DISPLAY ANALYTICS
        // ======================================

        container.innerHTML = `

            <div class="analytics-overview">

                <div>

                    <span class="analytics-kicker">
                        LIVE SYSTEM OVERVIEW
                    </span>

                    <h3 class="analytics-overview-title">
                        Traffic at a glance
                    </h3>

                    <p class="analytics-overview-copy">
                        A clear view of reported incidents and their current status.
                    </p>

                </div>

                <span class="analytics-live">
                    <i></i>
                    Analytics loaded
                </span>

            </div>


            <!-- STATISTIC CARDS -->

            <div class="analytics-cards">

                ${cards.map(card => `

                    <article class="analytics-card analytics-card-${card.tone}">

                        <div class="analytics-card-top">

                            <span class="analytics-card-label">
                                ${card.label}
                            </span>

                            <span class="analytics-card-icon">
                                ${card.icon}
                            </span>

                        </div>

                        <strong class="analytics-card-number">
                            ${card.value}
                        </strong>

                        <span class="analytics-card-note">
                            ${card.note}
                        </span>

                        <span class="analytics-card-accent"></span>

                    </article>

                `).join("")}

            </div>


            <!-- CHART SECTION HEADING -->

            <div class="analytics-section-heading">

                <div>

                    <span class="analytics-kicker">
                        INCIDENT BREAKDOWN
                    </span>

                    <h3>
                        Understand the patterns
                    </h3>

                </div>

                <p>
                    Compare incident volume across key categories.
                </p>

            </div>


            <!-- ANALYTICS CHARTS -->

            <div class="analytics-grid">


                <!-- INCIDENT TYPES -->

                <section class="analytics-panel">

                    <div class="analytics-panel-heading">

                        <span class="analytics-panel-icon">
                            ⌁
                        </span>

                        <div>

                            <h3>
                                Incidents by type
                            </h3>

                            <p>
                                Which incidents are reported most?
                            </p>

                        </div>

                    </div>

                    <div id="typeChart" class="analytics-chart"></div>

                </section>


                <!-- LOCATIONS -->

                <section class="analytics-panel">

                    <div class="analytics-panel-heading">

                        <span class="analytics-panel-icon">
                            ⌖
                        </span>

                        <div>

                            <h3>
                                Most affected locations
                            </h3>

                            <p>
                                Where incidents are concentrated.
                            </p>

                        </div>

                    </div>

                    <div id="locationChart" class="analytics-chart"></div>

                </section>


                <!-- STATUS -->

                <section class="analytics-panel">

                    <div class="analytics-panel-heading">

                        <span class="analytics-panel-icon">
                            ◉
                        </span>

                        <div>

                            <h3>
                                Incidents by status
                            </h3>

                            <p>
                                Track progress towards resolution.
                            </p>

                        </div>

                    </div>

                    <div id="statusChart" class="analytics-chart"></div>

                </section>

            </div>

        `;


        // ======================================
        // DRAW CHARTS
        // ======================================

        renderAnalyticsBars(
            "typeChart",
            data.types || [],
            "type"
        );

        renderAnalyticsBars(
            "locationChart",
            data.locations || [],
            "location"
        );

        renderAnalyticsBars(
            "statusChart",
            data.statuses || [],
            "status"
        );


    } catch (error) {

        console.error(
            "UrbanFlow Analytics Error:",
            error
        );

        container.innerHTML = `

            <div class="analytics-error">

                <strong>
                    Analytics could not be displayed
                </strong>

                <p>
                    ${escapeAnalyticsText(error.message)}
                </p>

                <button
                    type="button"
                    onclick="loadAnalytics()">

                    Try again

                </button>

            </div>

        `;

    }

}


// ======================================
// DRAW ANALYTICS BARS
// ======================================

function renderAnalyticsBars(elementId, items, labelKey) {

    const chart = document.getElementById(elementId);

    if (!chart) return;

    if (!Array.isArray(items) || items.length === 0) {

        chart.innerHTML = `

            <div class="analytics-empty">

                <span>—</span>

                <p>
                    No incident data available yet.
                </p>

            </div>

        `;

        return;
    }

    const maximum = Math.max(
        ...items.map(item => Number(item.total) || 0),
        1
    );


    chart.innerHTML = items.map((item, index) => {

        const value = Number(item.total) || 0;

        const percentage = Math.max(
            0,
            Math.min(100, (value / maximum) * 100)
        );

        const label = escapeAnalyticsText(
            item[labelKey]
        );


        return `

            <div class="analytics-bar-item">

                <div class="analytics-bar-heading">

                    <span>
                        ${label}
                    </span>

                    <strong>
                        ${value.toLocaleString()}
                    </strong>

                </div>


                <div class="analytics-bar-track">

                    <div
                        class="analytics-bar-fill"
                        style="
                            --bar-width: ${percentage}%;
                            --bar-delay: ${index * 90}ms;
                        ">
                    </div>

                </div>

            </div>

        `;

    }).join("");

}


// ======================================
// SAFE TEXT DISPLAY
// ======================================

function escapeAnalyticsText(value) {

    const element = document.createElement("span");

    element.textContent = value ?? "Unknown";

    return element.innerHTML;

}


// ======================================
// START ANALYTICS
// ======================================

document.addEventListener(
    "DOMContentLoaded",
    function () {

        loadAnalytics();

    }
);
