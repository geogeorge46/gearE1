<?php
include 'config.php';
session_start();

// Check if user is logged in as manager
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'manager') {
    header('Location: login.php');
    exit();
}

// Handle review status updates if needed
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['review_id'])) {
    $review_id = $_POST['review_id'];
    $status = $_POST['status'];
    $reviewed = 1;

    $update_query = "UPDATE reviews SET status = ?, reviewed = ? WHERE review_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("sii", $status, $reviewed, $review_id);
    
    if (!$stmt->execute()) {
        $error = "Error updating review: " . $conn->error;
    }
}

// Fetch all reviews with error handling
try {
    $reviews_query = "SELECT r.*, u.full_name as user_name, m.name as machine_name, 
                      DATE_FORMAT(r.created_at, '%M %d, %Y') as formatted_date,
                      m.daily_rate, m.image_url
                      FROM reviews r
                      JOIN users u ON r.user_id = u.user_id
                      JOIN machines m ON r.machine_id = m.machine_id
                      ORDER BY r.created_at DESC";
    
    if (!$reviews = $conn->query($reviews_query)) {
        throw new Exception("Error fetching reviews: " . $conn->error);
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviews - GEAR EQUIP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .star-rating {
            color: #FFD700;
        }
        .review-card {
            transition: all 0.3s ease;
        }
        .review-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Side Navigation -->
    <div class="fixed left-0 top-0 w-64 h-full bg-white shadow-lg">
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
            <p class="font-semibold text-gray-800">Manager</p>
        </div>

        <!-- Navigation Links -->
        <nav class="p-4">
            <div class="space-y-4">
                <a href="manager_dashboard.php" class="block px-4 py-2 rounded-lg hover:bg-gray-50 text-gray-700">
                    <div class="flex items-center">
                        <i class="fas fa-home w-6"></i>
                        <span>Overview</span>
                    </div>
                </a>
                <a href="manager_reviewed.php" class="block px-4 py-2 rounded-lg hover:bg-gray-50 text-gray-700">
                    <div class="flex items-center">
                        <i class="fas fa-star w-6"></i>
                        <span>Review Management</span>
                    </div>
                </a>
                <a href="manager_reviewed.php" class="block px-4 py-2 rounded-lg bg-blue-50 text-blue-700">
                    <div class="flex items-center">
                        <i class="fas fa-comments w-6"></i>
                        <span>Reviews</span>
                    </div>
                </a>
            </div>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="ml-64 p-8">
        <!-- Error Display if any -->
        <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <div class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-bold text-gray-800">All Reviews</h1>
            <div class="bg-white rounded-lg shadow px-4 py-2">
                <span class="text-sm text-gray-500">Total Reviews:</span>
                <span class="font-bold text-blue-600 ml-2">
                    <?php echo isset($reviews) ? $reviews->num_rows : 0; ?>
                </span>
            </div>
        </div>

        <!-- Reviews Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if (isset($reviews) && $reviews->num_rows > 0): 
                while ($review = $reviews->fetch_assoc()): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                    <div class="p-6">
                        <!-- Machine Info -->
                        <div class="flex items-center mb-4">
                            <?php if ($review['image_url']): ?>
                            <img src="<?php echo htmlspecialchars($review['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($review['machine_name']); ?>"
                                 class="w-16 h-16 object-cover rounded-lg mr-4">
                            <?php endif; ?>
                            <div>
                                <h3 class="font-semibold text-lg text-gray-800">
                                    <?php echo htmlspecialchars($review['machine_name']); ?>
                                </h3>
                                <p class="text-sm text-gray-500">
                                    ₹<?php echo number_format($review['daily_rate'], 2); ?> / day
                                </p>
                            </div>
                        </div>

                        <!-- User Info and Rating -->
                        <div class="mb-4">
                            <div class="flex justify-between items-center">
                                <p class="text-sm font-medium text-gray-600">
                                    By <?php echo htmlspecialchars($review['user_name']); ?>
                                </p>
                                <div class="flex text-yellow-400">
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'text-yellow-400' : 'text-gray-300'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 mt-1"><?php echo $review['formatted_date']; ?></p>
                        </div>

                        <!-- Review Comment -->
                        <div class="mb-4">
                            <p class="text-gray-600"><?php echo htmlspecialchars($review['comment']); ?></p>
                        </div>

                        <!-- Review Status -->
                        <div class="flex justify-between items-center pt-4 border-t">
                            <span class="px-3 py-1 rounded-full text-sm <?php 
                                echo $review['status'] == 'approved' ? 'bg-green-100 text-green-800' : 
                                    ($review['status'] == 'rejected' ? 'bg-red-100 text-red-800' : 
                                    'bg-yellow-100 text-yellow-800'); ?>">
                                <?php echo ucfirst($review['status']); ?>
                            </span>
                            <span class="text-sm text-gray-500">
                                <?php echo $review['reviewed'] ? 'Reviewed' : 'Pending Review'; ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endwhile; 
            else: ?>
                <div class="col-span-3 text-center py-8 text-gray-500">
                    No reviews found.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Add any JavaScript functionality here
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-hide success messages after 3 seconds
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    alert.style.display = 'none';
                });
            }, 3000);
        });
    </script>
</body>
</html> 