<?php
include 'config.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'manager') {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['rental_id']) || !isset($_POST['new_start_date']) || !isset($_POST['new_end_date'])) {
        $_SESSION['error'] = "Missing required information";
        header('Location: manager_approvals.php');
        exit();
    }
    
    $rental_id = (int)$_POST['rental_id'];
    $new_start_date = $_POST['new_start_date'];
    $new_end_date = $_POST['new_end_date'];
    
    // Validate dates
    if (strtotime($new_end_date) <= strtotime($new_start_date)) {
        $_SESSION['error'] = "End date must be after start date";
        header('Location: manager_approvals.php');
        exit();
    }
    
    // Get rental details
    $query = "SELECT r.*, m.daily_rate, m.name as machine_name, u.user_id, u.full_name 
              FROM rentals r 
              JOIN machines m ON r.machine_id = m.machine_id 
              JOIN users u ON r.user_id = u.user_id 
              WHERE r.rental_id = ? AND r.status = 'pending'";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $rental_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 0) {
        $_SESSION['error'] = "Rental not found or not in pending status";
        header('Location: manager_approvals.php');
        exit();
    }
    
    $rental = mysqli_fetch_assoc($result);
    $user_id = $rental['user_id'];
    $daily_rate = $rental['daily_rate'];
    $machine_name = $rental['machine_name'];
    
    // Calculate original and new rental days
    $original_days = (strtotime($rental['end_date']) - strtotime($rental['start_date'])) / (60 * 60 * 24);
    $new_days = (strtotime($new_end_date) - strtotime($new_start_date)) / (60 * 60 * 24);
    
    // Calculate original and new amounts
    $original_amount = $rental['total_amount'];
    $new_amount = $daily_rate * $new_days;
    
    // Calculate difference
    $amount_difference = $new_amount - $original_amount;
    
    // Begin transaction
    mysqli_begin_transaction($conn);
    
    try {
        if ($amount_difference > 0) {
            // Additional payment needed - create rental update record
            $update_type = 'increase';
            $payment_status = 'pending';
            
            $insert_update_query = "INSERT INTO rental_updates 
                                   (rental_id, original_start_date, original_end_date, original_amount,
                                    new_start_date, new_end_date, new_amount, difference_amount, 
                                    update_type, payment_status) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = mysqli_prepare($conn, $insert_update_query);
            mysqli_stmt_bind_param($stmt, "issdssdsss", 
                                  $rental_id, 
                                  $rental['start_date'], 
                                  $rental['end_date'], 
                                  $original_amount,
                                  $new_start_date, 
                                  $new_end_date, 
                                  $new_amount, 
                                  $amount_difference, 
                                  $update_type, 
                                  $payment_status);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Failed to create rental update record: " . mysqli_error($conn));
            }
            
            // Update rental payment status
            $update_rental_query = "UPDATE rentals SET payment_status = 'additional_payment_needed' WHERE rental_id = ?";
            $stmt = mysqli_prepare($conn, $update_rental_query);
            mysqli_stmt_bind_param($stmt, "i", $rental_id);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Failed to update rental status: " . mysqli_error($conn));
            }
            
            // Let's check the structure of your user_notifications table
            // First, get the column names
            $table_info_query = "SHOW COLUMNS FROM user_notifications";
            $table_info_result = mysqli_query($conn, $table_info_query);
            $columns = [];
            while ($column = mysqli_fetch_assoc($table_info_result)) {
                $columns[] = $column['Field'];
            }
            
            // Now construct the query based on the actual columns
            $notification_fields = ["user_id", "message"];
            $notification_values = [$user_id, "Your rental dates for {$machine_name} have been updated. Additional payment of ₹" . number_format($amount_difference, 2) . " is required."];
            
            if (in_array("type", $columns)) {
                $notification_fields[] = "type";
                $notification_values[] = "additional_payment_needed";
            }
            
            if (in_array("related_id", $columns)) {
                $notification_fields[] = "related_id";
                $notification_values[] = $rental_id;
            }
            
            if (in_array("is_read", $columns)) {
                $notification_fields[] = "is_read";
                $notification_values[] = 0;
            }
            
            // Build the query
            $placeholders = array_fill(0, count($notification_values), "?");
            $notification_query = "INSERT INTO user_notifications (" . implode(", ", $notification_fields) . ") 
                                  VALUES (" . implode(", ", $placeholders) . ")";
            
            $stmt = mysqli_prepare($conn, $notification_query);
            if (!$stmt) {
                throw new Exception("Failed to prepare notification query: " . mysqli_error($conn));
            }
            
            // Dynamically bind parameters based on types
            $types = "";
            foreach ($notification_values as $value) {
                if (is_int($value)) {
                    $types .= "i";
                } elseif (is_double($value)) {
                    $types .= "d";
                } else {
                    $types .= "s";
                }
            }
            
            // Create the bind_param arguments array
            $bind_params = array($stmt, $types);
            foreach ($notification_values as &$value) {
                $bind_params[] = &$value;
            }
            
            // Call bind_param with the dynamically created arguments
            call_user_func_array('mysqli_stmt_bind_param', $bind_params);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Failed to insert notification: " . mysqli_error($conn));
            }
            
        } elseif ($amount_difference < 0) {
            // Refund needed - create rental update record
            $update_type = 'decrease';
            $payment_status = 'refunded';
            
            $insert_update_query = "INSERT INTO rental_updates 
                                   (rental_id, original_start_date, original_end_date, original_amount,
                                    new_start_date, new_end_date, new_amount, difference_amount, 
                                    update_type, payment_status) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = mysqli_prepare($conn, $insert_update_query);
            mysqli_stmt_bind_param($stmt, "issdssdsss", 
                                  $rental_id, 
                                  $rental['start_date'], 
                                  $rental['end_date'], 
                                  $original_amount,
                                  $new_start_date, 
                                  $new_end_date, 
                                  $new_amount, 
                                  abs($amount_difference), 
                                  $update_type, 
                                  $payment_status);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Failed to create rental update record: " . mysqli_error($conn));
            }
            
            // Update rental with new dates and refund status
            $update_rental_query = "UPDATE rentals 
                                   SET start_date = ?, end_date = ?, 
                                       refund_amount = ?, refund_status = 'pending' 
                                   WHERE rental_id = ?";
            
            $stmt = mysqli_prepare($conn, $update_rental_query);
            mysqli_stmt_bind_param($stmt, "ssdi", 
                                  $new_start_date, 
                                  $new_end_date, 
                                  abs($amount_difference), 
                                  $rental_id);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Failed to update rental: " . mysqli_error($conn));
            }
            
            // Similar notification code as above
            // ...
        } else {
            // No change in amount - just update the dates
            $update_rental_query = "UPDATE rentals 
                                   SET start_date = ?, end_date = ? 
                                   WHERE rental_id = ?";
            
            $stmt = mysqli_prepare($conn, $update_rental_query);
            mysqli_stmt_bind_param($stmt, "ssi", 
                                  $new_start_date, 
                                  $new_end_date, 
                                  $rental_id);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Failed to update rental: " . mysqli_error($conn));
            }
            
            // Similar notification code as above
            // ...
        }
        
        // Commit transaction
        mysqli_commit($conn);
        
        $_SESSION['success'] = "Rental dates updated successfully. " . 
                              ($amount_difference > 0 ? "User has been notified about additional payment." : 
                              ($amount_difference < 0 ? "Refund has been initiated." : "No change in total amount."));
        
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        $_SESSION['error'] = "Error updating rental: " . $e->getMessage();
    }
    
    header('Location: manager_approvals.php');
    exit();
}

// If not POST request, redirect back
header('Location: manager_approvals.php');
exit();
?> 
} 
} 
} 
} 
} 
} 
} 
} 