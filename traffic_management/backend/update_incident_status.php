
<?php

require_once __DIR__ . "/session_guard.php";

urbanflow_require_login(
    false,
    "login.php?timeout=1"
);

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/db.php";

// ======================================
// ONLY ACCEPT POST REQUESTS
// ======================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Only POST requests are allowed."
    ]);

    exit;
}

// ======================================
// CHECK USER PERMISSIONS
// ======================================

$userRole = strtolower($_SESSION["user_role"] ?? "");

if (!in_array($userRole, ["admin", "officer"], true)) {
    http_response_code(403);

    echo json_encode([
        "success" => false,
        "message" => "You are not authorized to manage incidents."
    ]);

    exit;
}

// ======================================
// GET INCIDENT ID
// ======================================

$incidentId = filter_input(
    INPUT_POST,
    "incident_id",
    FILTER_VALIDATE_INT
);

if (!$incidentId || $incidentId < 1) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "A valid incident ID is required."
    ]);

    exit;
}

// ======================================
// CHECK THAT THE INCIDENT EXISTS
// ======================================

$check = $conn->prepare(
    "SELECT id, status FROM incidents WHERE id = ? LIMIT 1"
);

if (!$check) {
    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Could not prepare the incident lookup."
    ]);

    exit;
}

$check->bind_param("i", $incidentId);
$check->execute();

$result = $check->get_result();
$incident = $result->fetch_assoc();

$check->close();

if (!$incident) {
    http_response_code(404);

    echo json_encode([
        "success" => false,
        "message" => "The requested incident was not found."
    ]);

    exit;
}

// ======================================
// CHECK CURRENT STATUS
// ======================================

if ($incident["status"] === "resolved") {
    http_response_code(409);

    echo json_encode([
        "success" => false,
        "message" => "This incident has already been resolved."
    ]);

    exit;
}

if ($incident["status"] === "investigating") {
    echo json_encode([
        "success" => true,
        "message" => "This incident is already under investigation."
    ]);

    exit;
}

// ======================================
// UPDATE STATUS
// ======================================

$update = $conn->prepare(
    "UPDATE incidents
     SET status = 'investigating'
     WHERE id = ?
     AND status = 'reported'"
);

if (!$update) {
    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Could not prepare the status update."
    ]);

    exit;
}

$update->bind_param("i", $incidentId);

if (!$update->execute()) {
    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "The incident status could not be updated."
    ]);

    $update->close();
    exit;
}

$update->close();

// ======================================
// SUCCESS
// ======================================

echo json_encode([
    "success" => true,
    "message" => "Incident #" . $incidentId .
                 " is now under investigation."
]);

exit;

?>
