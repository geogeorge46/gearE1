<?php
require_once 'config.php';

// Delete expired tokens
$delete_stmt = mysqli_prepare($conn, "DELETE FROM password_reset_tokens WHERE expiry < NOW()");
if (mysqli_stmt_execute($delete_stmt)) {
    $affected_rows = mysqli_affected_rows($conn);
    echo "Cleaned up $affected_rows expired token(s)\n";
} else {
    echo "Error cleaning up tokens\n";
}

// Show remaining tokens
$query = "SELECT t.*, u.email, u.full_name 
          FROM password_reset_tokens t 
          JOIN users u ON t.user_id = u.user_id";
$result = mysqli_query($conn, $query);

echo "\nRemaining active tokens:\n";
while ($row = mysqli_fetch_assoc($result)) {
    echo "User: {$row['full_name']}, Expires: {$row['expiry']}\n";
}
