<?php
include 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Fetch cart items with machine details
$cart_query = "SELECT c.*, m.name, m.daily_rate, m.security_deposit, m.image_url 
               FROM cart c 
               JOIN machines m ON c.machine_id = m.machine_id 
               WHERE c.user_id = ?";
$stmt = mysqli_prepare($conn, $cart_query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$cart_result = mysqli_stmt_get_result($stmt);

// Calculate totals
$subtotal = 0;
$total_gst = 0;
$total_security_deposit = 0;

// Add this after fetching cart items
$unavailable_machines = [];

while($item = mysqli_fetch_assoc($cart_result)) {
    $rental_days = round((strtotime($item['end_date']) - strtotime($item['start_date'])) / (60 * 60 * 24));
    $item_subtotal = $item['daily_rate'] * $rental_days;
    $item_gst = $item_subtotal * 0.18;
    
    $subtotal += $item_subtotal;
    $total_gst += $item_gst;
    $total_security_deposit += $item['security_deposit'];

    // Check if machine is already rented
    $check_query = "SELECT status FROM machines WHERE machine_id = ?";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "i", $item['machine_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $machine = mysqli_fetch_assoc($result);
    
    if ($machine['status'] == 'rented') {
        $unavailable_machines[] = $item['name'];
    }
}

$grand_total = $subtotal + $total_gst + $total_security_deposit;
$total_in_paise = $grand_total * 100;

// Reset result pointer
mysqli_data_seek($cart_result, 0);

// Show warning if machines are unavailable
if (!empty($unavailable_machines)): ?>
    <div class="bg-red-50 border-l-4 border-red-500 mx-auto max-w-7xl mt-24 mb-6">
        <div class="p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-red-800 font-medium">Unavailable Items</h3>
                    <div class="mt-2 text-red-700">
                        <p class="text-sm">
                            The following machines are no longer available:
                            <span class="font-semibold"><?php echo implode(', ', $unavailable_machines); ?></span>
                        </p>
                    </div>
                    <div class="mt-3">
                        <a href="remove_unavailable.php" 
                           class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            Remove Unavailable Items
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - GEAR EQUIP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <style>
        .main-container {
            margin-top: 2rem !important; /* Reduced from 120px */
        }
        .page-title {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 30px;
            color: #1f2937;
        }
        .checkout-item {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-top: 1rem;
        }
        .item-image {
            max-width: 150px;
            height: 100px;
            object-fit: cover;
            display: block;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .item-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .rental-details {
            margin: 15px 0;
        }
        .cost-details {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-top: 15px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
        }
        .total-row {
            border-top: 2px solid #eee;
            margin-top: 10px;
            padding-top: 10px;
            font-weight: bold;
        }
        .pay-button {
            background: #2563eb;
            color: white;
            padding: 12px 24px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            width: 100%;
            margin-top: 20px;
        }
        .pay-button:hover {
            background: #1d4ed8;
        }
        .status-button {
            background-color: <?php echo $machine_status == 'available' ? '#10b981' : ($machine_status == 'rented' ? '#ef4444' : '#f59e0b'); ?>;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: default;
            margin-top: 10px;
        }
    </style>
</head>
<body style="background-color: #f3f4f6;">
    <?php include 'nav.php'; ?>

    <div class="main-container max-w-7xl mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-8">Checkout</h1>

        <?php while($item = mysqli_fetch_assoc($cart_result)): 
            $rental_days = ceil((strtotime($item['end_date']) - strtotime($item['start_date'])) / (60 * 60 * 24));
            $item_subtotal = $item['daily_rate'] * $rental_days;
            $item_gst = $item_subtotal * 0.18;

            // Check if machine is available
            $status_query = "SELECT status FROM machines WHERE machine_id = ?";
            $stmt = mysqli_prepare($conn, $status_query);
            mysqli_stmt_bind_param($stmt, "i", $item['machine_id']);
            mysqli_stmt_execute($stmt);
            $status_result = mysqli_stmt_get_result($stmt);
            $machine_status = mysqli_fetch_assoc($status_result)['status'];
        ?>
            <div class="checkout-item">
                <div class="item-content">
                    <div class="flex justify-between items-start">
                        <div class="flex items-start space-x-4">
                            <div class="image-container">
                                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>"
                                     class="w-24 h-24 object-cover rounded-lg">
                            </div>
                            
                            <div class="item-details">
                                <h2 class="text-xl font-bold"><?php echo htmlspecialchars($item['name']); ?></h2>
                                
                                <div class="rental-details mt-2">
                                    <p>Start: <?php echo date('d M Y', strtotime($item['start_date'])); ?></p>
                                    <p>End: <?php echo date('d M Y', strtotime($item['end_date'])); ?></p>
                                    <p>Duration: <?php echo $rental_days; ?> days</p>
                                </div>
                            </div>
                        </div>

                        <?php if ($machine_status == 'rented'): ?>
                        <button onclick="removeFromCart(<?php echo $item['machine_id']; ?>)" 
                                class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 transition-colors">
                            Remove
                        </button>
                        <?php endif; ?>
                    </div>

                    <div class="cost-details mt-4">
                        <div class="summary-row">
                            <span>Daily Rate:</span>
                            <span>₹<?php echo number_format($item['daily_rate'], 2); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Rental Cost (<?php echo $rental_days; ?> days):</span>
                            <span>₹<?php echo number_format($item_subtotal, 2); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>GST (18%):</span>
                            <span>₹<?php echo number_format($item_gst, 2); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Security Deposit:</span>
                            <span>₹<?php echo number_format($item['security_deposit'], 2); ?></span>
                        </div>
                    </div>

                    <!-- Status Button -->
                    <button class="status-button" style="
                        background-color: <?php echo $machine_status == 'available' ? '#10b981' : ($machine_status == 'rented' ? '#ef4444' : '#f59e0b'); ?>;
                        color: white;
                        padding: 5px 10px;
                        border: none;
                        border-radius: 4px;
                        cursor: default;
                        margin-top: 10px;
                    ">
                        <?php echo ucfirst($machine_status); ?>
                    </button>
                </div>
            </div>
        <?php endwhile; ?>

        <!-- Final Summary -->
        <div class="checkout-item">
            <h2 style="font-size: 24px; font-weight: bold; margin-bottom: 15px;">Order Summary</h2>
            <div class="cost-details">
                <div class="summary-row">
                    <span>Total Rental Cost:</span>
                    <span>₹<?php echo number_format($subtotal, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Total GST:</span>
                    <span>₹<?php echo number_format($total_gst, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Total Security Deposit:</span>
                    <span>₹<?php echo number_format($total_security_deposit, 2); ?></span>
                </div>
                <div class="summary-row total-row">
                    <span>Grand Total:</span>
                    <span>₹<?php echo number_format($grand_total, 2); ?></span>
                </div>
            </div>

            <button onclick="makePayment()" class="pay-button" <?php echo !empty($unavailable_machines) ? 'disabled style="background-color: #9ca3af; cursor: not-allowed;"' : ''; ?>>
                Pay Now - ₹<?php echo number_format($grand_total, 2); ?>
            </button>
        </div>
    </div>

    <script>
    function makePayment() {
        // First, set all machines to reserved status
        fetch('update_machine_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'reserve'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Continue with payment after machines are reserved
                initiateRazorpayPayment();
            } else {
                alert('Error reserving machines: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error reserving machines. Please try again.');
        });
    }

    function initiateRazorpayPayment() {
        var options = {
            "key": "rzp_test_e233tPrR8WUuea",
            "amount": "<?php echo $total_in_paise; ?>",
            "currency": "INR",
            "name": "GEAR EQUIP",
            "description": "Equipment Rental Payment",
            "image": "images/logo.png",
            "handler": function (response){
                verifyPayment(response);
            },
            "modal": {
                "ondismiss": function(){
                    // If payment modal is dismissed, reset machines to available
                    resetMachineStatus();
                }
            },
            "prefill": {
                "name": "<?php echo htmlspecialchars($_SESSION['full_name'] ?? ''); ?>",
                "email": "<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>"
            },
            "theme": {
                "color": "#2563eb"
            }
        };
        var rzp1 = new Razorpay(options);
        rzp1.open();
    }

    function resetMachineStatus() {
        fetch('update_machine_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'reset'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                console.error('Error resetting machine status:', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }

    function verifyPayment(response) {
        fetch('verify_payment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                razorpay_payment_id: response.razorpay_payment_id,
                amount: <?php echo $total_in_paise; ?>
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'payment_success.php';
            } else {
                resetMachineStatus(); // Reset machine status if payment fails
                alert('Payment verification failed: ' + data.message);
            }
        })
        .catch(error => {
            resetMachineStatus(); // Reset machine status if there's an error
            console.error('Verification error:', error);
            alert('Payment verification failed. Please try again.');
        });
    }

    // Add event listener to reset machine status when page is unloaded/closed
    window.addEventListener('beforeunload', function() {
        resetMachineStatus();
    });

    function removeFromCart(machineId) {
        fetch('remove_from_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                machine_id: machineId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert('Error removing item: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error removing item. Please try again.');
        });
    }
    </script>
</body>
</html> 