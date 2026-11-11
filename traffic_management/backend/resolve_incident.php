
<?php

require_once __DIR__ . "/session_guard.php";

urbanflow_require_login(
    false,
    "login.php?timeout=1"
);

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/db.php";

// Send a consistent JSON response.
function resolveResponse($success, $message, $statusCode = 200) {
    http_response_code($statusCode);

    echo json_encode([
        "success" => $success,
        "message" => $message
    ]);

    exit;
}

// Only accept POST requests.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    resolveResponse(false, "Only POST requests are allowed.", 405);
}

// Only admins and officers can resolve incidents.
$userRole = strtolower($_SESSION["user_role"] ?? "");

if (!in_array($userRole, ["admin", "officer"], true)) {
    resolveResponse(
        false,
        "You do not have permission to resolve incidents.",
        403
    );
}

// Get the logged-in user's ID.
$userId = (int)($_SESSION["user_id"] ?? 0);

if ($userId <= 0) {
    resolveResponse(false, "Your session is invalid. Please log in again.", 401);
}

// Get submitted information.
$incidentId = (int)($_POST["incident_id"] ?? 0);
$resolutionNotes = trim($_POST["resolution_notes"] ?? "");

// Validate incident ID.
if ($incidentId <= 0) {
    resolveResponse(false, "Invalid incident ID.", 400);
}

// Validate resolution notes.
if ($resolutionNotes === "") {
    resolveResponse(false, "Please provide resolution notes.", 400);
}

if (strlen($resolutionNotes) > 10000) {
    resolveResponse(false, "Resolution notes are too long.", 400);
}

// Check whether the incident exists.
$checkSql = "SELECT id, status FROM incidents WHERE id = ? LIMIT 1";

$checkStmt = $conn->prepare($checkSql);

if (!$checkStmt) {
    resolveResponse(false, "Could not check the incident.", 500);
}

$checkStmt->bind_param("i", $incidentId);
$checkStmt->execute();

$result = $checkStmt->get_result();
$incident = $result->fetch_assoc();

$checkStmt->close();

if (!$incident) {
    resolveResponse(false, "Incident not found.", 404);
}

// Prevent resolving an incident twice.
if ($incident["status"] === "resolved") {
    resolveResponse(false, "This incident has already been resolved.", 409);
}

// Save the resolution.
$updateSql = "
    UPDATE incidents
    SET
        status = 'resolved',
        resolved_at = NOW(),
        resolved_by = ?,
        resolution_notes = ?
    WHERE id = ?
";

$updateStmt = $conn->prepare($updateSql);

if (!$updateStmt) {
    resolveResponse(false, "Could not prepare the resolution.", 500);
}

$updateStmt->bind_param(
    "isi",
    $userId,
    $resolutionNotes,
    $incidentId
);

if (!$updateStmt->execute()) {
    $updateStmt->close();

    resolveResponse(
        false,
        "The incident could not be marked as resolved.",
        500
    );
}

$updateStmt->close();

resolveResponse(
    true,
    "Incident #" . $incidentId . " has been successfully resolved."
);

?>
