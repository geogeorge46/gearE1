<?php
include 'config.php';

// Create users table
$sql_users = "CREATE TABLE IF NOT EXISTS users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(15),
    role ENUM('user', 'manager', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql_users) === TRUE) {
    echo "Users table created successfully<br>";
} else {
    echo "Error creating users table: " . $conn->error . "<br>";
}

// Create admin account
$admin_email = "admin@gearequip.com";
$admin_password = password_hash("admin123", PASSWORD_DEFAULT);
$admin_name = "Admin";
$admin_role = "admin";

// Check if admin exists
$check_admin = "SELECT * FROM users WHERE email = ?";
$stmt = $conn->prepare($check_admin);
$stmt->bind_param("s", $admin_email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    // Insert admin if doesn't exist
    $sql = "INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $admin_name, $admin_email, $admin_password, $admin_role);
    
    if ($stmt->execute()) {
        echo "Admin account created successfully!<br>";
        echo "Email: admin@gearequip.com<br>";
        echo "Password: admin123<br>";
    } else {
        echo "Error creating admin account: " . $conn->error . "<br>";
    }
} else {
    echo "Admin account already exists<br>";
}

// Create manager account
$manager_email = "manager@gearequip.com";
$manager_password = password_hash("manager123", PASSWORD_DEFAULT);
$manager_name = "Manager";
$manager_role = "manager";

// Check if manager exists
$check_manager = "SELECT * FROM users WHERE email = ?";
$stmt = $conn->prepare($check_manager);
$stmt->bind_param("s", $manager_email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    // Insert manager if doesn't exist
    $sql = "INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $manager_name, $manager_email, $manager_password, $manager_role);
    
    if ($stmt->execute()) {
        echo "Manager account created successfully!<br>";
        echo "Email: manager@gearequip.com<br>";
        echo "Password: manager123<br>";
    } else {
        echo "Error creating manager account: " . $conn->error . "<br>";
    }
} else {
    echo "Manager account already exists<br>";
}

// Create categories table
$sql_categories = "CREATE TABLE IF NOT EXISTS categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql_categories) === TRUE) {
    echo "Categories table created successfully<br>";
} else {
    echo "Error creating categories table: " . $conn->error . "<br>";
}

// Create machines table
$sql_machines = "CREATE TABLE IF NOT EXISTS machines (
    machine_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    category_id INT,
    daily_rate DECIMAL(10,2) NOT NULL,
    status ENUM('available', 'rented', 'maintenance') DEFAULT 'available',
    image_url VARCHAR(255), 
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id)
)";

if ($conn->query($sql_machines) === TRUE) {
    echo "Machines table created successfully<br>";
} else {
    echo "Error creating machines table: " . $conn->error . "<br>";
}

// Create rentals table
$sql_rentals = "CREATE TABLE IF NOT EXISTS rentals (
    rental_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    machine_id INT NOT NULL,
    rental_days INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('pending', 'active', 'completed', 'cancelled') DEFAULT 'pending',
    payment_id VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (machine_id) REFERENCES machines(machine_id)
)";

if ($conn->query($sql_rentals) === TRUE) {
    echo "Rentals table created successfully<br>";
} else {
    echo "Error creating rentals table: " . $conn->error . "<br>";
}

// Create reviews table
$sql_reviews = "CREATE TABLE IF NOT EXISTS reviews (
    review_id INT PRIMARY KEY AUTO_INCREMENT,
    rental_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    reviewed BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rental_id) REFERENCES rentals(rental_id)
)";

if ($conn->query($sql_reviews) === TRUE) {
    echo "Reviews table created successfully<br>";
} else {
    echo "Error creating reviews table: " . $conn->error . "<br>";
}

// Create password reset tokens table
$sql_reset_tokens = "CREATE TABLE IF NOT EXISTS password_reset_tokens (
    token_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    expiry TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
)";

if ($conn->query($sql_reset_tokens) === TRUE) {
    echo "Password reset tokens table created successfully<br>";
} else {
    echo "Error creating password reset tokens table: " . $conn->error . "<br>";
}

$conn->close();
echo "<br>Database setup completed!";
?>