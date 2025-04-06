<?php
include 'config.php';
session_start();

// Handle image serving if 'image' parameter is present
if (isset($_GET['image'])) {
    $id = (int)$_GET['image'];
    
    $query = "SELECT image_data FROM subcategories WHERE subcategory_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($image_data);
        $stmt->fetch();
        
        header("Content-Type: image/jpeg");
        echo $image_data;
        exit(); // Important: stop execution after serving image
    }
    exit();
}

// Check if user is logged in as manager
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'manager') {
    header('Location: login.php');
    exit();
}

// Handle form submission for new subcategory
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $category_id = mysqli_real_escape_string($conn, $_POST['category_id']);
    $subcategory_name = mysqli_real_escape_string($conn, $_POST['subcategory_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    
    // Handle image upload
    $image_data = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['image']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        if (in_array(strtolower($filetype), $allowed)) {
            $image_data = file_get_contents($_FILES['image']['tmp_name']);
        }
    }
    
    // Insert subcategory with image
    $query = "INSERT INTO subcategories (category_id, subcategory_name, description, image_data) 
              VALUES (?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isss", $category_id, $subcategory_name, $description, $image_data);
    
    if ($stmt->execute()) {
        $success_message = "Subcategory added successfully!";
    } else {
        $error_message = "Error adding subcategory: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Machine Subcategories - GEAR EQUIP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .preview-image {
            max-width: 200px;
            max-height: 200px;
            object-fit: cover;
            border-radius: 8px;
            margin-top: 10px;
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
                <a href="manage_categories.php" class="text-gray-600 hover:text-blue-600 font-medium">
                    Category
                </a>
                <a href="manage_subcategories.php" class="text-blue-600 font-medium border-b-2 border-blue-600">
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

        <!-- Add Subcategory Form -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">Add Machine Subcategory</h2>
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Select Category
                        </label>
                        <select name="category_id" required 
                                class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Choose Category</option>
                            <?php
                            $categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY category_name");
                            while($category = mysqli_fetch_assoc($categories)) {
                                echo "<option value='" . $category['category_id'] . "'>" . 
                                     htmlspecialchars($category['category_name']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Subcategory Name
                        </label>
                        <input type="text" 
                               name="subcategory_name" 
                               required 
                               class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Description
                    </label>
                    <textarea name="description" 
                              rows="3" 
                              class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>

                <!-- Add image upload field -->
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Subcategory Image
                    </label>
                    <input type="file" 
                           name="image" 
                           accept="image/*"
                           class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                           onchange="previewImage(this)">
                    <div id="imagePreview" class="mt-2"></div>
                </div>

                <div class="mt-6">
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        Add Subcategory
                    </button>
                </div>
            </form>
        </div>

        <!-- Subcategories List -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b">
                <h2 class="text-xl font-semibold">Machine Subcategories</h2>
            </div>
            <div class="p-6">
                <table class="w-full">
                    <thead>
                        <tr class="text-left border-b">
                            <th class="pb-4 font-medium">Image</th>
                            <th class="pb-4 font-medium">Category</th>
                            <th class="pb-4 font-medium">Subcategory</th>
                            <th class="pb-4 font-medium">Description</th>
                            <th class="pb-4 font-medium">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT s.*, c.category_name 
                                 FROM subcategories s 
                                 JOIN categories c ON s.category_id = c.category_id 
                                 ORDER BY c.category_name, s.subcategory_name";
                        $result = mysqli_query($conn, $query);
                        
                        while($row = mysqli_fetch_assoc($result)) {
                            echo '<tr class="border-b">';
                            echo '<td class="py-4">';
                            if ($row['image_data']) {
                                echo '<img src="manage_subcategories.php?image=' . $row['subcategory_id'] . '" 
                                       alt="' . htmlspecialchars($row['subcategory_name']) . '"
                                       class="w-16 h-16 object-cover rounded">';
                            } else {
                                echo '<div class="w-16 h-16 bg-gray-200 rounded flex items-center justify-center">
                                        <span class="text-gray-500">No image</span>
                                      </div>';
                            }
                            echo '</td>';
                            echo '<td class="py-4">' . htmlspecialchars($row['category_name']) . '</td>';
                            echo '<td class="py-4">' . htmlspecialchars($row['subcategory_name']) . '</td>';
                            echo '<td class="py-4">' . htmlspecialchars($row['description']) . '</td>';
                            echo '<td class="py-4">';
                            echo '<a href="edit_subcategory.php?id=' . $row['subcategory_id'] . '" 
                                    class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700">Edit</a>';
                            echo '</td>';
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add this script section before closing body tag -->
    <script>
    function previewImage(input) {
        const preview = document.getElementById('imagePreview');
        preview.innerHTML = '';
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'preview-image';
                preview.appendChild(img);
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    </script>
</body>
</html> 