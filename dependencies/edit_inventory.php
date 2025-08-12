<?php
session_start();
include 'dependencies/config.php';

// Check if user is logged in
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

// Check if the required POST data is available
if (isset($_POST['product_id'], $_POST['product_name'], $_POST['inbound_qty'], $_POST['outbound_qty'], $_POST['supplier'])) {
    $product_id = $_POST['product_id'];
    $product_name = $_POST['product_name'];
    $inbound_qty = $_POST['inbound_qty'];
    $outbound_qty = $_POST['outbound_qty'];
    $supplier = $_POST['supplier'];

    // Fetch the current stock from the database
    $sql = "SELECT current_stock FROM inventory WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $stmt->bind_result($current_stock);
    
    // Check if the current stock was fetched correctly
    if ($stmt->fetch()) {
        // Calculate the new current stock
        $current_stock = $current_stock + $inbound_qty - $outbound_qty; // This ensures current_stock is updated properly
    } else {
        // If the product was not found, display an error
        die("Product not found in database.");
    }
    $stmt->close();

    // Get the current date and time for last restocked
    $last_restocked = date("Y-m-d H:i:s");

    // Prepare the SQL query to update the inventory
    $sql = "UPDATE inventory SET product_name = ?, inbound_qty = ?, outbound_qty = ?, current_stock = ?, supplier = ?, last_restocked = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("siiisss", $product_name, $inbound_qty, $outbound_qty, $current_stock, $supplier, $last_restocked, $product_id);

    if ($stmt->execute()) {
        echo "Product updated successfully!";
    } else {
        echo "Error updating product: " . $conn->error;
    }
} else {
    echo "Missing required data.";
}

// Close the database connection
$stmt->close();
$conn->close();
?>
