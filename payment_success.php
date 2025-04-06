<?php
include 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Success - GEAR EQUIP</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <?php include 'nav.php'; ?>

    <div class="max-w-4xl mx-auto px-4 py-16 mt-20 text-center">
        <div class="bg-white rounded-lg shadow-md p-8">
            <div class="text-green-500 mb-4">
                <svg class="w-20 h-20 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold mb-4">Payment Successful!</h1>
            <p class="text-gray-600 mb-8">Your rental order has been confirmed.</p>
            <a href="dashboard.php" 
               class="bg-blue-600 text-white py-2 px-6 rounded-lg font-semibold hover:bg-blue-700 transition duration-300">
                View My Rentals
            </a>
        </div>
    </div>
</body>
</html> 