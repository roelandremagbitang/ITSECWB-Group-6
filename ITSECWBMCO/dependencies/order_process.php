<?php
session_start();
include 'dependencies/config.php';

$customer_name = $_POST['customer_name'];
$product_id = $_POST['product_id'];
$quantity = $_POST['quantity'];
$total_amount = $_POST['total_amount'];
$payment_method = $_POST['payment_method']; 
$order_status = 'Pending'; // Default status

// Check if enough stock is available
$checkStock = $conn->prepare("SELECT quantity FROM products WHERE product_id = ?");
$checkStock->bind_param("i", $product_id);
$checkStock->execute();
$stockResult = $checkStock->get_result();
$stockRow = $stockResult->fetch_assoc();

if ($stockRow && $stockRow['quantity'] >= $quantity) {
    // Deduct stock
    $updateStock = $conn->prepare("UPDATE products SET quantity = quantity - ? WHERE product_id = ?");
    $updateStock->bind_param("ii", $quantity, $product_id);
    $updateStock->execute();

    // Insert order
    $sql = "INSERT INTO orders (username, product_id, quantity, total_amount, payment_method, status) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("siidss", $customer_name, $product_id, $quantity, $total_amount, $payment_method, $order_status);
    
    if ($stmt->execute()) {
        header("Location: OrderPage.php?success=1");
        exit();
    } else {
        echo "Error placing order: " . $conn->error;
    }
} else {
    echo "Error: Not enough stock available.";
}
?>
