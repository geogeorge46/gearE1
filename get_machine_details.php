<?php
require_once 'includes/db_connection.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Machine ID not provided']);
    exit;
}

$machine_id = mysqli_real_escape_string($conn, $_GET['id']);

$query = "SELECT * FROM machines WHERE id = '$machine_id'";
$result = mysqli_query($conn, $query);

if ($result && $machine = mysqli_fetch_assoc($result)) {
    echo json_encode($machine);
} else {
    echo json_encode(['error' => 'Machine not found']);
}
?>