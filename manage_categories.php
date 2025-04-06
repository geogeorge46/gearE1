<?php
include 'config.php';
session_start();

// Check if user is logged in as manager
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'manager') {
    header('Location: login.php');
    exit();
}

// Handle form submission for new category
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['action'])) {
    $errors = [];
    
    // Validate category name
    if(!isset($_POST['category_name']) || empty(trim($_POST['category_name']))) {
        $errors[] = "Category name is required";
    }
    
    // Validate description
    if(!isset($_POST['description']) || empty(trim($_POST['description']))) {
        $errors[] = "Description is required";
    }
    
    // Validate image
    if(!isset($_FILES['image_data']) || $_FILES['image_data']['error'] != 0) {
        $errors[] = "Category image is required";
    }
    
    // If no errors, proceed with insertion
    if(empty($errors)) {
        $category_name = mysqli_real_escape_string($conn, $_POST['category_name']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        
        // Handle image upload
        if(isset($_FILES['image_data']) && $_FILES['image_data']['error'] == 0) {
            $image_data = file_get_contents($_FILES['image_data']['tmp_name']);
            $image_data = mysqli_real_escape_string($conn, $image_data);
        }
        
        $query = "INSERT INTO categories (category_name, description, image_data) 
                  VALUES ('$category_name', '$description', '$image_data')";
        
        if (mysqli_query($conn, $query)) {
            $success_message = "New category '$category_name' added successfully! 🎉";
        } else {
            $error_message = "Error adding category: " . mysqli_error($conn);
        }
    }
}

// Add this at the top of your PHP file to handle messages
if(isset($_POST['action']) && $_POST['action'] == 'delete') {
    $category_id = mysqli_real_escape_string($conn, $_POST['category_id']);
    $query = "DELETE FROM categories WHERE category_id = '$category_id'";
    if(mysqli_query($conn, $query)) {
        $success_message = "Category deleted successfully! 🗑️";
    } else {
        $error_message = "Error deleting category: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Machine Categories - GEAR EQUIP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
    .success-message {
        animation: slideIn 0.5s ease-out, fadeOut 0.5s ease-out 3s forwards;
        position: fixed;
        top: 20px;
        right: 20px;
        background-color: #34D399;
        color: white;
        padding: 1rem;
        border-radius: 0.5rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        z-index: 50;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes fadeOut {
        from {
            opacity: 1;
        }
        to {
            opacity: 0;
            visibility: hidden;
        }
    }

    .success-icon {
        animation: bounce 0.5s ease-in-out;
    }

    @keyframes bounce {
        0%, 100% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.2);
        }
    }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <!-- Back Button -->
        <div class="mb-6">
            <a href="managerstore.php" class="inline-flex items-center text-blue-600 hover:text-blue-800">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Store
            </a>
        </div>

        <!-- Top Navigation -->
        <div class="border-b mb-8">
            <div class="flex space-x-6 px-8 py-4">
                <a href="manage_categories.php" class="text-blue-600 font-medium border-b-2 border-blue-600">
                    Category
                </a>
                <a href="manage_subcategories.php" class="text-gray-600 hover:text-blue-600 font-medium">
                    Sub Category
                </a>
                <a href="manage_machines.php" class="text-gray-600 hover:text-blue-600 font-medium">
                    Machines
                </a>
                <a href="store_machines.php" class="text-gray-600 hover:text-blue-600 font-medium">
                    Store Machines
                </a>
            </div>
        </div>

        <!-- Add Category Form -->
        <?php if(!empty($errors)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                <ul>
                    <?php foreach($errors as $error): ?>
                        <li>• <?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">Add Machine Category</h2>
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Category Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               name="category_name" 
                               required 
                               class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Category Image <span class="text-red-500">*</span>
                        </label>
                        <input type="file" 
                               name="image_data" 
                               accept="image/*" 
                               required
                               class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Description <span class="text-red-500">*</span>
                    </label>
                    <textarea name="description" 
                              rows="3" 
                              required
                              class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                <div class="mt-6">
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        Add Category
                    </button>
                </div>
            </form>
        </div>

        <!-- Categories List -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b">
                <h2 class="text-xl font-semibold">Machine Categories</h2>
            </div>
            <div class="p-6">
                <table class="w-full">
                    <thead>
                        <tr class="text-left border-b">
                            <th class="pb-4 font-medium">Category</th>
                            <th class="pb-4 font-medium">Description</th>
                            <th class="pb-4 font-medium">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT * FROM categories ORDER BY category_name";
                        $result = mysqli_query($conn, $query);
                        
                        while($row = mysqli_fetch_assoc($result)) {
                            echo '<tr class="border-b hover:bg-gray-50">';
                            echo '<td class="py-4">' . htmlspecialchars($row['category_name']) . '</td>';
                            echo '<td class="py-4">' . htmlspecialchars($row['description']) . '</td>';
                            echo '<td class="py-4 flex space-x-3">';
                            
                            // Edit button with icon
                            echo '<a href="edit_category.php?id=' . $row['category_id'] . '" 
                                    class="text-blue-600 hover:text-blue-800" title="Edit">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </a>';
                            
                            // Delete button with icon
                            echo '<form method="POST" style="display: inline;" onsubmit="return confirmDelete()">';
                            echo '<input type="hidden" name="category_id" value="' . $row['category_id'] . '">';
                            echo '<input type="hidden" name="action" value="delete">';
                            echo '<button type="submit" class="text-red-600 hover:text-red-800" title="Delete">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                  </button>';
                            echo '</form>';
                            
                            echo '</td>';
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Success Message -->
        <?php if(isset($success_message)): ?>
        <div class="success-message" id="successMessage">
            <svg class="success-icon w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" 
                      stroke-linejoin="round" 
                      stroke-width="2" 
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z">
                </path>
            </svg>
            <span class="font-medium"><?php echo $success_message; ?></span>
        </div>
        <?php endif; ?>
    </div>

    <!-- Add this JavaScript for delete confirmation -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-hide messages after 3.5 seconds
        const messages = document.querySelectorAll('.message-popup');
        messages.forEach(message => {
            setTimeout(() => {
                message.remove();
            }, 3500);
        });
    });

    function confirmDelete() {
        return confirm('Are you sure you want to delete this category? This action cannot be undone.');
    }
    </script>
</body>
</html>