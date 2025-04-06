<?php
include 'config.php';
session_start();

// Check if user is logged in as manager
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'manager') {
    header('Location: login.php');
    exit();
}

// Fetch all rented machines with rental and user details
$query = "SELECT m.*, r.rental_id, r.start_date, r.end_date, r.total_amount, 
          u.full_name as renter_name, u.email as renter_email, u.phone as renter_phone
          FROM machines m 
          JOIN rentals r ON m.machine_id = r.machine_id
          JOIN users u ON r.user_id = u.user_id
          WHERE m.status = 'rented' AND r.status = 'active'
          ORDER BY r.start_date DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rented Machines - GEAR EQUIP</title>
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
                <a href="rented_machines.php" class="block px-4 py-2 rounded-lg bg-blue-50 text-blue-700 font-medium">
                    <i class="fas fa-truck-loading mr-3"></i>Rented Machines
                </a>
                <!-- Other navigation links -->
            </div>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="ml-64 p-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-bold text-gray-800">Rented Machines</h1>
            <div class="bg-white rounded-lg shadow px-4 py-2">
                <span class="text-sm text-gray-500">Total Rented:</span>
                <span class="font-bold text-blue-600 ml-2"><?php echo mysqli_num_rows($result); ?></span>
            </div>
        </div>

        <!-- Rented Machines Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php while ($machine = mysqli_fetch_assoc($result)): ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-6">
                    <!-- Machine Image and Details -->
                    <div class="flex items-center mb-4">
                        <img src="<?php echo htmlspecialchars($machine['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($machine['name']); ?>"
                             class="w-24 h-24 object-cover rounded-lg mr-4">
                        <div>
                            <h3 class="font-semibold text-lg text-gray-800">
                                <?php echo htmlspecialchars($machine['name']); ?>
                            </h3>
                            <p class="text-sm text-gray-500">
                                ID: #<?php echo $machine['machine_id']; ?>
                            </p>
                        </div>
                    </div>

                    <!-- Rental Details -->
                    <div class="space-y-2 mb-4">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Rental Period:</span>
                            <span class="font-medium">
                                <?php echo date('M d, Y', strtotime($machine['start_date'])); ?> - 
                                <?php echo date('M d, Y', strtotime($machine['end_date'])); ?>
                            </span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Amount:</span>
                            <span class="font-medium">₹<?php echo number_format($machine['total_amount'], 2); ?></span>
                        </div>
                    </div>

                    <!-- Renter Information -->
                    <div class="border-t pt-4">
                        <h4 class="font-medium text-gray-700 mb-2">Renter Details</h4>
                        <div class="space-y-1 text-sm">
                            <p class="text-gray-600">
                                <i class="fas fa-user mr-2"></i>
                                <?php echo htmlspecialchars($machine['renter_name']); ?>
                            </p>
                            <p class="text-gray-600">
                                <i class="fas fa-envelope mr-2"></i>
                                <?php echo htmlspecialchars($machine['renter_email']); ?>
                            </p>
                            <p class="text-gray-600">
                                <i class="fas fa-phone mr-2"></i>
                                <?php echo htmlspecialchars($machine['renter_phone']); ?>
                            </p>
                        </div>
                    </div>

                    <!-- View Invoice Button -->
                    <div class="mt-4">
                        <a href="generate_invoice.php?rental_id=<?php echo $machine['rental_id']; ?>" 
                           class="inline-flex items-center justify-center w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-file-invoice mr-2"></i>
                            View Invoice
                        </a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</body>
</html> 