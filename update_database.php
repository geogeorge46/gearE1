<?php
include 'config.php';

// Add rental_days and total_amount columns if they don't exist
$alter_table_sql = "
ALTER TABLE rentals 
ADD COLUMN IF NOT EXISTS rental_days INT NOT NULL AFTER machine_id,
ADD COLUMN IF NOT EXISTS total_amount DECIMAL(10,2) NOT NULL AFTER rental_days
";

if ($conn->query($alter_table_sql)) {
    echo "Table updated successfully";
} else {
    echo "Error updating table: " . $conn->error;
}
?> 