<?php
include 'config.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get all notifications for the user
$query = "SELECT * FROM user_notifications WHERE user_id = ? ORDER BY created_at DESC";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Mark notifications as read
$mark_read = "UPDATE user_notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0";
$stmt = mysqli_prepare($conn, $mark_read);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - GEAR EQUIP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-100 font-[Poppins]">
    <?php include 'nav.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8">Notifications</h1>
        
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <div class="divide-y divide-gray-200">
                    <?php while ($notification = mysqli_fetch_assoc($result)): ?>
                        <div class="p-4 hover:bg-gray-50 transition-colors">
                            <p class="text-gray-800"><?php echo htmlspecialchars($notification['message']); ?></p>
                            <p class="text-sm text-gray-500 mt-1">
                                <?php echo date('M d, Y h:i A', strtotime($notification['created_at'])); ?>
                            </p>
                            
                            <?php if ($notification['type'] === 'additional_payment_needed'): ?>
                                <div class="mt-2">
                                    <a href="additional_payment.php?rental_id=<?php echo $notification['related_id']; ?>" 
                                       class="inline-block bg-blue-500 hover:bg-blue-600 text-white py-1 px-3 rounded text-sm transition-colors">
                                        Make Payment
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="p-8 text-center">
                    <p class="text-gray-500">No notifications yet</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include 'footer.php'; ?>
</body>
</html> 