<?php
// ... existing code ...

// Process payment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // In a real application, you would integrate with a payment gateway here
    // For this example, we'll simulate a successful payment
    
    // Begin transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Update rental with new dates and amount
        $update_query = "UPDATE rentals 
                        SET start_date = temp_start_date, 
                            end_date = temp_end_date, 
                            total_amount = total_amount + additional_amount,
                            temp_start_date = NULL,
                            temp_end_date = NULL,
                            additional_amount = 0,
                            payment_status = 'paid',
                            updated_at = CURRENT_TIMESTAMP 
                        WHERE rental_id = ?";
        
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "i", $rental_id);
        mysqli_stmt_execute($stmt);
        
        // Mark previous additional payment notification as read
        $update_notification = "UPDATE user_notifications 
                              SET is_read = 1 
                              WHERE user_id = ? AND type = 'additional_payment_needed' 
                              AND is_read = 0";
        $stmt = mysqli_prepare($conn, $update_notification);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        
        // Add notification for user
        $user_notification = "INSERT INTO user_notifications (user_id, message, type, is_read) 
                             VALUES (?, ?, 'payment_completed', 0)";
        $message = "Your additional payment of ₹" . number_format($amount_to_pay, 2) . " for {$rental['machine_name']} has been processed.";
        $stmt = mysqli_prepare($conn, $user_notification);
        mysqli_stmt_bind_param($stmt, "is", $user_id, $message);
        mysqli_stmt_execute($stmt);
        
        // Add notification for manager
        $manager_notification = "INSERT INTO manager_notifications (message, type, is_read) 
                               VALUES (?, 'additional_payment_received', 0)";
        $manager_message = "Additional payment of ₹" . number_format($amount_to_pay, 2) . " received for rental #{$rental_id}.";
        $stmt = mysqli_prepare($conn, $manager_notification);
        mysqli_stmt_bind_param($stmt, "s", $manager_message);
        mysqli_stmt_execute($stmt);
        
        // Commit transaction
        mysqli_commit($conn);
        
        $_SESSION['success'] = "Additional payment processed successfully. Your rental has been updated.";
        header('Location: user_rentals.php');
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        $_SESSION['error'] = "Error processing payment: " . $e->getMessage();
    }
}

// ... rest of the code ... 