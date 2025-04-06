<?php
include 'config.php';
session_start();

// Check if user is logged in as manager
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'manager') {
    header('Location: login.php');
    exit();
}

// Process refund action
if (isset($_POST['action']) && $_POST['action'] === 'process_refund' && isset($_POST['refund_id'])) {
    $refund_id = (int)$_POST['refund_id'];
    $manager_id = $_SESSION['user_id'];
    
    // Begin transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Get refund details
        $query = "SELECT r.*, ru.rental_id, u.user_id, u.full_name, m.name as machine_name
                 FROM refunds r
                 JOIN rental_updates ru ON r.update_id = ru.id
                 JOIN rentals rent ON ru.rental_id = rent.rental_id
                 JOIN users u ON rent.user_id = u.user_id
                 JOIN machines m ON rent.machine_id = m.machine_id
                 WHERE r.id = ? AND r.status = 'pending'";
        
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $refund_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) === 0) {
            throw new Exception("Refund not found or already processed");
        }
        
        $refund = mysqli_fetch_assoc($result);
        
        // Update refund status
        $update_query = "UPDATE refunds 
                        SET status = 'processed', 
                            processed_by = ?, 
                            processed_at = CURRENT_TIMESTAMP 
                        WHERE id = ?";
        
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "ii", $manager_id, $refund_id);
        mysqli_stmt_execute($stmt);
        
        // Update rental_updates status
        $update_ru = "UPDATE rental_updates SET status = 'refunded' WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_ru);
        mysqli_stmt_bind_param($stmt, "i", $refund['update_id']);
        mysqli_stmt_execute($stmt);
        
        // Add notification for user
        $notification = "INSERT INTO user_notifications (user_id, message, type, related_id) 
                        VALUES (?, ?, 'refund_processed', ?)";
        $message = "Your refund of ₹" . number_format($refund['amount'], 2) . " for {$refund['machine_name']} has been processed.";
        $stmt = mysqli_prepare($conn, $notification);
        mysqli_stmt_bind_param($stmt, "isi", $refund['user_id'], $message, $refund['rental_id']);
        mysqli_stmt_execute($stmt);
        
        // Add notification for manager
        $manager_notification = "INSERT INTO manager_notifications (message, type, related_id) 
                               VALUES (?, 'refund_processed', ?)";
        $manager_message = "Refund of ₹" . number_format($refund['amount'], 2) . " processed for {$refund['full_name']}'s rental.";
        $stmt = mysqli_prepare($conn, $manager_notification);
        mysqli_stmt_bind_param($stmt, "si", $manager_message, $refund['rental_id']);
        mysqli_stmt_execute($stmt);
        
        // Commit transaction
        mysqli_commit($conn);
        
        $_SESSION['success'] = "Refund processed successfully.";
        
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        $_SESSION['error'] = "Error processing refund: " . $e->getMessage();
    }
    
    header('Location: manage_refunds.php');
    exit();
}

