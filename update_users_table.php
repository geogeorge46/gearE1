<?php
include 'config.php';

// Add address column to users table if it doesn't exist
$sql = "ALTER TABLE users 
        ADD COLUMN IF NOT EXISTS address TEXT";

if ($conn->query($sql) === TRUE) {
    echo "Address column added successfully to users table<br>";
} else {
    echo "Error adding address column: " . $conn->error . "<br>";
}

// Add role column to users table if it doesn't exist
$sql = "ALTER TABLE users 
        ADD COLUMN IF NOT EXISTS role ENUM('user', 'manager', 'admin') DEFAULT 'user'";

if ($conn->query($sql) === TRUE) {
    echo "Role column added successfully to users table<br>";
    
    // Now create the admin account
    $admin_email = "admin@gearequip.com";
    $admin_password = password_hash("admin123", PASSWORD_DEFAULT);
    $admin_name = "Admin";
    $role = "admin";

    // Check if admin exists first
    $check_sql = "SELECT user_id FROM users WHERE email = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $admin_email);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows == 0) {
        $sql = "INSERT INTO users (full_name, email, password, role) 
                VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $admin_name, $admin_email, $admin_password, $role);

        if ($stmt->execute()) {
            echo "Admin account created successfully!<br>";
            echo "Email: admin@gearequip.com<br>";
            echo "Password: admin123";
        } else {
            echo "Error creating admin account: " . $conn->error;
        }
    } else {
        echo "Admin account already exists";
    }
} else {
    echo "Error adding role column: " . $conn->error;
}

$conn->close();
?>