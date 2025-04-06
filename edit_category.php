<?php
include 'config.php';
session_start();

// Check if user is logged in as manager
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'manager') {
    header('Location: login.php');
    exit();
}

$category_id = mysqli_real_escape_string($conn, $_GET['id']);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $category_name = mysqli_real_escape_string($conn, $_POST['category_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    
    $update_query = "UPDATE categories SET 
                     category_name = '$category_name', 
                     description = '$description'";

    // Handle image upload if new image is provided
    if(isset($_FILES['image_data']) && $_FILES['image_data']['error'] == 0) {
        $image_data = file_get_contents($_FILES['image_data']['tmp_name']);
        $image_data = mysqli_real_escape_string($conn, $image_data);
        $update_query .= ", image_data = '$image_data'";
    }

    $update_query .= " WHERE category_id = '$category_id'";
    
    if (mysqli_query($conn, $update_query)) {
        $success_message = "Category updated successfully!";
    } else {
        $error_message = "Error updating category: " . mysqli_error($conn);
    }
}

// Fetch category data
$query = "SELECT * FROM categories WHERE category_id = '$category_id'";
$result = mysqli_query($conn, $query);
$category = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Category - GEAR EQUIP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <!-- Main Content -->
    <div class="ml-64 p-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-bold text-gray-800">Edit Category</h1>
            <a href="manage_categories.php" class="text-blue-600 hover:text-blue-800">
                ← Back to Categories
            </a>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow p-6">
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="category_name">
                        Category Name
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                           id="category_name" 
                           name="category_name" 
                           type="text" 
                           value="<?php echo htmlspecialchars($category['category_name']); ?>"
                           required>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="description">
                        Description
                    </label>
                    <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                              id="description" 
                              name="description" 
                              rows="4"><?php echo htmlspecialchars($category['description'] ?? ''); ?></textarea>
                </div>

                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="image_data">
                        Category Image
                    </label>
                    <?php if($category['image_data']): ?>
                        <img src="data:image/jpeg;base64,<?php echo base64_encode($category['image_data']); ?>" 
                             alt="Current Category Image" 
                             class="h-32 w-32 object-cover mb-2">
                        <p class="text-sm text-gray-600 mb-2">Upload new image to replace current one</p>
                    <?php endif; ?>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                           id="image_data" 
                           name="image_data" 
                           type="file" 
                           accept="image/*">
                </div>

                <div class="flex items-center justify-end">
                    <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                            type="submit">
                        Update Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>