<?php
include 'config.php';
session_start();

// Check if user is logged in as manager
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'manager') {
    header('Location: login.php');
    exit();
}

// Mark notifications as read when viewed
$mark_read = "UPDATE manager_notifications SET is_read = TRUE WHERE is_read = FALSE";
mysqli_query($conn, $mark_read);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Notifications - GEAR EQUIP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
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
            </div>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="ml-64 p-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-bold text-gray-800">Notifications</h1>
            <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition">
                Logout
            </a>
        </div>

        <div class="bg-white rounded-lg shadow-md">
            <div class="p-6">
                <?php
                $query = "SELECT * FROM manager_notifications ORDER BY created_at DESC";
                $result = mysqli_query($conn, $query);

                if (mysqli_num_rows($result) > 0):
                    while ($notification = mysqli_fetch_assoc($result)):
                ?>
                    <div class="border-b border-gray-200 py-4 <?php echo $notification['is_read'] ? 'opacity-75' : ''; ?>">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-800"><?php echo htmlspecialchars($notification['message']); ?></p>
                                <p class="text-sm text-gray-500 mt-1">
                                    <?php echo date('M d, Y h:i A', strtotime($notification['created_at'])); ?>
                                </p>
                            </div>
                            <?php if (!$notification['is_read']): ?>
                                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">New</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php 
                    endwhile;
                else:
                ?>
                    <div class="text-center py-8">
                        <p class="text-gray-500">No notifications yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://kit.fontawesome.com/your-code.js" crossorigin="anonymous"></script>
</body>
</html> 