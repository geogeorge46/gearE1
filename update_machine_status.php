<?php
include 'config.php';
session_start();

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Get JSON data from request
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['action'])) {
    echo json_encode(['success' => false, 'message' => 'No action specified']);
    exit();
}

// Get all machines in user's cart
$cart_query = "SELECT c.machine_id 
               FROM cart c 
               JOIN machines m ON c.machine_id = m.machine_id 
               WHERE c.user_id = ?";
$stmt = mysqli_prepare($conn, $cart_query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$cart_result = mysqli_stmt_get_result($stmt);

$machine_ids = [];
while ($row = mysqli_fetch_assoc($cart_result)) {
    $machine_ids[] = $row['machine_id'];
}

if (empty($machine_ids)) {
    echo json_encode(['success' => false, 'message' => 'No machines in cart']);
    exit();
}

$success = true;
$message = '';

// Begin transaction
mysqli_begin_transaction($conn);

try {
    if ($data['action'] === 'reserve') {
        // Set machines to 'reserved' status
        $update_query = "UPDATE machines SET status = 'reserved' WHERE machine_id = ? AND status = 'available'";
        $stmt = mysqli_prepare($conn, $update_query);
        
        foreach ($machine_ids as $machine_id) {
            mysqli_stmt_bind_param($stmt, "i", $machine_id);
            mysqli_stmt_execute($stmt);
            
            // Check if any machines couldn't be reserved (already rented/reserved)
            if (mysqli_affected_rows($conn) === 0) {
                // Check why it couldn't be reserved
                $check_query = "SELECT status FROM machines WHERE machine_id = ?";
                $check_stmt = mysqli_prepare($conn, $check_query);
                mysqli_stmt_bind_param($check_stmt, "i", $machine_id);
                mysqli_stmt_execute($check_stmt);
                $result = mysqli_stmt_get_result($check_stmt);
                $machine = mysqli_fetch_assoc($result);
                
                if ($machine['status'] !== 'available') {
                    $success = false;
                    $message = 'Some machines are no longer available. Please refresh the page.';
                    break;
                }
            }
        }
    } elseif ($data['action'] === 'reset') {
        // Reset machines that were reserved by this user back to 'available'
        // We can add a timestamp to track when machines were reserved to ensure
        // we only reset machines that were reserved but not rented/paid for
        $update_query = "UPDATE machines SET status = 'available' WHERE machine_id = ? AND status = 'reserved'";
        $stmt = mysqli_prepare($conn, $update_query);
        
        foreach ($machine_ids as $machine_id) {
            mysqli_stmt_bind_param($stmt, "i", $machine_id);
            mysqli_stmt_execute($stmt);
        }
    } else {
        $success = false;
        $message = 'Invalid action specified';
    }
    
    // Commit or rollback based on success
    if ($success) {
        mysqli_commit($conn);
    } else {
        mysqli_rollback($conn);
    }
} catch (Exception $e) {
    mysqli_rollback($conn);
    $success = false;
    $message = 'Error: ' . $e->getMessage();
}

echo json_encode(['success' => $success, 'message' => $message]);
?> 