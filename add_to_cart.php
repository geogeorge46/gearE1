<?php
header('Content-Type: application/json');
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $machine_id = $_POST['machine_id'];
    $user_id = $_SESSION['user_id'];

    try {
        // Check if machine exists and is available
        $check_query = "SELECT status FROM machines WHERE machine_id = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("i", $machine_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $machine = $result->fetch_assoc();

        if (!$machine) {
            throw new Exception("Machine not found");
        }

        if ($machine['status'] != 'available') {
            throw new Exception("Machine is not available");
        }

        // Check if machine already in cart
        $cart_check = "SELECT cart_id FROM cart WHERE user_id = ? AND machine_id = ?";
        $stmt = $conn->prepare($cart_check);
        $stmt->bind_param("ii", $user_id, $machine_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception("Machine already in cart");
        }

        // Add new cart item
        $insert_query = "INSERT INTO cart (user_id, machine_id, start_date, end_date) 
                        VALUES (?, ?, CURRENT_DATE, DATE_ADD(CURRENT_DATE, INTERVAL 1 DAY))";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("ii", $user_id, $machine_id);
        $stmt->execute();

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>