
<?php

ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/session_guard.php";
urbanflow_require_login(true);

require_once __DIR__ . "/db.php";

if (!isset($conn) || $conn->connect_error) {
    die(json_encode([
        "success" => false,
        "message" => "Database connection failed."
    ]));
}

$summarySQL = "
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN LOWER(status) IN ('resolved', 'closed') THEN 1 ELSE 0 END) AS resolved,
        SUM(CASE WHEN LOWER(severity) = 'critical' THEN 1 ELSE 0 END) AS critical,
        SUM(CASE WHEN LOWER(severity) = 'high' THEN 1 ELSE 0 END) AS high_severity
    FROM incidents
";

$summaryResult = $conn->query($summarySQL);

if (!$summaryResult) {
    die(json_encode([
        "success" => false,
        "message" => $conn->error
    ]));
}

$summary = $summaryResult->fetch_assoc();

$total = (int) $summary["total"];
$resolved = (int) $summary["resolved"];

$summary["total"] = $total;
$summary["resolved"] = $resolved;
$summary["active"] = $total - $resolved;
$summary["critical"] = (int) $summary["critical"];
$summary["high_severity"] = (int) $summary["high_severity"];

$types = [];
$typeResult = $conn->query("
    SELECT type, COUNT(*) AS total
    FROM incidents
    GROUP BY type
    ORDER BY total DESC
");

if (!$typeResult) {
    die(json_encode(["success" => false, "message" => $conn->error]));
}

while ($row = $typeResult->fetch_assoc()) {
    $types[] = $row;
}

$locations = [];
$locationResult = $conn->query("
    SELECT location, COUNT(*) AS total
    FROM incidents
    GROUP BY location
    ORDER BY total DESC
    LIMIT 5
");

if (!$locationResult) {
    die(json_encode(["success" => false, "message" => $conn->error]));
}

while ($row = $locationResult->fetch_assoc()) {
    $locations[] = $row;
}

$statuses = [];
$statusResult = $conn->query("
    SELECT status, COUNT(*) AS total
    FROM incidents
    GROUP BY status
    ORDER BY total DESC
");

if (!$statusResult) {
    die(json_encode(["success" => false, "message" => $conn->error]));
}

while ($row = $statusResult->fetch_assoc()) {
    $statuses[] = $row;
}

echo json_encode([
    "success" => true,
    "summary" => $summary,
    "types" => $types,
    "locations" => $locations,
    "statuses" => $statuses
]);

$conn->close();

?>

