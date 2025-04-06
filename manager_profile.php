<?php
include 'config.php';
session_start();

// Check if user is logged in as manager
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'manager') {
    header('Location: login.php');
    exit();
}

// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Fetch manager data
$user_id = $_SESSION['user_id'];
$sql = "SELECT *, DATE_FORMAT(created_at, '%M %d, %Y') as member_since FROM users WHERE user_id = ? AND role = 'manager'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Fetch recent activity
$activitySql = "SELECT activity_type, description, created_at 
                FROM user_activity 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT 10";
$activityStmt = $conn->prepare($activitySql);
$activityStmt->bind_param("i", $user_id);
$activityStmt->execute();
$activities = $activityStmt->get_result();
$activityStmt->close();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $changes = false;
    $errors = [];
    
    // Personal Details validation
    if (isset($_POST['email'])) {
        $newEmail = trim($_POST['email']);
        
        if (filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            // Check if email already exists for another user
            $checkSql = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("si", $newEmail, $user_id);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            $checkStmt->close();

            if ($checkResult->num_rows === 0 && $newEmail !== $user['email']) {
                $sql = "UPDATE users SET email = ? WHERE user_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $newEmail, $user_id);
                $stmt->execute();
                $stmt->close();
                
                // Log activity
                $activitySql = "INSERT INTO user_activity (user_id, activity_type, description) VALUES (?, ?, ?)";
                $activityStmt = $conn->prepare($activitySql);
                $type = "profile_update";
                $description = "Email address was updated";
                $activityStmt->bind_param("iss", $user_id, $type, $description);
                $activityStmt->execute();
                $activityStmt->close();
                
                echo "<script>window.onload = function() { showSuccessMessage('Email updated successfully!'); }</script>";
            }
        }
    }

    // Phone validation
    if (isset($_POST['phone'])) {
        $newPhone = trim($_POST['phone']);
        
        if (preg_match('/^[6-9]\d{9}$/', $newPhone) && $newPhone !== $user['phone']) {
            $sql = "UPDATE users SET phone = ? WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $newPhone, $user_id);
            $stmt->execute();
            $stmt->close();
            
            // Log activity
            $activitySql = "INSERT INTO user_activity (user_id, activity_type, description) VALUES (?, ?, ?)";
            $activityStmt = $conn->prepare($activitySql);
            $type = "profile_update";
            $description = "Phone number was updated";
            $activityStmt->bind_param("iss", $user_id, $type, $description);
            $activityStmt->execute();
            $activityStmt->close();
            
            echo "<script>window.onload = function() { showSuccessMessage('Phone number updated successfully!'); }</script>";
        }
    }

    // Password change
    if (isset($_POST['current_password']) && isset($_POST['new_password']) && isset($_POST['confirm_password'])) {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];

        // Verify current password
        $sql = "SELECT password FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $userData = $result->fetch_assoc();
        $stmt->close();

        if (password_verify($currentPassword, $userData['password'])) {
            if ($newPassword === $confirmPassword) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $updateSql = "UPDATE users SET password = ? WHERE user_id = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param("si", $hashedPassword, $user_id);
                $updateStmt->execute();
                $updateStmt->close();

                // Log activity
                $activitySql = "INSERT INTO user_activity (user_id, activity_type, description) VALUES (?, ?, ?)";
                $activityStmt = $conn->prepare($activitySql);
                $type = "password_change";
                $description = "Password was updated";
                $activityStmt->bind_param("iss", $user_id, $type, $description);
                $activityStmt->execute();
                $activityStmt->close();

                echo "<script>window.onload = function() { showSuccessMessage('Password updated successfully!'); }</script>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Profile - GEAR EQUIP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 font-[Poppins]">
    <!-- Side Navigation -->
    <div class="fixed left-0 top-0 w-64 h-full bg-white shadow-lg z-50">
        <!-- Logo Section -->
        <div class="p-4 border-b">
            <div class="flex items-center space-x-3">
                <img src="images/logo.png" alt="GEAR EQUIP" class="h-8">
                <span class="text-xl font-bold text-gray-800">GEAR EQUIP</span>
            </div>
        </div>
        
        <!-- Manager Info -->
        <div class="p-4 border-b">
            <p class="text-sm text-gray-500">Welcome,</p>
            <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
        </div>

        <!-- Navigation Links -->
        <nav class="p-4">
            <div class="space-y-2">
                <a href="manager_dashboard.php" class="block px-4 py-2 rounded-lg hover:bg-gray-50 text-gray-700">
                    <i class="fas fa-home mr-3"></i>Overview
                </a>
                <a href="manager_profile.php" class="block px-4 py-2 rounded-lg bg-blue-50 text-blue-700">
                    <i class="fas fa-user-cog mr-3"></i>Profile Settings
                </a>
                <!-- Add other navigation links as needed -->
            </div>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="ml-64 p-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-bold text-gray-800">Profile Settings</h1>
        </div>

        <!-- Profile Settings Form -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <form method="POST" action="" class="space-y-6">
                <!-- Personal Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                        <input type="text" 
                               value="<?php echo htmlspecialchars($user['full_name']); ?>" 
                               disabled 
                               class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                        <input type="email" 
                               name="email" 
                               value="<?php echo htmlspecialchars($user['email']); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                        <input type="tel" 
                               name="phone" 
                               value="<?php echo htmlspecialchars($user['phone']); ?>"
                               pattern="[6-9][0-9]{9}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                        <input type="text" 
                               value="Manager" 
                               disabled 
                               class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg">
                    </div>
                </div>

                <!-- Password Change Section -->
                <div class="border-t pt-6 mt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Change Password</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                            <input type="password" 
                                   name="current_password" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                            <input type="password" 
                                   name="new_password" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                            <input type="password" 
                                   name="confirm_password" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end pt-6">
                    <button type="submit" 
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>

        <!-- Recent Activity -->
        <div class="mt-8 bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Recent Activity</h2>
            <div class="space-y-4">
                <?php while ($activity = $activities->fetch_assoc()): ?>
                    <div class="flex items-center space-x-4 py-3 border-b border-gray-200">
                        <div class="flex-shrink-0">
                            <i class="fas fa-history text-blue-500"></i>
                        </div>
                        <div>
                            <p class="text-gray-800"><?php echo htmlspecialchars($activity['description']); ?></p>
                            <p class="text-sm text-gray-500">
                                <?php echo date('M d, Y h:i A', strtotime($activity['created_at'])); ?>
                            </p>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <script>
    function showSuccessMessage(message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = 'fixed top-4 right-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-lg';
        alertDiv.innerHTML = `
            <div class="flex items-center">
                <div class="py-1"><svg class="fill-current h-6 w-6 text-green-500 mr-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM9 11V9h2v6H9v-4zm0-6h2v2H9V5z"/></svg></div>
                <div>${message}</div>
            </div>
        `;
        document.body.appendChild(alertDiv);
        setTimeout(() => alertDiv.remove(), 3000);
    }
    </script>
</body>
</html> 