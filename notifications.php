<?php
include 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Mark notifications as read when viewed
$mark_read = "UPDATE user_notifications SET is_read = TRUE WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $mark_read);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - GEAR EQUIP</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <?php include 'nav.php'; ?>

    <div class="container mx-auto px-4 py-8 mt-16">
        <h1 class="text-2xl font-bold mb-6">Your Notifications</h1>

        <div class="space-y-4">
            <?php
            $query = "SELECT * FROM user_notifications 
                      WHERE user_id = ? 
                      ORDER BY created_at DESC";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            while ($notification = mysqli_fetch_assoc($result)):
            ?>
                <div class="bg-white rounded-lg shadow p-4 <?php echo $notification['is_read'] ? 'opacity-75' : ''; ?>">
                    <p class="text-gray-800"><?php echo htmlspecialchars($notification['message']); ?></p>
                    <p class="text-sm text-gray-500 mt-2">
                        <?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?>
                    </p>
                </div>
            <?php endwhile; ?>

            <?php if (mysqli_num_rows($result) === 0): ?>
                <p class="text-center text-gray-500">No notifications yet.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 