<?php
date_default_timezone_set('Asia/Manila');
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "user_database";

$conn = new mysqli($servername, $username, $password, $dbname); // creates a new mysqli object and connects it to the database. $conn is now the db
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>