<?php
include 'config.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_GET['rental_id'])) {
    header('Location: rentals.php');
    exit();
}

$rental_id = (int)$_GET['rental_id'];

// Verify rental belongs to user
$check_rental = "SELECT r.*, m.machine_id 
                 FROM rentals r 
                 JOIN machines m ON r.machine_id = m.machine_id 
                 WHERE r.rental_id = ? AND r.user_id = ?";
$stmt = mysqli_prepare($conn, $check_rental);
mysqli_stmt_bind_param($stmt, "ii", $rental_id, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$rental = mysqli_fetch_assoc($result);

if ($rental) {
    // Update rental status to completed
    $update_rental = "UPDATE rentals SET status = 'completed' WHERE rental_id = ?";
    $stmt = mysqli_prepare($conn, $update_rental);
    mysqli_stmt_bind_param($stmt, "i", $rental_id);
    mysqli_stmt_execute($stmt);

    // Update machine status back to available
    $update_machine = "UPDATE machines SET status = 'available' WHERE machine_id = ?";
    $stmt = mysqli_prepare($conn, $update_machine);
    mysqli_stmt_bind_param($stmt, "i", $rental['machine_id']);
    mysqli_stmt_execute($stmt);

    $_SESSION['success'] = "Machine return processed successfully!";
} else {
    $_SESSION['error'] = "Invalid rental record.";
}

header('Location: rentals.php');
exit(); 