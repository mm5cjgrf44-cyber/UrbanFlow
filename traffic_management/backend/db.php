<?php

$host = "localhost";
$username = "root";
$password = "";
$database = "traffic_management";

// Connect to MySQL
$conn = new mysqli(
    $host,
    $username,
    $password,
    $database
);

// Check the connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Support text in different languages
$conn->set_charset("utf8mb4");

?>