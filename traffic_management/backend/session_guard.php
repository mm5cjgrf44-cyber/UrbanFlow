
<?php

// ======================================
// URBANFLOW SESSION SECURITY
// ======================================

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// ======================================
// CHECK LOGIN AND SESSION TIMEOUT
// ======================================

function urbanflow_require_login(
    bool $is_api = false,
    string $login_url = "../backend/login.php?timeout=1"
): void {

    $timeout = 900; // 15 minutes

    // Check login
    if (!isset($_SESSION["user_id"])) {
        urbanflow_end_session($is_api, $login_url);
    }

    // Check inactivity
    $last_activity = $_SESSION["last_activity"] ?? time();

    if (time() - $last_activity >= $timeout) {
        urbanflow_end_session($is_api, $login_url);
    }

    // Update activity
    $_SESSION["last_activity"] = time();
}

// ======================================
// END SESSION
// ======================================

function urbanflow_end_session(
    bool $is_api,
    string $login_url
): void {

    $_SESSION = [];

    if (ini_get("session.use_cookies")) {

        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            "",
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }

    if ($is_api) {

        http_response_code(401);

        header("Content-Type: application/json; charset=UTF-8");

        echo json_encode([
            "success" => false,
            "message" => "Your session has expired. Please log in again."
        ]);

        exit;
    }

    header("Location: " . $login_url);
    exit;
}

?>
