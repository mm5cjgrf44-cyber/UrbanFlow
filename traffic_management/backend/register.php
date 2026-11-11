
<?php

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/session_guard.php";
urbanflow_require_login(true);

require_once __DIR__ . "/db.php";

if (!$conn || $conn->connect_error) {
    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Database connection failed."
    ]);

    exit;
}

$sql = "
    SELECT
        i.id,
        i.title,
        i.type,
        i.location,
        i.severity,
        i.description,
        i.status,
        i.reported_by,
        i.created_at,
        i.updated_at,
        i.resolved_at,
        i.resolved_by,
        i.resolution_notes,
        u.full_name AS resolved_by_name

    FROM incidents i

    LEFT JOIN users u
        ON i.resolved_by = u.id

    ORDER BY
        CASE WHEN i.status = 'resolved' THEN 1 ELSE 0 END,
        i.created_at DESC
";

$result = $conn->query($sql);

if (!$result) {
    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Incident query failed."
    ]);

    exit;
}

$incidents = [];

while ($row = $result->fetch_assoc()) {
    $incidents[] = $row;
}

echo json_encode([
    "success" => true,
    "count" => count($incidents),
    "incidents" => $incidents
]);

$conn->close();

?>
