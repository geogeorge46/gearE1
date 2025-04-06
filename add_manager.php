<?php
include 'config.php';
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: admin_login.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    $errors = [];
    
    // Validate full name (first and last name)
    if (empty($full_name)) {
        $errors[] = "Full name is required";
    } elseif (str_word_count($full_name) < 2) {
        $errors[] = "Please enter both first and last name";
    } elseif (!preg_match("/^[a-zA-Z ]*$/", $full_name)) {
        $errors[] = "Name should only contain letters and spaces";
    }
    
    // Validate phone number
    if (empty($phone)) {
        $errors[] = "Phone number is required";
    } elseif (!preg_match("/^[6-9]\d{9}$/", $phone)) {
        $errors[] = "Phone number must be 10 digits and start with 6-9";
    } else {
        // Check if phone exists in database
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE phone = ?");
        $stmt->bind_param("s", $phone);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "Phone number already exists in the system";
        }
    }
    
    // Validate email
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    } else {
        // Check if email exists in database
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "Email already exists in the system";
        }
    }
    
    // Validate password
    if (empty($password)) {
        $errors[] = "Password is required";
    } else {
        // Check password strength
        if (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters long";
        }
        if (!preg_match("/[A-Z]/", $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }
        if (!preg_match("/[a-z]/", $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        }
        if (!preg_match("/[0-9]/", $password)) {
            $errors[] = "Password must contain at least one number";
        }
        if (!preg_match("/[!@#$%^&*(),.?\":{}|<>]/", $password)) {
            $errors[] = "Password must contain at least one special character";
        }
    }
    
    // Confirm password match
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    // If no errors, create manager
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role = 'manager';
        $status = 'active';
        
        $sql = "INSERT INTO users (full_name, email, phone, password, role, status) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", $full_name, $email, $phone, $hashed_password, $role, $status);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Manager added successfully!";
            header('Location: admin_managers.php');
            exit();
        } else {
            $errors[] = "Error creating manager account";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Manager - GEAR EQUIP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-md p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Add New Manager</h1>
            <a href="admin_managers.php" class="text-blue-600 hover:text-blue-800">
                Back to Managers
            </a>
        </div>

        <form method="POST" action="" class="space-y-4">
            <?php if (!empty($errors)): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">There were errors with your submission:</h3>
                            <div class="mt-2 text-sm text-red-700">
                                <ul class="list-disc list-inside">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div>
                <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                <input type="text" id="full_name" name="full_name" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="Enter first and last name" required>
                <p class="mt-1 text-sm text-gray-500">Please enter both first and last name</p>
            </div>

            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                <input type="tel" id="phone" name="phone" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="Enter 10-digit phone number" 
                       pattern="[6-9][0-9]{9}" required>
                <p class="mt-1 text-sm text-gray-500">10-digit number starting with 6-9</p>
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                <input type="email" id="email" name="email" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="example@email.com" required>
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" id="password" name="password" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                       required>
                <ul class="mt-2 text-sm text-gray-500 list-disc list-inside space-y-1">
                    <li>At least 8 characters long</li>
                    <li>Must contain at least one uppercase letter</li>
                    <li>Must contain at least one lowercase letter</li>
                    <li>Must contain at least one number</li>
                    <li>Must contain at least one special character (!@#$%^&*)</li>
                </ul>
            </div>

            <div>
                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                       required>
            </div>

            <div class="flex justify-end mt-6">
                <button type="submit" 
                        class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    Add Manager
                </button>
            </div>
        </form>
    </div>

    <script>
    // Real-time phone number validation
    document.getElementById('phone').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 10) value = value.slice(0, 10);
        if (value.length > 0 && !['6','7','8','9'].includes(value[0])) {
            value = '';
        }
        e.target.value = value;
    });

    document.getElementById('full_name').addEventListener('input', function(e) {
        const name = e.target.value.trim();
        const nameArr = name.split(' ');
        const isValid = nameArr.length >= 2 && nameArr.every(part => part.length > 0);
        e.target.setCustomValidity(isValid ? '' : 'Please enter both first and last name');
    });

    document.getElementById('password').addEventListener('input', function(e) {
        const password = e.target.value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        // Update confirm password validation
        document.getElementById('confirm_password').setCustomValidity(
            password === confirmPassword ? '' : 'Passwords do not match'
        );
    });

    document.getElementById('confirm_password').addEventListener('input', function(e) {
        const password = document.getElementById('password').value;
        const confirmPassword = e.target.value;
        
        e.target.setCustomValidity(
            password === confirmPassword ? '' : 'Passwords do not match'
        );
    });

    document.getElementById('email').addEventListener('input', function(e) {
        const email = e.target.value;
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        e.target.setCustomValidity(
            emailRegex.test(email) ? '' : 'Please enter a valid email address'
        );
    });
    </script>
</body>
</html>