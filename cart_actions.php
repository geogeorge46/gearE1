<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false];
    
    if (isset($_POST['action']) && isset($_POST['machine_id'])) {
        $machine_id = (int)$_POST['machine_id'];
        
        switch ($_POST['action']) {
            case 'add':
                if (!in_array($machine_id, $_SESSION['cart'])) {
                    $_SESSION['cart'][] = $machine_id;
                    $response['success'] = true;
                }
                break;
                
            case 'remove':
                if (($key = array_search($machine_id, $_SESSION['cart'])) !== false) {
                    unset($_SESSION['cart'][$key]);
                    $_SESSION['cart'] = array_values($_SESSION['cart']); // Reindex array
                    $response['success'] = true;
                }
                break;
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
} 