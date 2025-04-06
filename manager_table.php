<?php
include 'config.php';

// Check if table exists
$check_table = "SHOW TABLES LIKE 'managers'";
$table_exists = $conn->query($check_table);

if ($table_exists->num_rows == 0) {
    // SQL to create managers table
    $sql = "CREATE TABLE managers (
        manager_id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        phone VARCHAR(15) NOT NULL,
        company_name VARCHAR(100),
        address TEXT,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )";

    if ($conn->query($sql) === TRUE) {
        echo "<div style='padding: 20px; background-color: #d4edda; color: #155724; margin: 20px;'>
                Managers table created successfully!
              </div>";
    } else {
        echo "<div style='padding: 20px; background-color: #f8d7da; color: #721c24; margin: 20px;'>
                Error creating table: " . $conn->error . "
              </div>";
    }
} else {
    echo "<div style='padding: 20px; background-color: #cce5ff; color: #004085; margin: 20px;'>
            Managers table already exists!
          </div>";
}

$conn->close();
?>