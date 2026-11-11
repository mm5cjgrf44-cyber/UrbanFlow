
<?php

require_once __DIR__ . "/../backend/session_guard.php";

urbanflow_require_login(
    false,
    "../backend/login.php?timeout=1"
);

$userName = htmlspecialchars(
    $_SESSION["user_name"] ?? "User",
    ENT_QUOTES,
    "UTF-8"
);

$userRole = htmlspecialchars(
    $_SESSION["user_role"] ?? "reporter",
    ENT_QUOTES,
    "UTF-8"
);

$isAdmin = (($_SESSION["user_role"] ?? "") === "admin");

$canManageIncidents = in_array(
    strtolower($_SESSION["user_role"] ?? ""),
    ["admin", "officer"],
    true
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#183b32">

    <meta
        name="description"
        content="UrbanFlow - Smart traffic incident reporting and mobility management."
    >

    <title>UrbanFlow | Traffic Intelligence</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Manrope:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="/traffic_management/frontend/style.css?v=100">

    <style>

        /* =====================================
           INCIDENT MANAGEMENT INTERFACE
        ====================================== */

        .incident-overview-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
            margin: 30px 0;
        }

        .incident-overview-card {
            position: relative;
            padding: 25px;
            border: 1px solid rgba(40, 91, 73, .12);
            border-radius: 19px;
            background: linear-gradient(145deg, #ffffff, #f5f7f2);
            box-shadow: 0 12px 35px rgba(24, 59, 50, .045);
            overflow: hidden;
            transition: transform .25s ease, box-shadow .25s ease;
        }

        .incident-overview-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 18px 40px rgba(24, 59, 50, .09);
        }

        .incident-overview-card::after {
            content: "";
            position: absolute;
            width: 90px;
            height: 90px;
            right: -35px;
            bottom: -40px;
            border-radius: 50%;
            background: rgba(61, 118, 95, .08);
        }

        .incident-overview-label {
            display: block;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1.2px;
            color: #84918a;
        }

        .incident-overview-card strong {
            display: block;
            margin: 13px 0 5px;
            font-family: Manrope, sans-serif;
            font-size: 34px;
            color: #183b32;
        }

        .incident-overview-card small {
            font-size: 11px;
            color: #84918a;
        }

        .incident-management-toolbar,
        .resolved-archive-toolbar {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin: 25px 0;
        }

        .incident-management-toolbar .search-box,
        .resolved-archive-toolbar .search-box {
            flex: 1 1 240px;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 17px;
            min-height: 48px;
            border: 1px solid #e1e8e1;
            border-radius: 12px;
            background: #fff;
        }

        .incident-management-toolbar .search-box input,
        .resolved-archive-toolbar .search-box input {
            width: 100%;
            border: none;
            outline: none;
            background: transparent;
            font: inherit;
            font-size: 13px;
        }

        .incident-management-toolbar select {
            min-height: 48px;
            min-width: 145px;
            padding: 0 13px;
            border: 1px solid #e1e8e1;
            border-radius: 12px;
            background: #fff;
            color: #35483f;
            font: inherit;
            font-size: 12px;
            cursor: pointer;
        }

        .incident-list-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin: 35px 0 18px;
        }

        .incident-list-heading h3 {
            margin: 0 0 5px;
            color: #183b32;
            font-family: Manrope, sans-serif;
            font-size: 20px;
        }

        .incident-list-heading p {
            margin: 0;
            color: #84918a;
            font-size: 12px;
        }

        #incident-message {
            color: #84918a;
            font-size: 11px;
        }

        .incident-management-list,
        .resolved-incident-list {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .incident-item {
            position: relative;
            padding: 24px;
            border: 1px solid #e5ebe5;
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 10px 30px rgba(24, 59, 50, .045);
            transition: transform .25s ease, box-shadow .25s ease;
            min-width: 0;
        }

        .incident-item:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 38px rgba(24, 59, 50, .09);
        }

        .incident-card-title {
            margin: 12px 0;
            color: #183b32;
            font-family: Manrope, sans-serif;
            font-size: 19px;
            overflow-wrap: anywhere;
        }

        .incident-reference,
        .incident-date {
            color: #84918a;
            font-size: 11px;
        }

        .incident-detail {
            margin: 7px 0;
            color: #53645a;
            font-size: 12px;
        }

        .incident-description {
            margin: 16px 0;
            padding: 13px;
            border-radius: 10px;
            background: #f6f8f4;
            color: #53645a;
            font-size: 12px;
            line-height: 1.7;
            overflow-wrap: anywhere;
        }

        .incident-status {
            display: inline-flex;
            align-items: center;
            padding: 7px 11px;
            border-radius: 30px;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: .7px;
        }

        .status-reported {
            background: #fff4df;
            color: #a36b17;
        }

        .status-investigating {
            background: #e8efff;
            color: #4264a6;
        }

        .status-resolved {
            background: #e4f3e8;
            color: #286b43;
        }

        .incident-card-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 9px;
            margin-top: 17px;
            padding-top: 16px;
            border-top: 1px solid #edf0eb;
        }

        .resolve-incident-button,
        .investigate-incident-button {
            padding: 11px 15px;
            border: none;
            border-radius: 9px;
            background: #183b32;
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            transition: background .2s ease, transform .2s ease;
        }

        .investigate-incident-button {
            background: #eaf0eb;
            color: #285b49;
        }

        .resolve-incident-button:hover,
        .investigate-incident-button:hover {
            transform: translateY(-2px);
        }

        .resolve-incident-button:hover {
            background: #285b49;
        }

        .investigate-incident-button:hover {
            background: #dce8de;
        }

        .incident-card-actions button:disabled {
            opacity: .55;
            cursor: wait;
        }

        .resolved-archive-intro {
            display: flex;
            align-items: center;
            gap: 17px;
            padding: 22px;
            border: 1px solid #dceade;
            border-radius: 16px;
            background: #f1f7f0;
        }

        .resolved-archive-icon {
            width: 44px;
            height: 44px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            border-radius: 50%;
            background: #dceede;
            color: #286b43;
            font-size: 22px;
        }

        .resolved-archive-intro strong {
            color: #183b32;
            font-size: 14px;
        }

        .resolved-archive-intro p {
            margin: 6px 0 0;
            color: #718176;
            font-size: 11px;
            line-height: 1.7;
        }

        .resolved-archive-toolbar {
            justify-content: space-between;
        }

        #resolvedIncidentCount {
            color: #84918a;
            font-size: 11px;
        }

        .incident-resolution-date,
        .incident-resolved-by,
        .incident-resolution-notes {
            margin: 9px 0;
            color: #53645a;
            font-size: 11px;
            line-height: 1.7;
        }

        .incident-resolution-notes {
            padding: 13px;
            border-left: 3px solid #3d765f;
            border-radius: 5px;
            background: #f1f7f0;
            overflow-wrap: anywhere;
        }

        .empty-state {
            grid-column: 1 / -1;
            padding: 35px 20px;
            border: 1px dashed #d9e3d9;
            border-radius: 15px;
            background: #fafbf8;
            text-align: center;
            color: #84918a;
            font-size: 13px;
        }

        @media(max-width: 900px) {
            .incident-overview-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .incident-management-list,
            .resolved-incident-list {
                grid-template-columns: 1fr;
            }
        }

        @media(max-width: 600px) {
            .incident-overview-grid {
                gap: 10px;
            }

            .incident-overview-card {
                padding: 16px;
            }

            .incident-overview-card strong {
                font-size: 27px;
            }

            .incident-management-toolbar {
                align-items: stretch;
            }

            .incident-management-toolbar select {
                flex: 1 1 100%;
                width: 100%;
            }

            .incident-list-heading {
                align-items: flex-start;
                flex-direction: column;
            }
        }

    </style>

