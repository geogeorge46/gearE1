<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $asset_tag = $_POST['asset_tag_number'];
    
    $query = "SELECT COUNT(*) FROM machines WHERE asset_tag_number = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $asset_tag);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    
    header('Content-Type: application/json');
    echo json_encode(['exists' => ($count > 0)]);
    exit();
}
?> 