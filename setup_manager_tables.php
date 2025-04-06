<?php
include 'config.php';

// Create categories table
$sql_categories = "CREATE TABLE IF NOT EXISTS categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL,
    description TEXT
)";

// Create machines table
$sql_machines = "CREATE TABLE IF NOT EXISTS machines (
    machine_id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    daily_rate DECIMAL(10,2) NOT NULL,
    status ENUM('available', 'rented', 'maintenance') DEFAULT 'available',
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(user_id)
)";

// Create reviews table
$sql_reviews = "CREATE TABLE IF NOT EXISTS reviews (
    review_id INT PRIMARY KEY AUTO_INCREMENT,
    machine_id INT,
    user_id INT,
    rating INT NOT NULL,
    feedback TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (machine_id) REFERENCES machines(machine_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
)";

// Create payments table
$sql_payments = "CREATE TABLE IF NOT EXISTS payments (
    payment_id INT PRIMARY KEY AUTO_INCREMENT,
    rental_id INT,
    amount DECIMAL(10,2) NOT NULL,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    payment_method VARCHAR(50),
    FOREIGN KEY (rental_id) REFERENCES rentals(rental_id)
)";

// Execute each query
$queries = [$sql_categories, $sql_machines, $sql_reviews, $sql_payments];

foreach ($queries as $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "Table created successfully<br>";
    } else {
        echo "Error creating table: " . $conn->error . "<br>";
    }
}

// Insert default categories
$default_categories = [
    ['Agricultural', 'Farm and agriculture related machinery'],
    ['Commercial', 'Business and commercial use machinery'],
    ['Manufacturing', 'Industrial and manufacturing equipment'],
    ['Household', 'Home and domestic use equipment']
];

$insert_category = "INSERT INTO categories (category_name, description) VALUES (?, ?)";
$stmt = $conn->prepare($insert_category);

foreach ($default_categories as $category) {
    $stmt->bind_param("ss", $category[0], $category[1]);
    $stmt->execute();
}

$conn->close();
?> 