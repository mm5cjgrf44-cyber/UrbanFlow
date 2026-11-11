
<?php
session_start();

// If the user is already logged in, send them to dashboard
if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php");
    exit();
}

// Otherwise, send them to login
header("Location: login.php");
exit();
?>
