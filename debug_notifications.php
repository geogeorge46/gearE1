<?php
include 'config.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get all notifications for the user
$query = "SELECT * FROM user_notifications WHERE user_id = ? ORDER BY created_at DESC";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

echo "<h1>Debug Notifications</h1>";
echo "<p>User ID: $user_id</p>";
echo "<p>Total notifications: " . mysqli_num_rows($result) . "</p>";

if (mysqli_num_rows($result) > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Message</th><th>Type</th><th>Related ID</th><th>Is Read</th><th>Created At</th></tr>";
    
    while ($notification = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $notification['id'] . "</td>";
        echo "<td>" . htmlspecialchars($notification['message']) . "</td>";
        echo "<td>" . (isset($notification['type']) ? $notification['type'] : 'N/A') . "</td>";
        echo "<td>" . (isset($notification['related_id']) ? $notification['related_id'] : 'N/A') . "</td>";
        echo "<td>" . (isset($notification['is_read']) ? ($notification['is_read'] ? 'Yes' : 'No') : 'N/A') . "</td>";
        echo "<td>" . $notification['created_at'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>No notifications found</p>";
}

// Show table structure
echo "<h2>Table Structure</h2>";
$table_info_query = "SHOW COLUMNS FROM user_notifications";
$table_info_result = mysqli_query($conn, $table_info_query);

echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

while ($column = mysqli_fetch_assoc($table_info_result)) {
    echo "<tr>";
    echo "<td>" . $column['Field'] . "</td>";
    echo "<td>" . $column['Type'] . "</td>";
    echo "<td>" . $column['Null'] . "</td>";
    echo "<td>" . $column['Key'] . "</td>";
    echo "<td>" . $column['Default'] . "</td>";
    echo "<td>" . $column['Extra'] . "</td>";
    echo "</tr>";
}

echo "</table>";
?> 