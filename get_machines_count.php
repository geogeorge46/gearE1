<?php
include 'config.php';
session_start();

// Check if user is logged in and is a manager
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'manager') {
    header('Location: login.php');
    exit();
}

// Get count of machines with available_count > 0
$machines_query = "SELECT COUNT(*) as count FROM machines WHERE available_count > 0";
$result = $conn->query($machines_query);
echo $result ? $result->fetch_assoc()['count'] : '0';
?> 