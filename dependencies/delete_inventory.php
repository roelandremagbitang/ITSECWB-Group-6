<?php
session_start();
include 'dependencies/config.php';

if (!isset($_SESSION['email'])) {
    die("User not logged in.");
}

if (isset($_POST['product_id'])) {
    $product_id = $_POST['product_id'];

    // Prepare SQL query to delete the product from the database
    $query = "DELETE FROM inventory WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $product_id);

    if ($stmt->execute()) {
        echo "Product deleted successfully.";
    } else {
        echo "Error: Unable to delete the product.";
    }
} else {
    echo "Product ID not provided.";
}
?>
