<?php
include 'config.php';

$database_name = "GearEquip";
$sql = "CREATE DATABASE IF NOT EXISTS $database_name";

if (mysqli_query($conn, $sql)) {
    echo "Database created successfully";
} else {
    echo "Error creating database: " . mysqli_error($conn);
}

mysqli_close($conn);
?>
