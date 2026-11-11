
<?php

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/session_guard.php";

// Check the session and refresh activity time.
urbanflow_require_login(true);

echo json_encode([
    "success" => true,
    "message" => "Session active"
]);

?>
