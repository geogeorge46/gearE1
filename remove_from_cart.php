<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'config.php';

// Debug incoming request
echo "<!-- DEBUG REMOVE_FROM_CART: ";
echo "GET: " . print_r($_GET, true);
echo "POST: " . print_r($_POST, true);
echo " -->";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'User not logged in';
    header('Location: cart.php');
    exit;
}

// Check if cart_id is provided in GET parameters
if (isset($_GET['cart_id']) && !empty($_GET['cart_id'])) {
    $cart_id = (int)$_GET['cart_id'];
    
    // Remove item from cart
    $delete_query = "DELETE FROM cart WHERE cart_id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $delete_query);
    mysqli_stmt_bind_param($stmt, "ii", $cart_id, $_SESSION['user_id']);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = 'Item removed from cart successfully';
    } else {
        $_SESSION['error'] = 'Error removing item from cart: ' . mysqli_error($conn);
    }
} else {
    // This is where the error message is set
    echo "<!-- ERROR: No cart_id specified -->";
    $_SESSION['error'] = 'No cart item specified';
}

// Redirect back to cart
header('Location: cart.php');
exit;
?> 