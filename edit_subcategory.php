<?php
include 'config.php';
session_start();

// Check if user is logged in as manager
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'manager') {
    header('Location: login.php');
    exit();
}

// Get subcategory ID from URL
$subcategory_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$subcategory_id) {
    header('Location: manage_subcategories.php');
    exit();
}

// Handle image serving if 'image' parameter is present
if (isset($_GET['image'])) {
    $query = "SELECT image_data FROM subcategories WHERE subcategory_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $subcategory_id);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($image_data);
        $stmt->fetch();
        header("Content-Type: image/jpeg");
        echo $image_data;
        exit();
    }
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $category_id = mysqli_real_escape_string($conn, $_POST['category_id']);
    $subcategory_name = mysqli_real_escape_string($conn, $_POST['subcategory_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    
    // Start building the update query
    $query = "UPDATE subcategories SET 
              category_id = ?,
              subcategory_name = ?,
              description = ?";
    $params = [$category_id, $subcategory_name, $description];
    $types = "iss";
    
    // Handle image upload if new image is provided
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['image']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        if (in_array(strtolower($filetype), $allowed)) {
            $image_data = file_get_contents($_FILES['image']['tmp_name']);
            $query .= ", image_data = ?";
            $params[] = $image_data;
            $types .= "s";
        }
    }
    
    $query .= " WHERE subcategory_id = ?";
    $params[] = $subcategory_id;
    $types .= "i";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        header('Location: manage_subcategories.php');
        exit();
    } else {
        $error_message = "Error updating subcategory: " . $stmt->error;
    }
}

// Get existing subcategory data
$query = "SELECT * FROM subcategories WHERE subcategory_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $subcategory_id);
$stmt->execute();
$result = $stmt->get_result();
$subcategory = $result->fetch_assoc();

if (!$subcategory) {
    header('Location: manage_subcategories.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Subcategory - GEAR EQUIP</title>
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
        <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-md p-6">
            <h1 class="text-2xl font-bold mb-6">Edit Subcategory</h1>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Select Category
                    </label>
                    <select name="category_id" required 
                            class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <?php
                        $categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY category_name");
                        while($category = mysqli_fetch_assoc($categories)) {
                            $selected = ($category['category_id'] == $subcategory['category_id']) ? 'selected' : '';
                            echo "<option value='" . $category['category_id'] . "' $selected>" . 
                                 htmlspecialchars($category['category_name']) . "</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Subcategory Name
                    </label>
                    <input type="text" 
                           name="subcategory_name" 
                           value="<?php echo htmlspecialchars($subcategory['subcategory_name']); ?>"
                           required 
                           class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Description
                    </label>
                    <textarea name="description" 
                              rows="3" 
                              class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    ><?php echo htmlspecialchars($subcategory['description']); ?></textarea>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Current Image
                    </label>
                    <?php if ($subcategory['image_data']): ?>
                        <img src="<?php echo $_SERVER['PHP_SELF'] . '?id=' . $subcategory_id . '&image=1'; ?>" 
                             alt="Current subcategory image"
                             class="preview-image mb-4">
                    <?php else: ?>
                        <div class="w-32 h-32 bg-gray-200 rounded flex items-center justify-center mb-4">
                            <span class="text-gray-500">No image</span>
                        </div>
                    <?php endif; ?>

                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Upload New Image
                    </label>
                    <input type="file" 
                           name="image" 
                           accept="image/*"
                           class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                           onchange="previewImage(this)">
                    <div id="imagePreview" class="mt-2"></div>
                </div>

                <div class="flex justify-end gap-4">
                    <a href="manage_subcategories.php" 
                       class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Update Subcategory
                    </button>
                </div>
            </form>
        </div>
    </div>

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