<?php
session_start();
if (!isset($_SESSION['role'])) {
    die("Access denied.");
}


include 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $customer_name = $_POST['customer_name'];
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];
    $total_amount = $_POST['total_amount'];

    
    $stmt = $conn->prepare("INSERT INTO orders (customer_name, product_id, quantity, total_amount, status, payment_method) VALUES (?, ?, ?, ?, 'Pending', 'Cash')");
    $stmt->bind_param("siid", $customer_name, $product_id, $quantity, $total_amount);

    if ($stmt->execute()) {
        header("Location: OrderPage.php?success=1");
        exit();
    } else {
        echo "Error adding order: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
