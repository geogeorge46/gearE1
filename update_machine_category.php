<?php
include 'config.php';
session_start();

// Check if user is logged in and is a manager
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'manager') {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $machine_id = $_POST['machine_id'];
    $category_id = $_POST['category_id'];

    // First, delete existing category for this machine
    $delete_query = "DELETE FROM machine_categories WHERE machine_id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $machine_id);
    $stmt->execute();

    // Then, insert new category
    if (!empty($category_id)) {
        $insert_query = "INSERT INTO machine_categories (machine_id, category_id) VALUES (?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("ii", $machine_id, $category_id);
        
        if ($stmt->execute()) {
            echo "success";
        } else {
            echo "error";
        }
    } else {
        echo "success"; // If category was removed
    }
}
?> 