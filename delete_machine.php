<?php
include 'config.php';
session_start();

// Check if user is logged in and is a manager
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'manager') {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['machine_id'])) {
    $machine_id = $_POST['machine_id'];
    
    // First, delete related records from machine_categories
    $delete_categories = "DELETE FROM machine_categories WHERE machine_id = ?";
    $stmt = $conn->prepare($delete_categories);
    $stmt->bind_param("i", $machine_id);
    $stmt->execute();
    
    // Then, delete the machine
    $delete_machine = "DELETE FROM machines WHERE machine_id = ?";
    $stmt = $conn->prepare($delete_machine);
    $stmt->bind_param("i", $machine_id);
    
    if ($stmt->execute()) {
        echo "Machine deleted successfully";
    } else {
        echo "Error deleting machine";
    }
} else {
    echo "Invalid request";
}
?> 