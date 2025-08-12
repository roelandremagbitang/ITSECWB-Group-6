<?php
include 'dependencies/config.php';
include 'dependencies/auth.php';

require_login(); // user has to be logged in to access this page
require_role(['owner', 'admin', 'customer']); // both owner and admin has access to this page

if (!isset($_SESSION['email'])) {
    echo "Not authorized.";
    exit();
}

$email = $_SESSION['email'];

$stmt = $conn->prepare("DELETE FROM users WHERE email = ?");
$stmt->bind_param("s", $email);

if ($stmt->execute()) {
    session_unset();
    session_destroy();
    echo "Account deleted successfully.";
} else {
    echo "Failed to delete account.";
}

$stmt->close();
$conn->close();
?>
