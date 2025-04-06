<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user's rental history
$user_id = $_SESSION['user_id'];
$rentals_query = "SELECT r.*, m.name as machine_name, m.daily_rate 
                  FROM rentals r 
                  JOIN machines m ON r.machine_id = m.machine_id 
                  WHERE r.user_id = ? 
                  ORDER BY r.created_at DESC";

$stmt = $conn->prepare($rentals_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$rentals = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - GEAR EQUIP</title>
    <link href="styles.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f3f4f6; /* Light background for the entire page */
        }
        .sidebar {
            width: 250px;
            background: linear-gradient(180deg, #f8fafc, #e5e7eb); /* Gradient background */
            padding: 20px;
            border-right: 1px solid #e5e7eb; /* Light border */
            position: fixed; /* Fixed position */
            height: calc(100% - 50px); /* Full height minus the top margin */
            margin-top: 50px; /* Move down */
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1); /* Subtle shadow */
        }
        .sidebar h2 {
            font-size: 1.8rem; /* Increased font size */
            margin-bottom: 20px;
            text-align: center; /* Center align the title */
            color:rgb(17, 17, 18); /* Emphasized color */
        }
        .sidebar a {
            display: block;
            padding: 12px; /* Increased padding */
            margin-bottom: 10px;
            color: #1f2937; /* Dark text */
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s, transform 0.3s; /* Added transform for hover */
        }
        .sidebar a:hover {
            background-color: #d1fae5; /* Light hover effect */
            transform: scale(1.05); /* Slightly enlarge on hover */
        }
        .content {
            margin-left: 270px; /* Space for sidebar */
            padding: 20px;
            margin-top: 50px; /* Added margin to move content down */
        }
        .container {
            background-color: white; /* White background for the content area */
            border-radius: 8px; /* Rounded corners */
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); /* Shadow effect */
            padding: 20px;
        }
        .summary-card {
            background-color: #ffffff; /* White background */
            border-radius: 8px; /* Rounded corners */
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); /* Shadow effect */
            transition: transform 0.3s, box-shadow 0.3s; /* Animation on hover */
        }
        .summary-card:hover {
            transform: translateY(-5px); /* Lift effect on hover */
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2); /* Deeper shadow on hover */
        }
        .summary-card h3 {
            display: flex;
            align-items: center;
        }
        .summary-card h3 i {
            margin-right: 10px; /* Space between icon and text */
            color: #2563eb; /* Icon color */
        }
        table {
            width: 100%;
            border-collapse: collapse; /* Remove space between cells */
            border-radius: 8px; /* Rounded corners */
            overflow: hidden; /* Hide overflow for rounded corners */
        }
        th, td {
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #f9fafb; /* Light gray for header */
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f3f4f6; /* Alternating row colors */
        }
        tr:hover {
            background-color: #e5e7eb; /* Highlight row on hover */
        }
        .button {
            background: linear-gradient(90deg, #2563eb, #1d4ed8); /* Gradient background */
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px; /* Rounded corners */
            cursor: pointer;
            transition: background-color 0.3s, transform 0.3s;
        }
        .button:hover {
            transform: scale(1.05); /* Slightly enlarge on hover */
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>User Dashboard</h2>
        <a href="user_rentals.php" class="active">User Rentals</a>
        <a href="cart.php">Cart</a>
        <a href="user_feedback.php">Feedback</a>
    </div>

    <div class="content">
        <?php include 'nav.php'; ?>

        <div class="container mx-auto px-4 py-8 mt-32">
            <!-- Add Book Equipment Button -->
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></h1>
                <a href="index.php" 
                   class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg 
                          transition duration-300 ease-in-out transform hover:scale-105">
                    Book Equipment
                </a>
            </div>

            <!-- Dashboard Summary -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-xl font-semibold mb-2">Active Rentals</h3>
                    <p class="text-3xl text-blue-600">
                        <?php 
                        $active_rentals = mysqli_query($conn, 
                            "SELECT COUNT(*) as count FROM rentals 
                             WHERE user_id = $user_id AND status = 'active'");
                        $count = mysqli_fetch_assoc($active_rentals);
                        echo $count['count'];
                        ?>
                    </p>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-xl font-semibold mb-2">Total Spent</h3>
                    <p class="text-3xl text-green-600">₹
                        <?php 
                        $total_spent = mysqli_query($conn, 
                            "SELECT SUM(total_amount) as total FROM rentals 
                             WHERE user_id = $user_id AND status != 'cancelled'");
                        $total = mysqli_fetch_assoc($total_spent);
                        echo number_format($total['total'] ?? 0, 2);
                        ?>
                    </p>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-xl font-semibold mb-2">Completed Rentals</h3>
                    <p class="text-3xl text-purple-600">
                        <?php 
                        $completed_rentals = mysqli_query($conn, 
                            "SELECT COUNT(*) as count FROM rentals 
                             WHERE user_id = $user_id AND status = 'completed'");
                        $completed = mysqli_fetch_assoc($completed_rentals);
                        echo $completed['count'];
                        ?>
                    </p>
                </div>
            </div>

            <!-- Recent Rentals -->
            <h2 class="text-2xl font-bold mb-4">Recent Rentals</h2>
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Machine</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rental Period</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Refund Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php 
                        // Update the query to include refund information
                        $rentals_query = "SELECT r.*, 
                                       m.name as machine_name, 
                                       m.daily_rate,
                                       rf.status as refund_status,
                                       rf.amount as refund_amount,
                                       ru.new_start_date,
                                       ru.new_end_date
                                FROM rentals r 
                                JOIN machines m ON r.machine_id = m.machine_id 
                                LEFT JOIN rental_updates ru ON r.rental_id = ru.rental_id
                                LEFT JOIN refunds rf ON ru.update_id = rf.update_id
                                WHERE r.user_id = ? 
                                ORDER BY r.created_at DESC";

                        $stmt = $conn->prepare($rentals_query);
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $rentals = $stmt->get_result();

                        while ($rental = $rentals->fetch_assoc()): 
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">
                                    <?php echo htmlspecialchars($rental['machine_name']); ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    ID: #<?php echo $rental['rental_id']; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm">
                                    <?php if ($rental['new_start_date'] && $rental['new_end_date']): ?>
                                        <div class="line-through text-gray-500">
                                            <?php echo date('M d, Y', strtotime($rental['start_date'])); ?> - 
                                            <?php echo date('M d, Y', strtotime($rental['end_date'])); ?>
                                        </div>
                                        <div class="text-blue-600 font-medium">
                                            Updated to:<br>
                                            <?php echo date('M d, Y', strtotime($rental['new_start_date'])); ?> - 
                                            <?php echo date('M d, Y', strtotime($rental['new_end_date'])); ?>
                                        </div>
                                    <?php else: ?>
                                        <?php echo date('M d, Y', strtotime($rental['start_date'])); ?> - 
                                        <?php echo date('M d, Y', strtotime($rental['end_date'])); ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm">
                                    <div class="font-medium">₹<?php echo number_format($rental['total_amount'], 2); ?></div>
                                    <?php if ($rental['refund_amount']): ?>
                                        <div class="text-green-600 text-sm">
                                            Refund: ₹<?php echo number_format($rental['refund_amount'], 2); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?php 
                                    switch($rental['status']) {
                                        case 'active':
                                            echo 'bg-green-100 text-green-800';
                                            break;
                                        case 'completed':
                                            echo 'bg-blue-100 text-blue-800';
                                            break;
                                        case 'pending':
                                            echo 'bg-yellow-100 text-yellow-800';
                                            break;
                                        case 'cancelled':
                                            echo 'bg-red-100 text-red-800';
                                            break;
                                        default:
                                            echo 'bg-gray-100 text-gray-800';
                                    }
                                    ?>">
                                    <?php echo ucfirst($rental['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($rental['refund_status']): ?>
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php 
                                        switch($rental['refund_status']) {
                                            case 'processed':
                                                echo 'bg-green-100 text-green-800';
                                                break;
                                            case 'pending':
                                                echo 'bg-yellow-100 text-yellow-800';
                                                break;
                                            case 'failed':
                                                echo 'bg-red-100 text-red-800';
                                                break;
                                        }
                                        ?>">
                                        <?php 
                                        switch($rental['refund_status']) {
                                            case 'processed':
                                                echo '✓ Refund Paid';
                                                break;
                                            case 'pending':
                                                echo '⏳ Refund Pending';
                                                break;
                                            case 'failed':
                                                echo '✕ Refund Failed';
                                                break;
                                        }
                                        ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-gray-500 text-sm">No Refund</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <a href="generate_invoice.php?rental_id=<?php echo $rental['rental_id']; ?>" 
                                   class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                    View Invoice
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>