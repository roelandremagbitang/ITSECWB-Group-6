<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - La Frontera</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/error.css">
    <link rel="icon" type="image/x-icon" href="images/favicon.ico">
</head>
<body>
    <div class="error-container">
        <div class="error-content">
            <div class="error-icon">
                <i class="fas fa-ban"></i>
            </div>
            <h1>Access Denied</h1>
            <p>You don't have permission to access this resource.</p>
            <p>Please contact your administrator if you believe this is an error.</p>
            <div class="error-actions">
                <a href="javascript:history.back()" class="btn-primary">
                    <i class="fas fa-arrow-left"></i> Go Back
                </a>
                <a href="InventoryPage.php" class="btn-secondary">
                    <i class="fas fa-boxes"></i> View Inventory
                </a>
            </div>
        </div>
    </div>
    
    <script>
        // If we can't go back, redirect to inventory page
        if (history.length <= 1) {
            setTimeout(function() {
                window.location.href = 'InventoryPage.php';
            }, 3000);
        }
    </script>
</body>
</html>
