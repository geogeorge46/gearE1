<?php
include 'config.php';
session_start();

// Check if user is logged in as manager
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'manager') {
    header('Location: login.php');
    exit();
}

// Query counts for all tables
try {
    // Categories count
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM categories");
    $categoryCount = mysqli_fetch_assoc($result)['count'];

    // Subcategories count
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM subcategories");
    $subcategoryCount = mysqli_fetch_assoc($result)['count'];

    // Machines count
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM machines");
    $machineCount = mysqli_fetch_assoc($result)['count'];
} catch (mysqli_sql_exception $e) {
    // Default values if queries fail
    $categoryCount = 0;
    $subcategoryCount = 0;
    $machineCount = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store Management - GEAR EQUIP</title>
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
                <a href="managerstore.php" class="block px-4 py-2 rounded-lg bg-blue-50 text-blue-700 font-medium">
                    <i class="fas fa-store mr-3"></i>Store
                </a>
                <!-- ... other navigation links ... -->
            </div>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="ml-64 p-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-bold text-gray-800">Store Management</h1>
        </div>

        <!-- Top Navigation -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="flex space-x-6 px-8 py-4">
                <a href="manage_categories.php" class="text-gray-600 hover:text-blue-600 font-medium">
                    Category
                </a>
                <a href="manage_subcategories.php" class="text-gray-600 hover:text-blue-600 font-medium">
                    Sub Category
                </a>
                <a href="manage_machines.php" class="text-gray-600 hover:text-blue-600 font-medium">
                    Machines
                </a>
                <a href="store_machines.php" class="text-gray-600 hover:text-blue-600 font-medium">
                    Store Machines
                </a>
            </div>
        </div>

        <!-- Content Area -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <!-- Category Card -->
            <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Categories</h3>
                    <i class="fas fa-tags text-blue-500"></i>
                </div>
                <p class="text-gray-600 mb-4">Manage product categories</p>
                <a href="manage_categories.php" class="text-blue-600 hover:text-blue-700 font-medium">
                    Manage →
                </a>
            </div>

            <!-- Sub Category Card -->
            <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Sub Categories</h3>
                    <i class="fas fa-sitemap text-green-500"></i>
                </div>
                <p class="text-gray-600 mb-4">Manage product sub-categories</p>
                <a href="manage_subcategories.php" class="text-blue-600 hover:text-blue-700 font-medium">
                    Manage →
                </a>
            </div>

            <!-- Item Card -->
            <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Machines</h3>
                    <i class="fas fa-box text-purple-500"></i>
                </div>
                <p class="text-gray-600 mb-4">Manage store Machines</p>
                <a href="manage_machines.php" class="text-blue-600 hover:text-blue-700 font-medium">
                    Manage →
                </a>
            </div>

            <!-- Store Items Card -->
            <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Store Machines</h3>
                    <i class="fas fa-store text-orange-500"></i>
                </div>
                <p class="text-gray-600 mb-4">View all store Machines</p>
                <a href="store_machines.php" class="text-blue-600 hover:text-blue-700 font-medium">
                    View All →
                </a>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Quick Statistics</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="p-4 border rounded-lg">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm text-gray-500">Total Categories</p>
                        <i class="fas fa-tags text-blue-500"></i>
                    </div>
                    <p class="text-2xl font-bold text-gray-800"><?php echo $categoryCount; ?></p>
                </div>
                
                <div class="p-4 border rounded-lg">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm text-gray-500">Total Subcategories</p>
                        <i class="fas fa-sitemap text-green-500"></i>
                    </div>
                    <p class="text-2xl font-bold text-gray-800"><?php echo $subcategoryCount; ?></p>
                </div>
                
                <div class="p-4 border rounded-lg">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm text-gray-500">Total Machines</p>
                        <i class="fas fa-box text-purple-500"></i>
                    </div>
                    <p class="text-2xl font-bold text-gray-800"><?php echo $machineCount; ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Font Awesome for icons -->
    <script src="https://kit.fontawesome.com/your-code.js" crossorigin="anonymous"></script>
</body>
</html>