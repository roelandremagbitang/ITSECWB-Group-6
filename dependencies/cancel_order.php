<?php
session_start();
include 'dependencies/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['order_id'])) {
    $order_id = intval($_POST['order_id']);

    // Step 1: Fetch the order to get product and quantity
    $order_sql = "SELECT product_id, quantity, status FROM orders WHERE id = ?";
    $stmt = $conn->prepare($order_sql);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();

    if (!$order) {
        header("Location: OrderPage.php?error=OrderNotFound");
        exit();
    }

    // Only cancel if order is not already completed or cancelled
    if ($order['status'] === 'Complete' || $order['status'] === 'Cancelled') {
        header("Location: OrderPage.php?error=InvalidStatus");
        exit();
    }

    $product_id = $order['product_id'];
    $quantity = $order['quantity'];

    // Step 2: Update order status to 'Cancelled'
    $update_sql = "UPDATE orders SET status = 'Cancelled' WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("i", $order_id);
    
    if ($stmt->execute()) {
        // Step 3: Restock the product
        $restock_sql = "UPDATE products SET quantity = quantity + ? WHERE product_id = ?";
        $stmt = $conn->prepare($restock_sql);
        $stmt->bind_param("ii", $quantity, $product_id);
        $stmt->execute();

        header("Location: OrderPage.php?success=OrderCancelled");
        exit();
    } else {
        header("Location: OrderPage.php?error=CancelationFailed");
        exit();
    }
}
?>
