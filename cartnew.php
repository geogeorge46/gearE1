<?php
include 'config.php';
session_start();

// Add this at the beginning of cart.php to handle the add action
if (isset($_GET['action']) && $_GET['action'] === 'add' && isset($_GET['machine'])) {
    $machine_id = (int)$_GET['machine'];
    $user_id = $_SESSION['user_id'];

    // Check if machine exists and is available
    $check_machine = "SELECT status FROM machines WHERE machine_id = ?";
    $stmt = mysqli_prepare($conn, $check_machine);
    mysqli_stmt_bind_param($stmt, "i", $machine_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $machine = mysqli_fetch_assoc($result);

    if ($machine && $machine['status'] === 'available') {
        // Check if already in cart
        $check_cart = "SELECT cart_id FROM cart WHERE user_id = ? AND machine_id = ?";
        $stmt = mysqli_prepare($conn, $check_cart);
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $machine_id);
        mysqli_stmt_execute($stmt);
        $cart_result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($cart_result) === 0) {
            // Add to cart with default dates
            $start_date = date('Y-m-d');
            $end_date = date('Y-m-d', strtotime('+1 day'));
            
            $insert_query = "INSERT INTO cart (user_id, machine_id, start_date, end_date) VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($stmt, "iiss", $user_id, $machine_id, $start_date, $end_date);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success'] = "Machine added to cart successfully!";
            } else {
                $_SESSION['error'] = "Error adding machine to cart: " . mysqli_error($conn);
            }
        } else {
            $_SESSION['error'] = "This machine is already in your cart.";
        }
    } else {
        $_SESSION['error'] = "This machine is not available for rent.";
    }
    
    header('Location: cart.php');
    exit();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Updated query to include start_date and end_date
$cart_query = "SELECT c.cart_id, c.user_id, c.machine_id, 
               m.name, m.daily_rate, m.image_url, m.status,
               m.security_deposit, c.start_date, c.end_date
               FROM cart c 
               JOIN machines m ON c.machine_id = m.machine_id 
               WHERE c.user_id = ?";

