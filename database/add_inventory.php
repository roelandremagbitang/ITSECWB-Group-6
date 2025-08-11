<?php
session_start();
include 'dependencies/config.php';

// Check if the user is logged in and has the correct permissions
if (!isset($_SESSION['email'])) {
    die("User not logged in.");
}

$email = $_SESSION['email'];
$query = "SELECT id, usertype FROM users WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $user_id = $row['id'];
    $usertype = $row['usertype'];
} else {
    die("User not found in database.");
}

// Ensure the data is coming via POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_name = $_POST['product_name'];
    $current_stock = $_POST['current_stock'];
    $supplier = $_POST['supplier'];

    // Default values
    $inbound_qty = 0;  // Default value
    $outbound_qty = 0;  // Default value

    // Set the current date and time as last restocked
    $last_restocked = date("Y-m-d H:i:s"); // Current date and time in MySQL format

    // Insert the new item into the inventory table
    $query = "INSERT INTO inventory (product_name, inbound_qty, outbound_qty, current_stock, supplier, last_restocked) 
              VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("siiiss", $product_name, $inbound_qty, $outbound_qty, $current_stock, $supplier, $last_restocked);
    
    if ($stmt->execute()) {
        echo "Item added successfully!";
    } else {
        echo "Error adding item.";
    }
} else {
    echo "Invalid request method.";
}
?>
