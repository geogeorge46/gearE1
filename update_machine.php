<?php
include 'config.php';
session_start();











// Important file it is for manager update machine










// Check if user is logged in as manager
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'manager') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $machine_id = $_POST['machine_id'];
    $status = $_POST['status'];
    $available_count = $_POST['available_count'];
    $description = $_POST['description'];
    $manager_id = $_SESSION['user_id']; // Get the manager's user_id

    // Start transaction
    $conn->begin_transaction();

    try {
        // Update machine status
        $update_query = "UPDATE machines 
                        SET status = ?, 
                            available_count = ?, 
                            description = ? 
                        WHERE machine_id = ?";
        
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sisi", $status, $available_count, $description, $machine_id);

        if ($stmt->execute()) {
            // If status is changed to 'rented', create a rental record
            if ($status === 'rented') {
                $rental_days = 1; // Default rental period
                $start_date = date('Y-m-d');
                $end_date = date('Y-m-d', strtotime('+1 day')); // Default end date

                // Get machine daily rate
                $rate_query = "SELECT daily_rate FROM machines WHERE machine_id = ?";
                $stmt = $conn->prepare($rate_query);
                $stmt->bind_param("i", $machine_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $machine = $result->fetch_assoc();
                $total_amount = $machine['daily_rate'] * $rental_days;

                // Insert into rentals table with manager's user_id
                $rental_query = "INSERT INTO rentals (user_id, machine_id, rental_days, 
                                                    total_amount, start_date, 
                                                    end_date, status, created_at) 
                               VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())";
                
                $stmt = $conn->prepare($rental_query);
                $stmt->bind_param("iiidss", 
                    $manager_id,    // Add manager's user_id
                    $machine_id, 
                    $rental_days,
                    $total_amount,
                    $start_date,
                    $end_date
                );
                $stmt->execute();
            }

            $conn->commit();
            echo json_encode(['success' => true]);
        } else {
            throw new Exception("Error updating machine");
        }
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?> 