<?php
session_start();
require_once 'includes/db_connect.php';

if (!isset($_SESSION['manager_id'])) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$refund_id = $_POST['refund_id'] ?? 0;

try {
    mysqli_begin_transaction($conn);

    // Update refund status
    $update_query = "UPDATE refunds 
                    SET status = 'processed',
                        processed_at = NOW(),
                        processed_by = ?
                    WHERE refund_id = ?";
    
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "ii", $_SESSION['manager_id'], $refund_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Failed to update refund status");
    }

    // Get refund details for notification
    $refund_query = "SELECT r.user_id, rf.amount 
                     FROM refunds rf
                     JOIN rentals r ON rf.rental_id = r.rental_id
                     WHERE rf.refund_id = ?";
    
    $stmt = mysqli_prepare($conn, $refund_query);
    mysqli_stmt_bind_param($stmt, "i", $refund_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $refund_data = mysqli_fetch_assoc($result);

    // Create notification for user
    $notification_query = "INSERT INTO user_notifications 
                          (user_id, message, type) 
                          VALUES (?, ?, 'refund')";
    
    $message = "Your refund of ₹" . number_format($refund_data['amount'], 2) . " has been processed.";
    $stmt = mysqli_prepare($conn, $notification_query);
    mysqli_stmt_bind_param($stmt, "is", $refund_data['user_id'], $message);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Failed to create notification");
    }

    mysqli_commit($conn);
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 