// Get pending refunds
$query = "SELECT r.*, ru.rental_id, ru.new_start_date, ru.new_end_date, ru.original_amount, ru.new_amount,
          rent.start_date as original_start_date, rent.end_date as original_end_date,
          u.full_name, u.email, m.name as machine_name, m.image_url
          FROM refunds r
          JOIN rental_updates ru ON r.update_id = ru.id
          JOIN rentals rent ON ru.rental_id = rent.rental_id
          JOIN users u ON rent.user_id = u.user_id
          JOIN machines m ON rent.machine_id = m.machine_id
          WHERE r.status = 'pending'
          ORDER BY r.created_at DESC";

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Refunds - GEAR EQUIP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <!-- Side Navigation -->
    <div class="fixed left-0 top-0 w-64 h-full bg-white shadow-lg">
        <!-- Logo Section -->
        <div class="p-4 border-b">
            <div class="flex items-center space-x-3">
                <img src="images/logo.png" alt="GEAR EQUIP" class="h-8">
                <span class="text-xl font-bold text-gray-800">GEAR EQUIP</span>
            </div>
        </div>
        
        <!-- Manager Info -->
        <div class="p-4 border-b">
            <p class="text-sm text-gray-500">Welcome,</p>
            <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
        </div>

        <!-- Navigation Links -->
        <nav class="p-4">
            <div class="space-y-2">
                <a href="manager_dashboard.php" class="block px-4 py-2 rounded-lg hover:bg-gray-50 text-gray-700">
                    <i class="fas fa-home mr-3"></i>Overview
                </a>
                
                <a href="manager_approvals.php" class="block px-4 py-2 rounded-lg hover:bg-gray-50 text-gray-700">
                    <i class="fas fa-check-circle mr-3"></i>Rental Approvals
                </a>

                <a href="manage_refunds.php" class="block px-4 py-2 rounded-lg bg-blue-50 text-blue-700 font-medium">
                    <i class="fas fa-money-bill-wave mr-3"></i>Manage Refunds
                </a>
            </div>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="ml-64 p-8">
        <!-- Top Header -->
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-bold text-gray-800">Manage Refunds</h1>
            <div class="flex items-center space-x-4">
                <a href="manager_notifications.php" class="relative">
                    <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <?php
                    // Get unread notifications count
                    $unread_query = "SELECT COUNT(*) as count FROM manager_notifications WHERE is_read = FALSE";
                    $unread_result = mysqli_query($conn, $unread_query);
                    $unread_count = mysqli_fetch_assoc($unread_result)['count'];
                    if($unread_count > 0) {
                        echo "<span class='absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs font-bold'>$unread_count</span>";
                    }
                    ?>
                </a>
                <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition">
                    Logout
                </a>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <!-- Refunds Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($refund = mysqli_fetch_assoc($result)): ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="p-6">
                            <div class="flex items-center mb-4">
                                <img src="<?php echo htmlspecialchars($refund['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($refund['machine_name']); ?>"
                                     class="w-16 h-16 object-cover rounded-lg mr-4">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-800">
                                        <?php echo htmlspecialchars($refund['machine_name']); ?>
                                    </h3>
                                    <p class="text-gray-600">
                                        Rental #<?php echo $refund['rental_id']; ?>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <p class="text-gray-700">
                                    <span class="font-medium">Customer:</span> 
                                    <?php echo htmlspecialchars($refund['full_name']); ?>
                                </p>
                                <p class="text-gray-700">
                                    <span class="font-medium">Email:</span> 
                                    <?php echo htmlspecialchars($refund['email']); ?>
                                </p>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <!-- Original Booking -->
                                <div class="p-3 bg-gray-50 rounded-lg">
                                    <h4 class="font-medium text-gray-700 mb-2">Original Booking</h4>
                                    <div class="space-y-1 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Start:</span>
                                            <span class="text-gray-800">
                                                <?php echo date('M d, Y', strtotime($refund['original_start_date'])); ?>
                                            </span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">End:</span>
                                            <span class="text-gray-800">
                                                <?php echo date('M d, Y', strtotime($refund['original_end_date'])); ?>
                                            </span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Amount:</span>
                                            <span class="text-gray-800">
                                                ₹<?php echo number_format($refund['original_amount'], 2); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Updated Booking -->
                                <div class="p-3 bg-blue-50 rounded-lg">
                                    <h4 class="font-medium text-blue-700 mb-2">Updated Booking</h4>
                                    <div class="space-y-1 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-blue-600">Start:</span>
                                            <span class="text-blue-800">
                                                <?php echo date('M d, Y', strtotime($refund['new_start_date'])); ?>
                                            </span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-blue-600">End:</span>
                                            <span class="text-blue-800">
                                                <?php echo date('M d, Y', strtotime($refund['new_end_date'])); ?>
                                            </span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-blue-600">Amount:</span>
                                            <span class="text-blue-800">
                                                ₹<?php echo number_format($refund['new_amount'], 2); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Refund Amount -->
                            <div class="bg-green-50 p-4 rounded-lg border border-green-200 mb-6">
                                <div class="flex justify-between items-center">
                                    <span class="text-green-800 font-medium">Refund Amount:</span>
                                    <span class="text-green-800 text-xl font-bold">
                                        ₹<?php echo number_format($refund['amount'], 2); ?>
                                    </span>
                                </div>
                                <p class="text-green-700 text-sm mt-1">
                                    Requested on <?php echo date('M d, Y', strtotime($refund['created_at'])); ?>
                                </p>
                            </div>
                            
                            <!-- Action Button -->
                            <form method="POST" class="mt-4">
                                <input type="hidden" name="refund_id" value="<?php echo $refund['id']; ?>">
                                <input type="hidden" name="action" value="process_refund">
                                <button type="submit" 
                                        onclick="return confirm('Are you sure you want to process this refund?')"
                                        class="w-full bg-green-500 hover:bg-green-600 text-white py-2 px-4 rounded-lg transition duration-200">
                                    Process Refund
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-span-full text-center py-12">
                    <div class="text-gray-400 text-6xl mb-4">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <h3 class="text-xl font-medium text-gray-600">No Pending Refunds</h3>
                    <p class="text-gray-500 mt-2">All refunds have been processed.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://kit.fontawesome.com/your-code.js" crossorigin="anonymous"></script>
</body>
</html> 