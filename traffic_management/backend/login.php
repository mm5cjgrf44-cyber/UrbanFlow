
<?php

require_once __DIR__ . "/db.php";

// Start session
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$message = "";

// If already logged in, check session validity
if (isset($_SESSION["user_id"])) {

    $last_activity = $_SESSION["last_activity"] ?? time();

    if (time() - $last_activity < 900) {
        header("Location: ../frontend/dashboard.php");
        exit();
    }

    // Expired session: clear it
    $_SESSION = [];

    session_destroy();

    session_start();
}

// Registration success message
if (isset($_GET["registered"])) {
    $message = "Registration successful! Please log in.";
}

// Session timeout message
if (isset($_GET["timeout"])) {
    $message = "Your session expired due to inactivity. Please log in again.";
}

// Handle login
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($email) || empty($password)) {

        $message = "Please enter your email and password.";

    } else {

        $stmt = $conn->prepare(
            "SELECT id, full_name, email, password, role
             FROM users
             WHERE email = ?"
        );

        if (!$stmt) {
            die("Database query error: " . $conn->error);
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $stmt->bind_result(
            $id,
            $full_name,
            $user_email,
            $hashed_password,
            $role
        );

        if ($stmt->fetch()) {

            if (password_verify($password, $hashed_password)) {

                // Create a fresh session ID
                session_regenerate_id(true);

                // Store user details
                $_SESSION["user_id"] = $id;
                $_SESSION["user_name"] = $full_name;
                $_SESSION["user_email"] = $user_email;
                $_SESSION["user_role"] = $role;

                // Start the inactivity timer
                $_SESSION["last_activity"] = time();

                $stmt->close();

                header("Location: ../frontend/dashboard.php");
                exit();

            } else {
                $message = "Incorrect email or password.";
            }

        } else {
            $message = "Incorrect email or password.";
        }

        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login | UrbanFlow</title>

    <link rel="stylesheet" href="/traffic_management/frontend/style.css?v=4">
</head>

<body>

    <main class="login-container">

        <div class="login-card">

            <h1>UrbanFlow</h1>

            <p>Traffic Management System</p>

            <h2>Welcome Back</h2>

            <p>Log in to your account to continue.</p>

            <?php if (!empty($message)): ?>

                <p class="form-message">
                    <?php echo htmlspecialchars($message); ?>
                </p>

            <?php endif; ?>

            <form method="POST" action="login.php">

                <label for="email">Email Address</label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Enter your email"
                    required
                >

                <label for="password">Password</label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    required
                >

                <button type="submit">Login</button>

            </form>

            <p>
                Don't have an account?
                <a href="register.php">Register here</a>
            </p>

        </div>

    </main>

</body>
</html>
