<?php
require_once 'dependencies/config.php';
// Get form data
$product_name = $_POST['product_name'];
$price = $_POST['price'];
$quantity = $_POST['quantity'];
$supplier = $_POST['supplier'];

// Insert into database
$sql = "INSERT INTO products (product_name, price, quantity, supplier) VALUES (?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "sdss", $product_name, $price, $quantity, $supplier);

if (mysqli_stmt_execute($stmt)) {
    echo "Product added successfully!";
} else {
    echo "Error: " . mysqli_error($conn);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>