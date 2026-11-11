
<?php

require_once __DIR__ . "/session_guard.php";

urbanflow_require_login(
    false,
    "login.php?timeout=1"
);

require_once __DIR__ . "/db.php";

// Only accept POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../frontend/dashboard.php");
    exit;
}

// Get submitted information
$title = trim($_POST["title"] ?? "");
$type = trim($_POST["type"] ?? "");
$location = trim($_POST["location"] ?? "");
$severity = strtolower(trim($_POST["severity"] ?? ""));
$description = trim($_POST["description"] ?? "");

// Validate required fields
if (
    $title === "" ||
    $type === "" ||
    $location === "" ||
    $severity === "" ||
    $description === ""
) {
    header("Location: ../frontend/dashboard.php?report=error");
    exit;
}

// Validate severity
$allowedSeverity = ["low", "medium", "high", "critical"];

if (!in_array($severity, $allowedSeverity, true)) {
    header("Location: ../frontend/dashboard.php?report=error");
    exit;
}

// Insert incident
$sql = "INSERT INTO incidents
        (title, type, location, severity, description)
        VALUES (?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    header("Location: ../frontend/dashboard.php?report=error");
    exit;
}

$stmt->bind_param(
    "sssss",
    $title,
    $type,
    $location,
    $severity,
    $description
);

if ($stmt->execute()) {

    $stmt->close();
    $conn->close();

    header("Location: ../frontend/dashboard.php?report=success");
    exit;

} else {

    $stmt->close();
    $conn->close();

    header("Location: ../frontend/dashboard.php?report=error");
    exit;
}

?>
