<?php
// Simple error logging to help debug
error_log('get_subcategories.php called');

include 'config.php';

// Get category ID from request
$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

// Set content type to JSON
header('Content-Type: application/json');

// Validate category ID
if ($category_id <= 0) {
    echo json_encode(['error' => 'Invalid category ID']);
    exit;
}

// Get subcategories
$query = "SELECT subcategory_id, subcategory_name FROM subcategories WHERE category_id = $category_id ORDER BY subcategory_name";
$result = mysqli_query($conn, $query);

if (!$result) {
    echo json_encode(['error' => 'Database query failed: ' . mysqli_error($conn)]);
    exit;
}

// Build response
$subcategories = [];
while ($row = mysqli_fetch_assoc($result)) {
    $subcategories[] = [
        'id' => $row['subcategory_id'],
        'name' => $row['subcategory_name']
    ];
}

echo json_encode([
    'status' => 'success',
    'subcategories' => $subcategories
]);
?> 