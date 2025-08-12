<?php
include 'dependencies/config.php';
include 'dependencies/auth.php';
include 'dependencies/logger.php';

require_login(); // user has to be logged in to access this page
require_role(['owner', 'admin']); // only owner and admin has access to this page

// Initialize logger
$logger = new SecurityLogger($conn);

// Handle log filtering
$filter_type = $_GET['filter_type'] ?? '';
$filter_success = $_GET['filter_success'] ?? '';
$filter_date = $_GET['filter_date'] ?? '';

// Build query with filters
$where_conditions = [];
$params = [];
$types = '';

if ($filter_type) {
    $where_conditions[] = "event_type = ?";
    $params[] = $filter_type;
    $types .= 's';
}

if ($filter_success !== '') {
    $where_conditions[] = "success = ?";
    $params[] = $filter_success;
    $types .= 'i';
}

if ($filter_date) {
    $where_conditions[] = "DATE(timestamp) = ?";
    $params[] = $filter_date;
    $types .= 's';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get logs with pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 50;
$offset = ($page - 1) * $per_page;

$count_query = "SELECT COUNT(*) as total FROM security_logs $where_clause";
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_logs = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_logs / $per_page);

$logs_query = "SELECT * FROM security_logs $where_clause ORDER BY timestamp DESC LIMIT ? OFFSET ?";
$logs_stmt = $conn->prepare($logs_query);
$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';
$logs_stmt->bind_param($types, ...$params);
$logs_stmt->execute();
$logs_result = $logs_stmt->get_result();

// Get unique event types for filter
$event_types_query = "SELECT DISTINCT event_type FROM security_logs ORDER BY event_type";
$event_types_result = $conn->query($event_types_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Logs - La Frontera</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/reports.css">
    <link rel="icon" type="image/x-icon" href="images/favicon.ico">
    <style>
        .logs-container {
            padding: 20px;
        }
        .filters {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .filter-row {
            display: flex;
            gap: 20px;
            align-items: center;
            margin-bottom: 15px;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        .filter-group label {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .filter-group select, .filter-group input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .filter-buttons {
            display: flex;
            gap: 10px;
            align-items: end;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }
        .btn-primary {
            background-color: #FFC800;
            color: black;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        .logs-table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .logs-table table {
            width: 100%;
            border-collapse: collapse;
        }
        .logs-table th, .logs-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .logs-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .success-yes {
            color: #28a745;
            font-weight: bold;
        }
        .success-no {
            color: #dc3545;
            font-weight: bold;
        }
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }
        .pagination a {
            padding: 8px 12px;
            border: 1px solid #ddd;
            text-decoration: none;
            color: #333;
            border-radius: 4px;
        }
        .pagination a:hover {
            background-color: #f8f9fa;
        }
        .pagination .current {
            background-color: #FFC800;
            color: black;
            border-color: #FFC800;
        }
        .export-buttons {
            margin-bottom: 20px;
            text-align: right;
        }
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
        <a href="reports.php" class="sidebar-item"><i class="fas fa-chart-bar"></i> Reports</a>
        <?php if ($_SESSION['user_role'] === 'owner'): ?>
            <a href="accounts_page.php" class="sidebar-item"><i class="fas fa-users-cog"></i> Accounts</a>
        <?php endif; ?>
        <a href="logs_viewer.php" class="sidebar-item active"><i class="fas fa-file-alt"></i> Security Logs</a>
    </div>

    <div class="content">
        <div class="content-header">
            <h2>Security Logs</h2>
            <p>Monitor system security events and user activities</p>
        </div>

        <div class="export-buttons">
            <a href="export_logs.php?format=csv<?php echo $filter_type ? '&filter_type=' . urlencode($filter_type) : ''; ?><?php echo $filter_success !== '' ? '&filter_success=' . $filter_success : ''; ?><?php echo $filter_date ? '&filter_date=' . urlencode($filter_date) : ''; ?>" class="btn btn-primary">
                <i class="fas fa-download"></i> Export CSV
            </a>
        </div>

        <div class="filters">
            <form method="GET" action="logs_viewer.php">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="filter_type">Event Type:</label>
                        <select name="filter_type" id="filter_type">
                            <option value="">All Types</option>
                            <?php while ($type = $event_types_result->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($type['event_type']); ?>" <?php echo $filter_type === $type['event_type'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type['event_type']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="filter_success">Success:</label>
                        <select name="filter_success" id="filter_success">
                            <option value="">All</option>
                            <option value="1" <?php echo $filter_success === '1' ? 'selected' : ''; ?>>Success</option>
                            <option value="0" <?php echo $filter_success === '0' ? 'selected' : ''; ?>>Failure</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="filter_date">Date:</label>
                        <input type="date" name="filter_date" id="filter_date" value="<?php echo htmlspecialchars($filter_date); ?>">
                    </div>
                    <div class="filter-buttons">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="logs_viewer.php" class="btn btn-secondary">Clear</a>
                    </div>
                </div>
            </form>
        </div>

        <div class="logs-container">
            <div class="logs-table">
                <table>
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>Event Type</th>
                            <th>User</th>

                            <th>Success</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($log = $logs_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($log['timestamp']); ?></td>
                                <td><?php echo htmlspecialchars($log['event_type']); ?></td>
                                <td><?php echo htmlspecialchars($log['username']); ?></td>

                                <td class="<?php echo $log['success'] ? 'success-yes' : 'success-no'; ?>">
                                    <?php echo $log['success'] ? 'Yes' : 'No'; ?>
                                </td>
                                <td><?php echo htmlspecialchars($log['details']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?><?php echo $filter_type ? '&filter_type=' . urlencode($filter_type) : ''; ?><?php echo $filter_success !== '' ? '&filter_success=' . $filter_success : ''; ?><?php echo $filter_date ? '&filter_date=' . urlencode($filter_date) : ''; ?>">Previous</a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <a href="?page=<?php echo $i; ?><?php echo $filter_type ? '&filter_type=' . urlencode($filter_type) : ''; ?><?php echo $filter_success !== '' ? '&filter_success=' . $filter_success : ''; ?><?php echo $filter_date ? '&filter_date=' . urlencode($filter_date) : ''; ?>" class="<?php echo $i === $page ? 'current' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo $filter_type ? '&filter_type=' . urlencode($filter_type) : ''; ?><?php echo $filter_success !== '' ? '&filter_success=' . $filter_success : ''; ?><?php echo $filter_date ? '&filter_date=' . urlencode($filter_date) : ''; ?>">Next</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
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