$stmt = mysqli_prepare($conn, $cart_query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$cart_result = mysqli_stmt_get_result($stmt);

// Initialize totals
$subtotal = 0;
$total_gst = 0;
$total = 0;

// Process cart items
while($item = mysqli_fetch_assoc($cart_result)) {
    // Set default dates if not set
    $start_date = $item['start_date'] ?? date('Y-m-d');
    $end_date = $item['end_date'] ?? date('Y-m-d', strtotime('+1 day'));
    
    // Calculate rental duration
    $rental_days = max(1, (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24));
    
    // Calculate costs
    $item_subtotal = $item['daily_rate'] * $rental_days;
    $item_gst = $item_subtotal * 0.18; // 18% GST
    
    // Update totals
    $subtotal += $item_subtotal;
    $total_gst += $item_gst;
}
$total = $subtotal + $total_gst;

// Reset result pointer
mysqli_data_seek($cart_result, 0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Add date picker CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <style>
        .cart-container {
            max-width: 1200px;
            margin: 120px auto 40px;
            padding: 0 20px;
        }
        .cart-item {
            display: flex;
            align-items: center;
            padding: 20px;
            margin-bottom: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .cart-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 4px;
        }
        .cart-info {
            flex: 1;
            margin-left: 20px;
        }
        .remove-btn {
            background: #dc3545;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
        }
        .checkout-btn {
            background: #28a745;
            color: white;
            padding: 12px 24px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
        }
        .date-inputs {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        .date-input {
            padding: 4px 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 130px;
        }
        .error-message {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #fff;
            border-left: 4px solid #ef4444;
            padding: 16px 24px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            animation: slideIn 0.3s ease-out;
            max-width: 400px;
        }

        .error-message .title {
            color: #ef4444;
            font-weight: 600;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .error-message .message {
            color: #4b5563;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .error-message.hide {
            animation: slideOut 0.3s ease-in forwards;
        }

        @keyframes slideOut {
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
    </style>
</head>
<body class="bg-gray-100">
    <?php include 'nav.php'; ?>

    <div class="cart-container">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold">Your Cart</h1>
            <a href="machines.php" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                Back to Machines
            </a>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline"><?php echo $_SESSION['error']; ?></span>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline"><?php echo $_SESSION['success']; ?></span>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (mysqli_num_rows($cart_result) > 0): ?>
            <form method="post" action="update_cart.php">
                <?php 
                while($item = mysqli_fetch_assoc($cart_result)): 
                    $start_date = $item['start_date'] ?? date('Y-m-d');
                    $end_date = $item['end_date'] ?? date('Y-m-d', strtotime('+1 day'));
                    $rental_days = max(1, (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24));
                    $item_subtotal = $item['daily_rate'] * $rental_days;

                    // Fetch current machine status
                    $status_query = "SELECT status FROM machines WHERE machine_id = ?";
                    $stmt = mysqli_prepare($conn, $status_query);
                    mysqli_stmt_bind_param($stmt, "i", $item['machine_id']);
                    mysqli_stmt_execute($stmt);
                    $status_result = mysqli_stmt_get_result($stmt);
                    $machine_status = mysqli_fetch_assoc($status_result)['status'];
                ?>
                    <div class="cart-item">
                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                             class="cart-image">
                        <div class="cart-info">
                            <h3 class="text-xl font-semibold"><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p class="text-gray-600">₹<?php echo number_format($item['daily_rate'], 2); ?> / day</p>
                            <div class="date-inputs">
                                <div>
                                    <label>Start Date:</label>
                                    <input type="date" 
                                           name="start_date[<?php echo $item['cart_id']; ?>]" 
                                           value="<?php echo $start_date; ?>"
                                           min="<?php echo date('Y-m-d'); ?>"
                                           class="date-input"
                                           required>
                                </div>
                                <div>
                                    <label>End Date:</label>
                                    <input type="date" 
                                           name="end_date[<?php echo $item['cart_id']; ?>]" 
                                           value="<?php echo $end_date; ?>"
                                           min="<?php echo date('Y-m-d'); ?>"
                                           class="date-input"
                                           required>
                                </div>
                            </div>
                            <p class="mt-2">
                                Rental Duration: <?php echo $rental_days; ?> days
                                (₹<?php echo number_format($item['daily_rate'] * $rental_days, 2); ?>)
                            </p>

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
                        <a href="remove_from_cart.php?cart_id=<?php echo $item['cart_id']; ?>" 
                           class="remove-btn"
                           onclick="return confirm('Are you sure you want to remove this item?')">
                            Remove
                        </a>
                    </div>
                <?php endwhile; ?>

                <!-- Cart Summary -->
                <div class="bg-white p-6 rounded-lg shadow-md mt-6">
                    <h2 class="text-xl font-bold mb-4">Order Summary</h2>
                    <div class="flex justify-between mb-2">
                        <span>Subtotal:</span>
                        <span>₹<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="flex justify-between mb-2">
                        <span>GST (18%):</span>
                        <span>₹<?php echo number_format($total_gst, 2); ?></span>
                    </div>
                    <div class="flex justify-between text-xl font-bold">
                        <span>Total:</span>
                        <span>₹<?php echo number_format($total, 2); ?></span>
                    </div>
                </div>

                <div class="text-right">
                    <button type="submit" id="updateCartBtn" class="bg-blue-500 text-white px-4 py-2 rounded">Update Cart</button>
                    <a href="checkout.php" class="checkout-btn ml-4">Proceed to Checkout</a>
                </div>
            </form>
        <?php else: ?>
            <p class="text-center text-gray-600">Your cart is empty.</p>
            <p class="text-center mt-4">
                <a href="machines.php" class="text-blue-500 hover:underline">Browse Machines</a>
            </p>
        <?php endif; ?>
    </div>

    <div id="error-container"></div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        function showError(title, message) {
            const container = document.getElementById('error-container');
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.innerHTML = `
                <div class="title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    ${title}
                </div>
                <div class="message">${message}</div>
            `;
            container.appendChild(errorDiv);

            setTimeout(() => {
                errorDiv.classList.add('hide');
                setTimeout(() => {
                    errorDiv.remove();
                }, 300);
            }, 5000);
        }

        function isValidDate(dateString) {
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const checkDate = new Date(dateString);
            return checkDate >= today;
        }

        function validateDates() {
            const cartItems = document.querySelectorAll('.cart-item');
            let isValid = true;
            let errorTitle = '';
            let errorMessage = '';

            cartItems.forEach(item => {
                const startDate = item.querySelector('input[name^="start_date"]').value;
                const endDate = item.querySelector('input[name^="end_date"]').value;
                const itemName = item.querySelector('.text-xl').textContent;

                if (!startDate || !endDate) {
                    errorTitle = 'Missing Dates';
                    errorMessage = `Please select both start and end dates for ${itemName}`;
                    isValid = false;
                    return;
                }

                if (!isValidDate(startDate)) {
                    errorTitle = 'Invalid Start Date';
                    errorMessage = `Start date must be today or a future date for ${itemName}`;
                    isValid = false;
                    return;
                }

                if (new Date(endDate) <= new Date(startDate)) {
                    errorTitle = 'Invalid Date Range';
                    errorMessage = `End date must be after start date for ${itemName}`;
                    isValid = false;
                    return;
                }
            });

            if (!isValid) {
                showError(errorTitle, errorMessage);
            }
            return isValid;
        }

        // Add validation to the Update Cart button
        const updateCartBtn = document.getElementById('updateCartBtn');
        if (updateCartBtn) {
            updateCartBtn.closest('form').addEventListener('submit', function(e) {
                if (!validateDates()) {
                    e.preventDefault();
                }
            });
        }

        // Add validation on date input change
        const dateInputs = document.querySelectorAll('.date-input');
        dateInputs.forEach(input => {
            input.addEventListener('change', function() {
                const cartItem = this.closest('.cart-item');
                const startDateInput = cartItem.querySelector('input[name^="start_date"]');
                const endDateInput = cartItem.querySelector('input[name^="end_date"]');
                const itemName = cartItem.querySelector('.text-xl').textContent;

                if (startDateInput.value && !isValidDate(startDateInput.value)) {
                    showError('Invalid Start Date', `Start date must be today or a future date for ${itemName}`);
                    startDateInput.value = '';
                    return;
                }

                if (startDateInput.value && endDateInput.value) {
                    if (new Date(endDateInput.value) <= new Date(startDateInput.value)) {
                        showError('Invalid Date Range', 'End date must be after start date');
                        endDateInput.value = '';
                    }
                }
            });
        });

        // Set minimum dates for all date inputs
        const today = new Date().toISOString().split('T')[0];
        dateInputs.forEach(input => {
            input.setAttribute('min', today);
        });
    });
    </script>
</body>
</html> 