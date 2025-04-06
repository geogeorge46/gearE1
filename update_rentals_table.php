<?php
include 'config.php';

// Drop existing rentals table
$conn->query("DROP TABLE IF EXISTS reviews"); // Drop reviews table first due to foreign key
$conn->query("DROP TABLE IF EXISTS rentals");

// Recreate rentals table with payment_id
$sql_rentals = "CREATE TABLE rentals (
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
    echo "Rentals table recreated successfully<br>";
} else {
    echo "Error recreating rentals table: " . $conn->error . "<br>";
}

// Recreate reviews table
$sql_reviews = "CREATE TABLE reviews (
    review_id INT PRIMARY KEY AUTO_INCREMENT,
    rental_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    reviewed BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rental_id) REFERENCES rentals(rental_id)
)";

if ($conn->query($sql_reviews) === TRUE) {
    echo "Reviews table recreated successfully";
} else {
    echo "Error recreating reviews table: " . $conn->error;
}

$conn->close();
?> 