<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['rental_id'])) {
    exit('Unauthorized access');
}

$rental_id = $_GET['rental_id'];
$user_id = $_SESSION['user_id'];

// Get rental details with machine and user information
$query = "SELECT r.*, m.name as machine_name, m.image_url, m.daily_rate,
          u.full_name, u.email, u.phone
          FROM rentals r
          JOIN machines m ON r.machine_id = m.machine_id
          JOIN users u ON r.user_id = u.user_id
          WHERE r.rental_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $rental_id);
$stmt->execute();
$rental = $stmt->get_result()->fetch_assoc();

if (!$rental) {
    exit('Invoice not found');
}

// Calculate rental duration
$start_date = new DateTime($rental['start_date']);
$end_date = new DateTime($rental['end_date']);
$duration = $start_date->diff($end_date)->days + 1;

// After getting rental details, add this query to fetch additional payments
$additional_query = "SELECT ru.*, p.razorpay_payment_id 
                    FROM rental_updates ru
                    LEFT JOIN payments p ON ru.payment_id = p.razorpay_payment_id
                    WHERE ru.rental_id = ? AND ru.payment_status = 'paid'";
$stmt = $conn->prepare($additional_query);
$stmt->bind_param("i", $rental_id);
$stmt->execute();
$additional_payments = $stmt->get_result();

// After the additional payments query, add this query to fetch refund information
$refund_query = "SELECT r.*, ru.difference_amount, ru.old_start_date, ru.old_end_date,
                        ru.new_start_date, ru.new_end_date
                 FROM refunds r
                 JOIN rental_updates ru ON r.update_id = ru.update_id
                 WHERE r.rental_id = ?";
$stmt = $conn->prepare($refund_query);
$stmt->bind_param("i", $rental_id);
$stmt->execute();
$refunds = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice - GEAR EQUIP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="styles.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php include 'nav.php'; ?>

    <div class="container mx-auto px-4 py-8 mt-24">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <!-- Invoice Header -->
            <div class="flex justify-between items-start mb-8">
                <div>
                    <h1 class="text-2xl font-bold mb-2">Invoice #INV-<?php echo str_pad($rental_id, 6, '0', STR_PAD_LEFT); ?></h1>
                    <p class="text-gray-600">Date: <?php echo date('M d, Y'); ?></p>
                </div>
                <div class="text-right">
                    <img src="images/logo.png" alt="GEAR EQUIP" class="h-12 mb-4">
                    <p class="font-bold">GEAR EQUIP</p>
                    <p class="text-gray-600">123 Equipment Street</p>
                    <p class="text-gray-600">Mumbai, Maharashtra</p>
                    <p class="text-gray-600">India - 400001</p>
                </div>
            </div>

            <!-- Customer & Payment Info -->
            <div class="grid grid-cols-2 gap-8 mb-8">
                <div>
                    <h2 class="text-lg font-semibold mb-4">Bill To:</h2>
                    <p class="font-medium"><?php echo htmlspecialchars($rental['full_name']); ?></p>
                    <p class="text-gray-600"><?php echo htmlspecialchars($rental['email']); ?></p>
                    <p class="text-gray-600"><?php echo htmlspecialchars($rental['phone']); ?></p>
                </div>
                <div>
                    <h2 class="text-lg font-semibold mb-4">Payment Status:</h2>
                    <p class="inline-flex items-center px-3 py-1 rounded-full text-sm
                        <?php echo $rental['status'] === 'completed' ? 
                            'bg-green-100 text-green-800' : 
                            'bg-yellow-100 text-yellow-800'; ?>">
                        <?php echo ucfirst($rental['status']); ?>
                    </p>
                </div>
            </div>

            <!-- Rental Details -->
            <div class="mb-8">
                <h2 class="text-lg font-semibold mb-4">Rental Details</h2>
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-gray-600">Machine:</p>
                            <p class="font-medium"><?php echo htmlspecialchars($rental['machine_name']); ?></p>
                        </div>
                        <div>
                            <p class="text-gray-600">Rental Period:</p>
                            <p class="font-medium">
                                <?php echo date('M d, Y', strtotime($rental['start_date'])); ?> - 
                                <?php echo date('M d, Y', strtotime($rental['end_date'])); ?>
                            </p>
                        </div>
                        <div>
                            <p class="text-gray-600">Daily Rate:</p>
                            <p class="font-medium">₹<?php echo number_format($rental['daily_rate'], 2); ?></p>
                        </div>
                        <div>
                            <p class="text-gray-600">Security Deposit:</p>
                            <p class="font-medium">₹<?php echo number_format($rental['security_deposit'], 2); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Summary -->
            <div class="mb-8">
                <h2 class="text-lg font-semibold mb-4">Payment Summary</h2>
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Rental Amount:</span>
                            <span class="font-medium">₹<?php echo number_format($rental['total_amount'], 2); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Security Deposit:</span>
                            <span class="font-medium">₹<?php echo number_format($rental['security_deposit'], 2); ?></span>
                        </div>
                        <div class="border-t pt-2 mt-2">
                            <div class="flex justify-between font-bold">
                                <span>Total Amount:</span>
                                <span>₹<?php echo number_format($rental['total_amount'] + $rental['security_deposit'], 2); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Terms and Notes -->
            <div class="text-sm text-gray-600 mb-8">
                <h2 class="text-lg font-semibold text-gray-800 mb-2">Terms & Conditions:</h2>
                <ul class="list-disc pl-5 space-y-1">
                    <li>Security deposit will be refunded after the equipment is returned in good condition.</li>
                    <li>Late returns will incur additional charges as per the rental agreement.</li>
                    <li>Any damage to the equipment will be deducted from the security deposit.</li>
                </ul>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-center space-x-4 pt-6 border-t">
                <button onclick="window.print()" 
                        class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition-colors">
                    Print Invoice
                </button>
                <button onclick="window.history.back()" 
                        class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition-colors">
                    Back
                </button>
            </div>
        </div>
    </div>

    <script>
        // Add print-specific styles
        window.onbeforeprint = function() {
            document.querySelector('nav').style.display = 'none';
            document.querySelector('.container').style.marginTop = '0';
        };
        window.onafterprint = function() {
            document.querySelector('nav').style.display = 'block';
            document.querySelector('.container').style.marginTop = '6rem';
        };
    </script>
</body>
</html>