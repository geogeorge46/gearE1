//we are testing the subcategories database access
















<?php
include 'config.php';

// Set content type to plain text for easier debugging
header('Content-Type: text/plain');

echo "Testing subcategories database access\n\n";

// Test database connection
if (!$conn) {
    echo "Database connection failed: " . mysqli_connect_error();
    exit;
}
echo "Database connection successful\n\n";

// Test a simple query to get all subcategories
$query = "SELECT * FROM subcategories LIMIT 10";
$result = mysqli_query($conn, $query);

if (!$result) {
    echo "Query failed: " . mysqli_error($conn);
    exit;
}

echo "Found " . mysqli_num_rows($result) . " subcategories:\n";
while ($row = mysqli_fetch_assoc($result)) {
    echo "ID: " . $row['subcategory_id'] . ", Name: " . $row['subcategory_name'] . ", Category ID: " . $row['category_id'] . "\n";
}

echo "\n\nTesting specific category (ID: 1):\n";
$category_id = 1;
$query = "SELECT * FROM subcategories WHERE category_id = $category_id";
$result = mysqli_query($conn, $query);

if (!$result) {
    echo "Query failed: " . mysqli_error($conn);
    exit;
}

echo "Found " . mysqli_num_rows($result) . " subcategories for category ID $category_id:\n";
while ($row = mysqli_fetch_assoc($result)) {
    echo "ID: " . $row['subcategory_id'] . ", Name: " . $row['subcategory_name'] . "\n";
}

echo "\n\nTest completed successfully.";
?> 