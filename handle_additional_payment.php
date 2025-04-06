<?php
include 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit();
}

// Get JSON data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['rental_id']) || !isset($data['update_id']) || !isset($data['amount'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid data received']);
    exit();
}

mysqli_begin_transaction($conn);

try {
    // 1. Update rental_updates payment status
    $update_query = "UPDATE rental_updates 
                    SET payment_status = 'paid' 
                    WHERE update_id = ? 
                    AND rental_id = ? 
                    AND payment_status = 'pending'";
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "ii", $data['update_id'], $data['rental_id']);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Failed to update payment status");
    }

    // 2. Create notification for manager
    $notification = "INSERT INTO manager_notifications (message, type) 
                    VALUES (?, 'rental_update_paid')";
    $message = "Additional payment of ₹" . number_format($data['amount'], 2) . 
               " received for rental #" . $data['rental_id'] . ". Ready for approval.";
    
    $stmt = mysqli_prepare($conn, $notification);
    mysqli_stmt_bind_param($stmt, "s", $message);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Failed to create manager notification");
    }

    // 3. Create notification for user
    $user_notification = "INSERT INTO user_notifications (user_id, message, type) 
                         VALUES (?, ?, 'payment_confirmation')";
    $user_message = "Your additional payment of ₹" . number_format($data['amount'], 2) . 
                   " for rental #" . $data['rental_id'] . " has been received.";
    
    $stmt = mysqli_prepare($conn, $user_notification);
    mysqli_stmt_bind_param($stmt, "is", $_SESSION['user_id'], $user_message);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Failed to create user notification");
    }

    mysqli_commit($conn);
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    mysqli_rollback($conn);
    error_log("Payment Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

mysqli_close($conn);
?> 