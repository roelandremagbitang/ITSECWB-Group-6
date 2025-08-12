<?php
include 'dependencies/config.php';
include 'dependencies/auth.php';

require_login(); // user has to be logged in to access this page
require_role(['owner', 'manager']); // only owner and manager has access to this page

// Handle log filtering
$filter_type = $_GET['filter_type'] ?? '';
$filter_success = $_GET['filter_success'] ?? '';
$filter_date = $_GET['filter_date'] ?? '';
$format = $_GET['format'] ?? 'csv';

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

// Get all logs for export
$logs_query = "SELECT * FROM security_logs $where_clause ORDER BY timestamp DESC";
$logs_stmt = $conn->prepare($logs_query);
if (!empty($params)) {
    $logs_stmt->bind_param($types, ...$params);
}
$logs_stmt->execute();
$logs_result = $logs_stmt->get_result();

if ($format === 'csv') {
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="security_logs_' . date('Y-m-d_H-i-s') . '.csv"');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add CSV headers
               fputcsv($output, ['Timestamp', 'Event Type', 'User ID', 'Username', 'Success', 'Details']);
    
    // Add data rows
    while ($log = $logs_result->fetch_assoc()) {
        fputcsv($output, [
            $log['timestamp'],
            $log['event_type'],
            $log['user_id'],
            $log['username'],
            $log['success'] ? 'Yes' : 'No',
            $log['details']
        ]);
    }
    
    fclose($output);
} else {
    // Invalid format
    header("Location: logs_viewer.php");
    exit();
}

$logs_stmt->close();
$conn->close();
?>
