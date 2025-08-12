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

// Handle edit user type POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user_type'])) {
    $edit_user_id = $_POST['edit_user_id'];
    $new_usertype = $_POST['new_usertype'];

    // Validate user ID and user type
    if (
        $validator->validateUserId($edit_user_id, $_SESSION['user_id'], $_SESSION['username']) &&
        in_array($new_usertype, ['manager', 'customer'])
    ) {
        $update_query = "UPDATE users SET usertype = ? WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("si", $new_usertype, $edit_user_id);
        if ($stmt->execute()) {
            $logger->logAccountOperation('ACCOUNT_EDIT_TYPE', $_SESSION['user_id'], $_SESSION['username'], $edit_user_id, $new_usertype, true);
        } else {
            $logger->logAccountOperation('ACCOUNT_EDIT_TYPE', $_SESSION['user_id'], $_SESSION['username'], $edit_user_id, $new_usertype, false);
        }
        $stmt->close();
        header("Location: accounts_page.php");
        exit();
    } else {
        $logger->logSecurityEvent('VALIDATION_FAILURE', $_SESSION['user_id'], $_SESSION['username'], "Invalid user type update for user ID: $edit_user_id", false);
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
    <style>
        /* Modal styles */
        .modal {
            display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%;
            overflow: auto; background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: #fff; margin: 10% auto; padding: 20px; border: 1px solid #888; width: 300px; border-radius: 8px;
        }
        .close {
            color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;
        }
        .close:hover, .close:focus { color: #000; text-decoration: none; cursor: pointer; }
        .edit-btn { background: #ffc107; color: #222; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; margin-right: 6px;}
        .edit-btn i { margin-right: 4px; }
    </style>
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
                            <!-- Edit User Type Button -->
                            <button type="button" class="edit-btn" 
                                onclick="openEditModal('<?php echo $account['id']; ?>', '<?php echo $account['usertype']; ?>')">
                                <i class="fas fa-edit"></i>Edit Role
                            </button>
                            <!-- Delete Button -->
                            <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this account?');">
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

    <!-- Edit User Type Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h3>Edit User Type</h3>
            <form method="post">
                <input type="hidden" name="edit_user_id" id="edit_user_id">
                <label for="new_usertype">User Type:</label>
                <select name="new_usertype" id="new_usertype" required>
                    <option value="manager">Manager</option>
                    <option value="customer">Customer</option>
                </select>
                <br><br>
                <button type="submit" name="edit_user_type" class="edit-btn"><i class="fas fa-save"></i>Save</button>
            </form>
        </div>
    </div>

    <script>
        // Modal logic for editing user type
        function openEditModal(userId, currentType) {
            document.getElementById('edit_user_id').value = userId;
            document.getElementById('new_usertype').value = currentType;
            document.getElementById('editModal').style.display = 'block';
        }
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        // Only close modal if clicking outside modal-content, not on user-dropdown
        window.addEventListener("click", function(event) {
            const modal = document.getElementById('editModal');
            const modalContent = document.querySelector('.modal-content');
            if (modal.style.display === "block" && event.target === modal) {
                closeEditModal();
            }
        });

        // User dropdown logic (unchanged)
        document.getElementById("userDropdownBtn").addEventListener("click", function(event) {
            event.stopPropagation();
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