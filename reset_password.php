<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

$message = '';
$message_type = '';
$valid_token = false;
$token = isset($_GET['token']) ? $_GET['token'] : '';

if (!empty($token)) {
    // Convert times to UTC for comparison
    $stmt = mysqli_prepare($conn, 
        "SELECT t.*, u.email, u.full_name 
         FROM password_reset_tokens t 
         JOIN users u ON t.user_id = u.user_id 
         WHERE t.token = ? AND t.expiry > UTC_TIMESTAMP()");
    
    if (!$stmt) {
        die("Error preparing statement: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "s", $token);
    
    if (!mysqli_stmt_execute($stmt)) {
        die("Error executing statement: " . mysqli_error($conn));
    }
    
    $result = mysqli_stmt_get_result($stmt);
    
    if ($token_data = mysqli_fetch_assoc($result)) {
        $valid_token = true;
        
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $password = trim($_POST['password']);
            $confirm_password = trim($_POST['confirm_password']);
            
            // Enhanced password validation
            $uppercase = preg_match('@[A-Z]@', $password);
            $lowercase = preg_match('@[a-z]@', $password);
            $number    = preg_match('@[0-9]@', $password);
            $specialChars = preg_match('@[^\w]@', $password);
            
            if (strlen($password) < 8) {
                $message = "Password must be at least 8 characters long.";
                $message_type = 'error';
            } elseif (!$uppercase || !$lowercase || !$number || !$specialChars) {
                $message = "Password must include at least one uppercase letter, one lowercase letter, one number, and one special character.";
                $message_type = 'error';
            } elseif ($password !== $confirm_password) {
                $message = "Passwords do not match.";
                $message_type = 'error';
            } else {
                // Update password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $update_stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE user_id = ?");
                mysqli_stmt_bind_param($update_stmt, "si", $hashed_password, $token_data['user_id']);
                
                if (mysqli_stmt_execute($update_stmt)) {
                    // Delete used token
                    $delete_stmt = mysqli_prepare($conn, "DELETE FROM password_reset_tokens WHERE token = ?");
                    mysqli_stmt_bind_param($delete_stmt, "s", $token);
                    mysqli_stmt_execute($delete_stmt);
                    
                    $message = "Password has been reset successfully. You can now <a href='login.php' class='text-blue-500 hover:text-blue-800'>login</a> with your new password.";
                    $message_type = 'success';
                    
                    // Clear the form by setting valid_token to false
                    $valid_token = false;
                } else {
                    $message = "Error updating password. Please try again.";
                    $message_type = 'error';
                }
            }
        }
    } else {
        $message = "This password reset link has expired or is invalid. Please request a new one from the <a href='forgot_password.php' class='text-blue-500 hover:text-blue-800'>forgot password page</a>.";
        $message_type = 'error';
    }
    mysqli_stmt_close($stmt);
} else {
    $message = "Invalid request. Please request a password reset from the <a href='forgot_password.php' class='text-blue-500 hover:text-blue-800'>forgot password page</a>.";
    $message_type = 'error';
}

// Debug information (remove in production)
if ($message_type === 'error') {
    $debug_query = "SELECT token, expiry, NOW() as current_time FROM password_reset_tokens WHERE token = ?";
    $debug_stmt = mysqli_prepare($conn, $debug_query);
    mysqli_stmt_bind_param($debug_stmt, "s", $token);
    mysqli_stmt_execute($debug_stmt);
    $debug_result = mysqli_stmt_get_result($debug_stmt);
    if ($debug_data = mysqli_fetch_assoc($debug_result)) {
        $message .= "<br><small>Debug: Token exists, expires at " . $debug_data['expiry'] . ", current time is " . $debug_data['current_time'] . "</small>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - GEAR EQUIP</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .password-requirements {
            display: none;
            background: #f8fafc;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-top: 0.5rem;
        }
        .password-requirements.show {
            display: block;
        }
        .requirement {
            color: #64748b;
            margin: 0.25rem 0;
            font-size: 0.875rem;
        }
        .requirement.valid {
            color: #22c55e;
        }
        .requirement.valid::before {
            content: "✓ ";
        }
        .requirement.invalid {
            color: #ef4444;
        }
        .requirement.invalid::before {
            content: "× ";
        }
    </style>
</head>
<body class="bg-gray-100 font-[Poppins]">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-lg w-96">
            <div class="text-center mb-8">
                <img src="images/logo.png" alt="GEAR EQUIP" class="h-12 mx-auto mb-4">
                <h2 class="text-2xl font-bold text-gray-800">Reset Password</h2>
                <p class="text-gray-600">Enter your new password</p>
            </div>

            <?php if (!empty($message)): ?>
                <div class="<?php echo $message_type === 'error' ? 'bg-red-100 border-red-400 text-red-700' : 
                                  'bg-green-100 border-green-400 text-green-700'; ?> 
                            px-4 py-3 rounded mb-4 border">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if ($valid_token): ?>
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?token=' . htmlspecialchars($token); ?>" 
                      class="space-y-4" id="resetForm">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                            New Password
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                               id="password"
                               type="password"
                               name="password"
                               required
                               minlength="8">
                        <div class="password-requirements" id="passwordRequirements">
                            <div class="requirement" id="length">8+ characters</div>
                            <div class="requirement" id="uppercase">One uppercase letter</div>
                            <div class="requirement" id="lowercase">One lowercase letter</div>
                            <div class="requirement" id="number">One number</div>
                            <div class="requirement" id="special">One special character</div>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="confirm_password">
                            Confirm New Password
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                               id="confirm_password"
                               type="password"
                               name="confirm_password"
                               required>
                        <div class="requirement mt-1" id="passwordMatch"></div>
                    </div>
                    
                    <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full"
                            type="submit"
                            id="submitBtn"
                            disabled>
                        Reset Password
                    </button>
                </form>
            <?php endif; ?>
            
            <div class="text-center mt-4">
                <a href="login.php" class="text-sm text-gray-600 hover:text-gray-800">
                    ← Back to Login
                </a>
            </div>
        </div>
    </div>

    <script>
        const form = document.getElementById('resetForm');
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        const submitBtn = document.getElementById('submitBtn');
        const requirements = document.getElementById('passwordRequirements');
        const passwordMatch = document.getElementById('passwordMatch');

        // Show password requirements when password field is focused
        password.addEventListener('focus', () => {
            requirements.classList.add('show');
        });

        function validatePassword() {
            const value = password.value;
            let valid = true;

            // Check length
            const lengthReq = document.getElementById('length');
            if (value.length >= 8) {
                lengthReq.classList.add('valid');
                lengthReq.classList.remove('invalid');
            } else {
                lengthReq.classList.add('invalid');
                lengthReq.classList.remove('valid');
                valid = false;
            }

            // Check uppercase
            const upperReq = document.getElementById('uppercase');
            if (/[A-Z]/.test(value)) {
                upperReq.classList.add('valid');
                upperReq.classList.remove('invalid');
            } else {
                upperReq.classList.add('invalid');
                upperReq.classList.remove('valid');
                valid = false;
            }

            // Check lowercase
            const lowerReq = document.getElementById('lowercase');
            if (/[a-z]/.test(value)) {
                lowerReq.classList.add('valid');
                lowerReq.classList.remove('invalid');
            } else {
                lowerReq.classList.add('invalid');
                lowerReq.classList.remove('valid');
                valid = false;
            }

            // Check number
            const numberReq = document.getElementById('number');
            if (/[0-9]/.test(value)) {
                numberReq.classList.add('valid');
                numberReq.classList.remove('invalid');
            } else {
                numberReq.classList.add('invalid');
                numberReq.classList.remove('valid');
                valid = false;
            }

            // Check special character
            const specialReq = document.getElementById('special');
            if (/[^A-Za-z0-9]/.test(value)) {
                specialReq.classList.add('valid');
                specialReq.classList.remove('invalid');
            } else {
                specialReq.classList.add('invalid');
                specialReq.classList.remove('valid');
                valid = false;
            }

            return valid;
        }

        function validateConfirmPassword() {
            if (confirmPassword.value === password.value && confirmPassword.value !== '') {
                passwordMatch.textContent = "Passwords match";
                passwordMatch.classList.add('valid');
                passwordMatch.classList.remove('invalid');
                return true;
            } else {
                passwordMatch.textContent = "Passwords do not match";
                passwordMatch.classList.add('invalid');
                passwordMatch.classList.remove('valid');
                return false;
            }
        }

        function updateSubmitButton() {
            submitBtn.disabled = !(validatePassword() && validateConfirmPassword());
        }

        password.addEventListener('input', () => {
            validatePassword();
            validateConfirmPassword();
            updateSubmitButton();
        });

        confirmPassword.addEventListener('input', () => {
            validateConfirmPassword();
            updateSubmitButton();
        });

        // Prevent form submission if validation fails
        form?.addEventListener('submit', (e) => {
            if (!(validatePassword() && validateConfirmPassword())) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
