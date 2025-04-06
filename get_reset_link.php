<?php
require_once 'config.php';

// Your email
$email = 'geogeorge24680@gmail.com';

// Get user info
$stmt = mysqli_prepare($conn, "SELECT user_id, full_name FROM users WHERE email = ?");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($user = mysqli_fetch_assoc($result)) {
    // Generate token
    $token = bin2hex(random_bytes(32));
    $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Delete old tokens
    $delete_stmt = mysqli_prepare($conn, "DELETE FROM password_reset_tokens WHERE user_id = ?");
    mysqli_stmt_bind_param($delete_stmt, "i", $user['user_id']);
    mysqli_stmt_execute($delete_stmt);
    
    // Insert new token
    $insert_stmt = mysqli_prepare($conn, "INSERT INTO password_reset_tokens (user_id, token, expiry) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($insert_stmt, "iss", $user['user_id'], $token, $expiry);
    
    if (mysqli_stmt_execute($insert_stmt)) {
        echo "Here's your reset link:\n\n";
        echo "http://localhost/MINI/GEAR_EQUIP/reset_password.php?token=" . $token . "\n\n";
        echo "Copy and paste this entire URL into your browser to reset your password.\n";
        echo "This link will expire in 1 hour.\n";
    } else {
        echo "Error creating reset token\n";
    }
} else {
    echo "Email not found\n";
}