</head>


<body
    class="urbanflow-dashboard"
    data-user-role="<?php echo $userRole; ?>"
>

<header class="topbar" id="navigation">

    <a href="#overview" class="brand">
        <span class="brand-icon">↗</span>
        <span>URBAN<span class="brand-light">FLOW</span></span>
    </a>

    <nav class="main-navigation">

        <a href="#overview" class="active">Overview</a>

        <?php if ($isAdmin): ?>
            <a href="#analytics">Analytics</a>
        <?php endif; ?>

        <a href="#report">Report Incident</a>
        <a href="#incidents">Incidents</a>
        <a href="#resolved-cases">Resolved Cases</a>

    </nav>

    <div class="nav-user">

        <span class="nav-user-indicator"></span>

        <span class="nav-user-name">
            <?php echo $userName; ?>
        </span>

        <a href="../backend/logout.php" class="top-button">
            Logout <span>↗</span>
        </a>

    </div>

</header>


<main>

    <!-- HERO -->

    <section class="hero hero-premium" id="overview">

        <div class="hero-content">

            <span class="eyebrow">
                <span class="live-dot"></span>
                NAIROBI URBAN MOBILITY INTELLIGENCE
            </span>

            <p class="hero-greeting">
                YOUR URBANFLOW CONTROL CENTRE
            </p>

            <h1>
                Welcome back,
                <span><?php echo $userName; ?>.</span>
            </h1>

            <p class="hero-description">
                A smarter way to understand traffic,
                report road incidents and contribute
                to safer movement across Nairobi.
            </p>

            <div class="hero-actions">

                <a href="#report" class="primary-button">
                    Report an incident <span>↗</span>
                </a>

                <a href="#incidents" class="secondary-button">
                    Explore incidents <span>↓</span>
                </a>

            </div>

            <div class="hero-user-meta">

                <span class="hero-user-avatar">
                    <?php echo strtoupper(substr($userName, 0, 1)); ?>
                </span>

                <div>
                    <strong><?php echo $userName; ?></strong>
                    <small><?php echo ucfirst($userRole); ?> account</small>
                </div>

            </div>

        </div>


        <div class="hero-visual hero-3d-visual">

            <div class="hero-visual-image"></div>
            <div class="hero-visual-glow"></div>

            <canvas
                id="hero-3d-canvas"
                aria-label="Animated three-dimensional UrbanFlow visual"
            ></canvas>

            <div class="hero-visual-overlay"></div>

            <div class="floating-card card-top hero-floating-card">

                <span class="status-dot"></span>

                <div>
                    <strong>Urban Mobility</strong>
                    <small>Nairobi, Kenya</small>
                </div>

            </div>

            <div class="floating-card card-bottom hero-floating-card">

                <span class="traffic-icon">⌁</span>

                <div>
                    <strong>Smarter movement.</strong>
                    <small>Better roads. Safer journeys.</small>
                </div>

            </div>

            <div class="visual-caption">
                <span>01 / CONNECTED CITY</span>
                <span>NAIROBI · KENYA</span>
            </div>

        </div>

    </section>


    <!-- QUICK ACCESS -->

    <section class="quick-access-section">

        <div class="quick-access-item">
            <span class="quick-access-icon">↗</span>
            <div>
                <strong>Report</strong>
                <small>Submit a road incident</small>
            </div>
            <a href="#report" aria-label="Report an incident">→</a>
        </div>

        <div class="quick-access-item">
            <span class="quick-access-icon">⌕</span>
            <div>
                <strong>Explore</strong>
                <small>Browse incident reports</small>
            </div>
            <a href="#incidents" aria-label="Explore incidents">→</a>
        </div>

        <?php if ($isAdmin): ?>

            <div class="quick-access-item">
                <span class="quick-access-icon">◷</span>
                <div>
                    <strong>Insights</strong>
                    <small>Review system analytics</small>
                </div>
                <a href="#analytics" aria-label="View analytics">→</a>
            </div>

        <?php endif; ?>

    </section>


    <!-- ANALYTICS -->

    <?php if ($isAdmin): ?>

        <section class="analytics-section" id="analytics">

            <div class="section-heading analytics-main-heading">

                <div>
                    <span class="eyebrow">01 / SYSTEM INTELLIGENCE</span>
                    <h2>UrbanFlow Analytics.</h2>
                    <p>
                        Understand traffic patterns through
                        real-time incident data and visual insights.
                    </p>
                </div>

                <button
                    type="button"
                    class="analytics-refresh-button"
                    onclick="loadAnalytics()"
                >
                    <span>↻</span>
                    Refresh insights
                </button>

            </div>

            <div id="analyticsContent">

                <div class="analytics-loading">
                    <span class="analytics-loader"></span>
                    <p>Preparing your UrbanFlow insights...</p>
                </div>

            </div>

        </section>

    <?php endif; ?>


    <!-- 3D VISUALIZATION -->

    <section
        class="incident-3d-section"
        id="incident-visualization"
    >

        <div class="incident-3d-heading">

            <div>
                <span class="eyebrow">02 / SPATIAL INTELLIGENCE</span>
                <h2>Traffic, in another dimension.</h2>
                <p>
                    Explore an interactive three-dimensional
                    visualization of the UrbanFlow monitoring environment.
                </p>
            </div>

            <span class="incident-live-indicator">
                <i></i>
                MONITORING EXPERIENCE
            </span>

        </div>

        <div class="incident-3d-card">

            <div id="incident-3d-viewer">

                <div class="incident-3d-loading">
                    <span class="analytics-loader"></span>
                    <p>Preparing spatial visualization...</p>
                </div>

            </div>

            <div class="incident-3d-footer">
                <span>URBANFLOW SPATIAL MONITOR</span>
                <span>INTERACTIVE 3D ENVIRONMENT</span>
            </div>

        </div>

    </section>


    <!-- INTRODUCTION -->

    <section class="intro">

        <div>
            <span class="eyebrow">THE URBANFLOW APPROACH</span>
            <h2>Better information.<br>Better movement.</h2>
        </div>

        <p>
            UrbanFlow brings traffic incident reporting
            and mobility information into one central platform.
            By making road-related information easier to report
            and explore, we can contribute to a safer and more
            connected Nairobi.
        </p>

    </section>


    <!-- REPORT INCIDENT -->

    <section id="report" class="content-section">

        <div class="section-heading">

            <div>
                <span class="eyebrow">03 / INCIDENT REPORTING</span>
                <h2>Make a report.</h2>
                <p>
                    Help us understand what's happening on Nairobi's roads.
                </p>
            </div>

            <span class="section-number">01</span>

        </div>

        <div class="form-layout">

            <div class="form-info">

                <span class="info-symbol">↗</span>
                <span class="form-info-label">YOUR REPORT MATTERS</span>

                <h3>Help us keep Nairobi moving.</h3>

                <p>
                    Report accidents, road closures, traffic congestion,
                    road damage and other road-related incidents.
                </p>

                <div class="info-divider"></div>

                <div class="info-point">
                    <span>01</span>
                    <p>Provide the incident location.</p>
                </div>

                <div class="info-point">
                    <span>02</span>
                    <p>Select the correct severity.</p>
                </div>

                <div class="info-point">
                    <span>03</span>
                    <p>Describe what happened.</p>
                </div>

                <div class="form-info-note">
                    <span>✦</span>
                    Accurate reports support better traffic awareness.
                </div>

            </div>


            <div class="form-card">

                <div class="form-card-heading">
                    <span class="form-card-kicker">NEW SUBMISSION</span>
                    <h3>Incident details</h3>
                    <p>Complete the fields below to submit your report.</p>
                </div>

                <form
                    id="incidentForm"
                    method="POST"
                    action="../backend/report.php"
                >

                    <div class="form-group">

                        <label for="title">Incident Title</label>

                        <input
                            type="text"
                            id="title"
                            name="title"
                            placeholder="e.g. Accident along Thika Road"
                            maxlength="150"
                            required
                        >

                    </div>


                    <div class="form-row">

                        <div class="form-group">

                            <label for="type">Incident Type</label>

                            <select id="type" name="type" required>

                                <option value="">Select type</option>
                                <option value="Accident">Accident</option>
                                <option value="Traffic Congestion">Traffic Congestion</option>
                                <option value="Road Closure">Road Closure</option>
                                <option value="Road Damage">Road Damage</option>
                                <option value="Other">Other</option>

                            </select>

                        </div>


                        <div class="form-group">

                            <label for="severity">Severity</label>

                            <select id="severity" name="severity" required>

                                <option value="">Select severity</option>
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>

                            </select>

                        </div>

                    </div>


                    <div class="form-group">

                        <label for="location">Incident Location</label>

                        <input
                            type="text"
                            id="location"
                            name="location"
                            placeholder="Enter road, area or landmark"
                            maxlength="255"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="description">Incident Description</label>

                        <textarea
                            id="description"
                            name="description"
                            placeholder="Tell us what happened..."
                            rows="5"
                            required
                        ></textarea>

                    </div>


                    <button type="submit" class="submit-button">
                        Submit incident report <span>↗</span>
                    </button>

                    <p class="form-footnote">
                        Please ensure the information provided is accurate.
                    </p>

                </form>

            </div>

        </div>

    </section>


    <!-- ACTIVE INCIDENT MANAGEMENT -->

    <section
        id="incidents"
        class="content-section incidents-section"
    >

        <div class="section-heading">

            <div>
                <span class="eyebrow">04 / INCIDENT MANAGEMENT</span>
                <h2>Incident command centre.</h2>
                <p>
                    Monitor reported incidents, track investigations
                    and manage ongoing road activity.
                </p>
            </div>

            <span class="section-number">02</span>

        </div>


        <div class="incident-overview-grid">

            <div class="incident-overview-card">
                <span class="incident-overview-label">ACTIVE INCIDENTS</span>
                <strong id="activeIncidentCount">—</strong>
                <small>Currently requiring attention</small>
            </div>

            <div class="incident-overview-card">
                <span class="incident-overview-label">REPORTED</span>
                <strong id="reportedIncidentCount">—</strong>
                <small>Awaiting investigation</small>
            </div>

            <div class="incident-overview-card">
                <span class="incident-overview-label">INVESTIGATING</span>
                <strong id="investigatingIncidentCount">—</strong>
                <small>Under active management</small>
            </div>

            <div class="incident-overview-card">
                <span class="incident-overview-label">CRITICAL</span>
                <strong id="criticalIncidentCount">—</strong>
                <small>High-priority incidents</small>
            </div>

        </div>


        <div class="incident-management-toolbar">

            <div class="search-box">
                <span>⌕</span>
                <input
                    type="search"
                    id="searchInput"
                    placeholder="Search incidents, locations or references..."
                    autocomplete="off"
                >
            </div>

            <select id="statusFilter" aria-label="Filter by status">
                <option value="all">All active statuses</option>
                <option value="reported">Reported</option>
                <option value="investigating">Investigating</option>
            </select>

            <select id="severityFilter" aria-label="Filter by severity">
                <option value="all">All severity levels</option>
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
                <option value="critical">Critical</option>
            </select>

            <select id="typeFilter" aria-label="Filter by incident type">
                <option value="all">All incident types</option>
                <option value="Accident">Accidents</option>
                <option value="Traffic Congestion">Traffic Congestion</option>
                <option value="Road Closure">Road Closures</option>
                <option value="Road Damage">Road Damage</option>
                <option value="Other">Other</option>
            </select>

        </div>


        <div class="incident-list-heading">

            <div>
                <h3>Ongoing road activity</h3>
                <p>Incidents that have not yet been resolved.</p>
            </div>

            <span id="incident-message">
                Loading traffic incidents...
            </span>

        </div>


        <div id="incidentList" class="incident-management-list">
            <p>Loading active incidents...</p>
        </div>

    </section>


    <!-- RESOLVED CASES -->

    <section
        id="resolved-cases"
        class="content-section resolved-cases-section"
    >

        <div class="section-heading">

            <div>
                <span class="eyebrow">05 / INCIDENT RESOLUTION</span>
                <h2>Resolved cases.</h2>
                <p>
                    A permanent record of incidents successfully
                    handled through UrbanFlow.
                </p>
            </div>

            <span class="section-number">03</span>

        </div>


        <div class="resolved-archive-intro">

            <div class="resolved-archive-icon">✓</div>

            <div>
                <strong>Resolution and accountability archive</strong>
                <p>
                    Review completed incidents, resolution dates,
                    responsible officers and actions taken.
                </p>
            </div>

        </div>


        <div class="resolved-archive-toolbar">

            <div class="search-box">
                <span>⌕</span>
                <input
                    type="search"
                    id="resolvedSearchInput"
                    placeholder="Search resolved incidents..."
                    autocomplete="off"
                >
            </div>

            <span id="resolvedIncidentCount">
                Loading archive...
            </span>

        </div>


        <div
            id="resolvedIncidentList"
            class="resolved-incident-list"
        >
            <p>Loading resolved cases...</p>
        </div>

    </section>


    <!-- FOOTER -->

    <footer class="urbanflow-footer">

        <div class="footer-top">

            <a href="#overview" class="brand footer-brand">
                <span class="brand-icon">↗</span>
                <span>URBAN<span class="brand-light">FLOW</span></span>
            </a>

            <p>Building a smarter, safer and more connected Nairobi.</p>

            <a href="#overview" id="backToTop">
                Back to Top ↑
            </a>

        </div>


        <div class="footer-bottom">

            <span>© 2026 UrbanFlow Traffic Management System</span>

            <span>NAIROBI, KENYA · BUILT FOR BETTER MOBILITY</span>

        </div>

    </footer>

</main>


<!-- JAVASCRIPT -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script src="/traffic_management/frontend/script.js?v=100"></script>

<script src="session-timeout.js?v=1"></script>

<?php if ($isAdmin): ?>
<script src="analytics.js?v=2"></script>
<?php endif; ?>

<script src="incident-3d.js?v=1"></script>

</body>
</html>
