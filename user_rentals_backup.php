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
$rentals_query = "SELECT r.rental_id, r.machine_id, r.user_id, r.start_date, r.end_date, 
                  r.total_amount, r.status, r.payment_status, r.created_at, 
                  m.name as machine_name, m.image_url 
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
    <title>User Rentals - GEAR EQUIP</title>
    <link href="styles.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</head>
<body>
    

    <div class="content">
        <?php include 'nav.php'; ?>

        <div class="container mx-auto px-4 py-8 mt-32">
            <div class="flex justify-between items-center mb-8">
                <a href="dashboard.php" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition duration-300">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Dashboard
                </a>
                <h1 class="text-3xl font-bold">Your Rentals</h1>
            </div>

            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Machine</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Image</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Start Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">End Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Additional Payment</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invoice</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Refund Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php while ($rental = $rentals->fetch_assoc()): ?>
                        <tr>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($rental['machine_name']); ?></td>
                            <td class="px-6 py-4">
                                <img src="<?php echo htmlspecialchars($rental['image_url']); ?>" 
                                     class="w-20 h-20 object-cover rounded-lg" 
                                     alt="<?php echo htmlspecialchars($rental['machine_name']); ?>">
                            </td>
                            <td class="px-6 py-4"><?php echo date('M d, Y', strtotime($rental['start_date'])); ?></td>
                            <td class="px-6 py-4"><?php echo date('M d, Y', strtotime($rental['end_date'])); ?></td>
                            <td class="px-6 py-4">₹<?php echo number_format($rental['total_amount'], 2); ?></td>
                            <td class="px-6 py-4">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?php echo $rental['status'] == 'active' ? 'bg-green-100 text-green-800' : 
                                        ($rental['status'] == 'completed' ? 'bg-blue-100 text-blue-800' : 
                                        'bg-gray-100 text-gray-800'); ?>">
                                    <?php echo ucfirst($rental['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <?php
                                // Check for pending updates
                                $update_query = "SELECT * FROM rental_updates 
                                                 WHERE rental_id = ? AND update_type = 'increase' 
                                                 AND payment_status = 'pending'";
                                $stmt = mysqli_prepare($conn, $update_query);
                                mysqli_stmt_bind_param($stmt, "i", $rental['rental_id']);
                                mysqli_stmt_execute($stmt);
                                $update = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
                                
                                if ($update): 
                                    // Convert amount to paise (Razorpay requires amount in smallest currency unit)
                                    $amount_in_paise = (int)($update['difference_amount'] * 100);
                                    $payment_data = [
                                        'rental_id' => $rental['rental_id'],
                                        'amount' => $amount_in_paise,
                                        'update_id' => $update['update_id']
                                    ];
                                ?>
                                    <button 
                                        onclick="initiateAdditionalPayment(<?php echo htmlspecialchars(json_encode($payment_data)); ?>)" 
                                        class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600"
                                        type="button">
                                        Pay ₹<?php echo number_format($update['difference_amount'], 2); ?>
                                    </button>
                                    <script>
                                        // Debug: Print payment data
                                        console.log('Payment data for rental <?php echo $rental['rental_id']; ?>:', 
                                            <?php echo json_encode($payment_data); ?>);
                                    </script>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <button onclick="generateInvoice(<?php echo $rental['rental_id']; ?>)" 
                                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Invoice
                                </button>
                            </td>
                            <td class="px-6 py-4">
                                <?php
                                // Check for refunds
                                $refund_query = "SELECT r.*, ru.difference_amount 
                                                 FROM refunds r
                                                 JOIN rental_updates ru ON r.update_id = ru.update_id
                                                 WHERE r.rental_id = ? 
                                                 ORDER BY r.created_at DESC 
                                                 LIMIT 1";  // Get the latest refund status
                                $stmt = mysqli_prepare($conn, $refund_query);
                                mysqli_stmt_bind_param($stmt, "i", $rental['rental_id']);
                                mysqli_stmt_execute($stmt);
                                $refund = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
                                
                                if ($refund): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php 
                                        switch($refund['status']) {
                                            case 'processed':  // Changed from 'refunded' to 'processed'
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
                                        switch($refund['status']) {
                                            case 'processed':  // Changed from 'refunded' to 'processed'
                                                echo 'Refund Completed - ₹' . number_format($refund['amount'], 2);
                                                break;
                                            case 'pending':
                                                echo 'Refund Pending - ₹' . number_format($refund['amount'], 2);
                                                break;
                                            case 'failed':
                                                echo 'Refund Failed - ₹' . number_format($refund['amount'], 2);
                                                break;
                                        }
                                        ?>
                                    </span>
                                    <?php 
                                    // Debug output
                                    error_log("Refund status for rental {$rental['rental_id']}: " . $refund['status']);
                                    ?>
                                <?php else: ?>
                                    <!-- No refund record found -->
                                    <span class="text-gray-500">No refund</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add this modal for invoice -->
    <div id="invoiceModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-4/5 md:w-2/3 lg:w-1/2 shadow-lg rounded-md bg-white">
            <div id="invoiceContent" class="mt-3">
                <!-- Invoice content will be loaded here -->
            </div>
        </div>
    </div>

    <script>
    function generateInvoice(rentalId) {
        fetch(`generate_invoice.php?rental_id=${rentalId}`)
            .then(response => response.text())
            .then(data => {
                document.getElementById('invoiceContent').innerHTML = data;
                document.getElementById('invoiceModal').classList.remove('hidden');
            });
    }

    function initiateAdditionalPayment(data) {
        console.log('Payment initiated with data:', data); // Debug log

        // Create Razorpay options
        var options = {
            key: "rzp_test_e233tPrR8WUuea",
            amount: data.amount,
            currency: "INR",
            name: "GEAR EQUIP",
            description: "Additional Payment for Rental #" + data.rental_id,
            handler: function (response) {
                console.log('Payment successful:', response); // Debug log
                verifyAdditionalPayment({
                    razorpay_payment_id: response.razorpay_payment_id,
                    rental_id: data.rental_id,
                    update_id: data.update_id,
                    amount: data.amount
                });
            },
            prefill: {
                name: "<?php echo isset($_SESSION['full_name']) ? $_SESSION['full_name'] : ''; ?>",
                email: "<?php echo isset($_SESSION['email']) ? $_SESSION['email'] : ''; ?>"
            },
            theme: {
                color: "#EAB308"
            },
            modal: {
                ondismiss: function() {
                    console.log('Payment modal dismissed'); // Debug log
                }
            }
        };

        try {
            console.log('Creating Razorpay instance with options:', options); // Debug log
            var rzp = new Razorpay(options);
            rzp.on('payment.failed', function (response){
                console.log('Payment failed:', response); // Debug log
                alert('Payment failed. Please try again.');
            });
            rzp.open();
        } catch (error) {
            console.error('Error creating Razorpay instance:', error); // Debug log
            alert('Error initializing payment. Please try again.');
        }
    }

    function verifyAdditionalPayment(paymentData) {
        fetch('verify_additional_payment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(paymentData)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                alert('Payment successful!');
                location.reload();
            } else {
                alert('Payment verification failed: ' + (result.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error processing payment. Please try again.');
        });
    }

    // Close modal when clicking outside
    document.getElementById('invoiceModal').addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.add('hidden');
        }
    });
    </script>
</body>
</html> 