<?php
include 'config.php';

$admin_email = "admin@gearequip.com";
$admin_password = password_hash("your_admin_password", PASSWORD_DEFAULT);
$admin_name = "Admin";
$role = "admin";

$sql = "INSERT INTO users (full_name, email, password, role) 
        VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $admin_name, $admin_email, $admin_password, $role);

if ($stmt->execute()) {
    echo "Admin account created successfully!";
} else {
    echo "Error creating admin account: " . $conn->error;
}
?> 