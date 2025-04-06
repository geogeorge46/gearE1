<?php
include 'config.php';
session_start();

// Check if user is logged in as manager
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'manager') {
    header('Location: login.php');
    exit();
}

// Fetch various statistics
$stats = [
    'users' => $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'")->fetch_assoc()['count'],
    'total_machines' => $conn->query("SELECT COUNT(*) as count FROM machines")->fetch_assoc()['count'],
    'rented_machines' => $conn->query("SELECT COUNT(*) as count FROM machines WHERE status = 'rented'")->fetch_assoc()['count'],
    'available_machines' => $conn->query("SELECT COUNT(*) as count FROM machines WHERE status = 'available'")->fetch_assoc()['count'],
    'categories' => $conn->query("SELECT COUNT(*) as count FROM categories")->fetch_assoc()['count'],
    'subcategories' => $conn->query("SELECT COUNT(*) as count FROM subcategories")->fetch_assoc()['count'],
    'active_rentals' => $conn->query("SELECT COUNT(*) as count FROM rentals WHERE status = 'active'")->fetch_assoc()['count'],
    'total_revenue' => $conn->query("SELECT SUM(total_amount) as total FROM rentals WHERE status != 'cancelled'")->fetch_assoc()['total']
];

// Fetch recent user registrations
$recent_users = $conn->query("SELECT * FROM users WHERE role = 'user' ORDER BY created_at DESC LIMIT 5");

// Fetch most rented machines
$popular_machines = $conn->query("
    SELECT m.name, m.image_url, COUNT(r.rental_id) as rental_count, SUM(r.total_amount) as revenue
    FROM machines m
    LEFT JOIN rentals r ON m.machine_id = r.machine_id
    GROUP BY m.machine_id
    ORDER BY rental_count DESC
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - GEAR EQUIP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 font-[Poppins]">
    <!-- Side Navigation -->
    <div class="fixed left-0 top-0 w-64 h-full bg-white shadow-lg z-50">
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
            <div class="space-y-4">
                <a href="manager_dashboard.php" class="block px-4 py-2 rounded-lg hover:bg-gray-50 text-gray-700 transition-colors">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-home w-5"></i>
                        <span>Overview</span>
                    </div>
                </a>
                
                <a href="manager_reports.php" class="block px-4 py-2 rounded-lg bg-blue-50 text-blue-700">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-chart-bar w-5"></i>
                        <span>Reports</span>
                    </div>
                </a>

                <a href="manager_reportdownload.php" class="block px-4 py-2 rounded-lg hover:bg-gray-50 text-gray-700 transition-colors">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-download w-5"></i>
                        <span>Download Reports</span>
                    </div>
                </a>
            </div>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="ml-64 p-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-bold text-gray-800">Reports & Statistics</h1>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <!-- Users Card -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-100 rounded-full">
                        <i class="fas fa-users text-blue-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-gray-500 text-sm font-medium">Total Users</h3>
                        <p class="text-2xl font-bold text-gray-800"><?php echo $stats['users']; ?></p>
                    </div>
                </div>
            </div>

            <!-- Machines Card -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 rounded-full">
                        <i class="fas fa-cogs text-green-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-gray-500 text-sm font-medium">Total Machines</h3>
                        <p class="text-2xl font-bold text-gray-800"><?php echo $stats['total_machines']; ?></p>
                    </div>
                </div>
            </div>

            <!-- Categories Card -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-purple-100 rounded-full">
                        <i class="fas fa-tags text-purple-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-gray-500 text-sm font-medium">Categories</h3>
                        <p class="text-2xl font-bold text-gray-800"><?php echo $stats['categories']; ?></p>
                    </div>
                </div>
            </div>

            <!-- Revenue Card -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-yellow-100 rounded-full">
                        <i class="fas fa-indian-rupee-sign text-yellow-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-gray-500 text-sm font-medium">Total Revenue</h3>
                        <p class="text-2xl font-bold text-gray-800">₹<?php echo number_format($stats['total_revenue']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <!-- Machine Statistics -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold mb-4">Machine Statistics</h2>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Available Machines</span>
                        <span class="font-semibold"><?php echo $stats['available_machines']; ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Rented Machines</span>
                        <span class="font-semibold"><?php echo $stats['rented_machines']; ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Active Rentals</span>
                        <span class="font-semibold"><?php echo $stats['active_rentals']; ?></span>
                    </div>
                </div>
            </div>

            <!-- Category Statistics -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold mb-4">Category Statistics</h2>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Total Categories</span>
                        <span class="font-semibold"><?php echo $stats['categories']; ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Total Subcategories</span>
                        <span class="font-semibold"><?php echo $stats['subcategories']; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Popular Machines -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h2 class="text-lg font-semibold mb-4">Most Popular Machines</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Machine</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rental Count</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php while ($machine = $popular_machines->fetch_assoc()): ?>
                        <tr>
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <img src="<?php echo htmlspecialchars($machine['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($machine['name']); ?>"
                                         class="w-10 h-10 rounded-full object-cover mr-3">
                                    <span class="font-medium"><?php echo htmlspecialchars($machine['name']); ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4"><?php echo $machine['rental_count']; ?></td>
                            <td class="px-6 py-4">₹<?php echo number_format($machine['revenue']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html> 