<?php
include 'config.php';
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: admin_login.php');
    exit();
}

// Fetch all machines
$sql = "SELECT * FROM machines ORDER BY machine_id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Machines Management - GEAR EQUIP</title>
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
                <a href="admin_dashboard.php" class="block px-4 py-2 rounded-lg hover:bg-gray-50 text-gray-700">
                    <i class="fas fa-home mr-3"></i>Overview
                </a>
                <a href="admin_users.php" class="block px-4 py-2 rounded-lg hover:bg-gray-50 text-gray-700">
                    <i class="fas fa-users mr-3"></i>Users Management
                </a>
                <a href="admin_managers.php" class="block px-4 py-2 rounded-lg hover:bg-gray-50 text-gray-700">
                    <i class="fas fa-user-tie mr-3"></i>Managers
                </a>
                <a href="admin_machines.php" class="block px-4 py-2 rounded-lg bg-blue-50 text-blue-700 font-medium">
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
            <h1 class="text-2xl font-bold text-gray-800">Machines Management</h1>
            <div>
                <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition">
                    Logout
                </a>
            </div>
        </div>

        <!-- Machines Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php while($machine = $result->fetch_assoc()): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <img src="<?php echo htmlspecialchars($machine['image_url']); ?>" 
                         alt="<?php echo htmlspecialchars($machine['name']); ?>"
                         class="w-full h-48 object-cover">
                    <div class="p-4">
                        <h3 class="text-lg font-semibold text-gray-800">
                            <?php echo htmlspecialchars($machine['name']); ?>
                        </h3>
                        <p class="text-gray-600 mt-1">
                            <?php echo htmlspecialchars($machine['description']); ?>
                        </p>
                        <div class="mt-4 flex justify-between items-center">
                            <span class="text-lg font-bold text-gray-800">
                                ₹<?php echo number_format($machine['daily_rate'] ?? 0); ?>/day
                            </span>
                            <span class="px-3 py-1 rounded-full text-sm font-semibold 
                                <?php echo $machine['status'] == 'available' ? 
                                    'bg-green-100 text-green-800' : 
                                    'bg-red-100 text-red-800'; ?>">
                                <?php echo ucfirst($machine['status']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Font Awesome for icons -->
    <script src="https://kit.fontawesome.com/your-code.js" crossorigin="anonymous"></script>

    <script>
    function deleteMachine(machineId) {
        if (confirm('Are you sure you want to delete this machine? This action cannot be undone.')) {
            fetch('delete_machine.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `machine_id=${machineId}`
            })
            .then(response => response.text())
            .then(data => {
                if (data === 'success') {
                    location.reload();
                } else {
                    alert('Error deleting machine');
                }
            });
        }
    }
    </script>
</body>
</html> 