<?php
include 'config.php';
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

// Handle role update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];
    $new_role = $_POST['role'];
    
    $update_sql = "UPDATE users SET role = ? WHERE user_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("si", $new_role, $user_id);
    
    if ($stmt->execute()) {
        $success_message = "User role updated successfully!";
    } else {
        $error_message = "Error updating user role.";
    }
}

// First, verify if status column exists
$check_column = $conn->query("SHOW COLUMNS FROM users LIKE 'status'");
if ($check_column->num_rows == 0) {
    // If status column doesn't exist, create it
    $conn->query("ALTER TABLE users ADD COLUMN status VARCHAR(20) DEFAULT 'active'");
}

// Now fetch all users
$sql = "SELECT user_id, full_name, email, role, created_at, status FROM users WHERE user_id != ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$users = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - GEAR EQUIP</title>
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
        
        <!-- Admin Info -->
        <div class="p-4 border-b">
            <p class="text-sm text-gray-500">Welcome,</p>
            <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
        </div>

        <!-- Navigation Links -->
        <nav class="p-4">
            <div class="space-y-2">
                <a href="admin_dashboard.php" class="block px-4 py-2 rounded-lg bg-blue-50 text-blue-700 font-medium">
                    <i class="fas fa-home mr-3"></i>Overview
                </a>
                <a href="admin_users.php" class="block px-4 py-2 rounded-lg hover:bg-gray-50 text-gray-700">
                    <i class="fas fa-users mr-3"></i>Users Management
                </a>
                <a href="admin_managers.php" class="block px-4 py-2 rounded-lg hover:bg-gray-50 text-gray-700">
                    <i class="fas fa-user-tie mr-3"></i>Managers
                </a>
                <a href="admin_machines.php" class="block px-4 py-2 rounded-lg hover:bg-gray-50 text-gray-700">
                    <i class="fas fa-cogs mr-3"></i>Machines
                </a>
                <a href="admin_income.php" class="block px-4 py-2 rounded-lg hover:bg-gray-50 text-gray-700">
                    <i class="fas fa-chart-line mr-3"></i>Income
                </a>
                <a href="admin_profile.php" class="block px-4 py-2 rounded-lg hover:bg-gray-50 text-gray-700">
                    <i class="fas fa-user-cog mr-3"></i>Profile Settings
                </a>
            </div>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="ml-64 p-8">
        <!-- Top Header -->
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-bold text-gray-800">Dashboard Overview</h1>
            <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition">
                Logout
            </a>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-gray-500 text-sm font-medium">Total Users</h3>
                <p class="text-3xl font-bold text-gray-800">
                    <?php 
                    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'user'");
                    $row = mysqli_fetch_assoc($result);
                    echo $row['count'];
                    ?>
                </p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-gray-500 text-sm font-medium">Total Managers</h3>
                <p class="text-3xl font-bold text-gray-800">
                    <?php 
                    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'manager'");
                    $row = mysqli_fetch_assoc($result);
                    echo $row['count'];
                    ?>
                </p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-gray-500 text-sm font-medium">Total Machines</h3>
                <p class="text-3xl font-bold text-gray-800">
                    <?php 
                    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM machines");
                    $row = mysqli_fetch_assoc($result);
                    echo $row['count'];
                    ?>
                </p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-gray-500 text-sm font-medium">Total Revenue</h3>
                <p class="text-3xl font-bold text-gray-800">₹
                    <?php 
                    $result = mysqli_query($conn, "SELECT SUM(total_amount) as total FROM rentals WHERE status = 'paid'");
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
                <!-- Add your recent activity content here -->
            </div>
        </div>
    </div>

    <!-- Font Awesome for icons -->
    <script src="https://kit.fontawesome.com/your-code.js" crossorigin="anonymous"></script>
</body>
</html>