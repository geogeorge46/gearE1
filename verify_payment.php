<?php
header('Content-Type: application/json');
session_start();
include 'config.php';

try {
    // Get input data
    $raw_input = file_get_contents('php://input');
    $data = json_decode($raw_input, true);
    
    // Validate payment data
    if (!isset($data['razorpay_payment_id']) || !isset($data['amount'])) {
        throw new Exception('Missing required payment data');
    }

    $payment_id = $data['razorpay_payment_id'];
    $amount = $data['amount'];
    $user_id = $_SESSION['user_id'];

    // Start transaction
    mysqli_begin_transaction($conn);

    try {
        // Get cart items with a lock to prevent concurrent rentals
        $cart_query = "SELECT c.*, m.daily_rate, m.security_deposit, m.status as machine_status
                      FROM cart c 
                      JOIN machines m ON c.machine_id = m.machine_id 
                      WHERE c.user_id = ?
                      FOR UPDATE"; // Add FOR UPDATE to lock the rows
        $stmt = mysqli_prepare($conn, $cart_query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $cart_result = mysqli_stmt_get_result($stmt);
        
        // Check if any machines are already rented
        $already_rented = [];
        while ($cart_item = mysqli_fetch_assoc($cart_result)) {
            // Check if machine is already rented
            $check_machine = "SELECT status FROM machines WHERE machine_id = ? FOR UPDATE";
            $stmt = mysqli_prepare($conn, $check_machine);
            mysqli_stmt_bind_param($stmt, "i", $cart_item['machine_id']);
            mysqli_stmt_execute($stmt);
            $machine_result = mysqli_stmt_get_result($stmt);
            $machine = mysqli_fetch_assoc($machine_result);
            
            if ($machine['status'] == 'rented') {
                $already_rented[] = $cart_item['machine_id'];
            }
        }
        
        // If any machines are already rented, abort transaction
        if (!empty($already_rented)) {
            // Get machine names
            $machine_ids = implode(',', $already_rented);
            $machine_query = "SELECT name FROM machines WHERE machine_id IN ($machine_ids)";
            $machine_result = mysqli_query($conn, $machine_query);
            $machine_names = [];
            while ($machine = mysqli_fetch_assoc($machine_result)) {
                $machine_names[] = $machine['name'];
            }
            
            throw new Exception('The following machines are no longer available: ' . implode(', ', $machine_names));
        }
        
        // Reset result pointer
        mysqli_data_seek($cart_result, 0);
        
        // Process each cart item
        while ($cart_item = mysqli_fetch_assoc($cart_result)) {
            // Calculate rental days and amounts
            $rental_days = max(1, (strtotime($cart_item['end_date']) - strtotime($cart_item['start_date'])) / (60 * 60 * 24));
            $rental_amount = $cart_item['daily_rate'] * $rental_days;
            $total_amount = $rental_amount + ($rental_amount * 0.18); // Including GST

            // Insert into rentals table
            $insert_rental = "INSERT INTO rentals (
                user_id, 
                machine_id, 
                start_date, 
                end_date, 
                rental_days,
                total_amount,
                security_deposit,
                status,
                payment_status,
                created_at,
                updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', 'paid', NOW(), NOW())";

            $stmt = mysqli_prepare($conn, $insert_rental);
            mysqli_stmt_bind_param($stmt, "iissidi", 
                $user_id,
                $cart_item['machine_id'],
                $cart_item['start_date'],
                $cart_item['end_date'],
                $rental_days,
                $total_amount,
                $cart_item['security_deposit']
            );
            mysqli_stmt_execute($stmt);

            // Create notification for manager
            $manager_notification_query = "INSERT INTO manager_notifications (message, type, is_read, created_at) 
                                         VALUES (?, 'new_rental', FALSE, CURRENT_TIMESTAMP)";
            $notification_message = "New rental request received from " . $_SESSION['full_name'] . " for machine #" . $cart_item['machine_id'];
            $stmt = mysqli_prepare($conn, $manager_notification_query);
            mysqli_stmt_bind_param($stmt, "s", $notification_message);
            mysqli_stmt_execute($stmt);

            // Don't update machine status yet - wait for manager approval
            // Remove or comment out this section:
            /*
            $update_machine = "UPDATE machines SET status = 'rented' WHERE machine_id = ?";
            $stmt = mysqli_prepare($conn, $update_machine);
            mysqli_stmt_bind_param($stmt, "i", $cart_item['machine_id']);
            mysqli_stmt_execute($stmt);
            */
        }

        // Clear cart
        $clear_cart = "DELETE FROM cart WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $clear_cart);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);

        // Commit transaction
        mysqli_commit($conn);

        echo json_encode([
            'success' => true,
            'message' => 'Rental processed successfully',
            'payment_id' => $payment_id
        ]);

    } catch (Exception $e) {
        // Rollback on error
        mysqli_rollback($conn);
        throw $e;
    }

} catch (Exception $e) {
    error_log('Rental processing error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

mysqli_close($conn);
?> 