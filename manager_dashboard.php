<?php
include 'config.php';
session_start();

// Check if user is logged in as manager
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'manager') {
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard - GEAR EQUIP</title>
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
                <a href="manager_dashboard.php" class="block px-4 py-2 rounded-lg bg-blue-50 text-blue-700 font-medium">
                    <i class="fas fa-home mr-3"></i>Overview
                </a>
                
                <a href="managerstore.php" class="block px-4 py-2 rounded-lg hover:bg-gray-50 text-gray-700">
                    <i class="fas fa-store mr-3"></i>Store
                </a>
                <a href="manager_availablemachines.php" class="block px-4 py-2 rounded-lg hover:bg-gray-50 text-gray-700">
                    <i class="fas fa-cogs mr-3"></i>Available Machines
                </a>
                <a href="rented_machines.php" class="block px-4 py-2 rounded-lg hover:bg-gray-50 text-gray-700">
                    <i class="fas fa-truck-loading mr-3"></i>Rented Machines
                </a>
                <a href="manager_reports.php" class="block px-4 py-2 rounded-lg hover:bg-gray-50 text-gray-700">
                    <i class="fas fa-chart-bar mr-3"></i>Reports
                </a>
                <a href="manager_reviewed.php" class="block px-4 py-2 rounded-lg hover:bg-gray-50 text-gray-700">
                    <i class="fas fa-star mr-3"></i>Review Management
                </a>
                <a href="manager_approvals.php" class="block px-4 py-2 rounded-lg hover:bg-gray-50 text-gray-700">
                    <i class="fas fa-check-circle mr-3"></i>Rental Approvals
                </a>
                <a href="manager_profile.php" class="block px-4 py-2 rounded-lg hover:bg-gray-50 text-gray-700">
                    <i class="fas fa-user-cog mr-3"></i>Profile Settings
                </a>
            </div>
                </a>
            </div>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="ml-64 p-8">
        <!-- Top Header -->
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-bold text-gray-800">Manager Dashboard</h1>
            <div class="flex items-center space-x-4">
                <a href="manager_notifications.php" class="relative">
                    <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <?php
                    // Get unread notifications count
                    $unread_query = "SELECT COUNT(*) as count FROM manager_notifications WHERE is_read = FALSE";
                    $result = mysqli_query($conn, $unread_query);
                    $unread_count = mysqli_fetch_assoc($result)['count'];
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

        
        

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-gray-500 text-sm font-medium">Total Machines</h3>
                <p class="text-3xl font-bold text-gray-800">
                    <?php 
                    // Count available machines from the machines table
                    $machines_count_query = "SELECT COUNT(*) as total_count 
                                            FROM machines 
                                            WHERE status = 'available'";
                    $result = mysqli_query($conn, $machines_count_query);
                    $row = mysqli_fetch_assoc($result);
                    echo $row['total_count'] ?? 0;  // Show 0 if null
                    ?>
                </p>
                <p class="text-sm text-gray-600 mt-1">Available for Rent</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-gray-500 text-sm font-medium">Total Inventory</h3>
                <p class="text-3xl font-bold text-gray-800">
                    <?php 
                    // Update total inventory query as well
                    $total_inventory_query = "SELECT COUNT(*) as total_inventory 
                                            FROM machines";
                    $result = mysqli_query($conn, $total_inventory_query);
                    $row = mysqli_fetch_assoc($result);
                    echo $row['total_inventory'] ?? 0;  // Show 0 if null
                    ?>
                </p>
                <p class="text-sm text-gray-600 mt-1">Total Machines</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-gray-500 text-sm font-medium">Active Rentals</h3>
                <p class="text-3xl font-bold text-gray-800">
                    <?php 
                    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM rentals WHERE status = 'active'");
                    $row = mysqli_fetch_assoc($result);
                    echo $row['count'];
                    ?>
                </p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-gray-500 text-sm font-medium">Pending Reviews</h3>
                <p class="text-3xl font-bold text-gray-800">
                    <?php 
                    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM reviews WHERE reviewed = 0");
                    $row = mysqli_fetch_assoc($result);
                    echo $row['count'];
                    ?>
                </p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-gray-500 text-sm font-medium">Today's Revenue</h3>
                <p class="text-3xl font-bold text-gray-800">₹
                    <?php 
                    $result = mysqli_query($conn, "SELECT SUM(total_amount) as total FROM rentals 
                                                 WHERE DATE(created_at) = CURDATE() AND status = 'active'");
                    $row = mysqli_fetch_assoc($result);
                    echo number_format($row['total'] ?? 0);
                    ?>
                </p>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b">
                <h2 class="text-xl font-semibold text-gray-800">Recent Activity</h2>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <?php
                    // Fetch recent rentals with more details
                    $query = "SELECT r.*, u.full_name, m.name as machine_name 
                             FROM rentals r 
                             JOIN users u ON r.user_id = u.user_id 
                             JOIN machines m ON r.machine_id = m.machine_id 
                             ORDER BY r.created_at DESC LIMIT 5";
                    $result = mysqli_query($conn, $query);
                    while($row = mysqli_fetch_assoc($result)) {
                        echo '<div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">';
                        echo '<div class="flex-grow">';
                        echo '<p class="font-semibold">' . htmlspecialchars($row['full_name']) . '</p>';
                        echo '<p class="text-sm text-gray-600">Rented ' . htmlspecialchars($row['machine_name']) . '</p>';
                        echo '<p class="text-xs text-gray-500">' . date('M d, Y', strtotime($row['created_at'])) . '</p>';
                        echo '</div>';
                        echo '<div class="flex items-center space-x-3">';
                        echo '<span class="px-2 py-1 text-xs rounded-full ' . 
                             ($row['status'] === 'active' ? 'bg-green-100 text-green-800' : 
                             ($row['status'] === 'completed' ? 'bg-blue-100 text-blue-800' : 
                             'bg-gray-100 text-gray-800')) . '">' . 
                             ucfirst($row['status']) . '</span>';
                        echo '<a href="generate_invoice.php?rental_id=' . $row['rental_id'] . '" 
                                class="inline-flex items-center px-3 py-1 text-sm text-blue-600 hover:text-blue-800 
                                       border border-blue-600 rounded hover:bg-blue-50 transition-colors">';
                        echo '<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
                        echo '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                   d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />';
                        echo '</svg>';
                        echo 'View Invoice';
                        echo '</a>';
                        echo '</div>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Font Awesome for icons -->
    <script src="https://kit.fontawesome.com/your-code.js" crossorigin="anonymous"></script>
</body>
</html>