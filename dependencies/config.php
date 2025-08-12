<?php
// Define debug mode (set to false in production)
define('DEBUG_MODE', false);

date_default_timezone_set('Asia/Manila');
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "user_database";

$conn = new mysqli($servername, $username, $password, $dbname); // creates a new mysqli object and connects it to the database. $conn is now the db
if ($conn->connect_error) {
    // In production, don't show connection details
    if (DEBUG_MODE) {
        die("Connection failed: " . $conn->connect_error);
    } else {
        die("Database connection failed. Please contact the administrator.");
    }
}

// Set charset to prevent SQL injection
$conn->set_charset("utf8mb4");
?>