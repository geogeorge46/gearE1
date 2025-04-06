<?php
include 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_dates = $_POST['start_date'];
    $end_dates = $_POST['end_date'];
    
    foreach ($start_dates as $cart_id => $start_date) {
        $end_date = $end_dates[$cart_id];
        
        // Validate dates
        if (strtotime($end_date) < strtotime($start_date)) {
            $_SESSION['error'] = "End date cannot be earlier than start date.";
            header('Location: cart.php');
            exit();
        }

        // Update the cart item
        $update_query = "UPDATE cart SET start_date = ?, end_date = ? WHERE cart_id = ? AND user_id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "ssii", $start_date, $end_date, $cart_id, $_SESSION['user_id']);
        
        if (!mysqli_stmt_execute($stmt)) {
            $_SESSION['error'] = "Failed to update cart. Please try again.";
            header('Location: cart.php');
            exit();
        }
    }
    
    $_SESSION['success'] = "Cart updated successfully!";
    header('Location: cart.php');
    exit();
}

// If not POST request, redirect back to cart
header('Location: cart.php');
exit(); 