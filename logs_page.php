<?php
require_once "dependencies/config.php";
require_once "dependencies/auth.php";

// Only allow owner (adjust as needed)
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'owner') {
    header("Location: MenuPage.php");
    exit;
}

// Pagination and search setup
$perPage = 25;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;
$search = trim($_GET['search'] ?? '');

// Build WHERE clause for search
$where = '';
$params = [];
$types = '';
if ($search !== '') {
    $where = "WHERE (event_type LIKE ? OR username LIKE ? OR details LIKE ?)";
    $searchTerm = '%' . $search . '%';
    $params = [$searchTerm, $searchTerm, $searchTerm];
    $types = 'sss';
}

// Count total logs for pagination
$countSql = "SELECT COUNT(*) FROM security_logs $where";
$countStmt = $conn->prepare($countSql);
if ($where) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$countStmt->bind_result($totalRows);
$countStmt->fetch();
$countStmt->close();

$totalPages = max(1, ceil($totalRows / $perPage));

// Fetch logs
$sql = "SELECT id, event_type, user_id, username, details, success, timestamp
        FROM security_logs
        $where
        ORDER BY timestamp DESC
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($where
    ? "$sql"
    : "SELECT id, event_type, user_id, username, details, success, timestamp FROM security_logs ORDER BY timestamp DESC LIMIT ? OFFSET ?"
);

if ($where) {
    $allParams = array_merge($params, [$perPage, $offset]);
    $allTypes = $types . "ii";
    $stmt->bind_param($allTypes, ...$allParams);
} else {
    $stmt->bind_param("ii", $perPage, $offset);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Security Logs</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 8px; }
        th { background: #f8f8f8; }
        .success { color: green; font-weight: bold; }
        .fail { color: red; font-weight: bold; }
        .pagination a { margin: 0 4px; text-decoration: none; }
        .pagination .active { font-weight: bold; text-decoration: underline; }
        .search-form { margin-bottom: 20px; }
    </style>
</head>
<body>
    <h2>Security Logs</h2>
    <button onclick="window.location.href='MenuPage.php'" style="margin-bottom: 18px; padding: 7px 18px; background: #eee; border: 1px solid #ccc; border-radius: 5px; cursor: pointer;">
        &larr; Go Back
    </button>
    
    <form method="get" class="search-form">
        <input type="text" name="search" placeholder="Search event, user, or details" value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit">Search</button>
    </form>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Event Type</th>
                <th>User ID</th>
                <th>Username</th>
                <th>Details</th>
                <th>Success</th>
                <th>Timestamp</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['id']); ?></td>
                <td><?php echo htmlspecialchars($row['event_type']); ?></td>
                <td><?php echo htmlspecialchars($row['user_id'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($row['username']); ?></td>
                <td><?php echo htmlspecialchars($row['details']); ?></td>
                <td class="<?php echo $row['success'] ? 'success' : 'fail'; ?>">
                    <?php echo $row['success'] ? 'Yes' : 'No'; ?>
                </td>
                <td><?php echo htmlspecialchars($row['timestamp']); ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <div class="pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): 
            $query = http_build_query(['search' => $search, 'page' => $i]);
        ?>
            <a href="?<?php echo $query; ?>" class="<?php if ($i == $page) echo 'active'; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
    </div>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>