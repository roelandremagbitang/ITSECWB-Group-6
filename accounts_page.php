<?php
require_once 'dependencies/config.php';
require_once 'dependencies/auth.php';
require_once 'dependencies/logger.php';
require_once 'dependencies/validator.php';

require_login(); // user has to be logged in to access this page
require_role(['owner', 'manager']); // only owner and manager has access to this page

// Initialize logger and validator
$logger = new SecurityLogger($conn);
$validator = new SecurityValidator($logger);

$accounts_query = "SELECT id, username, email, usertype FROM users WHERE usertype IN ('manager', 'customer')";
$accounts_result = $conn->query($accounts_query);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $delete_user_id = $_POST['delete_user_id'];

    // Validate user ID
    if (!$validator->validateUserId($delete_user_id, $_SESSION['user_id'], $_SESSION['username'])) {
        $logger->logSecurityEvent('VALIDATION_FAILURE', $_SESSION['user_id'], $_SESSION['username'], "Invalid user ID for deletion: $delete_user_id", false);
        header("Location: accounts_page.php");
        exit();
    }

    // Prevent deleting owner/admin
    $check_query = "SELECT username, usertype FROM users WHERE id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $delete_user_id);
    $stmt->execute();
    $stmt->bind_result($target_username, $usertype);
    $stmt->fetch();
    $stmt->close();

    if (in_array($usertype, ['manager', 'customer'])) {
        $delete_query = "DELETE FROM users WHERE id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $delete_user_id);
        
        if ($stmt->execute()) {
            // Log successful account deletion
            $logger->logAccountOperation('ACCOUNT_DELETION', $_SESSION['user_id'], $_SESSION['username'], $delete_user_id, $target_username, true);
        } else {
            // Log failed account deletion
            $logger->logAccountOperation('ACCOUNT_DELETION', $_SESSION['user_id'], $_SESSION['username'], $delete_user_id, $target_username, false);
        }
        
        $stmt->close();

        // Optional: refresh page to reflect changes
        header("Location: accounts_page.php");
        exit();
    } else {
        // Log attempt to delete protected account
        $logger->logSecurityEvent('UNAUTHORIZED_ACTION', $_SESSION['user_id'], $_SESSION['username'], "Attempted to delete protected account type: $usertype", false);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>La Frontera - Account Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/accounts_page.css">
    <link rel="icon" type="image/x-icon" href="images/favicon.ico">
</head>

<body>
<div class="header">
    <div class="logo">La Frontera</div>
    <div class="search-nav">
        <a href="MenuPage.php" class="nav-item">Dashboard</a>
        <a href="reports.php" class="nav-item">Reports</a>
    </div>

    <div class="username-display">
        <?php echo htmlspecialchars($_SESSION['username']); ?>
    </div>

    <div class="user-dropdown">
        <div class="user-icon" id="userDropdownBtn">
            <i class="fas fa-user"></i>
        </div>
        <div class="dropdown-menu" id="userDropdownMenu">
            <a href="profile.php">My Account</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
</div>
    
<div class="container">
    <div class="sidebar">
        <a href="MenuPage.php" class="sidebar-item"><i class="fas fa-home"></i> Home</a>
        <a href="InventoryPage.php" class="sidebar-item"><i class="fas fa-boxes"></i> Inventory</a>
        <a href="ProductPage.php" class="sidebar-item"><i class="fas fa-box"></i> Products</a>
        <a href="OrderPage.php" class="sidebar-item"><i class="fas fa-shopping-cart"></i> Orders</a>

        <?php if ($_SESSION['user_role'] === 'owner'): ?>
            <a href="accounts_page.php" class="sidebar-item active"><i class="fas fa-users-cog"></i> Accounts</a>
        <?php endif; ?>
    </div>

    <div class="content">
        <h2>Accounts List</h2>
        <table class="accounts-table">
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>User Type</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($account = $accounts_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $account['id']; ?></td>
                        <td><?php echo htmlspecialchars($account['username']); ?></td>
                        <td><?php echo htmlspecialchars($account['email']); ?></td>
                        <td><?php echo htmlspecialchars(ucfirst($account['usertype'])); ?></td>
                        <td>
                            <form method="post" onsubmit="return confirm('Are you sure you want to delete this account?');">
                                <input type="hidden" name="delete_user_id" value="<?php echo $account['id']; ?>">
                                <button type="submit" name="delete_user" class="delete-btn">
                                    <i class="fas fa-trash-alt"></i> Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<script>
    document.getElementById("userDropdownBtn").addEventListener("click", function() {
        const dropdown = document.getElementById("userDropdownMenu");
        dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
    });

    window.addEventListener("click", function(event) {
        if (!event.target.closest(".user-dropdown")) {
            document.getElementById("userDropdownMenu").style.display = "none";
        }
    });
</script>
</body>
</html>