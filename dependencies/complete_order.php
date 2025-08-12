<?php
include 'dependencies/config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['order_id'])) {
    $order_id = intval($_POST['order_id']);

    $sql = "UPDATE orders SET status = 'Complete' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $order_id);

    if ($stmt->execute()) {
        header("Location: OrderPage.php");
        exit();
    } else {
        echo "Failed to complete order.";
    }

    $stmt->close();
}
$conn->close();
?>