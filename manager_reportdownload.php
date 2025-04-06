<?php
include 'config.php';
session_start();

// Check if user is logged in as manager
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'manager') {
    header('Location: login.php');
    exit();
}

// Handle download requests
if (isset($_POST['download'])) {
    $type = $_POST['report_type'];
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $type . '_report.csv"');
    
    $output = fopen('php://output', 'w');
    
    switch ($type) {
        case 'users':
            fputcsv($output, ['User ID', 'Full Name', 'Email', 'Phone', 'Created At']);
            $query = "SELECT user_id, full_name, email, phone, created_at FROM users WHERE role = 'user'";
            break;
            
        case 'machines':
            fputcsv($output, ['Machine ID', 'Name', 'Category', 'Subcategory', 'Status', 'Rate']);
            $query = "SELECT m.machine_id, m.name, c.name as category, s.name as subcategory, m.status, m.rate 
                     FROM machines m 
                     LEFT JOIN categories c ON m.category_id = c.category_id 
                     LEFT JOIN subcategories s ON m.subcategory_id = s.subcategory_id";
            break;
            
        case 'rented_machines':
            fputcsv($output, ['Machine Name', 'Status', 'Current Renter', 'Rental Start', 'Rental End', 'Amount']);
            $query = "SELECT m.name, m.status, u.full_name, r.start_date, r.end_date, r.total_amount 
                     FROM machines m 
                     LEFT JOIN rentals r ON m.machine_id = r.machine_id 
                     LEFT JOIN users u ON r.user_id = u.user_id 
                     WHERE m.status = 'rented'";
            break;
            
        case 'categories':
            fputcsv($output, ['Category ID', 'Name', 'Description', 'Total Machines']);
            $query = "SELECT c.*, COUNT(m.machine_id) as total_machines 
                     FROM categories c 
                     LEFT JOIN machines m ON c.category_id = m.category_id 
                     GROUP BY c.category_id";
            break;
            
        case 'subcategories':
            fputcsv($output, ['Subcategory ID', 'Category', 'Name', 'Description', 'Total Machines']);
            $query = "SELECT s.*, c.name as category, COUNT(m.machine_id) as total_machines 
                     FROM subcategories s 
                     LEFT JOIN categories c ON s.category_id = c.category_id 
                     LEFT JOIN machines m ON s.subcategory_id = m.subcategory_id 
                     GROUP BY s.subcategory_id";
            break;
            
        case 'rental_history':
            fputcsv($output, ['Rental ID', 'User Name', 'Machine', 'Start Date', 'End Date', 'Days', 'Amount Paid', 'Status']);
            $query = "SELECT r.rental_id, u.full_name, m.name as machine, r.start_date, r.end_date, 
                     DATEDIFF(r.end_date, r.start_date) as days, r.total_amount, r.status 
                     FROM rentals r 
                     JOIN users u ON r.user_id = u.user_id 
                     JOIN machines m ON r.machine_id = m.machine_id";
            break;
    }
    
    if (isset($query)) {
        $result = $conn->query($query);
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, $row);
        }
    }
    
    fclose($output);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download Reports - GEAR EQUIP</title>
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
                
                <a href="manager_reports.php" class="block px-4 py-2 rounded-lg hover:bg-gray-50 text-gray-700 transition-colors">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-chart-bar w-5"></i>
                        <span>Reports</span>
                    </div>
                </a>

                <a href="manager_reportdownload.php" class="block px-4 py-2 rounded-lg bg-blue-50 text-blue-700">
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
            <div class="flex items-center space-x-4">
                <a href="manager_reports.php" class="flex items-center text-gray-600 hover:text-gray-800 transition-colors">
                    <i class="fas fa-arrow-left text-lg"></i>
                    <span class="ml-2">Back</span>
                </a>
                <h1 class="text-2xl font-bold text-gray-800">Download Reports</h1>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Users Report -->
                <div class="bg-gray-50 rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-4">Users Report</h3>
                    <p class="text-gray-600 mb-4">Download complete user information including contact details.</p>
                    <form method="POST">
                        <input type="hidden" name="report_type" value="users">
                        <button type="submit" name="download" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 flex items-center">
                            <i class="fas fa-download mr-2"></i> Download CSV
                        </button>
                    </form>
                </div>

                <!-- Machines Report -->
                <div class="bg-gray-50 rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-4">Machines Report</h3>
                    <p class="text-gray-600 mb-4">Download complete machine inventory with categories and rates.</p>
                    <form method="POST">
                        <input type="hidden" name="report_type" value="machines">
                        <button type="submit" name="download" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 flex items-center">
                            <i class="fas fa-download mr-2"></i> Download CSV
                        </button>
                    </form>
                </div>

                <!-- Rented Machines Report -->
                <div class="bg-gray-50 rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-4">Rented Machines Report</h3>
                    <p class="text-gray-600 mb-4">Download current rental status of all machines.</p>
                    <form method="POST">
                        <input type="hidden" name="report_type" value="rented_machines">
                        <button type="submit" name="download" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 flex items-center">
                            <i class="fas fa-download mr-2"></i> Download CSV
                        </button>
                    </form>
                </div>

                <!-- Categories Report -->
                <div class="bg-gray-50 rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-4">Categories Report</h3>
                    <p class="text-gray-600 mb-4">Download category information with machine counts.</p>
                    <form method="POST">
                        <input type="hidden" name="report_type" value="categories">
                        <button type="submit" name="download" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 flex items-center">
                            <i class="fas fa-download mr-2"></i> Download CSV
                        </button>
                    </form>
                </div>

                <!-- Subcategories Report -->
                <div class="bg-gray-50 rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-4">Subcategories Report</h3>
                    <p class="text-gray-600 mb-4">Download subcategory information with machine counts.</p>
                    <form method="POST">
                        <input type="hidden" name="report_type" value="subcategories">
                        <button type="submit" name="download" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 flex items-center">
                            <i class="fas fa-download mr-2"></i> Download CSV
                        </button>
                    </form>
                </div>

                <!-- Rental History Report -->
                <div class="bg-gray-50 rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-4">Rental History Report</h3>
                    <p class="text-gray-600 mb-4">Download complete rental history with user and machine details.</p>
                    <form method="POST">
                        <input type="hidden" name="report_type" value="rental_history">
                        <button type="submit" name="download" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 flex items-center">
                            <i class="fas fa-download mr-2"></i> Download CSV
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 