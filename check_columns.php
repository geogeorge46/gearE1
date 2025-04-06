<?php
require_once 'config.php';

// Check if columns exist in rentals table
$query = "SHOW COLUMNS FROM rentals LIKE 'temp_start_date'";
$result = mysqli_query($conn, $query);
$temp_start_date_exists = mysqli_num_rows($result) > 0;

$query = "SHOW COLUMNS FROM rentals LIKE 'temp_end_date'";
$result = mysqli_query($conn, $query);
$temp_end_date_exists = mysqli_num_rows($result) > 0;

echo "temp_start_date exists: " . ($temp_start_date_exists ? "Yes" : "No") . "<br>";
echo "temp_end_date exists: " . ($temp_end_date_exists ? "Yes" : "No") . "<br>";

// Show all columns in rentals table
$query = "SHOW COLUMNS FROM rentals";
$result = mysqli_query($conn, $query);
echo "<h3>All columns in rentals table:</h3>";
echo "<ul>";
while ($row = mysqli_fetch_assoc($result)) {
    echo "<li>" . $row['Field'] . "</li>";
}
echo "</ul>";
?> 