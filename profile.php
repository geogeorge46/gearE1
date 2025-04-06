<?php
// Include database connection
include 'config.php';

// Start the session
session_start();

// Add this at the top of your file, after the session_start()
date_default_timezone_set('Asia/Kolkata'); // Set to Indian timezone

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Fetch user data
$user_id = $_SESSION['user_id'];
$sql = "SELECT *, DATE_FORMAT(created_at, '%M %d, %Y') as member_since FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// First, add this query near the top of the file with other database queries
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

// Add this at the top after session_start()
$message = '';
$messageType = '';

// Form validation and processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $changes = false;
    $errors = [];
    
    // Personal Details validation
    if (isset($_POST['address'])) {
        $newAddress = trim($_POST['address']);
        if ($newAddress !== $user['address']) {
            $changes = true;
            $sql = "UPDATE users SET address = ? WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $newAddress, $user_id);
            $stmt->execute();
            $stmt->close();
            
            // Log the address update activity
            $activitySql = "INSERT INTO user_activity (user_id, activity_type, description) VALUES (?, ?, ?)";
            $activityStmt = $conn->prepare($activitySql);
            $type = "profile_update";
            $description = "Address was updated";
            $activityStmt->bind_param("iss", $user_id, $type, $description);
            $activityStmt->execute();
            $activityStmt->close();
            
            echo "<script>
                window.onload = function() {
                    showSuccessMessage('Address updated successfully!');
                }
            </script>";
        }
    }

    if (isset($_POST['current_password']) && isset($_POST['new_password']) && isset($_POST['confirm_password'])) {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];

        // First verify current password
        $sql = "SELECT password FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $userData = $result->fetch_assoc();
        $stmt->close();

        if (password_verify($currentPassword, $userData['password'])) {
            // Current password is correct
            if ($newPassword === $confirmPassword) {
                // New passwords match, update the password
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $updateSql = "UPDATE users SET password = ? WHERE user_id = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param("si", $hashedPassword, $user_id);
                $updateStmt->execute();
                $updateStmt->close();

                // Log the password change activity
                $activitySql = "INSERT INTO user_activity (user_id, activity_type, description) VALUES (?, ?, ?)";
                $activityStmt = $conn->prepare($activitySql);
                $type = "password_change";
                $description = "Password was updated";
                $activityStmt->bind_param("iss", $user_id, $type, $description);
                $activityStmt->execute();
                $activityStmt->close();

                echo "<script>
                    window.onload = function() {
                        showSuccessMessage('Password updated successfully!');
                    }
                </script>";
            } else {
                echo "<script>
                    window.onload = function() {
                        showSuccessMessage('New passwords do not match!');
                    }
                </script>";
            }
        } else {
            echo "<script>
                window.onload = function() {
                    showSuccessMessage('Current password is incorrect!');
                }
            </script>";
        }
    }

    // Server-side validation
    if (isset($_POST['phone'])) {
        $newPhone = trim($_POST['phone']);
        
        // Validate phone number: starts with 6-9 and has exactly 10 digits
        if (preg_match('/^[6-9]\d{9}$/', $newPhone)) {
            if ($newPhone !== $user['phone']) {
                $sql = "UPDATE users SET phone = ? WHERE user_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $newPhone, $user_id);
                $stmt->execute();
                $stmt->close();
                
                // Log the phone update activity
                $activitySql = "INSERT INTO user_activity (user_id, activity_type, description) VALUES (?, ?, ?)";
                $activityStmt = $conn->prepare($activitySql);
                $type = "profile_update";
                $description = "Phone number was updated";
                $activityStmt->bind_param("iss", $user_id, $type, $description);
                $activityStmt->execute();
                $activityStmt->close();
                
                echo "<script>
                    window.onload = function() {
                        showSuccessMessage('Phone number updated successfully!');
                    }
                </script>";
            }
        } else {
            echo "<script>
                window.onload = function() {
                    showSuccessMessage('Invalid phone number! Must start with 6-9 and have 10 digits.');
                }
            </script>";
        }
    }

    if (isset($_POST['email'])) {
        $newEmail = trim($_POST['email']);
        
        // Validate email format
        if (filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            // Check if email already exists for another user
            $checkSql = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("si", $newEmail, $user_id);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            $checkStmt->close();

            if ($checkResult->num_rows === 0) {
                // Email is unique, proceed with update
                if ($newEmail !== $user['email']) {
                    $sql = "UPDATE users SET email = ? WHERE user_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("si", $newEmail, $user_id);
                    $stmt->execute();
                    $stmt->close();
                    
                    // Log the email update activity
                    $activitySql = "INSERT INTO user_activity (user_id, activity_type, description) VALUES (?, ?, ?)";
                    $activityStmt = $conn->prepare($activitySql);
                    $type = "profile_update";
                    $description = "Email address was updated";
                    $activityStmt->bind_param("iss", $user_id, $type, $description);
                    $activityStmt->execute();
                    $activityStmt->close();
                    
                    echo "<script>
                        window.onload = function() {
                            showSuccessMessage('Email updated successfully!');
                        }
                    </script>";
                }
            } else {
                echo "<script>
                    window.onload = function() {
                        showSuccessMessage('This email is already registered!');
                    }
                </script>";
            }
        } else {
            echo "<script>
                window.onload = function() {
                    showSuccessMessage('Please enter a valid email address!');
                }
            </script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - GEAR EQUIP</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="./styles.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50">
    <?php include 'nav.php'; ?>

    <!-- Add this right after the navbar -->
    <div class="max-w-7xl mx-auto px-4 mt-20">
        <?php if (!empty($message)): ?>
            <div id="alert-message" 
                 class="<?php echo $messageType === 'error' ? 'bg-red-100 border-red-400 text-red-700' : 
                             ($messageType === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 
                             'bg-blue-100 border-blue-400 text-blue-700'); ?> 
                        border px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $message; ?></span>
                <button onclick="this.parentElement.style.display='none'" 
                        class="absolute top-0 bottom-0 right-0 px-4 py-3">
                    <svg class="fill-current h-6 w-6" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                        <title>Close</title>
                        <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
                    </svg>
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Main container with consistent spacing -->
    <div class="max-w-7xl mx-auto px-4 mt-36">
        <?php if (!empty($message)): ?>
            <div id="alert-message" 
                 class="<?php echo $messageType === 'error' ? 'bg-red-100 border-red-400 text-red-700' : 
                             ($messageType === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 
                             'bg-blue-100 border-blue-400 text-blue-700'); ?> 
                        border px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $message; ?></span>
                <button onclick="this.parentElement.style.display='none'" 
                        class="absolute top-0 bottom-0 right-0 px-4 py-3">
                    <svg class="fill-current h-6 w-6" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                        <title>Close</title>
                        <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
                    </svg>
                </button>
            </div>
        <?php endif; ?>

        <div class="flex mt-10">
            <!-- Sidebar -->
            <div class="w-64 flex-shrink-0 mr-8">
                <div class="bg-white rounded-lg shadow-lg p-6 sticky top-32">
                    <nav class="space-y-2">
                        <button onclick="showSection('overview')" 
                            class="w-full flex items-center px-4 py-2 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-700 section-btn"
                            data-section="overview">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                    d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                    d="M14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                    d="M4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2z"/>
                            </svg>
                            Overview
                        </button>
                        <button onclick="showSection('personal-details')" 
                            class="w-full flex items-center px-4 py-2 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-700 section-btn"
                            data-section="personal-details">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            Personal Details
                        </button>
                        <button onclick="showSection('settings')" 
                            class="w-full flex items-center px-4 py-2 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-700 section-btn"
                            data-section="settings">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            Settings
                        </button>
                        <button onclick="showSection('recent-activity')" 
                            class="w-full flex items-center px-4 py-2 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-700 section-btn"
                            data-section="recent-activity">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Recent Activity
                        </button>
                    </nav>
                </div>
            </div>

            <!-- Main Content with fixed height and overflow -->
            <div class="flex-1">
                <!-- Overview Section -->
                <section id="overview" class="section-content min-h-[600px]">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Overview</h2>
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <div class="grid grid-cols-1 gap-6">
                            <div>
                                <h3 class="font-medium text-gray-700">Account Summary</h3>
                                <p class="text-gray-600">Member since: <?php echo htmlspecialchars($user['member_since']); ?></p>
                                <p class="text-gray-600">Role: <?php echo ucfirst(htmlspecialchars($user['role'] ?? 'user')); ?></p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Personal Details Section -->
                <section id="personal-details" class="section-content min-h-[600px] hidden">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Personal Details</h2>
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <form id="profileForm" method="POST" action="profile.php" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                                    <input type="text" 
                                           name="full_name" 
                                           value="<?php echo htmlspecialchars($user['full_name']); ?>"
                                           readonly
                                           disabled
                                           class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg cursor-not-allowed">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                    <input type="email" 
                                           name="email" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>"
                                           pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
                                           required
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                                    <input type="tel" 
                                           name="phone" 
                                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                           pattern="[6-9][0-9]{9}"
                                           maxlength="10"
                                           title="Phone number must start with 6-9 and have 10 digits"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                           oninput="validatePhone(this)">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Member Since</label>
                                    <input type="text" value="<?php echo htmlspecialchars($user['member_since']); ?>" disabled
                                        class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                                    <input type="text" value="<?php echo ucfirst(htmlspecialchars($user['role'] ?? 'user')); ?>" disabled
                                        class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg">
                                </div>
                            </div>

                            <div class="mt-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                                <textarea name="address" rows="3" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                ><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                            </div>

                            <div class="flex justify-end">
                                <button type="submit"
                                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                    Update Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </section>

                <!-- Settings Section -->
                <section id="settings" class="section-content min-h-[600px] hidden">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Settings</h2>
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <form method="POST" action="profile.php" id="settingsForm" class="space-y-6">
                            <!-- Password change section -->
                            <div class="border-b border-gray-200 pb-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Change Password</h3>
                                <div class="space-y-4">
                                    <!-- Current Password -->
                                    <div class="relative">
                                        <label for="current_password" class="block text-sm font-medium text-gray-700">Current Password</label>
                                        <div class="relative mt-1">
                                            <input type="password" 
                                                   name="current_password" 
                                                   id="current_password"
                                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 pr-10">
                                            <button type="button" 
                                                    onclick="togglePasswordVisibility('current_password')"
                                                    class="absolute inset-y-0 right-0 flex items-center pr-3">
                                                <svg class="h-5 w-5 text-gray-400 cursor-pointer password-toggle" 
                                                     fill="none" 
                                                     viewBox="0 0 24 24" 
                                                     stroke="currentColor">
                                                    <path stroke-linecap="round" 
                                                          stroke-linejoin="round" 
                                                          stroke-width="2" 
                                                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" 
                                                          stroke-linejoin="round" 
                                                          stroke-width="2" 
                                                          d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- New Password -->
                                    <div class="relative">
                                        <label for="new_password" class="block text-sm font-medium text-gray-700">New Password</label>
                                        <div class="relative mt-1">
                                            <input type="password" 
                                                   name="new_password" 
                                                   id="new_password"
                                                   oninput="validatePassword(this)"
                                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 pr-10">
                                            <button type="button" 
                                                    onclick="togglePasswordVisibility('new_password')"
                                                    class="absolute inset-y-0 right-0 flex items-center pr-3">
                                                <svg class="h-5 w-5 text-gray-400 cursor-pointer password-toggle" 
                                                     fill="none" 
                                                     viewBox="0 0 24 24" 
                                                     stroke="currentColor">
                                                    <path stroke-linecap="round" 
                                                          stroke-linejoin="round" 
                                                          stroke-width="2" 
                                                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" 
                                                          stroke-linejoin="round" 
                                                          stroke-width="2" 
                                                          d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </button>
                                        </div>
                                        <div id="password-validation" class="mt-2 text-sm space-y-1"></div>
                                    </div>

                                    <!-- Confirm Password -->
                                    <div class="relative">
                                        <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                                        <div class="relative mt-1">
                                            <input type="password" 
                                                   name="confirm_password" 
                                                   id="confirm_password"
                                                   oninput="validateConfirmPassword()"
                                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 pr-10">
                                            <button type="button" 
                                                    onclick="togglePasswordVisibility('confirm_password')"
                                                    class="absolute inset-y-0 right-0 flex items-center pr-3">
                                                <svg class="h-5 w-5 text-gray-400 cursor-pointer password-toggle" 
                                                     fill="none" 
                                                     viewBox="0 0 24 24" 
                                                     stroke="currentColor">
                                                    <path stroke-linecap="round" 
                                                          stroke-linejoin="round" 
                                                          stroke-width="2" 
                                                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" 
                                                          stroke-linejoin="round" 
                                                          stroke-width="2" 
                                                          d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </button>
                                        </div>
                                        <div id="confirm-password-validation" class="mt-2 text-sm text-red-600"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="flex justify-end pt-4">
                                <button type="submit"
                                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </section>

                <!-- Recent Activity Section -->
                <section id="recent-activity" class="section-content min-h-[600px] hidden">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Recent Activity</h2>
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <div class="space-y-4">
                            <div class="border-b border-gray-200 pb-4">
                                <h3 class="font-medium text-gray-900">Activity Log</h3>
                                <div class="mt-4 space-y-3">
                                    <?php if ($activities->num_rows > 0): ?>
                                        <?php while($activity = $activities->fetch_assoc()): ?>
                                            <div class="flex items-start">
                                                <div class="flex-shrink-0">
                                                    <?php 
                                                    // Choose icon based on activity type
                                                    $icon = match($activity['activity_type']) {
                                                        'profile_update' => '<svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                                           </svg>',
                                                        'password_change' => '<svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                                                           </svg>',
                                                        'login' => '<svg class="h-5 w-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                                                                           </svg>',
                                                        default => '<svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                                            <circle cx="10" cy="10" r="3"/>
                                                                           </svg>'
                                                    };
                                                    echo $icon;
                                                    ?>
                                                </div>
                                                <div class="ml-3">
                                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($activity['description']); ?></p>
                                                    <p class="text-xs text-gray-400">
                                                        <?php 
                                                        $activityDate = new DateTime($activity['created_at'], new DateTimeZone('Asia/Kolkata'));
                                                        $now = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
                                                        $interval = $now->diff($activityDate);
                                                        
                                                        if ($interval->d == 0) {
                                                            if ($interval->h == 0) {
                                                                if ($interval->i == 0) {
                                                                    echo 'Just now';
                                                                } else {
                                                                    echo $interval->i . ' minute' . ($interval->i > 1 ? 's' : '') . ' ago';
                                                                }
                                                            } else {
                                                                echo $interval->h . ' hour' . ($interval->h > 1 ? 's' : '') . ' ago';
                                                            }
                                                        } else if ($interval->d == 1) {
                                                            echo 'Yesterday';
                                                        } else {
                                                            echo $activityDate->format('M d, Y h:i A');
                                                        }
                                                        ?>
                                                    </p>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <p class="text-gray-500 text-center py-4">No recent activity</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <!-- Add this JavaScript for real-time password validation -->
    <script>
    function validatePassword(input) {
        const password = input.value;
        const validation = {
            length: password.length >= 8,
            uppercase: /[A-Z]/.test(password),
            lowercase: /[a-z]/.test(password),
            number: /\d/.test(password),
            special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
        };

        const validationDiv = document.getElementById('password-validation');
        validationDiv.innerHTML = `
            <p class="${validation.length ? 'text-green-600' : 'text-red-600'}">
                ${validation.length ? '✓' : '×'} At least 8 characters
            </p>
            <p class="${validation.uppercase ? 'text-green-600' : 'text-red-600'}">
                ${validation.uppercase ? '✓' : '×'} One uppercase letter
            </p>
            <p class="${validation.lowercase ? 'text-green-600' : 'text-red-600'}">
                ${validation.lowercase ? '✓' : '×'} One lowercase letter
            </p>
            <p class="${validation.number ? 'text-green-600' : 'text-red-600'}">
                ${validation.number ? '✓' : '×'} One number
            </p>
            <p class="${validation.special ? 'text-green-600' : 'text-red-600'}">
                ${validation.special ? '✓' : '×'} One special character
            </p>
        `;

        validateConfirmPassword();
    }

    function validateConfirmPassword() {
        const password = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const validationDiv = document.getElementById('confirm-password-validation');

        if (confirmPassword) {
            if (password === confirmPassword) {
                validationDiv.textContent = '✓ Passwords match';
                validationDiv.className = 'mt-2 text-sm text-green-600';
            } else {
                validationDiv.textContent = '× Passwords do not match';
                validationDiv.className = 'mt-2 text-sm text-red-600';
            }
        } else {
            validationDiv.textContent = '';
        }
    }

    function togglePasswordVisibility(inputId) {
        const input = document.getElementById(inputId);
        const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
        input.setAttribute('type', type);
        
        // Update the eye icon
        const button = input.nextElementSibling;
        const svg = button.querySelector('svg');
        if (type === 'text') {
            svg.innerHTML = `
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            `;
        } else {
            svg.innerHTML = `
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
            `;
        }
    }
    </script>

    <!-- Add this CSS for section transitions -->
    <style>
    .section-content {
        transition: opacity 0.3s ease-in-out;
        opacity: 1;
    }

    .section-content.hidden {
        display: none;
        opacity: 0;
    }

    .min-h-[600px] {
        min-height: 600px;
    }

    .section-btn {
        transition: all 0.2s ease-in-out;
    }
    </style>

    <script>
    function showSuccessMessage(message) {
        // Create and show custom alert message
        const alertDiv = document.createElement('div');
        alertDiv.id = 'alert-message';
        alertDiv.className = 'fixed top-32 left-0 right-0 z-50 mx-auto max-w-7xl px-4';
        alertDiv.innerHTML = `
            <div class="bg-green-100 border-green-400 text-green-700 border px-4 py-3 rounded relative flex items-center justify-between shadow-lg">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" 
                              d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" 
                              clip-rule="evenodd">
                        </path>
                    </svg>
                    <span class="block sm:inline font-medium">${message}</span>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-auto pl-3">
                    <svg class="fill-current h-4 w-4" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                        <title>Close</title>
                        <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
                    </svg>
                </button>
            </div>
        `;
        
        // Remove existing alert if present
        const existingAlert = document.getElementById('alert-message');
        if (existingAlert) {
            existingAlert.remove();
        }
        
        // Add to body
        document.body.appendChild(alertDiv);
        
        // Auto-hide after 3 seconds
        setTimeout(() => {
            alertDiv.style.opacity = '0';
            setTimeout(() => {
                alertDiv.remove();
            }, 300);
        }, 3000);
    }

    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('profileForm');
        let isSubmitting = false;
        
        // ... rest of your form handling code ...
    });
    </script>

    <style>
    #alert-message {
        transition: opacity 0.3s ease-in-out;
    }

    @keyframes slideDown {
        from {
            transform: translateY(-100%);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    #alert-message {
        animation: slideDown 0.3s ease-out forwards;
    }

    /* Added shadow effect */
    #alert-message > div {
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }
    </style>

    <script>
    // Add this function at the top of your script section
    function initializeFormValidation(formId) {
        const form = document.getElementById(formId);
        let isSubmitting = false;
        
        if (!form) return;

        // Store initial form values
        const initialValues = {};
        const formElements = form.elements;
        for (let element of formElements) {
            if (element.name) {
                initialValues[element.name] = element.value;
            }
        }
        
        form.addEventListener('submit', function(e) {
            if (isSubmitting) {
                e.preventDefault();
                return;
            }
            
            let hasChanges = false;
            
            // Compare current values with initial values
            for (let element of formElements) {
                if (element.name && element.value !== initialValues[element.name]) {
                    hasChanges = true;
                    break;
                }
            }
            
            if (!hasChanges) {
                e.preventDefault();
                
                // Remove any existing alerts first
                const existingAlert = document.getElementById('alert-message');
                if (existingAlert) {
                    existingAlert.remove();
                }
                
                // Create and show custom alert message
                const alertDiv = document.createElement('div');
                alertDiv.id = 'alert-message';
                alertDiv.className = 'fixed top-32 left-0 right-0 z-50 mx-auto max-w-7xl px-4';
                alertDiv.innerHTML = `
                    <div class="bg-green-100 border-green-400 text-green-700 border px-4 py-3 rounded relative flex items-center justify-between shadow-lg">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" 
                                      d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" 
                                      clip-rule="evenodd">
                                </path>
                            </svg>
                            <span class="block sm:inline font-medium">No changes were made</span>
                        </div>
                        <button onclick="this.parentElement.parentElement.remove()" class="ml-auto pl-3">
                            <svg class="fill-current h-4 w-4" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <title>Close</title>
                                <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
                            </svg>
                        </button>
                    </div>
                `;
                
                document.body.appendChild(alertDiv);
                
                // Auto-hide after 3 seconds
                setTimeout(() => {
                    alertDiv.style.opacity = '0';
                    setTimeout(() => {
                        alertDiv.remove();
                    }, 300);
                }, 3000);
            } else {
                isSubmitting = true;
            }
        });
    }

    // Initialize validation for both forms when the page loads
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize for profile form
        initializeFormValidation('profileForm');
        
        // Initialize for settings form
        initializeFormValidation('settingsForm');
    });

    function showSection(sectionId) {
        // Hide all sections with fade
        document.querySelectorAll('.section-content').forEach(section => {
            section.style.opacity = '0';
            setTimeout(() => {
                section.classList.add('hidden');
            }, 300);
        });
        
        // Show selected section with fade
        setTimeout(() => {
            const selectedSection = document.getElementById(sectionId);
            selectedSection.classList.remove('hidden');
            // Force a reflow
            selectedSection.offsetHeight;
            selectedSection.style.opacity = '1';
            
            // Maintain scroll position
            window.scrollTo({
                top: document.querySelector('.max-w-7xl').offsetTop,
                behavior: 'smooth'
            });
        }, 300);
        
        // Update active state of buttons
        document.querySelectorAll('.section-btn').forEach(btn => {
            if (btn.dataset.section === sectionId) {
                btn.classList.add('bg-blue-50', 'text-blue-700');
            } else {
                btn.classList.remove('bg-blue-50', 'text-blue-700');
            }
        });
        
        localStorage.setItem('activeSection', sectionId);
    }

    function validatePhone(input) {
        // Remove any non-numeric characters
        let value = input.value.replace(/\D/g, '');
        
        // If first digit is entered and it's not 6-9, remove it
        if (value.length > 0 && !'6789'.includes(value[0])) {
            value = '';
        }
        
        // Limit to 10 digits
        value = value.substring(0, 10);
        
        // Update input value
        input.value = value;
    }
    </script>
</body>
</html>