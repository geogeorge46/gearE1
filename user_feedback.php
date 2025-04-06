<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate that we have all required fields
    if (empty($_POST['rental_id']) || empty($_POST['machine_id']) || 
        empty($_POST['rating']) || empty($_POST['comment'])) {
        $error = "All fields are required.";
    } else {
        $rental_id = $_POST['rental_id'];
        $machine_id = $_POST['machine_id'];
        $rating = $_POST['rating'];
        $comment = $_POST['comment'];
        $user_id = $_SESSION['user_id'];

        try {
            // Start transaction
            $conn->begin_transaction();

            // Check if rental exists and hasn't been reviewed
            $check_query = "SELECT rental_id FROM rentals 
                           WHERE rental_id = ? AND user_id = ? AND status = 'completed'
                           AND NOT EXISTS (
                               SELECT 1 FROM reviews WHERE rental_id = ?
                           )";
            $stmt = $conn->prepare($check_query);
            $stmt->bind_param("iii", $rental_id, $user_id, $rental_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // Insert the review
                $insert_query = "INSERT INTO reviews (rental_id, user_id, machine_id, rating, comment, created_at, status) 
                                VALUES (?, ?, ?, ?, ?, NOW(), 'pending')";
                $stmt = $conn->prepare($insert_query);
                $stmt->bind_param("iiiss", $rental_id, $user_id, $machine_id, $rating, $comment);
                
                if ($stmt->execute()) {
                    // Update rental status
                    $update_query = "UPDATE rentals SET status = 'reviewed' WHERE rental_id = ?";
                    $stmt = $conn->prepare($update_query);
                    $stmt->bind_param("i", $rental_id);
                    
                    if ($stmt->execute()) {
                        $conn->commit();
                        header("Location: user_feedback.php?success=1");
                        exit();
                    }
                }
            } else {
                $error = "Invalid rental selection or rental already reviewed.";
            }
            
            $conn->rollback();
            if (!isset($error)) {
                $error = "Failed to submit feedback. Please try again.";
            }
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = "An error occurred. Please try again.";
        }
    }
}

// Display error message if there is one
if (isset($error)): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
        <span class="block sm:inline"><?php echo $error; ?></span>
    </div>
<?php endif;

// Get user's completed rentals for feedback
$user_id = $_SESSION['user_id'];
$rentals_query = "SELECT r.*, m.name as machine_name, m.machine_id 
                  FROM rentals r 
                  JOIN machines m ON r.machine_id = m.machine_id 
                  WHERE r.user_id = ? 
                  AND r.status = 'completed' 
                  AND NOT EXISTS (
                      SELECT 1 
                      FROM reviews rev 
                      WHERE rev.rental_id = r.rental_id
                  )
                  ORDER BY r.created_at DESC";

$stmt = $conn->prepare($rentals_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$rentals = $stmt->get_result();

// Add debug output to check if we're getting rentals
if ($rentals->num_rows === 0) {
    echo '<div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-4">
            No completed rentals available for feedback.
          </div>';
}

// Get user's existing reviews
$reviews_query = "SELECT r.*, m.name as machine_name 
                  FROM reviews r
                  JOIN machines m ON r.machine_id = m.machine_id
                  WHERE r.user_id = ?
                  ORDER BY r.created_at DESC";

$stmt = $conn->prepare($reviews_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$reviews = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Feedback - GEAR EQUIP</title>
    <link href="styles.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
    <div class="sidebar">
        <h2>User Dashboard</h2>
        <a href="user_rentals.php">User Rentals</a>
        <a href="cart.php">Cart</a>
        <a href="user_feedback.php" class="active">Feedback</a>
    </div>

    <div class="content">
        <?php include 'nav.php'; ?>

        <?php if (isset($_GET['success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">Feedback submitted successfully!</span>
        </div>
        <?php endif; ?>

        <div class="container mx-auto px-4 py-8 mt-32">
            <div class="flex justify-between items-center mb-8">
                <a href="user_rentals.php" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition duration-300">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Rentals
                </a>
                <h1 class="text-3xl font-bold">Provide Feedback</h1>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <form method="POST" class="space-y-6">
                    <div>
                        <label for="rental_id" class="block text-sm font-medium text-gray-700">Select Rental</label>
                        <select name="rental_id" id="rental_id" required onchange="updateMachineId(this)"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Select a rental</option>
                            <?php 
                            if ($rentals->num_rows > 0) {
                                while ($rental = $rentals->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $rental['rental_id']; ?>" 
                                        data-machine-id="<?php echo $rental['machine_id']; ?>">
                                    <?php echo htmlspecialchars($rental['machine_name']); ?> 
                                    (<?php echo date('M d, Y', strtotime($rental['start_date'])); ?> - 
                                    <?php echo date('M d, Y', strtotime($rental['end_date'])); ?>)
                                </option>
                            <?php 
                                endwhile;
                            } else {
                            ?>
                                <option value="" disabled>No completed rentals available for feedback</option>
                            <?php } ?>
                        </select>
                    </div>

                    <input type="hidden" name="machine_id" id="machine_id" value="">

                    <div>
                        <label for="rating" class="block text-sm font-medium text-gray-700">Rating</label>
                        <select name="rating" id="rating" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="1">1 Star</option>
                            <option value="2">2 Stars</option>
                            <option value="3">3 Stars</option>
                            <option value="4">4 Stars</option>
                            <option value="5">5 Stars</option>
                        </select>
                    </div>

                    <div>
                        <label for="comment" class="block text-sm font-medium text-gray-700">Comment</label>
                        <textarea name="comment" id="comment" rows="4" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    </div>

                    <div class="flex justify-between items-center space-x-4">
                        <button type="submit" 
                                class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            Submit Feedback
                        </button>

                        <a href="dashboard.php" 
                           class="inline-flex items-center px-6 py-3 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition duration-300">
                            Back to Dashboard
                        </a>
                    </div>
                </form>
            </div>

            <!-- Add JavaScript to handle machine_id -->
            <script>
            function updateMachineId(selectElement) {
                const selectedOption = selectElement.options[selectElement.selectedIndex];
                const machineId = selectedOption.getAttribute('data-machine-id');
                document.getElementById('machine_id').value = machineId;
                
                // For debugging
                console.log('Selected rental ID:', selectElement.value);
                console.log('Selected machine ID:', machineId);
            }

            // Add form validation
            document.querySelector('form').addEventListener('submit', function(e) {
                const rentalId = document.getElementById('rental_id').value;
                const machineId = document.getElementById('machine_id').value;
                
                if (!rentalId || !machineId) {
                    e.preventDefault();
                    alert('Please select a rental before submitting feedback.');
                    return false;
                }
            });
            </script>
        </div>
    </div>
</body>
</html>